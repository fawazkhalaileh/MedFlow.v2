@extends('layouts.app')

@section('title', 'Admin Panel - MedFlow CRM')
@section('breadcrumb', 'Admin Panel')

@section('content')

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ __('Admin Panel') }}</h1>
    <p class="page-subtitle">Company-wide governance, configuration, and staff management</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Branch
    </a>
    <a href="{{ route('admin.employees.create') }}" class="btn btn-secondary">
      New Staff Account
    </a>
  </div>
</div>

{{-- KPI GRID --}}
<div class="kpi-grid animate-in" style="animation-delay:.04s;margin-bottom:18px;">

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
    <div class="kpi-label">{{ __('Branches') }}</div>
    <div class="kpi-value">{{ $stats['branches'] }}</div>
    <div class="kpi-change up">{{ $stats['active_branches'] }} active</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
    <div class="kpi-label">Active Staff</div>
    <div class="kpi-value">{{ $stats['staff'] }}</div>
    <div class="kpi-change neutral">across all branches</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
    <div class="kpi-label">Total Patients</div>
    <div class="kpi-value">{{ $stats['patients'] }}</div>
    <div class="kpi-change neutral">registered</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
    <div class="kpi-label">Appointments Today</div>
    <div class="kpi-value">{{ $stats['appointments_today'] }}</div>
    <div class="kpi-change neutral">company-wide</div>
  </div>

</div>

<div class="grid-2-1 animate-in" style="animation-delay:.07s;align-items:start;">

  {{-- BRANCHES TABLE --}}
  <div class="card" style="padding:0;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <div class="card-title">{{ __('Branches') }}</div>
      <a href="{{ route('admin.branches.index') }}" class="btn btn-ghost btn-sm">{{ __('View All') }}</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>{{ __('Branch') }}</th><th>{{ __('Patients') }}</th><th>{{ __('Staff') }}</th><th>{{ __('Appointments') }}</th><th>{{ __('Status') }}</th><th></th></tr>
        </thead>
        <tbody>
          @foreach($branches as $b)
          <tr>
            <td>
              <div style="font-weight:500;font-size:.85rem;">{{ $b->name }}</div>
              <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $b->city }}</div>
            </td>
            <td style="font-size:.83rem;color:var(--text-secondary);">{{ $b->patients_count }}</td>
            <td style="font-size:.83rem;color:var(--text-secondary);">{{ $b->staff_count }}</td>
            <td style="font-size:.83rem;color:var(--text-secondary);">{{ $b->appointments_count }}</td>
            <td>
              <span class="badge {{ $b->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($b->status) }}</span>
            </td>
            <td>
              <a href="{{ route('admin.branches.edit', $b) }}" class="btn btn-ghost btn-sm" style="font-size:.73rem;">{{ __('Edit') }}</a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- RIGHT --}}
  <div style="display:flex;flex-direction:column;gap:16px;">

    {{-- ADMIN NAVIGATION SHORTCUTS --}}
    <div class="card">
      <div class="card-title" style="margin-bottom:14px;">Admin Areas</div>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <a href="{{ route('admin.branches.index') }}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);text-decoration:none;color:inherit;transition:var(--transition);" onmouseover="this.style.background='var(--bg-tertiary)'" onmouseout="this.style.background=''">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;color:var(--accent);"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
          <div>
            <div style="font-size:.85rem;font-weight:500;">Branch Management</div>
            <div style="font-size:.73rem;color:var(--text-tertiary);">Create, edit, deactivate branches</div>
          </div>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:var(--text-tertiary);margin-left:auto;"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <a href="{{ route('admin.employees.index') }}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);text-decoration:none;color:inherit;transition:var(--transition);" onmouseover="this.style.background='var(--bg-tertiary)'" onmouseout="this.style.background=''">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;color:#7c3aed;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
          <div>
            <div style="font-size:.85rem;font-weight:500;">Staff Accounts</div>
            <div style="font-size:.73rem;color:var(--text-tertiary);">Create, assign roles, deactivate staff</div>
          </div>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:var(--text-tertiary);margin-left:auto;"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <a href="{{ route('admin.roles') }}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);text-decoration:none;color:inherit;transition:var(--transition);" onmouseover="this.style.background='var(--bg-tertiary)'" onmouseout="this.style.background=''">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;color:var(--warning);"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <div>
            <div style="font-size:.85rem;font-weight:500;">Roles &amp; Permissions</div>
            <div style="font-size:.73rem;color:var(--text-tertiary);">Define what each role can access</div>
          </div>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:var(--text-tertiary);margin-left:auto;"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <a href="{{ route('admin.activity-logs') }}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);text-decoration:none;color:inherit;transition:var(--transition);" onmouseover="this.style.background='var(--bg-tertiary)'" onmouseout="this.style.background=''">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;color:var(--info);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          <div>
            <div style="font-size:.85rem;font-weight:500;">Activity Logs</div>
            <div style="font-size:.73rem;color:var(--text-tertiary);">Full audit trail of system actions</div>
          </div>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:var(--text-tertiary);margin-left:auto;"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <a href="{{ route('admin.import.index') }}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);text-decoration:none;color:inherit;transition:var(--transition);" onmouseover="this.style.background='var(--bg-tertiary)'" onmouseout="this.style.background=''">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;color:#10b981;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <div>
            <div style="font-size:.85rem;font-weight:500;">Data Import</div>
            <div style="font-size:.73rem;color:var(--text-tertiary);">Bulk import patients &amp; appointments</div>
          </div>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:var(--text-tertiary);margin-left:auto;"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <a href="{{ route('admin.settings') }}" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);text-decoration:none;color:inherit;transition:var(--transition);" onmouseover="this.style.background='var(--bg-tertiary)'" onmouseout="this.style.background=''">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;color:var(--text-secondary);"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          <div>
            <div style="font-size:.85rem;font-weight:500;">System Settings</div>
            <div style="font-size:.73rem;color:var(--text-tertiary);">Company info, branding, defaults</div>
          </div>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:var(--text-tertiary);margin-left:auto;"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      </div>
    </div>

    {{-- RECENT STAFF --}}
    <div class="card">
      <div class="card-header">
        <div class="card-title" style="font-size:.9rem;">Active Staff</div>
        <a href="{{ route('admin.employees.index') }}" class="btn btn-ghost btn-sm" style="font-size:.74rem;">All</a>
      </div>
      @foreach($staff as $s)
      <div style="display:flex;align-items:center;gap:9px;padding:7px 0;border-bottom:1px solid var(--border-light);">
        <div class="avatar avatar-sm" style="width:28px;height:28px;font-size:.68rem;flex-shrink:0;background:linear-gradient(135deg,var(--accent),#7c3aed);">
          {{ strtoupper(substr($s->first_name,0,1).substr($s->last_name ?? '',0,1)) }}
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:.82rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $s->first_name }} {{ $s->last_name }}</div>
          <div style="font-size:.71rem;color:var(--text-tertiary);">{{ ucfirst(str_replace('_',' ',$s->employee_type ?? '--')) }}</div>
        </div>
        <span class="badge badge-green" style="font-size:.66rem;">Active</span>
      </div>
      @endforeach
    </div>

  </div>
</div>
@endsection
