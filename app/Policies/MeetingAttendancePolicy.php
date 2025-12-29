<?php

namespace App\Policies;

use App\Models\MeetingAttendance;
use App\Models\User;

/**
 * Meeting Attendance Policy
 *
 * Authorization policy for meeting attendance access and management.
 * Controls who can view and update attendance records.
 */
class MeetingAttendancePolicy
{
    /**
     * Determine whether the user can view any meeting attendance records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole([
            'super_admin',
            'admin',
            'supervisor',
            'teacher',
            'academic_teacher'
        ]);
    }

    /**
     * Determine whether the user can view the meeting attendance.
     */
    public function view(User $user, MeetingAttendance $attendance): bool
    {
        // Admins and supervisors can view any attendance in their academy
        if ($user->hasRole(['super_admin', 'admin', 'supervisor'])) {
            return $this->sameAcademy($user, $attendance);
        }

        // Teachers can view attendance for their sessions
        if ($user->hasRole(['teacher', 'academic_teacher'])) {
            return $this->isSessionTeacher($user, $attendance);
        }

        // Students can view their own attendance
        if ($user->hasRole('student') && $attendance->user_id === $user->id) {
            return true;
        }

        // Parents can view their children's attendance
        if ($user->isParent()) {
            return $this->isParentOfStudent($user, $attendance->user_id);
        }

        return false;
    }

    /**
     * Determine whether the user can update the meeting attendance.
     * This allows manual adjustments to attendance records.
     */
    public function update(User $user, MeetingAttendance $attendance): bool
    {
        // Only admins and session teachers can update attendance
        if ($user->hasRole(['super_admin', 'admin'])) {
            return $this->sameAcademy($user, $attendance);
        }

        // Session teacher can update attendance for their session
        if ($user->hasRole(['teacher', 'academic_teacher'])) {
            return $this->isSessionTeacher($user, $attendance);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the meeting attendance.
     */
    public function delete(User $user, MeetingAttendance $attendance): bool
    {
        // Only admins can delete attendance records
        if (!$user->hasRole(['super_admin', 'admin'])) {
            return false;
        }

        return $this->sameAcademy($user, $attendance);
    }

    /**
     * Determine whether the user can recalculate attendance.
     */
    public function recalculate(User $user, MeetingAttendance $attendance): bool
    {
        // Admins and session teachers can recalculate attendance
        if ($user->hasRole(['super_admin', 'admin'])) {
            return $this->sameAcademy($user, $attendance);
        }

        if ($user->hasRole(['teacher', 'academic_teacher'])) {
            return $this->isSessionTeacher($user, $attendance);
        }

        return false;
    }

    /**
     * Check if user is the teacher of the session.
     */
    private function isSessionTeacher(User $user, MeetingAttendance $attendance): bool
    {
        $session = $attendance->session;
        if (!$session) {
            return false;
        }

        // For QuranSession
        if ($attendance->session_type === 'individual' || $attendance->session_type === 'group') {
            return $session->teacher_id === $user->id;
        }

        // For AcademicSession
        if ($attendance->session_type === 'academic') {
            return $session->teacher_id === $user->id;
        }

        // For InteractiveCourseSession
        if ($session instanceof \App\Models\InteractiveCourseSession) {
            $course = $session->course;
            return $course && $course->assigned_teacher_id === $user->academicTeacherProfile?->id;
        }

        return false;
    }

    /**
     * Check if user is parent of the student.
     */
    private function isParentOfStudent(User $user, ?string $studentUserId): bool
    {
        if (!$studentUserId) {
            return false;
        }

        $parent = $user->parentProfile;
        if (!$parent) {
            return false;
        }

        $studentUser = User::find($studentUserId);
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
     * Check if attendance belongs to same academy as user.
     */
    private function sameAcademy(User $user, MeetingAttendance $attendance): bool
    {
        $session = $attendance->session;
        if (!$session) {
            return false;
        }

        // For InteractiveCourseSession, get academy through course
        if ($session instanceof \App\Models\InteractiveCourseSession) {
            $academyId = $session->course?->academy_id;
            if ($user->hasRole('super_admin')) {
                return true; // Super admin can access all
            }
            return $academyId === $user->academy_id;
        }

        // For QuranSession and AcademicSession, use direct academy_id
        if ($user->hasRole('super_admin')) {
            return true; // Super admin can access all
        }

        return $session->academy_id === $user->academy_id;
    }
}
