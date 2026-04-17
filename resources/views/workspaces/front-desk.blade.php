@extends('layouts.app')

@section('title', 'Front Desk - MedFlow CRM')
@section('breadcrumb', 'Front Desk')

@push('page_style')
<style>
.page-container { padding: 16px 20px !important; }
#schedule-grid-scroll { max-height: calc(100vh - 270px) !important; }
</style>
@endpush

@section('content')

@if(session('success'))
<div style="background:var(--success-light);border:1px solid #6ee7b7;border-radius:var(--radius-md);padding:10px 16px;margin-bottom:14px;color:#065f46;font-size:.85rem;">
  {{ session('success') }}
</div>
@endif

@if(session('receipt_transaction_id') && session('open_receipt_after_checkout'))
<div class="card" style="margin-bottom:14px;border:1px solid rgba(37,99,235,.18);background:linear-gradient(135deg,rgba(37,99,235,.05),rgba(16,185,129,.07));">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div>
      <div class="card-title" style="margin-bottom:8px;">Receipt Ready</div>
      <p style="font-size:.86rem;color:var(--text-secondary);line-height:1.6;margin:0;">
        Checkout was completed and the receipt is ready to open.
      </p>
    </div>
    <a href="{{ route('finance.transactions.receipt', session('receipt_transaction_id')) }}" target="_blank" class="btn btn-primary btn-sm">
      Open Receipt PDF
    </a>
  </div>
</div>
@endif

@if(session('receipt_transaction_id') && session('open_receipt_after_checkout'))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  window.open(@json(route('finance.transactions.receipt', session('receipt_transaction_id'))), '_blank');
});
</script>
@endpush
@endif

<div class="page-header animate-in" style="margin-bottom:14px;">
  <div>
    <h1 class="page-title" style="font-size:1.35rem;">Front Desk</h1>
    <p class="page-subtitle">{{ Auth::user()->first_name }} &bull; {{ now()->format('l, d F Y') }}</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('appointments.checkout.index') }}" class="btn btn-secondary">
      Checkout Dashboard
    </a>
    <a href="{{ route('appointments.create') }}" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Appointment
    </a>
    <a href="{{ route('patients.create') }}" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      New Patient
    </a>
    @if(in_array(auth()->user()->employee_type, ['branch_manager','system_admin']))
    <button onclick="document.getElementById('add-device-modal').style.display='flex'" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/><line x1="9" y1="10" x2="15" y2="10"/><line x1="12" y1="7" x2="12" y2="13"/></svg>
      Add Device
    </button>
    <a href="{{ route('front-desk.export-pdf') }}" class="btn btn-secondary" target="_blank">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Export PDF
    </a>
    @endif
    @if(in_array(auth()->user()->employee_type, ['branch_manager','system_admin']))
    <a href="{{ route('appointments.kanban') }}" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><rect x="3" y="3" width="7" height="18"/><rect x="14" y="3" width="7" height="10"/></svg>
      Kanban
    </a>
    @endif
    <button onclick="location.reload()" class="btn btn-secondary" title="Refresh">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
    </button>
  </div>
</div>

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
    <div style="font-size:.73rem;color:var(--text-secondary);margin-top:2px;">Upcoming Bookings</div>
  </div>
  <div style="background:{{ $stats['no_show'] > 0 ? 'var(--danger-light)' : 'var(--bg-secondary)' }};border:1px solid {{ $stats['no_show'] > 0 ? '#fca5a5' : 'var(--border)' }};border-radius:var(--radius-md);padding:12px 14px;text-align:center;">
    <div style="font-size:1.6rem;font-weight:700;color:{{ $stats['no_show'] > 0 ? 'var(--danger)' : 'var(--text-primary)' }};">{{ $stats['no_show'] }}</div>
    <div style="font-size:.73rem;color:var(--text-secondary);margin-top:2px;">No Shows</div>
  </div>
</div>

<div class="card animate-in" style="margin-bottom:16px;padding:14px 18px;position:relative;">
  <div style="display:flex;align-items:center;gap:12px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:var(--text-tertiary);flex-shrink:0;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="quick-search" placeholder="Quick patient search - name, phone, code, email..."
      style="flex:1;border:none;background:transparent;font-size:.9rem;outline:none;font-family:inherit;color:var(--text-primary);"
      autocomplete="off">
  </div>
  <div id="quick-search-results" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-lg);z-index:100;max-height:300px;overflow-y:auto;"></div>
