<?php

namespace App\Jobs;

use App\Models\AcademicSession;
use App\Models\QuranSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
        $session = match (true) {
            $this->sessionType === AcademicSession::class => AcademicSession::find($this->sessionId),
            default => QuranSession::find($this->sessionId),
        };

        if (! $session) {
            Log::warning('CreateSessionMeetingJob: session not found', [
                'session_type' => $this->sessionType,
                'session_id' => $this->sessionId,
            ]);

            return;
        }

        if ($this->regenerate) {
            // Clear stale meeting data so generateMeetingLink() creates a fresh room
            $session->meeting_room_name = null;
            $session->meeting_link = null;
            $session->meeting_id = null;
            $session->meeting_data = null;
            $session->meeting_expires_at = null;
            $session->saveQuietly();
        } elseif (! empty($session->meeting_room_name)) {
            // Meeting was already created (e.g. by the cron job before this job ran)
            Log::debug('CreateSessionMeetingJob: meeting already exists', [
                'session_id' => $this->sessionId,
            ]);

            return;
        }

        $session->generateMeetingLink();

        Log::info('CreateSessionMeetingJob: meeting created', [
            'session_id' => $this->sessionId,
            'room_name' => $session->meeting_room_name,
        ]);
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
