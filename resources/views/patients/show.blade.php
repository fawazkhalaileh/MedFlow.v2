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
    <div class="card" id="notes-section">
      <div class="card-header">
        <div class="card-title">Notes</div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('add-note-form').classList.toggle('hidden')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Note
        </button>
      </div>

      {{-- Add Note Form --}}
      <div id="add-note-form" class="hidden" style="margin-bottom:16px;padding:14px;background:var(--bg-tertiary);border-radius:var(--radius-md);">
        <form method="POST" action="{{ route('notes.store') }}">
          @csrf
          <input type="hidden" name="notable_type" value="patient">
          <input type="hidden" name="notable_id"   value="{{ $patient->id }}">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
              <label style="font-size:.75rem;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:4px;">Note Type</label>
              <select name="note_type" class="filter-select" style="width:100%;">
                <option value="general">General</option>
                <option value="clinical">Clinical</option>
                <option value="treatment">Treatment</option>
                <option value="follow_up">Follow-up</option>
                <option value="financial">Financial</option>
                <option value="alert">Alert</option>
              </select>
            </div>
            <div style="display:flex;align-items:center;gap:16px;padding-top:22px;">
              <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;cursor:pointer;">
                <input type="checkbox" name="is_flagged" value="1"> Flag as Alert
              </label>
              <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;cursor:pointer;">
                <input type="checkbox" name="is_private" value="1"> Private
              </label>
            </div>
          </div>
          <textarea name="content" rows="3" placeholder="Write your note here..." style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;resize:vertical;background:var(--bg-secondary);" required maxlength="3000"></textarea>
          <div style="display:flex;gap:8px;margin-top:8px;justify-content:flex-end;">
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('add-note-form').classList.add('hidden')">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Save Note</button>
          </div>
        </form>
      </div>

      {{-- Existing Notes --}}
      @forelse($patient->notes as $note)
      <div style="padding:12px 0;border-bottom:1px solid var(--border-light);display:flex;gap:12px;" id="note-{{ $note->id }}">
        <div style="width:8px;height:8px;border-radius:50%;background:{{ $note->is_flagged ? 'var(--danger)' : 'var(--accent)' }};flex-shrink:0;margin-top:6px;"></div>
        <div style="flex:1;min-width:0;">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">
            <span class="badge badge-gray" style="font-size:.68rem;">{{ ucfirst(str_replace('_',' ',$note->note_type)) }}</span>
            @if($note->is_flagged) <span class="badge badge-red" style="font-size:.68rem;">Alert</span> @endif
            @if($note->is_private) <span class="badge badge-yellow" style="font-size:.68rem;">Private</span> @endif
            <span style="font-size:.73rem;color:var(--text-tertiary);margin-left:auto;">{{ $note->created_at->diffForHumans() }}</span>
          </div>

          {{-- View Mode --}}
          <div id="note-view-{{ $note->id }}">
            <p style="font-size:.85rem;line-height:1.5;color:var(--text-primary);">{{ $note->content }}</p>
            <div style="display:flex;gap:8px;margin-top:6px;">
              <button type="button" class="btn btn-ghost btn-sm" style="font-size:.73rem;padding:2px 8px;" onclick="toggleNoteEdit({{ $note->id }})">Edit</button>
              <form method="POST" action="{{ route('notes.destroy', $note) }}" onsubmit="return confirm('Delete this note?')" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm" style="font-size:.73rem;padding:2px 8px;color:var(--danger);">Delete</button>
              </form>
            </div>
          </div>

          {{-- Edit Mode --}}
          <div id="note-edit-{{ $note->id }}" class="hidden">
            <form method="POST" action="{{ route('notes.update', $note) }}">
              @csrf @method('PUT')
              <select name="note_type" class="filter-select" style="width:100%;margin-bottom:8px;">
                @foreach(['general','clinical','treatment','follow_up','financial','alert'] as $nt)
                <option value="{{ $nt }}" {{ $note->note_type === $nt ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$nt)) }}</option>
                @endforeach
              </select>
              <textarea name="content" rows="3" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;resize:vertical;background:var(--bg-secondary);margin-bottom:6px;" maxlength="3000" required>{{ $note->content }}</textarea>
              <div style="display:flex;align-items:center;gap:14px;margin-bottom:8px;">
                <label style="display:flex;align-items:center;gap:5px;font-size:.8rem;cursor:pointer;">
                  <input type="checkbox" name="is_flagged" value="1" {{ $note->is_flagged ? 'checked' : '' }}> Flag
                </label>
                <label style="display:flex;align-items:center;gap:5px;font-size:.8rem;cursor:pointer;">
                  <input type="checkbox" name="is_private" value="1" {{ $note->is_private ? 'checked' : '' }}> Private
                </label>
              </div>
              <div style="display:flex;gap:8px;">
                <button type="button" class="btn btn-ghost btn-sm" style="font-size:.73rem;" onclick="toggleNoteEdit({{ $note->id }})">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm" style="font-size:.73rem;">Update</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      @empty
      <p style="color:var(--text-tertiary);font-size:.84rem;padding:12px 0;">No notes yet. Add the first one above.</p>
      @endforelse
    </div>

    @push('scripts')
    <script>
    function toggleNoteEdit(id) {
      document.getElementById('note-view-'+id).classList.toggle('hidden');
      document.getElementById('note-edit-'+id).classList.toggle('hidden');
    }
    </script>
    <style>.hidden { display: none !important; }</style>
    @endpush

  </div>

  {{-- Right Column --}}
  <div style="display:flex;flex-direction:column;gap:18px;">

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
