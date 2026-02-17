<?php

namespace App\Filament\Shared\Resources\Financial;

use App\Filament\Resources\BaseResource;
use App\Models\SavedPaymentMethod;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

abstract class BaseSavedPaymentMethodResource extends BaseResource
{
    protected static ?string $model = SavedPaymentMethod::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $modelLabel = 'طريقة دفع';
    protected static ?string $pluralModelLabel = 'طرق الدفع المحفوظة';

    // Abstract methods for panel-specific implementation
    abstract protected static function scopeEloquentQuery(Builder $query): Builder;
    abstract protected static function getTableActions(): array;
    abstract protected static function getTableBulkActions(): array;
    abstract protected static function getAcademyFormField(): ?Forms\Components\Select;

    // Shared form sections
    protected static function getUserInfoSection(): Forms\Components\Section
    {
        $schema = [];

        // Add academy field if provided (SuperAdmin only)
        $academyField = static::getAcademyFormField();
        if ($academyField) {
            $schema[] = $academyField->disabled();
        }

        $schema[] = Forms\Components\Select::make('user_id')
            ->relationship('user', 'name')
            ->label('المستخدم')
            ->required()
            ->searchable()
            ->preload()
            ->disabled();

        return Forms\Components\Section::make('معلومات المستخدم')
            ->schema($schema)
            ->columns(2);
    }

