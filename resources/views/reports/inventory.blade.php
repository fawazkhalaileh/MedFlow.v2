@extends('layouts.app')

@section('title', 'Inventory Reports - MedFlow CRM')
@section('breadcrumb', 'Reports / Inventory')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Inventory Reports</h1>
    <p class="page-subtitle">Current stock, movement history, usage, low stock, expiry, and transfer trends.</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('reports.inventory.export', ['format' => 'csv'] + request()->query()) }}" class="btn btn-secondary">Export CSV</a>
    <a href="{{ route('reports.inventory.export', ['format' => 'pdf'] + request()->query()) }}" class="btn btn-primary">Export PDF</a>
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
    <button type="submit" class="btn btn-primary">Apply</button>
  </div>
</form>

<div class="kpi-grid animate-in">
  <div class="kpi-card"><div class="kpi-label">Branch Items</div><div class="kpi-value">{{ $report['stats']['branch_items'] }}</div></div>
  <div class="kpi-card"><div class="kpi-label">Low Stock Alerts</div><div class="kpi-value">{{ $report['stats']['low_stock_count'] }}</div></div>
  <div class="kpi-card"><div class="kpi-label">Expiry Alerts</div><div class="kpi-value">{{ $report['stats']['expiring_count'] }}</div></div>
  <div class="kpi-card"><div class="kpi-label">Movements</div><div class="kpi-value">{{ $report['stats']['movement_count'] }}</div></div>
</div>

<div class="grid-2 animate-in">
  <div class="card">
    <div class="card-header"><div class="card-title">Current Stock by Item</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Branch</th><th>Item</th><th>Stock</th><th>Low Stock</th></tr></thead>
        <tbody>
          @foreach($report['current_stock'] as $inventory)
          <tr>
            <td>{{ $inventory->branch?->name }}</td>
            <td>{{ $inventory->inventoryItem?->name }}</td>
            <td>{{ $inventory->current_stock }}</td>
            <td>{{ $inventory->low_stock ? 'Yes' : 'No' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Usage by Period</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Date</th><th>Used</th><th>Wasted</th></tr></thead>
        <tbody>
          @foreach($report['usage_by_period'] as $row)
          <tr><td>{{ $row['date'] }}</td><td>{{ $row['used'] }}</td><td>{{ $row['wasted'] }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="grid-2 animate-in" style="margin-top:18px;">
  <div class="card">
    <div class="card-header"><div class="card-title">Low Stock Alerts</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Branch</th><th>Item</th><th>Stock</th><th>Threshold</th></tr></thead>
        <tbody>
          @foreach($report['low_stock_alerts'] as $inventory)
          <tr><td>{{ $inventory->branch?->name }}</td><td>{{ $inventory->inventoryItem?->name }}</td><td>{{ $inventory->current_stock }}</td><td>{{ $inventory->low_stock_threshold }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Expiry Alerts</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Branch</th><th>Item</th><th>Batch</th><th>Expires</th></tr></thead>
        <tbody>
          @foreach($report['expiry_alerts'] as $batch)
          <tr><td>{{ $batch->branchInventory?->branch?->name }}</td><td>{{ $batch->branchInventory?->inventoryItem?->name }}</td><td>{{ $batch->batch_number }}</td><td>{{ $batch->expires_on?->toDateString() }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="grid-2 animate-in" style="margin-top:18px;">
  <div class="card">
    <div class="card-header"><div class="card-title">Deducted by Session / Service</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Bucket</th><th>Service</th><th>Session</th><th>Quantity</th></tr></thead>
        <tbody>
          @foreach($report['deducted_by_session'] as $row)
          <tr><td>{{ $row['bucket'] }}</td><td>{{ $row['service'] ?? 'Manual' }}</td><td>{{ $row['session_id'] ?? '--' }}</td><td>{{ $row['quantity'] }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Branch Summary</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Branch</th><th>Items</th><th>Stock</th><th>Movements</th></tr></thead>
        <tbody>
          @foreach($report['branch_summary'] as $row)
          <tr><td>{{ $row['branch'] }}</td><td>{{ $row['items'] }}</td><td>{{ $row['stock'] }}</td><td>{{ $row['movements'] }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card animate-in" style="margin-top:18px;">
  <div class="card-header"><div class="card-title">Transfer History</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Item</th><th>From</th><th>To</th><th>Status</th><th>Qty</th></tr></thead>
      <tbody>
        @foreach($report['transfer_history'] as $transfer)
        <tr>
          <td>{{ optional($transfer->transferred_at)->format('Y-m-d H:i') }}</td>
          <td>{{ $transfer->inventoryItem?->name }}</td>
          <td>{{ $transfer->sourceBranch?->name }}</td>
          <td>{{ $transfer->destinationBranch?->name }}</td>
          <td>{{ $transfer->status }}</td>
          <td>{{ $transfer->quantity }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
