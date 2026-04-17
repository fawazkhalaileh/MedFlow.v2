@extends('layouts.app')

@section('title', 'Doctor Queue - MedFlow CRM')
@section('breadcrumb', 'Doctor Queue')

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Doctor Queue</h1>
    <p class="page-subtitle">Only your medical waiting list, active visits, and clinical notes.</p>
  </div>
</div>

<div class="grid-2-1 animate-in" style="align-items:start;">
  <div style="display:flex;flex-direction:column;gap:18px;">
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Waiting Doctor</div>
          <div class="card-subtitle">Patients ready for medical review.</div>
        </div>
        <span class="badge badge-yellow">{{ $waiting->count() }}</span>
      </div>
      @forelse($waiting as $appointment)
      <div style="padding:12px 0;border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
        <div>
          <div style="font-weight:600;">{{ $appointment->patient?->full_name }}</div>
          <div style="font-size:.8rem;color:var(--text-secondary);">{{ $appointment->service?->name ?? 'Medical visit' }} | {{ $appointment->scheduled_at?->format('h:i A') }}</div>
          <div style="font-size:.76rem;color:var(--text-tertiary);">{{ \Illuminate\Support\Str::headline($appointment->status) }}</div>
          @if($appointment->patient?->medicalInfo?->hasContraindications())
          <div style="font-size:.75rem;color:var(--danger);margin-top:4px;">Contraindication flag present</div>
          @endif
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
          @if($appointment->status === \App\Models\Appointment::STATUS_WAITING_DOCTOR)
          <form method="POST" action="{{ route('appointments.doctor.start', $appointment) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-primary btn-sm">Start Visit</button>
          </form>
          @endif
          <a href="{{ route('appointments.doctor.show', $appointment) }}" class="btn btn-secondary btn-sm">Open Chart</a>
        </div>
      </div>
      @empty
      <div class="empty-state" style="padding:24px 12px;"><p>No patients waiting for doctor.</p></div>
      @endforelse
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Active Visits</div>
          <div class="card-subtitle">Visits currently in progress.</div>
        </div>
        <span class="badge badge-blue">{{ $active->count() }}</span>
      </div>
      @forelse($active as $appointment)
      <div style="padding:12px 0;border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;gap:10px;">
        <div>
          <div style="font-weight:600;">{{ $appointment->patient?->full_name }}</div>
          <div style="font-size:.8rem;color:var(--text-secondary);">{{ $appointment->service?->name ?? 'Medical visit' }}</div>
          <div style="font-size:.76rem;color:var(--text-tertiary);">Started {{ optional($appointment->provider_started_at)->diffForHumans() }}</div>
        </div>
        <a href="{{ route('appointments.doctor.show', $appointment) }}" class="btn btn-primary btn-sm">Continue Visit</a>
      </div>
      @empty
      <div class="empty-state" style="padding:24px 12px;"><p>No active doctor visits.</p></div>
      @endforelse
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Completed Today</div>
          <div class="card-subtitle">Returned to front desk for checkout.</div>
        </div>
        <span class="badge badge-green">{{ $done->count() }}</span>
      </div>
      @forelse($done as $appointment)
      <div style="padding:10px 0;border-bottom:1px solid var(--border-light);">
        <div style="font-weight:600;">{{ $appointment->patient?->full_name }}</div>
        <div style="font-size:.76rem;color:var(--text-tertiary);">{{ \Illuminate\Support\Str::headline($appointment->status) }}</div>
      </div>
      @empty
      <div class="empty-state" style="padding:24px 12px;"><p>No completed doctor visits yet.</p></div>
      @endforelse
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:18px;">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Consent Pending</div>
        <span class="badge {{ $consentPending->isNotEmpty() ? 'badge-yellow' : 'badge-green' }}">{{ $consentPending->count() }}</span>
      </div>
      @forelse($consentPending as $patient)
      <div style="padding:8px 0;border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;gap:8px;">
        <div>
          <div style="font-weight:600;font-size:.84rem;">{{ $patient->full_name }}</div>
          <div style="font-size:.76rem;color:var(--text-tertiary);">{{ $patient->branch?->name }}</div>
        </div>
        <a href="{{ route('patients.show', $patient) }}" class="btn btn-secondary btn-sm">Open</a>
      </div>
      @empty
      <p style="font-size:.82rem;color:var(--text-tertiary);">No consent issues in your current queue.</p>
      @endforelse
    </div>

    <div class="card">
      <div class="card-title" style="margin-bottom:10px;">Doctor Workflow</div>
      <div style="display:flex;flex-direction:column;gap:8px;font-size:.82rem;color:var(--text-secondary);">
        <div>1. Start visit from the waiting doctor list.</div>
        <div>2. Review patient history and document medical findings.</div>
        <div>3. Complete the visit to return the patient to front desk checkout.</div>
      </div>
    </div>
  </div>
</div>
@endsection
