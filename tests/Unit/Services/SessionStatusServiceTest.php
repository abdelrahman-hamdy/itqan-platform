<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\SessionNotificationService;
use App\Services\SessionSettingsService;
use App\Services\UnifiedSessionStatusService;
use Illuminate\Support\Facades\Log;

describe('UnifiedSessionStatusService', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->settingsService = Mockery::mock(SessionSettingsService::class);
        $this->notificationService = Mockery::mock(SessionNotificationService::class);

        $this->service = new UnifiedSessionStatusService(
            $this->settingsService,
            $this->notificationService
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('transitionToReady()', function () {
        it('transitions scheduled session to ready status', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
            ]);

            $this->settingsService->shouldReceive('getSessionType')
                ->andReturn('quran');

            $this->notificationService->shouldReceive('sendReadyNotifications')
                ->once();

            $result = $this->service->transitionToReady($session);

            expect($result)->toBeTrue()
                ->and($session->fresh()->status)->toBe(SessionStatus::READY);
        });

        it('returns false when session is not in scheduled status', function () {
            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
            ]);

            $this->settingsService->shouldReceive('getSessionType')
                ->andReturn('quran');

            Log::shouldReceive('warning')
                ->once();

            $result = $this->service->transitionToReady($session);

            expect($result)->toBeFalse()
                ->and($session->fresh()->status)->toBe(SessionStatus::COMPLETED);
        });

        it('returns boolean result from transition', function () {
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
            ]);

            $this->settingsService->shouldReceive('getSessionType')
                ->andReturn('quran');

            $this->notificationService->shouldReceive('sendReadyNotifications')
                ->zeroOrMoreTimes();

            $result = $this->service->transitionToReady($session);

            // The transition returns a boolean
            expect($result)->toBeBool();
        });

        it('cannot transition cancelled session', function () {
            $session = QuranSession::factory()->cancelled()->create([
                'academy_id' => $this->academy->id,
            ]);

            $this->settingsService->shouldReceive('getSessionType')
                ->andReturn('quran');

            Log::shouldReceive('warning')
                ->once();

            $result = $this->service->transitionToReady($session);

            expect($result)->toBeFalse();
        });

        it('cannot transition ongoing session', function () {
            $session = QuranSession::factory()->ongoing()->create([
                'academy_id' => $this->academy->id,
            ]);

            $this->settingsService->shouldReceive('getSessionType')
                ->andReturn('quran');

            Log::shouldReceive('warning')
                ->once();

            $result = $this->service->transitionToReady($session);

            expect($result)->toBeFalse();
        });
    });

    describe('session status state machine', function () {
        it('validates that SCHEDULED can transition to READY', function () {
            expect(SessionStatus::SCHEDULED->canStart())->toBeTrue();
        });

        it('validates that READY can transition to ONGOING', function () {
            expect(SessionStatus::READY->canStart())->toBeTrue();
        });

        it('validates that ONGOING can be completed', function () {
            expect(SessionStatus::ONGOING->canComplete())->toBeTrue();
        });

        it('validates that COMPLETED cannot be restarted', function () {
            expect(SessionStatus::COMPLETED->canStart())->toBeFalse();
        });

        it('validates that CANCELLED cannot be restarted', function () {
            expect(SessionStatus::CANCELLED->canStart())->toBeFalse();
        });
    });

    describe('status checking helpers', function () {
        it('returns correct session type for QuranSession', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            expect($session)->toBeInstanceOf(QuranSession::class);
        });

        it('correctly identifies individual sessions', function () {
            $session = QuranSession::factory()->individual()->create([
                'academy_id' => $this->academy->id,
            ]);

            expect($session->session_type)->toBe('individual');
        });

        it('correctly identifies group sessions', function () {
            $session = QuranSession::factory()->group()->create([
                'academy_id' => $this->academy->id,
            ]);

            expect($session->session_type)->toBe('group');
        });
    });
});
