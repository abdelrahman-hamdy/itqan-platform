<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Services\AcademyContextService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AcademicCalendarWidget extends Widget
{
    protected static string $view = 'filament.academic-teacher.widgets.academic-calendar-widget';
    
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (!$teacherProfile) {
            return ['events' => []];
        }

        $events = [];

        $timezone = AcademyContextService::getTimezone();
        $today = Carbon::now($timezone);

        // Get today's sessions
        $todaySessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereDate('scheduled_at', $today->toDateString())
            ->with(['student', 'academicIndividualLesson.academicSubject'])
            ->get();

        // Get today's interactive course sessions
        $todayCourseSessions = InteractiveCourseSession::whereHas('course', function ($query) use ($teacherProfile) {
                $query->where('assigned_teacher_id', $teacherProfile->id);
            })
            ->whereDate('scheduled_at', $today->toDateString())
            ->with(['course.subject'])
            ->get();

        // Format events for display
        foreach ($todaySessions as $session) {
            // Convert scheduled_at to academy timezone for display
            $scheduledAt = $session->scheduled_at->timezone($timezone);
            $events[] = [
                'title' => $session->title . ' - درس فردي',
                'time' => $scheduledAt->format('H:i'),
                'type' => 'individual',
                'color' => 'blue',
                'student' => $session->student?->name ?? 'طالب',
                'subject' => $session->academicIndividualLesson?->academicSubject?->name ?? 'مادة أكاديمية'
            ];
        }

        foreach ($todayCourseSessions as $courseSession) {
            // Convert scheduled_at to academy timezone for display
            $scheduledTime = $courseSession->scheduled_at->timezone($timezone);
            $events[] = [
                'title' => $courseSession->title . ' - دورة تفاعلية',
                'time' => $scheduledTime->format('H:i'),
                'type' => 'interactive',
                'color' => 'green',
                'course' => $courseSession->course?->title ?? 'دورة',
                'subject' => $courseSession->course?->subject?->name ?? 'مادة أكاديمية'
            ];
        }

        return [
            'events' => $events,
            'today' => $today->format('Y-m-d'),
            'dayName' => $today->locale('ar')->dayName
        ];
    }
}
