<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Academy;
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
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@itqan.com'],
            [
                'academy_id' => null, // Super admin doesn't belong to any specific academy
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone' => '+966501234567',
                'user_type' => 'super_admin',
                'status' => 'active',
                'password' => Hash::make('password123'),
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
        $this->command->info('Email: admin@itqan.com');
        $this->command->info('Password: password123');
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
        $this->command->info('🔑 All user passwords: password123');
    }
}
