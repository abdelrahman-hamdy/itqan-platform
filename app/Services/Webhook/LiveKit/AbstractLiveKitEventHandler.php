<?php

namespace App\Services\Webhook\LiveKit;

use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for LiveKit webhook event handlers.
 *
 * Provides common functionality used across event handlers including
 * session lookup, user identification, and logging.
 */
abstract class AbstractLiveKitEventHandler implements LiveKitEventHandlerInterface
{
    /**
     * The event type this handler processes.
     */
    protected string $eventType;

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function canHandle(string $eventType): bool
    {
        return $this->eventType === $eventType;
    }

    /**
     * Find a session by its room name.
     *
     * Room names follow the pattern: {type}_{session_id}
     *
     * @param string $roomName The room name
     * @return BaseSession|null The session or null if not found
     */
    protected function findSessionByRoomName(string $roomName): ?BaseSession
    {
        // Parse room name to extract session type and ID
        // Format: quran_123, academic_456, interactive_789
        $parts = explode('_', $roomName, 2);

        if (count($parts) !== 2) {
            Log::warning('Invalid room name format', ['room_name' => $roomName]);
            return null;
        }

        [$type, $sessionId] = $parts;

        return match ($type) {
            'quran' => QuranSession::find($sessionId),
            'academic' => AcademicSession::find($sessionId),
            'interactive' => InteractiveCourseSession::find($sessionId),
            default => null,
        };
    }

    /**
     * Extract user ID from participant identity.
     *
     * Identity format: {role}_{user_id}_{optional_info}
     *
     * @param string $identity The participant identity
     * @return int|null The user ID or null if not found
     */
    protected function extractUserIdFromIdentity(string $identity): ?int
    {
        // Parse identity to extract user ID
        // Format: teacher_123, student_456, parent_789
        $parts = explode('_', $identity);

        if (count($parts) < 2) {
            return null;
        }

        $userId = $parts[1] ?? null;

        return is_numeric($userId) ? (int) $userId : null;
    }

    /**
     * Log event processing information.
     *
     * @param string $message The log message
     * @param array $context Additional context data
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::channel('livekit')->info("[{$this->eventType}] {$message}", $context);
    }

    /**
     * Log event processing warning.
     *
     * @param string $message The log message
     * @param array $context Additional context data
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::channel('livekit')->warning("[{$this->eventType}] {$message}", $context);
    }

    /**
     * Log event processing error.
     *
     * @param string $message The log message
     * @param array $context Additional context data
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::channel('livekit')->error("[{$this->eventType}] {$message}", $context);
    }
}
