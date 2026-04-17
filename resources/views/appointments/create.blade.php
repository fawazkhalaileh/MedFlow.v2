@extends('layouts.app')

@section('title', (isset($appointment) ? 'Edit Appointment' : 'Book Appointment') . ' - MedFlow CRM')
@section('breadcrumb', isset($appointment) ? 'Edit Appointment' : 'Book Appointment')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ isset($appointment) ? 'Edit Appointment' : 'Book Appointment' }}</h1>
    <p class="page-subtitle">Front desk booking only. Choose visit type first, then assign the right provider.</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('front-desk') }}" class="btn btn-secondary">Back to Front Desk</a>
  </div>
</div>

<form method="POST" action="{{ isset($appointment) ? route('appointments.update', $appointment) : route('appointments.store') }}" class="animate-in">
  @csrf
  @isset($appointment)
  @method('PUT')
  @endisset
  <div class="grid-2-1" style="align-items:start;">
    <div class="card">
      <div class="form-section">
        <div class="form-section-title">Patient</div>
        <div class="form-group">
          <label class="form-label">Patient</label>
          <input type="text" id="patient-search" class="form-input" placeholder="Search by name, phone, or patient code" value="{{ old('patient_name', $prePatient?->full_name) }}">
          <input type="hidden" name="patient_id" id="patient_id" value="{{ old('patient_id', $prePatient?->id) }}">
          <div id="patient-results" style="display:none;margin-top:8px;border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;background:var(--bg-secondary);"></div>
          @error('patient_id')<div class="form-error">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="form-section">
        <div class="form-section-title">Visit Type</div>
        <div class="form-row">
          <label style="display:flex;gap:10px;padding:14px;border:1px solid var(--border);border-radius:var(--radius-md);cursor:pointer;">
            <input type="radio" name="visit_type" value="{{ \App\Models\Appointment::VISIT_TYPE_DOCTOR }}" {{ old('visit_type', $appointment->visit_type ?? null) === \App\Models\Appointment::VISIT_TYPE_DOCTOR ? 'checked' : '' }}>
            <div>
              <div style="font-weight:600;">Doctor / Medical</div>
              <div style="font-size:.78rem;color:var(--text-secondary);">Shows in the doctor queue.</div>
            </div>
          </label>
          <label style="display:flex;gap:10px;padding:14px;border:1px solid var(--border);border-radius:var(--radius-md);cursor:pointer;">
            <input type="radio" name="visit_type" value="{{ \App\Models\Appointment::VISIT_TYPE_TECHNICIAN }}" {{ old('visit_type', $appointment->visit_type ?? \App\Models\Appointment::VISIT_TYPE_TECHNICIAN) === \App\Models\Appointment::VISIT_TYPE_TECHNICIAN ? 'checked' : '' }}>
            <div>
              <div style="font-weight:600;">Technician / Laser / Procedure</div>
              <div style="font-size:.78rem;color:var(--text-secondary);">Shows in the technician queue.</div>
            </div>
          </label>
        </div>
        @error('visit_type')<div class="form-error">{{ $message }}</div>@enderror
      </div>

      <div class="form-section">
        <div class="form-section-title">Schedule</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Provider</label>
            <select name="assigned_staff_id" id="provider-select" class="form-select">
              <option value="">Select provider</option>
            </select>
            @error('assigned_staff_id')<div class="form-error">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label class="form-label">Room</label>
            <select name="room_id" class="form-select">
              <option value="">Select room</option>
              @foreach($rooms as $room)
              <option value="{{ $room->id }}" @selected(old('room_id', $appointment->room_id ?? null) == $room->id)>{{ $room->name }}{{ $room->device_name ? ' - ' . $room->device_name : '' }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-row-3">
          <div class="form-group">
            <label class="form-label">Date</label>
            <input type="date" name="scheduled_date" value="{{ old('scheduled_date', isset($appointment) ? $appointment->scheduled_at?->toDateString() : today()->toDateString()) }}" class="form-input">
            @error('scheduled_date')<div class="form-error">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label class="form-label">Time</label>
            <input type="time" name="scheduled_time" value="{{ old('scheduled_time', isset($appointment) ? $appointment->scheduled_at?->format('H:i') : null) }}" class="form-input">
            @error('scheduled_time')<div class="form-error">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label class="form-label">Duration (Minutes)</label>
            <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $appointment->duration_minutes ?? null) }}" class="form-input" placeholder="Auto from service">
          </div>
        </div>
      </div>

      <div class="form-section">
        <div class="form-section-title">Visit Details</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Service</label>
            <select name="service_id" class="form-select">
              <option value="">Select service</option>
              @foreach($services as $service)
              <option value="{{ $service->id }}" data-duration="{{ $service->duration_minutes }}" @selected(old('service_id', $appointment->service_id ?? null) == $service->id)>{{ $service->name }}</option>
              @endforeach
            </select>
            @error('service_id')<div class="form-error">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label class="form-label">Reason</label>
            <select name="reason_id" class="form-select">
              <option value="">Select reason</option>
              @foreach($reasons as $reason)
              <option value="{{ $reason->id }}" @selected(old('reason_id', $appointment->reason_id ?? null) == $reason->id)>{{ $reason->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Visit Reason / Note</label>
            <textarea name="reason_notes" class="form-textarea">{{ old('reason_notes', $appointment->reason_notes ?? null) }}</textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Front Desk Note</label>
            <textarea name="front_desk_note" class="form-textarea">{{ old('front_desk_note', $appointment->front_desk_note ?? null) }}</textarea>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Patient Package</label>
          <select name="patient_package_id" class="form-select">
            <option value="">No package attached</option>
            @foreach($patientPackages as $purchase)
            <option value="{{ $purchase->id }}" @selected(old('patient_package_id', $appointment->patient_package_id ?? null) == $purchase->id)>
              {{ $purchase->patient?->full_name }} - {{ $purchase->package?->name }} ({{ $purchase->remaining_sessions }} left)
            </option>
            @endforeach
          </select>
        </div>
      </div>

      <input type="hidden" name="branch_id" value="{{ old('branch_id', $appointment->branch_id ?? $branchId) }}">

      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">{{ isset($appointment) ? 'Update Appointment' : 'Book Appointment' }}</button>
        <a href="{{ route('front-desk') }}" class="btn btn-secondary">Cancel</a>
      </div>
    </div>

    <div class="card">
      <div class="card-title" style="margin-bottom:10px;">Booking Rules</div>
      <div style="display:flex;flex-direction:column;gap:10px;font-size:.82rem;color:var(--text-secondary);">
        <div>1. Choose the patient first.</div>
        <div>2. Select visit type so the system filters providers correctly.</div>
        <div>3. Doctor visits go to doctor queue. Technician visits go to technician queue.</div>
        <div>4. Front desk only handles arrival, waiting placement, and checkout.</div>
      </div>
    </div>
  </div>
</form>

@push('scripts')
<script>
const doctors = @json($doctors->map(fn ($user) => ['id' => $user->id, 'name' => $user->full_name]));
const technicians = @json($technicians->map(fn ($user) => ['id' => $user->id, 'name' => $user->full_name]));
const providerSelect = document.getElementById('provider-select');
const patientSearch = document.getElementById('patient-search');
const patientResults = document.getElementById('patient-results');
const patientIdField = document.getElementById('patient_id');

function updateProviders() {
  const visitType = document.querySelector('input[name="visit_type"]:checked')?.value;
  const selected = "{{ old('assigned_staff_id', $appointment->assigned_staff_id ?? null) }}";
  const options = visitType === 'doctor' ? doctors : technicians;

  providerSelect.innerHTML = '<option value="">Select provider</option>' + options.map((provider) => {
    const selectedAttr = String(provider.id) === String(selected) ? 'selected' : '';
    return `<option value="${provider.id}" ${selectedAttr}>${provider.name}</option>`;
  }).join('');
}

document.querySelectorAll('input[name="visit_type"]').forEach((input) => {
  input.addEventListener('change', updateProviders);
});
updateProviders();

let timeout;
patientSearch?.addEventListener('input', function () {
  clearTimeout(timeout);
  const q = this.value.trim();
  if (q.length < 2) {
    patientResults.style.display = 'none';
    return;
  }

  timeout = setTimeout(() => {
    fetch(`{{ route('patients.search') }}?q=${encodeURIComponent(q)}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(response => response.json())
      .then((patients) => {
        patientResults.innerHTML = patients.map((patient) => `
          <button type="button" data-id="${patient.id}" data-name="${patient.full_name}" style="display:block;width:100%;text-align:left;padding:10px 12px;border:none;border-bottom:1px solid var(--border-light);background:#fff;cursor:pointer;">
            <div style="font-weight:600;">${patient.full_name}</div>
            <div style="font-size:.76rem;color:var(--text-tertiary);">${patient.patient_code} | ${patient.phone}</div>
          </button>
        `).join('') || '<div style="padding:10px 12px;color:var(--text-tertiary);">No patients found.</div>';
        patientResults.style.display = 'block';
      });
  }, 250);
});

patientResults?.addEventListener('click', function (event) {
  const button = event.target.closest('button[data-id]');
  if (!button) return;

  patientIdField.value = button.dataset.id;
  patientSearch.value = button.dataset.name;
  patientResults.style.display = 'none';
});

document.addEventListener('click', function (event) {
  if (!event.target.closest('#patient-search') && !event.target.closest('#patient-results')) {
    patientResults.style.display = 'none';
  }
});

document.querySelector('select[name="service_id"]')?.addEventListener('change', function () {
  const durationField = document.querySelector('input[name="duration_minutes"]');
  if (durationField.value) return;
  const option = this.selectedOptions[0];
  if (option?.dataset.duration) {
    durationField.value = option.dataset.duration;
  }
});
</script>
@endpush
@endsection
