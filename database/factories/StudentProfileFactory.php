<?php

namespace Database\Factories;

use App\Models\AcademicGradeLevel;
use App\Models\ParentProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    protected $model = StudentProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null, // Will be linked later
            'email' => fake()->unique()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->numerify('05########'),
            'grade_level_id' => null,
            'birth_date' => fake()->dateTimeBetween('-18 years', '-5 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'nationality' => 'SA',
            'parent_id' => null,
            'enrollment_date' => now(),
        ];
    }

    /**
     * Create a student with an associated user.
     */
    public function withUser(?User $user = null): static
    {
        return $this->state(function (array $attributes) use ($user) {
            $user = $user ?? User::factory()->create([
                'user_type' => 'student',
                'email' => $attributes['email'],
                'first_name' => $attributes['first_name'],
                'last_name' => $attributes['last_name'],
            ]);

            return [
                'user_id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ];
        });
    }

    /**
     * Create a student with a parent.
     */
    public function withParent(?ParentProfile $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? ParentProfile::factory(),
        ]);
    }

    /**
     * Create a student with a grade level.
     */
    public function withGradeLevel(?AcademicGradeLevel $gradeLevel = null): static
    {
        return $this->state(fn (array $attributes) => [
            'grade_level_id' => $gradeLevel?->id ?? AcademicGradeLevel::factory(),
        ]);
    }

    /**
     * Create an unlinked student profile (no user account).
     */
    public function unlinked(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Create a male student.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 'male',
            'first_name' => fake()->firstNameMale(),
        ]);
    }

    /**
     * Create a female student.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 'female',
            'first_name' => fake()->firstNameFemale(),
        ]);
    }

    /**
     * Create a young student (5-10 years old).
     */
    public function young(): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_date' => fake()->dateTimeBetween('-10 years', '-5 years'),
        ]);
    }

    /**
     * Create a teenage student (11-18 years old).
     */
    public function teenager(): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_date' => fake()->dateTimeBetween('-18 years', '-11 years'),
        ]);
    }
}
