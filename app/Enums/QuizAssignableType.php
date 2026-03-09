<?php

namespace App\Enums;

/**
 * QuizAssignableType Enum
 *
 * Defines the valid education unit types that quizzes can be assigned to.
 * Note: Quizzes are assigned at the EDUCATION UNIT level (circles, courses, lessons),
 * NOT at the individual session level (QuranSession, AcademicSession, etc.)
 */
enum QuizAssignableType: string
{
    case QURAN_CIRCLE = 'App\Models\QuranCircle';
    case QURAN_INDIVIDUAL_CIRCLE = 'App\Models\QuranIndividualCircle';
    case ACADEMIC_INDIVIDUAL_LESSON = 'App\Models\AcademicIndividualLesson';
    case INTERACTIVE_COURSE = 'App\Models\InteractiveCourse';
    case RECORDED_COURSE = 'App\Models\RecordedCourse';

    /**
     * Get the Arabic label for this assignable type
     */
    public function label(): string
    {
        return match ($this) {
            self::QURAN_CIRCLE => __('teacher.quizzes.type_quran_circle'),
            self::QURAN_INDIVIDUAL_CIRCLE => __('teacher.quizzes.type_quran_individual'),
            self::ACADEMIC_INDIVIDUAL_LESSON => __('teacher.quizzes.type_academic_lesson'),
            self::INTERACTIVE_COURSE => __('teacher.quizzes.type_interactive_course'),
            self::RECORDED_COURSE => __('teacher.quizzes.type_recorded_course'),
        };
    }

    /**
     * Get the English label for this assignable type
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::QURAN_CIRCLE => __('teacher.quizzes.type_quran_circle', [], 'en'),
            self::QURAN_INDIVIDUAL_CIRCLE => __('teacher.quizzes.type_quran_individual', [], 'en'),
            self::ACADEMIC_INDIVIDUAL_LESSON => __('teacher.quizzes.type_academic_lesson', [], 'en'),
            self::INTERACTIVE_COURSE => __('teacher.quizzes.type_interactive_course', [], 'en'),
            self::RECORDED_COURSE => __('teacher.quizzes.type_recorded_course', [], 'en'),
        };
    }

    /**
     * Get the model class for this type
     */
    public function modelClass(): string
    {
        return $this->value;
    }

    /**
     * Get all types as options array for Filament selects
     * Returns [model_class => label] format
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * Get all types as options array with English labels
     */
    public static function optionsEn(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->labelEn();
        }

        return $options;
    }

    /**
     * Create from model class string
     */
    public static function fromModelClass(string $class): ?self
    {
        return self::tryFrom($class);
    }

    /**
     * Check if a model class is a valid assignable type
     */
    public static function isValid(string $class): bool
    {
        return self::tryFrom($class) !== null;
    }

    /**
     * Get the icon for this type (for Filament UI)
     */
    public function icon(): string
    {
        return match ($this) {
            self::QURAN_CIRCLE => 'heroicon-o-user-group',
            self::QURAN_INDIVIDUAL_CIRCLE => 'heroicon-o-user',
            self::ACADEMIC_INDIVIDUAL_LESSON => 'heroicon-o-academic-cap',
            self::INTERACTIVE_COURSE => 'heroicon-o-video-camera',
            self::RECORDED_COURSE => 'heroicon-o-play-circle',
        };
    }

    /**
     * Get the color for this type (for Filament UI)
     */
    public function color(): string
    {
        return match ($this) {
            self::QURAN_CIRCLE => 'success',
            self::QURAN_INDIVIDUAL_CIRCLE => 'info',
            self::ACADEMIC_INDIVIDUAL_LESSON => 'warning',
            self::INTERACTIVE_COURSE => 'primary',
            self::RECORDED_COURSE => 'gray',
        };
    }
}
