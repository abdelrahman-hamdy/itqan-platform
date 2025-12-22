<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'student_id' => User::factory()->state(['user_type' => 'student']),
            'teacher_id' => User::factory()->state(['user_type' => 'quran_teacher']),
            'certificateable_type' => 'App\\Models\\QuranSubscription',
            'certificateable_id' => 1,
            'certificate_number' => 'CERT-' . strtoupper(Str::random(10)),
            'certificate_type' => 'completion',
            'template_style' => 'default',
            'certificate_text' => fake()->paragraph(),
            'issued_at' => now(),
            'issued_by' => User::factory(),
            'is_manual' => false,
        ];
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
     * Manual certificate
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_manual' => true,
            'custom_achievement_text' => fake()->sentence(),
        ]);
    }
}
