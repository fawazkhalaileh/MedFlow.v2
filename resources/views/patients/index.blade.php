@extends('layouts.app')

@section('title', 'Patients - MedFlow CRM')
@section('breadcrumb', 'Patients')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Patients</h1>
    <p class="page-subtitle">All registered patients across your branches</p>
  </div>
  <div class="header-actions">
    <span class="badge badge-blue" style="padding:6px 14px;font-size:.82rem;">{{ $patients->total() }} total</span>
  </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('patients.index') }}">
<div class="filter-bar animate-in" style="animation-delay:.05s">
  <div class="filter-search-wrap" style="max-width:340px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="q" class="filter-search" placeholder="Name, phone, email, code..." value="{{ request('q') }}">
  </div>
  <select name="branch" class="filter-select" onchange="this.form.submit()">
    <option value="">All Branches</option>
    @foreach($branches as $b)
    <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
    @endforeach
  </select>
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="">All Statuses</option>
    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
    <option value="vip"      {{ request('status') === 'vip'      ? 'selected' : '' }}>VIP</option>
  </select>
  @if(request()->anyFilled(['q','branch','status']))
  <a href="{{ route('patients.index') }}" class="btn btn-ghost btn-sm">Clear</a>
  @endif
  <button type="submit" class="btn btn-secondary btn-sm">Search</button>
</div>
</form>

<div class="card animate-in" style="padding:0;animation-delay:.1s">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Patient</th>
          <th>Code</th>
          <th>Phone</th>
          <th>Branch</th>
          <th>Gender</th>
          <th>Status</th>
          <th>Last Visit</th>
          <th>Registered</th>
        </tr>
      </thead>
      <tbody>
        @forelse($patients as $p)
        <tr style="cursor:pointer;" onclick="window.location='{{ route('patients.show', $p) }}'">
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="avatar avatar-sm" style="background:linear-gradient(135deg,#{{ substr(md5($p->first_name),0,6) }},#{{ substr(md5($p->last_name ?? ''),0,6) }});">
                {{ strtoupper(substr($p->first_name,0,1).substr($p->last_name ?? '',0,1)) }}
              </div>
              <div>
                <div style="font-weight:500;">{{ $p->full_name }}</div>
                <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $p->email ?? 'No email' }}</div>
              </div>
            </div>
          </td>
          <td><span style="font-family:monospace;font-size:.82rem;color:var(--accent);">{{ $p->patient_code }}</span></td>
          <td style="color:var(--text-secondary);">{{ $p->phone }}</td>
          <td style="color:var(--text-secondary);font-size:.83rem;">{{ $p->branch?->name ?? '--' }}</td>
          <td style="color:var(--text-secondary);font-size:.83rem;">{{ ucfirst($p->gender ?? '--') }}</td>
          <td>
            @if($p->status === 'active')
              <span class="badge badge-green">Active</span>
            @elseif($p->status === 'vip')
              <span class="badge badge-purple">VIP</span>
            @else
              <span class="badge badge-gray">{{ ucfirst($p->status) }}</span>
            @endif
          </td>
          <td style="color:var(--text-secondary);font-size:.82rem;">
            {{ $p->last_visit_at ? $p->last_visit_at->format('d M Y') : '--' }}
          </td>
          <td style="color:var(--text-tertiary);font-size:.8rem;">{{ $p->created_at->format('d M Y') }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="8">
            <div class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <h3>No patients found</h3>
              <p>Try adjusting your filters.</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($patients->hasPages())
  <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
    <div style="font-size:.82rem;color:var(--text-secondary);">
      Showing {{ $patients->firstItem() }}–{{ $patients->lastItem() }} of {{ $patients->total() }}
    </div>
    <div style="display:flex;gap:6px;">
      @if($patients->onFirstPage())
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Previous</span>
      @else
        <a href="{{ $patients->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
      @endif
      @if($patients->hasMorePages())
        <a href="{{ $patients->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
      @else
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Next</span>
      @endif
    </div>
  </div>
  @endif
</div>
@endsection
