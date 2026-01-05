<?php

namespace App\Services;

use App\Contracts\SubscriptionServiceInterface;
use App\Enums\BillingCycle;
use App\Enums\EnrollmentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\SessionStatus;

/**
 * SubscriptionService
 *
 * Unified service for managing all subscription types:
 * - QuranSubscription (uses SessionSubscriptionStatus)
 * - AcademicSubscription (uses SessionSubscriptionStatus)
 * - CourseSubscription (uses EnrollmentStatus)
 *
 * Provides a single interface for:
 * - Creating subscriptions with package data snapshotting
 * - Activating subscriptions after payment
 * - Cancelling subscriptions
 * - Retrieving unified subscription lists
 * - Getting subscription statistics
 *
 * DESIGN PATTERN:
 * - Facade Pattern: Single entry point for subscription operations
 * - Factory Method: Creates appropriate subscription types
 * - Repository Pattern: Centralized data access
 */
class SubscriptionService implements SubscriptionServiceInterface
{
    /**
     * Subscription type constants
     */
    public const TYPE_QURAN = 'quran';
    public const TYPE_ACADEMIC = 'academic';
    public const TYPE_COURSE = 'course';

    /**
     * Get all subscription types
     */
    public function getSubscriptionTypes(): array
    {
        return [
            self::TYPE_QURAN => 'اشتراك قرآن',
            self::TYPE_ACADEMIC => 'اشتراك أكاديمي',
            self::TYPE_COURSE => 'اشتراك دورة',
        ];
    }

    /**
     * Get the model class for a subscription type
     */
    public function getModelClass(string $type): string
    {
        return match ($type) {
            self::TYPE_QURAN => QuranSubscription::class,
            self::TYPE_ACADEMIC => AcademicSubscription::class,
            self::TYPE_COURSE => CourseSubscription::class,
            default => throw new \InvalidArgumentException("Unknown subscription type: {$type}"),
        };
    }

    /**
     * Check if a subscription type uses session-based status
     */
    public function isSessionBased(string $type): bool
    {
        return in_array($type, [self::TYPE_QURAN, self::TYPE_ACADEMIC]);
    }

    // ========================================
    // SUBSCRIPTION CREATION
    // ========================================

    /**
     * Create a new subscription
     *
     * @param string $type Subscription type (quran, academic, course)
     * @param array $data Subscription data
     * @return BaseSubscription
     */
    public function create(string $type, array $data): BaseSubscription
    {
        $modelClass = $this->getModelClass($type);

        return DB::transaction(function () use ($modelClass, $data) {
            // Use the model's static factory method
            if (method_exists($modelClass, 'createSubscription')) {
                $subscription = $modelClass::createSubscription($data);
            } else {
                $subscription = $modelClass::create($data);
            }

            Log::info("Subscription created", [
                'type' => $subscription->getSubscriptionType(),
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
                'student_id' => $subscription->student_id,
            ]);

            return $subscription;
        });
    }

    /**
     * Create a Quran subscription
     */
    public function createQuranSubscription(array $data): QuranSubscription
    {
        return $this->create(self::TYPE_QURAN, $data);
    }

    /**
     * Create an Academic subscription
     */
    public function createAcademicSubscription(array $data): AcademicSubscription
    {
        return $this->create(self::TYPE_ACADEMIC, $data);
    }

    /**
     * Create a Course subscription
     */
    public function createCourseSubscription(array $data): CourseSubscription
    {
        return $this->create(self::TYPE_COURSE, $data);
    }

    /**
     * Create a trial subscription
     */
    public function createTrialSubscription(string $type, array $data): BaseSubscription
    {
        $modelClass = $this->getModelClass($type);

        return DB::transaction(function () use ($modelClass, $type, $data) {
            if (method_exists($modelClass, 'createTrialSubscription')) {
                return $modelClass::createTrialSubscription($data);
            }

            // Fallback: create regular subscription with trial flags
            if ($this->isSessionBased($type)) {
                $data['status'] = SessionSubscriptionStatus::ACTIVE;
            } else {
                $data['status'] = EnrollmentStatus::ENROLLED;
            }
            $data['payment_status'] = SubscriptionPaymentStatus::PAID;
            $data['final_price'] = 0;

            return $modelClass::create($data);
        });
    }

    // ========================================
    // SUBSCRIPTION ACTIVATION
    // ========================================

