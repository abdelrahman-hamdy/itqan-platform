<?php

namespace App\Enums;

use App\Models\AcademicIndividualLesson;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\RecordedCourse;

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
            self::QURAN_CIRCLE => 'حلقة قرآن جماعية',
            self::QURAN_INDIVIDUAL_CIRCLE => 'حلقة قرآن فردية',
            self::ACADEMIC_INDIVIDUAL_LESSON => 'درس أكاديمي خاص',
            self::INTERACTIVE_COURSE => 'دورة تفاعلية',
            self::RECORDED_COURSE => 'دورة مسجلة',
        };
    }

    /**
     * Get the English label for this assignable type
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::QURAN_CIRCLE => 'Quran Group Circle',
            self::QURAN_INDIVIDUAL_CIRCLE => 'Quran Individual Circle',
            self::ACADEMIC_INDIVIDUAL_LESSON => 'Academic Private Lesson',
            self::INTERACTIVE_COURSE => 'Interactive Course',
            self::RECORDED_COURSE => 'Recorded Course',
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
