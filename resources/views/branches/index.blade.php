@extends('layouts.app')

@section('title', 'Branches - MedFlow CRM')
@section('breadcrumb', 'Branches')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Branches</h1>
    <p class="page-subtitle">Manage all clinic locations and their settings</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Branch
    </a>
  </div>
</div>

{{-- Stats Row --}}
<div class="kpi-grid animate-in" style="animation-delay:.05s;grid-template-columns:repeat(3,1fr)">
  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div>
    <div class="kpi-label">Total Branches</div>
    <div class="kpi-value">{{ $branches->count() }}</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
    <div class="kpi-label">Active Branches</div>
    <div class="kpi-value">{{ $branches->where('status','active')->count() }}</div>
    <div class="kpi-change up">operational</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
    <div class="kpi-label">Total Staff</div>
    <div class="kpi-value">{{ $branches->sum('staff_count') }}</div>
    <div class="kpi-change neutral">across all branches</div>
  </div>
</div>

{{-- Branches Grid --}}
<div class="animate-in" style="animation-delay:.1s;display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:18px">
  @forelse($branches as $branch)
  <div class="card" style="padding:0;overflow:hidden;">
    {{-- Card Header --}}
    <div style="padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:42px;height:42px;border-radius:var(--radius-md);background:linear-gradient(135deg,var(--accent),#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem;flex-shrink:0;">
          {{ strtoupper(substr($branch->name,0,2)) }}
        </div>
        <div>
          <div style="font-weight:600;font-size:.95rem;">{{ $branch->name }}</div>
          <div style="font-size:.75rem;color:var(--text-tertiary);font-family:monospace;">{{ $branch->code }}</div>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        @if($branch->status === 'active')
          <span class="badge badge-green">{{ __('Active') }}</span>
        @elseif($branch->status === 'inactive')
          <span class="badge badge-gray">{{ __('Inactive') }}</span>
        @else
          <span class="badge badge-yellow">Coming Soon</span>
        @endif
        <div class="dropdown">
          <button class="btn btn-ghost btn-icon btn-sm" data-toggle="dropdown" style="border:1px solid var(--border);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
          </button>
          <div class="dropdown-menu">
            <a href="{{ route('admin.branches.edit', $branch) }}" class="dropdown-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit Branch
            </a>
            <div class="dropdown-divider"></div>
            <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" onsubmit="return confirm('Delete this branch?')">
              @csrf @method('DELETE')
              <button type="submit" class="dropdown-item danger" style="width:100%;border:none;background:none;text-align:left;cursor:pointer;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                Delete
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);text-align:center;padding:16px 0;">
      <div>
        <div style="font-size:1.2rem;font-weight:700;">{{ $branch->patients_count }}</div>
        <div style="font-size:.72rem;color:var(--text-tertiary);">Patients</div>
      </div>
      <div style="border-left:1px solid var(--border-light);border-right:1px solid var(--border-light);">
        <div style="font-size:1.2rem;font-weight:700;">{{ $branch->staff_count }}</div>
        <div style="font-size:.72rem;color:var(--text-tertiary);">Staff</div>
      </div>
      <div>
        <div style="font-size:1.2rem;font-weight:700;">{{ $branch->rooms->count() }}</div>
        <div style="font-size:.72rem;color:var(--text-tertiary);">Rooms</div>
      </div>
    </div>

    {{-- Details --}}
    <div style="padding:12px 20px 16px;border-top:1px solid var(--border-light);">
      @if($branch->city)
      <div style="display:flex;align-items:center;gap:7px;font-size:.82rem;color:var(--text-secondary);margin-bottom:5px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        {{ $branch->city }}{{ $branch->country ? ', '.$branch->country : '' }}
      </div>
      @endif
      @if($branch->phone)
      <div style="display:flex;align-items:center;gap:7px;font-size:.82rem;color:var(--text-secondary);margin-bottom:5px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.45 2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
        {{ $branch->phone }}
      </div>
      @endif
      @if($branch->manager)
      <div style="display:flex;align-items:center;gap:7px;font-size:.82rem;color:var(--text-secondary);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Manager: <strong style="color:var(--text-primary);">{{ $branch->manager->first_name }} {{ $branch->manager->last_name }}</strong>
      </div>
      @endif
    </div>

    {{-- Actions --}}
    <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:8px;">
      <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center;">
        Edit Branch
      </a>
    </div>
  </div>
  @empty
  <div class="card" style="grid-column:1/-1">
    <div class="empty-state">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
      <h3>No branches yet</h3>
      <p>Create your first branch to get started.</p>
      <a href="{{ route('admin.branches.create') }}" class="btn btn-primary" style="margin-top:14px">Add First Branch</a>
    </div>
  </div>
  @endforelse
</div>
@endsection
