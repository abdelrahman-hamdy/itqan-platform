<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\MeetingAttendanceEvent;
use App\Models\QuranSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingAttendanceEvent>
 */
class MeetingAttendanceEventFactory extends Factory
{
    protected $model = MeetingAttendanceEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventTimestamp = fake()->dateTimeBetween('-1 day', 'now');
        $leftAt = rand(0, 1) ? (clone $eventTimestamp)->modify('+' . rand(15, 60) . ' minutes') : null;
        $durationMinutes = $leftAt ? (int) round(($leftAt->getTimestamp() - $eventTimestamp->getTimestamp()) / 60) : null;

        return [
            'event_id' => fake()->uuid(),
            'event_type' => 'join',
            'event_timestamp' => $eventTimestamp,
            'session_id' => QuranSession::factory(),
            'session_type' => QuranSession::class,
            'user_id' => User::factory(),
            'academy_id' => Academy::factory(),
            'participant_sid' => 'PA_' . fake()->randomAlphanumeric(16),
            'participant_identity' => fake()->uuid(),
            'participant_name' => fake()->name(),
            'left_at' => $leftAt,
            'duration_minutes' => $durationMinutes,
            'leave_event_id' => $leftAt ? fake()->uuid() : null,
            'raw_webhook_data' => [
                'event' => 'participant_joined',
                'room' => ['name' => 'room_' . fake()->uuid()],
                'participant' => [
                    'sid' => 'PA_' . fake()->randomAlphanumeric(16),
                    'identity' => fake()->uuid(),
                ],
            ],
            'termination_reason' => null,
        ];
    }

    /**
     * Create a join event that is still active (no leave).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'join',
            'left_at' => null,
            'duration_minutes' => null,
            'leave_event_id' => null,
        ]);
    }

    /**
     * Create a completed attendance cycle (join + leave).
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $eventTimestamp = $attributes['event_timestamp'] ?? now()->subHour();
            $leftAt = (clone $eventTimestamp)->modify('+' . rand(20, 60) . ' minutes');
            $durationMinutes = (int) round(($leftAt->getTimestamp() - $eventTimestamp->getTimestamp()) / 60);

            return [
                'event_type' => 'join',
                'left_at' => $leftAt,
                'duration_minutes' => $durationMinutes,
                'leave_event_id' => fake()->uuid(),
            ];
        });
    }

    /**
     * Create a reconciled event (missed webhook).
     */
    public function reconciled(): static
    {
        return $this->completed()->state(fn (array $attributes) => [
            'termination_reason' => 'reconciled_missed_webhook',
        ]);
    }
}
