@extends('layouts.app')

@section('title', __('Review Queue') . ' - MedFlow CRM')
@section('breadcrumb', __('Review Queue'))

@section('content')

@if(session('success'))
<div style="background:var(--success-light);border:1px solid #6ee7b7;border-radius:var(--radius-md);padding:10px 16px;margin-bottom:16px;color:#065f46;font-size:.85rem;">
  {{ session('success') }}
</div>
@endif

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ __('Review Queue') }}</h1>
    <p class="page-subtitle">{{ __('Escalations, consent, and today\'s consultations') }} &bull; {{ now()->format('l, d F Y') }}</p>
  </div>
</div>

{{-- ESCALATIONS --}}
@if($escalations->isNotEmpty())
<div class="card animate-in" style="animation-delay:.04s;border:1px solid #fca5a5;margin-bottom:18px;padding:0;">
  <div style="padding:14px 20px;border-bottom:1px solid #fca5a5;display:flex;align-items:center;justify-content:space-between;">
    <div style="display:flex;align-items:center;gap:10px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:var(--danger);"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <div class="card-title" style="color:var(--danger);">{{ __('Escalations — Doctor Review Required') }}</div>
    </div>
    <span class="badge badge-red">{{ $escalations->count() }}</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>{{ __('Patient') }}</th><th>{{ __('Service') }}</th><th>{{ __('Assigned Tech') }}</th><th>{{ __('Waiting Since') }}</th><th>{{ __('Action') }}</th></tr>
      </thead>
      <tbody>
        @foreach($escalations as $appt)
        <tr>
          <td>
            <div style="font-weight:500;">{{ $appt->patient?->full_name }}</div>
            <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $appt->patient?->patient_code }}</div>
          </td>
          <td style="font-size:.83rem;color:var(--text-secondary);">{{ $appt->service?->name ?? '--' }}</td>
          <td style="font-size:.83rem;color:var(--text-secondary);">{{ $appt->assignedStaff?->first_name ?? '--' }}</td>
          <td style="font-size:.83rem;color:var(--text-tertiary);">{{ $appt->updated_at->diffForHumans() }}</td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="{{ route('patients.show', $appt->patient_id) }}" class="btn btn-secondary btn-sm" style="font-size:.73rem;">{{ __('View Patient') }}</a>
              <form method="POST" action="{{ route('appointments.status', $appt) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="in_treatment">
                <button type="submit" class="btn btn-primary btn-sm" style="font-size:.73rem;">{{ __('Clear & Continue') }}</button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@else
<div class="card animate-in" style="animation-delay:.04s;background:var(--success-light);border:1px solid #6ee7b7;margin-bottom:18px;">
  <div style="display:flex;align-items:center;gap:10px;color:#065f46;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <span style="font-weight:500;">{{ __('No escalations — all clear!') }}</span>
  </div>
</div>
@endif

<div class="grid-2-1 animate-in" style="animation-delay:.08s;align-items:start;">

  {{-- TODAY'S CONSULTATIONS --}}
  <div class="card" style="padding:0;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <div class="card-title">{{ __('Today\'s Consultations') }}</div>
      <span class="badge badge-blue">{{ $todayConsultations->count() }}</span>
    </div>
    @if($todayConsultations->isEmpty())
    <div class="empty-state" style="padding:24px;"><p>{{ __('No consultations assigned today') }}</p></div>
    @else
    <div class="table-wrap">
      <table>
        <thead><tr><th>{{ __('Time') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Service') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
          @foreach($todayConsultations as $appt)
          @php
            $sc = ['scheduled'=>'badge-blue','confirmed'=>'badge-cyan','arrived'=>'badge-yellow','in_treatment'=>'badge-purple','completed'=>'badge-green','review_needed'=>'badge-red'][$appt->status] ?? 'badge-gray';
          @endphp
          <tr>
            <td style="font-weight:600;white-space:nowrap;font-size:.83rem;">{{ \Carbon\Carbon::parse($appt->scheduled_at)->format('h:i A') }}</td>
            <td>
              <div style="font-weight:500;font-size:.85rem;">{{ $appt->patient?->full_name }}</div>
              <div style="font-size:.72rem;color:var(--text-tertiary);">{{ $appt->patient?->patient_code }}</div>
            </td>
            <td style="font-size:.83rem;color:var(--text-secondary);">{{ $appt->service?->name ?? '--' }}</td>
            <td><span class="badge {{ $sc }}">{{ __(\Illuminate\Support\Str::headline($appt->status)) }}</span></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>

  {{-- RIGHT: Consent Pending --}}
  <div style="display:flex;flex-direction:column;gap:16px;">

    <div class="card" style="{{ $consentPending->isNotEmpty() ? 'border:1px solid #fde68a;' : '' }}">
      <div class="card-header">
        <div class="card-title" style="{{ $consentPending->isNotEmpty() ? 'color:var(--warning);' : '' }}">{{ __('Consent Pending') }}</div>
        <span class="badge {{ $consentPending->isNotEmpty() ? 'badge-yellow' : 'badge-green' }}">{{ $consentPending->count() }}</span>
      </div>
      @forelse($consentPending as $patient)
      <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);">
        <div>
          <div style="font-size:.84rem;font-weight:500;">{{ $patient->full_name }}</div>
          <div style="font-size:.73rem;color:var(--text-tertiary);">{{ $patient->branch?->name }}</div>
        </div>
        <a href="{{ route('patients.show', $patient) }}" class="btn btn-secondary btn-sm" style="font-size:.72rem;">{{ __('View') }}</a>
      </div>
      @empty
      <p style="color:var(--text-tertiary);font-size:.83rem;padding:8px 0;">{{ __('All patients have given consent') }}</p>
      @endforelse
    </div>

    {{-- Quick Reference --}}
    <div class="card">
      <div class="card-title" style="margin-bottom:12px;">{{ __('Clinical Guidelines') }}</div>
      <div style="font-size:.8rem;color:var(--text-secondary);display:flex;flex-direction:column;gap:8px;">
        <div style="display:flex;align-items:flex-start;gap:8px;">
          <span style="color:var(--danger);font-weight:700;flex-shrink:0;">&times;</span>
          <span>{{ __('Do not proceed if patient is pregnant, has pacemaker, or active metal implants in treatment area') }}</span>
        </div>
        <div style="display:flex;align-items:flex-start;gap:8px;">
          <span style="color:var(--danger);font-weight:700;flex-shrink:0;">&times;</span>
          <span>{{ __('Do not proceed without signed consent form') }}</span>
        </div>
        <div style="display:flex;align-items:flex-start;gap:8px;">
          <span style="color:var(--warning);font-weight:700;flex-shrink:0;">!</span>
          <span>{{ __('Fitzpatrick V–VI requires reduced energy settings — confirm before laser') }}</span>
        </div>
        <div style="display:flex;align-items:flex-start;gap:8px;">
          <span style="color:var(--warning);font-weight:700;flex-shrink:0;">!</span>
          <span>{{ __('Review medication list for photosensitisers (isotretinoin, antibiotics)') }}</span>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection
