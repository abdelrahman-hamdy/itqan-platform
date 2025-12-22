<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\TeacherEarning;
use App\Enums\SessionStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EarningsCalculationService
{
    /**
     * Calculate earnings for a completed session
     *
     * @param BaseSession $session
     * @return TeacherEarning|null
     */
    public function calculateSessionEarnings(BaseSession $session): ?TeacherEarning
    {
        // Check if session is eligible for earnings
        if (!$this->isEligibleForEarnings($session)) {
            Log::info('Session not eligible for earnings', [
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'status' => $session->status,
            ]);
            return null;
        }

        // Check if already calculated (idempotency)
        if ($this->isAlreadyCalculated($session)) {
            Log::info('Session earnings already calculated', [
                'session_id' => $session->id,
            ]);
            return TeacherEarning::forSession(get_class($session), $session->id)->first();
        }

        // Calculate the earning amount
        $amount = $this->calculateForSession($session);

        if ($amount === null || $amount <= 0) {
            Log::warning('Invalid earning amount calculated', [
                'session_id' => $session->id,
                'amount' => $amount,
            ]);
            return null;
        }

        // Get teacher details
        $teacherData = $this->getTeacherData($session);

        if (!$teacherData) {
            Log::error('Could not get teacher data for session', [
                'session_id' => $session->id,
            ]);
            return null;
        }

        // Create earning record
        return DB::transaction(function () use ($session, $amount, $teacherData) {
            $earning = TeacherEarning::create([
                'academy_id' => $session->academy_id ?? $this->getAcademyId($session),
                'teacher_type' => $teacherData['type'],
                'teacher_id' => $teacherData['id'],
                'session_type' => get_class($session),
                'session_id' => $session->id,
                'amount' => $amount,
                'calculation_method' => $this->getCalculationMethod($session),
                'rate_snapshot' => $this->getRateSnapshot($session),
                'calculation_metadata' => $this->getCalculationMetadata($session, $amount),
                'earning_month' => $this->getEarningMonth($session),
                'session_completed_at' => $session->ended_at ?? $session->scheduled_at,
                'calculated_at' => now(),
                'is_finalized' => false,
                'is_disputed' => false,
            ]);

            Log::info('Earnings calculated successfully', [
                'earning_id' => $earning->id,
                'session_id' => $session->id,
                'amount' => $amount,
            ]);

            return $earning;
        });
    }

    /**
     * Check if session is eligible for earnings
     */
    private function isEligibleForEarnings(BaseSession $session): bool
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
        if (!$this->didTeacherAttend($session)) {
            return false;
        }

        return true;
    }

    /**
     * Check if teacher attended the session (at least 50%)
     */
    private function didTeacherAttend(BaseSession $session): bool
    {
        $teacherId = $this->getTeacherId($session);

        if (!$teacherId) {
            return false;
        }

        $attendance = MeetingAttendance::where('session_id', $session->id)
            ->where('user_id', $teacherId)
            ->where('user_type', 'teacher')
            ->where('is_calculated', true)
            ->first();

        if (!$attendance) {
            // If no attendance record, assume teacher attended for backwards compatibility
            // This handles sessions before attendance tracking was implemented
            return true;
        }

        // Teacher must have attended at least 50% of the session
        return ($attendance->attendance_percentage ?? 0) >= 50;
    }

    /**
     * Check if earning already calculated for this session
     */
    private function isAlreadyCalculated(BaseSession $session): bool
    {
        return TeacherEarning::forSession(get_class($session), $session->id)->exists();
    }

    /**
     * Calculate earnings based on session type (polymorphic dispatch)
     */
    private function calculateForSession(BaseSession $session): ?float
    {
        return match(true) {
            $session instanceof QuranSession => $this->calculateQuranSessionEarnings($session),
            $session instanceof AcademicSession => $this->calculateAcademicSessionEarnings($session),
            $session instanceof InteractiveCourseSession => $this->calculateInteractiveSessionEarnings($session),
            default => null,
        };
    }

    /**
     * Calculate earnings for Quran sessions
     */
    private function calculateQuranSessionEarnings(QuranSession $session): ?float
    {
        $teacher = $session->quranTeacher?->quranTeacherProfile;

        if (!$teacher) {
            Log::error('Quran teacher profile not found', ['session_id' => $session->id]);
            return null;
        }

        // Individual sessions
        if ($session->session_type === 'individual') {
            return (float) $teacher->session_price_individual;
        }

        // Group/circle sessions: teacher gets session_price_group per session
        // NOT multiplied by student count (per business rules)
        if (in_array($session->session_type, ['group', 'circle'])) {
            return (float) $teacher->session_price_group;
        }

        return null;
    }

    /**
     * Calculate earnings for Academic sessions
     */
    private function calculateAcademicSessionEarnings(AcademicSession $session): ?float
    {
        $teacher = $session->academicTeacher;

        if (!$teacher) {
            Log::error('Academic teacher profile not found', ['session_id' => $session->id]);
            return null;
        }

        // Academic sessions are always individual
        return (float) $teacher->session_price_individual;
    }

    /**
     * Calculate earnings for Interactive Course sessions
     */
    private function calculateInteractiveSessionEarnings(InteractiveCourseSession $session): ?float
    {
        $course = $session->course;

        if (!$course) {
            Log::error('Interactive course not found', ['session_id' => $session->id]);
            return null;
        }

        return match($course->payment_type) {
            'fixed_amount' => $this->calculateFixedAmount($course),
            'per_student' => $this->calculatePerStudent($course),
            'per_session' => $this->calculatePerSession($course),
            default => null,
        };
    }

    /**
     * Calculate fixed amount payment (total amount / total sessions)
     */
    private function calculateFixedAmount($course): float
    {
        $totalSessions = $course->total_sessions ?: 1;
        return (float) ($course->teacher_fixed_amount / $totalSessions);
    }

    /**
     * Calculate per-student payment (amount × enrolled students)
     */
    private function calculatePerStudent($course): float
    {
        $enrolledCount = $course->enrollments()->count();
        return (float) ($course->amount_per_student * $enrolledCount);
    }

    /**
     * Calculate per-session payment
     */
    private function calculatePerSession($course): float
    {
        return (float) $course->amount_per_session;
    }

    /**
     * Get teacher data (type and ID) from session
     */
    private function getTeacherData(BaseSession $session): ?array
    {
        if ($session instanceof QuranSession) {
            return [
                'type' => 'quran_teacher',
                'id' => $session->quranTeacher?->quranTeacherProfile?->id,
            ];
        }

        if ($session instanceof AcademicSession) {
            return [
                'type' => 'academic_teacher',
                'id' => $session->academicTeacher?->id,
            ];
        }

        if ($session instanceof InteractiveCourseSession) {
            return [
                'type' => 'academic_teacher',
                'id' => $session->course?->assignedTeacher?->id,
            ];
        }

        return null;
    }

    /**
     * Get teacher ID from session
     */
    private function getTeacherId(BaseSession $session): ?int
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
     * Get academy ID (handles InteractiveCourseSession special case)
     */
    private function getAcademyId(BaseSession $session): ?int
    {
        if ($session instanceof InteractiveCourseSession) {
            return $session->course?->academy_id;
        }

        return $session->academy_id;
    }

    /**
     * Get calculation method string
     */
    private function getCalculationMethod(BaseSession $session): string
    {
        if ($session instanceof QuranSession) {
            return $session->session_type === 'individual' ? 'individual_rate' : 'group_rate';
        }

        if ($session instanceof AcademicSession) {
            return 'individual_rate';
        }

        if ($session instanceof InteractiveCourseSession) {
            $course = $session->course;
            return match($course?->payment_type) {
                'fixed_amount' => 'fixed_amount',
                'per_student' => 'per_student',
                'per_session' => 'per_session',
                default => 'unknown',
            };
        }

        return 'unknown';
    }

    /**
     * Get rate snapshot for audit trail
     */
    private function getRateSnapshot(BaseSession $session): ?float
    {
        if ($session instanceof QuranSession) {
            $teacher = $session->quranTeacher?->quranTeacherProfile;
            return $session->session_type === 'individual'
                ? $teacher?->session_price_individual
                : $teacher?->session_price_group;
        }

        if ($session instanceof AcademicSession) {
            return $session->academicTeacher?->session_price_individual;
        }

        if ($session instanceof InteractiveCourseSession) {
            $course = $session->course;
            return match($course?->payment_type) {
                'fixed_amount' => $course->teacher_fixed_amount,
                'per_student' => $course->amount_per_student,
                'per_session' => $course->amount_per_session,
                default => null,
            };
        }

        return null;
    }

    /**
     * Get calculation metadata for transparency
     */
    private function getCalculationMetadata(BaseSession $session, float $amount): array
    {
        $metadata = [
            'calculated_at' => now()->toISOString(),
            'calculation_version' => '1.0',
            'session_code' => $session->session_code ?? null,
            'amount' => $amount,
        ];

        if ($session instanceof QuranSession) {
            $metadata['session_type_detail'] = $session->session_type;
            $metadata['teacher_rate'] = $this->getRateSnapshot($session);
        }

        if ($session instanceof InteractiveCourseSession) {
            $course = $session->course;
            $metadata['payment_type'] = $course->payment_type;
            $metadata['enrolled_students'] = $course->enrollments()->count();
            $metadata['total_sessions'] = $course->total_sessions;
        }

        return $metadata;
    }

    /**
     * Get earning month (first day of the month)
     */
    private function getEarningMonth(BaseSession $session): string
    {
        $completionDate = $session->ended_at ?? $session->completed_at ?? $session->scheduled_at ?? now();
        return Carbon::parse($completionDate)->firstOfMonth()->format('Y-m-d');
    }
}
