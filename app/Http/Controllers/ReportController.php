<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Service;
use App\Models\Transaction;
use App\Services\ExportService;
use App\Services\InventoryReportService;
use App\Services\PatientHistoryService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly InventoryReportService $inventoryReportService,
        private readonly PatientHistoryService $patientHistoryService,
        private readonly ExportService $exportService
    ) {
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
}
