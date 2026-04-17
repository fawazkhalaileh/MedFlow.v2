<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Service;
use App\Models\TreatmentSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentRoleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_books_doctor_visit_and_moves_patient_to_doctor_queue(): void
    {
        $fixture = $this->makeFixture();

        $this->actingAs($fixture['secretary'])
            ->post(route('appointments.store'), [
                'patient_id' => $fixture['patient']->id,
                'service_id' => $fixture['service']->id,
                'branch_id' => $fixture['branch']->id,
                'room_id' => $fixture['room']->id,
                'assigned_staff_id' => $fixture['doctor']->id,
                'visit_type' => Appointment::VISIT_TYPE_DOCTOR,
                'scheduled_date' => today()->toDateString(),
                'scheduled_time' => '10:00',
                'reason_notes' => 'Acne review',
                'front_desk_note' => 'Patient requested quick consult',
            ])
            ->assertRedirect(route('front-desk'));

        $appointment = Appointment::query()->firstOrFail();

        $this->assertSame(Appointment::STATUS_BOOKED, $appointment->status);
        $this->assertSame(Appointment::VISIT_TYPE_DOCTOR, $appointment->visit_type);

        $this->actingAs($fixture['doctor'])
            ->get(route('review-queue'))
            ->assertSee($fixture['patient']->full_name);

        $this->actingAs($fixture['secretary'])
            ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_ARRIVED])
            ->assertRedirect();

        $this->actingAs($fixture['secretary'])
            ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_WAITING_DOCTOR])
            ->assertRedirect();

        $this->assertSame(Appointment::STATUS_WAITING_DOCTOR, $appointment->fresh()->status);
    }

    public function test_doctor_can_start_and_complete_own_visit(): void
    {
        $fixture = $this->makeFixture();

        $appointment = Appointment::create([
            'company_id' => $fixture['company']->id,
            'branch_id' => $fixture['branch']->id,
            'patient_id' => $fixture['patient']->id,
            'service_id' => $fixture['service']->id,
            'room_id' => $fixture['room']->id,
            'assigned_staff_id' => $fixture['doctor']->id,
            'booked_by' => $fixture['secretary']->id,
            'appointment_type' => 'booked',
            'visit_type' => Appointment::VISIT_TYPE_DOCTOR,
            'scheduled_at' => now(),
            'duration_minutes' => 30,
            'status' => Appointment::STATUS_WAITING_DOCTOR,
        ]);

        $this->actingAs($fixture['doctor'])
            ->patch(route('appointments.doctor.start', $appointment))
            ->assertRedirect(route('appointments.doctor.show', $appointment));

        $this->assertSame(Appointment::STATUS_IN_DOCTOR_VISIT, $appointment->fresh()->status);

        $this->actingAs($fixture['doctor'])
            ->patch(route('appointments.doctor.complete', $appointment), [
                'chief_complaint' => 'Facial redness',
                'clinical_notes' => 'Patient reports intermittent redness after sun exposure.',
                'assessment' => 'Likely sensitivity flare.',
                'treatment_summary' => 'Examined skin and adjusted skin-care plan.',
                'doctor_recommendations' => 'Use SPF and return in 2 weeks.',
                'follow_up_required' => 1,
            ])
            ->assertRedirect(route('review-queue'));

        $appointment = $appointment->fresh();

        $this->assertSame(Appointment::STATUS_COMPLETED_WAITING_CHECKOUT, $appointment->status);
        $this->assertSame('Facial redness', $appointment->chief_complaint);
        $this->assertTrue($appointment->follow_up_required);

        $this->actingAs($fixture['technician'])
            ->get(route('appointments.doctor.show', $appointment))
            ->assertForbidden();
    }

    public function test_technician_can_complete_session_and_front_desk_can_checkout(): void
    {
        $fixture = $this->makeFixture();

        $appointment = Appointment::create([
            'company_id' => $fixture['company']->id,
            'branch_id' => $fixture['branch']->id,
            'patient_id' => $fixture['patient']->id,
            'service_id' => $fixture['service']->id,
            'room_id' => $fixture['room']->id,
            'assigned_staff_id' => $fixture['technician']->id,
            'booked_by' => $fixture['secretary']->id,
            'appointment_type' => 'booked',
            'visit_type' => Appointment::VISIT_TYPE_TECHNICIAN,
            'scheduled_at' => now(),
            'duration_minutes' => 45,
            'status' => Appointment::STATUS_WAITING_TECHNICIAN,
        ]);

        $this->actingAs($fixture['technician'])
            ->patch(route('appointments.technician.start', $appointment))
            ->assertRedirect(route('appointments.technician.show', $appointment));

        $this->actingAs($fixture['technician'])
            ->patch(route('appointments.technician.complete', $appointment), [
                'service_id' => $fixture['service']->id,
                'device_used' => 'Candela GentleMax',
                'treatment_areas' => 'Upper lip, chin',
                'shots_count' => 42,
                'fluence' => '18',
                'intensity' => 'Medium',
                'pulse' => 'Short',
                'frequency' => '2Hz',
                'duration_minutes' => 25,
                'skin_reaction' => 'mild',
                'issues' => 'Mild redness only',
                'consumables_used' => 'Cooling gel',
                'observations_before' => 'Area cleaned and shaved',
                'observations_after' => 'Mild perifollicular edema',
                'recommendations' => 'Avoid sun and heat for 48 hours',
                'next_session_notes' => 'Increase energy slightly next visit',
                'follow_up_required' => 1,
            ])
            ->assertRedirect(route('my-queue'));

        $appointment = $appointment->fresh();
        $session = TreatmentSession::query()->where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertSame(Appointment::STATUS_COMPLETED_WAITING_CHECKOUT, $appointment->status);
        $this->assertSame(42, $session->shots_count);
        $this->assertSame('Candela GentleMax', $session->device_used);

        $this->actingAs($fixture['secretary'])
            ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_CHECKED_OUT])
            ->assertRedirect();

        $this->assertSame(Appointment::STATUS_CHECKED_OUT, $appointment->fresh()->status);
    }

    public function test_secretary_can_edit_existing_appointment(): void
    {
        $fixture = $this->makeFixture();

        $appointment = Appointment::create([
            'company_id' => $fixture['company']->id,
            'branch_id' => $fixture['branch']->id,
            'patient_id' => $fixture['patient']->id,
            'service_id' => $fixture['service']->id,
            'room_id' => $fixture['room']->id,
            'assigned_staff_id' => $fixture['doctor']->id,
            'booked_by' => $fixture['secretary']->id,
            'appointment_type' => 'booked',
            'visit_type' => Appointment::VISIT_TYPE_DOCTOR,
            'scheduled_at' => now(),
            'duration_minutes' => 30,
            'status' => Appointment::STATUS_BOOKED,
        ]);

        $this->actingAs($fixture['secretary'])
            ->put(route('appointments.update', $appointment), [
                'patient_id' => $fixture['patient']->id,
                'service_id' => $fixture['service']->id,
                'branch_id' => $fixture['branch']->id,
                'room_id' => $fixture['room']->id,
                'assigned_staff_id' => $fixture['technician']->id,
                'visit_type' => Appointment::VISIT_TYPE_TECHNICIAN,
                'scheduled_date' => today()->toDateString(),
                'scheduled_time' => '14:30',
                'reason_notes' => 'Changed to technician session',
                'front_desk_note' => 'Edited by front desk',
            ])
            ->assertRedirect(route('front-desk'));

        $appointment = $appointment->fresh();

        $this->assertSame(Appointment::VISIT_TYPE_TECHNICIAN, $appointment->visit_type);
        $this->assertSame($fixture['technician']->id, $appointment->assigned_staff_id);
        $this->assertSame('Changed to technician session', $appointment->reason_notes);
    }

    private function makeFixture(): array
    {
        $company = Company::create([
            'name' => 'MedFlow Clinic',
            'slug' => 'medflow-clinic',
            'currency' => 'JOD',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'status' => 'active',
        ]);

        $patient = Patient::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'first_name' => 'Lina',
            'last_name' => 'Saleh',
            'phone' => '0777777777',
            'status' => 'active',
            'registration_date' => today()->toDateString(),
            'consent_given' => true,
            'consent_given_at' => now(),
        ]);

        $service = Service::create([
            'company_id' => $company->id,
            'name' => 'Laser Consultation',
            'duration_minutes' => 30,
            'price' => '45.00',
            'is_active' => true,
        ]);

        $room = Room::create([
            'branch_id' => $branch->id,
            'name' => 'Room 1',
            'is_active' => true,
        ]);

        $secretary = User::factory()->create([
            'company_id' => $company->id,
            'employee_type' => 'secretary',
            'role' => 'secretary',
            'primary_branch_id' => $branch->id,
            'first_name' => 'Front',
            'last_name' => 'Desk',
        ]);

        $doctor = User::factory()->create([
            'company_id' => $company->id,
            'employee_type' => 'doctor',
            'role' => 'doctor',
            'primary_branch_id' => $branch->id,
            'first_name' => 'Maya',
            'last_name' => 'Doctor',
        ]);

        $technician = User::factory()->create([
            'company_id' => $company->id,
            'employee_type' => 'technician',
            'role' => 'technician',
            'primary_branch_id' => $branch->id,
            'first_name' => 'Nour',
            'last_name' => 'Tech',
        ]);

        return compact('company', 'branch', 'patient', 'service', 'room', 'secretary', 'doctor', 'technician');
    }
}
