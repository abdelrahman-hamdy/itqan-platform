<?php

namespace App\Services;

use App\Contracts\EarningsCalculationServiceInterface;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\TeacherEarning;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

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
    /** Cached result from the last getSessionPriceForDuration() call, used by getRateSnapshot/getCalculationMetadata */
    protected ?array $lastRateResult = null;

    /**
     * {@inheritdoc}
     */
    public function calculateSessionEarnings(BaseSession $session): ?TeacherEarning
    {
        try {
            if (! $this->isEligibleForEarnings($session)) {
                Log::info('Session not eligible for earnings', [
                    'session_id' => $session->id,
                    'session_type' => $session->getMorphClass(),
                    'status' => $session->status,
                ]);

                return null;
            }

            if ($this->isAlreadyCalculated($session)) {
                Log::info('Session earnings already calculated', [
                    'session_id' => $session->id,
                ]);

                return $this->findExistingEarning($session);
            }

            // Bust teacher profile cache to ensure we use current rates, not stale cached rates
            if ($session instanceof QuranSession && $session->quranTeacher) {
                $this->clearTeacherCache('quran', $session->quranTeacher->id);
            } elseif ($session instanceof AcademicSession) {
                $this->clearTeacherCache('academic', $session->academic_teacher_id);
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
                $existing = $this->findExistingEarning($session, forUpdate: true);
                if ($existing) {
                    return $existing;
                }

                $earning = TeacherEarning::create([
                    'academy_id' => $session->academy_id ?? $this->getAcademyId($session),
                    'teacher_type' => $teacherData['type'],
                    'teacher_id' => $teacherData['id'],
                    'session_type' => $session->getMorphClass(),
                    'session_id' => $session->id,
                    'amount' => $amount,
                    'calculation_method' => $this->getCalculationMethod($session),
                    'rate_snapshot' => $this->getRateSnapshot($session),
                    'calculation_metadata' => $this->getCalculationMetadata($session, $amount),
                    'earning_month' => $this->getEarningMonth($session),
                    'session_completed_at' => $session->ended_at ?? $session->scheduled_at,
                    'calculated_at' => now(),
                    'is_finalized' => true,
                    'is_disputed' => false,
                ]);

                Log::info('Earnings calculated successfully', [
                    'earning_id' => $earning->id,
                    'session_id' => $session->id,
                    'amount' => $amount,
                ]);

                return $earning;
            });
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent retry won the race and inserted the earning row
            // first. Return the row that won instead of erroring.
            $existing = $this->findExistingEarning($session);
            if ($existing) {
                Log::info('Earnings already recorded by concurrent run', [
                    'session_id' => $session->id,
                    'earning_id' => $existing->id,
                ]);

                return $existing;
            }

            Log::error('Earnings unique-constraint violation but no row found', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            report($e);

            return null;
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

        // Use the counting flag if it has been set (auto or admin override)
        if ($session->counts_for_teacher !== null) {
            return (bool) $session->counts_for_teacher;
        }

        // Fallback to legacy attendance check
        if (! $this->didTeacherAttend($session)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the teacher earned this session. Only the ATTENDED tier earns;
     * partial and absent tiers get nothing.
     */
    protected function didTeacherAttend(BaseSession $session): bool
    {
        // The matrix pass in CalculateSessionForAttendance writes the final
        // decision to `counts_for_teacher`. If it's set, trust it — that's
        // what subscription counting and every other reader uses.
        if ($session->counts_for_teacher !== null) {
            return (bool) $session->counts_for_teacher;
        }

        // Fallback: the matrix job hasn't run yet (rare). Compute the same
        // decision from the teacher's MeetingAttendance row.
        $teacherId = $this->getTeacherId($session);
        if (! $teacherId) {
            return false;
        }

        $attendance = MeetingAttendance::where('session_id', $session->id)
            ->where('user_id', $teacherId)
            ->whereIn('user_type', \App\Models\MeetingAttendance::TEACHER_USER_TYPES)
            ->where('is_calculated', true)
            ->first();

        if (! $attendance) {
            // Offline/manual session with no LiveKit record — trust the
            // session's reported actual duration as proof of presence.
            return ($session->actual_duration_minutes ?? 0) > 0;
        }

        $fullPercent = app(\App\Services\SessionSettingsService::class)
            ->getTeacherFullAttendancePercent($session);

        return ($attendance->attendance_percentage ?? 0) >= $fullPercent;
    }

    /**
     * Calculate earnings for Quran sessions (duration-aware pricing)
     */
    protected function calculateQuranSessionEarnings(QuranSession $session): ?float
    {
        $cacheKey = "teacher:quran_profile:{$session->quranTeacher?->id}";

        $teacher = Cache::remember($cacheKey, now()->addHours(1), function () use ($session) {
            return $session->quranTeacher?->quranTeacherProfile?->load('academy');
        });

        if (! $teacher) {
            Log::error('Quran teacher profile not found', ['session_id' => $session->id]);

            return null;
        }

        $durationMinutes = $session->duration_minutes ?? 60;
        $type = $session->session_type === 'individual' ? 'individual' : 'group';

        if ($session->session_type !== 'individual' && ! in_array($session->session_type, ['group', 'circle'])) {
            return null;
        }

        $result = $teacher->getSessionPriceForDuration($durationMinutes, $type);
        $amount = $result['amount'];

        $this->lastRateResult = $result;

        if ($amount === null || $amount <= 0) {
            report(new InvalidArgumentException(
                "No valid {$type} session price for Quran teacher {$teacher->id} ".
                "at {$durationMinutes}min (source: {$result['source']}, session: {$session->id})"
            ));
            Log::error('Invalid Quran session price for duration', [
                'session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'duration_minutes' => $durationMinutes,
                'type' => $type,
                'rate_source' => $result['source'],
            ]);

            return null;
        }

        return $amount;
    }

    /**
     * Calculate earnings for Academic sessions (duration-aware pricing)
     */
    protected function calculateAcademicSessionEarnings(AcademicSession $session): ?float
    {
        $cacheKey = "teacher:academic_profile:{$session->academic_teacher_id}";

        $teacher = Cache::remember($cacheKey, now()->addHours(1), function () use ($session) {
            return $session->academicTeacher?->load('academy');
        });

        if (! $teacher) {
            Log::error('Academic teacher profile not found', ['session_id' => $session->id]);

            return null;
        }

        $durationMinutes = $session->duration_minutes ?? 60;
        $result = $teacher->getSessionPriceForDuration($durationMinutes);
        $amount = $result['amount'];

        $this->lastRateResult = $result;

        if ($amount === null || $amount <= 0) {
            report(new InvalidArgumentException(
                "No valid session price for Academic teacher {$teacher->id} ".
                "at {$durationMinutes}min (source: {$result['source']}, session: {$session->id})"
            ));
            Log::error('Invalid Academic session price for duration', [
                'session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'duration_minutes' => $durationMinutes,
                'rate_source' => $result['source'],
            ]);

            return null;
        }

        return $amount;
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

        $sessionEndedAt = $session->ended_at ?? $session->scheduled_at;

        return match ($course->payment_type) {
            'fixed_amount' => $this->calculateFixedAmount($course),
            'per_student' => $this->calculatePerStudent($course, $sessionEndedAt),
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
     * Calculate per-student payment (amount x enrolled students at session end time)
     */
    protected function calculatePerStudent($course, $sessionEndedAt = null): ?float
    {
        $amountPerStudent = $course->amount_per_student;
        // Scope enrollment count to students who enrolled at or before the session ended
        // to avoid counting students who enrolled after the session
        $enrolledCount = $course->enrollments()
            ->when(
                $sessionEndedAt,
                fn ($q) => $q->where('created_at', '<=', $sessionEndedAt)
            )
            ->count();

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
     * Get teacher User.id from session (used for attendance lookups against users table)
     */
    protected function getTeacherId(BaseSession $session): ?int
    {
        if ($session instanceof QuranSession) {
            return $session->quranTeacher?->id;
        }

        if ($session instanceof AcademicSession) {
            // academicTeacher is an AcademicTeacherProfile; return user_id (User.id) not profile id
            return $session->academicTeacher?->user_id;
        }

        if ($session instanceof InteractiveCourseSession) {
            // assignedTeacher is an AcademicTeacherProfile; return user_id (User.id)
            return $session->course?->assignedTeacher?->user_id;
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
        return TeacherEarning::withoutGlobalScopes()
            ->where('session_type', $session->getMorphClass())
            ->where('session_id', $session->id)
            ->where('academy_id', $session->academy_id ?? $this->getAcademyId($session))
            ->exists();
    }

    /**
     * Look up the earning row for a session in a way that's robust to queue
     * workers without an academy context. Bypasses ScopedToAcademy and filters
     * by academy_id explicitly so the unique-constraint catch path can always
     * recover the row that won the race.
     */
    protected function findExistingEarning(BaseSession $session, bool $forUpdate = false): ?TeacherEarning
    {
        $query = TeacherEarning::withoutGlobalScopes()
            ->where('session_type', $session->getMorphClass())
            ->where('session_id', $session->id)
            ->where('academy_id', $session->academy_id ?? $this->getAcademyId($session));

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
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
                'session_type' => $session->getMorphClass(),
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
     * Get rate snapshot for audit trail (returns array for duration-based rates)
     */
    protected function getRateSnapshot(BaseSession $session): mixed
    {
        if (($session instanceof QuranSession || $session instanceof AcademicSession) && $this->lastRateResult) {
            $durationMinutes = $session->duration_minutes ?? 60;

            return [
                'amount' => $this->lastRateResult['amount'] ?? null,
                'source' => $this->lastRateResult['source'] ?? 'none',
                'duration_minutes' => $durationMinutes,
                'type' => $session instanceof QuranSession
                    ? ($session->session_type === 'individual' ? 'individual' : 'group')
                    : 'individual',
            ];
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

        if ($session instanceof QuranSession || $session instanceof AcademicSession) {
            $metadata['duration_minutes'] = $session->duration_minutes ?? 60;
            $metadata['rate_source'] = $this->lastRateResult['source'] ?? 'none';
            if ($session instanceof QuranSession) {
                $metadata['session_type_detail'] = $session->session_type;
            }
        }

        if ($session instanceof InteractiveCourseSession) {
            $course = $session->course;
            $metadata['payment_type'] = $course->payment_type;
            // Use already-loaded enrollment count from the course relationship to avoid N+1
            // Count only enrollments that existed at session end time (historical scope)
            $sessionEndedAt = $session->ended_at ?? $session->scheduled_at;
            $metadata['enrolled_students'] = $course->enrollments()
                ->when(
                    $sessionEndedAt,
                    fn ($q) => $q->where('created_at', '<=', $sessionEndedAt)
                )
                ->count();
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