</div>

@php
$nowSlot = now()->minute < 30 ? now()->format('H') . ':00' : now()->format('H') . ':30';

$statusDotColor = [
    'booked' => '#9ca3af',
    'scheduled' => '#9ca3af',
    'confirmed' => '#7c3aed',
    'arrived' => '#d97706',
    'waiting_doctor' => '#d97706',
    'waiting_technician' => '#7c3aed',
    'in_doctor_visit' => '#2563eb',
    'in_technician_visit' => '#2563eb',
    'completed_waiting_checkout' => '#059669',
    'checked_out' => '#059669',
    'completed' => '#059669',
    'no_show' => '#dc2626',
    'cancelled' => '#6b7280',
];

$statusLabel = [
    'booked' => 'Booked',
    'scheduled' => 'Scheduled',
    'confirmed' => 'Confirmed',
    'arrived' => 'Arrived',
    'waiting_doctor' => 'Waiting Doctor',
    'waiting_technician' => 'Waiting Technician',
    'in_doctor_visit' => 'Doctor Visit',
    'in_technician_visit' => 'In Session',
    'completed_waiting_checkout' => 'Checkout Ready',
    'checked_out' => 'Done',
    'completed' => 'Done',
    'no_show' => 'No Show',
    'cancelled' => 'Cancelled',
];

$nextFrontDeskAction = function ($appointment) {
    if (in_array($appointment->status, \App\Models\Appointment::bookedStatuses(), true)) {
        return [
            'label' => 'Mark Arrived',
            'status' => \App\Models\Appointment::STATUS_ARRIVED,
            'class' => 'btn-primary',
        ];
    }

    if ($appointment->status === \App\Models\Appointment::STATUS_ARRIVED) {
        return [
            'label' => $appointment->isDoctorVisit() ? 'Send To Doctor' : 'Send To Technician',
            'status' => $appointment->isDoctorVisit()
                ? \App\Models\Appointment::STATUS_WAITING_DOCTOR
                : \App\Models\Appointment::STATUS_WAITING_TECHNICIAN,
            'class' => 'btn-secondary',
        ];
    }

    if ($appointment->status === \App\Models\Appointment::STATUS_COMPLETED_WAITING_CHECKOUT) {
        return [
            'label' => 'Proceed To Checkout',
            'href' => route('appointments.checkout', $appointment),
            'class' => 'btn-primary',
        ];
    }

    return null;
};
@endphp

