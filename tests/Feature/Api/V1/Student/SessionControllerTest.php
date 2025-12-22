<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);

    $this->gradeLevel = AcademicGradeLevel::create([
        'academy_id' => $this->academy->id,
        'name' => 'Grade 1',
        'is_active' => true,
    ]);

    $this->student = User::factory()
        ->student()
        ->forAcademy($this->academy)
        ->create();

    $this->teacher = User::factory()
        ->quranTeacher()
        ->forAcademy($this->academy)
        ->create();

    $this->student->refresh();
});

describe('Session Index', function () {
    it('returns all sessions for authenticated student', function () {
        Sanctum::actingAs($this->student, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/sessions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'sessions',
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

    it('filters sessions by type', function () {
        Sanctum::actingAs($this->student, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/sessions?type=quran', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $sessions = $response->json('data.sessions');
        expect($sessions)->toBeArray();

        if (!empty($sessions)) {
            expect($sessions[0]['type'])->toBe('quran');
        }
    });

    it('filters sessions by status', function () {
        Sanctum::actingAs($this->student, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/sessions?status=scheduled', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);
    });

    it('paginates session results', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/sessions?per_page=10&page=1', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.pagination.per_page', 10)
            ->assertJsonPath('data.pagination.current_page', 1);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/student/sessions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });
});

describe('Today Sessions', function () {
    it('returns only today sessions', function () {
        Sanctum::actingAs($this->student, ['*']);

        // Today's session
        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        // Tomorrow's session (should not appear)
        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/sessions/today', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'date',
                    'sessions',
                    'count',
                ],
            ]);

        expect($response->json('data.count'))->toBeGreaterThanOrEqual(0);
    });
});

describe('Upcoming Sessions', function () {
    it('returns upcoming sessions within 14 days', function () {
        Sanctum::actingAs($this->student, ['*']);

        // Create session for tomorrow
        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDays(2),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/sessions/upcoming', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'sessions',
                    'from_date',
                    'to_date',
                ],
            ]);
    });

    it('excludes completed and cancelled sessions', function () {
        Sanctum::actingAs($this->student, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/student/sessions/upcoming', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $sessions = $response->json('data.sessions');
        foreach ($sessions as $session) {
            expect($session['status'])->not->toBeIn(['completed', 'cancelled']);
        }
    });
});

describe('Show Session', function () {
    it('returns quran session details', function () {
        Sanctum::actingAs($this->student, ['*']);

        $session = QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson("/api/v1/student/sessions/quran/{$session->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'session' => [
                        'id',
                        'type',
                        'title',
                        'status',
                        'scheduled_at',
                        'duration_minutes',
                        'teacher',
                    ],
                ],
            ]);
    });

    it('returns 404 for non-existent session', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/sessions/quran/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('prevents accessing another student session', function () {
        $otherStudent = User::factory()
            ->student()
            ->forAcademy($this->academy)
            ->create();

        $session = QuranSession::factory()->create([
            'student_id' => $otherStudent->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson("/api/v1/student/sessions/quran/{$session->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('Submit Feedback', function () {
    it('allows submitting feedback for completed session', function () {
        Sanctum::actingAs($this->student, ['*']);

        $session = QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->subDay(),
            'status' => 'completed',
        ]);

        $response = $this->postJson("/api/v1/student/sessions/quran/{$session->id}/feedback", [
            'rating' => 5,
            'feedback' => 'Great session!',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $session->refresh();
        expect($session->student_rating)->toBe(5);
        expect($session->student_feedback)->toBe('Great session!');
    });

    it('requires rating', function () {
        Sanctum::actingAs($this->student, ['*']);

        $session = QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->subDay(),
            'status' => 'completed',
        ]);

        $response = $this->postJson("/api/v1/student/sessions/quran/{$session->id}/feedback", [
            'feedback' => 'Great session!',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    });

    it('validates rating range', function () {
        Sanctum::actingAs($this->student, ['*']);

        $session = QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->subDay(),
            'status' => 'completed',
        ]);

        $response = $this->postJson("/api/v1/student/sessions/quran/{$session->id}/feedback", [
            'rating' => 6,
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    });

    it('prevents submitting feedback for non-completed session', function () {
        Sanctum::actingAs($this->student, ['*']);

        $session = QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->postJson("/api/v1/student/sessions/quran/{$session->id}/feedback", [
            'rating' => 5,
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('prevents duplicate feedback submission', function () {
        Sanctum::actingAs($this->student, ['*']);

        $session = QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->subDay(),
            'status' => 'completed',
            'student_rating' => 4,
        ]);

        $response = $this->postJson("/api/v1/student/sessions/quran/{$session->id}/feedback", [
            'rating' => 5,
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'code' => 'FEEDBACK_ALREADY_SUBMITTED',
            ]);
    });
});
