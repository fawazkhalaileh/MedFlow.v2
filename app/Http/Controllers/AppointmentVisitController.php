<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Service;
use App\Models\TreatmentSession;
use App\Services\AppointmentStatusTransitionService;
use App\Services\PackageService;
use App\Services\PatientHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentVisitController extends Controller
{
    public function __construct(
        private readonly PatientHistoryService $patientHistoryService,
        private readonly AppointmentStatusTransitionService $transitionService,
        private readonly PackageService $packageService,
    ) {
    }

    public function doctorShow(Appointment $appointment)
    {
        $this->assertDoctorAccess($appointment);

        $appointment->load(['patient', 'service', 'room', 'assignedStaff']);
        $historyTimeline = $this->patientHistoryService->timeline(Auth::user(), $appointment->patient)->take(30);
        $services = Service::query()
            ->where('is_active', true)
            ->where('company_id', $appointment->company_id ?? Company::first()->id)
            ->orderBy('name')
            ->get();
        $doctorOutcomes = [
            'checkup' => 'Check-up / consultation only',
            'follow_up_review' => 'Follow-up / review',
            'procedure_done' => 'Procedure done',
            'treatment_done' => 'Treatment done',
            'prescription_only' => 'Prescription only',
        ];

        return view('appointments.doctor-visit', compact('appointment', 'historyTimeline', 'services', 'doctorOutcomes'));
    }

    public function doctorStart(Appointment $appointment)
    {
        $this->assertDoctorAccess($appointment);
        $this->transitionService->transition($appointment, Auth::user(), Appointment::STATUS_IN_DOCTOR_VISIT);

        return redirect()->route('appointments.doctor.show', $appointment)
            ->with('success', 'Doctor visit started.');
    }

    public function doctorComplete(Request $request, Appointment $appointment)
    {
        $this->assertDoctorAccess($appointment);

        $data = $request->validate([
            'chargeable_service_ids' => ['required', 'array', 'min:1'],
            'chargeable_service_ids.*' => ['integer', 'exists:services,id'],
            'chief_complaint' => ['required', 'string', 'max:2000'],
            'clinical_notes' => ['required', 'string', 'max:5000'],
            'assessment' => ['nullable', 'string', 'max:2000'],
            'doctor_visit_outcome' => ['required', 'string', 'max:80'],
            'treatment_summary' => ['required', 'string', 'max:3000'],
            'doctor_recommendations' => ['nullable', 'string', 'max:3000'],
            'checkout_summary' => ['required', 'string', 'max:2000'],
            'follow_up_required' => ['nullable', 'boolean'],
            'outcome_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $selectedServiceIds = collect($data['chargeable_service_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $selectedServices = Service::query()
            ->where('company_id', $appointment->company_id)
            ->whereIn('id', $selectedServiceIds)
            ->orderBy('name')
            ->get();

        if ($selectedServices->count() !== $selectedServiceIds->count()) {
            abort(422);
        }

        $primaryService = $selectedServices->firstWhere('id', $selectedServiceIds->first()) ?? $selectedServices->first();

        $chargeableSummary = $selectedServices
            ->map(fn (Service $service) => $service->name)
            ->implode(', ');

        $checkoutSummary = trim(collect([
            $chargeableSummary ? 'Charge items: ' . $chargeableSummary : null,
            $data['checkout_summary'],
        ])->filter()->implode("\n"));

        if ($appointment->status === Appointment::STATUS_WAITING_DOCTOR) {
            $this->transitionService->transition($appointment, Auth::user(), Appointment::STATUS_IN_DOCTOR_VISIT);
            $appointment->refresh();
        }

        $appointment->fill([
            'service_id' => $primaryService?->id,
            'chargeable_service_ids' => $selectedServiceIds->all(),
            'chief_complaint' => $data['chief_complaint'],
            'clinical_notes' => $data['clinical_notes'],
            'assessment' => $data['assessment'] ?? null,
            'doctor_visit_outcome' => $data['doctor_visit_outcome'],
            'treatment_summary' => $data['treatment_summary'],
            'doctor_recommendations' => $data['doctor_recommendations'] ?? null,
            'checkout_summary' => $checkoutSummary,
            'follow_up_required' => $request->boolean('follow_up_required'),
            'outcome_notes' => $data['outcome_notes'] ?? null,
        ])->save();

        $this->transitionService->transition($appointment, Auth::user(), Appointment::STATUS_COMPLETED_WAITING_CHECKOUT);

        if ($appointment->patient_package_id) {
            $this->packageService->recordAppointmentUsage(Auth::user(), $appointment->fresh());
        }

        return redirect()->route('review-queue')
            ->with('success', 'Doctor visit completed and sent back to front desk checkout.');
    }

    public function technicianShow(Appointment $appointment)
    {
        $this->assertTechnicianAccess($appointment);

        $appointment->load(['patient', 'service', 'room', 'assignedStaff', 'session']);
        $historyTimeline = $this->patientHistoryService->timeline(Auth::user(), $appointment->patient)->take(30);
        $services = Service::query()
            ->where('is_active', true)
            ->where('company_id', Company::first()->id)
            ->orderBy('name')
            ->get();

        return view('appointments.technician-visit', compact('appointment', 'historyTimeline', 'services'));
    }

    public function technicianStart(Appointment $appointment)
    {
        $this->assertTechnicianAccess($appointment);
        $this->transitionService->transition($appointment, Auth::user(), Appointment::STATUS_IN_TECHNICIAN_VISIT);

        return redirect()->route('appointments.technician.show', $appointment)
            ->with('success', 'Treatment session started.');
    }

    public function technicianComplete(Request $request, Appointment $appointment)
    {
        $this->assertTechnicianAccess($appointment);

        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'device_used' => ['nullable', 'string', 'max:160'],
            'treatment_areas' => ['nullable', 'string', 'max:1500'],
            'shots_count' => ['nullable', 'integer', 'min:0'],
            'fluence' => ['nullable', 'string', 'max:80'],
            'intensity' => ['nullable', 'string', 'max:80'],
            'pulse' => ['nullable', 'string', 'max:80'],
            'frequency' => ['nullable', 'string', 'max:80'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'skin_reaction' => ['nullable', 'string', 'max:120'],
            'issues' => ['nullable', 'string', 'max:2000'],
            'consumables_used' => ['nullable', 'string', 'max:2000'],
            'observations_before' => ['nullable', 'string', 'max:2000'],
            'observations_after' => ['nullable', 'string', 'max:2000'],
            'recommendations' => ['nullable', 'string', 'max:2000'],
            'next_session_notes' => ['nullable', 'string', 'max:2000'],
            'follow_up_required' => ['nullable', 'boolean'],
        ]);

        $session = $appointment->session()->firstOrNew([
            'appointment_id' => $appointment->id,
        ]);

        $session->fill([
            'treatment_plan_id' => $appointment->treatment_plan_id,
            'patient_id' => $appointment->patient_id,
            'branch_id' => $appointment->branch_id,
            'service_id' => $data['service_id'],
            'technician_id' => Auth::id(),
            'session_number' => $appointment->session_number ?? 1,
            'started_at' => $session->started_at ?? $appointment->provider_started_at ?? now(),
            'ended_at' => now(),
            'duration_minutes' => $data['duration_minutes'] ?? $appointment->duration_minutes,
            'shots_count' => $data['shots_count'] ?? null,
            'status' => 'completed',
            'device_used' => $data['device_used'] ?? null,
            'laser_settings' => [
                'fluence' => $data['fluence'] ?? null,
                'intensity' => $data['intensity'] ?? null,
                'pulse' => $data['pulse'] ?? null,
                'frequency' => $data['frequency'] ?? null,
            ],
            'treatment_areas' => filled($data['treatment_areas'] ?? null)
                ? collect(explode(',', $data['treatment_areas']))->map(fn ($area) => trim($area))->filter()->values()->all()
                : [],
            'observations_before' => $data['observations_before'] ?? null,
            'observations_after' => $data['observations_after'] ?? null,
            'skin_reaction' => $data['skin_reaction'] ?? null,
            'outcome' => $data['issues'] ?? null,
            'next_session_notes' => $data['next_session_notes'] ?? null,
            'recommendations' => $data['recommendations'] ?? null,
            'follow_up_required' => $request->boolean('follow_up_required'),
            'created_by' => $session->exists ? $session->created_by : Auth::id(),
        ]);
        $session->save();

        $appointment->fill([
            'service_id' => $data['service_id'],
            'follow_up_required' => $request->boolean('follow_up_required'),
            'outcome_notes' => collect([$data['issues'] ?? null, $data['consumables_used'] ?? null])
                ->filter()
                ->implode("\n"),
        ])->save();

        $this->transitionService->transition($appointment, Auth::user(), Appointment::STATUS_COMPLETED_WAITING_CHECKOUT);

        if ($appointment->patient_package_id) {
            $this->packageService->recordAppointmentUsage(Auth::user(), $appointment->fresh());
        }

        return redirect()->route('my-queue')
            ->with('success', 'Treatment completed and sent back to front desk checkout.');
    }

    private function assertDoctorAccess(Appointment $appointment): void
    {
        $user = Auth::user();
        $this->patientHistoryService->assertCanView($user, $appointment->patient()->firstOrFail());

        abort_unless($appointment->visit_type === Appointment::VISIT_TYPE_DOCTOR, 404);

        if (!$user->isSuperAdmin() && !$user->isRole('branch_manager')) {
            abort_unless($user->isRole('doctor', 'nurse') && $appointment->assigned_staff_id === $user->id, 404);
        }
    }

    private function assertTechnicianAccess(Appointment $appointment): void
    {
        $user = Auth::user();
        $this->patientHistoryService->assertCanView($user, $appointment->patient()->firstOrFail());

        abort_unless($appointment->visit_type === Appointment::VISIT_TYPE_TECHNICIAN, 404);

        if (!$user->isSuperAdmin() && !$user->isRole('branch_manager')) {
            abort_unless($user->isRole('technician') && $appointment->assigned_staff_id === $user->id, 404);
        }
    }
}
