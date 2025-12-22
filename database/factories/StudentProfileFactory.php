<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\ParentProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        // Use a random suffix to avoid collisions with users table emails
        $email = 'sp_' . Str::random(8) . '@' . fake()->safeEmailDomain();

        return [
            'user_id' => null, // Will be linked later
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
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
     * Configure the model factory.
     * Handles academy_id -> grade_level_id conversion for backward compatibility.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (StudentProfile $profile) {
            // If academy_id was set (not a real column), use it to set grade_level
            $rawAttributes = $profile->getAttributes();
            if (array_key_exists('academy_id', $rawAttributes)) {
                $academyId = $rawAttributes['academy_id'];

                // Remove academy_id from the model's attributes to prevent SQL error
                $profile->offsetUnset('academy_id');

                // If no grade level is set, create one for this academy
                // Use withoutGlobalScopes to bypass academy scoping during factory creation
                if (empty($profile->grade_level_id)) {
                    $gradeLevel = AcademicGradeLevel::withoutGlobalScopes()
                        ->firstOrCreate(
                            ['academy_id' => $academyId, 'name' => 'Grade 1'],
                            ['order' => 1]
                        );
                    $profile->grade_level_id = $gradeLevel->id;
                }
            }
        });
    }

    /**
     * Create a student profile for a specific academy.
     * Links the student to the academy through grade_level.
     */
    public function forAcademy(Academy $academy): static
    {
        return $this->state(function (array $attributes) use ($academy) {
            // Find or create a grade level for this academy
            $gradeLevel = AcademicGradeLevel::firstOrCreate(
                ['academy_id' => $academy->id, 'name' => 'Grade 1'],
                ['order' => 1]
            );

            return [
                'grade_level_id' => $gradeLevel->id,
            ];
        });
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