    protected static function getCardInfoSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('معلومات البطاقة')
            ->schema([
                Forms\Components\Select::make('gateway')
                    ->label('بوابة الدفع')
                    ->options([
                        'paymob' => 'Paymob',
                        'easykash' => 'EasyKash',
                        'tap' => 'Tap Payments',
                    ])
                    ->required()
                    ->disabled(),

                Forms\Components\Select::make('type')
                    ->label('النوع')
                    ->options([
                        SavedPaymentMethod::TYPE_CARD => 'بطاقة',
                        SavedPaymentMethod::TYPE_WALLET => 'محفظة',
                        SavedPaymentMethod::TYPE_APPLE_PAY => 'Apple Pay',
                        SavedPaymentMethod::TYPE_BANK_ACCOUNT => 'حساب بنكي',
                    ])
                    ->required()
                    ->disabled(),

                Forms\Components\Select::make('brand')
                    ->label('العلامة التجارية')
                    ->options([
                        SavedPaymentMethod::BRAND_VISA => 'Visa',
                        SavedPaymentMethod::BRAND_MASTERCARD => 'Mastercard',
                        SavedPaymentMethod::BRAND_MEEZA => 'Meeza',
                        SavedPaymentMethod::BRAND_AMEX => 'American Express',
                    ])
                    ->disabled(),

                Forms\Components\TextInput::make('last_four')
                    ->label('آخر 4 أرقام')
                    ->maxLength(4)
                    ->disabled(),

                Forms\Components\TextInput::make('expiry_month')
                    ->label('شهر الانتهاء')
                    ->maxLength(2)
                    ->disabled(),

                Forms\Components\TextInput::make('expiry_year')
                    ->label('سنة الانتهاء')
                    ->maxLength(4)
                    ->disabled(),

                Forms\Components\TextInput::make('holder_name')
                    ->label('اسم حامل البطاقة')
                    ->maxLength(255)
                    ->disabled(),

                Forms\Components\TextInput::make('display_name')
                    ->label('الاسم المعروض')
                    ->maxLength(255),
            ])->columns(2);
    }

    protected static function getStatusSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('الحالة')
            ->schema([
                Forms\Components\Toggle::make('is_default')
                    ->label('افتراضية')
                    ->helperText('هل هذه طريقة الدفع الافتراضية للمستخدم؟'),

                Forms\Components\Toggle::make('is_active')
                    ->label('نشطة')
                    ->helperText('هل طريقة الدفع نشطة ويمكن استخدامها؟'),

                Forms\Components\DateTimePicker::make('last_used_at')
                    ->label('آخر استخدام')
                    ->disabled(),

                Forms\Components\DateTimePicker::make('verified_at')
                    ->label('تاريخ التحقق')
                    ->disabled(),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('تاريخ انتهاء الصلاحية')
                    ->disabled(),
            ])->columns(3);
    }

    protected static function getAdditionalInfoSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('معلومات إضافية')
            ->schema([
                Forms\Components\KeyValue::make('metadata')
                    ->label('البيانات الإضافية')
                    ->disabled(),

                Forms\Components\KeyValue::make('billing_address')
                    ->label('عنوان الفواتير')
                    ->disabled(),
            ])
            ->collapsible()
            ->collapsed();
    }

    // Shared table columns
    protected static function getSharedTableColumns(): array
    {
        return [
            static::getAcademyColumn(),

            Tables\Columns\TextColumn::make('user.name')
                ->label('المستخدم')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('gateway')
                ->label('البوابة')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'paymob' => 'success',
                    'easykash' => 'info',
                    'tap' => 'warning',
                    default => 'gray',
                }),

            Tables\Columns\TextColumn::make('type')
                ->label('النوع')
                ->formatStateUsing(fn (SavedPaymentMethod $record) => $record->getTypeDisplayName())
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    SavedPaymentMethod::TYPE_CARD => 'primary',
                    SavedPaymentMethod::TYPE_WALLET => 'success',
                    SavedPaymentMethod::TYPE_APPLE_PAY => 'gray',
                    default => 'secondary',
                }),

            Tables\Columns\TextColumn::make('brand')
                ->label('العلامة')
                ->formatStateUsing(fn (?string $state) => match ($state) {
                    SavedPaymentMethod::BRAND_VISA => 'Visa',
                    SavedPaymentMethod::BRAND_MASTERCARD => 'Mastercard',
                    SavedPaymentMethod::BRAND_MEEZA => 'Meeza',
                    SavedPaymentMethod::BRAND_AMEX => 'Amex',
                    default => $state ?? '-',
                })
                ->badge()
                ->color(fn (?string $state): string => match ($state) {
                    SavedPaymentMethod::BRAND_VISA => 'info',
                    SavedPaymentMethod::BRAND_MASTERCARD => 'warning',
                    SavedPaymentMethod::BRAND_MEEZA => 'success',
                    SavedPaymentMethod::BRAND_AMEX => 'primary',
                    default => 'gray',
                }),

            Tables\Columns\TextColumn::make('last_four')
                ->label('آخر 4 أرقام')
                ->formatStateUsing(fn (?string $state) => $state ? "****{$state}" : '-')
                ->copyable()
                ->searchable(),

            Tables\Columns\TextColumn::make('expiry')
                ->label('تاريخ الانتهاء')
                ->getStateUsing(fn (SavedPaymentMethod $record) => $record->getExpiryDisplay())
                ->badge()
                ->color(fn (SavedPaymentMethod $record) => $record->isExpired() ? 'danger' : 'success'),

            Tables\Columns\IconColumn::make('is_default')
                ->label('افتراضية')
                ->boolean()
                ->trueIcon('heroicon-o-star')
                ->falseIcon('heroicon-o-minus')
                ->trueColor('warning')
                ->falseColor('gray'),

            Tables\Columns\IconColumn::make('is_active')
                ->label('نشطة')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger'),

            Tables\Columns\TextColumn::make('last_used_at')
                ->label('آخر استخدام')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime('Y-m-d')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // Shared filters
    protected static function getSharedFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('gateway')
                ->label('بوابة الدفع')
                ->options([
                    'paymob' => 'Paymob',
                    'easykash' => 'EasyKash',
                    'tap' => 'Tap Payments',
                ]),

            Tables\Filters\SelectFilter::make('type')
                ->label('النوع')
                ->options([
                    SavedPaymentMethod::TYPE_CARD => 'بطاقة',
                    SavedPaymentMethod::TYPE_WALLET => 'محفظة',
                    SavedPaymentMethod::TYPE_APPLE_PAY => 'Apple Pay',
                ]),

            Tables\Filters\SelectFilter::make('brand')
                ->label('العلامة التجارية')
                ->options([
                    SavedPaymentMethod::BRAND_VISA => 'Visa',
                    SavedPaymentMethod::BRAND_MASTERCARD => 'Mastercard',
                    SavedPaymentMethod::BRAND_MEEZA => 'Meeza',
                    SavedPaymentMethod::BRAND_AMEX => 'Amex',
                ]),

            Tables\Filters\TernaryFilter::make('is_active')
                ->label('الحالة')
                ->placeholder('الكل')
                ->trueLabel('نشطة')
                ->falseLabel('غير نشطة'),

            Tables\Filters\TernaryFilter::make('is_default')
                ->label('افتراضية')
                ->placeholder('الكل')
                ->trueLabel('افتراضية')
                ->falseLabel('غير افتراضية'),

            Tables\Filters\TrashedFilter::make()
                ->label('المحذوفة'),
        ];
    }

    // Shared actions
    protected static function getToggleActiveAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('toggle_active')
            ->label(fn (SavedPaymentMethod $record) => $record->is_active ? 'تعطيل' : 'تفعيل')
            ->icon(fn (SavedPaymentMethod $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
            ->color(fn (SavedPaymentMethod $record) => $record->is_active ? 'danger' : 'success')
            ->requiresConfirmation()
            ->action(function (SavedPaymentMethod $record) {
                if ($record->is_active) {
                    $record->deactivate();
                    Notification::make()
                        ->warning()
                        ->title('تم تعطيل طريقة الدفع')
                        ->body('لن يتمكن المستخدم من استخدام هذه الطريقة')
                        ->send();
                } else {
                    $record->activate();
                    Notification::make()
                        ->success()
                        ->title('تم تفعيل طريقة الدفع')
                        ->send();
                }
            });
    }

    protected static function getSetDefaultAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('set_default')
            ->label('تعيين كافتراضية')
            ->icon('heroicon-o-star')
            ->color('warning')
            ->visible(fn (SavedPaymentMethod $record) => ! $record->is_default && $record->is_active)
            ->requiresConfirmation()
            ->action(function (SavedPaymentMethod $record) {
                $record->markAsDefault();
                Notification::make()
                    ->success()
                    ->title('تم التعيين كافتراضية')
                    ->body('تم تعيين طريقة الدفع كافتراضية للمستخدم')
                    ->send();
            });
    }

    // Apply panel-specific scoping
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user', 'academy'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return static::scopeEloquentQuery($query);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns(static::getSharedTableColumns())
            ->filters(static::getSharedFilters())
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions())
            ->defaultSort('created_at', 'desc');
    }
}
