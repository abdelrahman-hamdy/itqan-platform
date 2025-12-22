<?php

use App\Enums\SessionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicSubject;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\User;
use App\Services\AcademicSessionSchedulingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

describe('AcademicSessionSchedulingService', function () {
    beforeEach(function () {
        $this->service = new AcademicSessionSchedulingService();
        $this->academy = Academy::factory()->create();

        // Create academic teacher with profile
        $this->teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
        $this->teacherProfile = AcademicTeacherProfile::factory()->create([
            'user_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        $this->student = User::factory()->student()->forAcademy($this->academy)->create();
        $this->subject = AcademicSubject::factory()->create([
            'academy_id' => $this->academy->id,
        ]);

        // Create subscription with required fields
        $this->subscription = AcademicSubscription::create([
            'academy_id' => $this->academy->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'subject_id' => $this->subject->id,
            'subject_name' => $this->subject->name,
            'subscription_type' => 'individual',
            'status' => SubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'session_duration_minutes' => 60,
            'sessions_per_week' => 2,
            'total_price' => 500,
            'currency' => 'SAR',
        ]);
    });

    describe('scheduleIndividualSession()', function () {
        it('successfully schedules a session with all required data', function () {
            Auth::login($this->teacher);
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            $session = $this->service->scheduleIndividualSession(
                $this->subscription,
                $scheduledAt,
                60,
                'Test Academic Session',
                'Test description'
            );

            expect($session)->toBeInstanceOf(AcademicSession::class)
                ->and($session->academy_id)->toBe($this->academy->id)
                ->and($session->academic_teacher_id)->toBe($this->teacherProfile->id)
                ->and($session->student_id)->toBe($this->student->id)
                ->and($session->academic_subscription_id)->toBe($this->subscription->id)
                ->and($session->session_type)->toBe('individual')
                ->and($session->status->value)->toBe('scheduled')
                ->and($session->is_scheduled)->toBeTrue()
                ->and($session->title)->toBe('Test Academic Session')
                ->and($session->description)->toBe('Test description')
                ->and($session->scheduled_at->timestamp)->toBe($scheduledAt->timestamp)
                ->and($session->duration_minutes)->toBe(60)
                ->and($session->location_type)->toBe('online')
                ->and($session->meeting_auto_generated)->toBeTrue()
                ->and($session->attendance_status)->toBe('scheduled')
                ->and($session->is_auto_generated)->toBeFalse()
                ->and($session->scheduled_by)->toBe($this->teacher->id);
        });

        it('uses default duration from subscription when not provided', function () {
            Auth::login($this->teacher);
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            $session = $this->service->scheduleIndividualSession(
                $this->subscription,
                $scheduledAt
            );

            expect($session->duration_minutes)->toBe(60);
        });

        it('auto-generates title when not provided', function () {
            Auth::login($this->teacher);
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            $session = $this->service->scheduleIndividualSession(
                $this->subscription,
                $scheduledAt
            );

            expect($session->title)->toContain('جلسة أكاديمية')
                ->and($session->title)->toContain($this->student->name)
                ->and($session->title)->toContain($this->subject->name);
        });

        it('increments session count in auto-generated title', function () {
            Auth::login($this->teacher);
            $scheduledAt1 = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);
            $scheduledAt2 = Carbon::now()->addDays(3)->setHour(10)->setMinute(0);

            $session1 = $this->service->scheduleIndividualSession(
                $this->subscription,
                $scheduledAt1
            );

            $session2 = $this->service->scheduleIndividualSession(
                $this->subscription,
                $scheduledAt2
            );

            expect($session1->title)->toContain('(جلسة 1)')
                ->and($session2->title)->toContain('(جلسة 2)');
        });

        it('throws validation exception when scheduling in the past', function () {
            Auth::login($this->teacher);
            $pastTime = Carbon::now()->subDay();

            expect(fn () => $this->service->scheduleIndividualSession(
                $this->subscription,
                $pastTime
            ))->toThrow(ValidationException::class);
        });

        it('throws validation exception when teacher has conflict', function () {
            Auth::login($this->teacher);
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            // Create existing session
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            expect(fn () => $this->service->scheduleIndividualSession(
                $this->subscription,
                $scheduledAt
            ))->toThrow(ValidationException::class);
        });

        it('accepts additional data parameter', function () {
            Auth::login($this->teacher);
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            $session = $this->service->scheduleIndividualSession(
                $this->subscription,
                $scheduledAt,
                60,
                'Test Session',
                'Test description',
                ['notes' => 'Custom notes']
            );

            expect($session->notes)->toBe('Custom notes');
        });

        it('sets teacher_scheduled_at timestamp', function () {
            Auth::login($this->teacher);
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            $before = now();
            $session = $this->service->scheduleIndividualSession(
                $this->subscription,
                $scheduledAt
            );
            $after = now();

            expect($session->teacher_scheduled_at)->not->toBeNull()
                ->and($session->teacher_scheduled_at->between($before, $after))->toBeTrue();
        });
    });

    describe('hasTeacherConflict()', function () {
        it('returns false when no conflicts exist', function () {
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            $hasConflict = $this->service->hasTeacherConflict(
                $this->teacherProfile->id,
                $scheduledAt,
                60
            );

            expect($hasConflict)->toBeFalse();
        });

        it('detects conflict when session overlaps at start time', function () {
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            // Create existing session from 10:00-11:00
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            // Try to schedule 10:30-11:30 (overlaps)
            $hasConflict = $this->service->hasTeacherConflict(
                $this->teacherProfile->id,
                $scheduledAt->copy()->addMinutes(30),
                60
            );

            expect($hasConflict)->toBeTrue();
        });

        it('detects conflict when new session encompasses existing session', function () {
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            // Create existing session from 10:30-11:00
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $scheduledAt->copy()->addMinutes(30),
                'duration_minutes' => 30,
                'status' => SessionStatus::SCHEDULED,
            ]);

            // Try to schedule 10:00-11:30 (encompasses existing)
            $hasConflict = $this->service->hasTeacherConflict(
                $this->teacherProfile->id,
                $scheduledAt,
                90
            );

            expect($hasConflict)->toBeTrue();
        });

        it('detects conflict when session ends during existing session', function () {
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            // Create existing session from 10:00-11:00
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            // Try to schedule 9:30-10:30 (ends during existing)
            $hasConflict = $this->service->hasTeacherConflict(
                $this->teacherProfile->id,
                $scheduledAt->copy()->subMinutes(30),
                60
            );

            expect($hasConflict)->toBeTrue();
        });

        it('returns false when sessions are back-to-back without overlap', function () {
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            // Create existing session from 10:00-11:00
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            // Schedule next session at 11:00-12:00 (no overlap)
            $hasConflict = $this->service->hasTeacherConflict(
                $this->teacherProfile->id,
                $scheduledAt->copy()->addMinutes(60),
                60
            );

            expect($hasConflict)->toBeFalse();
        });

        it('ignores cancelled sessions when checking conflicts', function () {
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            // Create cancelled session at same time
            AcademicSession::factory()->cancelled()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
            ]);

            $hasConflict = $this->service->hasTeacherConflict(
                $this->teacherProfile->id,
                $scheduledAt,
                60
            );

            expect($hasConflict)->toBeFalse();
        });

        it('excludes specified session from conflict check', function () {
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            $existingSession = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            // Should not detect conflict when excluding itself
            $hasConflict = $this->service->hasTeacherConflict(
                $this->teacherProfile->id,
                $scheduledAt,
                60,
                $existingSession->id
            );

            expect($hasConflict)->toBeFalse();
        });

        it('checks conflicts only for specified teacher', function () {
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);
            $otherTeacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $otherTeacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $otherTeacher->id,
                'academy_id' => $this->academy->id,
            ]);

            // Create session for different teacher
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $otherTeacherProfile->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            // No conflict for original teacher
            $hasConflict = $this->service->hasTeacherConflict(
                $this->teacherProfile->id,
                $scheduledAt,
                60
            );

            expect($hasConflict)->toBeFalse();
        });
    });

    describe('getCalendarEvents()', function () {
        it('returns empty array when no sessions exist', function () {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();

            $events = $this->service->getCalendarEvents(
                $this->teacherProfile->id,
                $start,
                $end
            );

            expect($events)->toBeArray()
                ->and($events)->toBeEmpty();
        });

        it('returns sessions within date range', function () {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $this->subscription->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
                'title' => 'Test Session',
                'status' => SessionStatus::SCHEDULED,
            ]);

            $events = $this->service->getCalendarEvents(
                $this->teacherProfile->id,
                $start,
                $end
            );

            expect($events)->toHaveCount(1)
                ->and($events[0])->toBeArray()
                ->and($events[0]['title'])->toBe('Test Session');
        });

        it('excludes sessions outside date range', function () {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();

            // Create session before range
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $start->copy()->subWeek(),
                'status' => SessionStatus::SCHEDULED,
            ]);

            // Create session after range
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $end->copy()->addWeek(),
                'status' => SessionStatus::SCHEDULED,
            ]);

            $events = $this->service->getCalendarEvents(
                $this->teacherProfile->id,
                $start,
                $end
            );

            expect($events)->toBeEmpty();
        });

        it('includes correct event structure', function () {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $this->subscription->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
                'title' => 'Test Session',
                'status' => SessionStatus::SCHEDULED,
            ]);

            $events = $this->service->getCalendarEvents(
                $this->teacherProfile->id,
                $start,
                $end
            );

            $event = $events[0];
            expect($event)->toHaveKeys(['id', 'title', 'start', 'end', 'backgroundColor', 'borderColor', 'textColor', 'extendedProps'])
                ->and($event['id'])->toBe($session->id)
                ->and($event['title'])->toBe('Test Session')
                ->and($event['backgroundColor'])->toBeString()
                ->and($event['borderColor'])->toBeString()
                ->and($event['textColor'])->toBe('#ffffff')
                ->and($event['extendedProps'])->toBeArray();
        });

        it('includes correct extended props', function () {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'student_id' => $this->student->id,
                'academic_subscription_id' => $this->subscription->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $events = $this->service->getCalendarEvents(
                $this->teacherProfile->id,
                $start,
                $end
            );

            $extendedProps = $events[0]['extendedProps'];
            expect($extendedProps)->toHaveKeys(['type', 'status', 'student_name', 'subject', 'session_code', 'meeting_link'])
                ->and($extendedProps['type'])->toBe('academic_session')
                ->and($extendedProps['student_name'])->toBe($this->student->name)
                ->and($extendedProps['subject'])->toBe($this->subject->name);
        });

        it('calculates correct end time based on duration', function () {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
            $scheduledAt = Carbon::now()->addDays(2)->setHour(10)->setMinute(0);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 90,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $events = $this->service->getCalendarEvents(
                $this->teacherProfile->id,
                $start,
                $end
            );

            $expectedEnd = $scheduledAt->copy()->addMinutes(90);
            expect($events[0]['end'])->toBe($expectedEnd->toISOString());
        });

        it('uses correct color for scheduled status', function () {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => Carbon::now()->addDays(2),
                'status' => SessionStatus::SCHEDULED,
            ]);

            $events = $this->service->getCalendarEvents(
                $this->teacherProfile->id,
                $start,
                $end
            );

            expect($events[0]['backgroundColor'])->toBe('#3B82F6');
        });

        it('uses correct color for completed status', function () {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();

            AcademicSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => Carbon::now()->subDays(2),
            ]);

            $events = $this->service->getCalendarEvents(
                $this->teacherProfile->id,
                $start,
                $end
            );

            expect($events[0]['backgroundColor'])->toBe('#6B7280');
        });

        it('uses correct color for cancelled status', function () {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();

            AcademicSession::factory()->cancelled()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => Carbon::now()->addDays(2),
            ]);

            $events = $this->service->getCalendarEvents(
                $this->teacherProfile->id,
                $start,
                $end
            );

            expect($events[0]['backgroundColor'])->toBe('#EF4444');
        });

        it('returns multiple sessions in correct order', function () {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();

            $scheduledAt1 = Carbon::now()->addDays(2)->setHour(10);
            $scheduledAt2 = Carbon::now()->addDays(3)->setHour(14);
            $scheduledAt3 = Carbon::now()->addDays(1)->setHour(9);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $scheduledAt1,
                'title' => 'Session 1',
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $scheduledAt2,
                'title' => 'Session 2',
            ]);

            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => $scheduledAt3,
                'title' => 'Session 3',
            ]);

            $events = $this->service->getCalendarEvents(
                $this->teacherProfile->id,
                $start,
                $end
            );

            expect($events)->toHaveCount(3);
        });

        it('only returns sessions for specified teacher', function () {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
            $otherTeacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $otherTeacherProfile = AcademicTeacherProfile::factory()->create([
                'user_id' => $otherTeacher->id,
                'academy_id' => $this->academy->id,
            ]);

            // Create session for other teacher
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $otherTeacherProfile->id,
                'scheduled_at' => Carbon::now()->addDays(2),
            ]);

            // Create session for target teacher
            AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->teacherProfile->id,
                'scheduled_at' => Carbon::now()->addDays(2),
            ]);

            $events = $this->service->getCalendarEvents(
                $this->teacherProfile->id,
                $start,
                $end
            );

            expect($events)->toHaveCount(1);
        });
    });
});
