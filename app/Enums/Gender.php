<?php

namespace App\Enums;

/**
 * Gender Enum
 *
 * Represents user gender options.
 * Used for teacher profiles, student profiles, and user accounts.
 *
 * @see \App\Models\User
 * @see \App\Models\QuranTeacherProfile
 * @see \App\Models\AcademicTeacherProfile
 */
enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.gender.' . $this->value);
    }

    /**
     * Get teacher-specific label (معلم/معلمة instead of ذكر/أنثى)
     */
    public function teacherLabel(): string
    {
        return match ($this) {
            self::MALE => __('enums.gender.teacher_male'),
            self::FEMALE => __('enums.gender.teacher_female'),
        };
    }

    /**
     * Get all genders as options for select inputs
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $gender) => [$gender->value => $gender->label()]
        )->all();
    }

    /**
     * Get teacher-specific options
     */
    public static function teacherOptions(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $gender) => [$gender->value => $gender->teacherLabel()]
        )->all();
    }

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
