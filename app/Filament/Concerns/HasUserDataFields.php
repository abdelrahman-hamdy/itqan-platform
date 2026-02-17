<?php

namespace App\Filament\Concerns;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
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
            Section::make('معلومات الحساب')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('user_first_name')
                                ->label('الاسم الأول')
                                ->required()
                                ->maxLength(255)
                                ->dehydrated(false),
                            TextInput::make('user_last_name')
                                ->label('اسم العائلة')
                                ->required()
                                ->maxLength(255)
                                ->dehydrated(false),
                            TextInput::make('user_email')
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
