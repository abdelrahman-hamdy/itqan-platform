<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\AcademySettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademySettings>
 */
class AcademySettingsFactory extends Factory
{
    protected $model = AcademySettings::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'timezone' => 'Asia/Riyadh',
            'default_session_duration' => 45,
            'default_preparation_minutes' => 5,
            'default_buffer_minutes' => 10,
            'default_late_tolerance_minutes' => 5,
            'requires_session_approval' => false,
            'allows_teacher_creation' => true,
            'allows_student_enrollment' => true,
            'default_attendance_threshold_percentage' => 75,
            'trial_session_duration' => 30,
            'trial_expiration_days' => 7,
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
}
