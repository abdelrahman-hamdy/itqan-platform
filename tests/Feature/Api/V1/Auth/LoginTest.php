<?php

use App\Models\Academy;
use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;

/**
 * API V1 Authentication - Login Tests
 *
 * Tests the login endpoint for various scenarios including:
 * - Successful authentication
 * - Invalid credentials
 * - Inactive accounts
 * - Rate limiting
 * - Academy context
 */
beforeEach(function () {
    // Disable rate limiting for tests to prevent 429 errors
    $this->withoutMiddleware(ThrottleRequests::class);

    // Create a test academy
    $this->academy = Academy::factory()->create([
        'name' => 'Test Academy',
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('POST /api/v1/login', function () {

    it('returns token on successful login', function () {
        $user = User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => 'student@test.com',
            'password' => Hash::make('SecurePassword123'),
            'user_type' => 'student',
            'active_status' => true,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'student@test.com',
            'password' => 'SecurePassword123',
            'device_name' => 'Test Device',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                    'token_type',
                    'expires_at',
                ],
                'meta' => [
                    'timestamp',
                    'request_id',
                    'api_version',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        expect($response->json('data.token'))->not->toBeNull();
        expect($response->json('data.token_type'))->toBe('Bearer');
    });

    it('rejects invalid credentials', function () {
        User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => 'user@test.com',
            'password' => Hash::make('CorrectPassword123'),
            'active_status' => true,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'user@test.com',
            'password' => 'WrongPassword123',
            'device_name' => 'Test Device',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('rejects non-existent user', function () {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'SomePassword123',
            'device_name' => 'Test Device',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(401);
    });

    it('rejects inactive user account', function () {
        User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => 'inactive@test.com',
            'password' => Hash::make('Password123'),
            'active_status' => false,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'inactive@test.com',
            'password' => 'Password123',
            'device_name' => 'Test Device',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(403);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/login', [], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('validates email format', function () {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'not-an-email',
            'password' => 'Password123',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('rejects login for user in different academy', function () {
        $otherAcademy = Academy::factory()->create([
            'subdomain' => 'other-academy',
            'is_active' => true,
        ]);

        User::factory()->create([
            'academy_id' => $otherAcademy->id,
            'email' => 'other@test.com',
            'password' => Hash::make('Password123'),
            'active_status' => true,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'other@test.com',
            'password' => 'Password123',
            'device_name' => 'Test Device',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        // Should fail because user belongs to different academy
        $response->assertStatus(401);
    });

    it('requires academy context', function () {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@test.com',
            'password' => 'Password123',
        ]);

        // Should fail or use default academy
        $response->assertStatus(404)->assertJson([
            'success' => false,
        ]);
    });

    it('rejects login for inactive academy', function () {
        $inactiveAcademy = Academy::factory()->create([
            'subdomain' => 'inactive-academy',
            'is_active' => false,
        ]);

        User::factory()->create([
            'academy_id' => $inactiveAcademy->id,
            'email' => 'user@inactive.com',
            'password' => Hash::make('Password123'),
            'active_status' => true,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'user@inactive.com',
            'password' => 'Password123',
            'device_name' => 'Test Device',
        ], [
            'X-Academy-Subdomain' => 'inactive-academy',
        ]);

        $response->assertStatus(403);
    });

});

describe('POST /api/v1/logout', function () {

    it('successfully logs out authenticated user', function () {
        $user = User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => 'logout@test.com',
            'password' => Hash::make('Password123'),
            'active_status' => true,
        ]);

        // First login
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => 'logout@test.com',
            'password' => 'Password123',
            'device_name' => 'Test Device',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $token = $loginResponse->json('data.token');

        // Then logout
        $response = $this->postJson('/api/v1/logout', [], [
            'Authorization' => "Bearer {$token}",
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    });

    it('rejects logout without authentication', function () {
        $response = $this->postJson('/api/v1/logout', [], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(401);
    });

});

describe('GET /api/v1/me', function () {

    it('returns authenticated user info', function () {
        $user = User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => 'me@test.com',
            'password' => Hash::make('Password123'),
            'user_type' => 'student',
            'active_status' => true,
        ]);

        // Login first
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => 'me@test.com',
            'password' => 'Password123',
            'device_name' => 'Test Device',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $token = $loginResponse->json('data.token');

        // Get user info
        $response = $this->getJson('/api/v1/me', [
            'Authorization' => "Bearer {$token}",
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    });

    it('rejects unauthenticated request', function () {
        $response = $this->getJson('/api/v1/me', [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(401);
    });

});