    /**
     * Activate a subscription after successful payment
     */
    public function activate(BaseSubscription $subscription, ?float $amountPaid = null): BaseSubscription
    {
        return DB::transaction(function () use ($subscription, $amountPaid) {
            // Lock the row to prevent race conditions
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            if (!$subscription->isPending()) {
                throw new \Exception('Subscription is not in pending state');
            }

            $subscription->activate();

            if ($amountPaid !== null) {
                $subscription->update(['final_price' => $amountPaid]);
            }

            Log::info("Subscription activated", [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
                'amount' => $amountPaid,
            ]);

            return $subscription->fresh();
        });
    }

    // ========================================
    // SUBSCRIPTION CANCELLATION
    // ========================================

    /**
     * Cancel a subscription
     */
    public function cancel(BaseSubscription $subscription, ?string $reason = null): BaseSubscription
    {
        return DB::transaction(function () use ($subscription, $reason) {
            $subscription = $subscription::lockForUpdate()->find($subscription->id);

            if (!$subscription->canCancel()) {
                throw new \Exception('Subscription cannot be cancelled in current state');
            }

            $subscription->cancel($reason);

            Log::info("Subscription cancelled", [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
                'reason' => $reason,
            ]);

            return $subscription->fresh();
        });
    }

    // ========================================
    // UNIFIED SUBSCRIPTION QUERIES
    // ========================================

    /**
     * Get all subscriptions for a student
     *
     * Returns a unified collection of all subscription types
     */
    public function getStudentSubscriptions(int $studentId, ?int $academyId = null): Collection
    {
        $subscriptions = collect();

        // Get Quran subscriptions
        $quranQuery = QuranSubscription::where('student_id', $studentId);
        if ($academyId) {
            $quranQuery->where('academy_id', $academyId);
        }
        $subscriptions = $subscriptions->merge($quranQuery->get());

        // Get Academic subscriptions
        $academicQuery = AcademicSubscription::where('student_id', $studentId);
        if ($academyId) {
            $academicQuery->where('academy_id', $academyId);
        }
        $subscriptions = $subscriptions->merge($academicQuery->get());

        // Get Course subscriptions
        $courseQuery = CourseSubscription::where('student_id', $studentId);
        if ($academyId) {
            $courseQuery->where('academy_id', $academyId);
        }
        $subscriptions = $subscriptions->merge($courseQuery->get());

        // Sort by created_at descending
        return $subscriptions->sortByDesc('created_at')->values();
    }

    /**
     * Get active subscriptions for a student
     */
    public function getActiveSubscriptions(int $studentId, ?int $academyId = null): Collection
    {
        return $this->getStudentSubscriptions($studentId, $academyId)
            ->filter(fn ($sub) => $sub->isActive());
    }

    /**
     * Get all subscriptions for an academy
     *
     * Note: This method accepts SessionSubscriptionStatus for Quran/Academic subscriptions.
     * CourseSubscription uses EnrollmentStatus but we map common statuses.
     */
    public function getAcademySubscriptions(int $academyId, ?SessionSubscriptionStatus $status = null): Collection
    {
        $subscriptions = collect();

        $quranQuery = QuranSubscription::where('academy_id', $academyId);
        $academicQuery = AcademicSubscription::where('academy_id', $academyId);
        $courseQuery = CourseSubscription::where('academy_id', $academyId);

        if ($status) {
            $quranQuery->where('status', $status);
            $academicQuery->where('status', $status);

            // Map SessionSubscriptionStatus to EnrollmentStatus for CourseSubscription
            $enrollmentStatus = match ($status) {
                SessionSubscriptionStatus::PENDING => EnrollmentStatus::PENDING,
                SessionSubscriptionStatus::ACTIVE => EnrollmentStatus::ENROLLED,
                SessionSubscriptionStatus::CANCELLED => EnrollmentStatus::CANCELLED,
                SessionSubscriptionStatus::PAUSED => null, // Courses don't have paused status
            };

            if ($enrollmentStatus) {
                $courseQuery->where('status', $enrollmentStatus);
            } else {
                // If no mapping, don't include courses for this status
                $courseQuery->whereRaw('1 = 0');
            }
        }

        return $subscriptions
            ->merge($quranQuery->get())
            ->merge($academicQuery->get())
            ->merge($courseQuery->get())
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Find a subscription by code
     */
    public function findByCode(string $code): ?BaseSubscription
    {
        // Determine type from code prefix
        $prefix = strtoupper(substr($code, 0, 2));

        $subscription = match ($prefix) {
            'QS' => QuranSubscription::where('subscription_code', $code)->first(),
            'AS' => AcademicSubscription::where('subscription_code', $code)->first(),
            'CS' => CourseSubscription::where('subscription_code', $code)->first(),
            default => null,
        };

        // If not found by prefix, search all types
        if (!$subscription) {
            $subscription = QuranSubscription::where('subscription_code', $code)->first()
                ?? AcademicSubscription::where('subscription_code', $code)->first()
                ?? CourseSubscription::where('subscription_code', $code)->first();
        }

        return $subscription;
    }

    /**
     * Find a subscription by ID and type
     */
    public function findById(int $id, string $type): ?BaseSubscription
    {
        $modelClass = $this->getModelClass($type);

        return $modelClass::find($id);
    }

    // ========================================
    // SUBSCRIPTION STATISTICS
    // ========================================

    /**
     * Get subscription statistics for an academy
     */
    public function getAcademyStatistics(int $academyId): array
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'pending' => 0,
            'paused' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'revenue' => 0,
            'by_type' => [],
        ];

