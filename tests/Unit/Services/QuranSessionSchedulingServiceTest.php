<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranCircleSchedule;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\QuranSessionSchedulingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

describe('QuranSessionSchedulingService', function () {
    beforeEach(function () {
        $this->service = new QuranSessionSchedulingService();
        $this->academy = Academy::factory()->create();
        $this->teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        $this->student = User::factory()->student()->forAcademy($this->academy)->create();

        $this->actingAs($this->teacher);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('scheduleIndividualSession()', function () {
        it('schedules a template session successfully', function () {
            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'total_sessions' => 10,
            ]);

            $templateSession = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'individual_circle_id' => $individualCircle->id,
                'is_template' => true,
                'is_scheduled' => false,
                'status' => SessionStatus::UNSCHEDULED,
                'duration_minutes' => 45,
            ]);

            $scheduledAt = Carbon::now()->addDays(2)->setTime(10, 0);

            $result = $this->service->scheduleIndividualSession($templateSession, $scheduledAt);

            expect($result)->toBeInstanceOf(QuranSession::class)
                ->and($result->scheduled_at->format('Y-m-d H:i'))->toBe($scheduledAt->format('Y-m-d H:i'))
                ->and($result->status)->toBe(SessionStatus::SCHEDULED)
                ->and($result->is_scheduled)->toBeTrue()
                ->and($result->teacher_scheduled_at)->not->toBeNull()
                ->and($result->scheduled_by)->toBe($this->teacher->id);
        });

        it('throws exception when session is not a template', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'is_template' => false,
                'is_scheduled' => true,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $scheduledAt = Carbon::now()->addDays(2);

            expect(fn () => $this->service->scheduleIndividualSession($session, $scheduledAt))
                ->toThrow(InvalidArgumentException::class, 'Session is not a schedulable template');
        });

        it('throws exception when session is already scheduled', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'is_template' => true,
                'is_scheduled' => true,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $scheduledAt = Carbon::now()->addDays(2);

            expect(fn () => $this->service->scheduleIndividualSession($session, $scheduledAt))
                ->toThrow(InvalidArgumentException::class, 'Session is not a schedulable template');
        });

        it('throws exception when scheduled time is in the past', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'is_template' => true,
                'is_scheduled' => false,
                'status' => SessionStatus::UNSCHEDULED,
            ]);

            $scheduledAt = Carbon::now()->subHours(2);

            expect(fn () => $this->service->scheduleIndividualSession($session, $scheduledAt))
                ->toThrow(ValidationException::class);
        });

        it('throws exception when teacher has conflicting session', function () {
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'is_template' => true,
                'is_scheduled' => false,
                'status' => SessionStatus::UNSCHEDULED,
                'duration_minutes' => 45,
            ]);

            $scheduledAt = Carbon::now()->addDays(2)->setTime(10, 0);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            expect(fn () => $this->service->scheduleIndividualSession($session, $scheduledAt))
                ->toThrow(ValidationException::class);
        });

        it('accepts additional data to merge into session', function () {
            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
            ]);

            $templateSession = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'individual_circle_id' => $individualCircle->id,
                'is_template' => true,
                'is_scheduled' => false,
                'status' => SessionStatus::UNSCHEDULED,
                'duration_minutes' => 45,
            ]);

            $scheduledAt = Carbon::now()->addDays(2)->setTime(10, 0);
            $additionalData = [
                'title' => 'Custom Session Title',
                'description' => 'Custom description',
            ];

            $result = $this->service->scheduleIndividualSession($templateSession, $scheduledAt, $additionalData);

            expect($result->title)->toBe('Custom Session Title')
                ->and($result->description)->toBe('Custom description');
        });
    });

    describe('createGroupCircleSchedule()', function () {
        it('creates and activates a schedule for group circle', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $weeklySchedule = [
                ['day' => 'sunday', 'time' => '10:00'],
                ['day' => 'tuesday', 'time' => '14:00'],
                ['day' => 'thursday', 'time' => '16:00'],
            ];

            $startsAt = Carbon::now()->addDays(1);
            $endsAt = Carbon::now()->addMonths(3);

            $result = $this->service->createGroupCircleSchedule(
                $circle,
                $weeklySchedule,
                $startsAt,
                $endsAt,
                ['duration' => 60, 'timezone' => 'Asia/Riyadh']
            );

            expect($result)->toBeInstanceOf(QuranCircleSchedule::class)
                ->and($result->circle_id)->toBe($circle->id)
                ->and($result->weekly_schedule)->toBe($weeklySchedule)
                ->and($result->default_duration_minutes)->toBe(60)
                ->and($result->timezone)->toBe('Asia/Riyadh')
                ->and($result->is_active)->toBeTrue();
        });

        it('throws exception when circle already has active schedule', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            QuranCircleSchedule::factory()->create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'is_active' => true,
                'weekly_schedule' => [['day' => 'sunday', 'time' => '10:00']],
                'schedule_starts_at' => Carbon::now(),
            ]);

            $weeklySchedule = [['day' => 'monday', 'time' => '10:00']];
            $startsAt = Carbon::now()->addDays(1);

            expect(fn () => $this->service->createGroupCircleSchedule($circle, $weeklySchedule, $startsAt))
                ->toThrow(ValidationException::class);
        });

        it('throws exception for invalid weekly schedule format', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $invalidSchedule = [
                ['day' => 'invalid_day', 'time' => '10:00'],
            ];

            $startsAt = Carbon::now()->addDays(1);

            expect(fn () => $this->service->createGroupCircleSchedule($circle, $invalidSchedule, $startsAt))
                ->toThrow(ValidationException::class);
        });

        it('throws exception for invalid time format', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $invalidSchedule = [
                ['day' => 'sunday', 'time' => '25:00'],
            ];

            $startsAt = Carbon::now()->addDays(1);

            expect(fn () => $this->service->createGroupCircleSchedule($circle, $invalidSchedule, $startsAt))
                ->toThrow(ValidationException::class);
        });

        it('accepts optional parameters for schedule configuration', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $weeklySchedule = [['day' => 'sunday', 'time' => '10:00']];
            $startsAt = Carbon::now()->addDays(1);
            $options = [
                'duration' => 90,
                'timezone' => 'Asia/Riyadh',
                'generate_ahead_days' => 60,
                'generate_before_hours' => 2,
                'title_template' => '{circle_name} - {day}',
                'description_template' => 'Session on {date}',
                'meeting_link' => 'https://meet.example.com/room123',
                'recording_enabled' => true,
            ];

            $result = $this->service->createGroupCircleSchedule($circle, $weeklySchedule, $startsAt, null, $options);

            expect($result->default_duration_minutes)->toBe(90)
                ->and($result->generate_ahead_days)->toBe(60)
                ->and($result->generate_before_hours)->toBe(2)
                ->and($result->session_title_template)->toBe('{circle_name} - {day}')
                ->and($result->meeting_link)->toBe('https://meet.example.com/room123')
                ->and($result->recording_enabled)->toBeTrue();
        });
    });

    describe('hasTeacherConflict()', function () {
        it('returns false when no conflicts exist', function () {
            $scheduledAt = Carbon::now()->addDays(2)->setTime(10, 0);
            $duration = 45;

            $result = $this->service->hasTeacherConflict($this->teacher->id, $scheduledAt, $duration);

            expect($result)->toBeFalse();
        });

        it('detects conflict when new session starts during existing session', function () {
            $existingStart = Carbon::now()->addDays(2)->setTime(10, 0);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'scheduled_at' => $existingStart,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $newStart = $existingStart->copy()->addMinutes(30);
            $result = $this->service->hasTeacherConflict($this->teacher->id, $newStart, 45);

            expect($result)->toBeTrue();
        });

        it('detects conflict when new session ends during existing session', function () {
            $existingStart = Carbon::now()->addDays(2)->setTime(10, 0);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'scheduled_at' => $existingStart,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $newStart = $existingStart->copy()->subMinutes(30);
            $result = $this->service->hasTeacherConflict($this->teacher->id, $newStart, 45);

            expect($result)->toBeTrue();
        });

        it('detects conflict when new session completely overlaps existing session', function () {
            $existingStart = Carbon::now()->addDays(2)->setTime(10, 0);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'scheduled_at' => $existingStart,
                'duration_minutes' => 30,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $newStart = $existingStart->copy()->subMinutes(10);
            $result = $this->service->hasTeacherConflict($this->teacher->id, $newStart, 60);

            expect($result)->toBeTrue();
        });

        it('returns false when sessions are adjacent but not overlapping', function () {
            $existingStart = Carbon::now()->addDays(2)->setTime(10, 0);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'scheduled_at' => $existingStart,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $newStart = $existingStart->copy()->addMinutes(60);
            $result = $this->service->hasTeacherConflict($this->teacher->id, $newStart, 45);

            expect($result)->toBeFalse();
        });

        it('ignores cancelled sessions when checking conflicts', function () {
            $existingStart = Carbon::now()->addDays(2)->setTime(10, 0);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'scheduled_at' => $existingStart,
                'duration_minutes' => 60,
                'status' => SessionStatus::CANCELLED,
            ]);

            $result = $this->service->hasTeacherConflict($this->teacher->id, $existingStart, 45);

            expect($result)->toBeFalse();
        });
    });

    describe('bulkScheduleIndividualSessions()', function () {
        it('schedules multiple sessions successfully', function () {
            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'total_sessions' => 10,
            ]);

            $template1 = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'individual_circle_id' => $individualCircle->id,
                'is_template' => true,
                'is_scheduled' => false,
                'status' => SessionStatus::UNSCHEDULED,
                'duration_minutes' => 45,
            ]);

            $template2 = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'individual_circle_id' => $individualCircle->id,
                'is_template' => true,
                'is_scheduled' => false,
                'status' => SessionStatus::UNSCHEDULED,
                'duration_minutes' => 45,
            ]);

            $sessionsData = [
                [
                    'template_session_id' => $template1->id,
                    'scheduled_at' => Carbon::now()->addDays(2)->setTime(10, 0)->toDateTimeString(),
                    'title' => 'Session 1',
                ],
                [
                    'template_session_id' => $template2->id,
                    'scheduled_at' => Carbon::now()->addDays(3)->setTime(10, 0)->toDateTimeString(),
                    'title' => 'Session 2',
                ],
            ];

            $result = $this->service->bulkScheduleIndividualSessions($individualCircle, $sessionsData);

            expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($result)->toHaveCount(2)
                ->and($result->first()->title)->toBe('Session 1')
                ->and($result->last()->title)->toBe('Session 2');
        });

        it('throws exception when template does not belong to circle', function () {
            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
            ]);

            $otherCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
            ]);

            $template = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'individual_circle_id' => $otherCircle->id,
                'is_template' => true,
                'is_scheduled' => false,
                'status' => SessionStatus::UNSCHEDULED,
            ]);

            $sessionsData = [
                [
                    'template_session_id' => $template->id,
                    'scheduled_at' => Carbon::now()->addDays(2)->toDateTimeString(),
                ],
            ];

            expect(fn () => $this->service->bulkScheduleIndividualSessions($individualCircle, $sessionsData))
                ->toThrow(InvalidArgumentException::class, 'Template session does not belong to this circle');
        });

        it('filters null values from additional data', function () {
            $individualCircle = QuranIndividualCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
            ]);

            $template = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'student_id' => $this->student->id,
                'individual_circle_id' => $individualCircle->id,
                'is_template' => true,
                'is_scheduled' => false,
                'status' => SessionStatus::UNSCHEDULED,
                'duration_minutes' => 45,
            ]);

            $sessionsData = [
                [
                    'template_session_id' => $template->id,
                    'scheduled_at' => Carbon::now()->addDays(2)->setTime(10, 0)->toDateTimeString(),
                    'title' => null,
                    'description' => 'Valid description',
                ],
            ];

            $result = $this->service->bulkScheduleIndividualSessions($individualCircle, $sessionsData);

            expect($result->first()->description)->toBe('Valid description');
        });
    });

    describe('getAvailableTimeSlots()', function () {
        it('returns available time slots for a date', function () {
            $date = Carbon::now()->addDays(2);
            $duration = 60;

            $result = $this->service->getAvailableTimeSlots($this->teacher->id, $date, $duration);

            expect($result)->toBeArray()
                ->and($result)->not->toBeEmpty()
                ->and($result[0])->toHaveKeys(['time', 'datetime', 'available']);
        });

        it('excludes time slots with existing sessions', function () {
            $date = Carbon::now()->addDays(2);
            $sessionTime = $date->copy()->setTime(10, 0);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'scheduled_at' => $sessionTime,
                'duration_minutes' => 60,
                'status' => SessionStatus::SCHEDULED,
            ]);

            $result = $this->service->getAvailableTimeSlots($this->teacher->id, $date, 60);

            $times = collect($result)->pluck('time')->toArray();

            expect($times)->not->toContain('10:00');
        });

        it('includes cancelled sessions as available', function () {
            $date = Carbon::now()->addDays(2);
            $sessionTime = $date->copy()->setTime(10, 0);

            QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
                'scheduled_at' => $sessionTime,
                'duration_minutes' => 60,
                'status' => SessionStatus::CANCELLED,
            ]);

            $result = $this->service->getAvailableTimeSlots($this->teacher->id, $date, 60);

            $times = collect($result)->pluck('time')->toArray();

            expect($times)->toContain('10:00');
        });

        it('generates slots in 30-minute intervals', function () {
            $date = Carbon::now()->addDays(2);
            $result = $this->service->getAvailableTimeSlots($this->teacher->id, $date, 60);

            $times = collect($result)->pluck('time')->take(5)->toArray();

            expect($times[0])->toMatch('/^\d{2}:00$/');
            expect($times[1])->toMatch('/^\d{2}:30$/');
        });

        it('respects working hours from 8:00 to 22:00', function () {
            $date = Carbon::now()->addDays(2);
            $result = $this->service->getAvailableTimeSlots($this->teacher->id, $date, 60);

            $times = collect($result)->pluck('time')->toArray();

            expect($times)->not->toContain('07:00')
                ->and($times)->not->toContain('23:00');
        });

        it('marks all slots as available', function () {
            $date = Carbon::now()->addDays(2);
            $result = $this->service->getAvailableTimeSlots($this->teacher->id, $date, 60);

            foreach ($result as $slot) {
                expect($slot['available'])->toBeTrue();
            }
        });
    });

    describe('updateGroupCircleSchedule()', function () {
        it('updates existing schedule successfully', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $schedule = QuranCircleSchedule::factory()->create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'weekly_schedule' => [['day' => 'sunday', 'time' => '10:00']],
                'default_duration_minutes' => 60,
                'schedule_starts_at' => Carbon::now(),
            ]);

            $updateData = [
                'default_duration_minutes' => 90,
                'recording_enabled' => true,
            ];

            $result = $this->service->updateGroupCircleSchedule($schedule, $updateData);

            expect($result->default_duration_minutes)->toBe(90)
                ->and($result->recording_enabled)->toBeTrue()
                ->and($result->updated_by)->toBe($this->teacher->id);
        });

        it('returns fresh instance after update', function () {
            $circle = QuranCircle::factory()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->teacher->id,
            ]);

            $schedule = QuranCircleSchedule::factory()->create([
                'academy_id' => $this->academy->id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $this->teacher->id,
                'weekly_schedule' => [['day' => 'sunday', 'time' => '10:00']],
                'schedule_starts_at' => Carbon::now(),
            ]);

            $result = $this->service->updateGroupCircleSchedule($schedule, ['default_duration_minutes' => 45]);

            expect($result)->toBeInstanceOf(QuranCircleSchedule::class)
                ->and($result->wasRecentlyCreated)->toBeFalse();
        });
    });
});
