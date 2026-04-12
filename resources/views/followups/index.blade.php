@extends('layouts.app')

@section('title', 'Follow-ups - MedFlow CRM')
@section('breadcrumb', 'Follow-ups')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Follow-ups</h1>
    <p class="page-subtitle">Track and manage patient follow-up tasks</p>
  </div>
  <div style="display:flex;gap:8px;align-items:center;">
    <span class="badge badge-yellow" style="padding:6px 14px;font-size:.82rem;">{{ $followups->total() }} {{ request('status','pending') === 'pending' ? 'pending' : 'total' }}</span>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('create-followup-modal').classList.remove('hidden')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Follow-up
    </button>
  </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('followups.index') }}">
<div class="filter-bar animate-in" style="animation-delay:.05s">
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="pending"   {{ (request('status','pending')) === 'pending'   ? 'selected' : '' }}>Pending</option>
    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
    <option value=""          {{ request('status') === ''          ? 'selected' : '' }}>All Statuses</option>
  </select>
  <select name="type" class="filter-select" onchange="this.form.submit()">
    <option value="">All Types</option>
    <option value="call"        {{ request('type') === 'call'        ? 'selected' : '' }}>📞 Call</option>
    <option value="appointment" {{ request('type') === 'appointment' ? 'selected' : '' }}>📅 Appointment</option>
    <option value="check_in"    {{ request('type') === 'check_in'    ? 'selected' : '' }}>✅ Check-in</option>
    <option value="email"       {{ request('type') === 'email'       ? 'selected' : '' }}>✉️ Email</option>
  </select>
  <select name="assigned_to" class="filter-select" onchange="this.form.submit()">
    <option value="">All Staff</option>
    @foreach($staff as $s)
    <option value="{{ $s->id }}" {{ request('assigned_to') == $s->id ? 'selected' : '' }}>{{ $s->first_name }} {{ $s->last_name }}</option>
    @endforeach
  </select>
  @if(Auth::user()->isSuperAdmin())
  <select name="branch" class="filter-select" onchange="this.form.submit()">
    <option value="">All Branches</option>
    @foreach($branches as $b)
    <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
    @endforeach
  </select>
  @endif
  @if(request()->anyFilled(['type','branch','assigned_to']) || request('status','pending') !== 'pending')
  <a href="{{ route('followups.index') }}" class="btn btn-ghost btn-sm">Reset</a>
  @endif
</div>
</form>

