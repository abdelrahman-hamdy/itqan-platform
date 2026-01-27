<?php

namespace App\Rules;

use Illuminate\Validation\Rules\Password;

/**
 * Centralized password rules for the entire platform.
 *
 * Rule: minimum 6 characters, at least one letter, at least one number.
 *
 * Usage:
 *   'password' => ['required', 'confirmed', ...PasswordRules::create()]
 *   'password' => ['required', 'confirmed', ...PasswordRules::update()]
 */
class PasswordRules
{
    /**
     * Get the unified password validation rule.
     *
     * Minimum 6 characters, at least one letter, at least one number.
     */
    public static function rule(): Password
    {
        return Password::min(6)->letters()->numbers();
    }

    /**
     * Rules for creating a new password (registration, admin create).
     *
     * @return array<mixed>
     */
    public static function create(): array
    {
        return ['required', 'confirmed', static::rule()];
    }

    /**
     * Rules for updating/changing a password (optional, confirmed).
     *
     * @return array<mixed>
     */
    public static function update(): array
    {
        return ['sometimes', 'string', 'confirmed', static::rule()];
    }

    /**
     * Rules for required password change (e.g., reset password).
     *
     * @return array<mixed>
     */
    public static function reset(): array
    {
        return ['required', 'confirmed', static::rule()];
    }

    /**
     * Validation messages in Arabic for password fields.
     *
     * @param  string  $field  The password field name (default: 'password')
     * @return array<string, string>
     */
    public static function messages(string $field = 'password'): array
    {
        return [
            "{$field}.required" => __('كلمة المرور مطلوبة'),
            "{$field}.min" => __('كلمة المرور يجب أن تكون 6 أحرف على الأقل'),
            "{$field}.letters" => __('كلمة المرور يجب أن تحتوي على حرف واحد على الأقل'),
            "{$field}.numbers" => __('كلمة المرور يجب أن تحتوي على رقم واحد على الأقل'),
            "{$field}.confirmed" => __('كلمة المرور غير متطابقة'),
        ];
    }

    /**
     * Validation messages in English for password fields.
     *
     * @param  string  $field  The password field name (default: 'password')
     * @return array<string, string>
     */
    public static function messagesEn(string $field = 'password'): array
    {
        return [
            "{$field}.required" => __('Password is required.'),
            "{$field}.min" => __('Password must be at least 6 characters.'),
            "{$field}.letters" => __('Password must contain at least one letter.'),
            "{$field}.numbers" => __('Password must contain at least one number.'),
            "{$field}.confirmed" => __('Password confirmation does not match.'),
        ];
    }

    /**
     * Get Filament-compatible min length for form fields.
     */
    public static function minLength(): int
    {
        return 6;
    }

    /**
     * Get a human-readable description of the password rules.
     */
    public static function description(): string
    {
        return __('كلمة المرور يجب أن تكون 6 أحرف على الأقل وتحتوي على حرف ورقم');
    }

    /**
     * Get a human-readable description in English.
     */
    public static function descriptionEn(): string
    {
        return __('Password must be at least 6 characters and contain at least one letter and one number.');
    }
}
