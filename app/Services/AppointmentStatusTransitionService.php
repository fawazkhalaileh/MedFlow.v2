<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AppointmentStatusTransitionService
{
    public function transition(Appointment $appointment, User $user, string $toStatus): Appointment
    {
        $this->assertScoped($appointment, $user);

        $fromStatus = $appointment->status;
        $allowed = $this->allowedTransitionsFor($appointment, $user);

        if (!$this->isManagerOverride($user) && !in_array($toStatus, $allowed[$fromStatus] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'This appointment cannot be moved to that status from your workspace.',
            ]);
        }

        $appointment->fill(array_merge(
            ['status' => $toStatus],
            $this->timestampsFor($toStatus)
        ));
        $appointment->save();

        ActivityLog::record(
            'appointment_status_updated',
            $appointment,
            "Appointment status changed from {$fromStatus} to {$toStatus}.",
            ['status' => $fromStatus],
            ['status' => $toStatus]
        );

        return $appointment;
    }

    public function allowedTransitionsFor(Appointment $appointment, User $user): array
    {
        if ($user->isSuperAdmin() || $user->isRole('branch_manager')) {
            return array_merge(
                $this->secretaryTransitions(),
                $this->doctorTransitions(),
                $this->technicianTransitions()
            );
        }

        if ($user->isRole('secretary')) {
            return $this->secretaryTransitions();
        }

        if ($user->isRole('doctor', 'nurse')) {
            $this->assertAssignedProvider($appointment, $user, Appointment::VISIT_TYPE_DOCTOR);
            return $this->doctorTransitions();
        }

        if ($user->isRole('technician')) {
            $this->assertAssignedProvider($appointment, $user, Appointment::VISIT_TYPE_TECHNICIAN);
            return $this->technicianTransitions();
        }

        return [];
    }

    private function secretaryTransitions(): array
    {
        return [
            Appointment::STATUS_BOOKED => [
                Appointment::STATUS_ARRIVED,
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_NO_SHOW,
            ],
            Appointment::STATUS_ARRIVED => [
                Appointment::STATUS_WAITING_DOCTOR,
                Appointment::STATUS_WAITING_TECHNICIAN,
            ],
            Appointment::STATUS_COMPLETED_WAITING_CHECKOUT => [
                Appointment::STATUS_CHECKED_OUT,
            ],
        ];
    }

    private function doctorTransitions(): array
    {
        return [
            Appointment::STATUS_WAITING_DOCTOR => [Appointment::STATUS_IN_DOCTOR_VISIT],
            Appointment::STATUS_IN_DOCTOR_VISIT => [Appointment::STATUS_COMPLETED_WAITING_CHECKOUT],
        ];
    }

    private function technicianTransitions(): array
    {
        return [
            Appointment::STATUS_WAITING_TECHNICIAN => [Appointment::STATUS_IN_TECHNICIAN_VISIT],
            Appointment::STATUS_IN_TECHNICIAN_VISIT => [Appointment::STATUS_COMPLETED_WAITING_CHECKOUT],
        ];
    }

    private function timestampsFor(string $status): array
    {
        return match ($status) {
            Appointment::STATUS_ARRIVED => ['arrived_at' => now()],
            Appointment::STATUS_IN_DOCTOR_VISIT,
            Appointment::STATUS_IN_TECHNICIAN_VISIT => ['provider_started_at' => now()],
            Appointment::STATUS_COMPLETED_WAITING_CHECKOUT => ['completed_at' => now()],
            Appointment::STATUS_CHECKED_OUT => ['checked_out_at' => now()],
            default => [],
        };
    }

    private function isManagerOverride(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isRole('branch_manager');
    }

    private function assertScoped(Appointment $appointment, User $user): void
    {
        if ($user->company_id !== $appointment->company_id) {
            abort(404);
        }

        if (!$user->isSuperAdmin() && $appointment->branch_id !== $user->scopedBranchId()) {
            abort(404);
        }
    }

    private function assertAssignedProvider(Appointment $appointment, User $user, string $requiredVisitType): void
    {
        if ($appointment->visit_type !== $requiredVisitType || $appointment->assigned_staff_id !== $user->id) {
            abort(404);
        }
    }
}
