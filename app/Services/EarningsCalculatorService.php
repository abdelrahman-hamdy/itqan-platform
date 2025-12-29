<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Earnings Calculator Service
 *
 * Pure calculation logic for teacher earnings.
 * Handles polymorphic calculation based on session type and payment models:
 * - Quran: individual_rate or group_rate
 * - Academic: individual_rate
 * - Interactive: fixed_amount, per_student, or per_session
 */
class EarningsCalculatorService
{
    /**
     * Calculate earnings based on session type (polymorphic dispatch)
     */
    public function calculateForSession(BaseSession $session): ?float
    {
        return match (true) {
            $session instanceof QuranSession => $this->calculateQuranSessionEarnings($session),
            $session instanceof AcademicSession => $this->calculateAcademicSessionEarnings($session),
            $session instanceof InteractiveCourseSession => $this->calculateInteractiveSessionEarnings($session),
            default => null,
        };
    }

    /**
     * Check if session is eligible for earnings
     */
    public function isEligibleForEarnings(BaseSession $session): bool
    {
        // 1. Must be completed
        if ($session->status !== SessionStatus::COMPLETED) {
            return false;
        }

        // 2. Must not be a trial session
        if ($session instanceof QuranSession && $session->session_type === 'trial') {
            return false;
        }

        // 3. Teacher must have attended
        if (! $this->didTeacherAttend($session)) {
            return false;
        }

        return true;
    }

    /**
     * Check if teacher attended the session (at least 50%)
     */
    public function didTeacherAttend(BaseSession $session): bool
    {
        $teacherId = $this->getTeacherId($session);

        if (! $teacherId) {
            return false;
        }

        $attendance = MeetingAttendance::where('session_id', $session->id)
            ->where('user_id', $teacherId)
            ->where('user_type', 'teacher')
            ->where('is_calculated', true)
            ->first();

        if (! $attendance) {
            return true;
        }

        // Teacher must have attended at least 50% of the session
        return ($attendance->attendance_percentage ?? 0) >= 50;
    }

    /**
     * Calculate earnings for Quran sessions
     */
    protected function calculateQuranSessionEarnings(QuranSession $session): ?float
    {
        $cacheKey = "teacher:quran_profile:{$session->quranTeacher?->id}";

        $teacher = Cache::remember($cacheKey, now()->addHours(1), function () use ($session) {
            return $session->quranTeacher?->quranTeacherProfile;
        });

        if (! $teacher) {
            Log::error('Quran teacher profile not found', ['session_id' => $session->id]);

            return null;
        }

        // Individual sessions
        if ($session->session_type === 'individual') {
            $amount = $teacher->session_price_individual;

            if ($amount === null || $amount <= 0) {
                report(new \InvalidArgumentException(
                    "Invalid session_price_individual for Quran teacher {$teacher->id}: ".
                    var_export($amount, true)." (session: {$session->id})"
                ));
                Log::error('Invalid Quran individual session price', [
                    'session_id' => $session->id,
                    'teacher_id' => $teacher->id,
                    'session_price_individual' => $amount,
                ]);

                return null;
            }

            return (float) $amount;
        }

        // Group/circle sessions: teacher gets session_price_group per session
        // NOT multiplied by student count (per business rules)
        if (in_array($session->session_type, ['group', 'circle'])) {
            $amount = $teacher->session_price_group;

            if ($amount === null || $amount <= 0) {
                report(new \InvalidArgumentException(
                    "Invalid session_price_group for Quran teacher {$teacher->id}: ".
                    var_export($amount, true)." (session: {$session->id})"
                ));
                Log::error('Invalid Quran group session price', [
                    'session_id' => $session->id,
                    'teacher_id' => $teacher->id,
                    'session_price_group' => $amount,
                ]);

                return null;
            }

            return (float) $amount;
        }

        return null;
    }

    /**
     * Calculate earnings for Academic sessions
     */
    protected function calculateAcademicSessionEarnings(AcademicSession $session): ?float
    {
        $cacheKey = "teacher:academic_profile:{$session->academic_teacher_id}";

        $teacher = Cache::remember($cacheKey, now()->addHours(1), function () use ($session) {
            return $session->academicTeacher;
        });

        if (! $teacher) {
            Log::error('Academic teacher profile not found', ['session_id' => $session->id]);

            return null;
        }

        // Academic sessions are always individual
        $amount = $teacher->session_price_individual;

        if ($amount === null || $amount <= 0) {
            report(new \InvalidArgumentException(
                "Invalid session_price_individual for Academic teacher {$teacher->id}: ".
                var_export($amount, true)." (session: {$session->id})"
            ));
            Log::error('Invalid Academic session price', [
                'session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'session_price_individual' => $amount,
            ]);

            return null;
        }

        return (float) $amount;
    }

    /**
     * Calculate earnings for Interactive Course sessions
     */
    protected function calculateInteractiveSessionEarnings(InteractiveCourseSession $session): ?float
    {
        $course = $session->course;

        if (! $course) {
            Log::error('Interactive course not found', ['session_id' => $session->id]);

            return null;
        }

        return match ($course->payment_type) {
            'fixed_amount' => $this->calculateFixedAmount($course),
            'per_student' => $this->calculatePerStudent($course),
            'per_session' => $this->calculatePerSession($course),
            default => null,
        };
    }

