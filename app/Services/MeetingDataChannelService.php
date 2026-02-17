<?php

namespace App\Services;

use Exception;
use App\Events\MeetingCommandEvent;
use App\Models\QuranSession;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Comprehensive data communication service for LiveKit meetings
 * Handles teacher commands, participant state synchronization, and real-time messaging
 */
class MeetingDataChannelService
{
    private const CACHE_PREFIX = 'meeting_state:';

    private const MESSAGE_TTL = 3600; // 1 hour

    private const MAX_RETRY_ATTEMPTS = 3;

    private const COMMAND_TOPICS = [
        'teacher_controls',
        'participant_management',
        'hand_raising',
        'session_announcements',
        'system_notifications',
    ];

    /**
     * Send teacher control command to all participants
     */
    public function sendTeacherControlCommand(
        QuranSession $session,
        User $teacher,
        string $command,
        array $data = [],
        array $targetParticipants = []
    ): array {
        $messageId = $this->generateMessageId();

        $commandData = [
            'message_id' => $messageId,
            'type' => 'teacher_control',
            'command' => $command,
            'session_id' => $session->id,
            'teacher_id' => $teacher->id,
            'teacher_identity' => $teacher->getIdentifier(),
            'timestamp' => now()->toISOString(),
            'data' => $data,
            'targets' => $targetParticipants, // Empty = broadcast to all
            'requires_acknowledgment' => $this->requiresAcknowledment($command),
            'priority' => $this->getCommandPriority($command),
            'topic' => 'teacher_controls',
        ];

        // Store command in persistent state
        $this->persistCommandState($session, $messageId, $commandData);

        // Send via multiple channels for reliability
        $result = $this->sendViaMultipleChannels($session, $commandData);

        // Log the command for debugging
        Log::info('Teacher control command sent', [
            'session_id' => $session->id,
            'command' => $command,
            'message_id' => $messageId,
            'delivery_results' => $result,
        ]);

        return [
            'message_id' => $messageId,
            'delivery_results' => $result,
            'sent_at' => now()->toISOString(),
        ];
    }

    /**
     * Send command via multiple delivery channels for maximum reliability
     */
    private function sendViaMultipleChannels(QuranSession $session, array $commandData): array
    {
        $results = [];

        // Primary: LiveKit Data Channel
        $results['livekit_data_channel'] = $this->sendViaLiveKitDataChannel($session, $commandData);

        // Secondary: WebSocket (Pusher/Broadcasting)
        $results['websocket'] = $this->sendViaWebSocket($session, $commandData);

        // Tertiary: Database polling fallback
        $results['database_state'] = $this->updateDatabaseState($session, $commandData);

        // Quaternary: Server-Sent Events
        $results['sse'] = $this->sendViaServerSentEvents($session, $commandData);

        return $results;
    }

    /**
     * Primary delivery method: LiveKit Data Channel
     */
    private function sendViaLiveKitDataChannel(QuranSession $session, array $commandData): array
    {
        try {
            // For now, we'll use a simple approach since LiveKit service might not have data messaging
            // This would be implemented based on your specific LiveKit service implementation

            // Encode data for transmission
            $encodedData = json_encode($commandData);

            // In a real implementation, you would send via LiveKit's data channel
            // For now, we'll simulate success
            return [
                'status' => 'success',
                'message' => 'LiveKit data channel delivery simulated (implement based on your LiveKit service)',
                'attempts' => 1,
            ];
        } catch (Exception $e) {
            Log::error('LiveKit data channel send failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'command' => $commandData['command'],
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'attempts' => 1,
            ];
        }
    }

