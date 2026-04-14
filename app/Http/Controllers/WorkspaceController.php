<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\Patient;
use App\Models\Transaction;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class WorkspaceController extends Controller
{
    // Secretary / Receptionist: Front Desk
    public function frontDesk()
    {
        $user     = Auth::user();
        $branchId = $user->primary_branch_id;
        $today    = today();

        $queue = Appointment::with(['patient', 'service', 'assignedStaff', 'room'])
            ->where('branch_id', $branchId)
            ->whereDate('scheduled_at', $today)
            ->orderBy('scheduled_at')
            ->get();

        $needsConfirmation = Appointment::with(['patient', 'service'])
            ->where('branch_id', $branchId)
            ->whereBetween('scheduled_at', [now(), now()->addHours(48)])
            ->where('status', 'booked')
            ->orderBy('scheduled_at')
            ->limit(15)
            ->get();

        $myFollowUps = FollowUp::with(['patient'])
            ->where('branch_id', $branchId)
            ->where('assigned_to', $user->id)
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $stats = [
            'total_today'    => $queue->count(),
            'arrived'        => $queue->whereIn('status', ['arrived', 'checked_in', 'intake_complete', 'assigned', 'in_room', 'in_treatment'])->count(),
            'completed'      => $queue->where('status', 'completed')->count(),
            'no_show'        => $queue->where('status', 'no_show')->count(),
            'pending_confirm'=> $needsConfirmation->count(),
            'my_followups'   => $myFollowUps->count(),
        ];

        return view('workspaces.front-desk', compact('queue', 'needsConfirmation', 'myFollowUps', 'stats'));
    }

    // Technician: My Queue
    public function myQueue()
    {
        $user     = Auth::user();
        $branchId = $user->primary_branch_id;
        $today    = today();

        $myAppointments = Appointment::with(['patient', 'service', 'room', 'treatmentPlan'])
            ->where('branch_id', $branchId)
            ->where('assigned_staff_id', $user->id)
            ->whereDate('scheduled_at', $today)
            ->orderBy('scheduled_at')
            ->get();

        // Group by Kanban column
        $waiting    = $myAppointments->whereIn('status', ['assigned', 'checked_in', 'intake_complete']);
        $inPrep     = $myAppointments->where('status', 'in_room');
        $inSession  = $myAppointments->where('status', 'in_treatment');
        $done       = $myAppointments->whereIn('status', ['completed', 'follow_up_needed']);

        $stats = [
            'total'     => $myAppointments->count(),
            'done'      => $done->count(),
            'remaining' => $myAppointments->whereNotIn('status', ['completed', 'cancelled', 'no_show', 'follow_up_needed'])->count(),
        ];

        return view('workspaces.my-queue', compact('myAppointments', 'waiting', 'inPrep', 'inSession', 'done', 'stats'));
    }

    // Branch Manager: Operations Board
    public function operations()
    {
        $user     = Auth::user();
        $branchId = $user->primary_branch_id;
        $today    = today();

        $allAppointments = Appointment::with(['patient', 'service', 'assignedStaff', 'room'])
            ->where('branch_id', $branchId)
            ->whereDate('scheduled_at', $today)
            ->orderBy('scheduled_at')
            ->get();

        // Status pipeline groups
        $pipeline = [
            'booked'           => $allAppointments->whereIn('status', ['booked', 'scheduled']),
            'confirmed'        => $allAppointments->where('status', 'confirmed'),
            'arrived'          => $allAppointments->whereIn('status', ['arrived', 'checked_in', 'intake_complete']),
            'in_progress'      => $allAppointments->whereIn('status', ['assigned', 'in_room', 'in_treatment']),
            'review_needed'    => $allAppointments->where('status', 'review_needed'),
            'completed'        => $allAppointments->where('status', 'completed'),
            'follow_up_needed' => $allAppointments->where('status', 'follow_up_needed'),
            'no_show'          => $allAppointments->where('status', 'no_show'),
        ];

        $staff = User::where('primary_branch_id', $branchId)
            ->where('employment_status', 'active')
            ->whereIn('employee_type', ['technician', 'doctor', 'nurse'])
            ->get()
            ->map(function ($s) use ($allAppointments) {
                $s->today_count   = $allAppointments->where('assigned_staff_id', $s->id)->count();
                $s->active_count  = $allAppointments->where('assigned_staff_id', $s->id)
                    ->whereIn('status', ['in_room', 'in_treatment'])->count();
                return $s;
            });

        $alerts = $this->buildAlerts($allAppointments, $branchId);

        $stats = [
            'total'     => $allAppointments->count(),
            'completed' => $allAppointments->where('status', 'completed')->count(),
            'in_clinic' => $allAppointments->whereIn('status', ['arrived','checked_in','intake_complete','assigned','in_room','in_treatment'])->count(),
            'no_shows'  => $allAppointments->where('status', 'no_show')->count(),
        ];

        return view('workspaces.operations', compact('pipeline', 'staff', 'alerts', 'stats'));
    }

    // Doctor: Review Queue
    public function reviewQueue()
    {
        $user     = Auth::user();
        $branchId = $user->primary_branch_id;

        $escalations = Appointment::with(['patient', 'service', 'assignedStaff'])
            ->where('branch_id', $branchId)
            ->where('status', 'review_needed')
            ->orderBy('updated_at')
            ->get();

        $consentPending = Patient::with('branch')
            ->where('branch_id', $branchId)
            ->where('consent_given', false)
            ->whereHas('appointments', fn($q) => $q->where('status', 'confirmed')->whereDate('scheduled_at', '>=', today()))
            ->limit(10)
            ->get();

        $todayConsultations = Appointment::with(['patient', 'service'])
            ->where('branch_id', $branchId)
            ->where('assigned_staff_id', $user->id)
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->get();

        return view('workspaces.review-queue', compact('escalations', 'consentPending', 'todayConsultations'));
    }

    // Finance Dashboard
    public function finance()
    {
        $user     = Auth::user();
        $branchId = $user->scopedBranchId();
        $company  = Company::first();
        $today    = today();

        $paymentPending = Appointment::with(['patient', 'service', 'treatmentPlan'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'completed')
            ->whereNotNull('treatment_plan_id')
            ->whereHas('treatmentPlan', function ($query) {
                $query->whereNotNull('total_price')
                    ->whereColumn('amount_paid', '<', 'total_price');
            })
            ->whereDate('completed_at', '>=', $today->copy()->subDays(7))
            ->orderBy('completed_at', 'desc')
            ->limit(30)
            ->get();

        $outstandingPlans = TreatmentPlan::with(['patient', 'service'])
            ->where('company_id', $company->id)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->whereNotNull('total_price')
            ->whereColumn('amount_paid', '<', 'total_price')
            ->orderByRaw('(total_price - amount_paid) DESC')
            ->limit(12)
            ->get();

        $outstandingPlansCount = TreatmentPlan::query()
            ->where('company_id', $company->id)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->whereNotNull('total_price')
            ->whereColumn('amount_paid', '<', 'total_price')
            ->count();

        $plansNearingEnd = TreatmentPlan::with(['patient', 'service'])
            ->where('company_id', $company->id)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->where('status', 'active')
            ->whereRaw('completed_sessions >= total_sessions - 1')
            ->limit(15)
            ->get();

        $recentTransactions = Transaction::with(['patient', 'treatmentPlan.service', 'receivedBy'])
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->latest('received_at')
            ->limit(10)
            ->get();

        $stats = [
            'payment_pending' => $outstandingPlansCount,
            'plans_ending'    => $plansNearingEnd->count(),
            'completed_today' => Appointment::when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('completed_at', $today)->where('status', 'completed')->count(),
        ];

        return view('workspaces.finance', compact('paymentPending', 'outstandingPlans', 'plansNearingEnd', 'recentTransactions', 'stats'));
    }

    // Appointment status quick-update (used by all role pages)
    public function updateAppointmentStatus(\Illuminate\Http\Request $request, Appointment $appointment)
    {
        $request->validate(['status' => 'required|string']);

        $timestamps = [
            'arrived'        => 'arrived_at',
            'in_treatment'   => null,
            'completed'      => 'completed_at',
        ];

        $appointment->status = $request->status;

        if (isset($timestamps[$request->status]) && $timestamps[$request->status]) {
            $appointment->{$timestamps[$request->status]} = now();
        }

        $appointment->save();

        return back()->with('success', 'Status updated to ' . ucfirst(str_replace('_', ' ', $request->status)) . '.');
    }

    private function buildAlerts($appointments, $branchId): array
    {
        $alerts = [];

        // Patients waiting > 20 minutes past scheduled time
        foreach ($appointments->whereIn('status', ['arrived', 'checked_in']) as $appt) {
            $wait = now()->diffInMinutes(\Carbon\Carbon::parse($appt->scheduled_at));
            if ($wait > 20) {
                $alerts[] = [
                    'type'    => $wait > 30 ? 'red' : 'amber',
                    'message' => "{$appt->patient?->full_name} has been waiting {$wait} minutes.",
                    'action'  => 'Assign to staff/room',
                ];
            }
        }

        // Appointments missing staff assignment
        foreach ($appointments->whereIn('status', ['confirmed', 'arrived', 'checked_in'])->whereNull('assigned_staff_id') as $appt) {
            $alerts[] = [
                'type'    => 'amber',
                'message' => "{$appt->patient?->full_name} ({$appt->service?->name}) has no assigned technician.",
                'action'  => 'Assign staff',
            ];
        }

        return $alerts;
    }
}
