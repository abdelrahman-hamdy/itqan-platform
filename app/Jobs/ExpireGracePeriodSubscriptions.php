<?php

namespace App\Jobs;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Notifications\GenericEmailNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireGracePeriodSubscriptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Exponential backoff between retries (seconds).
     *
     * @var array<int>
     */
    public array $backoff = [30, 60, 120];

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * Handles auto-renewal failure grace periods (payment_status=FAILED).
     * Only processes subscriptions that entered grace period due to payment failure.
     * Admin-granted grace periods (payment_status=PAID) are handled by
     * SuspendExpiredGraceSubscriptions command instead.
     */
    public function handle(): void
    {
        Log::info('Starting grace period expiry job');

        $expiredCount = 0;

        // Process Quran subscriptions with chunkById for memory efficiency
        QuranSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('payment_status', SubscriptionPaymentStatus::FAILED)
            ->whereNotNull('metadata')
            ->chunkById(100, function ($subscriptions) use (&$expiredCount) {
                foreach ($subscriptions as $subscription) {
                    if ($this->hasExpiredGracePeriod($subscription)) {
                        $this->cancelSubscription($subscription, 'quran');
                        $expiredCount++;
                    }
                }
            });

        // Process Academic subscriptions with chunkById for memory efficiency
        AcademicSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('payment_status', SubscriptionPaymentStatus::FAILED)
            ->whereNotNull('metadata')
            ->chunkById(100, function ($subscriptions) use (&$expiredCount) {
                foreach ($subscriptions as $subscription) {
                    if ($this->hasExpiredGracePeriod($subscription)) {
                        $this->cancelSubscription($subscription, 'academic');
                        $expiredCount++;
                    }
                }
            });

        Log::info('Grace period expiry job completed', [
            'expired_count' => $expiredCount,
        ]);
    }

    /**
     * Check if a subscription has an expired grace period.
     * Checks both standardized and legacy metadata keys.
     */
    private function hasExpiredGracePeriod($subscription): bool
    {
        $metadata = $subscription->metadata ?? [];

        // Check standardized key first, then legacy key
        $key = isset($metadata['grace_period_ends_at']) ? 'grace_period_ends_at'
            : (isset($metadata['grace_period_expires_at']) ? 'grace_period_expires_at' : null);

        if (! $key) {
            return false;
        }

        return Carbon::parse($metadata[$key])->isPast();
    }

    /**
     * Cancel a subscription after grace period expiry
     */
    private function cancelSubscription($subscription, string $type): void
    {
        $metadata = $subscription->metadata ?? [];
        $graceEndDate = $this->getGraceEndDate($metadata);

        // Update subscription status
        $subscription->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
            'payment_status' => SubscriptionPaymentStatus::FAILED,
            'cancellation_reason' => 'انتهت فترة السماح بعد فشل التجديد التلقائي',
            'cancelled_at' => now(),
        ]);

        // Send notification to student (guard against null student relationship)
        $student = $subscription->student;
        try {
            if ($student) {
                $student->notify(new GenericEmailNotification(
                    title: 'إلغاء الاشتراك',
                    message: 'انتهت فترة السماح بعد فشل التجديد التلقائي. يرجى الاشتراك مرة أخرى لاستئناف الخدمة.',
                    actionUrl: null,
                    academy: $subscription->academy,
                ));
            }
        } catch (Exception $e) {
            Log::error('Failed to send subscription cancelled notification', [
                'subscription_id' => $subscription->id,
                'subscription_type' => $type,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Subscription cancelled after grace period expiry', [
            'subscription_id' => $subscription->id,
            'subscription_type' => $type,
            'subscription_code' => $subscription->subscription_code,
            'student_id' => $subscription->student_id,
            'grace_period_expired_at' => $graceEndDate?->toDateTimeString(),
        ]);
    }

    /**
     * Get grace period end date from metadata (checks both keys).
     */
    private function getGraceEndDate(array $metadata): ?Carbon
    {
        if (isset($metadata['grace_period_ends_at'])) {
            return Carbon::parse($metadata['grace_period_ends_at']);
        }
        if (isset($metadata['grace_period_expires_at'])) {
            return Carbon::parse($metadata['grace_period_expires_at']);
        }

        return null;
    }
}
