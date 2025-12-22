<?php

use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\HomeworkSubmission;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'homework');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Homework API', function () {
    describe('list homework', function () {
        it('returns homework list for academic teacher', function () {
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
                'homework' => 'Complete exercises',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/homework', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'homework',
                        'pagination',
                    ],
                ]);

            expect(count($response->json('data.homework')))->toBeGreaterThanOrEqual(3);
        });

        it('excludes sessions without homework', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            AcademicSession::factory()->create([
                'academic_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'homework' => 'Complete exercises',
            ]);

            AcademicSession::factory()->create([
                'academic_teacher_id' => $profile->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
                'homework' => null,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/homework', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.homework')))->toBe(1);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/homework', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('show homework', function () {
        it('returns homework details with submissions', function () {
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
                'homework' => 'Complete exercises 1-10',
            ]);

            HomeworkSubmission::factory()->count(2)->create([
                'homeworkable_type' => AcademicSession::class,
                'homeworkable_id' => $session->id,
                'user_id' => $student->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/homework/academic/{$session->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'homework' => [
                            'id',
                            'type',
                            'title',
                            'description',
                            'student',
                            'session_date',
                            'submissions',
                        ],
                    ],
                ]);

            expect(count($response->json('data.homework.submissions')))->toBe(2);
        });

        it('prevents access to other teachers homework', function () {
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
                'homework' => 'Some homework',
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson("/api/v1/teacher/homework/academic/{$session->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/homework/academic/1', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('assign homework', function () {
        it('assigns homework to academic session', function () {
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

            $response = $this->postJson('/api/v1/teacher/homework/assign', [
                'session_type' => 'academic',
                'session_id' => $session->id,
                'homework' => 'Complete exercises 1-10',
                'due_date' => now()->addDays(7)->toDateString(),
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $session->refresh();
            expect($session->homework)->toBe('Complete exercises 1-10');
        });

        it('validates homework data', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson('/api/v1/teacher/homework/assign', [
                'session_type' => 'invalid',
                'session_id' => 1,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['session_type', 'homework']);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/v1/teacher/homework/assign', [
                'session_type' => 'academic',
                'session_id' => 1,
                'homework' => 'Some homework',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('update homework', function () {
        it('updates homework description', function () {
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
                'homework' => 'Old homework',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->putJson("/api/v1/teacher/homework/academic/{$session->id}", [
                'homework' => 'Updated homework',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $session->refresh();
            expect($session->homework)->toBe('Updated homework');
        });

        it('requires authentication', function () {
            $response = $this->putJson('/api/v1/teacher/homework/academic/1', [
                'homework' => 'Updated',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('grade submission', function () {
        it('grades student submission', function () {
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
                'homework' => 'Some homework',
            ]);

            $submission = HomeworkSubmission::factory()->create([
                'homeworkable_type' => AcademicSession::class,
                'homeworkable_id' => $session->id,
                'user_id' => $student->id,
                'status' => 'submitted',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/homework/submissions/{$submission->id}/grade", [
                'grade' => 85,
                'feedback' => 'Good work!',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $submission->refresh();
            expect($submission->grade)->toBe(85.0);
            expect($submission->feedback)->toBe('Good work!');
            expect($submission->status)->toBe('graded');
        });

        it('validates grade value', function () {
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

            $submission = HomeworkSubmission::factory()->create([
                'homeworkable_type' => AcademicSession::class,
                'homeworkable_id' => $session->id,
                'user_id' => $student->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/homework/submissions/{$submission->id}/grade", [
                'grade' => 150, // Invalid: over 100
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['grade']);
        });

        it('prevents grading other teachers submissions', function () {
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

            $submission = HomeworkSubmission::factory()->create([
                'homeworkable_type' => AcademicSession::class,
                'homeworkable_id' => $session->id,
                'user_id' => $student->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->postJson("/api/v1/teacher/homework/submissions/{$submission->id}/grade", [
                'grade' => 85,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(403);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/v1/teacher/homework/submissions/1/grade', [
                'grade' => 85,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });
});
