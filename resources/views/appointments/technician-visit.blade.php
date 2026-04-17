@extends('layouts.app')

@section('title', 'Technician Visit - MedFlow CRM')
@section('breadcrumb', 'Technician Visit')

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ $appointment->patient?->full_name }}</h1>
    <p class="page-subtitle">{{ $appointment->service?->name ?? 'Treatment visit' }} | {{ \Illuminate\Support\Str::headline($appointment->status) }}</p>
  </div>
  <div class="header-actions">
    @if($appointment->status === \App\Models\Appointment::STATUS_WAITING_TECHNICIAN)
    <form method="POST" action="{{ route('appointments.technician.start', $appointment) }}">
      @csrf @method('PATCH')
      <button type="submit" class="btn btn-primary">Start Visit</button>
    </form>
    @endif
    <a href="{{ route('my-queue') }}" class="btn btn-secondary">Back to Queue</a>
  </div>
</div>

<div class="grid-2-1 animate-in" style="align-items:start;">
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Treatment Session</div>
        <div class="card-subtitle">Structured laser and procedure fields with simple practical inputs.</div>
      </div>
    </div>

    @if($appointment->status === \App\Models\Appointment::STATUS_IN_TECHNICIAN_VISIT)
    <form method="POST" action="{{ route('appointments.technician.complete', $appointment) }}">
      @csrf @method('PATCH')

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Treatment Type / Service</label>
          <select name="service_id" class="form-select">
            @foreach($services as $service)
            <option value="{{ $service->id }}" @selected(old('service_id', $appointment->service_id) == $service->id)>{{ $service->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Device Used</label>
          <input type="text" name="device_used" value="{{ old('device_used', $appointment->session?->device_used ?? $appointment->room?->device_name) }}" class="form-input">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Treatment Area</label>
          <input type="text" name="treatment_areas" value="{{ old('treatment_areas', collect($appointment->session?->treatment_areas)->implode(', ')) }}" class="form-input" placeholder="Face, upper lip, underarm">
        </div>
        <div class="form-group">
          <label class="form-label">Shots / Hits</label>
          <input type="number" name="shots_count" value="{{ old('shots_count', $appointment->session?->shots_count) }}" class="form-input">
        </div>
      </div>

      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Fluence / Energy</label>
          <input type="text" name="fluence" value="{{ old('fluence', data_get($appointment->session?->laser_settings, 'fluence')) }}" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">Intensity / Heat</label>
          <input type="text" name="intensity" value="{{ old('intensity', data_get($appointment->session?->laser_settings, 'intensity')) }}" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">Pulse</label>
          <input type="text" name="pulse" value="{{ old('pulse', data_get($appointment->session?->laser_settings, 'pulse')) }}" class="form-input">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Frequency</label>
          <input type="text" name="frequency" value="{{ old('frequency', data_get($appointment->session?->laser_settings, 'frequency')) }}" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">Duration (Minutes)</label>
          <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $appointment->session?->duration_minutes ?? $appointment->duration_minutes) }}" class="form-input">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Skin Reaction / Tolerance</label>
          <select name="skin_reaction" class="form-select">
            @foreach(['none', 'mild', 'moderate', 'severe'] as $reaction)
            <option value="{{ $reaction }}" @selected(old('skin_reaction', $appointment->session?->skin_reaction) === $reaction)>{{ ucfirst($reaction) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Consumables Used</label>
          <input type="text" name="consumables_used" value="{{ old('consumables_used') }}" class="form-input" placeholder="Gel, cooling tip, syringe">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Before Notes</label>
          <textarea name="observations_before" class="form-textarea">{{ old('observations_before', $appointment->session?->observations_before) }}</textarea>
        </div>
        <div class="form-group">
          <label class="form-label">After Notes</label>
          <textarea name="observations_after" class="form-textarea">{{ old('observations_after', $appointment->session?->observations_after) }}</textarea>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Issues or Complications</label>
          <textarea name="issues" class="form-textarea">{{ old('issues') }}</textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Recommendation for Next Session</label>
          <textarea name="recommendations" class="form-textarea">{{ old('recommendations', $appointment->session?->recommendations) }}</textarea>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Next Session Notes</label>
          <textarea name="next_session_notes" class="form-textarea">{{ old('next_session_notes', $appointment->session?->next_session_notes) }}</textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Front Desk Note</label>
          <textarea class="form-textarea" disabled>{{ $appointment->front_desk_note ?: 'No front desk note.' }}</textarea>
        </div>
      </div>

      <label style="display:flex;align-items:center;gap:8px;font-size:.84rem;margin-bottom:18px;">
        <input type="checkbox" name="follow_up_required" value="1" {{ old('follow_up_required', $appointment->follow_up_required) ? 'checked' : '' }}>
        Follow-up required
      </label>

      <button type="submit" class="btn btn-primary">Complete Visit</button>
    </form>
    @else
    <div style="display:grid;gap:12px;">
      <div style="padding:12px;border-radius:var(--radius-md);background:var(--bg-tertiary);">
        <div style="font-size:.74rem;color:var(--text-tertiary);text-transform:uppercase;">Room</div>
        <div>{{ $appointment->room?->name ?? 'TBD' }}</div>
      </div>
      <div style="padding:12px;border-radius:var(--radius-md);background:var(--bg-tertiary);">
        <div style="font-size:.74rem;color:var(--text-tertiary);text-transform:uppercase;">Device</div>
        <div>{{ $appointment->session?->device_used ?? $appointment->room?->device_name ?? 'Not set yet' }}</div>
      </div>
      <div style="padding:12px;border-radius:var(--radius-md);background:var(--bg-tertiary);">
        <div style="font-size:.74rem;color:var(--text-tertiary);text-transform:uppercase;">Treatment Area</div>
        <div>{{ collect($appointment->session?->treatment_areas)->implode(', ') ?: 'Not set yet' }}</div>
      </div>
    </div>
    @endif
  </div>

  <div style="display:flex;flex-direction:column;gap:18px;">
    <div class="card">
      <div class="card-title" style="margin-bottom:10px;">Visit Snapshot</div>
      <div style="display:grid;gap:8px;font-size:.82rem;">
        <div><strong>Technician:</strong> {{ $appointment->assignedStaff?->full_name ?? 'Not assigned' }}</div>
        <div><strong>Room:</strong> {{ $appointment->room?->name ?? 'TBD' }}</div>
        <div><strong>Device:</strong> {{ $appointment->room?->device_name ?? 'TBD' }}</div>
        <div><strong>Reason:</strong> {{ $appointment->reason_notes ?: ($appointment->service?->name ?? 'Visit') }}</div>
        <div><strong>Front Desk Note:</strong> {{ $appointment->front_desk_note ?: 'None' }}</div>
      </div>
    </div>

    @include('patients.partials.history-timeline', ['timeline' => $historyTimeline, 'patient' => $appointment->patient])
  </div>
</div>
@endsection
