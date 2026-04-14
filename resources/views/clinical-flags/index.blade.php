@extends('layouts.app')

@section('title', 'Clinical Flag Library - MedFlow CRM')
@section('breadcrumb', 'Clinical Flag Library')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Clinical Flag Library</h1>
    <p class="page-subtitle">Manage preconfigured clinical flags that can be assigned to patients.</p>
  </div>
  <div class="header-actions">
    <button type="button" class="btn btn-primary" onclick="document.getElementById('create-flag-form').classList.toggle('hidden')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add New Flag
    </button>
  </div>
</div>

@if(session('success'))
<div style="margin-bottom:16px;padding:12px 16px;background:var(--success-light);border:1px solid #6ee7b7;border-radius:var(--radius-md);color:#065f46;font-size:.875rem;">
  {{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="margin-bottom:16px;padding:12px 16px;background:var(--danger-light);border:1px solid #fca5a5;border-radius:var(--radius-md);color:#991b1b;font-size:.875rem;">
  {{ session('error') }}
</div>
@endif

{{-- Create Flag Form --}}
<div id="create-flag-form" class="hidden animate-in" style="margin-bottom:24px;">
  <div class="card">
    <div class="card-title" style="margin-bottom:16px;">New Clinical Flag</div>
    <form method="POST" action="{{ route('clinical-flags.store') }}">
      @csrf
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;">
        <div>
          <label style="font-size:.75rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:4px;">{{ __('Flag Name') }} <span style="color:var(--danger);">*</span></label>
          <input type="text" name="name" required maxlength="80" placeholder="e.g. Allergic to Penicillin"
            style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;background:var(--bg-secondary);">
        </div>
        <div>
          <label style="font-size:.75rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:4px;">Category <span style="color:var(--danger);">*</span></label>
          <select name="category" class="filter-select" style="width:100%;">
            <option value="general">General</option>
            <option value="medical">Medical</option>
            <option value="allergy">Allergy</option>
            <option value="lifestyle">Lifestyle</option>
            <option value="alert">Alert</option>
          </select>
        </div>
        <div>
          <label style="font-size:.75rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:4px;">Badge Color</label>
          <div style="display:flex;align-items:center;gap:8px;">
            <input type="color" name="color" value="#dc2626"
              style="width:42px;height:36px;padding:2px;border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;background:var(--bg-secondary);">
            <span style="font-size:.78rem;color:var(--text-tertiary);">Pick a color</span>
          </div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;align-items:end;">
        <div>
          <label style="font-size:.75rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:4px;">Icon (emoji or text)</label>
          <input type="text" name="icon" maxlength="10" placeholder="⚠️"
            style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;background:var(--bg-secondary);">
        </div>
        <div>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding-top:20px;">
            <input type="checkbox" name="requires_detail" value="1" id="create_requires_detail"
              onchange="document.getElementById('create_placeholder_wrap').classList.toggle('hidden', !this.checked)">
            <span style="font-size:.84rem;font-weight:500;">Requires detail input?</span>
          </label>
        </div>
        <div id="create_placeholder_wrap" class="hidden">
          <label style="font-size:.75rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:4px;">Detail Placeholder</label>
          <input type="text" name="detail_placeholder" maxlength="120" placeholder="e.g. Specify allergen..."
            style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;background:var(--bg-secondary);">
        </div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;">
        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('create-flag-form').classList.add('hidden')">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-primary btn-sm">Create Flag</button>
      </div>
    </form>
  </div>
</div>

{{-- Flags Table --}}
@php
  $categoryLabels = [
    'allergy'   => ['label' => 'Allergy',   'badge' => 'badge-red'],
    'medical'   => ['label' => 'Medical',   'badge' => 'badge-yellow'],
    'lifestyle' => ['label' => 'Lifestyle', 'badge' => 'badge-purple'],
    'alert'     => ['label' => 'Alert',     'badge' => 'badge-red'],
    'general'   => ['label' => 'General',   'badge' => 'badge-gray'],
  ];
@endphp

<div class="card animate-in" style="animation-delay:.05s;padding:0;">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
    <div class="card-title">All Clinical Flags</div>
    <span class="badge badge-gray">{{ $flags->flatten()->count() }} total</span>
  </div>

  @if($flags->isEmpty())
  <div class="empty-state" style="padding:48px;">
    <p>No clinical flags yet. Add your first flag above.</p>
  </div>
  @else
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:50px;">Icon</th>
          <th>Name</th>
          <th>Category</th>
          <th>Color</th>
          <th>Needs Detail?</th>
          <th>Patients</th>
          <th>{{ __('Active') }}</th>
          <th style="width:140px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($flags as $category => $categoryFlags)
          @foreach($categoryFlags as $flag)
          <tr id="row-{{ $flag->id }}">
            {{-- Normal row view --}}
            <td style="font-size:1.2rem;text-align:center;">{{ $flag->icon ?? '' }}</td>
            <td style="font-weight:500;font-size:.875rem;">{{ $flag->name }}</td>
            <td>
              @php $catInfo = $categoryLabels[$flag->category] ?? ['label' => ucfirst($flag->category), 'badge' => 'badge-gray']; @endphp
              <span class="badge {{ $catInfo['badge'] }}">{{ $catInfo['label'] }}</span>
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:20px;height:20px;border-radius:4px;background:{{ $flag->color }};flex-shrink:0;border:1px solid rgba(0,0,0,.1);"></div>
                <span style="font-size:.78rem;color:var(--text-tertiary);font-family:monospace;">{{ $flag->color }}</span>
              </div>
            </td>
            <td>
              @if($flag->requires_detail)
                <span class="badge badge-cyan" style="font-size:.72rem;">Yes</span>
                @if($flag->detail_placeholder)
                  <div style="font-size:.72rem;color:var(--text-tertiary);margin-top:2px;">{{ $flag->detail_placeholder }}</div>
                @endif
              @else
                <span style="color:var(--text-tertiary);font-size:.82rem;">—</span>
              @endif
            </td>
            <td>
              <span class="badge badge-gray">{{ $flag->patients_count }}</span>
            </td>
            <td>
              @if($flag->is_active)
                <span class="badge badge-green">{{ __('Active') }}</span>
              @else
                <span class="badge badge-gray">{{ __('Inactive') }}</span>
              @endif
            </td>
            <td>
              <div style="display:flex;gap:6px;">
                <button type="button" class="btn btn-ghost btn-sm" style="font-size:.72rem;padding:3px 10px;"
                  onclick="toggleFlagEdit({{ $flag->id }})">{{ __('Edit') }}</button>
                <form method="POST" action="{{ route('clinical-flags.destroy', $flag) }}"
                  onsubmit="return confirm('Delete flag &quot;{{ addslashes($flag->name) }}&quot;? This will remove it from all assigned patients.')" style="display:inline;">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-ghost btn-sm" style="font-size:.72rem;padding:3px 10px;color:var(--danger);">{{ __('Delete') }}</button>
                </form>
              </div>
            </td>
          </tr>
          {{-- Inline Edit Row --}}
          <tr id="edit-row-{{ $flag->id }}" class="hidden" style="background:var(--bg-tertiary);">
            <td colspan="8" style="padding:16px 20px;">
              <form method="POST" action="{{ route('clinical-flags.update', $flag) }}">
                @csrf @method('PUT')
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 80px;gap:12px;margin-bottom:12px;align-items:end;">
                  <div>
                    <label style="font-size:.72rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:3px;">Name</label>
                    <input type="text" name="name" value="{{ $flag->name }}" required maxlength="80"
                      style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.83rem;font-family:inherit;background:var(--bg-secondary);">
                  </div>
                  <div>
                    <label style="font-size:.72rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:3px;">Category</label>
                    <select name="category" class="filter-select" style="width:100%;font-size:.83rem;">
                      @foreach(['general','medical','allergy','lifestyle','alert'] as $cat)
                      <option value="{{ $cat }}" {{ $flag->category === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <label style="font-size:.72rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:3px;">Icon</label>
                    <input type="text" name="icon" value="{{ $flag->icon }}" maxlength="10"
                      style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.83rem;font-family:inherit;background:var(--bg-secondary);">
                  </div>
                  <div>
                    <label style="font-size:.72rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:3px;">Color</label>
                    <input type="color" name="color" value="{{ $flag->color }}"
                      style="width:100%;height:36px;padding:2px;border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;background:var(--bg-secondary);">
                  </div>
                </div>
                <div style="display:grid;grid-template-columns:auto 1fr auto;gap:14px;align-items:center;margin-bottom:12px;">
                  <label style="display:flex;align-items:center;gap:7px;cursor:pointer;white-space:nowrap;">
                    <input type="checkbox" name="requires_detail" value="1" {{ $flag->requires_detail ? 'checked' : '' }}
                      id="edit_req_{{ $flag->id }}"
                      onchange="document.getElementById('edit_ph_{{ $flag->id }}').classList.toggle('hidden', !this.checked)">
                    <span style="font-size:.83rem;">Requires detail</span>
                  </label>
                  <div id="edit_ph_{{ $flag->id }}" class="{{ $flag->requires_detail ? '' : 'hidden' }}">
                    <input type="text" name="detail_placeholder" value="{{ $flag->detail_placeholder }}" maxlength="120"
                      placeholder="Detail placeholder text..."
                      style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.83rem;font-family:inherit;background:var(--bg-secondary);">
                  </div>
                  <label style="display:flex;align-items:center;gap:7px;cursor:pointer;white-space:nowrap;">
                    <input type="checkbox" name="is_active" value="1" {{ $flag->is_active ? 'checked' : '' }}>
                    <span style="font-size:.83rem;">{{ __('Active') }}</span>
                  </label>
                </div>
                <div style="display:flex;gap:8px;">
                  <button type="button" class="btn btn-ghost btn-sm" style="font-size:.73rem;" onclick="toggleFlagEdit({{ $flag->id }})">{{ __('Cancel') }}</button>
                  <button type="submit" class="btn btn-primary btn-sm" style="font-size:.73rem;">Save Changes</button>
                </div>
              </form>
            </td>
          </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>
  </div>
  @endif
</div>

@push('scripts')
<script>
function toggleFlagEdit(id) {
  document.getElementById('edit-row-' + id).classList.toggle('hidden');
}
</script>
<style>.hidden { display: none !important; }</style>
@endpush
@endsection
