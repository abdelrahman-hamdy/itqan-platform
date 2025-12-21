<?php

namespace Tests\Feature\Api;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for Authentication API endpoints.
 *
 * Tests cover:
 * - Login
 * - Logout
 * - User info (me)
 * - Token management
 * - Password reset
 */
class AuthApiTest extends TestCase
{
    use DatabaseTransactions;

    protected ?Academy $academy = null;
    protected string $testId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->academy = $this->createAcademy(['subdomain' => 'itqan-academy']);
        $this->testId = uniqid();
    }

    /**
     * Make an API request with academy context.
     */
    protected function apiPost(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ])->postJson($uri, $data);
    }

    protected function apiGet(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ])->getJson($uri);
    }

    protected function apiDelete(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ])->deleteJson($uri);
    }

    /**
     * Create a test user with known credentials.
     */
    protected function createTestUser(string $type = 'student'): User
    {
        return User::factory()->$type()->create([
            'academy_id' => $this->academy->id,
            'email' => "{$type}_{$this->testId}@test.local",
            'password' => Hash::make('password123'),
        ]);
    }

    // ==================== LOGIN TESTS ====================

    /**
     * Test successful login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->createTestUser('student');

        $response = $this->apiPost('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'email'],
                ],
            ]);
    }

    /**
     * Test login fails with invalid password.
     */
    public function test_login_fails_with_invalid_password(): void
    {
        $user = $this->createTestUser('student');

        $response = $this->apiPost('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test login fails with non-existent email.
     */
    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->apiPost('/api/v1/login', [
            'email' => 'nonexistent@test.local',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test login requires email field.
     */
    public function test_login_requires_email(): void
    {
        $response = $this->apiPost('/api/v1/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test login requires password field.
     */
    public function test_login_requires_password(): void
    {
        $response = $this->apiPost('/api/v1/login', [
            'email' => 'test@test.local',
        ]);

        $response->assertStatus(422);
    }

    // ==================== LOGOUT TESTS ====================

    /**
     * Test authenticated user can logout.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->createTestUser('student');
        Sanctum::actingAs($user);

        $response = $this->apiPost('/api/v1/logout');

        $response->assertStatus(200);
    }

    /**
     * Test unauthenticated user cannot logout.
     */
    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->apiPost('/api/v1/logout');

        $response->assertStatus(401);
    }

    // ==================== ME ENDPOINT TESTS ====================

    /**
     * Test authenticated user can get their info.
     */
    public function test_authenticated_user_can_get_their_info(): void
    {
        $user = $this->createTestUser('student');
        Sanctum::actingAs($user);

        $response = $this->apiGet('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.email', $user->email);
    }

    /**
     * Test unauthenticated user cannot get info.
     */
    public function test_unauthenticated_user_cannot_get_info(): void
    {
        $response = $this->apiGet('/api/v1/me');

        $response->assertStatus(401);
    }

    // ==================== TOKEN MANAGEMENT TESTS ====================

    /**
     * Test token validation endpoint.
     */
    public function test_valid_token_passes_validation(): void
    {
        $user = $this->createTestUser('student');

        // Create an actual token instead of Sanctum::actingAs() which creates TransientToken
        $token = $user->createToken('test-token', ['read', 'write', 'student:*'], now()->addDays(30));

        $response = $this->withHeaders([
            'X-Academy-Subdomain' => $this->academy->subdomain,
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ])->getJson('/api/v1/token/validate');

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', true);
    }

    /**
     * Test invalid token fails validation.
     */
    public function test_invalid_token_fails_validation(): void
    {
        $response = $this->apiGet('/api/v1/token/validate');

        $response->assertStatus(401);
    }

    /**
     * Test token refresh endpoint.
     */
    public function test_authenticated_user_can_refresh_token(): void
    {
        $user = $this->createTestUser('student');
        Sanctum::actingAs($user);

        $response = $this->apiPost('/api/v1/token/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['token'],
            ]);
    }

    /**
     * Test token revoke endpoint.
     */
    public function test_authenticated_user_can_revoke_current_token(): void
    {
        $user = $this->createTestUser('student');
        Sanctum::actingAs($user);

        $response = $this->apiDelete('/api/v1/token/revoke');

        $response->assertStatus(200);
    }

    /**
     * Test revoke all tokens endpoint.
     */
    public function test_authenticated_user_can_revoke_all_tokens(): void
    {
        $user = $this->createTestUser('student');
        Sanctum::actingAs($user);

        $response = $this->apiDelete('/api/v1/token/revoke-all');

        $response->assertStatus(200);
    }

    // ==================== ROLE-BASED LOGIN TESTS ====================

    /**
     * Test parent user can login.
     */
    public function test_parent_user_can_login(): void
    {
        $user = $this->createTestUser('parent');

        $response = $this->apiPost('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test quran teacher can login.
     */
    public function test_quran_teacher_can_login(): void
    {
        $user = $this->createTestUser('quranTeacher');

        $response = $this->apiPost('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test academic teacher can login.
     */
    public function test_academic_teacher_can_login(): void
    {
        $user = $this->createTestUser('academicTeacher');

        $response = $this->apiPost('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
    }
}
