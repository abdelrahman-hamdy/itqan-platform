<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSession;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\QuranSession;
use App\Models\StudentProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'parent-api', 'sessions');

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

describe('index (list all sessions)', function () {
    it('returns empty list when no sessions exist', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/sessions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'sessions',
                    'pagination',
                ],
            ])
            ->assertJson([
                'data' => [
                    'sessions' => [],
                ],
            ]);
    });

    it('returns Quran sessions for linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/parent/sessions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.sessions')
            ->assertJsonStructure([
                'data' => [
                    'sessions' => [
                        '*' => [
                            'id',
                            'type',
                            'child_id',
                            'child_name',
                            'status',
                            'title',
                            'teacher_name',
                            'scheduled_at',
                            'duration_minutes',
                        ],
                    ],
                ],
            ]);
    });

    it('returns Academic sessions for linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        AcademicSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/parent/sessions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.sessions');
    });

    it('filters sessions by child_id', function () {
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

        // Create sessions for both children
        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
        ]);

        QuranSession::factory()->create([
            'student_id' => $student2User->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/parent/sessions?child_id=' . $this->student->id, [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.sessions');
    });

    it('filters sessions by type', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
        ]);

        AcademicSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/parent/sessions?type=quran', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.sessions');
    });

    it('filters sessions by status', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->subDay(),
            'status' => 'completed',
        ]);

        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/parent/sessions?status=completed', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.sessions');
    });

    it('filters sessions by date range', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->subDays(10),
        ]);

        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/parent/sessions?from_date=' . now()->toDateString(), [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.sessions');
    });

    it('paginates results', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create 20 sessions
        QuranSession::factory()->count(20)->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/parent/sessions?per_page=10', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data.sessions')
            ->assertJsonStructure([
                'data' => [
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'total_pages',
                        'has_more',
                    ],
                ],
            ]);
    });

    it('does not show sessions of non-linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create another student not linked to this parent
        $otherStudentUser = User::factory()->student()->forAcademy($this->academy)->create();
        $otherStudent = StudentProfile::factory()->create([
            'user_id' => $otherStudentUser->id,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        QuranSession::factory()->create([
            'student_id' => $otherStudentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/parent/sessions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.sessions');
    });
});

describe('show (get specific session)', function () {
    it('returns Quran session details for linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $session = QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/v1/parent/sessions/quran/{$session->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'session' => [
                        'id',
                        'type',
                        'child_id',
                        'child_name',
                        'status',
                        'title',
                        'teacher',
                        'circle',
                        'homework',
                        'progress',
                        'scheduled_at',
                        'duration_minutes',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'session' => [
                        'id' => $session->id,
                        'type' => 'quran',
                    ],
                ],
            ]);
    });

    it('returns Academic session details for linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $session = AcademicSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/v1/parent/sessions/academic/{$session->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'session' => [
                        'id',
                        'type',
                        'child_id',
                        'child_name',
                        'status',
                        'title',
                        'teacher',
                        'subscription',
                        'homework',
                        'lesson_content',
                        'scheduled_at',
                    ],
                ],
            ]);
    });

    it('returns 404 for session of non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudentUser = User::factory()->student()->forAcademy($this->academy)->create();
        $session = QuranSession::factory()->create([
            'student_id' => $otherStudentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/v1/parent/sessions/quran/{$session->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('returns 404 for non-existent session', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/sessions/quran/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('today (get today\'s sessions)', function () {
    it('returns only today\'s sessions', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create today's session
        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        // Create tomorrow's session
        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/parent/sessions/today', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.sessions')
            ->assertJsonStructure([
                'data' => [
                    'sessions',
                    'total',
                    'date',
                ],
            ]);
    });

    it('excludes cancelled sessions', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'status' => 'cancelled',
        ]);

        $response = $this->getJson('/api/v1/parent/sessions/today', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.sessions');
    });

    it('returns sessions sorted by time', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $session1 = QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->setTime(10, 0),
            'status' => 'scheduled',
        ]);

        $session2 = QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->setTime(14, 0),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/parent/sessions/today', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $sessions = $response->json('data.sessions');
        expect($sessions[0]['id'])->toBe($session1->id);
    });
});

describe('upcoming (get upcoming sessions)', function () {
    it('returns future sessions only', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create past session
        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->subDay(),
            'status' => 'completed',
        ]);

        // Create future session
        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDays(2),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/parent/sessions/upcoming', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.sessions');
    });

    it('excludes cancelled and completed sessions', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'cancelled',
        ]);

        QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/parent/sessions/upcoming', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.sessions');
    });

    it('limits results to specified amount', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create 15 upcoming sessions
        QuranSession::factory()->count(15)->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDays(2),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/parent/sessions/upcoming?limit=5', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.sessions');
    });

    it('returns sessions sorted by scheduled time', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $session1 = QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDays(5),
            'status' => 'scheduled',
        ]);

        $session2 = QuranSession::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDays(2),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/parent/sessions/upcoming', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $sessions = $response->json('data.sessions');
        expect($sessions[0]['id'])->toBe($session2->id);
    });
});
