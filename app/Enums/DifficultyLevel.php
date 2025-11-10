<?php

namespace App\Enums;

enum DifficultyLevel: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';

    /**
     * Get label in Arabic
     */
    public function label(): string
    {
        return match ($this) {
            self::BEGINNER => 'مبتدئ',
            self::INTERMEDIATE => 'متوسط',
            self::ADVANCED => 'متقدم',
        };
    }

    /**
     * Get label in English
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::BEGINNER => 'Beginner',
            self::INTERMEDIATE => 'Intermediate',
            self::ADVANCED => 'Advanced',
        };
    }

    /**
     * Get all options for select fields (Arabic labels)
     */
    public static function options(): array
    {
        return [
            self::BEGINNER->value => self::BEGINNER->label(),
            self::INTERMEDIATE->value => self::INTERMEDIATE->label(),
            self::ADVANCED->value => self::ADVANCED->label(),
        ];
    }

    /**
     * Get all options for select fields (English labels)
     */
    public static function optionsEn(): array
    {
        return [
            self::BEGINNER->value => self::BEGINNER->labelEn(),
            self::INTERMEDIATE->value => self::INTERMEDIATE->labelEn(),
            self::ADVANCED->value => self::ADVANCED->labelEn(),
        ];
    }
}
