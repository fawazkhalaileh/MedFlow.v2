<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@medflow.local'],
            [
                'name'              => 'System Administrator',
                'email'             => 'admin@medflow.local',
                'password'          => Hash::make('Admin@MedFlow2024!'),
                'role'              => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user ready:');
        $this->command->info('  Email:    admin@medflow.local');
        $this->command->info('  Password: Admin@MedFlow2024!');
        $this->command->warn('  Change the password after first login!');
    }
}
