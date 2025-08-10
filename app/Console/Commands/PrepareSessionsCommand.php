<?php

namespace App\Console\Commands;

use App\Jobs\PrepareUpcomingSessions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PrepareSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sessions:prepare 
                            {--queue : Run the job in the queue instead of synchronously}
                            {--force : Force preparation even if already completed}';

    /**
     * The console command description.
     */
    protected $description = 'Prepare upcoming sessions by creating meeting links and sending notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting session preparation...');

        try {
            if ($this->option('queue')) {
                // Dispatch to queue
                PrepareUpcomingSessions::dispatch();
                $this->info('✅ Session preparation job dispatched to queue');
            } else {
                // Run synchronously
                $job = new PrepareUpcomingSessions();
                $job->handle();
                $this->info('✅ Session preparation completed synchronously');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Session preparation failed: ' . $e->getMessage());
            Log::error('Session preparation command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }
}