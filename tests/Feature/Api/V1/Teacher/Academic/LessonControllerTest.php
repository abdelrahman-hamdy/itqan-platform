<?php

use App\Models\Academy;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'academic', 'lessons');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Academic Lesson API', function () {
    describe('list lessons', function () {
        it('returns lessons for teacher', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            AcademicIndividualLesson::factory()->count(3)->create([
                'academic_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/academic/lessons', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'lessons',
                        'pagination',
                    ],
                ]);

            expect(count($response->json('data.lessons')))->toBe(3);
        });

        it('filters lessons by status', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            AcademicIndividualLesson::factory()->count(2)->create([
                'academic_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
                'status' => 'active',
            ]);

            AcademicIndividualLesson::factory()->create([
                'academic_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
                'status' => 'inactive',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/academic/lessons?status=active', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.lessons')))->toBe(2);
        });

        it('only shows teacher own lessons', function () {
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

            AcademicIndividualLesson::factory()->count(2)->create([
                'academic_teacher_id' => $profile1->id,
                'academy_id' => $this->academy->id,
            ]);

            AcademicIndividualLesson::factory()->count(3)->create([
                'academic_teacher_id' => $profile2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson('/api/v1/teacher/academic/lessons', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.lessons')))->toBe(2);
        });

        it('paginates lessons', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            AcademicIndividualLesson::factory()->count(20)->create([
                'academic_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/academic/lessons?per_page=10', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $pagination = $response->json('data.pagination');
            expect($pagination['per_page'])->toBe(10);
            expect($pagination['total'])->toBe(20);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/academic/lessons', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('show lesson', function () {
        it('returns lesson details', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $lesson = AcademicIndividualLesson::factory()->create([
                'academic_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/academic/lessons/{$lesson->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'lesson' => [
                            'id',
                            'name',
                            'description',
                            'student',
                            'subject',
                            'status',
                            'subscription',
                            'schedule',
                            'recent_sessions',
                        ],
                    ],
                ]);
        });

        it('prevents access to other teachers lessons', function () {
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

            $lesson = AcademicIndividualLesson::factory()->create([
                'academic_teacher_id' => $profile2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson("/api/v1/teacher/academic/lessons/{$lesson->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('returns 404 for non-existent lesson', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/academic/lessons/99999', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/academic/lessons/1', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });
});
