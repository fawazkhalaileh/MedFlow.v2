@extends('layouts.app')

@section('title', 'Leads - MedFlow CRM')
@section('breadcrumb', 'Leads')

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">Leads</h1>
    <p class="page-subtitle">Track and convert new inquiries into patients</p>
  </div>
  <div style="display:flex;gap:8px;align-items:center;">
    <span class="badge badge-blue" style="padding:6px 14px;font-size:.82rem;">{{ $leads->total() }} total</span>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('create-lead-modal').classList.remove('hidden')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Lead
    </button>
  </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('leads.index') }}">
<div class="filter-bar animate-in" style="animation-delay:.05s">
  <div class="filter-search-wrap" style="max-width:300px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="q" class="filter-search" placeholder="Name, phone, email…" value="{{ request('q') }}">
  </div>
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="">All Statuses</option>
    <option value="new"                {{ request('status') === 'new'                ? 'selected' : '' }}>🆕 New</option>
    <option value="contacted"          {{ request('status') === 'contacted'          ? 'selected' : '' }}>📞 Contacted</option>
    <option value="appointment_booked" {{ request('status') === 'appointment_booked' ? 'selected' : '' }}>📅 Appt Booked</option>
    <option value="converted"          {{ request('status') === 'converted'          ? 'selected' : '' }}>✅ Converted</option>
    <option value="lost"               {{ request('status') === 'lost'               ? 'selected' : '' }}>❌ Lost</option>
  </select>
  @if(Auth::user()->isSuperAdmin())
  <select name="branch" class="filter-select" onchange="this.form.submit()">
    <option value="">All Branches</option>
    @foreach($branches as $b)
    <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
    @endforeach
  </select>
  @endif
  @if(request()->anyFilled(['q','status','branch']))
  <a href="{{ route('leads.index') }}" class="btn btn-ghost btn-sm">Clear</a>
  @endif
  <button type="submit" class="btn btn-secondary btn-sm">Search</button>
</div>
</form>

