@extends('layouts.app')

@section('title', 'Customers - MedFlow CRM')
@section('breadcrumb', 'Customers')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Customers</h1>
    <p class="page-subtitle">All registered clients across your branches</p>
  </div>
  <div class="header-actions">
    <span class="badge badge-blue" style="padding:6px 14px;font-size:.82rem;">{{ $customers->total() }} total</span>
  </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('customers.index') }}">
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
    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>{{ __('Active') }}</option>
    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
    <option value="vip"      {{ request('status') === 'vip'      ? 'selected' : '' }}>{{ __('VIP') }}</option>
  </select>
  @if(request()->anyFilled(['q','branch','status']))
  <a href="{{ route('customers.index') }}" class="btn btn-ghost btn-sm">Clear</a>
  @endif
  <button type="submit" class="btn btn-secondary btn-sm">Search</button>
</div>
</form>

<div class="card animate-in" style="padding:0;animation-delay:.1s">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Customer</th>
          <th>Code</th>
          <th>{{ __('Phone') }}</th>
          <th>Branch</th>
          <th>{{ __('Gender') }}</th>
          <th>Status</th>
          <th>Last Visit</th>
          <th>Registered</th>
        </tr>
      </thead>
      <tbody>
        @forelse($customers as $c)
        <tr style="cursor:pointer;" onclick="window.location='{{ route('customers.show', $c) }}'">
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="avatar avatar-sm" style="background:linear-gradient(135deg,#{{ substr(md5($c->first_name),0,6) }},#{{ substr(md5($c->last_name ?? ''),0,6) }});">
                {{ strtoupper(substr($c->first_name,0,1).substr($c->last_name ?? '',0,1)) }}
              </div>
              <div>
                <div style="font-weight:500;">{{ $c->full_name }}</div>
                <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $c->email ?? 'No email' }}</div>
              </div>
            </div>
          </td>
          <td><span style="font-family:monospace;font-size:.82rem;color:var(--accent);">{{ $c->customer_code }}</span></td>
          <td style="color:var(--text-secondary);">{{ $c->phone }}</td>
          <td style="color:var(--text-secondary);font-size:.83rem;">{{ $c->branch?->name ?? '--' }}</td>
          <td style="color:var(--text-secondary);font-size:.83rem;">{{ ucfirst($c->gender ?? '--') }}</td>
          <td>
            @if($c->status === 'active')
              <span class="badge badge-green">{{ __('Active') }}</span>
            @elseif($c->status === 'vip')
              <span class="badge badge-purple">{{ __('VIP') }}</span>
            @else
              <span class="badge badge-gray">{{ ucfirst($c->status) }}</span>
            @endif
          </td>
          <td style="color:var(--text-secondary);font-size:.82rem;">
            {{ $c->last_visit_at ? $c->last_visit_at->format('d M Y') : '--' }}
          </td>
          <td style="color:var(--text-tertiary);font-size:.8rem;">{{ $c->created_at->format('d M Y') }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="8">
            <div class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <h3>No customers found</h3>
              <p>Try adjusting your filters.</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($customers->hasPages())
  <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
    <div style="font-size:.82rem;color:var(--text-secondary);">
      {{ __('Showing') }} {{ $customers->firstItem() }}–{{ $customers->lastItem() }} {{ __('of') }} {{ $customers->total() }}
    </div>
    <div style="display:flex;gap:6px;">
      @if($customers->onFirstPage())
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Previous</span>
      @else
        <a href="{{ $customers->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
      @endif
      @if($customers->hasMorePages())
        <a href="{{ $customers->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
      @else
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Next</span>
      @endif
    </div>
  </div>
  @endif
</div>
@endsection
