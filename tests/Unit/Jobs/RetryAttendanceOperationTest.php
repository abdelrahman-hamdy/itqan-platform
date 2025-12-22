<?php

use App\Jobs\RetryAttendanceOperation;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\MeetingAttendanceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

describe('RetryAttendanceOperation', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create();
        $this->service = Mockery::mock(MeetingAttendanceService::class);
        $this->app->instance(MeetingAttendanceService::class, $this->service);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('job configuration', function () {
        it('has correct retry configuration', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $job = new RetryAttendanceOperation($session->id, 'quran', $user->id, 'join');

            expect($job->tries)->toBe(3)
                ->and($job->backoff)->toBe(60);
        });

        it('has correct job payload', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            $job = new RetryAttendanceOperation($session->id, 'quran', $user->id, 'join');

            expect($job->sessionId)->toBe($session->id)
                ->and($job->sessionType)->toBe('quran')
                ->and($job->userId)->toBe($user->id)
                ->and($job->operation)->toBe('join');
        });
    });

    describe('job dispatch', function () {
        it('can be dispatched to queue', function () {
            Queue::fake();

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            RetryAttendanceOperation::dispatch($session->id, 'quran', $user->id, 'join');

            Queue::assertPushed(RetryAttendanceOperation::class, function ($job) use ($session, $user) {
                return $job->sessionId === $session->id
                    && $job->sessionType === 'quran'
                    && $job->userId === $user->id
                    && $job->operation === 'join';
            });
        });

        it('can dispatch for leave operation', function () {
            Queue::fake();

            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            RetryAttendanceOperation::dispatch($session->id, 'quran', $user->id, 'leave');

            Queue::assertPushed(RetryAttendanceOperation::class, function ($job) {
                return $job->operation === 'leave';
            });
        });
    });

    describe('handle() - join operation', function () {
        it('successfully retries join operation for quran session', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('info')
                ->twice()
                ->withArgs(function ($message) {
                    return str_contains($message, 'Retrying attendance operation')
                        || str_contains($message, 'Attendance operation retry successful');
                });

            $this->service->shouldReceive('handleUserJoin')
                ->once()
                ->with(Mockery::on(function ($arg) use ($session) {
                    return $arg->id === $session->id;
                }), Mockery::on(function ($arg) use ($user) {
                    return $arg->id === $user->id;
                }))
                ->andReturn(true);

            $job = new RetryAttendanceOperation($session->id, 'quran', $user->id, 'join');
            $job->handle($this->service);
        });

        it('successfully retries join operation for academic session', function () {
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('info')
                ->twice();

            $this->service->shouldReceive('handleUserJoin')
                ->once()
                ->andReturn(true);

            $job = new RetryAttendanceOperation($session->id, 'academic', $user->id, 'join');
            $job->handle($this->service);
        });

        it('throws exception when join operation fails', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('info')
                ->once();

            Log::shouldReceive('warning')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'Attendance operation retry returned false');
                });

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message) {
                    return str_contains($message, 'Attendance operation retry failed');
                });

            $this->service->shouldReceive('handleUserJoin')
                ->once()
                ->andReturn(false);

            $job = new RetryAttendanceOperation($session->id, 'quran', $user->id, 'join');

            expect(fn () => $job->handle($this->service))->toThrow(Exception::class);
        });
    });

    describe('handle() - leave operation', function () {
        it('successfully retries leave operation', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('info')
                ->twice();

            $this->service->shouldReceive('handleUserLeave')
                ->once()
                ->with(Mockery::on(function ($arg) use ($session) {
                    return $arg->id === $session->id;
                }), Mockery::on(function ($arg) use ($user) {
                    return $arg->id === $user->id;
                }))
                ->andReturn(true);

            $job = new RetryAttendanceOperation($session->id, 'quran', $user->id, 'leave');
            $job->handle($this->service);
        });

        it('throws exception when leave operation fails', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('info')
                ->once();

            Log::shouldReceive('warning')
                ->once();

            Log::shouldReceive('error')
                ->once();

            $this->service->shouldReceive('handleUserLeave')
                ->once()
                ->andReturn(false);

            $job = new RetryAttendanceOperation($session->id, 'quran', $user->id, 'leave');

            expect(fn () => $job->handle($this->service))->toThrow(Exception::class);
        });
    });

    describe('handle() - error cases', function () {
        it('logs error when session not found', function () {
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('info')
                ->once();

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Session not found for retry')
                        && isset($context['session_id']);
                });

            $this->service->shouldReceive('handleUserJoin')
                ->never();

            $job = new RetryAttendanceOperation(99999, 'quran', $user->id, 'join');
            $job->handle($this->service);
        });

        it('logs error when user not found', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            Log::shouldReceive('info')
                ->once();

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'User not found for retry')
                        && isset($context['user_id']);
                });

            $this->service->shouldReceive('handleUserJoin')
                ->never();

            $job = new RetryAttendanceOperation($session->id, 'quran', 99999, 'join');
            $job->handle($this->service);
        });

        it('logs error for invalid operation type', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('info')
                ->once();

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Invalid operation type')
                        && $context['operation'] === 'invalid';
                });

            $this->service->shouldReceive('handleUserJoin')
                ->never();
            $this->service->shouldReceive('handleUserLeave')
                ->never();

            $job = new RetryAttendanceOperation($session->id, 'quran', $user->id, 'invalid');
            $job->handle($this->service);
        });

        it('logs error and rethrows exception from service', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('info')
                ->once();

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Attendance operation retry failed')
                        && isset($context['error']);
                });

            $this->service->shouldReceive('handleUserJoin')
                ->once()
                ->andThrow(new Exception('Service error'));

            $job = new RetryAttendanceOperation($session->id, 'quran', $user->id, 'join');

            expect(fn () => $job->handle($this->service))->toThrow(Exception::class, 'Service error');
        });
    });

    describe('handle() - logging', function () {
        it('logs attempt number on each retry', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Retrying attendance operation')
                        && isset($context['attempt']);
                });

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) {
                    return str_contains($message, 'Attendance operation retry successful');
                });

            $this->service->shouldReceive('handleUserJoin')
                ->once()
                ->andReturn(true);

            $job = new RetryAttendanceOperation($session->id, 'quran', $user->id, 'join');
            $job->handle($this->service);
        });

        it('logs all context information', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
            ]);
            $user = User::factory()->student()->forAcademy($this->academy)->create();

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($session, $user) {
                    return $context['session_id'] === $session->id
                        && $context['session_type'] === 'quran'
                        && $context['user_id'] === $user->id
                        && $context['operation'] === 'join'
                        && isset($context['attempt']);
                });

            Log::shouldReceive('info')
                ->once();

            $this->service->shouldReceive('handleUserJoin')
                ->once()
                ->andReturn(true);

            $job = new RetryAttendanceOperation($session->id, 'quran', $user->id, 'join');
            $job->handle($this->service);
        });
    });
});
