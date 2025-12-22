<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
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

    $this->student->refresh();
});

describe('Quiz Index', function () {
    it('returns all assigned quizzes', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
        ]);

        QuizAssignment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'status' => 'pending',
            'due_date' => now()->addWeek(),
        ]);

        $response = $this->getJson('/api/v1/student/quizzes', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'quizzes',
                    'total',
                    'stats' => [
                        'pending',
                        'in_progress',
                        'completed',
                    ],
                ],
            ]);
    });

    it('filters quizzes by status', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
        ]);

        QuizAssignment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/student/quizzes?status=pending', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $quizzes = $response->json('data.quizzes');
        foreach ($quizzes as $quiz) {
            expect($quiz['status'])->toBe('pending');
        }
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/student/quizzes', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });
});

describe('Show Quiz', function () {
    it('returns quiz details', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
        ]);

        QuizAssignment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'status' => 'pending',
            'due_date' => now()->addWeek(),
        ]);

        $response = $this->getJson("/api/v1/student/quizzes/{$quiz->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'quiz' => [
                        'id',
                        'assignment_id',
                        'title',
                        'description',
                        'time_limit_minutes',
                        'passing_score',
                        'total_questions',
                        'status',
                        'can_start',
                        'attempts',
                    ],
                ],
            ]);
    });

    it('returns 404 for unassigned quiz', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
        ]);

        $response = $this->getJson("/api/v1/student/quizzes/{$quiz->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('Start Quiz', function () {
    it('starts a new quiz attempt', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
            'time_limit_minutes' => 30,
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => 'What is 2+2?',
            'type' => 'multiple_choice',
            'points' => 1,
        ]);

        QuizQuestionOption::create([
            'question_id' => $question->id,
            'text' => '4',
            'is_correct' => true,
        ]);

        QuizQuestionOption::create([
            'question_id' => $question->id,
            'text' => '5',
            'is_correct' => false,
        ]);

        QuizAssignment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'status' => 'pending',
            'attempts_allowed' => 2,
        ]);

        $response = $this->postJson("/api/v1/student/quizzes/{$quiz->id}/start", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'attempt' => [
                        'id',
                        'quiz_id',
                        'quiz_title',
                        'started_at',
                        'time_limit_minutes',
                        'questions',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
        ]);
    });

    it('prevents starting after due date', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
        ]);

        QuizAssignment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'status' => 'pending',
            'due_date' => now()->subDay(),
        ]);

        $response = $this->postJson("/api/v1/student/quizzes/{$quiz->id}/start", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'code' => 'QUIZ_OVERDUE',
            ]);
    });

    it('prevents starting when max attempts reached', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
        ]);

        $assignment = QuizAssignment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'status' => 'pending',
            'attempts_allowed' => 1,
        ]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'started_at' => now(),
            'completed_at' => now(),
            'score' => 50,
        ]);

        $response = $this->postJson("/api/v1/student/quizzes/{$quiz->id}/start", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'code' => 'MAX_ATTEMPTS_REACHED',
            ]);
    });

    it('returns existing in-progress attempt', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
        ]);

        QuizAssignment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'started_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/student/quizzes/{$quiz->id}/start", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attempt.id', $attempt->id);
    });
});

describe('Submit Quiz', function () {
    it('submits quiz and calculates score', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
            'passing_score' => 60,
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => 'What is 2+2?',
            'type' => 'multiple_choice',
            'points' => 1,
        ]);

        $correctOption = QuizQuestionOption::create([
            'question_id' => $question->id,
            'text' => '4',
            'is_correct' => true,
        ]);

        QuizQuestionOption::create([
            'question_id' => $question->id,
            'text' => '5',
            'is_correct' => false,
        ]);

        $assignment = QuizAssignment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'started_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/student/quizzes/{$quiz->id}/submit", [
            'answers' => [
                [
                    'question_id' => $question->id,
                    'selected_option_ids' => [$correctOption->id],
                ],
            ],
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'result' => [
                        'attempt_id',
                        'score',
                        'passed',
                        'correct_answers',
                        'total_questions',
                    ],
                ],
            ]);

        $attempt->refresh();
        expect($attempt->completed_at)->not->toBeNull();
        expect($attempt->score)->toBe(100.0);
        expect($attempt->passed)->toBeTrue();
    });

    it('requires answers array', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
        ]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'started_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/student/quizzes/{$quiz->id}/submit", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['answers']);
    });

    it('prevents submission without active attempt', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
        ]);

        $response = $this->postJson("/api/v1/student/quizzes/{$quiz->id}/submit", [
            'answers' => [],
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'code' => 'NO_ACTIVE_ATTEMPT',
            ]);
    });
});

describe('Quiz Result', function () {
    it('returns quiz result with answers', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
            'passing_score' => 60,
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => 'What is 2+2?',
            'type' => 'multiple_choice',
            'points' => 1,
        ]);

        $correctOption = QuizQuestionOption::create([
            'question_id' => $question->id,
            'text' => '4',
            'is_correct' => true,
        ]);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'score' => 100,
            'passed' => true,
            'time_taken_minutes' => 15,
            'answers' => [
                [
                    'question_id' => $question->id,
                    'selected_option_ids' => [$correctOption->id],
                    'is_correct' => true,
                ],
            ],
        ]);

        $response = $this->getJson("/api/v1/student/quizzes/{$quiz->id}/result?attempt_id={$attempt->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'result' => [
                        'attempt_id',
                        'quiz_title',
                        'score',
                        'passed',
                        'questions',
                    ],
                ],
            ]);
    });

    it('returns 404 when no results found', function () {
        Sanctum::actingAs($this->student, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'is_published' => true,
        ]);

        $response = $this->getJson("/api/v1/student/quizzes/{$quiz->id}/result", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});
