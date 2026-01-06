<?php

namespace App\Filament\Supervisor\Resources;

use App\Enums\AttendanceStatus;
use App\Filament\Supervisor\Resources\MonitoredSessionReportsResource\Pages;
use App\Models\StudentSessionReport;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'تقارير الجلسات';

    protected static ?string $modelLabel = 'تقرير جلسة';

    protected static ?string $pluralModelLabel = 'تقارير الجلسات';

    protected static ?string $navigationGroup = 'التقارير';

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
                Tables\Columns\TextColumn::make('session.title')
                    ->label('الجلسة')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('new_memorization_degree')
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

                Tables\Columns\TextColumn::make('reservation_degree')
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

                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('الحضور')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        AttendanceStatus::ATTENDED->value => 'success',
                        AttendanceStatus::LATE->value => 'warning',
                        AttendanceStatus::LEFT->value => 'info',
                        AttendanceStatus::ABSENT->value => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '-';
                        }
                        try {
                            return AttendanceStatus::from($state)->label();
                        } catch (\ValueError $e) {
                            return $state;
                        }
                    }),

                Tables\Columns\TextColumn::make('attendance_percentage')
                    ->label('نسبة الحضور')
                    ->formatStateUsing(fn ($state): string => ($state ?? 0).'%')
                    ->sortable(),

                Tables\Columns\IconColumn::make('manually_evaluated')
                    ->label('معدل يدوياً')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('تاريخ التقييم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(AttendanceStatus::options()),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $teacherIds = static::getAssignedQuranTeacherIds();

                        return \App\Models\User::whereIn('id', $teacherIds)
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('has_evaluation')
                    ->label('تم التقييم')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->whereNotNull('new_memorization_degree')
                            ->orWhereNotNull('reservation_degree');
                    })),

                Tables\Filters\Filter::make('low_score')
                    ->label('درجات منخفضة')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->where('new_memorization_degree', '<', 6)
                            ->orWhere('reservation_degree', '<', 6);
                    })),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
            ])
            ->bulkActions([
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
                Tables\Columns\TextColumn::make('session.title')
                    ->label('الجلسة')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('homework_degree')
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

                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('الحضور')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        AttendanceStatus::ATTENDED->value => 'success',
                        AttendanceStatus::LATE->value => 'warning',
                        AttendanceStatus::LEFT->value => 'info',
                        AttendanceStatus::ABSENT->value => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '-';
                        }
                        try {
                            return AttendanceStatus::from($state)->label();
                        } catch (\ValueError $e) {
                            return $state;
                        }
                    }),

                Tables\Columns\TextColumn::make('attendance_percentage')
                    ->label('نسبة الحضور')
                    ->formatStateUsing(fn ($state): string => ($state ?? 0).'%')
                    ->sortable(),

                Tables\Columns\IconColumn::make('manually_evaluated')
                    ->label('معدل يدوياً')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('تاريخ التقييم')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(AttendanceStatus::options()),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $teacherIds = static::getAssignedAcademicTeacherIds();

                        return \App\Models\User::whereIn('id', $teacherIds)
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('has_homework_grade')
                    ->label('تم تقييم الواجب')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('homework_degree')),

                Tables\Filters\Filter::make('low_score')
                    ->label('درجات منخفضة')
                    ->query(fn (Builder $query): Builder => $query->where('homework_degree', '<', 6)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
            ])
            ->bulkActions([
                // No bulk actions for supervisors
            ]);
    }

    public static function table(Table $table): Table
    {
        // Default to Quran reports table
        return static::getQuranReportsTable($table);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الجلسة')
                    ->schema([
                        Infolists\Components\TextEntry::make('session.title')
                            ->label('عنوان الجلسة'),
                        Infolists\Components\TextEntry::make('student.name')
                            ->label('الطالب'),
                        Infolists\Components\TextEntry::make('teacher.name')
                            ->label('المعلم'),
                        Infolists\Components\TextEntry::make('academy.name')
                            ->label('الأكاديمية'),
                    ])->columns(2),

                Infolists\Components\Section::make('الأداء')
                    ->schema([
                        Infolists\Components\TextEntry::make('new_memorization_degree')
                            ->label('درجة الحفظ الجديد')
                            ->placeholder('غير مقيم'),
                        Infolists\Components\TextEntry::make('reservation_degree')
                            ->label('درجة المراجعة')
                            ->placeholder('غير مقيم'),
                        Infolists\Components\TextEntry::make('homework_degree')
                            ->label('درجة الواجب')
                            ->placeholder('غير مقيم'),
                    ])->columns(3),

                Infolists\Components\Section::make('الحضور')
                    ->schema([
                        Infolists\Components\TextEntry::make('attendance_status')
                            ->label('حالة الحضور')
                            ->badge()
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '-';
                                }
                                try {
                                    return AttendanceStatus::from($state)->label();
                                } catch (\ValueError $e) {
                                    return $state;
                                }
                            }),
                        Infolists\Components\TextEntry::make('attendance_percentage')
                            ->label('نسبة الحضور')
                            ->formatStateUsing(fn ($state) => ($state ?? 0).'%'),
                        Infolists\Components\TextEntry::make('actual_attendance_minutes')
                            ->label('دقائق الحضور')
                            ->formatStateUsing(fn ($state) => ($state ?? 0).' دقيقة'),
                        Infolists\Components\IconEntry::make('is_late')
                            ->label('متأخر')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('late_minutes')
                            ->label('دقائق التأخير')
                            ->formatStateUsing(fn ($state) => ($state ?? 0).' دقيقة')
                            ->visible(fn ($record) => $record->is_late),
                    ])->columns(3),

                Infolists\Components\Section::make('ملاحظات')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('ملاحظات المعلم')
                            ->placeholder('لا توجد ملاحظات')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('معلومات النظام')
                    ->schema([
                        Infolists\Components\IconEntry::make('is_calculated')
                            ->label('محسوب تلقائياً')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('manually_evaluated')
                            ->label('معدل يدوياً')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('override_reason')
                            ->label('سبب التعديل')
                            ->placeholder('لا يوجد')
                            ->visible(fn ($record) => $record->manually_evaluated),
                        Infolists\Components\TextEntry::make('evaluated_at')
                            ->label('تاريخ التقييم')
                            ->dateTime('Y-m-d H:i'),
                        Infolists\Components\TextEntry::make('created_at')
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
            'index' => Pages\ListMonitoredSessionReports::route('/'),
            'view' => Pages\ViewMonitoredSessionReport::route('/{record}'),
        ];
    }
}
