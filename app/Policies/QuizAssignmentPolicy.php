<?php

namespace App\Policies;

use App\Models\QuizAssignment;
use App\Models\User;
use App\Services\AcademyContextService;

/**
 * Quiz Assignment Policy
 *
 * Authorization policy for quiz assignment access.
 * Controls who can view, take, and grade quiz assignments.
 */
class QuizAssignmentPolicy
{
    /**
     * Determine whether the user can view any quiz assignments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'supervisor', 'teacher', 'academic_teacher', 'student']);
    }

    /**
     * Determine whether the user can view the quiz assignment.
     */
    public function view(User $user, QuizAssignment $assignment): bool
    {
        // Admins can view any assignment in their academy
        if ($user->hasRole(['super_admin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $assignment);
        }

        // Teachers can view assignments for their quizzes
        if ($user->hasRole(['teacher', 'academic_teacher'])) {
            $quiz = $assignment->quiz;
            if ($quiz && $quiz->created_by === $user->id) {
                return true;
            }
        }

        // Students can view their own assignments
        if ($user->hasRole('student')) {
            return $assignment->student_id === $user->id;
        }

        // Parents can view their children's assignments
        if ($user->isParent()) {
            return $this->isParentOfStudent($user, $assignment->student_id);
        }

        return false;
    }

    /**
     * Determine whether the user can start the quiz.
     */
    public function start(User $user, QuizAssignment $assignment): bool
    {
        // Only the assigned student can start the quiz
        if (!$user->hasRole('student')) {
            return false;
        }

        if ($assignment->student_id !== $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can take the quiz (for QuizAttempt).
     */
    public function take(User $user, $attempt): bool
    {
        // Only the student who owns the attempt can take it
        if (!$user->hasRole('student')) {
            return false;
        }

        $student = $user->studentProfile;
        if (!$student) {
            return false;
        }

        return $attempt->student_id === $student->id;
    }

    /**
     * Determine whether the user can submit the quiz (for QuizAttempt).
     */
    public function submit(User $user, $attempt): bool
    {
        return $this->take($user, $attempt);
    }

    /**
     * Determine whether the user can view quiz result.
     */
    public function viewResult(User $user, QuizAssignment $assignment): bool
    {
        // Same rules as view
        return $this->view($user, $assignment);
    }

    /**
     * Determine whether the user can view quiz results.
     */
    public function viewResults(User $user, QuizAssignment $assignment): bool
    {
        return $this->viewResult($user, $assignment);
    }

    /**
     * Determine whether the user can grade the quiz.
     */
    public function grade(User $user, QuizAssignment $assignment): bool
    {
        // Only teachers and admins can grade
        if (!$user->hasRole(['super_admin', 'admin', 'teacher', 'academic_teacher'])) {
            return false;
        }

        // For teachers, must be the quiz creator
        if ($user->hasRole(['teacher', 'academic_teacher'])) {
            $quiz = $assignment->quiz;
            return $quiz && $quiz->created_by === $user->id;
        }

        // For admins, must be same academy
        return $this->sameAcademy($user, $assignment);
    }

    /**
     * Check if user is parent of the student.
     */
    private function isParentOfStudent(User $user, ?string $studentId): bool
    {
        if (!$studentId) {
            return false;
        }

        $parent = $user->parentProfile;
        if (!$parent) {
            return false;
        }

        $studentUser = User::find($studentId);
        if (!$studentUser) {
            return false;
        }

        $studentProfile = $studentUser->studentProfileUnscoped;
        if (!$studentProfile) {
            return false;
        }

        return $parent->students()
            ->where('student_profiles.id', $studentProfile->id)
            ->exists();
    }

    /**
     * Check if assignment belongs to same academy as user.
     */
    private function sameAcademy(User $user, QuizAssignment $assignment): bool
    {
        if ($user->hasRole('super_admin')) {
            $userAcademyId = AcademyContextService::getCurrentAcademyId();
            if (!$userAcademyId) {
                return true;
            }
            $quizAcademyId = $assignment->quiz?->academy_id;
            return $quizAcademyId === $userAcademyId;
        }

        $quizAcademyId = $assignment->quiz?->academy_id;
        return $quizAcademyId === $user->academy_id;
    }
}
