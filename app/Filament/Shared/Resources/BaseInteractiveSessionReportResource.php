<?php

namespace App\Filament\Shared\Resources;

use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use App\Enums\AttendanceStatus;
use App\Models\InteractiveSessionReport;
use Filament\Forms;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Interactive Session Report Resource
 *
 * Shared functionality for SuperAdmin and AcademicTeacher panels.
 * Child classes must implement query scoping and authorization methods.
 */
abstract class BaseInteractiveSessionReportResource extends BaseResource
{
    protected static ?string $model = InteractiveSessionReport::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

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

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return true;
    }

    // ========================================
    // Shared Form Definition
    // ========================================

    public static function form(Schema $form): Schema
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

        return $form->components($schema);
    }

    /**
     * Performance section - shared across panels.
     */
    protected static function getPerformanceFormSection(): Section
    {
        return Section::make('تقييم الواجب')
            ->schema([
                TextInput::make('homework_degree')
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
                Textarea::make('notes')
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
                Select::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(AttendanceStatus::options())
                    ->helperText('قم بالتغيير فقط إذا كان الحساب التلقائي غير صحيح')
                    ->dehydrated(fn (?string $state): bool => filled($state)),

                Toggle::make('manually_evaluated')
                    ->label('تم التقييم يدوياً')
                    ->helperText('حدد هذا إذا كنت تقوم بتعديل الحضور التلقائي')
                    ->dehydrated(fn (bool $state): bool => $state === true),

                Textarea::make('override_reason')
                    ->label('سبب التعديل')
                    ->placeholder('اشرح سبب تعديل الحضور التلقائي...')
                    ->visible(fn (Get $get) => $get('manually_evaluated'))
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
            ->filters(static::getTableFilters(), layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->deferColumnManager(false)
            ->recordActions(static::getTableActions())
            ->toolbarActions(static::getTableBulkActions())
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
                ->sortable()
                ->toggleable(),

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
                ->formatStateUsing(fn (?string $state): string => $state ? $state.'/10' : 'لم يقيم')
                ->toggleable(),

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
                })
                ->toggleable(),

            TextColumn::make('actual_attendance_minutes')
                ->label('مدة الحضور')
                ->formatStateUsing(fn (?string $state): string => $state ? $state.' دقيقة' : '-')
                ->sortable()
                ->toggleable(),

            IconColumn::make('manually_evaluated')
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
                        'evaluated' => $query->whereNotNull('homework_degree'),
                        'not_evaluated' => $query->whereNull('homework_degree'),
                        default => $query,
                    };
                }),

            SelectFilter::make('student_id')
                ->label('الطالب')
                ->relationship('student', 'first_name', fn (Builder $query) => $query->where('user_type', 'student'))
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
