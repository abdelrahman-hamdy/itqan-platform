<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Filament\Shared\Widgets\BaseFullCalendarWidget;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Data\EventData;

/**
 * Academic Teacher Calendar Widget
 *
 * Displays calendar for Academic Sessions and Interactive Course Sessions
 */
class AcademicFullCalendarWidget extends BaseFullCalendarWidget
{
    // Default model for academic sessions
    public Model|string|null $model = AcademicSession::class;

    /**
     * Fetch events for the calendar
     */
    public function fetchEvents(array $fetchInfo): array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (!$teacherProfile) {
            return [];
        }

        $timezone = AcademyContextService::getTimezone();

        // Fetch both academic and course sessions
        $academicEvents = $this->fetchAcademicSessions($teacherProfile->id, $fetchInfo, $timezone);
        $courseEvents = $this->fetchInteractiveCourseSessions($teacherProfile->id, $fetchInfo, $timezone);

        return array_merge($academicEvents, $courseEvents);
    }

    /**
     * Fetch Academic Sessions for the calendar
     */
    protected function fetchAcademicSessions(int $teacherProfileId, array $fetchInfo, string $timezone): array
    {
        $user = Auth::user();

        return AcademicSession::query()
            ->where('academic_teacher_id', $teacherProfileId)
            ->where('academy_id', $user->academy_id)
            ->where('scheduled_at', '>=', $fetchInfo['start'])
            ->where('scheduled_at', '<=', $fetchInfo['end'])
            ->whereNotNull('scheduled_at')
            ->with(['student', 'academicIndividualLesson.academicSubject'])
            ->get()
            ->map(function (AcademicSession $session) use ($timezone) {
                return $this->mapAcademicSessionToEvent($session, $timezone);
            })
            ->toArray();
    }

    /**
     * Fetch Interactive Course Sessions for the calendar
     */
    protected function fetchInteractiveCourseSessions(int $teacherProfileId, array $fetchInfo, string $timezone): array
    {
        $query = InteractiveCourseSession::query()
            ->whereHas('course', function ($q) use ($teacherProfileId) {
                $q->where('assigned_teacher_id', $teacherProfileId);
            })
            ->where('scheduled_at', '>=', $fetchInfo['start'])
            ->where('scheduled_at', '<=', $fetchInfo['end'])
            ->whereNotNull('scheduled_at')
            ->whereNull('deleted_at');

        return $query
            ->with(['course', 'course.subject'])
            ->whereIn('status', ['scheduled', 'ready', 'ongoing', 'completed'])
            ->get()
            ->map(function (InteractiveCourseSession $session) use ($timezone) {
                return $this->mapInteractiveCourseSessionToEvent($session, $timezone);
            })
            ->toArray();
    }

    /**
     * Map Academic Session to calendar event
     */
    protected function mapAcademicSessionToEvent(AcademicSession $session, string $timezone): EventData
    {
        $isPassed = $session->scheduled_at < Carbon::now();

        // Determine title and color
        $studentName = $session->subscription?->student?->name ?? $session->student?->name ?? 'درس خاص';
        $subject = $session->academicIndividualLesson?->academicSubject?->name ?? 'مادة أكاديمية';
        $sessionNumber = $session->monthly_session_number ? "({$session->monthly_session_number})" : '';
        $title = "درس خاص: {$studentName} {$sessionNumber}";
        $color = $this->getSessionColor('individual', $session->status->value, true);

        // Convert UTC time to academy timezone for display
        // FullCalendar doesn't have IANA timezone conversion by default,
        // so we convert times ourselves and send them as local times
        $scheduledAt = $session->scheduled_at instanceof \Carbon\Carbon
            ? $session->scheduled_at->copy()->setTimezone($timezone)
            : \Carbon\Carbon::parse($session->scheduled_at, 'UTC')->setTimezone($timezone);

        $endTime = $scheduledAt->copy()->addMinutes($session->duration_minutes ?? 60);

        // Format as simple datetime without timezone offset - FullCalendar treats as local time
        $startStr = $scheduledAt->format('Y-m-d\TH:i:s');
        $endStr = $endTime->format('Y-m-d\TH:i:s');

        return EventData::make()
            ->id('academic-' . $session->id)
            ->title($title)
            ->start($startStr)
            ->end($endStr)
            ->backgroundColor($color)
            ->borderColor($color)
            ->textColor('#ffffff')
            ->extendedProps([
                'isPassed' => $isPassed,
                'status' => $session->status->value,
                'type' => 'academic',
                'sessionNumber' => $session->monthly_session_number,
                'subject' => $subject,
                'studentName' => $studentName,
                'modelType' => 'academic',
            ]);
    }

    /**
     * Map Interactive Course Session to calendar event
     */
    protected function mapInteractiveCourseSessionToEvent(InteractiveCourseSession $session, string $timezone): EventData
    {
        $isPassed = $session->scheduled_at < Carbon::now();

        $courseTitle = $session->course?->title ?? 'دورة تفاعلية';
        $subject = $session->course?->subject?->name ?? 'مادة أكاديمية';
        $sessionNumber = $session->session_number ? "جلسة {$session->session_number}" : '';
        $title = "{$courseTitle} - {$sessionNumber}";

        $status = $session->status instanceof \App\Enums\SessionStatus ? $session->status->value : ($session->status ?? 'scheduled');
        $color = $this->getSessionColor('interactive_course', $status, true);

        // Convert UTC time to academy timezone for display
        // FullCalendar doesn't have IANA timezone conversion by default,
        // so we convert times ourselves and send them as local times
        $scheduledAt = $session->scheduled_at instanceof \Carbon\Carbon
            ? $session->scheduled_at->copy()->setTimezone($timezone)
            : \Carbon\Carbon::parse($session->scheduled_at, 'UTC')->setTimezone($timezone);

        $endTime = $scheduledAt->copy()->addMinutes($session->duration_minutes ?? 60);

        // Format as simple datetime without timezone offset - FullCalendar treats as local time
        $startStr = $scheduledAt->format('Y-m-d\TH:i:s');
        $endStr = $endTime->format('Y-m-d\TH:i:s');

        return EventData::make()
            ->id('course-' . $session->id)
            ->title($title)
            ->start($startStr)
            ->end($endStr)
            ->backgroundColor($color)
            ->borderColor($color)
            ->textColor('#ffffff')
            ->extendedProps([
                'isPassed' => $isPassed,
                'status' => $status,
                'type' => 'interactive_course',
                'sessionNumber' => $session->session_number,
                'subject' => $subject,
                'courseTitle' => $courseTitle,
                'modelType' => 'course',
            ]);
    }

    /**
     * Resolve event record by ID prefix
     */
    public function resolveRecord(int|string $key): Model
    {
        // Handle Academic sessions with 'academic-' prefix
        if (is_string($key) && str_starts_with($key, 'academic-')) {
            $id = substr($key, 9);
            $record = AcademicSession::find($id);

            if (!$record) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(AcademicSession::class, [$id]);
            }

            return $record;
        }

        // Handle Interactive Course sessions with 'course-' prefix
        if (is_string($key) && str_starts_with($key, 'course-')) {
            $id = substr($key, 7);
            $record = InteractiveCourseSession::with('course')->find($id);

            if (!$record) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(InteractiveCourseSession::class, [$id]);
            }

            return $record;
        }

        return parent::resolveRecord($key);
    }

    /**
     * Show all sessions for a specific day
     */
    public function showDaySessionsModal(string $dateStr): void
    {
        $clickedDate = Carbon::parse($dateStr);
        $user = Auth::user();

        if (!$user || !$user->academicTeacherProfile) {
            return;
        }

        $teacherProfile = $user->academicTeacherProfile;

        // Fetch all sessions for this day
        $academicSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereDate('scheduled_at', $clickedDate->toDateString())
            ->whereNotNull('scheduled_at')
            ->with(['student', 'academicIndividualLesson.academicSubject'])
            ->orderBy('scheduled_at')
            ->get();

        $courseSessions = InteractiveCourseSession::whereHas('course', function ($query) use ($teacherProfile) {
                $query->where('assigned_teacher_id', $teacherProfile->id);
            })
            ->whereDate('scheduled_at', $clickedDate->toDateString())
            ->whereNotNull('scheduled_at')
            ->with(['course.subject'])
            ->orderBy('scheduled_at')
            ->get();

        $allSessions = $academicSessions->merge($courseSessions);

        $this->selectedDate = $dateStr;

        $timezone = AcademyContextService::getTimezone();
        $this->daySessions = $allSessions->map(function ($session) use ($user, $timezone) {
            $isCourse = $session instanceof InteractiveCourseSession;

            $scheduledAt = $session->scheduled_at;
            // Convert to academy timezone for display
            $scheduledAtInTz = $scheduledAt instanceof Carbon
                ? $scheduledAt->copy()->timezone($timezone)
                : Carbon::parse($scheduledAt, 'UTC')->timezone($timezone);
            $isPassed = $scheduledAt < Carbon::now();

            $statusEnum = $session->status instanceof \App\Enums\SessionStatus
                ? $session->status
                : \App\Enums\SessionStatus::tryFrom($session->status ?? 'scheduled');

            $sessionData = [
                'type' => $isCourse ? 'course' : 'academic',
                'isPassed' => $isPassed,
                'time' => $scheduledAtInTz->format('h:i A'),
                'duration' => $session->duration_minutes ?? 60,
                'studentName' => $session->student?->name ?? ($isCourse ? $session->course?->title : 'طالب'),
                'subject' => '',
                'sessionType' => '',
                'color' => '',
                'eventId' => '',
                'canEdit' => false,
                'status' => $statusEnum?->value ?? 'scheduled',
                'statusLabel' => $statusEnum?->label() ?? 'مجدولة',
                'statusColor' => $statusEnum?->hexColor() ?? '#3B82F6',
            ];

            if ($isCourse) {
                $sessionData['sessionType'] = 'دورة تفاعلية';
                $sessionData['subject'] = $session->course?->subject?->name ?? 'مادة أكاديمية';
                $sessionData['color'] = $this->getSessionColor('interactive_course', $sessionData['status'], true);
                $sessionData['eventId'] = 'course-' . $session->id;
                // Query already filtered by assigned_teacher_id, so all course sessions belong to this teacher
                $sessionData['canEdit'] = true;
            } else {
                $sessionData['sessionType'] = 'درس أكاديمي';
                $sessionData['subject'] = $session->academicIndividualLesson?->academicSubject?->name ?? 'مادة';
                $sessionData['color'] = $this->getSessionColor('individual', $sessionData['status'], true);
                $sessionData['eventId'] = 'academic-' . $session->id;
                // Query already filtered by academic_teacher_id, so all academic sessions belong to this teacher
                $sessionData['canEdit'] = true;
            }

            return $sessionData;
        })->toArray();

        $this->mountAction('viewDaySessions');
    }

    /**
     * Handle event drop (drag and drop) - for both academic and interactive course sessions
     */
    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        $eventId = $event['id'];
        $modelType = $event['extendedProps']['modelType'] ?? 'academic';

        // Parse times with academy timezone context to prevent offset issues
        $timezone = AcademyContextService::getTimezone();
        $newStart = Carbon::parse($event['start'], $timezone);
        $newEnd = Carbon::parse($event['end'], $timezone);
        $duration = $newStart->diffInMinutes($newEnd);

        // Handle interactive course sessions
        if ($modelType === 'course') {
            $numericId = (int) str_replace('course-', '', $eventId);
            $record = InteractiveCourseSession::with('course')->find($numericId);

            if (!$record) {
                return true; // Revert - record not found
            }

            // Validate new date is not before the course start date
            if ($record->course && $record->course->start_date) {
                $courseStartDate = Carbon::parse($record->course->start_date)->startOfDay();
                if ($newStart->startOfDay()->lt($courseStartDate)) {
                    Notification::make()
                        ->title('غير مسموح')
                        ->body('لا يمكن جدولة الجلسة قبل تاريخ بداية الدورة (' . $courseStartDate->format('Y-m-d') . ')')
                        ->warning()
                        ->send();

                    return true; // Revert - validation failed
                }
            }

            // Validate new date is not after course end date (if set)
            if ($record->course && $record->course->end_date) {
                $courseEndDate = Carbon::parse($record->course->end_date)->endOfDay();
                if ($newStart->gt($courseEndDate)) {
                    Notification::make()
                        ->title('غير مسموح')
                        ->body('لا يمكن جدولة الجلسة بعد تاريخ نهاية الدورة (' . $courseEndDate->format('Y-m-d') . ')')
                        ->warning()
                        ->send();

                    return true; // Revert - validation failed
                }
            }

            // Validate no conflicts for course sessions
            try {
                $conflictData = [
                    'scheduled_at' => $newStart,
                    'duration_minutes' => $duration,
                    'teacher_id' => Auth::user()->academicTeacherProfile->id,
                ];

                $this->validateSessionConflicts($conflictData, $numericId, 'course');
            } catch (\Exception $e) {
                Notification::make()
                    ->title('تعارض في المواعيد')
                    ->body($e->getMessage())
                    ->warning()
                    ->send();

                return true; // Revert - conflict found
            }

            $record->update([
                'scheduled_at' => $newStart,
                'duration_minutes' => $duration,
            ]);

            Notification::make()
                ->title('تم تحديث موعد جلسة الدورة بنجاح')
                ->success()
                ->send();

            // Return FALSE to tell FullCalendar to KEEP the new position
            // (return true = revert, return false = keep)
            return false;
        }

        // Handle academic sessions
        $numericId = (int) str_replace('academic-', '', $eventId);
        $record = AcademicSession::find($numericId);

        if (!$record) {
            return true; // Revert - record not found
        }

        // Validate no conflicts
        try {
            $conflictData = [
                'scheduled_at' => $newStart,
                'duration_minutes' => $duration,
                'teacher_id' => Auth::user()->academicTeacherProfile->id,
            ];

            $this->validateSessionConflicts($conflictData, $numericId, 'academic');
        } catch (\Exception $e) {
            Notification::make()
                ->title('تعارض في المواعيد')
                ->body($e->getMessage())
                ->warning()
                ->send();

            return true; // Revert - conflict found
        }

        $record->update([
            'scheduled_at' => $newStart,
            'duration_minutes' => $duration,
        ]);

        Notification::make()
            ->title('تم تحديث موعد الجلسة بنجاح')
            ->success()
            ->send();

        // Return FALSE to tell FullCalendar to KEEP the new position
        return false;
    }

    /**
     * Handle event resize - for both academic and interactive course sessions
     */
    public function onEventResize(array $event, array $oldEvent, array $relatedEvents, array $endDelta, array $startDelta): bool
    {
        $eventId = $event['id'];
        $modelType = $event['extendedProps']['modelType'] ?? 'academic';

        // Parse times with academy timezone context to prevent offset issues
        $timezone = AcademyContextService::getTimezone();
        $newStart = Carbon::parse($event['start'], $timezone);
        $newEnd = Carbon::parse($event['end'], $timezone);
        $newDuration = $newStart->diffInMinutes($newEnd);

        // Validate duration (only standard durations allowed)
        $allowedDurations = [30, 45, 60, 90, 120, 180];
        if (!in_array($newDuration, $allowedDurations)) {
            Notification::make()
                ->title('مدة غير مسموحة')
                ->body('المدة المسموحة: 30، 45، 60، 90، 120، أو 180 دقيقة')
                ->warning()
                ->send();

            return true; // Revert - invalid duration
        }

        // Handle interactive course sessions
        if ($modelType === 'course') {
            $numericId = (int) str_replace('course-', '', $eventId);
            $record = InteractiveCourseSession::find($numericId);

            if (!$record) {
                return true; // Revert - record not found
            }

            $record->update([
                'duration_minutes' => $newDuration,
            ]);

            Notification::make()
                ->title('تم تحديث مدة جلسة الدورة بنجاح')
                ->success()
                ->send();

            // Return FALSE to tell FullCalendar to KEEP the new size
            return false;
        }

        // Handle academic sessions
        $numericId = (int) str_replace('academic-', '', $eventId);
        $record = AcademicSession::find($numericId);

        if (!$record) {
            return true; // Revert - record not found
        }

        $record->update([
            'duration_minutes' => $newDuration,
        ]);

        Notification::make()
            ->title('تم تحديث مدة الجلسة بنجاح')
            ->success()
            ->send();

        // Return FALSE to tell FullCalendar to KEEP the new size
        return false;
    }

    // canEditEvent(), getFullEditUrl(), and canViewFullEdit() are inherited from BaseFullCalendarWidget
}
