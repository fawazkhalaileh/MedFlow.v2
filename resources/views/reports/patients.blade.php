@extends('layouts.app')

@section('title', 'Patient Reports - MedFlow CRM')
@section('breadcrumb', 'Reports / Patients')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Patient Reports</h1>
    <p class="page-subtitle">Visit patterns, no-show metrics, follow-up risk, package use, and patient value.</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('reports.patients.export', ['format' => 'csv'] + request()->query()) }}" class="btn btn-secondary">Export CSV</a>
    <a href="{{ route('reports.patients.export', ['format' => 'pdf'] + request()->query()) }}" class="btn btn-primary">Export PDF</a>
  </div>
</div>

<form method="GET" class="card animate-in" style="margin-bottom:18px;">
  <div class="filter-bar" style="margin-bottom:0;">
    <select name="period" class="filter-select">
      @foreach(['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'custom' => 'Custom'] as $value => $label)
      <option value="{{ $value }}" @selected(($report['filters']['period'] ?? 'month') === $value)>{{ $label }}</option>
      @endforeach
    </select>
    <input type="date" name="start_date" value="{{ $report['filters']['start_date'] }}" class="form-input" style="max-width:180px;">
    <input type="date" name="end_date" value="{{ $report['filters']['end_date'] }}" class="form-input" style="max-width:180px;">
    <select name="branch_id" class="filter-select">
      <option value="">All branches</option>
      @foreach($branches as $branch)
      <option value="{{ $branch->id }}" @selected((string) $report['filters']['branch_id'] === (string) $branch->id)>{{ $branch->name }}</option>
      @endforeach
    </select>
    <select name="service_id" class="filter-select">
      <option value="">All services</option>
      @foreach($services as $service)
      <option value="{{ $service->id }}" @selected((string) $report['filters']['service_id'] === (string) $service->id)>{{ $service->name }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn btn-primary">Apply</button>
  </div>
</form>

<div class="kpi-grid animate-in">
  <div class="kpi-card"><div class="kpi-label">Patients</div><div class="kpi-value">{{ $report['stats']['patients_in_scope'] }}</div></div>
  <div class="kpi-card"><div class="kpi-label">Completed Visits</div><div class="kpi-value">{{ $report['stats']['completed_visits'] }}</div></div>
  <div class="kpi-card"><div class="kpi-label">Cancelled / No-show</div><div class="kpi-value">{{ $report['stats']['cancelled_or_no_show'] }}</div></div>
  <div class="kpi-card"><div class="kpi-label">Overdue Follow-ups</div><div class="kpi-value">{{ $report['stats']['overdue_follow_ups'] }}</div></div>
</div>

<div class="grid-2 animate-in">
  <div class="card">
    <div class="card-header"><div class="card-title">Visit Frequency</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Patient</th><th>Visits</th><th>Scheduled</th></tr></thead>
        <tbody>
          @foreach($report['visit_frequency'] as $row)
          <tr>
            <td><a href="{{ route('patients.show', $row['patient_id']) }}">{{ $row['patient'] }}</a></td>
            <td>{{ $row['visits'] }}</td>
            <td>{{ $row['scheduled'] }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">No-show / Cancellation Metrics</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Metric</th><th>Value</th></tr></thead>
        <tbody>
          <tr><td>Cancelled</td><td>{{ $report['no_show_metrics']['cancelled'] }}</td></tr>
          <tr><td>No show</td><td>{{ $report['no_show_metrics']['no_show'] }}</td></tr>
          <tr><td>Completed</td><td>{{ $report['no_show_metrics']['completed'] }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="grid-2 animate-in" style="margin-top:18px;">
  <div class="card">
    <div class="card-header"><div class="card-title">Package Consumption</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Patient</th><th>Package</th><th>Status</th><th>Used</th><th>Remaining</th></tr></thead>
        <tbody>
          @foreach($report['package_consumption'] as $row)
          <tr>
            <td><a href="{{ route('patients.show', $row['patient_id']) }}">{{ $row['patient'] }}</a></td>
            <td>{{ $row['package'] }}</td>
            <td>{{ $row['status'] }}</td>
            <td>{{ $row['used'] }}</td>
            <td>{{ $row['remaining'] }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Overdue Follow-up / No Future Booking</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Patient</th><th>Reason</th></tr></thead>
        <tbody>
          @foreach($report['overdue_follow_up'] as $followUp)
          <tr><td><a href="{{ route('patients.show', $followUp->patient_id) }}">{{ $followUp->patient?->full_name }}</a></td><td>Overdue follow-up</td></tr>
          @endforeach
          @foreach($report['no_future_booking'] as $patient)
          <tr><td><a href="{{ route('patients.show', $patient->id) }}">{{ $patient->full_name }}</a></td><td>No future booking</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="grid-2 animate-in" style="margin-top:18px;">
  <div class="card">
    <div class="card-header"><div class="card-title">Top Patients by Visits</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Patient</th><th>Visits</th></tr></thead>
        <tbody>
          @foreach($report['top_by_visits'] as $row)
          <tr><td><a href="{{ route('patients.show', $row['patient_id']) }}">{{ $row['patient'] }}</a></td><td>{{ $row['visits'] }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Top Patients by Spend</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Patient</th><th>Spend</th><th>Visits</th></tr></thead>
        <tbody>
          @foreach($report['top_by_spend'] as $row)
          <tr><td><a href="{{ route('patients.show', $row['patient_id']) }}">{{ $row['patient'] }}</a></td><td>JOD {{ number_format($row['spend'], 2) }}</td><td>{{ $row['visits'] }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="grid-2 animate-in" style="margin-top:18px;">
  <div class="card">
    <div class="card-header"><div class="card-title">Active vs Inactive</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Status</th><th>Count</th></tr></thead>
        <tbody>
          <tr><td>Active</td><td>{{ $report['active_vs_inactive']['active'] }}</td></tr>
          <tr><td>Inactive</td><td>{{ $report['active_vs_inactive']['inactive'] }}</td></tr>
          <tr><td>VIP</td><td>{{ $report['active_vs_inactive']['vip'] }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">First Visit vs Returning</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Type</th><th>Count</th></tr></thead>
        <tbody>
          <tr><td>First visit</td><td>{{ $report['first_vs_returning']['first_visit'] }}</td></tr>
          <tr><td>Returning</td><td>{{ $report['first_vs_returning']['returning'] }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
