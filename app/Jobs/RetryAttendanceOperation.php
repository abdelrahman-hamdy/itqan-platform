<?php

namespace App\Jobs;

use Exception;
use Throwable;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\MeetingAttendanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryAttendanceOperation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 2;

    /**
     * The number of seconds to wait before retrying with exponential backoff.
     */
    public array $backoff = [30, 60, 120];

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $sessionId,
        public string $sessionType,
        public int $userId,
        public string $operation, // 'join' or 'leave'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MeetingAttendanceService $service): void
    {
        Log::info('Retrying attendance operation', [
            'session_id' => $this->sessionId,
            'session_type' => $this->sessionType,
            'user_id' => $this->userId,
            'operation' => $this->operation,
            'attempt' => $this->attempts(),
        ]);

        // Get session polymorphically — handle both legacy string keys and full class names
        $session = match (true) {
            $this->sessionType === 'academic' || $this->sessionType === AcademicSession::class => AcademicSession::find($this->sessionId),
            default => QuranSession::find($this->sessionId),
        };

        $user = User::find($this->userId);

        if (! $session) {
            Log::error('Session not found for retry', [
                'session_id' => $this->sessionId,
                'session_type' => $this->sessionType,
            ]);

            return;
        }

        if (! $user) {
            Log::error('User not found for retry', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        try {
            if ($this->operation === 'join') {
                $success = $service->handleUserJoin($session, $user);
            } elseif ($this->operation === 'leave') {
                $success = $service->handleUserLeave($session, $user);
            } else {
                Log::error('Invalid operation type', [
                    'operation' => $this->operation,
                ]);

                return;
            }

            if ($success) {
                Log::info('Attendance operation retry successful', [
                    'session_id' => $this->sessionId,
                    'user_id' => $this->userId,
                    'operation' => $this->operation,
                    'attempt' => $this->attempts(),
                ]);
            } else {
                Log::warning('Attendance operation retry returned false', [
                    'session_id' => $this->sessionId,
                    'user_id' => $this->userId,
                    'operation' => $this->operation,
                    'attempt' => $this->attempts(),
                ]);

                // Throw exception to trigger another retry
                throw new Exception('Attendance operation failed');
            }
        } catch (Exception $e) {
            Log::error('Attendance operation retry failed', [
                'session_id' => $this->sessionId,
                'user_id' => $this->userId,
                'operation' => $this->operation,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger another retry (up to $tries)
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('RetryAttendanceOperation job failed permanently', [
            'session_id' => $this->sessionId,
            'session_type' => $this->sessionType,
            'user_id' => $this->userId,
            'operation' => $this->operation,
            'error' => $exception->getMessage(),
        ]);
    }
}
