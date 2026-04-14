@extends('layouts.app')

@section('title', 'My Queue - MedFlow CRM')
@section('breadcrumb', 'My Queue')

@section('content')

@if(session('success'))
<div style="background:var(--success-light);border:1px solid #6ee7b7;border-radius:var(--radius-md);padding:10px 16px;margin-bottom:16px;color:#065f46;font-size:.85rem;">
  {{ session('success') }}
</div>
@endif

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ __('My Queue') }}</h1>
    <p class="page-subtitle">{{ now()->format('l, d F Y') }}</p>
  </div>
  <div class="header-actions">
    <span class="badge badge-blue" style="padding:6px 14px;">{{ $stats['total'] }} assigned today</span>
    <span class="badge badge-green" style="padding:6px 14px;">{{ $stats['done'] }} done</span>
    <span class="badge badge-yellow" style="padding:6px 14px;">{{ $stats['remaining'] }} remaining</span>
  </div>
</div>

@if($myAppointments->isEmpty())
<div class="card animate-in">
  <div class="empty-state">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    <h3>No appointments assigned to you today</h3>
    <p>Check back later or contact your branch manager.</p>
  </div>
</div>
@else

{{-- KANBAN BOARD --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;align-items:start;" class="animate-in" style="animation-delay:.06s;">

  {{-- WAITING --}}
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
      <div style="width:10px;height:10px;border-radius:50%;background:var(--warning);"></div>
      <span style="font-weight:600;font-size:.82rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;">Waiting</span>
      <span class="badge badge-yellow" style="margin-left:auto;">{{ $waiting->count() }}</span>
    </div>
    @forelse($waiting as $appt)
    @include('workspaces._queue-card', ['appt' => $appt, 'nextStatus' => 'in_room', 'nextLabel' => 'Move to Room'])
    @empty
    <div style="border:2px dashed var(--border);border-radius:var(--radius-md);padding:20px;text-align:center;color:var(--text-tertiary);font-size:.8rem;">Empty</div>
    @endforelse
  </div>

  {{-- IN ROOM / PREP --}}
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
      <div style="width:10px;height:10px;border-radius:50%;background:#8b5cf6;"></div>
      <span style="font-weight:600;font-size:.82rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;">In Room</span>
      <span class="badge badge-purple" style="margin-left:auto;">{{ $inPrep->count() }}</span>
    </div>
    @forelse($inPrep as $appt)
    @include('workspaces._queue-card', ['appt' => $appt, 'nextStatus' => 'in_treatment', 'nextLabel' => 'Start Treatment'])
    @empty
    <div style="border:2px dashed var(--border);border-radius:var(--radius-md);padding:20px;text-align:center;color:var(--text-tertiary);font-size:.8rem;">Empty</div>
    @endforelse
  </div>

  {{-- IN TREATMENT --}}
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
      <div style="width:10px;height:10px;border-radius:50%;background:var(--accent);"></div>
      <span style="font-weight:600;font-size:.82rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;">In Treatment</span>
      <span class="badge badge-blue" style="margin-left:auto;">{{ $inSession->count() }}</span>
    </div>
    @forelse($inSession as $appt)
    @include('workspaces._queue-card', ['appt' => $appt, 'nextStatus' => 'completed', 'nextLabel' => 'Complete'])
    @empty
    <div style="border:2px dashed var(--border);border-radius:var(--radius-md);padding:20px;text-align:center;color:var(--text-tertiary);font-size:.8rem;">Empty</div>
    @endforelse
  </div>

  {{-- DONE --}}
  <div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
      <div style="width:10px;height:10px;border-radius:50%;background:var(--success);"></div>
      <span style="font-weight:600;font-size:.82rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.5px;">Done</span>
      <span class="badge badge-green" style="margin-left:auto;">{{ $done->count() }}</span>
    </div>
    @forelse($done as $appt)
    @include('workspaces._queue-card', ['appt' => $appt, 'nextStatus' => null, 'nextLabel' => null])
    @empty
    <div style="border:2px dashed var(--border);border-radius:var(--radius-md);padding:20px;text-align:center;color:var(--text-tertiary);font-size:.8rem;">Empty</div>
    @endforelse
  </div>

</div>
@endif
@endsection
