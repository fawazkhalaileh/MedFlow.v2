@extends('layouts.app')

@section('title', 'Import Result - MedFlow CRM')
@section('breadcrumb', 'Data Import')

@section('content')

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Import Result</h1>
    <p class="page-subtitle">{{ $log->filename }} &mdash; {{ $log->created_at->format('M d, Y H:i') }}</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('admin.import.index') }}" class="btn btn-ghost">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="15 18 9 12 15 6"/></svg>
      Back to Import
    </a>
    <a href="{{ route('admin.import.index') }}" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      Import Again
    </a>
  </div>
</div>

{{-- STATUS BANNER --}}
<div class="animate-in" style="animation-delay:.04s;">
  @if($log->status === 'completed')
  <div style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);border-radius:var(--radius);padding:20px 24px;display:flex;align-items:center;gap:16px;margin-bottom:20px;">
    <div style="width:48px;height:48px;border-radius:50%;background:rgba(16,185,129,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="width:24px;height:24px;"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div>
      <div style="font-size:1.05rem;font-weight:600;color:#10b981;">Import Completed Successfully</div>
      <div style="font-size:.82rem;color:var(--text-secondary);margin-top:2px;">
        {{ $log->imported }} record{{ $log->imported !== 1 ? 's' : '' }} imported from {{ $log->filename }}
      </div>
    </div>
  </div>
  @else
  <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:var(--radius);padding:20px 24px;display:flex;align-items:center;gap:16px;margin-bottom:20px;">
    <div style="width:48px;height:48px;border-radius:50%;background:rgba(239,68,68,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <svg viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" style="width:24px;height:24px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    </div>
    <div>
      <div style="font-size:1.05rem;font-weight:600;color:var(--danger);">Import {{ ucfirst($log->status) }}</div>
      <div style="font-size:.82rem;color:var(--text-secondary);margin-top:2px;">{{ $log->filename }}</div>
    </div>
  </div>
  @endif
</div>

{{-- STATS --}}
<div class="kpi-grid animate-in" style="animation-delay:.06s;margin-bottom:20px;">

  <div class="kpi-card">
    <div class="kpi-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/></svg></div>
    <div class="kpi-label">Total Rows</div>
    <div class="kpi-value">{{ $log->total_rows }}</div>
    <div class="kpi-change neutral">processed</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon" style="color:var(--success);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
    <div class="kpi-label">Imported</div>
    <div class="kpi-value" style="color:var(--success);">{{ $log->imported }}</div>
    <div class="kpi-change up">records added</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon" style="color:var(--warning);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg></div>
    <div class="kpi-label">Skipped</div>
    <div class="kpi-value" style="color:var(--warning);">{{ $log->skipped }}</div>
    <div class="kpi-change neutral">duplicates or errors</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-icon" style="{{ $log->errors > 0 ? 'color:var(--danger);' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    </div>
    <div class="kpi-label">Errors</div>
    <div class="kpi-value" style="{{ $log->errors > 0 ? 'color:var(--danger);' : '' }}">{{ $log->errors }}</div>
    <div class="kpi-change {{ $log->errors > 0 ? 'down' : 'neutral' }}">validation failures</div>
  </div>

</div>

{{-- META INFO --}}
<div class="card animate-in" style="animation-delay:.08s;margin-bottom:20px;">
  <div class="card-header">
    <div class="card-title">Import Details</div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;padding:4px 0;">
    <div>
      <div style="font-size:.72rem;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Type</div>
      <div style="font-size:.85rem;font-weight:500;text-transform:capitalize;">{{ $log->import_type }}</div>
    </div>
    <div>
      <div style="font-size:.72rem;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">File</div>
      <div style="font-size:.85rem;font-weight:500;">{{ $log->filename }}</div>
    </div>
    <div>
      <div style="font-size:.72rem;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Imported By</div>
      <div style="font-size:.85rem;font-weight:500;">{{ $log->user?->first_name }} {{ $log->user?->last_name }}</div>
    </div>
    <div>
      <div style="font-size:.72rem;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Date</div>
      <div style="font-size:.85rem;font-weight:500;">{{ $log->created_at->format('M d, Y \a\t H:i') }}</div>
    </div>
  </div>
</div>

{{-- ERROR DETAILS --}}
@if($log->errors > 0 && is_array($log->error_details) && count($log->error_details) > 0)
<div class="card animate-in" style="animation-delay:.1s;padding:0;margin-bottom:24px;">
  <div style="padding:14px 20px;border-bottom:1px solid var(--border);">
    <div class="card-title" style="color:var(--danger);">Error Details</div>
    <div style="font-size:.75rem;color:var(--text-tertiary);margin-top:2px;">Rows that were skipped during import</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:80px;">Row #</th>
          <th style="width:150px;">Field</th>
          <th>Reason</th>
        </tr>
      </thead>
      <tbody>
        @foreach($log->error_details as $err)
        <tr>
          <td style="font-size:.8rem;font-weight:500;color:var(--danger);">Row {{ $err['row_number'] ?? '?' }}</td>
          <td>
            <span style="font-family:monospace;font-size:.78rem;background:var(--bg-tertiary);padding:2px 6px;border-radius:4px;">{{ $err['field'] ?? '—' }}</span>
          </td>
          <td style="font-size:.8rem;color:var(--text-secondary);">{{ $err['message'] ?? '—' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

@endsection
