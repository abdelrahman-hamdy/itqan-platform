<?php

use App\Models\Academy;
use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\RecordedCourse;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\QuizService;
use Illuminate\Support\Facades\DB;

describe('QuizService', function () {
    beforeEach(function () {
        $this->service = new QuizService();
        $this->academy = Academy::factory()->create();
    });

    describe('createQuiz()', function () {
        it('creates a quiz without questions', function () {
            $data = [
                'academy_id' => $this->academy->id,
                'title' => 'Test Quiz',
                'description' => 'Test Description',
                'duration_minutes' => 30,
                'passing_score' => 70,
                'is_active' => true,
            ];

            $quiz = $this->service->createQuiz($data);

            expect($quiz)->toBeInstanceOf(Quiz::class)
                ->and($quiz->title)->toBe('Test Quiz')
                ->and($quiz->description)->toBe('Test Description')
                ->and($quiz->duration_minutes)->toBe(30)
                ->and($quiz->passing_score)->toBe(70)
                ->and($quiz->is_active)->toBeTrue()
                ->and($quiz->questions)->toBeEmpty();
        });

        it('creates a quiz with questions', function () {
            $data = [
                'academy_id' => $this->academy->id,
                'title' => 'Test Quiz',
                'description' => 'Test Description',
                'duration_minutes' => 30,
                'passing_score' => 70,
                'is_active' => true,
            ];

            $questions = [
                [
                    'question_text' => 'What is 2+2?',
                    'options' => ['3', '4', '5', '6'],
                    'correct_option' => 1,
                ],
                [
                    'question_text' => 'What is 3+3?',
                    'options' => ['5', '6', '7', '8'],
                    'correct_option' => 1,
                ],
            ];

            $quiz = $this->service->createQuiz($data, $questions);

            expect($quiz->questions)->toHaveCount(2)
                ->and($quiz->questions[0]->question_text)->toBe('What is 2+2?')
                ->and($quiz->questions[0]->options)->toBe(['3', '4', '5', '6'])
                ->and($quiz->questions[0]->correct_option)->toBe(1)
                ->and($quiz->questions[0]->order)->toBe(0)
                ->and($quiz->questions[1]->question_text)->toBe('What is 3+3?')
                ->and($quiz->questions[1]->order)->toBe(1);
        });

        it('uses custom order when provided', function () {
            $data = [
                'academy_id' => $this->academy->id,
                'title' => 'Test Quiz',
                'description' => 'Test Description',
                'duration_minutes' => 30,
                'passing_score' => 70,
                'is_active' => true,
            ];

            $questions = [
                [
                    'question_text' => 'Question 1',
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_option' => 0,
                    'order' => 10,
                ],
                [
                    'question_text' => 'Question 2',
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_option' => 1,
                    'order' => 5,
                ],
            ];

            $quiz = $this->service->createQuiz($data, $questions);

            expect($quiz->questions[0]->order)->toBe(10)
                ->and($quiz->questions[1]->order)->toBe(5);
        });

        it('wraps quiz creation in transaction', function () {
            DB::shouldReceive('transaction')
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $data = [
                'academy_id' => $this->academy->id,
                'title' => 'Test Quiz',
                'description' => 'Test Description',
                'duration_minutes' => 30,
                'passing_score' => 70,
                'is_active' => true,
            ];

            $this->service->createQuiz($data);

            expect(true)->toBeTrue();
        });
    });

    describe('updateQuiz()', function () {
        it('updates quiz data', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'title' => 'Old Title',
                'description' => 'Old Description',
            ]);

            $updatedQuiz = $this->service->updateQuiz($quiz, [
                'title' => 'New Title',
                'description' => 'New Description',
            ]);

            expect($updatedQuiz->title)->toBe('New Title')
                ->and($updatedQuiz->description)->toBe('New Description');
        });

        it('returns fresh quiz instance', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $updatedQuiz = $this->service->updateQuiz($quiz, [
                'title' => 'Updated Title',
            ]);

            expect($updatedQuiz->wasRecentlyCreated)->toBeFalse()
                ->and($updatedQuiz->exists)->toBeTrue();
        });
    });

    describe('addQuestion()', function () {
        it('adds a question to a quiz', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $questionData = [
                'question_text' => 'New Question?',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option' => 2,
            ];

            $question = $this->service->addQuestion($quiz, $questionData);

            expect($question)->toBeInstanceOf(QuizQuestion::class)
                ->and($question->quiz_id)->toBe($quiz->id)
                ->and($question->question_text)->toBe('New Question?')
                ->and($question->options)->toBe(['A', 'B', 'C', 'D'])
                ->and($question->correct_option)->toBe(2);
        });

        it('auto-increments order when not provided', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            QuizQuestion::factory()->create([
                'quiz_id' => $quiz->id,
                'order' => 0,
            ]);

            QuizQuestion::factory()->create([
                'quiz_id' => $quiz->id,
                'order' => 1,
            ]);

            $questionData = [
                'question_text' => 'New Question?',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option' => 0,
            ];

            $question = $this->service->addQuestion($quiz, $questionData);

            expect($question->order)->toBe(2);
        });

        it('uses custom order when provided', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $questionData = [
                'question_text' => 'Custom Order Question?',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option' => 0,
                'order' => 99,
            ];

            $question = $this->service->addQuestion($quiz, $questionData);

            expect($question->order)->toBe(99);
        });

        it('sets order to 0 when quiz has no questions', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $questionData = [
                'question_text' => 'First Question?',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option' => 0,
            ];

            $question = $this->service->addQuestion($quiz, $questionData);

            expect($question->order)->toBe(0);
        });
    });

    describe('assignQuiz()', function () {
        it('assigns quiz to a quran circle', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = $this->service->assignQuiz($quiz, $circle);

            expect($assignment)->toBeInstanceOf(QuizAssignment::class)
                ->and($assignment->quiz_id)->toBe($quiz->id)
                ->and($assignment->assignable_type)->toBe(QuranCircle::class)
                ->and($assignment->assignable_id)->toBe($circle->id)
                ->and($assignment->is_visible)->toBeTrue()
                ->and($assignment->max_attempts)->toBe(1);
        });

        it('assigns quiz to an interactive course', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $course = InteractiveCourse::factory()->create();

            $assignment = $this->service->assignQuiz($quiz, $course);

            expect($assignment->assignable_type)->toBe(InteractiveCourse::class)
                ->and($assignment->assignable_id)->toBe($course->id);
        });

        it('uses custom options when provided', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $options = [
                'is_visible' => false,
                'available_from' => now()->addDay(),
                'available_until' => now()->addDays(7),
                'max_attempts' => 3,
            ];

            $assignment = $this->service->assignQuiz($quiz, $circle, $options);

            expect($assignment->is_visible)->toBeFalse()
                ->and($assignment->available_from)->not->toBeNull()
                ->and($assignment->available_until)->not->toBeNull()
                ->and($assignment->max_attempts)->toBe(3);
        });

        it('uses default options when not provided', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = $this->service->assignQuiz($quiz, $circle);

            expect($assignment->is_visible)->toBeTrue()
                ->and($assignment->available_from)->toBeNull()
                ->and($assignment->available_until)->toBeNull()
                ->and($assignment->max_attempts)->toBe(1);
        });
    });

    describe('getAvailableQuizzes()', function () {
        it('returns empty collection when no quizzes assigned', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $quizzes = $this->service->getAvailableQuizzes($circle, $student->id);

            expect($quizzes)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($quizzes)->toBeEmpty();
        });

        it('returns available quizzes for assignable', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'is_active' => true,
            ]);

            QuizQuestion::factory()->count(3)->create([
                'quiz_id' => $quiz->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
                'is_visible' => true,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $quizzes = $this->service->getAvailableQuizzes($circle, $student->id);

            expect($quizzes)->toHaveCount(1)
                ->and($quizzes->first()->quiz->id)->toBe($quiz->id)
                ->and($quizzes->first()->assignment->id)->toBe($assignment->id);
        });

        it('excludes invisible quizzes', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'is_active' => true,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
                'is_visible' => false,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $quizzes = $this->service->getAvailableQuizzes($circle, $student->id);

            expect($quizzes)->toBeEmpty();
        });

        it('excludes quizzes that are not yet available', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'is_active' => true,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
                'is_visible' => true,
                'available_from' => now()->addDays(5),
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $quizzes = $this->service->getAvailableQuizzes($circle, $student->id);

            expect($quizzes)->toBeEmpty();
        });

        it('excludes expired quizzes', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'is_active' => true,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
                'is_visible' => true,
                'available_until' => now()->subDays(2),
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();

            $quizzes = $this->service->getAvailableQuizzes($circle, $student->id);

            expect($quizzes)->toBeEmpty();
        });

        it('includes attempt information', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'is_active' => true,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
                'is_visible' => true,
                'max_attempts' => 3,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            // Create one completed attempt
            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'score' => 80,
                'passed' => true,
                'submitted_at' => now(),
            ]);

            $quizzes = $this->service->getAvailableQuizzes($circle, $studentProfile->id);

            expect($quizzes->first()->completed_attempts)->toBe(1)
                ->and($quizzes->first()->remaining_attempts)->toBe(2)
                ->and($quizzes->first()->best_score)->toBe(80)
                ->and($quizzes->first()->passed)->toBeTrue()
                ->and($quizzes->first()->can_attempt)->toBeTrue();
        });

        it('detects in-progress attempts', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'is_active' => true,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
                'is_visible' => true,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            $attempt = QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => null,
            ]);

            $quizzes = $this->service->getAvailableQuizzes($circle, $studentProfile->id);

            expect($quizzes->first()->in_progress_attempt)->not->toBeNull()
                ->and($quizzes->first()->in_progress_attempt->id)->toBe($attempt->id);
        });
    });

    describe('startAttempt()', function () {
        it('creates a new quiz attempt', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            $attempt = $this->service->startAttempt($assignment, $studentProfile->id);

            expect($attempt)->toBeInstanceOf(QuizAttempt::class)
                ->and($attempt->quiz_assignment_id)->toBe($assignment->id)
                ->and($attempt->student_id)->toBe($studentProfile->id)
                ->and($attempt->started_at)->not->toBeNull()
                ->and($attempt->submitted_at)->toBeNull();
        });

        it('returns existing in-progress attempt', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            $existingAttempt = QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => null,
            ]);

            $attempt = $this->service->startAttempt($assignment, $studentProfile->id);

            expect($attempt->id)->toBe($existingAttempt->id);
        });

        it('throws exception when max attempts reached', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
                'max_attempts' => 1,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            // Create a completed attempt
            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now(),
            ]);

            expect(fn() => $this->service->startAttempt($assignment, $studentProfile->id))
                ->toThrow(\Exception::class, 'لقد استنفدت جميع محاولاتك المتاحة');
        });
    });

    describe('submitAttempt()', function () {
        it('submits quiz attempt and calculates score', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'passing_score' => 70,
            ]);

            $question1 = QuizQuestion::factory()->create([
                'quiz_id' => $quiz->id,
                'correct_option' => 1,
            ]);

            $question2 = QuizQuestion::factory()->create([
                'quiz_id' => $quiz->id,
                'correct_option' => 2,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            $attempt = QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => null,
            ]);

            $answers = [
                $question1->id => 1, // Correct
                $question2->id => 2, // Correct
            ];

            $submittedAttempt = $this->service->submitAttempt($attempt, $answers);

            expect($submittedAttempt->score)->toBe(100)
                ->and($submittedAttempt->passed)->toBeTrue()
                ->and($submittedAttempt->submitted_at)->not->toBeNull()
                ->and($submittedAttempt->answers)->toBe($answers);
        });

        it('throws exception when attempt is already submitted', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            $attempt = QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now(),
            ]);

            expect(fn() => $this->service->submitAttempt($attempt, []))
                ->toThrow(\Exception::class, 'تم تقديم هذه المحاولة بالفعل');
        });

        it('returns fresh attempt after submission', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            QuizQuestion::factory()->create([
                'quiz_id' => $quiz->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            $attempt = QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => null,
            ]);

            $submittedAttempt = $this->service->submitAttempt($attempt, []);

            expect($submittedAttempt->wasRecentlyCreated)->toBeFalse()
                ->and($submittedAttempt->exists)->toBeTrue();
        });
    });

    describe('getStudentResults()', function () {
        it('returns completed attempts for student', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now(),
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => null,
            ]);

            $results = $this->service->getStudentResults($studentProfile->id);

            expect($results)->toHaveCount(1);
        });

        it('filters by academy when provided', function () {
            $academy1 = Academy::factory()->create();
            $academy2 = Academy::factory()->create();

            $quiz1 = Quiz::factory()->create(['academy_id' => $academy1->id]);
            $quiz2 = Quiz::factory()->create(['academy_id' => $academy2->id]);

            $circle1 = QuranCircle::factory()->create(['academy_id' => $academy1->id]);
            $circle2 = QuranCircle::factory()->create(['academy_id' => $academy2->id]);

            $assignment1 = QuizAssignment::factory()->create([
                'quiz_id' => $quiz1->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle1->id,
            ]);

            $assignment2 = QuizAssignment::factory()->create([
                'quiz_id' => $quiz2->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle2->id,
            ]);

            $student = User::factory()->student()->forAcademy($academy1)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $academy1->id,
                'user_id' => $student->id,
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment1->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now(),
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment2->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now(),
            ]);

            $results = $this->service->getStudentResults($studentProfile->id, $academy1->id);

            expect($results)->toHaveCount(1);
        });

        it('returns results ordered by submission date', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now()->subDays(2),
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now(),
            ]);

            $results = $this->service->getStudentResults($studentProfile->id);

            expect($results->first()->submitted_at->isAfter($results->last()->submitted_at))->toBeTrue();
        });
    });

    describe('getAssignmentStatistics()', function () {
        it('returns zero statistics when no attempts', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $stats = $this->service->getAssignmentStatistics($assignment);

            expect($stats['total_attempts'])->toBe(0)
                ->and($stats['unique_students'])->toBe(0)
                ->and($stats['average_score'])->toBe(0)
                ->and($stats['pass_rate'])->toBe(0)
                ->and($stats['highest_score'])->toBe(0)
                ->and($stats['lowest_score'])->toBe(0);
        });

        it('calculates statistics correctly', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
                'passing_score' => 70,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student1 = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile1 = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student1->id,
            ]);

            $student2 = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile2 = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student2->id,
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile1->id,
                'score' => 80,
                'passed' => true,
                'submitted_at' => now(),
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile2->id,
                'score' => 60,
                'passed' => false,
                'submitted_at' => now(),
            ]);

            $stats = $this->service->getAssignmentStatistics($assignment);

            expect($stats['total_attempts'])->toBe(2)
                ->and($stats['unique_students'])->toBe(2)
                ->and($stats['average_score'])->toBe(70.0)
                ->and($stats['pass_rate'])->toBe(50.0)
                ->and($stats['highest_score'])->toBe(80)
                ->and($stats['lowest_score'])->toBe(60);
        });

        it('counts unique students correctly with multiple attempts', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
                'max_attempts' => 3,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now()->subHour(),
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now(),
            ]);

            $stats = $this->service->getAssignmentStatistics($assignment);

            expect($stats['total_attempts'])->toBe(2)
                ->and($stats['unique_students'])->toBe(1);
        });

        it('only counts completed attempts', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now(),
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => null,
            ]);

            $stats = $this->service->getAssignmentStatistics($assignment);

            expect($stats['total_attempts'])->toBe(1);
        });
    });

    describe('getStudentQuizHistory()', function () {
        it('returns quiz history for student', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'score' => 85,
                'passed' => true,
                'submitted_at' => now(),
            ]);

            $history = $this->service->getStudentQuizHistory($studentProfile->id);

            expect($history)->toHaveCount(1)
                ->and($history->first()->score)->toBe(85)
                ->and($history->first()->passed)->toBeTrue()
                ->and($history->first()->quiz->id)->toBe($quiz->id);
        });

        it('excludes in-progress attempts', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => null,
            ]);

            $history = $this->service->getStudentQuizHistory($studentProfile->id);

            expect($history)->toBeEmpty();
        });

        it('orders history by submission date descending', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now()->subDays(5),
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now(),
            ]);

            $history = $this->service->getStudentQuizHistory($studentProfile->id);

            expect($history->first()->submitted_at->isAfter($history->last()->submitted_at))->toBeTrue();
        });

        it('includes assignable name in history', function () {
            $quiz = Quiz::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'name' => 'Test Circle',
            ]);

            $assignment = QuizAssignment::factory()->create([
                'quiz_id' => $quiz->id,
                'assignable_type' => QuranCircle::class,
                'assignable_id' => $circle->id,
            ]);

            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $studentProfile = StudentProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
            ]);

            QuizAttempt::factory()->create([
                'quiz_assignment_id' => $assignment->id,
                'student_id' => $studentProfile->id,
                'submitted_at' => now(),
            ]);

            $history = $this->service->getStudentQuizHistory($studentProfile->id);

            expect($history->first()->assignable_name)->toBe('Test Circle');
        });
    });
});
