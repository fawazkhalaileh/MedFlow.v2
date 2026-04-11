@extends('layouts.app')

@section('title', 'Follow-ups - MedFlow CRM')
@section('breadcrumb', 'Follow-ups')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Follow-ups</h1>
    <p class="page-subtitle">Track and manage patient follow-up tasks</p>
  </div>
  <span class="badge badge-yellow" style="padding:6px 14px;font-size:.82rem;">{{ $followups->total() }} pending</span>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('followups.index') }}">
<div class="filter-bar animate-in" style="animation-delay:.05s">
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="pending"   {{ (request('status','pending')) === 'pending'   ? 'selected' : '' }}>Pending</option>
    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
    <option value="overdue"   {{ request('status') === 'overdue'   ? 'selected' : '' }}>Overdue</option>
    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
    <option value=""          {{ request('status') === ''          ? 'selected' : '' }}>All Statuses</option>
  </select>
  <select name="type" class="filter-select" onchange="this.form.submit()">
    <option value="">All Types</option>
    <option value="call"        {{ request('type') === 'call'        ? 'selected' : '' }}>Call</option>
    <option value="appointment" {{ request('type') === 'appointment' ? 'selected' : '' }}>Appointment</option>
    <option value="check_in"    {{ request('type') === 'check_in'    ? 'selected' : '' }}>Check-in</option>
    <option value="email"       {{ request('type') === 'email'       ? 'selected' : '' }}>Email</option>
  </select>
  <select name="branch" class="filter-select" onchange="this.form.submit()">
    <option value="">All Branches</option>
    @foreach($branches as $b)
    <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
    @endforeach
  </select>
  @if(request()->anyFilled(['type','branch']) || request('status','pending') !== 'pending')
  <a href="{{ route('followups.index') }}" class="btn btn-ghost btn-sm">Reset</a>
  @endif
</div>
</form>

<div class="card animate-in" style="padding:0;animation-delay:.1s">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Customer</th>
          <th>Type</th>
          <th>Due Date</th>
          <th>Assigned To</th>
          <th>Branch</th>
          <th>Notes</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($followups as $fu)
        @php $overdue = $fu->due_date < today() && $fu->status === 'pending'; @endphp
        <tr style="{{ $overdue ? 'background:rgba(220,38,38,0.02);' : '' }}">
          <td>
            <div style="font-weight:500;">{{ $fu->patient?->full_name ?? 'Unknown' }}</div>
            <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $fu->patient?->phone }}</div>
          </td>
          <td>
            @php
              $typeColors = ['call'=>'badge-blue','appointment'=>'badge-green','check_in'=>'badge-cyan','email'=>'badge-purple'];
              $tc = $typeColors[$fu->type] ?? 'badge-gray';
            @endphp
            <span class="badge {{ $tc }}">{{ ucfirst(str_replace('_',' ',$fu->type)) }}</span>
          </td>
          <td>
            <div style="font-weight:{{ $overdue ? '600' : '400' }};color:{{ $overdue ? 'var(--danger)' : 'var(--text-primary)' }};font-size:.84rem;">
              {{ \Carbon\Carbon::parse($fu->due_date)->format('d M Y') }}
            </div>
            <div style="font-size:.73rem;color:{{ $overdue ? 'var(--danger)' : 'var(--text-tertiary)' }};">
              {{ $overdue ? 'OVERDUE' : \Carbon\Carbon::parse($fu->due_date)->diffForHumans() }}
            </div>
          </td>
          <td style="color:var(--text-secondary);font-size:.83rem;">{{ $fu->assignedTo?->first_name ?? 'Unassigned' }}</td>
          <td style="color:var(--text-secondary);font-size:.82rem;">{{ $fu->branch?->name ?? '--' }}</td>
          <td style="max-width:180px;">
            @if($fu->notes)
            <div style="font-size:.8rem;color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;" title="{{ $fu->notes }}">
              {{ $fu->notes }}
            </div>
            @else
            <span style="color:var(--text-tertiary);font-size:.8rem;">--</span>
            @endif
          </td>
          <td>
            @php
              $statusColors = ['pending'=>'badge-yellow','completed'=>'badge-green','overdue'=>'badge-red','cancelled'=>'badge-gray'];
              $sc = $overdue ? 'badge-red' : ($statusColors[$fu->status] ?? 'badge-gray');
            @endphp
            <span class="badge {{ $sc }}">{{ $overdue ? 'Overdue' : ucfirst($fu->status) }}</span>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.45 2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
              <h3>No follow-ups found</h3>
              <p>All clear! No follow-ups match your current filter.</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($followups->hasPages())
  <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
    <div style="font-size:.82rem;color:var(--text-secondary);">
      Showing {{ $followups->firstItem() }}–{{ $followups->lastItem() }} of {{ $followups->total() }}
    </div>
    <div style="display:flex;gap:6px;">
      @if($followups->onFirstPage())
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Previous</span>
      @else
        <a href="{{ $followups->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
      @endif
      @if($followups->hasMorePages())
        <a href="{{ $followups->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
      @else
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Next</span>
      @endif
    </div>
  </div>
  @endif
</div>
@endsection
