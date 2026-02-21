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
                    $metadata = $subscription->metadata ?? [];
                    if (! isset($metadata['grace_period_ends_at'])) {
                        continue;
                    }

                    $gracePeriodEndsAt = Carbon::parse($metadata['grace_period_ends_at']);
                    if ($gracePeriodEndsAt->isPast()) {
                        continue;
                    }

                    if (! $subscription->student) {
                        continue;
                    }

                    $daysLeft = (int) now()->diffInDays($gracePeriodEndsAt, false);
                    if ($daysLeft < 0) {
                        continue;
                    }

                    if ($dryRun) {
                        $this->info("[DRY RUN] Would notify {$subscription->student->full_name} — Quran grace period ends in {$daysLeft} days ({$gracePeriodEndsAt->format('Y-m-d')})");
                        $count++;

                        continue;
                    }

                    try {
                        $subdomain = $subscription->academy?->subdomain ?? DefaultAcademy::subdomain();

                        $notificationService->send(
                            $subscription->student,
                            NotificationType::GRACE_PERIOD_EXPIRING,
                            [
                                'subscription_name' => 'حلقة '.($subscription->quranCircle?->name ?? 'القرآن'),
                                'days_left' => $daysLeft,
                                'grace_end_date' => $gracePeriodEndsAt->format('Y-m-d'),
                            ],
                            route('student.subscriptions', ['subdomain' => $subdomain]),
                            [
                                'subscription_id' => $subscription->id,
                            ],
                            $daysLeft <= 3 // Important if 3 days or less
                        );

                        // Also notify parents
                        $parentNotificationService->sendPaymentReminder($subscription);

                        $count++;
                        $this->line("  - Sent to {$subscription->student->full_name} (Quran — {$daysLeft} days left)");
                    } catch (Exception $e) {
                        $this->error("  - Failed for QuranSubscription {$subscription->id}: {$e->getMessage()}");
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
                    $metadata = $subscription->metadata ?? [];
                    if (! isset($metadata['grace_period_ends_at'])) {
                        continue;
                    }

                    $gracePeriodEndsAt = Carbon::parse($metadata['grace_period_ends_at']);
                    if ($gracePeriodEndsAt->isPast()) {
                        continue;
                    }

                    if (! $subscription->student) {
                        continue;
                    }

                    $daysLeft = (int) now()->diffInDays($gracePeriodEndsAt, false);
                    if ($daysLeft < 0) {
                        continue;
                    }

                    if ($dryRun) {
                        $this->info("[DRY RUN] Would notify {$subscription->student->full_name} — Academic grace period ends in {$daysLeft} days ({$gracePeriodEndsAt->format('Y-m-d')})");
                        $count++;

                        continue;
                    }

                    try {
                        $subdomain = $subscription->academy?->subdomain ?? DefaultAcademy::subdomain();

                        $notificationService->send(
                            $subscription->student,
                            NotificationType::GRACE_PERIOD_EXPIRING,
                            [
                                'subscription_name' => 'الاشتراك الأكاديمي',
                                'days_left' => $daysLeft,
                                'grace_end_date' => $gracePeriodEndsAt->format('Y-m-d'),
                            ],
                            route('student.subscriptions', ['subdomain' => $subdomain]),
                            ['subscription_id' => $subscription->id],
                            $daysLeft <= 3
                        );

                        // Also notify parents
                        $parentNotificationService->sendPaymentReminder($subscription);

                        $count++;
                        $this->line("  - Sent to {$subscription->student->full_name} (Academic — {$daysLeft} days left)");
                    } catch (Exception $e) {
                        $this->error("  - Failed for AcademicSubscription {$subscription->id}: {$e->getMessage()}");
                    }
                }
            });

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Done. Sent {$count} grace period expiry notification(s).");

        return 0;
    }
}
