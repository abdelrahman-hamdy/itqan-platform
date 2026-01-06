<?php

namespace App\Services\Webhook\LiveKit;

/**
 * Interface for LiveKit webhook event handlers.
 *
 * Each handler processes a specific type of LiveKit webhook event,
 * enabling separation of concerns and easier testing.
 */
interface LiveKitEventHandlerInterface
{
    /**
     * Get the event type this handler processes.
     *
     * @return string The event type (e.g., 'room_started', 'participant_joined')
     */
    public function getEventType(): string;

    /**
     * Handle the webhook event.
     *
     * @param  array  $data  The event payload data
     */
    public function handle(array $data): void;

    /**
     * Check if this handler can process the given event type.
     *
     * @param  string  $eventType  The event type to check
     * @return bool Whether this handler can process the event
     */
    public function canHandle(string $eventType): bool;
}
