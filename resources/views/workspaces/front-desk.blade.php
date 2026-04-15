@extends('layouts.app')

@section('title', 'Front Desk — MedFlow CRM')
@section('breadcrumb', 'Front Desk')

@section('content')

@if(session('success'))
<div style="background:var(--success-light);border:1px solid #6ee7b7;border-radius:var(--radius-md);padding:10px 16px;margin-bottom:14px;color:#065f46;font-size:.85rem;">
  {{ session('success') }}
</div>
@endif

{{-- PAGE HEADER --}}
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
    <a href="{{ route('appointments.kanban') }}" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><rect x="3" y="3" width="7" height="18"/><rect x="14" y="3" width="7" height="10"/></svg>
      Kanban
    </a>
    <button onclick="location.reload()" class="btn btn-secondary" title="Refresh">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
    </button>
  </div>
</div>

{{-- STAT STRIP --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:16px;" class="animate-in">

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

  <div style="background:{{ $stats['pending_confirm'] > 0 ? 'var(--warning-light)' : 'var(--bg-secondary)' }};border:1px solid {{ $stats['pending_confirm'] > 0 ? '#fde68a' : 'var(--border)' }};border-radius:var(--radius-md);padding:12px 14px;text-align:center;">
    <div style="font-size:1.6rem;font-weight:700;color:{{ $stats['pending_confirm'] > 0 ? 'var(--warning)' : 'var(--text-primary)' }};">{{ $stats['pending_confirm'] }}</div>
    <div style="font-size:.73rem;color:var(--text-secondary);margin-top:2px;">Unconfirmed</div>
  </div>

  <div style="background:{{ $stats['no_show'] > 0 ? 'var(--danger-light)' : 'var(--bg-secondary)' }};border:1px solid {{ $stats['no_show'] > 0 ? '#fca5a5' : 'var(--border)' }};border-radius:var(--radius-md);padding:12px 14px;text-align:center;">
    <div style="font-size:1.6rem;font-weight:700;color:{{ $stats['no_show'] > 0 ? 'var(--danger)' : 'var(--text-primary)' }};">{{ $stats['no_show'] }}</div>
    <div style="font-size:.73rem;color:var(--text-secondary);margin-top:2px;">No Shows</div>
  </div>

</div>

{{-- QUICK PATIENT SEARCH --}}
<div class="card animate-in" style="margin-bottom:16px;padding:14px 18px;position:relative;">
  <div style="display:flex;align-items:center;gap:12px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:var(--text-tertiary);flex-shrink:0;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="quick-search" placeholder="Quick patient search — name, phone, code, email..."
      style="flex:1;border:none;background:transparent;font-size:.9rem;outline:none;font-family:inherit;color:var(--text-primary);"
      autocomplete="off">
  </div>
  <div id="quick-search-results" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-lg);z-index:100;max-height:300px;overflow-y:auto;"></div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- SCHEDULING GRID                                                        --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}

@php
// Build a live "current slot" key for highlighting
$nowSlot = now()->minute < 30
    ? now()->format('H') . ':00'
    : now()->format('H') . ':30';

// Status → dot colour
$statusDotColor = [
    'completed'    => '#059669',
    'in_treatment' => '#2563eb',
    'in_room'      => '#2563eb',
    'arrived'      => '#d97706',
    'checked_in'   => '#d97706',
    'intake_complete' => '#d97706',
    'confirmed'    => '#7c3aed',
    'booked'       => '#9ca3af',
    'scheduled'    => '#9ca3af',
    'no_show'      => '#dc2626',
    'cancelled'    => '#6b7280',
];

// Status → human label
$statusLabel = [
    'completed'       => 'Done',
    'in_treatment'    => 'In Session',
    'in_room'         => 'In Room',
    'arrived'         => 'Arrived',
    'checked_in'      => 'Checked In',
    'intake_complete' => 'Intake Done',
    'confirmed'       => 'Confirmed',
    'booked'          => 'Booked',
    'scheduled'       => 'Scheduled',
    'no_show'         => 'No Show',
    'cancelled'       => 'Cancelled',
];
@endphp

<div class="card animate-in" style="padding:0;margin-bottom:16px;">

  {{-- Grid header bar --}}
  <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div>
      <div class="card-title" style="margin-bottom:2px;">Today's Schedule</div>
      <div style="font-size:.74rem;color:var(--text-tertiary);">
        {{ $rooms->count() }} room{{ $rooms->count() !== 1 ? 's' : '' }}
        &bull; {{ $queue->count() }} appointment{{ $queue->count() !== 1 ? 's' : '' }}
        @if(count($unassigned) > 0)
          &bull; <span style="color:var(--warning);font-weight:600;">{{ count($unassigned) }} unassigned</span>
        @endif
      </div>
    </div>

    {{-- Staff colour legend --}}
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <span style="font-size:.72rem;color:var(--text-tertiary);font-weight:500;">Staff:</span>
      @foreach($staffColorMap as $staffId => $color)
        @php $legendStaff = $queue->firstWhere('assigned_staff_id', $staffId)?->assignedStaff @endphp
        @if($legendStaff)
        <span style="display:inline-flex;align-items:center;gap:5px;font-size:.73rem;">
          <span style="width:9px;height:9px;border-radius:50%;background:{{ $color['border'] }};flex-shrink:0;"></span>
          <span style="font-weight:500;">{{ $legendStaff->first_name }}</span>
          <span style="font-size:.65rem;color:var(--text-tertiary);background:var(--bg-tertiary);border-radius:3px;padding:1px 4px;">
            {{ in_array($legendStaff->employee_type, ['doctor','nurse']) ? 'Dr.' : 'Tech' }}
          </span>
        </span>
        @endif
      @endforeach
    </div>
  </div>

  {{-- Scrollable grid --}}
  <div style="overflow-x:auto;overflow-y:auto;max-height:calc(100vh - 310px);" id="schedule-grid-scroll">
    <table style="width:100%;border-collapse:collapse;min-width:{{ 76 + $rooms->count() * 190 + (count($unassigned) > 0 ? 190 : 0) }}px;">

      {{-- Column headers --}}
      <thead>
        <tr style="position:sticky;top:0;z-index:20;">

          {{-- Time header --}}
          <th style="
            width:72px;min-width:72px;
            background:var(--bg-secondary);
            border-bottom:2px solid var(--border);
            border-right:2px solid var(--border);
            padding:10px 8px;
            font-size:.72rem;color:var(--text-tertiary);
            text-align:center;font-weight:500;
            position:sticky;left:0;z-index:30;
          ">Time</th>

          {{-- Room headers --}}
          @foreach($rooms as $room)
          <th style="
            background:var(--bg-secondary);
            border-bottom:2px solid var(--border);
            border-right:1px solid var(--border);
            padding:10px 12px;
            min-width:185px;
            text-align:left;
          ">
            {{-- Display row: device name (primary) + room name subtitle --}}
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:6px;">
              <div style="min-width:0;">
                <div style="font-size:.82rem;font-weight:700;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px;">
                  {{ $room->device_name ?? $room->name }}
                </div>
                @if($room->device_name)
                <div style="font-size:.67rem;color:var(--text-tertiary);margin-top:1px;">{{ $room->name }}</div>
                @elseif($room->description)
                <div style="font-size:.67rem;color:var(--text-tertiary);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px;">{{ $room->description }}</div>
                @endif
              </div>
              @if(in_array(auth()->user()->employee_type, ['branch_manager','system_admin']))
              <button onclick="toggleDeviceEdit({{ $room->id }})" title="Edit device name"
                style="flex-shrink:0;background:none;border:none;cursor:pointer;padding:2px 4px;color:var(--text-tertiary);font-size:.75rem;line-height:1;border-radius:4px;"
                onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-tertiary)'">✎</button>
              @endif
            </div>
            {{-- Inline edit form (hidden by default) --}}
            @if(in_array(auth()->user()->employee_type, ['branch_manager','system_admin']))
            <div id="device-edit-{{ $room->id }}" style="display:none;margin-top:6px;">
              <form method="POST" action="{{ route('rooms.update-device-name', $room) }}" style="display:flex;gap:4px;align-items:center;">
                @csrf
                @method('PATCH')
                <input type="text" name="device_name"
                  value="{{ $room->device_name }}"
                  placeholder="Device name…"
                  style="flex:1;min-width:0;font-size:.72rem;padding:3px 6px;border:1px solid var(--border);border-radius:4px;background:var(--bg-primary);color:var(--text-primary);">
                <button type="submit" style="font-size:.72rem;padding:3px 8px;background:var(--primary);color:#fff;border:none;border-radius:4px;cursor:pointer;">Save</button>
                <button type="button" onclick="toggleDeviceEdit({{ $room->id }})" style="font-size:.72rem;padding:3px 6px;background:none;border:1px solid var(--border);border-radius:4px;cursor:pointer;color:var(--text-secondary);">✕</button>
              </form>
            </div>
            @endif
          </th>
          @endforeach

          {{-- Unassigned column --}}
          @if(count($unassigned) > 0)
          <th style="
            background:var(--bg-secondary);
            border-bottom:2px solid var(--border);
            padding:10px 12px;
            min-width:185px;
            text-align:left;
          ">
            <div style="font-size:.82rem;font-weight:700;color:var(--warning);">Unassigned</div>
            <div style="font-size:.67rem;color:var(--text-tertiary);margin-top:1px;">No room assigned</div>
          </th>
          @endif

        </tr>
      </thead>

      {{-- Time-slot rows --}}
      <tbody>
        @foreach($slots as $slot)
        @php
          $isHour   = str_ends_with($slot, ':00');
          $isNow    = ($slot === $nowSlot);
          $isPast   = \Carbon\Carbon::createFromFormat('H:i', $slot)->lt(now()->subMinutes(1));
          $rowBg    = $isNow ? 'background:rgba(37,99,235,.04);' : ($isPast ? 'background:rgba(0,0,0,.012);' : '');
        @endphp
        <tr style="{{ $rowBg }}{{ $isHour ? 'border-top:1px solid var(--border);' : '' }}">

          {{-- Time label (sticky left) --}}
          <td style="
            background:{{ $isNow ? 'rgba(37,99,235,.08)' : ($isPast ? 'var(--bg-tertiary)' : 'var(--bg-secondary)') }};
            border-right:2px solid var(--border);
            border-bottom:1px solid {{ $isHour ? 'var(--border)' : 'var(--border-light)' }};
            padding:5px 8px;
            text-align:center;
            vertical-align:top;
            white-space:nowrap;
            width:72px;min-width:72px;
            position:sticky;left:0;z-index:10;
          ">
            @if($isHour)
              <div style="font-size:.8rem;font-weight:700;color:{{ $isNow ? 'var(--accent)' : ($isPast ? 'var(--text-tertiary)' : 'var(--text-primary)') }};line-height:1.1;">
                {{ \Carbon\Carbon::createFromFormat('H:i', $slot)->format('g') }}<span style="font-size:.62rem;font-weight:400;">{{ \Carbon\Carbon::createFromFormat('H:i', $slot)->format(':00') }}</span>
                <div style="font-size:.6rem;font-weight:500;color:{{ $isNow ? 'var(--accent)' : 'var(--text-tertiary)' }};">{{ \Carbon\Carbon::createFromFormat('H:i', $slot)->format('A') }}</div>
              </div>
            @else
              <div style="font-size:.68rem;color:var(--text-tertiary);">:30</div>
            @endif
            @if($isNow)
              <div style="width:6px;height:6px;border-radius:50%;background:var(--accent);margin:2px auto 0;"></div>
            @endif
          </td>

          {{-- Room cells --}}
          @foreach($rooms as $room)
          @php
            $cellAppts = collect($grid[$room->id][$slot] ?? []);
            $isDouble  = $cellAppts->count() > 1;
          @endphp
          <td style="
            border-right:1px solid var(--border-light);
            border-bottom:1px solid {{ $isHour ? 'var(--border)' : 'var(--border-light)' }};
            padding:3px 4px;
            vertical-align:top;
            min-width:185px;
          ">
            @if($isDouble)
              <div style="font-size:.6rem;color:var(--danger);font-weight:700;background:var(--danger-light);border-radius:3px;padding:1px 5px;margin-bottom:3px;text-align:center;">
                ⚠ Double booked
              </div>
            @endif

            @foreach($cellAppts as $appt)
              @php
                $sc       = $staffColorMap[$appt->assigned_staff_id] ?? ['border' => '#6b7280', 'bg' => '#f9fafb'];
                $isDoc    = in_array($appt->assignedStaff?->employee_type, ['doctor', 'nurse']);
                $dotColor = $statusDotColor[$appt->status] ?? '#9ca3af';
                $label    = $statusLabel[$appt->status]    ?? ucfirst(str_replace('_', ' ', $appt->status));
              @endphp
              <a href="{{ route('patients.show', $appt->patient_id) }}"
                 title="{{ $appt->patient?->full_name }} — {{ $appt->service?->name }} ({{ $label }})"
                 style="
                   display:block;text-decoration:none;
                   background:{{ $sc['bg'] }};
                   border-left:3px solid {{ $sc['border'] }};
                   border-radius:var(--radius-sm);
                   padding:5px 7px;
                   margin-bottom:3px;
                   transition:filter .12s;
                 "
                 onmouseover="this.style.filter='brightness(.94)'"
                 onmouseout="this.style.filter=''">

                {{-- Row 1: time + role badge + status dot --}}
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2px;">
                  <span style="font-size:.69rem;font-weight:700;color:{{ $sc['border'] }};">
                    {{ \Carbon\Carbon::parse($appt->scheduled_at)->format('g:i A') }}
                  </span>
                  <div style="display:flex;align-items:center;gap:3px;">
                    <span style="font-size:.58rem;background:{{ $sc['border'] }};color:#fff;border-radius:3px;padding:1px 4px;font-weight:700;letter-spacing:.01em;">
                      {{ $isDoc ? 'Dr.' : 'Tech' }}
                    </span>
                    <span style="width:6px;height:6px;border-radius:50%;background:{{ $dotColor }};flex-shrink:0;" title="{{ $label }}"></span>
                  </div>
                </div>

                {{-- Row 2: Patient name --}}
                <div style="font-weight:600;font-size:.78rem;color:var(--text-primary);line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  {{ $appt->patient?->full_name }}
                </div>

                {{-- Row 3: Service --}}
                <div style="font-size:.71rem;color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  {{ $appt->service?->name ?? '—' }}
                </div>

                {{-- Row 4: Staff name --}}
                <div style="font-size:.68rem;color:var(--text-tertiary);">
                  {{ $appt->assignedStaff?->first_name ?? '—' }}
                </div>

                {{-- Row 5: Notes preview (if any) --}}
                @if($appt->reason_notes)
                <div style="font-size:.66rem;color:var(--text-tertiary);font-style:italic;margin-top:3px;padding-top:3px;border-top:1px solid rgba(0,0,0,.07);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  {{ \Illuminate\Support\Str::limit($appt->reason_notes, 48) }}
                </div>
                @endif

              </a>
            @endforeach
          </td>
          @endforeach

          {{-- Unassigned column --}}
          @if(count($unassigned) > 0)
          <td style="
            border-bottom:1px solid {{ $isHour ? 'var(--border)' : 'var(--border-light)' }};
            padding:3px 4px;vertical-align:top;min-width:185px;
            background:{{ $isPast ? 'rgba(0,0,0,.012)' : '' }};
          ">
            @foreach($unassigned as $appt)
              @php
                $dt2      = \Carbon\Carbon::parse($appt->scheduled_at);
                $snapMin2 = $dt2->minute < 30 ? '00' : '30';
                $apptSlot = sprintf('%02d:%02d', $dt2->hour, $snapMin2);
              @endphp
              @if($apptSlot === $slot)
                @php $sc = $staffColorMap[$appt->assigned_staff_id] ?? ['border' => '#d97706', 'bg' => '#fffbeb']; @endphp
                <a href="{{ route('patients.show', $appt->patient_id) }}"
                   style="display:block;text-decoration:none;background:{{ $sc['bg'] }};border-left:3px solid #d97706;border-radius:var(--radius-sm);padding:5px 7px;margin-bottom:3px;transition:filter .12s;"
                   onmouseover="this.style.filter='brightness(.94)'" onmouseout="this.style.filter=''">
                  <div style="font-size:.69rem;font-weight:700;color:#d97706;">
                    {{ $dt2->format('g:i A') }}
                  </div>
                  <div style="font-weight:600;font-size:.78rem;color:var(--text-primary);">{{ $appt->patient?->full_name }}</div>
                  <div style="font-size:.71rem;color:var(--text-secondary);">{{ $appt->service?->name ?? '—' }}</div>
                  <div style="font-size:.68rem;color:var(--text-tertiary);">{{ $appt->assignedStaff?->first_name ?? '—' }}</div>
                </a>
              @endif
            @endforeach
          </td>
          @endif

        </tr>
        @endforeach
      </tbody>

    </table>
  </div>{{-- end scroll wrapper --}}

