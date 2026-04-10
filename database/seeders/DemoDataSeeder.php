<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerMedicalInfo;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Service;
use App\Models\TreatmentPlan;
use App\Models\TreatmentSession;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $company  = Company::first();
        $branch1  = Branch::where('code', 'BR-001')->first();
        $branch2  = Branch::where('code', 'BR-002')->first();
        $tech1    = User::where('email', 'tech1.marina@medflow.local')->first();
        $tech2    = User::where('email', 'tech2.marina@medflow.local')->first();
        $secretary = User::where('email', 'reception.marina@medflow.local')->first();
        $reason   = AppointmentReason::where('name', 'Laser Hair Removal Session')->first();
        $consult  = AppointmentReason::where('name', 'Initial Consultation')->first();

        $lhrService   = Service::where('name', 'Full Legs')->first();
        $faceService  = Service::where('name', 'Full Face')->first();
        $bodyService  = Service::where('name', 'Cryolipolysis - Abdomen')->first();

        // --- Demo Customers ---
        $customers = [
            [
                'first_name' => 'Layla', 'last_name' => 'Al Mansouri',
                'email' => 'layla@example.com', 'phone' => '+971 50 100 0001',
                'date_of_birth' => '1992-05-14', 'gender' => 'female',
                'nationality' => 'Emirati', 'source' => 'referral',
                'status' => 'active', 'consent_given' => true,
                'medical' => [
                    'skin_type' => 'III', 'skin_tone' => 'olive',
                    'allergies' => 'None known',
                    'medical_history' => 'No significant medical history',
                    'contraindications' => null,
                ],
                'plan' => ['service' => $lhrService, 'name' => 'Full Legs LHR - 6 Sessions', 'sessions' => 6, 'completed' => 3, 'price' => 4500],
            ],
            [
                'first_name' => 'Maha', 'last_name' => 'Nasser',
                'email' => 'maha@example.com', 'phone' => '+971 50 100 0002',
                'date_of_birth' => '1988-11-22', 'gender' => 'female',
                'nationality' => 'Lebanese', 'source' => 'social',
                'status' => 'active', 'consent_given' => true,
                'medical' => [
                    'skin_type' => 'IV', 'skin_tone' => 'dark',
                    'allergies' => 'Penicillin',
                    'medical_history' => 'Hypothyroidism - on medication',
                    'contraindications' => 'Active acne on treatment area - proceed with caution',
                ],
                'plan' => ['service' => $faceService, 'name' => 'Full Face LHR - 8 Sessions', 'sessions' => 8, 'completed' => 1, 'price' => 3200],
            ],
            [
                'first_name' => 'Amira', 'last_name' => 'Saleh',
                'email' => 'amira@example.com', 'phone' => '+971 55 200 0003',
                'date_of_birth' => '1995-03-08', 'gender' => 'female',
                'nationality' => 'Egyptian', 'source' => 'walk_in',
                'status' => 'active', 'consent_given' => true,
                'medical' => [
                    'skin_type' => 'II', 'skin_tone' => 'fair',
                    'allergies' => 'None',
                    'medical_history' => 'None',
                    'contraindications' => null,
                ],
                'plan' => ['service' => $bodyService, 'name' => 'Abdomen Contouring - 4 Sessions', 'sessions' => 4, 'completed' => 4, 'price' => 5000],
            ],
            [
                'first_name' => 'Reem', 'last_name' => 'Al Farsi',
                'email' => 'reem@example.com', 'phone' => '+971 56 300 0004',
                'date_of_birth' => '2000-07-19', 'gender' => 'female',
                'nationality' => 'Emirati', 'source' => 'phone',
                'status' => 'active', 'consent_given' => false,
                'medical' => null,
                'plan' => null,
            ],
            [
                'first_name' => 'Dana', 'last_name' => 'Khalifa',
                'email' => 'dana@example.com', 'phone' => '+971 52 400 0005',
                'date_of_birth' => '1985-09-30', 'gender' => 'female',
                'nationality' => 'Jordanian', 'source' => 'referral',
                'status' => 'inactive', 'consent_given' => true,
                'medical' => [
                    'skin_type' => 'V', 'skin_tone' => 'dark',
                    'allergies' => 'Latex',
                    'medical_history' => 'Diabetes Type 2',
                    'contraindications' => 'Diabetic - confirm glucose levels before sessions',
                    'has_metal_implants' => true,
                ],
                'plan' => ['service' => $lhrService, 'name' => 'Full Legs LHR - 6 Sessions', 'sessions' => 6, 'completed' => 6, 'price' => 4500],
            ],
        ];

        foreach ($customers as $i => $data) {
            $medicalData = $data['medical'];
            $planData    = $data['plan'];
            unset($data['medical'], $data['plan']);

            $customer = Customer::create(array_merge($data, [
                'company_id'      => $company->id,
                'branch_id'       => $i < 3 ? $branch1->id : $branch2->id,
                'assigned_staff_id' => $tech1->id,
                'registration_date' => now()->subMonths(rand(2, 8))->format('Y-m-d'),
                'consent_given_at'  => $data['consent_given'] ? now()->subMonths(rand(1, 6)) : null,
                'last_visit_at'   => now()->subDays(rand(3, 30)),
            ]));

            if ($medicalData) {
                CustomerMedicalInfo::create(array_merge(
                    ['customer_id' => $customer->id, 'updated_by' => $secretary->id],
                    $medicalData
                ));
            }

            if ($planData && $planData['service']) {
                $plan = TreatmentPlan::create([
                    'company_id'         => $company->id,
                    'branch_id'          => $customer->branch_id,
                    'customer_id'        => $customer->id,
                    'service_id'         => $planData['service']->id,
                    'name'               => $planData['name'],
                    'total_sessions'     => $planData['sessions'],
                    'completed_sessions' => $planData['completed'],
                    'status'             => $planData['completed'] >= $planData['sessions'] ? 'completed' : 'active',
                    'total_price'        => $planData['price'],
                    'amount_paid'        => $planData['price'],
                    'start_date'         => now()->subMonths(4)->format('Y-m-d'),
                    'treatment_areas'    => ['full_legs'],
                    'created_by'         => $secretary->id,
                ]);

                // Create completed sessions
                for ($s = 1; $s <= $planData['completed']; $s++) {
                    $sessionDate = now()->subWeeks($planData['sessions'] - $s + 1);

                    $appointment = Appointment::create([
                        'company_id'       => $company->id,
                        'branch_id'        => $customer->branch_id,
                        'customer_id'      => $customer->id,
                        'treatment_plan_id' => $plan->id,
                        'service_id'       => $planData['service']->id,
                        'assigned_staff_id' => $tech1->id,
                        'booked_by'        => $secretary->id,
                        'reason_id'        => $reason?->id,
                        'appointment_type' => 'booked',
                        'scheduled_at'     => $sessionDate,
                        'duration_minutes' => $planData['service']->duration_minutes,
                        'status'           => 'completed',
                        'session_number'   => $s,
                        'arrived_at'       => $sessionDate,
                        'completed_at'     => $sessionDate->copy()->addMinutes($planData['service']->duration_minutes),
                    ]);

                    TreatmentSession::create([
                        'appointment_id'    => $appointment->id,
                        'treatment_plan_id' => $plan->id,
                        'customer_id'       => $customer->id,
                        'branch_id'         => $customer->branch_id,
                        'service_id'        => $planData['service']->id,
                        'technician_id'     => $tech1->id,
                        'session_number'    => $s,
                        'started_at'        => $sessionDate,
                        'ended_at'          => $sessionDate->copy()->addMinutes($planData['service']->duration_minutes),
                        'status'            => 'completed',
                        'device_used'       => 'Candela GentleMax Pro',
                        'laser_settings'    => ['fluence' => 18, 'pulse_width' => '3ms', 'spot_size' => '18mm'],
                        'skin_reaction'     => 'mild',
                        'observations_before' => 'Skin clean and dry. Hair growth normal since last session.',
                        'observations_after'  => 'Mild erythema observed. Cooling gel applied.',
                        'outcome'             => 'Session completed successfully. Good follicular response.',
                        'next_session_notes'  => $s < $planData['completed'] ? 'Schedule in 6-8 weeks.' : null,
                        'created_by'          => $tech1->id,
                    ]);

                    // Add technician note for each session
                    Note::create([
                        'company_id'   => $company->id,
                        'branch_id'    => $customer->branch_id,
                        'notable_type' => TreatmentSession::class,
                        'notable_id'   => TreatmentSession::latest()->first()->id,
                        'note_type'    => 'session',
                        'content'      => "Session {$s} completed. Patient tolerated treatment well.",
                        'created_by'   => $tech1->id,
                    ]);
                }

                // Upcoming appointment for active plans
                if ($planData['completed'] < $planData['sessions']) {
                    Appointment::create([
                        'company_id'       => $company->id,
                        'branch_id'        => $customer->branch_id,
                        'customer_id'      => $customer->id,
                        'treatment_plan_id' => $plan->id,
                        'service_id'       => $planData['service']->id,
                        'assigned_staff_id' => $tech1->id,
                        'booked_by'        => $secretary->id,
                        'reason_id'        => $reason?->id,
                        'appointment_type' => 'booked',
                        'scheduled_at'     => now()->addDays(rand(3, 14))->setHour(rand(10, 17))->setMinute(0),
                        'duration_minutes' => $planData['service']->duration_minutes,
                        'status'           => 'confirmed',
                        'session_number'   => $planData['completed'] + 1,
                    ]);
                }
            }
        }

        // --- Reception note on a customer ---
        $firstCustomer = Customer::first();
        Note::create([
            'company_id'   => $company->id,
            'branch_id'    => $branch1->id,
            'notable_type' => Customer::class,
            'notable_id'   => $firstCustomer->id,
            'note_type'    => 'reception',
            'content'      => 'Customer called to confirm tomorrow appointment. Requested to change room to Room B.',
            'created_by'   => $secretary->id,
        ]);

        Note::create([
            'company_id'   => $company->id,
            'branch_id'    => $branch1->id,
            'notable_type' => Customer::class,
            'notable_id'   => $firstCustomer->id,
            'note_type'    => 'alert',
            'content'      => 'Customer is allergic to certain topical numbing creams. Always use Emla alternative.',
            'is_flagged'   => true,
            'created_by'   => $tech1->id,
        ]);

        // --- Follow-ups ---
        FollowUp::create([
            'company_id'  => $company->id,
            'branch_id'   => $branch1->id,
            'customer_id' => $firstCustomer->id,
            'assigned_to' => $secretary->id,
            'type'        => 'call',
            'due_date'    => now()->addDays(2)->format('Y-m-d'),
            'status'      => 'pending',
            'notes'       => 'Call customer to confirm next session and remind about pre-session care.',
            'created_by'  => $secretary->id,
        ]);

        // --- Leads ---
        Lead::create([
            'company_id'      => $company->id,
            'branch_id'       => $branch1->id,
            'first_name'      => 'Hessa',
            'last_name'       => 'Al Bloushi',
            'phone'           => '+971 50 999 0001',
            'service_interest' => 'Laser Hair Removal - Full Body',
            'source'          => 'instagram',
            'status'          => 'new',
            'notes'           => 'Inquired via DM. Wants pricing for full body package.',
            'assigned_to'     => $secretary->id,
            'created_by'      => $secretary->id,
        ]);

        Lead::create([
            'company_id'      => $company->id,
            'branch_id'       => $branch1->id,
            'first_name'      => 'Noura',
            'last_name'       => 'Salem',
            'phone'           => '+971 55 888 0002',
            'service_interest' => 'Skin Rejuvenation',
            'source'          => 'phone',
            'status'          => 'appointment_booked',
            'notes'           => 'Called asking about IPL. Booked for consultation next week.',
            'assigned_to'     => $secretary->id,
            'created_by'      => $secretary->id,
        ]);

        $this->command->info('Demo data created: 5 customers, treatment plans, sessions, notes, follow-ups, leads.');
    }
}
