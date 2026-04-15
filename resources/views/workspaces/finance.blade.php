@extends('layouts.app')

@section('title', __('finance_ui.dashboard') . ' - MedFlow CRM')
@section('breadcrumb', __('finance_ui.dashboard'))

@section('content')

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ __('finance_ui.dashboard') }}</h1>
    <p class="page-subtitle">{{ __('finance_ui.subtitle') }}</p>
  </div>
  <div class="header-actions">
    <span class="badge badge-red">{{ __('finance_ui.no_refunds_badge') }}</span>
  </div>
</div>

<div class="card animate-in" style="margin-bottom:18px;border:1px solid rgba(239,68,68,.22);background:linear-gradient(135deg,rgba(239,68,68,.06),rgba(251,191,36,.08));">
  <div class="card-title" style="color:var(--danger);margin-bottom:8px;">{{ __('finance_ui.no_refunds_title') }}</div>
  <p style="font-size:.86rem;color:var(--text-secondary);line-height:1.6;margin:0;">
    {{ __('finance_ui.no_refunds_message') }}
  </p>
</div>

@if(session('receipt_transaction_id'))
<div class="card animate-in" style="margin-bottom:18px;border:1px solid rgba(37,99,235,.18);background:linear-gradient(135deg,rgba(37,99,235,.05),rgba(16,185,129,.07));">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div>
      <div class="card-title" style="margin-bottom:8px;">{{ __('finance_ui.receipt_ready') }}</div>
      <p style="font-size:.86rem;color:var(--text-secondary);line-height:1.6;margin:0;">
        {{ __('finance_ui.receipt_ready_message') }}
      </p>
    </div>
    <a href="{{ route('finance.transactions.receipt', session('receipt_transaction_id')) }}" target="_blank" class="btn btn-primary btn-sm">
      {{ __('finance_ui.open_receipt_pdf') }}
    </a>
  </div>
</div>
@endif

{{-- KPIs --}}
<div class="kpi-grid animate-in" style="animation-delay:.04s;">

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
    <div class="kpi-label">{{ __('finance_ui.payment_pending') }}</div>
    <div class="kpi-value">{{ $stats['payment_pending'] }}</div>
    <div class="kpi-change neutral">{{ __('finance_ui.outstanding_plans') }}</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
    <div class="kpi-label">{{ __('finance_ui.cash_sales_today') }}</div>
    <div class="kpi-value" style="color:var(--success);">JOD {{ number_format($dailyCashFlow['cash_sales_total'], 2) }}</div>
    <div class="kpi-change up">{{ $dailyCashFlow['transactions_count'] }} {{ __('finance_ui.transactions_today') }}</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
    <div class="kpi-label">{{ __('finance_ui.completed_today') }}</div>
    <div class="kpi-value" style="color:var(--success);">{{ $stats['completed_today'] }}</div>
    <div class="kpi-change up">{{ __('finance_ui.appointments_finished') }}</div>
  </div>

</div>

