<?php

use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'academic', 'sessions');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Academic Session API', function () {
    describe('list sessions', function () {
        it('returns academic sessions for teacher', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->count(3)->create([
                'academic_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/academic/sessions', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'sessions',
                        'pagination',
                    ],
                ]);

            expect(count($response->json('data.sessions')))->toBeGreaterThanOrEqual(3);
        });

        it('filters sessions by status', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->count(2)->create([
                'academic_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'completed',
            ]);

            AcademicSession::factory()->create([
                'academic_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/academic/sessions?status=completed', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $sessions = $response->json('data.sessions');
            $completedCount = count(array_filter($sessions, fn($s) => $s['status'] === 'completed'));
            expect($completedCount)->toBeGreaterThanOrEqual(2);
        });

        it('only shows teacher own sessions', function () {
            $teacher1 = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile1 = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher1->id,
                'academy_id' => $this->academy->id,
            ]);

            $teacher2 = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile2 = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher2->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->count(2)->create([
                'academic_teacher_id' => $profile1->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            AcademicSession::factory()->count(3)->create([
                'academic_teacher_id' => $profile2->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson('/api/v1/teacher/academic/sessions', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $sessions = $response->json('data.sessions');
            $individualSessions = array_filter($sessions, fn($s) => $s['type'] === 'individual');
            expect(count($individualSessions))->toBe(2);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/academic/sessions', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('show session', function () {
        it('returns academic session details', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academic_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/academic/sessions/{$session->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'session' => [
                            'id',
                            'type',
                            'title',
                            'student',
                            'subject',
                            'scheduled_at',
                            'status',
                            'homework',
                            'lesson_content',
                            'topics_covered',
                        ],
                    ],
                ]);
        });

        it('prevents access to other teachers sessions', function () {
            $teacher1 = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile1 = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher1->id,
                'academy_id' => $this->academy->id,
            ]);

            $teacher2 = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile2 = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher2->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academic_teacher_id' => $profile2->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson("/api/v1/teacher/academic/sessions/{$session->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/academic/sessions/1', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('complete session', function () {
        it('completes a session', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academic_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/academic/sessions/{$session->id}/complete", [
                'homework' => 'Complete exercises 1-10',
                'lesson_content' => 'Algebra basics',
                'topics_covered' => ['Variables', 'Equations'],
                'notes' => 'Good progress',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $session->refresh();
            expect($session->status->value)->toBe('completed');
            expect($session->homework)->toBe('Complete exercises 1-10');
        });

        it('prevents completing already completed session', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academic_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'completed',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/academic/sessions/{$session->id}/complete", [], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(400);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/v1/teacher/academic/sessions/1/complete', [], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('cancel session', function () {
        it('cancels a session with reason', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academic_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/academic/sessions/{$session->id}/cancel", [
                'reason' => 'Teacher is unavailable',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $session->refresh();
            expect($session->status->value)->toBe('cancelled');
            expect($session->cancellation_reason)->toBe('Teacher is unavailable');
        });

        it('requires cancellation reason', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academic_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'status' => 'scheduled',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/academic/sessions/{$session->id}/cancel", [], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['reason']);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/v1/teacher/academic/sessions/1/cancel', [
                'reason' => 'Some reason',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('update evaluation', function () {
        it('updates session evaluation', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $session = AcademicSession::factory()->create([
                'academic_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->putJson("/api/v1/teacher/academic/sessions/{$session->id}/evaluation", [
                'homework' => 'New homework',
                'lesson_content' => 'Updated content',
                'rating' => 4,
                'feedback' => 'Great progress',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $session->refresh();
            expect($session->homework)->toBe('New homework');
            expect($session->lesson_content)->toBe('Updated content');
        });

        it('requires authentication', function () {
            $response = $this->putJson('/api/v1/teacher/academic/sessions/1/evaluation', [
                'rating' => 4,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });
});
