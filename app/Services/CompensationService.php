<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\CompensationSnapshot;
use App\Models\EmployeeCompensationProfile;
use App\Models\PatientPackage;
use App\Models\PackageUsage;
use App\Models\TreatmentSession;
use App\Models\User;
use App\Models\WorkAttribution;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompensationService
{
    public function __construct(private readonly CommissionCalculator $commissionCalculator)
    {
    }

    public function syncWorkAttributions(User $actor, Carbon $periodStart, Carbon $periodEnd, ?int $branchId = null): void
    {
        DB::transaction(function () use ($actor, $periodStart, $periodEnd, $branchId) {
            $this->syncSessionAttributions($actor, $periodStart, $periodEnd, $branchId);
            $this->syncPackageSaleAttributions($actor, $periodStart, $periodEnd, $branchId);
            $this->syncPackageConsumptionAttributions($actor, $periodStart, $periodEnd, $branchId);
        });
    }

    public function calculatePeriod(User $actor, Carbon $periodStart, Carbon $periodEnd, ?int $branchId = null, ?int $employeeId = null): Collection
    {
        $employees = User::query()
            ->where('company_id', $actor->company_id)
            ->where('employment_status', 'active')
            ->whereIn('employee_type', ['technician', 'doctor', 'nurse', 'branch_manager', 'finance'])
            ->when($branchId, fn (Builder $query) => $query->where('primary_branch_id', $branchId))
            ->when($employeeId, fn (Builder $query) => $query->where('id', $employeeId))
            ->orderBy('first_name')
            ->get();

        return $employees->map(function (User $employee) use ($periodStart, $periodEnd, $branchId) {
            $profile = $this->resolveCompensationProfile($employee, $periodStart, $periodEnd, $branchId);
            $attributions = WorkAttribution::query()
                ->with(['service', 'patient'])
                ->where('company_id', $employee->company_id)
                ->where('employee_id', $employee->id)
                ->whereBetween('occurred_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->orderBy('occurred_at')
                ->get();

            $commissionRows = $this->commissionCalculator->calculateForCollection($employee, $attributions, $periodStart, $periodEnd);
            $commissionTotal = round((float) $commissionRows->sum(fn ($row) => $row['calculation']['commission_amount']), 2);
            $fixedSalary = $profile ? (float) $profile->fixed_salary : 0.0;
            $totalDue = round($fixedSalary + $commissionTotal, 2);

            return [
                'employee' => $employee,
                'profile' => $profile,
                'fixed_salary' => $fixedSalary,
                'commission_total' => $commissionTotal,
                'total_due' => $totalDue,
                'work_items' => $attributions,
                'commission_breakdown' => $commissionRows,
                'totals' => [
                    'sessions_completed' => $attributions->where('source_type', 'completed_service')->count(),
                    'services_performed' => $attributions->where('source_type', 'completed_service')->groupBy('service_id')->count(),
                    'package_sales' => $attributions->where('source_type', 'package_sale')->count(),
                    'package_usage_items' => $attributions->where('source_type', 'package_consumption')->count(),
                    'revenue_attributed' => round((float) $attributions->sum('revenue_amount'), 2),
                ],
            ];
        });
    }

    public function createSnapshots(User $actor, Carbon $periodStart, Carbon $periodEnd, ?int $branchId = null): Collection
    {
        $rows = $this->calculatePeriod($actor, $periodStart, $periodEnd, $branchId);

        return DB::transaction(function () use ($actor, $periodStart, $periodEnd, $branchId, $rows) {
            return $rows->map(function (array $row) use ($actor, $periodStart, $periodEnd, $branchId) {
                $snapshot = CompensationSnapshot::create([
                    'company_id' => $actor->company_id,
                    'branch_id' => $branchId ?: $row['employee']->primary_branch_id,
                    'employee_id' => $row['employee']->id,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'fixed_salary' => $row['fixed_salary'],
                    'commission_total' => $row['commission_total'],
                    'total_due' => $row['total_due'],
                    'breakdown' => [
                        'compensation_type' => $row['profile']?->compensation_type,
                        'totals' => $row['totals'],
                        'commission_breakdown' => $row['commission_breakdown']->map(fn ($item) => $item['calculation'])->values()->all(),
                    ],
                    'generated_by' => $actor->id,
                    'generated_at' => now(),
                ]);

                ActivityLog::record(
                    'compensation_snapshot_created',
                    $snapshot,
                    "Compensation snapshot created for {$row['employee']->full_name}.",
                    [],
                    [
                        'employee_id' => $row['employee']->id,
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                        'total_due' => $row['total_due'],
                    ]
                );

                return $snapshot;
            });
        });
    }

    public function resolveCompensationProfile(User $employee, Carbon $periodStart, Carbon $periodEnd, ?int $branchId = null): ?EmployeeCompensationProfile
    {
        return EmployeeCompensationProfile::query()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($periodStart) {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $periodStart->toDateString());
            })
            ->where(function (Builder $query) use ($periodEnd) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $periodEnd->toDateString());
            })
            ->when($branchId, function (Builder $query) use ($branchId) {
                $query->where(function (Builder $innerQuery) use ($branchId) {
                    $innerQuery->whereNull('branch_id')->orWhere('branch_id', $branchId);
                });
            })
            ->orderByRaw('case when branch_id is null then 1 else 0 end')
            ->latest('effective_from')
            ->first();
    }

    private function syncSessionAttributions(User $actor, Carbon $periodStart, Carbon $periodEnd, ?int $branchId): void
    {
        $sessions = TreatmentSession::query()
            ->with(['appointment', 'treatmentPlan'])
            ->whereNotNull('technician_id')
            ->whereBetween('started_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->get();

        foreach ($sessions as $session) {
            $planRevenue = $session->treatmentPlan && $session->treatmentPlan->total_sessions > 0
                ? round((float) $session->treatmentPlan->total_price / (int) $session->treatmentPlan->total_sessions, 2)
                : 0.0;

            WorkAttribution::updateOrCreate(
                [
                    'employee_id' => $session->technician_id,
                    'attributable_type' => TreatmentSession::class,
                    'attributable_id' => $session->id,
                    'source_type' => 'completed_service',
                ],
                [
                    'company_id' => $session->patient?->company_id ?? $actor->company_id,
                    'branch_id' => $session->branch_id,
                    'patient_id' => $session->patient_id,
                    'appointment_id' => $session->appointment_id,
                    'treatment_session_id' => $session->id,
                    'service_id' => $session->service_id,
                    'quantity' => 1,
                    'revenue_amount' => $planRevenue,
                    'occurred_at' => $session->started_at ?? $session->created_at,
                    'meta' => [
                        'appointment_assigned_staff_id' => $session->appointment?->assigned_staff_id,
                        'duration_minutes' => $session->duration,
                    ],
                    'created_by' => $actor->id,
                ]
            );
        }
    }

    private function syncPackageSaleAttributions(User $actor, Carbon $periodStart, Carbon $periodEnd, ?int $branchId): void
    {
        $purchases = PatientPackage::query()
            ->whereNotNull('purchased_by')
            ->whereBetween('purchased_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->get();

        foreach ($purchases as $purchase) {
            WorkAttribution::updateOrCreate(
                [
                    'employee_id' => $purchase->purchased_by,
                    'attributable_type' => PatientPackage::class,
                    'attributable_id' => $purchase->id,
                    'source_type' => 'package_sale',
                ],
                [
                    'company_id' => $purchase->company_id,
                    'branch_id' => $purchase->branch_id,
                    'patient_id' => $purchase->patient_id,
                    'patient_package_id' => $purchase->id,
                    'service_id' => $purchase->package?->service_id,
                    'quantity' => 1,
                    'revenue_amount' => (float) $purchase->final_price,
                    'occurred_at' => $purchase->purchased_at,
                    'meta' => [
                        'sessions_purchased' => $purchase->sessions_purchased,
                    ],
                    'created_by' => $actor->id,
                ]
            );
        }
    }

    private function syncPackageConsumptionAttributions(User $actor, Carbon $periodStart, Carbon $periodEnd, ?int $branchId): void
    {
        $usages = PackageUsage::query()
            ->with('patientPackage')
            ->whereNotNull('used_by')
            ->whereBetween('used_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->get();

        foreach ($usages as $usage) {
            $packageValue = $usage->patientPackage && $usage->patientPackage->sessions_purchased > 0
                ? round(((float) $usage->patientPackage->final_price / (int) $usage->patientPackage->sessions_purchased) * (int) $usage->sessions_consumed, 2)
                : 0.0;

            WorkAttribution::updateOrCreate(
                [
                    'employee_id' => $usage->used_by,
                    'attributable_type' => PackageUsage::class,
                    'attributable_id' => $usage->id,
                    'source_type' => 'package_consumption',
                ],
                [
                    'company_id' => $usage->company_id,
                    'branch_id' => $usage->branch_id,
                    'patient_id' => $usage->patient_id,
                    'appointment_id' => $usage->appointment_id,
                    'patient_package_id' => $usage->patient_package_id,
                    'package_usage_id' => $usage->id,
                    'service_id' => $usage->service_id,
                    'quantity' => $usage->sessions_consumed,
                    'revenue_amount' => $packageValue,
                    'occurred_at' => $usage->used_at,
                    'meta' => [
                        'consumed_sessions' => $usage->sessions_consumed,
                    ],
                    'created_by' => $actor->id,
                ]
            );
        }
    }
}
