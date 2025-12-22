<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\QuranSubscription;
use App\Models\StudentProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'parent-api', 'subscriptions');

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

    // Create student with user
    $this->studentUser = User::factory()->student()->forAcademy($this->academy)->create();
    $this->gradeLevel = AcademicGradeLevel::factory()->create([
        'academy_id' => $this->academy->id,
    ]);
    $this->student = StudentProfile::factory()->create([
        'user_id' => $this->studentUser->id,
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    // Link student to parent
    ParentStudentRelationship::create([
        'parent_id' => $this->parentProfile->id,
        'student_id' => $this->student->id,
        'relationship_type' => 'father',
    ]);
});

describe('index (list all subscriptions)', function () {
    it('returns empty list when no subscriptions exist', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/subscriptions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'subscriptions',
                    'total',
                ],
            ])
            ->assertJson([
                'data' => [
                    'subscriptions' => [],
                    'total' => 0,
                ],
            ]);
    });

    it('returns Quran subscriptions for linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/parent/subscriptions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.subscriptions')
            ->assertJsonStructure([
                'data' => [
                    'subscriptions' => [
                        '*' => [
                            'id',
                            'type',
                            'child_id',
                            'child_name',
                            'name',
                            'teacher_name',
                            'status',
                            'sessions_total',
                            'sessions_used',
                            'sessions_remaining',
                            'price',
                            'currency',
                            'payment_status',
                            'start_date',
                            'end_date',
                            'created_at',
                        ],
                    ],
                ],
            ]);
    });

    it('returns Academic subscriptions for linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        AcademicSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/parent/subscriptions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.subscriptions');
    });

    it('returns Course subscriptions for linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $course = InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        CourseSubscription::factory()->create([
            'student_id' => $this->student->id, // Uses StudentProfile.id
            'interactive_course_id' => $course->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/parent/subscriptions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.subscriptions');
    });

    it('filters subscriptions by child_id', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create another child
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

        QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
        ]);

        QuranSubscription::factory()->create([
            'student_id' => $student2User->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson('/api/v1/parent/subscriptions?child_id=' . $this->student->id, [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.subscriptions');
    });

    it('filters subscriptions by status', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'expired',
        ]);

        $response = $this->getJson('/api/v1/parent/subscriptions?status=active', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.subscriptions');
    });

    it('filters subscriptions by type', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
        ]);

        AcademicSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson('/api/v1/parent/subscriptions?type=quran', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.subscriptions');
    });

    it('does not show subscriptions of non-linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudentUser = User::factory()->student()->forAcademy($this->academy)->create();

        QuranSubscription::factory()->create([
            'student_id' => $otherStudentUser->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson('/api/v1/parent/subscriptions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.subscriptions');
    });

    it('sorts subscriptions by created date descending', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $sub1 = QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'created_at' => now()->subDays(5),
        ]);

        $sub2 = QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->getJson('/api/v1/parent/subscriptions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $subscriptions = $response->json('data.subscriptions');
        expect($subscriptions[0]['id'])->toBe($sub2->id);
    });
});

describe('show (get specific subscription)', function () {
    it('returns Quran subscription details for linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/parent/subscriptions/quran/{$subscription->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'subscription' => [
                        'id',
                        'type',
                        'child_name',
                        'name',
                        'circle_type',
                        'teacher',
                        'status',
                        'sessions_total',
                        'sessions_used',
                        'sessions_remaining',
                        'price',
                        'currency',
                        'payment_status',
                        'start_date',
                        'end_date',
                        'auto_renew',
                        'schedule',
                        'recent_sessions',
                        'created_at',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'subscription' => [
                        'id' => $subscription->id,
                        'type' => 'quran',
                    ],
                ],
            ]);
    });

    it('returns Academic subscription details for linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $subscription = AcademicSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/parent/subscriptions/academic/{$subscription->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'subscription' => [
                        'id',
                        'type',
                        'child_name',
                        'name',
                        'subject',
                        'teacher',
                        'status',
                        'sessions_total',
                        'sessions_used',
                        'sessions_remaining',
                        'recent_sessions',
                    ],
                ],
            ]);
    });

    it('returns Course subscription details for linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $course = InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        $subscription = CourseSubscription::factory()->create([
            'student_id' => $this->student->id,
            'interactive_course_id' => $course->id,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/parent/subscriptions/course/{$subscription->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'subscription' => [
                        'id',
                        'type',
                        'course',
                        'teacher',
                        'status',
                        'progress_percentage',
                        'completed_sessions',
                    ],
                ],
            ]);
    });

    it('returns 404 for subscription of non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudentUser = User::factory()->student()->forAcademy($this->academy)->create();
        $subscription = QuranSubscription::factory()->create([
            'student_id' => $otherStudentUser->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson("/api/v1/parent/subscriptions/quran/{$subscription->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('returns 404 for non-existent subscription', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/subscriptions/quran/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('validates subscription type parameter', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/subscriptions/invalid/1', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        // Should return 404 since route won't match
        $response->assertStatus(404);
    });
});

describe('authorization', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/parent/subscriptions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });

    it('returns 404 when parent has no profile', function () {
        $userWithoutProfile = User::factory()->parent()->forAcademy($this->academy)->create();
        Sanctum::actingAs($userWithoutProfile, ['*']);

        $response = $this->getJson('/api/v1/parent/subscriptions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error_code' => 'PARENT_PROFILE_NOT_FOUND',
            ]);
    });

    it('prevents viewing subscriptions from different academy', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherAcademy = Academy::factory()->create([
            'subdomain' => 'other-academy',
            'is_active' => true,
        ]);

        $otherStudentUser = User::factory()->student()->forAcademy($otherAcademy)->create();

        QuranSubscription::factory()->create([
            'student_id' => $otherStudentUser->id,
            'academy_id' => $otherAcademy->id,
        ]);

        $response = $this->getJson('/api/v1/parent/subscriptions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.subscriptions');
    });
});
