<?php

use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\QuranSessionReport;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'quran', 'sessions');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Quran Session API', function () {
    describe('list sessions', function () {
        it('returns quran sessions for teacher', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            QuranSession::factory()->count(5)->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addDay(),
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/quran/sessions', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'sessions',
                        'pagination',
                    ],
                ]);

            expect(count($response->json('data.sessions')))->toBe(5);
        });

        it('filters sessions by status', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            QuranSession::factory()->count(3)->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'completed',
            ]);

            QuranSession::factory()->count(2)->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/quran/sessions?status=completed', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.sessions')))->toBe(3);
        });

        it('filters sessions by date range', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addDays(5),
            ]);

            QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addDays(15),
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $fromDate = now()->toDateString();
            $toDate = now()->addDays(10)->toDateString();

            $response = $this->getJson("/api/v1/teacher/quran/sessions?from_date={$fromDate}&to_date={$toDate}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.sessions')))->toBe(1);
        });

        it('only shows teacher own sessions', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile1 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher1->id,
                'academy_id' => $this->academy->id,
            ]);

            $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile2 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher2->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            QuranSession::factory()->count(2)->create([
                'quran_teacher_id' => $profile1->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            QuranSession::factory()->count(3)->create([
                'quran_teacher_id' => $profile2->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson('/api/v1/teacher/quran/sessions', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.sessions')))->toBe(2);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/quran/sessions', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('show session', function () {
        it('returns session details', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/quran/sessions/{$session->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'session' => [
                            'id',
                            'title',
                            'student',
                            'circle',
                            'scheduled_at',
                            'duration_minutes',
                            'status',
                            'homework',
                            'evaluation',
                            'notes',
                        ],
                    ],
                ]);
        });

        it('prevents access to other teachers sessions', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile1 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher1->id,
                'academy_id' => $this->academy->id,
            ]);

            $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile2 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher2->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile2->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson("/api/v1/teacher/quran/sessions/{$session->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/quran/sessions/1', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('complete session', function () {
        it('completes a session with evaluation', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/quran/sessions/{$session->id}/complete", [
                'memorization_rating' => 4,
                'tajweed_rating' => 5,
                'current_surah' => 'Al-Baqarah',
                'current_page' => 10,
                'verses_memorized' => 5,
                'notes' => 'Good progress',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $session->refresh();
            expect($session->status->value)->toBe('completed');
            expect($session->memorization_rating)->toBe(4);
            expect($session->tajweed_rating)->toBe(5);
        });

        it('prevents completing already completed session', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'completed',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/quran/sessions/{$session->id}/complete", [
                'memorization_rating' => 4,
                'tajweed_rating' => 5,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(400);
        });

        it('validates evaluation data', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/quran/sessions/{$session->id}/complete", [
                'memorization_rating' => 10, // Invalid rating
                'current_page' => 700, // Invalid page number
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(422);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/v1/teacher/quran/sessions/1/complete', [], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('cancel session', function () {
        it('cancels a session with reason', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/quran/sessions/{$session->id}/cancel", [
                'reason' => 'Teacher is sick',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $session->refresh();
            expect($session->status->value)->toBe('cancelled');
            expect($session->cancellation_reason)->toBe('Teacher is sick');
        });

        it('requires cancellation reason', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/quran/sessions/{$session->id}/cancel", [], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['reason']);
        });

        it('prevents cancelling completed session', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'completed',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/quran/sessions/{$session->id}/cancel", [
                'reason' => 'Some reason',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(400);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/v1/teacher/quran/sessions/1/cancel', [
                'reason' => 'Some reason',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('evaluate session', function () {
        it('submits session evaluation', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/quran/sessions/{$session->id}/evaluate", [
                'memorization_rating' => 4,
                'tajweed_rating' => 5,
                'feedback' => 'Excellent progress',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $session->refresh();
            expect($session->memorization_rating)->toBe(4);
            expect($session->tajweed_rating)->toBe(5);
        });

        it('creates session report', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/quran/sessions/{$session->id}/evaluate", [
                'memorization_rating' => 4,
                'tajweed_rating' => 5,
                'feedback' => 'Great work',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $this->assertDatabaseHas('quran_session_reports', [
                'quran_session_id' => $session->id,
                'memorization_rating' => 4,
                'tajweed_rating' => 5,
            ]);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/v1/teacher/quran/sessions/1/evaluate', [
                'memorization_rating' => 4,
                'tajweed_rating' => 5,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('update notes', function () {
        it('updates session notes', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->putJson("/api/v1/teacher/quran/sessions/{$session->id}/notes", [
                'notes' => 'Student notes updated',
                'teacher_notes' => 'Teacher notes updated',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $session->refresh();
            expect($session->notes)->toBe('Student notes updated');
            expect($session->teacher_notes)->toBe('Teacher notes updated');
        });

        it('requires authentication', function () {
            $response = $this->putJson('/api/v1/teacher/quran/sessions/1/notes', [
                'notes' => 'Updated notes',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });
});
