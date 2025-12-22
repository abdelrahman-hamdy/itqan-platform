<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\BaseSessionMeeting;
use App\Models\QuranSession;
use App\Models\QuranTrialRequest;
use App\Models\User;
use App\Services\TrialRequestSyncService;
use Illuminate\Support\Facades\Log;

describe('TrialRequestSyncService', function () {
    beforeEach(function () {
        $this->service = new TrialRequestSyncService();
        $this->academy = Academy::factory()->create();
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
    });

    describe('syncStatus()', function () {
        it('syncs status when trial session transitions to scheduled', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_PENDING,
            ]);

            $session = QuranSession::factory()->trial()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $this->service->syncStatus($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_SCHEDULED);
        });

        it('syncs status when trial session transitions to completed', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $this->service->syncStatus($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_COMPLETED);
        });

        it('syncs status when trial session transitions to cancelled', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->cancelled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $this->service->syncStatus($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_CANCELLED);
        });

        it('syncs status when trial session transitions to missed', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
                'status' => SessionStatus::MISSED,
            ]);

            $this->service->syncStatus($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_NO_SHOW);
        });

        it('does not sync status for non-trial sessions', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_PENDING,
            ]);

            $session = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $this->service->syncStatus($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_PENDING);
        });

        it('logs warning when trial session has no trial request', function () {
            $session = QuranSession::factory()->trial()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => null,
            ]);

            Log::shouldReceive('warning')
                ->once()
                ->withArgs(function ($message, $context) use ($session) {
                    return $message === 'Trial session has no associated trial request'
                        && $context['session_id'] === $session->id;
                });

            $this->service->syncStatus($session);

            expect(true)->toBeTrue();
        });

        it('does not update status if status has not changed', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $originalUpdatedAt = $trialRequest->updated_at;

            // Small delay to ensure timestamp would change if updated
            sleep(1);

            $this->service->syncStatus($session);

            expect($trialRequest->fresh()->updated_at->timestamp)->toBe($originalUpdatedAt->timestamp);
        });

        it('logs info when trial request status is synced', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_PENDING,
            ]);

            $session = QuranSession::factory()->trial()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($trialRequest, $session) {
                    return $message === 'Trial request status synced'
                        && $context['trial_request_id'] === $trialRequest->id
                        && $context['session_id'] === $session->id
                        && $context['old_status'] === QuranTrialRequest::STATUS_PENDING
                        && $context['new_status'] === QuranTrialRequest::STATUS_SCHEDULED;
                });

            $this->service->syncStatus($session);

            expect(true)->toBeTrue();
        });

        it('does not sync for sessions with ongoing status', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->ongoing()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $this->service->syncStatus($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_SCHEDULED);
        });

        it('does not sync for sessions with ready status', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->ready()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $this->service->syncStatus($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_SCHEDULED);
        });
    });

    describe('completeTrialRequest()', function () {
        it('completes trial request when session is completed', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $this->service->completeTrialRequest($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_COMPLETED)
                ->and($trialRequest->fresh()->completed_at)->not->toBeNull();
        });

        it('stores rating when completing trial request', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $this->service->completeTrialRequest($session, 5);

            expect($trialRequest->fresh()->rating)->toBe(5);
        });

        it('stores feedback when completing trial request', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $this->service->completeTrialRequest($session, 5, 'Excellent session');

            expect($trialRequest->fresh()->feedback)->toBe('Excellent session');
        });

        it('does not complete non-trial sessions', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->individual()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
            ]);

            $this->service->completeTrialRequest($session, 5);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_SCHEDULED);
        });

        it('logs info when trial request is completed', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($trialRequest, $session) {
                    return $message === 'Trial request completed'
                        && $context['trial_request_id'] === $trialRequest->id
                        && $context['session_id'] === $session->id;
                });

            $this->service->completeTrialRequest($session);

            expect(true)->toBeTrue();
        });

        it('can complete without rating or feedback', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $this->service->completeTrialRequest($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_COMPLETED)
                ->and($trialRequest->fresh()->rating)->toBeNull()
                ->and($trialRequest->fresh()->feedback)->toBeNull();
        });
    });

    describe('linkSessionToRequest()', function () {
        it('links newly created trial session to request', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_APPROVED,
            ]);

            $session = QuranSession::factory()->trial()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $this->service->linkSessionToRequest($session);

            expect($trialRequest->fresh()->trial_session_id)->toBe($session->id);
        });

        it('syncs status when linking session to request', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_APPROVED,
            ]);

            $session = QuranSession::factory()->trial()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $this->service->linkSessionToRequest($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_SCHEDULED);
        });

        it('does not link non-trial sessions', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_APPROVED,
            ]);

            $session = QuranSession::factory()->individual()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
            ]);

            $this->service->linkSessionToRequest($session);

            expect($trialRequest->fresh()->trial_session_id)->toBeNull();
        });

        it('does not link when trial_request_id is null', function () {
            $session = QuranSession::factory()->trial()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => null,
            ]);

            $this->service->linkSessionToRequest($session);

            expect(true)->toBeTrue();
        });

        it('logs error when trial request not found', function () {
            $session = QuranSession::factory()->trial()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => 99999,
            ]);

            Log::shouldReceive('error')
                ->once()
                ->withArgs(function ($message, $context) use ($session) {
                    return $message === 'Trial request not found for session'
                        && $context['session_id'] === $session->id
                        && $context['trial_request_id'] === 99999;
                });

            $this->service->linkSessionToRequest($session);

            expect(true)->toBeTrue();
        });

        it('does not update link if already linked', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $trialRequest->update(['trial_session_id' => $session->id]);

            $originalUpdatedAt = $trialRequest->updated_at;

            sleep(1);

            $this->service->linkSessionToRequest($session);

            expect($trialRequest->fresh()->trial_session_id)->toBe($session->id)
                ->and($trialRequest->fresh()->updated_at->timestamp)->toBe($originalUpdatedAt->timestamp);
        });

        it('logs info when trial session is linked', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_APPROVED,
            ]);

            $session = QuranSession::factory()->trial()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context) use ($trialRequest, $session) {
                    return str_contains($message, 'Trial session linked to request')
                        && $context['trial_request_id'] === $trialRequest->id
                        && $context['session_id'] === $session->id;
                });

            $this->service->linkSessionToRequest($session);

            expect(true)->toBeTrue();
        });
    });

    describe('getSchedulingInfo()', function () {
        it('returns null when trial request has no session', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_PENDING,
            ]);

            $info = $this->service->getSchedulingInfo($trialRequest);

            expect($info)->toBeNull();
        });

        it('returns scheduling info when session exists', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $trialRequest->update(['trial_session_id' => $session->id]);

            $info = $this->service->getSchedulingInfo($trialRequest);

            expect($info)->toBeArray()
                ->and($info)->toHaveKey('scheduled_at')
                ->and($info)->toHaveKey('duration_minutes')
                ->and($info)->toHaveKey('status')
                ->and($info)->toHaveKey('can_join');
        });

        it('includes meeting information when meeting exists', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $meeting = BaseSessionMeeting::create([
                'academy_id' => $this->academy->id,
                'session_type' => 'App\Models\QuranSession',
                'session_id' => $session->id,
                'room_name' => 'test-room-123',
                'platform' => 'livekit',
                'status' => 'scheduled',
                'scheduled_at' => $session->scheduled_at,
            ]);

            $trialRequest->update(['trial_session_id' => $session->id]);

            $info = $this->service->getSchedulingInfo($trialRequest);

            expect($info)->toHaveKey('room_name')
                ->and($info['room_name'])->toBe('test-room-123');
        });

        it('returns correct session status in scheduling info', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_COMPLETED,
            ]);

            $session = QuranSession::factory()->trial()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
            ]);

            $trialRequest->update(['trial_session_id' => $session->id]);

            $info = $this->service->getSchedulingInfo($trialRequest);

            expect($info['status'])->toBe(SessionStatus::COMPLETED->value);
        });

        it('includes scheduled_at and duration_minutes in scheduling info', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $scheduledTime = now()->addDay()->setTime(10, 0);
            $session = QuranSession::factory()->trial()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
                'scheduled_at' => $scheduledTime,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $trialRequest->update(['trial_session_id' => $session->id]);

            $info = $this->service->getSchedulingInfo($trialRequest);

            expect($info['scheduled_at'])->toEqual($scheduledTime)
                ->and($info['duration_minutes'])->toBe(60);
        });
    });

    describe('status mapping', function () {
        it('correctly maps SCHEDULED session status', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_PENDING,
            ]);

            $session = QuranSession::factory()->trial()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $this->service->syncStatus($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_SCHEDULED);
        });

        it('correctly maps COMPLETED session status', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
                'status' => SessionStatus::COMPLETED,
            ]);

            $this->service->syncStatus($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_COMPLETED);
        });

        it('correctly maps CANCELLED session status', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
                'status' => SessionStatus::CANCELLED,
            ]);

            $this->service->syncStatus($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_CANCELLED);
        });

        it('correctly maps MISSED session status to NO_SHOW', function () {
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacher->quranTeacherProfile->id,
                'student_name' => 'Test Student',
                'current_level' => QuranTrialRequest::LEVEL_BEGINNER,
                'status' => QuranTrialRequest::STATUS_SCHEDULED,
            ]);

            $session = QuranSession::factory()->trial()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'trial_request_id' => $trialRequest->id,
                'status' => SessionStatus::MISSED,
            ]);

            $this->service->syncStatus($session);

            expect($trialRequest->fresh()->status)->toBe(QuranTrialRequest::STATUS_NO_SHOW);
        });
    });
});