<div class="card animate-in" style="padding:0;animation-delay:.1s">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Patient</th>
          <th>Type</th>
          <th>Due Date</th>
          <th>Assigned To</th>
          <th>Notes</th>
          <th>Status</th>
          <th style="width:120px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($followups as $fu)
        @php $overdue = $fu->due_date->lt(today()) && $fu->status === 'pending'; @endphp
        <tr style="{{ $overdue ? 'background:rgba(220,38,38,0.025);' : '' }}">
          <td>
            <a href="{{ route('patients.show', $fu->patient_id) }}" style="font-weight:500;color:var(--accent);text-decoration:none;">
              {{ $fu->patient?->full_name ?? 'Unknown' }}
            </a>
            <div style="font-size:.74rem;color:var(--text-tertiary);">{{ $fu->patient?->phone }}</div>
          </td>
          <td>
            @php
              $typeIcons  = ['call'=>'📞','appointment'=>'📅','check_in'=>'✅','email'=>'✉️'];
              $typeColors = ['call'=>'badge-blue','appointment'=>'badge-green','check_in'=>'badge-cyan','email'=>'badge-purple'];
              $tc = $typeColors[$fu->type] ?? 'badge-gray';
            @endphp
            <span class="badge {{ $tc }}">{{ ($typeIcons[$fu->type] ?? '') . ' ' . ucfirst(str_replace('_',' ',$fu->type)) }}</span>
          </td>
          <td>
            <div style="font-weight:{{ $overdue ? '600' : '400' }};color:{{ $overdue ? 'var(--danger)' : 'var(--text-primary)' }};font-size:.84rem;">
              {{ $fu->due_date->format('d M Y') }}
            </div>
            <div style="font-size:.73rem;color:{{ $overdue ? 'var(--danger)' : 'var(--text-tertiary)' }};">
              {{ $overdue ? '⚠️ OVERDUE' : $fu->due_date->diffForHumans() }}
            </div>
          </td>
          <td style="color:var(--text-secondary);font-size:.83rem;">
            {{ $fu->assignedTo ? $fu->assignedTo->first_name . ' ' . $fu->assignedTo->last_name : 'Unassigned' }}
          </td>
          <td style="max-width:200px;">
            @if($fu->notes)
            <div style="font-size:.8rem;color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:190px;" title="{{ $fu->notes }}">
              {{ $fu->notes }}
            </div>
            @else
            <span style="color:var(--text-tertiary);font-size:.8rem;">—</span>
            @endif
            @if($fu->outcome)
            <div style="font-size:.76rem;color:var(--success);margin-top:2px;" title="{{ $fu->outcome }}">
              ✓ {{ \Illuminate\Support\Str::limit($fu->outcome, 40) }}
            </div>
            @endif
          </td>
          <td>
            @php
              $sc = $overdue ? 'badge-red' : match($fu->status) {
                'pending'   => 'badge-yellow',
                'completed' => 'badge-green',
                'cancelled' => 'badge-gray',
                default     => 'badge-gray',
              };
            @endphp
            <span class="badge {{ $sc }}">{{ $overdue ? 'Overdue' : ucfirst($fu->status) }}</span>
          </td>
          <td>
            <div style="display:flex;gap:5px;align-items:center;">
              {{-- Complete --}}
              @if($fu->status === 'pending')
              <button type="button"
                class="btn btn-ghost btn-sm"
                style="font-size:.72rem;padding:2px 8px;color:var(--success);"
                onclick="openCompleteModal({{ $fu->id }})">
                ✓ Done
              </button>
              @endif
              {{-- Edit --}}
              <button type="button"
                class="btn btn-ghost btn-sm"
                style="font-size:.72rem;padding:2px 8px;"
                onclick="openEditModal({{ $fu->id }})">
                Edit
              </button>
              {{-- Delete --}}
              <form method="POST" action="{{ route('followups.destroy', $fu) }}" onsubmit="return confirm('Delete this follow-up?')" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm" style="font-size:.72rem;padding:2px 8px;color:var(--danger);">Del</button>
              </form>
            </div>
          </td>
        </tr>

        {{-- Hidden edit form row --}}
        <tr id="edit-row-{{ $fu->id }}" class="hidden" style="background:var(--bg-tertiary);">
          <td colspan="7" style="padding:14px 18px;">
            <form method="POST" action="{{ route('followups.update', $fu) }}">
              @csrf @method('PUT')
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:10px;align-items:end;">
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Type</label>
                  <select name="type" class="filter-select" style="width:100%;">
                    <option value="call"        {{ $fu->type === 'call'        ? 'selected' : '' }}>📞 Call</option>
                    <option value="appointment" {{ $fu->type === 'appointment' ? 'selected' : '' }}>📅 Appointment</option>
                    <option value="check_in"    {{ $fu->type === 'check_in'    ? 'selected' : '' }}>✅ Check-in</option>
                    <option value="email"       {{ $fu->type === 'email'       ? 'selected' : '' }}>✉️ Email</option>
                  </select>
                </div>
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Due Date</label>
                  <input type="date" name="due_date" value="{{ $fu->due_date->format('Y-m-d') }}" class="filter-select" style="width:100%;">
                </div>
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Status</label>
                  <select name="status" class="filter-select" style="width:100%;">
                    <option value="pending"   {{ $fu->status === 'pending'   ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ $fu->status === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $fu->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                  </select>
                </div>
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Assigned To</label>
                  <select name="assigned_to" class="filter-select" style="width:100%;">
                    <option value="">Unassigned</option>
                    @foreach($staff as $s)
                    <option value="{{ $s->id }}" {{ $fu->assigned_to == $s->id ? 'selected' : '' }}>{{ $s->first_name }} {{ $s->last_name }}</option>
                    @endforeach
                  </select>
                </div>
                <div style="display:flex;gap:6px;">
                  <button type="submit" class="btn btn-primary btn-sm">Save</button>
                  <button type="button" class="btn btn-ghost btn-sm" onclick="closeEditModal({{ $fu->id }})">✕</button>
                </div>
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px;">
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Notes</label>
                  <textarea name="notes" rows="2" maxlength="1000"
                    style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;font-family:inherit;resize:none;background:var(--bg-secondary);">{{ $fu->notes }}</textarea>
                </div>
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Outcome</label>
                  <textarea name="outcome" rows="2" maxlength="1000"
                    style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;font-family:inherit;resize:none;background:var(--bg-secondary);">{{ $fu->outcome }}</textarea>
                </div>
              </div>
            </form>
          </td>
        </tr>

        {{-- Hidden complete form row --}}
        <tr id="complete-row-{{ $fu->id }}" class="hidden" style="background:var(--success-light);">
          <td colspan="7" style="padding:12px 18px;">
            <form method="POST" action="{{ route('followups.complete', $fu) }}">
              @csrf @method('PATCH')
              <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:.84rem;font-weight:500;color:var(--success);">✓ Mark as complete</span>
                <input type="text" name="outcome" placeholder="Outcome / notes (optional)" maxlength="1000"
                  style="flex:1;padding:7px 10px;border:1px solid #6ee7b7;border-radius:var(--radius-sm);font-size:.84rem;font-family:inherit;background:#fff;">
                <button type="submit" class="btn btn-sm" style="background:var(--success);color:#fff;border:none;">Complete</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeCompleteModal({{ $fu->id }})">✕</button>
              </div>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.45 2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
              <h3>No follow-ups found</h3>
              <p>All clear! No follow-ups match your current filter.</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($followups->hasPages())
  <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
    <div style="font-size:.82rem;color:var(--text-secondary);">
      Showing {{ $followups->firstItem() }}–{{ $followups->lastItem() }} of {{ $followups->total() }}
    </div>
    <div style="display:flex;gap:6px;">
      @if($followups->onFirstPage())
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Previous</span>
      @else
        <a href="{{ $followups->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
      @endif
      @if($followups->hasMorePages())
        <a href="{{ $followups->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
      @else
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Next</span>
      @endif
    </div>
  </div>
  @endif
</div>

{{-- ── Create Follow-up Modal ─────────────────────────────── --}}
<div id="create-followup-modal" class="hidden" style="position:fixed;inset:0;z-index:500;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);backdrop-filter:blur(2px);">
  <div style="background:var(--bg-secondary);border-radius:var(--radius-lg);width:520px;max-width:95vw;box-shadow:var(--shadow-xl);padding:24px;position:relative;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <h3 style="font-size:1.05rem;font-weight:600;">New Follow-up</h3>
      <button onclick="document.getElementById('create-followup-modal').classList.add('hidden')" style="background:none;border:none;cursor:pointer;color:var(--text-tertiary);font-size:1.2rem;">✕</button>
    </div>
    <form method="POST" action="{{ route('followups.store') }}">
      @csrf
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
        <div>
          <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Patient <span style="color:var(--danger)">*</span></label>
          <div style="position:relative;">
            <input type="text" id="fu-patient-search" placeholder="Search by name or phone…"
              autocomplete="off"
              style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;background:var(--bg-secondary);outline:none;"
              onfocus="this.style.borderColor='var(--accent)'" onblur="setTimeout(()=>document.getElementById('fu-patient-results').style.display='none',200)">
            <input type="hidden" name="patient_id" id="fu-patient-id">
            <div id="fu-patient-results" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--border);border-radius:var(--radius-sm);box-shadow:var(--shadow-md);z-index:600;max-height:160px;overflow-y:auto;margin-top:2px;"></div>
          </div>
        </div>
        <div>
          <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Type <span style="color:var(--danger)">*</span></label>
          <select name="type" class="filter-select" style="width:100%;" required>
            <option value="call">📞 Call</option>
            <option value="appointment">📅 Appointment</option>
            <option value="check_in">✅ Check-in</option>
            <option value="email">✉️ Email</option>
          </select>
        </div>
        <div>
          <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Due Date <span style="color:var(--danger)">*</span></label>
          <input type="date" name="due_date" required
            value="{{ today()->addDay()->format('Y-m-d') }}"
            style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;background:var(--bg-secondary);">
        </div>
        <div>
          <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Assign To</label>
          <select name="assigned_to" class="filter-select" style="width:100%;">
            <option value="">— me ({{ Auth::user()->first_name }}) —</option>
            @foreach($staff as $s)
            <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->last_name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div style="margin-bottom:16px;">
        <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Notes</label>
        <textarea name="notes" rows="2" maxlength="1000" placeholder="What needs to be done?"
          style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;resize:none;background:var(--bg-secondary);outline:none;"
          onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'"></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('create-followup-modal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Follow-up</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
function openEditModal(id) {
  document.querySelectorAll('[id^="edit-row-"]').forEach(r => r.classList.add('hidden'));
  document.querySelectorAll('[id^="complete-row-"]').forEach(r => r.classList.add('hidden'));
  document.getElementById('edit-row-'+id).classList.remove('hidden');
}
function closeEditModal(id) {
  document.getElementById('edit-row-'+id).classList.add('hidden');
}
function openCompleteModal(id) {
  document.querySelectorAll('[id^="complete-row-"]').forEach(r => r.classList.add('hidden'));
  document.querySelectorAll('[id^="edit-row-"]').forEach(r => r.classList.add('hidden'));
  document.getElementById('complete-row-'+id).classList.remove('hidden');
}
function closeCompleteModal(id) {
  document.getElementById('complete-row-'+id).classList.add('hidden');
}

// Patient autocomplete for create modal
const fuSearch  = document.getElementById('fu-patient-search');
const fuResults = document.getElementById('fu-patient-results');
const fuHidden  = document.getElementById('fu-patient-id');
let fuTimer;

fuSearch.addEventListener('input', () => {
  clearTimeout(fuTimer);
  const q = fuSearch.value.trim();
  if (q.length < 2) { fuResults.style.display = 'none'; return; }
  fuTimer = setTimeout(() => {
    fetch(`/patients/search?q=${encodeURIComponent(q)}`)
      .then(r => r.json())
      .then(data => {
        if (!data.length) { fuResults.style.display = 'none'; return; }
        fuResults.innerHTML = data.map(p =>
          `<div style="padding:8px 12px;cursor:pointer;font-size:.84rem;border-bottom:1px solid var(--border-light);"
            onmousedown="selectFuPatient(${p.id}, '${p.full_name.replace(/'/g,"\\'")} (${p.phone})')"
            onmouseover="this.style.background='var(--bg-tertiary)'" onmouseout="this.style.background=''">
            <strong>${p.full_name}</strong>
            <span style="color:var(--text-tertiary);font-size:.78rem;"> — ${p.phone}</span>
          </div>`
        ).join('');
        fuResults.style.display = 'block';
      });
  }, 250);
});

function selectFuPatient(id, label) {
  fuHidden.value = id;
  fuSearch.value = label;
  fuResults.style.display = 'none';
}

// Close modal on backdrop click
document.getElementById('create-followup-modal').addEventListener('click', function(e) {
  if (e.target === this) this.classList.add('hidden');
});
</script>
<style>.hidden { display: none !important; }</style>
@endpush
@endsection
