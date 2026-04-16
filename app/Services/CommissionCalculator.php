<?php

namespace App\Services;

use App\Models\EmployeeCommissionRule;
use App\Models\User;
use App\Models\WorkAttribution;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CommissionCalculator
{
    public function calculateForAttribution(User $employee, WorkAttribution $attribution, Carbon $periodStart, Carbon $periodEnd): array
    {
        $rule = $this->resolveRule($employee, $attribution, $periodStart, $periodEnd);

        if (!$rule) {
            return [
                'rule_id' => null,
                'rule_scope' => null,
                'source_type' => $attribution->source_type,
                'commission_amount' => 0.0,
                'base_amount' => (float) $attribution->revenue_amount,
                'quantity' => (float) $attribution->quantity,
            ];
        }

        $baseAmount = (float) $attribution->revenue_amount;
        $quantity = (float) $attribution->quantity;

        $commission = match ($rule->calculation_type) {
            EmployeeCommissionRule::CALC_PERCENTAGE => round($baseAmount * ((float) $rule->rate / 100), 2),
            EmployeeCommissionRule::CALC_PER_SESSION => round($quantity * (float) $rule->flat_amount, 2),
            default => round((float) $rule->flat_amount, 2),
        };

        return [
            'rule_id' => $rule->id,
            'rule_scope' => $rule->rule_scope,
            'source_type' => $attribution->source_type,
            'commission_amount' => $commission,
            'base_amount' => $baseAmount,
            'quantity' => $quantity,
            'service_id' => $attribution->service_id,
            'patient_package_id' => $attribution->patient_package_id,
            'package_usage_id' => $attribution->package_usage_id,
            'treatment_session_id' => $attribution->treatment_session_id,
        ];
    }

    public function calculateForCollection(User $employee, Collection $attributions, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        return $attributions->map(fn (WorkAttribution $attribution) => [
            'attribution' => $attribution,
            'calculation' => $this->calculateForAttribution($employee, $attribution, $periodStart, $periodEnd),
        ]);
    }

    private function resolveRule(User $employee, WorkAttribution $attribution, Carbon $periodStart, Carbon $periodEnd): ?EmployeeCommissionRule
    {
        return EmployeeCommissionRule::query()
            ->where('company_id', $employee->company_id)
            ->where('source_type', $attribution->source_type)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($periodStart) {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $periodStart->toDateString());
            })
            ->where(function (Builder $query) use ($periodEnd) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $periodEnd->toDateString());
            })
            ->where(function (Builder $query) use ($employee, $attribution) {
                $query
                    ->where(function (Builder $innerQuery) use ($employee, $attribution) {
                        $innerQuery
                            ->where('rule_scope', EmployeeCommissionRule::SCOPE_EMPLOYEE_BRANCH)
                            ->where('employee_id', $employee->id)
                            ->where('branch_id', $attribution->branch_id);
                    })
                    ->orWhere(function (Builder $innerQuery) use ($employee) {
                        $innerQuery
                            ->where('rule_scope', EmployeeCommissionRule::SCOPE_EMPLOYEE)
                            ->where('employee_id', $employee->id);
                    })
                    ->orWhere(function (Builder $innerQuery) use ($attribution) {
                        $innerQuery
                            ->where('rule_scope', EmployeeCommissionRule::SCOPE_BRANCH)
                            ->where('branch_id', $attribution->branch_id)
                            ->whereNull('employee_id');
                    })
                    ->orWhere(function (Builder $innerQuery) {
                        $innerQuery
                            ->where('rule_scope', EmployeeCommissionRule::SCOPE_GLOBAL)
                            ->whereNull('employee_id')
                            ->whereNull('branch_id');
                    });
            })
            ->where(function (Builder $query) use ($attribution) {
                $query->whereNull('service_id')->orWhere('service_id', $attribution->service_id);
            })
            ->orderBy('priority')
            ->first();
    }
}
