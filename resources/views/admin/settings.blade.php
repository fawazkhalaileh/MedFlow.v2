@extends('layouts.app')
@section('title', 'System Settings - MedFlow CRM')
@section('breadcrumb', 'Admin / Settings')
@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">System Settings</h1>
    <p class="page-subtitle">Company configuration, branding, and defaults</p>
  </div>
  <a href="{{ route('admin.index') }}" class="btn btn-secondary">Back to Admin</a>
</div>

<div class="card animate-in" style="max-width:640px;">
  <div class="card-title" style="margin-bottom:16px;">Company Information</div>
  <div style="display:flex;flex-direction:column;gap:10px;font-size:.85rem;">
    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);">
      <span style="color:var(--text-secondary);">Company Name</span>
      <span style="font-weight:500;">{{ $company->name }}</span>
    </div>
    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);">
      <span style="color:var(--text-secondary);">Email</span>
      <span>{{ $company->email ?? '--' }}</span>
    </div>
    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);">
      <span style="color:var(--text-secondary);">Phone</span>
      <span>{{ $company->phone ?? '--' }}</span>
    </div>
    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);">
      <span style="color:var(--text-secondary);">Country</span>
      <span>{{ $company->country ?? '--' }}</span>
    </div>
    <div style="display:flex;justify-content:space-between;padding:8px 0;">
      <span style="color:var(--text-secondary);">Currency</span>
      <span>{{ $company->currency ?? 'AED' }}</span>
    </div>
  </div>
</div>
@endsection
