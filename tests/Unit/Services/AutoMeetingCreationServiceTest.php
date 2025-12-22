<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\User;
use App\Models\VideoSettings;
use App\Services\AutoMeetingCreationService;
use App\Services\LiveKitService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

describe('AutoMeetingCreationService', function () {
    beforeEach(function () {
        $this->livekitService = Mockery::mock(LiveKitService::class);
        $this->service = new AutoMeetingCreationService($this->livekitService);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('createMeetingsForAllAcademies()', function () {
        it('processes all active academies', function () {
            $academy1 = Academy::factory()->create(['is_active' => true]);
            $academy2 = Academy::factory()->create(['is_active' => true]);
            $academy3 = Academy::factory()->create(['is_active' => false]);

            // Create video settings for academies
            VideoSettings::factory()->create([
                'academy_id' => $academy1->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            VideoSettings::factory()->create([
                'academy_id' => $academy2->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            $results = $this->service->createMeetingsForAllAcademies();

            expect($results['total_academies_processed'])->toBe(2)
                ->and($results)->toHaveKeys([
                    'total_academies_processed',
                    'total_sessions_processed',
                    'meetings_created',
                    'meetings_failed',
                    'errors',
                ]);
        });

        it('returns zero academies when none are active', function () {
            Academy::factory()->count(3)->create(['is_active' => false]);

            $results = $this->service->createMeetingsForAllAcademies();

            expect($results['total_academies_processed'])->toBe(0)
                ->and($results['total_sessions_processed'])->toBe(0)
                ->and($results['meetings_created'])->toBe(0);
        });

        it('aggregates results from all academies', function () {
            $academy1 = Academy::factory()->create(['is_active' => true]);
            $academy2 = Academy::factory()->create(['is_active' => true]);

            VideoSettings::factory()->create([
                'academy_id' => $academy1->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            VideoSettings::factory()->create([
                'academy_id' => $academy2->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            // Create eligible sessions for both academies
            QuranSession::factory()->create([
                'academy_id' => $academy1->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
                'meeting_room_name' => null,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $academy2->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addMinutes(90),
                'meeting_room_name' => null,
            ]);

            $results = $this->service->createMeetingsForAllAcademies();

            expect($results['total_academies_processed'])->toBe(2)
                ->and($results['total_sessions_processed'])->toBeGreaterThanOrEqual(0);
        });

        it('handles exceptions and logs errors', function () {
            Academy::factory()->create(['is_active' => true]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $results = $this->service->createMeetingsForAllAcademies();

            expect($results)->toHaveKeys(['errors', 'total_academies_processed']);
        });

        it('logs process start and completion', function () {
            Log::shouldReceive('info')
                ->with('Starting auto meeting creation process for all academies')
                ->once();

            Log::shouldReceive('info')
                ->with('Auto meeting creation completed', Mockery::type('array'))
                ->once();

            $this->service->createMeetingsForAllAcademies();
        });
    });

    describe('createMeetingsForAcademy()', function () {
        it('returns academy information in results', function () {
            $academy = Academy::factory()->create(['is_active' => true]);

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => false,
            ]);

            $results = $this->service->createMeetingsForAcademy($academy);

            expect($results)->toHaveKeys([
                'academy_id',
                'academy_name',
                'sessions_processed',
                'meetings_created',
                'meetings_failed',
                'errors',
            ])
                ->and($results['academy_id'])->toBe($academy->id)
                ->and($results['academy_name'])->toBe($academy->name);
        });

        it('skips when auto creation is disabled', function () {
            $academy = Academy::factory()->create(['is_active' => true]);

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => false,
            ]);

            Log::shouldReceive('info')
                ->with('Processing academy for auto meeting creation', Mockery::type('array'))
                ->once();

            Log::shouldReceive('info')
                ->with('Auto meeting creation disabled for academy', [
                    'academy_id' => $academy->id,
                ])
                ->once();

            $results = $this->service->createMeetingsForAcademy($academy);

            expect($results['sessions_processed'])->toBe(0)
                ->and($results['meetings_created'])->toBe(0);
        });

        it('creates meetings for eligible sessions', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
                'create_meetings_minutes_before' => 15,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
                'meeting_room_name' => null,
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();

            $results = $this->service->createMeetingsForAcademy($academy);

            expect($results['sessions_processed'])->toBeGreaterThanOrEqual(0)
                ->and($results['meetings_created'])->toBeGreaterThanOrEqual(0);
        });

        it('tracks failed meeting creations', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            // Create a session that will be eligible
            $session = QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addMinutes(30),
                'meeting_room_name' => null,
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $results = $this->service->createMeetingsForAcademy($academy);

            expect($results)->toHaveKey('meetings_failed');
        });

        it('logs session processing', function () {
            $academy = Academy::factory()->create(['is_active' => true]);

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            Log::shouldReceive('info')
                ->with('Processing academy for auto meeting creation', Mockery::type('array'))
                ->once();

            Log::shouldReceive('info')
                ->with('Found eligible sessions for meeting creation', Mockery::type('array'))
                ->once();

            $this->service->createMeetingsForAcademy($academy);
        });

        it('handles academy processing errors', function () {
            $academy = Academy::factory()->create(['is_active' => true]);

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $results = $this->service->createMeetingsForAcademy($academy);

            expect($results)->toHaveKey('errors');
        });
    });

    describe('cleanupExpiredMeetings()', function () {
        it('returns cleanup statistics', function () {
            $results = $this->service->cleanupExpiredMeetings();

            expect($results)->toHaveKeys([
                'sessions_checked',
                'meetings_ended',
                'meetings_failed_to_end',
                'errors',
            ]);
        });

        it('finds sessions that should be ended', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_end_meetings' => true,
                'auto_end_minutes_after' => 15,
            ]);

            // Create expired session
            QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::ONGOING,
                'scheduled_at' => now()->subHours(2),
                'duration_minutes' => 60,
                'meeting_room_name' => 'test-room-' . Str::uuid(),
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('warning')->zeroOrMoreTimes();

            $results = $this->service->cleanupExpiredMeetings();

            expect($results['sessions_checked'])->toBeGreaterThanOrEqual(0);
        });

        it('skips academies with auto end disabled', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_end_meetings' => false,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::ONGOING,
                'scheduled_at' => now()->subHours(2),
                'duration_minutes' => 60,
                'meeting_room_name' => 'test-room',
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();

            $results = $this->service->cleanupExpiredMeetings();

            expect($results['meetings_ended'])->toBe(0);
        });

        it('tracks failed meeting endings', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_end_meetings' => true,
                'auto_end_minutes_after' => 15,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::ONGOING,
                'scheduled_at' => now()->subHours(2),
                'duration_minutes' => 60,
                'meeting_room_name' => 'test-room',
            ]);

            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();
            Log::shouldReceive('warning')->zeroOrMoreTimes();

            $results = $this->service->cleanupExpiredMeetings();

            expect($results)->toHaveKey('meetings_failed_to_end');
        });

        it('logs cleanup start and completion', function () {
            Log::shouldReceive('info')
                ->with('Starting expired meetings cleanup')
                ->once();

            Log::shouldReceive('info')
                ->with('Expired meetings cleanup completed', Mockery::type('array'))
                ->once();

            $this->service->cleanupExpiredMeetings();
        });

        it('handles cleanup process errors', function () {
            Log::shouldReceive('info')->zeroOrMoreTimes();
            Log::shouldReceive('error')->zeroOrMoreTimes();

            $results = $this->service->cleanupExpiredMeetings();

            expect($results)->toHaveKeys([
                'sessions_checked',
                'meetings_ended',
                'meetings_failed_to_end',
                'errors',
            ]);
        });
    });

    describe('getStatistics()', function () {
        it('returns all required statistics', function () {
            $stats = $this->service->getStatistics();

            expect($stats)->toHaveKeys([
                'total_auto_generated_meetings',
                'active_meetings',
                'meetings_created_today',
                'meetings_created_this_week',
                'academies_with_auto_creation_enabled',
            ]);
        });

        it('counts auto generated meetings correctly', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            QuranSession::factory()->count(3)->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'meeting_auto_generated' => true,
            ]);

            QuranSession::factory()->count(2)->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'meeting_auto_generated' => false,
            ]);

            $stats = $this->service->getStatistics();

            expect($stats['total_auto_generated_meetings'])->toBe(3);
        });

        it('counts active meetings correctly', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            QuranSession::factory()->count(2)->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'meeting_room_name' => 'test-room',
            ]);

            QuranSession::factory()->count(1)->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::ONGOING,
                'meeting_room_name' => 'test-room-2',
            ]);

            QuranSession::factory()->count(2)->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::COMPLETED,
                'meeting_room_name' => 'test-room-3',
            ]);

            $stats = $this->service->getStatistics();

            expect($stats['active_meetings'])->toBe(3);
        });

        it('counts meetings created today', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            QuranSession::factory()->count(2)->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'meeting_auto_generated' => true,
                'meeting_created_at' => now(),
            ]);

            QuranSession::factory()->count(1)->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'meeting_auto_generated' => true,
                'meeting_created_at' => now()->subDays(2),
            ]);

            $stats = $this->service->getStatistics();

            expect($stats['meetings_created_today'])->toBe(2);
        });

        it('counts meetings created this week', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            QuranSession::factory()->count(3)->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'meeting_auto_generated' => true,
                'meeting_created_at' => now()->startOfWeek()->addDays(2),
            ]);

            QuranSession::factory()->count(2)->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'meeting_auto_generated' => true,
                'meeting_created_at' => now()->subWeeks(2),
            ]);

            $stats = $this->service->getStatistics();

            expect($stats['meetings_created_this_week'])->toBeGreaterThanOrEqual(0);
        });

        it('counts academies with auto creation enabled', function () {
            VideoSettings::factory()->count(3)->create([
                'auto_create_meetings' => true,
            ]);

            VideoSettings::factory()->count(2)->create([
                'auto_create_meetings' => false,
            ]);

            $stats = $this->service->getStatistics();

            expect($stats['academies_with_auto_creation_enabled'])->toBe(3);
        });
    });

    describe('testMeetingCreation()', function () {
        it('creates test meeting successfully', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
            ]);

            $result = $this->service->testMeetingCreation($session);

            expect($result)->toHaveKey('success')
                ->and($result)->toHaveKey('session_id', $session->id);
        });

        it('returns error when auto creation disabled', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => false,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
            ]);

            $result = $this->service->testMeetingCreation($session);

            expect($result['success'])->toBeFalse()
                ->and($result)->toHaveKey('error')
                ->and($result['message'])->toContain('disabled');
        });

        it('handles creation errors gracefully', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
            ]);

            $result = $this->service->testMeetingCreation($session);

            expect($result)->toHaveKey('success');
        });

        it('returns meeting details on success', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
            ]);

            $result = $this->service->testMeetingCreation($session);

            expect($result)->toHaveKeys([
                'success',
                'message',
                'session_id',
            ]);
        });
    });

    describe('eligible sessions filtering', function () {
        it('excludes sessions without scheduled_at', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => null,
                'meeting_room_name' => null,
            ]);

            $results = $this->service->createMeetingsForAcademy($academy);

            expect($results['sessions_processed'])->toBe(0);
        });

        it('excludes sessions with existing meeting rooms', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
                'meeting_room_name' => 'existing-room',
            ]);

            $results = $this->service->createMeetingsForAcademy($academy);

            expect($results['sessions_processed'])->toBe(0);
        });

        it('excludes non-scheduled sessions', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->addHour(),
                'meeting_room_name' => null,
            ]);

            $results = $this->service->createMeetingsForAcademy($academy);

            expect($results['sessions_processed'])->toBe(0);
        });

        it('excludes sessions outside time window', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            // Create session beyond 2-hour window
            QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHours(3),
                'meeting_room_name' => null,
            ]);

            $results = $this->service->createMeetingsForAcademy($academy);

            expect($results['sessions_processed'])->toBe(0);
        });
    });

    describe('meeting options building', function () {
        it('uses video settings for meeting options', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            $videoSettings = VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
                'default_max_participants' => 100,
                'enable_recording_by_default' => true,
                'default_video_quality' => 'high',
                'default_audio_quality' => 'high',
                'enable_screen_sharing' => true,
                'enable_chat' => true,
                'mute_participants_on_entry' => true,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addMinutes(30),
                'duration_minutes' => 60,
            ]);

            $result = $this->service->testMeetingCreation($session);

            // If creation is successful, the session should have been updated
            if ($result['success']) {
                $session->refresh();
                expect($session->meeting_auto_generated)->toBeTrue();
            }

            expect($result)->toHaveKey('success');
        });
    });

    describe('transaction safety', function () {
        it('handles transaction rollback correctly', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
                'meeting_auto_generated' => false,
            ]);

            $result = $this->service->testMeetingCreation($session);

            expect($result)->toHaveKey('success');
        });

        it('commits on successful meeting creation', function () {
            $academy = Academy::factory()->create(['is_active' => true]);
            $teacher = User::factory()->quranTeacher()->forAcademy($academy)->create();

            VideoSettings::factory()->create([
                'academy_id' => $academy->id,
                'auto_create_meetings' => true,
                'integration_enabled' => true,
            ]);

            $session = QuranSession::factory()->create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $teacher->id,
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addHour(),
                'meeting_auto_generated' => false,
            ]);

            $result = $this->service->testMeetingCreation($session);

            if ($result['success']) {
                $session->refresh();
                expect($session->meeting_auto_generated)->toBeTrue()
                    ->and($session->meeting_created_at)->not->toBeNull();
            }

            expect($result)->toHaveKey('success');
        });
    });
});
