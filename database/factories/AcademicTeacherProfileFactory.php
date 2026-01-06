<?php

namespace Database\Factories;

use App\Enums\EducationalQualification;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicTeacherProfile>
 */
class AcademicTeacherProfileFactory extends Factory
{
    protected $model = AcademicTeacherProfile::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->academicTeacher(),
            'academy_id' => Academy::factory(),
            'gender' => fake()->randomElement(['male', 'female']),
            'teacher_code' => 'AT-'.str_pad(fake()->unique()->randomNumber(4), 4, '0', STR_PAD_LEFT),
            'bio_arabic' => fake()->sentence(),
            'bio_english' => fake()->sentence(),
            'education_level' => fake()->randomElement(EducationalQualification::cases()),
            'teaching_experience_years' => fake()->numberBetween(1, 20),
            'session_price_individual' => fake()->randomFloat(2, 50, 200),
            'approval_status' => 'approved',
            'rating' => fake()->randomFloat(1, 3, 5),
            'total_students' => 0,
            'is_active' => true,
            'languages' => ['arabic'],
            'available_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
        ];
    }
}
