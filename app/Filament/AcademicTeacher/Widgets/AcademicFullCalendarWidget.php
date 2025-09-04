<?php

namespace App\Filament\AcademicTeacher\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AcademicFullCalendarWidget extends FullCalendarWidget
{
    public function getViewData(): array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (!$teacherProfile) {
            return [];
        }

        // Get individual academic sessions
        $individualSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->where('session_type', 'individual')
            ->whereNotNull('scheduled_at')
            ->with(['student', 'academicIndividualLesson.academicSubject'])
            ->get();

        // Get interactive course sessions
        $interactiveCourseSessions = InteractiveCourseSession::whereHas('course', function ($query) use ($teacherProfile) {
                $query->where('assigned_teacher_id', $teacherProfile->id);
            })
            ->whereNotNull('scheduled_date')
            ->with(['course.subject'])
            ->get();

        $events = [];

        // Add individual sessions (Blue color)
        foreach ($individualSessions as $session) {
            $events[] = [
                'id' => 'individual_' . $session->id,
                'title' => $session->title . ' - ' . ($session->student->name ?? 'طالب'),
                'start' => $session->scheduled_at->toISOString(),
                'end' => $session->scheduled_at->copy()->addMinutes($session->duration_minutes)->toISOString(),
                'backgroundColor' => '#3B82F6', // Blue for individual lessons
                'borderColor' => '#2563EB',
                'textColor' => '#FFFFFF',
                'extendedProps' => [
                    'type' => 'individual',
                    'sessionId' => $session->id,
                    'status' => $session->status,
                    'subject' => $session->academicIndividualLesson?->academicSubject?->name ?? 'مادة أكاديمية',
                    'student' => $session->student?->name ?? 'طالب',
                    'url' => route('teacher.academic.sessions.show', ['subdomain' => $user->academy->subdomain, 'session' => $session->id])
                ]
            ];
        }

        // Add interactive course sessions (Green color)
        foreach ($interactiveCourseSessions as $courseSession) {
            $sessionDateTime = Carbon::createFromFormat('Y-m-d', $courseSession->scheduled_date->format('Y-m-d'))
                ->setTimeFromTimeString($courseSession->scheduled_time->format('H:i:s'));

            $events[] = [
                'id' => 'course_' . $courseSession->id,
                'title' => $courseSession->title . ' - دورة تفاعلية',
                'start' => $sessionDateTime->toISOString(),
                'end' => $sessionDateTime->copy()->addMinutes($courseSession->duration_minutes)->toISOString(),
                'backgroundColor' => '#10B981', // Green for interactive courses
                'borderColor' => '#059669',
                'textColor' => '#FFFFFF',
                'extendedProps' => [
                    'type' => 'interactive_course',
                    'sessionId' => $courseSession->id,
                    'status' => $courseSession->status,
                    'subject' => $courseSession->course?->subject?->name ?? 'دورة تفاعلية',
                    'course' => $courseSession->course?->title ?? 'دورة',
                    'url' => route('teacher.academic.sessions.show', ['subdomain' => $user->academy->subdomain, 'session' => $courseSession->id])
                ]
            ];
        }

        return [
            'events' => $events,
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $data = $this->getViewData();
        return $data['events'] ?? [];
    }

    public function getOptions(): array
    {
        return [
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay individualButton,interactiveButton',
            ],
            'customButtons' => [
                'individualButton' => [
                    'text' => 'دروس فردية',
                    'click' => 'function() { window.filterCalendar("individual"); }'
                ],
                'interactiveButton' => [
                    'text' => 'دورات تفاعلية',
                    'click' => 'function() { window.filterCalendar("interactive_course"); }'
                ]
            ],
            'eventClick' => 'function(info) { 
                window.open(info.event.extendedProps.url, "_blank"); 
            }',
            'height' => 'auto',
            'firstDay' => 6, // Saturday
            'slotMinTime' => '06:00:00',
            'slotMaxTime' => '23:00:00',
            'nowIndicator' => true,
            'businessHours' => [
                'daysOfWeek' => [6, 0, 1, 2, 3, 4, 5],
                'startTime' => '08:00',
                'endTime' => '22:00',
            ],
            'eventDisplay' => 'block',
            'displayEventTime' => true,
            'eventTimeFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'hour12' => false
            ]
        ];
    }
}