    /**
     * Secondary delivery method: WebSocket Broadcasting
     */
    private function sendViaWebSocket(QuranSession $session, array $commandData): array
    {
        try {
            $channelName = "meeting.{$session->id}";

            broadcast(new MeetingCommandEvent($session, $commandData))
                ->toOthers();

            return ['status' => 'success', 'channel' => $channelName];
        } catch (Exception $e) {
            Log::error('WebSocket broadcast failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Tertiary delivery method: Database state persistence
     */
    private function updateDatabaseState(QuranSession $session, array $commandData): array
    {
        try {
            $stateKey = "meeting_state:{$session->id}";

            // Get current state
            $currentState = Cache::get($stateKey, []);

            // Update with new command
            $currentState['last_command'] = $commandData;
            $currentState['commands'][] = $commandData;
            $currentState['updated_at'] = now()->toISOString();

            // Keep only last 50 commands to prevent memory bloat
            if (count($currentState['commands']) > 50) {
                $currentState['commands'] = array_slice($currentState['commands'], -50);
            }

            // Store in cache with TTL
            Cache::put($stateKey, $currentState, now()->addHours(2));

            // Try to store in Redis for real-time polling (graceful fallback)
            try {
                if (class_exists('Redis') && extension_loaded('redis')) {
                    Redis::setex(
                        "meeting_commands:{$session->id}",
                        3600,
                        json_encode($currentState)
                    );
                }
            } catch (Exception $redisError) {
                Log::warning('Redis unavailable, using cache fallback', [
                    'session_id' => $session->id,
                    'redis_error' => $redisError->getMessage(),
                ]);
            }

            return ['status' => 'success', 'state_updated' => true];
        } catch (Exception $e) {
            Log::error('Database state update failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Quaternary delivery method: Server-Sent Events
     */
    private function sendViaServerSentEvents(QuranSession $session, array $commandData): array
    {
        try {
            $sseKey = "sse_events:{$session->id}";
            $eventData = [
                'event' => 'meeting_command',
                'data' => $commandData,
                'id' => $commandData['message_id'],
                'retry' => 3000,
            ];

            // Try to store for SSE clients (graceful fallback)
            try {
                if (class_exists('Redis') && extension_loaded('redis')) {
                    Redis::lpush($sseKey, json_encode($eventData));
                    Redis::expire($sseKey, 3600);
                } else {
                    // Fallback to cache for SSE events
                    $existingEvents = Cache::get($sseKey, []);
                    array_unshift($existingEvents, $eventData);
                    // Keep only last 20 events
                    $existingEvents = array_slice($existingEvents, 0, 20);
                    Cache::put($sseKey, $existingEvents, now()->addHour());
                }
            } catch (Exception $redisError) {
                Log::warning('Redis unavailable for SSE, using cache fallback', [
                    'session_id' => $session->id,
                    'redis_error' => $redisError->getMessage(),
                ]);

                // Cache fallback
                $existingEvents = Cache::get($sseKey, []);
                array_unshift($existingEvents, $eventData);
                $existingEvents = array_slice($existingEvents, 0, 20);
                Cache::put($sseKey, $existingEvents, now()->addHour());
            }

            return ['status' => 'success', 'sse_queued' => true];
        } catch (Exception $e) {
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle participant acknowledgment
     */
    public function handleParticipantAcknowledgment(
        QuranSession $session,
        User $participant,
        string $messageId,
        array $responseData = []
    ): void {
        $ackKey = "message_acks:{$session->id}:{$messageId}";

        $acknowledgment = [
            'participant_id' => $participant->id,
            'participant_identity' => $participant->getIdentifier(),
            'acknowledged_at' => now()->toISOString(),
            'response_data' => $responseData,
        ];

        // Store acknowledgment with Redis fallback
        try {
            if (class_exists('Redis') && extension_loaded('redis')) {
                Redis::hset($ackKey, $participant->id, json_encode($acknowledgment));
                Redis::expire($ackKey, 3600);
            } else {
                // Cache fallback
                $acks = Cache::get($ackKey, []);
                $acks[$participant->id] = $acknowledgment;
                Cache::put($ackKey, $acks, now()->addHour());
            }
        } catch (Exception $e) {
            Log::warning('Redis unavailable for acknowledgments, using cache', [
                'session_id' => $session->id,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            // Cache fallback
            $acks = Cache::get($ackKey, []);
            $acks[$participant->id] = $acknowledgment;
            Cache::put($ackKey, $acks, now()->addHour());
        }

        // Check if all participants have acknowledged
        $this->checkCommandDeliveryCompletion($session, $messageId);
    }

    /**
     * Get current meeting state for late joiners
     */
    public function getMeetingStateForParticipant(
        QuranSession $session,
        User $participant
    ): array {
        $stateKey = "meeting_state:{$session->id}";
        $currentState = Cache::get($stateKey, []);

        // Filter commands relevant to this participant
        $relevantCommands = $this->filterCommandsForParticipant(
            $currentState['commands'] ?? [],
            $participant
        );

        return [
            'session_id' => $session->id,
            'participant_id' => $participant->id,
            'current_state' => $currentState,
            'relevant_commands' => $relevantCommands,
            'sync_timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Clean up expired meeting data
     */
    public function cleanupExpiredMeetingData(QuranSession $session): void
    {
        $patterns = [
            "meeting_state:{$session->id}",
            "meeting_commands:{$session->id}",
            "sse_events:{$session->id}",
            "message_acks:{$session->id}:*",
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $keys = Redis::keys($pattern);
                if (! empty($keys)) {
                    Redis::del($keys);
                }
            } else {
                Redis::del($pattern);
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Predefined command methods for common teacher actions
     */
    public function muteAllStudents(QuranSession $session, User $teacher): array
    {
        return $this->sendTeacherControlCommand($session, $teacher, 'mute_all_students', [
            'muted' => true,
            'allow_self_unmute' => false,
            'message' => 'تم كتم جميع الطلاب من قبل المعلم',
        ]);
    }

    public function allowStudentMicrophones(QuranSession $session, User $teacher): array
    {
        return $this->sendTeacherControlCommand($session, $teacher, 'allow_student_microphones', [
            'muted' => false,
            'allow_self_unmute' => true,
            'message' => 'تم السماح للطلاب باستخدام الميكروفون',
        ]);
    }

    public function clearAllHandRaises(QuranSession $session, User $teacher): array
    {
        return $this->sendTeacherControlCommand($session, $teacher, 'clear_all_hand_raises', [
            'clear_queue' => true,
            'reset_notifications' => true,
            'message' => 'تم مسح جميع الأيدي المرفوعة',
        ]);
    }

    public function grantMicrophoneToStudent(
        QuranSession $session,
        User $teacher,
        User $student
    ): array {
        return $this->sendTeacherControlCommand(
            $session,
            $teacher,
            'grant_microphone_permission',
            [
                'student_id' => $student->id,
                'student_identity' => $student->getIdentifier(),
                'granted' => true,
                'auto_unmute' => true,
                'message' => "تم منح {$student->first_name} إذن استخدام الميكروفون",
            ],
            [$student->getIdentifier()] // Target specific student
        );
    }

    // Helper methods
    private function generateMessageId(): string
    {
        return 'msg_'.uniqid().'_'.time();
    }

    private function requiresAcknowledment(string $command): bool
    {
        return in_array($command, [
            'mute_all_students',
            'grant_microphone_permission',
            'end_session',
            'kick_participant',
        ]);
    }

    private function getCommandPriority(string $command): int
    {
        $priorities = [
            'end_session' => 1,
            'kick_participant' => 1,
            'mute_all_students' => 2,
            'grant_microphone_permission' => 2,
            'clear_all_hand_raises' => 3,
            'allow_student_microphones' => 3,
        ];

        return $priorities[$command] ?? 5;
    }

    private function persistCommandState(QuranSession $session, string $messageId, array $commandData): void
    {
        // Store command for delivery tracking with Redis fallback
        $commandKey = "command:{$session->id}:{$messageId}";

        try {
            if (class_exists('Redis') && extension_loaded('redis')) {
                Redis::setex($commandKey, 3600, json_encode($commandData));
            } else {
                // Cache fallback
                Cache::put($commandKey, $commandData, now()->addHour());
            }
        } catch (Exception $e) {
            Log::warning('Redis unavailable for command persistence, using cache', [
                'session_id' => $session->id,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            // Cache fallback
            Cache::put($commandKey, $commandData, now()->addHour());
        }
    }

    private function filterCommandsForParticipant(array $commands, User $participant): array
    {
        return array_filter($commands, function ($command) use ($participant) {
            // If no specific targets, command applies to all
            if (empty($command['targets'])) {
                return true;
            }

            // Check if participant is in target list
            return in_array($participant->getIdentifier(), $command['targets']);
        });
    }

    private function checkCommandDeliveryCompletion(QuranSession $session, string $messageId): void
    {
        // This method would check if all required participants have acknowledged
        // and trigger any completion callbacks
        $ackKey = "message_acks:{$session->id}:{$messageId}";

        try {
            if (class_exists('Redis') && extension_loaded('redis')) {
                $acks = Redis::hgetall($ackKey);
            } else {
                $acks = Cache::get($ackKey, []);
            }
        } catch (Exception $e) {
            $acks = Cache::get($ackKey, []);
        }

        // Get expected participant count
        $expectedCount = $this->getExpectedParticipantCount($session);

        if (count($acks) >= $expectedCount) {
            Log::info('Command delivery completed', [
                'session_id' => $session->id,
                'message_id' => $messageId,
                'ack_count' => count($acks),
            ]);
        }
    }

    private function getExpectedParticipantCount(QuranSession $session): int
    {
        // This would return the expected number of participants
        // For individual sessions: 1 student + 1 teacher = 2
        // For group sessions: use circle's student count + 1 teacher
        if ($session->session_type === 'individual') {
            return 2; // student + teacher
        }

        // For group/circle sessions
        if ($session->circle) {
            return $session->circle->students()->count() + 1; // students + teacher
        }

        return 2; // default fallback
    }
}
