<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Enums\SubscriptionStatus;
use App\Models\QuranSubscription;
use App\Models\AcademicSubscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckExpiringSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expiring';
    protected $description = 'Send notifications for subscriptions expiring soon (7, 3, and 1 days before)';

    public function handle()
    {
        $notificationService = app(NotificationService::class);
        $parentNotificationService = app(\App\Services\ParentNotificationService::class);
        $count = 0;

        $this->info('Checking for expiring subscriptions...');

        // Check subscriptions expiring in 7 days, 3 days, and 1 day
        foreach ([7, 3, 1] as $days) {
            $targetDate = now()->addDays($days)->startOfDay();
            $endDate = $targetDate->copy()->endOfDay();

            $this->info("Checking subscriptions expiring in {$days} days...");

            // Quran Subscriptions
            $quranSubs = QuranSubscription::whereBetween('end_date', [$targetDate, $endDate])
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->with(['student', 'quranCircle'])
                ->get();

            foreach ($quranSubs as $subscription) {
                try {
                    if (!$subscription->student) {
                        continue;
                    }

                    $notificationService->send(
                        $subscription->student,
                        NotificationType::SUBSCRIPTION_EXPIRING,
                        [
                            'subscription_name' => 'حلقة ' . ($subscription->quranCircle?->name ?? 'القرآن'),
                            'days_left' => $days,
                            'expiry_date' => $subscription->end_date->format('Y-m-d'),
                        ],
                        "/circles/{$subscription->quran_circle_id}",
                        [
                            'subscription_id' => $subscription->id,
                            'circle_id' => $subscription->quran_circle_id,
                        ],
                        $days <= 3  // Important if 3 days or less
                    );

                    // Also notify parents
                    $parentNotificationService->sendPaymentReminder($subscription);

                    $count++;

                    $this->line("  - Sent to {$subscription->student->full_name} (Quran - {$days} days)");
                } catch (\Exception $e) {
                    $this->error("  - Failed for Quran subscription {$subscription->id}: {$e->getMessage()}");
                }
            }

            // Academic Subscriptions
            $academicSubs = AcademicSubscription::whereBetween('end_date', [$targetDate, $endDate])
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->with(['student'])
                ->get();

            foreach ($academicSubs as $subscription) {
                try {
                    if (!$subscription->student) {
                        continue;
                    }

                    $notificationService->send(
                        $subscription->student,
                        NotificationType::SUBSCRIPTION_EXPIRING,
                        [
                            'subscription_name' => 'الاشتراك الأكاديمي',
                            'days_left' => $days,
                            'expiry_date' => $subscription->end_date->format('Y-m-d'),
                        ],
                        "/academic-subscriptions/{$subscription->id}",
                        ['subscription_id' => $subscription->id],
                        $days <= 3
                    );

                    // Also notify parents
                    $parentNotificationService->sendPaymentReminder($subscription);

                    $count++;

                    $this->line("  - Sent to {$subscription->student->full_name} (Academic - {$days} days)");
                } catch (\Exception $e) {
                    $this->error("  - Failed for Academic subscription {$subscription->id}: {$e->getMessage()}");
                }
            }
        }

        $this->info("✓ Sent {$count} subscription expiry notifications.");
        return 0;
    }
}
