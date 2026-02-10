<?php

namespace App\Services;

use App\Enums\PayoutStatus;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Generate monthly payout for a specific teacher
     *
     * @param  string  $teacherType  'quran_teacher' | 'academic_teacher'
     */
    public function generateMonthlyPayout(
        string $teacherType,
        int $teacherId,
        int $year,
        int $month,
        int $academyId
    ): ?TeacherPayout {
        $monthDate = sprintf('%04d-%02d-01', $year, $month);

        // Check if payout already exists for this month
        $existingPayout = TeacherPayout::forTeacher($teacherType, $teacherId)
            ->forMonth($year, $month)
            ->where('academy_id', $academyId)
            ->first();

        if ($existingPayout) {
            Log::info('Payout already exists', [
                'payout_id' => $existingPayout->id,
                'teacher_type' => $teacherType,
                'teacher_id' => $teacherId,
                'month' => $monthDate,
            ]);

            return $existingPayout;
        }

        // Get unpaid, unfinalized earnings for this month
        $earnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->forMonth($year, $month)
            ->unpaid()
            ->where('is_disputed', false)
            ->get();

        if ($earnings->isEmpty()) {
            Log::info('No unpaid earnings found for payout generation', [
                'teacher_type' => $teacherType,
                'teacher_id' => $teacherId,
                'month' => $monthDate,
            ]);

            return null;
        }

        // Calculate breakdown by calculation method
        $breakdown = $this->calculateBreakdown($earnings);

        // Create payout in a transaction
        return DB::transaction(function () use (
            $teacherType,
            $teacherId,
            $academyId,
            $monthDate,
            $earnings,
            $breakdown
        ) {
            // Create payout record
            $payout = TeacherPayout::create([
                'academy_id' => $academyId,
                'teacher_type' => $teacherType,
                'teacher_id' => $teacherId,
                'payout_month' => $monthDate,
                'total_amount' => $earnings->sum('amount'),
                'sessions_count' => $earnings->count(),
                'breakdown' => $breakdown,
                'status' => PayoutStatus::PENDING->value,
            ]);

            // Link earnings to payout and finalize them with row-level locking
            // Prevents race conditions where same earning could be included in multiple payouts
            $earningIds = $earnings->pluck('id')->toArray();

            // Lock and update in a single query to maintain lock during update
            // The previous approach released the lock immediately after get()
            TeacherEarning::whereIn('id', $earningIds)
                ->lockForUpdate()
                ->update([
                    'payout_id' => $payout->id,
                    'is_finalized' => true,
                ]);

            Log::info('Monthly payout generated successfully', [
                'payout_id' => $payout->id,
                'teacher_type' => $teacherType,
                'teacher_id' => $teacherId,
                'total_amount' => $payout->total_amount,
                'sessions_count' => $payout->sessions_count,
            ]);

            return $payout;
        });
    }

    /**
     * Generate payouts for all teachers in an academy for a specific month
     */
    public function generatePayoutsForMonth(int $academyId, int $year, int $month): Collection
    {
        $monthDate = sprintf('%04d-%02d-01', $year, $month);

        $payouts = collect();

        // Process teachers in chunks to prevent memory issues
        TeacherEarning::where('academy_id', $academyId)
            ->forMonth($year, $month)
            ->unpaid()
            ->where('is_disputed', false)
            ->select('teacher_type', 'teacher_id')
            ->distinct()
            ->chunkById(50, function ($teachersWithEarnings) use ($year, $month, $academyId, &$payouts) {
                foreach ($teachersWithEarnings as $teacher) {
                    $payout = $this->generateMonthlyPayout(
                        $teacher->teacher_type,
                        $teacher->teacher_id,
                        $year,
                        $month,
                        $academyId
                    );

                    if ($payout) {
                        $payouts->push($payout);
                    }
                }
            });

        Log::info('Bulk payout generation completed', [
            'academy_id' => $academyId,
            'month' => $monthDate,
            'payouts_generated' => $payouts->count(),
        ]);

        return $payouts;
    }

    /**
     * Approve a payout
     *
     * @throws \Exception
     */
    public function approvePayout(TeacherPayout $payout, User $approvedBy, ?string $notes = null): bool
    {
        if (! $payout->canApprove()) {
            throw new \InvalidArgumentException('Payout cannot be approved in current status: '.$payout->status->value);
        }

        // Run validation
        $validationErrors = $this->validateForApproval($payout);
        if (! empty($validationErrors)) {
            throw new \DomainException('Validation failed: '.implode(', ', $validationErrors));
        }

        return DB::transaction(function () use ($payout, $approvedBy, $notes) {
            $payout->update([
                'status' => PayoutStatus::APPROVED->value,
                'approved_by' => $approvedBy->id,
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            Log::info('Payout approved', [
                'payout_id' => $payout->id,
                'payout_code' => $payout->payout_code,
                'amount' => $payout->total_amount,
                'approved_by' => $approvedBy->id,
            ]);

            // Send notification to teacher
            $this->sendPayoutNotification($payout, 'approved');

            return true;
        });
    }

    /**
     * Reject a payout
     *
     * @throws \Exception
     */
    public function rejectPayout(TeacherPayout $payout, User $rejectedBy, string $reason): bool
    {
        if (! $payout->canReject()) {
            throw new \InvalidArgumentException('Payout cannot be rejected in current status: '.$payout->status->value);
        }

        return DB::transaction(function () use ($payout, $rejectedBy, $reason) {
            // Unlink earnings from payout and unfinalize them
            $payout->earnings()->update([
                'payout_id' => null,
                'is_finalized' => false,
            ]);

            $payout->update([
                'status' => PayoutStatus::REJECTED->value,
                'rejected_by' => $rejectedBy->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            Log::info('Payout rejected', [
                'payout_id' => $payout->id,
                'payout_code' => $payout->payout_code,
                'rejected_by' => $rejectedBy->id,
                'reason' => $reason,
            ]);

            // Send notification to teacher
            $this->sendPayoutNotification($payout, 'rejected', $reason);

            return true;
        });
    }

    /**
     * Validate payout before approval
     *
     * @return array Array of validation error messages
     */
    public function validateForApproval(TeacherPayout $payout): array
    {
        $errors = [];

        // Check all earnings are calculated
        $uncalculatedCount = $payout->earnings()
            ->whereNull('calculated_at')
            ->count();

        if ($uncalculatedCount > 0) {
            $errors[] = "Has {$uncalculatedCount} uncalculated earnings";
        }

        // Check no disputed earnings
        $disputedCount = $payout->earnings()
            ->where('is_disputed', true)
            ->count();

        if ($disputedCount > 0) {
            $errors[] = "Has {$disputedCount} disputed earnings";
        }

        // Verify total amount matches sum of earnings
        $calculatedTotal = $payout->earnings()->sum('amount');
        if (abs($calculatedTotal - $payout->total_amount) > 0.01) {
            $errors[] = "Total mismatch: expected {$payout->total_amount}, got {$calculatedTotal}";
        }

        // Check sessions count matches
        $actualCount = $payout->earnings()->count();
        if ($actualCount !== $payout->sessions_count) {
            $errors[] = "Sessions count mismatch: expected {$payout->sessions_count}, got {$actualCount}";
        }

        return $errors;
    }

    /**
     * Calculate breakdown of earnings by calculation method
     */
    private function calculateBreakdown(Collection $earnings): array
    {
        $breakdown = [];

        $grouped = $earnings->groupBy('calculation_method');

        foreach ($grouped as $method => $methodEarnings) {
            $breakdown[$method] = [
                'count' => $methodEarnings->count(),
                'total' => $methodEarnings->sum('amount'),
            ];
        }

        return $breakdown;
    }

    /**
     * Get payout statistics for a teacher
     */
    public function getTeacherPayoutStats(string $teacherType, int $teacherId, int $academyId): array
    {
        $cacheKey = "teacher:payout_stats:{$teacherType}:{$teacherId}:{$academyId}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($teacherType, $teacherId, $academyId) {
            // Use database aggregation for counts and sums instead of loading all records
            $stats = TeacherPayout::forTeacher($teacherType, $teacherId)
                ->where('academy_id', $academyId)
                ->selectRaw('
                    COUNT(*) as total_payouts,
                    SUM(CASE WHEN status = ? THEN total_amount ELSE 0 END) as total_pending,
                    SUM(CASE WHEN status = ? THEN total_amount ELSE 0 END) as total_approved
                ', [PayoutStatus::PENDING->value, PayoutStatus::APPROVED->value])
                ->first();

            $lastPayout = TeacherPayout::forTeacher($teacherType, $teacherId)
                ->where('academy_id', $academyId)
                ->orderByDesc('payout_month')
                ->first();

            return [
                'total_payouts' => (int) ($stats->total_payouts ?? 0),
                'total_pending' => (float) ($stats->total_pending ?? 0),
                'total_approved' => (float) ($stats->total_approved ?? 0),
                'last_payout' => $lastPayout,
            ];
        });
    }

    /**
     * Send payout notification to teacher
     *
     * @param  string  $type  'approved' | 'rejected'
     * @param  string|null  $reason  Rejection reason (for rejected type)
     */
    protected function sendPayoutNotification(
        TeacherPayout $payout,
        string $type,
        ?string $reason = null
    ): void {
        try {
            $teacher = $this->getTeacherUser($payout);
            if (! $teacher) {
                Log::warning('Could not find teacher user for payout notification', [
                    'payout_id' => $payout->id,
                    'teacher_type' => $payout->teacher_type,
                    'teacher_id' => $payout->teacher_id,
                ]);

                return;
            }

            $monthName = Carbon::parse($payout->payout_month)->translatedFormat('F Y');
            $payoutData = [
                'payout_id' => $payout->id,
                'payout_code' => $payout->payout_code,
                'month' => $monthName,
                'amount' => number_format($payout->total_amount, 2),
                'currency' => getCurrencyCode(null, $payout->academy),
            ];

            switch ($type) {
                case 'approved':
                    $this->notificationService->sendPayoutApprovedNotification($teacher, $payoutData);
                    break;

                case 'rejected':
                    $payoutData['reason'] = $reason ?? '';
                    $this->notificationService->sendPayoutRejectedNotification($teacher, $payoutData);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Failed to send payout notification', [
                'payout_id' => $payout->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the User model for a teacher based on payout type
     * Uses eager loading to prevent N+1 queries
     */
    protected function getTeacherUser(TeacherPayout $payout): ?User
    {
        if ($payout->teacher_type === 'quran_teacher') {
            $profile = QuranTeacherProfile::with('user')->find($payout->teacher_id);

            return $profile?->user;
        }

        if ($payout->teacher_type === 'academic_teacher') {
            $profile = AcademicTeacherProfile::with('user')->find($payout->teacher_id);

            return $profile?->user;
        }

        return null;
    }

    /**
     * Clear payout statistics cache for a teacher.
     */
    public function clearTeacherPayoutCache(string $teacherType, int $teacherId, int $academyId): void
    {
        Cache::forget("teacher:payout_stats:{$teacherType}:{$teacherId}:{$academyId}");
    }
}
