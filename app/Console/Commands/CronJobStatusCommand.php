<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use App\Services\CronJobLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CronJobStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cron:status 
                          {--hours=24 : Number of hours to look back for job executions}
                          {--job= : Show details for a specific job name}
                          {--errors-only : Show only jobs with recent errors}
                          {--export= : Export detailed logs to file}';

    /**
     * The console command description.
     */
    protected $description = 'Display comprehensive status and logs for all cron jobs';

    private CronJobLogger $cronJobLogger;

    public function __construct(CronJobLogger $cronJobLogger)
    {
        parent::__construct();
        $this->cronJobLogger = $cronJobLogger;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $specificJob = $this->option('job');
        $errorsOnly = $this->option('errors-only');
        $exportFile = $this->option('export');

        $this->info("📊 Cron Job Status Report (Last {$hours} hours)");
        $this->info('═══════════════════════════════════════════════════');

        $summary = $this->cronJobLogger->getRecentCronSummary($hours);

        if (empty($summary)) {
            $this->warn('⚠️  No cron job logs found in the specified timeframe.');

            return self::SUCCESS;
        }

        if ($specificJob) {
            return $this->showJobDetails($specificJob, $hours);
        }

        if ($errorsOnly) {
            $summary = array_filter($summary, fn ($job) => $job['recent_errors'] > 0);
        }

        $this->displaySummaryTable($summary);
        $this->displayRecommendations($summary);

        if ($exportFile) {
            $this->exportDetailedLogs($summary, $exportFile, $hours);
        }

        return self::SUCCESS;
    }

    /**
     * Display summary table of all cron jobs
     */
    private function displaySummaryTable(array $summary): void
    {
        $tableData = [];
        $totalJobs = 0;
        $totalErrors = 0;
        $totalExecutions = 0;

        foreach ($summary as $job) {
            $totalJobs++;
            $totalErrors += $job['recent_errors'];
            $totalExecutions += $job['recent_executions'];

            $status = $this->getJobStatus($job);
            $lastExecution = $job['last_execution']
                ? $job['last_execution']->diffForHumans()
                : 'Never';

            $tableData[] = [
                $job['job_name'],
                $status,
                $job['recent_executions'],
                $job['recent_errors'],
                $lastExecution,
            ];
        }

        $this->table([
            'Job Name',
            'Status',
            'Executions',
            'Errors',
            'Last Run',
        ], $tableData);

        $this->info("\n📈 Summary:");
        $this->info("• Total Jobs: {$totalJobs}");
        $this->info("• Total Executions: {$totalExecutions}");
        $this->info("• Total Errors: {$totalErrors}");

        if ($totalExecutions > 0) {
            $errorRate = round(($totalErrors / $totalExecutions) * 100, 2);
            $this->info("• Error Rate: {$errorRate}%");
        }
    }

    /**
     * Get visual status for a job
     */
    private function getJobStatus(array $job): string
    {
        if ($job['recent_errors'] > 0) {
            return '🔴 Errors';
        }

        if ($job['recent_executions'] === 0) {
            return '⚪ Inactive';
        }

        if ($job['last_execution'] && $job['last_execution']->isAfter(now()->subHour())) {
            return '🟢 Active';
        }

        return '🟡 Stale';
    }

    /**
     * Show detailed information for a specific job
     */
    private function showJobDetails(string $jobName, int $hours): int
    {
        $logFile = storage_path("logs/cron/{$jobName}.log");

        if (! File::exists($logFile)) {
            $this->error("❌ No log file found for job: {$jobName}");

            return self::FAILURE;
        }

        $this->info("🔍 Detailed logs for: {$jobName}");
        $this->info('═══════════════════════════════════════════════════');

        $logs = $this->getRecentLogs($logFile, $hours);

        if (empty($logs)) {
            $this->warn("⚠️  No recent logs found for {$jobName}");

            return self::SUCCESS;
        }

        foreach ($logs as $log) {
            $this->displayLogEntry($log);
        }

        return self::SUCCESS;
    }

    /**
     * Get recent log entries from a file
     */
    private function getRecentLogs(string $logFile, int $hours): array
    {
        $lines = $this->cronJobLogger->tail($logFile, 200);
        $cutoff = now()->subHours($hours);
        $logs = [];

        foreach ($lines as $line) {
            if (preg_match('/\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[^\]]*)\]\s+(.+?)\.\w+:\s+(.+)/', $line, $matches)) {
                try {
                    $timestamp = Carbon::parse($matches[1]);
                    if ($timestamp->isAfter($cutoff)) {
                        $logs[] = [
                            'timestamp' => $timestamp,
                            'level' => $matches[2],
                            'message' => $matches[3],
                            'full_line' => $line,
                        ];
                    }
                } catch (Exception $e) {
                    // Skip invalid timestamps
                }
            }
        }

        return array_reverse($logs); // Show newest first
    }

    /**
     * Display a single log entry
     */
    private function displayLogEntry(array $log): void
    {
        $icon = $this->getLogIcon($log['level']);
        $time = $log['timestamp']->format('Y-m-d H:i:s');

        $this->line("$icon [{$time}] {$log['message']}");
    }

    /**
     * Get icon for log level
     */
    private function getLogIcon(string $level): string
    {
        return match (strtolower($level)) {
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            'debug' => '🔍',
            default => '📝'
        };
    }

    /**
     * Display recommendations based on job analysis
     */
    private function displayRecommendations(array $summary): void
    {
        $this->info("\n💡 Recommendations:");

        $hasErrors = false;
        $hasStaleJobs = false;
        $hasInactiveJobs = false;

        foreach ($summary as $job) {
            if ($job['recent_errors'] > 0) {
                $hasErrors = true;
                $this->warn("• Check logs for {$job['job_name']} - {$job['recent_errors']} recent errors");
            }

            if ($job['recent_executions'] === 0) {
                $hasInactiveJobs = true;
                $this->warn("• {$job['job_name']} hasn't run recently - check schedule");
            } elseif ($job['last_execution'] && $job['last_execution']->isBefore(now()->subHours(2))) {
                $hasStaleJobs = true;
                $this->warn("• {$job['job_name']} last ran {$job['last_execution']->diffForHumans()} - may be stale");
            }
        }

        if (! $hasErrors && ! $hasStaleJobs && ! $hasInactiveJobs) {
            $this->info('• ✅ All cron jobs appear to be running normally!');
        }

        $this->info("\n🔧 Useful Commands:");
        $this->info('• php artisan cron:status --job=<name> - View specific job details');
        $this->info('• php artisan cron:status --errors-only - Show only problematic jobs');
        $this->info('• php artisan schedule:run - Run scheduled tasks manually');
        $this->info('• php artisan schedule:list - List all scheduled commands');
    }

    /**
     * Export detailed logs to a file
     */
    private function exportDetailedLogs(array $summary, string $exportFile, int $hours): void
    {
        $this->info("\n📄 Exporting detailed logs to: {$exportFile}");

        $content = [];
        $content[] = '=== CRON JOB STATUS REPORT ===';
        $content[] = 'Generated: '.now()->format('Y-m-d H:i:s');
        $content[] = "Time Range: Last {$hours} hours";
        $content[] = '';

        foreach ($summary as $job) {
            $content[] = "--- {$job['job_name']} ---";
            $content[] = "Recent Executions: {$job['recent_executions']}";
            $content[] = "Recent Errors: {$job['recent_errors']}";
            $content[] = 'Last Execution: '.($job['last_execution']
                ? $job['last_execution']->format('Y-m-d H:i:s')
                : 'Never');
            $content[] = '';

            // Add recent logs
            $logFile = $job['log_file'];
            if (File::exists($logFile)) {
                $logs = $this->getRecentLogs($logFile, $hours);
                foreach ($logs as $log) {
                    $content[] = "[{$log['timestamp']->format('Y-m-d H:i:s')}] {$log['message']}";
                }
            }
            $content[] = '';
        }

        File::put($exportFile, implode("\n", $content));
        $this->info('✅ Export completed!');
    }
}
