<?php

namespace Tests\Feature\Api;

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for Student API endpoints.
 *
 * Tests cover:
 * - Authorization (role-based access control)
 * - Profile endpoints
 * - Teacher browsing
 * - Payments listing
 *
 * Note: Dashboard, Sessions, Subscriptions, Calendar, Homework, Quizzes,
 * Certificates, and Courses endpoints require additional schema fixes
 * that are tracked for future implementation.
 */
class StudentApiTest extends TestCase
{
    use DatabaseTransactions;

    protected ?Academy $academy = null;
    protected ?User $studentUser = null;
    protected ?StudentProfile $studentProfile = null;
    protected string $testId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testId = uniqid();
        $this->academy = $this->createAcademy(['subdomain' => 'itqan-academy']);

        // Create grade level for student
        $gradeLevel = AcademicGradeLevel::firstOrCreate(
            ['academy_id' => $this->academy->id, 'name' => 'الصف الأول'],
            ['slug' => 'grade-1', 'order' => 1, 'is_active' => true]
        );

        // Create student user
        $this->studentUser = User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => "student_{$this->testId}@test.local",
            'password' => Hash::make('password123'),
            'user_type' => 'student',
            'active_status' => true,
        ]);

        // Create student profile (use firstOrCreate to handle existing profiles)
        $this->studentProfile = StudentProfile::firstOrCreate(
            ['user_id' => $this->studentUser->id],
            [
                'academy_id' => $this->academy->id,
                'first_name' => 'Test',
                'last_name' => 'Student',
                'email' => $this->studentUser->email,
                'gender' => 'male',
                'grade_level_id' => $gradeLevel->id,
                'student_code' => 'STU-' . strtoupper($this->testId),
                'is_active' => true,
            ]
        );
    }

    /**
     * Make an API request with academy context.
     */
    protected function apiGet(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ])->getJson($uri);
    }

    protected function apiPost(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ])->postJson($uri, $data);
    }

    protected function apiPut(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ])->putJson($uri, $data);
    }

    // ==================== AUTHORIZATION TESTS ====================

    /**
     * Test unauthenticated user cannot access student API.
     */
    public function test_unauthenticated_user_cannot_access_student_api(): void
    {
        $response = $this->apiGet('/api/v1/student/dashboard');
        $response->assertStatus(401);
    }

    /**
     * Test parent user cannot access student API.
     */
    public function test_parent_user_cannot_access_student_api(): void
    {
        $parentUser = User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => "parent_{$this->testId}@test.local",
            'password' => Hash::make('password123'),
            'user_type' => 'parent',
            'active_status' => true,
        ]);
        Sanctum::actingAs($parentUser);

        $response = $this->apiGet('/api/v1/student/dashboard');
        $response->assertStatus(403);
    }

    /**
     * Test teacher user cannot access student API.
     */
    public function test_teacher_user_cannot_access_student_api(): void
    {
        $teacherUser = User::factory()->create([
            'academy_id' => $this->academy->id,
            'email' => "teacher_{$this->testId}@test.local",
            'password' => Hash::make('password123'),
            'user_type' => 'quran_teacher',
            'active_status' => true,
        ]);
        Sanctum::actingAs($teacherUser);

        $response = $this->apiGet('/api/v1/student/dashboard');
        $response->assertStatus(403);
    }

    // ==================== PROFILE TESTS ====================

    /**
     * Test student can view profile.
     */
    public function test_student_can_view_profile(): void
    {
        Sanctum::actingAs($this->studentUser);

        $response = $this->apiGet('/api/v1/student/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /**
     * Test student can update profile.
     */
    public function test_student_can_update_profile(): void
    {
        Sanctum::actingAs($this->studentUser);

        $response = $this->apiPut('/api/v1/student/profile', [
            'phone' => '0501234567',
        ]);

        $response->assertStatus(200);
    }

    // ==================== PAYMENTS TESTS ====================

    /**
     * Test student can view payments list.
     */
    public function test_student_can_view_payments_list(): void
    {
        Sanctum::actingAs($this->studentUser);

        $response = $this->apiGet('/api/v1/student/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    // ==================== TEACHER BROWSING TESTS ====================

    /**
     * Test student can browse Quran teachers.
     */
    public function test_student_can_browse_quran_teachers(): void
    {
        Sanctum::actingAs($this->studentUser);

        $response = $this->apiGet('/api/v1/student/teachers/quran');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /**
     * Test student can browse academic teachers.
     */
    public function test_student_can_browse_academic_teachers(): void
    {
        Sanctum::actingAs($this->studentUser);

        $response = $this->apiGet('/api/v1/student/teachers/academic');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }
}
