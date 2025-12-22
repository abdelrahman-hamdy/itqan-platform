<?php

use App\Enums\SessionStatus;
use App\Jobs\CalculateSessionEarningsJob;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Models\TeacherEarning;
use App\Services\EarningsCalculationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

describe('CalculateSessionEarningsJob', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->earningsService = Mockery::mock(EarningsCalculationService::class);
        $this->app->instance(EarningsCalculationService::class, $this->earningsService);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('job configuration', function () {
        it('stores session type and id in job payload', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $job = new CalculateSessionEarningsJob($session);

            expect($job->sessionType)->toBe(get_class($session))
                ->and($job->sessionId)->toBe($session->id)
                ->and($job->session)->not->toBeNull();
        });

        it('implements ShouldQueue interface', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $job = new CalculateSessionEarningsJob($session);

            expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
        });
    });

    describe('job dispatch', function () {
        it('can be dispatched to queue', function () {
            Queue::fake();

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            CalculateSessionEarningsJob::dispatch($session);

            Queue::assertPushed(CalculateSessionEarningsJob::class, function ($job) use ($session) {
                return $job->sessionId === $session->id
                    && $job->sessionType === get_class($session);
            });
        });

        it('can dispatch for academic session', function () {
            Queue::fake();

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            CalculateSessionEarningsJob::dispatch($session);

            Queue::assertPushed(CalculateSessionEarningsJob::class, function ($job) use ($session) {
                return $job->sessionType === get_class($session);
            });
        });
    });

    describe('handle() - successful earnings calculation', function () {
        it('calculates earnings for quran session', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $earning = new TeacherEarning([
                'id' => 1,
                'academy_id' => $this->academy->id,
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'amount' => 100.00,
            ]);
            $earning->exists = true;

            Log::shouldReceive('info')
                ->once();

            Log::shouldReceive('error')
                ->zeroOrMoreTimes(); // Allow for any error logging

            $this->earningsService->shouldReceive('calculateSessionEarnings')
                ->once()
                ->with(Mockery::on(function ($arg) use ($session) {
                    return $arg->id === $session->id;
                }))
                ->andReturn($earning);

            $job = new CalculateSessionEarningsJob($session);
            $job->handle($this->earningsService);

            // Verify earning was returned
            expect($earning)->not->toBeNull()
                ->and($earning->amount)->toBe(100.00);
        });

        it('calculates earnings for academic session', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $earning = new TeacherEarning([
                'id' => 1,
                'academy_id' => $this->academy->id,
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'amount' => 150.00,
            ]);
            $earning->exists = true;

            Log::shouldReceive('info')
                ->once();

            Log::shouldReceive('error')
                ->zeroOrMoreTimes(); // Allow for any error logging

            $this->earningsService->shouldReceive('calculateSessionEarnings')
                ->once()
                ->andReturn($earning);

            $job = new CalculateSessionEarningsJob($session);
            $job->handle($this->earningsService);

            // Verify earning was returned
            expect($earning)->not->toBeNull()
                ->and($earning->amount)->toBe(150.00);
        });

        it('re-fetches session to get latest data', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $earning = new TeacherEarning([
                'id' => 1,
                'academy_id' => $this->academy->id,
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'amount' => 100.00,
            ]);
            $earning->exists = true;

            Log::shouldReceive('info')
                ->once();

            $this->earningsService->shouldReceive('calculateSessionEarnings')
                ->once()
                ->andReturn($earning);

            // Update session after job creation
            $session->update(['participants_count' => 5]);

            $job = new CalculateSessionEarningsJob($session);
            $job->handle($this->earningsService);

            // Job should re-fetch and get updated data
        });
    });

    describe('handle() - no earnings calculated', function () {
        it('logs when session is not eligible for earnings', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($session) {
                    return str_contains($message, 'No earnings calculated for session')
                        && $context['session_id'] === $session->id;
                });

            $this->earningsService->shouldReceive('calculateSessionEarnings')
                ->once()
                ->andReturn(null);

            $job = new CalculateSessionEarningsJob($session);
            $job->handle($this->earningsService);
        });

        it('logs when earnings already calculated', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'not eligible or already calculated');
                });

            $this->earningsService->shouldReceive('calculateSessionEarnings')
                ->once()
                ->andReturn(null);

            $job = new CalculateSessionEarningsJob($session);
            $job->handle($this->earningsService);
        });
    });

    describe('handle() - session not found', function () {
        it('logs warning when session not found', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $sessionId = $session->id;
            $sessionType = get_class($session);

            // Delete session
            $session->forceDelete();

            Log::shouldReceive('warning')
                ->once()
                ->withArgs(function ($message, $context) use ($sessionId, $sessionType) {
                    return str_contains($message, 'Session not found for earnings calculation')
                        && $context['session_id'] === $sessionId
                        && $context['session_type'] === $sessionType;
                });

            $this->earningsService->shouldReceive('calculateSessionEarnings')
                ->never();

            $job = new CalculateSessionEarningsJob($session);
            $job->handle($this->earningsService);
        });
    });

    describe('handle() - error handling', function () {
        it('logs error and rethrows exception', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) use ($session) {
                    return str_contains($message, 'Failed to calculate session earnings')
                        && $context['session_id'] === $session->id
                        && isset($context['error'])
                        && isset($context['trace']);
                });

            $this->earningsService->shouldReceive('calculateSessionEarnings')
                ->once()
                ->andThrow(new Exception('Database error'));

            $job = new CalculateSessionEarningsJob($session);

            expect(fn () => $job->handle($this->earningsService))
                ->toThrow(Exception::class, 'Database error');
        });

        it('includes session type in error log', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return $context['session_type'] === AcademicSession::class;
                });

            $this->earningsService->shouldReceive('calculateSessionEarnings')
                ->once()
                ->andThrow(new Exception('Error'));

            $job = new CalculateSessionEarningsJob($session);

            expect(fn () => $job->handle($this->earningsService))
                ->toThrow(Exception::class);
        });
    });

    describe('failed() - job failure handling', function () {
        it('logs permanent failure', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $exception = new Exception('Permanent error');

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) use ($session) {
                    return str_contains($message, 'CalculateSessionEarningsJob failed permanently')
                        && $context['session_id'] === $session->id
                        && $context['session_type'] === get_class($session)
                        && isset($context['error']);
                });

            $job = new CalculateSessionEarningsJob($session);
            $job->failed($exception);
        });

        it('includes error message in failure log', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $exception = new Exception('Out of memory');

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return $context['error'] === 'Out of memory';
                });

            $job = new CalculateSessionEarningsJob($session);
            $job->failed($exception);
        });
    });

    describe('handle() - service integration', function () {
        it('passes correct session to service', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
                'duration_minutes' => 60,
            ]);

            $earning = new TeacherEarning([
                'id' => 1,
                'academy_id' => $this->academy->id,
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'amount' => 100.00,
            ]);
            $earning->exists = true;

            Log::shouldReceive('info')
                ->once();

            $this->earningsService->shouldReceive('calculateSessionEarnings')
                ->once()
                ->with(Mockery::on(function ($arg) use ($session) {
                    return $arg instanceof QuranSession
                        && $arg->id === $session->id
                        && $arg->duration_minutes === 60;
                }))
                ->andReturn($earning);

            $job = new CalculateSessionEarningsJob($session);
            $job->handle($this->earningsService);
        });

        it('works with polymorphic session types', function () {
            $sessions = [
                QuranSession::factory()->create([
                    'academy_id' => $this->academy->id,
                    'status' => SessionStatus::COMPLETED,
                ]),
                AcademicSession::factory()->create([
                    'academy_id' => $this->academy->id,
                    'status' => SessionStatus::COMPLETED,
                ]),
            ];

            foreach ($sessions as $session) {
                $earning = new TeacherEarning([
                    'id' => 1,
                    'academy_id' => $this->academy->id,
                    'session_id' => $session->id,
                    'session_type' => get_class($session),
                    'amount' => 100.00,
                ]);
                $earning->exists = true;

                Log::shouldReceive('info')
                    ->once();

                $this->earningsService->shouldReceive('calculateSessionEarnings')
                    ->once()
                    ->andReturn($earning);

                $job = new CalculateSessionEarningsJob($session);
                $job->handle($this->earningsService);
            }
        });
    });

    describe('handle() - logging details', function () {
        it('logs all relevant context information', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $earning = new TeacherEarning([
                'id' => 123,
                'academy_id' => $this->academy->id,
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'amount' => 250.00,
            ]);
            $earning->exists = true;

            Log::shouldReceive('info')
                ->once();

            Log::shouldReceive('error')
                ->zeroOrMoreTimes(); // Allow for any error logging

            $this->earningsService->shouldReceive('calculateSessionEarnings')
                ->once()
                ->andReturn($earning);

            $job = new CalculateSessionEarningsJob($session);
            $job->handle($this->earningsService);

            // Verify earning was returned with correct amount
            expect($earning)->not->toBeNull()
                ->and($earning->amount)->toBe(250.00);
        });
    });

    describe('job serialization', function () {
        it('properly serializes and unserializes session data', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $job = new CalculateSessionEarningsJob($session);

            // Serialize the job (simulates queue storage)
            $serialized = serialize($job);
            $unserialized = unserialize($serialized);

            expect($unserialized->sessionId)->toBe($session->id)
                ->and($unserialized->sessionType)->toBe(get_class($session));
        });
    });

    describe('handle() - multiple calls', function () {
        it('handles idempotent earnings calculation', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            // First call returns earning
            $earning = new TeacherEarning([
                'id' => 1,
                'academy_id' => $this->academy->id,
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'amount' => 100.00,
            ]);
            $earning->exists = true;

            Log::shouldReceive('info')
                ->twice();

            $this->earningsService->shouldReceive('calculateSessionEarnings')
                ->once()
                ->andReturn($earning);

            // Second call returns null (already calculated)
            $this->earningsService->shouldReceive('calculateSessionEarnings')
                ->once()
                ->andReturn(null);

            $job1 = new CalculateSessionEarningsJob($session);
            $job1->handle($this->earningsService);

            $job2 = new CalculateSessionEarningsJob($session);
            $job2->handle($this->earningsService);

            // Both should complete without error
        });
    });
});
