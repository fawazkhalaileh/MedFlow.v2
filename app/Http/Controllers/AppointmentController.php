<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Models\Room;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $company = Company::first();
        $date = $request->input('date', today()->toDateString());

        $query = Appointment::with(['patient', 'service', 'assignedStaff', 'branch', 'room'])
            ->where('company_id', $company->id)
            ->whereDate('scheduled_at', $date);

        if ($branchId = $user->scopedBranchId()) {
            $query->where('branch_id', $branchId);
        }

        if ($user->isRole('doctor', 'nurse', 'technician')) {
            $query->where('assigned_staff_id', $user->id);
        }

        if ($request->filled('branch') && $user->isSuperAdmin()) {
            $query->where('branch_id', $request->integer('branch'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('visit_type')) {
            $query->where('visit_type', $request->string('visit_type'));
        }

        $appointments = $query->orderBy('scheduled_at')->paginate(25)->withQueryString();
        $branches = $user->isSuperAdmin()
            ? Branch::orderBy('name')->get()
            : Branch::where('id', $user->primary_branch_id)->get();

        $statuses = [
            Appointment::STATUS_BOOKED,
            Appointment::STATUS_ARRIVED,
            Appointment::STATUS_WAITING_DOCTOR,
            Appointment::STATUS_WAITING_TECHNICIAN,
            Appointment::STATUS_IN_DOCTOR_VISIT,
            Appointment::STATUS_IN_TECHNICIAN_VISIT,
            Appointment::STATUS_COMPLETED_WAITING_CHECKOUT,
            Appointment::STATUS_CHECKED_OUT,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_NO_SHOW,
        ];

        return view('appointments.index', compact('appointments', 'branches', 'statuses', 'date'));
    }

    public function kanban(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->isRole('branch_manager') || $user->isSuperAdmin(), 403);

        $branchId = $user->scopedBranchId() ?? $request->integer('branch');
        $today = today();

        $appointments = Appointment::with(['patient', 'service', 'assignedStaff', 'room'])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereDate('scheduled_at', $today)
            ->orderBy('scheduled_at')
            ->get();

        $columns = [
            'booked' => ['label' => 'Booked', 'color' => '#94a3b8', 'items' => $appointments->whereIn('status', Appointment::bookedStatuses())],
            'waiting_doctor' => ['label' => 'Waiting Doctor', 'color' => '#d97706', 'items' => $appointments->where('status', Appointment::STATUS_WAITING_DOCTOR)],
            'waiting_technician' => ['label' => 'Waiting Technician', 'color' => '#7c3aed', 'items' => $appointments->where('status', Appointment::STATUS_WAITING_TECHNICIAN)],
            'in_visit' => ['label' => 'In Visit', 'color' => '#2563eb', 'items' => $appointments->whereIn('status', [Appointment::STATUS_IN_DOCTOR_VISIT, Appointment::STATUS_IN_TECHNICIAN_VISIT])],
            'checkout' => ['label' => 'Checkout', 'color' => '#059669', 'items' => $appointments->where('status', Appointment::STATUS_COMPLETED_WAITING_CHECKOUT)],
        ];

        $stats = [
            'total' => $appointments->count(),
            'in_clinic' => $appointments->whereIn('status', [
                Appointment::STATUS_ARRIVED,
                Appointment::STATUS_WAITING_DOCTOR,
                Appointment::STATUS_WAITING_TECHNICIAN,
                Appointment::STATUS_IN_DOCTOR_VISIT,
                Appointment::STATUS_IN_TECHNICIAN_VISIT,
            ])->count(),
            'completed' => $appointments->whereIn('status', [Appointment::STATUS_COMPLETED_WAITING_CHECKOUT, Appointment::STATUS_CHECKED_OUT])->count(),
            'no_show' => $appointments->where('status', Appointment::STATUS_NO_SHOW)->count(),
        ];

        $branches = $user->isSuperAdmin() ? Branch::orderBy('name')->get() : collect();

        return view('appointments.kanban', compact('columns', 'stats', 'branches', 'branchId', 'today'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $branchId = $user->scopedBranchId();

        $prePatient = $request->filled('patient_id')
            ? Patient::find($request->integer('patient_id'))
            : null;

        $services = Service::where('is_active', true)->orderBy('name')->get();
        $reasons = AppointmentReason::where('is_active', true)->orderBy('name')->get();
        $rooms = Room::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $doctors = User::query()
            ->where('employment_status', 'active')
            ->whereIn('employee_type', ['doctor', 'nurse'])
            ->when($branchId, fn ($query) => $query->where('primary_branch_id', $branchId))
            ->orderBy('first_name')
            ->get();

        $technicians = User::query()
            ->where('employment_status', 'active')
            ->where('employee_type', 'technician')
            ->when($branchId, fn ($query) => $query->where('primary_branch_id', $branchId))
            ->orderBy('first_name')
            ->get();

        $branches = $user->isSuperAdmin()
            ? Branch::where('status', 'active')->orderBy('name')->get()
            : Branch::where('id', $branchId)->get();

        $patientPackages = PatientPackage::query()
            ->with(['patient', 'package'])
            ->where('company_id', $user->company_id)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', PatientPackage::STATUS_ACTIVE)
            ->orderByDesc('purchased_at')
            ->get();

        return view('appointments.create', compact(
            'prePatient',
            'services',
            'reasons',
            'rooms',
            'doctors',
            'technicians',
            'branches',
            'branchId',
            'patientPackages'
        ));
    }

    public function edit(Appointment $appointment)
    {
        $user = Auth::user();
        $branchId = $user->scopedBranchId();

        if ($branchId) {
            abort_unless($appointment->branch_id === $branchId, 404);
        }

        $appointment->load(['patient', 'service', 'room']);

        $prePatient = $appointment->patient;
        $services = Service::where('is_active', true)->orderBy('name')->get();
        $reasons = AppointmentReason::where('is_active', true)->orderBy('name')->get();
        $rooms = Room::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $doctors = User::query()
            ->where('employment_status', 'active')
            ->whereIn('employee_type', ['doctor', 'nurse'])
            ->when($branchId, fn ($query) => $query->where('primary_branch_id', $branchId))
            ->orderBy('first_name')
            ->get();

        $technicians = User::query()
            ->where('employment_status', 'active')
            ->where('employee_type', 'technician')
            ->when($branchId, fn ($query) => $query->where('primary_branch_id', $branchId))
            ->orderBy('first_name')
            ->get();

        $branches = $user->isSuperAdmin()
            ? Branch::where('status', 'active')->orderBy('name')->get()
            : Branch::where('id', $branchId)->get();

        $patientPackages = PatientPackage::query()
            ->with(['patient', 'package'])
            ->where('company_id', $user->company_id)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', PatientPackage::STATUS_ACTIVE)
            ->orderByDesc('purchased_at')
            ->get();

        return view('appointments.create', compact(
            'appointment',
            'prePatient',
            'services',
            'reasons',
            'rooms',
            'doctors',
            'technicians',
            'branches',
            'branchId',
            'patientPackages'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'service_id' => ['required', 'exists:services,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'assigned_staff_id' => ['required', 'exists:users,id'],
            'patient_package_id' => ['nullable', 'exists:patient_packages,id'],
            'visit_type' => ['required', Rule::in([Appointment::VISIT_TYPE_DOCTOR, Appointment::VISIT_TYPE_TECHNICIAN])],
            'reason_id' => ['nullable', 'exists:appointment_reasons,id'],
            'reason_notes' => ['nullable', 'string', 'max:500'],
            'front_desk_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $scheduledAt = now()->parse($data['scheduled_date'] . ' ' . $data['scheduled_time']);

        if ($scopedBranchId = $user->scopedBranchId()) {
            abort_unless((int) $data['branch_id'] === (int) $scopedBranchId, 404);
        }

        $service = Service::findOrFail((int) $data['service_id']);
        $duration = $data['duration_minutes'] ?? $service->duration_minutes ?? 60;
        $company = Company::first();

        $patient = Patient::query()
            ->where('company_id', $company->id)
            ->where('branch_id', $data['branch_id'])
            ->findOrFail((int) $data['patient_id']);

        $provider = User::query()
            ->where('company_id', $company->id)
            ->where('primary_branch_id', $data['branch_id'])
            ->findOrFail((int) $data['assigned_staff_id']);

        $expectedTypes = $data['visit_type'] === Appointment::VISIT_TYPE_DOCTOR
            ? ['doctor', 'nurse']
            : ['technician'];

        if (!in_array($provider->employee_type, $expectedTypes, true)) {
            return back()
                ->withErrors(['assigned_staff_id' => 'The selected provider does not match the visit type.'])
                ->withInput();
        }

        $patientPackage = null;
        if (!empty($data['patient_package_id'])) {
            $patientPackage = PatientPackage::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $data['branch_id'])
                ->findOrFail((int) $data['patient_package_id']);

            if ($patientPackage->patient_id !== (int) $data['patient_id']
                || $patientPackage->package?->service_id !== (int) $data['service_id']
                || !$patientPackage->isUsable()
            ) {
                return back()
                    ->withErrors(['patient_package_id' => 'Selected package must match the patient, branch, and service.'])
                    ->withInput();
            }
        }

        $appointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $data['branch_id'],
            'patient_id' => $patient->id,
            'patient_package_id' => $patientPackage?->id,
            'service_id' => $service->id,
            'room_id' => $data['room_id'] ?? null,
            'assigned_staff_id' => $provider->id,
            'booked_by' => $user->id,
            'reason_id' => $data['reason_id'] ?? null,
            'reason_notes' => $data['reason_notes'] ?? null,
            'front_desk_note' => $data['front_desk_note'] ?? null,
            'appointment_type' => 'booked',
            'visit_type' => $data['visit_type'],
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $duration,
            'status' => Appointment::STATUS_BOOKED,
        ]);

        $redirect = $user->isRole('secretary') ? route('front-desk') : route('appointments.index');

        return redirect($redirect)
            ->with('success', "Appointment booked for {$appointment->patient->full_name} on {$scheduledAt->format('d M Y \\a\\t h:i A')}.");
    }

    public function update(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        $branchId = $user->scopedBranchId();

        if ($branchId) {
            abort_unless($appointment->branch_id === $branchId, 404);
        }

        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'service_id' => ['required', 'exists:services,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'assigned_staff_id' => ['required', 'exists:users,id'],
            'patient_package_id' => ['nullable', 'exists:patient_packages,id'],
            'visit_type' => ['required', Rule::in([Appointment::VISIT_TYPE_DOCTOR, Appointment::VISIT_TYPE_TECHNICIAN])],
            'reason_id' => ['nullable', 'exists:appointment_reasons,id'],
            'reason_notes' => ['nullable', 'string', 'max:500'],
            'front_desk_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $scheduledAt = now()->parse($data['scheduled_date'] . ' ' . $data['scheduled_time']);

        if ($branchId) {
            abort_unless((int) $data['branch_id'] === (int) $branchId, 404);
        }

        $company = Company::first();
        $service = Service::findOrFail((int) $data['service_id']);
        $duration = $data['duration_minutes'] ?? $service->duration_minutes ?? 60;

        Patient::query()
            ->where('company_id', $company->id)
            ->where('branch_id', $data['branch_id'])
            ->findOrFail((int) $data['patient_id']);

        $provider = User::query()
            ->where('company_id', $company->id)
            ->where('primary_branch_id', $data['branch_id'])
            ->findOrFail((int) $data['assigned_staff_id']);

        $expectedTypes = $data['visit_type'] === Appointment::VISIT_TYPE_DOCTOR
            ? ['doctor', 'nurse']
            : ['technician'];

        if (!in_array($provider->employee_type, $expectedTypes, true)) {
            return back()
                ->withErrors(['assigned_staff_id' => 'The selected provider does not match the visit type.'])
                ->withInput();
        }

        $patientPackageId = null;
        if (!empty($data['patient_package_id'])) {
            $patientPackage = PatientPackage::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $data['branch_id'])
                ->findOrFail((int) $data['patient_package_id']);

            if ($patientPackage->patient_id !== (int) $data['patient_id']
                || $patientPackage->package?->service_id !== (int) $data['service_id']
                || !$patientPackage->isUsable()
            ) {
                return back()
                    ->withErrors(['patient_package_id' => 'Selected package must match the patient, branch, and service.'])
                    ->withInput();
            }

            $patientPackageId = $patientPackage->id;
        }

        $appointment->update([
            'branch_id' => $data['branch_id'],
            'patient_id' => $data['patient_id'],
            'patient_package_id' => $patientPackageId,
            'service_id' => $data['service_id'],
            'room_id' => $data['room_id'] ?? null,
            'assigned_staff_id' => $provider->id,
            'reason_id' => $data['reason_id'] ?? null,
            'reason_notes' => $data['reason_notes'] ?? null,
            'front_desk_note' => $data['front_desk_note'] ?? null,
            'visit_type' => $data['visit_type'],
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $duration,
        ]);

        return redirect()->route('front-desk')
            ->with('success', "Appointment for {$appointment->patient->full_name} updated.");
    }

    public function checkIn(Request $request, Appointment $appointment)
    {
        abort_unless(Auth::user()->isRole('secretary', 'branch_manager') || Auth::user()->isSuperAdmin(), 403);

        $appointment->update([
            'status' => Appointment::STATUS_ARRIVED,
            'arrived_at' => now(),
        ]);

        return back()->with('success', "{$appointment->patient?->full_name} marked as arrived.");
    }
}
