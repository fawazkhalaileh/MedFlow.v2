@extends('layouts.app')

@section('title', 'Technician Performance - MedFlow CRM')
@section('breadcrumb', 'Reports / Technician Performance')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Technician Performance</h1>
    <p class="page-subtitle">Sessions completed, services performed, utilization, attributable revenue, and package usage by employee.</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('reports.technician-performance.export', ['format' => 'csv'] + request()->query()) }}" class="btn btn-secondary">Export CSV</a>
    <a href="{{ route('reports.technician-performance.export', ['format' => 'pdf'] + request()->query()) }}" class="btn btn-primary">Export PDF</a>
  </div>
</div>

<form method="GET" class="card animate-in" style="margin-bottom:18px;">
  <div class="filter-bar" style="margin-bottom:0;">
    <input type="date" name="period_start" value="{{ $report['filters']['period_start'] }}" class="form-input" style="max-width:180px;">
    <input type="date" name="period_end" value="{{ $report['filters']['period_end'] }}" class="form-input" style="max-width:180px;">
    <select name="branch_id" class="filter-select">
      <option value="">All branches</option>
      @foreach($branches as $branch)
      <option value="{{ $branch->id }}" @selected((string) $report['filters']['branch_id'] === (string) $branch->id)>{{ $branch->name }}</option>
      @endforeach
    </select>
    <select name="employee_id" class="filter-select">
      <option value="">All employees</option>
      @foreach($employees as $employee)
      <option value="{{ $employee->id }}" @selected((string) $report['filters']['employee_id'] === (string) $employee->id)>{{ $employee->full_name }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn btn-primary">Apply</button>
  </div>
</form>

<div class="card animate-in">
  <div class="card-header"><div class="card-title">Performance by Employee</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Employee</th>
          <th>Sessions</th>
          <th>Revenue</th>
          <th>Package Usage</th>
          <th>Package Sales</th>
          <th>Services</th>
        </tr>
      </thead>
      <tbody>
        @foreach($report['performance'] as $row)
        <tr>
          <td>{{ $row['employee']->full_name }}</td>
          <td>{{ $row['sessions_completed'] }}</td>
          <td>JOD {{ number_format($row['revenue_attributable'], 2) }}</td>
          <td>JOD {{ number_format($row['package_usage_attributable'], 2) }}</td>
          <td>JOD {{ number_format($row['package_sales_attributable'], 2) }}</td>
          <td>
            {{ $row['services_performed']->map(fn ($service) => $service['service'].' ('.$service['count'].')')->implode(', ') ?: '--' }}
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<div class="card animate-in" style="margin-top:18px;">
  <div class="card-header"><div class="card-title">Branch Summary</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Branch</th><th>Sessions</th><th>Revenue</th><th>Package Usage</th></tr></thead>
      <tbody>
        @foreach($report['branch_summary'] as $row)
        <tr>
          <td>{{ $row['branch'] }}</td>
          <td>{{ $row['sessions'] }}</td>
          <td>JOD {{ number_format($row['revenue'], 2) }}</td>
          <td>JOD {{ number_format($row['package_usage'], 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