        // Session-based subscriptions (Quran & Academic)
        foreach ([self::TYPE_QURAN, self::TYPE_ACADEMIC] as $type) {
            $modelClass = $this->getModelClass($type);

            $typeStats = [
                'total' => $modelClass::where('academy_id', $academyId)->count(),
                'active' => $modelClass::where('academy_id', $academyId)
                    ->where('status', SessionSubscriptionStatus::ACTIVE)->count(),
                'pending' => $modelClass::where('academy_id', $academyId)
                    ->where('status', SessionSubscriptionStatus::PENDING)->count(),
                'paused' => $modelClass::where('academy_id', $academyId)
                    ->where('status', SessionSubscriptionStatus::PAUSED)->count(),
                'cancelled' => $modelClass::where('academy_id', $academyId)
                    ->where('status', SessionSubscriptionStatus::CANCELLED)->count(),
                'completed' => 0, // Session-based subscriptions don't have completed status
                'revenue' => $modelClass::where('academy_id', $academyId)
                    ->where('payment_status', SubscriptionPaymentStatus::PAID)
                    ->sum('final_price') ?? 0,
            ];

            $stats['by_type'][$type] = $typeStats;
            $stats['total'] += $typeStats['total'];
            $stats['active'] += $typeStats['active'];
            $stats['pending'] += $typeStats['pending'];
            $stats['paused'] += $typeStats['paused'];
            $stats['cancelled'] += $typeStats['cancelled'];
            $stats['revenue'] += $typeStats['revenue'];
        }

        // Course subscriptions (use EnrollmentStatus)
        $courseStats = [
            'total' => CourseSubscription::where('academy_id', $academyId)->count(),
            'active' => CourseSubscription::where('academy_id', $academyId)
                ->where('status', EnrollmentStatus::ENROLLED)->count(),
            'pending' => CourseSubscription::where('academy_id', $academyId)
                ->where('status', EnrollmentStatus::PENDING)->count(),
            'paused' => 0, // Courses don't have paused status
            'cancelled' => CourseSubscription::where('academy_id', $academyId)
                ->where('status', EnrollmentStatus::CANCELLED)->count(),
            'completed' => CourseSubscription::where('academy_id', $academyId)
                ->where('status', EnrollmentStatus::COMPLETED)->count(),
            'revenue' => CourseSubscription::where('academy_id', $academyId)
                ->where('payment_status', SubscriptionPaymentStatus::PAID)
                ->sum('final_price') ?? 0,
        ];

        $stats['by_type'][self::TYPE_COURSE] = $courseStats;
        $stats['total'] += $courseStats['total'];
        $stats['active'] += $courseStats['active'];
        $stats['pending'] += $courseStats['pending'];
        $stats['cancelled'] += $courseStats['cancelled'];
        $stats['completed'] += $courseStats['completed'];
        $stats['revenue'] += $courseStats['revenue'];

