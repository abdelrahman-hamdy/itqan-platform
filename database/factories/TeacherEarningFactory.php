<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherEarning>
 */
class TeacherEarningFactory extends Factory
{
    protected $model = TeacherEarning::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'teacher_type' => 'quran_teacher',
            'teacher_id' => QuranTeacherProfile::factory(),
            'session_type' => 'App\\Models\\QuranSession',
            'session_id' => 1,
            'amount' => fake()->randomFloat(2, 50, 200),
            'calculation_method' => 'per_session',
            'rate_snapshot' => ['rate' => 100], // This is correct - cast handles JSON encoding
            'earning_month' => now()->format('Y-m-01'), // Fixed: should be full date format
            'session_completed_at' => now(),
            'calculated_at' => now(),
            'is_finalized' => false,
            'is_disputed' => false,
        ];
    }

    /**
     * Finalized earning
     */
    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_finalized' => true,
        ]);
    }

    /**
     * For specific academy
     */
    public function forAcademy(Academy $academy): static
    {
        return $this->state(fn (array $attributes) => [
            'academy_id' => $academy->id,
        ]);
    }
}
