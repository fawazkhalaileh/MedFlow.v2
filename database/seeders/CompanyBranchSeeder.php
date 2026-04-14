<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Room;
use Illuminate\Database\Seeder;

class CompanyBranchSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::create([
            'name'     => 'MedFlow Laser Clinics',
            'slug'     => 'medflow',
            'email'    => 'info@medflow.ae',
            'phone'    => '+971 4 000 0000',
            'address'  => 'Dubai, United Arab Emirates',
            'timezone' => 'Asia/Dubai',
            'currency' => 'JOD',
            'status'   => 'active',
            'settings' => [
                'clinic_type'       => 'laser',
                'skin_type_system'  => 'fitzpatrick',
                'consent_required'  => true,
                'default_session_duration' => 60,
            ],
        ]);

        $branches = [
            [
                'name'    => 'Dubai Marina',
                'code'    => 'BR-001',
                'email'   => 'marina@medflow.ae',
                'phone'   => '+971 4 111 1111',
                'address' => 'Marina Walk, Dubai Marina',
                'city'    => 'Dubai',
                'country' => 'UAE',
                'status'  => 'active',
                'working_hours' => [
                    'mon' => ['open' => '09:00', 'close' => '21:00'],
                    'tue' => ['open' => '09:00', 'close' => '21:00'],
                    'wed' => ['open' => '09:00', 'close' => '21:00'],
                    'thu' => ['open' => '09:00', 'close' => '21:00'],
                    'fri' => ['open' => '10:00', 'close' => '22:00'],
                    'sat' => ['open' => '10:00', 'close' => '22:00'],
                    'sun' => ['open' => '11:00', 'close' => '20:00'],
                ],
                'rooms' => ['Room A', 'Room B', 'Room C', 'Consultation Room'],
            ],
            [
                'name'    => 'Jumeirah',
                'code'    => 'BR-002',
                'email'   => 'jumeirah@medflow.ae',
                'phone'   => '+971 4 222 2222',
                'address' => 'Jumeirah Beach Road, Jumeirah',
                'city'    => 'Dubai',
                'country' => 'UAE',
                'status'  => 'active',
                'working_hours' => [
                    'mon' => ['open' => '09:00', 'close' => '20:00'],
                    'tue' => ['open' => '09:00', 'close' => '20:00'],
                    'wed' => ['open' => '09:00', 'close' => '20:00'],
                    'thu' => ['open' => '09:00', 'close' => '20:00'],
                    'fri' => ['open' => '10:00', 'close' => '21:00'],
                    'sat' => ['open' => '10:00', 'close' => '21:00'],
                    'sun' => ['open' => '12:00', 'close' => '19:00'],
                ],
                'rooms' => ['Room 1', 'Room 2', 'VIP Suite'],
            ],
            [
                'name'    => 'Business Bay',
                'code'    => 'BR-003',
                'email'   => 'businessbay@medflow.ae',
                'phone'   => '+971 4 333 3333',
                'address' => 'Bay Square, Business Bay',
                'city'    => 'Dubai',
                'country' => 'UAE',
                'status'  => 'active',
                'working_hours' => [
                    'mon' => ['open' => '08:00', 'close' => '20:00'],
                    'tue' => ['open' => '08:00', 'close' => '20:00'],
                    'wed' => ['open' => '08:00', 'close' => '20:00'],
                    'thu' => ['open' => '08:00', 'close' => '20:00'],
                    'fri' => ['open' => '10:00', 'close' => '21:00'],
                    'sat' => ['open' => '10:00', 'close' => '21:00'],
                    'sun' => null, // closed
                ],
                'rooms' => ['Treatment Room 1', 'Treatment Room 2'],
            ],
        ];

        foreach ($branches as $data) {
            $rooms = $data['rooms'];
            unset($data['rooms']);

            $branch = Branch::create(array_merge($data, ['company_id' => $company->id]));

            foreach ($rooms as $roomName) {
                Room::create(['branch_id' => $branch->id, 'name' => $roomName]);
            }
        }
    }
}
