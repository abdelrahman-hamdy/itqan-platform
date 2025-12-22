<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherPayout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherPayout>
 */
class TeacherPayoutFactory extends Factory
{
    protected $model = TeacherPayout::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'teacher_type' => 'quran_teacher',
            'teacher_id' => QuranTeacherProfile::factory(),
            'payout_code' => 'PO-' . strtoupper(Str::random(8)),
            'payout_month' => now()->format('Y-m-01'), // Fixed: should be full date format
            'total_amount' => fake()->randomFloat(2, 500, 5000),
            'sessions_count' => fake()->numberBetween(10, 50),
            'breakdown' => [ // Fixed: should be array, not JSON string (cast handles encoding)
                'base_amount' => fake()->randomFloat(2, 400, 4000),
                'bonus' => fake()->randomFloat(2, 0, 500),
                'deductions' => 0,
            ],
            'status' => 'pending',
        ];
    }

    /**
     * Pending payout
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Approved payout
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => User::factory(),
        ]);
    }

    /**
     * Paid payout
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'approved_at' => now()->subDay(),
            'approved_by' => User::factory(),
            'paid_at' => now(),
            'paid_by' => User::factory(),
            'payment_method' => fake()->randomElement(['bank_transfer', 'cash']),
            'payment_reference' => 'REF-' . strtoupper(Str::random(10)),
        ]);
    }

    /**
     * Rejected payout
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => User::factory(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    /**
     * For Quran teacher
     */
    public function forQuranTeacher(QuranTeacherProfile $profile = null): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_type' => 'quran_teacher',
            'teacher_id' => $profile?->id ?? QuranTeacherProfile::factory(),
        ]);
    }

    /**
     * For Academic teacher
     */
    public function forAcademicTeacher(AcademicTeacherProfile $profile = null): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_type' => 'academic_teacher',
            'teacher_id' => $profile?->id ?? AcademicTeacherProfile::factory(),
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
