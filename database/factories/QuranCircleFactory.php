<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuranCircle>
 */
class QuranCircleFactory extends Factory
{
    protected $model = QuranCircle::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'quran_teacher_id' => User::factory()->quranTeacher(),
            'name_ar' => 'حلقة ' . fake()->firstName(),
            'name_en' => fake()->firstName() . ' Circle',
            'description_ar' => fake()->sentence(),
            'description_en' => fake()->sentence(),
            'circle_type' => fake()->randomElement(['memorization', 'recitation', 'mixed']),
            'specialization' => fake()->randomElement(['memorization', 'recitation', 'interpretation']),
            'memorization_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'age_group' => fake()->randomElement(['children', 'youth', 'adults', 'all_ages']),
            'gender_type' => fake()->randomElement(['male', 'female', 'mixed']),
            'max_students' => fake()->numberBetween(5, 20),
            'enrolled_students' => 0,
            'min_students_to_start' => 3,
            'monthly_sessions_count' => fake()->numberBetween(4, 12),
            'monthly_fee' => fake()->randomFloat(2, 100, 500),
            'sessions_completed' => 0,
            'status' => true,
            'enrollment_status' => 'open',
            'recording_enabled' => false,
            'attendance_required' => true,
            'makeup_sessions_allowed' => true,
            'certificates_enabled' => true,
            'avg_rating' => 0,
            'total_reviews' => 0,
            'completion_rate' => 0,
            'dropout_rate' => 0,
        ];
    }
}
