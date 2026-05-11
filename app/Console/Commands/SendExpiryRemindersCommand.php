<?php

namespace App\Console\Commands;

use App\Services\Subscription\ExpiryReminderService;
use App\Services\Subscription\SubscriptionFailureCounter;
use Illuminate\Console\Command;

class SendExpiryRemindersCommand extends Command
{
    protected $signature = 'subscriptions:send-expiry-reminders';

    protected $description = 'Send reminders for subscriptions expiring in 7, 3, or 1 days';

    public function handle(ExpiryReminderService $service): int
    {
        $this->info('Sending subscription expiry reminders...');

        $stats = $service->sendReminders();

        $this->info("Done. Sent: {$stats['sent']}, Skipped: {$stats['skipped']}, Errors: {$stats['errors']}");

        $this->maybeAlertRenewalFailures();

        return self::SUCCESS;
    }

    private function maybeAlertRenewalFailures(): void
    {
        $threshold = (int) config('telegram.renewal_failure_threshold', 5);

        $totals = [
            'today' => SubscriptionFailureCounter::countFor(),
            'yesterday' => SubscriptionFailureCounter::countFor(now()->subDay()->toDateString()),
        ];

        $worst = max($totals);

        if ($worst <= $threshold) {
            return;
        }

        alert_telegram(
            'medium',
            'subscription-renewal',
            "Renewal failures past 24h — today={$totals['today']} yesterday={$totals['yesterday']} (threshold={$threshold})"
        );
    }
}
