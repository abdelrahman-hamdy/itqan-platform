<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
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
        ->academicTeacher()
        ->forAcademy($this->academy)
        ->create();

    $this->student->refresh();
});

describe('Course Index', function () {
    it('returns all available courses', function () {
        Sanctum::actingAs($this->student, ['*']);

        InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/student/courses/interactive', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'courses',
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

    it('filters courses by enrolled status', function () {
        Sanctum::actingAs($this->student, ['*']);

        $course = InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'status' => 'published',
        ]);

        CourseSubscription::factory()->create([
            'course_id' => $course->id,
            'user_id' => $this->student->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/student/courses/interactive?filter=enrolled', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $courses = $response->json('data.courses');
        foreach ($courses as $course) {
            expect($course['is_enrolled'])->toBeTrue();
        }
    });

    it('filters courses by completed status', function () {
        Sanctum::actingAs($this->student, ['*']);

        $course = InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'status' => 'published',
        ]);

        CourseSubscription::factory()->create([
            'course_id' => $course->id,
            'user_id' => $this->student->id,
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/student/courses/interactive?filter=completed', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);
    });

    it('searches courses by title', function () {
        Sanctum::actingAs($this->student, ['*']);

        InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
            'title' => 'Math Course',
            'is_active' => true,
            'status' => 'published',
        ]);

        InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
            'title' => 'Science Course',
            'is_active' => true,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/student/courses/interactive?search=Math', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $courses = $response->json('data.courses');
        foreach ($courses as $course) {
            expect($course['title'])->toContain('Math');
        }
    });

    it('paginates course results', function () {
        Sanctum::actingAs($this->student, ['*']);

        InteractiveCourse::factory()->count(20)->create([
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/student/courses/interactive?per_page=10', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.pagination.per_page', 10);

        $courses = $response->json('data.courses');
        expect(count($courses))->toBeLessThanOrEqual(10);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/student/courses/interactive', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });
});

describe('Show Course', function () {
    it('returns course details for unenrolled course', function () {
        Sanctum::actingAs($this->student, ['*']);

        $course = InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'status' => 'published',
        ]);

        $response = $this->getJson("/api/v1/student/courses/interactive/{$course->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'course' => [
                        'id',
                        'title',
                        'description',
                        'thumbnail',
                        'price',
                        'is_free',
                        'is_enrolled',
                        'curriculum',
                    ],
                ],
            ])
            ->assertJsonPath('data.course.is_enrolled', false);
    });

    it('returns course details with sessions for enrolled course', function () {
        Sanctum::actingAs($this->student, ['*']);

        $course = InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'status' => 'published',
        ]);

        CourseSubscription::factory()->create([
            'course_id' => $course->id,
            'user_id' => $this->student->id,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/student/courses/interactive/{$course->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.course.is_enrolled', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'course' => [
                        'id',
                        'title',
                        'is_enrolled',
                        'enrollment',
                        'sessions',
                    ],
                ],
            ]);
    });

    it('returns 404 for non-existent course', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/courses/interactive/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('hides session details for unenrolled students', function () {
        Sanctum::actingAs($this->student, ['*']);

        $course = InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'status' => 'published',
        ]);

        $response = $this->getJson("/api/v1/student/courses/interactive/{$course->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.course.sessions', [])
            ->assertJsonMissing(['sessions' => null]);
    });

    it('shows curriculum for unenrolled students', function () {
        Sanctum::actingAs($this->student, ['*']);

        $course = InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'status' => 'published',
        ]);

        $response = $this->getJson("/api/v1/student/courses/interactive/{$course->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'course' => [
                        'curriculum',
                    ],
                ],
            ]);
    });
});

describe('Course Enrollment Status', function () {
    it('correctly shows enrollment status', function () {
        Sanctum::actingAs($this->student, ['*']);

        $enrolledCourse = InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'status' => 'published',
        ]);

        CourseSubscription::factory()->create([
            'course_id' => $enrolledCourse->id,
            'user_id' => $this->student->id,
            'status' => 'active',
        ]);

        $notEnrolledCourse = InteractiveCourse::factory()->create([
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/student/courses/interactive', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $courses = collect($response->json('data.courses'));

        $enrolledCourseData = $courses->firstWhere('id', $enrolledCourse->id);
        expect($enrolledCourseData['is_enrolled'])->toBeTrue();

        $notEnrolledCourseData = $courses->firstWhere('id', $notEnrolledCourse->id);
        expect($notEnrolledCourseData['is_enrolled'])->toBeFalse();
    });
});
