<?php

use App\Events\MeetingCommandEvent;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use App\Services\MeetingDataChannelService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\Sanctum;

describe('Meeting Data Channel API', function () {
    beforeEach(function () {
        Event::fake();

        $this->academy = createAcademy();
        $this->teacher = createUser('quran_teacher', $this->academy);
        $this->student = createUser('student', $this->academy);

        $this->teacherProfile = QuranTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        $this->session = QuranSession::factory()->create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'scheduled_at' => now(),
            'status' => 'ongoing',
        ]);

        $this->dataChannelService = app(MeetingDataChannelService::class);
    });

    describe('POST /api/meetings/{session}/commands', function () {
        it('allows teacher to send control commands', function () {
            Sanctum::actingAs($this->teacher);

            $response = $this->postJson("/api/meetings/{$this->session->id}/commands", [
                'command' => 'mute_all_students',
                'data' => ['muted' => true],
                'targets' => [],
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'message_id',
                        'delivery_results',
                        'sent_at',
                    ],
                ]);

            expect($response->json('success'))->toBeTrue();
        });

        it('broadcasts command event', function () {
            Sanctum::actingAs($this->teacher);

            $this->postJson("/api/meetings/{$this->session->id}/commands", [
                'command' => 'clear_all_hand_raises',
                'data' => [],
            ]);

            Event::assertDispatched(MeetingCommandEvent::class);
        });

        it('validates required command field', function () {
            Sanctum::actingAs($this->teacher);

            $response = $this->postJson("/api/meetings/{$this->session->id}/commands", [
                'data' => ['test' => 'value'],
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['command']);
        });

        it('validates command is in allowed list', function () {
            Sanctum::actingAs($this->teacher);

            $response = $this->postJson("/api/meetings/{$this->session->id}/commands", [
                'command' => 'invalid_command',
                'data' => [],
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['command']);
        });

        it('prevents students from sending teacher commands', function () {
            Sanctum::actingAs($this->student);

            $response = $this->postJson("/api/meetings/{$this->session->id}/commands", [
                'command' => 'mute_all_students',
                'data' => [],
            ]);

            $response->assertStatus(403);
        });

        it('requires authentication', function () {
            $response = $this->postJson("/api/meetings/{$this->session->id}/commands", [
                'command' => 'mute_all_students',
            ]);

            $response->assertStatus(401);
        });
    });

    describe('POST /api/meetings/{session}/acknowledge', function () {
        it('allows participants to acknowledge messages', function () {
            Sanctum::actingAs($this->student);

            $response = $this->postJson("/api/meetings/{$this->session->id}/acknowledge", [
                'message_id' => 'msg_test_123',
                'response_data' => ['acknowledged' => true],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Acknowledgment recorded',
                ]);
        });

        it('validates required message_id', function () {
            Sanctum::actingAs($this->student);

            $response = $this->postJson("/api/meetings/{$this->session->id}/acknowledge", [
                'response_data' => ['test' => 'value'],
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['message_id']);
        });

        it('requires authentication', function () {
            $response = $this->postJson("/api/meetings/{$this->session->id}/acknowledge", [
                'message_id' => 'test',
            ]);

            $response->assertStatus(401);
        });
    });

    describe('GET /api/meetings/{session}/state', function () {
        it('returns current meeting state for participant', function () {
            Sanctum::actingAs($this->student);

            $response = $this->getJson("/api/meetings/{$this->session->id}/state");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'session_id',
                        'participant_id',
                        'current_state',
                        'relevant_commands',
                        'sync_timestamp',
                    ],
                ]);
        });

        it('requires participant authorization', function () {
            $otherStudent = createUser('student', $this->academy);
            Sanctum::actingAs($otherStudent);

            $response = $this->getJson("/api/meetings/{$this->session->id}/state");

            $response->assertStatus(403);
        });
    });

    describe('GET /api/meetings/{session}/pending-commands', function () {
        it('returns pending commands for polling fallback', function () {
            Sanctum::actingAs($this->student);

            $response = $this->getJson("/api/meetings/{$this->session->id}/pending-commands");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'commands',
                    'server_time',
                ]);
        });

        it('filters commands based on last check timestamp', function () {
            Sanctum::actingAs($this->student);

            $lastCheck = now()->subMinutes(5)->toISOString();

            $response = $this->getJson("/api/meetings/{$this->session->id}/pending-commands", [
                'Last-Check-Timestamp' => $lastCheck,
            ]);

            $response->assertStatus(200);
        });
    });

    describe('Predefined Command Endpoints', function () {
        beforeEach(function () {
            Sanctum::actingAs($this->teacher);
        });

        it('mutes all students', function () {
            $response = $this->postJson("/api/meetings/{$this->session->id}/commands/mute-all");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'All students muted successfully',
                ]);
        });

        it('allows student microphones', function () {
            $response = $this->postJson("/api/meetings/{$this->session->id}/commands/allow-mics");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Student microphones allowed successfully',
                ]);
        });

        it('clears all hand raises', function () {
            $response = $this->postJson("/api/meetings/{$this->session->id}/commands/clear-hands");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'All hand raises cleared successfully',
                ]);
        });

        it('grants microphone to specific student', function () {
            $response = $this->postJson("/api/meetings/{$this->session->id}/commands/grant-mic", [
                'student_id' => $this->student->id,
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Microphone permission granted successfully',
                ]);
        });

        it('validates student_id for grant microphone', function () {
            $response = $this->postJson("/api/meetings/{$this->session->id}/commands/grant-mic", [
                'student_id' => 99999,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['student_id']);
        });
    });

    describe('GET /api/meetings/{session}/commands/{messageId}/status', function () {
        it('returns command delivery status', function () {
            Sanctum::actingAs($this->teacher);

            // First send a command
            $commandResponse = $this->postJson("/api/meetings/{$this->session->id}/commands", [
                'command' => 'mute_all_students',
                'data' => [],
            ]);

            $messageId = $commandResponse->json('data.message_id');

            $response = $this->getJson("/api/meetings/{$this->session->id}/commands/{$messageId}/status");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'message_id',
                        'command',
                        'sent_at',
                        'acknowledgments',
                        'expected_acknowledgments',
                        'delivery_complete',
                        'acknowledgment_details',
                    ],
                ]);
        });

        it('returns 404 for non-existent command', function () {
            Sanctum::actingAs($this->teacher);

            $response = $this->getJson("/api/meetings/{$this->session->id}/commands/invalid-msg-id/status");

            $response->assertStatus(404);
        });

        it('requires teacher authorization', function () {
            Sanctum::actingAs($this->student);

            $response = $this->getJson("/api/meetings/{$this->session->id}/commands/test-msg-id/status");

            $response->assertStatus(403);
        });
    });

    describe('GET /api/meetings/{session}/test-connectivity', function () {
        it('sends connectivity test message', function () {
            Sanctum::actingAs($this->student);

            $response = $this->getJson("/api/meetings/{$this->session->id}/test-connectivity");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'test_id',
                ]);

            Event::assertDispatched(MeetingCommandEvent::class);
        });
    });

    describe('Server-Sent Events', function () {
        it('streams events for real-time updates', function () {
            Sanctum::actingAs($this->student);

            $response = $this->get("/api/meetings/{$this->session->id}/events");

            $response->assertStatus(200);
            expect($response->headers->get('Content-Type'))->toContain('text/event-stream')
                ->and($response->headers->get('Cache-Control'))->toBe('no-cache')
                ->and($response->headers->get('Connection'))->toBe('keep-alive');
        });

        it('requires authentication for SSE stream', function () {
            $response = $this->get("/api/meetings/{$this->session->id}/events");

            $response->assertStatus(401);
        });
    });
});
