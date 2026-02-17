<?php

namespace App\Policies;

use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use App\Enums\EnrollmentStatus;
use App\Enums\UserType;
use App\Models\SessionRecording;
use App\Models\User;

/**
 * Recording Policy
 *
 * Authorization policy for session recording access and management.
 * Controls who can view, download, and delete recordings.
 */
class RecordingPolicy
{
    /**
     * Determine whether the user can view any session recordings.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole([
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
            UserType::SUPERVISOR->value,
            'teacher',
            UserType::ACADEMIC_TEACHER->value,
            UserType::STUDENT->value,
            UserType::PARENT->value,
        ]);
    }

    /**
     * Determine whether the user can view the session recording.
     */
    public function view(User $user, SessionRecording $recording): bool
    {
        // Admins can view any recording in their academy
        if ($user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])) {
            return $this->sameAcademy($user, $recording);
        }

        // Session teacher can view recordings of their sessions
        if ($user->hasRole(['teacher', UserType::ACADEMIC_TEACHER->value])) {
            return $this->isSessionTeacher($user, $recording);
        }

        // Enrolled students can view recordings
        if ($user->hasRole(UserType::STUDENT->value)) {
            return $this->isEnrolledInSession($user, $recording);
        }

        // Parents can view their children's session recordings
        if ($user->isParent()) {
            return $this->hasChildEnrolledInSession($user, $recording);
        }

        return false;
    }

    /**
     * Determine whether the user can download the session recording.
     */
    public function download(User $user, SessionRecording $recording): bool
    {
        // Same as view permission
        return $this->view($user, $recording);
    }

    /**
     * Determine whether the user can delete the session recording.
     */
    public function delete(User $user, SessionRecording $recording): bool
    {
        // Only admins and super admins can delete recordings
        if (! $user->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value])) {
            return false;
        }

        return $this->sameAcademy($user, $recording);
    }

    /**
     * Determine whether the user can restore the recording.
     */
    public function restore(User $user, SessionRecording $recording): bool
    {
        // Only super admins can restore recordings
        return $user->hasRole(UserType::SUPER_ADMIN->value);
    }

    /**
     * Determine whether the user can permanently delete the recording.
     */
    public function forceDelete(User $user, SessionRecording $recording): bool
    {
        // Only super admins can permanently delete recordings
        return $user->hasRole(UserType::SUPER_ADMIN->value);
    }

    /**
     * Check if user is the teacher of the recorded session.
     */
    private function isSessionTeacher(User $user, SessionRecording $recording): bool
    {
        $session = $recording->recordable;
        if (! $session) {
            return false;
        }

        // For InteractiveCourseSession
        if ($session instanceof InteractiveCourseSession) {
            $course = $session->course;

            return $course && $course->assigned_teacher_id === $user->academicTeacherProfile?->id;
        }

        // For QuranSession
        if ($session instanceof QuranSession) {
            return $session->teacher_id === $user->id;
        }

        // For AcademicSession
        if ($session instanceof AcademicSession) {
            return $session->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Check if student is enrolled in the session.
     */
    private function isEnrolledInSession(User $user, SessionRecording $recording): bool
    {
        $session = $recording->recordable;
        if (! $session || ! $user->studentProfileUnscoped) {
            return false;
        }

        // For InteractiveCourseSession
        if ($session instanceof InteractiveCourseSession) {
            $course = $session->course;
            if (! $course) {
                return false;
            }

            return $course->enrollments()
                ->where('student_id', $user->studentProfileUnscoped->id)
                ->where('enrollment_status', EnrollmentStatus::ENROLLED)
                ->exists();
        }

        // For QuranSession
        if ($session instanceof QuranSession) {
            return $session->student_id === $user->id;
        }

        // For AcademicSession
        if ($session instanceof AcademicSession) {
            return $session->student_id === $user->id;
        }

        return false;
    }

    /**
     * Check if user's child is enrolled in the session.
     */
    private function hasChildEnrolledInSession(User $user, SessionRecording $recording): bool
    {
        $parent = $user->parentProfile;
        if (! $parent) {
            return false;
        }

        $session = $recording->recordable;
        if (! $session) {
            return false;
        }

        $childIds = $parent->students()->pluck('student_profiles.id')->toArray();
        $childUserIds = $parent->students()->with('user')->get()->pluck('user.id')->filter()->toArray();

        // For InteractiveCourseSession
        if ($session instanceof InteractiveCourseSession) {
            $course = $session->course;
            if (! $course) {
                return false;
            }

            return $course->enrollments()
                ->whereIn('student_id', $childIds)
                ->where('enrollment_status', EnrollmentStatus::ENROLLED)
                ->exists();
        }

        // For QuranSession
        if ($session instanceof QuranSession) {
            return in_array($session->student_id, $childUserIds);
        }

        // For AcademicSession
        if ($session instanceof AcademicSession) {
            return in_array($session->student_id, $childUserIds);
        }

        return false;
    }

    /**
     * Check if recording belongs to same academy as user.
     */
    private function sameAcademy(User $user, SessionRecording $recording): bool
    {
        $session = $recording->recordable;
        if (! $session) {
            return false;
        }

        // For InteractiveCourseSession, get academy through course
        if ($session instanceof InteractiveCourseSession) {
            $academyId = $session->course?->academy_id;
            if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
                return true; // Super admin can access all
            }

            return $academyId === $user->academy_id;
        }

        // For QuranSession and AcademicSession, use direct academy_id
        if ($user->hasRole(UserType::SUPER_ADMIN->value)) {
            return true; // Super admin can access all
        }

        return $session->academy_id === $user->academy_id;
    }
}
