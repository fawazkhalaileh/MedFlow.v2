@extends('layouts.app')

@section('title', 'Confirm Import - MedFlow CRM')
@section('breadcrumb', 'Data Import')

@section('content')

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Confirm Import &mdash; {{ ucfirst($importType) }}</h1>
    <p class="page-subtitle">Review the validation results before committing the import.</p>
  </div>
</div>

{{-- SUMMARY CARDS --}}
<div class="kpi-grid animate-in" style="animation-delay:.04s;margin-bottom:20px;">

  <div class="kpi-card">
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
    </div>
    <div class="kpi-label">{{ __('Total Rows') }}</div>
    <div class="kpi-value">{{ $totalRows }}</div>
    <div class="kpi-change neutral">in your file</div>
  </div>

  <div class="kpi-card" style="{{ $validCount > 0 ? 'border-color:var(--success);' : '' }}">
    <div class="kpi-icon" style="{{ $validCount > 0 ? 'color:var(--success);' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div class="kpi-label">{{ __('Valid Rows') }}</div>
    <div class="kpi-value" style="{{ $validCount > 0 ? 'color:var(--success);' : '' }}">{{ $validCount }}</div>
    <div class="kpi-change up">ready to import</div>
  </div>

  <div class="kpi-card" style="{{ $errorCount > 0 ? 'border-color:var(--danger);' : '' }}">
    <div class="kpi-icon" style="{{ $errorCount > 0 ? 'color:var(--danger);' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    </div>
    <div class="kpi-label">{{ __('Rows with Errors') }}</div>
    <div class="kpi-value" style="{{ $errorCount > 0 ? 'color:var(--danger);' : '' }}">{{ $errorCount }}</div>
    <div class="kpi-change {{ $errorCount > 0 ? 'down' : 'neutral' }}">will be skipped</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    </div>
    <div class="kpi-label">{{ __('Will Import') }}</div>
    <div class="kpi-value" style="color:var(--accent);">{{ $validCount }}</div>
    <div class="kpi-change up">records</div>
  </div>

</div>

{{-- WARNING IF ERRORS --}}
@if($errorCount > 0)
<div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-sm);padding:12px 16px;display:flex;align-items:center;gap:10px;margin-bottom:20px;" class="animate-in" style="animation-delay:.06s;">
  <svg viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
  <span style="font-size:.82rem;color:var(--danger);">
    <strong>{{ $errorCount }} row{{ $errorCount !== 1 ? 's' : '' }} with errors will be skipped.</strong>
    Only the {{ $validCount }} valid row{{ $validCount !== 1 ? 's' : '' }} will be imported.
  </span>
</div>
@endif

{{-- ERROR TABLE --}}
@if(count($errors) > 0)
<div class="card" style="padding:0;margin-bottom:20px;" class="animate-in" style="animation-delay:.08s;">
  <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
    <div class="card-title" style="color:var(--danger);">{{ __('Validation Errors') }}</div>
    <span style="font-size:.75rem;color:var(--text-tertiary);">Showing first {{ min(20, count($errors)) }} of {{ count($errors) }} errors</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:80px;">Row #</th>
          <th style="width:150px;">{{ __('Field') }}</th>
          <th>{{ __('Issue') }}</th>
          <th>{{ __('Raw Data') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach(array_slice($errors, 0, 20) as $err)
        <tr>
          <td style="font-size:.8rem;font-weight:500;color:var(--danger);">Row {{ $err['row_number'] }}</td>
          <td>
            <span style="font-family:monospace;font-size:.78rem;background:var(--bg-tertiary);padding:2px 6px;border-radius:4px;">{{ $err['field'] }}</span>
          </td>
          <td style="font-size:.8rem;color:var(--text-secondary);">{{ $err['message'] }}</td>
          <td style="font-size:.75rem;color:var(--text-tertiary);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $err['raw_data'] ?? '—' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @if(count($errors) > 20)
  <div style="padding:10px 20px;font-size:.75rem;color:var(--text-tertiary);border-top:1px solid var(--border);">
    ...and {{ count($errors) - 20 }} more errors not shown.
  </div>
  @endif
</div>
@endif

{{-- ACTIONS --}}
<div style="display:flex;gap:10px;padding-bottom:24px;" class="animate-in" style="animation-delay:.1s;">
  <a href="{{ route('admin.import.preview') }}" class="btn btn-ghost">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Mapping
  </a>

  @if($validCount > 0)
  <form action="{{ route('admin.import.execute') }}" method="POST" style="margin-left:auto;">
    @csrf
    <button type="submit" class="btn btn-primary" onclick="return confirm('Import {{ $validCount }} {{ $importType }} records? This cannot be undone.')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      Import {{ $validCount }} {{ ucfirst($importType) }} Record{{ $validCount !== 1 ? 's' : '' }}
    </button>
  </form>
  @else
  <div style="margin-left:auto;display:flex;align-items:center;gap:8px;color:var(--text-tertiary);font-size:.83rem;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    No valid rows to import
  </div>
  @endif
</div>

@endsection
