<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AcademicQuickActionsWidget extends Widget
{
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
                'todayCourseSession' => null,
            ];
        }

        // Get today's upcoming individual session
        $todaySession = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereDate('scheduled_at', today())
            ->whereIn('status', ['scheduled', 'ready'])
            ->orderBy('scheduled_at')
            ->first();

        // Get today's upcoming course session
        $todayCourseSession = InteractiveCourseSession::whereHas('course', function ($q) use ($teacherProfile) {
            $q->where('assigned_teacher_id', $teacherProfile->id);
        })
            ->whereDate('scheduled_at', today())
            ->whereIn('status', ['scheduled', 'ready'])
            ->orderBy('scheduled_at')
            ->with('course')
            ->first();

        return [
            'todaySession' => $todaySession,
            'todayCourseSession' => $todayCourseSession,
        ];
    }
}
