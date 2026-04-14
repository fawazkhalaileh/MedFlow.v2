@extends('layouts.app')

@section('title', 'Finance Dashboard - MedFlow CRM')
@section('breadcrumb', 'Finance Dashboard')

@section('content')

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Finance Dashboard</h1>
    <p class="page-subtitle">Billing, payments and treatment plan status</p>
  </div>
</div>

{{-- KPIs --}}
<div class="kpi-grid animate-in" style="animation-delay:.04s;">

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
    <div class="kpi-label">Payment Pending</div>
    <div class="kpi-value">{{ $stats['payment_pending'] }}</div>
    <div class="kpi-change neutral">outstanding plans</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
    <div class="kpi-label">Plans Ending Soon</div>
    <div class="kpi-value" style="color:var(--warning);">{{ $stats['plans_ending'] }}</div>
    <div class="kpi-change neutral">last session approaching</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
    <div class="kpi-label">Completed Today</div>
    <div class="kpi-value" style="color:var(--success);">{{ $stats['completed_today'] }}</div>
    <div class="kpi-change up">appointments finished</div>
  </div>

</div>

<div class="grid-2-1 animate-in" style="animation-delay:.08s;align-items:start;">

  <div class="card" style="padding:0;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <div class="card-title">Outstanding Plans</div>
      <span style="font-size:.75rem;color:var(--text-tertiary);">Record payments and returned change</span>
    </div>
    @if($outstandingPlans->isEmpty())
    <div class="empty-state" style="padding:30px;"><p>No outstanding treatment plan balances</p></div>
    @else
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Patient</th>
            <th>Plan</th>
            <th>Balance</th>
            <th>Record Payment</th>
          </tr>
        </thead>
        <tbody>
          @foreach($outstandingPlans as $plan)
          @php
            $latestCompletedAppointment = $paymentPending->firstWhere('treatment_plan_id', $plan->id);
          @endphp
          <tr>
            <td>
              <div style="font-weight:500;font-size:.85rem;">{{ $plan->patient?->full_name }}</div>
              <div style="font-size:.72rem;color:var(--text-tertiary);">{{ $plan->patient?->patient_code }}</div>
            </td>
            <td style="font-size:.83rem;color:var(--text-secondary);">
              <div>{{ $plan->service?->name ?? $plan->name ?? 'Plan' }}</div>
              <div style="font-size:.72rem;color:var(--text-tertiary);margin-top:3px;">
                Paid AED {{ number_format($plan->amount_paid, 2) }} / AED {{ number_format($plan->total_price, 2) }}
              </div>
            </td>
            <td style="font-size:.82rem;color:var(--text-tertiary);">
              <div style="font-weight:600;color:var(--warning);">AED {{ number_format($plan->amount_remaining, 2) }}</div>
              @if($latestCompletedAppointment?->completed_at)
              <div style="font-size:.72rem;margin-top:3px;">
                Last completed {{ \Carbon\Carbon::parse($latestCompletedAppointment->completed_at)->format('d M, h:i A') }}
              </div>
              @endif
            </td>
            <td style="min-width:360px;">
              <form method="POST" action="{{ route('finance.transactions.store') }}" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                @csrf
                <input type="hidden" name="treatment_plan_id" value="{{ $plan->id }}">
                @if($latestCompletedAppointment)
                <input type="hidden" name="appointment_id" value="{{ $latestCompletedAppointment->id }}">
                @endif
                <input
                  type="number"
                  step="0.01"
                  min="0.01"
                  max="{{ number_format($plan->amount_remaining, 2, '.', '') }}"
                  name="amount"
                  value="{{ old('treatment_plan_id') == $plan->id ? old('amount') : number_format($plan->amount_remaining, 2, '.', '') }}"
                  class="form-input"
                  placeholder="Payment amount"
                  required
                >
                <input
                  type="number"
                  step="0.01"
                  min="0.01"
                  name="amount_received"
                  value="{{ old('treatment_plan_id') == $plan->id ? old('amount_received') : number_format($plan->amount_remaining, 2, '.', '') }}"
                  class="form-input"
                  placeholder="Amount received"
                  required
                >
                <select name="payment_method" class="form-input" required>
                  @foreach(\App\Models\Transaction::paymentMethods() as $method)
                  <option value="{{ $method }}" {{ old('treatment_plan_id') == $plan->id && old('payment_method') === $method ? 'selected' : ($method === 'cash' ? 'selected' : '') }}>
                    {{ ucfirst($method) }}
                  </option>
                  @endforeach
                </select>
                <input
                  type="text"
                  name="reference_number"
                  value="{{ old('treatment_plan_id') == $plan->id ? old('reference_number') : '' }}"
                  class="form-input"
                  placeholder="Reference number"
                >
                <input
                  type="text"
                  name="notes"
                  value="{{ old('treatment_plan_id') == $plan->id ? old('notes') : '' }}"
                  class="form-input"
                  placeholder="Optional note"
                  style="grid-column:1 / -1;"
                >
                <div style="grid-column:1 / -1;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                  <span style="font-size:.72rem;color:var(--text-tertiary);">
                    Change = amount received minus payment amount
                  </span>
                  <div style="display:flex;gap:8px;">
                    <a href="{{ route('patients.show', $plan->patient_id) }}" class="btn btn-secondary btn-sm" style="font-size:.73rem;">View Patient</a>
                    <button type="submit" class="btn btn-primary btn-sm" style="font-size:.73rem;">Record Payment</button>
                  </div>
                </div>
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>

  <div style="display:flex;flex-direction:column;gap:16px;">

    <div class="card">
      <div class="card-header">
        <div class="card-title">Recent Transactions</div>
        <span class="badge badge-gray">{{ $recentTransactions->count() }}</span>
      </div>
      @forelse($recentTransactions as $transaction)
      <div style="padding:10px 0;border-bottom:1px solid var(--border-light);">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
          <div>
            <div style="font-size:.84rem;font-weight:500;">{{ $transaction->patient?->full_name }}</div>
            <div style="font-size:.74rem;color:var(--text-tertiary);">
              {{ $transaction->treatmentPlan?->service?->name ?? $transaction->treatmentPlan?->name ?? 'Plan payment' }}
            </div>
          </div>
          <span class="badge badge-green">AED {{ number_format($transaction->amount, 2) }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.72rem;color:var(--text-tertiary);margin-top:6px;gap:10px;">
          <span>{{ ucfirst($transaction->payment_method) }} by {{ $transaction->receivedBy?->full_name ?? 'System' }}</span>
          <span>{{ $transaction->received_at?->format('d M, h:i A') }}</span>
        </div>
        @if((float) $transaction->change_returned > 0)
        <div style="font-size:.72rem;color:var(--warning);margin-top:4px;">Change returned: AED {{ number_format($transaction->change_returned, 2) }}</div>
        @endif
      </div>
      @empty
      <p style="color:var(--text-tertiary);font-size:.83rem;padding:8px 0;">No payments recorded yet</p>
      @endforelse
    </div>

    <div class="card" style="{{ $plansNearingEnd->isNotEmpty() ? 'border:1px solid #fde68a;' : '' }}">
      <div class="card-header">
        <div class="card-title" style="{{ $plansNearingEnd->isNotEmpty() ? 'color:var(--warning);' : '' }}">Plans Nearing End</div>
        <span class="badge {{ $plansNearingEnd->isNotEmpty() ? 'badge-yellow' : 'badge-green' }}">{{ $plansNearingEnd->count() }}</span>
      </div>
      @forelse($plansNearingEnd as $plan)
      <div style="padding:10px 0;border-bottom:1px solid var(--border-light);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
          <div>
            <div style="font-size:.84rem;font-weight:500;">{{ $plan->patient?->full_name }}</div>
            <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $plan->service?->name ?? $plan->name ?? 'Plan' }}</div>
          </div>
          <span class="badge badge-yellow" style="font-size:.7rem;">{{ $plan->total_sessions - $plan->completed_sessions }} left</span>
        </div>
        <div style="height:4px;background:var(--bg-tertiary);border-radius:2px;overflow:hidden;">
          <div style="height:100%;width:{{ $plan->progress_percent }}%;background:var(--warning);border-radius:2px;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.72rem;color:var(--text-tertiary);margin-top:3px;">
          <span>{{ $plan->completed_sessions }}/{{ $plan->total_sessions }} sessions</span>
          @if($plan->total_price)
          <span>AED {{ number_format($plan->total_price) }}</span>
          @endif
        </div>
      </div>
      @empty
      <p style="color:var(--text-tertiary);font-size:.83rem;padding:8px 0;">No plans nearing end</p>
      @endforelse
    </div>

    <div class="card">
      <div class="card-title" style="margin-bottom:12px;">Finance Checklist</div>
      <div style="font-size:.8rem;color:var(--text-secondary);display:flex;flex-direction:column;gap:7px;">
        <div style="display:flex;align-items:flex-start;gap:8px;">
          <span style="color:var(--accent);flex-shrink:0;">&rarr;</span>
          <span>Collect payment against the plan balance and return any cash change immediately</span>
        </div>
        <div style="display:flex;align-items:flex-start;gap:8px;">
          <span style="color:var(--accent);flex-shrink:0;">&rarr;</span>
          <span>Offer package renewal to patients with <strong>1 session remaining</strong></span>
        </div>
        <div style="display:flex;align-items:flex-start;gap:8px;">
          <span style="color:var(--accent);flex-shrink:0;">&rarr;</span>
          <span>Capture a reference number for card, transfer, or insurance payments when available</span>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection
