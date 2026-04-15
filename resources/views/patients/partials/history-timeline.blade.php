<div class="card animate-in" style="{{ empty($fullPage) ? 'margin-bottom:18px;' : '' }}">
  <div class="card-header">
    <div>
      <div class="card-title">History Timeline</div>
      <div class="card-subtitle">Appointments, status changes, sessions, package activity, payments, follow-ups, notes, and attachments.</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a href="{{ route('reports.patients.history', $patient) }}" class="btn btn-secondary btn-sm">Open Full History</a>
      <a href="{{ route('reports.patients.history.export', [$patient, 'format' => 'pdf']) }}" class="btn btn-primary btn-sm">Export PDF</a>
    </div>
  </div>

  <div style="display:grid;gap:12px;">
    @forelse($timeline as $item)
    <div style="padding:14px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--bg-secondary);">
      <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
        <div>
          <div style="font-weight:600;margin-bottom:4px;">{{ $item['title'] }}</div>
          <div style="font-size:.84rem;color:var(--text-secondary);">{{ $item['summary'] }}</div>
        </div>
        <div style="text-align:right;font-size:.76rem;color:var(--text-tertiary);white-space:nowrap;">
          <div>{{ optional($item['occurred_at'])->format('d M Y, h:i A') }}</div>
          @if(!empty($item['author']))<div>By {{ $item['author'] }}</div>@endif
        </div>
      </div>

      @if(!empty($item['details']))
      <div style="margin-top:10px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;">
        @foreach($item['details'] as $label => $value)
          @continue(blank($value))
          <div style="padding:8px 10px;background:var(--bg-tertiary);border-radius:var(--radius-sm);">
            <div style="font-size:.72rem;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:.4px;">{{ \Illuminate\Support\Str::headline((string) $label) }}</div>
            <div style="font-size:.83rem;color:var(--text-primary);margin-top:3px;word-break:break-word;">
              @if(is_array($value) || $value instanceof \Illuminate\Support\Collection)
                {{ collect($value)->filter()->implode(', ') }}
              @else
                {{ $value }}
              @endif
            </div>
          </div>
        @endforeach
      </div>
      @endif
    </div>
    @empty
    <div class="empty-state">
      <p>No history items are available for this patient within your current role visibility.</p>
    </div>
    @endforelse
  </div>
</div>
