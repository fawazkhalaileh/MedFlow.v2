@extends('layouts.app')

@section('title', __('Edit Branch') . ' - MedFlow CRM')
@section('breadcrumb', __('Branches') . ' / ' . __('Edit'))

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ $branch->name }}</h1>
    <p class="page-subtitle">{{ __('Edit branch details') }} &mdash; <span style="font-family:monospace;font-size:.85em;">{{ $branch->code }}</span></p>
  </div>
  <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    {{ __('Back to Branches') }}
  </a>
</div>

<div class="grid-2-1 animate-in" style="animation-delay:.05s;align-items:start;">

  {{-- Edit Form --}}
  <form method="POST" action="{{ route('admin.branches.update', $branch) }}">
    @csrf @method('PUT')

    <div class="card" style="margin-bottom:18px;">
      <div class="form-section">
        <div class="form-section-title">{{ __('Branch Information') }}</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">{{ __('Branch Name') }} <span class="required">*</span></label>
            <input type="text" name="name" class="form-input {{ $errors->has('name') ? 'error' : '' }}" value="{{ old('name', $branch->name) }}" required>
            @error('name')<div class="form-error">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label class="form-label">{{ __('Branch Code') }} <span class="required">*</span></label>
            <input type="text" name="code" class="form-input {{ $errors->has('code') ? 'error' : '' }}" value="{{ old('code', $branch->code) }}" required>
            @error('code')<div class="form-error">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">{{ __('Status') }}</label>
            <select name="status" class="form-select">
              <option value="active"      {{ old('status',$branch->status) === 'active'      ? 'selected' : '' }}>{{ __('Active') }}</option>
              <option value="inactive"    {{ old('status',$branch->status) === 'inactive'    ? 'selected' : '' }}>{{ __('Inactive') }}</option>
              <option value="coming_soon" {{ old('status',$branch->status) === 'coming_soon' ? 'selected' : '' }}>{{ __('Coming Soon') }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">{{ __('Branch Manager') }}</label>
            <select name="manager_id" class="form-select">
              <option value="">{{ __('-- No manager --') }}</option>
              @foreach($managers as $mgr)
              <option value="{{ $mgr->id }}" {{ old('manager_id',$branch->manager_id) == $mgr->id ? 'selected' : '' }}>
                {{ $mgr->first_name }} {{ $mgr->last_name }}
              </option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      <div class="form-section">
        <div class="form-section-title">{{ __('Contact Details') }}</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">{{ __('Phone') }}</label>
            <input type="text" name="phone" class="form-input" value="{{ old('phone', $branch->phone) }}">
          </div>
          <div class="form-group">
            <label class="form-label">{{ __('Email') }}</label>
            <input type="email" name="email" class="form-input" value="{{ old('email', $branch->email) }}">
          </div>
        </div>
      </div>

      <div class="form-section">
        <div class="form-section-title">{{ __('Location') }}</div>
        <div class="form-group">
          <label class="form-label">{{ __('Address') }}</label>
          <input type="text" name="address" class="form-input" value="{{ old('address', $branch->address) }}">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">{{ __('City') }}</label>
            <input type="text" name="city" class="form-input" value="{{ old('city', $branch->city) }}">
          </div>
          <div class="form-group">
            <label class="form-label">{{ __('Country') }}</label>
            <input type="text" name="country" class="form-input" value="{{ old('country', $branch->country) }}">
          </div>
        </div>
      </div>

      <div class="form-section" style="border-bottom:none;margin-bottom:0;padding-bottom:0;">
        <div class="form-section-title">{{ __('Notes') }}</div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">{{ __('Internal Notes') }}</label>
          <textarea name="notes" class="form-textarea">{{ old('notes', $branch->notes) }}</textarea>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:10px;">
      <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
      <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
    </div>
  </form>

  {{-- Sidebar Info --}}
  <div style="display:flex;flex-direction:column;gap:18px;">

    {{-- Stats --}}
    <div class="card">
      <div class="card-title" style="margin-bottom:14px;">{{ __('Branch Stats') }}</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div style="text-align:center;padding:12px;background:var(--bg-tertiary);border-radius:var(--radius-md);">
          <div style="font-size:1.5rem;font-weight:700;">{{ $branch->patients_count ?? 0 }}</div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">{{ __('Patients') }}</div>
        </div>
        <div style="text-align:center;padding:12px;background:var(--bg-tertiary);border-radius:var(--radius-md);">
          <div style="font-size:1.5rem;font-weight:700;">{{ $branch->staff_count ?? 0 }}</div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">{{ __('Staff') }}</div>
        </div>
        <div style="text-align:center;padding:12px;background:var(--bg-tertiary);border-radius:var(--radius-md);">
          <div style="font-size:1.5rem;font-weight:700;">{{ $branch->rooms->count() }}</div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">{{ __('Rooms') }}</div>
        </div>
        <div style="text-align:center;padding:12px;background:var(--bg-tertiary);border-radius:var(--radius-md);">
          <div style="font-size:1.5rem;font-weight:700;">{{ $branch->appointments_count ?? 0 }}</div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">{{ __('Appts') }}</div>
        </div>
      </div>
    </div>

    {{-- Staff List --}}
    @if($branch->staff->isNotEmpty())
    <div class="card">
      <div class="card-title" style="margin-bottom:14px;">{{ __('Staff at This Branch') }}</div>
      @foreach($branch->staff->take(6) as $member)
      <div style="display:flex;align-items:center;gap:9px;padding:7px 0;border-bottom:1px solid var(--border-light);">
        <div class="avatar avatar-sm" style="background:linear-gradient(135deg,#2563eb,#7c3aed);">
          {{ strtoupper(substr($member->first_name ?? $member->name,0,2)) }}
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:.84rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $member->first_name }} {{ $member->last_name }}</div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">{{ __(\Illuminate\Support\Str::headline($member->employee_type ?? '')) }}</div>
        </div>
        <span class="badge {{ $member->employment_status === 'active' ? 'badge-green' : 'badge-gray' }} badge" style="font-size:.68rem;">{{ __(\Illuminate\Support\Str::headline($member->employment_status)) }}</span>
      </div>
      @endforeach
      @if($branch->staff->count() > 6)
      <div style="text-align:center;padding:10px 0;color:var(--text-tertiary);font-size:.8rem;">+{{ $branch->staff->count() - 6 }} {{ __('more staff') }}</div>
      @endif
    </div>
    @endif

    {{-- Rooms --}}
    @if($branch->rooms->isNotEmpty())
    <div class="card">
      <div class="card-title" style="margin-bottom:14px;">{{ __('Treatment Rooms') }}</div>
      @foreach($branch->rooms as $room)
      <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-light);">
        <div style="font-size:.84rem;font-weight:500;">{{ $room->name }}</div>
        <span class="badge {{ $room->status === 'available' ? 'badge-green' : 'badge-gray' }}">{{ __(\Illuminate\Support\Str::headline($room->status)) }}</span>
      </div>
      @endforeach
    </div>
    @endif

    {{-- Danger Zone --}}
    <div class="card" style="border-color:var(--danger-light);">
      <div class="card-title" style="margin-bottom:10px;color:var(--danger);">{{ __('Danger Zone') }}</div>
      <p style="font-size:.82rem;color:var(--text-secondary);margin-bottom:12px;">{{ __('Deleting this branch will soft-delete all its records. This action can be reversed by an admin.') }}</p>
      <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete :name?', ['name' => $branch->name]) }}')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">{{ __('Delete Branch') }}</button>
      </form>
    </div>

  </div>
</div>
@endsection
