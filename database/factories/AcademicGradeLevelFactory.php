<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicGradeLevel>
 */
class AcademicGradeLevelFactory extends Factory
{
    protected $model = AcademicGradeLevel::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'name' => fake()->randomElement(['الصف الأول', 'الصف الثاني', 'الصف الثالث', 'الصف الرابع', 'الصف الخامس', 'الصف السادس']),
            'name_en' => fake()->randomElement(['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']),
            'description' => fake()->sentence(),
            'description_en' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the grade level is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
