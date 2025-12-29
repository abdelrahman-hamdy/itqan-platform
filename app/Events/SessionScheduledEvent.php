<?php

namespace App\Events;

use App\Models\BaseSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a new session is scheduled.
 *
 * Use cases:
 * - Send schedule notification to student/parent
 * - Notify teacher of new session
 * - Add to calendar integrations
 * - Queue reminder notifications
 */
class SessionScheduledEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly BaseSession $session,
        public readonly string $sessionType
    ) {}

    /**
     * Get the session model.
     */
    public function getSession(): BaseSession
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

    /**
     * Get the scheduled time.
     */
    public function getScheduledAt(): ?\DateTimeInterface
    {
        return $this->session->scheduled_at;
    }
}
