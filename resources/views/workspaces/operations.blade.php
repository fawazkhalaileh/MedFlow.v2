@extends('layouts.app')

@section('title', 'Operations Board - MedFlow CRM')
@section('breadcrumb', 'Operations Board')

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Operations Board</h1>
    <p class="page-subtitle">Live branch overview for front desk, doctor, technician, and checkout flow.</p>
  </div>
  <a href="{{ route('appointments.kanban') }}" class="btn btn-secondary">Compact Board</a>
</div>

<div class="kpi-grid animate-in" style="margin-bottom:20px;">
  <div class="kpi-card"><div class="kpi-label">Total Today</div><div class="kpi-value">{{ $stats['total'] }}</div></div>
  <div class="kpi-card"><div class="kpi-label">In Clinic</div><div class="kpi-value">{{ $stats['in_clinic'] }}</div></div>
  <div class="kpi-card"><div class="kpi-label">Completed</div><div class="kpi-value">{{ $stats['completed'] }}</div></div>
  <div class="kpi-card"><div class="kpi-label">No Shows</div><div class="kpi-value">{{ $stats['no_shows'] }}</div></div>
</div>

@if(count($alerts))
<div class="card animate-in" style="margin-bottom:18px;">
  <div class="card-title" style="margin-bottom:12px;">Alerts</div>
  @foreach($alerts as $alert)
  <div style="padding:10px 12px;border-radius:var(--radius-md);margin-bottom:8px;background:{{ $alert['type'] === 'red' ? 'var(--danger-light)' : 'var(--warning-light)' }};">
    <div style="font-weight:600;">{{ $alert['message'] }}</div>
    <div style="font-size:.78rem;color:var(--text-secondary);">{{ $alert['action'] }}</div>
  </div>
  @endforeach
</div>
@endif

<div class="grid-2-1 animate-in" style="align-items:start;">
  <div style="display:grid;grid-template-columns:repeat(5,minmax(180px,1fr));gap:14px;">
    @php
      $labels = [
        'front_desk' => ['title' => 'Front Desk', 'badge' => 'badge-gray'],
        'doctor' => ['title' => 'Doctor Flow', 'badge' => 'badge-yellow'],
        'technician' => ['title' => 'Technician Flow', 'badge' => 'badge-purple'],
        'checkout' => ['title' => 'Checkout', 'badge' => 'badge-green'],
        'done' => ['title' => 'Done', 'badge' => 'badge-blue'],
      ];
    @endphp
    @foreach($labels as $key => $meta)
    <div class="card" style="padding:16px;">
      <div class="card-header" style="margin-bottom:12px;">
        <div class="card-title">{{ $meta['title'] }}</div>
        <span class="badge {{ $meta['badge'] }}">{{ $pipeline[$key]->count() }}</span>
      </div>
      @forelse($pipeline[$key] as $appointment)
      <div style="padding:10px;border:1px solid var(--border);border-radius:var(--radius-md);margin-bottom:8px;background:var(--bg-tertiary);">
        <div style="font-weight:600;font-size:.83rem;">{{ $appointment->patient?->full_name }}</div>
        <div style="font-size:.76rem;color:var(--text-secondary);">{{ $appointment->service?->name ?? 'Visit' }}</div>
        <div style="font-size:.72rem;color:var(--text-tertiary);">{{ \Illuminate\Support\Str::headline($appointment->status) }}</div>
      </div>
      @empty
      <div style="border:2px dashed var(--border);border-radius:var(--radius-md);padding:16px;text-align:center;color:var(--text-tertiary);font-size:.8rem;">Empty</div>
      @endforelse
    </div>
    @endforeach
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">Staff Load</div>
    </div>
    @forelse($staff as $member)
    <div style="display:flex;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-light);">
      <div>
        <div style="font-weight:600;font-size:.84rem;">{{ $member->full_name }}</div>
        <div style="font-size:.76rem;color:var(--text-tertiary);">{{ \Illuminate\Support\Str::headline($member->employee_type) }}</div>
      </div>
      <div style="text-align:right;font-size:.78rem;color:var(--text-secondary);">
        <div>{{ $member->today_count }} assigned</div>
        <div>{{ $member->active_count }} active</div>
      </div>
    </div>
    @empty
    <p style="font-size:.82rem;color:var(--text-tertiary);">No active staff today.</p>
    @endforelse
  </div>
</div>
@endsection
