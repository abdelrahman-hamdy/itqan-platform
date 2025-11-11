<?php

namespace App\Console\Commands;

use App\Jobs\CleanupExpiredTokens;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tokens:cleanup 
                            {--queue : Run the job in the queue instead of synchronously}
                            {--dry-run : Show what would be cleaned without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up expired Google tokens and refresh expiring ones';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('dry-run')) {
            return $this->dryRun();
        }

        $this->info('🚀 Starting Google token cleanup...');

        try {
            if ($this->option('queue')) {
                // Dispatch to queue
                CleanupExpiredTokens::dispatch();
                $this->info('✅ Token cleanup job dispatched to queue');
            } else {
                // Run synchronously
                $job = new CleanupExpiredTokens();
                $job->handle();
                $this->info('✅ Token cleanup completed synchronously');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Token cleanup failed: ' . $e->getMessage());
            Log::error('Token cleanup command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Run in dry-run mode
     */
    private function dryRun(): int
    {
        $this->info('🔍 Running token cleanup in dry-run mode...');

        try {
            // Get tokens that would be processed
            $expiredTokens = \App\Models\GoogleToken::where('token_status', 'expired')
                ->where('updated_at', '<', now()->subDays(30))
                ->count();

            $tokensNeedingRefresh = \App\Models\GoogleToken::where('token_status', 'active')
                ->where('expires_at', '<', now()->addMinutes(30))
                ->count();

            $platformAccountsNeedingRefresh = \App\Models\PlatformGoogleAccount::active()
                ->where('expires_at', '<', now()->addMinutes(30))
                ->count();

            $this->table(['Item', 'Count'], [
                ['Expired tokens to delete', $expiredTokens],
                ['User tokens needing refresh', $tokensNeedingRefresh],
                ['Platform accounts needing refresh', $platformAccountsNeedingRefresh],
            ]);

            $this->info('✅ Dry-run completed. Use --queue or remove --dry-run to execute changes.');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Dry-run failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}