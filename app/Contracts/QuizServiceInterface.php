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
     *
     * @param  array  $data
     * @param  array  $questions
     * @return Quiz
     */
    public function createQuiz(array $data, array $questions = []): Quiz;

    /**
     * Update a quiz.
     *
     * @param  Quiz  $quiz
     * @param  array  $data
     * @return Quiz
     */
    public function updateQuiz(Quiz $quiz, array $data): Quiz;

    /**
     * Add a question to a quiz.
     *
     * @param  Quiz  $quiz
     * @param  array  $questionData
     * @return QuizQuestion
     */
    public function addQuestion(Quiz $quiz, array $questionData): QuizQuestion;

    /**
     * Assign quiz to an entity (circle, class, course).
     *
     * @param  Quiz  $quiz
     * @param  Model  $assignable
     * @param  array  $options
     * @return QuizAssignment
     */
    public function assignQuiz(Quiz $quiz, Model $assignable, array $options = []): QuizAssignment;

    /**
     * Get available quizzes for a student in a specific context.
     *
     * @param  Model  $assignable
     * @param  int  $studentId
     * @return Collection
     */
    public function getAvailableQuizzes(Model $assignable, int $studentId): Collection;

    /**
     * Start a quiz attempt.
     *
     * @param  QuizAssignment  $assignment
     * @param  int  $studentId
     * @return QuizAttempt
     *
     * @throws \Exception If student has no remaining attempts
     */
    public function startAttempt(QuizAssignment $assignment, int $studentId): QuizAttempt;

    /**
     * Submit quiz answers.
     *
     * @param  QuizAttempt  $attempt
     * @param  array  $answers
     * @return QuizAttempt
     *
     * @throws \Exception If attempt is already submitted
     */
    public function submitAttempt(QuizAttempt $attempt, array $answers): QuizAttempt;

    /**
     * Get quiz results for a student.
     *
     * @param  int  $studentId
     * @param  int|null  $academyId
     * @return Collection
     */
    public function getStudentResults(int $studentId, ?int $academyId = null): Collection;

    /**
     * Get quiz statistics for an assignment.
     *
     * @param  QuizAssignment  $assignment
     * @return array
     */
    public function getAssignmentStatistics(QuizAssignment $assignment): array;

    /**
     * Get all quizzes assigned to a student across all their enrollments.
     *
     * @param  int  $studentId
     * @return Collection
     */
    public function getStudentQuizzes(int $studentId): Collection;

    /**
     * Get all quiz attempts history for a student.
     *
     * @param  int  $studentId
     * @return Collection
     */
    public function getStudentQuizHistory(int $studentId): Collection;
}
