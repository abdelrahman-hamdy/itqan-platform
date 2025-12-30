<?php

namespace App\Enums;

/**
 * Quran Learning Level Enum
 *
 * Defines the current Quran learning proficiency levels for students.
 * Used in subscription forms to assess student's starting point.
 *
 * @see \App\Models\QuranSubscription
 */
enum QuranLearningLevel: string
{
    case BEGINNER = 'beginner';          // مبتدئ - لا أعرف القراءة
    case ELEMENTARY = 'elementary';      // أساسي - أقرأ ببطء
    case INTERMEDIATE = 'intermediate';  // متوسط - أقرأ بطلاقة
    case ADVANCED = 'advanced';          // متقدم - أحفظ أجزاء من القرآن
    case EXPERT = 'expert';              // متمكن - أحفظ أكثر من 10 أجزاء
    case HAFIZ = 'hafiz';                // حافظ - أحفظ القرآن كاملاً

    /**
     * Get localized label for the learning level
     */
    public function label(): string
    {
        return __('enums.quran_learning_level.' . $this->value);
    }

    /**
     * Get all learning level values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get learning level options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($level) => $level->label(), self::cases())
        );
    }
}
