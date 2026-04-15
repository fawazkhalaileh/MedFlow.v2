@php
  $contraFlag = $appt->patient?->medicalInfo?->hasContraindications() ?? false;
@endphp
<div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;margin-bottom:10px;box-shadow:var(--shadow-sm);{{ $contraFlag ? 'border-left:3px solid var(--danger);' : '' }}">

  {{-- Safety Banner --}}
  @if($contraFlag)
  <div style="background:var(--danger-light);border-radius:var(--radius-sm);padding:5px 8px;margin-bottom:9px;font-size:.73rem;color:var(--danger);font-weight:600;">
    {{ __('Contraindication flagged — verify before treatment') }}
  </div>
  @endif

  {{-- Patient Info --}}
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
    <div class="avatar avatar-sm" style="width:30px;height:30px;font-size:.68rem;flex-shrink:0;background:linear-gradient(135deg,#{{ substr(md5($appt->patient?->first_name ?? ''),0,6) }},#{{ substr(md5($appt->patient?->last_name ?? ''),0,6) }});">
      {{ strtoupper(substr($appt->patient?->first_name ?? '?',0,1).substr($appt->patient?->last_name ?? '',0,1)) }}
    </div>
    <div>
      <div style="font-weight:600;font-size:.85rem;">{{ $appt->patient?->full_name ?? __('Unknown') }}</div>
      <div style="font-size:.72rem;color:var(--text-tertiary);">{{ $appt->patient?->patient_code }}</div>
    </div>
  </div>

  {{-- Service & Time --}}
  <div style="font-size:.78rem;color:var(--text-secondary);margin-bottom:6px;">
    <strong>{{ $appt->service?->name ?? '--' }}</strong>
    &bull; {{ \Carbon\Carbon::parse($appt->scheduled_at)->format('h:i A') }}
  </div>

  {{-- Treatment Plan Progress --}}
  @if($appt->treatmentPlan)
  <div style="margin-bottom:8px;">
    <div style="display:flex;justify-content:space-between;font-size:.72rem;color:var(--text-tertiary);margin-bottom:3px;">
      <span>{{ __('Service') }}</span>
      <span>{{ $appt->treatmentPlan->completed_sessions }}/{{ $appt->treatmentPlan->total_sessions }}</span>
    </div>
    <div style="height:4px;background:var(--bg-tertiary);border-radius:2px;overflow:hidden;">
      <div style="height:100%;width:{{ $appt->treatmentPlan->progress_percent }}%;background:var(--accent);border-radius:2px;"></div>
    </div>
  </div>
  @endif

  {{-- Room --}}
  @if($appt->room)
  <div style="font-size:.74rem;color:var(--text-tertiary);margin-bottom:8px;">
    {{ __('Room:') }} <strong style="color:var(--text-secondary);">{{ $appt->room->name }}</strong>
  </div>
  @endif

  {{-- Action --}}
  @if($nextStatus)
  <form method="POST" action="{{ route('appointments.status', $appt) }}">
    @csrf @method('PATCH')
    <input type="hidden" name="status" value="{{ $nextStatus }}">
    <button type="submit" class="btn btn-primary btn-sm" style="width:100%;font-size:.78rem;justify-content:center;">
      {{ $nextLabel }}
    </button>
  </form>
  @else
  <div style="text-align:center;font-size:.78rem;color:var(--success);font-weight:600;padding:4px 0;">
    {{ __('Complete') }}
  </div>
  @endif

</div>