<div class="card animate-in" style="padding:0;margin-bottom:16px;">
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

  <div style="overflow-x:auto;overflow-y:auto;max-height:calc(100vh - 310px);" id="schedule-grid-scroll">
    <table style="width:100%;border-collapse:collapse;min-width:{{ 76 + $rooms->count() * 190 + (count($unassigned) > 0 ? 190 : 0) }}px;">
      <thead>
        <tr style="position:sticky;top:0;z-index:20;">
          <th style="width:72px;min-width:72px;background:var(--bg-secondary);border-bottom:2px solid var(--border);border-right:2px solid var(--border);padding:10px 8px;font-size:.72rem;color:var(--text-tertiary);text-align:center;font-weight:500;position:sticky;left:0;z-index:30;">Time</th>

          @foreach($rooms as $room)
          <th style="background:var(--bg-secondary);border-bottom:2px solid var(--border);border-right:1px solid var(--border);padding:10px 12px;min-width:185px;text-align:left;">
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
            @if(in_array(auth()->user()->employee_type, ['branch_manager','system_admin']))
            <div id="device-edit-{{ $room->id }}" style="display:none;margin-top:6px;">
              <form method="POST" action="{{ route('rooms.update-device-name', $room) }}" style="display:flex;gap:4px;align-items:center;">
                @csrf
                @method('PATCH')
                <input type="text" name="device_name"
                  value="{{ $room->device_name }}"
                  placeholder="Device name..."
                  style="flex:1;min-width:0;font-size:.72rem;padding:3px 6px;border:1px solid var(--border);border-radius:4px;background:var(--bg-primary);color:var(--text-primary);">
                <button type="submit" style="font-size:.72rem;padding:3px 8px;background:var(--accent);color:#fff;border:none;border-radius:4px;cursor:pointer;">Save</button>
                <button type="button" onclick="toggleDeviceEdit({{ $room->id }})" style="font-size:.72rem;padding:3px 6px;background:none;border:1px solid var(--border);border-radius:4px;cursor:pointer;color:var(--text-secondary);">✕</button>
              </form>
            </div>
            @endif
          </th>
          @endforeach

          @if(count($unassigned) > 0)
          <th style="background:var(--bg-secondary);border-bottom:2px solid var(--border);padding:10px 12px;min-width:185px;text-align:left;">
            <div style="font-size:.82rem;font-weight:700;color:var(--warning);">Unassigned</div>
            <div style="font-size:.67rem;color:var(--text-tertiary);margin-top:1px;">No room assigned</div>
          </th>
          @endif
        </tr>
      </thead>
      <tbody>
        @foreach($slots as $slot)
        @php
          $isHour = str_ends_with($slot, ':00');
          $isNow = ($slot === $nowSlot);
          $isPast = \Carbon\Carbon::createFromFormat('H:i', $slot)->lt(now()->subMinutes(1));
          $rowBg = $isNow ? 'background:rgba(37,99,235,.04);' : ($isPast ? 'background:rgba(0,0,0,.012);' : '');
        @endphp
        <tr style="{{ $rowBg }}{{ $isHour ? 'border-top:1px solid var(--border);' : '' }}">
          <td style="background:{{ $isNow ? 'rgba(37,99,235,.08)' : ($isPast ? 'var(--bg-tertiary)' : 'var(--bg-secondary)') }};border-right:2px solid var(--border);border-bottom:1px solid {{ $isHour ? 'var(--border)' : 'var(--border-light)' }};padding:5px 8px;text-align:center;vertical-align:top;white-space:nowrap;width:72px;min-width:72px;position:sticky;left:0;z-index:10;">
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

          @foreach($rooms as $room)
          @php
            $cellAppts = collect($grid[$room->id][$slot] ?? []);
            $isDouble = $cellAppts->count() > 1;
          @endphp
          <td style="border-right:1px solid var(--border-light);border-bottom:1px solid {{ $isHour ? 'var(--border)' : 'var(--border-light)' }};padding:3px 4px;vertical-align:top;min-width:185px;">
            @if($isDouble)
              <div style="font-size:.6rem;color:var(--danger);font-weight:700;background:var(--danger-light);border-radius:3px;padding:1px 5px;margin-bottom:3px;text-align:center;">⚠ Double booked</div>
            @endif

            @foreach($cellAppts as $appt)
              @php
                $sc = $staffColorMap[$appt->assigned_staff_id] ?? ['border' => '#6b7280', 'bg' => '#f9fafb'];
                $isDoc = in_array($appt->assignedStaff?->employee_type, ['doctor', 'nurse']);
                $dotColor = $statusDotColor[$appt->status] ?? '#9ca3af';
                $label = $statusLabel[$appt->status] ?? ucfirst(str_replace('_', ' ', $appt->status));
                $nextAction = $nextFrontDeskAction($appt);
              @endphp
              <div
                 title="{{ $appt->patient?->full_name }} - {{ $appt->service?->name }} ({{ $label }})"
                 style="display:block;background:{{ $sc['bg'] }};border-left:3px solid {{ $sc['border'] }};border-radius:var(--radius-sm);padding:5px 7px;margin-bottom:3px;transition:filter .12s;"
                 onmouseover="this.style.filter='brightness(.94)'" onmouseout="this.style.filter=''">

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

                <div style="font-weight:600;font-size:.78rem;color:var(--text-primary);line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  {{ $appt->patient?->full_name }}
                </div>

                <div style="font-size:.71rem;color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  {{ $appt->service?->name ?? '-' }}
                </div>

                <div style="font-size:.68rem;color:var(--text-tertiary);">
                  {{ $appt->assignedStaff?->first_name ?? '-' }}
                </div>

                @if($appt->reason_notes)
                <div style="font-size:.66rem;color:var(--text-tertiary);font-style:italic;margin-top:3px;padding-top:3px;border-top:1px solid rgba(0,0,0,.07);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  {{ \Illuminate\Support\Str::limit($appt->reason_notes, 48) }}
                </div>
                @endif

                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;padding-top:6px;border-top:1px solid rgba(0,0,0,.07);">
                  @if($nextAction)
                    @if(isset($nextAction['href']))
                    <a href="{{ $nextAction['href'] }}" class="btn {{ $nextAction['class'] }} btn-sm" style="font-size:.68rem;">
                      {{ $nextAction['label'] }}
                    </a>
                    @else
                    <form method="POST" action="{{ route('appointments.status', $appt) }}">
                      @csrf @method('PATCH')
                      <input type="hidden" name="status" value="{{ $nextAction['status'] }}">
                      <button type="submit" class="btn {{ $nextAction['class'] }} btn-sm" style="font-size:.68rem;">
                        {{ $nextAction['label'] }}
                      </button>
                    </form>
                    @endif
                  @endif
                  <a href="{{ route('appointments.edit', $appt) }}" class="btn btn-ghost btn-sm" style="font-size:.68rem;">Edit</a>
                </div>
              </div>
            @endforeach
          </td>
          @endforeach

          @if(count($unassigned) > 0)
          <td style="border-bottom:1px solid {{ $isHour ? 'var(--border)' : 'var(--border-light)' }};padding:3px 4px;vertical-align:top;min-width:185px;background:{{ $isPast ? 'rgba(0,0,0,.012)' : '' }};">
            @foreach($unassigned as $appt)
              @php
                $dt2 = \Carbon\Carbon::parse($appt->scheduled_at);
                $snapMin2 = $dt2->minute < 30 ? '00' : '30';
                $apptSlot = sprintf('%02d:%02d', $dt2->hour, $snapMin2);
              @endphp
              @if($apptSlot === $slot)
                @php
                  $sc = $staffColorMap[$appt->assigned_staff_id] ?? ['border' => '#d97706', 'bg' => '#fffbeb'];
                  $nextAction = $nextFrontDeskAction($appt);
                @endphp
                <div
                   style="display:block;background:{{ $sc['bg'] }};border-left:3px solid #d97706;border-radius:var(--radius-sm);padding:5px 7px;margin-bottom:3px;transition:filter .12s;"
                   onmouseover="this.style.filter='brightness(.94)'" onmouseout="this.style.filter=''">
                  <div style="font-size:.69rem;font-weight:700;color:#d97706;">{{ $dt2->format('g:i A') }}</div>
                  <div style="font-weight:600;font-size:.78rem;color:var(--text-primary);">{{ $appt->patient?->full_name }}</div>
                  <div style="font-size:.71rem;color:var(--text-secondary);">{{ $appt->service?->name ?? '-' }}</div>
                  <div style="font-size:.68rem;color:var(--text-tertiary);">{{ $appt->assignedStaff?->first_name ?? '-' }}</div>
                  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;padding-top:6px;border-top:1px solid rgba(0,0,0,.07);">
                    @if($nextAction)
                      @if(isset($nextAction['href']))
                      <a href="{{ $nextAction['href'] }}" class="btn {{ $nextAction['class'] }} btn-sm" style="font-size:.68rem;">
                        {{ $nextAction['label'] }}
                      </a>
                      @else
                      <form method="POST" action="{{ route('appointments.status', $appt) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $nextAction['status'] }}">
                        <button type="submit" class="btn {{ $nextAction['class'] }} btn-sm" style="font-size:.68rem;">
                          {{ $nextAction['label'] }}
                        </button>
                      </form>
                      @endif
                    @endif
                    <a href="{{ route('appointments.edit', $appt) }}" class="btn btn-ghost btn-sm" style="font-size:.68rem;">Edit</a>
                  </div>
                </div>
              @endif
            @endforeach
          </td>
          @endif
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr 220px;gap:14px;align-items:start;" class="animate-in">
  <div class="card" style="{{ $needsConfirmation->isNotEmpty() ? 'border:1px solid #fde68a;' : '' }}">
    <div class="card-header">
      <div class="card-title" style="font-size:.88rem;{{ $needsConfirmation->isNotEmpty() ? 'color:var(--warning);' : '' }}">Upcoming Bookings</div>
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
          &bull; {{ $appt->service?->name ?? '-' }}
        </div>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
        @php $nextAction = $nextFrontDeskAction($appt); @endphp
        @if($nextAction)
          @if(isset($nextAction['href']))
          <a href="{{ $nextAction['href'] }}" class="btn {{ $nextAction['class'] }} btn-sm" style="font-size:.7rem;white-space:nowrap;">
            {{ $nextAction['label'] }}
          </a>
          @else
          <form method="POST" action="{{ route('appointments.status', $appt) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="{{ $nextAction['status'] }}">
            <button type="submit" class="btn {{ $nextAction['class'] }} btn-sm" style="font-size:.7rem;white-space:nowrap;">
              {{ $nextAction['label'] }}
            </button>
          </form>
          @endif
        @endif
        <a href="{{ route('appointments.edit', $appt) }}" class="btn btn-secondary btn-sm" style="font-size:.7rem;white-space:nowrap;">Open</a>
      </div>
    </div>
    @empty
    <p style="font-size:.8rem;color:var(--text-tertiary);padding:8px 0;">No upcoming bookings need review.</p>
    @endforelse
  </div>

  <div class="card" style="{{ $checkoutReady->isNotEmpty() ? 'border:1px solid #bbf7d0;' : '' }}">
    <div class="card-header">
      <div class="card-title" style="font-size:.88rem;">Ready for Checkout</div>
      @if($checkoutReady->isNotEmpty())
        <span class="badge badge-green">{{ $checkoutReady->count() }}</span>
      @endif
    </div>
    @forelse($checkoutReady as $appt)
    <div style="padding:8px 0;border-bottom:1px solid var(--border-light);display:flex;flex-direction:column;gap:4px;">
      <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
        <div>
          <div style="font-size:.82rem;font-weight:600;">{{ $appt->patient?->full_name }}</div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">
            {{ $appt->chargeable_service_names?->implode(', ') ?: ($appt->service?->name ?? 'Service not selected') }}
            @if($appt->service?->price)
              &bull; {{ number_format((float) $appt->service->price, 2) }}
            @endif
          </div>
        </div>
        <a href="{{ route('appointments.checkout', $appt) }}" class="btn btn-primary btn-sm" style="font-size:.7rem;">Proceed To Checkout</a>
      </div>
      @if($appt->doctor_visit_outcome)
      <div style="font-size:.72rem;color:var(--text-secondary);">
        {{ \Illuminate\Support\Str::headline($appt->doctor_visit_outcome) }}
      </div>
      @endif
      @if($appt->checkout_summary)
      <div style="font-size:.72rem;color:var(--text-tertiary);">
        {{ $appt->checkout_summary }}
      </div>
      @endif
      <a href="{{ route('appointments.edit', $appt) }}" class="btn btn-ghost btn-sm" style="width:max-content;font-size:.72rem;padding-left:0;">Open Appointment</a>
    </div>
    @empty
    <p style="font-size:.8rem;color:var(--text-tertiary);padding:8px 0;">No visits are waiting for reception checkout.</p>
    @endforelse
  </div>

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
      <a href="{{ route('appointments.index') }}" class="btn btn-secondary btn-sm" style="justify-content:flex-start;gap:8px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        All Appointments
      </a>
      <a href="{{ route('patients.index') }}" class="btn btn-ghost btn-sm" style="justify-content:flex-start;gap:8px;color:var(--text-secondary);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        All Patients
      </a>
    </div>
  </div>
