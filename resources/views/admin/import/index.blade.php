@extends('layouts.app')

@section('title', 'Data Import - MedFlow CRM')
@section('breadcrumb', 'Data Import')

@section('content')

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ __('Data Import') }}</h1>
    <p class="page-subtitle">Import data from your old system into MedFlow CRM</p>
  </div>
</div>

{{-- FLASH MESSAGES --}}
@if(session('success'))
<div class="alert alert-success animate-in" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger animate-in" style="margin-bottom:16px;">{{ session('error') }}</div>
@endif

{{-- IMPORT TYPE CARDS --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;" class="animate-in" style="animation-delay:.04s;">

  {{-- PATIENTS CARD --}}
  <div class="card" style="padding:0;">
    <div style="padding:20px 24px 16px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,var(--accent),#6366f1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px;height:20px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
          <div style="font-size:1rem;font-weight:600;">Import Patients</div>
          <div style="font-size:.78rem;color:var(--text-tertiary);">Bulk register patients from CSV</div>
        </div>
      </div>
      <p style="font-size:.82rem;color:var(--text-secondary);margin-bottom:16px;line-height:1.5;">
        Import patient records including name, contact info, date of birth, emergency contacts, and more.
        Duplicate emails are automatically skipped.
      </p>
      <a href="{{ route('admin.import.template', 'patients') }}" style="font-size:.78rem;color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:5px;margin-bottom:16px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download patients template
      </a>
    </div>
    <div style="padding:0 24px 20px;">
      <form action="{{ route('admin.import.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="import_type" value="patients">
        <div style="border:2px dashed var(--border);border-radius:var(--radius-sm);padding:16px;text-align:center;background:var(--bg-secondary);margin-bottom:12px;cursor:pointer;" onclick="document.getElementById('patients-file').click()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:28px;height:28px;color:var(--text-tertiary);margin-bottom:6px;display:block;margin:0 auto 6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <div style="font-size:.8rem;font-weight:500;">Click to select file</div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">CSV or XLSX, max 10MB</div>
        </div>
        <input type="file" id="patients-file" name="file" accept=".csv,.xlsx,.txt" style="display:none;" onchange="this.closest('form').querySelector('.file-name-display').textContent=this.files[0]?.name||''">
        <div class="file-name-display" style="font-size:.75rem;color:var(--text-secondary);margin-bottom:10px;min-height:16px;"></div>
        <button type="submit" class="btn btn-primary" style="width:100%;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Upload &amp; Preview
        </button>
      </form>
      @error('file') <div style="color:var(--danger);font-size:.75rem;margin-top:6px;">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- APPOINTMENTS CARD --}}
  <div class="card" style="padding:0;">
    <div style="padding:20px 24px 16px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:20px;height:20px;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div>
          <div style="font-size:1rem;font-weight:600;">Import Appointments</div>
          <div style="font-size:.78rem;color:var(--text-tertiary);">Bulk add appointment history</div>
        </div>
      </div>
      <p style="font-size:.82rem;color:var(--text-secondary);margin-bottom:16px;line-height:1.5;">
        Import appointment records linked to existing patients. Patients are matched by code, email, or phone.
        Services are matched by name (case-insensitive).
      </p>
      <a href="{{ route('admin.import.template', 'appointments') }}" style="font-size:.78rem;color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:5px;margin-bottom:16px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download appointments template
      </a>
    </div>
    <div style="padding:0 24px 20px;">
      <form action="{{ route('admin.import.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="import_type" value="appointments">
        <div style="border:2px dashed var(--border);border-radius:var(--radius-sm);padding:16px;text-align:center;background:var(--bg-secondary);margin-bottom:12px;cursor:pointer;" onclick="document.getElementById('appt-file').click()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:28px;height:28px;color:var(--text-tertiary);margin-bottom:6px;display:block;margin:0 auto 6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <div style="font-size:.8rem;font-weight:500;">Click to select file</div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">CSV or XLSX, max 10MB</div>
        </div>
        <input type="file" id="appt-file" name="file" accept=".csv,.xlsx,.txt" style="display:none;" onchange="this.closest('form').querySelector('.file-name-display').textContent=this.files[0]?.name||''">
        <div class="file-name-display" style="font-size:.75rem;color:var(--text-secondary);margin-bottom:10px;min-height:16px;"></div>
        <button type="submit" class="btn btn-primary" style="width:100%;background:linear-gradient(135deg,#10b981,#059669);border-color:#10b981;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Upload &amp; Preview
        </button>
      </form>
    </div>
  </div>

</div>

{{-- RECENT IMPORTS TABLE --}}
<div class="card animate-in" style="animation-delay:.08s;padding:0;">
  <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
    <div class="card-title">{{ __('Import Logs') }}</div>
    <span style="font-size:.75rem;color:var(--text-tertiary);">Last 20 imports</span>
  </div>

  @if($logs->isEmpty())
  <div style="padding:40px;text-align:center;color:var(--text-tertiary);">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;margin:0 auto 10px;display:block;opacity:.4;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
    <div style="font-size:.85rem;">No imports yet</div>
  </div>
  @else
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>File</th>
          <th>Type</th>
          <th>Status</th>
          <th style="text-align:center;">Total</th>
          <th style="text-align:center;">Imported</th>
          <th style="text-align:center;">Errors</th>
          <th>Imported By</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($logs as $log)
        <tr>
          <td>
            <div style="font-size:.83rem;font-weight:500;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $log->filename }}</div>
          </td>
          <td>
            <span style="font-size:.75rem;font-weight:500;text-transform:capitalize;">{{ $log->import_type }}</span>
          </td>
          <td>
            @if($log->status === 'completed')
              <span class="badge badge-green">Completed</span>
            @elseif($log->status === 'failed')
              <span class="badge badge-red">Failed</span>
            @elseif($log->status === 'processing')
              <span class="badge badge-blue">Processing</span>
            @else
              <span class="badge" style="background:var(--bg-tertiary);color:var(--text-secondary);">Pending</span>
            @endif
          </td>
          <td style="text-align:center;font-size:.83rem;">{{ $log->total_rows }}</td>
          <td style="text-align:center;font-size:.83rem;color:var(--success);">{{ $log->imported }}</td>
          <td style="text-align:center;font-size:.83rem;color:{{ $log->errors > 0 ? 'var(--danger)' : 'var(--text-tertiary)' }};">{{ $log->errors }}</td>
          <td style="font-size:.8rem;color:var(--text-secondary);">{{ $log->user?->first_name }} {{ $log->user?->last_name }}</td>
          <td style="font-size:.78rem;color:var(--text-tertiary);">{{ $log->created_at->format('M d, Y H:i') }}</td>
          <td>
            <a href="{{ route('admin.import.show', $log) }}" class="btn btn-ghost btn-sm" style="font-size:.74rem;">View</a>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @endif
</div>

@endsection
