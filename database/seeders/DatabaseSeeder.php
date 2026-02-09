<?php

namespace Database\Seeders;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Super Admin (no academy association)
        $superAdminEmail = config('seeding.super_admin.email');
        $superAdminPassword = config('seeding.default_password');

        $superAdmin = User::firstOrCreate(
            ['email' => $superAdminEmail],
            [
                'academy_id' => null, // Super admin doesn't belong to any specific academy
                'first_name' => config('seeding.super_admin.first_name'),
                'last_name' => config('seeding.super_admin.last_name'),
                'phone' => '+966501234567',
                'user_type' => 'super_admin',
                'status' => 'active',
                'active_status' => true,
                'password' => Hash::make($superAdminPassword),
                'email_verified_at' => now(),
            ]
        );

        // Call the comprehensive seeder to populate all data
        $this->call([
            ComprehensiveDataSeeder::class,
        ]);

        // Output login credentials
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('🔐 Super Admin Login Credentials:');
        $this->command->info('Email: '.$superAdminEmail);
        $this->command->info('Password: '.$superAdminPassword);
        $this->command->info('URL: http://localhost:8000/admin');
        $this->command->info('');
        $this->command->info('📊 Comprehensive data has been seeded including:');
        $this->command->info('- Multiple academies with full user populations');
        $this->command->info('- Grade levels, subjects, and courses');
        $this->command->info('- Quran packages and subscriptions');
        $this->command->info('- Recorded courses with sections and lessons');
        $this->command->info('- Interactive courses and Quran circles');
        $this->command->info('- Student progress and payments');
        $this->command->info('');
        $this->command->info('🔑 All user passwords: '.$superAdminPassword);
    }
}
