<?php

namespace App\Console\Commands;

use App\Services\Subscription\ExpiryReminderService;
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

        return self::SUCCESS;
    }
}
