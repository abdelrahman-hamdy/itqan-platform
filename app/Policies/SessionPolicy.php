<?php

namespace App\Policies;

use App\Contracts\MeetingCapable;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\User;

/**
 * Session Policy
 *
 * Authorization policy for session access across all session types.
 * Handles Quran, Academic, and Interactive Course sessions.
 */
class SessionPolicy
{
    /**
     * Determine whether the user can view any sessions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['superadmin', 'admin', 'supervisor', 'teacher', 'quran_teacher', 'academic_teacher', 'student']);
    }

    /**
     * Determine whether the user can view the session.
     */
    public function view(User $user, $session): bool
    {
        // Admins and supervisors can view any session in their academy
        if ($user->hasRole(['superadmin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $session);
        }

        // Teachers can view their own sessions
        if ($user->hasRole(['teacher', 'quran_teacher', 'academic_teacher'])) {
            return $this->isSessionTeacher($user, $session);
        }

        // Students can view sessions they're enrolled in
        if ($user->hasRole('student')) {
            return $this->isSessionStudent($user, $session);
        }

        // Parents can view their children's sessions
        if ($user->isParent()) {
            return $this->isParentOfSessionStudent($user, $session);
        }

        return false;
    }

    /**
     * Determine whether the user can create sessions.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['superadmin', 'admin', 'supervisor', 'teacher', 'quran_teacher', 'academic_teacher']);
    }

    /**
     * Determine whether the user can update the session.
     */
    public function update(User $user, $session): bool
    {
        // Admins and supervisors can update any session in their academy
        if ($user->hasRole(['superadmin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $session);
        }

        // Teachers can update their own sessions
        if ($user->hasRole(['teacher', 'quran_teacher', 'academic_teacher'])) {
            return $this->isSessionTeacher($user, $session);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the session.
     */
    public function delete(User $user, $session): bool
    {
        // Only admins can delete sessions
        if ($user->hasRole(['superadmin', 'admin'])) {
            return $this->sameAcademy($user, $session);
        }

        return false;
    }

    /**
     * Determine whether the user can join the session meeting.
     */
    public function joinMeeting(User $user, $session): bool
    {
        // Must be able to view the session
        if (!$this->view($user, $session)) {
            return false;
        }

        // Session must be in joinable state
        if ($session instanceof MeetingCapable) {
            return $session->canUserJoinMeeting($user);
        }

        return false;
    }

    /**
     * Determine whether the user can manage the session meeting.
     */
    public function manageMeeting(User $user, $session): bool
    {
        // Only teachers can manage their own session meetings
        if (!$user->hasRole(['superadmin', 'admin', 'teacher', 'quran_teacher', 'academic_teacher'])) {
            return false;
        }

        return $this->isSessionTeacher($user, $session) || $user->hasRole(['superadmin', 'admin']);
    }

    /**
     * Determine whether the user can reschedule the session.
     */
    public function reschedule(User $user, $session): bool
    {
        return $this->update($user, $session);
    }

    /**
     * Determine whether the user can cancel the session.
     */
    public function cancel(User $user, $session): bool
    {
        return $this->update($user, $session);
    }

    /**
     * Check if user is the teacher of this session.
     */
    private function isSessionTeacher(User $user, $session): bool
    {
        if ($session instanceof QuranSession) {
            return $session->quran_teacher_profile_id === $user->quranTeacherProfile?->id;
        }

        if ($session instanceof AcademicSession) {
            return $session->academic_teacher_profile_id === $user->academicTeacherProfile?->id;
        }

        if ($session instanceof InteractiveCourseSession) {
            $course = $session->course;
            return $course && $course->academic_teacher_profile_id === $user->academicTeacherProfile?->id;
        }

        return false;
    }

    /**
     * Check if user is a student in this session.
     */
    private function isSessionStudent(User $user, $session): bool
    {
        $studentProfile = $user->studentProfileUnscoped;
        if (!$studentProfile) {
            return false;
        }

        if ($session instanceof QuranSession) {
            // For individual sessions, check direct assignment
            if ($session->session_type === 'individual') {
                return $session->student_profile_id === $studentProfile->id;
            }
            // For group sessions, check circle membership
            $circle = $session->circle;
            return $circle && $circle->students()->where('student_profiles.id', $studentProfile->id)->exists();
        }

        if ($session instanceof AcademicSession) {
            return $session->student_profile_id === $studentProfile->id;
        }

        if ($session instanceof InteractiveCourseSession) {
            $course = $session->course;
            return $course && $course->enrollments()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Check if user is a parent of a student in this session.
     */
    private function isParentOfSessionStudent(User $user, $session): bool
    {
        $parent = $user->parentProfile;
        if (!$parent) {
            return false;
        }

        // Get all student IDs for this parent
        $studentIds = $parent->students()->pluck('student_profiles.id')->toArray();

        if ($session instanceof QuranSession) {
            if ($session->session_type === 'individual') {
                return in_array($session->student_profile_id, $studentIds);
            }
            // For group sessions, check circle membership
            $circle = $session->circle;
            return $circle && $circle->students()->whereIn('student_profiles.id', $studentIds)->exists();
        }

        if ($session instanceof AcademicSession) {
            return in_array($session->student_profile_id, $studentIds);
        }

        if ($session instanceof InteractiveCourseSession) {
            $course = $session->course;
            if (!$course) {
                return false;
            }
            $userIds = $parent->students()->with('user')->get()->pluck('user.id')->filter()->toArray();
            return $course->enrollments()->whereIn('user_id', $userIds)->exists();
        }

        return false;
    }

    /**
     * Check if session belongs to same academy as user.
     */
    private function sameAcademy(User $user, $session): bool
    {
        $userAcademyId = $user->getCurrentAcademyId();
        if (!$userAcademyId) {
            return false;
        }

        if ($session instanceof InteractiveCourseSession) {
            return $session->course?->academy_id === $userAcademyId;
        }

        return $session->academy_id === $userAcademyId;
    }
}
