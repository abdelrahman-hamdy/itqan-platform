<?php

namespace App\Filament\Supervisor\Resources\MonitoredAllSessionsResource\Pages;

use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Filament\Supervisor\Resources\MonitoredAllSessionsResource;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\MeetingObserverService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;

class ObserveSession extends Page
{
    protected static string $resource = MonitoredAllSessionsResource::class;

    protected string $view = 'filament.supervisor.pages.observe-session';

    public Model $record;

    public string $sessionType;

    public string $teacherName = '';

    public string $studentInfo = '';

    public string $sessionTitle = '';

    public string $sessionStatus = '';

    public string $scheduledAt = '';

    public string $observerRoleLabel = '';

    public string $observerTokenUrl = '';

    public string $supervisorNotes = '';

    public function mount(int|string $record): void
    {
        $this->sessionType = request()->query('type', 'quran');
        $this->record = $this->resolveRecord($record);

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
            MonitoredAllSessionsResource::getUrl('index') => __('supervisor.observation.all_sessions'),
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

    protected function resolveRecord(int|string $key): Model
    {
        return match ($this->sessionType) {
            'academic' => AcademicSession::with(['academicTeacher.user', 'student'])->findOrFail($key),
            'interactive' => InteractiveCourseSession::with(['course.assignedTeacher.user'])->findOrFail($key),
            default => QuranSession::with(['quranTeacher', 'circle', 'student'])->findOrFail($key),
        };
    }

    protected function prepareViewData(): void
    {
        $user = auth()->user();

        // Observer role label
        $this->observerRoleLabel = $user->user_type === UserType::SUPER_ADMIN->value
            ? __('supervisor.observation.role_super_admin')
            : __('supervisor.observation.role_supervisor');

        // Session title
        $this->sessionTitle = $this->record->title ?? $this->record->session_code ?? '';

        // Session status
        $status = $this->record->status instanceof SessionStatus
            ? $this->record->status
            : SessionStatus::tryFrom($this->record->status);
        $this->sessionStatus = $status?->label() ?? '';

        // Scheduled time
        $this->scheduledAt = $this->record->scheduled_at
            ? toAcademyTimezone($this->record->scheduled_at)->format('Y-m-d H:i')
            : '';

        // Teacher and student info based on session type
        $this->extractParticipantInfo();

        // Observer token URL
        $this->observerTokenUrl = route('api.meetings.observer-token', [
            'sessionType' => $this->sessionType,
            'sessionId' => $this->record->id,
        ]);

        // Supervisor notes
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