</div>

@if(in_array(auth()->user()->employee_type, ['branch_manager','system_admin']))
<div id="add-device-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:900;align-items:center;justify-content:center;">
  <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px;width:420px;max-width:calc(100vw - 40px);box-shadow:var(--shadow-lg);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <h3 style="font-size:1rem;font-weight:700;">Add New Device</h3>
      <button onclick="document.getElementById('add-device-modal').style.display='none'"
        style="background:none;border:none;cursor:pointer;color:var(--text-tertiary);font-size:1.3rem;line-height:1;">&times;</button>
    </div>
    <form method="POST" action="{{ route('rooms.store') }}">
      @csrf
      <div style="display:flex;flex-direction:column;gap:14px;">
        <div>
          <label class="form-label">Room Label *</label>
          <input type="text" name="name" class="form-input" required placeholder="e.g. Laser Room D" autofocus>
          <div style="font-size:.72rem;color:var(--text-tertiary);margin-top:4px;">Internal identifier shown on the grid column header.</div>
        </div>
        <div>
          <label class="form-label">Device Name</label>
          <input type="text" name="device_name" class="form-input" placeholder="e.g. Lumenis LightSheer">
          <div style="font-size:.72rem;color:var(--text-tertiary);margin-top:4px;">Displayed as the column header. Leave blank to use Room Label.</div>
        </div>
        <div>
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-input" placeholder="Optional - treatment types, notes...">
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
          <button type="submit" class="btn btn-primary">Add Device</button>
          <button type="button" onclick="document.getElementById('add-device-modal').style.display='none'" class="btn btn-ghost">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endif

