<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CronJobLogger
{
    /**
     * Log cron job execution with structured data
     */
    public static function logCronStart(string $jobName, array $context = []): array
    {
        $executionId = uniqid("{$jobName}_", true);
        $startTime = microtime(true);
        $timestamp = now();
        
        $logData = [
            'execution_id' => $executionId,
            'job_name' => $jobName,
            'started_at' => $timestamp->toISOString(),
            'start_time' => $startTime,
            'context' => $context,
        ];
        
        // Log to dedicated cron log file
        Log::build([
            'driver' => 'single',
            'path' => storage_path("logs/cron/{$jobName}.log"),
            'level' => 'debug',
            'replace_placeholders' => true,
        ])->info("🚀 [{$jobName}] STARTED", $logData);
        
        // Also log to main log
        Log::info("🚀 CRON JOB STARTED: {$jobName}", [
            'execution_id' => $executionId,
            'context' => $context
        ]);
        
        return [
            'execution_id' => $executionId,
            'start_time' => $startTime,
            'started_at' => $timestamp,
        ];
    }
    
    /**
     * Log cron job completion with results
     */
    public static function logCronEnd(string $jobName, array $executionData, array $results = [], ?string $status = 'success'): void
    {
        $endTime = microtime(true);
        $executionTime = round($endTime - $executionData['start_time'], 2);
        $timestamp = now();
        
        $logData = [
            'execution_id' => $executionData['execution_id'],
            'job_name' => $jobName,
            'status' => $status,
            'started_at' => $executionData['started_at']->toISOString(),
            'finished_at' => $timestamp->toISOString(),
            'execution_time_seconds' => $executionTime,
            'results' => $results,
        ];
        
        $icon = $status === 'success' ? '✅' : ($status === 'error' ? '❌' : '⚠️');
        $level = $status === 'error' ? 'error' : 'info';
        
        // Log to dedicated cron log file
        Log::build([
            'driver' => 'single',
            'path' => storage_path("logs/cron/{$jobName}.log"),
            'level' => 'debug',
            'replace_placeholders' => true,
        ])->log($level, "{$icon} [{$jobName}] FINISHED in {$executionTime}s", $logData);
        
        // Also log to main log
        Log::log($level, "{$icon} CRON JOB FINISHED: {$jobName} ({$executionTime}s)", [
            'execution_id' => $executionData['execution_id'],
            'status' => $status,
            'execution_time' => $executionTime,
            'results' => $results
        ]);
    }
    
    /**
     * Log cron job error
     */
    public static function logCronError(string $jobName, array $executionData, \Exception $exception): void
    {
        $endTime = microtime(true);
        $executionTime = round($endTime - $executionData['start_time'], 2);
        $timestamp = now();
        
        $logData = [
            'execution_id' => $executionData['execution_id'],
            'job_name' => $jobName,
            'status' => 'error',
            'started_at' => $executionData['started_at']->toISOString(),
            'failed_at' => $timestamp->toISOString(),
            'execution_time_seconds' => $executionTime,
            'error' => [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ],
        ];
        
        // Log to dedicated cron log file
        Log::build([
            'driver' => 'single',
            'path' => storage_path("logs/cron/{$jobName}.log"),
            'level' => 'debug',
            'replace_placeholders' => true,
        ])->error("❌ [{$jobName}] FAILED after {$executionTime}s: {$exception->getMessage()}", $logData);
        
        // Also log to main log
        Log::error("❌ CRON JOB FAILED: {$jobName} ({$executionTime}s)", [
            'execution_id' => $executionData['execution_id'],
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
    
    /**
     * Log intermediate progress during cron job execution
     */
    public static function logCronProgress(string $jobName, string $executionId, string $message, array $data = []): void
    {
        $logData = array_merge([
            'execution_id' => $executionId,
            'job_name' => $jobName,
            'timestamp' => now()->toISOString(),
        ], $data);
        
        // Log to dedicated cron log file
        Log::build([
            'driver' => 'single',
            'path' => storage_path("logs/cron/{$jobName}.log"),
            'level' => 'debug',
            'replace_placeholders' => true,
        ])->info("📊 [{$jobName}] {$message}", $logData);
    }
    
    /**
     * Create a summary report of recent cron job executions
     */
    public static function getRecentCronSummary(int $hours = 24): array
    {
        $cronLogDir = storage_path('logs/cron');
        $summary = [];
        
        if (!is_dir($cronLogDir)) {
            return $summary;
        }
        
        $logFiles = glob($cronLogDir . '/*.log');
        
        foreach ($logFiles as $logFile) {
            $jobName = basename($logFile, '.log');
            $summary[$jobName] = [
                'job_name' => $jobName,
                'log_file' => $logFile,
                'last_execution' => null,
                'recent_executions' => 0,
                'recent_errors' => 0,
            ];
            
            // Read recent lines (last 100)
            $lines = self::tail($logFile, 100);
            $cutoff = now()->subHours($hours);
            
            foreach ($lines as $line) {
                if (preg_match('/\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[^\]]*)\]/', $line, $matches)) {
                    try {
                        $lineTime = Carbon::parse($matches[1]);
                        if ($lineTime->isAfter($cutoff)) {
                            if (strpos($line, 'STARTED') !== false || strpos($line, 'FINISHED') !== false) {
                                $summary[$jobName]['recent_executions']++;
                                if (!$summary[$jobName]['last_execution'] || $lineTime->isAfter($summary[$jobName]['last_execution'])) {
                                    $summary[$jobName]['last_execution'] = $lineTime;
                                }
                            } elseif (strpos($line, 'FAILED') !== false || strpos($line, 'ERROR') !== false) {
                                $summary[$jobName]['recent_errors']++;
                            }
                        }
                    } catch (\Exception $e) {
                        // Skip unparseable dates
                    }
                }
            }
        }
        
        return $summary;
    }
    
    /**
     * Simple tail implementation for reading last N lines of a file
     */
    private static function tail(string $filepath, int $lines = 100): array
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
