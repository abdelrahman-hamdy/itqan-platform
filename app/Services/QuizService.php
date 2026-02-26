<?php

namespace App\Services;

use Exception;
use App\Models\StudentProfile;
use App\Models\QuranCircleEnrollment;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourseEnrollment;
use App\Models\InteractiveCourse;
use App\Models\CourseSubscription;
use App\Models\RecordedCourse;
use App\Contracts\QuizServiceInterface;
use App\Enums\EnrollmentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuizService implements QuizServiceInterface
{
    /**
     * Create a new quiz with questions
     */
    public function createQuiz(array $data, array $questions = []): Quiz
    {
        return DB::transaction(function () use ($data, $questions) {
            $quiz = Quiz::create($data);

            foreach ($questions as $index => $questionData) {
                $quiz->questions()->create([
                    'question_text' => $questionData['question_text'],
                    'options' => $questionData['options'],
                    'correct_option' => $questionData['correct_option'],
                    'order' => $questionData['order'] ?? $index,
                ]);
            }

            return $quiz->load('questions');
        });
    }

    /**
     * Update a quiz
     */
    public function updateQuiz(Quiz $quiz, array $data): Quiz
    {
        $quiz->update($data);

        return $quiz->fresh();
    }

    /**
     * Add a question to a quiz
     */
    public function addQuestion(Quiz $quiz, array $questionData): QuizQuestion
    {
        $maxOrder = $quiz->questions()->max('order') ?? -1;

        return $quiz->questions()->create([
            'question_text' => $questionData['question_text'],
            'options' => $questionData['options'],
            'correct_option' => $questionData['correct_option'],
            'order' => $questionData['order'] ?? ($maxOrder + 1),
        ]);
    }

    /**
     * Assign quiz to an entity (circle, class, course)
     */
    public function assignQuiz(Quiz $quiz, Model $assignable, array $options = []): QuizAssignment
    {
        return QuizAssignment::create([
            'quiz_id' => $quiz->id,
            'assignable_type' => get_class($assignable),
            'assignable_id' => $assignable->getKey(),
            'is_visible' => $options['is_visible'] ?? true,
            'available_from' => $options['available_from'] ?? null,
            'available_until' => $options['available_until'] ?? null,
            'max_attempts' => $options['max_attempts'] ?? 1,
        ]);
    }

    /**
     * Get available quizzes for a student in a specific context
     */
    public function getAvailableQuizzes(Model $assignable, int $studentId): Collection
    {
        $assignments = QuizAssignment::with(['quiz.questions', 'assignable', 'attempts' => function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        }])
            ->where('assignable_type', get_class($assignable))
            ->where('assignable_id', $assignable->getKey())
            ->visible()
            ->available()
            ->whereHas('quiz', function ($query) {
                $query->active();
            })
            ->get();

        return $assignments->map(function ($assignment) {
            $completedAttempts = $assignment->attempts->filter(fn ($a) => $a->isCompleted())->count();
            $bestScore = $assignment->attempts->filter(fn ($a) => $a->isCompleted())->max('score');
            $passed = $assignment->attempts->filter(fn ($a) => $a->passed)->isNotEmpty();
            $inProgress = $assignment->attempts->filter(fn ($a) => $a->isInProgress())->first();

            return (object) [
                'assignment' => $assignment,
                'quiz' => $assignment->quiz,
                'assignable' => $assignment->assignable,
                'assignable_name' => $this->getAssignableName($assignment->assignable),
                'completed_attempts' => $completedAttempts,
                'remaining_attempts' => max(0, $assignment->max_attempts - $completedAttempts),
                'best_score' => $bestScore,
                'passed' => $passed,
                'can_attempt' => $completedAttempts < $assignment->max_attempts,
                'in_progress_attempt' => $inProgress,
            ];
        });
    }

    /**
     * Start a quiz attempt
     */
    public function startAttempt(QuizAssignment $assignment, int $studentId): QuizAttempt
    {
        // Check if student has an in-progress attempt
        $existingAttempt = QuizAttempt::where('quiz_assignment_id', $assignment->id)
            ->where('student_id', $studentId)
            ->whereNull('submitted_at')
            ->first();

        if ($existingAttempt) {
            return $existingAttempt;
        }

        // Check if student can start a new attempt
        if (! $assignment->canStudentAttempt($studentId)) {
            throw new Exception(__('quiz.max_attempts_exceeded'));
        }

        return QuizAttempt::create([
            'quiz_assignment_id' => $assignment->id,
            'student_id' => $studentId,
            'started_at' => now(),
        ]);
    }

    /**
     * Submit quiz answers
     */
    public function submitAttempt(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        if ($attempt->isCompleted()) {
            throw new Exception(__('quiz.attempt_already_submitted'));
        }

        $attempt->submit($answers);

        return $attempt->fresh();
    }

    /**
     * Get quiz results for a student
     */
    public function getStudentResults(int $studentId, ?int $academyId = null): Collection
    {
        $query = QuizAttempt::with(['assignment.quiz', 'assignment.assignable'])
            ->where('student_id', $studentId)
            ->completed()
            ->latest('submitted_at');

        if ($academyId) {
            $query->whereHas('assignment.quiz', function ($q) use ($academyId) {
                $q->where('academy_id', $academyId);
            });
        }

        return $query->get();
    }

    /**
     * Get quiz statistics for an assignment
     */
    public function getAssignmentStatistics(QuizAssignment $assignment): array
    {
        $attempts = $assignment->attempts()->completed()->get();

        if ($attempts->isEmpty()) {
            return [
                'total_attempts' => 0,
                'unique_students' => 0,
                'average_score' => 0,
                'pass_rate' => 0,
                'highest_score' => 0,
                'lowest_score' => 0,
            ];
        }

        return [
            'total_attempts' => $attempts->count(),
            'unique_students' => $attempts->unique('student_id')->count(),
            'average_score' => round($attempts->avg('score'), 1),
            'pass_rate' => round(($attempts->where('passed', true)->count() / $attempts->count()) * 100, 1),
            'highest_score' => $attempts->max('score'),
            'lowest_score' => $attempts->min('score'),
        ];
    }

    /**
     * Get all quizzes assigned to a student across all their enrollments
     */
    public function getStudentQuizzes(int $studentId): Collection
    {
        // Get student's user_id (most tables use users.id as student_id)
        $student = StudentProfile::find($studentId);
        $userId = $student?->user_id;

        if (! $userId) {
            return collect();
        }

        // Collect all assignable IDs that the student has access to
        $assignableIds = collect();

        // 1. Quran Circles (group) - using Eloquent for tenant scoping via circle relationship
        // The QuranCircleEnrollment model joins with QuranCircle which has tenant scoping
        $quranCircleIds = QuranCircleEnrollment::where('student_id', $userId)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereHas('circle', function ($query) use ($student) {
                // Ensure circle belongs to same academy as student for tenant isolation
                $query->where('academy_id', $student->user?->academy_id);
            })
            ->pluck('circle_id');
        foreach ($quranCircleIds as $id) {
            $assignableIds->push(['type' => QuranCircle::class, 'id' => $id]);
        }

        // 2. Quran Individual Circles (uses users.id)
        $individualCircleIds = QuranIndividualCircle::where('student_id', $userId)
            ->pluck('id');
        foreach ($individualCircleIds as $id) {
            $assignableIds->push(['type' => QuranIndividualCircle::class, 'id' => $id]);
        }

        // 3. Academic Individual Lessons (uses users.id) - directly on lesson or via subscription
        $academicLessonIds = AcademicIndividualLesson::where('student_id', $userId)
            ->pluck('id');
        foreach ($academicLessonIds as $id) {
            $assignableIds->push(['type' => AcademicIndividualLesson::class, 'id' => $id]);
        }

        // 3b. Academic Subscriptions (uses users.id) - private lessons subscriptions
        $academicSubIds = AcademicSubscription::where('student_id', $userId)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->pluck('id');
        foreach ($academicSubIds as $id) {
            $assignableIds->push(['type' => AcademicSubscription::class, 'id' => $id]);
        }

        // 4. Interactive Courses - from active enrollments (uses student_profiles.id)
        $interactiveCourseIds = InteractiveCourseEnrollment::where('student_id', $studentId)
            ->where('enrollment_status', EnrollmentStatus::ENROLLED)
            ->pluck('course_id');
        foreach ($interactiveCourseIds as $id) {
            $assignableIds->push(['type' => InteractiveCourse::class, 'id' => $id]);
        }

        // 5. Recorded Courses - using CourseSubscription model for tenant scoping
        // CourseSubscription extends BaseSubscription which has tenant scoping
        $recordedCourseIds = CourseSubscription::where('student_id', $userId)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereNotNull('recorded_course_id')
            ->whereHas('recordedCourse', function ($query) use ($student) {
                // Ensure course belongs to same academy for tenant isolation
                $query->where('academy_id', $student->user?->academy_id);
            })
            ->pluck('recorded_course_id');
        foreach ($recordedCourseIds as $id) {
            $assignableIds->push(['type' => RecordedCourse::class, 'id' => $id]);
        }

        // Build query for quiz assignments based on collected assignables
        $query = QuizAssignment::with(['quiz.questions', 'assignable', 'attempts' => function ($q) use ($studentId) {
            $q->where('student_id', $studentId);
        }])
            ->visible()
            ->whereHas('quiz', function ($q) {
                $q->active();
            });

        // Add where conditions for each assignable
        if ($assignableIds->isNotEmpty()) {
            $query->where(function ($q) use ($assignableIds) {
                foreach ($assignableIds as $assignable) {
                    $q->orWhere(function ($subQ) use ($assignable) {
                        $subQ->where('assignable_type', $assignable['type'])
                            ->where('assignable_id', $assignable['id']);
                    });
                }
            });
        } else {
            // No subscriptions, return empty
            return collect();
        }

        $assignments = $query->get();

        return $assignments->map(function ($assignment) {
            $completedAttempts = $assignment->attempts->filter(fn ($a) => $a->isCompleted())->count();
            $bestScore = $assignment->attempts->filter(fn ($a) => $a->isCompleted())->max('score');
            $passed = $assignment->attempts->filter(fn ($a) => $a->passed)->isNotEmpty();
            $inProgress = $assignment->attempts->filter(fn ($a) => $a->isInProgress())->first();

            return (object) [
                'assignment' => $assignment,
                'quiz' => $assignment->quiz,
                'assignable' => $assignment->assignable,
                'assignable_type' => class_basename($assignment->assignable_type),
                'assignable_name' => $this->getAssignableName($assignment->assignable),
                'completed_attempts' => $completedAttempts,
                'remaining_attempts' => max(0, $assignment->max_attempts - $completedAttempts),
                'best_score' => $bestScore,
                'passed' => $passed,
                'can_attempt' => $assignment->isAvailable() && $completedAttempts < $assignment->max_attempts,
                'in_progress_attempt' => $inProgress,
            ];
        })->sortByDesc(function ($quiz) {
            // Sort by: in-progress first, then by can_attempt, then by date
            if ($quiz->in_progress_attempt) {
                return 3;
            }
            if ($quiz->can_attempt) {
                return 2;
            }

            return 1;
        })->values();
    }

    /**
     * Get all quiz attempts history for a student
     */
    public function getStudentQuizHistory(int $studentId): Collection
    {
        return QuizAttempt::with(['assignment.quiz', 'assignment.assignable'])
            ->where('student_id', $studentId)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->get()
            ->map(function ($attempt) {
                return (object) [
                    'attempt' => $attempt,
                    'quiz' => $attempt->assignment->quiz,
                    'assignable' => $attempt->assignment->assignable,
                    'assignable_name' => $this->getAssignableName($attempt->assignment->assignable),
                    'score' => $attempt->score,
                    'passed' => $attempt->passed,
                    'submitted_at' => $attempt->submitted_at,
                ];
            });
    }

    /**
     * Check if student has access to the assignable entity
     */
    private function studentHasAccessToAssignable(int $studentId, Model $assignable): bool
    {
        $type = get_class($assignable);

        return match ($type) {
            QuranCircle::class => $assignable->subscriptions()
                ->where('student_id', $studentId)
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->exists(),
            QuranIndividualCircle::class => $assignable->student_id === $studentId,
            AcademicIndividualLesson::class => $assignable->subscription?->student_id === $studentId,
            AcademicSubscription::class => $assignable->student_id === $studentId && $assignable->status === SessionSubscriptionStatus::ACTIVE,
            InteractiveCourse::class => $assignable->enrollments()
                ->where('student_id', $studentId)
                ->where('enrollment_status', EnrollmentStatus::ENROLLED)
                ->exists(),
            RecordedCourse::class => ($sp = \App\Models\StudentProfile::find($studentId)) !== null &&
                $assignable->enrollments()
                    ->where('student_id', $sp->user_id)
                    ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                    ->exists(),
            default => false,
        };
    }

    /**
     * Get a human-readable name for the assignable entity
     */
    private function getAssignableName(Model $assignable): string
    {
        $type = get_class($assignable);

        return match ($type) {
            QuranCircle::class => $assignable->name ?? __('quiz.assignable.quran_circle'),
            QuranIndividualCircle::class => $assignable->name ?? __('quiz.assignable.individual_circle'),
            AcademicIndividualLesson::class => $assignable->subscription?->teacher?->user?->name ?? __('quiz.assignable.academic_lesson'),
            AcademicSubscription::class => ($assignable->subject_name ?? __('quiz.assignable.private_lesson')).' - '.($assignable->teacher?->user?->name ?? ''),
            InteractiveCourse::class => $assignable->name ?? __('quiz.assignable.interactive_course'),
            RecordedCourse::class => $assignable->name ?? __('quiz.assignable.recorded_course'),
            default => __('quiz.assignable.unknown'),
        };
    }
}
