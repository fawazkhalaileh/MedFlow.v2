@extends('layouts.app')

@section('title', $patient->full_name . ' History - MedFlow CRM')
@section('breadcrumb', 'Reports / Patient History / ' . $patient->full_name)

@section('content')
<div class="page-header animate-in">
  <div>
    <h1 class="page-title">{{ $patient->full_name }} History</h1>
    <p class="page-subtitle">Full retrievable history timeline with operational, financial, and clinical events based on your role.</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('reports.patients.history.export', [$patient, 'format' => 'csv']) }}" class="btn btn-secondary">Export CSV</a>
    <a href="{{ route('reports.patients.history.export', [$patient, 'format' => 'pdf']) }}" class="btn btn-primary">Export PDF</a>
    <a href="{{ route('patients.show', $patient) }}" class="btn btn-secondary">Back to Profile</a>
  </div>
</div>

@include('patients.partials.history-timeline', ['timeline' => $timeline, 'patient' => $patient, 'fullPage' => true])
@endsection
