@extends('layouts.app')

@section('title', __('inventory_ui.workspace_title').' - MedFlow CRM')
@section('breadcrumb', __('inventory_ui.workspace_title'))

@section('content')
@php
  use App\Models\BranchTransfer;
  use Illuminate\Support\Carbon;

  $transferTypes = BranchTransfer::transferTypes();
  $transferStatuses = BranchTransfer::statuses();
  $user = auth()->user();
  $isAdminInventoryView = $scopedBranchId === null;
  $formatQuantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
@endphp

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ __('inventory_ui.workspace_title') }}</h1>
    <p class="page-subtitle">{{ __('inventory_ui.workspace_subtitle') }}</p>
  </div>
  <div class="header-actions">
    <span class="badge badge-blue">{{ trans_choice('inventory_ui.branch_items', $branchInventories->count(), ['count' => $branchInventories->count()]) }}</span>
    <span class="badge {{ $lowStockAlerts->isEmpty() ? 'badge-green' : 'badge-yellow' }}">{{ trans_choice('inventory_ui.low_stock_summary', $lowStockAlerts->count(), ['count' => $lowStockAlerts->count()]) }}</span>
  </div>
</div>

@if($errors->any())
<div class="alert alert-danger">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <div>
    <div style="font-weight:600;margin-bottom:4px;">{{ __('inventory_ui.errors_heading') }}</div>
    <ul style="margin:0;padding-left:18px;">
      @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
</div>
@endif