        return $stats;
    }

    /**
     * Get student subscription statistics
     */
    public function getStudentStatistics(int $studentId): array
    {
        $subscriptions = $this->getStudentSubscriptions($studentId);

        return [
            'total' => $subscriptions->count(),
            'active' => $subscriptions->filter(fn ($s) => $s->isActive())->count(),
            'completed' => $subscriptions->filter(fn ($s) => method_exists($s, 'isCompleted') && $s->isCompleted())->count(),
            'total_spent' => $subscriptions
                ->where('payment_status', SubscriptionPaymentStatus::PAID)
                ->sum('final_price'),
            'by_type' => [
                self::TYPE_QURAN => $subscriptions->filter(
                    fn ($s) => $s instanceof QuranSubscription
                )->count(),
                self::TYPE_ACADEMIC => $subscriptions->filter(
                    fn ($s) => $s instanceof AcademicSubscription
                )->count(),
                self::TYPE_COURSE => $subscriptions->filter(
                    fn ($s) => $s instanceof CourseSubscription
                )->count(),
            ],
        ];
    }

    // ========================================
    // EXPIRING SUBSCRIPTIONS
    // ========================================

    /**
     * Get subscriptions expiring within N days
     */
    public function getExpiringSoon(int $academyId, int $days = 7): Collection
    {
        $subscriptions = collect();

        // Only Quran and Academic have time-based expiry with auto-renewal
        $subscriptions = $subscriptions->merge(
            QuranSubscription::where('academy_id', $academyId)
                ->expiringSoon($days)
                ->get()
        );

        $subscriptions = $subscriptions->merge(
            AcademicSubscription::where('academy_id', $academyId)
                ->expiringSoon($days)
                ->get()
        );

        // Course subscriptions with timed access
        $subscriptions = $subscriptions->merge(
            CourseSubscription::where('academy_id', $academyId)
                ->where('lifetime_access', false)
                ->where('status', EnrollmentStatus::ENROLLED)
                ->whereBetween('ends_at', [now(), now()->addDays($days)])
                ->get()
        );

        return $subscriptions->sortBy('ends_at')->values();
    }

    /**
     * Get subscriptions due for renewal
     */
    public function getDueForRenewal(int $academyId): Collection
    {
        $subscriptions = collect();

        // Only Quran and Academic support auto-renewal
        $subscriptions = $subscriptions->merge(
            QuranSubscription::where('academy_id', $academyId)
                ->dueForRenewal()
                ->get()
        );

        $subscriptions = $subscriptions->merge(
            AcademicSubscription::where('academy_id', $academyId)
                ->dueForRenewal()
                ->get()
        );

        return $subscriptions->sortBy('next_billing_date')->values();
    }

    // ========================================
    // SUBSCRIPTION SUMMARIES FOR VIEWS
    // ========================================

    /**
     * Get unified subscription summaries for display
     *
     * Returns array of subscription summary data suitable for views
     */
    public function getSubscriptionSummaries(int $studentId, ?int $academyId = null): array
    {
        $subscriptions = $this->getStudentSubscriptions($studentId, $academyId);

        return $subscriptions->map(function ($subscription) {
            return $subscription->getSubscriptionSummary();
        })->toArray();
    }

    /**
     * Get active subscription summaries grouped by type
     */
    public function getActiveSubscriptionsByType(int $studentId, ?int $academyId = null): array
    {
        $subscriptions = $this->getActiveSubscriptions($studentId, $academyId);

        return [
            'quran' => $subscriptions->filter(fn ($s) => $s instanceof QuranSubscription)
                ->map(fn ($s) => $s->getSubscriptionSummary())->values()->toArray(),
            'academic' => $subscriptions->filter(fn ($s) => $s instanceof AcademicSubscription)
                ->map(fn ($s) => $s->getSubscriptionSummary())->values()->toArray(),
            'course' => $subscriptions->filter(fn ($s) => $s instanceof CourseSubscription)
                ->map(fn ($s) => $s->getSubscriptionSummary())->values()->toArray(),
        ];
    }

    // ========================================
    // BILLING CYCLE CHANGES
    // ========================================

    /**
     * Change subscription billing cycle
     *
     * Note: Takes effect on next renewal, not immediately
     */
    public function changeBillingCycle(BaseSubscription $subscription, BillingCycle $newCycle): BaseSubscription
    {
        if (!$newCycle->supportsAutoRenewal() && $subscription->auto_renew) {
            $subscription->update(['auto_renew' => false]);
        }

        $subscription->update(['billing_cycle' => $newCycle]);

        Log::info("Subscription billing cycle changed", [
            'id' => $subscription->id,
            'new_cycle' => $newCycle->value,
        ]);

        return $subscription->fresh();
    }

    /**
     * Toggle auto-renewal
     */
    public function toggleAutoRenewal(BaseSubscription $subscription, bool $enabled): BaseSubscription
    {
        if ($enabled && !$subscription->billing_cycle->supportsAutoRenewal()) {
            throw new \Exception('This billing cycle does not support auto-renewal');
        }

        $subscription->update(['auto_renew' => $enabled]);

        Log::info("Subscription auto-renewal toggled", [
            'id' => $subscription->id,
            'auto_renew' => $enabled,
        ]);

        return $subscription->fresh();
    }
}
