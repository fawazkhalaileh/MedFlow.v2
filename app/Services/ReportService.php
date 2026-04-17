<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\FollowUp;
use App\Models\Patient;
use App\Models\PatientPackage;
use App\Models\Transaction;
use App\Models\TreatmentPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    public function accounting(User $user, array $filters = []): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);
        $branchId = $this->resolveBranchFilter($user, $filters);

        $transactions = Transaction::query()
            ->with(['branch', 'patient', 'treatmentPlan.service', 'receivedBy'])
            ->where('company_id', $user->company_id)
            ->whereBetween('received_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);

        $expenses = Expense::query()
            ->with(['branch', 'service', 'createdBy'])
            ->where('company_id', $user->company_id)
            ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()]);

        $packages = PatientPackage::query()
            ->with(['branch', 'patient', 'package.service', 'purchasedBy'])
            ->where('company_id', $user->company_id)
            ->whereBetween('purchased_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);

        $outstandingPlans = TreatmentPlan::query()
            ->with(['branch', 'patient', 'service'])
            ->where('company_id', $user->company_id)
            ->whereColumn('amount_paid', '<', 'total_price');

        $this->applyBranchScope($transactions, $user, $branchId, 'branch_id');
        $this->applyBranchScope($expenses, $user, $branchId, 'branch_id');
        $this->applyBranchScope($packages, $user, $branchId, 'branch_id');
        $this->applyBranchScope($outstandingPlans, $user, $branchId, 'branch_id');

        if (!empty($filters['service_id'])) {
            $serviceId = (int) $filters['service_id'];
            $transactions->whereHas('treatmentPlan', fn (Builder $query) => $query->where('service_id', $serviceId));
            $expenses->where('service_id', $serviceId);
            $packages->whereHas('package', fn (Builder $query) => $query->where('service_id', $serviceId));
            $outstandingPlans->where('service_id', $serviceId);
        }

        if (!empty($filters['payment_method'])) {
            $transactions->where('payment_method', $filters['payment_method']);
            $expenses->where('payment_method', $filters['payment_method']);
        }

        $transactions = $transactions->orderByDesc('received_at')->get();
        $expenses = $expenses->orderByDesc('expense_date')->get();
        $packages = $packages->orderByDesc('purchased_at')->get();
        $outstandingPlans = $outstandingPlans->orderByDesc('updated_at')->get();

        $revenueSeries = $this->groupMoneyByPeriod($transactions, 'received_at', 'amount', $filters['period'] ?? 'day');
        $expenseSeries = $this->groupMoneyByPeriod($expenses, 'expense_date', 'amount', $filters['period'] ?? 'day');
        $paymentsByMethod = $transactions
            ->groupBy('payment_method')
            ->map(fn (Collection $rows, string $method) => [
                'method' => $method,
                'count' => $rows->count(),
                'amount' => round((float) $rows->sum('amount'), 2),
            ])
            ->values();

        $packageSales = $packages
            ->groupBy(fn (PatientPackage $package) => $package->package?->name ?? 'Unknown package')
            ->map(fn (Collection $rows, string $name) => [
                'package' => $name,
                'count' => $rows->count(),
                'sessions' => $rows->sum('sessions_purchased'),
                'sales' => round((float) $rows->sum('final_price'), 2),
            ])
            ->sortByDesc('sales')
            ->values();

        $branchProfit = Branch::query()
            ->where('company_id', $user->company_id)
            ->when($this->allowedBranchId($user), fn (Builder $query, int $scopeId) => $query->where('id', $scopeId))
            ->when($branchId, fn (Builder $query) => $query->where('id', $branchId))
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($transactions, $expenses) {
                $branchRevenue = round((float) $transactions->where('branch_id', $branch->id)->sum('amount'), 2);
                $branchExpenses = round((float) $expenses->where('branch_id', $branch->id)->sum('amount'), 2);

                return [
                    'branch' => $branch->name,
                    'revenue' => $branchRevenue,
                    'expenses' => $branchExpenses,
                    'net' => round($branchRevenue - $branchExpenses, 2),
                ];
            });

        return [
            'filters' => $this->filterMeta($user, $filters, $startDate, $endDate, $branchId),
            'stats' => [
                'revenue_total' => round((float) $transactions->sum('amount'), 2),
                'expense_total' => round((float) $expenses->sum('amount'), 2),
                'package_sales_total' => round((float) $packages->sum('final_price'), 2),
                'outstanding_total' => round((float) $outstandingPlans->sum(fn (TreatmentPlan $plan) => $plan->amount_remaining), 2),
            ],
            'revenue_series' => $revenueSeries,
            'expense_series' => $expenseSeries,
            'payments_by_method' => $paymentsByMethod,
            'package_sales' => $packageSales,
            'outstanding_balances' => $outstandingPlans,
            'branch_profit' => $branchProfit,
            'transactions' => $transactions,
            'expenses' => $expenses,
            'package_purchases' => $packages,
        ];
    }

    public function patients(User $user, array $filters = []): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);
        $branchId = $this->resolveBranchFilter($user, $filters);

        $appointments = Appointment::query()
            ->with(['patient.branch', 'service', 'assignedStaff', 'treatmentPlan'])
            ->where('company_id', $user->company_id)
            ->whereBetween('scheduled_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);

        $patients = Patient::query()
            ->with(['branch', 'appointments', 'transactions', 'patientPackages.usages'])
            ->where('company_id', $user->company_id);

        $followUps = FollowUp::query()
            ->with('patient')
            ->where('company_id', $user->company_id)
            ->where('status', 'pending');

        $this->applyBranchScope($appointments, $user, $branchId, 'branch_id');
        $this->applyBranchScope($patients, $user, $branchId, 'branch_id');
        $this->applyBranchScope($followUps, $user, $branchId, 'branch_id');

        if (!empty($filters['service_id'])) {
            $serviceId = (int) $filters['service_id'];
            $appointments->where('service_id', $serviceId);
            $patients->whereHas('appointments', fn (Builder $query) => $query->where('service_id', $serviceId));
        }

        $appointments = $appointments->orderByDesc('scheduled_at')->get();
        $patients = $patients->get();
        $followUps = $followUps->orderBy('due_date')->get();

        $visitFrequency = $appointments
            ->groupBy('patient_id')
            ->map(function (Collection $rows) {
                $patient = $rows->first()?->patient;

                return [
                    'patient' => $patient?->full_name ?? 'Unknown',
                    'patient_id' => $patient?->id,
                    'visits' => $rows->whereIn('status', Appointment::completedStatuses())->count(),
                    'scheduled' => $rows->count(),
                ];
            })
            ->sortByDesc('visits')
            ->values();

        $noShowMetrics = [
            'cancelled' => $appointments->where('status', Appointment::STATUS_CANCELLED)->count(),
            'no_show' => $appointments->where('status', Appointment::STATUS_NO_SHOW)->count(),
            'completed' => $appointments->whereIn('status', Appointment::completedStatuses())->count(),
        ];

        $packageConsumption = $patients
            ->flatMap(fn (Patient $patient) => $patient->patientPackages->map(function (PatientPackage $package) use ($patient) {
                return [
                    'patient' => $patient->full_name,
                    'patient_id' => $patient->id,
                    'package' => $package->package?->name ?? 'Package',
                    'status' => $package->status,
                    'used' => $package->sessions_used,
                    'remaining' => $package->remaining_sessions,
                    'expiry_date' => $package->expiry_date?->toDateString(),
                ];
            }))
            ->values();

        $overdueFollowUp = $followUps
            ->filter(fn (FollowUp $followUp) => $followUp->isOverdue())
            ->values();

        $noFutureBooking = $patients
            ->filter(function (Patient $patient) {
                return !$patient->appointments
                    ->where('scheduled_at', '>=', now())
                    ->whereIn('status', array_merge(
                        Appointment::bookedStatuses(),
                        [
                            Appointment::STATUS_ARRIVED,
                            Appointment::STATUS_WAITING_DOCTOR,
                            Appointment::STATUS_WAITING_TECHNICIAN,
                            Appointment::STATUS_IN_DOCTOR_VISIT,
                            Appointment::STATUS_IN_TECHNICIAN_VISIT,
                        ]
                    ))
                    ->count();
            })
            ->values();

        $topByVisits = $visitFrequency->take(10)->values();
        $topBySpend = $patients
            ->map(fn (Patient $patient) => [
                'patient' => $patient->full_name,
                'patient_id' => $patient->id,
                'spend' => round((float) $patient->transactions->sum('amount') + (float) $patient->patientPackages->sum('final_price'), 2),
                'visits' => $patient->appointments->whereIn('status', Appointment::completedStatuses())->count(),
            ])
            ->sortByDesc('spend')
            ->take(10)
            ->values();

        $activeVsInactive = [
            'active' => $patients->where('status', 'active')->count(),
            'inactive' => $patients->where('status', 'inactive')->count(),
            'vip' => $patients->where('status', 'vip')->count(),
        ];

        $firstVsReturning = [
            'first_visit' => $patients->filter(fn (Patient $patient) => $patient->appointments->count() <= 1)->count(),
            'returning' => $patients->filter(fn (Patient $patient) => $patient->appointments->count() > 1)->count(),
        ];

        return [
            'filters' => $this->filterMeta($user, $filters, $startDate, $endDate, $branchId),
            'stats' => [
                'patients_in_scope' => $patients->count(),
                'completed_visits' => $appointments->whereIn('status', Appointment::completedStatuses())->count(),
                'cancelled_or_no_show' => $noShowMetrics['cancelled'] + $noShowMetrics['no_show'],
                'overdue_follow_ups' => $overdueFollowUp->count(),
            ],
            'visit_frequency' => $visitFrequency,
            'no_show_metrics' => $noShowMetrics,
            'package_consumption' => $packageConsumption,
            'overdue_follow_up' => $overdueFollowUp,
            'no_future_booking' => $noFutureBooking,
            'top_by_visits' => $topByVisits,
            'top_by_spend' => $topBySpend,
            'active_vs_inactive' => $activeVsInactive,
            'first_vs_returning' => $firstVsReturning,
        ];
    }

    public function createExpense(User $user, array $attributes): Expense
    {
        $branchId = $this->allowedBranchId($user);
        $targetBranchId = $branchId ?: (int) $attributes['branch_id'];

        if ($branchId && $branchId !== $targetBranchId) {
            abort(404);
        }

        return Expense::create([
            'company_id' => $user->company_id,
            'branch_id' => $targetBranchId,
            'service_id' => $attributes['service_id'] ?: null,
            'category' => $attributes['category'],
            'title' => $attributes['title'],
            'amount' => $attributes['amount'],
            'payment_method' => $attributes['payment_method'] ?: null,
            'expense_date' => $attributes['expense_date'],
            'notes' => $attributes['notes'] ?: null,
            'created_by' => $user->id,
        ]);
    }

    public function availableBranches(User $user): Collection
    {
        return Branch::query()
            ->where('company_id', $user->company_id)
            ->when($this->allowedBranchId($user), fn (Builder $query, int $branchId) => $query->where('id', $branchId))
            ->orderBy('name')
            ->get();
    }

    public function allowedBranchId(User $user): ?int
    {
        return $user->isSuperAdmin() ? null : $user->scopedBranchId();
    }

    public function resolveDateRange(array $filters): array
    {
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            return [Carbon::parse($filters['start_date']), Carbon::parse($filters['end_date'])];
        }

        $period = $filters['period'] ?? 'month';

        return match ($period) {
            'day' => [today(), today()],
            'week' => [today()->startOfWeek(), today()->endOfWeek()],
            'custom' => [today()->startOfMonth(), today()],
            default => [today()->startOfMonth(), today()->endOfMonth()],
        };
    }

    private function resolveBranchFilter(User $user, array $filters): ?int
    {
        if ($user->isSuperAdmin()) {
            return !empty($filters['branch_id']) ? (int) $filters['branch_id'] : null;
        }

        return $user->scopedBranchId();
    }

    private function applyBranchScope(Builder $query, User $user, ?int $branchId, string $column): void
    {
        $scopeBranchId = $this->allowedBranchId($user);

        if ($scopeBranchId) {
            $query->where($column, $scopeBranchId);

            return;
        }

        if ($branchId) {
            $query->where($column, $branchId);
        }
    }

    private function groupMoneyByPeriod(Collection $rows, string $dateField, string $amountField, string $period): Collection
    {
        return $rows
            ->groupBy(function ($row) use ($dateField, $period) {
                $date = Carbon::parse(data_get($row, $dateField));

                return match ($period) {
                    'week' => $date->startOfWeek()->format('Y-m-d'),
                    'month' => $date->format('Y-m'),
                    default => $date->format('Y-m-d'),
                };
            })
            ->map(fn (Collection $group, string $label) => [
                'label' => $label,
                'amount' => round((float) $group->sum($amountField), 2),
            ])
            ->values();
    }

    private function filterMeta(User $user, array $filters, Carbon $startDate, Carbon $endDate, ?int $branchId): array
    {
        return [
            'period' => $filters['period'] ?? 'month',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'branch_id' => $branchId,
            'service_id' => !empty($filters['service_id']) ? (int) $filters['service_id'] : null,
            'payment_method' => $filters['payment_method'] ?? null,
            'allowed_branch_id' => $this->allowedBranchId($user),
        ];
    }
}
