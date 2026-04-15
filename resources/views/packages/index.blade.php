@extends('layouts.app')

@section('title', __('Packages').' - MedFlow CRM')
@section('breadcrumb', __('Packages'))

@section('content')
@php
  use App\Models\Package;
  use App\Models\PatientPackage;
@endphp

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ __('Packages') }}</h1>
    <p class="page-subtitle">{{ __('Reusable package masters plus patient purchases that deduct sessions only on completed appointments.') }}</p>
  </div>
  <div class="header-actions">
    <span class="badge badge-blue">{{ $packages->count() }} {{ __('Master Packages') }}</span>
    <span class="badge badge-green">{{ $patientPackages->count() }} {{ __('Patient Purchases') }}</span>
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

@if(!$scopedBranchId)
<form method="GET" action="{{ route('packages.index') }}" class="filter-bar animate-in" style="margin-bottom:18px;">
  <select name="branch" class="filter-select" style="min-width:220px;">
    <option value="">{{ __('All branches') }}</option>
    @foreach($branches as $branch)
    <option value="{{ $branch->id }}" {{ (string) $selectedBranchId === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn btn-secondary btn-sm">{{ __('Filter') }}</button>
  @if($selectedBranchId)
  <a href="{{ route('packages.index') }}" class="btn btn-ghost btn-sm">{{ __('Clear') }}</a>
  @endif
</form>
@endif

<div class="grid-2-1 animate-in" style="align-items:start;">
  <div style="display:flex;flex-direction:column;gap:18px;">
    <div class="card" style="padding:0;">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div>
          <div class="card-title">{{ __('Package Catalog') }}</div>
          <div class="card-subtitle">{{ __('Managers define reusable bundle masters per branch and service. Pricing locks after creation.') }}</div>
        </div>
        <span class="badge badge-gray">{{ $packages->count() }}</span>
      </div>

      @if($packages->isEmpty())
      <div class="empty-state">
        <h3>{{ __('No package masters yet') }}</h3>
        <p>{{ __('Create a reusable package first, then sell it to patients as patient-specific purchases.') }}</p>
      </div>
      @else
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>{{ __('Branch') }}</th>
              <th>{{ __('Package') }}</th>
              <th>{{ __('Service') }}</th>
              <th>{{ __('Sessions') }}</th>
              <th>{{ __('Price') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Expiry') }}</th>
              <th>{{ __('Purchases') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($packages as $package)
            <tr>
              <td>{{ $package->branch?->name }}</td>
              <td>
                <div style="font-weight:600;">{{ $package->name }}</div>
                <div style="font-size:.73rem;color:var(--text-tertiary);">{{ __('Created by') }} {{ $package->createdBy?->full_name }}</div>
              </td>
              <td>{{ $package->service?->name }}</td>
              <td>{{ $package->sessions_purchased }}</td>
              <td>
                <div style="font-weight:600;">{{ $package->final_price }}</div>
                <div style="font-size:.73rem;color:var(--text-tertiary);">{{ __('Original') }} {{ $package->original_price }}</div>
                @if($package->discount_type)
                <div style="font-size:.73rem;color:var(--text-tertiary);">{{ __(ucfirst($package->discount_type)) }} {{ __('discount') }} {{ $package->discount_value }}</div>
                @endif
              </td>
              <td>
                <span class="badge {{ match($package->status) {
                  Package::STATUS_ACTIVE => 'badge-green',
                  Package::STATUS_FROZEN => 'badge-yellow',
                  Package::STATUS_EXPIRED => 'badge-red',
                  default => 'badge-blue',
                } }}">{{ ucfirst($package->status) }}</span>
              </td>
              <td>{{ $package->expiry_date?->format('d M Y') ?: __('No expiry') }}</td>
              <td>{{ $package->patientPackages->count() }}</td>
              <td>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                  <a href="{{ route('packages.edit', $package) }}" class="btn btn-secondary btn-sm">{{ __('Edit') }}</a>
                  @if($package->status === Package::STATUS_ACTIVE)
                  <form method="POST" action="{{ route('packages.freeze', $package) }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">{{ __('Freeze') }}</button>
                  </form>
                  @elseif($package->status === Package::STATUS_FROZEN)
                  <form method="POST" action="{{ route('packages.unfreeze', $package) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Unfreeze') }}</button>
                  </form>
                  @endif
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>

    <div class="card" style="padding:0;">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div>
          <div class="card-title">{{ __('Patient Package Purchases') }}</div>
          <div class="card-subtitle">{{ __('Each purchase tracks sessions used per patient. Booking may attach it, but deduction happens only on completion.') }}</div>
        </div>
        <span class="badge badge-gray">{{ $patientPackages->count() }}</span>
      </div>

      @if($patientPackages->isEmpty())
      <div class="empty-state">
        <h3>{{ __('No patient package purchases yet') }}</h3>
        <p>{{ __('Sell a package master to a patient to begin tracking patient-specific usage.') }}</p>
      </div>
      @else
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>{{ __('Patient') }}</th>
              <th>{{ __('Package') }}</th>
              <th>{{ __('Sessions') }}</th>
              <th>{{ __('Price') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Expiry') }}</th>
              <th>{{ __('Recent Usage') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($patientPackages as $patientPackage)
            <tr>
              <td>
                <div style="font-weight:600;">{{ $patientPackage->patient?->full_name }}</div>
                <div style="font-size:.73rem;color:var(--text-tertiary);">{{ $patientPackage->branch?->name }}</div>
              </td>
              <td>
                <div style="font-weight:600;">{{ $patientPackage->package?->name }}</div>
                <div style="font-size:.73rem;color:var(--text-tertiary);">{{ $patientPackage->package?->service?->name }}</div>
              </td>
              <td>
                <div style="font-weight:600;">{{ $patientPackage->sessions_used }}/{{ $patientPackage->sessions_purchased }}</div>
                <div style="font-size:.73rem;color:var(--text-tertiary);">{{ $patientPackage->remaining_sessions }} {{ __('remaining') }}</div>
              </td>
              <td>{{ $patientPackage->final_price }}</td>
              <td>
                <span class="badge {{ match($patientPackage->status) {
                  PatientPackage::STATUS_ACTIVE => 'badge-green',
                  PatientPackage::STATUS_FROZEN => 'badge-yellow',
                  PatientPackage::STATUS_EXPIRED => 'badge-red',
                  default => 'badge-blue',
                } }}">{{ ucfirst($patientPackage->status) }}</span>
              </td>
              <td>{{ $patientPackage->expiry_date?->format('d M Y') ?: __('No expiry') }}</td>
              <td>
                @forelse($patientPackage->usages->sortByDesc('used_at')->take(2) as $usage)
                <div style="font-size:.74rem;color:var(--text-secondary);margin-bottom:4px;">
                  {{ $usage->used_at?->format('d M Y') }}
                  @if($usage->appointment)
                  &middot; {{ __('Appointment') }} #{{ $usage->appointment->id }}
                  @endif
                </div>
                @empty
                <span style="font-size:.74rem;color:var(--text-tertiary);">{{ __('No usage yet') }}</span>
                @endforelse
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px;">
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ __('Create Package Master') }}</div>
          <div class="card-subtitle">{{ __('Reusable branch-scoped package definition. No patient is selected here.') }}</div>
        </div>
      </div>

      <form method="POST" action="{{ route('packages.store') }}" style="display:grid;gap:10px;">
        @csrf
        @if(!$scopedBranchId)
        <select name="branch_id" class="form-select" required>
          <option value="">{{ __('Select branch') }}</option>
          @foreach($branches as $branch)
          <option value="{{ $branch->id }}" {{ old('branch_id', $selectedBranchId) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
          @endforeach
        </select>
        @endif
        <select name="service_id" class="form-select" required>
          <option value="">{{ __('Select service') }}</option>
          @foreach($services as $service)
          <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
          @endforeach
        </select>
        <input type="text" name="name" class="form-input" placeholder="{{ __('Package name') }}" value="{{ old('name') }}" required>
        <input type="number" min="1" step="1" name="sessions_purchased" class="form-input" placeholder="{{ __('Sessions purchased') }}" value="{{ old('sessions_purchased') }}" required>
        <input type="number" min="0" step="0.01" name="original_price" class="form-input" placeholder="{{ __('Original price') }}" value="{{ old('original_price') }}" required>
        <select name="discount_type" class="form-select">
          <option value="">{{ __('No discount') }}</option>
          <option value="percentage" {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>{{ __('Percentage') }}</option>
          <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>{{ __('Fixed amount') }}</option>
        </select>
        <input type="number" min="0" step="0.01" name="discount_value" class="form-input" placeholder="{{ __('Discount value') }}" value="{{ old('discount_value') }}">
        <div style="display:grid;gap:6px;">
          <label for="package-expiry-date" style="font-size:.78rem;font-weight:600;color:var(--text-secondary);">{{ __('Expiry date') }}</label>
          <input id="package-expiry-date" type="date" name="expiry_date" class="form-input" value="{{ old('expiry_date') }}">
        </div>
        <textarea name="notes" class="form-textarea" placeholder="{{ __('Package notes') }}">{{ old('notes') }}</textarea>
        <button type="submit" class="btn btn-primary btn-sm" style="justify-content:center;">{{ __('Create Package') }}</button>
      </form>
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ __('Sell Package to Patient') }}</div>
          <div class="card-subtitle">{{ __('Create a patient-specific purchase from an active package master.') }}</div>
        </div>
      </div>

      <form method="POST" action="{{ route('packages.purchases.store') }}" style="display:grid;gap:10px;">
        @csrf
        <select name="package_id" class="form-select" required>
          <option value="">{{ __('Select package master') }}</option>
          @foreach($packages->where('status', Package::STATUS_ACTIVE) as $package)
          <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>
            {{ $package->name }} &middot; {{ $package->service?->name }} &middot; {{ $package->branch?->name }}
          </option>
          @endforeach
        </select>
        <select name="patient_id" class="form-select" required>
          <option value="">{{ __('Select patient') }}</option>
          @foreach($patients as $patient)
          <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
            {{ $patient->full_name }}{{ !$scopedBranchId ? ' &middot; '.$patient->branch?->name : '' }}
          </option>
          @endforeach
        </select>
        <div style="display:grid;gap:6px;">
          <label for="purchase-expiry-date" style="font-size:.78rem;font-weight:600;color:var(--text-secondary);">{{ __('Purchase expiry override') }}</label>
          <input id="purchase-expiry-date" type="date" name="expiry_date" class="form-input" value="{{ old('expiry_date') }}">
        </div>
        <textarea name="notes" class="form-textarea" placeholder="{{ __('Purchase notes') }}">{{ old('notes') }}</textarea>
        <button type="submit" class="btn btn-primary btn-sm" style="justify-content:center;">{{ __('Create Patient Purchase') }}</button>
      </form>
    </div>
  </div>
</div>
@endsection

