<?php

namespace App\Filament\Shared\Resources;

use App\Enums\DifficultyLevel;
use App\Enums\WeekDays;
use App\Models\QuranCircle;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Quran Circle Resource
 *
 * Shared functionality for SuperAdmin and Teacher panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseQuranCircleResource extends Resource
{
    protected static ?string $model = QuranCircle::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'حلقات القرآن الجماعية';

    protected static ?string $modelLabel = 'حلقة قرآن جماعية';

    protected static ?string $pluralModelLabel = 'حلقات القرآن الجماعية';

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
     * Get the teacher field for the form.
     * SuperAdmin: Select with academy teachers
     * Teacher: Hidden field with auto-assignment
     */
    abstract protected static function getTeacherFormField(): Forms\Components\Component;

    /**
     * Get description field(s) for the form.
     * SuperAdmin: Single description field
     * Teacher: Bilingual fields (description_ar, description_en)
     */
    abstract protected static function getDescriptionFormFields(): array;

    // ========================================
    // Authorization - Override in child classes
    // ========================================

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    // ========================================
    // Shared Form Definition
    // ========================================

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات الحلقة الأساسية')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('اسم الحلقة')
                                    ->required()
                                    ->maxLength(150),

                                TextInput::make('circle_code')
                                    ->label('رمز الحلقة')
                                    ->disabled()
                                    ->dehydrated(false),

                                Select::make('age_group')
                                    ->label('الفئة العمرية')
                                    ->options(static::getAgeGroupOptions())
                                    ->required(),

                                Select::make('gender_type')
                                    ->label('النوع')
                                    ->options(static::getGenderTypeOptions())
                                    ->required(),

                                Select::make('specialization')
                                    ->label('التخصص')
                                    ->options(static::getSpecializationOptions())
                                    ->default('memorization')
                                    ->required(),

                                Select::make('memorization_level')
                                    ->label('مستوى الحفظ')
                                    ->options(static::getMemorizationLevelOptions())
                                    ->default('beginner')
                                    ->required(),
                            ]),

                        ...static::getDescriptionFormFields(),

                        TagsInput::make('learning_objectives')
                            ->label('أهداف الحلقة')
                            ->placeholder('أضف هدفاً من أهداف الحلقة')
                            ->helperText('أهداف تعليمية واضحة ومحددة للحلقة')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Section::make('إعدادات الحلقة')
                    ->schema([
                        static::getTeacherFormField(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('max_students')
                                    ->label('الحد الأقصى للطلاب')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(20)
                                    ->default(8)
                                    ->required(),

                                TextInput::make('enrolled_students')
                                    ->label('عدد الطلاب الحالي')
                                    ->numeric()
                                    ->disabled()
                                    ->default(fn ($record) => $record ? $record->students()->count() : 0)
                                    ->dehydrated(false),

                                TextInput::make('monthly_fee')
                                    ->label('الرسوم الشهرية')
                                    ->numeric()
                                    ->prefix(getCurrencyCode())
                                    ->minValue(0)
                                    ->required()
                                    ->helperText('هذه الرسوم للطلاب المشتركين في الحلقة'),

                                Select::make('monthly_sessions_count')
                                    ->label('عدد الجلسات الشهرية')
                                    ->options(static::getMonthlySessionsOptions())
                                    ->default(8)
                                    ->required()
                                    ->helperText('يحدد هذا الرقم عدد الجلسات التي يمكن للمعلم جدولتها شهرياً'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('schedule_days')
                                    ->label('أيام الانعقاد')
                                    ->options(WeekDays::options())
                                    ->multiple()
                                    ->native(false)
                                    ->helperText('أيام انعقاد الحلقة - للمعلومات العامة'),

                                Select::make('schedule_time')
                                    ->label('الساعة')
                                    ->options(static::getScheduleTimeOptions())
                                    ->native(false)
                                    ->searchable()
                                    ->helperText('وقت الحلقة - للمعلومات العامة'),
                            ]),
                    ]),

                ...static::getAdditionalFormSections(),
            ]);
    }

    /**
     * Get additional form sections - override in child classes.
     * SuperAdmin adds: Progress tracking, enrollment status, supervisor notes
     * Teacher adds: Simple status section with disabled admin_notes
     */
    protected static function getAdditionalFormSections(): array
    {
        return [
            Section::make('الحالة والإعدادات الإدارية')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Toggle::make('status')
                                ->label('حالة الحلقة')
                                ->helperText('تفعيل أو إلغاء تفعيل الحلقة')
                                ->default(true),
                        ]),
                ]),
        ];
    }

    // ========================================
    // Shared Table Definition
    // ========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->defaultSort('created_at', 'desc')
            ->filters(static::getTableFilters())
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions());
    }

    /**
     * Get the table columns - shared across panels.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('circle_code')
                ->label('رمز الحلقة')
                ->searchable()
                ->fontFamily('mono')
                ->weight(FontWeight::Bold),

            TextColumn::make('name')
                ->label('اسم الحلقة')
                ->searchable()
                ->limit(30),

            BadgeColumn::make('memorization_level')
                ->label('المستوى')
                ->formatStateUsing(fn (string $state): string => static::formatMemorizationLevel($state)),

            BadgeColumn::make('age_group')
                ->label('الفئة العمرية')
                ->formatStateUsing(fn (string $state): string => static::formatAgeGroup($state)),

            BadgeColumn::make('gender_type')
                ->label('النوع')
                ->formatStateUsing(fn (string $state): string => static::formatGenderType($state))
                ->colors([
                    'info' => 'male',
                    'success' => 'female',
                    'warning' => 'mixed',
                ]),

            TextColumn::make('students_count')
                ->label('المسجلون')
                ->alignCenter()
                ->color('info')
                ->sortable(),

            TextColumn::make('max_students')
                ->label('الحد الأقصى')
                ->alignCenter(),

            TextColumn::make('schedule_days')
                ->label('أيام الانعقاد')
                ->formatStateUsing(function ($state) {
                    if (! is_array($state) || empty($state)) {
                        return 'غير محدد';
                    }

                    return WeekDays::getDisplayNames($state);
                })
                ->wrap()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('schedule_time')
                ->label('الساعة')
                ->placeholder('غير محدد')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('schedule_status')
                ->label('حالة الجدولة')
                ->getStateUsing(fn ($record) => $record->schedule ? 'مُجدولة' : 'غير مُجدولة')
                ->badge()
                ->color(fn ($state) => $state === 'مُجدولة' ? 'success' : 'warning')
                ->toggleable(),

            TextColumn::make('monthly_fee')
                ->label('الرسوم الشهرية')
                ->money(fn ($record) => $record->academy?->currency?->value ?? config('currencies.default', 'SAR'))
                ->toggleable(),

            BadgeColumn::make('status')
                ->label(__('filament.status'))
                ->formatStateUsing(fn (bool $state): string => $state
                    ? __('enums.circle_active_status.active')
                    : __('enums.circle_active_status.inactive'))
                ->colors([
                    'success' => true,
                    'danger' => false,
                ]),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime('Y-m-d')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Get the table filters - shared across panels.
     */
    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label(__('filament.status'))
                ->options([
                    '1' => __('enums.circle_active_status.active'),
                    '0' => __('enums.circle_active_status.inactive'),
                ]),

            SelectFilter::make('memorization_level')
                ->label('المستوى')
                ->options(static::getMemorizationLevelOptions()),

            SelectFilter::make('age_group')
                ->label(__('filament.circle.age_group'))
                ->options([
                    'children' => __('enums.age_group.children'),
                    'youth' => __('enums.age_group.youth'),
                    'adults' => __('enums.age_group.adults'),
                    'all_ages' => __('enums.age_group.mixed'),
                ]),

            SelectFilter::make('gender_type')
                ->label(__('filament.gender_type'))
                ->options([
                    'male' => __('enums.gender_type.male'),
                    'female' => __('enums.gender_type.female'),
                    'mixed' => __('enums.gender_type.mixed'),
                ]),

            Filter::make('available_spots')
                ->label(__('filament.circle.available_spots'))
                ->query(fn (Builder $query): Builder => $query->whereRaw(
                    '(SELECT COUNT(*) FROM quran_circle_students WHERE circle_id = quran_circles.id) < max_students'
                )),
        ];
    }

    // ========================================
    // Options Helper Methods
    // ========================================

    protected static function getAgeGroupOptions(): array
    {
        return [
            'children' => 'أطفال (5-12 سنة)',
            'youth' => 'شباب (13-17 سنة)',
            'adults' => 'بالغون (18+ سنة)',
            'all_ages' => 'كل الفئات',
        ];
    }

    protected static function getGenderTypeOptions(): array
    {
        return [
            'male' => 'رجال',
            'female' => 'نساء',
            'mixed' => 'مختلط',
        ];
    }

    protected static function getSpecializationOptions(): array
    {
        return [
            'memorization' => 'حفظ القرآن',
            'recitation' => 'تلاوة وتجويد',
            'interpretation' => 'تفسير',
            'arabic_language' => 'اللغة العربية',
            'complete' => 'شامل',
        ];
    }

    protected static function getMemorizationLevelOptions(): array
    {
        return DifficultyLevel::options();
    }

    protected static function getMonthlySessionsOptions(): array
    {
        return [
            4 => '4 جلسات (جلسة واحدة أسبوعياً)',
            8 => '8 جلسات (جلستين أسبوعياً)',
            12 => '12 جلسة (3 جلسات أسبوعياً)',
            16 => '16 جلسة (4 جلسات أسبوعياً)',
            20 => '20 جلسة (5 جلسات أسبوعياً)',
        ];
    }

    protected static function getScheduleTimeOptions(): array
    {
        return [
            '00:00' => '12:00 ص',
            '01:00' => '01:00 ص',
            '02:00' => '02:00 ص',
            '03:00' => '03:00 ص',
            '04:00' => '04:00 ص',
            '05:00' => '05:00 ص',
            '06:00' => '06:00 ص',
            '07:00' => '07:00 ص',
            '08:00' => '08:00 ص',
            '09:00' => '09:00 ص',
            '10:00' => '10:00 ص',
            '11:00' => '11:00 ص',
            '12:00' => '12:00 م',
            '13:00' => '01:00 م',
            '14:00' => '02:00 م',
            '15:00' => '03:00 م',
            '16:00' => '04:00 م',
            '17:00' => '05:00 م',
            '18:00' => '06:00 م',
            '19:00' => '07:00 م',
            '20:00' => '08:00 م',
            '21:00' => '09:00 م',
            '22:00' => '10:00 م',
            '23:00' => '11:00 م',
        ];
    }

    // ========================================
    // Formatting Helper Methods
    // ========================================

    protected static function formatMemorizationLevel(string $state): string
    {
        return match ($state) {
            'beginner' => 'مبتدئ',
            'intermediate' => 'متوسط',
            'advanced' => 'متقدم',
            default => $state,
        };
    }

    protected static function formatAgeGroup(string $state): string
    {
        return match ($state) {
            'children' => 'أطفال',
            'youth' => 'شباب',
            'adults' => 'كبار',
            'all_ages' => 'كل الفئات',
            default => $state,
        };
    }

    protected static function formatGenderType(string $state): string
    {
        return match ($state) {
            'male' => 'رجال',
            'female' => 'نساء',
            'mixed' => 'مختلط',
            default => $state,
        };
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['academy', 'quranTeacher'])
            ->withCount('students');

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
