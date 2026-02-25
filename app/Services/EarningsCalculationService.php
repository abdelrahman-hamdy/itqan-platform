<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Throwable;
use App\Contracts\EarningsCalculationServiceInterface;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\TeacherEarning;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Unified Earnings Calculation Service
 *
 * Handles the full earnings lifecycle:
 * - Eligibility checks and teacher attendance validation
 * - Polymorphic calculation based on session type and payment models
 * - Earning record persistence with audit trail
 * - Teacher profile caching
 */
class EarningsCalculationService implements EarningsCalculationServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function calculateSessionEarnings(BaseSession $session): ?TeacherEarning
    {
        try {
            if (! $this->isEligibleForEarnings($session)) {
                Log::info('Session not eligible for earnings', [
                    'session_id' => $session->id,
                    'session_type' => get_class($session),
                    'status' => $session->status,
                ]);

                return null;
            }

            if ($this->isAlreadyCalculated($session)) {
                Log::info('Session earnings already calculated', [
                    'session_id' => $session->id,
                ]);

                return TeacherEarning::forSession(get_class($session), $session->id)->first();
            }

            $amount = $this->calculateForSession($session);

            if ($amount === null || $amount <= 0) {
                Log::warning('Invalid earning amount calculated', [
                    'session_id' => $session->id,
                    'amount' => $amount,
                ]);

                return null;
            }

            $teacherData = $this->getTeacherData($session);

            if (! $teacherData) {
                Log::error('Could not get teacher data for session', [
                    'session_id' => $session->id,
                ]);

                return null;
            }

            return DB::transaction(function () use ($session, $amount, $teacherData) {
                // Re-check inside the transaction with a lock to prevent race conditions.
                // Two concurrent calls may both pass the early isAlreadyCalculated() check;
                // this lockForUpdate() ensures only one proceeds to create the record.
                $existing = TeacherEarning::forSession(get_class($session), $session->id)
                    ->lockForUpdate()
                    ->first();
                if ($existing) {
                    return $existing;
                }

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
        } catch (QueryException $e) {
            Log::error('Database error calculating session earnings', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            report($e);

            return null;
        } catch (InvalidArgumentException $e) {
            Log::error('Invalid data for earnings calculation', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (Throwable $e) {
            Log::critical('Unexpected error calculating session earnings', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);

            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearTeacherCache(string $type, int $id): void
    {
        if ($type === 'quran') {
            Cache::forget("teacher:quran_profile:{$id}");
        } elseif ($type === 'academic') {
            Cache::forget("teacher:academic_profile:{$id}");
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  Calculation Logic
    // ──────────────────────────────────────────────────────────────

    /**
     * Calculate earnings based on session type (polymorphic dispatch)
     */
    protected function calculateForSession(BaseSession $session): ?float
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
    protected function isEligibleForEarnings(BaseSession $session): bool
    {
        if ($session->status !== SessionStatus::COMPLETED) {
            return false;
        }

        if ($session instanceof QuranSession && $session->session_type === 'trial') {
            return false;
        }

        if (! $this->didTeacherAttend($session)) {
            return false;
        }

        return true;
    }

    /**
     * Check if teacher attended the session (at least 50%)
     */
    protected function didTeacherAttend(BaseSession $session): bool
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

        if ($session->session_type === 'individual') {
            $amount = $teacher->session_price_individual;

            if ($amount === null || $amount <= 0) {
                report(new InvalidArgumentException(
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

        if (in_array($session->session_type, ['group', 'circle'])) {
            $amount = $teacher->session_price_group;

            if ($amount === null || $amount <= 0) {
                report(new InvalidArgumentException(
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

        $amount = $teacher->session_price_individual;

        if ($amount === null || $amount <= 0) {
            report(new InvalidArgumentException(
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

        if ($fixedAmount === null || $fixedAmount <= 0) {
            report(new InvalidArgumentException(
                "Invalid teacher_fixed_amount for course {$course->id}: ".
                var_export($fixedAmount, true)
            ));
            Log::error('Invalid teacher fixed amount', [
                'course_id' => $course->id,
                'teacher_fixed_amount' => $fixedAmount,
            ]);

            return null;
        }

        if ($totalSessions === null || $totalSessions <= 0) {
            report(new InvalidArgumentException(
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

        if ($perSessionAmount <= 0) {
            report(new InvalidArgumentException(
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
     * Calculate per-student payment (amount x enrolled students)
     */
    protected function calculatePerStudent($course): ?float
    {
        $amountPerStudent = $course->amount_per_student;
        $enrolledCount = $course->enrollments()->count();

        if ($amountPerStudent === null || $amountPerStudent <= 0) {
            report(new InvalidArgumentException(
                "Invalid amount_per_student for course {$course->id}: ".
                var_export($amountPerStudent, true)
            ));
            Log::error('Invalid amount per student', [
                'course_id' => $course->id,
                'amount_per_student' => $amountPerStudent,
            ]);

            return null;
        }

        if ($enrolledCount === 0) {
            Log::warning('No enrolled students for per-student payment', [
                'course_id' => $course->id,
                'amount_per_student' => $amountPerStudent,
            ]);

            return 0.0;
        }

        $totalAmount = $amountPerStudent * $enrolledCount;

        if ($totalAmount <= 0) {
            report(new InvalidArgumentException(
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

        if ($amount === null || $amount <= 0) {
            report(new InvalidArgumentException(
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

    // ──────────────────────────────────────────────────────────────
    //  Teacher Data Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Get teacher ID from session
     */
    protected function getTeacherId(BaseSession $session): ?int
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
    protected function getTeacherData(BaseSession $session): ?array
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

    // ──────────────────────────────────────────────────────────────
    //  Persistence Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Check if earning already calculated for this session
     */
    protected function isAlreadyCalculated(BaseSession $session): bool
    {
        return TeacherEarning::forSession(get_class($session), $session->id)->exists();
    }

    /**
     * Get academy ID (handles InteractiveCourseSession special case)
     */
    protected function getAcademyId(BaseSession $session): ?int
    {
        if ($session instanceof InteractiveCourseSession) {
            $academyId = $session->course?->academy_id;

            if (! $academyId) {
                Log::error('Academy ID missing for interactive course session', [
                    'session_id' => $session->id,
                    'has_course' => $session->course !== null,
                ]);
            }

            return $academyId;
        }

        $academyId = $session->academy_id;

        if (! $academyId) {
            Log::error('Academy ID missing for session', [
                'session_id' => $session->id,
                'session_type' => get_class($session),
            ]);
        }

        return $academyId;
    }

    /**
     * Get calculation method string
     */
    protected function getCalculationMethod(BaseSession $session): string
    {
        if ($session instanceof QuranSession) {
            return $session->session_type === 'individual' ? 'individual_rate' : 'group_rate';
        }

        if ($session instanceof AcademicSession) {
            return 'individual_rate';
        }

        if ($session instanceof InteractiveCourseSession) {
            $course = $session->course;

            return match ($course?->payment_type) {
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
    protected function getRateSnapshot(BaseSession $session): ?float
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

            return match ($course?->payment_type) {
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
    protected function getCalculationMetadata(BaseSession $session, float $amount): array
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
    protected function getEarningMonth(BaseSession $session): string
    {
        $completionDate = $session->ended_at ?? $session->completed_at ?? $session->scheduled_at ?? now();

        return Carbon::parse($completionDate)->firstOfMonth()->format('Y-m-d');
    }
}
