<?php

use App\Models\Academy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * API V1 Authentication - Password Reset Tests
 *
 * Tests the password reset flow:
 * - Forgot password (request reset link)
 * - Verify reset token
 * - Reset password
 * - Rate limiting
 */
beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'name' => 'Test Academy',
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('POST /api/v1/forgot-password', function () {

    it('sends reset link for valid email', function () {
        $user = User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => 'forgot@test.com',
            'active_status' => true,
        ]);

        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'forgot@test.com',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify token was created
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'forgot@test.com',
        ]);
    });

    it('returns success even for non-existent email (security)', function () {
        // For security, we shouldn't reveal if email exists or not
        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'nonexistent@test.com',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        // Should still return success to not reveal if email exists
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    });

    it('validates email format', function () {
        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'not-an-email',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('requires email field', function () {
        $response = $this->postJson('/api/v1/forgot-password', [], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

});

describe('POST /api/v1/verify-reset-token', function () {

    it('validates correct token', function () {
        $user = User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => 'verify@test.com',
        ]);

        // Create a reset token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => 'verify@test.com',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/verify-reset-token', [
            'email' => 'verify@test.com',
            'token' => $token,
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    });

    it('rejects invalid token', function () {
        $user = User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => 'invalid-token@test.com',
        ]);

        // Create a reset token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => 'invalid-token@test.com',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/verify-reset-token', [
            'email' => 'invalid-token@test.com',
            'token' => 'wrong-token',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(400);
    });

    it('rejects expired token', function () {
        $user = User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => 'expired@test.com',
        ]);

        // Create an expired token (2 hours old)
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => 'expired@test.com',
            'token' => Hash::make($token),
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->postJson('/api/v1/verify-reset-token', [
            'email' => 'expired@test.com',
            'token' => $token,
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(400);
    });

});

describe('POST /api/v1/reset-password', function () {

    it('successfully resets password with valid token', function () {
        $user = User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => 'reset@test.com',
            'password' => Hash::make('OldPassword123'),
        ]);

        // Create a reset token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => 'reset@test.com',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => 'reset@test.com',
            'token' => $token,
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify password was updated
        $user->refresh();
        expect(Hash::check('NewSecurePassword123', $user->password))->toBeTrue();

        // Verify token was deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'reset@test.com',
        ]);
    });

    it('validates password requirements', function () {
        $response = $this->postJson('/api/v1/reset-password', [
            'email' => 'reset@test.com',
            'token' => 'some-token',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('validates password confirmation', function () {
        $response = $this->postJson('/api/v1/reset-password', [
            'email' => 'reset@test.com',
            'token' => 'some-token',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'DifferentPassword123',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('rejects invalid token', function () {
        $user = User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => 'invalid-reset@test.com',
        ]);

        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => 'invalid-reset@test.com',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => 'invalid-reset@test.com',
            'token' => 'wrong-token',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ], [
            'X-Academy-Subdomain' => 'test-academy',
        ]);

        $response->assertStatus(400);
    });

});
