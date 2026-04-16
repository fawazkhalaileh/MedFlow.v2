@extends('layouts.app')

@section('title', 'Reports - MedFlow CRM')
@section('breadcrumb', 'Reports')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Reports</h1>
    <p class="page-subtitle">Open this from the left sidebar via <strong>Reports</strong>. Finance and managers can open accounting and commission reports. Clinical staff can open patient and performance reporting within their visibility.</p>
  </div>
</div>

<div class="grid-3 animate-in">
  @if($user->isSuperAdmin() || $user->isRole('finance', 'branch_manager'))
  <a href="{{ route('reports.accounting') }}" class="card" style="text-decoration:none;color:inherit;">
    <div class="card-title" style="margin-bottom:8px;">Accounting Reports</div>
    <div class="card-subtitle">Revenue, expenses, balances, and package sales.</div>
  </a>
  @endif

  <a href="{{ route('reports.patients') }}" class="card" style="text-decoration:none;color:inherit;">
    <div class="card-title" style="margin-bottom:8px;">Patient Reports</div>
    <div class="card-subtitle">Visit patterns, no-show metrics, package status, and patient value.</div>
  </a>

  @if($user->isSuperAdmin() || $user->isRole('finance', 'branch_manager'))
  <a href="{{ route('reports.inventory') }}" class="card" style="text-decoration:none;color:inherit;">
    <div class="card-title" style="margin-bottom:8px;">Inventory Reports</div>
    <div class="card-subtitle">Stock, movements, low stock, expiry, and transfers.</div>
  </a>
  @endif

  <a href="{{ route('reports.technician-performance') }}" class="card" style="text-decoration:none;color:inherit;">
    <div class="card-title" style="margin-bottom:8px;">Technician Performance</div>
    <div class="card-subtitle">Sessions completed, service mix, utilization, and attributable revenue.</div>
  </a>

  @if($user->isSuperAdmin() || $user->isRole('finance', 'branch_manager'))
  <a href="{{ route('reports.commissions') }}" class="card" style="text-decoration:none;color:inherit;">
    <div class="card-title" style="margin-bottom:8px;">Commissions & Compensation</div>
    <div class="card-subtitle">Salary, commission rules, totals due, and auditable period snapshots.</div>
  </a>
  @endif
</div>
@endsection
