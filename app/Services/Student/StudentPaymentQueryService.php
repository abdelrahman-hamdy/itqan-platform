<?php

namespace App\Services\Student;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for handling student payment queries and statistics.
 *
 * Handles:
 * - Payment history queries
 * - Payment filtering
 * - Payment statistics
 */
class StudentPaymentQueryService
{
    /**
     * Get paginated payment history for a student.
     */
    public function getPaymentHistory(User $user, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $academy = $user->academy;

        $query = Payment::where('user_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['subscription'])
            ->orderBy('payment_date', 'desc');

        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && ! empty($request->date_from)) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && ! empty($request->date_to)) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get payment statistics for a student.
     */
    public function getPaymentStatistics(User $user): array
    {
        $academy = $user->academy;
        $cacheKey = "student:payment_stats:{$user->id}:{$academy->id}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user, $academy) {
            $totalPayments = Payment::where('user_id', $user->id)
                ->where('academy_id', $academy->id)
                ->count();

            $successfulPayments = Payment::where('user_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('status', 'completed')
                ->count();

            $totalAmountPaid = Payment::where('user_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('status', 'completed')
                ->sum('amount');

            $pendingPayments = Payment::where('user_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('status', 'pending')
                ->count();

            $failedPayments = Payment::where('user_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('status', 'failed')
                ->count();

            return [
                'total_payments' => $totalPayments,
                'successful_payments' => $successfulPayments,
                'total_amount_paid' => $totalAmountPaid,
                'pending_payments' => $pendingPayments,
                'failed_payments' => $failedPayments,
            ];
        });
    }

    /**
     * Get recent payments for a student.
     */
    public function getRecentPayments(User $user, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        $academy = $user->academy;

        return Payment::where('user_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['subscription'])
            ->orderBy('payment_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get payments by subscription type.
     */
    public function getPaymentsBySubscriptionType(User $user, string $subscriptionType): \Illuminate\Database\Eloquent\Collection
    {
        $academy = $user->academy;

        return Payment::where('user_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('subscription_type', $subscriptionType)
            ->with(['subscription'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get payment by ID for a student.
     */
    public function getPaymentById(User $user, string $paymentId): ?Payment
    {
        $academy = $user->academy;

        return Payment::where('id', $paymentId)
            ->where('user_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['subscription'])
            ->first();
    }

    /**
     * Get monthly payment summary for a student.
     *
     * @param  int  $months  Number of months to include
     */
    public function getMonthlyPaymentSummary(User $user, int $months = 12): array
    {
        $academy = $user->academy;
        $cacheKey = "student:payment_monthly:{$user->id}:{$academy->id}:{$months}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($user, $academy, $months) {
            $monthlySummary = Payment::where('user_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('status', 'completed')
                ->where('payment_date', '>=', now()->subMonths($months))
                ->select(
                    DB::raw('DATE_FORMAT(payment_date, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->get();

            return $monthlySummary->toArray();
        });
    }

    /**
     * Clear payment caches for a student.
     */
    public function clearPaymentCache(int $userId, int $academyId): void
    {
        Cache::forget("student:payment_stats:{$userId}:{$academyId}");
        Cache::forget("student:payment_monthly:{$userId}:{$academyId}:12");
        Cache::forget("student:payment_monthly:{$userId}:{$academyId}:6");
        Cache::forget("student:payment_monthly:{$userId}:{$academyId}:3");
    }
}
