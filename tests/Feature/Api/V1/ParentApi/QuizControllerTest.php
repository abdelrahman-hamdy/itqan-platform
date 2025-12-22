<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'parent-api', 'quizzes');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);

    // Create parent user with profile
    $this->parentUser = User::factory()->parent()->forAcademy($this->academy)->create();
    $this->parentProfile = ParentProfile::factory()->create([
        'user_id' => $this->parentUser->id,
        'academy_id' => $this->academy->id,
    ]);

    // Create student with user
    $this->studentUser = User::factory()->student()->forAcademy($this->academy)->create();
    $this->gradeLevel = AcademicGradeLevel::factory()->create([
        'academy_id' => $this->academy->id,
    ]);
    $this->student = StudentProfile::factory()->create([
        'user_id' => $this->studentUser->id,
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    // Link student to parent
    ParentStudentRelationship::create([
        'parent_id' => $this->parentProfile->id,
        'student_id' => $this->student->id,
        'relationship_type' => 'father',
    ]);
});

describe('index (list all quiz results)', function () {
    it('returns empty list when no quiz attempts exist', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/quizzes', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'quiz_results',
                    'pagination',
                ],
            ])
            ->assertJson([
                'data' => [
                    'quiz_results' => [],
                ],
            ]);
    });

    it('returns quiz attempts for linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'title' => 'Math Quiz 1',
        ]);

        QuizAttempt::factory()->create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
            'score' => 85,
            'total_questions' => 10,
            'correct_answers' => 8,
            'passed' => true,
        ]);

        $response = $this->getJson('/api/v1/parent/quizzes', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.quiz_results')
            ->assertJsonStructure([
                'data' => [
                    'quiz_results' => [
                        '*' => [
                            'id',
                            'child_id',
                            'child_name',
                            'quiz',
                            'score',
                            'total_questions',
                            'correct_answers',
                            'percentage',
                            'passed',
                            'time_taken_minutes',
                            'started_at',
                            'completed_at',
                            'created_at',
                        ],
                    ],
                ],
            ]);
    });

    it('filters quiz results by child_id', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create another child
        $student2User = User::factory()->student()->forAcademy($this->academy)->create();
        $student2 = StudentProfile::factory()->create([
            'user_id' => $student2User->id,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student2->id,
            'relationship_type' => 'mother',
        ]);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        QuizAttempt::factory()->create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
        ]);

        QuizAttempt::factory()->create([
            'student_id' => $student2->id,
            'quiz_id' => $quiz->id,
        ]);

        $response = $this->getJson('/api/v1/parent/quizzes?child_id=' . $this->student->id, [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.quiz_results');
    });

    it('filters quiz results by status (passed/failed)', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        QuizAttempt::factory()->create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
            'score' => 90,
            'passed' => true,
        ]);

        QuizAttempt::factory()->create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
            'score' => 40,
            'passed' => false,
        ]);

        $response = $this->getJson('/api/v1/parent/quizzes?status=passed', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.quiz_results');
    });

    it('calculates percentage correctly', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        QuizAttempt::factory()->create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
            'total_questions' => 10,
            'correct_answers' => 7,
        ]);

        $response = $this->getJson('/api/v1/parent/quizzes', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $results = $response->json('data.quiz_results');
        expect($results[0]['percentage'])->toBe(70.0);
    });

    it('paginates results', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        QuizAttempt::factory()->count(20)->create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
        ]);

        $response = $this->getJson('/api/v1/parent/quizzes?per_page=10', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data.quiz_results')
            ->assertJsonStructure([
                'data' => [
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

    it('does not show quiz results of non-linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudent = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        QuizAttempt::factory()->create([
            'student_id' => $otherStudent->id,
            'quiz_id' => $quiz->id,
        ]);

        $response = $this->getJson('/api/v1/parent/quizzes', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.quiz_results');
    });
});

describe('show (get specific quiz result)', function () {
    it('returns quiz attempt details for linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
            'title' => 'Science Quiz',
            'passing_score' => 60,
        ]);

        $attempt = QuizAttempt::factory()->create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
            'score' => 85,
            'total_questions' => 10,
            'correct_answers' => 8,
            'passed' => true,
        ]);

        $response = $this->getJson("/api/v1/parent/quizzes/{$attempt->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'quiz_result' => [
                        'id',
                        'child',
                        'quiz' => [
                            'id',
                            'title',
                            'description',
                            'type',
                            'total_questions',
                            'passing_score',
                            'time_limit_minutes',
                        ],
                        'score',
                        'total_questions',
                        'correct_answers',
                        'wrong_answers',
                        'percentage',
                        'passed',
                        'time_taken_minutes',
                        'answers',
                        'started_at',
                        'completed_at',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'quiz_result' => [
                        'id' => $attempt->id,
                        'score' => 85,
                        'passed' => true,
                    ],
                ],
            ]);
    });

    it('returns 404 for quiz attempt of non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudent = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        $attempt = QuizAttempt::factory()->create([
            'student_id' => $otherStudent->id,
            'quiz_id' => $quiz->id,
        ]);

        $response = $this->getJson("/api/v1/parent/quizzes/{$attempt->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('returns 404 for non-existent quiz attempt', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/quizzes/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('childQuizzes (get quizzes for specific child)', function () {
    it('returns quiz attempts for specific linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        QuizAttempt::factory()->count(3)->create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
        ]);

        $response = $this->getJson("/api/v1/parent/children/{$this->student->id}/quizzes", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.quiz_results')
            ->assertJsonStructure([
                'data' => [
                    'child' => [
                        'id',
                        'name',
                    ],
                    'quiz_results',
                    'stats' => [
                        'total_quizzes_taken',
                        'quizzes_passed',
                        'quizzes_failed',
                        'pass_rate',
                        'average_score',
                    ],
                    'pagination',
                ],
            ])
            ->assertJson([
                'data' => [
                    'child' => [
                        'id' => $this->student->id,
                    ],
                ],
            ]);
    });

    it('calculates quiz stats correctly', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        // Create 2 passed and 1 failed attempt
        QuizAttempt::factory()->create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
            'score' => 90,
            'passed' => true,
        ]);

        QuizAttempt::factory()->create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
            'score' => 85,
            'passed' => true,
        ]);

        QuizAttempt::factory()->create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
            'score' => 45,
            'passed' => false,
        ]);

        $response = $this->getJson("/api/v1/parent/children/{$this->student->id}/quizzes", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $stats = $response->json('data.stats');
        expect($stats['total_quizzes_taken'])->toBe(3);
        expect($stats['quizzes_passed'])->toBe(2);
        expect($stats['quizzes_failed'])->toBe(1);
        expect($stats['pass_rate'])->toBeGreaterThan(65.0);
    });

    it('returns 404 for non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudent = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $response = $this->getJson("/api/v1/parent/children/{$otherStudent->id}/quizzes", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('paginates child quiz attempts', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $quiz = Quiz::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        QuizAttempt::factory()->count(20)->create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
        ]);

        $response = $this->getJson("/api/v1/parent/children/{$this->student->id}/quizzes?per_page=10", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data.quiz_results')
            ->assertJsonStructure([
                'data' => [
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
});

describe('authorization', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/parent/quizzes', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });

    it('returns 404 when parent has no profile', function () {
        $userWithoutProfile = User::factory()->parent()->forAcademy($this->academy)->create();
        Sanctum::actingAs($userWithoutProfile, ['*']);

        $response = $this->getJson('/api/v1/parent/quizzes', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error_code' => 'PARENT_PROFILE_NOT_FOUND',
            ]);
    });
});
