<?php

namespace App\Filament\Pages;

use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\MeetingObserverService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

/**
 * Observe Session page for SuperAdmin panel.
 * Provides subscribe-only LiveKit meeting observation.
 */
class ObserveSessionPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-eye';

    protected static ?string $slug = 'observe-session';

    protected string $view = 'filament.supervisor.pages.observe-session';

    protected static bool $shouldRegisterNavigation = false;

    #[Url]
    public ?string $sessionId = null;

    #[Url]
    public string $sessionType = 'quran';

    public ?Model $record = null;

    public string $teacherName = '';

    public string $studentInfo = '';

    public string $sessionTitle = '';

    public string $sessionStatus = '';

    public string $scheduledAt = '';

    public string $observerRoleLabel = '';

    public string $observerTokenUrl = '';

    public string $supervisorNotes = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        if (! $this->sessionId) {
            abort(404, __('supervisor.observation.session_not_found'));
        }

        $this->record = $this->resolveRecord();

        if (! $this->record) {
            abort(404, __('supervisor.observation.session_not_found'));
        }

        $observerService = app(MeetingObserverService::class);

        if (! $observerService->canObserveSession(auth()->user(), $this->record)) {
            abort(403, __('supervisor.observation.not_authorized'));
        }

        if (! $observerService->isSessionObservable($this->record)) {
            abort(403, __('supervisor.observation.session_not_active'));
        }

        $this->prepareViewData();
    }

    public function getTitle(): string
    {
        return __('supervisor.observation.observe_session').': '.($this->record->title ?? $this->record->session_code ?? '');
    }

    public function getBreadcrumbs(): array
    {
        return [
            LiveSessionsPage::getUrl() => 'مراقبة الجلسات',
            '#' => __('supervisor.observation.observe_session'),
        ];
    }

    public function saveNotes(): void
    {
        if ($this->record) {
            $this->record->update(['supervisor_notes' => $this->supervisorNotes]);
            Notification::make()
                ->title(__('supervisor.observation.saved'))
                ->success()
                ->send();
        }
    }

    protected function resolveRecord(): ?Model
    {
        return match ($this->sessionType) {
            'academic' => AcademicSession::with(['academicTeacher.user', 'student'])->find($this->sessionId),
            'interactive' => InteractiveCourseSession::with(['course.assignedTeacher.user'])->find($this->sessionId),
            default => QuranSession::with(['quranTeacher', 'circle', 'student'])->find($this->sessionId),
        };
    }

    protected function prepareViewData(): void
    {
        $user = auth()->user();

        $this->observerRoleLabel = $user->user_type === UserType::SUPER_ADMIN->value
            ? __('supervisor.observation.role_super_admin')
            : __('supervisor.observation.role_supervisor');

        $this->sessionTitle = $this->record->title ?? $this->record->session_code ?? '';

        $status = $this->record->status instanceof SessionStatus
            ? $this->record->status
            : SessionStatus::tryFrom($this->record->status);
        $this->sessionStatus = $status?->label() ?? '';

        $this->scheduledAt = $this->record->scheduled_at
            ? toAcademyTimezone($this->record->scheduled_at)->format('Y-m-d H:i')
            : '';

        $this->extractParticipantInfo();

        $this->observerTokenUrl = route('api.meetings.observer-token', [
            'sessionType' => $this->sessionType,
            'sessionId' => $this->record->id,
        ]);

        $this->supervisorNotes = $this->record->supervisor_notes ?? '';
    }

    protected function extractParticipantInfo(): void
    {
        if ($this->record instanceof QuranSession) {
            $this->teacherName = $this->record->quranTeacher?->name ?? __('supervisor.observation.unknown');
            $this->studentInfo = $this->record->session_type === 'individual'
                ? ($this->record->student?->name ?? __('supervisor.observation.unknown'))
                : ($this->record->circle?->name ?? __('supervisor.observation.group_session'));
        } elseif ($this->record instanceof AcademicSession) {
            $this->teacherName = $this->record->academicTeacher?->user?->name ?? __('supervisor.observation.unknown');
            $this->studentInfo = $this->record->student?->name ?? __('supervisor.observation.unknown');
        } elseif ($this->record instanceof InteractiveCourseSession) {
            $this->teacherName = $this->record->course?->assignedTeacher?->user?->name ?? __('supervisor.observation.unknown');
            $this->studentInfo = $this->record->course?->title ?? __('supervisor.observation.unknown');
        }
    }
}
