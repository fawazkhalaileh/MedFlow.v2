@extends('layouts.app')

@section('title', __('Add Branch') . ' - MedFlow CRM')
@section('breadcrumb', __('Branches') . ' / ' . __('Add New'))

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ __('Add New Branch') }}</h1>
    <p class="page-subtitle">{{ __('Set up a new clinic location') }}</p>
  </div>
  <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    {{ __('Back to Branches') }}
  </a>
</div>

<div style="max-width:760px;" class="animate-in" style="animation-delay:.05s">
  <form method="POST" action="{{ route('admin.branches.store') }}">
    @csrf

    <div class="card" style="margin-bottom:18px;">
      <div class="form-section">
        <div class="form-section-title">{{ __('Branch Information') }}</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">{{ __('Branch Name') }} <span class="required">*</span></label>
            <input type="text" name="name" class="form-input {{ $errors->has('name') ? 'error' : '' }}" value="{{ old('name') }}" placeholder="{{ __('e.g. Dubai Marina Branch') }}" required>
            @error('name')<div class="form-error">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label class="form-label">{{ __('Branch Code') }} <span class="required">*</span></label>
            <input type="text" name="code" class="form-input {{ $errors->has('code') ? 'error' : '' }}" value="{{ old('code') }}" placeholder="{{ __('e.g. BR-004') }}" required>
            <div class="form-hint">{{ __('Unique identifier for this branch') }}</div>
            @error('code')<div class="form-error">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">{{ __('Status') }} <span class="required">*</span></label>
            <select name="status" class="form-select" required>
              <option value="active"       {{ old('status','active') === 'active'       ? 'selected' : '' }}>{{ __('Active') }}</option>
              <option value="inactive"     {{ old('status') === 'inactive'     ? 'selected' : '' }}>{{ __('Inactive') }}</option>
              <option value="coming_soon"  {{ old('status') === 'coming_soon'  ? 'selected' : '' }}>{{ __('Coming Soon') }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">{{ __('Branch Manager') }}</label>
            <select name="manager_id" class="form-select">
              <option value="">{{ __('-- No manager assigned --') }}</option>
              @foreach($managers as $mgr)
              <option value="{{ $mgr->id }}" {{ old('manager_id') == $mgr->id ? 'selected' : '' }}>
                {{ $mgr->first_name }} {{ $mgr->last_name }} ({{ __(\Illuminate\Support\Str::headline($mgr->employee_type)) }})
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
            <input type="text" name="phone" class="form-input" value="{{ old('phone') }}" placeholder="{{ __('+971 4 XXX XXXX') }}">
          </div>
          <div class="form-group">
            <label class="form-label">{{ __('Email') }}</label>
            <input type="email" name="email" class="form-input" value="{{ old('email') }}" placeholder="{{ __('branch@medflow.ae') }}">
          </div>
        </div>
      </div>

      <div class="form-section">
        <div class="form-section-title">{{ __('Location') }}</div>
        <div class="form-group">
          <label class="form-label">{{ __('Address') }}</label>
          <input type="text" name="address" class="form-input" value="{{ old('address') }}" placeholder="{{ __('Street address') }}">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">{{ __('City') }}</label>
            <input type="text" name="city" class="form-input" value="{{ old('city') }}" placeholder="{{ __('Dubai') }}">
          </div>
          <div class="form-group">
            <label class="form-label">{{ __('Country') }}</label>
            <input type="text" name="country" class="form-input" value="{{ old('country', 'UAE') }}" placeholder="{{ __('UAE') }}">
          </div>
        </div>
      </div>

      <div class="form-section" style="border-bottom:none;margin-bottom:0;padding-bottom:0;">
        <div class="form-section-title">{{ __('Notes') }}</div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">{{ __('Internal Notes') }}</label>
          <textarea name="notes" class="form-textarea" placeholder="{{ __('Any internal notes about this branch...') }}">{{ old('notes') }}</textarea>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:10px;">
      <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        {{ __('Create Branch') }}
      </button>
      <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
    </div>
  </form>
</div>
@endsection
