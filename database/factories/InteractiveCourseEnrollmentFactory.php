<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InteractiveCourseEnrollment>
 */
class InteractiveCourseEnrollmentFactory extends Factory
{
    protected $model = InteractiveCourseEnrollment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'course_id' => InteractiveCourse::factory(),
            'student_id' => StudentProfile::factory(),
            'enrollment_status' => 'enrolled',
            'enrollment_date' => now(),
            'payment_status' => 'paid',
            'payment_amount' => fake()->randomFloat(2, 200, 2000),
        ];
    }

    /**
     * Configure a completed enrollment
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_status' => 'completed',
            'completion_percentage' => 100,
            'certificate_issued' => true,
        ]);
    }
}
