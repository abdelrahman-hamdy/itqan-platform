<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\AcademicSubject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicSubject>
 */
class AcademicSubjectFactory extends Factory
{
    protected $model = AcademicSubject::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'name' => fake()->randomElement(['الرياضيات', 'العلوم', 'اللغة العربية', 'اللغة الإنجليزية']),
            'name_en' => fake()->randomElement(['Mathematics', 'Science', 'Arabic', 'English']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