</div>{{-- end grid card --}}

{{-- BOTTOM PANELS: Confirmation + Follow-ups + Quick Actions --}}
<div style="display:grid;grid-template-columns:1fr 1fr 220px;gap:14px;align-items:start;" class="animate-in">

  {{-- Needs Confirmation --}}
  <div class="card" style="{{ $needsConfirmation->isNotEmpty() ? 'border:1px solid #fde68a;' : '' }}">
    <div class="card-header">
      <div class="card-title" style="font-size:.88rem;{{ $needsConfirmation->isNotEmpty() ? 'color:var(--warning);' : '' }}">Needs Confirmation</div>
      @if($needsConfirmation->isNotEmpty())
        <span class="badge badge-yellow">{{ $needsConfirmation->count() }}</span>
      @endif
    </div>
    @forelse($needsConfirmation as $appt)
    <div style="padding:8px 0;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;gap:8px;">
      <div>
        <div style="font-size:.82rem;font-weight:500;">{{ $appt->patient?->full_name }}</div>
        <div style="font-size:.72rem;color:var(--text-tertiary);">
          {{ \Carbon\Carbon::parse($appt->scheduled_at)->format('d M, g:i A') }}
          &bull; {{ $appt->service?->name ?? '—' }}
        </div>
      </div>
      <form method="POST" action="{{ route('appointments.status', $appt) }}">
        @csrf @method('PATCH')
        <input type="hidden" name="status" value="confirmed">
        <button type="submit" class="btn btn-secondary btn-sm" style="font-size:.7rem;white-space:nowrap;">{{ __('Confirm') }}</button>
      </form>
    </div>
    @empty
    <p style="font-size:.8rem;color:var(--text-tertiary);padding:8px 0;">All upcoming appointments confirmed.</p>
    @endforelse
  </div>

  {{-- My Follow-ups --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title" style="font-size:.88rem;">My Follow-ups</div>
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
    <p style="font-size:.8rem;color:var(--text-tertiary);padding:8px 0;">No pending follow-ups.</p>
    @endforelse
  </div>

  {{-- Quick Actions --}}
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
        Kanban Board
      </a>
      <a href="{{ route('patients.index') }}" class="btn btn-ghost btn-sm" style="justify-content:flex-start;gap:8px;color:var(--text-secondary);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        All Patients
      </a>
    </div>
  </div>

</div>

@push('scripts')
<script>
// Quick patient search
const qs        = document.getElementById('quick-search');
const qsResults = document.getElementById('quick-search-results');
let qsTimeout;

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
            <div style="margin-left:auto;">
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
  if (!e.target.closest('#quick-search, #quick-search-results')) {
    qsResults.style.display = 'none';
  }
});

// Toggle room device name edit form
function toggleDeviceEdit(roomId) {
  const el = document.getElementById('device-edit-' + roomId);
  if (!el) return;
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

// Auto-scroll to current time on page load
document.addEventListener('DOMContentLoaded', function () {
  const grid = document.getElementById('schedule-grid-scroll');
  if (!grid) return;

  // Find the "now" row by its blue dot indicator
  const nowDot = grid.querySelector('td span[style*="var(--accent)"]');
  if (nowDot) {
    const row = nowDot.closest('tr');
    if (row) {
      const rowTop = row.offsetTop;
      grid.scrollTop = Math.max(0, rowTop - grid.clientHeight / 3);
    }
  }
});
</script>
@endpush

@endsection
