<?php

use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranSession;
use App\Models\QuranSessionReport;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'students');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Teacher Students API', function () {
    describe('list students', function () {
        it('returns students for quran teacher', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student1->id,
                'academy_id' => $this->academy->id,
            ]);

            QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/students', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'students',
                        'pagination',
                    ],
                ]);

            expect(count($response->json('data.students')))->toBeGreaterThanOrEqual(2);
        });

        it('returns students for academic teacher', function () {
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
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/students', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.students')))->toBeGreaterThanOrEqual(1);
        });

        it('only shows teacher own students', function () {
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

            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            QuranSession::factory()->create([
                'quran_teacher_id' => $profile1->id,
                'student_id' => $student1->id,
                'academy_id' => $this->academy->id,
            ]);

            QuranSession::factory()->create([
                'quran_teacher_id' => $profile2->id,
                'student_id' => $student2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson('/api/v1/teacher/students', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $students = $response->json('data.students');
            expect(count($students))->toBe(1);
        });

        it('supports search', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student1 = User::factory()->student()->forAcademy($this->academy)->create([
                'first_name' => 'Ahmad',
                'last_name' => 'Ali',
            ]);

            $student2 = User::factory()->student()->forAcademy($this->academy)->create([
                'first_name' => 'Mohammed',
                'last_name' => 'Salem',
            ]);

            QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student1->id,
                'academy_id' => $this->academy->id,
            ]);

            QuranSession::factory()->create([
                'quran_teacher_id' => $profile->id,
                'student_id' => $student2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/students?search=ahmad', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $students = $response->json('data.students');
            expect(count($students))->toBe(1);
        });

        it('paginates students', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            for ($i = 0; $i < 25; $i++) {
                $student = User::factory()->student()->forAcademy($this->academy)->create();
                QuranSession::factory()->create([
                    'quran_teacher_id' => $profile->id,
                    'student_id' => $student->id,
                    'academy_id' => $this->academy->id,
                ]);
            }

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/students?per_page=10&page=1', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $pagination = $response->json('data.pagination');
            expect($pagination['per_page'])->toBe(10);
            expect($pagination['total'])->toBeGreaterThanOrEqual(25);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/students', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('show student', function () {
        it('returns student details with stats', function () {
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
                'status' => 'completed',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/students/{$student->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'student' => [
                            'id',
                            'name',
                            'email',
                            'phone',
                        ],
                        'quran_stats',
                    ],
                ]);

            expect($response->json('data.quran_stats.total_sessions'))->toBe(5);
            expect($response->json('data.quran_stats.completed_sessions'))->toBe(5);
        });

        it('prevents access to other teachers students', function () {
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

            QuranSession::factory()->create([
                'quran_teacher_id' => $profile2->id,
                'student_id' => $student->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson("/api/v1/teacher/students/{$student->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(403);
        });

        it('returns 404 for non-existent student', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/students/99999', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->getJson("/api/v1/teacher/students/{$student->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('create student report', function () {
        it('creates report for quran session', function () {
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

            $response = $this->postJson("/api/v1/teacher/students/{$student->id}/reports", [
                'session_type' => 'quran',
                'session_id' => $session->id,
                'rating' => 4,
                'notes' => 'Good progress',
                'feedback' => 'Keep practicing',
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(201);

            $this->assertDatabaseHas('quran_session_reports', [
                'quran_session_id' => $session->id,
                'rating' => 4,
            ]);
        });

        it('validates report data', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->postJson("/api/v1/teacher/students/{$student->id}/reports", [
                'session_type' => 'quran',
                'session_id' => 999,
                'rating' => 10, // Invalid rating
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(422);
        });

        it('prevents creating report for other teachers session', function () {
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

            $response = $this->postJson("/api/v1/teacher/students/{$student->id}/reports", [
                'session_type' => 'quran',
                'session_id' => $session->id,
                'rating' => 4,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $response = $this->postJson("/api/v1/teacher/students/{$student->id}/reports", [
                'session_type' => 'quran',
                'session_id' => 1,
                'rating' => 4,
            ], [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });
});
