@extends('layouts.app')

@section('title', 'Employees - MedFlow CRM')
@section('breadcrumb', 'Employees')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Employees</h1>
    <p class="page-subtitle">Manage all staff accounts and their branch assignments</p>
  </div>
  <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Employee
  </a>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.employees.index') }}">
<div class="filter-bar animate-in" style="animation-delay:.05s">
  <div class="filter-search-wrap" style="max-width:320px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="q" class="filter-search" placeholder="Search by name, email, ID..." value="{{ request('q') }}">
  </div>
  <select name="branch" class="filter-select" onchange="this.form.submit()">
    <option value="">All Branches</option>
    @foreach($branches as $b)
    <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
    @endforeach
  </select>
  <select name="type" class="filter-select" onchange="this.form.submit()">
    <option value="">All Types</option>
    @foreach($types as $t)
    <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
    @endforeach
  </select>
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="">All Statuses</option>
    <option value="active"      {{ request('status') === 'active'      ? 'selected' : '' }}>Active</option>
    <option value="inactive"    {{ request('status') === 'inactive'    ? 'selected' : '' }}>Inactive</option>
    <option value="on_leave"    {{ request('status') === 'on_leave'    ? 'selected' : '' }}>On Leave</option>
    <option value="terminated"  {{ request('status') === 'terminated'  ? 'selected' : '' }}>Terminated</option>
  </select>
  @if(request()->anyFilled(['q','branch','type','status']))
  <a href="{{ route('admin.employees.index') }}" class="btn btn-ghost btn-sm">Clear</a>
  @endif
  <button type="submit" class="btn btn-secondary btn-sm">Search</button>
</div>
</form>

{{-- Table --}}
<div class="card animate-in" style="padding:0;animation-delay:.1s">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
    <div style="font-weight:600;font-size:.9rem;">{{ $employees->total() }} employees found</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Employee</th>
          <th>ID</th>
          <th>Type</th>
          <th>Primary Branch</th>
          <th>Hire Date</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($employees as $emp)
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:11px;">
              <div class="avatar" style="background:linear-gradient(135deg,#{{ substr(md5($emp->email),0,6) }},#{{ substr(md5($emp->first_name ?? ''),0,6) }});">
                {{ strtoupper(substr($emp->first_name ?? $emp->name,0,1).substr($emp->last_name ?? '',0,1)) }}
              </div>
              <div>
                <div style="font-weight:500;">{{ $emp->first_name }} {{ $emp->last_name }}</div>
                <div style="font-size:.75rem;color:var(--text-tertiary);">{{ $emp->email }}</div>
              </div>
            </div>
          </td>
          <td><span style="font-family:monospace;font-size:.82rem;color:var(--text-secondary);">{{ $emp->employee_id ?? '--' }}</span></td>
          <td>
            @php
              $typeColors = [
                'branch_manager' => 'badge-blue',
                'secretary'      => 'badge-cyan',
                'technician'     => 'badge-purple',
                'doctor'         => 'badge-green',
                'nurse'          => 'badge-yellow',
                'finance'        => 'badge-gray',
                'system_admin'   => 'badge-red',
              ];
              $cls = $typeColors[$emp->employee_type] ?? 'badge-gray';
            @endphp
            <span class="badge {{ $cls }}">{{ ucfirst(str_replace('_',' ',$emp->employee_type ?? 'N/A')) }}</span>
          </td>
          <td style="color:var(--text-secondary);font-size:.84rem;">{{ $emp->primaryBranch?->name ?? '--' }}</td>
          <td style="color:var(--text-secondary);font-size:.82rem;">{{ $emp->hire_date?->format('d M Y') ?? '--' }}</td>
          <td>
            @if($emp->employment_status === 'active')
              <span class="status-dot active">Active</span>
            @elseif($emp->employment_status === 'on_leave')
              <span class="status-dot pending">On Leave</span>
            @elseif($emp->employment_status === 'terminated')
              <span class="status-dot inactive" style="color:var(--danger)">Terminated</span>
            @else
              <span class="status-dot inactive">{{ ucfirst($emp->employment_status) }}</span>
            @endif
          </td>
          <td>
            <div class="dropdown">
              <button class="btn btn-ghost btn-icon btn-sm" data-toggle="dropdown">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
              </button>
              <div class="dropdown-menu">
                <a href="{{ route('admin.employees.edit', $emp) }}" class="dropdown-item">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </a>
                @if($emp->employment_status !== 'terminated')
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('admin.employees.destroy', $emp) }}" onsubmit="return confirm('Terminate {{ addslashes($emp->first_name) }}?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="dropdown-item danger" style="width:100%;border:none;background:none;text-align:left;cursor:pointer;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Terminate
                  </button>
                </form>
                @endif
              </div>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
              <h3>No employees found</h3>
              <p>Try adjusting your filters or add a new employee.</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($employees->hasPages())
  <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
    <div style="font-size:.82rem;color:var(--text-secondary);">
      Showing {{ $employees->firstItem() }}–{{ $employees->lastItem() }} of {{ $employees->total() }}
    </div>
    <div style="display:flex;gap:6px;">
      @if($employees->onFirstPage())
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Previous</span>
      @else
        <a href="{{ $employees->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
      @endif
      @if($employees->hasMorePages())
        <a href="{{ $employees->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
      @else
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Next</span>
      @endif
    </div>
  </div>
  @endif
</div>
@endsection
