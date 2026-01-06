<?php

namespace App\Contracts;

use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Interface for quiz management service.
 *
 * Handles quiz creation, assignment, attempts, and grading
 * for all assignable entities (circles, courses, subscriptions).
 */
interface QuizServiceInterface
{
    /**
     * Create a new quiz with questions.
     */
    public function createQuiz(array $data, array $questions = []): Quiz;

    /**
     * Update a quiz.
     */
    public function updateQuiz(Quiz $quiz, array $data): Quiz;

    /**
     * Add a question to a quiz.
     */
    public function addQuestion(Quiz $quiz, array $questionData): QuizQuestion;

    /**
     * Assign quiz to an entity (circle, class, course).
     */
    public function assignQuiz(Quiz $quiz, Model $assignable, array $options = []): QuizAssignment;

    /**
     * Get available quizzes for a student in a specific context.
     */
    public function getAvailableQuizzes(Model $assignable, int $studentId): Collection;

    /**
     * Start a quiz attempt.
     *
     *
     * @throws \Exception If student has no remaining attempts
     */
    public function startAttempt(QuizAssignment $assignment, int $studentId): QuizAttempt;

    /**
     * Submit quiz answers.
     *
     *
     * @throws \Exception If attempt is already submitted
     */
    public function submitAttempt(QuizAttempt $attempt, array $answers): QuizAttempt;

    /**
     * Get quiz results for a student.
     */
    public function getStudentResults(int $studentId, ?int $academyId = null): Collection;

    /**
     * Get quiz statistics for an assignment.
     */
    public function getAssignmentStatistics(QuizAssignment $assignment): array;

    /**
     * Get all quizzes assigned to a student across all their enrollments.
     */
    public function getStudentQuizzes(int $studentId): Collection;

    /**
     * Get all quiz attempts history for a student.
     */
    public function getStudentQuizHistory(int $studentId): Collection;
}
