@extends('layouts.app')

@section('title', __('Edit Package').' - MedFlow CRM')
@section('breadcrumb', __('Edit Package'))

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ __('Edit Package Master') }}</h1>
    <p class="page-subtitle">{{ __('Pricing is locked after creation. You can update package details and lifecycle state only.') }}</p>
  </div>
</div>

@if($errors->any())
<div class="alert alert-danger">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <div>
    <div style="font-weight:600;margin-bottom:4px;">{{ __('Please fix the package form errors below.') }}</div>
    <ul style="margin:0;padding-left:18px;">
      @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
</div>
@endif

<div class="grid-2-1 animate-in" style="align-items:start;">
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">{{ $package->name }}</div>
        <div class="card-subtitle">{{ $package->service?->name }} &middot; {{ $package->branch?->name }}</div>
      </div>
    </div>

    <form method="POST" action="{{ route('packages.update', $package) }}" style="display:grid;gap:12px;">
      @csrf
      @method('PUT')
      <input type="text" name="name" class="form-input" value="{{ old('name', $package->name) }}" required>
      <div style="display:grid;gap:6px;">
        <label for="edit-package-expiry-date" style="font-size:.78rem;font-weight:600;color:var(--text-secondary);">{{ __('Expiry date') }}</label>
        <input id="edit-package-expiry-date" type="date" name="expiry_date" class="form-input" value="{{ old('expiry_date', $package->expiry_date?->toDateString()) }}">
      </div>
      <textarea name="notes" class="form-textarea" placeholder="{{ __('Package notes') }}">{{ old('notes', $package->notes) }}</textarea>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary btn-sm">{{ __('Save Package') }}</button>
        <a href="{{ route('packages.index') }}" class="btn btn-secondary btn-sm">{{ __('Back to Packages') }}</a>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">{{ __('Locked Pricing') }}</div>
        <div class="card-subtitle">{{ __('These values are fixed on the reusable package master after creation.') }}</div>
      </div>
    </div>

    <div style="display:grid;gap:10px;">
      <input type="text" class="form-input" value="{{ __('Original price') }}: {{ $package->original_price }}" disabled>
      <input type="text" class="form-input" value="{{ __('Discount type') }}: {{ $package->discount_type ? __(ucfirst($package->discount_type)) : __('No discount') }}" disabled>
      <input type="text" class="form-input" value="{{ __('Discount value') }}: {{ $package->discount_value ?: '0.00' }}" disabled>
      <input type="text" class="form-input" value="{{ __('Final price') }}: {{ $package->final_price }}" disabled>
      <input type="text" class="form-input" value="{{ __('Sessions per purchase') }}: {{ $package->sessions_purchased }}" disabled>
      <input type="text" class="form-input" value="{{ __('Status') }}: {{ __(ucfirst($package->status)) }}" disabled>
    </div>
  </div>
</div>
@endsection

