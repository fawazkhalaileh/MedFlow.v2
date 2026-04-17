@extends('layouts.app')

@section('title', 'Technician Queue - MedFlow CRM')
@section('breadcrumb', 'Technician Queue')

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Technician Queue</h1>
    <p class="page-subtitle">Only your treatment visits, room details, and session actions.</p>
  </div>
  <div class="header-actions">
    <span class="badge badge-yellow">{{ $stats['waiting'] }} waiting</span>
    <span class="badge badge-blue">{{ $stats['active'] }} active</span>
    <span class="badge badge-green">{{ $stats['done'] }} completed</span>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;align-items:start;" class="animate-in">
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Waiting Technician</div>
        <div class="card-subtitle">Patients ready to start treatment.</div>
      </div>
      <span class="badge badge-yellow">{{ $waiting->count() }}</span>
    </div>
    @forelse($waiting as $appointment)
    <div style="padding:12px 0;border-bottom:1px solid var(--border-light);">
      <div style="font-weight:600;">{{ $appointment->patient?->full_name }}</div>
      <div style="font-size:.8rem;color:var(--text-secondary);">{{ $appointment->service?->name ?? 'Visit' }} | {{ $appointment->scheduled_at?->format('h:i A') }}</div>
      <div style="font-size:.76rem;color:var(--text-tertiary);margin:3px 0 10px;">Room {{ $appointment->room?->name ?? 'TBD' }}</div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <form method="POST" action="{{ route('appointments.technician.start', $appointment) }}">
          @csrf @method('PATCH')
          <button type="submit" class="btn btn-primary btn-sm">Start Visit</button>
        </form>
        <a href="{{ route('appointments.technician.show', $appointment) }}" class="btn btn-secondary btn-sm">Open Session</a>
      </div>
    </div>
    @empty
    <div class="empty-state" style="padding:24px 12px;"><p>No patients waiting for you.</p></div>
    @endforelse
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">In Treatment</div>
        <div class="card-subtitle">Current active treatment sessions.</div>
      </div>
      <span class="badge badge-blue">{{ $active->count() }}</span>
    </div>
    @forelse($active as $appointment)
    <div style="padding:12px 0;border-bottom:1px solid var(--border-light);">
      <div style="font-weight:600;">{{ $appointment->patient?->full_name }}</div>
      <div style="font-size:.8rem;color:var(--text-secondary);">{{ $appointment->service?->name ?? 'Visit' }} | Started {{ optional($appointment->provider_started_at)->diffForHumans() }}</div>
      <div style="font-size:.76rem;color:var(--text-tertiary);margin:3px 0 10px;">Room {{ $appointment->room?->name ?? 'TBD' }}</div>
      <a href="{{ route('appointments.technician.show', $appointment) }}" class="btn btn-primary btn-sm">Continue Session</a>
    </div>
    @empty
    <div class="empty-state" style="padding:24px 12px;"><p>No active treatment sessions.</p></div>
    @endforelse
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Completed Today</div>
        <div class="card-subtitle">Finished and returned to front desk.</div>
      </div>
      <span class="badge badge-green">{{ $done->count() }}</span>
    </div>
    @forelse($done as $appointment)
    <div style="padding:12px 0;border-bottom:1px solid var(--border-light);">
      <div style="font-weight:600;">{{ $appointment->patient?->full_name }}</div>
      <div style="font-size:.8rem;color:var(--text-secondary);">{{ $appointment->service?->name ?? 'Visit' }}</div>
      <div style="font-size:.76rem;color:var(--text-tertiary);">{{ \Illuminate\Support\Str::headline($appointment->status) }}</div>
    </div>
    @empty
    <div class="empty-state" style="padding:24px 12px;"><p>No completed sessions yet.</p></div>
    @endforelse
  </div>
</div>
@endsection