@if(!$scopedBranchId)
<form method="GET" action="{{ route('inventory.index') }}" class="filter-bar animate-in" style="margin-bottom:18px;">
  <select name="branch" class="filter-select" style="min-width:220px;">
    <option value="">{{ __('inventory_ui.all_branches') }}</option>
    @foreach($branches as $branch)
    <option value="{{ $branch->id }}" {{ (string) $selectedBranchId === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn btn-secondary btn-sm">{{ __('inventory_ui.filter') }}</button>
  @if($selectedBranchId)
  <a href="{{ route('inventory.index') }}" class="btn btn-ghost btn-sm">{{ __('inventory_ui.clear') }}</a>
  @endif
</form>
@endif

<div class="grid-2-1 animate-in" style="align-items:start;">
  <div style="display:flex;flex-direction:column;gap:18px;">

    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ __('inventory_ui.cards.low_stock_title') }}</div>
          <div class="card-subtitle">{{ __('inventory_ui.cards.low_stock_subtitle') }}</div>
        </div>
        <span class="badge {{ $lowStockAlerts->isEmpty() ? 'badge-green' : 'badge-yellow' }}">{{ $lowStockAlerts->count() }}</span>
      </div>

      @if($lowStockAlerts->isEmpty())
      <div class="empty-state" style="padding:28px 20px;">
        <h3>{{ __('inventory_ui.empty.no_low_stock') }}</h3>
        <p>{{ __('inventory_ui.empty.no_low_stock_body') }}</p>
      </div>
      @else
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>{{ __('inventory_ui.table.branch') }}</th>
              <th>{{ __('inventory_ui.table.item') }}</th>
              <th>{{ __('inventory_ui.table.stock') }}</th>
              <th>{{ __('inventory_ui.table.threshold') }}</th>
              <th>{{ __('inventory_ui.table.next_expiry') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($lowStockAlerts as $inventory)
            <tr>
              <td>{{ $inventory->branch?->name }}</td>
              <td>
                <div style="font-weight:600;">{{ $inventory->inventoryItem?->name }}</div>
                <div style="font-size:.73rem;color:var(--text-tertiary);">{{ $inventory->inventoryItem?->sku ?: __('inventory_ui.labels.no_sku') }}</div>
              </td>
              <td>
                <span class="badge badge-red">{{ $formatQuantity($inventory->current_stock) }} {{ $inventory->inventoryItem?->unit }}</span>
              </td>
              <td>{{ $inventory->low_stock_threshold }}</td>
              <td>{{ $inventory->nearest_expiry ? Carbon::parse($inventory->nearest_expiry)->format('d M Y') : __('inventory_ui.labels.no_dated_batches') }}</td>
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
          <div class="card-title">{{ __('inventory_ui.cards.branch_inventory_title') }}</div>
          <div class="card-subtitle">{{ __('inventory_ui.cards.branch_inventory_subtitle') }}</div>
        </div>
        <span class="badge badge-gray">{{ $branchInventories->count() }}</span>
      </div>

      @if($branchInventories->isEmpty())
      <div class="empty-state">
        <h3>{{ __('inventory_ui.empty.no_inventory') }}</h3>
        <p>{{ __('inventory_ui.empty.no_inventory_body') }}</p>
      </div>
      @else
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>{{ __('inventory_ui.table.branch') }}</th>
              <th>{{ __('inventory_ui.table.item') }}</th>
              <th>{{ __('inventory_ui.table.stock') }}</th>
              <th>{{ __('inventory_ui.table.batches') }}</th>
              <th>{{ __('inventory_ui.table.status') }}</th>
              <th>{{ __('inventory_ui.table.nearest_expiry') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($branchInventories as $inventory)
            @php
              $nearestExpiry = $inventory->nearest_expiry ? Carbon::parse($inventory->nearest_expiry) : null;
              $isExpired = $nearestExpiry?->isPast();
              $isNearExpiry = $nearestExpiry && !$isExpired && $nearestExpiry->diffInDays(now()) <= 30;
            @endphp
            <tr>
              <td>{{ $inventory->branch?->name }}</td>
              <td>
                <div style="font-weight:600;">{{ $inventory->inventoryItem?->name }}</div>
                <div style="font-size:.73rem;color:var(--text-tertiary);">{{ $inventory->inventoryItem?->sku ?: __('inventory_ui.labels.no_sku') }} &middot; {{ $inventory->inventoryItem?->unit }}</div>
              </td>
              <td>
                <div style="font-weight:600;color:{{ $inventory->low_stock ? 'var(--danger)' : 'var(--text-primary)' }};">
                  {{ $formatQuantity($inventory->current_stock) }} {{ $inventory->inventoryItem?->unit }}
                </div>
                <div style="font-size:.73rem;color:var(--text-tertiary);">{{ __('inventory_ui.states.available_quantity') }} &middot; {{ $inventory->low_stock_threshold }}</div>
              </td>
              <td>
                @forelse($inventory->batches->take(3) as $batch)
                @php
                  $batchExpired = $batch->expires_on?->isPast();
                  $batchNearExpiry = $batch->expires_on && !$batchExpired && $batch->expires_on->diffInDays(now()) <= 30;
                @endphp
                <div style="font-size:.75rem;color:var(--text-secondary);margin-bottom:6px;">
                  <span class="badge badge-gray" style="margin-right:4px;">{{ $formatQuantity($batch->quantity_remaining) }}</span>
                  {{ $batch->batch_number ?: __('inventory_ui.labels.unnumbered_batch') }}
                  @if($batchExpired)
                  <span class="badge badge-red" style="margin-left:4px;">{{ __('inventory_ui.states.expired') }}</span>
                  @elseif($batchNearExpiry)
                  <span class="badge badge-yellow" style="margin-left:4px;">{{ __('inventory_ui.states.near_expiry') }}</span>
                  @endif
                </div>
                @empty
                <span style="font-size:.75rem;color:var(--text-tertiary);">{{ __('inventory_ui.labels.no_dated_batches') }}</span>
                @endforelse
                @if($inventory->batches->count() > 3)
                <div style="font-size:.73rem;color:var(--text-tertiary);">+{{ $inventory->batches->count() - 3 }}</div>
                @endif
              </td>
              <td>
                @if($inventory->low_stock)
                <span class="badge badge-red">{{ __('inventory_ui.states.low_stock') }}</span>
                @elseif($isExpired)
                <span class="badge badge-red">{{ __('inventory_ui.states.expired') }}</span>
                @elseif($isNearExpiry)
                <span class="badge badge-yellow">{{ __('inventory_ui.states.near_expiry') }}</span>
                @else
                <span class="badge badge-green">{{ __('inventory_ui.states.healthy') }}</span>
                @endif
              </td>
              <td>
                @if($nearestExpiry)
                <div>{{ $nearestExpiry->format('d M Y') }}</div>
                <div style="font-size:.73rem;color:var(--text-tertiary);">
                  @if($nearestExpiry->isToday())
                  {{ __('inventory_ui.states.expires_today') }}
                  @elseif($nearestExpiry->isPast())
                  {{ __('inventory_ui.states.expired') }}
                  @else
                  {{ __('inventory_ui.states.days_left', ['count' => now()->diffInDays($nearestExpiry)]) }}
                  @endif
                </div>
                @else
                {{ __('inventory_ui.labels.no_expiry') }}
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>

    <div class="grid-2">
      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title">{{ __('inventory_ui.cards.expiring_batches_title') }}</div>
            <div class="card-subtitle">{{ __('inventory_ui.cards.expiring_batches_subtitle') }}</div>
          </div>
          <span class="badge badge-gray">{{ $expiringBatches->count() }}</span>
        </div>

        @forelse($expiringBatches as $batch)
        @php
          $expired = $batch->expires_on?->isPast();
          $nearExpiry = $batch->expires_on && !$expired && $batch->expires_on->diffInDays(now()) <= 30;
        @endphp
        <div style="padding:10px 0;border-bottom:1px solid var(--border-light);">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <div>
              <div style="font-weight:600;font-size:.85rem;">{{ $batch->branchInventory?->inventoryItem?->name }}</div>
              <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $batch->branchInventory?->branch?->name }} &middot; {{ $batch->batch_number ?: __('inventory_ui.labels.unnumbered_batch') }}</div>
            </div>
            <span class="badge {{ $expired ? 'badge-red' : ($nearExpiry ? 'badge-yellow' : 'badge-gray') }}">{{ $formatQuantity($batch->quantity_remaining) }}</span>
          </div>
          <div style="font-size:.75rem;color:var(--text-secondary);margin-top:6px;">
            {{ __('inventory_ui.forms.expiry_date') }} {{ $batch->expires_on?->format('d M Y') }}
            @if($expired)
            &middot; {{ __('inventory_ui.states.expired') }}
            @elseif($nearExpiry)
            &middot; {{ __('inventory_ui.states.near_expiry') }}
            @endif
          </div>
        </div>
        @empty
        <p style="font-size:.84rem;color:var(--text-tertiary);">{{ __('inventory_ui.empty.no_dated_batches') }}</p>
        @endforelse
      </div>

      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title">{{ __('inventory_ui.cards.recent_transfers_title') }}</div>
            <div class="card-subtitle">{{ __('inventory_ui.cards.recent_transfers_subtitle') }}</div>
          </div>
          <span class="badge badge-gray">{{ $recentTransfers->count() }}</span>
        </div>

        @forelse($recentTransfers as $transfer)
        @php
          $canApprove = $transfer->canBeApproved() && ($isAdminInventoryView || $transfer->source_branch_id === $scopedBranchId);
          $canSend = $transfer->canBeSent() && ($isAdminInventoryView || $transfer->source_branch_id === $scopedBranchId);
          $canReceive = $transfer->canBeReceived() && ($isAdminInventoryView || $transfer->destination_branch_id === $scopedBranchId);
          $canCancel = $transfer->canBeCancelled() && ($isAdminInventoryView || $transfer->source_branch_id === $scopedBranchId);
        @endphp
        <div style="padding:12px 0;border-bottom:1px solid var(--border-light);">
          <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
            <div>
              <div style="font-weight:600;font-size:.85rem;">{{ $transfer->inventoryItem?->name }}</div>
              <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $transfer->sourceBranch?->name }} &rarr; {{ $transfer->destinationBranch?->name }}</div>
            </div>
            <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
              <span class="badge badge-blue">{{ $transfer->quantity }}</span>
              <span class="badge {{ $transfer->status === BranchTransfer::STATUS_RECEIVED ? 'badge-green' : ($transfer->status === BranchTransfer::STATUS_CANCELLED ? 'badge-red' : 'badge-yellow') }}">
                {{ __('inventory_ui.transfers.'.$transfer->status) }}
              </span>
            </div>
          </div>
          <div style="font-size:.75rem;color:var(--text-secondary);margin-top:6px;">
            {{ __('inventory_ui.transfers.'.$transfer->transfer_type) }} &middot;
            {{ $transfer->transferred_at?->format('d M, h:i A') }} &middot;
            {{ $transfer->transferredBy?->full_name }}
            @if($transfer->internal_unit_price !== null)
            &middot; {{ __('inventory_ui.transfers.internal_price') }} {{ number_format((float) $transfer->internal_unit_price, 2) }}
            @endif
          </div>
          @if($transfer->notes)
          <div style="font-size:.74rem;color:var(--text-tertiary);margin-top:4px;">{{ __('inventory_ui.transfers.notes') }}: {{ $transfer->notes }}</div>
          @endif
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
            @if($canApprove)
            <form method="POST" action="{{ route('inventory.transfers.approve', $transfer) }}">
              @csrf
              <button type="submit" class="btn btn-secondary btn-sm">{{ __('inventory_ui.transfers.approve') }}</button>
            </form>
            @endif
            @if($canSend)
            <form method="POST" action="{{ route('inventory.transfers.send', $transfer) }}">
              @csrf
              <button type="submit" class="btn btn-primary btn-sm">{{ __('inventory_ui.transfers.send') }}</button>
            </form>
            @endif
            @if($canReceive)
            <form method="POST" action="{{ route('inventory.transfers.receive', $transfer) }}">
              @csrf
              <button type="submit" class="btn btn-primary btn-sm">{{ __('inventory_ui.transfers.receive') }}</button>
            </form>
            @endif
            @if($canCancel)
            <form method="POST" action="{{ route('inventory.transfers.cancel', $transfer) }}">
              @csrf
              <button type="submit" class="btn btn-ghost btn-sm">{{ __('inventory_ui.transfers.cancel') }}</button>
            </form>
            @endif
          </div>
        </div>
        @empty
        <p style="font-size:.84rem;color:var(--text-tertiary);">{{ __('inventory_ui.empty.no_transfers') }}</p>
        @endforelse
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ __('inventory_ui.cards.recent_movements_title') }}</div>
          <div class="card-subtitle">{{ __('inventory_ui.cards.recent_movements_subtitle') }}</div>
        </div>
        <span class="badge badge-gray">{{ $recentMovements->count() }}</span>
      </div>

      @forelse($recentMovements as $movement)
      <div style="padding:10px 0;border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;gap:14px;">
        <div>
          <div style="font-weight:600;font-size:.84rem;">{{ $movement->inventoryItem?->name }}</div>
          <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $movement->branch?->name }} &middot; {{ __('inventory_ui.movements.'.$movement->movement_type) }}</div>
          @if($movement->patient)
          <div style="font-size:.74rem;color:var(--text-secondary);margin-top:2px;">{{ __('inventory_ui.labels.patient') }}: {{ $movement->patient->full_name }}</div>
          @elseif($movement->movement_type === \App\Models\InventoryMovement::TYPE_WASTE)
          <div style="font-size:.74rem;color:var(--text-secondary);margin-top:2px;">{{ __('inventory_ui.labels.waste_entry') }}</div>
          @endif
          @if($movement->notes)
          <div style="font-size:.74rem;color:var(--text-tertiary);margin-top:2px;">{{ $movement->notes }}</div>
          @endif
        </div>
        <div style="text-align:right;">
          <div style="font-weight:600;color:{{ $movement->quantity_change < 0 ? 'var(--danger)' : 'var(--success)' }};">
            {{ $movement->quantity_change > 0 ? '+' : '' }}{{ $formatQuantity($movement->quantity_change) }}
          </div>
          <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $movement->occurred_at?->format('d M, h:i A') }}</div>
        </div>
      </div>
      @empty
      <p style="font-size:.84rem;color:var(--text-tertiary);">{{ __('inventory_ui.empty.no_movements') }}</p>
      @endforelse
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px;">
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ __('inventory_ui.cards.create_item_title') }}</div>
          <div class="card-subtitle">{{ __('inventory_ui.cards.create_item_subtitle') }}</div>
        </div>
      </div>

      <form method="POST" action="{{ route('inventory.items.store') }}" style="display:grid;gap:10px;">
        @csrf
        <input type="text" name="name" class="form-input" placeholder="{{ __('inventory_ui.forms.item_name') }}" value="{{ old('name') }}" required>
        <input type="text" name="sku" class="form-input" placeholder="{{ __('inventory_ui.forms.sku_optional') }}" value="{{ old('sku') }}">
        <input type="text" name="unit" class="form-input" placeholder="{{ __('inventory_ui.forms.unit_placeholder') }}" value="{{ old('unit', 'unit') }}" required>
        <textarea name="description" class="form-textarea" placeholder="{{ __('inventory_ui.labels.description') }}">{{ old('description') }}</textarea>
        <button type="submit" class="btn btn-primary btn-sm" style="justify-content:center;">{{ __('inventory_ui.forms.create_item') }}</button>
      </form>
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ __('inventory_ui.cards.add_stock_title') }}</div>
          <div class="card-subtitle">{{ __('inventory_ui.cards.add_stock_subtitle') }}</div>
        </div>
      </div>

      <form method="POST" action="{{ route('inventory.stock.store') }}" style="display:grid;gap:10px;">
        @csrf
        @if(!$scopedBranchId)
        <select name="branch_id" class="form-select" required>
          <option value="">{{ __('inventory_ui.forms.select_branch') }}</option>
          @foreach($branches as $branch)
          <option value="{{ $branch->id }}" {{ old('branch_id', $selectedBranchId) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
          @endforeach
        </select>
        @endif
        <select name="inventory_item_id" class="form-select" required>
          <option value="">{{ __('inventory_ui.forms.select_item') }}</option>
          @foreach($inventoryItems as $item)
          <option value="{{ $item->id }}" {{ old('inventory_item_id') == $item->id ? 'selected' : '' }}>{{ $item->name }}{{ $item->sku ? ' &middot; '.$item->sku : '' }}</option>
          @endforeach
        </select>
        <input type="number" min="0.01" step="0.01" name="quantity" class="form-input" placeholder="{{ __('inventory_ui.forms.quantity') }}" value="{{ old('quantity') }}" required>
        <input type="text" name="batch_number" class="form-input" placeholder="{{ __('inventory_ui.forms.batch_number_optional') }}" value="{{ old('batch_number') }}">
        <div style="display:grid;gap:6px;">
          <label for="inventory-expires-on" style="font-size:.78rem;font-weight:600;color:var(--text-secondary);">{{ __('inventory_ui.forms.expiry_date') }}</label>
          <input id="inventory-expires-on" type="date" name="expires_on" class="form-input" value="{{ old('expires_on') }}">
        </div>
        <div style="display:grid;gap:6px;">
          <label for="inventory-received-on" style="font-size:.78rem;font-weight:600;color:var(--text-secondary);">{{ __('inventory_ui.forms.received_on') }}</label>
          <input id="inventory-received-on" type="date" name="received_on" class="form-input" value="{{ old('received_on', now()->toDateString()) }}">
        </div>
        <input type="number" min="0" step="1" name="low_stock_threshold" class="form-input" placeholder="{{ __('inventory_ui.forms.low_stock_threshold') }}" value="{{ old('low_stock_threshold', 5) }}">
        <input type="number" min="0" step="0.01" name="unit_cost" class="form-input" placeholder="{{ __('inventory_ui.forms.unit_cost_optional') }}" value="{{ old('unit_cost') }}">
        <textarea name="notes" class="form-textarea" placeholder="{{ __('inventory_ui.labels.batch_notes') }}">{{ old('notes') }}</textarea>
        <button type="submit" class="btn btn-primary btn-sm" style="justify-content:center;">{{ __('inventory_ui.forms.add_stock') }}</button>
      </form>
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ __('inventory_ui.cards.usage_title') }}</div>
          <div class="card-subtitle">{{ __('inventory_ui.cards.usage_subtitle') }}</div>
        </div>
      </div>

      @if($branchInventories->isEmpty())
      <p style="font-size:.84rem;color:var(--text-tertiary);">{{ __('inventory_ui.empty.add_stock_before_usage') }}</p>
      @else
      <form method="POST" action="{{ route('inventory.usage.store') }}" style="display:grid;gap:10px;">
        @csrf
        <select name="branch_inventory_id" class="form-select" required>
          <option value="">{{ __('inventory_ui.forms.select_branch_item') }}</option>
          @foreach($branchInventories as $inventory)
          <option value="{{ $inventory->id }}" {{ old('branch_inventory_id') == $inventory->id ? 'selected' : '' }}>
            {{ $inventory->branch?->name }} &middot; {{ $inventory->inventoryItem?->name }} ({{ $formatQuantity($inventory->current_stock) }} {{ $inventory->inventoryItem?->unit }})
          </option>
          @endforeach
        </select>
        <select name="patient_id" class="form-select">
          <option value="">{{ __('inventory_ui.forms.patient_optional') }}</option>
          @foreach($patients as $patient)
          <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
            {{ $patient->full_name }}{{ !$scopedBranchId ? ' &middot; '.$patient->branch?->name : '' }}
          </option>
          @endforeach
        </select>
        <input type="number" min="0" step="0.01" name="used_quantity" class="form-input" placeholder="{{ __('inventory_ui.forms.quantity_used_on_patient') }}" value="{{ old('used_quantity', old('quantity')) }}">
        <input type="number" min="0" step="0.01" name="wasted_quantity" class="form-input" placeholder="{{ __('inventory_ui.forms.quantity_wasted') }}" value="{{ old('wasted_quantity') }}">
        <div style="font-size:.75rem;color:var(--text-tertiary);">{{ __('inventory_ui.labels.usage_split_hint') }}</div>
        <textarea name="notes" class="form-textarea" placeholder="{{ __('inventory_ui.labels.usage_notes') }}">{{ old('notes') }}</textarea>
        <button type="submit" class="btn btn-primary btn-sm" style="justify-content:center;">{{ __('inventory_ui.forms.record_usage') }}</button>
      </form>
      @endif
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ __('inventory_ui.cards.transfer_title') }}</div>
          <div class="card-subtitle">{{ __('inventory_ui.cards.transfer_subtitle') }}</div>
        </div>
      </div>

      @if($branchInventories->isEmpty() || $branches->count() < 2)
      <p style="font-size:.84rem;color:var(--text-tertiary);">{{ __('inventory_ui.empty.transfer_requirements') }}</p>
      @else
      <form method="POST" action="{{ route('inventory.transfers.store') }}" style="display:grid;gap:10px;">
        @csrf
        <select name="branch_inventory_id" class="form-select" required>
          <option value="">{{ __('inventory_ui.forms.select_branch_item') }}</option>
          @foreach($branchInventories as $inventory)
          <option value="{{ $inventory->id }}" {{ old('branch_inventory_id') == $inventory->id ? 'selected' : '' }}>
            {{ $inventory->branch?->name }} &middot; {{ $inventory->inventoryItem?->name }} ({{ $formatQuantity($inventory->current_stock) }} {{ $inventory->inventoryItem?->unit }})
          </option>
          @endforeach
        </select>
        <select name="destination_branch_id" class="form-select" required>
          <option value="">{{ __('inventory_ui.forms.destination_branch') }}</option>
          @foreach($destinationBranches as $branch)
          @if(!$scopedBranchId || $branch->id !== $scopedBranchId)
          <option value="{{ $branch->id }}" {{ old('destination_branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
          @endif
          @endforeach
        </select>
        <select name="transfer_type" class="form-select" required>
          @foreach($transferTypes as $transferType)
          <option value="{{ $transferType }}" {{ old('transfer_type', BranchTransfer::TYPE_TRANSFER) === $transferType ? 'selected' : '' }}>
            {{ __('inventory_ui.transfers.'.$transferType) }}
          </option>
          @endforeach
        </select>
        <input type="number" min="0.01" step="0.01" name="quantity" class="form-input" placeholder="{{ __('inventory_ui.forms.quantity_to_transfer') }}" value="{{ old('quantity') }}" required>
        <input type="number" min="0" step="0.01" name="internal_unit_price" class="form-input" placeholder="{{ __('inventory_ui.forms.internal_price_optional') }}" value="{{ old('internal_unit_price') }}">
        <textarea name="notes" class="form-textarea" placeholder="{{ __('inventory_ui.labels.transfer_notes') }}">{{ old('notes') }}</textarea>
        <button type="submit" class="btn btn-primary btn-sm" style="justify-content:center;">{{ __('inventory_ui.forms.create_transfer') }}</button>
      </form>
      @endif
    </div>
  </div>
</div>
@endsection