<div class="card animate-in" style="padding:0;animation-delay:.1s">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Contact</th>
          <th>Service Interest</th>
          <th>Source</th>
          <th>Assigned To</th>
          <th>Status</th>
          <th>Date</th>
          <th style="width:140px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($leads as $lead)
        <tr>
          <td>
            <div style="font-weight:500;">{{ $lead->first_name }} {{ $lead->last_name }}</div>
            @if($lead->email)<div style="font-size:.74rem;color:var(--text-tertiary);">{{ $lead->email }}</div>@endif
          </td>
          <td style="color:var(--text-secondary);font-size:.84rem;">{{ $lead->phone }}</td>
          <td style="color:var(--text-secondary);font-size:.83rem;">{{ $lead->service_interest ?? '—' }}</td>
          <td>
            @php
              $sourceColors = ['phone'=>'badge-blue','walk_in'=>'badge-green','social'=>'badge-purple','online'=>'badge-cyan','referral'=>'badge-yellow'];
              $sc = $sourceColors[$lead->source] ?? 'badge-gray';
            @endphp
            <span class="badge {{ $sc }}">{{ ucfirst(str_replace('_',' ',$lead->source)) }}</span>
          </td>
          <td style="color:var(--text-secondary);font-size:.82rem;">{{ $lead->assignedTo?->first_name ?? '—' }}</td>
          <td>
            @php
              $statusColors = ['new'=>'badge-blue','contacted'=>'badge-yellow','appointment_booked'=>'badge-cyan','converted'=>'badge-green','lost'=>'badge-red'];
              $sc2 = $statusColors[$lead->status] ?? 'badge-gray';
            @endphp
            <span class="badge {{ $sc2 }}">{{ ucfirst(str_replace('_',' ',$lead->status)) }}</span>
          </td>
          <td style="color:var(--text-tertiary);font-size:.8rem;">{{ $lead->created_at->format('d M Y') }}</td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap;">
              {{-- Convert --}}
              @if($lead->status !== 'converted' && $lead->status !== 'lost')
              <form method="POST" action="{{ route('leads.convert', $lead) }}" onsubmit="return confirm('Convert this lead to a patient?')" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm" style="font-size:.71rem;padding:2px 7px;color:var(--success);">→ Patient</button>
              </form>
              @elseif($lead->status === 'converted' && $lead->converted_to_patient_id)
              <a href="{{ route('patients.show', $lead->converted_to_patient_id) }}" class="btn btn-ghost btn-sm" style="font-size:.71rem;padding:2px 7px;color:var(--accent);">View</a>
              @endif
              {{-- Edit --}}
              <button type="button" class="btn btn-ghost btn-sm" style="font-size:.71rem;padding:2px 7px;"
                onclick="openLeadEdit({{ $lead->id }})">Edit</button>
              {{-- Delete --}}
              <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('Delete this lead?')" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm" style="font-size:.71rem;padding:2px 7px;color:var(--danger);">Del</button>
              </form>
            </div>
          </td>
        </tr>

        {{-- Inline edit row --}}
        <tr id="lead-edit-row-{{ $lead->id }}" class="hidden" style="background:var(--bg-tertiary);">
          <td colspan="8" style="padding:14px 18px;">
            <form method="POST" action="{{ route('leads.update', $lead) }}">
              @csrf @method('PUT')
              <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:10px;">
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">First Name</label>
                  <input type="text" name="first_name" value="{{ $lead->first_name }}" required maxlength="80"
                    style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;font-family:inherit;background:var(--bg-secondary);">
                </div>
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Last Name</label>
                  <input type="text" name="last_name" value="{{ $lead->last_name }}" maxlength="80"
                    style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;font-family:inherit;background:var(--bg-secondary);">
                </div>
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Phone</label>
                  <input type="text" name="phone" value="{{ $lead->phone }}" required maxlength="30"
                    style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;font-family:inherit;background:var(--bg-secondary);">
                </div>
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Email</label>
                  <input type="email" name="email" value="{{ $lead->email }}" maxlength="120"
                    style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;font-family:inherit;background:var(--bg-secondary);">
                </div>
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Service Interest</label>
                  <input type="text" name="service_interest" value="{{ $lead->service_interest }}" maxlength="120"
                    style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;font-family:inherit;background:var(--bg-secondary);">
                </div>
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Source</label>
                  <select name="source" class="filter-select" style="width:100%;">
                    <option value="phone"    {{ $lead->source === 'phone'    ? 'selected' : '' }}>Phone</option>
                    <option value="walk_in"  {{ $lead->source === 'walk_in'  ? 'selected' : '' }}>Walk-in</option>
                    <option value="social"   {{ $lead->source === 'social'   ? 'selected' : '' }}>Social</option>
                    <option value="online"   {{ $lead->source === 'online'   ? 'selected' : '' }}>Online</option>
                    <option value="referral" {{ $lead->source === 'referral' ? 'selected' : '' }}>Referral</option>
                  </select>
                </div>
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Status</label>
                  <select name="status" class="filter-select" style="width:100%;">
                    <option value="new"                {{ $lead->status === 'new'                ? 'selected' : '' }}>New</option>
                    <option value="contacted"          {{ $lead->status === 'contacted'          ? 'selected' : '' }}>Contacted</option>
                    <option value="appointment_booked" {{ $lead->status === 'appointment_booked' ? 'selected' : '' }}>Appt Booked</option>
                    <option value="converted"          {{ $lead->status === 'converted'          ? 'selected' : '' }}>Converted</option>
                    <option value="lost"               {{ $lead->status === 'lost'               ? 'selected' : '' }}>Lost</option>
                  </select>
                </div>
                <div>
                  <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Assign To</label>
                  <select name="assigned_to" class="filter-select" style="width:100%;">
                    <option value="">Unassigned</option>
                    @foreach($staff as $s)
                    <option value="{{ $s->id }}" {{ $lead->assigned_to == $s->id ? 'selected' : '' }}>{{ $s->first_name }} {{ $s->last_name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div style="margin-bottom:10px;">
                <label style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:4px;">Notes</label>
                <textarea name="notes" rows="2" maxlength="1000"
                  style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.84rem;font-family:inherit;resize:none;background:var(--bg-secondary);">{{ $lead->notes }}</textarea>
              </div>
              <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeLeadEdit({{ $lead->id }})">Cancel</button>
              </div>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8">
            <div class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
              <h3>No leads found</h3>
              <p>No leads match your current filters. Click <strong>New Lead</strong> to add one.</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($leads->hasPages())
  <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
    <div style="font-size:.82rem;color:var(--text-secondary);">
      Showing {{ $leads->firstItem() }}–{{ $leads->lastItem() }} of {{ $leads->total() }}
    </div>
    <div style="display:flex;gap:6px;">
      @if($leads->onFirstPage())
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Previous</span>
      @else
        <a href="{{ $leads->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
      @endif
      @if($leads->hasMorePages())
        <a href="{{ $leads->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
      @else
        <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed;">Next</span>
      @endif
    </div>
  </div>
  @endif
</div>

{{-- ── Create Lead Modal ─────────────────────────────────── --}}
<div id="create-lead-modal" class="hidden" style="position:fixed;inset:0;z-index:500;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);backdrop-filter:blur(2px);">
  <div style="background:var(--bg-secondary);border-radius:var(--radius-lg);width:580px;max-width:95vw;box-shadow:var(--shadow-xl);padding:24px;position:relative;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <h3 style="font-size:1.05rem;font-weight:600;">New Lead</h3>
      <button onclick="document.getElementById('create-lead-modal').classList.add('hidden')" style="background:none;border:none;cursor:pointer;color:var(--text-tertiary);font-size:1.2rem;">✕</button>
    </div>
    <form method="POST" action="{{ route('leads.store') }}">
      @csrf
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
        <div>
          <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">First Name <span style="color:var(--danger)">*</span></label>
          <input type="text" name="first_name" required maxlength="80" placeholder="First name"
            style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;background:var(--bg-secondary);outline:none;"
            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <div>
          <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Last Name</label>
          <input type="text" name="last_name" maxlength="80" placeholder="Last name"
            style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;background:var(--bg-secondary);outline:none;"
            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <div>
          <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Phone <span style="color:var(--danger)">*</span></label>
          <input type="text" name="phone" required maxlength="30" placeholder="+971 50 000 0000"
            style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;background:var(--bg-secondary);outline:none;"
            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <div>
          <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Email</label>
          <input type="email" name="email" maxlength="120" placeholder="email@example.com"
            style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;background:var(--bg-secondary);outline:none;"
            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <div>
          <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Service Interest</label>
          <input type="text" name="service_interest" maxlength="120" placeholder="e.g. Laser Hair Removal"
            style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;background:var(--bg-secondary);outline:none;"
            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <div>
          <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Source <span style="color:var(--danger)">*</span></label>
          <select name="source" class="filter-select" style="width:100%;" required>
            <option value="phone">📞 Phone</option>
            <option value="walk_in">🚶 Walk-in</option>
            <option value="social">📱 Social Media</option>
            <option value="online">🌐 Online</option>
            <option value="referral">🤝 Referral</option>
          </select>
        </div>
        <div>
          <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Branch <span style="color:var(--danger)">*</span></label>
          <select name="branch_id" class="filter-select" style="width:100%;" required>
            @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ (Auth::user()->primary_branch_id == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Assign To</label>
          <select name="assigned_to" class="filter-select" style="width:100%;">
            <option value="">— Unassigned —</option>
            @foreach($staff as $s)
            <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->last_name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div style="margin-bottom:16px;">
        <label style="font-size:.74rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-secondary);display:block;margin-bottom:5px;">Notes</label>
        <textarea name="notes" rows="2" maxlength="1000" placeholder="Any additional details…"
          style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;resize:none;background:var(--bg-secondary);outline:none;"
          onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'"></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('create-lead-modal').classList.add('hidden')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Lead</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
function openLeadEdit(id) {
  document.querySelectorAll('[id^="lead-edit-row-"]').forEach(r => r.classList.add('hidden'));
  document.getElementById('lead-edit-row-'+id).classList.remove('hidden');
  document.getElementById('lead-edit-row-'+id).scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function closeLeadEdit(id) {
  document.getElementById('lead-edit-row-'+id).classList.add('hidden');
}

// Close modal on backdrop click
document.getElementById('create-lead-modal').addEventListener('click', function(e) {
  if (e.target === this) this.classList.add('hidden');
});
</script>
<style>.hidden { display: none !important; }</style>
@endpush
@endsection
