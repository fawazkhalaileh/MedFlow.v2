@extends('layouts.app')

@section('title', 'Front Desk - MedFlow CRM')
@section('breadcrumb', 'Front Desk')

@section('content')

{{-- Flash --}}
@if(session('success'))
<div style="background:var(--success-light);border:1px solid #6ee7b7;border-radius:var(--radius-md);padding:10px 16px;margin-bottom:16px;color:#065f46;font-size:.85rem;">
  {{ session('success') }}
</div>
@endif

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Front Desk</h1>
    <p class="page-subtitle">Today's queue &bull; {{ now()->format('l, d F Y') }}</p>
  </div>
  <a href="{{ route('appointments.index') }}" class="btn btn-secondary">Full Schedule</a>
</div>

{{-- STAT CARDS --}}
<div class="kpi-grid animate-in" style="animation-delay:.04s;">

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
    <div class="kpi-label">Total Today</div>
    <div class="kpi-value">{{ $stats['total_today'] }}</div>
    <div class="kpi-change neutral">appointments</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
    <div class="kpi-label">In Clinic</div>
    <div class="kpi-value">{{ $stats['arrived'] }}</div>
    <div class="kpi-change up">arrived / active</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
    <div class="kpi-label">Completed</div>
    <div class="kpi-value">{{ $stats['completed'] }}</div>
    <div class="kpi-change up">done today</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
    <div class="kpi-label">Need Confirmation</div>
    <div class="kpi-value">{{ $stats['pending_confirm'] }}</div>
    <div class="kpi-change {{ $stats['pending_confirm'] > 0 ? 'neutral' : 'up' }}">next 48 h</div>
  </div>

</div>

<div class="grid-2-1 animate-in" style="animation-delay:.08s;align-items:start;">

  {{-- TODAY'S QUEUE --}}
  <div class="card" style="padding:0;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <div class="card-title">Today's Queue</div>
      <span class="badge badge-blue">{{ $stats['total_today'] }} total</span>
    </div>

    @if($queue->isEmpty())
    <div class="empty-state"><p>No appointments today</p></div>
    @else
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Time</th>
            <th>Patient</th>
            <th>Service</th>
            <th>Staff</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($queue as $appt)
          @php
            $statusColors = [
              'booked'          => 'badge-gray',
              'scheduled'       => 'badge-blue',
              'confirmed'       => 'badge-cyan',
              'arrived'         => 'badge-yellow',
              'checked_in'      => 'badge-yellow',
              'intake_complete' => 'badge-purple',
              'assigned'        => 'badge-purple',
              'in_room'         => 'badge-purple',
              'in_treatment'    => 'badge-purple',
              'completed'       => 'badge-green',
              'no_show'         => 'badge-red',
            ];
            $sc = $statusColors[$appt->status] ?? 'badge-gray';
            $nextStatus = match($appt->status) {
              'booked','scheduled','confirmed' => 'arrived',
              'arrived'    => 'checked_in',
              'checked_in' => 'intake_complete',
              default      => null,
            };
            $nextLabel = $nextStatus ? ucfirst(str_replace('_',' ',$nextStatus)) : null;
          @endphp
          <tr>
            <td style="font-weight:600;white-space:nowrap;font-size:.83rem;">{{ \Carbon\Carbon::parse($appt->scheduled_at)->format('h:i A') }}</td>
            <td>
              <div style="font-weight:500;font-size:.85rem;">{{ $appt->patient?->full_name }}</div>
              <div style="font-size:.72rem;color:var(--text-tertiary);">{{ $appt->patient?->patient_code }}</div>
            </td>
            <td style="font-size:.82rem;color:var(--text-secondary);">{{ $appt->service?->name ?? '--' }}</td>
            <td style="font-size:.82rem;color:var(--text-secondary);">{{ $appt->assignedStaff?->first_name ?? '--' }}</td>
            <td><span class="badge {{ $sc }}">{{ ucfirst(str_replace('_',' ',$appt->status)) }}</span></td>
            <td>
              @if($nextStatus)
              <form method="POST" action="{{ route('appointments.status', $appt) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="{{ $nextStatus }}">
                <button type="submit" class="btn btn-secondary btn-sm" style="font-size:.73rem;white-space:nowrap;">→ {{ $nextLabel }}</button>
              </form>
              @else
              <span style="font-size:.75rem;color:var(--text-tertiary);">—</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>

  {{-- RIGHT COLUMN --}}
  <div style="display:flex;flex-direction:column;gap:18px;">

    {{-- Needs Confirmation --}}
    <div class="card">
      <div class="card-header">
        <div class="card-title">Needs Confirmation</div>
        <span class="badge {{ $stats['pending_confirm'] > 0 ? 'badge-yellow' : 'badge-green' }}">{{ $stats['pending_confirm'] }}</span>
      </div>
      @forelse($needsConfirmation as $appt)
      <div style="padding:9px 0;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;gap:10px;">
        <div>
          <div style="font-size:.84rem;font-weight:500;">{{ $appt->patient?->full_name }}</div>
          <div style="font-size:.74rem;color:var(--text-tertiary);">
            {{ \Carbon\Carbon::parse($appt->scheduled_at)->format('d M, h:i A') }}
            &bull; {{ $appt->service?->name ?? '--' }}
          </div>
        </div>
        <form method="POST" action="{{ route('appointments.status', $appt) }}">
          @csrf @method('PATCH')
          <input type="hidden" name="status" value="confirmed">
          <button type="submit" class="btn btn-secondary btn-sm" style="font-size:.72rem;">Confirm</button>
        </form>
      </div>
      @empty
      <p style="color:var(--text-tertiary);font-size:.83rem;padding:10px 0;">All appointments confirmed</p>
      @endforelse
    </div>

    {{-- My Follow-ups --}}
    <div class="card">
      <div class="card-header">
        <div class="card-title">My Follow-ups</div>
        <a href="{{ route('followups.index') }}" class="btn btn-ghost btn-sm">View all</a>
      </div>
      @forelse($myFollowUps as $fu)
      <div style="padding:8px 0;border-bottom:1px solid var(--border-light);display:flex;align-items:center;gap:10px;">
        <div class="activity-dot" style="background:{{ $fu->due_date < today() ? 'var(--danger)' : 'var(--warning)' }};width:8px;height:8px;border-radius:50%;flex-shrink:0;"></div>
        <div>
          <div style="font-size:.84rem;font-weight:500;">{{ $fu->patient?->full_name }}</div>
          <div style="font-size:.73rem;color:var(--text-tertiary);">
            {{ ucfirst($fu->type) }} &bull; Due {{ \Carbon\Carbon::parse($fu->due_date)->diffForHumans() }}
          </div>
        </div>
      </div>
      @empty
      <p style="color:var(--text-tertiary);font-size:.83rem;padding:10px 0;">No pending follow-ups</p>
      @endforelse
    </div>

    {{-- No Shows --}}
    @if($stats['no_show'] > 0)
    <div class="card" style="border:1px solid #fca5a5;">
      <div class="card-title" style="color:var(--danger);margin-bottom:10px;">No Shows Today — {{ $stats['no_show'] }}</div>
      @foreach($queue->where('status','no_show') as $appt)
      <div style="font-size:.83rem;padding:5px 0;border-bottom:1px solid var(--border-light);">
        {{ $appt->patient?->full_name }} &bull; {{ \Carbon\Carbon::parse($appt->scheduled_at)->format('h:i A') }}
      </div>
      @endforeach
    </div>
    @endif

  </div>
</div>
@endsection
