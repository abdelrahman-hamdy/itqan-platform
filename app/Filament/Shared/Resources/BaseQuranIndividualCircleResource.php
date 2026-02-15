<?php

namespace App\Filament\Shared\Resources;

use App\Models\QuranIndividualCircle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Quran Individual Circle Resource
 *
 * Shared functionality for SuperAdmin and Teacher panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseQuranIndividualCircleResource extends Resource
{
    protected static ?string $model = QuranIndividualCircle::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $modelLabel = 'حلقة فردية';

    protected static ?string $pluralModelLabel = 'الحلقات الفردية';

    protected static ?string $recordTitleAttribute = 'name';

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
     * Get the basic info form section (teacher/student selection differs by panel).
     */
    abstract protected static function getBasicInfoFormSection(): Section;

    // ========================================
    // Authorization - Override in child classes
    // ========================================

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    // ========================================
    // Shared Form Definition
    // ========================================

    public static function form(Form $form): Form
    {
        $schema = [];

        // Add basic info section (panel-specific)
        $schema[] = static::getBasicInfoFormSection();

        // Add academic progress section (shared)
        $schema[] = static::getAcademicProgressFormSection();

        // Add progress tracking section (shared)
        $schema[] = static::getProgressTrackingFormSection();

        // Add additional sections from child classes
        $schema = array_merge($schema, static::getAdditionalFormSections());

        return $form->schema($schema);
    }

    /**
     * Academic progress section - shared across panels.
     */
    protected static function getAcademicProgressFormSection(): Section
    {
        return Section::make('التقدم الأكاديمي')
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('specialization')
                            ->label('التخصص')
                            ->options(QuranIndividualCircle::SPECIALIZATIONS)
                            ->default('memorization')
                            ->required(),

                        Select::make('memorization_level')
                            ->label('مستوى الحفظ')
                            ->options(QuranIndividualCircle::MEMORIZATION_LEVELS)
                            ->default('beginner')
                            ->required(),
                    ]),
            ]);
    }

    /**
     * Progress tracking section - shared across panels (read-only).
     */
    protected static function getProgressTrackingFormSection(): Section
    {
        return Section::make('تتبع التقدم')
            ->description('يتم حسابها تلقائياً من واجبات الجلسات')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('total_memorized_pages')
                            ->label('إجمالي الصفحات المحفوظة')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('يتم تحديثه من واجبات الحفظ الجديد'),

                        TextInput::make('total_reviewed_pages')
                            ->label('إجمالي الصفحات المراجعة')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('يتم تحديثه من واجبات المراجعة'),

                        TextInput::make('total_reviewed_surahs')
                            ->label('إجمالي السور المراجعة')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('يتم تحديثه من واجبات المراجعة الشاملة'),
                    ]),
            ])
            ->collapsible()
            ->collapsed();
    }

    /**
     * Get additional form sections - override in child classes.
     * SuperAdmin: Description, Learning Objectives, Notes section
     * Teacher: Session Settings, Teacher Notes
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
            ->defaultSort('created_at', 'desc')
            ->filters(static::getTableFilters())
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions());
    }

    /**
     * Get the table columns - shared across panels.
     * Override in child classes for panel-specific columns.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('student.name')
                ->label('الطالب')
                ->searchable()
                ->sortable()
                ->weight(FontWeight::SemiBold),

            BadgeColumn::make('specialization')
                ->label('التخصص')
                ->formatStateUsing(fn (string $state): string => QuranIndividualCircle::SPECIALIZATIONS[$state] ?? $state)
                ->colors([
                    'success' => 'memorization',
                    'info' => 'recitation',
                    'warning' => 'interpretation',
                    'danger' => 'tajweed',
                    'primary' => 'complete',
                ]),

            TextColumn::make('memorization_level')
                ->label('المستوى')
                ->formatStateUsing(fn (string $state): string => QuranIndividualCircle::MEMORIZATION_LEVELS[$state] ?? $state)
                ->badge()
                ->color('gray'),

            TextColumn::make('sessions_completed')
                ->label('الجلسات المكتملة')
                ->numeric()
                ->sortable()
                ->alignCenter(),

            TextColumn::make('total_memorized_pages')
                ->label('صفحات الحفظ')
                ->numeric()
                ->sortable()
                ->alignCenter(),

            TextColumn::make('total_reviewed_pages')
                ->label('صفحات المراجعة')
                ->numeric()
                ->sortable()
                ->alignCenter()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\IconColumn::make('is_active')
                ->label('الحالة')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger'),

            TextColumn::make('last_session_at')
                ->label('آخر جلسة')
                ->dateTime('Y-m-d')
                ->placeholder('لم تبدأ')
                ->sortable()
                ->toggleable(),

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
            TernaryFilter::make('is_active')
                ->label('الحالة')
                ->trueLabel('نشطة')
                ->falseLabel('غير نشطة')
                ->placeholder('الكل'),

            SelectFilter::make('specialization')
                ->label('التخصص')
                ->options(QuranIndividualCircle::SPECIALIZATIONS),

            SelectFilter::make('memorization_level')
                ->label('مستوى الحفظ')
                ->options(QuranIndividualCircle::MEMORIZATION_LEVELS),
        ];
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'quranTeacher',
                'student',
                'academy',
            ]);

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
