<?php

namespace App\Jobs;

use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateSessionMeetingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    public array $backoff = [30, 60, 120];

    public int $timeout = 60;

    /**
     * @param  string  $sessionType  Full class name: QuranSession::class or AcademicSession::class
     * @param  int  $sessionId
     * @param  bool  $regenerate  Clear existing meeting data before creating (used on reschedule)
     */
    public function __construct(
        public string $sessionType,
        public int $sessionId,
        public bool $regenerate = false,
    ) {
        $this->onQueue('meetings');
    }

    public function handle(): void
    {
        // Resolve the session model class once for reuse inside the transaction.
        $sessionClass = match (true) {
            $this->sessionType === AcademicSession::class => AcademicSession::class,
            $this->sessionType === InteractiveCourseSession::class => InteractiveCourseSession::class,
            default => QuranSession::class,
        };

        $session = $sessionClass::find($this->sessionId);

        if (! $session) {
            Log::warning('CreateSessionMeetingJob: session not found', [
                'session_type' => $this->sessionType,
                'session_id' => $this->sessionId,
            ]);

            return;
        }

        // Wrap the existence-check + creation in a transaction with a row-level lock
        // to prevent TOCTOU race conditions when multiple job workers run concurrently.
        DB::transaction(function () use ($sessionClass) {
            /** @var \App\Models\BaseSession $lockedSession */
            $lockedSession = $sessionClass::lockForUpdate()->find($this->sessionId);

            if (! $lockedSession) {
                Log::warning('CreateSessionMeetingJob: session disappeared inside transaction', [
                    'session_id' => $this->sessionId,
                ]);

                return;
            }

            if ($this->regenerate) {
                // Clear stale meeting data so generateMeetingLink() creates a fresh room.
                $lockedSession->meeting_room_name = null;
                $lockedSession->meeting_link = null;
                $lockedSession->meeting_id = null;
                $lockedSession->meeting_data = null;
                $lockedSession->meeting_expires_at = null;
                $lockedSession->saveQuietly();
            } elseif (! empty($lockedSession->meeting_room_name)) {
                // Meeting was already created (e.g. by the cron job before this job ran).
                Log::debug('CreateSessionMeetingJob: meeting already exists', [
                    'session_id' => $this->sessionId,
                ]);

                return;
            }

            $lockedSession->generateMeetingLink();

            Log::info('CreateSessionMeetingJob: meeting created', [
                'session_id' => $this->sessionId,
                'room_name' => $lockedSession->meeting_room_name,
            ]);
        });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('CreateSessionMeetingJob failed permanently', [
            'session_type' => $this->sessionType,
            'session_id' => $this->sessionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
