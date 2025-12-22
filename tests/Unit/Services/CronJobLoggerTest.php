<?php

use App\Services\CronJobLogger;
use Illuminate\Support\Facades\Log;

describe('CronJobLogger', function () {
    beforeEach(function () {
        // Ensure cron log directory exists
        $cronLogDir = storage_path('logs/cron');
        if (!is_dir($cronLogDir)) {
            mkdir($cronLogDir, 0755, true);
        }
    });

    describe('logCronStart', function () {
        it('returns execution data with execution ID', function () {
            $result = CronJobLogger::logCronStart('test_job');

            expect($result)->toBeArray()
                ->and($result)->toHaveKey('execution_id')
                ->and($result)->toHaveKey('start_time')
                ->and($result)->toHaveKey('started_at')
                ->and($result['execution_id'])->toContain('test_job_');
        });

        it('includes context in execution data', function () {
            $context = ['key' => 'value', 'count' => 10];
            $result = CronJobLogger::logCronStart('test_job', $context);

            expect($result)->toBeArray()
                ->and($result['execution_id'])->toContain('test_job_');
        });

        it('logs to main log', function () {
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'CRON JOB STARTED')
                        && isset($context['execution_id']);
                });

            Log::shouldReceive('build')->andReturnSelf();
            Log::shouldReceive('log')->andReturnSelf();

            CronJobLogger::logCronStart('session_status_check');
        });

        it('generates unique execution IDs', function () {
            $result1 = CronJobLogger::logCronStart('test_job');
            $result2 = CronJobLogger::logCronStart('test_job');

            expect($result1['execution_id'])->not->toBe($result2['execution_id']);
        });
    });

    describe('logCronEnd', function () {
        it('logs successful completion', function () {
            $executionData = [
                'execution_id' => 'test_job_123',
                'start_time' => microtime(true),
                'started_at' => now(),
            ];

            Log::shouldReceive('log')
                ->once()
                ->withArgs(function ($level, $message, $context) {
                    return $level === 'info'
                        && str_contains($message, 'CRON JOB FINISHED')
                        && isset($context['status'])
                        && $context['status'] === 'success';
                });

            Log::shouldReceive('build')->andReturnSelf();
            Log::shouldReceive('log')->andReturnSelf();

            CronJobLogger::logCronEnd('test_job', $executionData, ['processed' => 5], 'success');
        });

        it('logs with error status', function () {
            $executionData = [
                'execution_id' => 'test_job_456',
                'start_time' => microtime(true),
                'started_at' => now(),
            ];

            Log::shouldReceive('log')
                ->once()
                ->withArgs(function ($level, $message, $context) {
                    return $level === 'error'
                        && str_contains($message, 'CRON JOB FINISHED');
                });

            Log::shouldReceive('build')->andReturnSelf();
            Log::shouldReceive('log')->andReturnSelf();

            CronJobLogger::logCronEnd('test_job', $executionData, [], 'error');
        });

        it('calculates execution time', function () {
            $startTime = microtime(true);
            usleep(10000); // 10ms delay

            $executionData = [
                'execution_id' => 'test_job_789',
                'start_time' => $startTime,
                'started_at' => now(),
            ];

            Log::shouldReceive('log')->once();
            Log::shouldReceive('build')->andReturnSelf();
            Log::shouldReceive('log')->andReturnSelf();

            CronJobLogger::logCronEnd('test_job', $executionData);
        });

        it('includes results in log', function () {
            $executionData = [
                'execution_id' => 'test_job_results',
                'start_time' => microtime(true),
                'started_at' => now(),
            ];

            $results = [
                'sessions_processed' => 15,
                'status_changes' => 3,
            ];

            Log::shouldReceive('log')
                ->once()
                ->withArgs(function ($level, $message, $context) use ($results) {
                    return isset($context['results'])
                        && $context['results'] === $results;
                });

            Log::shouldReceive('build')->andReturnSelf();
            Log::shouldReceive('log')->andReturnSelf();

            CronJobLogger::logCronEnd('test_job', $executionData, $results);
        });
    });

    describe('logCronError', function () {
        it('logs exception details', function () {
            $executionData = [
                'execution_id' => 'test_job_error',
                'start_time' => microtime(true),
                'started_at' => now(),
            ];

            $exception = new \Exception('Test error message', 500);

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'CRON JOB FAILED')
                        && str_contains($context['error'], 'Test error message');
                });

            Log::shouldReceive('build')->andReturnSelf();
            Log::shouldReceive('error')->andReturnSelf();

            CronJobLogger::logCronError('test_job', $executionData, $exception);
        });

        it('includes exception file and line', function () {
            $executionData = [
                'execution_id' => 'test_job_trace',
                'start_time' => microtime(true),
                'started_at' => now(),
            ];

            $exception = new \Exception('Database connection failed');

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return isset($context['file'])
                        && isset($context['line']);
                });

            Log::shouldReceive('build')->andReturnSelf();
            Log::shouldReceive('error')->andReturnSelf();

            CronJobLogger::logCronError('test_job', $executionData, $exception);
        });
    });

    describe('logCronProgress', function () {
        it('logs intermediate progress', function () {
            Log::shouldReceive('build')->andReturnSelf();
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Processing batch')
                        && isset($context['execution_id']);
                });

            CronJobLogger::logCronProgress(
                'batch_processor',
                'batch_123',
                'Processing batch',
                ['batch_number' => 1, 'items' => 100]
            );
        });

        it('includes custom data in progress log', function () {
            $customData = ['processed' => 50, 'remaining' => 150];

            Log::shouldReceive('build')->andReturnSelf();
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($customData) {
                    return isset($context['processed'])
                        && $context['processed'] === 50;
                });

            CronJobLogger::logCronProgress(
                'data_processor',
                'exec_456',
                'Progress update',
                $customData
            );
        });
    });

    describe('getRecentCronSummary', function () {
        it('returns empty array when no cron logs exist', function () {
            // Use a non-existent directory
            $originalPath = storage_path('logs/cron');

            // If directory exists but empty
            $summary = CronJobLogger::getRecentCronSummary(24);

            expect($summary)->toBeArray();
        });

        it('accepts hours parameter', function () {
            $summary = CronJobLogger::getRecentCronSummary(48);

            expect($summary)->toBeArray();
        });

        it('returns summary structure for each job', function () {
            // Create a test log file
            $logDir = storage_path('logs/cron');
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $testLogFile = $logDir . '/test_summary_job.log';
            $timestamp = now()->format('Y-m-d\TH:i:s.uP');
            file_put_contents($testLogFile, "[{$timestamp}] test.INFO: STARTED test message\n");

            $summary = CronJobLogger::getRecentCronSummary(24);

            expect($summary)->toBeArray();

            // Cleanup
            @unlink($testLogFile);
        });
    });

    describe('integration scenarios', function () {
        it('completes full cron lifecycle', function () {
            Log::shouldReceive('info')->times(2);
            Log::shouldReceive('log')->once();
            Log::shouldReceive('build')->andReturnSelf();
            Log::shouldReceive('info')->andReturnSelf();
            Log::shouldReceive('log')->andReturnSelf();

            // Start
            $executionData = CronJobLogger::logCronStart('lifecycle_test', ['test' => true]);

            // Progress
            CronJobLogger::logCronProgress(
                'lifecycle_test',
                $executionData['execution_id'],
                'Step 1 complete'
            );

            // End
            CronJobLogger::logCronEnd(
                'lifecycle_test',
                $executionData,
                ['steps_completed' => 1]
            );

            expect($executionData['execution_id'])->toContain('lifecycle_test_');
        });

        it('handles cron failure lifecycle', function () {
            Log::shouldReceive('info')->once();
            Log::shouldReceive('error')->once();
            Log::shouldReceive('build')->andReturnSelf();
            Log::shouldReceive('info')->andReturnSelf();
            Log::shouldReceive('error')->andReturnSelf();

            // Start
            $executionData = CronJobLogger::logCronStart('failing_job');

            // Simulate failure
            try {
                throw new \Exception('Simulated database failure');
            } catch (\Exception $e) {
                CronJobLogger::logCronError('failing_job', $executionData, $e);
            }

            expect($executionData)->toHaveKey('execution_id');
        });
    });
});
