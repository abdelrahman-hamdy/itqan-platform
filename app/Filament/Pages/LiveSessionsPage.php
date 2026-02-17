<?php

namespace App\Filament\Pages;

use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use App\Filament\Resources\QuranSessionResource;
use App\Filament\Resources\AcademicSessionResource;
use App\Filament\Resources\InteractiveCourseSessionResource;
use Filament\Actions\Action;
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

/**
 * Live Sessions monitoring page for SuperAdmin panel.
 * Shows all sessions in the current academy with native Filament tabs and observe capabilities.
 */
class LiveSessionsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-eye';

    protected static ?string $navigationLabel = 'مراقبة الجلسات';

    protected static ?string $title = 'مراقبة الجلسات المباشرة';

    protected static ?string $slug = 'live-sessions';

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.pages.live-sessions';

    public ?string $activeTab = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function getTabs(): array
    {
        $academyId = AcademyContextService::getCurrentAcademyId();

        $quranQuery = QuranSession::query();
        $academicQuery = AcademicSession::query();
        $interactiveQuery = InteractiveCourseSession::query();

        if ($academyId) {
            $quranQuery->where('academy_id', $academyId);
            $academicQuery->where('academy_id', $academyId);
            $interactiveQuery->whereHas('course', fn ($q) => $q->where('academy_id', $academyId));
        }

        return [
            'quran' => Tab::make('جلسات القرآن')
                ->icon('heroicon-o-book-open')
                ->badge($quranQuery->count())
                ->badgeColor('success'),
            'academic' => Tab::make('جلسات أكاديمية')
                ->icon('heroicon-o-academic-cap')
                ->badge($academicQuery->count())
                ->badgeColor('warning'),
            'interactive' => Tab::make('جلسات الدورات')
                ->icon('heroicon-o-video-camera')
                ->badge($interactiveQuery->count())
                ->badgeColor('info'),
        ];
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

                Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),

                Filter::make('has_meeting')
                    ->label('لديها اجتماع نشط')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('meeting_room_name')
                        ->whereIn('status', [SessionStatus::READY->value, SessionStatus::ONGOING->value])),
            ])
            ->recordActions([
                $this->getObserveAction('quran'),
                ViewAction::make()
                    ->label('عرض')
                    ->url(fn ($record): string => QuranSessionResource::getUrl('view', ['record' => $record])),
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
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),

                Filter::make('has_meeting')
                    ->label('لديها اجتماع نشط')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('meeting_room_name')
                        ->whereIn('status', [SessionStatus::READY->value, SessionStatus::ONGOING->value])),
            ])
            ->recordActions([
                $this->getObserveAction('academic'),
                ViewAction::make()
                    ->label('عرض')
                    ->url(fn ($record): string => AcademicSessionResource::getUrl('view', ['record' => $record])),
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
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(SessionStatus::options()),

                Filter::make('today')
                    ->label('جلسات اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Filter::make('this_week')
                    ->label('جلسات هذا الأسبوع')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),

                Filter::make('has_meeting')
                    ->label('لديها اجتماع نشط')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('meeting_room_name')
                        ->whereIn('status', [SessionStatus::READY->value, SessionStatus::ONGOING->value])),
            ])
            ->recordActions([
                $this->getObserveAction('interactive'),
                ViewAction::make()
                    ->label('عرض')
                    ->url(fn ($record): string => InteractiveCourseSessionResource::getUrl('view', ['record' => $record])),
            ]);
    }

    protected function getObserveAction(string $sessionType): Action
    {
        return Action::make('observe_meeting')
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
}
