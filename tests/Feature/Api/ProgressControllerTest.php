<?php

use App\Models\Lesson;
use App\Models\RecordedCourse;
use App\Models\StudentProgress;
use Laravel\Sanctum\Sanctum;

describe('Progress API', function () {
    beforeEach(function () {
        $this->academy = createAcademy();
        $this->student = createUser('student', $this->academy);

        $this->course = RecordedCourse::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
        ]);

        $this->lessons = Lesson::factory()->count(10)->create([
            'recorded_course_id' => $this->course->id,
            'is_published' => true,
        ]);

        Sanctum::actingAs($this->student);
    });

    describe('GET /api/progress/courses/{courseId}', function () {
        it('returns course progress for authenticated user', function () {
            // Complete 5 out of 10 lessons
            $completedLessons = $this->lessons->take(5);
            foreach ($completedLessons as $lesson) {
                StudentProgress::factory()->create([
                    'user_id' => $this->student->id,
                    'recorded_course_id' => $this->course->id,
                    'lesson_id' => $lesson->id,
                    'is_completed' => true,
                ]);
            }

            $response = $this->getJson("/api/progress/courses/{$this->course->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'progress_percentage',
                    'completed_lessons',
                    'total_lessons',
                ]);

            expect($response->json('success'))->toBeTrue()
                ->and($response->json('progress_percentage'))->toBe(50)
                ->and($response->json('completed_lessons'))->toBe(5)
                ->and($response->json('total_lessons'))->toBe(10);
        });

        it('returns zero progress for course with no completed lessons', function () {
            $response = $this->getJson("/api/progress/courses/{$this->course->id}");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'progress_percentage' => 0,
                    'completed_lessons' => 0,
                    'total_lessons' => 10,
                ]);
        });

        it('returns 100 percent for fully completed course', function () {
            foreach ($this->lessons as $lesson) {
                StudentProgress::factory()->create([
                    'user_id' => $this->student->id,
                    'recorded_course_id' => $this->course->id,
                    'lesson_id' => $lesson->id,
                    'is_completed' => true,
                ]);
            }

            $response = $this->getJson("/api/progress/courses/{$this->course->id}");

            $response->assertStatus(200);
            expect($response->json('progress_percentage'))->toBe(100);
        });

        it('only counts published lessons', function () {
            // Create unpublished lessons
            Lesson::factory()->count(5)->create([
                'recorded_course_id' => $this->course->id,
                'is_published' => false,
            ]);

            $response = $this->getJson("/api/progress/courses/{$this->course->id}");

            $response->assertStatus(200);
            expect($response->json('total_lessons'))->toBe(10); // Only published lessons
        });

        it('requires authentication', function () {
            Sanctum::actingAs(null);

            $response = $this->getJson("/api/progress/courses/{$this->course->id}");

            $response->assertStatus(401);
        });

        it('returns 404 for non-existent course', function () {
            $response = $this->getJson('/api/progress/courses/99999');

            $response->assertStatus(404);
        });
    });

    describe('GET /api/progress/courses/{courseId}/lessons/{lessonId}', function () {
        it('returns lesson progress', function () {
            $lesson = $this->lessons->first();
            $progress = StudentProgress::factory()->create([
                'user_id' => $this->student->id,
                'recorded_course_id' => $this->course->id,
                'lesson_id' => $lesson->id,
                'current_position_seconds' => 120,
                'progress_percentage' => 40,
                'is_completed' => false,
                'watch_time_seconds' => 150,
                'total_time_seconds' => 300,
            ]);

            $response = $this->getJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'progress' => [
                        'current_position_seconds',
                        'progress_percentage',
                        'is_completed',
                        'watch_time_seconds',
                        'total_time_seconds',
                    ],
                ]);

            expect($response->json('progress.current_position_seconds'))->toBe(120)
                ->and($response->json('progress.progress_percentage'))->toBe(40)
                ->and($response->json('progress.is_completed'))->toBeFalse();
        });

        it('creates progress record if none exists', function () {
            $lesson = $this->lessons->first();

            expect(StudentProgress::where('lesson_id', $lesson->id)->count())->toBe(0);

            $response = $this->getJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}");

            $response->assertStatus(200);
            expect(StudentProgress::where('lesson_id', $lesson->id)->count())->toBe(1);
        });

        it('requires authentication', function () {
            Sanctum::actingAs(null);
            $lesson = $this->lessons->first();

            $response = $this->getJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}");

            $response->assertStatus(401);
        });
    });

    describe('POST /api/progress/courses/{courseId}/lessons/{lessonId}', function () {
        it('updates lesson progress', function () {
            $lesson = $this->lessons->first();

            $response = $this->postJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}", [
                'current_time' => 150,
                'total_time' => 300,
                'progress_percentage' => 50,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'progress' => [
                        'current_position_seconds',
                        'progress_percentage',
                        'is_completed',
                    ],
                ]);

            expect($response->json('progress.current_position_seconds'))->toBe(150);

            $progress = StudentProgress::where([
                'user_id' => $this->student->id,
                'lesson_id' => $lesson->id,
            ])->first();

            expect($progress)->not->toBeNull()
                ->and($progress->current_position_seconds)->toBe(150);
        });

        it('validates required fields', function () {
            $lesson = $this->lessons->first();

            $response = $this->postJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}", [
                'current_time' => 150,
                // Missing total_time and progress_percentage
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['total_time', 'progress_percentage']);
        });

        it('validates numeric fields', function () {
            $lesson = $this->lessons->first();

            $response = $this->postJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}", [
                'current_time' => 'invalid',
                'total_time' => 300,
                'progress_percentage' => 50,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['current_time']);
        });

        it('validates progress percentage range', function () {
            $lesson = $this->lessons->first();

            $response = $this->postJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}", [
                'current_time' => 100,
                'total_time' => 300,
                'progress_percentage' => 150, // Invalid: > 100
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['progress_percentage']);
        });

        it('requires authentication', function () {
            Sanctum::actingAs(null);
            $lesson = $this->lessons->first();

            $response = $this->postJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}", [
                'current_time' => 100,
                'total_time' => 300,
                'progress_percentage' => 33,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('POST /api/progress/courses/{courseId}/lessons/{lessonId}/complete', function () {
        it('marks lesson as complete', function () {
            $lesson = $this->lessons->first();

            $response = $this->postJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}/complete");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'progress' => [
                        'is_completed',
                        'completed_at',
                    ],
                ]);

            expect($response->json('progress.is_completed'))->toBeTrue();

            $progress = StudentProgress::where([
                'user_id' => $this->student->id,
                'lesson_id' => $lesson->id,
            ])->first();

            expect($progress->is_completed)->toBeTrue()
                ->and($progress->completed_at)->not->toBeNull();
        });

        it('returns success message', function () {
            $lesson = $this->lessons->first();

            $response = $this->postJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}/complete");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Lesson marked as complete',
                ]);
        });

        it('requires authentication', function () {
            Sanctum::actingAs(null);
            $lesson = $this->lessons->first();

            $response = $this->postJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}/complete");

            $response->assertStatus(401);
        });
    });

    describe('POST /api/progress/courses/{courseId}/lessons/{lessonId}/toggle', function () {
        it('toggles completion status from incomplete to complete', function () {
            $lesson = $this->lessons->first();
            StudentProgress::factory()->create([
                'user_id' => $this->student->id,
                'recorded_course_id' => $this->course->id,
                'lesson_id' => $lesson->id,
                'is_completed' => false,
            ]);

            $response = $this->postJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}/toggle");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Lesson marked as complete',
                    'progress' => ['is_completed' => true],
                ]);
        });

        it('toggles completion status from complete to incomplete', function () {
            $lesson = $this->lessons->first();
            StudentProgress::factory()->create([
                'user_id' => $this->student->id,
                'recorded_course_id' => $this->course->id,
                'lesson_id' => $lesson->id,
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            $response = $this->postJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}/toggle");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Lesson marked as incomplete',
                    'progress' => ['is_completed' => false],
                ]);

            $progress = StudentProgress::where([
                'user_id' => $this->student->id,
                'lesson_id' => $lesson->id,
            ])->first();

            expect($progress->completed_at)->toBeNull();
        });

        it('creates progress record if none exists and marks as complete', function () {
            $lesson = $this->lessons->first();

            expect(StudentProgress::where('lesson_id', $lesson->id)->count())->toBe(0);

            $response = $this->postJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}/toggle");

            $response->assertStatus(200);
            expect($response->json('progress.is_completed'))->toBeTrue();
        });

        it('requires authentication', function () {
            Sanctum::actingAs(null);
            $lesson = $this->lessons->first();

            $response = $this->postJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}/toggle");

            $response->assertStatus(401);
        });
    });

    describe('User Isolation', function () {
        it('does not show other users progress', function () {
            $otherStudent = createUser('student', $this->academy);
            $lesson = $this->lessons->first();

            // Create progress for other student
            StudentProgress::factory()->create([
                'user_id' => $otherStudent->id,
                'recorded_course_id' => $this->course->id,
                'lesson_id' => $lesson->id,
                'current_position_seconds' => 200,
                'is_completed' => true,
            ]);

            // Create progress for current student
            StudentProgress::factory()->create([
                'user_id' => $this->student->id,
                'recorded_course_id' => $this->course->id,
                'lesson_id' => $lesson->id,
                'current_position_seconds' => 50,
                'is_completed' => false,
            ]);

            $response = $this->getJson("/api/progress/courses/{$this->course->id}/lessons/{$lesson->id}");

            $response->assertStatus(200);
            expect($response->json('progress.current_position_seconds'))->toBe(50)
                ->and($response->json('progress.is_completed'))->toBeFalse();
        });

        it('calculates course progress only for authenticated user', function () {
            $otherStudent = createUser('student', $this->academy);

            // Other student completes all lessons
            foreach ($this->lessons as $lesson) {
                StudentProgress::factory()->create([
                    'user_id' => $otherStudent->id,
                    'recorded_course_id' => $this->course->id,
                    'lesson_id' => $lesson->id,
                    'is_completed' => true,
                ]);
            }

            // Current student completes none
            $response = $this->getJson("/api/progress/courses/{$this->course->id}");

            $response->assertStatus(200);
            expect($response->json('progress_percentage'))->toBe(0)
                ->and($response->json('completed_lessons'))->toBe(0);
        });
    });
});
