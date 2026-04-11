@extends('layouts.app')

@section('title', 'Operations Board - MedFlow CRM')
@section('breadcrumb', 'Operations Board')

@section('content')

@if(session('success'))
<div style="background:var(--success-light);border:1px solid #6ee7b7;border-radius:var(--radius-md);padding:10px 16px;margin-bottom:16px;color:#065f46;font-size:.85rem;">
  {{ session('success') }}
</div>
@endif

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Operations Board</h1>
    <p class="page-subtitle">Live branch overview &bull; {{ now()->format('l, d F Y') }}</p>
  </div>
  <button onclick="location.reload()" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
    Refresh
  </button>
</div>

{{-- KPI ROW --}}
<div class="kpi-grid animate-in" style="animation-delay:.04s;">
  <div class="kpi-card">
    <div class="kpi-label">Total Today</div>
    <div class="kpi-value">{{ $stats['total'] }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">In Clinic Now</div>
    <div class="kpi-value" style="color:var(--accent);">{{ $stats['in_clinic'] }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Completed</div>
    <div class="kpi-value" style="color:var(--success);">{{ $stats['completed'] }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">No Shows</div>
    <div class="kpi-value" style="color:{{ $stats['no_shows'] > 0 ? 'var(--danger)' : 'var(--text-tertiary)' }};">{{ $stats['no_shows'] }}</div>
  </div>
</div>

{{-- ALERTS --}}
@if(count($alerts) > 0)
<div class="animate-in" style="animation-delay:.06s;margin-bottom:16px;">
  @foreach($alerts as $alert)
  <div style="display:flex;align-items:center;gap:12px;padding:10px 16px;margin-bottom:6px;border-radius:var(--radius-md);
    background:{{ $alert['type'] === 'red' ? 'var(--danger-light)' : 'var(--warning-light)' }};
    border:1px solid {{ $alert['type'] === 'red' ? '#fca5a5' : '#fde68a' }};">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;color:{{ $alert['type'] === 'red' ? 'var(--danger)' : 'var(--warning)' }};">
      <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
      <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
    </svg>
    <span style="font-size:.84rem;font-weight:500;flex:1;color:{{ $alert['type'] === 'red' ? '#991b1b' : '#92400e' }};">{{ $alert['message'] }}</span>
    <span style="font-size:.75rem;color:{{ $alert['type'] === 'red' ? '#b91c1c' : '#b45309' }};">{{ $alert['action'] }}</span>
  </div>
  @endforeach
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 260px;gap:18px;align-items:start;" class="animate-in" style="animation-delay:.08s;">

  {{-- PIPELINE --}}
  <div>
    <div style="overflow-x:auto;">
      <div style="display:flex;gap:12px;min-width:900px;align-items:start;">

        @php
          $pipelineConfig = [
            'booked'           => ['label'=>'Booked',      'color'=>'var(--text-tertiary)', 'badge'=>'badge-gray'],
            'confirmed'        => ['label'=>'Confirmed',   'color'=>'var(--info)',           'badge'=>'badge-cyan'],
            'arrived'          => ['label'=>'Arrived',     'color'=>'var(--warning)',         'badge'=>'badge-yellow'],
            'in_progress'      => ['label'=>'In Progress', 'color'=>'var(--accent)',          'badge'=>'badge-blue'],
            'review_needed'    => ['label'=>'Review',      'color'=>'#7c3aed',               'badge'=>'badge-purple'],
            'completed'        => ['label'=>'Done',        'color'=>'var(--success)',         'badge'=>'badge-green'],
            'follow_up_needed' => ['label'=>'Follow-up',  'color'=>'var(--warning)',         'badge'=>'badge-yellow'],
            'no_show'          => ['label'=>'No Show',     'color'=>'var(--danger)',          'badge'=>'badge-red'],
          ];
        @endphp

        @foreach($pipelineConfig as $key => $cfg)
        @php $col = $pipeline[$key] ?? collect(); @endphp
        <div style="flex:1;min-width:110px;">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
            <div style="width:8px;height:8px;border-radius:50%;background:{{ $cfg['color'] }};"></div>
            <span style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);">{{ $cfg['label'] }}</span>
            <span class="badge {{ $cfg['badge'] }}" style="margin-left:auto;font-size:.65rem;">{{ $col->count() }}</span>
          </div>

          @if($col->isEmpty())
          <div style="border:2px dashed var(--border);border-radius:var(--radius-md);padding:16px 8px;text-align:center;color:var(--text-tertiary);font-size:.75rem;">—</div>
          @endif

          @foreach($col as $appt)
          <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);padding:10px;margin-bottom:6px;box-shadow:var(--shadow-sm);">
            <div style="font-weight:600;font-size:.8rem;line-height:1.2;">{{ $appt->patient?->full_name }}</div>
            <div style="font-size:.72rem;color:var(--text-tertiary);margin-top:2px;">{{ $appt->service?->name ?? '--' }}</div>
            <div style="font-size:.72rem;color:var(--text-tertiary);">{{ \Carbon\Carbon::parse($appt->scheduled_at)->format('h:i A') }}</div>
            @if($appt->assignedStaff)
            <div style="font-size:.7rem;color:var(--accent);margin-top:4px;">{{ $appt->assignedStaff->first_name }}</div>
            @endif
          </div>
          @endforeach
        </div>
        @endforeach

      </div>
    </div>
  </div>

  {{-- STAFF LOAD --}}
  <div>
    <div class="card">
      <div class="card-header" style="margin-bottom:12px;">
        <div class="card-title">Staff Today</div>
      </div>
      @forelse($staff as $s)
      <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light);">
        <div class="avatar avatar-sm" style="width:30px;height:30px;font-size:.7rem;flex-shrink:0;background:linear-gradient(135deg,var(--accent),#7c3aed);">
          {{ strtoupper(substr($s->first_name,0,1).substr($s->last_name,0,1)) }}
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:.83rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $s->first_name }} {{ $s->last_name }}</div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">{{ ucfirst($s->employee_type) }}</div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
          <div style="font-size:.8rem;font-weight:600;">{{ $s->today_count }}</div>
          @if($s->active_count > 0)
          <div style="font-size:.7rem;color:var(--accent);">{{ $s->active_count }} active</div>
          @endif
        </div>
      </div>
      @empty
      <p style="color:var(--text-tertiary);font-size:.82rem;">No active staff</p>
      @endforelse
    </div>
  </div>

</div>
@endsection
