<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\CashRegisterSession;
use App\Models\Transaction;
use App\Models\TreatmentPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'treatment_plan_id' => ['required', 'integer', 'exists:treatment_plans,id'],
            'appointment_id'    => ['nullable', 'integer', 'exists:appointments,id'],
            'transaction_type'  => ['required', Rule::in([Transaction::TYPE_PAYMENT])],
            'amount'            => ['required', 'numeric', 'min:0.01'],
            'amount_received'   => ['required', 'numeric', 'min:0.01'],
            'payment_method'    => ['required', Rule::in(Transaction::paymentMethods())],
            'reference_number'  => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        $branchId = $user->scopedBranchId();

        if ($validated['transaction_type'] !== Transaction::TYPE_PAYMENT) {
            return back()
                ->withInput()
                ->withErrors(['transaction_type' => __('finance_ui.no_refunds_allowed')]);
        }

        $plan = TreatmentPlan::query()
            ->with('patient')
            ->when($branchId, fn($query) => $query->forBranch($branchId))
            ->findOrFail($validated['treatment_plan_id']);

        $appointment = null;

        if (!empty($validated['appointment_id'])) {
            $appointment = Appointment::query()
                ->when($branchId, fn($query) => $query->forBranch($branchId))
                ->where('treatment_plan_id', $plan->id)
                ->where('patient_id', $plan->patient_id)
                ->where('status', Appointment::STATUS_COMPLETED)
                ->findOrFail($validated['appointment_id']);
        }

        $remaining = round((float) $plan->amount_remaining, 2);
        $amount = round((float) $validated['amount'], 2);
        $amountReceived = round((float) $validated['amount_received'], 2);
        $activeCashRegister = null;

        if ($amount > $remaining) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'Payment amount cannot exceed the remaining balance.']);
        }

        if ($amountReceived < $amount) {
            return back()
                ->withInput()
                ->withErrors(['amount_received' => 'Amount received must be at least the payment amount.']);
        }

        if ($validated['payment_method'] === Transaction::METHOD_CASH) {
            $activeCashRegister = CashRegisterSession::query()
                ->when($branchId, fn($query) => $query->forBranch($branchId))
                ->open()
                ->latest('opened_at')
                ->first();

            if (!$activeCashRegister) {
                return back()
                    ->withInput()
                    ->withErrors(['payment_method' => __('finance_ui.cash_register_required')]);
            }
        }

        $transaction = DB::transaction(function () use ($validated, $user, $plan, $appointment, $amount, $amountReceived, $activeCashRegister) {
            $changeReturned = round($amountReceived - $amount, 2);

            $transaction = Transaction::create([
                'company_id'       => $plan->company_id,
                'branch_id'        => $plan->branch_id,
                'patient_id'       => $plan->patient_id,
                'treatment_plan_id'=> $plan->id,
                'appointment_id'   => $appointment?->id,
                'cash_register_session_id' => $activeCashRegister?->id,
                'amount'           => $amount,
                'amount_received'  => $amountReceived,
                'change_returned'  => $changeReturned,
                'transaction_type' => $validated['transaction_type'],
                'payment_method'   => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'received_at'      => now(),
                'received_by'      => $user->id,
                'notes'            => $validated['notes'] ?? null,
            ]);

            $transaction->forceFill([
                'receipt_number' => Transaction::makeReceiptNumber(
                    $plan->branch()->value('code'),
                    $transaction->received_at,
                    $transaction->id
                ),
            ])->save();

            $beforePaid = (float) $plan->amount_paid;

            $plan->update([
                'amount_paid' => round($beforePaid + $amount, 2),
            ]);

            ActivityLog::record(
                'payment_recorded',
                $transaction,
                "Payment of {$amount} recorded for {$plan->patient?->full_name}.",
                ['amount_paid' => $beforePaid],
                [
                    'amount_paid'      => (float) $plan->amount_paid,
                    'transaction_id'   => $transaction->id,
                    'receipt_number'   => $transaction->receipt_number,
                    'payment_method'   => $transaction->payment_method,
                    'change_returned'  => (float) $transaction->change_returned,
                ]
            );

            return $transaction;
        });

        if ($activeCashRegister) {
            $activeCashRegister->refreshTotals();
        }

        $change = number_format((float) $transaction->change_returned, 2);

        return redirect()
            ->route('finance')
            ->with('success', "Payment recorded successfully. Change returned: {$change}.")
            ->with('receipt_transaction_id', $transaction->id);
    }
}
