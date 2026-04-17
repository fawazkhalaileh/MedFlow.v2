@extends('layouts.app')

@section('title', 'Operations Board - MedFlow CRM')
@section('breadcrumb', 'Operations Board')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Operations Board</h1>
    <p class="page-subtitle">Manager overview of today&apos;s role-based appointment flow.</p>
  </div>
</div>

<div class="kpi-grid animate-in" style="margin-bottom:20px;">
  <div class="kpi-card"><div class="kpi-label">Total</div><div class="kpi-value">{{ $stats['total'] }}</div></div>
  <div class="kpi-card"><div class="kpi-label">In Clinic</div><div class="kpi-value">{{ $stats['in_clinic'] }}</div></div>
  <div class="kpi-card"><div class="kpi-label">Completed</div><div class="kpi-value">{{ $stats['completed'] }}</div></div>
  <div class="kpi-card"><div class="kpi-label">No Show</div><div class="kpi-value">{{ $stats['no_show'] }}</div></div>
</div>

<div style="display:grid;grid-template-columns:repeat({{ count($columns) }}, minmax(220px, 1fr));gap:16px;align-items:start;" class="animate-in">
  @foreach($columns as $column)
  <div class="card" style="padding:16px;">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
      <span style="width:10px;height:10px;border-radius:50%;background:{{ $column['color'] }};"></span>
      <div style="font-weight:600;">{{ $column['label'] }}</div>
      <span class="badge badge-gray" style="margin-left:auto;">{{ $column['items']->count() }}</span>
    </div>
    @forelse($column['items'] as $appointment)
    <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius-md);margin-bottom:10px;background:var(--bg-tertiary);">
      <div style="font-weight:600;">{{ $appointment->patient?->full_name }}</div>
      <div style="font-size:.8rem;color:var(--text-secondary);">{{ $appointment->service?->name ?? 'Visit' }}</div>
      <div style="font-size:.76rem;color:var(--text-tertiary);">
        {{ $appointment->isDoctorVisit() ? 'Doctor' : 'Technician' }} | {{ $appointment->assignedStaff?->full_name ?? 'Unassigned' }}
      </div>
    </div>
    @empty
    <div style="border:2px dashed var(--border);border-radius:var(--radius-md);padding:18px;text-align:center;color:var(--text-tertiary);font-size:.82rem;">Empty</div>
    @endforelse
  </div>
  @endforeach
</div>
@endsection
