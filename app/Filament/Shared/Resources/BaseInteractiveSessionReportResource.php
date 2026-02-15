<?php

namespace App\Filament\Shared\Resources;

use App\Enums\AttendanceStatus;
use App\Models\InteractiveSessionReport;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Interactive Session Report Resource
 *
 * Shared functionality for SuperAdmin and AcademicTeacher panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseInteractiveSessionReportResource extends Resource
{
    protected static ?string $model = InteractiveSessionReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'تقرير جلسة تفاعلية';

    protected static ?string $pluralModelLabel = 'تقارير الجلسات التفاعلية';

    protected static ?string $navigationLabel = 'تقارير الدورات التفاعلية';

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
     * Performance section - shared across panels.
     */
    protected static function getPerformanceFormSection(): Section
    {
        return Section::make('تقييم الواجب')
            ->schema([
                Forms\Components\TextInput::make('homework_degree')
                    ->label('درجة الواجب (0-10)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10)
                    ->step(0.5)
                    ->helperText('تقييم جودة وإنجاز الواجب المنزلي'),
            ]);
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
                    ->placeholder('أضف ملاحظات حول أداء الطالب...')
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
                    ->helperText('قم بالتغيير فقط إذا كان الحساب التلقائي غير صحيح')
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
            ->filters(static::getTableFilters())
            ->actions(static::getTableActions())
            ->bulkActions(static::getTableBulkActions())
            ->emptyStateHeading('لا توجد تقارير')
            ->emptyStateDescription('لم يتم إنشاء أي تقارير جلسات بعد.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    /**
     * Get the table columns - shared across panels.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('session.course.name')
                ->label('الدورة')
                ->searchable()
                ->sortable()
                ->limit(25),

            TextColumn::make('session.scheduled_date')
                ->label('تاريخ الجلسة')
                ->date('Y-m-d')
                ->sortable(),

            TextColumn::make('student.name')
                ->label('الطالب')
                ->searchable()
                ->sortable(),

            TextColumn::make('homework_degree')
                ->label('درجة الواجب')
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

            TextColumn::make('actual_attendance_minutes')
                ->label('مدة الحضور')
                ->formatStateUsing(fn (?string $state): string => $state ? $state.' دقيقة' : '-')
                ->sortable()
                ->toggleable(),

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

            Tables\Filters\Filter::make('has_homework_grade')
                ->label('تم تقييم الواجب')
                ->query(fn (Builder $query): Builder => $query->whereNotNull('homework_degree')),

            Tables\Filters\Filter::make('not_graded')
                ->label('بدون تقييم')
                ->query(fn (Builder $query): Builder => $query->whereNull('homework_degree')),
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
                'session.course',
                'student',
            ]);

        return static::scopeEloquentQuery($query);
    }

    public static function getRelations(): array
    {
        return [];
    }
}
