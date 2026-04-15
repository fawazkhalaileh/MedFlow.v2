<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ScheduleGridSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $branch1 = Branch::where('code', 'BR-001')->first();

        if (! $company || ! $branch1) {
            $this->command->warn('Run CompanyBranchSeeder first.');
            return;
        }

        // Skip if today's grid appointments already exist (idempotent)
        $todayCount = Appointment::where('branch_id', $branch1->id)
            ->whereDate('scheduled_at', today())
            ->count();

        if ($todayCount >= 10) {
            $this->command->info("Today's schedule already has {$todayCount} appointments. Skipping grid seed.");
            return;
        }

        // ── Staff ──────────────────────────────────────────────────────────────
        $doctorRole = Role::where('company_id', $company->id)->where('name', 'doctor')->first();

        $doctor = User::firstOrCreate(
            ['email' => 'doctor.marina@medflow.local'],
            [
                'company_id'        => $company->id,
                'employee_id'       => 'EMP-006',
                'name'              => 'Dr. Hana Al Qasim',
                'first_name'        => 'Hana',
                'last_name'         => 'Al Qasim',
                'password'          => Hash::make('Doctor@123!'),
                'role'              => 'doctor',
                'employee_type'     => 'doctor',
                'employment_status' => 'active',
                'primary_branch_id' => $branch1->id,
                'hire_date'         => '2023-06-01',
                'specialties'       => ['Botox', 'Dermal Fillers', 'Skin Consultations'],
                'email_verified_at' => now(),
            ]
        );

        if ($doctorRole) {
            $doctor->branches()->syncWithoutDetaching([
                $branch1->id => ['role_id' => $doctorRole->id, 'is_primary' => true],
            ]);
        }

        $tech1     = User::where('email', 'tech1.marina@medflow.local')->first();
        $tech2     = User::where('email', 'tech2.marina@medflow.local')->first();
        $secretary = User::where('email', 'reception.marina@medflow.local')->first();

        // ── Rooms ─────────────────────────────────────────────────────────────
        // Update existing room names/descriptions to clearly reflect device type.
        // CompanyBranchSeeder created 4 rooms for BR-001.
        $rooms = Room::where('branch_id', $branch1->id)->where('is_active', true)->orderBy('id')->get();

        $roomLabels = [
            ['name' => 'Laser Room A',       'description' => 'Candela GentleMax Pro — laser hair removal & skin treatments'],
            ['name' => 'Laser Room B',       'description' => 'Candela GentleMax Pro — laser hair removal & body contouring'],
            ['name' => 'Laser Room C',       'description' => 'Cynosure Elite iQ — tattoo removal & IPL photofacial'],
            ['name' => 'Consultation Room',  'description' => 'Doctor consultations, botox & dermal filler procedures'],
        ];

        foreach ($rooms as $i => $room) {
            if (isset($roomLabels[$i])) {
                $room->update($roomLabels[$i]);
            }
        }

        $roomA       = $rooms->get(0);
        $roomB       = $rooms->get(1);
        $roomC       = $rooms->get(2);
        $roomConsult = $rooms->get(3) ?? $roomA;

        if (! $roomA || ! $roomB || ! $roomC) {
            $this->command->warn('Expected at least 3 rooms in BR-001. Found ' . $rooms->count() . '. Run CompanyBranchSeeder first.');
            return;
        }

        // ── Services ──────────────────────────────────────────────────────────
        $laserService = Service::where('company_id', $company->id)->where('name', 'Full Legs')->first()
            ?? Service::where('company_id', $company->id)->first();

        $faceService = Service::where('company_id', $company->id)->where('name', 'Full Face')->first()
            ?? $laserService;

        $bodyService = Service::where('company_id', $company->id)->where('name', 'like', '%Body%')->first()
            ?? $laserService;

        $underarmService = Service::where('company_id', $company->id)->where('name', 'Underarms')->first()
            ?? $laserService;

        $ipl = Service::where('company_id', $company->id)->where('name', 'IPL Photofacial')->first()
            ?? $laserService;

        // Botox — create if not present
        $injectablesCat = ServiceCategory::where('company_id', $company->id)
            ->where('name', 'Injectables')
            ->first();

        if (! $injectablesCat) {
            $injectablesCat = ServiceCategory::create([
                'company_id' => $company->id,
                'name'       => 'Injectables',
                'color'      => '#DB2777',
            ]);
        }

        $botoxService = Service::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Botox — Forehead & Glabella'],
            [
                'category_id'      => $injectablesCat->id,
                'description'      => 'Botulinum toxin injection for forehead lines and frown lines.',
                'duration_minutes' => 30,
                'price'            => 1200,
                'is_active'        => true,
            ]
        );

        $fillerService = Service::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Dermal Filler — Lips'],
            [
                'category_id'      => $injectablesCat->id,
                'description'      => 'Hyaluronic acid filler for lip augmentation and definition.',
                'duration_minutes' => 45,
                'price'            => 1800,
                'is_active'        => true,
            ]
        );

        $consultService = Service::where('company_id', $company->id)
            ->whereIn('name', ['Initial Consultation', 'Consultation'])
            ->first();

        if (! $consultService) {
            $consultCat = ServiceCategory::where('company_id', $company->id)
                ->where('name', 'Consultations')->first();
            $consultService = Service::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Initial Consultation'],
                [
                    'category_id'      => $consultCat?->id,
                    'description'      => 'Initial skin and treatment consultation with doctor.',
                    'duration_minutes' => 30,
                    'price'            => 200,
                    'is_active'        => true,
                ]
            );
        }

        // ── Patients ──────────────────────────────────────────────────────────
        $newPatients = [
            ['first_name' => 'Sara',    'last_name' => 'Al Zaabi',    'phone' => '+971 50 555 0021', 'gender' => 'female'],
            ['first_name' => 'Nadia',   'last_name' => 'Hassan',      'phone' => '+971 55 555 0022', 'gender' => 'female'],
            ['first_name' => 'Manar',   'last_name' => 'Al Shehhi',   'phone' => '+971 56 555 0023', 'gender' => 'female'],
            ['first_name' => 'Hind',    'last_name' => 'Mahmoud',     'phone' => '+971 50 555 0024', 'gender' => 'female'],
            ['first_name' => 'Asma',    'last_name' => 'Al Bloushi',  'phone' => '+971 52 555 0025', 'gender' => 'female'],
            ['first_name' => 'Sheikha', 'last_name' => 'Al Rashidi',  'phone' => '+971 54 555 0026', 'gender' => 'female'],
            ['first_name' => 'Mariam',  'last_name' => 'Khalfan',     'phone' => '+971 58 555 0027', 'gender' => 'female'],
            ['first_name' => 'Lina',    'last_name' => 'Al Marzouqi', 'phone' => '+971 50 555 0028', 'gender' => 'female'],
        ];

        $patients = collect();
        foreach ($newPatients as $pd) {
            $patients->push(Patient::firstOrCreate(
                ['phone' => $pd['phone'], 'company_id' => $company->id],
                array_merge($pd, [
                    'company_id'        => $company->id,
                    'branch_id'         => $branch1->id,
                    'status'            => 'active',
                    'consent_given'     => true,
                    'consent_given_at'  => now()->subMonths(1),
                    'registration_date' => now()->subMonths(rand(1, 6))->format('Y-m-d'),
                ])
            ));
        }

        // Also pull in some existing branch patients
        $existing = Patient::where('branch_id', $branch1->id)
            ->whereNotIn('phone', array_column($newPatients, 'phone'))
            ->limit(5)
            ->get();
        $patients = $patients->merge($existing);

        // Helper ── build one appointment
        $book = function (int $pi, int $roomId, int $h, int $m, int $staffId, int $serviceId, string $status, ?string $notes = null) use ($company, $branch1, $secretary, $patients): void {
            $patient = $patients->values()->get($pi % $patients->count());
            if (! $patient) {
                return;
            }
            Appointment::create([
                'company_id'        => $company->id,
                'branch_id'         => $branch1->id,
                'patient_id'        => $patient->id,
                'service_id'        => $serviceId,
                'room_id'           => $roomId,
                'assigned_staff_id' => $staffId,
                'booked_by'         => $secretary?->id,
                'appointment_type'  => 'booked',
                'scheduled_at'      => today()->setHour($h)->setMinute($m)->setSecond(0),
                'duration_minutes'  => 60,
                'status'            => $status,
                'reason_notes'      => $notes,
                'arrived_at'        => in_array($status, ['arrived', 'checked_in', 'in_treatment', 'completed']) ? today()->setHour($h)->setMinute($m + 5) : null,
                'completed_at'      => $status === 'completed' ? today()->setHour($h)->setMinute($m + 55) : null,
            ]);
        };

        // ── Today's schedule ──────────────────────────────────────────────────
        // Laser Room A — tech1 heavy day
        $book(0, $roomA->id,  9,  0,  $tech1->id,  $laserService->id,    'completed',   'Completed session 4 of 6. Good response — next in 6 weeks.');
        $book(1, $roomA->id, 10,  0,  $tech1->id,  $faceService->id,     'in_treatment', 'Sensitive skin — lower fluence setting used.');
        $book(2, $roomA->id, 11, 30,  $tech1->id,  $underarmService->id, 'arrived');
        $book(3, $roomA->id, 13,  0,  $tech1->id,  $laserService->id,    'confirmed');
        $book(4, $roomA->id, 15,  0,  $tech1->id,  $faceService->id,     'booked');

        // Laser Room B — tech2 + intentional double booking at 11:00
        $book(5, $roomB->id,  9, 30,  $tech2->id,  $bodyService->id,     'completed',   'Cryolipolysis session 2. Measuring results today.');
        $book(6, $roomB->id, 11,  0,  $tech1->id,  $laserService->id,    'confirmed');   // ← double booking
        $book(7, $roomB->id, 11,  0,  $tech2->id,  $faceService->id,     'confirmed',   '⚠ Double-booked — please reassign one of these sessions.');
        $book(0, $roomB->id, 13, 30,  $tech2->id,  $ipl->id,             'booked');
        $book(1, $roomB->id, 15, 30,  $tech2->id,  $laserService->id,    'booked');
        $book(2, $roomB->id, 17,  0,  $tech2->id,  $bodyService->id,     'booked');

        // Laser Room C — tech1 and tech2 sharing
        $book(3, $roomC->id, 10,  0,  $tech2->id,  $faceService->id,     'arrived');
        $book(4, $roomC->id, 12,  0,  $tech1->id,  $laserService->id,    'confirmed');
        $book(5, $roomC->id, 14,  0,  $tech2->id,  $underarmService->id, 'booked');
        $book(6, $roomC->id, 16,  0,  $tech1->id,  $laserService->id,    'booked');

        // Consultation Room — doctor-led appointments
        $book(0, $roomConsult->id,  9,  0,  $doctor->id, $consultService->id, 'completed',   'New patient — recommended full face LHR package. Follow-up in 2 weeks.');
        $book(1, $roomConsult->id, 10,  0,  $doctor->id, $botoxService->id,   'in_treatment', 'Forehead lines — 20 units Botox. Patient prefers no brow-lift effect.');
        $book(2, $roomConsult->id, 11,  0,  $doctor->id, $botoxService->id,   'arrived',     'Crow\'s feet — patch test done, no contraindications.');
        $book(3, $roomConsult->id, 12,  0,  $doctor->id, $fillerService->id,  'confirmed',   'Lip augmentation — 1ml HA filler. Patient wants natural look.');
        $book(4, $roomConsult->id, 14, 30,  $doctor->id, $consultService->id, 'confirmed');
        $book(5, $roomConsult->id, 16,  0,  $doctor->id, $botoxService->id,   'booked');

        $this->command->info('Schedule grid seeded successfully:');
        $this->command->info('  Rooms updated: Laser Room A/B/C + Consultation Room');
        $this->command->info('  Today\'s appointments: ~22 across 4 rooms (including 1 double-booked slot)');
        $this->command->info('  Staff mix: tech1 (Rania), tech2 (Nour), doctor (Dr. Hana)');
        $this->command->info('  New login: doctor.marina@medflow.local / Doctor@123!');
    }
}
