<?php

namespace Database\Seeders;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create academy
        $academy = Academy::first();

        if (! $academy) {
            $academy = Academy::create([
                'name' => 'Itqan Academy',
                'subdomain' => 'itqan-academy',
                'is_active' => true,
                'maintenance_mode' => false,
            ]);
        }

        $email = config('seeding.super_admin.email');
        $password = config('seeding.super_admin.password');

        // Delete existing user if exists
        User::where('email', $email)->forceDelete();

        // Create super admin using DB insert to bypass model restrictions
        // Super admin has NO academy_id - they manage the whole platform
        \DB::table('users')->insert([
            'academy_id' => null,
            'first_name' => config('seeding.super_admin.first_name'),
            'last_name' => config('seeding.super_admin.last_name'),
            'email' => $email,
            'password' => Hash::make($password),
            'gender' => 'male',
            'phone' => '0500000000',
            'phone_country_code' => '+966',
            'email_verified_at' => now(),
            'status' => 'active',
            'active_status' => 1,
            'user_type' => 'super_admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: '.$email);
        $this->command->info('Password: '.$password);
    }
}
