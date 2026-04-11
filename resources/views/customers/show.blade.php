@extends('layouts.app')

@section('title', $customer->full_name . ' - MedFlow CRM')
@section('breadcrumb', 'Customers / ' . $customer->full_name)

@section('content')
<div class="page-header animate-in">
  <div style="display:flex;align-items:center;gap:14px;">
    <div class="avatar avatar-lg" style="width:52px;height:52px;font-size:1.1rem;background:linear-gradient(135deg,#{{ substr(md5($customer->first_name),0,6) }},#{{ substr(md5($customer->last_name ?? ''),0,6) }});">
      {{ strtoupper(substr($customer->first_name,0,1).substr($customer->last_name ?? '',0,1)) }}
    </div>
    <div>
      <h1 class="page-title" style="font-size:1.5rem;">{{ $customer->full_name }}</h1>
      <p class="page-subtitle">
        <span style="font-family:monospace;color:var(--accent);">{{ $customer->customer_code }}</span>
        &bull; {{ $customer->branch?->name ?? 'No branch' }}
        @if($customer->status === 'vip') &bull; <span class="badge badge-purple">VIP</span> @endif
      </p>
    </div>
  </div>
  <a href="{{ route('customers.index') }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back
  </a>
</div>

<div class="grid-2-1 animate-in" style="animation-delay:.05s;align-items:start;">

  {{-- Left Column --}}
  <div style="display:flex;flex-direction:column;gap:18px;">

    {{-- Appointments --}}
    <div class="card" style="padding:0;">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <div class="card-title">Recent Appointments</div>
      </div>
      @if($customer->appointments->isEmpty())
      <div class="empty-state" style="padding:32px;"><p>No appointments yet</p></div>
      @else
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date & Time</th><th>Service</th><th>Staff</th><th>Status</th></tr></thead>
          <tbody>
            @foreach($customer->appointments as $appt)
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
    @if($customer->treatmentPlans->isNotEmpty())
    <div class="card">
      <div class="card-header">
        <div class="card-title">Treatment Plans</div>
      </div>
      @foreach($customer->treatmentPlans as $plan)
      <div style="padding:12px 0;border-bottom:1px solid var(--border-light);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
          <div style="font-weight:500;font-size:.9rem;">{{ $plan->service?->name ?? 'Custom Plan' }}</div>
          <span class="badge {{ $plan->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($plan->status) }}</span>
        </div>
        <div style="display:flex;gap:16px;font-size:.78rem;color:var(--text-tertiary);">
          <span>Sessions: {{ $plan->sessions_completed ?? 0 }} / {{ $plan->total_sessions }}</span>
          @if($plan->total_cost)<span>AED {{ number_format($plan->total_cost) }}</span>@endif
        </div>
      </div>
      @endforeach
    </div>
    @endif

    {{-- Notes --}}
    @if($customer->notes->isNotEmpty())
    <div class="card">
      <div class="card-header"><div class="card-title">Notes</div></div>
      @foreach($customer->notes as $note)
      <div class="activity-item">
        <div class="activity-dot" style="background:{{ $note->is_flagged ? 'var(--danger)' : 'var(--accent)' }}"></div>
        <div class="activity-text">
          <span class="badge badge-gray" style="font-size:.68rem;margin-bottom:4px;">{{ ucfirst(str_replace('_',' ',$note->note_type)) }}</span><br>
          {{ $note->content }}
          <div class="activity-time">{{ $note->created_at->diffForHumans() }}</div>
        </div>
      </div>
      @endforeach
    </div>
    @endif

  </div>

  {{-- Right Column --}}
  <div style="display:flex;flex-direction:column;gap:18px;">

    {{-- Personal Info --}}
    <div class="card">
      <div class="card-title" style="margin-bottom:14px;">Personal Information</div>
      <div style="display:flex;flex-direction:column;gap:9px;font-size:.84rem;">
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Phone</span>
          <span style="font-weight:500;">{{ $customer->phone }}</span>
        </div>
        @if($customer->email)
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Email</span>
          <span>{{ $customer->email }}</span>
        </div>
        @endif
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Gender</span>
          <span>{{ ucfirst($customer->gender ?? '--') }}</span>
        </div>
        @if($customer->date_of_birth)
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Age</span>
          <span>{{ $customer->age }} years</span>
        </div>
        @endif
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Nationality</span>
          <span>{{ $customer->nationality ?? '--' }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Source</span>
          <span>{{ ucfirst($customer->source ?? '--') }}</span>
        </div>
        <div style="border-top:1px solid var(--border);padding-top:9px;display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Registered</span>
          <span>{{ $customer->created_at->format('d M Y') }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--text-secondary);">Last Visit</span>
          <span>{{ $customer->last_visit_at?->format('d M Y') ?? 'Never' }}</span>
        </div>
      </div>
    </div>

    {{-- Medical Info --}}
    @if($customer->medicalInfo)
    <div class="card">
      <div class="card-title" style="margin-bottom:12px;">Medical Notes</div>
      @if($customer->medicalInfo->allergies)
      <div style="margin-bottom:8px;">
        <div style="font-size:.75rem;color:var(--text-tertiary);margin-bottom:3px;">Allergies</div>
        <div style="font-size:.84rem;">{{ $customer->medicalInfo->allergies }}</div>
      </div>
      @endif
      @if($customer->medicalInfo->skin_type)
      <div style="margin-bottom:8px;">
        <div style="font-size:.75rem;color:var(--text-tertiary);margin-bottom:3px;">Skin Type</div>
        <div style="font-size:.84rem;">{{ $customer->medicalInfo->skin_type }}</div>
      </div>
      @endif
    </div>
    @endif

    {{-- Follow-ups --}}
    @if($customer->followUps->isNotEmpty())
    <div class="card">
      <div class="card-title" style="margin-bottom:12px;">Follow-ups</div>
      @foreach($customer->followUps as $fu)
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
