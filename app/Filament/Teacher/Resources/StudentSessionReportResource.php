<?php

namespace App\Filament\Teacher\Resources;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use App\Filament\Teacher\Resources\StudentSessionReportResource\Pages\ListStudentSessionReports;
use App\Filament\Teacher\Resources\StudentSessionReportResource\Pages\ViewStudentSessionReport;
use App\Filament\Teacher\Resources\StudentSessionReportResource\Pages\EditStudentSessionReport;
use App\Enums\AttendanceStatus;
use App\Filament\Shared\Resources\BaseStudentSessionReportResource;
use App\Filament\Teacher\Resources\StudentSessionReportResource\Pages;
use App\Models\StudentSessionReport;
use App\Services\StudentReportService;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Student Session Report Resource for Teacher Panel (Quran Sessions)
 *
 * Teachers can view and manage reports for their own Quran sessions.
 * Extends BaseStudentSessionReportResource for shared form/table definitions.
 */
class StudentSessionReportResource extends BaseStudentSessionReportResource
{
    // ========================================
    // Navigation Configuration
    // ========================================

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'تقارير الطلاب';

    protected static ?string $modelLabel = 'تقرير طالب';

    protected static ?string $pluralModelLabel = 'تقارير الطلاب';

    protected static string | \UnitEnum | null $navigationGroup = 'التقارير والتقييمات';

    protected static ?int $navigationSort = 1;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    /**
     * Filter reports to current teacher's sessions only.
     */
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $teacher = Auth::user();

