<?php

namespace Tests\Feature\Api;

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\StudentProfile;
use App\Models\User;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Enums\SessionStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for Parent API endpoints.
 *
 * Tests cover:
 * - Authentication and authorization
 * - Dashboard endpoint
 * - Children management
 * - Sessions listing
 * - Profile management
 */
class ParentApiTest extends TestCase
{
    use DatabaseTransactions;

    protected ?ParentProfile $parentProfile = null;
    protected ?StudentProfile $studentProfile = null;
    protected ?User $parentUser = null;
    protected ?User $studentUser = null;
    protected string $testId;

    protected function setUp(): void
    {
        parent::setUp();
        // Create academy with default subdomain to work with multi-tenancy scopes
        $this->createAcademy(['subdomain' => 'itqan-academy']);
        $this->testId = uniqid();
        $this->createParentWithChild();
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

    /**
     * Make an API PUT request with academy context.
     */
    protected function apiPut(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ])->putJson($uri, $data);
    }

    /**
     * Create a parent user with linked child.
     * Uses the auto-created profiles from User::boot method.
     * Note: We use withoutGlobalScopes() because StudentProfile has
     * ScopedToAcademyViaRelationship which filters by academy context.
     */
    protected function createParentWithChild(): void
    {
        // Create a grade level for the academy (required for StudentProfile scoping)
        // Use AcademicGradeLevel which is what StudentProfile.gradeLevel relation uses
        $gradeLevel = AcademicGradeLevel::withoutGlobalScopes()->create([
            'academy_id' => $this->academy->id,
            'name' => 'Test Grade Level',
            'name_en' => 'Test Grade Level',
            'is_active' => true,
        ]);

        // Create parent user - profile is auto-created by User boot method
        $this->parentUser = User::factory()->parent()->create([
            'academy_id' => $this->academy->id,
            'email' => "parent_{$this->testId}@test.local",
        ]);

        // Get the auto-created profile without global scopes
        $this->parentProfile = ParentProfile::withoutGlobalScopes()
            ->where('user_id', $this->parentUser->id)
            ->first();

        // If profile wasn't auto-created, create it manually
        if (!$this->parentProfile) {
            $this->parentProfile = ParentProfile::create([
                'academy_id' => $this->academy->id,
                'user_id' => $this->parentUser->id,
                'first_name' => $this->parentUser->first_name,
                'last_name' => $this->parentUser->last_name,
                'email' => $this->parentUser->email,
                'phone' => '0501234567',
                'is_active' => true,
            ]);
        } else {
            // Ensure the academy_id matches the test's academy
            $this->parentProfile->update(['academy_id' => $this->academy->id]);
        }

        // Create student user - profile is auto-created by User boot method
        $this->studentUser = User::factory()->student()->create([
            'academy_id' => $this->academy->id,
            'email' => "student_{$this->testId}@test.local",
        ]);

        // Get the auto-created profile without global scopes
        $this->studentProfile = StudentProfile::withoutGlobalScopes()
            ->where('user_id', $this->studentUser->id)
            ->first();

        // If profile wasn't auto-created, create it manually
        if (!$this->studentProfile) {
            $this->studentProfile = StudentProfile::create([
                'user_id' => $this->studentUser->id,
                'first_name' => $this->studentUser->first_name,
                'last_name' => $this->studentUser->last_name,
                'email' => $this->studentUser->email,
                'student_code' => 'STU-' . $this->testId,
                'grade_level_id' => $gradeLevel->id,
                'is_active' => true,
            ]);
        } else {
            // Ensure the grade_level_id is set for the scoping to work
            $this->studentProfile->update(['grade_level_id' => $gradeLevel->id]);
        }

        // Link parent to student
        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $this->studentProfile->id,
            'relationship_type' => 'father',
        ]);
    }

    /**
     * Test unauthenticated request returns 401.
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->apiGet('/api/v1/parent/dashboard');

        $response->assertStatus(401);
    }

    /**
     * Test non-parent user cannot access parent API.
     */
    public function test_non_parent_user_cannot_access_parent_api(): void
    {
        $student = User::factory()->student()->create([
            'academy_id' => $this->academy->id,
            'email' => "student_other_{$this->testId}@test.local",
        ]);

        Sanctum::actingAs($student);

        $response = $this->apiGet('/api/v1/parent/dashboard');

        $response->assertStatus(403);
    }

    /**
     * Test parent can access dashboard.
     */
    public function test_parent_can_access_dashboard(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet('/api/v1/parent/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'parent' => ['id', 'name'],
                    'children',
                    'stats' => [
                        'total_children',
                        'total_today_sessions',
                        'total_active_subscriptions',
                        'upcoming_sessions',
                    ],
                ],
            ]);
    }

    /**
     * Test parent can view linked children.
     */
    public function test_parent_can_view_children(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet('/api/v1/parent/children');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'children' => [
                        '*' => ['id', 'name'],
                    ],
                    'total',
                ],
            ]);
    }

    /**
     * Test parent can view specific child.
     */
    public function test_parent_can_view_specific_child(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet("/api/v1/parent/children/{$this->studentProfile->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.child.id', $this->studentProfile->id);
    }

    /**
     * Test parent cannot view unlinked child.
     */
    public function test_parent_cannot_view_unlinked_child(): void
    {
        // Create another student not linked to this parent
        $otherStudentUser = User::factory()->student()->create([
            'academy_id' => $this->academy->id,
            'email' => "other_student_{$this->testId}@test.local",
        ]);

        // Get auto-created profile without global scopes
        $otherStudent = StudentProfile::withoutGlobalScopes()
            ->where('user_id', $otherStudentUser->id)
            ->first();

        // If not auto-created, create manually
        if (!$otherStudent) {
            $otherStudent = StudentProfile::create([
                'user_id' => $otherStudentUser->id,
                'first_name' => $otherStudentUser->first_name,
                'last_name' => $otherStudentUser->last_name,
                'email' => $otherStudentUser->email,
                'student_code' => 'STU-OTHER-' . $this->testId,
                'is_active' => true,
            ]);
        }

        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet("/api/v1/parent/children/{$otherStudent->id}");

        // API returns 404 (not 403) to prevent child ID enumeration
        $response->assertStatus(404);
    }

    /**
     * Test parent can view profile.
     */
    public function test_parent_can_view_profile(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet('/api/v1/parent/profile');

        $response->assertStatus(200)
            ->assertJsonPath('data.profile.email', $this->parentUser->email);
    }

    /**
     * Test parent can update profile.
     */
    public function test_parent_can_update_profile(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->putJson('/api/v1/parent/profile', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $response->assertStatus(200);

        $this->parentProfile->refresh();
        $this->assertEquals('Updated', $this->parentProfile->first_name);
    }

    /**
     * Test parent can view sessions.
     */
    public function test_parent_can_view_sessions(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet('/api/v1/parent/sessions');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /**
     * Test parent can view today's sessions.
     */
    public function test_parent_can_view_today_sessions(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet('/api/v1/parent/sessions/today');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /**
     * Test parent can view upcoming sessions.
     */
    public function test_parent_can_view_upcoming_sessions(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet('/api/v1/parent/sessions/upcoming');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /**
     * Test parent can view subscriptions.
     */
    public function test_parent_can_view_subscriptions(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet('/api/v1/parent/subscriptions');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /**
     * Test parent can view reports progress.
     */
    public function test_parent_can_view_reports_progress(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet('/api/v1/parent/reports/progress');

        $response->assertStatus(200);
    }

    /**
     * Test parent can view reports attendance.
     */
    public function test_parent_can_view_reports_attendance(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet('/api/v1/parent/reports/attendance');

        $response->assertStatus(200);
    }

    /**
     * Test parent can view child quizzes.
     */
    public function test_parent_can_view_child_quizzes(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet("/api/v1/parent/children/{$this->studentProfile->id}/quizzes");

        $response->assertStatus(200);
    }

    /**
     * Test parent can view child certificates.
     */
    public function test_parent_can_view_child_certificates(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet("/api/v1/parent/children/{$this->studentProfile->id}/certificates");

        $response->assertStatus(200);
    }

    /**
     * Test parent can view payments.
     */
    public function test_parent_can_view_payments(): void
    {
        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet('/api/v1/parent/payments');

        $response->assertStatus(200);
    }

    /**
     * Test dashboard shows correct children count.
     */
    public function test_dashboard_shows_correct_children_count(): void
    {
        // Add another child
        $studentUser2 = User::factory()->student()->create([
            'academy_id' => $this->academy->id,
            'email' => "student2_{$this->testId}@test.local",
        ]);

        // Get auto-created profile without global scopes
        $studentProfile2 = StudentProfile::withoutGlobalScopes()
            ->where('user_id', $studentUser2->id)
            ->first();

        // If not auto-created, create manually
        if (!$studentProfile2) {
            $studentProfile2 = StudentProfile::create([
                'user_id' => $studentUser2->id,
                'first_name' => $studentUser2->first_name,
                'last_name' => $studentUser2->last_name,
                'email' => $studentUser2->email,
                'student_code' => 'STU-2-' . $this->testId,
                'is_active' => true,
            ]);
        }

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $studentProfile2->id,
            'relationship_type' => 'father',
        ]);

        Sanctum::actingAs($this->parentUser);

        $response = $this->apiGet('/api/v1/parent/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.stats.total_children', 2);
    }
}
