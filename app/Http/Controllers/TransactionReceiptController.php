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
                'appointment.service',
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

        $chargeableItems = collect();
        if ($transaction->appointment) {
            $chargeableItems = \App\Models\Service::query()
                ->whereIn('id', collect($transaction->appointment->chargeable_service_ids ?: [$transaction->appointment->service_id])->filter()->values())
                ->orderBy('name')
                ->get();
        }

        $pdf = Pdf::loadView('finance.receipt-pdf', [
            'transaction' => $transaction,
            'brandingName' => $brandingName,
            'currency' => $currency,
            'chargeableItems' => $chargeableItems,
        ])->setPaper('a4');

        return $pdf->stream($transaction->receiptFilename());
    }
}
