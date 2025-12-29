<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\TeacherEarning;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Earnings Report Service
 *
 * Handles report generation, metadata creation, and earning record persistence.
 * Provides audit trail and transparency for earnings calculations.
 */
class EarningsReportService
{
    public function __construct(
        protected EarningsCalculatorService $calculator
    ) {}

    /**
     * Calculate and create earning record for a completed session
     */
    public function calculateSessionEarnings(BaseSession $session): ?TeacherEarning
    {
        try {
            // Check if session is eligible for earnings
            if (! $this->calculator->isEligibleForEarnings($session)) {
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
            $amount = $this->calculator->calculateForSession($session);

            if ($amount === null || $amount <= 0) {
                Log::warning('Invalid earning amount calculated', [
                    'session_id' => $session->id,
                    'amount' => $amount,
                ]);

                return null;
            }

            // Get teacher details
            $teacherData = $this->calculator->getTeacherData($session);

            if (! $teacherData) {
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
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error calculating session earnings', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            report($e);

            return null;
        } catch (\InvalidArgumentException $e) {
            Log::error('Invalid data for earnings calculation', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Throwable $e) {
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
