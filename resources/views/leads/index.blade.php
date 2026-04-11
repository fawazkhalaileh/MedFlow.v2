@extends('layouts.app')

@section('title', 'Leads - MedFlow CRM')
@section('breadcrumb', 'Leads')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Leads</h1>
    <p class="page-subtitle">Track and convert new inquiries into patients</p>
  </div>
  <div style="display:flex;gap:8px;">
    <span class="badge badge-blue" style="padding:6px 14px;font-size:.82rem;">{{ $leads->total() }} total</span>
  </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('leads.index') }}">
<div class="filter-bar animate-in" style="animation-delay:.05s">
  <div class="filter-search-wrap" style="max-width:320px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="q" class="filter-search" placeholder="Name, phone, email..." value="{{ request('q') }}">
  </div>
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="">All Statuses</option>
    <option value="new"               {{ request('status') === 'new'               ? 'selected' : '' }}>New</option>
    <option value="contacted"         {{ request('status') === 'contacted'         ? 'selected' : '' }}>Contacted</option>
    <option value="appointment_booked"{{ request('status') === 'appointment_booked'? 'selected' : '' }}>Appointment Booked</option>
    <option value="converted"         {{ request('status') === 'converted'         ? 'selected' : '' }}>Converted</option>
    <option value="lost"              {{ request('status') === 'lost'              ? 'selected' : '' }}>Lost</option>
  </select>
  <select name="branch" class="filter-select" onchange="this.form.submit()">
    <option value="">All Branches</option>
    @foreach($branches as $b)
    <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
    @endforeach
  </select>
  @if(request()->anyFilled(['q','status','branch']))
  <a href="{{ route('leads.index') }}" class="btn btn-ghost btn-sm">Clear</a>
  @endif
  <button type="submit" class="btn btn-secondary btn-sm">Search</button>
</div>
</form>

<div class="card animate-in" style="padding:0;animation-delay:.1s">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Phone</th>
          <th>Service Interest</th>
          <th>Source</th>
          <th>Branch</th>
          <th>Assigned To</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        @forelse($leads as $lead)
        <tr>
          <td>
            <div style="font-weight:500;">{{ $lead->first_name }} {{ $lead->last_name }}</div>
            @if($lead->email)<div style="font-size:.74rem;color:var(--text-tertiary);">{{ $lead->email }}</div>@endif
          </td>
          <td style="color:var(--text-secondary);">{{ $lead->phone }}</td>
          <td style="color:var(--text-secondary);font-size:.83rem;">{{ $lead->service_interest ?? '--' }}</td>
          <td>
            @php
              $sourceColors = ['phone'=>'badge-blue','walk_in'=>'badge-green','social'=>'badge-purple','online'=>'badge-cyan','referral'=>'badge-yellow'];
              $sc = $sourceColors[$lead->source] ?? 'badge-gray';
            @endphp
            <span class="badge {{ $sc }}">{{ ucfirst(str_replace('_',' ',$lead->source)) }}</span>
          </td>
          <td style="color:var(--text-secondary);font-size:.82rem;">{{ $lead->branch?->name ?? '--' }}</td>
          <td style="color:var(--text-secondary);font-size:.82rem;">{{ $lead->assignedTo?->first_name ?? '--' }}</td>
          <td>
            @php
              $statusColors = ['new'=>'badge-blue','contacted'=>'badge-yellow','appointment_booked'=>'badge-cyan','converted'=>'badge-green','lost'=>'badge-red'];
              $sc2 = $statusColors[$lead->status] ?? 'badge-gray';
            @endphp
            <span class="badge {{ $sc2 }}">{{ ucfirst(str_replace('_',' ',$lead->status)) }}</span>
          </td>
          <td style="color:var(--text-tertiary);font-size:.8rem;">{{ $lead->created_at->format('d M Y') }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="8">
            <div class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
              <h3>No leads found</h3>
              <p>No leads match your current filters.</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($leads->hasPages())
  <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
    <div style="font-size:.82rem;color:var(--text-secondary);">
      Showing {{ $leads->firstItem() }}–{{ $leads->lastItem() }} of {{ $leads->total() }}
    </div>
    <div style="display:flex;gap:6px;">
      @if($leads->onFirstPage())
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Previous</span>
      @else
        <a href="{{ $leads->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
      @endif
      @if($leads->hasMorePages())
        <a href="{{ $leads->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
      @else
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Next</span>
      @endif
    </div>
  </div>
  @endif
</div>
@endsection
