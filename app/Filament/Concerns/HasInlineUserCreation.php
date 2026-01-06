<?php

namespace App\Filament\Concerns;

use App\Enums\Gender;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Forms;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

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
     * @return array<Forms\Components\Component>
     */
    protected static function getUserCreationFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('first_name')
                ->label('الاسم الأول')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('last_name')
                ->label('اسم العائلة')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->required()
                ->unique('users', 'email')
                ->maxLength(255),
            PhoneInput::make('phone')
                ->label('رقم الهاتف')
                ->defaultCountry('SA')
                ->initialCountry('sa')
                ->onlyCountries([
                    'sa', 'eg', 'ae', 'kw', 'qa', 'om', 'bh',
                    'jo', 'lb', 'ps', 'iq', 'ye', 'sd', 'tr', 'us', 'gb',
                ])
                ->separateDialCode(true)
                ->formatAsYouType(true)
                ->showFlags(true),
            Forms\Components\Select::make('gender')
                ->label('الجنس')
                ->options(Gender::options())
                ->required(),
            Forms\Components\TextInput::make('password')
                ->label('كلمة المرور')
                ->password()
                ->required()
                ->minLength(8)
                ->maxLength(255),
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
