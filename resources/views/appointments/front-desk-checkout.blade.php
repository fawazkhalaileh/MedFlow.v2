@extends('layouts.app')

@section('title', 'Front Desk Checkout - MedFlow CRM')
@section('breadcrumb', 'Front Desk Checkout')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Front Desk Checkout</h1>
    <p class="page-subtitle">{{ $appointment->patient?->full_name }} • {{ $appointment->scheduled_at?->format('d M Y h:i A') }}</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('front-desk') }}" class="btn btn-secondary">Back to Front Desk</a>
  </div>
</div>

<div class="grid-2-1 animate-in" style="align-items:start;">
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Checkout Payment</div>
        <div class="card-subtitle">Confirm what the doctor charged, collect payment, and finish checkout.</div>
      </div>
    </div>

    <form method="POST" action="{{ route('appointments.checkout.store', $appointment) }}" id="front-desk-checkout-form">
      @csrf

      <div class="form-group">
        <label class="form-label">Charged Items</label>
        <div style="display:grid;gap:8px;padding:12px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--bg-tertiary);">
          @forelse($chargeableItems as $item)
          <div style="display:flex;justify-content:space-between;gap:8px;font-size:.84rem;">
            <span>{{ $item->name }}</span>
            <strong>{{ number_format((float) $item->price, 2) }}</strong>
          </div>
          @empty
          <div style="font-size:.82rem;color:var(--danger);">No chargeable items were selected by the doctor.</div>
          @endforelse
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Total To Charge</label>
        <input type="text" class="form-input" value="{{ number_format($checkoutTotal, 2) }}" readonly id="checkout-total-display">
      </div>

      <div class="form-group">
        <label class="form-label">Payment Method</label>
        <select name="payment_method" class="form-select" id="payment-method-select">
          <option value="cash" @selected(old('payment_method') === 'cash')>Cash</option>
          <option value="card" @selected(old('payment_method', 'card') === 'card')>Visa / Card</option>
        </select>
        @error('payment_method')<div class="form-error">{{ $message }}</div>@enderror
        @if(!$activeCashRegister)
        <div style="font-size:.74rem;color:var(--warning);margin-top:6px;">
          Cash requires an open cash register for this branch.
        </div>
        @endif
      </div>

      <div class="form-group" id="cash-received-group">
        <label class="form-label">Cash Given By Patient</label>
        <input
          type="number"
          step="0.01"
          min="0"
          name="amount_received"
          value="{{ old('amount_received', number_format($checkoutTotal, 2, '.', '')) }}"
          class="form-input"
          id="amount-received-input"
        >
        @error('amount_received')<div class="form-error">{{ $message }}</div>@enderror
      </div>

      <div class="form-group">
        <label class="form-label">Change To Return</label>
        <input type="text" class="form-input" readonly id="change-due-display" value="0.00">
      </div>

      <div class="form-group">
        <label class="form-label">Card Reference / Note</label>
        <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-input" placeholder="Optional reference number">
      </div>

      <div class="form-group">
        <label class="form-label">Reception Note</label>
        <textarea name="notes" class="form-textarea">{{ old('notes') }}</textarea>
      </div>

      <label style="display:flex;align-items:center;gap:8px;font-size:.84rem;margin-bottom:18px;">
        <input type="checkbox" name="open_receipt" value="1" {{ old('open_receipt') ? 'checked' : '' }}>
        Open receipt after checkout
      </label>

      <button type="submit" class="btn btn-primary">Complete Checkout</button>
    </form>
  </div>

  <div style="display:flex;flex-direction:column;gap:18px;">
    <div class="card">
      <div class="card-title" style="margin-bottom:10px;">Visit Summary</div>
      <div style="display:grid;gap:8px;font-size:.82rem;">
        <div><strong>Provider:</strong> {{ $appointment->assignedStaff?->full_name ?? 'Not assigned' }}</div>
        <div><strong>Doctor Outcome:</strong> {{ \Illuminate\Support\Str::headline($appointment->doctor_visit_outcome ?: 'not recorded') }}</div>
        <div><strong>Checkout Note:</strong> {{ $appointment->checkout_summary ?: 'No checkout note.' }}</div>
        <div><strong>Patient:</strong> {{ $appointment->patient?->full_name }}</div>
      </div>
    </div>

    <div class="card">
      <div class="card-title" style="margin-bottom:10px;">Cash Register</div>
      @if($activeCashRegister)
      <div style="font-size:.82rem;color:var(--text-secondary);display:grid;gap:8px;">
        <div><strong>Status:</strong> Open</div>
        <div><strong>Opened:</strong> {{ $activeCashRegister->opened_at?->format('d M Y h:i A') }}</div>
        <div><strong>Opening Balance:</strong> {{ number_format((float) $activeCashRegister->opening_balance, 2) }}</div>
      </div>
      @else
      <p style="font-size:.82rem;color:var(--warning);margin:0;">
        No cash register is open. Card checkout still works. Cash checkout requires an open register.
      </p>
      @endif
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const paymentMethod = document.getElementById('payment-method-select');
  const cashGroup = document.getElementById('cash-received-group');
  const amountReceivedInput = document.getElementById('amount-received-input');
  const changeDueDisplay = document.getElementById('change-due-display');
  const total = {{ number_format($checkoutTotal, 2, '.', '') }};

  function updateCheckoutUi() {
    const isCash = paymentMethod.value === 'cash';
    cashGroup.style.display = isCash ? 'block' : 'none';

    const received = parseFloat(amountReceivedInput.value || '0');
    const change = isCash ? Math.max(0, received - total) : 0;
    changeDueDisplay.value = change.toFixed(2);
  }

  paymentMethod.addEventListener('change', updateCheckoutUi);
  amountReceivedInput.addEventListener('input', updateCheckoutUi);
  updateCheckoutUi();
});
</script>
@endpush
@endsection
