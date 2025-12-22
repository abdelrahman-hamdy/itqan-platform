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

uses()->group('api', 'parent-api', 'reports');

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

describe('progress (progress report)', function () {
    it('returns progress report for all children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/reports/progress', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'reports' => [
                        '*' => [
                            'child' => [
                                'id',
                                'name',
                                'avatar',
                                'grade_level',
                            ],
                            'quran' => [
                                'active_subscriptions',
                                'total_subscriptions',
                                'completed_sessions',
                                'total_sessions',
                            ],
                            'academic' => [
                                'active_subscriptions',
                                'total_subscriptions',
                                'completed_sessions',
                                'total_sessions',
                            ],
                            'courses' => [
                                'active_enrollments',
                                'completed_enrollments',
                                'total_enrollments',
                                'completed_sessions',
                                'total_sessions',
                            ],
                            'overall_stats' => [
                                'total_sessions_completed',
                                'attendance_rate',
                                'active_subscriptions',
                            ],
                        ],
                    ],
                ],
            ]);
    });

    it('returns progress report for specific child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson("/api/v1/parent/reports/progress/{$this->student->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'reports' => [
                        'child',
                        'quran',
                        'academic',
                        'courses',
                        'overall_stats',
                    ],
                ],
            ]);
    });

    it('includes Quran progress data', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        QuranSession::factory()->count(5)->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/parent/reports/progress', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $report = $response->json('data.reports')[0];
        expect($report['quran']['active_subscriptions'])->toBeGreaterThan(0);
        expect($report['quran']['completed_sessions'])->toBe(5);
    });

    it('includes Academic progress data', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        AcademicSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        AcademicSession::factory()->count(3)->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/parent/reports/progress', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $report = $response->json('data.reports')[0];
        expect($report['academic']['active_subscriptions'])->toBeGreaterThan(0);
        expect($report['academic']['completed_sessions'])->toBe(3);
    });

    it('returns 404 for non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudent = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $response = $this->getJson("/api/v1/parent/reports/progress/{$otherStudent->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('attendance (attendance report)', function () {
    it('returns attendance report for all children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/reports/attendance', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'reports' => [
                        '*' => [
                            'child' => [
                                'id',
                                'name',
                                'avatar',
                            ],
                            'period' => [
                                'start_date',
                                'end_date',
                            ],
                            'summary' => [
                                'total_sessions',
                                'attended',
                                'missed',
                                'attendance_rate',
                            ],
                            'by_type' => [
                                'quran',
                                'academic',
                                'course',
                            ],
                        ],
                    ],
                ],
            ]);
    });

    it('returns attendance report for specific child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson("/api/v1/parent/reports/attendance/{$this->student->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'reports' => [
                        'child',
                        'period',
                        'summary',
                        'by_type',
                    ],
                ],
            ]);
    });

    it('filters by date range', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $startDate = now()->subDays(7)->toDateString();
        $endDate = now()->toDateString();

        $response = $this->getJson("/api/v1/parent/reports/attendance?start_date={$startDate}&end_date={$endDate}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $report = $response->json('data.reports')[0];
        expect($report['period']['start_date'])->toBe($startDate);
        expect($report['period']['end_date'])->toBe($endDate);
    });

    it('defaults to last 30 days when no dates provided', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/reports/attendance', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $report = $response->json('data.reports')[0];
        expect($report['period']['start_date'])->not()->toBeNull();
        expect($report['period']['end_date'])->not()->toBeNull();
    });

    it('calculates attendance statistics correctly', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create completed sessions (attended)
        QuranSession::factory()->count(8)->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'completed',
            'scheduled_at' => now()->subDays(5),
        ]);

        // Create cancelled sessions (missed)
        QuranSession::factory()->count(2)->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'cancelled',
            'scheduled_at' => now()->subDays(5),
        ]);

        $response = $this->getJson('/api/v1/parent/reports/attendance', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $report = $response->json('data.reports')[0];
        expect($report['summary']['total_sessions'])->toBe(10);
        expect($report['summary']['attended'])->toBe(8);
        expect($report['summary']['missed'])->toBe(2);
    });

    it('breaks down attendance by session type', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        QuranSession::factory()->count(5)->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'completed',
            'scheduled_at' => now()->subDays(5),
        ]);

        AcademicSession::factory()->count(3)->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'completed',
            'scheduled_at' => now()->subDays(5),
        ]);

        $response = $this->getJson('/api/v1/parent/reports/attendance', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $report = $response->json('data.reports')[0];
        expect($report['by_type']['quran']['total'])->toBe(5);
        expect($report['by_type']['academic']['total'])->toBe(3);
    });
});

