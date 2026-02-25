<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseHomework;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\AcademyContextService;

/**
 * Homework Policy
 *
 * Authorization policy for homework access across all homework types:
 * - Quran homework (embedded in QuranSession)
 * - Academic homework (embedded in AcademicSession)
 * - Interactive course homework (InteractiveCourseHomework model)
 */
class HomeworkPolicy
{
    /**
     * Determine whether the user can view any homework.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value, 'teacher', UserType::ACADEMIC_TEACHER->value, UserType::STUDENT->value]);
    }

    /**
     * Determine whether the user can view the interactive course homework.
     */
    public function view(User $user, InteractiveCourseHomework $homework): bool
    {
        // Admins can view any homework in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $homework);
        }

        // Teachers can view homework for their courses
        if ($user->hasRole(['teacher', UserType::ACADEMIC_TEACHER->value])) {
            $course = $homework->session?->course;
            if ($course && $course->assigned_teacher_id === $user->academicTeacherProfile?->id) {
                return true;
            }
        }

        // Students can view their own homework
        if ($user->hasRole(UserType::STUDENT->value)) {
            return $this->isEnrolledInCourse($user, $homework);
        }

        // Parents can view their children's homework
        if ($user->isParent()) {
            return $this->isParentOfEnrolledStudent($user, $homework);
        }

        return false;
    }

    /**
     * Determine whether the user can view Quran session homework.
     */
    public function viewQuranHomework(User $user, QuranSession $session): bool
    {
        // Admins can view any homework
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return true;
        }

        // Teacher can view homework for their sessions
        if ($user->hasRole('teacher') && $session->quran_teacher_id === $user->id) {
            return true;
        }

        // Student can view their own homework
        if ($user->hasRole(UserType::STUDENT->value) && $session->student_id === $user->id) {
            return true;
        }

        // Parent can view their child's homework
        if ($user->isParent()) {
            return $this->isParentOfStudent($user, $session->student_id);
        }

        return false;
    }

    /**
     * Determine whether the user can view academic session homework.
     */
    public function viewAcademicHomework(User $user, AcademicSession $session): bool
    {
        // Admins can view any homework
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return true;
        }

        // Teacher can view homework for their sessions
        // academic_teacher_id references AcademicTeacherProfile.id, not User.id
        if ($user->hasRole(UserType::ACADEMIC_TEACHER->value) && $session->academic_teacher_id === $user->academicTeacherProfile?->id) {
            return true;
        }

        // Student can view their own homework
        if ($user->hasRole(UserType::STUDENT->value) && $session->student_id === $user->id) {
            return true;
        }

        // Parent can view their child's homework
        if ($user->isParent()) {
            return $this->isParentOfStudent($user, $session->student_id);
        }

        return false;
    }

    /**
     * Determine whether the user can submit homework.
     */
    public function submit(User $user, InteractiveCourseHomework $homework): bool
    {
        // Only students can submit homework
        if (! $user->hasRole(UserType::STUDENT->value)) {
            return false;
        }

        // Must be enrolled in the course
        return $this->isEnrolledInCourse($user, $homework);
    }

    /**
     * Determine whether the user can grade homework.
     */
    public function grade(User $user, InteractiveCourseHomework $homework): bool
    {
        // Only teachers can grade
        if (! $user->hasRole(['teacher', UserType::ACADEMIC_TEACHER->value])) {
            return false;
        }

        // Must be the course teacher
        // assigned_teacher_id references AcademicTeacherProfile.id, not User.id
        $course = $homework->session?->course;

        return $course && $course->assigned_teacher_id === $user->academicTeacherProfile?->id;
    }

    /**
     * Check if student is enrolled in the course.
     */
    private function isEnrolledInCourse(User $user, InteractiveCourseHomework $homework): bool
    {
        $course = $homework->session?->course;
        if (! $course) {
            return false;
        }

        return $course->enrollments()
            ->where('student_id', $user->studentProfileUnscoped?->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Check if user is a parent of an enrolled student.
     */
    private function isParentOfEnrolledStudent(User $user, InteractiveCourseHomework $homework): bool
    {
        $parent = $user->parentProfile;
        if (! $parent) {
            return false;
        }

        $course = $homework->session?->course;
        if (! $course) {
            return false;
        }

        $childIds = $parent->students()->pluck('student_profiles.id')->toArray();

        return $course->enrollments()
            ->whereIn('student_id', $childIds)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Check if user is parent of the student.
     */
    private function isParentOfStudent(User $user, ?string $studentId): bool
    {
        if (! $studentId) {
            return false;
        }

        $parent = $user->parentProfile;
        if (! $parent) {
            return false;
        }

        // Get the student user's profile
        $studentUser = User::find($studentId);
        if (! $studentUser) {
            return false;
        }

        $studentProfile = $studentUser->studentProfileUnscoped;
        if (! $studentProfile) {
            return false;
        }

        return $parent->students()
            ->where('student_profiles.id', $studentProfile->id)
            ->exists();
    }

    /**
     * Check if homework belongs to same academy as user.
     */
    private function sameAcademy(User $user, InteractiveCourseHomework $homework): bool
    {
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            $userAcademyId = AcademyContextService::getCurrentAcademyId();
            if (! $userAcademyId) {
                return true;
            }
            $homeworkAcademyId = $homework->session?->course?->academy_id;

            return $homeworkAcademyId === $userAcademyId;
        }

        $homeworkAcademyId = $homework->session?->course?->academy_id;

        return $homeworkAcademyId === $user->academy_id;
    }
}
