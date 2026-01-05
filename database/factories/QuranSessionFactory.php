<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuranSession>
 */
class QuranSessionFactory extends Factory
{
    protected $model = QuranSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scheduledAt = fake()->dateTimeBetween('now', '+7 days');

        return [
            'academy_id' => Academy::factory(),
            'quran_teacher_id' => User::factory()->state(['user_type' => 'quran_teacher']),
            'student_id' => User::factory()->state(['user_type' => 'student']),
            'session_type' => 'individual',
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => 45,
            'title' => 'Quran Memorization Session',
            'session_code' => 'QS-' . fake()->unique()->numberBetween(10000, 99999),
        ];
    }

    /**
     * Create an individual session.
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => 'individual',
        ]);
    }

    /**
     * Create a group session.
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => 'group',
            'student_id' => null,
        ]);
    }

    /**
     * Create a scheduled session.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::SCHEDULED,
            'scheduled_at' => fake()->dateTimeBetween('+1 hour', '+7 days'),
        ]);
    }

    /**
     * Create an ongoing session.
     */
    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::ONGOING,
            'scheduled_at' => now()->subMinutes(15),
            'started_at' => now()->subMinutes(15),
        ]);
    }

    /**
     * Create a completed session.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::COMPLETED,
            'scheduled_at' => now()->subHours(2),
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
            'actual_duration_minutes' => 45,
            'attendance_status' => 'attended',
        ]);
    }

    /**
     * Create a cancelled session.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Create an absent session.
     */
    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::ABSENT,
            'scheduled_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
            'attendance_status' => 'absent',
        ]);
    }

    /**
     * Create a session ready to start.
     */
    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::READY,
            'scheduled_at' => now()->addMinutes(5),
            'meeting_room_name' => 'room-' . fake()->uuid(),
            'meeting_link' => 'https://meet.example.com/' . fake()->uuid(),
        ]);
    }

    /**
     * Create a session for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => now()->setTime(
                fake()->numberBetween(8, 20),
                fake()->randomElement([0, 15, 30, 45]),
                0
            ),
        ]);
    }

    /**
     * Create a session in the past.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Create a session with homework assigned.
     */
    public function withHomework(): static
    {
        return $this->state(fn (array $attributes) => [
            'homework_assigned' => ['assigned' => true, 'type' => 'memorization'],
            'homework_details' => fake()->paragraph(),
        ]);
    }

    /**
     * Create a session with meeting data.
     */
    public function withMeeting(): static
    {
        return $this->state(fn (array $attributes) => [
            'meeting_room_name' => 'room-' . fake()->uuid(),
            'meeting_link' => 'https://meet.example.com/' . fake()->uuid(),
            'meeting_id' => fake()->uuid(),
            'meeting_platform' => 'livekit',
            'meeting_auto_generated' => true,
            'meeting_expires_at' => now()->addHours(3),
        ]);
    }

    /**
     * Create a session with a specific teacher.
     */
    public function forTeacher(User $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'quran_teacher_id' => $teacher->id,
            'academy_id' => $teacher->academy_id,
        ]);
    }

    /**
     * Create a session with a specific student.
     */
    public function forStudent(User $student): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $student->id,
            'academy_id' => $student->academy_id,
        ]);
    }

    /**
     * Create a session with a subscription.
     */
    public function withSubscription(?QuranSubscription $subscription = null): static
    {
        return $this->state(fn (array $attributes) => [
            'quran_subscription_id' => $subscription?->id ?? QuranSubscription::factory(),
        ]);
    }

    /**
     * Create a trial session.
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'session_type' => 'trial',
        ]);
    }
}
