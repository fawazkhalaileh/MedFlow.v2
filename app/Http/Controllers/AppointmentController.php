<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    // -----------------------------------------------------------------------
    // INDEX — filterable schedule view
    // -----------------------------------------------------------------------
    public function index(Request $request)
    {
        $user    = Auth::user();
        $company = Company::first();
        $date    = $request->input('date', today()->format('Y-m-d'));

        $query = Appointment::with(['patient', 'service', 'assignedStaff', 'branch'])
            ->whereHas('patient', fn($q) => $q->where('company_id', $company->id));

        // Non-admin roles are scoped to their branch
        if ($branchId = $user->scopedBranchId()) {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('date')) {
            $query->whereDate('scheduled_at', $date);
        }
        if ($request->filled('branch') && $user->isSuperAdmin()) {
            $query->where('branch_id', $request->branch);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->orderBy('scheduled_at')->paginate(25)->withQueryString();
        $branches     = $user->isSuperAdmin()
            ? Branch::orderBy('name')->get()
            : Branch::where('id', $user->primary_branch_id)->get();

        $statuses = [
            'scheduled', 'confirmed', 'arrived', 'checked_in',
            'intake_complete', 'assigned', 'in_room', 'in_treatment',
            'review_needed', 'completed', 'follow_up_needed', 'no_show', 'cancelled',
        ];

        return view('appointments.index', compact('appointments', 'branches', 'statuses', 'date'));
    }

    // -----------------------------------------------------------------------
    // KANBAN — live status board for the user's branch
    // -----------------------------------------------------------------------
    public function kanban(Request $request)
    {
        $user     = Auth::user();
        $branchId = $user->scopedBranchId() ?? $request->input('branch');
        $today    = today();

        $appointments = Appointment::with(['patient.medicalInfo', 'service', 'assignedStaff', 'treatmentPlan'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('scheduled_at', $today)
            ->orderBy('scheduled_at')
            ->get();

        $columns = [
            'booked'           => ['label' => 'Booked',             'color' => '#94a3b8', 'items' => $appointments->whereIn('status', ['booked', 'scheduled'])],
            'confirmed'        => ['label' => 'Confirmed',          'color' => '#0891b2', 'items' => $appointments->where('status', 'confirmed')],
            'arrived'          => ['label' => 'Arrived',            'color' => '#d97706', 'items' => $appointments->where('status', 'arrived')],
            'checked_in'       => ['label' => 'Checked In',         'color' => '#d97706', 'items' => $appointments->whereIn('status', ['checked_in', 'intake_complete'])],
            'assigned'         => ['label' => 'Ready for Tech',     'color' => '#7c3aed', 'items' => $appointments->where('status', 'assigned')],
            'in_treatment'     => ['label' => 'In Session',         'color' => '#2563eb', 'items' => $appointments->whereIn('status', ['in_room', 'in_treatment'])],
            'completed'        => ['label' => 'Completed',          'color' => '#059669', 'items' => $appointments->where('status', 'completed')],
            'follow_up_needed' => ['label' => 'Follow-up Needed',   'color' => '#d97706', 'items' => $appointments->where('status', 'follow_up_needed')],
            'no_show'          => ['label' => 'No Show',            'color' => '#dc2626', 'items' => $appointments->where('status', 'no_show')],
        ];

        $stats = [
            'total'     => $appointments->count(),
            'in_clinic' => $appointments->whereIn('status', ['arrived','checked_in','intake_complete','assigned','in_room','in_treatment'])->count(),
            'completed' => $appointments->where('status','completed')->count(),
            'no_show'   => $appointments->where('status','no_show')->count(),
        ];

        $branches = $user->isSuperAdmin() ? Branch::orderBy('name')->get() : collect();

        return view('appointments.kanban', compact('columns', 'stats', 'branches', 'branchId', 'today'));
    }

    // -----------------------------------------------------------------------
    // CREATE — booking form with patient search
    // -----------------------------------------------------------------------
    public function create(Request $request)
    {
        $user     = Auth::user();
        $branchId = $user->scopedBranchId();

        // Pre-populate patient if navigated from patient profile
        $prePatient = $request->filled('patient_id')
            ? Patient::find($request->patient_id)
            : null;

        $services = Service::where('is_active', true)->orderBy('name')->get();

        $staff = User::whereNotNull('company_id')
            ->where('employment_status', 'active')
            ->whereIn('employee_type', ['technician', 'doctor', 'nurse'])
            ->when($branchId, fn($q) => $q->where('primary_branch_id', $branchId))
            ->orderBy('first_name')
            ->get();

        $reasons = AppointmentReason::where('is_active', true)->orderBy('name')->get();

        $branches = $user->isSuperAdmin()
            ? Branch::where('status', 'active')->orderBy('name')->get()
            : Branch::where('id', $branchId)->get();

        return view('appointments.create', compact('prePatient', 'services', 'staff', 'reasons', 'branches', 'branchId'));
    }

    // -----------------------------------------------------------------------
    // STORE — save new appointment
    // -----------------------------------------------------------------------
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'patient_id'        => 'required|exists:patients,id',
            'service_id'        => 'required|exists:services,id',
            'branch_id'         => 'required|exists:branches,id',
            'scheduled_date'    => 'required|date',
            'scheduled_time'    => 'required',
            'duration_minutes'  => 'nullable|integer|min:5|max:480',
            'assigned_staff_id' => 'nullable|exists:users,id',
            'appointment_type'  => 'nullable|in:booked,walk_in',
            'reason_id'         => 'nullable|exists:appointment_reasons,id',
            'reason_notes'      => 'nullable|string|max:500',
            'status'            => 'nullable|in:scheduled,confirmed,booked',
        ]);

        // Combine date + time into a datetime
        $scheduledAt = \Carbon\Carbon::parse(
            $data['scheduled_date'] . ' ' . $data['scheduled_time']
        );

        // Auto-fill duration from service if not specified
        $service  = Service::find($data['service_id']);
        $duration = $data['duration_minutes'] ?? $service->duration_minutes ?? 60;

        $company = Company::first();

        $appointment = Appointment::create([
            'company_id'        => $company->id,
            'branch_id'         => $data['branch_id'],
            'patient_id'        => $data['patient_id'],
            'service_id'        => $data['service_id'],
            'assigned_staff_id' => $data['assigned_staff_id'] ?? null,
            'booked_by'         => $user->id,
            'reason_id'         => $data['reason_id'] ?? null,
            'reason_notes'      => $data['reason_notes'] ?? null,
            'appointment_type'  => $data['appointment_type'] ?? 'booked',
            'scheduled_at'      => $scheduledAt,
            'duration_minutes'  => $duration,
            'status'            => $data['status'] ?? 'scheduled',
        ]);

        // Secretary goes back to Front Desk; others go to appointment index
        $redirect = $user->isRole('secretary')
            ? route('front-desk')
            : route('appointments.index');

        return redirect($redirect)
            ->with('success', "Appointment booked for {$appointment->patient->full_name} on {$scheduledAt->format('d M Y \a\t h:i A')}.");
    }

    // -----------------------------------------------------------------------
    // CHECK-IN — arrive + check-in in one step
    // -----------------------------------------------------------------------
    public function checkIn(Request $request, Appointment $appointment)
    {
        $appointment->update([
            'status'     => 'checked_in',
            'arrived_at' => now(),
        ]);

        return back()->with('success', "{$appointment->patient?->full_name} checked in successfully.");
    }
}
