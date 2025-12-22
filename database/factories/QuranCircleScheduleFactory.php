<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranCircleSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuranCircleSchedule>
 */
class QuranCircleScheduleFactory extends Factory
{
    protected $model = QuranCircleSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'circle_id' => QuranCircle::factory(),
            'quran_teacher_id' => User::factory()->quranTeacher(),
            'weekly_schedule' => [
                ['day' => 'sunday', 'time' => '10:00'],
                ['day' => 'tuesday', 'time' => '14:00'],
                ['day' => 'thursday', 'time' => '16:00'],
            ],
            'timezone' => 'Asia/Riyadh',
            'default_duration_minutes' => 60,
            'is_active' => false,
            'schedule_starts_at' => Carbon::now(),
            'schedule_ends_at' => Carbon::now()->addMonths(3),
            'generate_ahead_days' => 30,
            'generate_before_hours' => 1,
            'recording_enabled' => false,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Create an active schedule.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive schedule.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
