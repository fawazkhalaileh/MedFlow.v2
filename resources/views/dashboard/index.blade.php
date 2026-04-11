@extends('layouts.app')

@section('title', 'Dashboard - MedFlow CRM')
@section('breadcrumb', 'Dashboard')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ Auth::user()->first_name ?? Auth::user()->name }}</h1>
    <p class="page-subtitle">Here's what's happening across your clinics today &mdash; {{ now()->format('l, d F Y') }}</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      View Schedule
    </a>
  </div>
</div>

{{-- KPI CARDS --}}
<div class="kpi-grid animate-in" style="animation-delay:.05s">
  <div class="kpi-card">
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </div>
    <div class="kpi-label">Total Patients</div>
    <div class="kpi-value">{{ number_format($kpi['total_patients']) }}</div>
    <div class="kpi-change up">{{ $kpi['active_patients'] }} active</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    </div>
    <div class="kpi-label">Today's Appointments</div>
    <div class="kpi-value">{{ $kpi['today_appointments'] }}</div>
    <div class="kpi-change {{ $kpi['today_completed'] > 0 ? 'up' : 'neutral' }}">{{ $kpi['today_completed'] }} completed</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
    </div>
    <div class="kpi-label">Active Plans</div>
    <div class="kpi-value">{{ $kpi['active_plans'] }}</div>
    <div class="kpi-change neutral">treatment plans</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.45 2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
    </div>
    <div class="kpi-label">Pending Follow-ups</div>
    <div class="kpi-value">{{ $kpi['pending_followups'] }}</div>
    <div class="kpi-change {{ $kpi['open_leads'] > 0 ? 'neutral' : 'up' }}">{{ $kpi['open_leads'] }} open leads</div>
  </div>
</div>

{{-- MAIN GRID --}}
<div class="grid-2-1 animate-in" style="animation-delay:.1s">

  {{-- Today's Appointments --}}
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Today's Appointments</div>
        <div class="card-subtitle">{{ now()->format('d M Y') }}</div>
      </div>
      <a href="{{ route('appointments.index') }}" class="btn btn-secondary btn-sm">View all</a>
    </div>
    @if($todayAppointments->isEmpty())
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <h3>No appointments today</h3>
        <p>Enjoy the quiet day!</p>
      </div>
    @else
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>Customer</th>
              <th>Service</th>
              <th>Staff</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($todayAppointments as $appt)
            <tr>
              <td style="font-weight:600;white-space:nowrap;">{{ \Carbon\Carbon::parse($appt->scheduled_at)->format('h:i A') }}</td>
              <td>
                <div style="font-weight:500;">{{ $appt->patient?->full_name ?? 'Unknown' }}</div>
                <div style="font-size:.76rem;color:var(--text-tertiary);">{{ $appt->patient?->patient_code }}</div>
              </td>
              <td style="color:var(--text-secondary);font-size:.83rem;">{{ $appt->service?->name ?? '--' }}</td>
              <td style="color:var(--text-secondary);font-size:.83rem;">{{ $appt->assignedStaff?->first_name ?? '--' }}</td>
              <td>
                @php
                  $statusMap = [
                    'scheduled'   => 'badge-blue',
                    'confirmed'   => 'badge-cyan',
                    'arrived'     => 'badge-yellow',
                    'in_progress' => 'badge-purple',
                    'completed'   => 'badge-green',
                    'cancelled'   => 'badge-red',
                    'no_show'     => 'badge-gray',
                  ];
                  $cls = $statusMap[$appt->status] ?? 'badge-gray';
                @endphp
                <span class="badge {{ $cls }}">{{ ucfirst(str_replace('_',' ',$appt->status)) }}</span>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Right column --}}
  <div style="display:flex;flex-direction:column;gap:18px;">

    {{-- Branches Overview --}}
    <div class="card">
      <div class="card-header">
        <div class="card-title">Branches</div>
        <a href="{{ route('branches.index') }}" class="btn btn-ghost btn-sm">Manage</a>
      </div>
      @foreach($branches as $branch)
      <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);">
        <div style="display:flex;align-items:center;gap:9px;">
          <div class="status-dot {{ $branch->status === 'active' ? 'active' : 'inactive' }}"></div>
          <div>
            <div style="font-size:.85rem;font-weight:500;">{{ $branch->name }}</div>
            <div style="font-size:.73rem;color:var(--text-tertiary);">{{ $branch->city }}</div>
          </div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:.82rem;font-weight:600;">{{ $branch->patients_count }} <span style="color:var(--text-tertiary);font-weight:400;">clients</span></div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">{{ $branch->staff_count }} staff</div>
        </div>
      </div>
      @endforeach
    </div>

    {{-- Pending Follow-ups --}}
    <div class="card">
      <div class="card-header">
        <div class="card-title">Pending Follow-ups</div>
        <a href="{{ route('followups.index') }}" class="btn btn-ghost btn-sm">View all</a>
      </div>
      @forelse($pendingFollowUps as $fu)
      <div class="activity-item">
        <div class="activity-dot" style="background:{{ $fu->due_date < today() ? 'var(--danger)' : 'var(--warning)' }}"></div>
        <div class="activity-text">
          <strong>{{ $fu->patient?->full_name }}</strong>
          &mdash; {{ ucfirst($fu->type) }}
          <div class="activity-time">Due {{ \Carbon\Carbon::parse($fu->due_date)->diffForHumans() }}</div>
        </div>
      </div>
      @empty
      <p style="color:var(--text-tertiary);font-size:.84rem;padding:10px 0;">No pending follow-ups</p>
      @endforelse
    </div>

  </div>
</div>

{{-- Recent Patients --}}
<div class="card animate-in" style="margin-top:18px;animation-delay:.15s">
  <div class="card-header">
    <div>
      <div class="card-title">Recent Patients</div>
      <div class="card-subtitle">Latest registrations</div>
    </div>
    <a href="{{ route('patients.index') }}" class="btn btn-secondary btn-sm">View all</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Customer</th>
          <th>Code</th>
          <th>Phone</th>
          <th>Branch</th>
          <th>Status</th>
          <th>Registered</th>
        </tr>
      </thead>
      <tbody>
        @foreach($recentPatients as $c)
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:9px;">
              <div class="avatar avatar-sm" style="background:linear-gradient(135deg,#{{ substr(md5($c->first_name),0,6) }},#{{ substr(md5($c->last_name),0,6) }});font-size:.68rem;">
                {{ strtoupper(substr($c->first_name,0,1).substr($c->last_name ?? '',0,1)) }}
              </div>
              <div>
                <div style="font-weight:500;">{{ $c->full_name }}</div>
                <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $c->email }}</div>
              </div>
            </div>
          </td>
          <td><span style="font-family:monospace;font-size:.82rem;color:var(--text-secondary);">{{ $c->patient_code }}</span></td>
          <td style="color:var(--text-secondary);">{{ $c->phone }}</td>
          <td style="color:var(--text-secondary);">{{ $c->branch?->name ?? '--' }}</td>
          <td><span class="badge {{ $c->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($c->status) }}</span></td>
          <td style="color:var(--text-tertiary);font-size:.82rem;">{{ $c->created_at->format('d M Y') }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
