<?php

namespace App\Events;

use App\Contracts\MeetingCapable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a session transitions to completed status.
 * Used to decouple SessionStatusService from MeetingAttendanceService.
 */
class SessionCompletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly MeetingCapable $session,
        public readonly string $sessionType
    ) {}

    /**
     * Get the session model.
     */
    public function getSession(): MeetingCapable
    {
        return $this->session;
    }

    /**
     * Get the session type (quran, academic, interactive).
     */
    public function getSessionType(): string
    {
        return $this->sessionType;
    }
}
