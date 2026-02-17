<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

/**
 * Health Check Controller
 *
 * Provides endpoints for monitoring application health.
 * Used by load balancers, monitoring systems, and deployment pipelines.
 */
class HealthCheckController extends Controller
{
    /**
     * Basic liveness check - is the application running?
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness check - is the application ready to serve traffic?
     * Checks all critical dependencies.
     */
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        // Only check Redis if it's configured
        if (config('cache.default') === 'redis' || config('queue.default') === 'redis') {
            $checks['redis'] = $this->checkRedis();
        }

        $allHealthy = collect($checks)->every(fn ($check) => $check['healthy']);

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Detailed health check with all services.
     */
    public function health(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = collect($checks)->filter(fn ($check) => $check['healthy'])->count();
        $total = count($checks);
        $allHealthy = $healthy === $total;

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'version' => config('app.version', '1.0.0'),
            'checks' => $checks,
            'summary' => [
                'healthy' => $healthy,
                'total' => $total,
            ],
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Database health check.
     */
    public function database(): JsonResponse
    {
        $check = $this->checkDatabase();

        return response()->json([
            'status' => $check['healthy'] ? 'ok' : 'error',
            'timestamp' => now()->toIso8601String(),
            ...$check,
        ], $check['healthy'] ? 200 : 503);
    }

    /**
     * Redis health check.
     */
    public function redis(): JsonResponse
    {
        $check = $this->checkRedis();

        return response()->json([
            'status' => $check['healthy'] ? 'ok' : 'error',
            'timestamp' => now()->toIso8601String(),
            ...$check,
        ], $check['healthy'] ? 200 : 503);
    }

    /**
     * Queue health check.
     */
    public function queue(): JsonResponse
    {
        $check = $this->checkQueue();

        return response()->json([
            'status' => $check['healthy'] ? 'ok' : 'error',
            'timestamp' => now()->toIso8601String(),
            ...$check,
        ], $check['healthy'] ? 200 : 503);
    }

    /**
     * Storage health check.
     */
    public function storage(): JsonResponse
    {
        $check = $this->checkStorage();

        return response()->json([
            'status' => $check['healthy'] ? 'ok' : 'error',
            'timestamp' => now()->toIso8601String(),
            ...$check,
        ], $check['healthy'] ? 200 : 503);
    }

    /**
     * Check database connectivity.
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'healthy' => true,
                'message' => 'Database connection successful',
                'latency_ms' => $latency,
                'driver' => config('database.default'),
            ];
        } catch (Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Database connection failed',
                'error' => app()->environment('local') ? $e->getMessage() : 'Connection error',
            ];
        }
    }

    /**
     * Check cache connectivity.
     */
    private function checkCache(): array
    {
        try {
            $key = 'health_check_'.uniqid();
            $start = microtime(true);

            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($value !== 'test') {
                throw new Exception('Cache read/write mismatch');
            }

            return [
                'healthy' => true,
                'message' => 'Cache is working',
                'latency_ms' => $latency,
                'driver' => config('cache.default'),
            ];
        } catch (Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Cache check failed',
                'error' => app()->environment('local') ? $e->getMessage() : 'Cache error',
                'driver' => config('cache.default'),
            ];
        }
    }

    /**
     * Check Redis connectivity.
     */
    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            $pong = Redis::ping();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'healthy' => true,
                'message' => 'Redis connection successful',
                'latency_ms' => $latency,
                'response' => $pong,
            ];
        } catch (Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Redis connection failed',
                'error' => app()->environment('local') ? $e->getMessage() : 'Connection error',
            ];
        }
    }

    /**
     * Check queue health.
     */
    private function checkQueue(): array
    {
        try {
            $driver = config('queue.default');

            // For sync driver, it's always "healthy" but warn about production
            if ($driver === 'sync') {
                return [
                    'healthy' => true,
                    'message' => 'Queue is using sync driver (not recommended for production)',
                    'driver' => $driver,
                    'warning' => 'Jobs execute synchronously - use redis or database driver in production',
                ];
            }

            // For Redis queue, check connection
            if ($driver === 'redis') {
                $start = microtime(true);
                $size = Queue::size();
                $latency = round((microtime(true) - $start) * 1000, 2);

                return [
                    'healthy' => true,
                    'message' => 'Queue connection successful',
                    'driver' => $driver,
                    'queue_size' => $size,
                    'latency_ms' => $latency,
                ];
            }

            // For database queue
            if ($driver === 'database') {
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();

                return [
                    'healthy' => true,
                    'message' => 'Queue connection successful',
                    'driver' => $driver,
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                ];
            }

            return [
                'healthy' => true,
                'message' => 'Queue configured',
                'driver' => $driver,
            ];
        } catch (Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Queue check failed',
                'error' => app()->environment('local') ? $e->getMessage() : 'Queue error',
                'driver' => config('queue.default'),
            ];
        }
    }

    /**
     * Check storage health.
     */
    private function checkStorage(): array
    {
        try {
            $disk = config('filesystems.default');
            $testFile = 'health_check_'.uniqid().'.txt';

            $start = microtime(true);
            Storage::put($testFile, 'health check');
            $content = Storage::get($testFile);
            Storage::delete($testFile);
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($content !== 'health check') {
                throw new Exception('Storage read/write mismatch');
            }

            return [
                'healthy' => true,
                'message' => 'Storage is working',
                'latency_ms' => $latency,
                'disk' => $disk,
            ];
        } catch (Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Storage check failed',
                'error' => app()->environment('local') ? $e->getMessage() : 'Storage error',
                'disk' => config('filesystems.default'),
            ];
        }
    }
}
