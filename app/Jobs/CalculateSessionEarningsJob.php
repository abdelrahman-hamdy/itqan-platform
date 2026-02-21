<?php

namespace App\Jobs;

use Exception;
use Throwable;
use App\Models\BaseSession;
use App\Services\EarningsCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateSessionEarningsJob implements ShouldQueue
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
     * The session instance.
     *
     * @var BaseSession
     */
    public $session;

    /**
     * The session type (for polymorphic handling).
     *
     * @var string
     */
    public $sessionType;

    /**
     * The session ID.
     *
     * @var int
     */
    public $sessionId;

    /**
     * Create a new job instance.
     */
    public function __construct(BaseSession $session)
    {
        $this->sessionType = get_class($session);
        $this->sessionId = $session->id;

        // Store the session for immediate use
        $this->session = $session;
    }

    /**
     * Execute the job.
     */
    public function handle(EarningsCalculationService $earningsService): void
    {
        try {
            // Re-fetch the session to ensure we have the latest data
            $sessionClass = $this->sessionType;
            $session = $sessionClass::find($this->sessionId);

            if (! $session) {
                Log::warning('Session not found for earnings calculation', [
                    'session_type' => $this->sessionType,
                    'session_id' => $this->sessionId,
                ]);

                return;
            }

            // Calculate earnings
            $earning = $earningsService->calculateSessionEarnings($session);

            if ($earning) {
                Log::info('Session earnings calculated via job', [
                    'earning_id' => $earning->id,
                    'session_id' => $this->sessionId,
                    'session_type' => $this->sessionType,
                    'amount' => $earning->amount,
                ]);
            } else {
                Log::info('No earnings calculated for session (not eligible or already calculated)', [
                    'session_id' => $this->sessionId,
                    'session_type' => $this->sessionType,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to calculate session earnings', [
                'session_id' => $this->sessionId,
                'session_type' => $this->sessionType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('CalculateSessionEarningsJob failed permanently', [
            'session_id' => $this->sessionId,
            'session_type' => $this->sessionType,
            'error' => $exception->getMessage(),
        ]);
    }
}
