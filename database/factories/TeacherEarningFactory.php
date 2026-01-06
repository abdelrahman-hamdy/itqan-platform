<?php

namespace Database\Factories;

use App\Models\AcademicTeacherProfile;
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
            'teacher_type' => QuranTeacherProfile::class,
            'teacher_id' => QuranTeacherProfile::factory(),
            'session_type' => 'quran_session', // Uses morph map alias
            'session_id' => 1,
            'amount' => fake()->randomFloat(2, 50, 200),
            'calculation_method' => fake()->randomElement(['individual_rate', 'group_rate', 'per_session']),
            'rate_snapshot' => ['rate' => fake()->numberBetween(50, 150)],
            'calculation_metadata' => [
                'session_duration' => fake()->numberBetween(30, 60),
                'students_count' => fake()->numberBetween(1, 5),
            ],
            'earning_month' => now()->startOfMonth()->format('Y-m-d'),
            'session_completed_at' => now()->subDays(fake()->numberBetween(1, 28)),
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
     * Disputed earning
     */
    public function disputed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_disputed' => true,
            'dispute_notes' => fake()->sentence(),
        ]);
    }

    /**
     * For Quran teacher
     */
    public function forQuranTeacher(?QuranTeacherProfile $profile = null): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_type' => QuranTeacherProfile::class,
            'teacher_id' => $profile?->id ?? QuranTeacherProfile::factory(),
            'session_type' => 'quran_session',
        ]);
    }

    /**
     * For Academic teacher
     */
    public function forAcademicTeacher(?AcademicTeacherProfile $profile = null): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_type' => AcademicTeacherProfile::class,
            'teacher_id' => $profile?->id ?? AcademicTeacherProfile::factory(),
            'session_type' => 'academic_session',
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

    /**
     * For a specific month
     */
    public function forMonth(int $year, int $month): static
    {
        $monthDate = sprintf('%04d-%02d-01', $year, $month);

        return $this->state(fn (array $attributes) => [
            'earning_month' => $monthDate,
        ]);
    }
}
