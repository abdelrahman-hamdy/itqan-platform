<?php

namespace Database\Factories;

use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuranSessionHomeworkFactory extends Factory
{
    protected $model = QuranSessionHomework::class;

    public function definition(): array
    {
        $session = QuranSession::factory();
        $teacher = User::factory()->state(['user_type' => 'quran_teacher']);

        $hasNewMemorization = $this->faker->boolean(80);
        $hasReview = $this->faker->boolean(70);
        $hasComprehensiveReview = $this->faker->boolean(30);

        return [
            'session_id' => $session,
            'created_by' => $teacher,

            // Homework type flags
            'has_new_memorization' => $hasNewMemorization,
            'has_review' => $hasReview,
            'has_comprehensive_review' => $hasComprehensiveReview,

            // New memorization details
            'new_memorization_pages' => $hasNewMemorization ? $this->faker->randomFloat(2, 0.25, 3) : null,
            'new_memorization_surah' => $hasNewMemorization ? $this->faker->randomElement([
                'سورة البقرة', 'سورة آل عمران', 'سورة النساء', 'سورة المائدة', 'سورة الأنعام',
                'سورة الأعراف', 'سورة الأنفال', 'سورة التوبة', 'سورة يونس', 'سورة هود',
            ]) : null,
            'new_memorization_from_verse' => $hasNewMemorization ? $this->faker->numberBetween(1, 50) : null,
            'new_memorization_to_verse' => $hasNewMemorization ? $this->faker->numberBetween(51, 100) : null,

            // Review details
            'review_pages' => $hasReview ? $this->faker->randomFloat(2, 0.5, 5) : null,
            'review_surah' => $hasReview ? $this->faker->randomElement([
                'سورة الفاتحة', 'سورة البقرة', 'سورة آل عمران', 'سورة النساء', 'سورة المائدة',
            ]) : null,
            'review_from_verse' => $hasReview ? $this->faker->numberBetween(1, 30) : null,
            'review_to_verse' => $hasReview ? $this->faker->numberBetween(31, 80) : null,

            // Comprehensive review details
            'comprehensive_review_surahs' => $hasComprehensiveReview ? $this->faker->randomElements([
                'سورة الملك', 'سورة القلم', 'سورة الحاقة', 'سورة المعارج', 'سورة نوح',
                'سورة الجن', 'سورة المزمل', 'سورة المدثر', 'سورة القيامة',
            ], $this->faker->numberBetween(1, 4)) : null,

            // Additional metadata
            'additional_instructions' => $this->faker->optional(0.4)->sentence(10),
            'due_date' => $this->faker->optional(0.8)->dateTimeBetween('tomorrow', '+1 week'),
            'difficulty_level' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            'is_active' => $this->faker->boolean(95),
        ];
    }

    /**
     * Homework with only new memorization
     */
    public function newMemorizationOnly(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'has_new_memorization' => true,
                'has_review' => false,
                'has_comprehensive_review' => false,
                'new_memorization_pages' => $this->faker->randomFloat(2, 0.25, 2),
                'new_memorization_surah' => 'سورة البقرة',
                'new_memorization_from_verse' => 1,
                'new_memorization_to_verse' => 20,
                'review_pages' => null,
                'review_surah' => null,
                'comprehensive_review_surahs' => null,
            ];
        });
    }

    /**
     * Homework with only review
     */
    public function reviewOnly(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'has_new_memorization' => false,
                'has_review' => true,
                'has_comprehensive_review' => false,
                'new_memorization_pages' => null,
                'new_memorization_surah' => null,
                'review_pages' => $this->faker->randomFloat(2, 1, 4),
                'review_surah' => 'سورة الفاتحة',
                'review_from_verse' => 1,
                'review_to_verse' => 7,
                'comprehensive_review_surahs' => null,
            ];
        });
    }

    /**
     * Comprehensive homework with all types
     */
    public function comprehensive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'has_new_memorization' => true,
                'has_review' => true,
                'has_comprehensive_review' => true,
                'new_memorization_pages' => $this->faker->randomFloat(2, 0.5, 1.5),
                'new_memorization_surah' => 'سورة آل عمران',
                'review_pages' => $this->faker->randomFloat(2, 2, 4),
                'review_surah' => 'سورة البقرة',
                'comprehensive_review_surahs' => ['سورة الملك', 'سورة القلم'],
                'difficulty_level' => 'hard',
            ];
        });
    }

    /**
     * Easy homework
     */
    public function easy(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'difficulty_level' => 'easy',
                'new_memorization_pages' => 0.25,
                'review_pages' => 0.5,
            ];
        });
    }

    /**
     * Hard homework
     */
    public function hard(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'difficulty_level' => 'hard',
                'has_new_memorization' => true,
                'has_review' => true,
                'has_comprehensive_review' => true,
                'new_memorization_pages' => $this->faker->randomFloat(2, 2, 5),
                'review_pages' => $this->faker->randomFloat(2, 3, 8),
            ];
        });
    }

    /**
     * Overdue homework
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'due_date' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
            ];
        });
    }

    /**
     * Inactive homework
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
