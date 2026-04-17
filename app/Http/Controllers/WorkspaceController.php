<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\CashRegisterSession;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Services\AppointmentStatusTransitionService;
use App\Services\PackageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkspaceController extends Controller
{
    public function __construct(
        private readonly AppointmentStatusTransitionService $transitionService,
        private readonly PackageService $packageService,
    ) {
    }

    public function frontDesk()
    {
        $user = Auth::user();
        $branchId = $user->primary_branch_id;
        $today = today();

        $queue = Appointment::with(['patient', 'service', 'assignedStaff', 'room'])
            ->where('branch_id', $branchId)
            ->whereDate('scheduled_at', $today)
            ->orderBy('scheduled_at')
            ->get();

        $needsConfirmation = Appointment::with(['patient', 'service'])
            ->where('branch_id', $branchId)
            ->whereBetween('scheduled_at', [now(), now()->addHours(48)])
            ->whereIn('status', Appointment::bookedStatuses())
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

        $checkoutReady = Appointment::with(['patient', 'service', 'assignedStaff'])
            ->where('branch_id', $branchId)
            ->whereDate('scheduled_at', $today)
            ->where('status', Appointment::STATUS_COMPLETED_WAITING_CHECKOUT)
            ->orderByDesc('completed_at')
            ->limit(10)
            ->get();

        $chargeableServiceMap = Service::query()
            ->whereIn('id', $checkoutReady->pluck('chargeable_service_ids')->flatten()->filter()->unique()->values())
            ->pluck('name', 'id');

        $checkoutReady->transform(function (Appointment $appointment) use ($chargeableServiceMap) {
            $appointment->chargeable_service_names = collect($appointment->chargeable_service_ids ?? [])
                ->map(fn ($id) => $chargeableServiceMap[$id] ?? null)
                ->filter()
                ->values();

            return $appointment;
        });

        $stats = [
            'total_today' => $queue->count(),
            'arrived' => $queue->whereIn('status', [
                Appointment::STATUS_ARRIVED,
                Appointment::STATUS_WAITING_DOCTOR,
                Appointment::STATUS_WAITING_TECHNICIAN,
                Appointment::STATUS_IN_DOCTOR_VISIT,
                Appointment::STATUS_IN_TECHNICIAN_VISIT,
            ])->count(),
            'completed' => $queue->whereIn('status', Appointment::completedStatuses())->count(),
            'no_show' => $queue->where('status', Appointment::STATUS_NO_SHOW)->count(),
            'pending_confirm' => $needsConfirmation->count(),
            'my_followups' => $myFollowUps->count(),
        ];

        $rooms = Room::where('branch_id', $branchId)->where('is_active', true)->orderBy('id')->get();

        $slots = [];
        for ($h = 7; $h < 22; $h++) {
            $slots[] = sprintf('%02d:00', $h);
            $slots[] = sprintf('%02d:30', $h);
        }

        $grid = [];
        foreach ($rooms as $room) {
            $grid[$room->id] = array_fill_keys($slots, []);
        }

        $unassigned = [];
        foreach ($queue as $appointment) {
            if (!$appointment->room_id) {
                $unassigned[] = $appointment;
                continue;
            }

            $dt = Carbon::parse($appointment->scheduled_at);
            $snapMin = $dt->minute < 30 ? '00' : '30';
            $slotKey = sprintf('%02d:%02d', $dt->hour, $snapMin);

            if (isset($grid[$appointment->room_id][$slotKey])) {
                $grid[$appointment->room_id][$slotKey][] = $appointment;
            }
        }

        $staffPalette = [
            ['border' => '#2563eb', 'bg' => '#eff6ff'],
            ['border' => '#059669', 'bg' => '#ecfdf5'],
            ['border' => '#7c3aed', 'bg' => '#f5f3ff'],
            ['border' => '#d97706', 'bg' => '#fffbeb'],
            ['border' => '#db2777', 'bg' => '#fdf2f8'],
            ['border' => '#0891b2', 'bg' => '#f0f9ff'],
            ['border' => '#16a34a', 'bg' => '#f0fdf4'],
            ['border' => '#4338ca', 'bg' => '#eef2ff'],
        ];
        $staffColorMap = [];
        $palIdx = 0;
        foreach ($queue as $appointment) {
            if ($appointment->assigned_staff_id && !isset($staffColorMap[$appointment->assigned_staff_id])) {
                $staffColorMap[$appointment->assigned_staff_id] = $staffPalette[$palIdx % 8];
                $palIdx++;
            }
        }

        return view('workspaces.front-desk', compact(
            'queue',
            'needsConfirmation',
            'myFollowUps',
            'checkoutReady',
            'stats',
            'rooms',
            'slots',
            'grid',
            'unassigned',
            'staffPalette',
            'staffColorMap'
        ));
    }

    public function myQueue()
    {
        $user = Auth::user();
        abort_unless($user->isRole('technician', 'branch_manager') || $user->isSuperAdmin(), 403);

        $appointments = Appointment::with(['patient', 'service', 'room', 'session'])
            ->where('branch_id', $user->primary_branch_id)
            ->where('visit_type', Appointment::VISIT_TYPE_TECHNICIAN)
            ->when(!$user->isRole('branch_manager') && !$user->isSuperAdmin(), fn ($query) => $query->where('assigned_staff_id', $user->id))
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->get();

        $waiting = $appointments->where('status', Appointment::STATUS_WAITING_TECHNICIAN);
        $active = $appointments->where('status', Appointment::STATUS_IN_TECHNICIAN_VISIT);
        $done = $appointments->whereIn('status', Appointment::completedStatuses());

        $stats = [
            'total' => $appointments->count(),
            'waiting' => $waiting->count(),
            'active' => $active->count(),
            'done' => $done->count(),
        ];

        return view('workspaces.my-queue', compact('appointments', 'waiting', 'active', 'done', 'stats'));
    }

    public function operations()
    {
        $user = Auth::user();
        $branchId = $user->primary_branch_id;

        $allAppointments = Appointment::with(['patient', 'service', 'assignedStaff', 'room'])
            ->where('branch_id', $branchId)
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->get();

        $pipeline = [
            'front_desk' => $allAppointments->whereIn('status', array_merge(Appointment::bookedStatuses(), [
                Appointment::STATUS_ARRIVED,
                Appointment::STATUS_WAITING_DOCTOR,
                Appointment::STATUS_WAITING_TECHNICIAN,
            ])),
            'doctor' => $allAppointments->whereIn('status', [
                Appointment::STATUS_WAITING_DOCTOR,
                Appointment::STATUS_IN_DOCTOR_VISIT,
            ]),
            'technician' => $allAppointments->whereIn('status', [
                Appointment::STATUS_WAITING_TECHNICIAN,
                Appointment::STATUS_IN_TECHNICIAN_VISIT,
            ]),
            'checkout' => $allAppointments->where('status', Appointment::STATUS_COMPLETED_WAITING_CHECKOUT),
            'done' => $allAppointments->whereIn('status', Appointment::completedStatuses()),
        ];

        $staff = User::where('primary_branch_id', $branchId)
            ->where('employment_status', 'active')
            ->whereIn('employee_type', ['technician', 'doctor', 'nurse'])
            ->get()
            ->map(function (User $staff) use ($allAppointments) {
                $staff->today_count = $allAppointments->where('assigned_staff_id', $staff->id)->count();
                $staff->active_count = $allAppointments->where('assigned_staff_id', $staff->id)
                    ->whereIn('status', [Appointment::STATUS_IN_DOCTOR_VISIT, Appointment::STATUS_IN_TECHNICIAN_VISIT])
                    ->count();
                return $staff;
            });

        $alerts = $this->buildAlerts($allAppointments);
        $stats = [
            'total' => $allAppointments->count(),
            'completed' => $allAppointments->whereIn('status', Appointment::completedStatuses())->count(),
            'in_clinic' => $allAppointments->whereIn('status', [
                Appointment::STATUS_ARRIVED,
                Appointment::STATUS_WAITING_DOCTOR,
                Appointment::STATUS_WAITING_TECHNICIAN,
                Appointment::STATUS_IN_DOCTOR_VISIT,
                Appointment::STATUS_IN_TECHNICIAN_VISIT,
                Appointment::STATUS_COMPLETED_WAITING_CHECKOUT,
            ])->count(),
            'no_shows' => $allAppointments->where('status', Appointment::STATUS_NO_SHOW)->count(),
        ];

        return view('workspaces.operations', compact('pipeline', 'staff', 'alerts', 'stats'));
    }

    public function reviewQueue()
    {
        $user = Auth::user();
        abort_unless($user->isRole('doctor', 'nurse', 'branch_manager') || $user->isSuperAdmin(), 403);

        $today = today();

        $appointments = Appointment::with(['patient.medicalInfo', 'service', 'room'])
            ->where('branch_id', $user->primary_branch_id)
            ->where('visit_type', Appointment::VISIT_TYPE_DOCTOR)
            ->when(!$user->isRole('branch_manager') && !$user->isSuperAdmin(), fn ($query) => $query->where('assigned_staff_id', $user->id))
            ->where(function ($query) use ($today) {
                $query
                    ->whereDate('scheduled_at', '>=', $today)
                    ->orWhere(function ($statusQuery) use ($today) {
                        $statusQuery
                            ->whereDate('scheduled_at', $today)
                            ->whereIn('status', array_merge(
                                [Appointment::STATUS_IN_DOCTOR_VISIT],
                                Appointment::completedStatuses()
                            ));
                    });
            })
            ->orderBy('scheduled_at')
            ->get();

        $waiting = $appointments->whereIn('status', array_merge(
            Appointment::bookedStatuses(),
            [Appointment::STATUS_WAITING_DOCTOR]
        ));
        $active = $appointments->where('status', Appointment::STATUS_IN_DOCTOR_VISIT);
        $done = $appointments->whereIn('status', Appointment::completedStatuses());

        $consentPending = Patient::with('branch')
            ->where('branch_id', $user->primary_branch_id)
            ->where('consent_given', false)
            ->whereHas('appointments', function ($query) {
                $query
                    ->where('visit_type', Appointment::VISIT_TYPE_DOCTOR)
                    ->whereIn('status', array_merge(Appointment::bookedStatuses(), [
                        Appointment::STATUS_ARRIVED,
                        Appointment::STATUS_WAITING_DOCTOR,
                    ]))
                    ->whereDate('scheduled_at', '>=', today());
            })
            ->limit(10)
            ->get();

        return view('workspaces.review-queue', compact('appointments', 'waiting', 'active', 'done', 'consentPending'));
    }

    public function finance()
    {
        $branchId = Auth::user()->scopedBranchId();
        $company = Company::first();
        $today = today();

        $paymentPending = Appointment::with(['patient', 'service', 'treatmentPlan'])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereIn('status', Appointment::completedStatuses())
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
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->whereNotNull('total_price')
            ->whereColumn('amount_paid', '<', 'total_price')
            ->orderByRaw('(total_price - amount_paid) DESC')
            ->limit(12)
            ->get();

        $outstandingPlansCount = TreatmentPlan::query()
            ->where('company_id', $company->id)
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->whereNotNull('total_price')
            ->whereColumn('amount_paid', '<', 'total_price')
            ->count();

        $plansNearingEnd = TreatmentPlan::with(['patient', 'service'])
            ->where('company_id', $company->id)
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->where('status', 'active')
            ->whereRaw('completed_sessions >= total_sessions - 1')
            ->limit(15)
            ->get();

        $activeRegister = CashRegisterSession::with(['openedBy', 'closedBy'])
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->open()
            ->latest('opened_at')
            ->first();

        $todayRegisterSessions = CashRegisterSession::with(['openedBy', 'closedBy'])
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->where(function ($query) use ($today) {
                $query
                    ->whereDate('opened_at', $today)
                    ->orWhereDate('closed_at', $today);
            })
            ->latest('opened_at')
            ->limit(5)
            ->get();

        if ($activeRegister) {
            $activeRegister->refreshTotals();
        }

        $todayTransactions = Transaction::query()
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->whereDate('received_at', $today)
            ->get();

        $recentTransactions = Transaction::with(['patient', 'treatmentPlan.service', 'receivedBy'])
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->latest('received_at')
            ->limit(10)
            ->get();

        $dailyCashFlow = [
            'payments_total' => round((float) $todayTransactions->sum('amount'), 2),
            'cash_sales_total' => round((float) $todayTransactions->where('payment_method', Transaction::METHOD_CASH)->sum('amount'), 2),
            'cash_received_total' => round((float) $todayTransactions->where('payment_method', Transaction::METHOD_CASH)->sum('amount_received'), 2),
            'change_total' => round((float) $todayTransactions->where('payment_method', Transaction::METHOD_CASH)->sum('change_returned'), 2),
            'non_cash_total' => round((float) $todayTransactions->where('payment_method', '!=', Transaction::METHOD_CASH)->sum('amount'), 2),
            'transactions_count' => $todayTransactions->count(),
        ];

        $stats = [
            'payment_pending' => $outstandingPlansCount,
            'plans_ending' => $plansNearingEnd->count(),
            'completed_today' => Appointment::when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->whereDate('completed_at', $today)
                ->whereIn('status', Appointment::completedStatuses())
                ->count(),
        ];

        return view('workspaces.finance', compact(
            'paymentPending',
            'outstandingPlans',
            'plansNearingEnd',
            'activeRegister',
            'todayRegisterSessions',
            'dailyCashFlow',
            'recentTransactions',
            'stats'
        ));
    }

    public function updateRoomDevice(Request $request, Room $room)
    {
        $request->validate(['device_name' => 'nullable|string|max:100']);

        $user = Auth::user();
        if ($user->employee_type !== 'system_admin' && $room->branch_id !== $user->primary_branch_id) {
            abort(403);
        }

        $room->update(['device_name' => $request->device_name ?: null]);

        return back()->with('success', 'Device name updated.');
    }

    public function storeRoom(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'device_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $branchId = $user->primary_branch_id;

        abort_unless($branchId, 403, 'No branch assigned.');

        Room::create([
            'branch_id' => $branchId,
            'name' => $request->name,
            'device_name' => $request->device_name ?: null,
            'description' => $request->description ?: null,
            'is_active' => true,
        ]);

        return back()->with('success', "Device \"{$request->name}\" added to the schedule grid.");
    }

    public function exportSchedulePdf(Request $request)
    {
        $user = Auth::user();
        $branchId = $user->primary_branch_id;
        $date = $request->input('date', today()->toDateString());
        $dateObj = Carbon::parse($date);

        $rooms = Room::where('branch_id', $branchId)->where('is_active', true)->orderBy('id')->get();

        $queue = Appointment::with(['patient', 'service', 'assignedStaff', 'room'])
            ->where('branch_id', $branchId)
            ->whereDate('scheduled_at', $dateObj)
            ->orderBy('scheduled_at')
            ->get();

        $slots = [];
        for ($h = 7; $h < 22; $h++) {
            $slots[] = sprintf('%02d:00', $h);
            $slots[] = sprintf('%02d:30', $h);
        }

        $grid = [];
        foreach ($rooms as $room) {
            $grid[$room->id] = array_fill_keys($slots, []);
        }

        $unassigned = [];
        foreach ($queue as $appointment) {
            if (!$appointment->room_id) {
                $unassigned[] = $appointment;
                continue;
            }

            $dt = Carbon::parse($appointment->scheduled_at);
            $snapMin = $dt->minute < 30 ? '00' : '30';
            $slotKey = sprintf('%02d:%02d', $dt->hour, $snapMin);

            if (isset($grid[$appointment->room_id][$slotKey])) {
                $grid[$appointment->room_id][$slotKey][] = $appointment;
            }
        }

        $pdf = Pdf::loadView('workspaces.front-desk-pdf', compact(
            'rooms', 'slots', 'grid', 'unassigned', 'queue', 'dateObj', 'user'
        ))->setPaper('a3', 'landscape');

        return $pdf->download('schedule-' . $dateObj->format('Y-m-d') . '.pdf');
    }

    public function updateAppointmentStatus(Request $request, Appointment $appointment)
    {
        $request->validate(['status' => 'required|string']);

        $this->transitionService->transition($appointment, Auth::user(), $request->string('status')->toString());

        if ($request->string('status')->toString() === Appointment::STATUS_COMPLETED_WAITING_CHECKOUT && $appointment->patient_package_id) {
            $this->packageService->recordAppointmentUsage(Auth::user(), $appointment->fresh());
        }

        return back()->with('success', 'Appointment updated.');
    }

    private function buildAlerts($appointments): array
    {
        $alerts = [];

        foreach ($appointments->whereIn('status', [Appointment::STATUS_ARRIVED, Appointment::STATUS_WAITING_DOCTOR, Appointment::STATUS_WAITING_TECHNICIAN]) as $appointment) {
            $wait = now()->diffInMinutes($appointment->scheduled_at, false) * -1;

            if ($wait > 20) {
                $alerts[] = [
                    'type' => $wait > 30 ? 'red' : 'amber',
                    'message' => "{$appointment->patient?->full_name} has been waiting {$wait} minutes.",
                    'action' => $appointment->isDoctorVisit() ? 'Move to doctor' : 'Move to technician',
                ];
            }
        }

        foreach ($appointments->whereIn('status', Appointment::bookedStatuses())->whereNull('assigned_staff_id') as $appointment) {
            $alerts[] = [
                'type' => 'amber',
                'message' => "{$appointment->patient?->full_name} does not have an assigned provider yet.",
                'action' => 'Assign provider',
            ];
        }

        return $alerts;
    }
}
