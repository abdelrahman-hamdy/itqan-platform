<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
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

        // Get today's sessions
        $todaySessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereDate('scheduled_at', today())
            ->with(['student', 'academicIndividualLesson.academicSubject'])
            ->get();

        // Get today's interactive course sessions
        $todayCourseSessions = InteractiveCourseSession::whereHas('course', function ($query) use ($teacherProfile) {
                $query->where('assigned_teacher_id', $teacherProfile->id);
            })
            ->whereDate('scheduled_date', today())
            ->with(['course.subject'])
            ->get();

        // Format events for display
        foreach ($todaySessions as $session) {
            $events[] = [
                'title' => $session->title . ' - درس فردي',
                'time' => $session->scheduled_at->format('H:i'),
                'type' => 'individual',
                'color' => 'blue',
                'student' => $session->student?->name ?? 'طالب',
                'subject' => $session->academicIndividualLesson?->academicSubject?->name ?? 'مادة أكاديمية'
            ];
        }

        foreach ($todayCourseSessions as $courseSession) {
            $events[] = [
                'title' => $courseSession->title . ' - دورة تفاعلية',
                'time' => $courseSession->scheduled_time->format('H:i'),
                'type' => 'interactive',
                'color' => 'green',
                'course' => $courseSession->course?->title ?? 'دورة',
                'subject' => $courseSession->course?->subject?->name ?? 'مادة أكاديمية'
            ];
        }

        return [
            'events' => $events,
            'today' => today()->format('Y-m-d'),
            'dayName' => today()->locale('ar')->dayName
        ];
    }
}
