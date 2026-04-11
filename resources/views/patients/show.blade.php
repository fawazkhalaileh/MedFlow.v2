@extends('layouts.app')

@section('title', $patient->full_name . ' - MedFlow CRM')
@section('breadcrumb', 'Patients / ' . $patient->full_name)

@section('content')
<div class="page-header animate-in">
  <div style="display:flex;align-items:center;gap:14px;">
    <div class="avatar" style="width:52px;height:52px;font-size:1.1rem;flex-shrink:0;background:linear-gradient(135deg,#{{ substr(md5($patient->first_name),0,6) }},#{{ substr(md5($patient->last_name ?? ''),0,6) }});">
      {{ strtoupper(substr($patient->first_name,0,1).substr($patient->last_name ?? '',0,1)) }}
    </div>
    <div>
      <h1 class="page-title" style="font-size:1.5rem;">{{ $patient->full_name }}</h1>
      <p class="page-subtitle">
        <span style="font-family:monospace;color:var(--accent);">{{ $patient->patient_code }}</span>
        &bull; {{ $patient->branch?->name ?? 'No branch' }}
        @if($patient->status === 'vip') &bull; <span class="badge badge-purple">VIP</span> @endif
        @if(!$patient->consent_given) &bull; <span class="badge badge-red">Consent Pending</span> @endif
      </p>
    </div>
  </div>
  <div class="header-actions">
    <a href="{{ route('patients.edit', $patient) }}" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Edit Patient
    </a>
    <a href="{{ route('patients.index') }}" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Back
    </a>
  </div>
</div>

