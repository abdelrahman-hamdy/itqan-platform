<?php

namespace App\Console\Commands;

use App\Constants\DefaultAcademy;
use App\Enums\NotificationType;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

/**
 * Send daily warning notifications to students whose grace period is about to end.
 *
 * Finds ACTIVE subscriptions with metadata['grace_period_ends_at'] in the future,
 * calculates days remaining, and sends a daily reminder to the student and parent.
 *
 * Usage:
 *   php artisan subscriptions:notify-grace-expiring
 *   php artisan subscriptions:notify-grace-expiring --dry-run
 */
class NotifyGracePeriodExpiring extends Command
{
    protected $signature = 'subscriptions:notify-grace-expiring
                            {--dry-run : Preview notifications without sending them}';

    protected $description = 'Send daily warning notifications for subscriptions with expiring grace periods';

    public function handle()
    {
        $notificationService = app(NotificationService::class);
        $parentNotificationService = app(ParentNotificationService::class);
        $dryRun = $this->option('dry-run');
        $count = 0;

        $this->info('Checking for subscriptions with expiring grace periods...');

        // Quran Subscriptions with active grace period
        QuranSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereNotNull('metadata')
            ->with(['student', 'quranCircle', 'academy'])
            ->chunkById(100, function ($subscriptions) use ($notificationService, $parentNotificationService, $dryRun, &$count) {
                foreach ($subscriptions as $subscription) {
                    $result = $this->processGraceNotification($subscription, 'quran', $notificationService, $parentNotificationService, $dryRun);
                    if ($result) {
                        $count++;
                    }
                }
            });

        // Academic Subscriptions with active grace period
        AcademicSubscription::withoutGlobalScopes()
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereNotNull('metadata')
            ->with(['student', 'academy'])
            ->chunkById(100, function ($subscriptions) use ($notificationService, $parentNotificationService, $dryRun, &$count) {
                foreach ($subscriptions as $subscription) {
                    $result = $this->processGraceNotification($subscription, 'academic', $notificationService, $parentNotificationService, $dryRun);
                    if ($result) {
                        $count++;
                    }
                }
            });

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Done. Sent {$count} grace period expiry notification(s).");

        return 0;
    }

    /**
     * Process grace period notification for a single subscription.
     * Returns true if notification was sent/would be sent, false if skipped.
     */
    private function processGraceNotification($subscription, string $type, $notificationService, $parentNotificationService, bool $dryRun): bool
    {
        $metadata = $subscription->metadata ?? [];

        // Check both standardized and legacy keys
        $key = isset($metadata['grace_period_ends_at']) ? 'grace_period_ends_at'
            : (isset($metadata['grace_period_expires_at']) ? 'grace_period_expires_at' : null);

        if (! $key) {
            return false;
        }

        $gracePeriodEndsAt = Carbon::parse($metadata[$key]);
        if ($gracePeriodEndsAt->isPast()) {
            return false;
        }

        if (! $subscription->student) {
            return false;
        }

        $daysLeft = (int) now()->diffInDays($gracePeriodEndsAt, false);
        if ($daysLeft < 0) {
            return false;
        }

        // Deduplication: skip if already notified today (unless critical threshold changed)
        $lastSentAt = isset($metadata['grace_notification_last_sent_at'])
            ? Carbon::parse($metadata['grace_notification_last_sent_at'])
            : null;

        if ($lastSentAt && $lastSentAt->isToday()) {
            // Allow re-notification only at critical thresholds (3 days, 1 day)
            $isCriticalThreshold = in_array($daysLeft, [3, 1, 0]);
            if (! $isCriticalThreshold) {
                return false;
            }
        }

        $studentName = $subscription->student->full_name;

        if ($dryRun) {
            $this->info("[DRY RUN] Would notify {$studentName} — {$type} grace period ends in {$daysLeft} days ({$gracePeriodEndsAt->format('Y-m-d')})");

            return true;
        }

        try {
            $subdomain = $subscription->academy?->subdomain ?? DefaultAcademy::subdomain();

            $subscriptionName = $type === 'quran'
                ? 'حلقة '.($subscription->quranCircle?->name ?? 'القرآن')
                : 'الاشتراك الأكاديمي';

            $notificationService->send(
                $subscription->student,
                NotificationType::GRACE_PERIOD_EXPIRING,
                [
                    'subscription_name' => $subscriptionName,
                    'days_left' => $daysLeft,
                    'grace_end_date' => $gracePeriodEndsAt->format('Y-m-d'),
                ],
                route('student.subscriptions', ['subdomain' => $subdomain]),
                ['subscription_id' => $subscription->id],
                $daysLeft <= 3
            );

            // Also notify parents
            $parentNotificationService->sendPaymentReminder($subscription);

            // Store last notification timestamp in metadata for deduplication
            $metadata['grace_notification_last_sent_at'] = now()->toIso8601String();
            $subscription->update(['metadata' => $metadata]);

            $this->line("  - Sent to {$studentName} ({$type} — {$daysLeft} days left)");

            return true;
        } catch (Exception $e) {
            $this->error("  - Failed for {$type} subscription {$subscription->id}: {$e->getMessage()}");

            return false;
        }
    }
}
