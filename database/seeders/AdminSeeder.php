<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $company  = Company::first();
        $branches = Branch::where('company_id', $company->id)->get();
        $superAdminRole = Role::where('company_id', $company->id)->where('name', 'super_admin')->first();
        $managerRole    = Role::where('company_id', $company->id)->where('name', 'branch_manager')->first();
        $secretaryRole  = Role::where('company_id', $company->id)->where('name', 'secretary')->first();
        $techRole       = Role::where('company_id', $company->id)->where('name', 'technician')->first();

        $branch1 = $branches->firstWhere('code', 'BR-001');
        $branch2 = $branches->firstWhere('code', 'BR-002');

        // Super Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@medflow.local'],
            [
                'company_id'        => $company->id,
                'employee_id'       => 'EMP-001',
                'name'              => 'System Administrator',
                'first_name'        => 'System',
                'last_name'         => 'Administrator',
                'email'             => 'admin@medflow.local',
                'password'          => Hash::make('Admin@MedFlow2024!'),
                'role'              => 'admin',
                'employee_type'     => 'system_admin',
                'employment_status' => 'active',
                'primary_branch_id' => $branch1?->id,
                'email_verified_at' => now(),
            ]
        );

        if ($superAdminRole && $branch1) {
            $admin->branches()->syncWithoutDetaching([
                $branch1->id => ['role_id' => $superAdminRole->id, 'is_primary' => true],
            ]);
        }

        // Branch Manager - Dubai Marina
        $manager = User::firstOrCreate(
            ['email' => 'manager.marina@medflow.local'],
            [
                'company_id'        => $company->id,
                'employee_id'       => 'EMP-002',
                'name'              => 'Sara Al Hassan',
                'first_name'        => 'Sara',
                'last_name'         => 'Al Hassan',
                'password'          => Hash::make('Manager@123!'),
                'role'              => 'branch_manager',
                'employee_type'     => 'branch_manager',
                'employment_status' => 'active',
                'primary_branch_id' => $branch1?->id,
                'hire_date'         => '2023-01-15',
                'email_verified_at' => now(),
            ]
        );

        if ($managerRole && $branch1) {
            $manager->branches()->syncWithoutDetaching([
                $branch1->id => ['role_id' => $managerRole->id, 'is_primary' => true],
            ]);
            $branch1->update(['manager_id' => $manager->id]);
        }

        // Secretary
        $secretary = User::firstOrCreate(
            ['email' => 'reception.marina@medflow.local'],
            [
                'company_id'        => $company->id,
                'employee_id'       => 'EMP-003',
                'name'              => 'Fatima Khalid',
                'first_name'        => 'Fatima',
                'last_name'         => 'Khalid',
                'password'          => Hash::make('Secretary@123!'),
                'role'              => 'secretary',
                'employee_type'     => 'secretary',
                'employment_status' => 'active',
                'primary_branch_id' => $branch1?->id,
                'hire_date'         => '2023-03-01',
                'email_verified_at' => now(),
            ]
        );

        if ($secretaryRole && $branch1) {
            $secretary->branches()->syncWithoutDetaching([
                $branch1->id => ['role_id' => $secretaryRole->id, 'is_primary' => true],
            ]);
        }

        // Technician 1
        $tech1 = User::firstOrCreate(
            ['email' => 'tech1.marina@medflow.local'],
            [
                'company_id'        => $company->id,
                'employee_id'       => 'EMP-004',
                'name'              => 'Rania Yousef',
                'first_name'        => 'Rania',
                'last_name'         => 'Yousef',
                'password'          => Hash::make('Tech@123!'),
                'role'              => 'technician',
                'employee_type'     => 'technician',
                'employment_status' => 'active',
                'primary_branch_id' => $branch1?->id,
                'hire_date'         => '2023-02-10',
                'specialties'       => ['Laser Hair Removal', 'IPL', 'Skin Rejuvenation'],
                'certifications'    => [
                    ['name' => 'Laser Safety Operator', 'issued_by' => 'DHA', 'date' => '2023-01-01'],
                ],
                'email_verified_at' => now(),
            ]
        );

        if ($techRole && $branch1) {
            $tech1->branches()->syncWithoutDetaching([
                $branch1->id => ['role_id' => $techRole->id, 'is_primary' => true],
            ]);
        }

        // Technician 2
        $tech2 = User::firstOrCreate(
            ['email' => 'tech2.marina@medflow.local'],
            [
                'company_id'        => $company->id,
                'employee_id'       => 'EMP-005',
                'name'              => 'Nour Ibrahim',
                'first_name'        => 'Nour',
                'last_name'         => 'Ibrahim',
                'password'          => Hash::make('Tech@123!'),
                'role'              => 'technician',
                'employee_type'     => 'technician',
                'employment_status' => 'active',
                'primary_branch_id' => $branch1?->id,
                'hire_date'         => '2023-04-20',
                'specialties'       => ['Body Contouring', 'Tattoo Removal'],
                'email_verified_at' => now(),
            ]
        );

        if ($techRole && $branch1) {
            $tech2->branches()->syncWithoutDetaching([
                $branch1->id => ['role_id' => $techRole->id, 'is_primary' => true],
            ]);
        }

        $this->command->info('Staff created:');
        $this->command->info('  admin@medflow.local           / Admin@MedFlow2024!  (Super Admin)');
        $this->command->info('  manager.marina@medflow.local  / Manager@123!        (Branch Manager)');
        $this->command->info('  reception.marina@medflow.local/ Secretary@123!      (Secretary)');
        $this->command->info('  tech1.marina@medflow.local    / Tech@123!           (Technician)');
        $this->command->info('  tech2.marina@medflow.local    / Tech@123!           (Technician)');
    }
}
