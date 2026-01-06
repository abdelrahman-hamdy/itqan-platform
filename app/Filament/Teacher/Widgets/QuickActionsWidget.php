<?php

namespace App\Filament\Teacher\Widgets;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Filament\Teacher\Resources\QuranSessionResource;
use App\Filament\Teacher\Resources\QuranTrialRequestResource;
use App\Filament\Teacher\Resources\StudentSessionReportResource;
use App\Models\QuranSession;
use App\Models\QuranTrialRequest;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class QuickActionsWidget extends Widget
{
    // Prevent auto-discovery - Dashboard explicitly adds this widget
    protected static bool $isDiscoverable = false;

    protected static string $view = 'filament.teacher.widgets.quick-actions';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = Auth::user();
        $teacher = $user->quranTeacherProfile;

        if (! $teacher) {
            return [
                'todaySession' => null,
                'todaySessionUrl' => null,
                'pendingTrials' => 0,
                'trialRequestsUrl' => QuranTrialRequestResource::getUrl('index'),
                'sessionsUrl' => QuranSessionResource::getUrl('index'),
                'reportsUrl' => StudentSessionReportResource::getUrl('index'),
            ];
        }

        // Get today's upcoming session
        $todaySession = QuranSession::where('quran_teacher_id', $teacher->id)
            ->whereDate('scheduled_at', today())
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::READY->value])
            ->orderBy('scheduled_at')
            ->first();

        // Get pending trial requests count
        $pendingTrials = QuranTrialRequest::where('teacher_id', $teacher->id)
            ->where('status', SessionSubscriptionStatus::PENDING->value)
            ->count();

        return [
            'todaySession' => $todaySession,
            'todaySessionUrl' => $todaySession ? QuranSessionResource::getUrl('view', ['record' => $todaySession->id]) : null,
            'pendingTrials' => $pendingTrials,
            'trialRequestsUrl' => QuranTrialRequestResource::getUrl('index'),
            'sessionsUrl' => QuranSessionResource::getUrl('index'),
            'reportsUrl' => StudentSessionReportResource::getUrl('index'),
        ];
    }
}
