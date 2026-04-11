@extends('layouts.app')

@section('title', 'Appointments - MedFlow CRM')
@section('breadcrumb', 'Appointments')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Appointments</h1>
    <p class="page-subtitle">Schedule and manage patient appointments</p>
  </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('appointments.index') }}">
<div class="filter-bar animate-in" style="animation-delay:.05s">
  <input type="date" name="date" class="filter-select" value="{{ $date }}" onchange="this.form.submit()">
  <select name="branch" class="filter-select" onchange="this.form.submit()">
    <option value="">All Branches</option>
    @foreach($branches as $b)
    <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
    @endforeach
  </select>
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="">All Statuses</option>
    @foreach($statuses as $s)
    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
    @endforeach
  </select>
  @if(request()->anyFilled(['branch','status']) || request('date') !== today()->format('Y-m-d'))
  <a href="{{ route('appointments.index') }}" class="btn btn-ghost btn-sm">Reset</a>
  @endif
</div>
</form>

{{-- Date Header --}}
<div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;" class="animate-in" style="animation-delay:.08s">
  <div style="font-size:.9rem;font-weight:600;">
    {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
    @if($date === today()->format('Y-m-d'))
    <span class="badge badge-blue" style="margin-left:8px;">Today</span>
    @endif
  </div>
  <div style="margin-left:auto;display:flex;gap:8px;">
    <a href="{{ route('appointments.index', ['date' => \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d')]) }}" class="btn btn-secondary btn-sm">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Prev
    </a>
    <a href="{{ route('appointments.index', ['date' => today()->format('Y-m-d')]) }}" class="btn btn-secondary btn-sm">Today</a>
    <a href="{{ route('appointments.index', ['date' => \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d')]) }}" class="btn btn-secondary btn-sm">
      Next
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
  </div>
</div>

<div class="card animate-in" style="padding:0;animation-delay:.1s">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Time</th>
          <th>Patient</th>
          <th>Service</th>
          <th>Staff</th>
          <th>Branch</th>
          <th>Duration</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($appointments as $appt)
        <tr>
          <td style="font-weight:600;white-space:nowrap;font-size:.88rem;">
            {{ \Carbon\Carbon::parse($appt->scheduled_at)->format('h:i A') }}
          </td>
          <td>
            <div style="font-weight:500;">{{ $appt->patient?->full_name ?? 'Unknown' }}</div>
            <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $appt->patient?->phone }}</div>
          </td>
          <td style="color:var(--text-secondary);font-size:.84rem;">{{ $appt->service?->name ?? '--' }}</td>
          <td style="color:var(--text-secondary);font-size:.84rem;">
            {{ $appt->assignedStaff ? $appt->assignedStaff->first_name.' '.$appt->assignedStaff->last_name : '--' }}
          </td>
          <td style="color:var(--text-secondary);font-size:.82rem;">{{ $appt->branch?->name ?? '--' }}</td>
          <td style="color:var(--text-secondary);font-size:.82rem;">
            {{ $appt->duration_minutes ? $appt->duration_minutes.' min' : '--' }}
          </td>
          <td>
            @php
              $sc = [
                'scheduled'   => 'badge-blue',
                'confirmed'   => 'badge-cyan',
                'arrived'     => 'badge-yellow',
                'in_progress' => 'badge-purple',
                'completed'   => 'badge-green',
                'cancelled'   => 'badge-red',
                'no_show'     => 'badge-gray',
                'rescheduled' => 'badge-yellow',
              ][$appt->status] ?? 'badge-gray';
            @endphp
            <span class="badge {{ $sc }}">{{ ucfirst(str_replace('_',' ',$appt->status)) }}</span>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <h3>No appointments found</h3>
              <p>No appointments scheduled for this day or filter.</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($appointments->hasPages())
  <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
    <div style="font-size:.82rem;color:var(--text-secondary);">
      Showing {{ $appointments->firstItem() }}–{{ $appointments->lastItem() }} of {{ $appointments->total() }}
    </div>
    <div style="display:flex;gap:6px;">
      @if($appointments->onFirstPage())
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Previous</span>
      @else
        <a href="{{ $appointments->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
      @endif
      @if($appointments->hasMorePages())
        <a href="{{ $appointments->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
      @else
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Next</span>
      @endif
    </div>
  </div>
  @endif
</div>
@endsection
