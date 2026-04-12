<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentSessionReportFactory extends Factory
{
    protected $model = StudentSessionReport::class;

    public function definition(): array
    {
        $academy = Academy::factory();
        $teacher = User::factory()->state(['user_type' => 'quran_teacher']);
        $student = User::factory()->state(['user_type' => 'student']);
        $session = QuranSession::factory();

        $attendanceStatus = $this->faker->randomElement(['attended', 'partially_attended', 'absent']);

        $attendancePercentage = match ($attendanceStatus) {
            'attended' => $this->faker->numberBetween(80, 100),
            'partially_attended' => $this->faker->numberBetween(50, 79),
            'absent' => 0,
        };

        $actualMinutes = $attendanceStatus === 'absent' ? 0 :
            (int) round(($attendancePercentage / 100) * 60);

        return [
            'session_id' => $session,
            'student_id' => $student,
            'teacher_id' => $teacher,
            'academy_id' => $academy,

            'new_memorization_degree' => $this->faker->optional(0.7)->randomFloat(1, 0, 10),
            'reservation_degree' => $this->faker->optional(0.7)->randomFloat(1, 0, 10),
            'notes' => $this->faker->optional(0.3)->sentence(),

            'attendance_status' => $attendanceStatus,
            'meeting_enter_time' => $attendanceStatus === 'absent' ? null :
                $this->faker->dateTimeBetween('-1 hour', 'now'),
            'meeting_leave_time' => $attendanceStatus === 'absent' ? null :
                $this->faker->dateTimeBetween('now', '+1 hour'),
            'actual_attendance_minutes' => $actualMinutes,
            'is_late' => false,
            'late_minutes' => 0,
            'attendance_percentage' => $attendancePercentage,

            'meeting_events' => $attendanceStatus === 'absent' ? [] : [
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
            'attendance_status' => 'attended',
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
                'attendance_status' => 'partially_attended',
                'attendance_percentage' => round(($attendanceMinutes / 60) * 100, 2),
                'actual_attendance_minutes' => $attendanceMinutes,
                'is_late' => false,
                'late_minutes' => 0,
                'meeting_enter_time' => now()->subMinutes(60),
                'meeting_leave_time' => now()->subMinutes(60 - $attendanceMinutes),
            ];
        });
    }

    /**
     * Create a report for an absent student
     */
    public function absent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'attendance_status' => 'absent',
                'attendance_percentage' => 0,
                'actual_attendance_minutes' => 0,
                'is_late' => false,
                'late_minutes' => 0,
                'meeting_enter_time' => null,
                'meeting_leave_time' => null,
                'meeting_events' => [],
            ];
        });
    }

    /**
     * Create a report with excellent performance
     */
    public function excellentPerformance(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'new_memorization_degree' => $this->faker->randomFloat(1, 8.5, 10),
                'reservation_degree' => $this->faker->randomFloat(1, 8.0, 10),
                'notes' => 'أداء ممتاز ومتميز',
                'manually_evaluated' => true,
            ];
        });
    }

    /**
     * Create a report with poor performance
     */
    public function poorPerformance(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'new_memorization_degree' => $this->faker->randomFloat(1, 0, 4),
                'reservation_degree' => $this->faker->randomFloat(1, 0, 4),
                'notes' => 'يحتاج إلى مزيد من التحسين',
                'manually_evaluated' => true,
            ];
        });
    }

    /**
     * Create a report that's auto-calculated only
     */
    public function autoCalculated(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'new_memorization_degree' => null,
                'reservation_degree' => null,
                'notes' => null,
                'is_calculated' => true,
                'manually_evaluated' => false,
            ];
        });
    }

    /**
     * Create a report that's manually evaluated
     */
    public function manuallyEvaluated(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'new_memorization_degree' => $this->faker->randomFloat(1, 5, 9),
                'reservation_degree' => $this->faker->randomFloat(1, 5, 9),
                'notes' => $this->faker->sentence(),
                'is_calculated' => false,
                'manually_evaluated' => true,
                'evaluated_at' => now(),
            ];
        });
    }
}
