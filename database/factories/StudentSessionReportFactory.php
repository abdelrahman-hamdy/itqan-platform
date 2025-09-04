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

        $attendanceStatuses = ['present', 'late', 'partial', 'absent'];
        $attendanceStatus = $this->faker->randomElement($attendanceStatuses);

        // Calculate realistic attendance percentage based on status
        $attendancePercentage = match ($attendanceStatus) {
            'present' => $this->faker->numberBetween(80, 100),
            'late' => $this->faker->numberBetween(70, 95),
            'partial' => $this->faker->numberBetween(30, 69),
            'absent' => 0,
        };

        // Calculate realistic attendance minutes (assuming 60-minute sessions)
        $actualMinutes = $attendanceStatus === 'absent' ? 0 :
            round(($attendancePercentage / 100) * 60);

        // Late minutes only for late status
        $lateMinutes = $attendanceStatus === 'late' ? $this->faker->numberBetween(5, 15) : 0;

        return [
            'session_id' => $session,
            'student_id' => $student,
            'teacher_id' => $teacher,
            'academy_id' => $academy,

            // Performance degrees (nullable)
            'new_memorization_degree' => $this->faker->optional(0.7)->randomFloat(1, 0, 10),
            'reservation_degree' => $this->faker->optional(0.7)->randomFloat(1, 0, 10),
            'notes' => $this->faker->optional(0.3)->sentence(),

            // Attendance data
            'attendance_status' => $attendanceStatus,
            'meeting_enter_time' => $attendanceStatus === 'absent' ? null :
                $this->faker->dateTimeBetween('-1 hour', 'now'),
            'meeting_leave_time' => $attendanceStatus === 'absent' ? null :
                $this->faker->dateTimeBetween('now', '+1 hour'),
            'actual_attendance_minutes' => $actualMinutes,
            'is_late' => $attendanceStatus === 'late',
            'late_minutes' => $lateMinutes,
            'attendance_percentage' => $attendancePercentage,
            'connection_quality_score' => $this->faker->numberBetween(0, 100),

            // Meeting events (as JSON)
            'meeting_events' => $attendanceStatus === 'absent' ? [] : [
                [
                    'joined_at' => now()->subMinutes(60)->toISOString(),
                    'left_at' => now()->toISOString(),
                    'duration_minutes' => $actualMinutes,
                ],
            ],

            // Evaluation metadata
            'evaluated_at' => now(),
            'is_auto_calculated' => $this->faker->boolean(80), // 80% auto-calculated
            'manually_evaluated' => $this->faker->boolean(30), // 30% manually evaluated
        ];
    }

    /**
     * Create a report for a present student
     */
    public function present(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'attendance_status' => 'present',
                'attendance_percentage' => $this->faker->numberBetween(80, 100),
                'actual_attendance_minutes' => $this->faker->numberBetween(48, 60), // 80-100% of 60 minutes
                'is_late' => false,
                'late_minutes' => 0,
                'meeting_enter_time' => now()->subMinutes(60),
                'meeting_leave_time' => now(),
                'connection_quality_score' => $this->faker->numberBetween(70, 100),
            ];
        });
    }

    /**
     * Create a report for a late student
     */
    public function late(): static
    {
        return $this->state(function (array $attributes) {
            $lateMinutes = $this->faker->numberBetween(5, 15);
            $attendanceMinutes = $this->faker->numberBetween(40, 55);

            return [
                'attendance_status' => 'late',
                'attendance_percentage' => round(($attendanceMinutes / 60) * 100, 2),
                'actual_attendance_minutes' => $attendanceMinutes,
                'is_late' => true,
                'late_minutes' => $lateMinutes,
                'meeting_enter_time' => now()->subMinutes(60)->addMinutes($lateMinutes),
                'meeting_leave_time' => now(),
                'connection_quality_score' => $this->faker->numberBetween(60, 90),
            ];
        });
    }

    /**
     * Create a report for a student with partial attendance
     */
    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            $attendanceMinutes = $this->faker->numberBetween(20, 35);

            return [
                'attendance_status' => 'partial',
                'attendance_percentage' => round(($attendanceMinutes / 60) * 100, 2),
                'actual_attendance_minutes' => $attendanceMinutes,
                'is_late' => $this->faker->boolean(),
                'late_minutes' => $this->faker->boolean() ? $this->faker->numberBetween(0, 10) : 0,
                'meeting_enter_time' => now()->subMinutes(40),
                'meeting_leave_time' => now()->subMinutes(20),
                'connection_quality_score' => $this->faker->numberBetween(30, 70),
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
                'connection_quality_score' => 0,
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
                'is_auto_calculated' => true,
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
                'is_auto_calculated' => false,
                'manually_evaluated' => true,
                'evaluated_at' => now(),
            ];
        });
    }

    /**
     * Create a report with excellent connection quality
     */
    public function excellentConnection(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'connection_quality_score' => $this->faker->numberBetween(90, 100),
                'meeting_events' => [
                    [
                        'joined_at' => now()->subMinutes(60)->toISOString(),
                        'left_at' => now()->toISOString(),
                        'duration_minutes' => 60,
                    ],
                ],
            ];
        });
    }

    /**
     * Create a report with poor connection quality (many disconnections)
     */
    public function poorConnection(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'connection_quality_score' => $this->faker->numberBetween(20, 50),
                'meeting_events' => [
                    [
                        'joined_at' => now()->subMinutes(60)->toISOString(),
                        'left_at' => now()->subMinutes(50)->toISOString(),
                        'duration_minutes' => 10,
                    ],
                    [
                        'joined_at' => now()->subMinutes(45)->toISOString(),
                        'left_at' => now()->subMinutes(30)->toISOString(),
                        'duration_minutes' => 15,
                    ],
                    [
                        'joined_at' => now()->subMinutes(20)->toISOString(),
                        'left_at' => now()->toISOString(),
                        'duration_minutes' => 20,
                    ],
                ],
            ];
        });
    }
}
