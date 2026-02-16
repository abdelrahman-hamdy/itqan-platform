<?php

namespace App\Policies;

use App\Enums\UserType;
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
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value, UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value, UserType::STUDENT->value]);
    }

    /**
     * Determine whether the user can view the quiz assignment.
     */
    public function view(User $user, QuizAssignment $assignment): bool
    {
        // Admins can view any assignment in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $assignment);
        }

        // Teachers can view assignments for their quizzes
        if ($user->hasRole([UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            $quiz = $assignment->quiz;
            if ($quiz && $quiz->created_by === $user->id) {
                return true;
            }
        }

        // Students can view assignments they are part of (via assignable entity)
        if ($user->hasRole(UserType::STUDENT->value)) {
            return $assignment->isStudentInAssignment($user);
        }

        // Parents can view their children's assignments
        if ($user->isParent()) {
            return $this->isParentOfStudentInAssignment($user, $assignment);
        }

        return false;
    }

    /**
     * Determine whether the user can start the quiz.
     */
    public function start(User $user, QuizAssignment $assignment): bool
    {
        // Only students can start quizzes
        if (! $user->hasRole(UserType::STUDENT->value)) {
            return false;
        }

        // Student must be part of the assignable entity (circle, course, lesson, etc.)
        return $assignment->isStudentInAssignment($user);
    }

    /**
     * Determine whether the user can take the quiz (for QuizAttempt).
     */
    public function take(User $user, $attempt): bool
    {
        // Only the student who owns the attempt can take it
        if (! $user->hasRole(UserType::STUDENT->value)) {
            return false;
        }

        $student = $user->studentProfile;
        if (! $student) {
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
        if (! $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            return false;
        }

        // For teachers, must be the quiz creator
        if ($user->hasRole([UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            $quiz = $assignment->quiz;

            return $quiz && $quiz->created_by === $user->id;
        }

        // For admins, must be same academy
        return $this->sameAcademy($user, $assignment);
    }

    /**
     * Check if user is parent of any student in the assignment.
     */
    private function isParentOfStudentInAssignment(User $user, QuizAssignment $assignment): bool
    {
        $parent = $user->parentProfile;
        if (! $parent) {
            return false;
        }

        $affectedStudents = $assignment->getAffectedStudents();

        foreach ($affectedStudents as $student) {
            $studentProfile = $student->studentProfileUnscoped;
            if ($studentProfile && $parent->students()->where('student_profiles.id', $studentProfile->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if assignment belongs to same academy as user.
     */
    private function sameAcademy(User $user, QuizAssignment $assignment): bool
    {
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            $userAcademyId = AcademyContextService::getCurrentAcademyId();
            if (! $userAcademyId) {
                return true;
            }
            $quizAcademyId = $assignment->quiz?->academy_id;

            return $quizAcademyId === $userAcademyId;
        }

        $quizAcademyId = $assignment->quiz?->academy_id;

        return $quizAcademyId === $user->academy_id;
    }
}
