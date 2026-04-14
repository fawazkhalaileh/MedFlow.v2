@extends('layouts.app')

@section('title', 'Appointment Kanban - MedFlow CRM')
@section('breadcrumb', 'Appointments / Kanban')

@section('content')

@if(session('success'))
<div style="background:var(--success-light);border:1px solid #6ee7b7;border-radius:var(--radius-md);padding:10px 16px;margin-bottom:14px;color:#065f46;font-size:.85rem;">
  {{ session('success') }}
</div>
@endif

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ __('Appointment Kanban') }}</h1>
    <p class="page-subtitle">{{ $today->format('l, d F Y') }} &bull; {{ __('Live status board') }}</p>
  </div>
  <div class="header-actions">
    <span class="badge badge-blue"   style="padding:5px 12px;">{{ $stats['total'] }} total</span>
    <span class="badge badge-purple" style="padding:5px 12px;">{{ $stats['in_clinic'] }} in clinic</span>
    <span class="badge badge-green"  style="padding:5px 12px;">{{ $stats['completed'] }} done</span>
    @if(Auth::user()->isRole('secretary','branch_manager') || Auth::user()->isSuperAdmin())
    <a href="{{ route('appointments.create') }}" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Appointment
    </a>
    @endif
    <button onclick="location.reload()" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
      Refresh
    </button>
  </div>
</div>

{{-- KANBAN BOARD --}}
<div style="overflow-x:auto;padding-bottom:24px;" class="animate-in" style="animation-delay:.06s;">
<div style="display:flex;gap:12px;min-width:max-content;align-items:start;padding:2px;">

  @foreach($columns as $key => $col)
  <div style="width:210px;flex-shrink:0;">

    {{-- Column Header --}}
    <div style="display:flex;align-items:center;gap:7px;margin-bottom:8px;padding:0 2px;">
      <div style="width:9px;height:9px;border-radius:50%;background:{{ $col['color'] }};flex-shrink:0;"></div>
      <span style="font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.6px;color:var(--text-secondary);white-space:nowrap;">{{ $col['label'] }}</span>
      <div style="margin-left:auto;background:var(--bg-tertiary);color:var(--text-secondary);font-size:.7rem;font-weight:600;padding:1px 7px;border-radius:10px;flex-shrink:0;">
        {{ $col['items']->count() }}
      </div>
    </div>

    {{-- Empty state --}}
    @if($col['items']->isEmpty())
    <div style="border:2px dashed var(--border);border-radius:var(--radius-md);padding:18px 10px;text-align:center;color:var(--text-tertiary);font-size:.76rem;min-height:80px;display:flex;align-items:center;justify-content:center;">
      None
    </div>
    @endif

    {{-- Cards --}}
    @foreach($col['items'] as $appt)
    @php
      $isNew     = !$appt->patient?->appointments->where('id','!=',$appt->id)->count();
      $contraFlag = $appt->patient?->medicalInfo?->hasContraindications() ?? false;
      $waitMins   = in_array($appt->status, ['arrived','checked_in'])
        ? now()->diffInMinutes(\Carbon\Carbon::parse($appt->scheduled_at))
        : null;

      $nextStatus = match($appt->status) {
        'booked','scheduled' => 'confirmed',
        'confirmed'          => 'arrived',
        'arrived'            => 'checked_in',
        'checked_in'         => 'intake_complete',
        'intake_complete'    => 'assigned',
        'assigned'           => 'in_room',
        'in_room'            => 'in_treatment',
        'in_treatment'       => 'completed',
        default              => null,
      };
    @endphp

    <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);padding:11px;margin-bottom:8px;box-shadow:var(--shadow-sm);
      {{ $contraFlag ? 'border-left:3px solid var(--danger);' : '' }}
      {{ $waitMins > 30 ? 'border-left:3px solid var(--danger);' : ($waitMins > 20 ? 'border-left:3px solid var(--warning);' : '') }}">

      {{-- Wait time alert --}}
      @if($waitMins > 20)
      <div style="background:{{ $waitMins > 30 ? 'var(--danger-light)' : 'var(--warning-light)' }};color:{{ $waitMins > 30 ? 'var(--danger)' : 'var(--warning)' }};font-size:.68rem;font-weight:600;padding:3px 6px;border-radius:4px;margin-bottom:6px;">
        Waiting {{ $waitMins }}m
      </div>
      @endif

      {{-- Contraindication alert --}}
      @if($contraFlag)
      <div style="background:var(--danger-light);color:var(--danger);font-size:.68rem;font-weight:600;padding:3px 6px;border-radius:4px;margin-bottom:6px;">
        {{ __('Contraindication flagged') }}
      </div>
      @endif

      {{-- Patient name + tags --}}
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:4px;margin-bottom:5px;">
        <div style="font-weight:600;font-size:.83rem;line-height:1.2;">
          {{ $appt->patient?->full_name ?? 'Unknown' }}
        </div>
        @if($isNew)
        <span style="font-size:.62rem;background:#dbeafe;color:var(--accent);padding:1px 5px;border-radius:3px;white-space:nowrap;flex-shrink:0;">New</span>
        @endif
      </div>

      {{-- Time + Service --}}
      <div style="font-size:.74rem;color:var(--text-secondary);margin-bottom:3px;">
        {{ \Carbon\Carbon::parse($appt->scheduled_at)->format('h:i A') }}
      </div>
      @if($appt->service)
      <div style="font-size:.74rem;color:var(--text-tertiary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:4px;">
        {{ $appt->service->name }}
      </div>
      @endif

      {{-- Staff --}}
      @if($appt->assignedStaff)
      <div style="font-size:.7rem;color:var(--accent);margin-bottom:5px;">
        {{ $appt->assignedStaff->first_name }} {{ $appt->assignedStaff->last_name }}
      </div>
      @else
      <div style="font-size:.7rem;color:var(--text-tertiary);margin-bottom:5px;">{{ __('No staff assigned') }}</div>
      @endif

      {{-- Treatment plan progress --}}
      @if($appt->treatmentPlan)
      <div style="margin-bottom:6px;">
        <div style="height:3px;background:var(--bg-tertiary);border-radius:2px;overflow:hidden;">
          <div style="height:100%;width:{{ $appt->treatmentPlan->progress_percent }}%;background:var(--accent);border-radius:2px;"></div>
        </div>
        <div style="font-size:.66rem;color:var(--text-tertiary);margin-top:2px;">Session {{ $appt->treatmentPlan->completed_sessions + 1 }}/{{ $appt->treatmentPlan->total_sessions }}</div>
      </div>
      @endif

      {{-- Quick actions --}}
      <div style="display:flex;gap:5px;margin-top:6px;">
        <a href="{{ route('patients.show', $appt->patient_id) }}" class="btn btn-ghost btn-sm" style="font-size:.68rem;padding:3px 7px;flex:1;text-align:center;">{{ __('Profile') }}</a>
        @if($nextStatus)
        <form method="POST" action="{{ route('appointments.status', $appt) }}" style="flex:1;">
          @csrf @method('PATCH')
          <input type="hidden" name="status" value="{{ $nextStatus }}">
          <button type="submit" class="btn btn-primary btn-sm" style="font-size:.68rem;padding:3px 7px;width:100%;">
            Advance
          </button>
        </form>
        @endif
      </div>

    </div>
    @endforeach

  </div>
  @endforeach

</div>
</div>
@endsection
