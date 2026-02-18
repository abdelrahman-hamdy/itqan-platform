<?php

namespace App\Services\Subscription;

use InvalidArgumentException;
use App\Enums\EnrollmentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles all subscription creation logic including duplicate prevention.
 *
 * Extracted from SubscriptionService to isolate creation/factory logic.
 */
class SubscriptionCreationService
{
    /**
     * Subscription type constants (mirrors SubscriptionService)
     */
    public const TYPE_QURAN = 'quran';

    public const TYPE_ACADEMIC = 'academic';

    public const TYPE_COURSE = 'course';

    /**
     * Create a new subscription
     *
     * @param  string  $type  Subscription type (quran, academic, course)
     * @param  array  $data  Subscription data
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

            Log::info('Subscription created', [
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

    /**
     * Create a subscription with automatic duplicate handling.
     *
     * 1. Cancels any existing pending subscriptions for the same combination
     * 2. Creates the new subscription with pending status
     *
     * @param  string  $type  Subscription type (quran, academic, course)
     * @param  array  $data  Subscription data
     * @param  array  $duplicateKeyValues  Fields for duplicate detection
     */
    public function createWithDuplicateHandling(
        string $type,
        array $data,
        array $duplicateKeyValues
    ): BaseSubscription {
        return DB::transaction(function () use ($type, $data, $duplicateKeyValues) {
            // Cancel any existing pending subscriptions for this combination
            $this->cancelDuplicatePending(
                $type,
                $data['academy_id'],
                $data['student_id'],
                $duplicateKeyValues
            );

            // Create the new subscription
            return $this->create($type, $data);
        });
    }

    /**
     * Check if an active or pending subscription exists for a combination.
     *
     * @param  string  $type  Subscription type
     * @param  int  $academyId  Academy ID
     * @param  int  $studentId  Student ID
     * @param  array  $keyValues  Fields for uniqueness check
     * @return array{active: BaseSubscription|null, pending: BaseSubscription|null}
     */
    public function findExistingSubscription(
        string $type,
        int $academyId,
        int $studentId,
        array $keyValues
    ): array {
        $modelClass = $this->getModelClass($type);
        $activeStatus = $this->isSessionBased($type)
            ? SessionSubscriptionStatus::ACTIVE
            : EnrollmentStatus::ENROLLED;
        $pendingStatus = $this->isSessionBased($type)
            ? SessionSubscriptionStatus::PENDING
            : EnrollmentStatus::PENDING;

        $baseQuery = fn () => $modelClass::where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->when($keyValues, function ($query) use ($keyValues) {
                foreach ($keyValues as $field => $value) {
                    if ($value !== null) {
                        $query->where($field, $value);
                    }
                }
            });

        return [
            'active' => $baseQuery()->where('status', $activeStatus)->first(),
            'pending' => $baseQuery()->where('status', $pendingStatus)
                ->where('payment_status', SubscriptionPaymentStatus::PENDING)
                ->first(),
        ];
    }

    /**
     * Cancel any existing pending subscriptions for the same combination.
     *
     * This is called before creating a new subscription to ensure only
     * one pending subscription exists per unique combination.
     *
     * @param  string  $type  Subscription type (quran, academic, course)
     * @param  int  $academyId  Academy ID
     * @param  int  $studentId  Student ID
     * @param  array  $keyValues  Fields that identify the unique combination
     * @return int Number of pending subscriptions cancelled
     */
    public function cancelDuplicatePending(
        string $type,
        int $academyId,
        int $studentId,
        array $keyValues
    ): int {
        if (! config('subscriptions.duplicates.auto_cancel_old_pending', true)) {
            return 0;
        }

        $modelClass = $this->getModelClass($type);
        $cancelledCount = 0;

        // Build the query for pending subscriptions matching the combination
        $query = $modelClass::where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('payment_status', SubscriptionPaymentStatus::PENDING);

        // Apply the pending status based on subscription type
        if ($this->isSessionBased($type)) {
            $query->where('status', SessionSubscriptionStatus::PENDING);
        } else {
            $query->where('status', EnrollmentStatus::PENDING);
        }

        // Apply key value filters
        foreach ($keyValues as $field => $value) {
            if ($value !== null) {
                $query->where($field, $value);
            }
        }

        $pendingSubscriptions = $query->get();

        foreach ($pendingSubscriptions as $subscription) {
            $subscription->cancelAsDuplicateOrExpired(
                config('subscriptions.cancellation_reasons.duplicate')
            );
            $cancelledCount++;

            Log::info('Cancelled duplicate pending subscription', [
                'id' => $subscription->id,
                'code' => $subscription->subscription_code,
                'type' => $type,
                'reason' => 'duplicate',
            ]);
        }

        return $cancelledCount;
    }

    /**
     * Get the model class for a subscription type
     */
    private function getModelClass(string $type): string
    {
        return match ($type) {
            self::TYPE_QURAN => QuranSubscription::class,
            self::TYPE_ACADEMIC => AcademicSubscription::class,
            self::TYPE_COURSE => CourseSubscription::class,
            default => throw new InvalidArgumentException("Unknown subscription type: {$type}"),
        };
    }

    /**
     * Check if a subscription type uses session-based status
     */
    private function isSessionBased(string $type): bool
    {
        return in_array($type, [self::TYPE_QURAN, self::TYPE_ACADEMIC]);
    }
}
