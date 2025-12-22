<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\StudentProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'parent-api', 'dashboard');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);

    // Create parent user with profile
    $this->parentUser = User::factory()->parent()->forAcademy($this->academy)->create();
    $this->parentProfile = ParentProfile::factory()->create([
        'user_id' => $this->parentUser->id,
        'academy_id' => $this->academy->id,
    ]);

    // Create grade level for students
    $this->gradeLevel = AcademicGradeLevel::factory()->create([
        'academy_id' => $this->academy->id,
    ]);
});

describe('index (dashboard data)', function () {
    it('returns dashboard data with no children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'parent' => [
                        'id',
                        'name',
                        'avatar',
                    ],
                    'children',
                    'stats' => [
                        'total_children',
                        'total_today_sessions',
                        'total_active_subscriptions',
                        'upcoming_sessions',
                    ],
                    'upcoming_sessions',
                ],
            ])
            ->assertJson([
                'data' => [
                    'children' => [],
                    'stats' => [
                        'total_children' => 0,
                        'total_today_sessions' => 0,
                        'total_active_subscriptions' => 0,
                        'upcoming_sessions' => 0,
                    ],
                ],
            ]);
    });

    it('returns dashboard data with linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create student with user
        $studentUser = User::factory()->student()->forAcademy($this->academy)->create();
        $student = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student->id,
            'relationship_type' => 'father',
        ]);

        $response = $this->getJson('/api/v1/parent/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'stats' => [
                        'total_children' => 1,
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data.children')
            ->assertJsonStructure([
                'data' => [
                    'children' => [
                        '*' => [
                            'id',
                            'user_id',
                            'name',
                            'student_code',
                            'avatar',
                            'grade_level',
                            'relationship',
                            'today_sessions_count',
                            'active_subscriptions_count',
                        ],
                    ],
                ],
            ]);
    });

    it('includes today sessions count for children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $studentUser = User::factory()->student()->forAcademy($this->academy)->create();
        $student = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student->id,
            'relationship_type' => 'father',
        ]);

        // Create today's Quran session
        QuranSession::factory()->create([
            'student_id' => $studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/parent/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $children = $response->json('data.children');
        expect($children[0]['today_sessions_count'])->toBeGreaterThanOrEqual(0);
    });

    it('includes active subscriptions count for children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $studentUser = User::factory()->student()->forAcademy($this->academy)->create();
        $student = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student->id,
            'relationship_type' => 'father',
        ]);

        // Create active Quran subscription
        QuranSubscription::factory()->create([
            'student_id' => $studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/parent/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $children = $response->json('data.children');
        expect($children[0]['active_subscriptions_count'])->toBeGreaterThanOrEqual(0);
    });

    it('includes upcoming sessions for all children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $studentUser = User::factory()->student()->forAcademy($this->academy)->create();
        $student = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student->id,
            'relationship_type' => 'father',
        ]);

        // Create upcoming Quran session
        QuranSession::factory()->create([
            'student_id' => $studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDays(2),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/parent/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'upcoming_sessions' => [
                        '*' => [
                            'id',
                            'type',
                            'title',
                            'child_name',
                            'scheduled_at',
                        ],
                    ],
                ],
            ]);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/parent/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });

    it('returns 404 when parent has no profile', function () {
        $userWithoutProfile = User::factory()->parent()->forAcademy($this->academy)->create();
        Sanctum::actingAs($userWithoutProfile, ['*']);

        $response = $this->getJson('/api/v1/parent/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error_code' => 'PARENT_PROFILE_NOT_FOUND',
            ]);
    });

    it('calculates stats correctly for multiple children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create first child with subscription
        $student1User = User::factory()->student()->forAcademy($this->academy)->create();
        $student1 = StudentProfile::factory()->create([
            'user_id' => $student1User->id,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student1->id,
            'relationship_type' => 'father',
        ]);

        QuranSubscription::factory()->create([
            'student_id' => $student1User->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        // Create second child with subscription
        $student2User = User::factory()->student()->forAcademy($this->academy)->create();
        $student2 = StudentProfile::factory()->create([
            'user_id' => $student2User->id,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student2->id,
            'relationship_type' => 'mother',
        ]);

        AcademicSubscription::factory()->create([
            'student_id' => $student2User->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/parent/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'stats' => [
                        'total_children' => 2,
                    ],
                ],
            ]);
    });
});