describe('subscription (subscription report)', function () {
    it('returns detailed Quran subscription report', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'sessions_count' => 20,
            'status' => 'active',
        ]);

        QuranSession::factory()->count(5)->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'quran_subscription_id' => $subscription->id,
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/v1/parent/reports/subscription/quran/{$subscription->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'report' => [
                        'subscription' => [
                            'id',
                            'type',
                            'name',
                            'status',
                            'start_date',
                            'end_date',
                        ],
                        'child' => [
                            'id',
                            'name',
                        ],
                        'teacher',
                        'progress' => [
                            'sessions_total',
                            'sessions_completed',
                            'sessions_remaining',
                            'completion_percentage',
                        ],
                        'attendance' => [
                            'total_scheduled',
                            'attended',
                            'missed',
                            'attendance_rate',
                        ],
                        'recent_sessions',
                        'upcoming_sessions',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'report' => [
                        'subscription' => [
                            'id' => $subscription->id,
                            'type' => 'quran',
                        ],
                    ],
                ],
            ]);
    });

    it('returns detailed Academic subscription report', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $subscription = AcademicSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'sessions_count' => 15,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/parent/reports/subscription/academic/{$subscription->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'report' => [
                        'subscription',
                        'child',
                        'teacher',
                        'progress',
                        'attendance',
                        'recent_sessions',
                        'upcoming_sessions',
                    ],
                ],
            ]);
    });

    it('calculates progress percentage correctly', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'sessions_count' => 10,
            'status' => 'active',
        ]);

        QuranSession::factory()->count(7)->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'quran_subscription_id' => $subscription->id,
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/v1/parent/reports/subscription/quran/{$subscription->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $progress = $response->json('data.report.progress');
        expect($progress['sessions_completed'])->toBe(7);
        expect($progress['sessions_total'])->toBe(10);
        expect($progress['completion_percentage'])->toBe(70.0);
    });

    it('returns 404 for subscription of non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudentUser = User::factory()->student()->forAcademy($this->academy)->create();
        $subscription = QuranSubscription::factory()->create([
            'student_id' => $otherStudentUser->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson("/api/v1/parent/reports/subscription/quran/{$subscription->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('returns 404 for non-existent subscription', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/reports/subscription/quran/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('includes recent and upcoming sessions', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        // Create recent completed sessions
        QuranSession::factory()->count(3)->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'quran_subscription_id' => $subscription->id,
            'status' => 'completed',
            'scheduled_at' => now()->subDays(2),
        ]);

        // Create upcoming sessions
        QuranSession::factory()->count(2)->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'quran_subscription_id' => $subscription->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(2),
        ]);

        $response = $this->getJson("/api/v1/parent/reports/subscription/quran/{$subscription->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $report = $response->json('data.report');
        expect(count($report['recent_sessions']))->toBeGreaterThan(0);
        expect(count($report['upcoming_sessions']))->toBeGreaterThan(0);
    });
});

describe('authorization', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/parent/reports/progress', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });

    it('returns 404 when parent has no profile', function () {
        $userWithoutProfile = User::factory()->parent()->forAcademy($this->academy)->create();
        Sanctum::actingAs($userWithoutProfile, ['*']);

        $response = $this->getJson('/api/v1/parent/reports/progress', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error_code' => 'PARENT_PROFILE_NOT_FOUND',
            ]);
    });
});
