<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\ChatGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatGroup>
 */
class ChatGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'name' => fake()->sentence(3),
            'type' => ChatGroup::TYPE_CUSTOM,
            'owner_id' => User::factory(),
            'metadata' => [
                'description' => fake()->sentence(),
            ],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the chat group is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the chat group is for a Quran circle.
     */
    public function forQuranCircle(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChatGroup::TYPE_QURAN_CIRCLE,
        ]);
    }

    /**
     * Indicate that the chat group is for an individual session.
     */
    public function forIndividualSession(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChatGroup::TYPE_INDIVIDUAL_SESSION,
        ]);
    }

    /**
     * Indicate that the chat group is for an academic session.
     */
    public function forAcademicSession(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChatGroup::TYPE_ACADEMIC_SESSION,
        ]);
    }

    /**
     * Indicate that the chat group is for an interactive course.
     */
    public function forInteractiveCourse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChatGroup::TYPE_INTERACTIVE_COURSE,
        ]);
    }

    /**
     * Indicate that the chat group is for a recorded course.
     */
    public function forRecordedCourse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChatGroup::TYPE_RECORDED_COURSE,
        ]);
    }

    /**
     * Indicate that the chat group is an announcement group.
     */
    public function announcement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChatGroup::TYPE_ANNOUNCEMENT,
        ]);
    }
}
