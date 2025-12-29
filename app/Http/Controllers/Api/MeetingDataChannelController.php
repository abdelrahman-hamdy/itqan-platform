<?php

namespace App\Http\Controllers\Api;

use App\Events\MeetingCommandEvent;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Http\Requests\AcknowledgeMeetingMessageRequest;
use App\Http\Requests\GrantMicrophoneToStudentRequest;
use App\Http\Requests\SendTeacherCommandRequest;
use App\Models\QuranSession;
use App\Services\MeetingDataChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Enums\SessionStatus;

class MeetingDataChannelController extends Controller
{
    use ApiResponses;

    private MeetingDataChannelService $dataChannelService;

    public function __construct(MeetingDataChannelService $dataChannelService)
    {
        $this->dataChannelService = $dataChannelService;
    }

    /**
     * Send teacher control command
     */
    public function sendTeacherCommand(SendTeacherCommandRequest $request, QuranSession $session): JsonResponse
    {
        $this->authorize('control', $session);

        try {
            $result = $this->dataChannelService->sendTeacherControlCommand(
                $session,
                Auth::user(),
                $request->input('command'),
                $request->input('data', []),
                $request->input('targets', [])
            );

            return $this->success($result, 'Command sent successfully');
        } catch (\Exception $e) {
            Log::error('Failed to send teacher command', [
                'session_id' => $session->id,
                'command' => $request->input('command'),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Failed to send command');
        }
    }

    /**
     * Handle participant acknowledgment
     */
    public function acknowledgeMessage(AcknowledgeMeetingMessageRequest $request, QuranSession $session): JsonResponse
    {
        $this->authorize('participate', $session);

        try {
            $this->dataChannelService->handleParticipantAcknowledgment(
                $session,
                Auth::user(),
                $request->input('message_id'),
                $request->input('response_data', [])
            );

            return $this->success(null, 'Acknowledgment recorded');
        } catch (\Exception $e) {
            Log::error('Failed to record acknowledgment', [
                'session_id' => $session->id,
                'message_id' => $request->input('message_id'),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Failed to record acknowledgment');
        }
    }

    /**
     * Get current meeting state for participant
     */
    public function getMeetingState(QuranSession $session): JsonResponse
    {
        $this->authorize('participate', $session);

        try {
            $state = $this->dataChannelService->getMeetingStateForParticipant(
                $session,
                Auth::user()
            );

            return $this->success($state);
        } catch (\Exception $e) {
            Log::error('Failed to get meeting state', [
                'session_id' => $session->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Failed to get meeting state');
        }
    }

    /**
     * Get pending commands for polling fallback
     */
    public function getPendingCommands(QuranSession $session): JsonResponse
    {
        $this->authorize('participate', $session);

        try {
            $stateKey = "meeting_commands:{$session->id}";
            $lastCheck = request()->header('Last-Check-Timestamp');

            $commandsData = Redis::get($stateKey);
            if (! $commandsData) {
                return $this->success([
                    'commands' => [],
                ]);
            }

            $state = json_decode($commandsData, true);
            $commands = $state['commands'] ?? [];

            // Filter commands since last check
            if ($lastCheck) {
                $commands = array_filter($commands, function ($command) use ($lastCheck) {
                    return strtotime($command['timestamp']) > strtotime($lastCheck);
                });
            }

            // Filter for this participant
            $userIdentifier = Auth::user()->getIdentifier();
            $relevantCommands = array_filter($commands, function ($command) use ($userIdentifier) {
                return empty($command['targets']) || in_array($userIdentifier, $command['targets']);
            });

            return $this->success([
                'commands' => array_values($relevantCommands),
                'server_time' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get pending commands', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return $this->success([
                'commands' => [],
            ]);
        }
    }

    /**
     * Server-Sent Events endpoint for real-time updates
     */
    public function streamEvents(QuranSession $session): Response
    {
        $this->authorize('participate', $session);

        $response = new Response;
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');

        // Set up the SSE stream
        $callback = function () use ($session) {
            echo "retry: 3000\n";
            echo 'id: '.time()."\n";
            echo "event: connected\n";
            echo 'data: '.json_encode(['message' => 'Connected to meeting events'])."\n\n";

            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            $sseKey = "sse_events:{$session->id}";
            $lastEventId = request()->header('Last-Event-ID', 0);

            // Send any pending events
            $events = Redis::lrange($sseKey, 0, -1);
            foreach ($events as $eventData) {
                $event = json_decode($eventData, true);
                if ($event && $event['id'] > $lastEventId) {
                    echo "id: {$event['id']}\n";
                    echo "event: {$event['event']}\n";
                    echo 'data: '.json_encode($event['data'])."\n\n";

                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
            }

            // Keep connection alive
            $start = time();
            while (time() - $start < 55) { // 55 seconds max
                sleep(1);

                // Check for new events
                $newEvents = Redis::lrange($sseKey, 0, 0);
                if (! empty($newEvents)) {
                    $event = json_decode($newEvents[0], true);
                    if ($event && $event['id'] > $lastEventId) {
                        echo "id: {$event['id']}\n";
                        echo "event: {$event['event']}\n";
                        echo 'data: '.json_encode($event['data'])."\n\n";

                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();

                        $lastEventId = $event['id'];
                    }
                }

                // Send heartbeat
                if ((time() - $start) % 10 === 0) {
                    echo "event: heartbeat\n";
                    echo 'data: '.json_encode(['timestamp' => time()])."\n\n";

                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
            }
        };

        $response->setCallback($callback);

        return $response;
    }

    /**
     * Predefined command endpoints for common actions
     */
    public function muteAllStudents(QuranSession $session): JsonResponse
    {
        $this->authorize('control', $session);

        try {
            $result = $this->dataChannelService->muteAllStudents($session, Auth::user());

            return $this->success($result, 'All students muted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to mute all students');
        }
    }

    public function allowStudentMicrophones(QuranSession $session): JsonResponse
    {
        $this->authorize('control', $session);

        try {
            $result = $this->dataChannelService->allowStudentMicrophones($session, Auth::user());

            return $this->success($result, 'Student microphones allowed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to allow student microphones');
        }
    }

    public function clearAllHandRaises(QuranSession $session): JsonResponse
    {
        $this->authorize('control', $session);

        try {
            $result = $this->dataChannelService->clearAllHandRaises($session, Auth::user());

            return $this->success($result, 'All hand raises cleared successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to clear hand raises');
        }
    }

    public function grantMicrophoneToStudent(GrantMicrophoneToStudentRequest $request, QuranSession $session): JsonResponse
    {
        $this->authorize('control', $session);

        try {
            $student = \App\Models\User::findOrFail($request->input('student_id'));

            $result = $this->dataChannelService->grantMicrophoneToStudent(
                $session,
                Auth::user(),
                $student
            );

            return $this->success($result, 'Microphone permission granted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to grant microphone permission');
        }
    }

    /**
     * Get delivery status for a command
     */
    public function getCommandDeliveryStatus(QuranSession $session, string $messageId): JsonResponse
    {
        $this->authorize('control', $session);

        try {
            $ackKey = "message_acks:{$session->id}:{$messageId}";
            $acks = Redis::hgetall($ackKey);

            $commandKey = "command:{$session->id}:{$messageId}";
            $commandData = Redis::get($commandKey);

            if (! $commandData) {
                return $this->notFound('Command not found');
            }

            $command = json_decode($commandData, true);
            $expectedCount = $session->students()->count() + 1; // +1 for teacher

            return $this->success([
                'message_id' => $messageId,
                'command' => $command['command'],
                'sent_at' => $command['timestamp'],
                'acknowledgments' => count($acks),
                'expected_acknowledgments' => $expectedCount,
                'delivery_complete' => count($acks) >= $expectedCount,
                'acknowledgment_details' => array_map(function ($ack) {
                    return json_decode($ack, true);
                }, $acks),
            ]);
        } catch (\Exception $e) {
            return $this->serverError('Failed to get delivery status');
        }
    }

    /**
     * Test data channel connectivity
     */
    public function testConnectivity(QuranSession $session): JsonResponse
    {
        $this->authorize('participate', $session);

        try {
            $testData = [
                'message_id' => 'test_'.uniqid(),
                'type' => 'connectivity_test',
                'command' => 'test_connectivity',
                'data' => [
                    'test_timestamp' => now()->toISOString(),
                    'user_id' => Auth::id(),
                    'message' => 'Testing data channel connectivity',
                ],
            ];

            // Test WebSocket broadcast
            broadcast(new MeetingCommandEvent($session, $testData));

            return $this->success([
                'test_id' => $testData['message_id'],
            ], 'Connectivity test sent');
        } catch (\Exception $e) {
            return $this->serverError('Connectivity test failed');
        }
    }
}
