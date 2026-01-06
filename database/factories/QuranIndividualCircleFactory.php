<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuranIndividualCircleFactory extends Factory
{
    protected $model = QuranIndividualCircle::class;

    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'quran_teacher_id' => User::factory()->quranTeacher(),
            'student_id' => User::factory()->student(),
            'subscription_id' => QuranSubscription::factory(),
            'circle_code' => 'QIC-'.fake()->unique()->numberBetween(10000, 99999),
            'name' => 'حلقة '.fake()->firstName(),
            'description' => fake()->sentence(),
            'specialization' => fake()->randomElement(['memorization', 'recitation', 'interpretation', 'arabic_language', 'complete']),
            'memorization_level' => fake()->randomElement(['beginner', 'elementary', 'intermediate', 'advanced', 'expert']),
            'total_sessions' => fake()->numberBetween(10, 100),
            'sessions_scheduled' => 0,
            'sessions_completed' => 0,
            'sessions_remaining' => function (array $attributes) {
                return $attributes['total_sessions'];
            },
            'default_duration_minutes' => 45,
            'is_active' => true,
            'started_at' => now(),
        ];
    }
}