@push('scripts')
<script>
const qs = document.getElementById('quick-search');
const qsResults = document.getElementById('quick-search-results');
let qsTimeout;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

function searchActionForAppointment(appointment) {
  if (['booked', 'scheduled', 'confirmed'].includes(appointment.status)) {
    return { label: 'Mark Arrived', status: 'arrived', className: 'btn-primary' };
  }

  if (appointment.status === 'arrived') {
    return {
      label: appointment.visit_type === 'doctor' ? 'Send To Doctor' : 'Send To Technician',
      status: appointment.visit_type === 'doctor' ? 'waiting_doctor' : 'waiting_technician',
      className: 'btn-secondary',
    };
  }

  if (appointment.status === 'completed_waiting_checkout') {
    return {
      label: 'Proceed To Checkout',
      href: `/appointments/${appointment.id}/checkout`,
      className: 'btn-primary',
    };
  }

  return null;
}

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
          <div style="padding:10px 14px;border-bottom:1px solid var(--border-light);"
             onmouseover="this.style.background='var(--bg-tertiary)'" onmouseout="this.style.background=''">
            <div style="display:flex;align-items:flex-start;gap:10px;">
              <a href="/patients/${p.id}" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;flex:1;">
                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.68rem;font-weight:700;flex-shrink:0;">
                  ${p.full_name.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase()}
                </div>
                <div>
                  <div style="font-weight:600;font-size:.84rem;">${p.full_name}</div>
                  <div style="font-size:.72rem;color:var(--text-tertiary);">${p.patient_code} &bull; ${p.phone}</div>
                </div>
              </a>
              <div style="margin-left:auto;">
                <a href="/patients/${p.id}" style="font-size:.7rem;padding:3px 8px;background:var(--accent);color:#fff;border-radius:4px;text-decoration:none;">Open</a>
              </div>
            </div>
            ${p.appointments?.length ? `
              <div style="margin-top:8px;display:grid;gap:6px;">
                ${p.appointments.map(appt => {
                  const action = searchActionForAppointment(appt);
                  return `
                  <div style="padding:8px 10px;background:var(--bg-primary);border:1px solid var(--border-light);border-radius:8px;display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                    <div>
                      <div style="font-size:.78rem;font-weight:600;">${appt.scheduled_at || ''}</div>
                      <div style="font-size:.74rem;color:var(--text-secondary);">${appt.service || 'Appointment'} • ${appt.staff || '-'}</div>
                      <div style="font-size:.7rem;color:var(--text-tertiary);">${appt.status.replaceAll('_', ' ')}</div>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
                      ${action?.href ? `<a href="${action.href}" class="btn ${action.className} btn-sm" style="font-size:.68rem;">${action.label}</a>` : ''}
                      ${action?.status ? `
                        <form method="POST" action="/appointments/${appt.id}/status">
                          <input type="hidden" name="_token" value="${csrfToken}">
                          <input type="hidden" name="_method" value="PATCH">
                          <input type="hidden" name="status" value="${action.status}">
                          <button type="submit" class="btn ${action.className} btn-sm" style="font-size:.68rem;">${action.label}</button>
                        </form>` : ''}
                      <a href="/appointments/${appt.id}/edit" class="btn btn-ghost btn-sm" style="font-size:.68rem;">Open Appointment</a>
                    </div>
                  </div>`;
                }).join('')}
              </div>
            ` : ''}
          </div>`).join('');
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

function toggleDeviceEdit(roomId) {
  const el = document.getElementById('device-edit-' + roomId);
  if (!el) return;
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function () {
  const grid = document.getElementById('schedule-grid-scroll');
  if (!grid) return;

  const nowDot = grid.querySelector('td div[style*="background:var(--accent)"]');
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
