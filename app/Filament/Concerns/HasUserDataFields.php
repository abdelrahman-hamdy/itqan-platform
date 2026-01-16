<?php

namespace App\Filament\Concerns;

use Filament\Forms;

trait HasUserDataFields
{
    /**
     * Get the form schema for user data fields (name, email, phone)
     * These fields read from/write to the linked User record
     */
    protected static function getUserDataFormSchema(): array
    {
        return [
            Forms\Components\Section::make('معلومات الحساب')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('user_first_name')
                                ->label('الاسم الأول')
                                ->required()
                                ->maxLength(255)
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('user_last_name')
                                ->label('اسم العائلة')
                                ->required()
                                ->maxLength(255)
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('user_email')
                                ->label('البريد الإلكتروني')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->dehydrated(false),
                            static::getPhoneInput('user_phone', 'رقم الهاتف')
                                ->dehydrated(false),
                        ]),
                ]),
        ];
    }
}
