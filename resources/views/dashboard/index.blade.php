@extends('layouts.app')

@section('title', 'Dashboard - MedFlow CRM')
@section('breadcrumb', 'Dashboard')

@push('page_style')
<style>
  /* ── Tab bar ─────────────────────────────────────────────── */
  .dash-tabs { display:flex; gap:4px; border-bottom:2px solid var(--border); margin-bottom:24px; }
  .dash-tab  {
    padding:9px 20px; font-size:.85rem; font-weight:600; color:var(--text-secondary);
    cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px;
    transition:color .15s, border-color .15s;
  }
  .dash-tab:hover { color:var(--text-primary); }
  .dash-tab.active { color:var(--primary); border-bottom-color:var(--primary); }

  .dash-panel { display:none; }
  .dash-panel.active { display:block; }

  /* ── Revenue KPI row ─────────────────────────────────────── */
  .rev-grid {
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:16px; margin-bottom:20px;
  }
  @media(max-width:900px){ .rev-grid{ grid-template-columns:repeat(2,1fr); } }
  @media(max-width:500px){ .rev-grid{ grid-template-columns:1fr; } }

  .rev-card {
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:var(--radius-lg);
    padding:20px 22px;
    position:relative; overflow:hidden;
  }
  .rev-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:var(--accent-gradient, linear-gradient(90deg,var(--primary),var(--primary-light,#6d9ef7)));
  }
  .rev-card.green::before { background:linear-gradient(90deg,#10b981,#34d399); }
  .rev-card.blue::before  { background:linear-gradient(90deg,#3b82f6,#60a5fa); }
  .rev-card.amber::before { background:linear-gradient(90deg,#f59e0b,#fbbf24); }
  .rev-card.red::before   { background:linear-gradient(90deg,#ef4444,#f87171); }
  .rev-card.purple::before{ background:linear-gradient(90deg,#8b5cf6,#a78bfa); }

  .rev-label  { font-size:.73rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-tertiary); margin-bottom:6px; }
  .rev-value  { font-size:1.6rem; font-weight:700; color:var(--text-primary); line-height:1.1; }
  .rev-sub    { font-size:.76rem; color:var(--text-tertiary); margin-top:4px; }
  .rev-badge  { display:inline-flex; align-items:center; gap:3px; font-size:.74rem; font-weight:600;
                padding:2px 8px; border-radius:20px; margin-top:6px; }
  .rev-badge.up   { background:#d1fae5; color:#065f46; }
  .rev-badge.down { background:#fee2e2; color:#991b1b; }
  .rev-badge.flat { background:#e5e7eb; color:#374151; }

  /* ── Chart containers ────────────────────────────────────── */
  .chart-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:22px 24px; }
  .chart-title{ font-size:.9rem; font-weight:700; color:var(--text-primary); margin-bottom:4px; }
  .chart-sub  { font-size:.75rem; color:var(--text-tertiary); margin-bottom:18px; }
  .chart-wrap { position:relative; }

  /* ── Two-col chart grid ──────────────────────────────────── */
  .charts-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:18px; }
  @media(max-width:800px){ .charts-2{ grid-template-columns:1fr; } }

  /* ── Horizontal bar (branch / service) ───────────────────── */
  .hbar-row  { display:flex; align-items:center; gap:10px; padding:7px 0; border-bottom:1px solid var(--border-light); }
  .hbar-row:last-child { border-bottom:none; }
  .hbar-name { font-size:.82rem; font-weight:500; width:130px; flex-shrink:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .hbar-track{ flex:1; background:var(--border-light); border-radius:4px; height:7px; overflow:hidden; }
  .hbar-fill { height:100%; border-radius:4px; transition:width .4s ease; }
  .hbar-val  { font-size:.78rem; font-weight:600; color:var(--text-secondary); white-space:nowrap; width:80px; text-align:right; }

  /* ── Outstanding table tweaks ────────────────────────────── */
  .balance-pill {
    display:inline-block; padding:2px 9px; border-radius:20px;
    font-size:.76rem; font-weight:700;
    background:#fef3c7; color:#92400e;
  }
</style>
@endpush

@section('content')
{{-- Page header --}}
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ Auth::user()->first_name ?? Auth::user()->name }}</h1>
    <p class="page-subtitle">Here's what's happening across your clinics &mdash; {{ now()->format('l, d F Y') }}</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      {{ __('View Schedule') }}
    </a>
  </div>
</div>

{{-- Tab bar --}}
<div class="dash-tabs animate-in" style="animation-delay:.03s">
  <div class="dash-tab active" data-tab="overview">📊 {{ __('Overview') }}</div>
  <div class="dash-tab" data-tab="revenue">💰 {{ __('Revenue & Reports') }}</div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     TAB 1 — OVERVIEW
════════════════════════════════════════════════════════════ --}}
<div class="dash-panel active" id="tab-overview">

  {{-- Operational KPI cards --}}
  <div class="kpi-grid animate-in" style="animation-delay:.05s">
    <div class="kpi-card">
      <div class="kpi-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div class="kpi-label">{{ __('Total Patients') }}</div>
      <div class="kpi-value">{{ number_format($kpi['total_patients']) }}</div>
      <div class="kpi-change up">{{ number_format($kpi['active_patients']) }} {{ __('active') }}</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <div class="kpi-label">{{ __("Today's Appointments") }}</div>
      <div class="kpi-value">{{ $kpi['today_appointments'] }}</div>
      <div class="kpi-change {{ $kpi['today_completed'] > 0 ? 'up' : 'neutral' }}">{{ $kpi['today_completed'] }} {{ __('completed') }}</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      </div>
      <div class="kpi-label">{{ __('Active Plans') }}</div>
      <div class="kpi-value">{{ number_format($kpi['active_plans']) }}</div>
      <div class="kpi-change neutral">{{ __('treatment plans') }}</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.45 2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
      </div>
      <div class="kpi-label">{{ __('Pending Follow-ups') }}</div>
      <div class="kpi-value">{{ number_format($kpi['pending_followups']) }}</div>
      <div class="kpi-change {{ $kpi['open_leads'] > 0 ? 'neutral' : 'up' }}">{{ number_format($kpi['open_leads']) }} {{ __('open leads') }}</div>
    </div>
  </div>

  {{-- Main grid --}}
  <div class="grid-2-1 animate-in" style="animation-delay:.1s">

    {{-- Today's Appointments --}}
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ __("Today's Appointments") }}</div>
          <div class="card-subtitle">{{ now()->format('d M Y') }}</div>
        </div>
        <a href="{{ route('appointments.index') }}" class="btn btn-secondary btn-sm">{{ __('View all') }}</a>
      </div>
      @if($todayAppointments->isEmpty())
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <h3>{{ __('No appointments today') }}</h3>
          <p>{{ __('Enjoy the quiet day!') }}</p>
        </div>
      @else
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>{{ __('Time') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Service') }}</th><th>{{ __('Staff') }}</th><th>{{ __('Status') }}</th></tr>
            </thead>
            <tbody>
              @foreach($todayAppointments as $appt)
              <tr>
                <td style="font-weight:600;white-space:nowrap;">{{ \Carbon\Carbon::parse($appt->scheduled_at)->format('h:i A') }}</td>
                <td>
                  <div style="font-weight:500;">{{ $appt->patient?->full_name ?? 'Unknown' }}</div>
                  <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $appt->patient?->patient_code }}</div>
                </td>
                <td style="color:var(--text-secondary);font-size:.83rem;">{{ $appt->service?->name ?? '--' }}</td>
                <td style="color:var(--text-secondary);font-size:.83rem;">{{ $appt->assignedStaff?->first_name ?? '--' }}</td>
                <td>
                  @php $cls = ['booked'=>'badge-blue','arrived'=>'badge-yellow','waiting_doctor'=>'badge-yellow','waiting_technician'=>'badge-purple','in_doctor_visit'=>'badge-blue','in_technician_visit'=>'badge-blue','completed_waiting_checkout'=>'badge-green','checked_out'=>'badge-green','cancelled'=>'badge-red','no_show'=>'badge-gray'][$appt->status] ?? 'badge-gray'; @endphp
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

      {{-- Branches --}}
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ __('Branches') }}</div>
          @if(Auth::user()->isSuperAdmin())<a href="{{ route('admin.branches.index') }}" class="btn btn-ghost btn-sm">{{ __('Manage') }}</a>@endif
        </div>
        @forelse($branches as $branch)
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
        @empty
        <p style="color:var(--text-tertiary);font-size:.84rem;padding:10px 0;">{{ __('No branches configured') }}</p>
        @endforelse
      </div>

      {{-- Pending Follow-ups --}}
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ __('Pending Follow-ups') }}</div>
          <a href="{{ route('followups.index') }}" class="btn btn-ghost btn-sm">{{ __('View all') }}</a>
        </div>
        @forelse($pendingFollowUps as $fu)
        <div class="activity-item">
          <div class="activity-dot" style="background:{{ $fu->due_date < today() ? 'var(--danger)' : 'var(--warning)' }}"></div>
          <div class="activity-text">
            <strong>{{ $fu->patient?->full_name }}</strong> &mdash; {{ ucfirst($fu->type) }}
            <div class="activity-time">Due {{ \Carbon\Carbon::parse($fu->due_date)->diffForHumans() }}</div>
          </div>
        </div>
        @empty
        <p style="color:var(--text-tertiary);font-size:.84rem;padding:10px 0;">{{ __('No pending follow-ups') }} 🎉</p>
        @endforelse
      </div>

    </div>
  </div>

  {{-- Recent Patients --}}
  <div class="card animate-in" style="margin-top:18px;animation-delay:.15s">
    <div class="card-header">
      <div>
        <div class="card-title">{{ __('Recent Patients') }}</div>
        <div class="card-subtitle">{{ __('Latest registrations') }}</div>
      </div>
      <a href="{{ route('patients.index') }}" class="btn btn-secondary btn-sm">{{ __('View all') }}</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>{{ __('Patient') }}</th><th>{{ __('Code') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Status') }}</th><th>{{ __('Registered') }}</th></tr>
        </thead>
        <tbody>
          @foreach($recentPatients as $c)
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:9px;">
                <div class="avatar avatar-sm" style="background:linear-gradient(135deg,#{{ substr(md5($c->first_name),0,6) }},#{{ substr(md5($c->last_name??''),0,6) }});font-size:.68rem;">
                  {{ strtoupper(substr($c->first_name,0,1).substr($c->last_name??'',0,1)) }}
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

</div>{{-- /tab-overview --}}


{{-- ═══════════════════════════════════════════════════════════
     TAB 2 — REVENUE & REPORTS
════════════════════════════════════════════════════════════ --}}
<div class="dash-panel" id="tab-revenue">

  {{-- Revenue KPI cards --}}
  <div class="rev-grid animate-in" style="animation-delay:.05s">

    {{-- Collected this month --}}
    <div class="rev-card green">
      <div class="rev-label">{{ __('Collected This Month') }}</div>
      <div class="rev-value">{{ number_format($revenue['collected_this_month'], 0) }} <span style="font-size:.9rem;font-weight:500;color:var(--text-tertiary);">SAR</span></div>
      <div class="rev-sub">{{ __('vs') }} {{ number_format($revenue['collected_last_month'], 0) }} SAR {{ __('last month') }}</div>
      @if($revenue['mom_change'] !== null)
        <span class="rev-badge {{ $revenue['mom_change'] >= 0 ? 'up' : 'down' }}">
          {{ $revenue['mom_change'] >= 0 ? '↑' : '↓' }} {{ abs($revenue['mom_change']) }}% MoM
        </span>
      @endif
    </div>

    {{-- Booked this month --}}
    <div class="rev-card blue">
      <div class="rev-label">{{ __('Booked This Month') }}</div>
      <div class="rev-value">{{ number_format($revenue['booked_this_month'], 0) }} <span style="font-size:.9rem;font-weight:500;color:var(--text-tertiary);">SAR</span></div>
      <div class="rev-sub">{{ __('Total treatment plan value') }}</div>
      @php $collRate = $revenue['booked_this_month'] > 0 ? round($revenue['collected_this_month'] / $revenue['booked_this_month'] * 100, 1) : 0; @endphp
      <span class="rev-badge flat">{{ $collRate }}% collected</span>
    </div>

    {{-- Outstanding --}}
    <div class="rev-card amber">
      <div class="rev-label">{{ __('Outstanding Balance') }}</div>
      <div class="rev-value">{{ number_format($revenue['outstanding'], 0) }} <span style="font-size:.9rem;font-weight:500;color:var(--text-tertiary);">SAR</span></div>
      <div class="rev-sub">{{ __('Unpaid on active plans') }}</div>
    </div>

    {{-- Collection rate all time --}}
    <div class="rev-card purple">
      <div class="rev-label">{{ __('Collection Rate (All Time)') }}</div>
      <div class="rev-value">{{ $revenue['collection_rate'] }}<span style="font-size:1rem;">%</span></div>
      <div class="rev-sub">{{ number_format($revenue['total_all_time'], 0) }} / {{ number_format($revenue['total_booked'], 0) }} SAR</div>
    </div>

  </div>

  {{-- Revenue trend + Appointment trend --}}
  <div class="charts-2 animate-in" style="animation-delay:.08s">

    <div class="chart-card">
      <div class="chart-title">{{ __('Revenue Trend') }}</div>
      <div class="chart-sub">{{ __('Booked vs Collected — last 6 months') }}</div>
      <div class="chart-wrap" style="height:220px;">
        <canvas id="chartRevenue"></canvas>
      </div>
    </div>

    <div class="chart-card">
      <div class="chart-title">{{ __('Appointment Trend') }}</div>
      <div class="chart-sub">{{ __('Daily appointments — last 30 days') }}</div>
      <div class="chart-wrap" style="height:220px;">
        <canvas id="chartAppts"></canvas>
      </div>
    </div>

  </div>

  {{-- Top services + Branch revenue --}}
  <div class="charts-2 animate-in" style="animation-delay:.11s">

    {{-- Top services --}}
    <div class="chart-card">
      <div class="chart-title">{{ __('Top Services This Month') }}</div>
      <div class="chart-sub">{{ __('By revenue generated') }}</div>
      @php $maxSvcRev = collect($topServices)->max('revenue') ?: 1; @endphp
      @forelse($topServices as $svc)
      <div class="hbar-row">
        <div class="hbar-name" title="{{ $svc['name'] }}">{{ $svc['name'] }}</div>
        <div class="hbar-track">
          <div class="hbar-fill" style="width:{{ round($svc['revenue']/$maxSvcRev*100) }}%;background:linear-gradient(90deg,#6366f1,#818cf8);"></div>
        </div>
        <div class="hbar-val">{{ number_format($svc['revenue'], 0) }} <span style="font-weight:400;color:var(--text-tertiary);">SAR</span></div>
      </div>
      @empty
      <p style="color:var(--text-tertiary);font-size:.84rem;padding:20px 0;text-align:center;">{{ __('No services data this month') }}</p>
      @endforelse
    </div>

    {{-- Branch revenue --}}
    <div class="chart-card">
      <div class="chart-title">{{ __('Revenue by Branch') }}</div>
      <div class="chart-sub">{{ __('This month — collected vs booked') }}</div>
      @php $maxBranchRev = collect($branchRevenue)->max('revenue') ?: 1; @endphp
      @forelse($branchRevenue as $br)
      <div class="hbar-row">
        <div class="hbar-name" title="{{ $br['name'] }}">{{ $br['name'] }}</div>
        <div class="hbar-track">
          <div class="hbar-fill" style="width:{{ round($br['revenue']/$maxBranchRev*100) }}%;background:linear-gradient(90deg,#10b981,#34d399);"></div>
        </div>
        <div class="hbar-val">{{ number_format($br['revenue'], 0) }} <span style="font-weight:400;color:var(--text-tertiary);">SAR</span></div>
      </div>
      @empty
      <p style="color:var(--text-tertiary);font-size:.84rem;padding:20px 0;text-align:center;">{{ __('No branch revenue data') }}</p>
      @endforelse
    </div>

  </div>

  {{-- Patient growth + Outstanding balances --}}
  <div class="charts-2 animate-in" style="animation-delay:.14s">

    {{-- Patient trend chart --}}
    <div class="chart-card">
      <div class="chart-title">{{ __('New Patients per Month') }}</div>
      <div class="chart-sub">{{ __('Patient registrations — last 6 months') }}</div>
      <div class="chart-wrap" style="height:200px;">
        <canvas id="chartPatients"></canvas>
      </div>
    </div>

    {{-- Quick stats panel --}}
    <div class="chart-card">
      <div class="chart-title">{{ __('At a Glance') }}</div>
      <div class="chart-sub">{{ __('Operational summary') }}</div>
      <div style="display:flex;flex-direction:column;gap:14px;margin-top:6px;">
        @php
          $glanceItems = [
            ['label'=>__('Total Staff (Active)'),    'val'=> number_format($kpi['total_staff']),          'icon'=>'👥', 'color'=>'#6366f1'],
            ['label'=>__('Open Leads'),              'val'=> number_format($kpi['open_leads']),            'icon'=>'🎯', 'color'=>'#f59e0b'],
            ['label'=>__('Pending Follow-ups'),      'val'=> number_format($kpi['pending_followups']),    'icon'=>'📞', 'color'=>'#ef4444'],
            ['label'=>__('Active Treatment Plans'),  'val'=> number_format($kpi['active_plans']),         'icon'=>'📋', 'color'=>'#10b981'],
            ['label'=>__('All-Time Collected'),      'val'=> number_format($revenue['total_all_time'],0).' SAR', 'icon'=>'💵', 'color'=>'#3b82f6'],
          ];
        @endphp
        @foreach($glanceItems as $g)
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:9px;">
            <div style="font-size:1rem;width:26px;text-align:center;">{{ $g['icon'] }}</div>
            <div style="font-size:.82rem;color:var(--text-secondary);">{{ $g['label'] }}</div>
          </div>
          <div style="font-size:.9rem;font-weight:700;color:{{ $g['color'] }};">{{ $g['val'] }}</div>
        </div>
        @endforeach
      </div>
    </div>

  </div>

  {{-- Outstanding balances table --}}
  <div class="card animate-in" style="margin-top:4px;animation-delay:.17s">
    <div class="card-header">
      <div>
        <div class="card-title">{{ __('Outstanding Balances') }}</div>
        <div class="card-subtitle">{{ __('Treatment plans with unpaid amounts — largest first') }}</div>
      </div>
    </div>
    @if($outstanding->isEmpty())
      <div class="empty-state" style="padding:30px 0;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/><polyline points="9 11 12 14 22 4"/></svg>
        <h3>{{ __('All caught up!') }}</h3>
        <p>{{ __('No outstanding balances.') }}</p>
      </div>
    @else
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>{{ __('Patient') }}</th>
              <th>{{ __('Service') }}</th>
              <th>{{ __('Total Price') }}</th>
              <th>{{ __('Paid') }}</th>
              <th>{{ __('Balance Due') }}</th>
              <th>{{ __('Status') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($outstanding as $plan)
            <tr>
              <td>
                <div style="font-weight:500;">{{ $plan->patient?->full_name ?? 'Unknown' }}</div>
                <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $plan->patient?->patient_code }}</div>
              </td>
              <td style="color:var(--text-secondary);font-size:.83rem;">{{ $plan->service?->name ?? '--' }}</td>
              <td style="font-weight:600;">{{ number_format($plan->total_price, 0) }} <span style="color:var(--text-tertiary);font-weight:400;font-size:.78rem;">SAR</span></td>
              <td style="color:var(--text-secondary);">{{ number_format($plan->amount_paid, 0) }} SAR</td>
              <td>
                <span class="balance-pill">{{ number_format($plan->total_price - $plan->amount_paid, 0) }} SAR</span>
              </td>
              <td>
                <span class="badge {{ $plan->status === 'active' ? 'badge-blue' : 'badge-gray' }}">{{ ucfirst($plan->status) }}</span>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

</div>{{-- /tab-revenue --}}
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Tab switching ──────────────────────────────────────────────
document.querySelectorAll('.dash-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.dash-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.dash-panel').forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
    // init charts lazily when revenue tab first opens
    if (tab.dataset.tab === 'revenue' && !window._chartsInited) {
      window._chartsInited = true;
      initCharts();
    }
  });
});

// ── Chart.js shared defaults ───────────────────────────────────
Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#6b7280';

function initCharts() {
  const revLabels    = @json($revenueTrend['labels']);
  const revBooked    = @json($revenueTrend['booked']);
  const revCollected = @json($revenueTrend['collected']);

  const apptLabels    = @json($apptTrend['labels']);
  const apptTotal     = @json($apptTrend['total']);
  const apptCompleted = @json($apptTrend['completed']);
  const apptCancelled = @json($apptTrend['cancelled']);

  const ptLabels = @json($patientTrend['labels']);
  const ptCounts = @json($patientTrend['counts']);

  // ── Revenue trend ──────────────────────────────────────────
  new Chart(document.getElementById('chartRevenue'), {
    type: 'bar',
    data: {
      labels: revLabels,
      datasets: [
        {
          label: 'Booked',
          data: revBooked,
          backgroundColor: 'rgba(99,102,241,0.18)',
          borderColor: 'rgba(99,102,241,0.8)',
          borderWidth: 2,
          borderRadius: 5,
        },
        {
          label: 'Collected',
          data: revCollected,
          backgroundColor: 'rgba(16,185,129,0.25)',
          borderColor: 'rgba(16,185,129,0.85)',
          borderWidth: 2,
          borderRadius: 5,
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 14 } } },
      scales: {
        x: { grid: { display: false }, ticks: { maxRotation: 0 } },
        y: { grid: { color: 'rgba(0,0,0,.06)' }, ticks: { callback: v => v >= 1000 ? (v/1000).toFixed(0)+'k' : v } }
      }
    }
  });

  // ── Appointment trend ──────────────────────────────────────
  new Chart(document.getElementById('chartAppts'), {
    type: 'line',
    data: {
      labels: apptLabels,
      datasets: [
        {
          label: 'Total',
          data: apptTotal,
          borderColor: '#6366f1',
          backgroundColor: 'rgba(99,102,241,0.08)',
          fill: true, tension: 0.35, pointRadius: 2,
        },
        {
          label: 'Completed',
          data: apptCompleted,
          borderColor: '#10b981',
          backgroundColor: 'transparent',
          tension: 0.35, pointRadius: 2,
        },
        {
          label: 'Cancelled / No-show',
          data: apptCancelled,
          borderColor: '#ef4444',
          backgroundColor: 'transparent',
          tension: 0.35, pointRadius: 2, borderDash: [4,3],
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 14 } } },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            maxTicksLimit: 8, maxRotation: 0,
            callback: function(val, idx) {
              // show every 4th label to avoid crowding on 30-day chart
              return idx % 4 === 0 ? this.getLabelForValue(val) : '';
            }
          }
        },
        y: { grid: { color: 'rgba(0,0,0,.06)' }, beginAtZero: true, ticks: { stepSize: 1 } }
      }
    }
  });

  // ── Patient growth ─────────────────────────────────────────
  new Chart(document.getElementById('chartPatients'), {
    type: 'bar',
    data: {
      labels: ptLabels,
      datasets: [{
        label: '{{ __('New Patients') }}',
        data: ptCounts,
        backgroundColor: 'rgba(59,130,246,0.6)',
        borderColor: '#3b82f6',
        borderWidth: 2,
        borderRadius: 5,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { maxRotation: 0 } },
        y: { grid: { color: 'rgba(0,0,0,.06)' }, beginAtZero: true, ticks: { stepSize: 1 } }
      }
    }
  });
}
</script>
@endpush
