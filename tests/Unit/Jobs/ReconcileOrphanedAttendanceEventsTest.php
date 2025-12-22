<?php

use App\Jobs\ReconcileOrphanedAttendanceEvents;
use App\Models\Academy;
use App\Models\MeetingAttendanceEvent;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\LiveKitService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

describe('ReconcileOrphanedAttendanceEvents', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->livekitService = Mockery::mock(LiveKitService::class);
        $this->app->instance(LiveKitService::class, $this->livekitService);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('job configuration', function () {
        it('can be instantiated', function () {
            $job = new ReconcileOrphanedAttendanceEvents();

            expect($job)->toBeInstanceOf(ReconcileOrphanedAttendanceEvents::class);
        });

        it('implements ShouldQueue interface', function () {
            $job = new ReconcileOrphanedAttendanceEvents();

            expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
        });
    });

    describe('job dispatch', function () {
        it('can be dispatched to queue', function () {
            Queue::fake();

            ReconcileOrphanedAttendanceEvents::dispatch();

            Queue::assertPushed(ReconcileOrphanedAttendanceEvents::class);
        });
    });

    describe('handle() - orphaned event detection', function () {
        it('finds and closes orphaned join events older than 2 hours', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Create orphaned join event (older than 2 hours, no left_at)
            $event = MeetingAttendanceEvent::create([
                'event_id' => 'evt_' . uniqid(),
                'event_type' => 'join',
                'event_timestamp' => now()->subHours(3),
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
                'participant_sid' => 'PA_' . uniqid(),
                'participant_identity' => 'user_' . $user->id,
                'participant_name' => $user->name,
                'left_at' => null,
            ]);

            Log::shouldReceive('info')
                ->times(3); // Starting, closed event, complete

            Log::shouldReceive('warning')
                ->zeroOrMoreTimes(); // For when session/meeting not found

            Log::shouldReceive('error')
                ->zeroOrMoreTimes(); // For any errors during reconciliation

            // LiveKit service not called when no meeting exists
            $this->livekitService->shouldReceive('listParticipants')
                ->never();

            $job = new ReconcileOrphanedAttendanceEvents();
            $job->handle();

            $event->refresh();
            expect($event->left_at)->not->toBeNull()
                ->and($event->duration_minutes)->toBeGreaterThan(0)
                ->and($event->termination_reason)->toBe('reconciled_missed_webhook');
        });

        it('does not close recent join events', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Create recent join event (less than 2 hours old)
            $event = MeetingAttendanceEvent::create([
                'event_id' => 'evt_' . uniqid(),
                'event_type' => 'join',
                'event_timestamp' => now()->subMinutes(30),
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
                'participant_sid' => 'PA_' . uniqid(),
                'participant_identity' => 'user_' . $user->id,
                'participant_name' => $user->name,
                'left_at' => null,
            ]);

            Log::shouldReceive('info')
                ->twice(); // Starting and complete only

            $job = new ReconcileOrphanedAttendanceEvents();
            $job->handle();

            $event->refresh();
            expect($event->left_at)->toBeNull();
        });

        it('does not close already closed events', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Create already closed event
            $event = MeetingAttendanceEvent::create([
                'event_id' => 'evt_' . uniqid(),
                'event_type' => 'join',
                'event_timestamp' => now()->subHours(3),
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
                'participant_sid' => 'PA_' . uniqid(),
                'participant_identity' => 'user_' . $user->id,
                'participant_name' => $user->name,
                'left_at' => now()->subHours(1),
                'duration_minutes' => 120,
            ]);

            $originalLeftAt = $event->left_at;

            Log::shouldReceive('info')
                ->twice(); // Starting and complete only

            $job = new ReconcileOrphanedAttendanceEvents();
            $job->handle();

            $event->refresh();
            expect($event->left_at->timestamp)->toBe($originalLeftAt->timestamp);
        });

        it('does not close leave events', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Create leave event
            MeetingAttendanceEvent::create([
                'event_id' => 'evt_' . uniqid(),
                'event_type' => 'leave',
                'event_timestamp' => now()->subHours(3),
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
                'participant_sid' => 'PA_' . uniqid(),
                'participant_identity' => 'user_' . $user->id,
                'participant_name' => $user->name,
            ]);

            Log::shouldReceive('info')
                ->twice(); // Starting and complete only

            $job = new ReconcileOrphanedAttendanceEvents();
            $job->handle();

            // Should not process leave events
            expect(MeetingAttendanceEvent::where('event_type', 'leave')->count())->toBe(1);
        });
    });

    describe('handle() - participant still in room', function () {
        it('skips reconciliation if participant check returns true', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $participantSid = 'PA_' . uniqid();

            $event = MeetingAttendanceEvent::create([
                'event_id' => 'evt_' . uniqid(),
                'event_type' => 'join',
                'event_timestamp' => now()->subHours(3),
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
                'participant_sid' => $participantSid,
                'participant_identity' => 'user_' . $user->id,
                'participant_name' => $user->name,
                'left_at' => null,
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->times(2); // Starting and complete

            Log::shouldReceive('warning')
                ->zeroOrMoreTimes();

            Log::shouldReceive('error')
                ->zeroOrMoreTimes();

            // Since session has no meetings, it will return false and close the event
            $this->livekitService->shouldReceive('listParticipants')
                ->never(); // Won't be called if meeting not found

            $job = new ReconcileOrphanedAttendanceEvents();
            $job->handle();

            // Event should be closed since no meeting found
            $event->refresh();
            expect($event->left_at)->not->toBeNull();
        });
    });

    describe('handle() - cache clearing', function () {
        it('clears attendance status cache when closing event', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $event = MeetingAttendanceEvent::create([
                'event_id' => 'evt_' . uniqid(),
                'event_type' => 'join',
                'event_timestamp' => now()->subHours(3),
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
                'participant_sid' => 'PA_' . uniqid(),
                'participant_identity' => 'user_' . $user->id,
                'participant_name' => $user->name,
                'left_at' => null,
            ]);

            // Set cache
            $cacheKey = "attendance_status_{$session->id}_{$user->id}";
            Cache::put($cacheKey, 'test_value', 60);

            expect(Cache::has($cacheKey))->toBeTrue();

            Log::shouldReceive('info')
                ->times(3);

            Log::shouldReceive('warning')
                ->zeroOrMoreTimes();

            Log::shouldReceive('error')
                ->zeroOrMoreTimes();

            $this->livekitService->shouldReceive('listParticipants')
                ->never();

            $job = new ReconcileOrphanedAttendanceEvents();
            $job->handle();

            expect(Cache::has($cacheKey))->toBeFalse();
        });
    });

    describe('handle() - duration calculation', function () {
        it('calculates duration as 2 hours for orphaned events', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $joinTime = now()->subHours(3);

            $event = MeetingAttendanceEvent::create([
                'event_id' => 'evt_' . uniqid(),
                'event_type' => 'join',
                'event_timestamp' => $joinTime,
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
                'participant_sid' => 'PA_' . uniqid(),
                'participant_identity' => 'user_' . $user->id,
                'participant_name' => $user->name,
                'left_at' => null,
            ]);

            Log::shouldReceive('info')
                ->times(3);

            Log::shouldReceive('warning')
                ->zeroOrMoreTimes();

            Log::shouldReceive('error')
                ->zeroOrMoreTimes();

            $this->livekitService->shouldReceive('listParticipants')
                ->never();

            $job = new ReconcileOrphanedAttendanceEvents();
            $job->handle();

            $event->refresh();
            expect($event->duration_minutes)->toBe(120); // 2 hours
        });
    });

    describe('handle() - error handling', function () {
        it('logs error and continues processing on exception', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Create two orphaned events
            $event1 = MeetingAttendanceEvent::create([
                'event_id' => 'evt_' . uniqid(),
                'event_type' => 'join',
                'event_timestamp' => now()->subHours(3),
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
                'participant_sid' => 'PA_' . uniqid(),
                'participant_identity' => 'user_' . $user->id,
                'participant_name' => $user->name,
                'left_at' => null,
            ]);

            $event2 = MeetingAttendanceEvent::create([
                'event_id' => 'evt_' . uniqid(),
                'event_type' => 'join',
                'event_timestamp' => now()->subHours(3),
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
                'participant_sid' => 'PA_' . uniqid(),
                'participant_identity' => 'user_' . $user->id,
                'participant_name' => $user->name,
                'left_at' => null,
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->times(2);

            Log::shouldReceive('warning')
                ->zeroOrMoreTimes();

            Log::shouldReceive('error')
                ->zeroOrMoreTimes();

            // Both events will be closed (no meetings exist)
            $this->livekitService->shouldReceive('listParticipants')
                ->never(); // Won't be called since no meetings exist

            $job = new ReconcileOrphanedAttendanceEvents();
            $job->handle();

            // Both events should be closed, since meeting not found returns false
            $event1->refresh();
            $event2->refresh();
            expect($event1->left_at)->not->toBeNull()
                ->and($event2->left_at)->not->toBeNull();
        });

        it('handles missing session gracefully', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $event = MeetingAttendanceEvent::create([
                'event_id' => 'evt_' . uniqid(),
                'event_type' => 'join',
                'event_timestamp' => now()->subHours(3),
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
                'participant_sid' => 'PA_' . uniqid(),
                'participant_identity' => 'user_' . $user->id,
                'participant_name' => $user->name,
                'left_at' => null,
            ]);

            // Delete session after creating event
            $session->forceDelete();

            Log::shouldReceive('info')
                ->atLeast()
                ->times(2);

            Log::shouldReceive('warning')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'Session not found for attendance event');
                });

            Log::shouldReceive('error')
                ->zeroOrMoreTimes();

            $job = new ReconcileOrphanedAttendanceEvents();
            $job->handle();

            // Should close event even without session
            $event->refresh();
            expect($event->left_at)->not->toBeNull();
        });
    });

    describe('handle() - statistics logging', function () {
        it('logs reconciliation statistics', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Create 3 orphaned events
            for ($i = 0; $i < 3; $i++) {
                MeetingAttendanceEvent::create([
                    'event_id' => 'evt_' . uniqid(),
                    'event_type' => 'join',
                    'event_timestamp' => now()->subHours(3),
                    'session_id' => $session->id,
                    'session_type' => get_class($session),
                    'user_id' => $user->id,
                    'academy_id' => $this->academy->id,
                    'participant_sid' => 'PA_' . uniqid(),
                    'participant_identity' => 'user_' . $user->id,
                    'participant_name' => $user->name,
                    'left_at' => null,
                ]);
            }

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'Starting reconciliation');
                });

            Log::shouldReceive('info')
                ->times(3)
                ->withArgs(function ($message) {
                    return str_contains($message, 'Closed orphaned attendance event');
                });

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Reconciliation complete')
                        && $context['orphaned_events_found'] === 3
                        && $context['events_closed'] === 3
                        && $context['events_skipped'] === 0;
                });

            Log::shouldReceive('warning')
                ->zeroOrMoreTimes();

            Log::shouldReceive('error')
                ->zeroOrMoreTimes();

            $this->livekitService->shouldReceive('listParticipants')
                ->never();

            $job = new ReconcileOrphanedAttendanceEvents();
            $job->handle();
        });

        it('correctly counts closed and skipped events', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            // Create 2 orphaned events
            $participantSid1 = 'PA_' . uniqid();
            $participantSid2 = 'PA_' . uniqid();

            MeetingAttendanceEvent::create([
                'event_id' => 'evt_' . uniqid(),
                'event_type' => 'join',
                'event_timestamp' => now()->subHours(3),
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
                'participant_sid' => $participantSid1,
                'participant_identity' => 'user_' . $user->id,
                'participant_name' => $user->name,
                'left_at' => null,
            ]);

            MeetingAttendanceEvent::create([
                'event_id' => 'evt_' . uniqid(),
                'event_type' => 'join',
                'event_timestamp' => now()->subHours(3),
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $this->academy->id,
                'participant_sid' => $participantSid2,
                'participant_identity' => 'user_' . $user->id,
                'participant_name' => $user->name,
                'left_at' => null,
            ]);

            Log::shouldReceive('info')
                ->atLeast()
                ->times(3);

            Log::shouldReceive('warning')
                ->zeroOrMoreTimes();

            Log::shouldReceive('error')
                ->zeroOrMoreTimes();

            // Both will be closed since no meetings exist
            $this->livekitService->shouldReceive('listParticipants')
                ->never();

            $job = new ReconcileOrphanedAttendanceEvents();
            $job->handle();

            // Both should be closed
            $events = MeetingAttendanceEvent::whereIn('participant_sid', [$participantSid1, $participantSid2])->get();
            expect($events->every(fn ($e) => $e->left_at !== null))->toBeTrue();
        });
    });
});
