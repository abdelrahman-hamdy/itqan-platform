<?php

namespace App\Enums;

/**
 * Learning Goal Enum
 *
 * Defines the Quran learning goals that students can select.
 * Used in subscription forms to understand student's objectives.
 *
 * @see \App\Models\QuranSubscription
 */
enum LearningGoal: string
{
    case READING = 'reading';            // تعلم القراءة الصحيحة
    case TAJWEED = 'tajweed';            // تعلم أحكام التجويد
    case MEMORIZATION = 'memorization';  // حفظ القرآن الكريم
    case IMPROVEMENT = 'improvement';    // تحسين الأداء والإتقان

    /**
     * Get localized label for the learning goal
     */
    public function label(): string
    {
        return __('enums.learning_goal.'.$this->value);
    }

    /**
     * Get all learning goal values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get learning goal options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($goal) => $goal->label(), self::cases())
        );
    }
}
