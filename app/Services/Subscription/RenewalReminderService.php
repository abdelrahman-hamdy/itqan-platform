<?php

namespace App\Services\Subscription;

use Illuminate\Database\QueryException;
use Throwable;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * RenewalReminderService
 *
 * Manages the scheduling and sending of renewal reminders.
 *
 * RESPONSIBILITIES:
 * - Identifying subscriptions that need reminders
 * - Coordinating reminder delivery at 7-day and 3-day intervals
 * - Tracking reminder delivery status
 * - Processing batch reminder operations
 *
 * REMINDER SCHEDULE:
 * - 7-day reminder: First notification, sets renewal_reminder_sent_at
 * - 3-day reminder: Follow-up notification (no timestamp update)
 */
class RenewalReminderService
{
    public function __construct(
        private RenewalNotificationService $notificationService
    ) {}

    /**
     * Send renewal reminders for upcoming renewals
     *
     * Sends reminders at 7 days and 3 days before renewal
     */
    public function sendRenewalReminders(): array
    {
        $results = [
            'sent' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $sevenDayReminders = $this->getSubscriptionsNeedingReminder(7);
        foreach ($sevenDayReminders as $subscription) {
            try {
                $this->notificationService->sendRenewalReminderNotification($subscription, 7);
                $subscription->update(['renewal_reminder_sent_at' => now()]);
                $results['sent']++;
            } catch (QueryException $e) {
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'days' => 7,
                    'error' => 'Database error: '.$e->getMessage(),
                    'type' => 'database',
                ];
            } catch (Throwable $e) {
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'days' => 7,
                    'error' => $e->getMessage(),
                    'type' => 'notification',
                ];
            }
        }

        $threeDayReminders = $this->getSubscriptionsNeedingReminder(3);
        foreach ($threeDayReminders as $subscription) {
            try {
                $this->notificationService->sendRenewalReminderNotification($subscription, 3);
                $results['sent']++;
            } catch (QueryException $e) {
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'days' => 3,
                    'error' => 'Database error: '.$e->getMessage(),
                    'type' => 'database',
                ];
            } catch (Throwable $e) {
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'days' => 3,
                    'error' => $e->getMessage(),
                    'type' => 'notification',
                ];
            }
        }

        Log::info('Renewal reminders sent', $results);

        return $results;
    }

    /**
     * Get subscriptions needing reminder at N days before renewal
     */
    public function getSubscriptionsNeedingReminder(int $daysBeforeRenewal): Collection
    {
        $targetDate = now()->addDays($daysBeforeRenewal)->toDateString();

        $quranSubscriptions = QuranSubscription::where('auto_renew', true)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->whereDate('next_billing_date', $targetDate)
            ->when($daysBeforeRenewal === 7, function ($query) {
                $query->whereNull('renewal_reminder_sent_at');
            })
            ->get();

        $academicSubscriptions = AcademicSubscription::where('auto_renew', true)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->whereDate('next_billing_date', $targetDate)
            ->when($daysBeforeRenewal === 7, function ($query) {
                $query->whereNull('renewal_reminder_sent_at');
            })
            ->get();

        return $quranSubscriptions->concat($academicSubscriptions);
    }
}
