<?php

namespace App\Services;

use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutService
{
    /**
     * Generate monthly payout for a specific teacher
     *
     * @param string $teacherType 'quran_teacher' | 'academic_teacher'
     * @param int $teacherId
     * @param int $year
     * @param int $month
     * @param int $academyId
     * @return TeacherPayout|null
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
                'status' => 'pending',
            ]);

            // Link earnings to payout and finalize them
            $earnings->each(function ($earning) use ($payout) {
                $earning->update([
                    'payout_id' => $payout->id,
                    'is_finalized' => true,
                ]);
            });

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
     *
     * @param int $academyId
     * @param int $year
     * @param int $month
     * @return Collection
     */
    public function generatePayoutsForMonth(int $academyId, int $year, int $month): Collection
    {
        $monthDate = sprintf('%04d-%02d-01', $year, $month);

        // Get all teachers who have unpaid earnings for this month
        $teachersWithEarnings = TeacherEarning::where('academy_id', $academyId)
            ->forMonth($year, $month)
            ->unpaid()
            ->where('is_disputed', false)
            ->select('teacher_type', 'teacher_id')
            ->distinct()
            ->get();

        $payouts = collect();

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
     * @param TeacherPayout $payout
     * @param User $approvedBy
     * @param string|null $notes
     * @return bool
     * @throws \Exception
     */
    public function approvePayout(TeacherPayout $payout, User $approvedBy, ?string $notes = null): bool
    {
        if (!$payout->canApprove()) {
            throw new \InvalidArgumentException('Payout cannot be approved in current status: ' . $payout->status);
        }

        // Run validation
        $validationErrors = $this->validateForApproval($payout);
        if (!empty($validationErrors)) {
            throw new \DomainException('Validation failed: ' . implode(', ', $validationErrors));
        }

        return DB::transaction(function () use ($payout, $approvedBy, $notes) {
            $payout->update([
                'status' => 'approved',
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

            // TODO: Send notification to teacher

            return true;
        });
    }

    /**
     * Reject a payout
     *
     * @param TeacherPayout $payout
     * @param User $rejectedBy
     * @param string $reason
     * @return bool
     * @throws \Exception
     */
    public function rejectPayout(TeacherPayout $payout, User $rejectedBy, string $reason): bool
    {
        if (!$payout->canReject()) {
            throw new \InvalidArgumentException('Payout cannot be rejected in current status: ' . $payout->status);
        }

        return DB::transaction(function () use ($payout, $rejectedBy, $reason) {
            // Unlink earnings from payout and unfinalize them
            $payout->earnings()->update([
                'payout_id' => null,
                'is_finalized' => false,
            ]);

            $payout->update([
                'status' => 'rejected',
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

            // TODO: Send notification to teacher

            return true;
        });
    }

    /**
     * Mark payout as paid
     *
     * @param TeacherPayout $payout
     * @param User $paidBy
     * @param array $paymentDetails ['method' => 'bank_transfer', 'reference' => 'TXN123', 'notes' => '...']
     * @return bool
     * @throws \Exception
     */
    public function markAsPaid(TeacherPayout $payout, User $paidBy, array $paymentDetails): bool
    {
        if (!$payout->canMarkPaid()) {
            throw new \InvalidArgumentException('Payout cannot be marked as paid in current status: ' . $payout->status);
        }

        return DB::transaction(function () use ($payout, $paidBy, $paymentDetails) {
            $payout->update([
                'status' => 'paid',
                'paid_by' => $paidBy->id,
                'paid_at' => now(),
                'payment_method' => $paymentDetails['method'] ?? null,
                'payment_reference' => $paymentDetails['reference'] ?? null,
                'payment_notes' => $paymentDetails['notes'] ?? null,
            ]);

            Log::info('Payout marked as paid', [
                'payout_id' => $payout->id,
                'payout_code' => $payout->payout_code,
                'amount' => $payout->total_amount,
                'paid_by' => $paidBy->id,
                'payment_method' => $paymentDetails['method'] ?? null,
            ]);

            // TODO: Send notification to teacher

            return true;
        });
    }

    /**
     * Validate payout before approval
     *
     * @param TeacherPayout $payout
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
     *
     * @param Collection $earnings
     * @return array
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
     *
     * @param string $teacherType
     * @param int $teacherId
     * @param int $academyId
     * @return array
     */
    public function getTeacherPayoutStats(string $teacherType, int $teacherId, int $academyId): array
    {
        $payouts = TeacherPayout::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->get();

        return [
            'total_payouts' => $payouts->count(),
            'total_paid' => $payouts->where('status', 'paid')->sum('total_amount'),
            'total_pending' => $payouts->where('status', 'pending')->sum('total_amount'),
            'total_approved' => $payouts->where('status', 'approved')->sum('total_amount'),
            'last_payout' => $payouts->sortByDesc('payout_month')->first(),
        ];
    }
}
