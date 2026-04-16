@extends('layouts.app')

@section('title', 'Commissions & Compensation - MedFlow CRM')
@section('breadcrumb', 'Reports / Commissions')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Commissions & Compensation</h1>
    <p class="page-subtitle">Salary, commission rules, work attribution, totals due, and auditable period snapshots.</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('reports.commissions.export', ['format' => 'csv'] + request()->query()) }}" class="btn btn-secondary">Export CSV</a>
    <a href="{{ route('reports.commissions.export', ['format' => 'pdf'] + request()->query()) }}" class="btn btn-primary">Export PDF</a>
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
  <div class="card-header">
    <div class="card-title">Period Compensation</div>
    <form method="POST" action="{{ route('reports.commissions.snapshots.store', request()->query()) }}">
      @csrf
      <button type="submit" class="btn btn-secondary btn-sm">Generate Snapshots</button>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Employee</th>
          <th>Comp Type</th>
          <th>Worked On</th>
          <th>Revenue Attributed</th>
          <th>Fixed Salary</th>
          <th>Commission</th>
          <th>Total Due</th>
        </tr>
      </thead>
      <tbody>
        @foreach($report['rows'] as $row)
        <tr>
          <td>{{ $row['employee']->full_name }}</td>
          <td>{{ $row['profile']?->compensation_type ?? 'no_profile' }}</td>
          <td>
            Sessions: {{ $row['totals']['sessions_completed'] }}<br>
            Services: {{ $row['totals']['services_performed'] }}<br>
            Package sales: {{ $row['totals']['package_sales'] }}<br>
            Package usage: {{ $row['totals']['package_usage_items'] }}
          </td>
          <td>JOD {{ number_format($row['totals']['revenue_attributed'], 2) }}</td>
          <td>JOD {{ number_format($row['fixed_salary'], 2) }}</td>
          <td>JOD {{ number_format($row['commission_total'], 2) }}</td>
          <td>JOD {{ number_format($row['total_due'], 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<div class="grid-2 animate-in" style="margin-top:18px;align-items:start;">
  <div class="card">
    <div class="card-header"><div class="card-title">Compensation Profiles</div></div>
    <form method="POST" action="{{ route('reports.commissions.profiles.store') }}" class="form-row-3" style="margin-bottom:16px;">
      @csrf
      <select name="employee_id" class="form-select" required>
        <option value="">Employee</option>
        @foreach($employees as $employee)
        <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
        @endforeach
      </select>
      <select name="branch_id" class="form-select">
        <option value="">Global / All allowed branches</option>
        @foreach($branches as $branch)
        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
        @endforeach
      </select>
      <select name="compensation_type" class="form-select" required>
        <option value="salary_only">Salary only</option>
        <option value="commission_only">Commission only</option>
        <option value="salary_plus_commission">Salary + commission</option>
      </select>
      <input type="number" step="0.01" min="0" name="fixed_salary" class="form-input" placeholder="Fixed salary" required>
      <input type="date" name="effective_from" class="form-input">
      <input type="date" name="effective_to" class="form-input">
      <button type="submit" class="btn btn-primary">Save Profile</button>
    </form>

    <div class="table-wrap">
      <table>
        <thead><tr><th>Employee</th><th>Branch</th><th>Type</th><th>Salary</th></tr></thead>
        <tbody>
          @foreach($profiles as $profile)
          <tr>
            <td>{{ $profile->employee?->full_name }}</td>
            <td>{{ $profile->branch?->name ?? 'Global' }}</td>
            <td>{{ $profile->compensation_type }}</td>
            <td>JOD {{ number_format((float) $profile->fixed_salary, 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Commission Rules</div></div>
    <form method="POST" action="{{ route('reports.commissions.rules.store') }}" class="form-row-3" style="margin-bottom:16px;">
      @csrf
      <select name="rule_scope" class="form-select" required>
        <option value="global">Global</option>
        <option value="branch">Branch</option>
        <option value="employee">Employee</option>
        <option value="employee_branch">Employee + branch</option>
      </select>
      <select name="source_type" class="form-select" required>
        <option value="completed_service">Completed service</option>
        <option value="package_sale">Package sale</option>
        <option value="package_consumption">Package consumed</option>
        <option value="per_session">Per session</option>
      </select>
      <select name="calculation_type" class="form-select" required>
        <option value="percentage">Percentage</option>
        <option value="per_session">Per session</option>
        <option value="fixed">Fixed</option>
      </select>
      <select name="employee_id" class="form-select">
        <option value="">No employee override</option>
        @foreach($employees as $employee)
        <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
        @endforeach
      </select>
      <select name="branch_id" class="form-select">
        <option value="">No branch override</option>
        @foreach($branches as $branch)
        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
        @endforeach
      </select>
      <select name="service_id" class="form-select">
        <option value="">Any service</option>
        @foreach($services as $service)
        <option value="{{ $service->id }}">{{ $service->name }}</option>
        @endforeach
      </select>
      <input type="number" step="0.01" min="0" name="rate" class="form-input" placeholder="Rate %">
      <input type="number" step="0.01" min="0" name="flat_amount" class="form-input" placeholder="Flat amount">
      <input type="number" min="1" name="priority" class="form-input" placeholder="Priority" value="100">
      <input type="date" name="effective_from" class="form-input">
      <input type="date" name="effective_to" class="form-input">
      <button type="submit" class="btn btn-primary">Save Rule</button>
    </form>

    <div class="table-wrap">
      <table>
        <thead><tr><th>Scope</th><th>Source</th><th>Calc</th><th>Employee</th><th>Branch</th></tr></thead>
        <tbody>
          @foreach($rules as $rule)
          <tr>
            <td>{{ $rule->rule_scope }}</td>
            <td>{{ $rule->source_type }}</td>
            <td>{{ $rule->calculation_type }} {{ $rule->rate ? '(' . $rule->rate . '%)' : ($rule->flat_amount ? '(JOD ' . number_format((float) $rule->flat_amount, 2) . ')' : '') }}</td>
            <td>{{ $rule->employee?->full_name ?? 'All' }}</td>
            <td>{{ $rule->branch?->name ?? 'All' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
