<?php

namespace App\Enums;

enum RelationshipType: string
{
    case FATHER = 'father';
    case MOTHER = 'mother';
    case OTHER = 'other';

    /**
     * Get the Arabic label for the relationship type
     */
    public function label(): string
    {
        return match ($this) {
            self::FATHER => 'أب',
            self::MOTHER => 'أم',
            self::OTHER => 'أخرى',
        };
    }

    /**
     * Get all labels as an array
     */
    public static function labels(): array
    {
        return [
            self::FATHER->value => self::FATHER->label(),
            self::MOTHER->value => self::MOTHER->label(),
            self::OTHER->value => self::OTHER->label(),
        ];
    }
}
