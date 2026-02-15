<?php

namespace App\Filament\Shared\Resources;

use App\Enums\AttendanceStatus;
use App\Models\StudentSessionReport;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Student Session Report Resource (Quran Sessions)
 *
 * Shared functionality for SuperAdmin and Teacher panels.
 * For Quran session reports with memorization and revision degrees.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseStudentSessionReportResource extends Resource
{
    protected static ?string $model = StudentSessionReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'تقرير جلسة قرآن';

    protected static ?string $pluralModelLabel = 'تقارير جلسات القرآن';

    protected static ?string $navigationLabel = 'تقارير جلسات القرآن';

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
     * Get the session info form section (selection differs by panel).
     */
    abstract protected static function getSessionInfoFormSection(): Section;

    // ========================================
    // Authorization - Override in child classes
    // ========================================

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }

    // ========================================
    // Shared Form Definition
    // ========================================

    public static function form(Form $form): Form
    {
        $schema = [];

        // Add session info section (panel-specific)
        $schema[] = static::getSessionInfoFormSection();

        // Add performance section
        $schema[] = static::getPerformanceFormSection();

        // Add notes section
        $schema[] = static::getNotesFormSection();

        // Add attendance section
        $schema[] = static::getAttendanceFormSection();

        // Add additional sections from child classes
        $schema = array_merge($schema, static::getAdditionalFormSections());

        return $form->schema($schema);
    }

    /**
     * Performance section - Quran memorization and revision degrees.
     */
    protected static function getPerformanceFormSection(): Section
    {
        return Section::make('أداء الحفظ والمراجعة')
            ->schema([
                Forms\Components\TextInput::make('new_memorization_degree')
                    ->label('درجة الحفظ الجديد (0-10)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10)
                    ->step(0.5)
                    ->helperText('تقييم مستوى حفظ الآيات الجديدة'),

                Forms\Components\TextInput::make('reservation_degree')
                    ->label('درجة المراجعة (0-10)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10)
                    ->step(0.5)
                    ->helperText('تقييم مستوى مراجعة المحفوظ'),
            ])->columns(2);
    }

    /**
     * Notes section - shared across panels.
     */
    protected static function getNotesFormSection(): Section
    {
        return Section::make('الملاحظات')
            ->schema([
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات المعلم')
                    ->placeholder('أضف ملاحظات حول أداء الطالب في الجلسة...')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Attendance override section - shared across panels.
     */
    protected static function getAttendanceFormSection(): Section
    {
        return Section::make('تعديل الحضور (إذا لزم الأمر)')
            ->schema([
                Forms\Components\Select::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(AttendanceStatus::options())
                    ->helperText('اختياري - اتركه فارغاً للاحتفاظ بالحالة المحسوبة تلقائياً')
                    ->dehydrated(fn (?string $state): bool => filled($state)),

                Forms\Components\Toggle::make('manually_evaluated')
                    ->label('تم التقييم يدوياً')
                    ->helperText('حدد هذا إذا كنت تقوم بتعديل الحضور التلقائي')
                    ->dehydrated(fn (bool $state): bool => $state === true),

                Forms\Components\Textarea::make('override_reason')
                    ->label('سبب التعديل')
                    ->placeholder('اشرح سبب تعديل الحضور التلقائي...')
                    ->visible(fn (Forms\Get $get) => $get('manually_evaluated'))
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->collapsed()
            ->description('افتح هذا القسم فقط إذا كنت بحاجة إلى تصحيح الحضور يدوياً');
    }

    /**
     * Get additional form sections - override in child classes.
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
            ->filters(static::getTableFilters(), layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions())
            ->emptyStateHeading('لا توجد تقارير')
            ->emptyStateDescription('لم يتم إنشاء أي تقارير جلسات بعد.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    /**
     * Get the table columns - shared across panels.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('session.title')
                ->label('الجلسة')
                ->searchable()
                ->sortable(),

            TextColumn::make('student.name')
                ->label('الطالب')
                ->searchable()
                ->sortable(),

            TextColumn::make('new_memorization_degree')
                ->label('الحفظ الجديد')
                ->numeric()
                ->sortable()
                ->badge()
                ->color(fn (?string $state): string => match (true) {
                    $state === null => 'gray',
                    (float) $state >= 8 => 'success',
                    (float) $state >= 6 => 'warning',
                    default => 'danger',
                })
                ->formatStateUsing(fn (?string $state): string => $state ? $state.'/10' : 'لم يقيم'),

            TextColumn::make('reservation_degree')
                ->label('المراجعة')
                ->numeric()
                ->sortable()
                ->badge()
                ->color(fn (?string $state): string => match (true) {
                    $state === null => 'gray',
                    (float) $state >= 8 => 'success',
                    (float) $state >= 6 => 'warning',
                    default => 'danger',
                })
                ->formatStateUsing(fn (?string $state): string => $state ? $state.'/10' : 'لم يقيم'),

            TextColumn::make('attendance_status')
                ->label('الحضور')
                ->badge()
                ->formatStateUsing(function ($state): string {
                    if (! $state) {
                        return '-';
                    }

                    if ($state instanceof AttendanceStatus) {
                        return $state->label();
                    }

                    return AttendanceStatus::tryFrom($state)?->label() ?? $state;
                })
                ->color(function ($state): string {
                    if ($state instanceof AttendanceStatus) {
                        return $state->color();
                    }

                    return AttendanceStatus::tryFrom($state ?? '')?->color() ?? 'gray';
                }),

            TextColumn::make('attendance_percentage')
                ->label('نسبة الحضور')
                ->numeric()
                ->sortable()
                ->formatStateUsing(fn (?string $state): string => $state ? $state.'%' : '-')
                ->toggleable(),

            TextColumn::make('actual_attendance_minutes')
                ->label('مدة الحضور')
                ->formatStateUsing(fn (?string $state): string => $state ? $state.' دقيقة' : '-')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\IconColumn::make('manually_evaluated')
                ->label('تعديل يدوي')
                ->boolean()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('evaluated_at')
                ->label('تاريخ التقييم')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime()
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
            SelectFilter::make('attendance_status')
                ->label('حالة الحضور')
                ->options(AttendanceStatus::options()),

            SelectFilter::make('evaluation_status')
                ->label('حالة التقييم')
                ->options([
                    'evaluated' => 'تم التقييم',
                    'not_evaluated' => 'لم يتم التقييم',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return match ($data['value'] ?? null) {
                        'evaluated' => $query->where(fn ($q) => $q->whereNotNull('new_memorization_degree')->orWhereNotNull('reservation_degree')),
                        'not_evaluated' => $query->whereNull('new_memorization_degree')->whereNull('reservation_degree'),
                        default => $query,
                    };
                }),

            SelectFilter::make('student_id')
                ->label('الطالب')
                ->relationship('student', 'first_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->first_name ?? 'طالب #'.$record->id)
                ->searchable()
                ->preload(),
        ];
    }

    // ========================================
    // Eloquent Query
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'session',
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