<div class="grid-2-1 animate-in" style="animation-delay:.05s;align-items:start;">

  {{-- Left Column --}}
  <div style="display:flex;flex-direction:column;gap:18px;">

    {{-- Appointments --}}
    <div class="card" style="padding:0;">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <div class="card-title">Recent Appointments</div>
        <span class="badge badge-gray">{{ $patient->appointments->count() }} shown</span>
      </div>
      @if($patient->appointments->isEmpty())
      <div class="empty-state" style="padding:32px;"><p>No appointments yet</p></div>
      @else
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date & Time</th><th>Service</th><th>Staff</th><th>Status</th></tr></thead>
          <tbody>
            @foreach($patient->appointments as $appt)
            <tr>
              <td style="white-space:nowrap;font-size:.83rem;">{{ \Carbon\Carbon::parse($appt->scheduled_at)->format('d M Y, h:i A') }}</td>
              <td style="font-size:.83rem;color:var(--text-secondary);">{{ $appt->service?->name ?? '--' }}</td>
              <td style="font-size:.83rem;color:var(--text-secondary);">{{ $appt->assignedStaff?->first_name ?? '--' }}</td>
              <td>
                @php $sc = ['scheduled'=>'badge-blue','confirmed'=>'badge-cyan','completed'=>'badge-green','cancelled'=>'badge-red','no_show'=>'badge-gray'][$appt->status] ?? 'badge-gray'; @endphp
                <span class="badge {{ $sc }}">{{ ucfirst(str_replace('_',' ',$appt->status)) }}</span>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>

    {{-- Treatment Plans --}}
    @if($patient->treatmentPlans->isNotEmpty())
    <div class="card">
      <div class="card-header">
        <div class="card-title">Treatment Plans</div>
      </div>
      @foreach($patient->treatmentPlans as $plan)
      <div style="padding:12px 0;border-bottom:1px solid var(--border-light);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
          <div style="font-weight:500;font-size:.9rem;">{{ $plan->service?->name ?? $plan->name }}</div>
          <span class="badge {{ $plan->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($plan->status) }}</span>
        </div>
        <div style="margin-bottom:6px;">
          <div style="height:5px;background:var(--bg-tertiary);border-radius:3px;overflow:hidden;">
            <div style="height:100%;width:{{ $plan->progress_percent }}%;background:var(--accent);border-radius:3px;"></div>
          </div>
        </div>
        <div style="display:flex;gap:16px;font-size:.78rem;color:var(--text-tertiary);">
          <span>{{ $plan->completed_sessions }} / {{ $plan->total_sessions }} sessions</span>
          @if($plan->total_price)<span>AED {{ number_format($plan->total_price) }}</span>@endif
        </div>
      </div>
      @endforeach
    </div>
    @endif

    {{-- Notes --}}
    @php
      $me         = Auth::user();
      $canEditAll = $me->isSuperAdmin() || $me->isRole('branch_manager','doctor','nurse');

      // Role-specific note types available when adding a note
      $noteTypesForRole = match(true) {
        $me->isRole('secretary')                      => ['reception'=>'Reception Note','general'=>'General','alert'=>'Alert','follow_up'=>'Follow-Up'],
        $me->isRole('technician')                     => ['technician'=>'Technician Note','session'=>'Session Note','general'=>'General','alert'=>'Alert'],
        $me->isRole('doctor','nurse')                 => ['clinical'=>'Clinical Note','treatment_plan'=>'Treatment Plan','follow_up'=>'Follow-Up','internal'=>'Internal','alert'=>'Alert'],
        $me->isRole('finance')                        => ['general'=>'General','internal'=>'Internal'],
        default /* admin, branch_manager */           => \App\Models\Note::TYPES,
      };

      // Type badge colours
      $typeBadge = [
        'reception'      => 'badge-cyan',
        'clinical'       => 'badge-blue',
        'technician'     => 'badge-purple',
        'follow_up'      => 'badge-yellow',
        'internal'       => 'badge-gray',
        'alert'          => 'badge-red',
        'session'        => 'badge-green',
        'treatment_plan' => 'badge-blue',
        'general'        => 'badge-gray',
      ];
    @endphp

    <div class="card" id="notes-section">
      <div class="card-header">
        <div style="display:flex;align-items:center;gap:8px;">
          <div class="card-title">Notes</div>
          <span style="font-size:.73rem;background:var(--bg-tertiary);color:var(--text-tertiary);padding:2px 8px;border-radius:10px;font-weight:600;">{{ $patient->notes->count() }}</span>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('add-note-form').classList.toggle('hidden');this.innerText=this.innerText.trim()==='+ Add Note'?'✕ Cancel':'+ Add Note'">
          + Add Note
        </button>
      </div>

      {{-- ── Add Note Form ─────────────────────────────────── --}}
      <div id="add-note-form" class="hidden" style="margin-bottom:16px;padding:14px;background:var(--bg-tertiary);border-radius:var(--radius-md);border:1px solid var(--border);">
        <form method="POST" action="{{ route('notes.store') }}">
          @csrf
          <input type="hidden" name="notable_type" value="patient">
          <input type="hidden" name="notable_id"   value="{{ $patient->id }}">

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
              <label style="font-size:.73rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Type</label>
              <select name="note_type" class="filter-select" style="width:100%;font-size:.85rem;">
                @foreach($noteTypesForRole as $val => $label)
                  <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div style="display:flex;flex-direction:column;justify-content:flex-end;gap:8px;padding-bottom:2px;">
              <label style="display:flex;align-items:center;gap:7px;font-size:.83rem;cursor:pointer;user-select:none;">
                <input type="checkbox" name="is_flagged" value="1" style="accent-color:var(--danger);width:15px;height:15px;">
                <span>🚨 Flag as Alert</span>
              </label>
              <label style="display:flex;align-items:center;gap:7px;font-size:.83rem;cursor:pointer;user-select:none;">
                <input type="checkbox" name="is_private" value="1" style="accent-color:var(--warning);width:15px;height:15px;">
                <span>🔒 Private (only me)</span>
              </label>
            </div>
          </div>

          <textarea name="content" rows="3"
            placeholder="Write your note here…"
            style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.87rem;font-family:inherit;resize:vertical;background:var(--bg-secondary);transition:border-color .2s;outline:none;"
            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'"
            required maxlength="3000"></textarea>

          <div style="display:flex;gap:8px;margin-top:10px;justify-content:flex-end;">
            <button type="button" class="btn btn-ghost btn-sm"
              onclick="document.getElementById('add-note-form').classList.add('hidden');document.querySelector('[onclick*=add-note-form]').innerText='+ Add Note'">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary btn-sm">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><polyline points="20 6 9 17 4 12"/></svg>
              Save Note
            </button>
          </div>
        </form>
      </div>

      {{-- ── Notes List ─────────────────────────────────────── --}}
      @forelse($patient->notes as $note)
      @php
        // Private notes: only show to the author or elevated roles
        $canSee  = !$note->is_private
                || $note->created_by === $me->id
                || $canEditAll;
        // Can edit/delete: own note OR elevated role
        $canEdit = $note->created_by === $me->id || $canEditAll;
        $author  = $note->createdBy;
        $authorName = $author
            ? ($author->first_name ?? $author->name) . ($author->last_name ? ' '.$author->last_name : '')
            : 'System';
        $badgeClass = $typeBadge[$note->note_type] ?? 'badge-gray';
        $typeLabel  = \App\Models\Note::TYPES[$note->note_type] ?? ucfirst(str_replace('_',' ',$note->note_type));
      @endphp
      @if($canSee)
      <div style="padding:13px 0;border-bottom:1px solid var(--border-light);" id="note-{{ $note->id }}">

        {{-- View Mode --}}
        <div id="note-view-{{ $note->id }}">

          {{-- Header row --}}
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:7px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
              {{-- Coloured dot --}}
              <div style="width:7px;height:7px;border-radius:50%;background:{{ $note->is_flagged ? 'var(--danger)' : ($note->is_private ? 'var(--warning)' : 'var(--accent)') }};flex-shrink:0;"></div>
              <span class="badge {{ $badgeClass }}" style="font-size:.67rem;">{{ $typeLabel }}</span>
              @if($note->is_flagged) <span class="badge badge-red" style="font-size:.67rem;">🚨 Alert</span> @endif
              @if($note->is_private) <span class="badge badge-yellow" style="font-size:.67rem;">🔒 Private</span> @endif
            </div>
            <div style="font-size:.72rem;color:var(--text-tertiary);white-space:nowrap;flex-shrink:0;">
              {{ $note->created_at->diffForHumans() }}
            </div>
          </div>

          {{-- Body --}}
          <p style="font-size:.87rem;line-height:1.55;color:var(--text-primary);margin-bottom:8px;">{{ $note->content }}</p>

          {{-- Footer: author + actions --}}
          <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:6px;">
              <div class="avatar avatar-sm" style="width:22px;height:22px;font-size:.6rem;background:linear-gradient(135deg,#2563eb,#7c3aed);flex-shrink:0;">
                {{ strtoupper(substr($authorName,0,2)) }}
              </div>
              <span style="font-size:.74rem;color:var(--text-secondary);">{{ $authorName }}</span>
            </div>
            @if($canEdit)
            <div style="display:flex;gap:6px;">
              <button type="button"
                class="btn btn-ghost btn-sm"
                style="font-size:.72rem;padding:2px 9px;"
                onclick="toggleNoteEdit({{ $note->id }})">
                Edit
              </button>
              <form method="POST" action="{{ route('notes.destroy', $note) }}"
                onsubmit="return confirm('Delete this note?')" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm"
                  style="font-size:.72rem;padding:2px 9px;color:var(--danger);">
                  Delete
                </button>
              </form>
            </div>
            @endif
          </div>
        </div>

        {{-- Edit Mode --}}
        @if($canEdit)
        <div id="note-edit-{{ $note->id }}" class="hidden" style="margin-top:8px;padding:12px;background:var(--bg-tertiary);border-radius:var(--radius-md);">
          <form method="POST" action="{{ route('notes.update', $note) }}">
            @csrf @method('PUT')
            <select name="note_type" class="filter-select" style="width:100%;margin-bottom:8px;font-size:.85rem;">
              @foreach(\App\Models\Note::TYPES as $val => $label)
              <option value="{{ $val }}" {{ $note->note_type === $val ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
            <textarea name="content" rows="3"
              style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.86rem;font-family:inherit;resize:vertical;background:var(--bg-secondary);margin-bottom:8px;outline:none;"
              maxlength="3000" required>{{ $note->content }}</textarea>
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:10px;">
              <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;cursor:pointer;">
                <input type="checkbox" name="is_flagged" value="1" style="accent-color:var(--danger);" {{ $note->is_flagged ? 'checked' : '' }}> 🚨 Flag
              </label>
              <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;cursor:pointer;">
                <input type="checkbox" name="is_private" value="1" style="accent-color:var(--warning);" {{ $note->is_private ? 'checked' : '' }}> 🔒 Private
              </label>
            </div>
            <div style="display:flex;gap:8px;">
              <button type="button" class="btn btn-ghost btn-sm" onclick="toggleNoteEdit({{ $note->id }})">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm">Update Note</button>
            </div>
          </form>
        </div>
        @endif

      </div>
      @endif
      @empty
      <div style="padding:24px 0;text-align:center;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:32px;height:32px;color:var(--text-tertiary);margin:0 auto 8px;display:block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        <p style="color:var(--text-tertiary);font-size:.84rem;">No notes yet — be the first to add one.</p>
      </div>
      @endforelse
    </div>

    @push('scripts')
    <script>
    function toggleNoteEdit(id) {
      document.getElementById('note-view-'+id).classList.toggle('hidden');
      document.getElementById('note-edit-'+id).classList.toggle('hidden');
    }

    function handleFlagSelect(select) {
      var option = select.options[select.selectedIndex];
      var requiresDetail = option.getAttribute('data-requires-detail') === '1';
      var placeholder    = option.getAttribute('data-placeholder') || 'Enter detail...';
      var detailWrap     = document.getElementById('flag-detail-wrap');
      var detailInput    = document.getElementById('flag-detail-input');
      if (requiresDetail) {
        detailWrap.classList.remove('hidden');
        detailInput.placeholder = placeholder;
        detailInput.required = true;
      } else {
        detailWrap.classList.add('hidden');
        detailInput.required = false;
        detailInput.value = '';
      }
    }
    </script>
    <style>.hidden { display: none !important; }</style>
    @endpush

  </div>

  {{-- Right Column --}}
  <div style="display:flex;flex-direction:column;gap:18px;">

    {{-- Clinical Flags --}}
    <div class="card" id="clinical-flags-card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div class="card-title" style="margin:0;">Clinical Flags</div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('add-flag-form').classList.toggle('hidden')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Flag
        </button>
      </div>

      {{-- Assigned Flags --}}
      @if($patient->clinicalFlags->isEmpty())
        <p style="color:var(--text-tertiary);font-size:.83rem;margin-bottom:12px;">No clinical flags assigned yet.</p>
      @else
        <div style="display:flex;flex-wrap:wrap;gap:7px;margin-bottom:12px;">
          @foreach($patient->clinicalFlags as $flag)
          <div style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:999px;background:{{ $flag->color }}1a;border:1px solid {{ $flag->color }}55;max-width:100%;">
            @if($flag->icon)
              <span style="font-size:.9rem;line-height:1;">{{ $flag->icon }}</span>
            @endif
            <span style="font-size:.78rem;font-weight:600;color:{{ $flag->color }};">{{ $flag->name }}</span>
            @if($flag->pivot->detail)
              <span style="font-size:.75rem;color:var(--text-secondary);">— {{ $flag->pivot->detail }}</span>
            @endif
            <form method="POST" action="{{ route('patient-flags.remove', [$patient, $flag]) }}" style="display:inline;margin-left:2px;">
              @csrf @method('DELETE')
              <button type="submit" title="Remove flag" style="background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;color:{{ $flag->color }};opacity:.7;line-height:1;"
                onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.7">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:12px;height:12px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </form>
          </div>
          @endforeach
        </div>
      @endif

      {{-- Add Flag Inline Form --}}
      <div id="add-flag-form" class="hidden" style="padding:12px;background:var(--bg-tertiary);border-radius:var(--radius-md);margin-bottom:10px;">
        <form method="POST" action="{{ route('patient-flags.assign', $patient) }}">
          @csrf
          <div style="margin-bottom:10px;">
            <label style="font-size:.74rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:4px;">Select Flag</label>
            <select name="flag_id" id="flag-select" class="filter-select" style="width:100%;"
              onchange="handleFlagSelect(this)">
              <option value="">— choose a flag —</option>
              @php
                $flagsByCategory = $allFlags->groupBy('category');
                $catOrder = ['alert','allergy','medical','lifestyle','general'];
              @endphp
              @foreach($catOrder as $cat)
                @if($flagsByCategory->has($cat))
                <optgroup label="{{ ucfirst($cat) }}">
                  @foreach($flagsByCategory[$cat] as $f)
                  <option value="{{ $f->id }}"
                    data-requires-detail="{{ $f->requires_detail ? '1' : '0' }}"
                    data-placeholder="{{ $f->detail_placeholder ?? '' }}">
                    {{ $f->icon ? $f->icon . ' ' : '' }}{{ $f->name }}
                  </option>
                  @endforeach
                </optgroup>
                @endif
              @endforeach
            </select>
          </div>
          <div id="flag-detail-wrap" class="hidden" style="margin-bottom:10px;">
            <label style="font-size:.74rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:4px;">Detail</label>
            <input type="text" name="detail" id="flag-detail-input" maxlength="200"
              style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.83rem;font-family:inherit;background:var(--bg-secondary);"
              placeholder="e.g. Penicillin">
          </div>
          <div style="display:flex;gap:8px;justify-content:flex-end;">
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('add-flag-form').classList.add('hidden')">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Add Flag</button>
          </div>
        </form>
      </div>

      @if(Auth::user()->isRole('branch_manager') || Auth::user()->isSuperAdmin())
      <div style="margin-top:4px;">
        <a href="{{ route('clinical-flags.index') }}" style="font-size:.76rem;color:var(--accent);text-decoration:none;">
          Manage flag library →
        </a>
      </div>
      @endif
    </div>

    {{-- Personal Info --}}
    <div class="card">
      <div class="card-title" style="margin-bottom:14px;">Patient Information</div>
      <div style="display:flex;flex-direction:column;gap:9px;font-size:.84rem;">
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Phone</span>
          <span style="font-weight:500;">{{ $patient->phone }}</span>
        </div>
        @if($patient->email)
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Email</span>
          <span>{{ $patient->email }}</span>
        </div>
        @endif
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Gender</span>
          <span>{{ ucfirst($patient->gender ?? '--') }}</span>
        </div>
        @if($patient->date_of_birth)
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Age</span>
          <span>{{ $patient->age }} years</span>
        </div>
        @endif
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Nationality</span>
          <span>{{ $patient->nationality ?? '--' }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Source</span>
          <span>{{ ucfirst(str_replace('_',' ',$patient->source ?? '--')) }}</span>
        </div>
        <div style="border-top:1px solid var(--border);padding-top:9px;display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Consent</span>
          @if($patient->consent_given)
            <span class="badge badge-green">Given</span>
          @else
            <span class="badge badge-red">Pending</span>
          @endif
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Registered</span>
          <span>{{ $patient->created_at->format('d M Y') }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Last Visit</span>
          <span>{{ $patient->last_visit_at?->format('d M Y') ?? 'Never' }}</span>
        </div>
      </div>
    </div>

    {{-- Medical Info --}}
    @if($patient->medicalInfo)
    <div class="card">
      <div class="card-title" style="margin-bottom:12px;">Medical & Clinical</div>
      @if($patient->medicalInfo->skin_type)
      <div style="margin-bottom:10px;">
        <div style="font-size:.75rem;color:var(--text-tertiary);margin-bottom:3px;">Skin Type (Fitzpatrick)</div>
        <span class="badge badge-cyan">Type {{ $patient->medicalInfo->skin_type }}</span>
        @if($patient->medicalInfo->skin_tone)
        <span class="badge badge-gray" style="margin-left:4px;">{{ ucfirst($patient->medicalInfo->skin_tone) }}</span>
        @endif
      </div>
      @endif
      @if($patient->medicalInfo->allergies)
      <div style="margin-bottom:8px;">
        <div style="font-size:.75rem;color:var(--text-tertiary);margin-bottom:3px;">Allergies</div>
        <div style="font-size:.84rem;">{{ $patient->medicalInfo->allergies }}</div>
      </div>
      @endif
      @if($patient->medicalInfo->contraindications)
      <div style="margin-bottom:8px;padding:8px;background:var(--danger-light);border-radius:var(--radius-sm);border:1px solid #fca5a5;">
        <div style="font-size:.75rem;color:var(--danger);font-weight:600;margin-bottom:3px;">Contraindications</div>
        <div style="font-size:.82rem;color:#991b1b;">{{ $patient->medicalInfo->contraindications }}</div>
      </div>
      @endif
      @if($patient->medicalInfo->is_pregnant || $patient->medicalInfo->has_pacemaker || $patient->medicalInfo->has_metal_implants)
      <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:5px;">
        @if($patient->medicalInfo->is_pregnant)  <span class="badge badge-red">Pregnant</span> @endif
        @if($patient->medicalInfo->has_pacemaker) <span class="badge badge-red">Pacemaker</span> @endif
        @if($patient->medicalInfo->has_metal_implants) <span class="badge badge-yellow">Metal Implants</span> @endif
      </div>
      @endif
    </div>
    @endif

    {{-- Follow-ups --}}
    @if($patient->followUps->isNotEmpty())
    <div class="card">
      <div class="card-title" style="margin-bottom:12px;">Follow-ups</div>
      @foreach($patient->followUps as $fu)
      <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-light);">
        <div>
          <div style="font-size:.84rem;font-weight:500;">{{ ucfirst($fu->type) }}</div>
          <div style="font-size:.74rem;color:var(--text-tertiary);">Due {{ \Carbon\Carbon::parse($fu->due_date)->format('d M Y') }}</div>
        </div>
        <span class="badge {{ $fu->status === 'completed' ? 'badge-green' : ($fu->status === 'pending' ? 'badge-yellow' : 'badge-gray') }}">{{ ucfirst($fu->status) }}</span>
      </div>
      @endforeach
    </div>
    @endif

  </div>
</div>
@endsection
