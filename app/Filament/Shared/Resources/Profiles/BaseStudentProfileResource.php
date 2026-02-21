<?php

namespace App\Filament\Shared\Resources\Profiles;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use App\Enums\Gender;
use App\Filament\Concerns\TenantAwareFileUpload;
use App\Filament\Resources\BaseResource;
use App\Helpers\CountryList;
use App\Models\StudentProfile;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Student Profile Resource
 *
 * Shared functionality for Admin and Academy panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseStudentProfileResource extends BaseResource
{
    use TenantAwareFileUpload;

    protected static ?string $model = StudentProfile::class;

    protected static ?string $tenantOwnershipRelationshipName = 'gradeLevel';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'الطلاب';

    protected static string | \UnitEnum | null $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

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

    /**
     * Get grade level options (panel-specific scoping).
     */
    abstract protected static function getGradeLevelOptions(): array;

    // ========================================
    // Academy Relationship Path Override
    // ========================================

    /**
     * Override academy relationship path since students access academy through gradeLevel.
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'gradeLevel.academy';
    }

    // ========================================
    // Shared Form Implementation
    // ========================================

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::getPersonalInfoSection(),
                static::getAcademicInfoSection(),
                static::getContactInfoSection(),
                static::getNotesSection(),
            ]);
    }

    protected static function getPersonalInfoSection(): Section
    {
        return Section::make('المعلومات الشخصية')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->label('الاسم الأول')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->label('الاسم الأخير')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('سيستخدم الطالب هذا البريد للدخول إلى المنصة'),
                        static::getPhoneInput('phone', 'رقم الهاتف')
                            ->helperText('رقم الهاتف مع رمز الدولة'),
                    ]),
                FileUpload::make('avatar')
                    ->label('الصورة الشخصية')
                    ->image()
                    ->imageEditor()
                    ->circleCropper()
                    ->directory(static::getTenantDirectoryLazy('avatars/students'))
                    ->maxSize(2048),
                Grid::make(3)
                    ->schema([
                        DatePicker::make('birth_date')
                            ->label('تاريخ الميلاد'),
                        Select::make('nationality')
                            ->label('الجنسية')
                            ->options(CountryList::toSelectArray())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('gender')
                            ->label('الجنس')
                            ->options(Gender::options()),
                    ]),
            ]);
    }

    protected static function getAcademicInfoSection(): Section
    {
        return Section::make('المعلومات الأكاديمية')
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('grade_level_id')
                            ->label('المرحلة الدراسية')
                            ->options(fn () => static::getGradeLevelOptions())
                            ->required()
                            ->searchable()
                            ->preload(),
                        DatePicker::make('enrollment_date')
                            ->label('تاريخ التسجيل')
                            ->default(now()),
                    ]),
            ]);
    }

    protected static function getContactInfoSection(): Section
    {
        return Section::make('معلومات الاتصال والطوارئ')
            ->schema([
                Textarea::make('address')
                    ->label('العنوان')
                    ->maxLength(500)
                    ->rows(3)
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema([
                        static::getPhoneInput('parent_phone', 'رقم هاتف ولي الأمر')
                            ->required()
                            ->helperText('رقم الهاتف مع رمز الدولة (مطلوب للربط مع حساب ولي الأمر)'),
                        TextInput::make('emergency_contact')
                            ->label('رقم الطوارئ (اختياري)')
                            ->tel()
                            ->maxLength(20),
                    ]),
                Select::make('parent_id')
                    ->label('ولي الأمر')
                    ->relationship('parent', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->parent_code.')')
                    ->searchable(['first_name', 'last_name', 'parent_code', 'email'])
                    ->preload()
                    ->nullable()
                    ->helperText('اختر ولي الأمر المسؤول عن هذا الطالب (أو سيتم الربط تلقائياً عند تسجيل ولي الأمر)'),
            ]);
    }

    protected static function getNotesSection(): Section
    {
        return Section::make('ملاحظات إضافية')
            ->schema([
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->maxLength(1000)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    // ========================================
    // Shared Table Implementation
    // ========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions(static::getTableActions())
            ->toolbarActions(static::getTableBulkActions())
            ->defaultSort('created_at', 'desc');
    }

    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('student_code')
                ->label('رمز الطالب')
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
                ->toggleable(),

            TextColumn::make('gradeLevel.name')
                ->label('المرحلة الدراسية')
                ->sortable()
                ->searchable()
                ->toggleable(),

            TextColumn::make('parent.full_name')
                ->label('ولي الأمر')
                ->searchable()
                ->toggleable(),

            TextColumn::make('phone')
                ->label('الهاتف')
                ->searchable()
                ->toggleable(),

            IconColumn::make('user.active_status')
                ->label('الحالة')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger'),

            TextColumn::make('created_at')
                ->label('تاريخ التسجيل')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('grade_level_id')
                ->label('المرحلة الدراسية')
                ->relationship('gradeLevel', 'name')
                ->searchable()
                ->preload(),

            SelectFilter::make('gender')
                ->label('الجنس')
                ->options(Gender::options()),

            TernaryFilter::make('has_parent')
                ->label('ولي الأمر')
                ->placeholder('الكل')
                ->trueLabel('مرتبط بولي أمر')
                ->falseLabel('غير مرتبط')
                ->queries(
                    true: fn (Builder $query) => $query->whereNotNull('parent_id'),
                    false: fn (Builder $query) => $query->whereNull('parent_id'),
                ),
        ];
    }

    // ========================================
    // Query Scoping
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user', 'parent', 'gradeLevel.academy']);

        return static::scopeEloquentQuery($query);
    }

    // ========================================
    // Helper Methods
    // ========================================

    protected static function getPhoneInput(
        string $name = 'phone',
        string $label = 'رقم الهاتف'
    ): PhoneInput {
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
