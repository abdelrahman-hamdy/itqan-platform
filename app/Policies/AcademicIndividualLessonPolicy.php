<?php

namespace App\Policies;

use App\Services\AcademyContextService;
use App\Enums\UserType;
use App\Models\AcademicIndividualLesson;
use App\Models\User;

/**
 * Academic Individual Lesson Policy
 *
 * Authorization policy for individual academic lessons (1-to-1 tutoring).
 */
class AcademicIndividualLessonPolicy
{
    /**
     * Determine whether the user can view any lessons.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value, UserType::ACADEMIC_TEACHER->value, UserType::STUDENT->value, UserType::PARENT->value]);
    }

    /**
     * Determine whether the user can view the lesson.
     */
    public function view(User $user, AcademicIndividualLesson $lesson): bool
    {
        // Admins and supervisors can view any lesson in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $lesson);
        }

        // Teachers can view lessons they teach
        if ($user->hasRole(UserType::ACADEMIC_TEACHER->value)) {
            return $this->isLessonTeacher($user, $lesson);
        }

        // Students can view their own lesson
        if ($user->hasRole(UserType::STUDENT->value)) {
            return $lesson->student_id === $user->id;
        }

        // Parents can view their children's lessons
        if ($user->isParent()) {
            return $this->isParentOfStudent($user, $lesson);
        }

        return false;
    }

    /**
     * Determine whether the user can create lessons.
     */
    public function create(User $user): bool
    {
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value]);
    }

    /**
     * Determine whether the user can update the lesson.
     */
    public function update(User $user, AcademicIndividualLesson $lesson): bool
    {
        // Admins and supervisors can update any lesson in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $lesson);
        }

        // Teachers can update their own lessons
        if ($user->hasRole(UserType::ACADEMIC_TEACHER->value)) {
            return $this->isLessonTeacher($user, $lesson);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the lesson.
     */
    public function delete(User $user, AcademicIndividualLesson $lesson): bool
    {
        // Only admins can delete lessons
        return $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value]) && $this->sameAcademy($user, $lesson);
    }

    /**
     * Determine whether the user can view the lesson's report.
     */
    public function viewReport(User $user, AcademicIndividualLesson $lesson): bool
    {
        return $this->view($user, $lesson);
    }

    /**
     * Check if user is the teacher of this lesson.
     */
    private function isLessonTeacher(User $user, AcademicIndividualLesson $lesson): bool
    {
        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile) {
            return false;
        }

        return $lesson->academic_teacher_id === $teacherProfile->id;
    }

    /**
     * Check if user is parent of the student in this lesson.
     */
    private function isParentOfStudent(User $user, AcademicIndividualLesson $lesson): bool
    {
        $parent = $user->parentProfile;
        if (! $parent) {
            return false;
        }

        // Get student user IDs through the parent-student relationship
        $studentUserIds = $parent->students()->with('user')->get()->pluck('user.id')->filter()->toArray();

        // lesson.student_id references User.id
        return in_array($lesson->student_id, $studentUserIds);
    }

    /**
     * Check if lesson belongs to same academy as user.
     */
    private function sameAcademy(User $user, AcademicIndividualLesson $lesson): bool
    {
        // For super_admin, use the selected academy context
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            $userAcademyId = AcademyContextService::getCurrentAcademyId();
            // If super admin is in global view, allow access
            if (! $userAcademyId) {
                return true;
            }

            return $lesson->academy_id === $userAcademyId;
        }

        // For other users, use their assigned academy
        return $lesson->academy_id === $user->academy_id;
    }
}