    /**
     * Calculate fixed amount payment (total amount / total sessions)
     */
    protected function calculateFixedAmount($course): ?float
    {
        $fixedAmount = $course->teacher_fixed_amount;
        $totalSessions = $course->total_sessions;

        // Validate fixed amount
        if ($fixedAmount === null || $fixedAmount <= 0) {
            report(new \InvalidArgumentException(
                "Invalid teacher_fixed_amount for course {$course->id}: ".
                var_export($fixedAmount, true)
            ));
            Log::error('Invalid teacher fixed amount', [
                'course_id' => $course->id,
                'teacher_fixed_amount' => $fixedAmount,
            ]);

            return null;
        }

        // Validate total sessions
        if ($totalSessions === null || $totalSessions <= 0) {
            report(new \InvalidArgumentException(
                "Invalid total_sessions for course {$course->id}: ".
                var_export($totalSessions, true)
            ));
            Log::error('Invalid total sessions count', [
                'course_id' => $course->id,
                'total_sessions' => $totalSessions,
            ]);

            return null;
        }

        $perSessionAmount = $fixedAmount / $totalSessions;

        // Final sanity check
        if ($perSessionAmount <= 0) {
            report(new \InvalidArgumentException(
                "Calculated per-session amount is invalid for course {$course->id}: {$perSessionAmount}"
            ));
            Log::error('Invalid calculated per-session amount', [
                'course_id' => $course->id,
                'fixed_amount' => $fixedAmount,
                'total_sessions' => $totalSessions,
                'per_session_amount' => $perSessionAmount,
            ]);

            return null;
        }

        return (float) $perSessionAmount;
    }

    /**
     * Calculate per-student payment (amount × enrolled students)
     */
    protected function calculatePerStudent($course): ?float
    {
        $amountPerStudent = $course->amount_per_student;
        $enrolledCount = $course->enrollments()->count();

        // Validate amount per student
        if ($amountPerStudent === null || $amountPerStudent <= 0) {
            report(new \InvalidArgumentException(
                "Invalid amount_per_student for course {$course->id}: ".
                var_export($amountPerStudent, true)
            ));
            Log::error('Invalid amount per student', [
                'course_id' => $course->id,
                'amount_per_student' => $amountPerStudent,
            ]);

            return null;
        }

        // Validate enrollment count (warn if zero, but still calculate)
        if ($enrolledCount === 0) {
            Log::warning('No enrolled students for per-student payment', [
                'course_id' => $course->id,
                'amount_per_student' => $amountPerStudent,
            ]);

            // Return 0 for zero students (this is legitimate)
            return 0.0;
        }

        $totalAmount = $amountPerStudent * $enrolledCount;

        // Final sanity check
        if ($totalAmount <= 0) {
            report(new \InvalidArgumentException(
                "Calculated per-student total is invalid for course {$course->id}: {$totalAmount}"
            ));
            Log::error('Invalid calculated per-student total', [
                'course_id' => $course->id,
                'amount_per_student' => $amountPerStudent,
                'enrolled_count' => $enrolledCount,
                'total_amount' => $totalAmount,
            ]);

            return null;
        }

        return (float) $totalAmount;
    }

    /**
     * Calculate per-session payment
     */
    protected function calculatePerSession($course): ?float
    {
        $amount = $course->amount_per_session;

        // Validate amount per session
        if ($amount === null || $amount <= 0) {
            report(new \InvalidArgumentException(
                "Invalid amount_per_session for course {$course->id}: ".
                var_export($amount, true)
            ));
            Log::error('Invalid amount per session', [
                'course_id' => $course->id,
                'amount_per_session' => $amount,
            ]);

            return null;
        }

        return (float) $amount;
    }

    /**
     * Get teacher ID from session
     */
    public function getTeacherId(BaseSession $session): ?int
    {
        if ($session instanceof QuranSession) {
            return $session->quranTeacher?->id;
        }

        if ($session instanceof AcademicSession) {
            return $session->academicTeacher?->id;
        }

        if ($session instanceof InteractiveCourseSession) {
            return $session->course?->assignedTeacher?->id;
        }

        return null;
    }

    /**
     * Get teacher data (type and ID) from session
     */
    public function getTeacherData(BaseSession $session): ?array
    {
        if ($session instanceof QuranSession) {
            $teacherId = $session->quranTeacher?->quranTeacherProfile?->id;

            if (! $teacherId) {
                Log::error('Quran teacher ID missing for session', [
                    'session_id' => $session->id,
                    'has_teacher_relation' => $session->quranTeacher !== null,
                ]);

                return null;
            }

            return [
                'type' => 'quran_teacher',
                'id' => $teacherId,
            ];
        }

        if ($session instanceof AcademicSession) {
            $teacherId = $session->academicTeacher?->id;

            if (! $teacherId) {
                Log::error('Academic teacher ID missing for session', [
                    'session_id' => $session->id,
                    'has_teacher_relation' => $session->academicTeacher !== null,
                ]);

                return null;
            }

            return [
                'type' => 'academic_teacher',
                'id' => $teacherId,
            ];
        }

        if ($session instanceof InteractiveCourseSession) {
            $teacherId = $session->course?->assignedTeacher?->id;

            if (! $teacherId) {
                Log::error('Course teacher ID missing for session', [
                    'session_id' => $session->id,
                    'has_course' => $session->course !== null,
                    'has_assigned_teacher' => $session->course?->assignedTeacher !== null,
                ]);

                return null;
            }

            return [
                'type' => 'academic_teacher',
                'id' => $teacherId,
            ];
        }

        return null;
    }

    /**
     * Clear teacher profile cache.
     */
    public function clearTeacherCache(string $type, int $id): void
    {
        if ($type === 'quran') {
            Cache::forget("teacher:quran_profile:{$id}");
        } elseif ($type === 'academic') {
            Cache::forget("teacher:academic_profile:{$id}");
        }
    }
}
