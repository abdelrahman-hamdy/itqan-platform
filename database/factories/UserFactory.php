<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('05########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'user_type' => 'student',
            'active_status' => true,
            'academy_id' => Academy::factory(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a super admin user.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'super_admin',
            'academy_id' => null,
        ]);
    }

    /**
     * Create an academy admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'admin',
        ]);
    }

    /**
     * Create a supervisor user.
     */
    public function supervisor(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'supervisor',
        ]);
    }

    /**
     * Create a Quran teacher user.
     */
    public function quranTeacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'quran_teacher',
        ]);
    }

    /**
     * Create an academic teacher user.
     */
    public function academicTeacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'academic_teacher',
        ]);
    }

    /**
     * Create a student user.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'student',
        ]);
    }

    /**
     * Create a parent user.
     */
    public function parent(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'parent',
        ]);
    }

    /**
     * Create an inactive user.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active_status' => false,
        ]);
    }

    /**
     * Create a user for a specific academy.
     */
    public function forAcademy(Academy $academy): static
    {
        return $this->state(fn (array $attributes) => [
            'academy_id' => $academy->id,
        ]);
    }

    /**
     * Create a male user.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => fake()->firstNameMale(),
            'gender' => 'male',
        ]);
    }

    /**
     * Create a female user.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => fake()->firstNameFemale(),
            'gender' => 'female',
        ]);
    }

    /**
     * Create a user with a specific password.
     */
    public function withPassword(string $password): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => Hash::make($password),
        ]);
    }
}
