@extends('layouts.app')

@section('title', 'Doctor Visit - MedFlow CRM')
@section('breadcrumb', 'Doctor Visit')

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ $appointment->patient?->full_name }}</h1>
    <p class="page-subtitle">{{ $appointment->service?->name ?? 'Medical visit' }} | {{ \Illuminate\Support\Str::headline($appointment->status) }}</p>
  </div>
  <div class="header-actions">
    @if($appointment->status === \App\Models\Appointment::STATUS_WAITING_DOCTOR)
    <form method="POST" action="{{ route('appointments.doctor.start', $appointment) }}">
      @csrf @method('PATCH')
      <button type="submit" class="btn btn-primary">Start Visit</button>
    </form>
    @endif
    <a href="{{ route('review-queue') }}" class="btn btn-secondary">Back to Queue</a>
  </div>
</div>

<div class="grid-2-1 animate-in" style="align-items:start;">
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Doctor Visit Form</div>
        <div class="card-subtitle">Document the visit, choose the actual service performed, and hand it back to reception for checkout.</div>
      </div>
    </div>

    @if(in_array($appointment->status, [\App\Models\Appointment::STATUS_WAITING_DOCTOR, \App\Models\Appointment::STATUS_IN_DOCTOR_VISIT], true))
    <form method="POST" action="{{ route('appointments.doctor.complete', $appointment) }}">
      @csrf @method('PATCH')
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Chargeable Items</label>
          @php
            $selectedChargeableItems = collect(old('chargeable_service_ids', $appointment->chargeable_service_ids ?: [$appointment->service_id]))
              ->filter()
              ->map(fn ($id) => (string) $id)
              ->all();
          @endphp
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px;padding:12px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--bg-tertiary);max-height:220px;overflow:auto;">
            @foreach($services as $service)
            <label style="display:flex;align-items:flex-start;gap:8px;font-size:.83rem;">
              <input type="checkbox" name="chargeable_service_ids[]" value="{{ $service->id }}" @checked(in_array((string) $service->id, $selectedChargeableItems, true))>
              <span>
                <span style="display:block;font-weight:600;color:var(--text-primary);">{{ $service->name }}</span>
                @if($service->price)
                <span style="font-size:.74rem;color:var(--text-tertiary);">{{ number_format((float) $service->price, 2) }}</span>
                @endif
              </span>
            </label>
            @endforeach
          </div>
          @error('chargeable_service_ids')<div class="form-error">{{ $message }}</div>@enderror
          @error('chargeable_service_ids.*')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Visit Outcome</label>
          <select name="doctor_visit_outcome" class="form-select">
            @foreach($doctorOutcomes as $value => $label)
            <option value="{{ $value }}" @selected(old('doctor_visit_outcome', $appointment->doctor_visit_outcome) === $value)>{{ $label }}</option>
            @endforeach
          </select>
          @error('doctor_visit_outcome')<div class="form-error">{{ $message }}</div>@enderror
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Chief Complaint</label>
        <textarea name="chief_complaint" class="form-textarea">{{ old('chief_complaint', $appointment->chief_complaint) }}</textarea>
        @error('chief_complaint')<div class="form-error">{{ $message }}</div>@enderror
      </div>
      <div class="form-group">
        <label class="form-label">Clinical Notes</label>
        <textarea name="clinical_notes" class="form-textarea">{{ old('clinical_notes', $appointment->clinical_notes) }}</textarea>
        @error('clinical_notes')<div class="form-error">{{ $message }}</div>@enderror
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Assessment</label>
          <textarea name="assessment" class="form-textarea">{{ old('assessment', $appointment->assessment) }}</textarea>
          @error('assessment')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">What Was Done</label>
          <textarea name="treatment_summary" class="form-textarea">{{ old('treatment_summary', $appointment->treatment_summary) }}</textarea>
          @error('treatment_summary')<div class="form-error">{{ $message }}</div>@enderror
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Recommendations / Next Step</label>
          <textarea name="doctor_recommendations" class="form-textarea">{{ old('doctor_recommendations', $appointment->doctor_recommendations) }}</textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Reception Checkout Note</label>
          <textarea name="checkout_summary" class="form-textarea" placeholder="Example: Charge consultation only, or charge Botox forehead + glabella.">{{ old('checkout_summary', $appointment->checkout_summary) }}</textarea>
          @error('checkout_summary')<div class="form-error">{{ $message }}</div>@enderror
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Additional Outcome Notes</label>
          <textarea name="outcome_notes" class="form-textarea">{{ old('outcome_notes', $appointment->outcome_notes) }}</textarea>
        </div>
      <label style="display:flex;align-items:center;gap:8px;font-size:.84rem;margin-bottom:18px;">
        <input type="checkbox" name="follow_up_required" value="1" {{ old('follow_up_required', $appointment->follow_up_required) ? 'checked' : '' }}>
        Follow-up needed
      </label>
      <button type="submit" class="btn btn-primary">
        {{ $appointment->status === \App\Models\Appointment::STATUS_WAITING_DOCTOR ? 'Complete and Send to Reception' : 'Complete Visit' }}
      </button>
    </form>
    @else
    <div style="display:grid;gap:12px;">
      <div style="padding:12px;border-radius:var(--radius-md);background:var(--bg-tertiary);">
        <div style="font-size:.74rem;color:var(--text-tertiary);text-transform:uppercase;">Chargeable Items</div>
        <div>
          {{ collect($appointment->chargeable_service_ids ?: [$appointment->service_id])->filter()->map(function ($id) use ($services) {
              return $services->firstWhere('id', $id)?->name;
          })->filter()->implode(', ') ?: 'No service selected.' }}
        </div>
      </div>
      <div style="padding:12px;border-radius:var(--radius-md);background:var(--bg-tertiary);">
        <div style="font-size:.74rem;color:var(--text-tertiary);text-transform:uppercase;">Visit Outcome</div>
        <div>{{ \Illuminate\Support\Str::headline($appointment->doctor_visit_outcome ?: 'not recorded') }}</div>
      </div>
      <div style="padding:12px;border-radius:var(--radius-md);background:var(--bg-tertiary);">
        <div style="font-size:.74rem;color:var(--text-tertiary);text-transform:uppercase;">Chief Complaint</div>
        <div>{{ $appointment->chief_complaint ?: 'No note yet.' }}</div>
      </div>
      <div style="padding:12px;border-radius:var(--radius-md);background:var(--bg-tertiary);">
        <div style="font-size:.74rem;color:var(--text-tertiary);text-transform:uppercase;">Clinical Notes</div>
        <div>{{ $appointment->clinical_notes ?: 'No note yet.' }}</div>
      </div>
      <div style="padding:12px;border-radius:var(--radius-md);background:var(--bg-tertiary);">
        <div style="font-size:.74rem;color:var(--text-tertiary);text-transform:uppercase;">Treatment Summary</div>
        <div>{{ $appointment->treatment_summary ?: 'No treatment summary yet.' }}</div>
      </div>
      <div style="padding:12px;border-radius:var(--radius-md);background:var(--bg-tertiary);">
        <div style="font-size:.74rem;color:var(--text-tertiary);text-transform:uppercase;">Reception Checkout Note</div>
        <div>{{ $appointment->checkout_summary ?: 'No checkout note yet.' }}</div>
      </div>
    </div>
    @endif
  </div>

  <div style="display:flex;flex-direction:column;gap:18px;">
    <div class="card">
      <div class="card-title" style="margin-bottom:10px;">Visit Snapshot</div>
      <div style="display:grid;gap:8px;font-size:.82rem;">
        <div><strong>Provider:</strong> {{ $appointment->assignedStaff?->full_name ?? 'Not assigned' }}</div>
        <div><strong>Room:</strong> {{ $appointment->room?->name ?? 'TBD' }}</div>
        <div><strong>Scheduled:</strong> {{ $appointment->scheduled_at?->format('d M Y h:i A') }}</div>
        <div><strong>Reason:</strong> {{ $appointment->reason_notes ?: ($appointment->service?->name ?? 'Visit') }}</div>
        <div><strong>Front Desk Note:</strong> {{ $appointment->front_desk_note ?: 'None' }}</div>
      </div>
    </div>

    @include('patients.partials.history-timeline', ['timeline' => $historyTimeline, 'patient' => $appointment->patient])
  </div>
</div>
@endsection
