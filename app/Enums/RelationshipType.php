<?php

namespace App\Enums;

/**
 * Relationship Type Enum
 *
 * Defines parent-child relationships.
 *
 * Types:
 * - FATHER: Father relationship
 * - MOTHER: Mother relationship
 * - OTHER: Other guardian/family relationship
 *
 * @see \App\Models\ParentStudent
 */
enum RelationshipType: string
{
    case FATHER = 'father';
    case MOTHER = 'mother';
    case OTHER = 'other';

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.relationship_type.' . $this->value);
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

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
