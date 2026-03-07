<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use Illuminate\Console\Command;

class CleanupStaleDeviceTokensCommand extends Command
{
    protected $signature = 'device-tokens:cleanup {--days=90 : Days of inactivity before token is considered stale}';

    protected $description = 'Remove stale device tokens that have not been used recently';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = DeviceToken::where(function ($query) use ($cutoff) {
            $query->where('last_used_at', '<', $cutoff)
                ->orWhere(function ($q) use ($cutoff) {
                    $q->whereNull('last_used_at')
                        ->where('created_at', '<', $cutoff);
                });
        })->delete();

        $this->info("Deleted {$deleted} stale device tokens (inactive for {$days}+ days).");

        return self::SUCCESS;
    }
}
