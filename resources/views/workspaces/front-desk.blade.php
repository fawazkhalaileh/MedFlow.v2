@extends('layouts.app')

@section('title', 'Front Desk - MedFlow CRM')
@section('breadcrumb', 'Front Desk')

@section('content')

@if(session('success'))
<div style="background:var(--success-light);border:1px solid #6ee7b7;border-radius:var(--radius-md);padding:10px 16px;margin-bottom:14px;color:#065f46;font-size:.85rem;">
  {{ session('success') }}
</div>
@endif

{{-- TOP BAR --}}
<div class="page-header animate-in" style="margin-bottom:14px;">
  <div>
    <h1 class="page-title" style="font-size:1.35rem;">Front Desk</h1>
    <p class="page-subtitle">{{ Auth::user()->first_name }} &bull; {{ now()->format('l, d F Y') }}</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('appointments.create') }}" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Appointment
    </a>
    <a href="{{ route('patients.create') }}" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      New Patient
    </a>
    <button onclick="location.reload()" class="btn btn-secondary" title="Refresh">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
    </button>
  </div>
</div>

{{-- STAT STRIP --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:16px;" class="animate-in" style="animation-delay:.04s;">

  <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);padding:12px 14px;text-align:center;">
    <div style="font-size:1.6rem;font-weight:700;color:var(--text-primary);">{{ $stats['total_today'] }}</div>
    <div style="font-size:.73rem;color:var(--text-secondary);margin-top:2px;">Total Today</div>
  </div>

  <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);padding:12px 14px;text-align:center;">
    <div style="font-size:1.6rem;font-weight:700;color:var(--warning);">{{ $stats['arrived'] }}</div>
    <div style="font-size:.73rem;color:var(--text-secondary);margin-top:2px;">In Clinic</div>
  </div>

  <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);padding:12px 14px;text-align:center;">
    <div style="font-size:1.6rem;font-weight:700;color:var(--success);">{{ $stats['completed'] }}</div>
    <div style="font-size:.73rem;color:var(--text-secondary);margin-top:2px;">{{ __('Completed') }}</div>
  </div>

  <div style="background:var(--bg-secondary);border:1px solid {{ $stats['pending_confirm'] > 0 ? '#fde68a' : 'var(--border)' }};border-radius:var(--radius-md);padding:12px 14px;text-align:center;background:{{ $stats['pending_confirm'] > 0 ? 'var(--warning-light)' : 'var(--bg-secondary)' }};">
    <div style="font-size:1.6rem;font-weight:700;color:{{ $stats['pending_confirm'] > 0 ? 'var(--warning)' : 'var(--text-primary)' }};">{{ $stats['pending_confirm'] }}</div>
    <div style="font-size:.73rem;color:var(--text-secondary);margin-top:2px;">Unconfirmed</div>
  </div>

  <div style="background:var(--bg-secondary);border:1px solid {{ $stats['no_show'] > 0 ? '#fca5a5' : 'var(--border)' }};border-radius:var(--radius-md);padding:12px 14px;text-align:center;background:{{ $stats['no_show'] > 0 ? 'var(--danger-light)' : 'var(--bg-secondary)' }};">
    <div style="font-size:1.6rem;font-weight:700;color:{{ $stats['no_show'] > 0 ? 'var(--danger)' : 'var(--text-primary)' }};">{{ $stats['no_show'] }}</div>
    <div style="font-size:.73rem;color:var(--text-secondary);margin-top:2px;">No Shows</div>
  </div>

</div>

{{-- QUICK PATIENT SEARCH --}}
<div class="card animate-in" style="animation-delay:.06s;margin-bottom:16px;padding:14px 18px;">
  <div style="display:flex;align-items:center;gap:12px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:var(--text-tertiary);flex-shrink:0;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="quick-search" placeholder="Quick patient search — name, phone, code, email..."
      style="flex:1;border:none;background:transparent;font-size:.9rem;outline:none;font-family:inherit;color:var(--text-primary);"
      autocomplete="off">
    <div id="quick-search-results" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-lg);z-index:100;max-height:300px;overflow-y:auto;"></div>
  </div>
</div>