<div class="grid-2-1 animate-in" style="animation-delay:.08s;align-items:start;">

  <div class="card" style="padding:0;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <div class="card-title">{{ __('finance_ui.outstanding_plans_title') }}</div>
      <span style="font-size:.75rem;color:var(--text-tertiary);">{{ __('finance_ui.record_payments_and_change') }}</span>
    </div>
    @if($outstandingPlans->isEmpty())
    <div class="empty-state" style="padding:30px;"><p>{{ __('finance_ui.no_outstanding_balances') }}</p></div>
    @else
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>{{ __('Patient') }}</th>
            <th>{{ __('finance_ui.plan') }}</th>
            <th>{{ __('finance_ui.balance') }}</th>
            <th>{{ __('finance_ui.record_payment') }}</th>
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
              <div>{{ $plan->service?->name ?? $plan->name ?? __('finance_ui.plan') }}</div>
              <div style="font-size:.72rem;color:var(--text-tertiary);margin-top:3px;">
                {{ __('finance_ui.paid') }} JOD {{ number_format($plan->amount_paid, 2) }} / JOD {{ number_format($plan->total_price, 2) }}
              </div>
            </td>
            <td style="font-size:.82rem;color:var(--text-tertiary);">
              <div style="font-weight:600;color:var(--warning);">JOD {{ number_format($plan->amount_remaining, 2) }}</div>
              @if($latestCompletedAppointment?->completed_at)
              <div style="font-size:.72rem;margin-top:3px;">
                {{ __('finance_ui.last_completed') }} {{ \Carbon\Carbon::parse($latestCompletedAppointment->completed_at)->format('d M, h:i A') }}
              </div>
              @endif
            </td>
            <td style="min-width:360px;">
              <form method="POST" action="{{ route('finance.transactions.store') }}" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                @csrf
                <input type="hidden" name="transaction_type" value="payment">
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
                  placeholder="{{ __('finance_ui.payment_amount') }}"
                  required
                >
                <input
                  type="number"
                  step="0.01"
                  min="0.01"
                  name="amount_received"
                  value="{{ old('treatment_plan_id') == $plan->id ? old('amount_received') : number_format($plan->amount_remaining, 2, '.', '') }}"
                  class="form-input"
                  placeholder="{{ __('finance_ui.amount_received') }}"
                  required
                >
                <select name="payment_method" class="form-input" required>
                  @foreach(\App\Models\Transaction::paymentMethods() as $method)
                  <option
                    value="{{ $method }}"
                    {{ old('treatment_plan_id') == $plan->id && old('payment_method') === $method ? 'selected' : (!$activeRegister && $method === 'card' ? 'selected' : ($activeRegister && $method === 'cash' ? 'selected' : '')) }}
                    @if(!$activeRegister && $method === \App\Models\Transaction::METHOD_CASH) disabled @endif
                  >
                    {{ __('finance_ui.methods.' . $method) }}
                    @if(!$activeRegister && $method === \App\Models\Transaction::METHOD_CASH)
                      ({{ __('finance_ui.register_required_short') }})
                    @endif
                  </option>
                  @endforeach
                </select>
                <input
                  type="text"
                  name="reference_number"
                  value="{{ old('treatment_plan_id') == $plan->id ? old('reference_number') : '' }}"
                  class="form-input"
                  placeholder="{{ __('finance_ui.reference_number') }}"
                >
                <input
                  type="text"
                  name="notes"
                  value="{{ old('treatment_plan_id') == $plan->id ? old('notes') : '' }}"
                  class="form-input"
                  placeholder="{{ __('finance_ui.optional_note') }}"
                  style="grid-column:1 / -1;"
                >
                <div style="grid-column:1 / -1;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                  <span style="font-size:.72rem;color:var(--text-tertiary);">
                    @if($activeRegister)
                      {{ __('finance_ui.change_formula') }}
                    @else
                      {{ __('finance_ui.cash_register_required_hint') }}
                    @endif
                  </span>
                  <div style="display:flex;gap:8px;">
                    <a href="{{ route('patients.show', $plan->patient_id) }}" class="btn btn-secondary btn-sm" style="font-size:.73rem;">{{ __('finance_ui.view_patient') }}</a>
                    <button type="submit" class="btn btn-primary btn-sm" style="font-size:.73rem;">{{ __('finance_ui.record_payment') }}</button>
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
        <div class="card-title">{{ __('finance_ui.cash_register') }}</div>
        <span class="badge {{ $activeRegister ? 'badge-green' : 'badge-gray' }}">
          {{ $activeRegister ? __('finance_ui.register_open') : __('finance_ui.register_closed') }}
        </span>
      </div>

      @if($activeRegister)
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:.8rem;color:var(--text-secondary);margin-bottom:12px;">
        <div>
          <div style="font-size:.72rem;color:var(--text-tertiary);margin-bottom:4px;">{{ __('finance_ui.opening_balance') }}</div>
          <div style="font-weight:600;color:var(--text-primary);">JOD {{ number_format($activeRegister->opening_balance, 2) }}</div>
        </div>
        <div>
          <div style="font-size:.72rem;color:var(--text-tertiary);margin-bottom:4px;">{{ __('finance_ui.expected_closing_balance') }}</div>
          <div style="font-weight:600;color:var(--text-primary);">JOD {{ number_format($activeRegister->expected_closing_balance, 2) }}</div>
        </div>
        <div>
          <div style="font-size:.72rem;color:var(--text-tertiary);margin-bottom:4px;">{{ __('finance_ui.cash_sales_total') }}</div>
          <div style="font-weight:600;color:var(--success);">JOD {{ number_format($activeRegister->cash_sales_total, 2) }}</div>
        </div>
        <div>
          <div style="font-size:.72rem;color:var(--text-tertiary);margin-bottom:4px;">{{ __('finance_ui.change_returned_total') }}</div>
          <div style="font-weight:600;color:var(--warning);">JOD {{ number_format($activeRegister->change_returned_total, 2) }}</div>
        </div>
        <div style="grid-column:1 / -1;">
          <div style="font-size:.72rem;color:var(--text-tertiary);margin-bottom:4px;">{{ __('finance_ui.register_opened_at') }}</div>
          <div style="font-weight:500;color:var(--text-primary);">
            {{ $activeRegister->opened_at?->format('d M Y, h:i A') }} {{ __('finance_ui.by') }} {{ $activeRegister->openedBy?->full_name }}
          </div>
        </div>
      </div>

      <form method="POST" action="{{ route('finance.register.close', $activeRegister->id) }}" style="display:grid;gap:8px;">
        @csrf
        <input
          type="number"
          step="0.01"
          min="0"
          name="closing_balance"
          value="{{ old('closing_balance') }}"
          class="form-input"
          placeholder="{{ __('finance_ui.closing_balance') }}"
          required
        >
        <input
          type="text"
          name="closing_notes"
          value="{{ old('closing_notes') }}"
          class="form-input"
          placeholder="{{ __('finance_ui.closing_notes') }}"
        >
        <button type="submit" class="btn btn-primary btn-sm" style="justify-content:center;">
          {{ __('finance_ui.close_register') }}
        </button>
      </form>
      @else
      <p style="font-size:.82rem;color:var(--text-secondary);margin-bottom:12px;">
        {{ __('finance_ui.open_register_prompt') }}
      </p>
      <form method="POST" action="{{ route('finance.register.open') }}" style="display:grid;gap:8px;">
        @csrf
        <input
          type="number"
          step="0.01"
          min="0"
          name="opening_balance"
          value="{{ old('opening_balance') }}"
          class="form-input"
          placeholder="{{ __('finance_ui.opening_balance') }}"
          required
        >
        <input
          type="text"
          name="notes"
          value="{{ old('notes') }}"
          class="form-input"
          placeholder="{{ __('finance_ui.register_notes') }}"
        >
        <button type="submit" class="btn btn-primary btn-sm" style="justify-content:center;">
          {{ __('finance_ui.open_register') }}
        </button>
      </form>
      @endif
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">{{ __('finance_ui.daily_cash_flow') }}</div>
        <span class="badge badge-gray">{{ $dailyCashFlow['transactions_count'] }}</span>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:.8rem;color:var(--text-secondary);">
        <div>
          <div style="font-size:.72rem;color:var(--text-tertiary);margin-bottom:4px;">{{ __('finance_ui.payments_total') }}</div>
          <div style="font-weight:600;color:var(--text-primary);">JOD {{ number_format($dailyCashFlow['payments_total'], 2) }}</div>
        </div>
        <div>
          <div style="font-size:.72rem;color:var(--text-tertiary);margin-bottom:4px;">{{ __('finance_ui.non_cash_total') }}</div>
          <div style="font-weight:600;color:var(--text-primary);">JOD {{ number_format($dailyCashFlow['non_cash_total'], 2) }}</div>
        </div>
        <div>
          <div style="font-size:.72rem;color:var(--text-tertiary);margin-bottom:4px;">{{ __('finance_ui.cash_received_total') }}</div>
          <div style="font-weight:600;color:var(--success);">JOD {{ number_format($dailyCashFlow['cash_received_total'], 2) }}</div>
        </div>
        <div>
          <div style="font-size:.72rem;color:var(--text-tertiary);margin-bottom:4px;">{{ __('finance_ui.change_returned_total') }}</div>
          <div style="font-weight:600;color:var(--warning);">JOD {{ number_format($dailyCashFlow['change_total'], 2) }}</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">{{ __('finance_ui.recent_transactions') }}</div>
        <span class="badge badge-gray">{{ $recentTransactions->count() }}</span>
      </div>
      @forelse($recentTransactions as $transaction)
      <div style="padding:10px 0;border-bottom:1px solid var(--border-light);">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
          <div>
            <div style="font-size:.84rem;font-weight:500;">{{ $transaction->patient?->full_name }}</div>
            <div style="font-size:.74rem;color:var(--text-tertiary);">
              {{ $transaction->treatmentPlan?->service?->name ?? $transaction->treatmentPlan?->name ?? __('finance_ui.plan_payment') }}
            </div>
          </div>
          <span class="badge badge-green">JOD {{ number_format($transaction->amount, 2) }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.72rem;color:var(--text-tertiary);margin-top:6px;gap:10px;">
          <span>{{ __('finance_ui.methods.' . $transaction->payment_method) }} {{ __('finance_ui.by') }} {{ $transaction->receivedBy?->full_name ?? __('finance_ui.system') }}</span>
          <span>{{ $transaction->received_at?->format('d M, h:i A') }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:6px;">
          <div style="font-size:.72rem;color:var(--text-tertiary);">
            {{ $transaction->receipt_number }}
          </div>
          <a href="{{ route('finance.transactions.receipt', $transaction->id) }}" target="_blank" class="btn btn-secondary btn-sm" style="font-size:.72rem;padding:6px 10px;">
            {{ __('finance_ui.receipt_pdf') }}
          </a>
        </div>
        @if($transaction->cash_register_session_id)
        <div style="font-size:.72rem;color:var(--text-tertiary);margin-top:4px;">{{ __('finance_ui.register_session_label') }} #{{ $transaction->cash_register_session_id }}</div>
        @endif
        @if((float) $transaction->change_returned > 0)
        <div style="font-size:.72rem;color:var(--warning);margin-top:4px;">{{ __('finance_ui.change_returned') }}: JOD {{ number_format($transaction->change_returned, 2) }}</div>
        @endif
      </div>
      @empty
      <p style="color:var(--text-tertiary);font-size:.83rem;padding:8px 0;">{{ __('finance_ui.no_payments_yet') }}</p>
      @endforelse
    </div>

    <div class="card" style="{{ $plansNearingEnd->isNotEmpty() ? 'border:1px solid #fde68a;' : '' }}">
      <div class="card-header">
        <div class="card-title" style="{{ $plansNearingEnd->isNotEmpty() ? 'color:var(--warning);' : '' }}">{{ __('finance_ui.plans_ending_soon') }}</div>
        <span class="badge {{ $plansNearingEnd->isNotEmpty() ? 'badge-yellow' : 'badge-green' }}">{{ $plansNearingEnd->count() }}</span>
      </div>
      @forelse($plansNearingEnd as $plan)
      <div style="padding:10px 0;border-bottom:1px solid var(--border-light);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
          <div>
            <div style="font-size:.84rem;font-weight:500;">{{ $plan->patient?->full_name }}</div>
            <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $plan->service?->name ?? $plan->name ?? __('finance_ui.plan') }}</div>
          </div>
          <span class="badge badge-yellow" style="font-size:.7rem;">{{ $plan->total_sessions - $plan->completed_sessions }} {{ __('finance_ui.left') }}</span>
        </div>
        <div style="height:4px;background:var(--bg-tertiary);border-radius:2px;overflow:hidden;">
          <div style="height:100%;width:{{ $plan->progress_percent }}%;background:var(--warning);border-radius:2px;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.72rem;color:var(--text-tertiary);margin-top:3px;">
          <span>{{ $plan->completed_sessions }}/{{ $plan->total_sessions }} {{ __('finance_ui.sessions') }}</span>
          @if($plan->total_price)
          <span>JOD {{ number_format($plan->total_price) }}</span>
          @endif
        </div>
      </div>
      @empty
      <p style="color:var(--text-tertiary);font-size:.83rem;padding:8px 0;">{{ __('finance_ui.no_plans_nearing_end') }}</p>
      @endforelse
    </div>

  </div>
</div>
@endsection
