<?php

namespace App\Enums;

enum EducationalQualification: string
{
    case BACHELOR = 'bachelor';
    case MASTER = 'master';
    case PHD = 'phd';
    case OTHER = 'other';

    /**
     * Get the Arabic label for the qualification
     */
    public function label(): string
    {
        return match ($this) {
            self::BACHELOR => 'بكالوريوس',
            self::MASTER => 'ماجستير',
            self::PHD => 'دكتوراه',
            self::OTHER => 'أخرى',
        };
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
}