{{-- MAIN GRID --}}
<div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start;" class="animate-in" style="animation-delay:.08s;">

  {{-- LEFT: Today's Queue Table --}}
  <div class="card" style="padding:0;">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
      <div>
        <div class="card-title">Today's Queue</div>
        <div style="font-size:.75rem;color:var(--text-tertiary);">All appointments for your branch today</div>
      </div>
      <a href="{{ route('appointments.kanban') }}" class="btn btn-secondary btn-sm" style="font-size:.76rem;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><rect x="3" y="3" width="7" height="18"/><rect x="14" y="3" width="7" height="10"/></svg>
        Kanban View
      </a>
    </div>

    @if($queue->isEmpty())
    <div class="empty-state" style="padding:40px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg><h3>No appointments today</h3></div>
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
              'booked' => 'badge-gray', 'scheduled' => 'badge-blue', 'confirmed' => 'badge-cyan',
              'arrived' => 'badge-yellow', 'checked_in' => 'badge-yellow', 'intake_complete' => 'badge-purple',
              'assigned' => 'badge-purple', 'in_room' => 'badge-purple', 'in_treatment' => 'badge-purple',
              'completed' => 'badge-green', 'follow_up_needed' => 'badge-yellow',
              'no_show' => 'badge-red', 'cancelled' => 'badge-red',
            ];
            $sc = $statusColors[$appt->status] ?? 'badge-gray';
            $isArrivalStatus = in_array($appt->status, ['scheduled','booked','confirmed']);
            $waitMins = in_array($appt->status, ['arrived','checked_in'])
              ? now()->diffInMinutes(\Carbon\Carbon::parse($appt->scheduled_at)) : null;
          @endphp
          <tr style="{{ $waitMins > 30 ? 'background:var(--danger-light);' : ($waitMins > 20 ? 'background:var(--warning-light);' : '') }}">
            <td style="font-weight:600;white-space:nowrap;font-size:.83rem;">
              {{ \Carbon\Carbon::parse($appt->scheduled_at)->format('h:i A') }}
              @if($waitMins > 20)
              <div style="font-size:.66rem;color:{{ $waitMins > 30 ? 'var(--danger)' : 'var(--warning)' }};font-weight:600;">{{ $waitMins }}m wait</div>
              @endif
            </td>
            <td>
              <a href="{{ route('patients.show', $appt->patient_id) }}" style="text-decoration:none;color:inherit;">
                <div style="font-weight:500;font-size:.85rem;">{{ $appt->patient?->full_name }}</div>
                <div style="font-size:.72rem;color:var(--accent);">{{ $appt->patient?->patient_code }}</div>
              </a>
            </td>
            <td style="font-size:.82rem;color:var(--text-secondary);">{{ $appt->service?->name ?? '--' }}</td>
            <td style="font-size:.82rem;color:var(--text-secondary);">{{ $appt->assignedStaff?->first_name ?? '--' }}</td>
            <td><span class="badge {{ $sc }}">{{ ucfirst(str_replace('_',' ',$appt->status)) }}</span></td>
            <td>
              @if($isArrivalStatus)
              <form method="POST" action="{{ route('appointments.checkin', $appt) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-primary btn-sm" style="font-size:.72rem;white-space:nowrap;">{{ __('Check In') }}</button>
              </form>
              @elseif($appt->status === 'checked_in' || $appt->status === 'intake_complete')
              <form method="POST" action="{{ route('appointments.status', $appt) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="assigned">
                <button type="submit" class="btn btn-secondary btn-sm" style="font-size:.72rem;">Ready for Tech</button>
              </form>
              @else
              <a href="{{ route('patients.show', $appt->patient_id) }}" class="btn btn-ghost btn-sm" style="font-size:.72rem;">View</a>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>

  {{-- RIGHT PANEL --}}
  <div style="display:flex;flex-direction:column;gap:14px;">

    {{-- NEEDS CONFIRMATION --}}
    @if($needsConfirmation->isNotEmpty())
    <div class="card" style="border:1px solid #fde68a;">
      <div class="card-header">
        <div class="card-title" style="color:var(--warning);font-size:.9rem;">Needs Confirmation</div>
        <span class="badge badge-yellow">{{ $needsConfirmation->count() }}</span>
      </div>
      @foreach($needsConfirmation as $appt)
      <div style="padding:8px 0;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;gap:8px;">
        <div>
          <div style="font-size:.82rem;font-weight:500;">{{ $appt->patient?->full_name }}</div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">
            {{ \Carbon\Carbon::parse($appt->scheduled_at)->format('d M, h:i A') }}
          </div>
        </div>
        <form method="POST" action="{{ route('appointments.status', $appt) }}">
          @csrf @method('PATCH')
          <input type="hidden" name="status" value="confirmed">
          <button type="submit" class="btn btn-secondary btn-sm" style="font-size:.7rem;">{{ __('Confirm') }}</button>
        </form>
      </div>
      @endforeach
    </div>
    @endif

    {{-- MY FOLLOW-UPS --}}
    <div class="card">
      <div class="card-header">
        <div class="card-title" style="font-size:.9rem;">My Follow-ups</div>
        <a href="{{ route('followups.index') }}" class="btn btn-ghost btn-sm" style="font-size:.74rem;">All</a>
      </div>
      @forelse($myFollowUps as $fu)
      <div style="display:flex;align-items:flex-start;gap:8px;padding:7px 0;border-bottom:1px solid var(--border-light);">
        <div style="width:7px;height:7px;border-radius:50%;background:{{ $fu->due_date < today() ? 'var(--danger)' : 'var(--warning)' }};flex-shrink:0;margin-top:5px;"></div>
        <div>
          <div style="font-size:.82rem;font-weight:500;">{{ $fu->patient?->full_name }}</div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">{{ ucfirst($fu->type) }} &bull; {{ \Carbon\Carbon::parse($fu->due_date)->diffForHumans() }}</div>
        </div>
      </div>
      @empty
      <p style="font-size:.8rem;color:var(--text-tertiary);padding:8px 0;">No pending follow-ups</p>
      @endforelse
    </div>

    {{-- QUICK ACTIONS --}}
    <div class="card" style="background:var(--bg-tertiary);border:none;">
      <div class="card-title" style="margin-bottom:10px;font-size:.85rem;">{{ __('Quick Actions') }}</div>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <a href="{{ route('appointments.create') }}" class="btn btn-primary btn-sm" style="justify-content:flex-start;gap:8px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          Book Appointment
        </a>
        <a href="{{ route('patients.create') }}" class="btn btn-secondary btn-sm" style="justify-content:flex-start;gap:8px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
          Register Patient
        </a>
        <a href="{{ route('appointments.kanban') }}" class="btn btn-secondary btn-sm" style="justify-content:flex-start;gap:8px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><rect x="3" y="3" width="7" height="18"/><rect x="14" y="3" width="7" height="10"/></svg>
          Open Kanban Board
        </a>
        <a href="{{ route('patients.index') }}" class="btn btn-ghost btn-sm" style="justify-content:flex-start;gap:8px;color:var(--text-secondary);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          All Patients
        </a>
      </div>
    </div>

  </div>
</div>

@push('scripts')
<script>
// Quick patient search
const qs = document.getElementById('quick-search');
const qsResults = document.getElementById('quick-search-results');
let qsTimeout;

// Make search bar's parent position relative for dropdown positioning
qs.closest('.card').style.position = 'relative';

qs.addEventListener('input', function () {
  clearTimeout(qsTimeout);
  const q = this.value.trim();
  if (q.length < 2) { qsResults.style.display = 'none'; return; }

  qsTimeout = setTimeout(() => {
    fetch(`{{ route('patients.search') }}?q=${encodeURIComponent(q)}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(patients => {
      if (!patients.length) {
        qsResults.innerHTML = '<div style="padding:10px 14px;font-size:.82rem;color:var(--text-tertiary);">No patients found</div>';
      } else {
        qsResults.innerHTML = patients.map(p => `
          <a href="/patients/${p.id}" style="display:flex;align-items:center;gap:10px;padding:9px 14px;text-decoration:none;color:inherit;border-bottom:1px solid var(--border-light);"
            onmouseover="this.style.background='var(--bg-tertiary)'" onmouseout="this.style.background=''">
            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.68rem;font-weight:700;flex-shrink:0;">
              ${p.full_name.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase()}
            </div>
            <div>
              <div style="font-weight:600;font-size:.84rem;">${p.full_name}</div>
              <div style="font-size:.72rem;color:var(--text-tertiary);">${p.patient_code} &bull; ${p.phone}</div>
            </div>
            <div style="margin-left:auto;display:flex;gap:5px;">
              <a href="/appointments/create?patient_id=${p.id}" onclick="event.stopPropagation();"
                style="font-size:.7rem;padding:3px 8px;background:var(--accent);color:#fff;border-radius:4px;text-decoration:none;">Book</a>
            </div>
          </a>`).join('');
      }
      qsResults.style.display = 'block';
    });
  }, 280);
});

document.addEventListener('click', e => {
  if (!e.target.closest('.card')) qsResults.style.display = 'none';
});
</script>
@endpush

@endsection
