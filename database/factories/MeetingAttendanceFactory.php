<?php

namespace Database\Factories;

use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeetingAttendanceFactory extends Factory
{
    protected $model = MeetingAttendance::class;

    public function definition(): array
    {
        $session = QuranSession::factory();
        $user = User::factory()->state(['user_type' => 'student']);

        return [
            'session_id' => $session,
            'user_id' => $user,
            'user_type' => 'student',
            'session_type' => 'individual',
            'first_join_time' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'last_leave_time' => $this->faker->dateTimeBetween('now', '+1 hour'),
            'total_duration_minutes' => $this->faker->numberBetween(0, 60),
            'join_leave_cycles' => [],
            'attendance_calculated_at' => null,
            'attendance_status' => 'absent',
            'attendance_percentage' => 0,
            'session_duration_minutes' => 60,
            'session_start_time' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'session_end_time' => $this->faker->dateTimeBetween('now', '+1 hour'),
            'join_count' => 0,
            'leave_count' => 0,
            'is_calculated' => false,
        ];
    }
}
