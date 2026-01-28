<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\TrialRequestStatus;
use App\Models\QuranIndividualCircle;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\QuranTrialRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service to handle trial-to-subscription conversion
 *
 * DECOUPLED ARCHITECTURE:
 * This service manages the conversion of completed trial sessions
 * into paid subscriptions following the new architecture:
 * 1. Creates an independent QuranIndividualCircle first
 * 2. Then creates a QuranSubscription linked to the circle via polymorphic relationship
 * 3. Circle persists even if subscription is cancelled
 */
class TrialConversionService
{
    /**
     * Get available packages for conversion
     */
    public function getAvailablePackages(QuranTrialRequest $trialRequest): \Illuminate\Database\Eloquent\Collection
    {
        return QuranPackage::where('academy_id', $trialRequest->academy_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();
    }

    /**
     * Check if a trial request is eligible for conversion
     */
    public function isEligibleForConversion(QuranTrialRequest $trialRequest): bool
    {
        // Must be completed
        if ($trialRequest->status !== TrialRequestStatus::COMPLETED) {
            return false;
        }

        // Must have a student
        if (! $trialRequest->student_id) {
            return false;
        }

        // Check if student already has an active subscription with this teacher
        $hasActiveSubscription = QuranSubscription::where('student_id', $trialRequest->student_id)
            ->where('quran_teacher_id', $trialRequest->teacher?->user_id)
            ->where('academy_id', $trialRequest->academy_id)
            ->whereIn('status', [SessionSubscriptionStatus::ACTIVE, SessionSubscriptionStatus::PENDING])
            ->exists();

        return ! $hasActiveSubscription;
    }

    /**
     * Check if a trial was already converted to a subscription
     */
    public function wasConverted(QuranTrialRequest $trialRequest): bool
    {
        return QuranSubscription::where('student_id', $trialRequest->student_id)
            ->where('quran_teacher_id', $trialRequest->teacher?->user_id)
            ->where('academy_id', $trialRequest->academy_id)
            ->whereJsonContains('metadata->trial_request_id', $trialRequest->id)
            ->exists();
    }

    /**
     * Get conversion statistics for an academy
     */
    public function getConversionStats(int $academyId, ?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): array
    {
        $query = QuranTrialRequest::where('academy_id', $academyId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalTrials = $query->count();
        $completedTrials = (clone $query)->where('status', TrialRequestStatus::COMPLETED)->count();

        // Count converted trials (subscriptions with trial_request_id in metadata)
        $convertedTrials = QuranSubscription::where('academy_id', $academyId)
            ->whereNotNull('metadata->trial_request_id')
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
            ->count();

        // Average rating of completed trials
        $avgRating = (clone $query)
            ->where('status', TrialRequestStatus::COMPLETED)
            ->whereNotNull('rating')
            ->avg('rating');

        return [
            'total_trials' => $totalTrials,
            'completed_trials' => $completedTrials,
            'converted_trials' => $convertedTrials,
            'completion_rate' => $totalTrials > 0 ? round(($completedTrials / $totalTrials) * 100, 1) : 0,
            'conversion_rate' => $completedTrials > 0 ? round(($convertedTrials / $completedTrials) * 100, 1) : 0,
            'average_rating' => $avgRating ? round($avgRating, 1) : null,
        ];
    }

    /**
     * Get conversion stats by teacher
     */
    public function getTeacherConversionStats(int $academyId): array
    {
        $stats = [];

        $teachers = QuranTrialRequest::where('academy_id', $academyId)
            ->select('teacher_id')
            ->distinct()
            ->with('teacher.user')
            ->get()
            ->pluck('teacher')
            ->filter();

        foreach ($teachers as $teacher) {
            $totalTrials = QuranTrialRequest::where('academy_id', $academyId)
                ->where('teacher_id', $teacher->id)
                ->count();

            $completedTrials = QuranTrialRequest::where('academy_id', $academyId)
                ->where('teacher_id', $teacher->id)
                ->where('status', TrialRequestStatus::COMPLETED)
                ->count();

            $convertedTrials = QuranSubscription::where('academy_id', $academyId)
                ->where('quran_teacher_id', $teacher->user_id)
                ->whereNotNull('metadata->trial_request_id')
                ->count();

            $avgRating = QuranTrialRequest::where('academy_id', $academyId)
                ->where('teacher_id', $teacher->id)
                ->where('status', TrialRequestStatus::COMPLETED)
                ->whereNotNull('rating')
                ->avg('rating');

            $stats[] = [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->user?->name ?? 'Unknown',
                'total_trials' => $totalTrials,
                'completed_trials' => $completedTrials,
                'converted_trials' => $convertedTrials,
                'conversion_rate' => $completedTrials > 0 ? round(($convertedTrials / $completedTrials) * 100, 1) : 0,
                'average_rating' => $avgRating ? round($avgRating, 1) : null,
            ];
        }

        // Sort by conversion rate descending
        usort($stats, fn ($a, $b) => $b['conversion_rate'] <=> $a['conversion_rate']);

        return $stats;
    }

    /**
     * Convert a trial request to a subscription
     *
     * DECOUPLED ARCHITECTURE:
     * 1. Creates an independent QuranIndividualCircle first
     * 2. Then creates a QuranSubscription linked to the circle via polymorphic relationship
     * 3. Returns both the subscription and the circle
     */
    public function convertToSubscription(
        QuranTrialRequest $trialRequest,
        QuranPackage $package,
        BillingCycle $billingCycle,
        ?User $createdBy = null
    ): QuranSubscription {
        if (! $this->isEligibleForConversion($trialRequest)) {
            throw new \Exception('هذا الطلب التجريبي غير مؤهل للتحويل إلى اشتراك');
        }

        return DB::transaction(function () use ($trialRequest, $package, $billingCycle, $createdBy) {
            // Calculate pricing
            $price = $package->getPriceForBillingCycle($billingCycle->value);
            $sessionsPerMonth = $package->sessions_per_month;
            $multiplier = $billingCycle->sessionMultiplier();
            $totalSessions = $sessionsPerMonth * $multiplier;

            // Calculate dates
            $startDate = now();
            $endDate = match ($billingCycle) {
                BillingCycle::MONTHLY => now()->addMonth(),
                BillingCycle::QUARTERLY => now()->addMonths(3),
                BillingCycle::YEARLY => now()->addYear(),
                default => now()->addMonth(),
            };

            // Step 1: Create the individual circle FIRST (independently from subscription)
            $circle = QuranIndividualCircle::create([
                'academy_id' => $trialRequest->academy_id,
                'quran_teacher_id' => $trialRequest->teacher?->user_id,
                'student_id' => $trialRequest->student_id,
                'subscription_id' => null, // Will be linked after subscription creation
                'name' => 'حلقة '.($trialRequest->student?->name ?? 'طالب'),
                'description' => 'تم إنشاؤها من طلب تجريبي #'.$trialRequest->id,
                'specialization' => $trialRequest->specialization ?? 'memorization',
                'memorization_level' => $trialRequest->current_level ?? 'beginner',
                'total_sessions' => $totalSessions,
                'sessions_scheduled' => 0,
                'sessions_completed' => 0,
                'sessions_remaining' => $totalSessions,
                'default_duration_minutes' => $package->session_duration_minutes ?? 45,
                'status' => SessionSubscriptionStatus::PENDING->value, // Will become active when subscription is paid
                'started_at' => null,
                'recording_enabled' => true,
                'learning_objectives' => ['الحفظ', 'التجويد', 'المراجعة'],
                'notes' => $trialRequest->notes,
                'created_by' => $createdBy?->id ?? $trialRequest->student_id,
            ]);

            // Step 2: Create the subscription with polymorphic link to circle
            $subscription = QuranSubscription::createSubscription([
                'academy_id' => $trialRequest->academy_id,
                'student_id' => $trialRequest->student_id,
                'quran_teacher_id' => $trialRequest->teacher?->user_id,
                'package_id' => $package->id,
                'subscription_type' => QuranSubscription::SUBSCRIPTION_TYPE_INDIVIDUAL,
                // Polymorphic link to education unit
                'education_unit_id' => $circle->id,
                'education_unit_type' => QuranIndividualCircle::class,
                'billing_cycle' => $billingCycle,
                'total_sessions' => $totalSessions,
                'sessions_remaining' => $totalSessions,
                'sessions_per_month' => $sessionsPerMonth,
                'session_duration_minutes' => $package->session_duration_minutes ?? 45,
                'base_price' => $price,
                'final_price' => $price,
                'currency' => getCurrencyCode(null, $trialRequest->academy), // Always use academy's configured currency
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => SessionSubscriptionStatus::PENDING,
                'payment_status' => SubscriptionPaymentStatus::PENDING,
                'auto_renew' => true,
                'memorization_level' => $trialRequest->current_level,
                'created_by' => $createdBy?->id ?? $trialRequest->student_id,
                'metadata' => [
                    'trial_request_id' => $trialRequest->id,
                    'converted_at' => now()->toIso8601String(),
                    'trial_rating' => $trialRequest->rating,
                    'trial_feedback' => $trialRequest->feedback,
                    'circle_id' => $circle->id,
                ],
            ]);

            // Step 3: Update circle with subscription_id (for backward compatibility)
            $circle->update(['subscription_id' => $subscription->id]);

            Log::info('Trial converted to subscription with independent circle', [
                'trial_request_id' => $trialRequest->id,
                'circle_id' => $circle->id,
                'subscription_id' => $subscription->id,
                'student_id' => $trialRequest->student_id,
                'teacher_id' => $trialRequest->teacher?->user_id,
                'package_id' => $package->id,
                'billing_cycle' => $billingCycle->value,
            ]);

            return $subscription;
        });
    }

    /**
     * Create an individual circle for a student WITHOUT a subscription
     * Useful for trial periods or free access
     */
    public function createIndependentCircle(
        QuranTrialRequest $trialRequest,
        ?User $createdBy = null
    ): QuranIndividualCircle {
        return QuranIndividualCircle::create([
            'academy_id' => $trialRequest->academy_id,
            'quran_teacher_id' => $trialRequest->teacher?->user_id,
            'student_id' => $trialRequest->student_id,
            'subscription_id' => null, // Independent - no subscription
            'name' => 'حلقة تجريبية - '.($trialRequest->student?->name ?? 'طالب'),
            'description' => 'حلقة تجريبية من طلب #'.$trialRequest->id,
            'specialization' => $trialRequest->specialization ?? 'memorization',
            'memorization_level' => $trialRequest->current_level ?? 'beginner',
            'total_sessions' => 0, // Independent circles don't have session limits
            'sessions_scheduled' => 0,
            'sessions_completed' => 0,
            'sessions_remaining' => 0,
            'default_duration_minutes' => 30, // Trial sessions are shorter
            'status' => SessionSubscriptionStatus::ACTIVE->value, // Active for trial
            'started_at' => now(),
            'recording_enabled' => true,
            'notes' => 'حلقة تجريبية مستقلة',
            'created_by' => $createdBy?->id ?? $trialRequest->student_id,
        ]);
    }

    /**
     * Get pending conversion opportunities (completed trials without subscriptions)
     */
    public function getPendingConversions(int $academyId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return QuranTrialRequest::where('academy_id', $academyId)
            ->where('status', TrialRequestStatus::COMPLETED)
            ->whereNotNull('student_id')
            ->whereDoesntHave('student.quranSubscriptions', function ($query) {
                $query->whereIn('status', [SessionSubscriptionStatus::ACTIVE, SessionSubscriptionStatus::PENDING]);
            })
            ->with(['student', 'teacher.user', 'academy'])
            ->orderByDesc('completed_at')
            ->limit($limit)
            ->get();
    }
}
