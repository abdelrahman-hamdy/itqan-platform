<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Enums\SessionStatus;
use App\Filament\AcademicTeacher\Resources\AcademicSessionReportResource;
use App\Filament\AcademicTeacher\Resources\AcademicSessionResource;
use App\Filament\AcademicTeacher\Resources\InteractiveCourseResource;
use App\Filament\AcademicTeacher\Resources\InteractiveCourseSessionResource;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AcademicQuickActionsWidget extends Widget
{
    // Prevent auto-discovery - Dashboard explicitly adds this widget
    protected static bool $isDiscoverable = false;

    protected static string $view = 'filament.academic-teacher.widgets.quick-actions';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            return [
                'todaySession' => null,
                'todaySessionUrl' => null,
                'todayCourseSession' => null,
                'todayCourseSessionUrl' => null,
                'academicSessionsUrl' => AcademicSessionResource::getUrl('index'),
                'interactiveCoursesUrl' => InteractiveCourseResource::getUrl('index'),
                'reportsUrl' => AcademicSessionReportResource::getUrl('index'),
            ];
        }

        // Get today's upcoming individual session
        $todaySession = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereDate('scheduled_at', today())
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::READY->value])
            ->orderBy('scheduled_at')
            ->first();

        // Get today's upcoming course session
        $todayCourseSession = InteractiveCourseSession::whereHas('course', function ($q) use ($teacherProfile) {
            $q->where('assigned_teacher_id', $teacherProfile->id);
        })
            ->whereDate('scheduled_at', today())
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::READY->value])
            ->orderBy('scheduled_at')
            ->with('course')
            ->first();

        return [
            'todaySession' => $todaySession,
            'todaySessionUrl' => $todaySession ? AcademicSessionResource::getUrl('view', ['record' => $todaySession->id]) : null,
            'todayCourseSession' => $todayCourseSession,
            'todayCourseSessionUrl' => $todayCourseSession ? InteractiveCourseSessionResource::getUrl('view', ['record' => $todayCourseSession->id]) : null,
            'academicSessionsUrl' => AcademicSessionResource::getUrl('index'),
            'interactiveCoursesUrl' => InteractiveCourseResource::getUrl('index'),
            'reportsUrl' => AcademicSessionReportResource::getUrl('index'),
        ];
    }
}
