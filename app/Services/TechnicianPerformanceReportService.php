<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use App\Models\WorkAttribution;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TechnicianPerformanceReportService
{
    public function __construct(private readonly CompensationService $compensationService)
    {
    }

    public function build(User $actor, Carbon $periodStart, Carbon $periodEnd, ?int $branchId = null, ?int $employeeId = null): array
    {
        $this->compensationService->syncWorkAttributions($actor, $periodStart, $periodEnd, $branchId);

        $employees = User::query()
            ->where('company_id', $actor->company_id)
            ->where('employment_status', 'active')
            ->whereIn('employee_type', ['technician', 'doctor', 'nurse'])
            ->when($branchId, fn (Builder $query) => $query->where('primary_branch_id', $branchId))
            ->when($employeeId, fn (Builder $query) => $query->where('id', $employeeId))
            ->orderBy('first_name')
            ->get();

        $performance = $employees->map(function (User $employee) use ($periodStart, $periodEnd, $branchId) {
            $attributions = WorkAttribution::query()
                ->with(['service', 'branch'])
                ->where('company_id', $employee->company_id)
                ->where('employee_id', $employee->id)
                ->whereBetween('occurred_at', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->orderBy('occurred_at')
                ->get();

            $sessionItems = $attributions->where('source_type', 'completed_service');
            $packageSales = $attributions->where('source_type', 'package_sale');
            $packageUsage = $attributions->where('source_type', 'package_consumption');

            return [
                'employee' => $employee,
                'sessions_completed' => $sessionItems->count(),
                'services_performed' => $sessionItems->groupBy('service_id')->map(fn (Collection $group) => [
                    'service' => $group->first()?->service?->name ?? 'Unknown',
                    'count' => $group->count(),
                    'revenue' => round((float) $group->sum('revenue_amount'), 2),
                ])->values(),
                'utilization_by_period' => $sessionItems->groupBy(fn ($item) => Carbon::parse($item->occurred_at)->format('Y-m-d'))->map(fn (Collection $group, string $day) => [
                    'day' => $day,
                    'sessions' => $group->count(),
                ])->values(),
                'revenue_attributable' => round((float) $sessionItems->sum('revenue_amount'), 2),
                'package_usage_attributable' => round((float) $packageUsage->sum('revenue_amount'), 2),
                'package_sales_attributable' => round((float) $packageSales->sum('revenue_amount'), 2),
                'attributions' => $attributions,
            ];
        });

        $branchSummary = Branch::query()
            ->where('company_id', $actor->company_id)
            ->when(!$actor->isSuperAdmin(), fn (Builder $query) => $query->where('id', $actor->scopedBranchId()))
            ->when($branchId, fn (Builder $query) => $query->where('id', $branchId))
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($performance) {
                $branchRows = $performance->where(fn ($row) => $row['employee']->primary_branch_id === $branch->id);

                return [
                    'branch' => $branch->name,
                    'sessions' => $branchRows->sum('sessions_completed'),
                    'revenue' => round((float) $branchRows->sum('revenue_attributable'), 2),
                    'package_usage' => round((float) $branchRows->sum('package_usage_attributable'), 2),
                ];
            });

        return [
            'filters' => [
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'branch_id' => $branchId,
                'employee_id' => $employeeId,
            ],
            'performance' => $performance,
            'branch_summary' => $branchSummary,
        ];
    }
}
