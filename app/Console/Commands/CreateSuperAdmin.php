<?php

namespace App\Console\Commands;

use App\Enums\UserType;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    protected $signature = 'make:super-admin {email?}';

    protected $description = 'Create a super admin user';

    public function handle()
    {
        $email = $this->argument('email') ?: $this->ask('Enter email for super admin');
        $password = $this->secret('Enter password for super admin');

        // Super admin doesn't need an academy

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error('User with this email already exists.');

            return 1;
        }

        // Create super admin user
        $user = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'user_type' => UserType::SUPER_ADMIN->value,
            'status' => 'active',
            'active_status' => true,
            'academy_id' => null, // Super admin doesn't belong to any specific academy
            'email_verified_at' => now(),
        ]);

        $this->info('Super admin created successfully!');
        $this->info("Email: {$email}");
        $this->info('Access: /admin');

        return 0;
    }
}
