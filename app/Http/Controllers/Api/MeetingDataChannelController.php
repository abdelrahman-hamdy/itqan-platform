<?php

namespace App\Http\Controllers\Api;

use App\Events\MeetingCommandEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcknowledgeMeetingMessageRequest;
use App\Http\Requests\GrantMicrophoneToStudentRequest;
use App\Http\Requests\SendTeacherCommandRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranSession;
use App\Services\MeetingDataChannelService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

            return $this->success($result, __('api.meeting.command_sent'));
        } catch (Exception $e) {
            Log::error('Failed to send teacher command', [
                'session_id' => $session->id,
                'command' => $request->input('command'),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError(__('api.meeting.command_failed'));
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

            return $this->success(null, __('api.meeting.ack_recorded'));
        } catch (Exception $e) {
            Log::error('Failed to record acknowledgment', [
                'session_id' => $session->id,
                'message_id' => $request->input('message_id'),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError(__('api.meeting.ack_failed'));
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
        } catch (Exception $e) {
            Log::error('Failed to get meeting state', [
                'session_id' => $session->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError(__('api.meeting.state_failed'));
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
        } catch (Exception $e) {
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
    public function streamEvents(QuranSession $session): StreamedResponse
    {
        $this->authorize('participate', $session);

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
            while (time() - $start < 25) { // 25 seconds max to limit PHP-FPM worker hold time
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

        $response = new StreamedResponse($callback);
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');

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

            return $this->success($result, __('api.meeting.students_muted'));
        } catch (Exception $e) {
            return $this->serverError(__('api.meeting.mute_failed'));
        }
    }

    public function allowStudentMicrophones(QuranSession $session): JsonResponse
    {
        $this->authorize('control', $session);

        try {
            $result = $this->dataChannelService->allowStudentMicrophones($session, Auth::user());

            return $this->success($result, __('api.meeting.mics_allowed'));
        } catch (Exception $e) {
            return $this->serverError(__('api.meeting.mics_failed'));
        }
    }

    public function clearAllHandRaises(QuranSession $session): JsonResponse
    {
        $this->authorize('control', $session);

        try {
            $result = $this->dataChannelService->clearAllHandRaises($session, Auth::user());

            return $this->success($result, __('api.meeting.hands_cleared'));
        } catch (Exception $e) {
            return $this->serverError(__('api.meeting.hands_failed'));
        }
    }

    public function grantMicrophoneToStudent(GrantMicrophoneToStudentRequest $request, QuranSession $session): JsonResponse
    {
        $this->authorize('control', $session);

        try {
            $studentId = $request->input('student_id');

            // SECURITY: Verify student is enrolled in this session before loading
            // This prevents information disclosure of arbitrary users
            $sessionStudents = $session->getStudentsForSession();
            $student = $sessionStudents->firstWhere('id', $studentId);

            if (! $student) {
                return $this->notFound(__('api.meeting.student_not_enrolled'));
            }

            $result = $this->dataChannelService->grantMicrophoneToStudent(
                $session,
                Auth::user(),
                $student
            );

            return $this->success($result, __('api.meeting.mic_granted'));
        } catch (Exception $e) {
            return $this->serverError(__('api.meeting.mic_grant_failed'));
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
                return $this->notFound(__('api.meeting.command_not_found'));
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
        } catch (Exception $e) {
            return $this->serverError(__('api.meeting.delivery_status_failed'));
        }
    }

    /**
     * Test data channel connectivity
     */
    public function testConnectivity(QuranSession $session): JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->isSuperAdmin()) {
            abort(403, 'Only super administrators can use the connectivity test endpoint.');
        }

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
            ], __('api.meeting.connectivity_test_sent'));
        } catch (Exception $e) {
            return $this->serverError(__('api.meeting.connectivity_test_failed'));
        }
    }
}
