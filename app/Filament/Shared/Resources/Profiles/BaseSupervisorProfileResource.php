<?php

namespace App\Filament\Shared\Resources\Profiles;

use App\Enums\Gender;
use App\Filament\Concerns\TenantAwareFileUpload;
use App\Models\SupervisorProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Supervisor Profile Resource
 *
 * Shared functionality for Admin and Academy panels.
 */
abstract class BaseSupervisorProfileResource extends Resource
{
    use TenantAwareFileUpload;

    protected static ?string $model = SupervisorProfile::class;

    protected static ?string $tenantOwnershipRelationshipName = 'academy';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'المشرفين';

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $modelLabel = 'مشرف';

    protected static ?string $pluralModelLabel = 'المشرفين';

    // ========================================
    // Abstract Methods
    // ========================================

    abstract protected static function scopeEloquentQuery(Builder $query): Builder;
    abstract protected static function getTableActions(): array;
    abstract protected static function getTableBulkActions(): array;

    // ========================================
    // Shared Form
    // ========================================

    public static function form(Form $form): Form
    {
        return $form->schema([
            static::getBasicInfoSection(),
            static::getAccountInfoSection(),
        ]);
    }

    protected static function getBasicInfoSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('المعلومات الأساسية')
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->required()
                        ->unique(table: SupervisorProfile::class, ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\TextInput::make('supervisor_code')
                        ->label('رمز المشرف')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (string $operation): bool => $operation !== 'create'),
                    Forms\Components\TextInput::make('first_name')
                        ->label('الاسم الأول')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('last_name')
                        ->label('اسم العائلة')
                        ->required()
                        ->maxLength(255),
                    static::getPhoneInput()->required(),
                    Forms\Components\Select::make('gender')
                        ->label('الجنس')
                        ->options(Gender::options())
                        ->default(Gender::MALE->value)
                        ->required(),
                ]),
                Forms\Components\FileUpload::make('avatar')
                    ->label('الصورة الشخصية')
                    ->image()
                    ->imageEditor()
                    ->circleCropper()
                    ->directory(static::getTenantDirectoryLazy('avatars/supervisors'))
                    ->maxSize(2048),
            ]);
    }

    protected static function getAccountInfoSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('معلومات الحساب')
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('كلمة المرور')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context): bool => $context === 'create')
                        ->minLength(6)
                        ->maxLength(255)
                        ->rules([\App\Rules\PasswordRules::rule()]),
                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('تأكيد كلمة المرور')
                        ->password()
                        ->revealable()
                        ->dehydrated(false)
                        ->required(fn (string $context, $get): bool => $context === 'create' || filled($get('password')))
                        ->same('password'),
                ]),
                Forms\Components\Toggle::make('user_active_status')
                    ->label('الحساب مفعل')
                    ->default(true)
                    ->afterStateHydrated(function ($component, $record) {
                        if ($record && $record->user) {
                            $component->state($record->user->active_status);
                        }
                    })
                    ->dehydrated(false),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->maxLength(1000),
            ]);
    }

    // ========================================
    // Shared Table
    // ========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions());
    }

    protected static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('supervisor_code')
                ->label('رمز المشرف')
                ->searchable()
                ->sortable()
                ->weight('bold')
                ->copyable(),
            Tables\Columns\TextColumn::make('full_name')
                ->label('الاسم الكامل')
                ->searchable(['first_name', 'last_name'])
                ->sortable(),
            Tables\Columns\TextColumn::make('email')
                ->label('البريد الإلكتروني')
                ->searchable()
                ->sortable()
                ->copyable(),
            Tables\Columns\TextColumn::make('phone')
                ->label('رقم الهاتف')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\IconColumn::make('user.active_status')
                ->label('نشط')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\TrashedFilter::make(),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['academy', 'user']);
        return static::scopeEloquentQuery($query);
    }

    protected static function getPhoneInput(string $name = 'phone', string $label = 'رقم الهاتف'): Forms\Components\Component
    {
        return \Ysfkaya\FilamentPhoneInput\Forms\PhoneInput::make($name)
            ->label($label)
            ->defaultCountry('SA')
            ->initialCountry('sa')
            ->excludeCountries(['il'])
            ->separateDialCode(true)
            ->formatAsYouType(true)
            ->showFlags(true)
            ->strictMode(true)
            ->locale('ar')
            ->i18n(['ps' => 'فلسطين']);
    }
}