        if (! $teacher->isQuranTeacher() && ! $teacher->isAcademicTeacher()) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        return $query->with(['session.circle', 'session.individualCircle'])
            ->whereHas('session', function (Builder $q) use ($teacher) {
                $q->where('quran_teacher_id', $teacher->id);
            });
    }

    /**
     * Simplified session info section for teachers (read-only context).
     */
    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة')
            ->schema([
                Placeholder::make('student_info')
                    ->label('الطالب')
                    ->content(fn (?StudentSessionReport $record) => $record?->student?->name ?? '-'),

                Placeholder::make('session_info')
                    ->label('الجلسة')
                    ->content(fn (?StudentSessionReport $record) => $record?->session?->title ?? '-'),
            ])->columns(2);
    }

    /**
     * Teacher-specific table actions with update evaluation modal.
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),

                Action::make('update_evaluation')
                    ->label('تحديث التقييم')
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('new_memorization_degree')
                                    ->label('درجة الحفظ الجديد (0-10)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(10)
                                    ->step(0.5),

                                TextInput::make('reservation_degree')
                                    ->label('درجة المراجعة (0-10)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(10)
                                    ->step(0.5),
                            ]),

                        Select::make('attendance_status')
                            ->label('حالة الحضور')
                            ->options([
                                AttendanceStatus::ATTENDED->value => 'حاضر',
                                AttendanceStatus::LATE->value => 'متأخر',
                                AttendanceStatus::LEFT->value => 'غادر مبكراً',
                                AttendanceStatus::ABSENT->value => 'غائب',
                            ])
                            ->helperText('اختياري - اتركه فارغاً للاحتفاظ بالحالة المحسوبة تلقائياً')
                            ->dehydrated(fn (?string $state): bool => filled($state)),

                        Textarea::make('notes')
                            ->label('ملاحظات التقييم')
                            ->rows(3),
                    ])
                    ->action(function (StudentSessionReport $record, array $data): void {
                        $studentReportService = app(StudentReportService::class);

                        $studentReportService->updateTeacherEvaluation(
                            $record,
                            $data['new_memorization_degree'] ?? 0,
                            $data['reservation_degree'] ?? 0,
                            $data['notes'] ?? null
                        );

                        // Update attendance status if provided
                        if (! empty($data['attendance_status'])) {
                            $record->update([
                                'attendance_status' => $data['attendance_status'],
                                'manually_evaluated' => true,
                            ]);
                        }

                        Notification::make()
                            ->title('تم تحديث التقييم بنجاح')
                            ->success()
                            ->send();
                    })
                    ->fillForm(fn (StudentSessionReport $record): array => [
                        'new_memorization_degree' => $record->new_memorization_degree,
                        'reservation_degree' => $record->reservation_degree,
                        'attendance_status' => $record->manually_evaluated ? $record->attendance_status : '',
                        'notes' => $record->notes,
                    ]),
            ]),
        ];
    }

    /**
     * Minimal bulk actions for teachers.
     */
    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                // Add bulk actions if needed
            ]),
        ];
    }

    // ========================================
    // Table Columns Override (Teacher-specific)
    // ========================================

    /**
     * Teacher-specific table columns extending the base set.
     * Adds session date and session type; uses base-standard badge/color/enum handling.
     */
    protected static function getTableColumns(): array
    {
        return [
            TextColumn::make('student.name')
                ->label('اسم الطالب')
                ->searchable()
                ->sortable(),

            TextColumn::make('session.scheduled_at')
                ->label('تاريخ الجلسة')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(),

            TextColumn::make('session.session_type')
                ->label('نوع الجلسة')
                ->formatStateUsing(fn (mixed $state): string => match ((string) $state) {
                    'individual' => 'فردية',
                    'group' => 'جماعية',
                    default => (string) $state,
                })
                ->badge()
                ->color(fn (mixed $state): string => match ((string) $state) {
                    'individual' => 'info',
                    'group' => 'success',
                    default => 'gray',
                })
                ->toggleable(),

            // Use base-standard enum handling via tryFrom to avoid manual match/switch
            TextColumn::make('attendance_status')
                ->label('الحضور')
                ->badge()
                ->toggleable()
                ->formatStateUsing(function (mixed $state): string {
                    if ($state instanceof AttendanceStatus) {
                        return $state->label();
                    }

                    return AttendanceStatus::tryFrom($state ?? '')?->label() ?? (string) $state;
                })
                ->color(function (mixed $state): string {
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

            // Base-standard color-coded badges for memorization scores
            TextColumn::make('new_memorization_degree')
                ->label('الحفظ الجديد')
                ->numeric()
                ->sortable()
                ->badge()
                ->toggleable()
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
                ->toggleable()
                ->color(fn (?string $state): string => match (true) {
                    $state === null => 'gray',
                    (float) $state >= 8 => 'success',
                    (float) $state >= 6 => 'warning',
                    default => 'danger',
                })
                ->formatStateUsing(fn (?string $state): string => $state ? $state.'/10' : 'لم يقيم'),

            IconColumn::make('manually_evaluated')
                ->label('تعديل يدوي')
                ->boolean()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('evaluated_at')
                ->label('تاريخ التقييم')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // ========================================
    // Table Filters Override (Teacher-specific)
    // ========================================

    /**
     * Base filters (attendance_status, evaluation_status, student_id) plus
     * teacher-specific additions: session_type and date_range.
     */
    protected static function getTableFilters(): array
    {
        return [
            // From base
            SelectFilter::make('attendance_status')
                ->label('حالة الحضور')
                ->options(AttendanceStatus::options()),

            // From base — useful for finding reports still needing evaluation
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

            // From base — filter by specific student
            SelectFilter::make('student_id')
                ->label('الطالب')
                ->relationship('student', 'first_name', fn (Builder $query) => $query->where('user_type', 'student'))
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->first_name ?? 'طالب #'.$record->id)
                ->searchable()
                ->preload(),

            // Teacher-specific — filter by session type (فردية / جماعية)
            SelectFilter::make('session_type')
                ->label('نوع الجلسة')
                ->options([
                    'individual' => 'فردية',
                    'group' => 'جماعية',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (! $data['value']) {
                        return $query;
                    }

                    return $query->whereHas('session', function (Builder $subQuery) use ($data) {
                        $subQuery->where('session_type', $data['value']);
                    });
                }),

            // Teacher-specific — date range on session scheduled_at
            Filter::make('date_range')
                ->schema([
                    DatePicker::make('from')
                        ->label('من تاريخ'),
                    DatePicker::make('until')
                        ->label('إلى تاريخ'),
                ])
                ->columns(2)
                ->columnSpan(2)
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['from'],
                            fn (Builder $query, $date): Builder => $query->whereHas('session', function (Builder $subQuery) use ($date) {
                                $subQuery->whereDate('scheduled_at', '>=', $date);
                            }),
                        )
                        ->when(
                            $data['until'],
                            fn (Builder $query, $date): Builder => $query->whereHas('session', function (Builder $subQuery) use ($date) {
                                $subQuery->whereDate('scheduled_at', '<=', $date);
                            }),
                        );
                }),
        ];
    }

    // ========================================
    // Table Configuration Override
    // ========================================

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->deferFilters(false)
            ->defaultSort('session.scheduled_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    // ========================================
    // Authorization Overrides
    // ========================================

    public static function canCreate(): bool
    {
        return false; // Teachers don't create reports directly
    }

    public static function canEdit(Model $record): bool
    {
        $teacher = Auth::user();

        return $record->session?->quran_teacher_id === $teacher->id;
    }

    public static function canView(Model $record): bool
    {
        $teacher = Auth::user();

        return $record->session?->quran_teacher_id === $teacher->id;
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Teachers cannot delete reports
    }

    public static function canAccess(): bool
    {
        return Auth::check() && (Auth::user()->isQuranTeacher() || Auth::user()->isAcademicTeacher());
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListStudentSessionReports::route('/'),
            'view' => ViewStudentSessionReport::route('/{record}'),
            'edit' => EditStudentSessionReport::route('/{record}/edit'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return static::$pluralModelLabel ?? 'تقارير الطلاب';
    }
}
