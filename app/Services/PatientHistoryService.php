<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Collection;

class PatientHistoryService
{
    public function timeline(User $user, Patient $patient): Collection
    {
        $this->assertCanView($user, $patient);

        $patient->loadMissing([
            'appointments.service',
            'appointments.assignedStaff',
            'appointments.bookedBy',
            'sessions.service',
            'sessions.technician',
            'sessions.notes.createdBy',
            'sessions.attachments.uploadedBy',
            'patientPackages.package.service',
            'patientPackages.purchasedBy',
            'packageUsages.service',
            'packageUsages.usedBy',
            'transactions.receivedBy',
            'followUps.assignedTo',
            'attachments.uploadedBy',
            'notes.createdBy',
        ]);

        $timeline = collect();

        foreach ($patient->appointments as $appointment) {
            $timeline->push([
                'type' => 'appointment',
                'occurred_at' => $appointment->scheduled_at,
                'title' => 'Appointment',
                'summary' => trim(($appointment->service?->name ?? 'Service') . ' - ' . $appointment->status),
                'details' => [
                    'status' => $appointment->status,
                    'service' => $appointment->service?->name,
                    'staff' => $appointment->assignedStaff?->full_name,
                    'duration' => $appointment->duration_minutes,
                ],
                'author' => $appointment->bookedBy?->full_name,
            ]);
        }

        if ($this->canViewOperational($user)) {
            $statusChanges = ActivityLog::query()
                ->with('user')
                ->where('company_id', $patient->company_id)
                ->where('model_type', Appointment::class)
                ->whereIn('model_id', $patient->appointments->pluck('id'))
                ->where(function ($query) {
                    $query->where('action', 'appointment_status_updated')
                        ->orWhere('description', 'like', '%Appointment status changed%');
                })
                ->latest('created_at')
                ->get();

            foreach ($statusChanges as $log) {
                $timeline->push([
                    'type' => 'appointment_status',
                    'occurred_at' => $log->created_at,
                    'title' => 'Appointment status changed',
                    'summary' => $log->description ?: 'Appointment status updated',
                    'details' => [
                        'from' => data_get($log->old_values, 'status'),
                        'to' => data_get($log->new_values, 'status'),
                    ],
                    'author' => $log->user?->full_name,
                ]);
            }
        }

        if ($this->canViewClinical($user)) {
            foreach ($patient->sessions as $session) {
                $timeline->push([
                    'type' => 'session',
                    'occurred_at' => $session->started_at ?? $session->created_at,
                    'title' => 'Treatment session',
                    'summary' => $session->service?->name ?? 'Session',
                    'details' => [
                        'device_used' => $session->device_used,
                        'treatment_areas' => $session->treatment_areas,
                        'laser_settings' => $session->laser_settings,
                        'shots_count' => $session->shots_count,
                        'duration' => $session->duration,
                        'before_condition' => $session->observations_before,
                        'after_condition' => $session->observations_after,
                        'outcome' => $session->outcome,
                        'recommendations' => $session->recommendations,
                        'technician_notes' => $session->notes->where('note_type', 'technician')->pluck('content')->values(),
                        'doctor_notes' => $session->notes->where('note_type', 'clinical')->pluck('content')->values(),
                    ],
                    'author' => $session->technician?->full_name,
                ]);
            }
        }

        foreach ($patient->patientPackages as $purchase) {
            $timeline->push([
                'type' => 'package_purchase',
                'occurred_at' => $purchase->purchased_at,
                'title' => 'Package purchase',
                'summary' => $purchase->package?->name ?? 'Package',
                'details' => [
                    'sessions_purchased' => $purchase->sessions_purchased,
                    'sessions_used' => $purchase->sessions_used,
                    'remaining' => $purchase->remaining_sessions,
                    'final_price' => $purchase->final_price,
                    'status' => $purchase->status,
                ],
                'author' => $purchase->purchasedBy?->full_name,
            ]);
        }

        foreach ($patient->packageUsages as $usage) {
            $timeline->push([
                'type' => 'package_usage',
                'occurred_at' => $usage->used_at,
                'title' => 'Package usage',
                'summary' => ($usage->service?->name ?? 'Service') . ' - ' . $usage->sessions_consumed . ' session(s)',
                'details' => [
                    'service' => $usage->service?->name,
                    'sessions_consumed' => $usage->sessions_consumed,
                    'notes' => $usage->notes,
                ],
                'author' => $usage->usedBy?->full_name,
            ]);
        }

        if ($this->canViewFinancial($user)) {
            foreach ($patient->transactions as $transaction) {
                $timeline->push([
                    'type' => 'payment',
                    'occurred_at' => $transaction->received_at,
                    'title' => 'Payment / receipt',
                    'summary' => $transaction->receipt_number ?: 'Payment',
                    'details' => [
                        'amount' => $transaction->amount,
                        'method' => $transaction->payment_method,
                        'received' => $transaction->amount_received,
                        'change' => $transaction->change_returned,
                        'notes' => $transaction->notes,
                    ],
                    'author' => $transaction->receivedBy?->full_name,
                ]);
            }
        }

        if ($this->canViewOperational($user)) {
            foreach ($patient->followUps as $followUp) {
                $timeline->push([
                    'type' => 'follow_up',
                    'occurred_at' => $followUp->completed_at ?? $followUp->due_date,
                    'title' => 'Follow-up',
                    'summary' => $followUp->type,
                    'details' => [
                        'status' => $followUp->status,
                        'due_date' => $followUp->due_date?->toDateString(),
                        'outcome' => $followUp->outcome,
                        'notes' => $followUp->notes,
                    ],
                    'author' => $followUp->assignedTo?->full_name,
                ]);
            }
        }

        $patientNotes = $patient->notes->filter(fn (Note $note) => $this->canViewNote($user, $note));
        foreach ($patientNotes as $note) {
            $timeline->push([
                'type' => 'note',
                'occurred_at' => $note->created_at,
                'title' => 'Note',
                'summary' => $note->type_display,
                'details' => [
                    'content' => $note->content,
                    'type' => $note->note_type,
                    'private' => $note->is_private,
                ],
                'author' => $note->createdBy?->full_name,
            ]);
        }

        foreach ($patient->attachments as $attachment) {
            if ($attachment->is_private && !$this->canViewClinical($user) && !$this->canViewFinancial($user)) {
                continue;
            }

            $timeline->push([
                'type' => 'attachment',
                'occurred_at' => $attachment->created_at,
                'title' => 'Attachment',
                'summary' => $attachment->title ?: $attachment->file_name,
                'details' => [
                    'attachment_type' => $attachment->attachment_type,
                    'file_name' => $attachment->file_name,
                    'notes' => $attachment->notes,
                    'path' => $attachment->file_path,
                ],
                'author' => $attachment->uploadedBy?->full_name,
            ]);
        }

        return $timeline
            ->sortByDesc(fn (array $item) => optional($item['occurred_at'])->timestamp ?? 0)
            ->values();
    }

    public function assertCanView(User $user, Patient $patient): void
    {
        abort_unless($patient->company_id === $user->company_id, 404);

        if (!$user->isSuperAdmin()) {
            abort_unless($patient->branch_id === $user->scopedBranchId(), 404);
        }
    }

    private function canViewClinical(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isRole('doctor', 'nurse', 'technician', 'branch_manager');
    }

    private function canViewFinancial(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isRole('finance', 'branch_manager');
    }

    private function canViewOperational(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isRole('secretary', 'doctor', 'nurse', 'technician', 'finance', 'branch_manager');
    }

    private function canViewNote(User $user, Note $note): bool
    {
        if ($note->is_private && $note->created_by !== $user->id && !$user->isSuperAdmin() && !$user->isRole('branch_manager')) {
            return false;
        }

        if (in_array($note->note_type, ['clinical', 'technician', 'session', 'treatment_plan'], true)) {
            return $this->canViewClinical($user);
        }

        if ($note->note_type === 'internal') {
            return $user->isSuperAdmin() || $user->isRole('branch_manager', 'finance', 'doctor', 'nurse');
        }

        return $this->canViewOperational($user);
    }
}
