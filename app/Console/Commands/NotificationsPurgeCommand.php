<?php

namespace App\Console\Commands;

use App\Contracts\NotificationServiceInterface;
use App\Models\Academy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotificationsPurgeCommand extends Command
{
    protected $signature = 'notifications:purge
                          {--days=90 : Delete read notifications older than this many days}
                          {--academy= : Only purge for a specific academy ID}';

    protected $description = 'Delete old read notifications to prevent table bloat';

    public function __construct(private readonly NotificationServiceInterface $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $academyId = $this->option('academy') ? (int) $this->option('academy') : null;

        if ($academyId) {
            $count = $this->notificationService->deleteOldReadNotifications($days, $academyId);
            $this->info("Purged {$count} notifications for academy {$academyId}.");
            Log::info("notifications:purge — academy={$academyId}, days={$days}, deleted={$count}");

            return self::SUCCESS;
        }

        // Iterate over all academies
        $total = 0;
        Academy::query()->select('id')->each(function (Academy $academy) use ($days, &$total) {
            $count = $this->notificationService->deleteOldReadNotifications($days, $academy->id);
            $total += $count;
            if ($count > 0) {
                $this->line("  Academy {$academy->id}: deleted {$count} notifications.");
            }
        });

        $this->info("Total purged: {$total} notifications older than {$days} days.");
        Log::info("notifications:purge — all academies, days={$days}, deleted={$total}");

        return self::SUCCESS;
    }
}
