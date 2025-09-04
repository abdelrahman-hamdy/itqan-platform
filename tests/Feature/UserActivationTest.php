<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivationTest extends TestCase
{
    use RefreshDatabase;

    protected Academy $academy;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test academy
        $this->academy = Academy::create([
            'name' => 'Test Academy',
            'subdomain' => 'test-academy',
            'is_active' => true,
            'maintenance_mode' => false,
        ]);
    }

    /** @test */
    public function users_with_proper_activation_can_access_system()
    {
        // Create users with proper activation
        $users = [
            'student' => User::create([
                'first_name' => 'Test',
                'last_name' => 'Student',
                'email' => 'student@test.com',
                'phone' => '+966501234567',
                'user_type' => 'student',
                'status' => 'active',
                'active_status' => true,
                'academy_id' => $this->academy->id,
                'password' => bcrypt('password'),
            ]),
            'teacher' => User::create([
                'first_name' => 'Test',
                'last_name' => 'Teacher',
                'email' => 'teacher@test.com',
                'phone' => '+966501234568',
                'user_type' => 'quran_teacher',
                'status' => 'active',
                'active_status' => true,
                'academy_id' => $this->academy->id,
                'password' => bcrypt('password'),
            ]),
            'admin' => User::create([
                'first_name' => 'Test',
                'last_name' => 'Admin',
                'email' => 'admin@test.com',
                'phone' => '+966501234569',
                'user_type' => 'admin',
                'status' => 'active',
                'active_status' => true,
                'academy_id' => $this->academy->id,
                'password' => bcrypt('password'),
            ]),
        ];

        // Test that all users are properly activated
        foreach ($users as $type => $user) {
            $this->assertTrue($user->isActive(), "User type {$type} should be active");
            $this->assertEquals('active', $user->status);
            $this->assertTrue($user->active_status);
        }
    }

    /** @test */
    public function users_with_inactive_status_cannot_access_system()
    {
        // Create user with inactive status
        $inactiveUser = User::create([
            'first_name' => 'Inactive',
            'last_name' => 'User',
            'email' => 'inactive@test.com',
            'phone' => '+966501234570',
            'user_type' => 'student',
            'status' => 'active',
            'active_status' => false, // This should make isActive() return false
            'academy_id' => $this->academy->id,
            'password' => bcrypt('password'),
        ]);

        $this->assertFalse($inactiveUser->isActive(), 'User with active_status=false should not be active');

        // Test login attempt should fail
        $response = $this->post(route('login', ['subdomain' => $this->academy->subdomain]), [
            'email' => 'inactive@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email' => 'حسابك غير نشط. يرجى التواصل مع الإدارة']);
        $this->assertGuest();
    }

    /** @test */
    public function users_with_suspended_status_cannot_access_system()
    {
        // Create user with suspended status
        $suspendedUser = User::create([
            'first_name' => 'Suspended',
            'last_name' => 'User',
            'email' => 'suspended@test.com',
            'phone' => '+966501234571',
            'user_type' => 'student',
            'status' => 'suspended',
            'active_status' => true,
            'academy_id' => $this->academy->id,
            'password' => bcrypt('password'),
        ]);

        $this->assertFalse($suspendedUser->isActive(), 'User with suspended status should not be active');
    }

    /** @test */
    public function login_redirects_work_correctly_for_different_user_types()
    {
        $testCases = [
            'student' => ['user_type' => 'student', 'expected_route_contains' => 'profile'],
            'quran_teacher' => ['user_type' => 'quran_teacher', 'expected_route_contains' => 'teacher'],
            'admin' => ['user_type' => 'admin', 'expected_route_contains' => 'panel'],
        ];

        foreach ($testCases as $type => $config) {
            $user = User::create([
                'first_name' => 'Test',
                'last_name' => ucfirst($type),
                'email' => "{$type}@test.com",
                'phone' => '+96650123456'.rand(0, 9),
                'user_type' => $config['user_type'],
                'status' => 'active',
                'active_status' => true,
                'academy_id' => $this->academy->id,
                'password' => bcrypt('password'),
            ]);

            $this->assertTrue($user->isActive(), "User {$type} should be active for login test");
        }
    }
}
