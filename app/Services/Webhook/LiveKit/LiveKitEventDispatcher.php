<?php

namespace App\Services\Webhook\LiveKit;

use Illuminate\Support\Facades\Log;

/**
 * Dispatcher for LiveKit webhook events.
 *
 * Routes incoming webhook events to the appropriate handler based on event type.
 * Supports registering multiple handlers and handles unknown event types gracefully.
 */
class LiveKitEventDispatcher
{
    /**
     * Registered event handlers.
     *
     * @var array<string, LiveKitEventHandlerInterface>
     */
    private array $handlers = [];

    public function __construct(
        RoomStartedHandler $roomStartedHandler,
        RoomFinishedHandler $roomFinishedHandler,
        ParticipantJoinedHandler $participantJoinedHandler,
        ParticipantLeftHandler $participantLeftHandler
    ) {
        $this->registerHandler($roomStartedHandler);
        $this->registerHandler($roomFinishedHandler);
        $this->registerHandler($participantJoinedHandler);
        $this->registerHandler($participantLeftHandler);
    }

    /**
     * Register an event handler.
     *
     * @param LiveKitEventHandlerInterface $handler The handler to register
     */
    public function registerHandler(LiveKitEventHandlerInterface $handler): void
    {
        $this->handlers[$handler->getEventType()] = $handler;
    }

    /**
     * Dispatch an event to the appropriate handler.
     *
     * @param string $eventType The event type
     * @param array $data The event payload data
     * @return bool Whether a handler was found and executed
     */
    public function dispatch(string $eventType, array $data): bool
    {
        $handler = $this->handlers[$eventType] ?? null;

        if (!$handler) {
            Log::channel('livekit')->debug("No handler registered for event type: {$eventType}");
            return false;
        }

        try {
            $handler->handle($data);
            return true;
        } catch (\Exception $e) {
            Log::channel('livekit')->error("Error handling event: {$eventType}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);
            return false;
        }
    }

    /**
     * Check if a handler is registered for the given event type.
     *
     * @param string $eventType The event type to check
     * @return bool Whether a handler exists
     */
    public function hasHandler(string $eventType): bool
    {
        return isset($this->handlers[$eventType]);
    }

    /**
     * Get all registered event types.
     *
     * @return array<string> List of event types
     */
    public function getRegisteredEventTypes(): array
    {
        return array_keys($this->handlers);
    }
}
