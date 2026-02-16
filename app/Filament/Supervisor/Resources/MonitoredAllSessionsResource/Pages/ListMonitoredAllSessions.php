<?php

namespace App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages;

use App\Enums\SessionStatus;
use App\Filament\Shared\Tables\SessionTableColumns;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Filament\Forms;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListMonitoredAllSessions extends ListRecords
{
    protected static string $resource = MonitoredAllSessionsResource::class;

    /**
     * Build the "Observe Meeting" action for a given session type.
     */
    protected function getObserveAction(string $sessionType): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('observe_meeting')
            ->label(__('supervisor.observation.observe_session'))
            ->icon('heroicon-o-eye')
            ->color('info')
            ->visible(fn ($record): bool => $record->meeting_room_name
                && in_array(
                    $record->status instanceof SessionStatus ? $record->status : SessionStatus::tryFrom($record->status),
                    [SessionStatus::READY, SessionStatus::ONGOING]
                ))
            ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.observe', [
                'record' => $record->id,
                'type' => $sessionType,
            ]))
            ->openUrlInNewTab();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('إضافة جلسة')
                ->url(fn () => route('filament.supervisor.resources.monitored-all-sessions.create')),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [];

        // Quran Sessions tab
        if (MonitoredAllSessionsResource::hasAssignedQuranTeachers()) {
            $quranTeacherIds = MonitoredAllSessionsResource::getAssignedQuranTeacherIds();
            $quranCount = QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)->count();

            $tabs['quran'] = Tab::make('جلسات القرآن')
                ->icon('heroicon-o-book-open')
                ->badge($quranCount)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query);
        }

        // Academic Sessions tab
        if (MonitoredAllSessionsResource::hasAssignedAcademicTeachers()) {
            $profileIds = MonitoredAllSessionsResource::getAssignedAcademicTeacherProfileIds();
            $academicCount = AcademicSession::whereIn('academic_teacher_id', $profileIds)->count();

            $tabs['academic'] = Tab::make('جلسات أكاديمية')
                ->icon('heroicon-o-academic-cap')
                ->badge($academicCount)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query);
        }

        // Interactive Course Sessions tab
        if (MonitoredAllSessionsResource::hasDerivedInteractiveCourses()) {
            $courseIds = MonitoredAllSessionsResource::getDerivedInteractiveCourseIds();
            $interactiveCount = InteractiveCourseSession::whereIn('course_id', $courseIds)->count();

            $tabs['interactive'] = Tab::make('جلسات الدورات')
                ->icon('heroicon-o-video-camera')
                ->badge($interactiveCount)
                ->badgeColor('info')
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
     * Get the query based on active tab, scoped to supervisor's assigned teachers.
     */
    protected function getTableQuery(): ?Builder
    {
        $activeTab = $this->activeTab;

        if ($activeTab === 'academic') {
            $profileIds = MonitoredAllSessionsResource::getAssignedAcademicTeacherProfileIds();

            return AcademicSession::query()
                ->with(['academicTeacher.user', 'academicIndividualLesson.academicSubject', 'student'])
                ->whereIn('academic_teacher_id', $profileIds);
        }

        if ($activeTab === 'interactive') {
            $courseIds = MonitoredAllSessionsResource::getDerivedInteractiveCourseIds();

            return InteractiveCourseSession::query()
                ->with(['course.assignedTeacher.user', 'course.subject'])
                ->whereIn('course_id', $courseIds);
        }

        // Default: Quran Sessions
        $teacherIds = MonitoredAllSessionsResource::getAssignedQuranTeacherIds();

        return QuranSession::query()
            ->with(['quranTeacher', 'circle', 'student', 'individualCircle'])
            ->whereIn('quran_teacher_id', $teacherIds);
    }

    /**
     * Configure table based on active tab
     */
    public function table(Table $table): Table
    {
        $activeTab = $this->activeTab;
        $timezone = AcademyContextService::getTimezone();

        if ($activeTab === 'academic') {
            return $this->getAcademicTable($table, $timezone);
        }

        if ($activeTab === 'interactive') {
            return $this->getInteractiveTable($table, $timezone);
        }

        // Default: Quran Sessions
        return $this->getQuranTable($table, $timezone);
    }

    protected function getQuranTable(Table $table, string $timezone): Table
    {
        return $table
            ->columns(SessionTableColumns::getQuranSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Tables\Filters\SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                    ]),

                Tables\Filters\Filter::make('filter_by')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('filter_type')
                                    ->label('تصفية حسب')
                                    ->options([
                                        'teacher' => 'المعلم',
                                        'student' => 'الطالب',
                                        'group_circle' => 'الحلقة الجماعية',
                                        'individual_circle' => 'الحلقة الفردية',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('filter_value', null)),

                                Forms\Components\Select::make('filter_value')
                                    ->label('القيمة')
                                    ->options(function (Forms\Get $get) {
                                        $teacherIds = MonitoredAllSessionsResource::getAssignedQuranTeacherIds();

                                        return match ($get('filter_type')) {
                                            'teacher' => \App\Models\User::whereIn('id', $teacherIds)
                                                ->get()
                                                ->mapWithKeys(fn ($u) => [
                                                    $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'معلم #'.$u->id,
                                                ])
                                                ->toArray(),
                                            'student' => \App\Models\User::query()
                                                ->where('user_type', 'student')
                                                ->get()
                                                ->mapWithKeys(fn ($u) => [
                                                    $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'طالب #'.$u->id,
                                                ])
                                                ->toArray(),
                                            'group_circle' => \App\Models\QuranCircle::query()
                                                ->pluck('name', 'id')
                                                ->toArray(),
                                            'individual_circle' => \App\Models\QuranIndividualCircle::query()
                                                ->with(['student', 'quranTeacher'])
                                                ->get()
                                                ->mapWithKeys(fn ($ic) => [
                                                    $ic->id => trim(($ic->student?->first_name ?? '').' '.($ic->student?->last_name ?? ''))
                                                        .' - '.trim(($ic->quranTeacher?->first_name ?? '').' '.($ic->quranTeacher?->last_name ?? '')),
                                                ])
                                                ->toArray(),
                                            default => [],
                                        };
                                    })
                                    ->searchable()
                                    ->visible(fn (Forms\Get $get) => filled($get('filter_type'))),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $type = $data['filter_type'] ?? null;
                        $value = $data['filter_value'] ?? null;

                        if (! $type || ! $value) {
                            return $query;
                        }

                        return match ($type) {
                            'teacher' => $query->where('quran_teacher_id', $value),
                            'student' => $query->where('student_id', $value),
                            'group_circle' => $query->where('circle_id', $value),
                            'individual_circle' => $query->where('individual_circle_id', $value),
                            default => $query,
                        };
                    })
                    ->columnSpan(2),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('from')
                                    ->label('من تاريخ'),
                                Forms\Components\DatePicker::make('until')
                                    ->label('إلى تاريخ'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('scheduled_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('scheduled_at', '<=', $date));
                    })
                    ->columnSpan(2),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                $this->getObserveAction('quran'),
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.view', [
                        'record' => $record->id,
                        'type' => 'quran',
                    ])),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.edit', [
                        'record' => $record->id,
                        'type' => 'quran',
                    ])),
                Tables\Actions\Action::make('add_note')
                    ->label('ملاحظة')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->default(fn ($record) => $record->supervisor_notes),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'supervisor_notes' => $data['supervisor_notes'],
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    protected function getAcademicTable(Table $table, string $timezone): Table
    {
        return $table
            ->columns(SessionTableColumns::getAcademicSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Tables\Filters\SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(array_merge(
                        [SessionStatus::SCHEDULED->value => SessionStatus::SCHEDULED->label()],
                        \App\Enums\AttendanceStatus::options()
                    )),

                Tables\Filters\Filter::make('filter_by')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('filter_type')
                                    ->label('تصفية حسب')
                                    ->options([
                                        'teacher' => 'المعلم',
                                        'student' => 'الطالب',
                                        'individual_lesson' => 'الدرس الفردي',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('filter_value', null)),

                                Forms\Components\Select::make('filter_value')
                                    ->label('القيمة')
                                    ->options(function (Forms\Get $get) {
                                        $profileIds = MonitoredAllSessionsResource::getAssignedAcademicTeacherProfileIds();

                                        return match ($get('filter_type')) {
                                            'teacher' => \App\Models\AcademicTeacherProfile::whereIn('id', $profileIds)
                                                ->with('user')
                                                ->get()
                                                ->mapWithKeys(fn ($profile) => [
                                                    $profile->id => $profile->user
                                                        ? trim(($profile->user->first_name ?? '').' '.($profile->user->last_name ?? '')) ?: 'معلم #'.$profile->id
                                                        : 'معلم #'.$profile->id,
                                                ])
                                                ->toArray(),
                                            'student' => \App\Models\User::query()
                                                ->where('user_type', 'student')
                                                ->get()
                                                ->mapWithKeys(fn ($u) => [
                                                    $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'طالب #'.$u->id,
                                                ])
                                                ->toArray(),
                                            'individual_lesson' => \App\Models\AcademicIndividualLesson::query()
                                                ->with(['student', 'academicTeacher.user'])
                                                ->get()
                                                ->mapWithKeys(fn ($lesson) => [
                                                    $lesson->id => ($lesson->name ?? 'درس #'.$lesson->id)
                                                        .' - '.trim(($lesson->student?->first_name ?? '').' '.($lesson->student?->last_name ?? '')),
                                                ])
                                                ->toArray(),
                                            default => [],
                                        };
                                    })
                                    ->searchable()
                                    ->visible(fn (Forms\Get $get) => filled($get('filter_type'))),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $type = $data['filter_type'] ?? null;
                        $value = $data['filter_value'] ?? null;

                        if (! $type || ! $value) {
                            return $query;
                        }

                        return match ($type) {
                            'teacher' => $query->where('academic_teacher_id', $value),
                            'student' => $query->where('student_id', $value),
                            'individual_lesson' => $query->where('academic_individual_lesson_id', $value),
                            default => $query,
                        };
                    })
                    ->columnSpan(2),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('from')
                                    ->label('من تاريخ'),
                                Forms\Components\DatePicker::make('until')
                                    ->label('إلى تاريخ'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('scheduled_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('scheduled_at', '<=', $date));
                    })
                    ->columnSpan(2),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                $this->getObserveAction('academic'),
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.view', [
                        'record' => $record->id,
                        'type' => 'academic',
                    ])),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.edit', [
                        'record' => $record->id,
                        'type' => 'academic',
                    ])),
                Tables\Actions\Action::make('add_note')
                    ->label('ملاحظة')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->default(fn ($record) => $record->supervisor_notes),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'supervisor_notes' => $data['supervisor_notes'],
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    protected function getInteractiveTable(Table $table, string $timezone): Table
    {
        return $table
            ->columns(SessionTableColumns::getInteractiveCourseSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Tables\Filters\Filter::make('filter_by')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('filter_type')
                                    ->label('تصفية حسب')
                                    ->options([
                                        'course' => 'الدورة',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('filter_value', null)),

                                Forms\Components\Select::make('filter_value')
                                    ->label('القيمة')
                                    ->options(function (Forms\Get $get) {
                                        $courseIds = MonitoredAllSessionsResource::getDerivedInteractiveCourseIds();

                                        return match ($get('filter_type')) {
                                            'course' => \App\Models\InteractiveCourse::whereIn('id', $courseIds)
                                                ->pluck('title', 'id')
                                                ->toArray(),
                                            default => [],
                                        };
                                    })
                                    ->searchable()
                                    ->visible(fn (Forms\Get $get) => filled($get('filter_type'))),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $type = $data['filter_type'] ?? null;
                        $value = $data['filter_value'] ?? null;

                        if (! $type || ! $value) {
                            return $query;
                        }

                        return match ($type) {
                            'course' => $query->where('course_id', $value),
                            default => $query,
                        };
                    })
                    ->columnSpan(2),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('from')
                                    ->label('من تاريخ'),
                                Forms\Components\DatePicker::make('until')
                                    ->label('إلى تاريخ'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('scheduled_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('scheduled_at', '<=', $date));
                    })
                    ->columnSpan(2),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                $this->getObserveAction('interactive'),
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.view', [
                        'record' => $record->id,
                        'type' => 'interactive',
                    ])),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.edit', [
                        'record' => $record->id,
                        'type' => 'interactive',
                    ])),
                Tables\Actions\Action::make('add_note')
                    ->label('ملاحظة')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('supervisor_notes')
                            ->label('ملاحظات المشرف')
                            ->rows(4)
                            ->default(fn ($record) => $record->supervisor_notes),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'supervisor_notes' => $data['supervisor_notes'],
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
