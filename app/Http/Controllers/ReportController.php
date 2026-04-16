<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\EmployeeCommissionRule;
use App\Models\EmployeeCompensationProfile;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CompensationService;
use App\Services\ExportService;
use App\Services\InventoryReportService;
use App\Services\PatientHistoryService;
use App\Services\ReportService;
use App\Services\TechnicianPerformanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly InventoryReportService $inventoryReportService,
        private readonly PatientHistoryService $patientHistoryService,
        private readonly ExportService $exportService,
        private readonly TechnicianPerformanceReportService $technicianPerformanceReportService,
        private readonly CompensationService $compensationService
    ) {
    }

    public function index()
    {
        $user = Auth::user();

        return view('reports.index', [
            'user' => $user,
        ]);
    }

    public function accounting(Request $request)
    {
        $user = Auth::user();
        $filters = $this->validatedFilters($request);
        $report = $this->reportService->accounting($user, $filters);

        return view('reports.accounting', [
            'report' => $report,
            'branches' => $this->reportService->availableBranches($user),
            'services' => Service::query()->where('company_id', $user->company_id)->orderBy('name')->get(),
            'paymentMethods' => Transaction::paymentMethods(),
        ]);
    }

    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'category' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', Rule::in(Transaction::paymentMethods())],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->reportService->createExpense(Auth::user(), $validated);

        return redirect()->route('reports.accounting')->with('success', 'Expense recorded successfully.');
    }

    public function patients(Request $request)
    {
        $user = Auth::user();
        $filters = $this->validatedFilters($request);
        $report = $this->reportService->patients($user, $filters);

        return view('reports.patients', [
            'report' => $report,
            'branches' => $this->reportService->availableBranches($user),
            'services' => Service::query()->where('company_id', $user->company_id)->orderBy('name')->get(),
        ]);
    }

    public function inventory(Request $request)
    {
        $user = Auth::user();
        $filters = $this->validatedFilters($request);
        $report = $this->inventoryReportService->build($user, $filters);

        return view('reports.inventory', [
            'report' => $report,
            'branches' => $this->reportService->availableBranches($user),
        ]);
    }

    public function patientHistory(Patient $patient)
    {
        $timeline = $this->patientHistoryService->timeline(Auth::user(), $patient);

        return view('reports.patient-history', compact('patient', 'timeline'));
    }

    public function technicianPerformance(Request $request)
    {
        $user = Auth::user();
        [$periodStart, $periodEnd] = $this->resolveCompensationRange($request);
        $branchId = $this->resolveCompensationBranch($user, $request);
        $employeeId = $this->resolveSelfOrSelectedEmployee($user, $request);
        $report = $this->technicianPerformanceReportService->build($user, $periodStart, $periodEnd, $branchId, $employeeId);

        return view('reports.technician-performance', [
            'report' => $report,
            'branches' => $this->reportService->availableBranches($user),
            'employees' => $this->compensationEmployees($user, $branchId, ['technician', 'doctor', 'nurse']),
        ]);
    }

    public function commissions(Request $request)
    {
        $user = Auth::user();
        [$periodStart, $periodEnd] = $this->resolveCompensationRange($request);
        $branchId = $this->resolveCompensationBranch($user, $request);
        $employeeId = $this->resolveSelfOrSelectedEmployee($user, $request);

        $this->compensationService->syncWorkAttributions($user, $periodStart, $periodEnd, $branchId);
        $rows = $this->compensationService->calculatePeriod($user, $periodStart, $periodEnd, $branchId, $employeeId);

        return view('reports.commissions', [
            'report' => [
                'filters' => [
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'branch_id' => $branchId,
                    'employee_id' => $employeeId,
                ],
                'rows' => $rows,
                'branch_summary' => $rows->groupBy(fn ($row) => $row['employee']->primary_branch_id)
                    ->map(function ($group, $id) {
                        return [
                            'branch_id' => $id,
                            'total_due' => round((float) $group->sum('total_due'), 2),
                            'salary_total' => round((float) $group->sum('fixed_salary'), 2),
                            'commission_total' => round((float) $group->sum('commission_total'), 2),
                        ];
                    })->values(),
            ],
            'branches' => $this->reportService->availableBranches($user),
            'employees' => $this->compensationEmployees($user, $branchId),
            'profiles' => EmployeeCompensationProfile::query()
                ->where('company_id', $user->company_id)
                ->when(!$user->isSuperAdmin(), fn ($query) => $query->where(function ($inner) use ($user) {
                    $inner->whereNull('branch_id')->orWhere('branch_id', $user->scopedBranchId());
                }))
                ->latest('updated_at')
                ->get(),
            'rules' => EmployeeCommissionRule::query()
                ->with(['employee', 'branch', 'service', 'package'])
                ->where('company_id', $user->company_id)
                ->when(!$user->isSuperAdmin(), fn ($query) => $query->where(function ($inner) use ($user) {
                    $inner->whereNull('branch_id')->orWhere('branch_id', $user->scopedBranchId());
                }))
                ->orderBy('priority')
                ->get(),
            'services' => Service::query()->where('company_id', $user->company_id)->orderBy('name')->get(),
        ]);
    }

    public function storeCompensationProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:users,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'compensation_type' => ['required', Rule::in([
                EmployeeCompensationProfile::TYPE_SALARY_ONLY,
                EmployeeCompensationProfile::TYPE_COMMISSION_ONLY,
                EmployeeCompensationProfile::TYPE_SALARY_PLUS_COMMISSION,
            ])],
            'fixed_salary' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date'],
        ]);

        $branchId = $this->guardCompensationBranchWrite($user, $validated['branch_id'] ?? null);

        EmployeeCompensationProfile::create([
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'employee_id' => $validated['employee_id'],
            'compensation_type' => $validated['compensation_type'],
            'fixed_salary' => $validated['fixed_salary'],
            'effective_from' => $validated['effective_from'] ?? null,
            'effective_to' => $validated['effective_to'] ?? null,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        return redirect()->route('reports.commissions')->with('success', 'Compensation profile saved.');
    }

    public function storeCommissionRule(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'rule_scope' => ['required', Rule::in([
                EmployeeCommissionRule::SCOPE_GLOBAL,
                EmployeeCommissionRule::SCOPE_BRANCH,
                EmployeeCommissionRule::SCOPE_EMPLOYEE,
                EmployeeCommissionRule::SCOPE_EMPLOYEE_BRANCH,
            ])],
            'source_type' => ['required', Rule::in([
                EmployeeCommissionRule::SOURCE_COMPLETED_SERVICE,
                EmployeeCommissionRule::SOURCE_PACKAGE_SALE,
                EmployeeCommissionRule::SOURCE_PACKAGE_CONSUMPTION,
                EmployeeCommissionRule::SOURCE_PER_SESSION,
            ])],
            'calculation_type' => ['required', Rule::in([
                EmployeeCommissionRule::CALC_PERCENTAGE,
                EmployeeCommissionRule::CALC_PER_SESSION,
                EmployeeCommissionRule::CALC_FIXED,
            ])],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'flat_amount' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer', 'min:1'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date'],
        ]);

        $branchId = $this->guardCompensationBranchWrite($user, $validated['branch_id'] ?? null);

        EmployeeCommissionRule::create([
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'employee_id' => $validated['employee_id'] ?? null,
            'service_id' => $validated['service_id'] ?? null,
            'package_id' => $validated['package_id'] ?? null,
            'rule_scope' => $validated['rule_scope'],
            'source_type' => $validated['source_type'],
            'calculation_type' => $validated['calculation_type'],
            'rate' => $validated['rate'] ?? null,
            'flat_amount' => $validated['flat_amount'] ?? null,
            'priority' => $validated['priority'] ?? 100,
            'effective_from' => $validated['effective_from'] ?? null,
            'effective_to' => $validated['effective_to'] ?? null,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        return redirect()->route('reports.commissions')->with('success', 'Commission rule saved.');
    }

    public function createCompensationSnapshots(Request $request)
    {
        $user = Auth::user();
        [$periodStart, $periodEnd] = $this->resolveCompensationRange($request);
        $branchId = $this->resolveCompensationBranch($user, $request);

        $this->compensationService->createSnapshots($user, $periodStart, $periodEnd, $branchId);

        return redirect()->route('reports.commissions', $request->query())->with('success', 'Compensation snapshots generated.');
    }

    public function exportAccounting(string $format, Request $request)
    {
        $report = $this->reportService->accounting(Auth::user(), $this->validatedFilters($request));

        return $this->exportTable(
            $format,
            'accounting-report',
            ['Date', 'Branch', 'Patient', 'Method', 'Amount'],
            $report['transactions']->map(fn ($transaction) => [
                optional($transaction->received_at)->format('Y-m-d H:i'),
                $transaction->branch?->name,
                $transaction->patient?->full_name,
                $transaction->payment_method,
                $transaction->amount,
            ]),
            'reports.pdf-table',
            ['title' => 'Accounting Report', 'headers' => ['Date', 'Branch', 'Patient', 'Method', 'Amount'], 'rows' => $report['transactions']->map(fn ($transaction) => [
                optional($transaction->received_at)->format('Y-m-d H:i'),
                $transaction->branch?->name,
                $transaction->patient?->full_name,
                $transaction->payment_method,
                number_format((float) $transaction->amount, 2),
            ])]
        );
    }

    public function exportPatients(string $format, Request $request)
    {
        $report = $this->reportService->patients(Auth::user(), $this->validatedFilters($request));

        return $this->exportTable(
            $format,
            'patient-report',
            ['Patient', 'Visits', 'Scheduled'],
            $report['visit_frequency']->map(fn ($row) => [$row['patient'], $row['visits'], $row['scheduled']]),
            'reports.pdf-table',
            ['title' => 'Patient Report', 'headers' => ['Patient', 'Visits', 'Scheduled'], 'rows' => $report['visit_frequency']->map(fn ($row) => [$row['patient'], $row['visits'], $row['scheduled']])]
        );
    }

    public function exportInventory(string $format, Request $request)
    {
        $report = $this->inventoryReportService->build(Auth::user(), $this->validatedFilters($request));

        return $this->exportTable(
            $format,
            'inventory-report',
            ['Branch', 'Item', 'Current Stock', 'Low Stock Threshold'],
            $report['current_stock']->map(fn ($row) => [$row->branch?->name, $row->inventoryItem?->name, $row->current_stock, $row->low_stock_threshold]),
            'reports.pdf-table',
            ['title' => 'Inventory Report', 'headers' => ['Branch', 'Item', 'Current Stock', 'Low Stock Threshold'], 'rows' => $report['current_stock']->map(fn ($row) => [$row->branch?->name, $row->inventoryItem?->name, $row->current_stock, $row->low_stock_threshold])]
        );
    }

    public function exportTechnicianPerformance(string $format, Request $request)
    {
        $user = Auth::user();
        [$periodStart, $periodEnd] = $this->resolveCompensationRange($request);
        $branchId = $this->resolveCompensationBranch($user, $request);
        $employeeId = $this->resolveSelfOrSelectedEmployee($user, $request);
        $report = $this->technicianPerformanceReportService->build($user, $periodStart, $periodEnd, $branchId, $employeeId);

        return $this->exportTable(
            $format,
            'technician-performance-report',
            ['Employee', 'Sessions', 'Revenue', 'Package Usage', 'Package Sales'],
            $report['performance']->map(fn ($row) => [
                $row['employee']->full_name,
                $row['sessions_completed'],
                $row['revenue_attributable'],
                $row['package_usage_attributable'],
                $row['package_sales_attributable'],
            ]),
            'reports.pdf-table',
            ['title' => 'Technician Performance Report', 'headers' => ['Employee', 'Sessions', 'Revenue', 'Package Usage', 'Package Sales'], 'rows' => $report['performance']->map(fn ($row) => [
                $row['employee']->full_name,
                $row['sessions_completed'],
                number_format($row['revenue_attributable'], 2),
                number_format($row['package_usage_attributable'], 2),
                number_format($row['package_sales_attributable'], 2),
            ])]
        );
    }

    public function exportCommissions(string $format, Request $request)
    {
        $user = Auth::user();
        [$periodStart, $periodEnd] = $this->resolveCompensationRange($request);
        $branchId = $this->resolveCompensationBranch($user, $request);
        $employeeId = $this->resolveSelfOrSelectedEmployee($user, $request);
        $rows = $this->compensationService->calculatePeriod($user, $periodStart, $periodEnd, $branchId, $employeeId);

        return $this->exportTable(
            $format,
            'commission-report',
            ['Employee', 'Fixed Salary', 'Commission', 'Total Due'],
            $rows->map(fn ($row) => [
                $row['employee']->full_name,
                $row['fixed_salary'],
                $row['commission_total'],
                $row['total_due'],
            ]),
            'reports.pdf-table',
            ['title' => 'Commission Report', 'headers' => ['Employee', 'Fixed Salary', 'Commission', 'Total Due'], 'rows' => $rows->map(fn ($row) => [
                $row['employee']->full_name,
                number_format($row['fixed_salary'], 2),
                number_format($row['commission_total'], 2),
                number_format($row['total_due'], 2),
            ])]
        );
    }

    public function exportPatientHistory(Patient $patient, string $format)
    {
        $timeline = $this->patientHistoryService->timeline(Auth::user(), $patient);

        return $this->exportTable(
            $format,
            'patient-history-' . $patient->id,
            ['Timestamp', 'Type', 'Title', 'Summary', 'Author'],
            $timeline->map(fn ($item) => [
                optional($item['occurred_at'])->format('Y-m-d H:i'),
                $item['type'],
                $item['title'],
                $item['summary'],
                $item['author'],
            ]),
            'reports.pdf-table',
            ['title' => 'Patient History - ' . $patient->full_name, 'headers' => ['Timestamp', 'Type', 'Title', 'Summary', 'Author'], 'rows' => $timeline->map(fn ($item) => [
                optional($item['occurred_at'])->format('Y-m-d H:i'),
                $item['type'],
                $item['title'],
                $item['summary'],
                $item['author'],
            ])]
        );
    }

    private function exportTable(string $format, string $baseName, array $headers, $rows, string $pdfView, array $pdfData)
    {
        if ($format === 'csv') {
            return $this->exportService->csv($baseName . '.csv', $headers, $rows);
        }

        abort_unless($format === 'pdf', 404);

        return $this->exportService->pdf($pdfView, $pdfData, $baseName . '.pdf');
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'period' => ['nullable', Rule::in(['day', 'week', 'month', 'custom'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'payment_method' => ['nullable', Rule::in(Transaction::paymentMethods())],
        ]);
    }

    private function resolveCompensationRange(Request $request): array
    {
        $validated = $request->validate([
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
        ]);

        $start = !empty($validated['period_start']) ? Carbon::parse($validated['period_start']) : today()->startOfMonth();
        $end = !empty($validated['period_end']) ? Carbon::parse($validated['period_end']) : today()->endOfMonth();

        return [$start, $end];
    }

    private function resolveCompensationBranch($user, Request $request): ?int
    {
        if ($user->isSuperAdmin()) {
            return $request->filled('branch_id') ? (int) $request->branch_id : null;
        }

        return $user->scopedBranchId();
    }

    private function resolveSelfOrSelectedEmployee($user, Request $request): ?int
    {
        if ($user->isRole('technician', 'doctor', 'nurse') && !$user->isRole('branch_manager', 'finance') && !$user->isSuperAdmin()) {
            return $user->id;
        }

        return $request->filled('employee_id') ? (int) $request->employee_id : null;
    }

    private function compensationEmployees($user, ?int $branchId = null, array $types = ['technician', 'doctor', 'nurse', 'branch_manager', 'finance'])
    {
        if ($user->isRole('technician', 'doctor', 'nurse') && !$user->isRole('branch_manager', 'finance') && !$user->isSuperAdmin()) {
            return User::query()->whereKey($user->id)->get();
        }

        return User::query()
            ->where('company_id', $user->company_id)
            ->where('employment_status', 'active')
            ->whereIn('employee_type', $types)
            ->when(!$user->isSuperAdmin(), fn ($query) => $query->where('primary_branch_id', $user->scopedBranchId()))
            ->when($branchId, fn ($query) => $query->where('primary_branch_id', $branchId))
            ->orderBy('first_name')
            ->get();
    }

    private function guardCompensationBranchWrite($user, ?int $branchId): ?int
    {
        if (!$user->isSuperAdmin()) {
            return $user->scopedBranchId();
        }

        return $branchId;
    }
}
