@extends('layouts.app')

@section('title', 'Accounting Reports - MedFlow CRM')
@section('breadcrumb', 'Reports / Accounting')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Accounting Reports</h1>
    <p class="page-subtitle">Revenue, expenses, package sales, payment mix, and outstanding balances.</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('reports.accounting.export', ['format' => 'csv'] + request()->query()) }}" class="btn btn-secondary">Export CSV</a>
    <a href="{{ route('reports.accounting.export', ['format' => 'pdf'] + request()->query()) }}" class="btn btn-primary">Export PDF</a>
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
    <select name="payment_method" class="filter-select">
      <option value="">All payment methods</option>
      @foreach($paymentMethods as $method)
      <option value="{{ $method }}" @selected(($report['filters']['payment_method'] ?? null) === $method)>{{ ucfirst($method) }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn btn-primary">Apply</button>
  </div>
</form>

<div class="kpi-grid animate-in">
  <div class="kpi-card"><div class="kpi-label">Revenue</div><div class="kpi-value">JOD {{ number_format($report['stats']['revenue_total'], 2) }}</div></div>
  <div class="kpi-card"><div class="kpi-label">Expenses</div><div class="kpi-value">JOD {{ number_format($report['stats']['expense_total'], 2) }}</div></div>
  <div class="kpi-card"><div class="kpi-label">Package Sales</div><div class="kpi-value">JOD {{ number_format($report['stats']['package_sales_total'], 2) }}</div></div>
  <div class="kpi-card"><div class="kpi-label">Outstanding</div><div class="kpi-value">JOD {{ number_format($report['stats']['outstanding_total'], 2) }}</div></div>
</div>

<div class="grid-2 animate-in">
  <div class="card">
    <div class="card-header"><div class="card-title">Revenue by Period</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Period</th><th>Revenue</th></tr></thead>
        <tbody>
          @foreach($report['revenue_series'] as $row)
          <tr><td>{{ $row['label'] }}</td><td>JOD {{ number_format($row['amount'], 2) }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Expenses by Period</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Period</th><th>Expenses</th></tr></thead>
        <tbody>
          @foreach($report['expense_series'] as $row)
          <tr><td>{{ $row['label'] }}</td><td>JOD {{ number_format($row['amount'], 2) }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="grid-2 animate-in" style="margin-top:18px;align-items:start;">
  <div class="card">
    <div class="card-header"><div class="card-title">Payments by Method</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Method</th><th>Count</th><th>Amount</th></tr></thead>
        <tbody>
          @foreach($report['payments_by_method'] as $row)
          <tr><td>{{ ucfirst($row['method']) }}</td><td>{{ $row['count'] }}</td><td>JOD {{ number_format($row['amount'], 2) }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Package Sales Summary</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Package</th><th>Sales</th><th>Sessions</th></tr></thead>
        <tbody>
          @foreach($report['package_sales'] as $row)
          <tr><td>{{ $row['package'] }}</td><td>JOD {{ number_format($row['sales'], 2) }}</td><td>{{ $row['sessions'] }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="grid-2 animate-in" style="margin-top:18px;align-items:start;">
  <div class="card">
    <div class="card-header"><div class="card-title">Outstanding Balances</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Patient</th><th>Plan</th><th>Balance</th></tr></thead>
        <tbody>
          @foreach($report['outstanding_balances'] as $plan)
          <tr>
            <td>{{ $plan->patient?->full_name }}</td>
            <td>{{ $plan->service?->name ?? $plan->name }}</td>
            <td>JOD {{ number_format($plan->amount_remaining, 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Branch Profit Summary</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Branch</th><th>Revenue</th><th>Expenses</th><th>Net</th></tr></thead>
        <tbody>
          @foreach($report['branch_profit'] as $row)
          <tr>
            <td>{{ $row['branch'] }}</td>
            <td>JOD {{ number_format($row['revenue'], 2) }}</td>
            <td>JOD {{ number_format($row['expenses'], 2) }}</td>
            <td>JOD {{ number_format($row['net'], 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card animate-in" style="margin-top:18px;">
  <div class="card-header"><div><div class="card-title">Record Expense</div><div class="card-subtitle">Simple phase 1 entry point for expense reports.</div></div></div>
  <form method="POST" action="{{ route('reports.accounting.expenses.store') }}" class="form-row-3">
    @csrf
    <select name="branch_id" class="form-select">
      @foreach($branches as $branch)
      <option value="{{ $branch->id }}">{{ $branch->name }}</option>
      @endforeach
    </select>
    <select name="service_id" class="form-select">
      <option value="">No linked service</option>
      @foreach($services as $service)
      <option value="{{ $service->id }}">{{ $service->name }}</option>
      @endforeach
    </select>
    <input type="date" name="expense_date" value="{{ now()->toDateString() }}" class="form-input">
    <input type="text" name="category" class="form-input" placeholder="Category">
    <input type="text" name="title" class="form-input" placeholder="Expense title">
    <input type="number" step="0.01" min="0.01" name="amount" class="form-input" placeholder="Amount">
    <select name="payment_method" class="form-select">
      <option value="">No payment method</option>
      @foreach($paymentMethods as $method)
      <option value="{{ $method }}">{{ ucfirst($method) }}</option>
      @endforeach
    </select>
    <input type="text" name="notes" class="form-input" placeholder="Notes" style="grid-column: span 2;">
    <button type="submit" class="btn btn-primary">Save Expense</button>
  </form>
</div>
@endsection
