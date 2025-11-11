<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CronJobLogger;
use Carbon\Carbon;

class CronJobMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cron:monitor 
                          {--hours=24 : Hours to look back for cron job executions}
                          {--job= : Monitor specific job only}
                          {--show-logs : Show recent log entries}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor cron job execution status and show recent activity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $specificJob = $this->option('job');
        $showLogs = $this->option('show-logs');

        $this->info("📊 Cron Jobs Monitor - Last {$hours} hours");
        $this->newLine();

        // Get cron job summary
        $summary = CronJobLogger::getRecentCronSummary($hours);

        if (empty($summary)) {
            $this->warn('⚠️  No cron job logs found');
            $this->comment('Make sure cron jobs are running and logging is enabled');
            return self::SUCCESS;
        }

        // Filter by specific job if requested
        if ($specificJob) {
            $summary = array_filter($summary, function($job) use ($specificJob) {
                return $job['job_name'] === $specificJob;
            });
            
            if (empty($summary)) {
                $this->error("❌ No logs found for job: {$specificJob}");
                return self::FAILURE;
            }
        }

        // Display summary table
        $this->displayJobSummary($summary, $hours);

        // Show detailed logs if requested
        if ($showLogs) {
            $this->newLine();
            $this->showRecentLogs($summary);
        }

        // Show recommendations
        $this->showRecommendations($summary, $hours);

        return self::SUCCESS;
    }

    /**
     * Display job summary in a table
     */
    private function displayJobSummary(array $summary, int $hours): void
    {
        $tableData = [];
        
        foreach ($summary as $job) {
            $status = $this->getJobStatus($job);
            $lastRun = $job['last_execution'] ? $job['last_execution']->diffForHumans() : 'Never';
            
            $tableData[] = [
                $job['job_name'],
                $status,
                $lastRun,
                $job['recent_executions'],
                $job['recent_errors'],
            ];
        }

        $this->table([
            'Job Name',
            'Status',
            'Last Execution',
            'Runs (' . $hours . 'h)',
            'Errors (' . $hours . 'h)'
        ], $tableData);
    }

    /**
     * Get job status icon and text
     */
    private function getJobStatus(array $job): string
    {
        if (!$job['last_execution']) {
            return '❓ Unknown';
        }

        $hoursSinceLastRun = now()->diffInHours($job['last_execution']);
        
        // If job hasn't run in more than 2 hours, it might be stuck
        if ($hoursSinceLastRun > 2) {
            return '🔴 Stale';
        }
        
        // If there are recent errors
        if ($job['recent_errors'] > 0) {
            return '⚠️  Issues';
        }
        
        // If job is running regularly
        if ($job['recent_executions'] > 0) {
            return '✅ Active';
        }
        
        return '⚪ Idle';
    }

    /**
     * Show recent log entries for jobs
     */
    private function showRecentLogs(array $summary): void
    {
        $this->info('📋 Recent Log Entries:');
        $this->newLine();

        foreach ($summary as $job) {
            if (!file_exists($job['log_file'])) {
                continue;
            }

            $this->comment("📄 {$job['job_name']}:");
            
            // Read last few lines
            $lines = $this->tail($job['log_file'], 5);
            foreach ($lines as $line) {
                // Clean up the log line for display
                $cleanLine = preg_replace('/^\[.*?\]\s*\w+\.\w+:\s*/', '', $line);
                $cleanLine = substr($cleanLine, 0, 120) . (strlen($cleanLine) > 120 ? '...' : '');
                $this->line("  {$cleanLine}");
            }
            $this->newLine();
        }
    }

    /**
     * Show recommendations based on job status
     */
    private function showRecommendations(array $summary, int $hours): void
    {
        $this->newLine();
        $this->info('💡 Recommendations:');
        
        $hasIssues = false;
        
        foreach ($summary as $job) {
            $status = $this->getJobStatus($job);
            
            if (str_contains($status, 'Stale')) {
                $this->warn("⚠️  {$job['job_name']} hasn't run recently - check if scheduler is working");
                $hasIssues = true;
            }
            
            if (str_contains($status, 'Issues') && $job['recent_errors'] > 0) {
                $this->warn("⚠️  {$job['job_name']} has {$job['recent_errors']} recent errors - check logs");
                $hasIssues = true;
            }
        }
        
        if (!$hasIssues) {
            $this->info('✅ All monitored cron jobs are running normally');
        }
        
        // General tips
        $this->comment('General tips:');
        $this->comment('• Run "php artisan schedule:list" to see all scheduled commands');
        $this->comment('• Run "php artisan schedule:run" to manually trigger scheduled jobs');
        $this->comment('• Check "* * * * * cd /path-to-your-project && php artisan schedule:run" in crontab');
        $this->comment('• Use --show-logs flag to see recent execution logs');
    }

    /**
     * Read last N lines of a file
     */
    private function tail(string $filepath, int $lines = 10): array
    {
        if (!file_exists($filepath)) {
            return [];
        }
        
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return [];
        }
        
        $lineArray = [];
        while (!feof($handle)) {
            $line = fgets($handle);
            if ($line) {
                $lineArray[] = rtrim($line);
            }
        }
        fclose($handle);
        
        return array_slice($lineArray, -$lines);
    }
}