<?php

namespace App\Enums;

enum CourseLanguage: string
{
    case ARABIC = 'ar';
    case ENGLISH = 'en';
    case BOTH = 'both';

    /**
     * Get label in Arabic
     */
    public function label(): string
    {
        return match ($this) {
            self::ARABIC => 'العربية',
            self::ENGLISH => 'الإنجليزية',
            self::BOTH => 'العربية والإنجليزية',
        };
    }

    /**
     * Get label in English
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::ARABIC => 'Arabic',
            self::ENGLISH => 'English',
            self::BOTH => 'Arabic and English',
        };
    }

    /**
     * Get all options for select fields (Arabic labels)
     */
    public static function options(): array
    {
        return [
            self::ARABIC->value => self::ARABIC->label(),
            self::ENGLISH->value => self::ENGLISH->label(),
            self::BOTH->value => self::BOTH->label(),
        ];
    }

    /**
     * Get all options for select fields (English labels)
     */
    public static function optionsEn(): array
    {
        return [
            self::ARABIC->value => self::ARABIC->labelEn(),
            self::ENGLISH->value => self::ENGLISH->labelEn(),
            self::BOTH->value => self::BOTH->labelEn(),
        ];
    }
}
