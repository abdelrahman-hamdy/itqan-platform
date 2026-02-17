<?php

namespace App\Filament\Supervisor\Resources\MonitoredSessionReportsResource\Pages;

use App\Enums\AttendanceStatus;
use App\Filament\Supervisor\Resources\MonitoredSessionReportsResource;
use App\Models\AcademicSessionReport;
use App\Models\StudentSessionReport;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListMonitoredSessionReports extends ListRecords
{
    protected static string $resource = MonitoredSessionReportsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - supervisors view only
        ];
    }

    public function getTabs(): array
    {
        $tabs = [];

        // Quran Reports tab
        if (MonitoredSessionReportsResource::hasAssignedQuranTeachers()) {
            $quranTeacherIds = MonitoredSessionReportsResource::getAssignedQuranTeacherIds();
            $quranCount = StudentSessionReport::whereIn('teacher_id', $quranTeacherIds)->count();

            $tabs['quran'] = Tab::make('تقارير القرآن')
                ->icon('heroicon-o-book-open')
                ->badge($quranCount)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query);
        }

        // Academic Reports tab
        if (MonitoredSessionReportsResource::hasAssignedAcademicTeachers()) {
            $academicTeacherIds = MonitoredSessionReportsResource::getAssignedAcademicTeacherIds();
            $academicCount = AcademicSessionReport::whereIn('teacher_id', $academicTeacherIds)->count();

            $tabs['academic'] = Tab::make('تقارير أكاديمية')
                ->icon('heroicon-o-academic-cap')
                ->badge($academicCount)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query);
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        $tabs = $this->getTabs();

        return array_key_first($tabs);
    }

    /**
     * Get the query based on active tab.
     */
    protected function getTableQuery(): ?Builder
    {
        $activeTab = $this->activeTab;

        if ($activeTab === 'academic') {
            $teacherIds = MonitoredSessionReportsResource::getAssignedAcademicTeacherIds();

            return AcademicSessionReport::query()
                ->with(['session', 'student', 'teacher', 'academy'])
                ->whereIn('teacher_id', $teacherIds);
        }

        // Default: Quran Reports
        $teacherIds = MonitoredSessionReportsResource::getAssignedQuranTeacherIds();

        return StudentSessionReport::query()
            ->with(['session', 'student', 'teacher', 'academy'])
            ->whereIn('teacher_id', $teacherIds);
    }

    /**
     * Configure table based on active tab.
     */
    public function table(Table $table): Table
    {
        $activeTab = $this->activeTab;

        if ($activeTab === 'academic') {
            return $this->getAcademicTable($table);
        }

        // Default: Quran Reports
        return $this->getQuranTable($table);
    }

    protected function getQuranTable(Table $table): Table
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
                    ->label('الحفظ')
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
                    ->label('المراجعة')
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
                    ->color(function (mixed $state): string {
                        $value = $state instanceof \BackedEnum ? $state->value : (string) $state;

                        return match ($value) {
                            AttendanceStatus::ATTENDED->value => 'success',
                            AttendanceStatus::LATE->value => 'warning',
                            AttendanceStatus::LEFT->value => 'info',
                            AttendanceStatus::ABSENT->value => 'danger',
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
                            return AttendanceStatus::from((string) $state)->label();
                        } catch (\ValueError $e) {
                            return (string) $state;
                        }
                    }),

                Tables\Columns\TextColumn::make('attendance_percentage')
                    ->label('النسبة')
                    ->formatStateUsing(fn ($state): string => ($state ?? 0).'%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
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
                        $teacherIds = MonitoredSessionReportsResource::getAssignedQuranTeacherIds();

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
                    ->label('عرض')
                    ->url(fn ($record): string => route('filament.supervisor.resources.monitored-session-reports.view', [
                        'record' => $record->id,
                        'type' => 'quran',
                    ])),
            ])
            ->bulkActions([]);
    }

    protected function getAcademicTable(Table $table): Table
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
                    ->label('الواجب')
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
                    ->color(function (mixed $state): string {
                        $value = $state instanceof \BackedEnum ? $state->value : (string) $state;

                        return match ($value) {
                            AttendanceStatus::ATTENDED->value => 'success',
                            AttendanceStatus::LATE->value => 'warning',
                            AttendanceStatus::LEFT->value => 'info',
                            AttendanceStatus::ABSENT->value => 'danger',
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
                            return AttendanceStatus::from((string) $state)->label();
                        } catch (\ValueError $e) {
                            return (string) $state;
                        }
                    }),

                Tables\Columns\TextColumn::make('attendance_percentage')
                    ->label('النسبة')
                    ->formatStateUsing(fn ($state): string => ($state ?? 0).'%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
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
                        $teacherIds = MonitoredSessionReportsResource::getAssignedAcademicTeacherIds();

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
                    ->label('عرض')
                    ->url(fn ($record): string => route('filament.supervisor.resources.monitored-session-reports.view', [
                        'record' => $record->id,
                        'type' => 'academic',
                    ])),
            ])
            ->bulkActions([]);
    }
}
