@extends('layouts.app')

@section('title', 'Edit ' . $patient->full_name . ' - MedFlow CRM')
@section('breadcrumb', 'Patients / ' . $patient->full_name . ' / Edit')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ __('Edit Patient') }}</h1>
    <p class="page-subtitle">
      <span style="font-family:monospace;color:var(--accent);">{{ $patient->patient_code }}</span>
      &bull; {{ $patient->full_name }}
    </p>
  </div>
  <a href="{{ route('patients.show', $patient) }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Profile
  </a>
</div>

@if($errors->any())
<div style="background:var(--danger-light);border:1px solid #fca5a5;border-radius:var(--radius-md);padding:12px 16px;margin-bottom:18px;color:#991b1b;">
  <strong>Please fix the following errors:</strong>
  <ul style="margin-top:6px;padding-left:18px;">
    @foreach($errors->all() as $error)
    <li style="font-size:.84rem;">{{ $error }}</li>
    @endforeach
  </ul>
</div>
@endif

<form method="POST" action="{{ route('patients.update', $patient) }}">
@csrf @method('PUT')

{{-- PERSONAL INFO --}}
<div class="card animate-in" style="animation-delay:.05s;margin-bottom:18px;">
  <div class="card-header">
    <div class="card-title">{{ __('Personal Information') }}</div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;padding-top:4px;">

    <div class="form-group">
      <label class="form-label">First Name <span style="color:var(--danger)">*</span></label>
      <input type="text" name="first_name" class="form-input" value="{{ old('first_name', $patient->first_name) }}" required>
    </div>

    <div class="form-group">
      <label class="form-label">Last Name <span style="color:var(--danger)">*</span></label>
      <input type="text" name="last_name" class="form-input" value="{{ old('last_name', $patient->last_name) }}" required>
    </div>

    <div class="form-group">
      <label class="form-label">Phone <span style="color:var(--danger)">*</span></label>
      <input type="text" name="phone" class="form-input" value="{{ old('phone', $patient->phone) }}" required>
    </div>

    <div class="form-group">
      <label class="form-label">Alternate Phone</label>
      <input type="text" name="phone_alt" class="form-input" value="{{ old('phone_alt', $patient->phone_alt) }}">
    </div>

    <div class="form-group">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-input" value="{{ old('email', $patient->email) }}">
    </div>

    <div class="form-group">
      <label class="form-label">{{ __('Date of Birth') }}</label>
      <input type="date" name="date_of_birth" class="form-input" value="{{ old('date_of_birth', $patient->date_of_birth?->format('Y-m-d')) }}">
    </div>

    <div class="form-group">
      <label class="form-label">{{ __('Gender') }}</label>
      <select name="gender" class="form-input">
        <option value="">-- Select --</option>
        @foreach(['male','female','other'] as $g)
        <option value="{{ $g }}" {{ old('gender', $patient->gender) === $g ? 'selected' : '' }}>{{ ucfirst($g) }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Nationality</label>
      <input type="text" name="nationality" class="form-input" value="{{ old('nationality', $patient->nationality) }}">
    </div>

    <div class="form-group">
      <label class="form-label">ID / Passport Number</label>
      <input type="text" name="id_number" class="form-input" value="{{ old('id_number', $patient->id_number) }}">
    </div>

    <div class="form-group" style="grid-column:span 2;">
      <label class="form-label">{{ __('Address') }}</label>
      <input type="text" name="address" class="form-input" value="{{ old('address', $patient->address) }}">
    </div>

    <div class="form-group">
      <label class="form-label">{{ __('City') }}</label>
      <input type="text" name="city" class="form-input" value="{{ old('city', $patient->city) }}">
    </div>

  </div>
</div>

{{-- EMERGENCY CONTACT --}}
<div class="card animate-in" style="animation-delay:.08s;margin-bottom:18px;">
  <div class="card-header">
    <div class="card-title">Emergency Contact</div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;padding-top:4px;">
    <div class="form-group">
      <label class="form-label">Contact Name</label>
      <input type="text" name="emergency_contact_name" class="form-input" value="{{ old('emergency_contact_name', $patient->emergency_contact_name) }}">
    </div>
    <div class="form-group">
      <label class="form-label">Contact Phone</label>
      <input type="text" name="emergency_contact_phone" class="form-input" value="{{ old('emergency_contact_phone', $patient->emergency_contact_phone) }}">
    </div>
    <div class="form-group">
      <label class="form-label">Relationship</label>
      <input type="text" name="emergency_contact_relation" class="form-input" value="{{ old('emergency_contact_relation', $patient->emergency_contact_relation) }}">
    </div>
  </div>
</div>

{{-- CLINIC DETAILS --}}
<div class="card animate-in" style="animation-delay:.10s;margin-bottom:18px;">
  <div class="card-header">
    <div class="card-title">Clinic Details</div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;padding-top:4px;">

    <div class="form-group">
      <label class="form-label">Branch <span style="color:var(--danger)">*</span></label>
      <select name="branch_id" class="form-input" required>
        <option value="">{{ __('-- Select Branch --') }}</option>
        @foreach($branches as $b)
        <option value="{{ $b->id }}" {{ old('branch_id', $patient->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Assigned Staff</label>
      <select name="assigned_staff_id" class="form-input">
        <option value="">-- None --</option>
        @foreach($staff as $s)
        <option value="{{ $s->id }}" {{ old('assigned_staff_id', $patient->assigned_staff_id) == $s->id ? 'selected' : '' }}>
          {{ $s->first_name }} {{ $s->last_name }} ({{ ucfirst($s->employee_type) }})
        </option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Status <span style="color:var(--danger)">*</span></label>
      <select name="status" class="form-input" required>
        @foreach(['active','inactive','vip','blacklisted'] as $st)
        <option value="{{ $st }}" {{ old('status', $patient->status) === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Lead Source</label>
      <select name="source" class="form-input">
        <option value="">-- Select --</option>
        @foreach(['instagram','facebook','tiktok','google','referral','walk_in','website','whatsapp','other'] as $src)
        <option value="{{ $src }}" {{ old('source', $patient->source) === $src ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$src)) }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Referral Detail</label>
      <input type="text" name="referral_source" class="form-input" value="{{ old('referral_source', $patient->referral_source) }}">
    </div>

    <div class="form-group" style="display:flex;align-items:center;gap:10px;padding-top:26px;">
      <input type="checkbox" name="consent_given" value="1" id="consent"
        {{ old('consent_given', $patient->consent_given) ? 'checked' : '' }} style="width:16px;height:16px;">
      <label for="consent" class="form-label" style="margin:0;cursor:pointer;">Consent Given</label>
    </div>

  </div>
  <div class="form-group" style="margin-top:10px;">
    <label class="form-label">Internal Notes</label>
    <textarea name="internal_notes" rows="2" class="form-input" style="resize:vertical;">{{ old('internal_notes', $patient->internal_notes) }}</textarea>
  </div>
</div>

{{-- MEDICAL & CLINICAL --}}
<div class="card animate-in" style="animation-delay:.12s;margin-bottom:18px;">
  <div class="card-header">
    <div class="card-title">Medical &amp; Clinical Info</div>
  </div>
  @php $med = $patient->medicalInfo; @endphp
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;padding-top:4px;">

    <div class="form-group">
      <label class="form-label">Height (cm)</label>
      <input type="number" name="height_cm" class="form-input" step="0.1" value="{{ old('height_cm', $med?->height_cm) }}">
    </div>

    <div class="form-group">
      <label class="form-label">Weight (kg)</label>
      <input type="number" name="weight_kg" class="form-input" step="0.1" value="{{ old('weight_kg', $med?->weight_kg) }}">
    </div>

    <div class="form-group">
      <label class="form-label">Skin Type (Fitzpatrick I–VI)</label>
      <select name="skin_type" class="form-input">
        <option value="">-- Select --</option>
        @foreach(['I','II','III','IV','V','VI'] as $st)
        <option value="{{ $st }}" {{ old('skin_type', $med?->skin_type) === $st ? 'selected' : '' }}>Type {{ $st }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Skin Tone</label>
      <select name="skin_tone" class="form-input">
        <option value="">-- Select --</option>
        @foreach(['fair','medium','olive','dark','very_dark'] as $tone)
        <option value="{{ $tone }}" {{ old('skin_tone', $med?->skin_tone) === $tone ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$tone)) }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group" style="grid-column:span 2;">
      <label class="form-label">Known Allergies</label>
      <textarea name="allergies" rows="2" class="form-input" style="resize:vertical;">{{ old('allergies', $med?->allergies) }}</textarea>
    </div>

    <div class="form-group" style="grid-column:span 2;">
      <label class="form-label">Contraindications</label>
      <textarea name="contraindications" rows="2" class="form-input" style="resize:vertical;">{{ old('contraindications', $med?->contraindications) }}</textarea>
    </div>

    <div class="form-group" style="grid-column:span 2;">
      <label class="form-label">Current Medications</label>
      <textarea name="current_medications" rows="2" class="form-input" style="resize:vertical;">{{ old('current_medications', $med?->current_medications) }}</textarea>
    </div>

    <div class="form-group" style="grid-column:span 2;">
      <label class="form-label">Medical History / Other Conditions</label>
      <textarea name="medical_history" rows="2" class="form-input" style="resize:vertical;">{{ old('medical_history', $med?->medical_history) }}</textarea>
    </div>

    <div class="form-group" style="grid-column:span 2;">
      <label class="form-label">Other Conditions</label>
      <textarea name="other_conditions" rows="2" class="form-input" style="resize:vertical;">{{ old('other_conditions', $med?->other_conditions) }}</textarea>
    </div>

  </div>

  {{-- Safety Flags --}}
  <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:20px;">
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.85rem;">
      <input type="checkbox" name="is_pregnant" value="1" {{ old('is_pregnant', $med?->is_pregnant) ? 'checked' : '' }} style="width:16px;height:16px;">
      <span style="color:var(--danger);font-weight:500;">Pregnant</span>
    </label>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.85rem;">
      <input type="checkbox" name="has_pacemaker" value="1" {{ old('has_pacemaker', $med?->has_pacemaker) ? 'checked' : '' }} style="width:16px;height:16px;">
      <span style="color:var(--danger);font-weight:500;">Pacemaker</span>
    </label>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.85rem;">
      <input type="checkbox" name="has_metal_implants" value="1" {{ old('has_metal_implants', $med?->has_metal_implants) ? 'checked' : '' }} style="width:16px;height:16px;">
      <span style="color:var(--warning);font-weight:500;">Metal Implants</span>
    </label>
  </div>

  {{-- Insurance --}}
  <div style="margin-top:18px;border-top:1px solid var(--border);padding-top:16px;">
    <div style="font-size:.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px;">Insurance</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;">
      <div class="form-group">
        <label class="form-label">Provider</label>
        <input type="text" name="insurance_provider" class="form-input" value="{{ old('insurance_provider', $med?->insurance_provider) }}">
      </div>
      <div class="form-group">
        <label class="form-label">Policy Number</label>
        <input type="text" name="insurance_number" class="form-input" value="{{ old('insurance_number', $med?->insurance_number) }}">
      </div>
      <div class="form-group">
        <label class="form-label">Plan Name</label>
        <input type="text" name="insurance_plan" class="form-input" value="{{ old('insurance_plan', $med?->insurance_plan) }}">
      </div>
      <div class="form-group">
        <label class="form-label">Expiry Date</label>
        <input type="date" name="insurance_expiry" class="form-input" value="{{ old('insurance_expiry', $med?->insurance_expiry?->format('Y-m-d')) }}">
      </div>
    </div>
  </div>
</div>

{{-- DANGER ZONE --}}
<div class="card animate-in" style="animation-delay:.14s;margin-bottom:18px;border:1px solid #fca5a5;">
  <div class="card-header">
    <div class="card-title" style="color:var(--danger);">Danger Zone</div>
  </div>
  <div style="display:flex;align-items:center;justify-content:space-between;">
    <div>
      <div style="font-size:.85rem;font-weight:500;">Archive Patient</div>
      <div style="font-size:.78rem;color:var(--text-tertiary);">Removes this patient from active lists. Can be restored later.</div>
    </div>
    <form method="POST" action="{{ route('patients.destroy', $patient) }}" onsubmit="return confirm('Archive {{ $patient->full_name }}? This can be undone.')">
      @csrf @method('DELETE')
      <button type="submit" class="btn" style="background:var(--danger);color:#fff;border-color:var(--danger);">Archive Patient</button>
    </form>
  </div>
</div>

{{-- SUBMIT --}}
<div class="animate-in" style="animation-delay:.16s;display:flex;justify-content:flex-end;gap:10px;margin-bottom:32px;">
  <a href="{{ route('patients.show', $patient) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
  <button type="submit" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
    Save Changes
  </button>
</div>

</form>
@endsection
