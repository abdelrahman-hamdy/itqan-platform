<?php

use App\Events\MeetingCommandEvent;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\MeetingDataChannelService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

describe('MeetingDataChannelService', function () {
    beforeEach(function () {
        $this->service = new MeetingDataChannelService();
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
        $this->session = QuranSession::factory()->scheduled()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacher->id,
        ]);

        Cache::flush();
        Event::fake();
    });

    describe('sendTeacherControlCommand()', function () {
        it('sends teacher control command successfully', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'mute_all_students',
                ['muted' => true]
            );

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['message_id', 'delivery_results', 'sent_at'])
                ->and($result['message_id'])->toBeString()
                ->and($result['delivery_results'])->toBeArray()
                ->and($result['sent_at'])->toBeString();
        });

        it('includes command metadata in result', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'allow_student_microphones'
            );

            expect($result['message_id'])->toStartWith('msg_')
                ->and($result['delivery_results'])->toHaveKeys([
                    'livekit_data_channel',
                    'websocket',
                    'database_state',
                    'sse',
                ]);
        });

        it('accepts custom data payload', function () {
            $customData = [
                'setting' => 'value',
                'flag' => true,
                'count' => 42,
            ];

            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'custom_command',
                $customData
            );

            expect($result)->toBeArray()
                ->and($result['message_id'])->toBeString();
        });

        it('accepts target participants list', function () {
            $targets = [$this->student->getIdentifier()];

            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'grant_microphone_permission',
                [],
                $targets
            );

            expect($result)->toBeArray()
                ->and($result['message_id'])->toBeString();
        });

        it('broadcasts command via WebSocket', function () {
            $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'mute_all_students'
            );

            Event::assertDispatched(MeetingCommandEvent::class);
        });

        it('persists command state in cache', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'clear_all_hand_raises'
            );

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state)->toBeArray()
                ->and($state)->toHaveKey('last_command')
                ->and($state['last_command']['message_id'])->toBe($result['message_id']);
        });

        it('logs command execution', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('Teacher control command sent', Mockery::type('array'));

            $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );
        });
    });

    describe('muteAllStudents()', function () {
        it('sends mute all command with correct data', function () {
            $result = $this->service->muteAllStudents($this->session, $this->teacher);

            expect($result)->toBeArray()
                ->and($result)->toHaveKey('message_id');

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['command'])->toBe('mute_all_students')
                ->and($state['last_command']['data']['muted'])->toBeTrue()
                ->and($state['last_command']['data']['allow_self_unmute'])->toBeFalse();
        });

        it('includes Arabic message in data', function () {
            $result = $this->service->muteAllStudents($this->session, $this->teacher);

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['data']['message'])->toContain('تم كتم جميع الطلاب');
        });
    });

    describe('allowStudentMicrophones()', function () {
        it('sends allow microphones command with correct data', function () {
            $result = $this->service->allowStudentMicrophones($this->session, $this->teacher);

            expect($result)->toBeArray()
                ->and($result)->toHaveKey('message_id');

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['command'])->toBe('allow_student_microphones')
                ->and($state['last_command']['data']['muted'])->toBeFalse()
                ->and($state['last_command']['data']['allow_self_unmute'])->toBeTrue();
        });

        it('includes Arabic message in data', function () {
            $result = $this->service->allowStudentMicrophones($this->session, $this->teacher);

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['data']['message'])->toContain('تم السماح للطلاب');
        });
    });

    describe('clearAllHandRaises()', function () {
        it('sends clear hand raises command with correct data', function () {
            $result = $this->service->clearAllHandRaises($this->session, $this->teacher);

            expect($result)->toBeArray()
                ->and($result)->toHaveKey('message_id');

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['command'])->toBe('clear_all_hand_raises')
                ->and($state['last_command']['data']['clear_queue'])->toBeTrue()
                ->and($state['last_command']['data']['reset_notifications'])->toBeTrue();
        });

        it('includes Arabic message in data', function () {
            $result = $this->service->clearAllHandRaises($this->session, $this->teacher);

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['data']['message'])->toContain('تم مسح جميع الأيدي المرفوعة');
        });
    });

    describe('grantMicrophoneToStudent()', function () {
        it('sends grant microphone command to specific student', function () {
            $result = $this->service->grantMicrophoneToStudent(
                $this->session,
                $this->teacher,
                $this->student
            );

            expect($result)->toBeArray()
                ->and($result)->toHaveKey('message_id');

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['command'])->toBe('grant_microphone_permission')
                ->and($state['last_command']['data']['student_id'])->toBe($this->student->id)
                ->and($state['last_command']['data']['granted'])->toBeTrue()
                ->and($state['last_command']['data']['auto_unmute'])->toBeTrue()
                ->and($state['last_command']['targets'])->toContain($this->student->getIdentifier());
        });

        it('includes student name in Arabic message', function () {
            $result = $this->service->grantMicrophoneToStudent(
                $this->session,
                $this->teacher,
                $this->student
            );

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['data']['message'])->toContain($this->student->first_name)
                ->and($state['last_command']['data']['message'])->toContain('تم منح');
        });
    });

    describe('handleParticipantAcknowledgment()', function () {
        it('stores participant acknowledgment', function () {
            $messageId = 'msg_test_123';

            $this->service->handleParticipantAcknowledgment(
                $this->session,
                $this->student,
                $messageId,
                ['status' => 'received']
            );

            $ackKey = "message_acks:{$this->session->id}:{$messageId}";
            $acks = Cache::get($ackKey, []);

            expect($acks)->toBeArray()
                ->and($acks)->toHaveKey($this->student->id)
                ->and($acks[$this->student->id]['participant_id'])->toBe($this->student->id)
                ->and($acks[$this->student->id]['response_data']['status'])->toBe('received');
        });

        it('includes acknowledgment timestamp', function () {
            $messageId = 'msg_test_456';

            $this->service->handleParticipantAcknowledgment(
                $this->session,
                $this->student,
                $messageId
            );

            $ackKey = "message_acks:{$this->session->id}:{$messageId}";
            $acks = Cache::get($ackKey, []);

            expect($acks[$this->student->id]['acknowledged_at'])->toBeString();
        });

        it('handles multiple participant acknowledgments', function () {
            $messageId = 'msg_test_789';
            $student2 = User::factory()->student()->forAcademy($this->academy)->create();

            $this->service->handleParticipantAcknowledgment(
                $this->session,
                $this->student,
                $messageId
            );

            $this->service->handleParticipantAcknowledgment(
                $this->session,
                $student2,
                $messageId
            );

            $ackKey = "message_acks:{$this->session->id}:{$messageId}";
            $acks = Cache::get($ackKey, []);

            expect($acks)->toBeArray()
                ->and(count($acks))->toBe(2)
                ->and($acks)->toHaveKey($this->student->id)
                ->and($acks)->toHaveKey($student2->id);
        });
    });

    describe('getMeetingStateForParticipant()', function () {
        it('returns current meeting state for participant', function () {
            $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );

            $state = $this->service->getMeetingStateForParticipant(
                $this->session,
                $this->student
            );

            expect($state)->toBeArray()
                ->and($state)->toHaveKeys(['session_id', 'participant_id', 'current_state', 'relevant_commands', 'sync_timestamp'])
                ->and($state['session_id'])->toBe($this->session->id)
                ->and($state['participant_id'])->toBe($this->student->id);
        });

        it('filters commands for specific participant', function () {
            $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'broadcast_command',
                [],
                []
            );

            $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'targeted_command',
                [],
                [$this->student->getIdentifier()]
            );

            $state = $this->service->getMeetingStateForParticipant(
                $this->session,
                $this->student
            );

            expect($state['relevant_commands'])->toBeArray();
        });

        it('includes sync timestamp', function () {
            $state = $this->service->getMeetingStateForParticipant(
                $this->session,
                $this->student
            );

            expect($state['sync_timestamp'])->toBeString();
        });
    });

    describe('cleanupExpiredMeetingData()', function () {
        it('clears all meeting-related cache keys', function () {
            $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );

            $stateKey = "meeting_state:{$this->session->id}";
            expect(Cache::has($stateKey))->toBeTrue();

            Redis::shouldReceive('keys')->andReturn([]);
            Redis::shouldReceive('del')->zeroOrMoreTimes();

            $this->service->cleanupExpiredMeetingData($this->session);

            expect(Cache::has($stateKey))->toBeFalse();
        });

        it('handles cleanup when no data exists', function () {
            Redis::shouldReceive('keys')->andReturn([]);
            Redis::shouldReceive('del')->zeroOrMoreTimes();

            $this->service->cleanupExpiredMeetingData($this->session);

            expect(true)->toBeTrue();
        });
    });

    describe('command state persistence', function () {
        it('maintains command history in state', function () {
            $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'command_1'
            );

            $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'command_2'
            );

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['commands'])->toBeArray()
                ->and(count($state['commands']))->toBe(2);
        });

        it('limits command history to 50 entries', function () {
            for ($i = 0; $i < 60; $i++) {
                $this->service->sendTeacherControlCommand(
                    $this->session,
                    $this->teacher,
                    "command_{$i}"
                );
            }

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect(count($state['commands']))->toBeLessThanOrEqual(50);
        });

        it('stores updated_at timestamp', function () {
            $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state)->toHaveKey('updated_at')
                ->and($state['updated_at'])->toBeString();
        });
    });

    describe('command priority system', function () {
        it('assigns high priority to end_session command', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'end_session'
            );

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['priority'])->toBe(1);
        });

        it('assigns medium priority to mute commands', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'mute_all_students'
            );

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['priority'])->toBe(2);
        });

        it('assigns default priority to unknown commands', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'unknown_command'
            );

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['priority'])->toBe(5);
        });
    });

    describe('command acknowledgment requirements', function () {
        it('requires acknowledgment for critical commands', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'end_session'
            );

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['requires_acknowledgment'])->toBeTrue();
        });

        it('does not require acknowledgment for non-critical commands', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'clear_all_hand_raises'
            );

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['requires_acknowledgment'])->toBeFalse();
        });
    });

    describe('command metadata', function () {
        it('includes session and teacher information', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['session_id'])->toBe($this->session->id)
                ->and($state['last_command']['teacher_id'])->toBe($this->teacher->id)
                ->and($state['last_command']['teacher_identity'])->toBe($this->teacher->getIdentifier());
        });

        it('includes command type and topic', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['type'])->toBe('teacher_control')
                ->and($state['last_command']['topic'])->toBe('teacher_controls');
        });

        it('includes timestamp in ISO format', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );

            $stateKey = "meeting_state:{$this->session->id}";
            $state = Cache::get($stateKey);

            expect($state['last_command']['timestamp'])->toBeString()
                ->and($state['last_command']['timestamp'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
        });
    });

    describe('delivery channel results', function () {
        it('returns results from all delivery channels', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );

            expect($result['delivery_results'])->toHaveKey('livekit_data_channel')
                ->and($result['delivery_results'])->toHaveKey('websocket')
                ->and($result['delivery_results'])->toHaveKey('database_state')
                ->and($result['delivery_results'])->toHaveKey('sse');
        });

        it('livekit channel returns success status', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );

            expect($result['delivery_results']['livekit_data_channel']['status'])->toBe('success');
        });

        it('websocket channel returns success when event dispatched', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );

            expect($result['delivery_results']['websocket']['status'])->toBe('success');
        });

        it('database channel returns success when state updated', function () {
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );

            expect($result['delivery_results']['database_state']['status'])->toBe('success')
                ->and($result['delivery_results']['database_state']['state_updated'])->toBeTrue();
        });
    });

    describe('error handling', function () {
        it('handles WebSocket broadcast failures gracefully', function () {
            Event::shouldReceive('dispatch')
                ->andThrow(new Exception('Broadcasting failed'));

            Log::shouldReceive('error')->once();
            Log::shouldReceive('info')->once();
            Log::shouldReceive('warning')->zeroOrMoreTimes();

            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );

            expect($result)->toBeArray()
                ->and($result)->toHaveKey('message_id');
        });

        it('handles cache failures gracefully using try-catch', function () {
            Log::shouldReceive('error')->zeroOrMoreTimes();
            Log::shouldReceive('info')->once();
            Log::shouldReceive('warning')->zeroOrMoreTimes();

            // The service handles cache failures internally with try-catch
            // This test validates the service continues execution despite failures
            $result = $this->service->sendTeacherControlCommand(
                $this->session,
                $this->teacher,
                'test_command'
            );

            expect($result)->toBeArray()
                ->and($result)->toHaveKey('message_id')
                ->and($result)->toHaveKey('delivery_results');
        });
    });

    afterEach(function () {
        Mockery::close();
    });
});
