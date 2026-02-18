<?php

namespace App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Filament\Shared\Tables\SessionTableColumns;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\AcademyContextService;
use App\Filament\Shared\Actions\MeetingActions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListMonitoredAllSessions extends ListRecords
{
    protected static string $resource = MonitoredAllSessionsResource::class;

    /**
     * Build the "View Meeting" action for a given session type.
     */
    protected function getObserveAction(string $sessionType): Action
    {
        return MeetingActions::viewMeeting($sessionType);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
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
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                SelectFilter::make('session_type')
                    ->label('نوع الجلسة')
                    ->options([
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                    ]),

                SelectFilter::make('individual_circle_id')
                    ->label('الحلقة الفردية')
                    ->options(fn () => QuranIndividualCircle::query()
                        ->with(['student', 'quranTeacher'])
                        ->get()
                        ->mapWithKeys(fn ($ic) => [
                            $ic->id => trim(($ic->student?->first_name ?? '').' '.($ic->student?->last_name ?? ''))
                                .' - '.trim(($ic->quranTeacher?->first_name ?? '').' '.($ic->quranTeacher?->last_name ?? '')),
                        ])
                    )
                    ->searchable(),

                SelectFilter::make('circle_id')
                    ->label('الحلقة الجماعية')
                    ->options(fn () => QuranCircle::query()->pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->options(fn () => User::whereIn('id', MonitoredAllSessionsResource::getAssignedQuranTeacherIds())
                        ->get()
                        ->mapWithKeys(fn ($u) => [
                            $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'معلم #'.$u->id,
                        ])
                    )
                    ->searchable(),

                SelectFilter::make('student_id')
                    ->label('الطالب')
                    ->options(fn () => User::query()
                        ->where('user_type', 'student')
                        ->get()
                        ->mapWithKeys(fn ($u) => [
                            $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'طالب #'.$u->id,
                        ])
                    )
                    ->searchable(),

                Filter::make('date_range')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('from')
                                    ->label('من تاريخ'),
                                DatePicker::make('until')
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
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('عرض')
                        ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.view', [
                            'record' => $record->id,
                            'type' => 'quran',
                        ])),
                    $this->getObserveAction('quran'),
                    EditAction::make()
                        ->label('تعديل')
                        ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.edit', [
                            'record' => $record->id,
                            'type' => 'quran',
                        ])),
                    Action::make('add_note')
                        ->label('ملاحظة')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->schema([
                            Textarea::make('supervisor_notes')
                                ->label('ملاحظات المشرف')
                                ->rows(4)
                                ->default(fn ($record) => $record->supervisor_notes),
                        ])
                        ->action(function ($record, array $data): void {
                            $record->update([
                                'supervisor_notes' => $data['supervisor_notes'],
                            ]);
                        }),
                    DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    protected function getAcademicTable(Table $table, string $timezone): Table
    {
        return $table
            ->columns(SessionTableColumns::getAcademicSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                SelectFilter::make('attendance_status')
                    ->label('حالة الحضور')
                    ->options(array_merge(
                        [SessionStatus::SCHEDULED->value => SessionStatus::SCHEDULED->label()],
                        AttendanceStatus::options()
                    )),

                SelectFilter::make('academic_teacher_id')
                    ->label('المعلم')
                    ->options(fn () => AcademicTeacherProfile::whereIn('id', MonitoredAllSessionsResource::getAssignedAcademicTeacherProfileIds())
                        ->with('user')
                        ->get()
                        ->mapWithKeys(fn ($profile) => [
                            $profile->id => $profile->user
                                ? trim(($profile->user->first_name ?? '').' '.($profile->user->last_name ?? '')) ?: 'معلم #'.$profile->id
                                : 'معلم #'.$profile->id,
                        ])
                    )
                    ->searchable(),

                SelectFilter::make('student_id')
                    ->label('الطالب')
                    ->options(fn () => User::query()
                        ->where('user_type', 'student')
                        ->get()
                        ->mapWithKeys(fn ($u) => [
                            $u->id => trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: 'طالب #'.$u->id,
                        ])
                    )
                    ->searchable(),

                SelectFilter::make('academic_individual_lesson_id')
                    ->label('الدرس الفردي')
                    ->options(fn () => AcademicIndividualLesson::query()
                        ->with(['student', 'academicTeacher.user'])
                        ->get()
                        ->mapWithKeys(fn ($lesson) => [
                            $lesson->id => ($lesson->name ?? 'درس #'.$lesson->id)
                                .' - '.trim(($lesson->student?->first_name ?? '').' '.($lesson->student?->last_name ?? '')),
                        ])
                    )
                    ->searchable(),

                Filter::make('date_range')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('from')
                                    ->label('من تاريخ'),
                                DatePicker::make('until')
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
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('عرض')
                        ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.view', [
                            'record' => $record->id,
                            'type' => 'academic',
                        ])),
                    $this->getObserveAction('academic'),
                    EditAction::make()
                        ->label('تعديل')
                        ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.edit', [
                            'record' => $record->id,
                            'type' => 'academic',
                        ])),
                    Action::make('add_note')
                        ->label('ملاحظة')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->schema([
                            Textarea::make('supervisor_notes')
                                ->label('ملاحظات المشرف')
                                ->rows(4)
                                ->default(fn ($record) => $record->supervisor_notes),
                        ])
                        ->action(function ($record, array $data): void {
                            $record->update([
                                'supervisor_notes' => $data['supervisor_notes'],
                            ]);
                        }),
                    DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    protected function getInteractiveTable(Table $table, string $timezone): Table
    {
        return $table
            ->columns(SessionTableColumns::getInteractiveCourseSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Filter::make('filter_by')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('filter_type')
                                    ->label('تصفية حسب')
                                    ->options([
                                        'course' => 'الدورة',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('filter_value', null)),

                                Select::make('filter_value')
                                    ->label('القيمة')
                                    ->options(function (Get $get) {
                                        $courseIds = MonitoredAllSessionsResource::getDerivedInteractiveCourseIds();

                                        return match ($get('filter_type')) {
                                            'course' => InteractiveCourse::whereIn('id', $courseIds)
                                                ->pluck('title', 'id')
                                                ->toArray(),
                                            default => [],
                                        };
                                    })
                                    ->searchable()
                                    ->visible(fn (Get $get) => filled($get('filter_type'))),
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

                Filter::make('date_range')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('from')
                                    ->label('من تاريخ'),
                                DatePicker::make('until')
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
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('عرض')
                        ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.view', [
                            'record' => $record->id,
                            'type' => 'interactive',
                        ])),
                    $this->getObserveAction('interactive'),
                    EditAction::make()
                        ->label('تعديل')
                        ->url(fn ($record): string => route('filament.supervisor.resources.monitored-all-sessions.edit', [
                            'record' => $record->id,
                            'type' => 'interactive',
                        ])),
                    Action::make('add_note')
                        ->label('ملاحظة')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->schema([
                            Textarea::make('supervisor_notes')
                                ->label('ملاحظات المشرف')
                                ->rows(4)
                                ->default(fn ($record) => $record->supervisor_notes),
                        ])
                        ->action(function ($record, array $data): void {
                            $record->update([
                                'supervisor_notes' => $data['supervisor_notes'],
                            ]);
                        }),
                    DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
