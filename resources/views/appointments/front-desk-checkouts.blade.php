@extends('layouts.app')

@section('title', 'Checkout Dashboard - MedFlow CRM')
@section('breadcrumb', 'Checkout Dashboard')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Checkout Dashboard</h1>
    <p class="page-subtitle">All visits waiting for reception payment and receipt handling.</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('front-desk') }}" class="btn btn-secondary">Back to Front Desk</a>
  </div>
</div>

<div class="card animate-in">
  <div class="card-header">
    <div>
      <div class="card-title">Waiting For Checkout</div>
      <div class="card-subtitle">Use this list to open the payment form for each completed visit.</div>
    </div>
    <span class="badge badge-green">{{ $appointments->count() }}</span>
  </div>

  @forelse($appointments as $appointment)
  <div style="padding:14px 0;border-bottom:1px solid var(--border-light);display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div style="display:grid;gap:6px;">
      <div style="font-weight:600;">{{ $appointment->patient?->full_name }}</div>
      <div style="font-size:.8rem;color:var(--text-secondary);">
        {{ $appointment->chargeable_service_names->implode(', ') ?: ($appointment->service?->name ?? 'Service not selected') }}
      </div>
      <div style="font-size:.76rem;color:var(--text-tertiary);">
        Doctor: {{ $appointment->assignedStaff?->full_name ?? 'Not assigned' }}
        @if($appointment->completed_at)
          • Completed {{ $appointment->completed_at->format('d M, h:i A') }}
        @endif
      </div>
      @if($appointment->checkout_summary)
      <div style="font-size:.76rem;color:var(--text-tertiary);">
        {{ $appointment->checkout_summary }}
      </div>
      @endif
    </div>

    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <div style="font-weight:700;color:var(--success);">
        {{ number_format((float) $appointment->checkout_total, 2) }}
      </div>
      <a href="{{ route('appointments.checkout', $appointment) }}" class="btn btn-primary btn-sm">Proceed To Checkout</a>
    </div>
  </div>
  @empty
  <div class="empty-state" style="padding:28px 12px;">
    <p>No visits are waiting for checkout right now.</p>
  </div>
  @endforelse
</div>
@endsection
