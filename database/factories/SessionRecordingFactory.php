<?php

namespace Database\Factories;

use App\Enums\RecordingStatus;
use App\Models\InteractiveCourseSession;
use App\Models\SessionRecording;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SessionRecording>
 */
class SessionRecordingFactory extends Factory
{
    protected $model = SessionRecording::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-1 week', 'now');
        $endedAt = (clone $startedAt)->modify('+'.rand(15, 90).' minutes');
        $duration = ($endedAt->getTimestamp() - $startedAt->getTimestamp());

        return [
            'recordable_type' => InteractiveCourseSession::class,
            'recordable_id' => InteractiveCourseSession::factory(),
            'recording_id' => 'EG_'.fake()->uuid(),
            'meeting_room' => 'room_'.fake()->uuid(),
            'status' => RecordingStatus::COMPLETED,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration' => $duration,
            'file_path' => '/recordings/'.fake()->uuid().'.mp4',
            'file_name' => 'recording_'.fake()->dateTimeThisMonth()->format('Y-m-d_H-i').'.mp4',
            'file_size' => rand(10000000, 500000000), // 10MB - 500MB
            'file_format' => 'mp4',
            'metadata' => [
                'session_type' => 'interactive_course',
                'participants' => rand(2, 30),
            ],
            'processing_error' => null,
            'processed_at' => $endedAt,
            'completed_at' => (clone $endedAt)->modify('+5 minutes'),
        ];
    }

    /**
     * Indicate the recording is in progress.
     */
    public function recording(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecordingStatus::RECORDING,
            'ended_at' => null,
            'duration' => null,
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
            'processed_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate the recording is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecordingStatus::PROCESSING,
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
            'processed_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate the recording has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecordingStatus::FAILED,
            'processing_error' => fake()->sentence(),
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate the recording has been deleted.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RecordingStatus::DELETED,
        ]);
    }
}
