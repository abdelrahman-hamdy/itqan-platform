<?php

use App\Models\Academy;
use App\Models\AcademicTeacherProfile;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'academic', 'courses');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Academic Course API', function () {
    describe('list courses', function () {
        it('returns assigned courses for teacher', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourse::factory()->count(3)->create([
                'assigned_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/academic/courses', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'courses',
                        'pagination',
                    ],
                ]);

            expect(count($response->json('data.courses')))->toBe(3);
        });

        it('filters courses by status', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourse::factory()->count(2)->create([
                'assigned_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
                'status' => 'published',
            ]);

            InteractiveCourse::factory()->create([
                'assigned_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
                'status' => 'draft',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/academic/courses?status=published', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.courses')))->toBe(2);
        });

        it('only shows teacher assigned courses', function () {
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

            InteractiveCourse::factory()->count(2)->create([
                'assigned_teacher_id' => $profile1->id,
                'academy_id' => $this->academy->id,
            ]);

            InteractiveCourse::factory()->count(3)->create([
                'assigned_teacher_id' => $profile2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson('/api/v1/teacher/academic/courses', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.courses')))->toBe(2);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/academic/courses', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('show course', function () {
        it('returns course details with sessions', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $course = InteractiveCourse::factory()->create([
                'assigned_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/academic/courses/{$course->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'course' => [
                            'id',
                            'title',
                            'description',
                            'thumbnail',
                            'category',
                            'level',
                            'status',
                            'enrollments_count',
                            'sessions_count',
                            'sessions',
                        ],
                    ],
                ]);
        });

        it('prevents access to other teachers courses', function () {
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

            $course = InteractiveCourse::factory()->create([
                'assigned_teacher_id' => $profile2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson("/api/v1/teacher/academic/courses/{$course->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/academic/courses/1', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('course students', function () {
        it('returns enrolled students for course', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $course = InteractiveCourse::factory()->create([
                'assigned_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
            ]);

            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            CourseSubscription::factory()->create([
                'course_id' => $course->id,
                'user_id' => $student1->id,
                'academy_id' => $this->academy->id,
            ]);

            CourseSubscription::factory()->create([
                'course_id' => $course->id,
                'user_id' => $student2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/academic/courses/{$course->id}/students", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'course',
                        'students',
                        'pagination',
                    ],
                ]);

            expect(count($response->json('data.students')))->toBe(2);
        });

        it('filters students by status', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $course = InteractiveCourse::factory()->create([
                'assigned_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
            ]);

            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            CourseSubscription::factory()->create([
                'course_id' => $course->id,
                'user_id' => $student1->id,
                'academy_id' => $this->academy->id,
                'status' => 'active',
            ]);

            CourseSubscription::factory()->create([
                'course_id' => $course->id,
                'user_id' => $student2->id,
                'academy_id' => $this->academy->id,
                'status' => 'completed',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/academic/courses/{$course->id}/students?status=active", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.students')))->toBe(1);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/academic/courses/1/students', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });
});
