@extends('layouts.app')

@section('title', 'Map Columns - MedFlow CRM')
@section('breadcrumb', 'Data Import')

@section('content')

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Map Columns &mdash; {{ ucfirst($importType) }}</h1>
    <p class="page-subtitle">Match your CSV columns to MedFlow fields. Columns that don't match any field will be ignored.</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('admin.import.index') }}" class="btn btn-ghost">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="15 18 9 12 15 6"/></svg>
      Back
    </a>
  </div>
</div>

<form action="{{ route('admin.import.validate') }}" method="POST" class="animate-in">
  @csrf

  {{-- COLUMN MAPPING TABLE --}}
  <div class="card" style="padding:0;margin-bottom:20px;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);">
      <div class="card-title">Column Mapping</div>
      <div style="font-size:.78rem;color:var(--text-tertiary);margin-top:2px;">
        {{ count($headers) }} columns detected in your file
      </div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:35%;">CSV Column</th>
            <th style="width:25%;">Sample Value</th>
            <th style="width:40%;">Map to MedFlow Field</th>
          </tr>
        </thead>
        <tbody>
          @foreach($headers as $i => $header)
          <tr>
            <td>
              <div style="font-size:.83rem;font-weight:500;color:var(--text-primary);">{{ $header }}</div>
            </td>
            <td>
              <div style="font-size:.78rem;color:var(--text-tertiary);font-style:italic;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                {{ $sample[0][$i] ?? '—' }}
              </div>
            </td>
            <td>
              @php
                // Auto-detect matching system field
                $normalizeKey = fn($s) => strtolower(preg_replace('/[\s_\-]+/', '', $s));
                $normalized = $normalizeKey($header);
                $autoMatch = null;
                foreach ($systemFields as $fieldKey => $fieldLabel) {
                    if ($normalizeKey($fieldKey) === $normalized || $normalizeKey($fieldLabel) === $normalized) {
                        $autoMatch = $fieldKey;
                        break;
                    }
                }
              @endphp
              <select name="column_map[{{ $header }}]" class="form-select" style="font-size:.82rem;padding:5px 8px;">
                <option value="_skip">— skip this column —</option>
                @foreach($systemFields as $fieldKey => $fieldLabel)
                  <option value="{{ $fieldKey }}" {{ $autoMatch === $fieldKey ? 'selected' : '' }}>
                    {{ $fieldLabel }}
                  </option>
                @endforeach
              </select>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- PREVIEW TABLE --}}
  @if(count($sample) > 0)
  <div class="card" style="padding:0;margin-bottom:20px;">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);">
      <div class="card-title">Data Preview</div>
      <div style="font-size:.78rem;color:var(--text-tertiary);margin-top:2px;">First {{ count($sample) }} rows of your file</div>
    </div>
    <div style="overflow-x:auto;">
      <table style="font-size:.76rem;min-width:max-content;">
        <thead>
          <tr>
            <th style="font-size:.72rem;white-space:nowrap;">#</th>
            @foreach($headers as $h)
            <th style="font-size:.72rem;white-space:nowrap;max-width:140px;overflow:hidden;text-overflow:ellipsis;">{{ $h }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($sample as $rowIdx => $row)
          <tr>
            <td style="color:var(--text-tertiary);">{{ $rowIdx + 1 }}</td>
            @foreach($row as $cell)
            <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-secondary);">{{ $cell }}</td>
            @endforeach
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- SUBMIT --}}
  <div style="display:flex;gap:10px;justify-content:flex-end;padding-bottom:24px;">
    <a href="{{ route('admin.import.index') }}" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      Validate Import
    </button>
  </div>

</form>

@endsection
