<?php

namespace App\Filament\Pages;

use App\Enums\SessionStatus;
use App\Filament\Shared\Tables\SessionTableColumns;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * Live Sessions monitoring page for SuperAdmin panel.
 * Shows all sessions in the current academy with tabs and observe capabilities.
 */
class LiveSessionsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-eye';

    protected static ?string $navigationLabel = 'مراقبة الجلسات';

    protected static ?string $title = 'مراقبة الجلسات المباشرة';

    protected static ?string $slug = 'live-sessions';

    protected static ?int $navigationSort = -1;

    protected static string $view = 'filament.pages.live-sessions';

    #[Url]
    public string $activeTab = 'quran';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function switchTab(string $tab): void
    {
        if ($this->activeTab === $tab) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        $academyId = AcademyContextService::getCurrentAcademyId();

        return match ($this->activeTab) {
            'academic' => $this->buildAcademicTable($table, $academyId),
            'interactive' => $this->buildInteractiveTable($table, $academyId),
            default => $this->buildQuranTable($table, $academyId),
        };
    }

    protected function buildQuranTable(Table $table, ?int $academyId): Table
    {
        $query = QuranSession::query()
            ->with(['quranTeacher', 'circle', 'student', 'individualCircle']);

        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        return $table
            ->query($query)
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

                Tables\Filters\Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),

                Tables\Filters\Filter::make('has_meeting')
                    ->label('لديها اجتماع نشط')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('meeting_room_name')
                        ->whereIn('status', [SessionStatus::READY->value, SessionStatus::ONGOING->value])),
            ])
            ->actions([
                $this->getObserveAction('quran'),
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->url(fn ($record): string => \App\Filament\Resources\QuranSessionResource::getUrl('view', ['record' => $record])),
            ]);
    }

    protected function buildAcademicTable(Table $table, ?int $academyId): Table
    {
        $query = AcademicSession::query()
            ->with(['academicTeacher.user', 'academicIndividualLesson.academicSubject', 'student']);

        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        return $table
            ->query($query)
            ->columns(SessionTableColumns::getAcademicSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Tables\Filters\Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),

                Tables\Filters\Filter::make('has_meeting')
                    ->label('لديها اجتماع نشط')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('meeting_room_name')
                        ->whereIn('status', [SessionStatus::READY->value, SessionStatus::ONGOING->value])),
            ])
            ->actions([
                $this->getObserveAction('academic'),
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->url(fn ($record): string => \App\Filament\Resources\AcademicSessionResource::getUrl('view', ['record' => $record])),
            ]);
    }

    protected function buildInteractiveTable(Table $table, ?int $academyId): Table
    {
        $query = InteractiveCourseSession::query()
            ->with(['course.assignedTeacher.user', 'course.subject']);

        if ($academyId) {
            $query->whereHas('course', fn ($q) => $q->where('academy_id', $academyId));
        }

        return $table
            ->query($query)
            ->columns(SessionTableColumns::getInteractiveCourseSessionColumns())
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Tables\Filters\Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),

                Tables\Filters\Filter::make('has_meeting')
                    ->label('لديها اجتماع نشط')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('meeting_room_name')
                        ->whereIn('status', [SessionStatus::READY->value, SessionStatus::ONGOING->value])),
            ])
            ->actions([
                $this->getObserveAction('interactive'),
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->url(fn ($record): string => \App\Filament\Resources\InteractiveCourseSessionResource::getUrl('view', ['record' => $record])),
            ]);
    }

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
            ->url(fn ($record): string => ObserveSessionPage::getUrl().'?'.http_build_query([
                'sessionId' => $record->id,
                'sessionType' => $sessionType,
            ]))
            ->openUrlInNewTab();
    }

    public function getQuranCount(): int
    {
        $query = QuranSession::query();
        $academyId = AcademyContextService::getCurrentAcademyId();
        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        return $query->count();
    }

    public function getAcademicCount(): int
    {
        $query = AcademicSession::query();
        $academyId = AcademyContextService::getCurrentAcademyId();
        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        return $query->count();
    }

    public function getInteractiveCount(): int
    {
        $query = InteractiveCourseSession::query();
        $academyId = AcademyContextService::getCurrentAcademyId();
        if ($academyId) {
            $query->whereHas('course', fn ($q) => $q->where('academy_id', $academyId));
        }

        return $query->count();
    }
}
