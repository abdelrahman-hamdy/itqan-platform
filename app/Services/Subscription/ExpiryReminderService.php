<?php

namespace App\Services\Subscription;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Notifications\SubscriptionExpiringNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ExpiryReminderService
{
    /**
     * Days before expiry to send reminders.
     */
    private const REMINDER_DAYS = [7, 3, 1];

    /**
     * Send expiry reminders for subscriptions expiring in 7, 3, or 1 days.
     */
    public function sendReminders(): array
    {
        $stats = ['sent' => 0, 'skipped' => 0, 'errors' => 0];

        foreach (self::REMINDER_DAYS as $days) {
            $targetDate = Carbon::now()->addDays($days)->startOfDay();
            $targetEnd = $targetDate->copy()->endOfDay();

            // Query both subscription types
            $quranSubs = QuranSubscription::withoutGlobalScopes()
                ->where('status', SessionSubscriptionStatus::ACTIVE)
                ->whereBetween('ends_at', [$targetDate, $targetEnd])
                ->get();

            $academicSubs = AcademicSubscription::withoutGlobalScopes()
                ->where('status', SessionSubscriptionStatus::ACTIVE)
                ->whereBetween('ends_at', [$targetDate, $targetEnd])
                ->get();

            $subscriptions = $quranSubs->merge($academicSubs);

            foreach ($subscriptions as $subscription) {
                try {
                    $student = $subscription->student;
                    if (! $student) {
                        $stats['skipped']++;

                        continue;
                    }

                    // Deduplicate: check if already notified for this subscription + day combo
                    $alreadySent = $student->notifications()
                        ->where('type', SubscriptionExpiringNotification::class)
                        ->whereJsonContains('data->subscription_id', $subscription->id)
                        ->whereJsonContains('data->days_remaining', $days)
                        ->where('created_at', '>=', Carbon::today())
                        ->exists();

                    if ($alreadySent) {
                        $stats['skipped']++;

                        continue;
                    }

                    $student->notify(new SubscriptionExpiringNotification($subscription, $days));
                    $stats['sent']++;

                    Log::info('Subscription expiry reminder sent', [
                        'subscription_id' => $subscription->id,
                        'subscription_type' => get_class($subscription),
                        'student_id' => $student->id,
                        'days_remaining' => $days,
                    ]);
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Failed to send expiry reminder', [
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $stats;
    }
}
