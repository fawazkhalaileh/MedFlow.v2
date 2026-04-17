<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Front Desk Schedule</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
    h1 { font-size: 18px; margin-bottom: 4px; }
    p { margin: 0 0 12px; color: #6b7280; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
    th { background: #f3f4f6; text-align: left; }
    .time { width: 52px; font-weight: bold; }
    .card { border-left: 3px solid #2563eb; padding-left: 5px; margin-bottom: 4px; }
    .muted { color: #6b7280; }
  </style>
</head>
<body>
  <h1>Front Desk Schedule</h1>
  <p>{{ $dateObj->format('l, d F Y') }} | {{ $user->first_name }}</p>

  <table>
    <thead>
      <tr>
        <th class="time">Time</th>
        @foreach($rooms as $room)
        <th>{{ $room->device_name ?? $room->name }}</th>
        @endforeach
        @if(count($unassigned) > 0)
        <th>Unassigned</th>
        @endif
      </tr>
    </thead>
    <tbody>
      @foreach($slots as $slot)
      <tr>
        <td class="time">{{ $slot }}</td>
        @foreach($rooms as $room)
        <td>
          @foreach($grid[$room->id][$slot] ?? [] as $appt)
          <div class="card">
            <div><strong>{{ $appt->patient?->full_name }}</strong></div>
            <div>{{ $appt->service?->name ?? 'Visit' }}</div>
            <div class="muted">{{ $appt->assignedStaff?->full_name }}</div>
          </div>
          @endforeach
        </td>
        @endforeach
        @if(count($unassigned) > 0)
        <td>
          @foreach($unassigned as $appt)
            @php
              $dt2 = \Carbon\Carbon::parse($appt->scheduled_at);
              $snapMin2 = $dt2->minute < 30 ? '00' : '30';
              $apptSlot = sprintf('%02d:%02d', $dt2->hour, $snapMin2);
            @endphp
            @if($apptSlot === $slot)
            <div class="card">
              <div><strong>{{ $appt->patient?->full_name }}</strong></div>
              <div>{{ $appt->service?->name ?? 'Visit' }}</div>
              <div class="muted">{{ $appt->assignedStaff?->full_name }}</div>
            </div>
            @endif
          @endforeach
        </td>
        @endif
      </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
