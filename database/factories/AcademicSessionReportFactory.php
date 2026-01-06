<?php

namespace Database\Factories;

use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicSessionReport>
 */
class AcademicSessionReportFactory extends Factory
{
    protected $model = AcademicSessionReport::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'session_id' => AcademicSession::factory(),
            'student_id' => User::factory()->student(),
            'teacher_id' => User::factory()->academicTeacher(), // teacher_id references users, not profiles
            'academy_id' => Academy::factory(),
            'attendance_status' => fake()->randomElement(['attended', 'absent', 'late', 'left']),
            'is_calculated' => true,
            'evaluated_at' => now(),
        ];
    }
}
