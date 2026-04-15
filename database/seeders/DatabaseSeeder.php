<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompanyBranchSeeder::class,   // 1. Company + branches + rooms
            RolePermissionSeeder::class,  // 2. Roles + permissions matrix
            AdminSeeder::class,           // 3. Staff accounts (5 employees)
            ServiceSeeder::class,         // 4. Services + categories + appointment reasons
            DemoDataSeeder::class,        // 5. Customers + plans + sessions + notes
            ScheduleGridSeeder::class,    // 6. Today's scheduling grid (rooms + doctor + appointments)
        ]);
    }
}
