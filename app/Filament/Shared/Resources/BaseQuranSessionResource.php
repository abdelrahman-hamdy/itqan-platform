<?php

namespace App\Filament\Shared\Resources;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Enums\FiltersLayout;
use App\Enums\QuranSurah;
use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use App\Filament\Resources\BaseResource;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Quran Session Resource
 *
 * Shared functionality for SuperAdmin and Teacher panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseQuranSessionResource extends BaseResource
{
    protected static ?string $model = QuranSession::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $modelLabel = 'جلسة قرآن';

    protected static ?string $pluralModelLabel = 'جلسات القرآن';

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
     * Get additional form sections (Teacher/Circle selection for SuperAdmin, simpler for Teacher).
     */
    abstract protected static function getTeacherCircleFormSection(): ?Section;

    // ========================================
    // Authorization - Override in child classes
    // ========================================

    public static function canCreate(): bool
    {
        return false; // Deny by default — child panel resources must explicitly enable creation
    }

    public static function canEdit(Model $record): bool
    {
        return false; // Deny by default — child panel resources must explicitly enable editing
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    // ========================================
    // Shared Form Definition
    // ========================================

    public static function form(Schema $form): Schema
    {
        $schema = [];

        // Add session info section
        $schema[] = static::getSessionInfoSection();

        // Add teacher/circle section if provided (SuperAdmin only)
        $teacherCircleSection = static::getTeacherCircleFormSection();
        if ($teacherCircleSection) {
            $schema[] = $teacherCircleSection;
        }

        // Add timing section
        $schema[] = static::getTimingFormSection();

        // Add content section
        $schema[] = static::getContentFormSection();

        // Add homework section
        $schema[] = static::getHomeworkFormSection();

        // Add additional sections from child classes
        $schema = array_merge($schema, static::getAdditionalFormSections());

        return $form->components($schema);
    }

    /**
     * Session info section - shared across panels.
     */
    protected static function getSessionInfoSection(): Section
    {
        return Section::make('معلومات الجلسة')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('session_code')
                            ->label('رمز الجلسة')
                            ->disabled(),

                        Select::make('session_type')
                            ->label('نوع الجلسة')
                            ->options(static::getSessionTypeOptions())
                            ->disabled()
                            ->dehydrated()
                            ->helperText('نوع الجلسة يُحدد تلقائياً'),

                        Select::make('status')
                            ->label('الحالة')
                            ->options(SessionStatus::options())
                            ->default(SessionStatus::SCHEDULED->value)
                            ->required(),
                    ]),
            ]);
    }

    /**
     * Timing section - shared across panels.
     */
    protected static function getTimingFormSection(): Section
    {
        return Section::make('التوقيت')
            ->schema([
                Grid::make(2)
                    ->schema([
                        DateTimePicker::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->timezone(AcademyContextService::getTimezone())
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('Y-m-d H:i')
                            ->helperText(function () {
                                $tz = AcademyContextService::getTimezone();
                                $label = match($tz) {
                                    'Asia/Riyadh' => 'توقيت السعودية (GMT+3)',
                                    'Africa/Cairo' => 'توقيت مصر (GMT+2)',
                                    'Asia/Dubai' => 'توقيت الإمارات (GMT+4)',
                                    default => $tz,
                                };
                                return "⏰ الأوقات بـ {$label}";
                            })
                            ->required(),

                        Select::make('duration_minutes')
                            ->label('مدة الجلسة')
                            ->options(SessionDuration::options())
                            ->default(60)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('المدة محددة بناءً على باقة القرآن'),
                    ]),
            ]);
    }

    /**
     * Content section - shared across panels.
     */
    protected static function getContentFormSection(): Section
    {
        return Section::make('محتوى الجلسة')
            ->schema([
                TextInput::make('title')
                    ->label('عنوان الجلسة')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        Textarea::make('description')
                            ->label('وصف الجلسة')
                            ->helperText('أهداف ومحتوى الجلسة')
                            ->rows(3),

                        Textarea::make('lesson_content')
                            ->label('محتوى الدرس')
                            ->rows(4),
                    ]),
            ]);
    }

    /**
     * Homework section - shared across panels.
     * Uses nested relationship fields for sessionHomework.
     */
    protected static function getHomeworkFormSection(): Section
    {
        return Section::make('الواجب المنزلي')
            ->schema([
                // New Memorization Section
                Toggle::make('sessionHomework.has_new_memorization')
                    ->label('حفظ جديد')
                    ->live()
                    ->default(false),

                Grid::make(2)
                    ->schema([
                        Select::make('sessionHomework.new_memorization_surah')
                            ->label('سورة الحفظ الجديد')
                            ->options(QuranSurah::getAllSurahs())
                            ->searchable()
                            ->visible(fn ($get) => $get('sessionHomework.has_new_memorization')),

                        TextInput::make('sessionHomework.new_memorization_pages')
                            ->label('عدد الأوجه')
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0.5)
                            ->maxValue(10)
                            ->suffix('وجه')
                            ->visible(fn ($get) => $get('sessionHomework.has_new_memorization')),
                    ])
                    ->visible(fn ($get) => $get('sessionHomework.has_new_memorization')),

                // Review Section
                Toggle::make('sessionHomework.has_review')
                    ->label('مراجعة')
                    ->live()
                    ->default(false),

                Grid::make(2)
                    ->schema([
                        Select::make('sessionHomework.review_surah')
                            ->label('سورة المراجعة')
                            ->options(QuranSurah::getAllSurahs())
                            ->searchable()
                            ->visible(fn ($get) => $get('sessionHomework.has_review')),

                        TextInput::make('sessionHomework.review_pages')
                            ->label('عدد أوجه المراجعة')
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0.5)
                            ->maxValue(20)
                            ->suffix('وجه')
                            ->visible(fn ($get) => $get('sessionHomework.has_review')),
                    ])
                    ->visible(fn ($get) => $get('sessionHomework.has_review')),

                // Comprehensive Review Section
                Toggle::make('sessionHomework.has_comprehensive_review')
                    ->label('مراجعة شاملة')
                    ->live()
                    ->default(false),

                CheckboxList::make('sessionHomework.comprehensive_review_surahs')
                    ->label('سور المراجعة الشاملة')
                    ->options(QuranSurah::getAllSurahs())
                    ->searchable()
                    ->columns(3)
                    ->visible(fn ($get) => $get('sessionHomework.has_comprehensive_review')),

                Textarea::make('sessionHomework.additional_instructions')
                    ->label('تعليمات إضافية')
                    ->rows(3)
                    ->placeholder('أي تعليمات أو ملاحظات إضافية للطلاب'),
            ]);
    }

    /**
     * Get additional form sections - override in child classes.
     * SuperAdmin adds: Notes section with supervisor_notes
     * Teacher: No additional sections
     */
    protected static function getAdditionalFormSections(): array
    {
        return [];
    }

    // ========================================
    // Shared Table Definition
    // ========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters(static::getTableFilters())
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions(static::getTableActions())
            ->toolbarActions(static::getTableBulkActions());
    }

    /**
     * Get the table columns - shared across panels.
     * Override in child classes for panel-specific columns.
     */
    protected static function getTableColumns(): array
    {
        return [
            static::getAcademyColumn(),

            TextColumn::make('session_code')
                ->label('رمز الجلسة')
                ->searchable()
                ->sortable(),

            TextColumn::make('title')
                ->label('عنوان الجلسة')
                ->searchable()
                ->limit(30)
                ->toggleable(),

            TextColumn::make('session_type')
                ->badge()
                ->label('نوع الجلسة')
                ->formatStateUsing(fn (string $state): string => static::formatSessionType($state))
                ->colors([
                    'primary' => 'individual',
                    'success' => 'group',
                    'warning' => 'trial',
                ])
                ->toggleable(),

            TextColumn::make('scheduled_at')
                ->label('موعد الجلسة')
                ->dateTime('Y-m-d H:i')
                ->timezone(AcademyContextService::getTimezone())
                ->sortable()
                ->toggleable(),

            TextColumn::make('duration_minutes')
                ->label('المدة')
                ->suffix(' دقيقة')
                ->sortable()
                ->toggleable(),

            TextColumn::make('status')
                ->badge()
                ->label('الحالة')
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof SessionStatus) {
                        return $state->label();
                    }
                    $status = SessionStatus::tryFrom($state);

                    return $status?->label() ?? $state;
                })
                ->colors(SessionStatus::colorOptions()),

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
            SelectFilter::make('session_type')
                ->label('نوع الجلسة')
                ->options(static::getSessionTypeOptions()),

            SelectFilter::make('status')
                ->label('الحالة')
                ->options(SessionStatus::options()),

            Filter::make('today')
                ->label('جلسات اليوم')
                ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

            Filter::make('this_week')
                ->label('جلسات هذا الأسبوع')
                ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])),
        ];
    }

    // ========================================
    // Options Helper Methods
    // ========================================

    protected static function getSessionTypeOptions(): array
    {
        return [
            'individual' => 'فردية',
            'group' => 'جماعية',
            'trial' => 'تجريبية',
        ];
    }

    // ========================================
    // Formatting Helper Methods
    // ========================================

    protected static function formatSessionType(string $state): string
    {
        return match ($state) {
            'individual' => 'فردية',
            'group' => 'جماعية',
            'trial' => 'تجريبية',
            default => $state,
        };
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'quranTeacher',
                'circle',
                'student',
                'individualCircle',
                'academy',
                'sessionHomework',
            ]);

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
