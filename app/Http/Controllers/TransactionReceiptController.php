<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class TransactionReceiptController extends Controller
{
    public function show(int $transaction): Response
    {
        $user = Auth::user();
        $branchId = $user->scopedBranchId();

        $transaction = Transaction::query()
            ->with([
                'branch',
                'company',
                'patient',
                'treatmentPlan.service',
                'receivedBy',
                'cashRegisterSession',
            ])
            ->when($branchId, fn($query) => $query->forBranch($branchId))
            ->findOrFail($transaction);

        abort_unless($transaction->isPayment(), 404);

        $brandingName = data_get($transaction->branch?->settings, 'display_name')
            ?: $transaction->branch?->name
            ?: $transaction->company?->name
            ?: config('app.name');

        $currency = data_get($transaction->branch?->settings, 'currency')
            ?: $transaction->company?->currency
            ?: 'JOD';

        $pdf = Pdf::loadView('finance.receipt-pdf', [
            'transaction' => $transaction,
            'brandingName' => $brandingName,
            'currency' => $currency,
        ])->setPaper('a4');

        return $pdf->stream($transaction->receiptFilename());
    }
}
