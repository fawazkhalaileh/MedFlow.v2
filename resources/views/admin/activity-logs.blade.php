@extends('layouts.app')
@section('title', 'Activity Logs - MedFlow CRM')
@section('breadcrumb', 'Admin / Activity Logs')
@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ __('Activity Logs') }}</h1>
    <p class="page-subtitle">Full audit trail of all system actions</p>
  </div>
  <a href="{{ route('admin.index') }}" class="btn btn-secondary">Back to Admin</a>
</div>

<div class="card animate-in" style="padding:0;">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>{{ __('Time') }}</th><th>{{ __('User') }}</th><th>{{ __('Actions') }}</th><th>{{ __('Name') }}</th><th>{{ __('Description') }}</th></tr>
      </thead>
      <tbody>
        @forelse($logs as $log)
        <tr>
          <td style="font-size:.78rem;color:var(--text-tertiary);white-space:nowrap;">{{ $log->created_at->format('d M Y H:i') }}</td>
          <td style="font-size:.83rem;">{{ $log->user?->first_name ?? 'System' }}</td>
          <td>
            <span class="badge {{ str_contains($log->action,'delete') ? 'badge-red' : (str_contains($log->action,'create') ? 'badge-green' : 'badge-gray') }}">
              {{ ucfirst($log->action) }}
            </span>
          </td>
          <td style="font-size:.82rem;color:var(--text-secondary);">{{ class_basename($log->model_type ?? '') }} #{{ $log->model_id }}</td>
          <td style="font-size:.78rem;color:var(--text-tertiary);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $log->description }}</td>
        </tr>
        @empty
        <tr><td colspan="5"><div class="empty-state"><p>No activity logs found</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($logs->hasPages())
  <div style="padding:14px 18px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
    <span style="font-size:.82rem;color:var(--text-secondary);">{{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}</span>
    <div style="display:flex;gap:6px;">
      @if(!$logs->onFirstPage())<a href="{{ $logs->previousPageUrl() }}" class="btn btn-secondary btn-sm">Prev</a>@endif
      @if($logs->hasMorePages())<a href="{{ $logs->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>@endif
    </div>
  </div>
  @endif
</div>
@endsection
