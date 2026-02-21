<?php

namespace App\Filament\Shared\Resources\Profiles;

use App\Enums\UserType;
use App\Filament\Concerns\TenantAwareFileUpload;
use App\Models\ParentProfile;
use App\Models\User;
use App\Services\AcademyContextService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

/**
 * Base Parent Profile Resource
 *
 * Shared functionality for Admin and Academy panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseParentProfileResource extends Resource
{
    use TenantAwareFileUpload;

    protected static ?string $model = ParentProfile::class;

    protected static ?string $tenantOwnershipRelationshipName = 'user';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'أولياء الأمور';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $modelLabel = 'ولي أمر';

    protected static ?string $pluralModelLabel = 'أولياء الأمور';

    // ========================================
    // Abstract Methods - Panel-specific implementation
    // ========================================

    /**
     * Apply panel-specific query scoping.
     */
    abstract protected static function scopeEloquentQuery(Builder $query): Builder;

    /**
     * Get panel-specific table actions.
     */
    abstract protected static function getTableActions(): array;

    /**
     * Get panel-specific bulk actions.
     */
    abstract protected static function getTableBulkActions(): array;

    // ========================================
    // Shared Form Implementation
    // ========================================

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::getPersonalInfoSection(),
                static::getAdditionalInfoSection(),
                static::getContactInfoSection(),
                static::getAccountStatusSection(),
            ]);
    }

    protected static function getPersonalInfoSection(): Section
    {
        return Section::make('المعلومات الشخصية')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->rule(function ($livewire) {
                                return function (string $attribute, $value, Closure $fail) use ($livewire) {
                                    // Get current academy from tenant context
                                    $academyId = Filament::getTenant()?->id;

                                    // Check if email exists in parent_profiles table for this academy
                                    $parentProfileQuery = ParentProfile::where('email', $value)
                                        ->where('academy_id', $academyId);

                                    if ($livewire->record ?? null) {
                                        $parentProfileQuery->where('id', '!=', $livewire->record->id);
                                    }

                                    if ($parentProfileQuery->exists()) {
                                        $fail('البريد الإلكتروني مستخدم بالفعل في هذه الأكاديمية.');

                                        return;
                                    }

                                    // Check if email exists in users table for this academy
                                    $userQuery = User::where('email', $value)
                                        ->where('academy_id', $academyId);

                                    if ($livewire->record ?? null) {
                                        $userQuery->where('id', '!=', $livewire->record->user_id);
                                    }

                                    if ($userQuery->exists()) {
                                        $fail('البريد الإلكتروني مستخدم بالفعل في هذه الأكاديمية.');
                                    }
                                };
                            })
                            ->maxLength(255)
                            ->helperText('سيستخدم ولي الأمر هذا البريد للدخول إلى المنصة'),
                        TextInput::make('first_name')
                            ->label('الاسم الأول')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->label('اسم العائلة')
                            ->required()
                            ->maxLength(255),
                        static::getPhoneInput()
                            ->required(),
                    ]),
                FileUpload::make('avatar')
                    ->label('الصورة الشخصية')
                    ->image()
                    ->imageEditor()
                    ->circleCropper()
                    ->directory(static::getTenantDirectoryLazy('avatars/parents'))
                    ->maxSize(2048),
            ]);
    }

    protected static function getAdditionalInfoSection(): Section
    {
        return Section::make('معلومات إضافية')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('parent_code')
                            ->label('رمز ولي الأمر')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('occupation')
                            ->label('المهنة')
                            ->maxLength(255),
                    ]),
                Textarea::make('address')
                    ->label('العنوان')
                    ->rows(3)
                    ->maxLength(500),
            ]);
    }

    protected static function getContactInfoSection(): Section
    {
        return Section::make('معلومات الاتصال')
            ->schema([
                Grid::make(2)
                    ->schema([
                        static::getPhoneInput('secondary_phone', 'رقم هاتف ثانوي'),
                        Select::make('preferred_contact_method')
                            ->label('طريقة الاتصال المفضلة')
                            ->options([
                                'phone' => 'هاتف',
                                'email' => 'بريد إلكتروني',
                                'sms' => 'رسالة نصية',
                                'whatsapp' => 'واتساب',
                            ])
                            ->default('phone'),
                    ]),
                Textarea::make('admin_notes')
                    ->label('ملاحظات الإدارة')
                    ->rows(3)
                    ->maxLength(1000)
                    ->helperText('ملاحظات خاصة بالإدارة فقط')
                    ->visible(fn () => auth()->user()?->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value])),
            ]);
    }

    protected static function getAccountStatusSection(): Section
    {
        return Section::make('حالة الحساب')
            ->schema([
                Toggle::make('user_active_status')
                    ->label('الحساب نشط')
                    ->helperText('عند تعطيل الحساب، لن يتمكن ولي الأمر من تسجيل الدخول')
                    ->default(true)
                    ->afterStateHydrated(function ($component, $record) {
                        if ($record && $record->user) {
                            $component->state($record->user->active_status);
                        }
                    })
                    ->dehydrated(false),
            ])
            ->visible(fn () => auth()->user()?->hasRole([UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::SUPERVISOR->value]));
    }

    // ========================================
    // Shared Table Implementation
    // ========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions(static::getTableActions())
            ->toolbarActions(static::getTableBulkActions());
    }

    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('parent_code')
                ->label('رمز ولي الأمر')
                ->searchable()
                ->sortable()
                ->weight('bold')
                ->copyable(),

            TextColumn::make('full_name')
                ->label('الاسم الكامل')
                ->searchable(['first_name', 'last_name'])
                ->sortable(),

            TextColumn::make('email')
                ->label('البريد الإلكتروني')
                ->searchable()
                ->sortable()
                ->copyable()
                ->toggleable(),

            TextColumn::make('phone')
                ->label('رقم الهاتف')
                ->searchable()
                ->copyable()
                ->toggleable(),

            IconColumn::make('has_students')
                ->label('مرتبط بطلاب')
                ->boolean()
                ->getStateUsing(fn ($record) => $record->students()->exists())
                ->toggleable(),

            IconColumn::make('user.active_status')
                ->label('نشط')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger')
                ->sortable()
                ->toggleable(),

            TextColumn::make('created_at')
                ->label(__('filament.created_at'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            TernaryFilter::make('has_students')
                ->label('مرتبط بطلاب')
                ->placeholder('جميع أولياء الأمور')
                ->trueLabel('لديه طلاب')
                ->falseLabel('بدون طلاب')
                ->queries(
                    true: fn (Builder $query) => $query->has('students'),
                    false: fn (Builder $query) => $query->doesntHave('students'),
                ),

            TernaryFilter::make('active_status')
                ->label('حالة الحساب')
                ->placeholder('جميع الحسابات')
                ->trueLabel('نشط')
                ->falseLabel('غير نشط')
                ->queries(
                    true: fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('active_status', 1)),
                    false: fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where(fn ($inner) => $inner->where('active_status', 0)->orWhereNull('active_status'))),
                ),
        ];
    }

    // ========================================
    // Shared Actions
    // ========================================

    protected static function getToggleActiveAction(): Action
    {
        return Action::make('toggleActive')
            ->label(fn ($record) => $record->user?->active_status ? 'تعطيل' : 'تفعيل')
            ->icon(fn ($record) => $record->user?->active_status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
            ->color(fn ($record) => $record->user?->active_status ? 'danger' : 'success')
            ->requiresConfirmation()
            ->modalHeading(fn ($record) => $record->user?->active_status ? 'تعطيل الحساب' : 'تفعيل الحساب')
            ->modalDescription(fn ($record) => $record->user?->active_status
                ? 'هل أنت متأكد من تعطيل حساب ولي الأمر؟ لن يتمكن من تسجيل الدخول.'
                : 'هل أنت متأكد من تفعيل حساب ولي الأمر؟')
            ->action(function ($record) {
                if ($record->user) {
                    $record->user->update(['active_status' => ! $record->user->active_status]);
                }
            });
    }

    protected static function getActivateBulkAction(): BulkAction
    {
        return BulkAction::make('activate')
            ->label('تفعيل المحددين')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->action(fn ($records) => $records->each(fn ($record) => $record->user?->update(['active_status' => true])));
    }

    protected static function getDeactivateBulkAction(): BulkAction
    {
        return BulkAction::make('deactivate')
            ->label('تعطيل المحددين')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->action(fn ($records) => $records->each(fn ($record) => $record->user?->update(['active_status' => false])));
    }

    // ========================================
    // Query Scoping
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['academy', 'user', 'students']);

        return static::scopeEloquentQuery($query);
    }

    // ========================================
    // Academy Context Methods
    // ========================================

    protected static function isViewingAllAcademies(): bool
    {
        if (Filament::getTenant() !== null) {
            return false;
        }

        $academyContextService = app(AcademyContextService::class);

        return $academyContextService->getCurrentAcademyId() === null;
    }

    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    protected static function getAcademyColumn(): TextColumn
    {
        $academyPath = static::getAcademyRelationshipPath();

        return TextColumn::make($academyPath.'.name')
            ->label('الأكاديمية')
            ->sortable()
            ->searchable()
            ->visible(static::isViewingAllAcademies())
            ->placeholder('غير محدد');
    }

    // ========================================
    // Helper Methods
    // ========================================

    protected static function getPhoneInput(string $name = 'phone', string $label = 'رقم الهاتف'): PhoneInput
    {
        return PhoneInput::make($name)
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
