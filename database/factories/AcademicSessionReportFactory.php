<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicSessionReport>
 */
class AcademicSessionReportFactory extends Factory
{
    protected $model = AcademicSessionReport::class;

    public function definition(): array
    {
        $attendanceStatus = $this->faker->randomElement([
            AttendanceStatus::ATTENDED->value,
            AttendanceStatus::PARTIALLY_ATTENDED->value,
            AttendanceStatus::ABSENT->value,
        ]);

        $attendancePercentage = match ($attendanceStatus) {
            AttendanceStatus::ATTENDED->value => $this->faker->numberBetween(80, 100),
            AttendanceStatus::PARTIALLY_ATTENDED->value => $this->faker->numberBetween(50, 79),
            AttendanceStatus::ABSENT->value => 0,
        };

        $actualMinutes = $attendanceStatus === AttendanceStatus::ABSENT->value ? 0 :
            (int) round(($attendancePercentage / 100) * 60);

        return [
            'session_id' => AcademicSession::factory(),
            'student_id' => User::factory()->student(),
            'teacher_id' => User::factory()->academicTeacher(),
            'academy_id' => Academy::factory(),

            'homework_degree' => $this->faker->optional(0.6)->randomFloat(1, 0, 10),
            'notes' => $this->faker->optional(0.3)->sentence(),

            'attendance_status' => $attendanceStatus,
            'meeting_enter_time' => $attendanceStatus === AttendanceStatus::ABSENT->value ? null :
                $this->faker->dateTimeBetween('-1 hour', 'now'),
            'meeting_leave_time' => $attendanceStatus === AttendanceStatus::ABSENT->value ? null :
                $this->faker->dateTimeBetween('now', '+1 hour'),
            'actual_attendance_minutes' => $actualMinutes,
            'is_late' => false,
            'late_minutes' => 0,
            'attendance_percentage' => $attendancePercentage,

            'meeting_events' => $attendanceStatus === AttendanceStatus::ABSENT->value ? [] : [
                [
                    'joined_at' => now()->subMinutes(60)->toISOString(),
                    'left_at' => now()->toISOString(),
                    'duration_minutes' => $actualMinutes,
                ],
            ],

            'evaluated_at' => now(),
            'is_calculated' => $this->faker->boolean(80),
            'manually_evaluated' => $this->faker->boolean(30),
        ];
    }

    public function attended(): static
    {
        return $this->state(fn () => [
            'attendance_status' => AttendanceStatus::ATTENDED->value,
            'attendance_percentage' => $this->faker->numberBetween(80, 100),
            'actual_attendance_minutes' => $this->faker->numberBetween(48, 60),
            'is_late' => false,
            'late_minutes' => 0,
            'meeting_enter_time' => now()->subMinutes(60),
            'meeting_leave_time' => now(),
        ]);
    }

    public function partiallyAttended(): static
    {
        return $this->state(function () {
            $attendanceMinutes = $this->faker->numberBetween(30, 47);

            return [
                'attendance_status' => AttendanceStatus::PARTIALLY_ATTENDED->value,
                'attendance_percentage' => round(($attendanceMinutes / 60) * 100, 2),
                'actual_attendance_minutes' => $attendanceMinutes,
                'is_late' => false,
                'late_minutes' => 0,
                'meeting_enter_time' => now()->subMinutes(60),
                'meeting_leave_time' => now()->subMinutes(60 - $attendanceMinutes),
            ];
        });
    }

    public function absent(): static
    {
        return $this->state(fn () => [
            'attendance_status' => AttendanceStatus::ABSENT->value,
            'attendance_percentage' => 0,
            'actual_attendance_minutes' => 0,
            'is_late' => false,
            'late_minutes' => 0,
            'meeting_enter_time' => null,
            'meeting_leave_time' => null,
            'meeting_events' => [],
        ]);
    }

    public function excellentPerformance(): static
    {
        return $this->state(fn () => [
            'homework_degree' => $this->faker->randomFloat(1, 8.5, 10),
            'notes' => 'أداء ممتاز في الواجب',
            'manually_evaluated' => true,
        ]);
    }

    public function poorPerformance(): static
    {
        return $this->state(fn () => [
            'homework_degree' => $this->faker->randomFloat(1, 0, 4),
            'notes' => 'يحتاج إلى مزيد من التحسين',
            'manually_evaluated' => true,
        ]);
    }

    public function autoCalculated(): static
    {
        return $this->state(fn () => [
            'homework_degree' => null,
            'notes' => null,
            'is_calculated' => true,
            'manually_evaluated' => false,
        ]);
    }

    public function manuallyEvaluated(): static
    {
        return $this->state(fn () => [
            'homework_degree' => $this->faker->randomFloat(1, 5, 9),
            'notes' => $this->faker->sentence(),
            'is_calculated' => false,
            'manually_evaluated' => true,
            'evaluated_at' => now(),
        ]);
    }
}
