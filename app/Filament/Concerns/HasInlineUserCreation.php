<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Rules\PasswordRules;
use App\Enums\Gender;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Forms;

/**
 * Trait for resources that need inline user creation in Select fields.
 *
 * Provides reusable form schema and creation logic for user account creation
 * within teacher profile resources.
 *
 * Usage:
 * ```php
 * Forms\Components\Select::make('user_id')
 *     ->relationship('user', 'email')
 *     ->createOptionForm(static::getUserCreationFormSchema())
 *     ->createOptionUsing(fn (array $data) => static::createUserFromFormData($data, 'quran_teacher'))
 * ```
 */
trait HasInlineUserCreation
{
    /**
     * Get the form schema for inline user creation.
     *
     * @return array<\Filament\Schemas\Components\Component>
     */
    protected static function getUserCreationFormSchema(): array
    {
        return [
            TextInput::make('first_name')
                ->label('الاسم الأول')
                ->required()
                ->maxLength(255),
            TextInput::make('last_name')
                ->label('اسم العائلة')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->required()
                ->unique('users', 'email')
                ->maxLength(255),
            static::getPhoneInput(),
            Select::make('gender')
                ->label('الجنس')
                ->options(Gender::options())
                ->required(),
            TextInput::make('password')
                ->label('كلمة المرور')
                ->password()
                ->required()
                ->minLength(6)
                ->maxLength(255)
                ->rules([PasswordRules::rule()])
                ->helperText(PasswordRules::description()),
        ];
    }

    /**
     * Create a user from the inline form data.
     *
     * @param  array  $data  Form data from createOptionUsing
     * @param  string  $userType  The user type (e.g., 'quran_teacher', 'academic_teacher')
     * @param  bool  $activeByDefault  Whether the user should be active by default
     * @return int The created user's ID
     */
    protected static function createUserFromFormData(array $data, string $userType, bool $activeByDefault = false): int
    {
        $academyId = AcademyContextService::getCurrentAcademy()?->id;

        $user = User::create([
            'academy_id' => $academyId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'],
            'password' => bcrypt($data['password']),
            'user_type' => $userType,
            'active_status' => $activeByDefault,
        ]);

        return $user->id;
    }

    /**
     * Get user options for a Select field, scoped to current academy.
     *
     * @param  string  $userType  The user type to filter by
     */
    protected static function getUserOptionsForType(string $userType): array
    {
        $academyId = AcademyContextService::getCurrentAcademy()?->id;

        $query = User::where('user_type', $userType);

        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        return $query->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn ($user) => [
                $user->id => $user->full_name ?? $user->name ?? $user->email,
            ])
            ->toArray();
    }
}
