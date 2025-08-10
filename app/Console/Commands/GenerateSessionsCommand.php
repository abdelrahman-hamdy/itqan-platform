<?php

namespace App\Console\Commands;

use App\Jobs\GenerateWeeklyScheduleSessions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sessions:generate 
                            {--queue : Run the job in the queue instead of synchronously}
                            {--weeks=4 : Number of weeks ahead to generate sessions for}';

    /**
     * The console command description.
     */
    protected $description = 'Generate sessions for all active schedules and circles';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $weeks = (int) $this->option('weeks');
        
        $this->info("🚀 Starting session generation for {$weeks} weeks ahead...");

        try {
            if ($this->option('queue')) {
                // Dispatch to queue
                GenerateWeeklyScheduleSessions::dispatch();
                $this->info('✅ Session generation job dispatched to queue');
            } else {
                // Run synchronously
                $job = new GenerateWeeklyScheduleSessions();
                $job->handle();
                $this->info('✅ Session generation completed synchronously');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Session generation failed: ' . $e->getMessage());
            Log::error('Session generation command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }
}