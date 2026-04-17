<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\CashRegisterSession;
use App\Models\Transaction;
use App\Models\TreatmentPlan;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function frontDeskCheckoutIndex()
    {
        $user = Auth::user();
        abort_unless($user->isRole('secretary', 'branch_manager') || $user->isSuperAdmin(), 403);

        $branchId = $user->scopedBranchId();

        $appointments = Appointment::query()
            ->with(['patient', 'assignedStaff', 'service'])
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->where('status', Appointment::STATUS_COMPLETED_WAITING_CHECKOUT)
            ->whereDate('scheduled_at', '>=', today()->subDays(7))
            ->orderByDesc('completed_at')
            ->get();

        $serviceMap = Service::query()
            ->whereIn('id', $appointments->pluck('chargeable_service_ids')->flatten()->filter()->unique()->values())
            ->pluck('name', 'id');

        $appointments->transform(function (Appointment $appointment) use ($serviceMap) {
            $appointment->chargeable_service_names = collect($appointment->chargeable_service_ids ?: [$appointment->service_id])
                ->map(fn ($id) => $serviceMap[$id] ?? $appointment->service?->name)
                ->filter()
                ->unique()
                ->values();

            $appointment->checkout_total = round((float) Service::query()
                ->whereIn('id', collect($appointment->chargeable_service_ids ?: [$appointment->service_id])->filter()->values())
                ->sum('price'), 2);

            return $appointment;
        });

        return view('appointments.front-desk-checkouts', compact('appointments'));
    }

    public function frontDeskCheckout(Appointment $appointment)
    {
        $user = Auth::user();
        abort_unless($user->isRole('secretary', 'branch_manager') || $user->isSuperAdmin(), 403);

        $branchId = $user->scopedBranchId();
        $appointment = Appointment::query()
            ->with(['patient', 'branch', 'assignedStaff', 'service'])
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->findOrFail($appointment->id);

        abort_unless($appointment->status === Appointment::STATUS_COMPLETED_WAITING_CHECKOUT, 404);

        $chargeableItems = Service::query()
            ->whereIn('id', collect($appointment->chargeable_service_ids ?: [$appointment->service_id])->filter()->values())
            ->orderBy('name')
            ->get();

        $checkoutTotal = round((float) $chargeableItems->sum(fn (Service $service) => (float) $service->price), 2);
        $activeCashRegister = CashRegisterSession::query()
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->open()
            ->latest('opened_at')
            ->first();

        return view('appointments.front-desk-checkout', compact(
            'appointment',
            'chargeableItems',
            'checkoutTotal',
            'activeCashRegister'
        ));
    }

    public function storeFrontDeskCheckout(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isRole('secretary', 'branch_manager') || $user->isSuperAdmin(), 403);

        $branchId = $user->scopedBranchId();

        $appointment = Appointment::query()
            ->with(['patient', 'branch'])
            ->when($branchId, fn ($query) => $query->forBranch($branchId))
            ->findOrFail($appointment->id);

        abort_unless($appointment->status === Appointment::STATUS_COMPLETED_WAITING_CHECKOUT, 404);

        $chargeableItems = Service::query()
            ->whereIn('id', collect($appointment->chargeable_service_ids ?: [$appointment->service_id])->filter()->values())
            ->orderBy('name')
            ->get();

        $checkoutTotal = round((float) $chargeableItems->sum(fn (Service $service) => (float) $service->price), 2);

        $validated = $request->validate([
            'payment_method' => ['required', Rule::in([Transaction::METHOD_CASH, Transaction::METHOD_CARD])],
            'amount_received' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'open_receipt' => ['nullable', 'boolean'],
        ]);

        if ($checkoutTotal <= 0) {
            return back()
                ->withInput()
                ->withErrors(['payment_method' => 'At least one priced chargeable item is required before checkout.']);
        }

        $amountReceived = $validated['payment_method'] === Transaction::METHOD_CASH
            ? round((float) ($validated['amount_received'] ?? 0), 2)
            : $checkoutTotal;

        if ($validated['payment_method'] === Transaction::METHOD_CASH && $amountReceived < $checkoutTotal) {
            return back()
                ->withInput()
                ->withErrors(['amount_received' => 'Amount received must be at least the checkout total.']);
        }

        $activeCashRegister = null;
        if ($validated['payment_method'] === Transaction::METHOD_CASH) {
            $activeCashRegister = CashRegisterSession::query()
                ->when($branchId, fn ($query) => $query->forBranch($branchId))
                ->open()
                ->latest('opened_at')
                ->first();

            if (!$activeCashRegister) {
                return back()
                    ->withInput()
                    ->withErrors(['payment_method' => __('finance_ui.cash_register_required')]);
            }
        }

        $transaction = DB::transaction(function () use ($appointment, $chargeableItems, $checkoutTotal, $amountReceived, $validated, $activeCashRegister, $user) {
            $changeReturned = round($amountReceived - $checkoutTotal, 2);

            $transaction = Transaction::create([
                'company_id' => $appointment->company_id,
                'branch_id' => $appointment->branch_id,
                'patient_id' => $appointment->patient_id,
                'treatment_plan_id' => $appointment->treatment_plan_id,
                'appointment_id' => $appointment->id,
                'cash_register_session_id' => $activeCashRegister?->id,
                'amount' => $checkoutTotal,
                'amount_received' => $amountReceived,
                'change_returned' => $changeReturned,
                'transaction_type' => Transaction::TYPE_PAYMENT,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'received_at' => now(),
                'received_by' => $user->id,
                'notes' => trim(collect([
                    'Checkout items: ' . $chargeableItems->pluck('name')->implode(', '),
                    $validated['notes'] ?? null,
                ])->filter()->implode("\n")),
            ]);

            $transaction->forceFill([
                'receipt_number' => Transaction::makeReceiptNumber(
                    $appointment->branch?->code,
                    $transaction->received_at,
                    $transaction->id
                ),
            ])->save();

            $appointment->update([
                'status' => Appointment::STATUS_CHECKED_OUT,
                'checked_out_at' => now(),
            ]);

            ActivityLog::record(
                'front_desk_checkout_completed',
                $transaction,
                "Front desk checkout recorded for {$appointment->patient?->full_name}.",
                [],
                [
                    'appointment_id' => $appointment->id,
                    'amount' => (float) $transaction->amount,
                    'payment_method' => $transaction->payment_method,
                    'change_returned' => (float) $transaction->change_returned,
                ]
            );

            return $transaction;
        });

        if ($activeCashRegister) {
            $activeCashRegister->refreshTotals();
        }

        $redirect = redirect()
            ->route('front-desk')
            ->with('success', 'Checkout completed successfully.')
            ->with('receipt_transaction_id', $transaction->id);

        if ($request->boolean('open_receipt')) {
            $redirect->with('open_receipt_after_checkout', true);
        }

        return $redirect;
    }

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
                ->whereIn('status', Appointment::completedStatuses())
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
