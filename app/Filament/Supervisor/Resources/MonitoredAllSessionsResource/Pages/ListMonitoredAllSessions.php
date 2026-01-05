<?php

namespace App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages;

use App\Enums\SessionStatus;
use App\Enums\AttendanceStatus;
use App\Filament\Shared\Tables\SessionTableColumns;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
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
     * Get the query based on active tab
     */
    protected function getTableQuery(): Builder|null
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

                Tables\Filters\SelectFilter::make('quran_teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $teacherIds = MonitoredAllSessionsResource::getAssignedQuranTeacherIds();
                        return \App\Models\User::whereIn('id', $teacherIds)
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->id => $user->full_name ?? $user->name ?? $user->email]);
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('circle_id')
                    ->label('الحلقة')
                    ->relationship('circle', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
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

                Tables\Filters\SelectFilter::make('academic_teacher_id')
                    ->label('المعلم')
                    ->options(function () {
                        $profileIds = MonitoredAllSessionsResource::getAssignedAcademicTeacherProfileIds();
                        return \App\Models\AcademicTeacherProfile::whereIn('id', $profileIds)
                            ->with('user')
                            ->get()
                            ->mapWithKeys(fn ($profile) => [$profile->id => $profile->user?->name ?? 'غير محدد']);
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
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

                Tables\Filters\SelectFilter::make('course_id')
                    ->label('الدورة')
                    ->options(function () {
                        $courseIds = MonitoredAllSessionsResource::getDerivedInteractiveCourseIds();
                        return \App\Models\InteractiveCourse::whereIn('id', $courseIds)
                            ->pluck('title', 'id');
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
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
