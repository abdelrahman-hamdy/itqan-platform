<?php

namespace App\Enums;

/**
 * Educational Qualification Enum
 *
 * Defines academic qualification levels for teacher profiles.
 *
 * Levels:
 * - DIPLOMA: Diploma degree
 * - BACHELOR: Bachelor's degree
 * - MASTER: Master's degree
 * - PHD: Doctorate degree
 * - OTHER: Other qualifications
 *
 * @see \App\Models\QuranTeacherProfile
 * @see \App\Models\AcademicTeacherProfile
 */
enum EducationalQualification: string
{
    case DIPLOMA = 'diploma';
    case BACHELOR = 'bachelor';
    case MASTER = 'master';
    case PHD = 'phd';
    case OTHER = 'other';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.educational_qualification.' . $this->value);
    }

    /**
     * Get all qualifications as an array for form options
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $qualification) => [$qualification->value => $qualification->label()])
            ->toArray();
    }

    /**
     * Get qualification label safely
     */
    public static function getLabel(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return self::from($value)->label();
        } catch (\ValueError $e) {
            return $value;
        }
    }

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
