<?php

use App\Enums\AttendanceStatus as AttendanceStatusEnum;
use App\Livewire\Student\AttendanceStatus;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\User;
use Livewire\Livewire;

describe('Student Attendance Status Component', function () {
    beforeEach(function () {
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
            'is_active' => true,
        ]);
    });

    describe('component rendering', function () {
        it('renders successfully for quran session', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addHour(),
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertStatus(200)
                ->assertSet('sessionId', $session->id)
                ->assertSet('sessionType', 'quran');
        });

        it('renders successfully for academic session', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = AcademicSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addHour(),
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'academic',
                ])
                ->assertStatus(200)
                ->assertSet('sessionType', 'academic');
        });

        it('renders successfully for interactive course session', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = InteractiveCourseSession::factory()->create([
                'scheduled_date' => now()->addDay()->toDateString(),
                'scheduled_time' => '10:00:00',
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'interactive',
                ])
                ->assertStatus(200)
                ->assertSet('sessionType', 'interactive');
        });

        it('uses authenticated user ID when userId not provided', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addHour(),
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('userId', $student->id);
        });
    });

    describe('session states', function () {
        it('shows waiting state before preparation time', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addHours(2),
                'duration_minutes' => 60,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('status', 'waiting')
                ->assertSet('dotColor', 'bg-blue-400');
        });

        it('shows preparation state during preparation window', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            // Session starts in 5 minutes (within 10-minute preparation window)
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addMinutes(5),
                'duration_minutes' => 60,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('status', 'preparation')
                ->assertSet('dotColor', 'bg-yellow-400 animate-pulse');
        });

        it('shows live state when session is ongoing', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subMinutes(10),
                'duration_minutes' => 60,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('status', 'in_meeting');
        });

        it('shows completed state after session ends', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subHours(2),
                'duration_minutes' => 60,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('status', 'completed');
        });
    });

    describe('attendance tracking', function () {
        it('shows not joined message when student has not joined', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subMinutes(10),
                'duration_minutes' => 60,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('status', 'in_meeting')
                ->assertSet('dotColor', 'bg-red-400 animate-pulse');
        });

        it('shows in meeting message when student is currently in session', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subMinutes(10),
                'duration_minutes' => 60,
            ]);

            $attendance = MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $student->id,
                'first_join_time' => now()->subMinutes(5),
                'last_leave_time' => null,
                'is_calculated' => false,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('status', 'in_meeting')
                ->assertSet('dotColor', 'bg-green-500 animate-pulse');
        });

        it('shows disconnected message when student has left session', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subMinutes(10),
                'duration_minutes' => 60,
            ]);

            $attendance = MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $student->id,
                'first_join_time' => now()->subMinutes(5),
                'last_leave_time' => now()->subMinutes(2),
                'is_calculated' => false,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('status', 'in_meeting')
                ->assertSet('dotColor', 'bg-orange-400');
        });
    });

    describe('completed session attendance', function () {
        it('shows calculated attendance for attended status', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subHours(2),
                'duration_minutes' => 60,
            ]);

            $attendance = MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $student->id,
                'first_join_time' => now()->subHours(2),
                'last_leave_time' => now()->subHours(1),
                'total_duration_minutes' => 55,
                'attendance_status' => AttendanceStatusEnum::ATTENDED->value,
                'attendance_percentage' => 92,
                'is_calculated' => true,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('status', 'completed')
                ->assertSet('dotColor', 'bg-green-500')
                ->assertSet('duration', 55)
                ->assertSet('attendancePercentage', 92)
                ->assertSet('showProgress', true);
        });

        it('shows calculating message when attendance not yet calculated', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subHours(2),
                'duration_minutes' => 60,
            ]);

            $attendance = MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $student->id,
                'first_join_time' => now()->subHours(2),
                'last_leave_time' => now()->subHours(1),
                'is_calculated' => false,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('status', 'completed')
                ->assertSet('dotColor', 'bg-blue-400 animate-pulse')
                ->assertSet('showProgress', false);
        });

        it('shows absent message when no attendance record exists', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subHours(2),
                'duration_minutes' => 60,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('status', 'completed')
                ->assertSet('dotColor', 'bg-red-500')
                ->assertSet('showProgress', false);
        });
    });

    describe('attendance status enum mapping', function () {
        it('correctly maps attended status', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subHours(2),
                'duration_minutes' => 60,
            ]);

            $attendance = MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $student->id,
                'attendance_status' => AttendanceStatusEnum::ATTENDED->value,
                'is_calculated' => true,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('dotColor', 'bg-green-500');
        });

        it('correctly maps late status', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subHours(2),
                'duration_minutes' => 60,
            ]);

            $attendance = MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $student->id,
                'attendance_status' => AttendanceStatusEnum::LATE->value,
                'is_calculated' => true,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('dotColor', 'bg-yellow-500');
        });

        it('correctly maps absent status', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->subHours(2),
                'duration_minutes' => 60,
            ]);

            $attendance = MeetingAttendance::factory()->create([
                'session_id' => $session->id,
                'user_id' => $student->id,
                'attendance_status' => AttendanceStatusEnum::ABSENT->value,
                'is_calculated' => true,
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->assertSet('dotColor', 'bg-red-500');
        });
    });

    describe('event listeners', function () {
        it('refreshes attendance when attendance-updated event is dispatched', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->create([
                'academy_id' => $this->academy->id,
                'scheduled_at' => now()->addHour(),
            ]);

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => $session->id,
                    'sessionType' => 'quran',
                ])
                ->dispatch('attendance-updated')
                ->assertStatus(200);
        });
    });

    describe('edge cases', function () {
        it('handles non-existent session gracefully', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            Livewire::actingAs($student)
                ->test(AttendanceStatus::class, [
                    'sessionId' => 99999,
                    'sessionType' => 'quran',
                ])
                ->assertSet('status', 'loading');
        });

        it('handles different session types', function () {
            $student = User::factory()->student()->forAcademy($this->academy)->create();

            foreach (['quran', 'academic', 'interactive'] as $type) {
                $session = match ($type) {
                    'quran' => QuranSession::factory()->create([
                        'academy_id' => $this->academy->id,
                        'scheduled_at' => now()->addHour(),
                    ]),
                    'academic' => AcademicSession::factory()->create([
                        'academy_id' => $this->academy->id,
                        'scheduled_at' => now()->addHour(),
                    ]),
                    'interactive' => InteractiveCourseSession::factory()->create([
                        'scheduled_date' => now()->addDay()->toDateString(),
                        'scheduled_time' => '10:00:00',
                    ]),
                };

                Livewire::actingAs($student)
                    ->test(AttendanceStatus::class, [
                        'sessionId' => $session->id,
                        'sessionType' => $type,
                    ])
                    ->assertStatus(200);
            }
        });
    });
});
