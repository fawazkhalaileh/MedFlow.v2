@extends('layouts.app')

@section('title', 'Book Appointment - MedFlow CRM')
@section('breadcrumb', 'Appointments / New Booking')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Book Appointment</h1>
    <p class="page-subtitle">Create a new appointment for an existing or new patient</p>
  </div>
  <a href="{{ route('front-desk') }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Front Desk
  </a>
</div>

@if($errors->any())
<div style="background:var(--danger-light);border:1px solid #fca5a5;border-radius:var(--radius-md);padding:12px 16px;margin-bottom:18px;color:#991b1b;">
  <strong>Please fix the following:</strong>
  <ul style="margin-top:6px;padding-left:18px;">
    @foreach($errors->all() as $error)
    <li style="font-size:.84rem;">{{ $error }}</li>
    @endforeach
  </ul>
</div>
@endif

<form method="POST" action="{{ route('appointments.store') }}" id="booking-form">
@csrf

<div style="display:grid;grid-template-columns:1fr 340px;gap:18px;align-items:start;" class="animate-in" style="animation-delay:.05s;">

  {{-- LEFT: Main booking fields --}}
  <div style="display:flex;flex-direction:column;gap:18px;">

    {{-- PATIENT SEARCH --}}
    <div class="card">
      <div class="card-header">
        <div class="card-title">Patient</div>
        <a href="{{ route('patients.create') }}" class="btn btn-ghost btn-sm" style="font-size:.78rem;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Register New Patient
        </a>
      </div>

      {{-- Hidden patient_id --}}
      <input type="hidden" name="patient_id" id="patient_id" value="{{ $prePatient?->id ?? old('patient_id') }}">

      {{-- Selected patient display --}}
      <div id="patient-selected" style="display:{{ ($prePatient || old('patient_id')) ? 'flex' : 'none' }};align-items:center;justify-content:space-between;padding:12px;background:var(--accent-light);border-radius:var(--radius-md);margin-bottom:10px;border:1px solid #bfdbfe;">
        <div style="display:flex;align-items:center;gap:10px;">
          <div class="avatar avatar-sm" style="background:linear-gradient(135deg,var(--accent),#7c3aed);width:32px;height:32px;font-size:.72rem;flex-shrink:0;" id="patient-avatar">
            {{ $prePatient ? strtoupper(substr($prePatient->first_name,0,1).substr($prePatient->last_name,0,1)) : '' }}
          </div>
          <div>
            <div style="font-weight:600;font-size:.88rem;" id="patient-name">{{ $prePatient?->full_name }}</div>
            <div style="font-size:.74rem;color:var(--text-secondary);" id="patient-meta">
              {{ $prePatient?->patient_code }} &bull; {{ $prePatient?->phone }}
            </div>
          </div>
        </div>
        <button type="button" onclick="clearPatient()" class="btn btn-ghost btn-sm" style="font-size:.75rem;color:var(--danger);">Change</button>
      </div>

      {{-- Search input --}}
      <div id="patient-search-wrap" style="{{ ($prePatient || old('patient_id')) ? 'display:none;' : '' }}position:relative;">
        <div class="filter-search-wrap" style="max-width:100%;margin-bottom:0;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="patient-search-input" class="filter-search" style="width:100%;"
            placeholder="Search by name, phone, email or patient code..."
            autocomplete="off">
        </div>
        <div id="patient-results" style="display:none;position:absolute;top:100%;left:0;right:0;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-lg);z-index:50;max-height:280px;overflow-y:auto;margin-top:4px;"></div>
        <div style="margin-top:8px;font-size:.78rem;color:var(--text-tertiary);">
          Type at least 2 characters. If the patient is not registered, use "Register New Patient" above.
        </div>
      </div>
    </div>

    {{-- SERVICE + DATE/TIME --}}
    <div class="card">
      <div class="card-title" style="margin-bottom:16px;">Service &amp; Schedule</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

        <div class="form-group" style="grid-column:span 2;">
          <label class="form-label">Service <span style="color:var(--danger)">*</span></label>
          <select name="service_id" id="service_id" class="form-input" required onchange="fillDuration()">
            <option value="">-- Select Service --</option>
            @foreach($services as $svc)
            <option value="{{ $svc->id }}"
              data-duration="{{ $svc->duration_minutes }}"
              {{ old('service_id') == $svc->id ? 'selected' : '' }}>
              {{ $svc->name }}
              @if($svc->duration_minutes)
              ({{ $svc->duration_minutes }} min)
              @endif
            </option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Date <span style="color:var(--danger)">*</span></label>
          <input type="date" name="scheduled_date" class="form-input"
            value="{{ old('scheduled_date', today()->format('Y-m-d')) }}"
            min="{{ today()->format('Y-m-d') }}" required>
        </div>

        <div class="form-group">
          <label class="form-label">Time <span style="color:var(--danger)">*</span></label>
          <input type="time" name="scheduled_time" class="form-input"
            value="{{ old('scheduled_time', '09:00') }}" required>
        </div>

        <div class="form-group">
          <label class="form-label">Duration (minutes)</label>
          <input type="number" name="duration_minutes" id="duration_minutes" class="form-input"
            value="{{ old('duration_minutes', 60) }}" min="5" max="480" step="5">
        </div>

        <div class="form-group">
          <label class="form-label">Appointment Type</label>
          <select name="appointment_type" class="form-input">
            <option value="booked"   {{ old('appointment_type','booked') === 'booked'   ? 'selected' : '' }}>Booked</option>
            <option value="walk_in"  {{ old('appointment_type') === 'walk_in'  ? 'selected' : '' }}>Walk-In</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-input">
            <option value="scheduled" {{ old('status','scheduled') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
            <option value="confirmed" {{ old('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
            <option value="booked"    {{ old('status') === 'booked'    ? 'selected' : '' }}>Booked (Unconfirmed)</option>
          </select>
        </div>

      </div>
    </div>

    {{-- NOTES --}}
    <div class="card">
      <div class="card-title" style="margin-bottom:12px;">Notes &amp; Reason</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="form-group">
          <label class="form-label">Appointment Reason</label>
          <select name="reason_id" class="form-input">
            <option value="">-- Select Reason --</option>
            @foreach($reasons as $r)
            <option value="{{ $r->id }}" {{ old('reason_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group" style="grid-column:span 2;">
          <label class="form-label">Additional Notes</label>
          <textarea name="reason_notes" rows="2" class="form-input" style="resize:vertical;"
            placeholder="Any special instructions, patient requests, or intake notes...">{{ old('reason_notes') }}</textarea>
        </div>
      </div>
    </div>

  </div>

  {{-- RIGHT: Assignment panel --}}
  <div style="display:flex;flex-direction:column;gap:18px;">

    {{-- BRANCH --}}
    <div class="card">
      <div class="card-title" style="margin-bottom:12px;">Branch</div>
      @if($branches->count() === 1)
        <input type="hidden" name="branch_id" value="{{ $branches->first()->id }}">
        <div style="display:flex;align-items:center;gap:8px;padding:8px;background:var(--bg-tertiary);border-radius:var(--radius-sm);">
          <div class="status-dot active"></div>
          <span style="font-size:.85rem;font-weight:500;">{{ $branches->first()->name }}</span>
        </div>
      @else
        <select name="branch_id" class="form-input" required>
          <option value="">-- Select Branch --</option>
          @foreach($branches as $b)
          <option value="{{ $b->id }}" {{ old('branch_id', $branchId) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
          @endforeach
        </select>
      @endif
    </div>

    {{-- STAFF ASSIGNMENT --}}
    <div class="card">
      <div class="card-title" style="margin-bottom:12px;">Assign Technician</div>
      <select name="assigned_staff_id" class="form-input">
        <option value="">-- Assign Later --</option>
        @foreach($staff as $s)
        <option value="{{ $s->id }}" {{ old('assigned_staff_id') == $s->id ? 'selected' : '' }}>
          {{ $s->first_name }} {{ $s->last_name }}
          <span style="color:var(--text-tertiary);">({{ ucfirst($s->employee_type) }})</span>
        </option>
        @endforeach
      </select>
      <p style="font-size:.75rem;color:var(--text-tertiary);margin-top:6px;">Can be assigned from the Kanban board after arrival.</p>
    </div>

    {{-- BOOKING SUMMARY --}}
    <div class="card" style="background:var(--bg-tertiary);border:none;">
      <div class="card-title" style="margin-bottom:10px;">Booking Summary</div>
      <div style="font-size:.82rem;color:var(--text-secondary);display:flex;flex-direction:column;gap:6px;">
        <div style="display:flex;justify-content:space-between;">
          <span>Status after save</span>
          <span style="font-weight:500;color:var(--accent);">Scheduled</span>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span>Confirmation needed</span>
          <span style="font-weight:500;">Yes</span>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span>Booked by</span>
          <span style="font-weight:500;">{{ Auth::user()->first_name }}</span>
        </div>
      </div>
    </div>

    {{-- SUBMIT --}}
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Confirm Booking
    </button>

    <a href="{{ route('front-desk') }}" class="btn btn-secondary" style="width:100%;justify-content:center;">Cancel</a>

  </div>

</div>
</form>

@push('scripts')
<script>
// Patient search autocomplete
const searchInput = document.getElementById('patient-search-input');
const resultsDiv  = document.getElementById('patient-results');
const patientIdInput = document.getElementById('patient_id');
let searchTimeout;

if (searchInput) {
  searchInput.addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const q = this.value.trim();

    if (q.length < 2) {
      resultsDiv.style.display = 'none';
      resultsDiv.innerHTML = '';
      return;
    }

    searchTimeout = setTimeout(() => {
      fetch(`{{ route('patients.search') }}?q=${encodeURIComponent(q)}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.json())
      .then(patients => {
        if (patients.length === 0) {
          resultsDiv.innerHTML = '<div style="padding:12px 16px;font-size:.83rem;color:var(--text-tertiary);">No patients found. Use "Register New Patient" to create one.</div>';
        } else {
          resultsDiv.innerHTML = patients.map(p => `
            <div onclick="selectPatient(${p.id}, '${p.full_name.replace(/'/g,"\\'")}', '${p.patient_code}', '${p.phone}')"
              style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--border-light);display:flex;align-items:center;gap:10px;"
              onmouseover="this.style.background='var(--bg-tertiary)'" onmouseout="this.style.background=''">
              <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.7rem;font-weight:600;flex-shrink:0;">
                ${p.full_name.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase()}
              </div>
              <div>
                <div style="font-weight:600;font-size:.85rem;">${p.full_name}</div>
                <div style="font-size:.74rem;color:var(--text-tertiary);">${p.patient_code} &bull; ${p.phone}</div>
              </div>
              <span style="margin-left:auto;font-size:.7rem;padding:2px 8px;border-radius:4px;background:${p.status === 'active' ? 'var(--success-light)' : 'var(--bg-tertiary)'}; color:${p.status === 'active' ? 'var(--success)' : 'var(--text-tertiary)'};">${p.status}</span>
            </div>`).join('');
        }
        resultsDiv.style.display = 'block';
      });
    }, 300);
  });

  // Close on click outside
  document.addEventListener('click', (e) => {
    if (!e.target.closest('#patient-search-wrap')) {
      resultsDiv.style.display = 'none';
    }
  });
}

function selectPatient(id, name, code, phone) {
  patientIdInput.value = id;

  // Show selected panel
  document.getElementById('patient-name').textContent = name;
  document.getElementById('patient-meta').textContent = `${code} • ${phone}`;
  document.getElementById('patient-avatar').textContent = name.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase();

  document.getElementById('patient-selected').style.display = 'flex';
  document.getElementById('patient-search-wrap').style.display = 'none';
  resultsDiv.style.display = 'none';
  searchInput.value = '';
}

function clearPatient() {
  patientIdInput.value = '';
  document.getElementById('patient-selected').style.display = 'none';
  document.getElementById('patient-search-wrap').style.display = 'block';
}

// Auto-fill duration from service selection
function fillDuration() {
  const sel = document.getElementById('service_id');
  const opt = sel.options[sel.selectedIndex];
  const dur = opt.dataset.duration;
  if (dur) {
    document.getElementById('duration_minutes').value = dur;
  }
}
</script>
@endpush
@endsection
