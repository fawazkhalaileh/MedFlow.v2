@extends('layouts.app')
@section('title', 'Roles & Permissions - MedFlow CRM')
@section('breadcrumb', 'Admin / Roles & Permissions')
@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Roles &amp; Permissions</h1>
    <p class="page-subtitle">Define what each role can access across the system</p>
  </div>
  <a href="{{ route('admin.index') }}" class="btn btn-secondary">Back to Admin</a>
</div>

<div class="animate-in" style="animation-delay:.05s;display:flex;flex-direction:column;gap:14px;">
@foreach($roles as $role)
<div class="card">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
    <div style="width:12px;height:12px;border-radius:50%;background:{{ $role->color }};flex-shrink:0;"></div>
    <div class="card-title">{{ $role->display_name }}</div>
    @if($role->is_system)
    <span class="badge badge-gray" style="font-size:.68rem;">System Role</span>
    @endif
    <span class="badge badge-blue" style="margin-left:auto;font-size:.72rem;">{{ $role->permissions->count() }} permissions</span>
  </div>
  <p style="font-size:.83rem;color:var(--text-secondary);margin-bottom:12px;">{{ $role->description }}</p>
  <div style="display:flex;flex-wrap:wrap;gap:5px;">
    @foreach($role->permissions as $perm)
    <span style="font-size:.71rem;padding:2px 8px;border-radius:4px;background:var(--accent-light);color:var(--accent);">
      {{ $perm->display_name }}
    </span>
    @endforeach
  </div>
</div>
@endforeach
</div>
@endsection
