<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\AttendanceStatus;
use App\Filament\Supervisor\Resources\MonitoredSessionReportsResource\Pages\ListMonitoredSessionReports;
use App\Filament\Supervisor\Resources\MonitoredSessionReportsResource\Pages\ViewMonitoredSessionReport;
use App\Models\StudentSessionReport;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ValueError;

/**
 * Monitored Session Reports Resource for Supervisor Panel
 *
 * Unified view of Quran and Academic session reports for supervised teachers.
 * Read-only view - supervisors can monitor but not edit reports.
 */
class MonitoredSessionReportsResource extends BaseSupervisorResource
{
    // Default model for Quran reports (tabs switch between models)
    protected static ?string $model = StudentSessionReport::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'تقارير الجلسات';

    protected static ?string $modelLabel = 'تقرير جلسة';

    protected static ?string $pluralModelLabel = 'تقارير الجلسات';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 1;

    /**
     * Supervisors cannot create reports.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Supervisors cannot edit reports.
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * Supervisors cannot delete reports.
     */
    public static function canDelete($record): bool
    {
        return false;
    }

    /**
     * Get Quran session reports table configuration.
     */
    public static function getQuranReportsTable(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session.title')
                    ->label('الجلسة')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('new_memorization_degree')
                    ->label('درجة الحفظ')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state >= 8 => 'success',
                        (float) $state >= 6 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('reservation_degree')
                    ->label('درجة المراجعة')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state >= 8 => 'success',
                        (float) $state >= 6 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('attendance_status')
                    ->label('الحضور')
                    ->badge()
                    ->color(function (mixed $state): string {
                        if ($state instanceof AttendanceStatus) {
                            return match ($state) {
                                AttendanceStatus::ATTENDED => 'success',
                                AttendanceStatus::LATE => 'warning',
                                AttendanceStatus::LEFT => 'info',
                                AttendanceStatus::ABSENT => 'danger',
                            };
                        }

                        return match ($state) {
                            'attended' => 'success',
                            'late' => 'warning',
                            'left', 'leaved' => 'info',
                            'absent' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (mixed $state): string {
                        if (! $state) {
                            return '-';
                        }
                        if ($state instanceof AttendanceStatus) {
                            return $state->label();
                        }
                        try {
                            return AttendanceStatus::from($state)->label();
                        } catch (ValueError $e) {
                            return (string) $state;
                        }
                    }),

                TextColumn::make('attendance_percentage')
                    ->label('نسبة الحضور')
                    ->formatStateUsing(fn ($state): string => ($state ?? 0).'%')
                    ->sortable(),

                IconColumn::make('manually_evaluated')
                    ->label('معدل يدوياً')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('evaluated_at')
                    ->label('تاريخ التقييم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(AttendanceStatus::options()),

                SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $teacherIds = static::getAssignedQuranTeacherIds();

                        return User::whereIn('id', $teacherIds)
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
                    })
                    ->searchable()
                    ->preload(),

                Filter::make('has_evaluation')
                    ->label('تم التقييم')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->whereNotNull('new_memorization_degree')
                            ->orWhereNotNull('reservation_degree');
                    })),

                Filter::make('low_score')
                    ->label('درجات منخفضة')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->where('new_memorization_degree', '<', 6)
                            ->orWhere('reservation_degree', '<', 6);
                    })),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('عرض'),
                ]),
            ])
            ->toolbarActions([
                // No bulk actions for supervisors
            ]);
    }

    /**
     * Get Academic session reports table configuration.
     */
    public static function getAcademicReportsTable(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session.title')
                    ->label('الجلسة')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('teacher.name')
                    ->label('المعلم')
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
                    }),

                TextColumn::make('attendance_status')
                    ->label('الحضور')
                    ->badge()
                    ->color(function (mixed $state): string {
                        if ($state instanceof AttendanceStatus) {
                            return match ($state) {
                                AttendanceStatus::ATTENDED => 'success',
                                AttendanceStatus::LATE => 'warning',
                                AttendanceStatus::LEFT => 'info',
                                AttendanceStatus::ABSENT => 'danger',
                            };
                        }

                        return match ($state) {
                            'attended' => 'success',
                            'late' => 'warning',
                            'left', 'leaved' => 'info',
                            'absent' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (mixed $state): string {
                        if (! $state) {
                            return '-';
                        }
                        if ($state instanceof AttendanceStatus) {
                            return $state->label();
                        }
                        try {
                            return AttendanceStatus::from($state)->label();
                        } catch (ValueError $e) {
                            return (string) $state;
                        }
                    }),

                TextColumn::make('attendance_percentage')
                    ->label('نسبة الحضور')
                    ->formatStateUsing(fn ($state): string => ($state ?? 0).'%')
                    ->sortable(),

                IconColumn::make('manually_evaluated')
                    ->label('معدل يدوياً')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('evaluated_at')
                    ->label('تاريخ التقييم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(AttendanceStatus::options()),

                SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $teacherIds = static::getAssignedAcademicTeacherIds();

                        return User::whereIn('id', $teacherIds)
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
                    })
                    ->searchable()
                    ->preload(),

                Filter::make('has_homework_grade')
                    ->label('تم تقييم الواجب')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('homework_degree')),

                Filter::make('low_score')
                    ->label('درجات منخفضة')
                    ->query(fn (Builder $query): Builder => $query->where('homework_degree', '<', 6)),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('عرض'),
                ]),
            ])
            ->toolbarActions([
                // No bulk actions for supervisors
            ]);
    }

    public static function table(Table $table): Table
    {
        // Default to Quran reports table
        return static::getQuranReportsTable($table)
            ->deferFilters(false);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الجلسة')
                    ->schema([
                        TextEntry::make('session.title')
                            ->label('عنوان الجلسة'),
                        TextEntry::make('student.name')
                            ->label('الطالب'),
                        TextEntry::make('teacher.name')
                            ->label('المعلم'),
                        TextEntry::make('academy.name')
                            ->label('الأكاديمية'),
                    ])->columns(2),

                Section::make('الأداء')
                    ->schema([
                        TextEntry::make('new_memorization_degree')
                            ->label('درجة الحفظ الجديد')
                            ->placeholder('غير مقيم'),
                        TextEntry::make('reservation_degree')
                            ->label('درجة المراجعة')
                            ->placeholder('غير مقيم'),
                        TextEntry::make('homework_degree')
                            ->label('درجة الواجب')
                            ->placeholder('غير مقيم'),
                    ])->columns(3),

                Section::make('الحضور')
                    ->schema([
                        TextEntry::make('attendance_status')
                            ->label('حالة الحضور')
                            ->badge()
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '-';
                                }
                                try {
                                    return AttendanceStatus::from($state)->label();
                                } catch (ValueError $e) {
                                    return $state;
                                }
                            }),
                        TextEntry::make('attendance_percentage')
                            ->label('نسبة الحضور')
                            ->formatStateUsing(fn ($state) => ($state ?? 0).'%'),
                        TextEntry::make('actual_attendance_minutes')
                            ->label('دقائق الحضور')
                            ->formatStateUsing(fn ($state) => ($state ?? 0).' دقيقة'),
                        IconEntry::make('is_late')
                            ->label('متأخر')
                            ->boolean(),
                        TextEntry::make('late_minutes')
                            ->label('دقائق التأخير')
                            ->formatStateUsing(fn ($state) => ($state ?? 0).' دقيقة')
                            ->visible(fn ($record) => $record->is_late),
                    ])->columns(3),

                Section::make('ملاحظات')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('لا توجد ملاحظات')
                            ->columnSpanFull(),
                    ]),

                Section::make('معلومات النظام')
                    ->schema([
                        IconEntry::make('is_calculated')
                            ->label('محسوب تلقائياً')
                            ->boolean(),
                        IconEntry::make('manually_evaluated')
                            ->label('معدل يدوياً')
                            ->boolean(),
                        TextEntry::make('override_reason')
                            ->label('سبب التعديل')
                            ->placeholder('لا يوجد')
                            ->visible(fn ($record) => $record->manually_evaluated),
                        TextEntry::make('evaluated_at')
                            ->label('تاريخ التقييم')
                            ->dateTime('Y-m-d H:i'),
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('Y-m-d H:i'),
                    ])->columns(3),
            ]);
    }

    /**
     * Only show navigation if supervisor has assigned teachers.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAssignedTeachers();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonitoredSessionReports::route('/'),
            'view' => ViewMonitoredSessionReport::route('/{record}'),
        ];
    }
}
