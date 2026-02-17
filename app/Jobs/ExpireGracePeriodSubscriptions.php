<?php

namespace App\Jobs;

use Carbon\Carbon;
use Exception;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Notifications\SubscriptionCancelledNotification;
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
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting grace period expiry job');

        $expiredCount = 0;

        // Process Quran subscriptions (withoutGlobalScopes: runs without tenant context)
        $quranSubscriptions = QuranSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('payment_status', SubscriptionPaymentStatus::FAILED)
            ->whereNotNull('metadata')
            ->get()
            ->filter(function ($subscription) {
                $metadata = $subscription->metadata ?? [];
                if (! isset($metadata['grace_period_expires_at'])) {
                    return false;
                }

                $expiresAt = Carbon::parse($metadata['grace_period_expires_at']);

                return $expiresAt->isPast();
            });

        foreach ($quranSubscriptions as $subscription) {
            $this->cancelSubscription($subscription, 'quran');
            $expiredCount++;
        }

        // Process Academic subscriptions (withoutGlobalScopes: runs without tenant context)
        $academicSubscriptions = AcademicSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('payment_status', SubscriptionPaymentStatus::FAILED)
            ->whereNotNull('metadata')
            ->get()
            ->filter(function ($subscription) {
                $metadata = $subscription->metadata ?? [];
                if (! isset($metadata['grace_period_expires_at'])) {
                    return false;
                }

                $expiresAt = Carbon::parse($metadata['grace_period_expires_at']);

                return $expiresAt->isPast();
            });

        foreach ($academicSubscriptions as $subscription) {
            $this->cancelSubscription($subscription, 'academic');
            $expiredCount++;
        }

        Log::info('Grace period expiry job completed', [
            'expired_count' => $expiredCount,
            'quran_count' => $quranSubscriptions->count(),
            'academic_count' => $academicSubscriptions->count(),
        ]);
    }

    /**
     * Cancel a subscription after grace period expiry
     */
    private function cancelSubscription($subscription, string $type): void
    {
        $metadata = $subscription->metadata ?? [];
        $gracePeriodExpiresAt = isset($metadata['grace_period_expires_at'])
            ? Carbon::parse($metadata['grace_period_expires_at'])
            : null;

        // Update subscription status
        $subscription->update([
            'status' => SessionSubscriptionStatus::CANCELLED,
            'payment_status' => SubscriptionPaymentStatus::FAILED,
            'cancellation_reason' => 'انتهت فترة السماح بعد فشل التجديد التلقائي',
            'cancelled_at' => now(),
        ]);

        // Send notification to student
        try {
            $subscription->student->notify(new SubscriptionCancelledNotification(
                $subscription,
                'انتهت فترة السماح بعد فشل التجديد التلقائي. يرجى الاشتراك مرة أخرى لاستئناف الخدمة.',
                $gracePeriodExpiresAt
            ));
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
            'grace_period_expired_at' => $gracePeriodExpiresAt?->toDateTimeString(),
        ]);
    }
}
