<?php

namespace App\Filament\Supervisor\Widgets;

use App\Enums\SessionStatus;
use App\Filament\Supervisor\Resources\MonitoredCirclesResource;
use App\Filament\Supervisor\Resources\MonitoredSessionsResource;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SupervisorQuickActionsWidget extends Widget
{
    // Prevent auto-discovery - Dashboard explicitly adds this widget
    protected static bool $isDiscoverable = false;

    protected static string $view = 'filament.supervisor.widgets.quick-actions';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = Auth::user();
        $profile = $user?->supervisorProfile;

        if (! $profile) {
            return [
                'ongoingSessions' => 0,
                'todayScheduled' => 0,
                'activeCircles' => 0,
                'ongoingSessionsUrl' => MonitoredSessionsResource::getUrl('index'),
                'todaySessionsUrl' => MonitoredSessionsResource::getUrl('index'),
                'circlesUrl' => MonitoredCirclesResource::getUrl('index'),
                'allSessionsUrl' => MonitoredSessionsResource::getUrl('index'),
            ];
        }

        $academyId = $profile->academy_id;
        $assignedTeachers = $profile->assigned_teachers ?? [];

        // Build base queries
        $circlesQuery = QuranCircle::where('academy_id', $academyId);
        $sessionsQuery = QuranSession::where('academy_id', $academyId);

        // Filter by assigned teachers if set
        if (! empty($assignedTeachers)) {
            $circlesQuery->whereIn('quran_teacher_id', $assignedTeachers);
            $sessionsQuery->whereIn('quran_teacher_id', $assignedTeachers);
        }

        // Calculate stats for quick actions
        $ongoingSessions = (clone $sessionsQuery)
            ->where('status', SessionStatus::ONGOING->value)
            ->count();

        $todayScheduled = (clone $sessionsQuery)
            ->whereDate('scheduled_at', today())
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::READY->value])
            ->count();

        $activeCircles = (clone $circlesQuery)
            ->where('status', true)
            ->count();

        return [
            'ongoingSessions' => $ongoingSessions,
            'todayScheduled' => $todayScheduled,
            'activeCircles' => $activeCircles,
            'ongoingSessionsUrl' => MonitoredSessionsResource::getUrl('index', ['tableFilters[status][value]' => 'ongoing']),
            'todaySessionsUrl' => MonitoredSessionsResource::getUrl('index', ['tableFilters[scheduled_date][value]' => today()->toDateString()]),
            'circlesUrl' => MonitoredCirclesResource::getUrl('index'),
            'allSessionsUrl' => MonitoredSessionsResource::getUrl('index'),
        ];
    }
}
