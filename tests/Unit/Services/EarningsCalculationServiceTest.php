<?php

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Models\User;
use App\Services\EarningsCalculationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

describe('EarningsCalculationService', function () {
    beforeEach(function () {
        $this->service = new EarningsCalculationService();
        $this->academy = Academy::factory()->create();
    });

    describe('calculateSessionEarnings()', function () {
        it('returns null when session is not completed', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->toBeNull();
        });

        it('returns null when session is a trial', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->completed()->trial()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->toBeNull();
        });

        it('creates earning record for completed Quran individual session', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
                'session_price_group' => 50.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->not->toBeNull()
                ->and($earning->amount)->toBe('100.00')
                ->and($earning->teacher_type)->toBe('quran_teacher')
                ->and($earning->teacher_id)->toBe($teacherProfile->id)
                ->and($earning->session_type)->toBe(get_class($session))
                ->and($earning->session_id)->toBe($session->id)
                ->and($earning->calculation_method)->toBe('individual_rate')
                ->and($earning->is_finalized)->toBeFalse()
                ->and($earning->is_disputed)->toBeFalse();
        });

        it('creates earning record for completed Quran group session', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
                'session_price_group' => 50.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->group()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->not->toBeNull()
                ->and($earning->amount)->toBe('50.00')
                ->and($earning->calculation_method)->toBe('group_rate');
        });

        it('creates earning record for completed academic session', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 150.00,
            ]);
            $teacher->refresh();

            $session = AcademicSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->not->toBeNull()
                ->and($earning->amount)->toBe('150.00')
                ->and($earning->teacher_type)->toBe('academic_teacher')
                ->and($earning->teacher_id)->toBe($teacherProfile->id)
                ->and($earning->calculation_method)->toBe('individual_rate');
        });

        it('returns null when teacher profile is missing', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            Log::partialMock()->shouldReceive('error')->atLeast()->once();

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->toBeNull();
        });

        it('returns existing earning if already calculated', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $firstEarning = $this->service->calculateSessionEarnings($session);
            $secondEarning = $this->service->calculateSessionEarnings($session);

            expect($firstEarning->id)->toBe($secondEarning->id);
        });

        it('stores calculation metadata with session details', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'session_code' => 'QS-12345',
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning->calculation_metadata)->toBeArray()
                ->and($earning->calculation_metadata)->toHaveKey('calculated_at')
                ->and($earning->calculation_metadata)->toHaveKey('calculation_version')
                ->and($earning->calculation_metadata)->toHaveKey('session_code')
                ->and($earning->calculation_metadata['session_code'])->toBe('QS-12345')
                ->and($earning->calculation_metadata)->toHaveKey('amount');
        });

        it('stores earning month as first day of completion month', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'ended_at' => '2025-03-15 10:30:00',
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning->earning_month->format('Y-m-d'))->toBe('2025-03-01');
        });

        it('stores rate snapshot for audit trail', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
                'session_price_group' => 50.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning->rate_snapshot)->toBe('100.00');
        });

        it('returns null when teacher did not meet attendance threshold', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $teacher->id,
                'user_type' => 'teacher',
                'session_type' => 'individual',
                'attendance_percentage' => 40.00,
                'is_calculated' => true,
                'first_join_time' => now()->subHour(),
                'total_duration_minutes' => 20,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->toBeNull();
        });

        it('allows earning when teacher attendance is exactly 50%', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $teacher->id,
                'user_type' => 'teacher',
                'session_type' => 'individual',
                'attendance_percentage' => 50.00,
                'is_calculated' => true,
                'first_join_time' => now()->subHour(),
                'total_duration_minutes' => 30,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->not->toBeNull()
                ->and($earning->amount)->toBe('100.00');
        });

        it('allows earning when no attendance record exists for backwards compatibility', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->not->toBeNull();
        });
    });

    describe('interactive course session earnings', function () {
        it('calculates fixed payment type correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);
            $teacher->refresh();

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $teacherProfile->id,
                'payment_type' => 'fixed',
                'teacher_fixed_amount' => 1000.00,
                'total_sessions' => 10,
            ]);

            $session = InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subHours(2),
                'started_at' => now()->subHours(2),
                'ended_at' => now()->subHour(),
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->not->toBeNull()
                ->and($earning->amount)->toBe('100.00')
                ->and($earning->calculation_method)->toBe('fixed');
        });

        it('calculates per_student payment type correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);
            $teacher->refresh();

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $teacherProfile->id,
                'payment_type' => 'per_student',
                'amount_per_student' => 50.00,
            ]);

            $course->enrollments()->create([
                'student_id' => User::factory()->student()->forAcademy($this->academy)->create()->id,
                'academy_id' => $this->academy->id,
                'enrolled_at' => now(),
            ]);
            $course->enrollments()->create([
                'student_id' => User::factory()->student()->forAcademy($this->academy)->create()->id,
                'academy_id' => $this->academy->id,
                'enrolled_at' => now(),
            ]);
            $course->enrollments()->create([
                'student_id' => User::factory()->student()->forAcademy($this->academy)->create()->id,
                'academy_id' => $this->academy->id,
                'enrolled_at' => now(),
            ]);

            $session = InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subHours(2),
                'started_at' => now()->subHours(2),
                'ended_at' => now()->subHour(),
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->not->toBeNull()
                ->and($earning->amount)->toBe('150.00')
                ->and($earning->calculation_method)->toBe('per_student')
                ->and($earning->calculation_metadata['enrolled_students'])->toBe(3);
        });

        it('calculates per_session payment type correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);
            $teacher->refresh();

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $teacherProfile->id,
                'payment_type' => 'per_session',
                'amount_per_session' => 75.00,
            ]);

            $session = InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subHours(2),
                'started_at' => now()->subHours(2),
                'ended_at' => now()->subHour(),
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->not->toBeNull()
                ->and($earning->amount)->toBe('75.00')
                ->and($earning->calculation_method)->toBe('per_session');
        });

        it('handles fixed payment with single session correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);
            $teacher->refresh();

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $teacherProfile->id,
                'payment_type' => 'fixed',
                'teacher_fixed_amount' => 500.00,
                'total_sessions' => 1,
            ]);

            $session = InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subHours(2),
                'started_at' => now()->subHours(2),
                'ended_at' => now()->subHour(),
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->not->toBeNull()
                ->and($earning->amount)->toBe('500.00');
        });

        it('gets academy_id from course for interactive course session', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);
            $teacher->refresh();

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $teacherProfile->id,
                'payment_type' => 'per_session',
                'amount_per_session' => 75.00,
            ]);

            $session = InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subHours(2),
                'started_at' => now()->subHours(2),
                'ended_at' => now()->subHour(),
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->not->toBeNull()
                ->and($earning->academy_id)->toBe($this->academy->id);
        });

        it('stores course metadata for interactive session', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
            ]);
            $teacher->refresh();

            $course = InteractiveCourse::factory()->create([
                'academy_id' => $this->academy->id,
                'assigned_teacher_id' => $teacherProfile->id,
                'payment_type' => 'fixed',
                'teacher_fixed_amount' => 1000.00,
                'total_sessions' => 10,
            ]);

            $session = InteractiveCourseSession::factory()->create([
                'course_id' => $course->id,
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subHours(2),
                'started_at' => now()->subHours(2),
                'ended_at' => now()->subHour(),
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning->calculation_metadata)->toHaveKey('payment_type')
                ->and($earning->calculation_metadata['payment_type'])->toBe('fixed')
                ->and($earning->calculation_metadata)->toHaveKey('enrolled_students')
                ->and($earning->calculation_metadata)->toHaveKey('total_sessions')
                ->and($earning->calculation_metadata['total_sessions'])->toBe(10);
        });
    });

    describe('calculation method determination', function () {
        it('identifies Quran individual rate correctly', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->individual()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning->calculation_method)->toBe('individual_rate');
        });

        it('identifies Quran group rate correctly', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_group' => 50.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->group()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning->calculation_method)->toBe('group_rate');
        });

        it('identifies academic individual rate correctly', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = AcademicTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 150.00,
            ]);
            $teacher->refresh();

            $session = AcademicSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $teacherProfile->id,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning->calculation_method)->toBe('individual_rate');
        });
    });

    describe('session completion date handling', function () {
        it('uses ended_at when available', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $endedAt = now()->subDays(5);
            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'ended_at' => $endedAt,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning->session_completed_at->format('Y-m-d H:i:s'))
                ->toBe($endedAt->format('Y-m-d H:i:s'));
        });

        it('falls back to scheduled_at when ended_at is null', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $scheduledAt = now()->subDays(3);
            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'scheduled_at' => $scheduledAt,
                'ended_at' => null,
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning->session_completed_at->format('Y-m-d H:i:s'))
                ->toBe($scheduledAt->format('Y-m-d H:i:s'));
        });
    });

    describe('transaction handling', function () {
        it('creates earning record within database transaction', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $initialCount = TeacherEarning::count();

            $earning = $this->service->calculateSessionEarnings($session);

            expect(TeacherEarning::count())->toBe($initialCount + 1)
                ->and($earning)->not->toBeNull();
        });

        it('rolls back on transaction failure', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $initialCount = TeacherEarning::count();

            DB::shouldReceive('transaction')
                ->once()
                ->andThrow(new \Exception('Database error'));

            try {
                $this->service->calculateSessionEarnings($session);
            } catch (\Exception $e) {
            }

            expect(TeacherEarning::count())->toBe($initialCount);
        });
    });

    describe('edge cases', function () {
        it('handles zero amount correctly', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 0.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            Log::partialMock()->shouldReceive('warning')->atLeast()->once();

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->toBeNull();
        });

        it('handles negative amount correctly', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => -50.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            Log::partialMock()->shouldReceive('warning')->atLeast()->once();

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->toBeNull();
        });

        it('handles missing course for interactive session', function () {
            $session = InteractiveCourseSession::factory()->create([
                'course_id' => 999999,
                'status' => SessionStatus::COMPLETED,
            ]);

            Log::partialMock()->shouldReceive('error')->atLeast()->once();

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->toBeNull();
        });

        it('handles group session type correctly', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_group' => 60.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
                'session_type' => 'group',
            ]);

            $earning = $this->service->calculateSessionEarnings($session);

            expect($earning)->not->toBeNull()
                ->and($earning->amount)->toBe('60.00')
                ->and($earning->calculation_method)->toBe('group_rate');
        });
    });

    describe('logging behavior', function () {
        it('logs info when session not eligible', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $session = QuranSession::factory()->scheduled()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            Log::partialMock()->shouldReceive('info')->atLeast()->once();

            $this->service->calculateSessionEarnings($session);

            expect(true)->toBeTrue();
        });

        it('logs info when already calculated', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            $this->service->calculateSessionEarnings($session);

            Log::partialMock()->shouldReceive('info')->atLeast()->once();

            $this->service->calculateSessionEarnings($session);

            expect(true)->toBeTrue();
        });

        it('logs success when earning is calculated', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $teacherProfile = QuranTeacherProfile::factory()->create([
                'academy_id' => $this->academy->id,
                'user_id' => $teacher->id,
                'session_price_individual' => 100.00,
            ]);
            $teacher->refresh();

            $session = QuranSession::factory()->completed()->create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $teacher->id,
            ]);

            Log::partialMock()->shouldReceive('info')->atLeast()->once();

            $this->service->calculateSessionEarnings($session);

            expect(true)->toBeTrue();
        });
    });
});
