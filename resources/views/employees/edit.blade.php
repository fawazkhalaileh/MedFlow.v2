@extends('layouts.app')

@section('title', 'Edit Employee - MedFlow CRM')
@section('breadcrumb', 'Employees / Edit')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ $employee->first_name }} {{ $employee->last_name }}</h1>
    <p class="page-subtitle">{{ $employee->employee_id }} &mdash; {{ ucfirst(str_replace('_',' ',$employee->employee_type ?? '')) }}</p>
  </div>
  <a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back
  </a>
</div>

<form method="POST" action="{{ route('admin.employees.update', $employee) }}" class="animate-in" style="animation-delay:.05s">
  @csrf @method('PUT')
  <div class="grid-2-1" style="align-items:start;">

    <div>
      <div class="card" style="margin-bottom:18px;">
        <div class="form-section">
          <div class="form-section-title">{{ __('Personal Information') }}</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">First Name <span class="required">*</span></label>
              <input type="text" name="first_name" class="form-input" value="{{ old('first_name', $employee->first_name) }}" required>
            </div>
            <div class="form-group">
              <label class="form-label">{{ __('Last Name') }}</label>
              <input type="text" name="last_name" class="form-input" value="{{ old('last_name', $employee->last_name) }}">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">{{ __('Gender') }}</label>
              <select name="gender" class="form-select">
                <option value="">-- Select --</option>
                <option value="female" {{ old('gender',$employee->gender) === 'female' ? 'selected' : '' }}>Female</option>
                <option value="male"   {{ old('gender',$employee->gender) === 'male'   ? 'selected' : '' }}>Male</option>
                <option value="other"  {{ old('gender',$employee->gender) === 'other'  ? 'selected' : '' }}>Other</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">{{ __('Date of Birth') }}</label>
              <input type="date" name="date_of_birth" class="form-input" value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}">
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-title">Contact</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Email <span class="required">*</span></label>
              <input type="email" name="email" class="form-input {{ $errors->has('email') ? 'error' : '' }}" value="{{ old('email', $employee->email) }}" required>
              @error('email')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label class="form-label">{{ __('Phone') }}</label>
              <input type="text" name="phone" class="form-input" value="{{ old('phone', $employee->phone) }}">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="password" class="form-input" placeholder="Leave blank to keep current">
            <div class="form-hint">Only fill this if you want to change the password</div>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-title">Employment</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">{{ __('Employee Type') }} <span class="required">*</span></label>
              <select name="employee_type" class="form-select" required>
                @foreach($types as $t)
                <option value="{{ $t }}" {{ old('employee_type',$employee->employee_type) === $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select name="employment_status" class="form-select">
                <option value="active"      {{ old('employment_status',$employee->employment_status) === 'active'      ? 'selected' : '' }}>{{ __('Active') }}</option>
                <option value="inactive"    {{ old('employment_status',$employee->employment_status) === 'inactive'    ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                <option value="on_leave"    {{ old('employment_status',$employee->employment_status) === 'on_leave'    ? 'selected' : '' }}>On Leave</option>
                <option value="terminated"  {{ old('employment_status',$employee->employment_status) === 'terminated'  ? 'selected' : '' }}>Terminated</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Primary Branch</label>
              <select name="primary_branch_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ old('primary_branch_id',$employee->primary_branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Hire Date</label>
              <input type="date" name="hire_date" class="form-input" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}">
            </div>
          </div>
        </div>

        <div class="form-section" style="border-bottom:none;margin-bottom:0;padding-bottom:0;">
          <div class="form-section-title">{{ __('Notes') }}</div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Internal Notes</label>
            <textarea name="employee_notes" class="form-textarea">{{ old('employee_notes', $employee->employee_notes) }}</textarea>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
      </div>
    </div>

    {{-- Profile Panel --}}
    <div style="display:flex;flex-direction:column;gap:18px;">
      <div class="card" style="text-align:center;padding:24px;">
        <div class="avatar avatar-lg" style="margin:0 auto 12px;background:linear-gradient(135deg,#2563eb,#7c3aed);width:60px;height:60px;font-size:1.2rem;">
          {{ strtoupper(substr($employee->first_name ?? $employee->name,0,1).substr($employee->last_name ?? '',0,1)) }}
        </div>
        <div style="font-weight:600;font-size:1rem;">{{ $employee->first_name }} {{ $employee->last_name }}</div>
        <div style="color:var(--text-tertiary);font-size:.8rem;margin:4px 0 10px;">{{ $employee->email }}</div>
        @if($employee->employment_status === 'active')
          <span class="badge badge-green">{{ __('Active') }}</span>
        @elseif($employee->employment_status === 'on_leave')
          <span class="badge badge-yellow">On Leave</span>
        @else
          <span class="badge badge-gray">{{ ucfirst($employee->employment_status) }}</span>
        @endif
      </div>

      <div class="card">
        <div class="card-title" style="margin-bottom:12px;">Account Details</div>
        <div style="font-size:.83rem;display:flex;flex-direction:column;gap:9px;">
          <div style="display:flex;justify-content:space-between;">
            <span style="color:var(--text-secondary);">Employee ID</span>
            <span style="font-family:monospace;font-weight:500;">{{ $employee->employee_id ?? '--' }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span style="color:var(--text-secondary);">Joined</span>
            <span>{{ $employee->hire_date?->format('d M Y') ?? '--' }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span style="color:var(--text-secondary);">Branch</span>
            <span>{{ $employee->primaryBranch?->name ?? 'Unassigned' }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span style="color:var(--text-secondary);">{{ __('Phone') }}</span>
            <span>{{ $employee->phone ?? '--' }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection
