<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Filament\AcademicTeacher\Resources\AcademicSessionResource;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class AcademicFullCalendarWidget extends FullCalendarWidget
{
    public Model|string|null $model = AcademicSession::class;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getViewData(): array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
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
                'id' => 'individual_'.$session->id,
                'title' => $session->title.' - '.($session->student->name ?? 'طالب'),
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
                    'url' => AcademicSessionResource::getUrl('view', ['record' => $session]),
                ],
            ];
        }

        // Add interactive course sessions (Green color)
        foreach ($interactiveCourseSessions as $courseSession) {
            $sessionDateTime = Carbon::createFromFormat('Y-m-d', $courseSession->scheduled_date->format('Y-m-d'))
                ->setTimeFromTimeString($courseSession->scheduled_time->format('H:i:s'));

            $events[] = [
                'id' => 'course_'.$courseSession->id,
                'title' => $courseSession->title.' - دورة تفاعلية',
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
                    'url' => AcademicSessionResource::getUrl('view', ['record' => $courseSession->id]),
                ],
            ];
        }

        return [
            'events' => $events,
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile) {
            return [];
        }

        return AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->where('scheduled_at', '>=', $fetchInfo['start'])
            ->where('scheduled_at', '<=', $fetchInfo['end'])
            ->whereNotNull('scheduled_at')
            ->with(['student', 'academicSubscription'])
            ->get()
            ->map(function (AcademicSession $session) {
                $title = $session->title ?? 'جلسة أكاديمية';
                $studentName = $session->student?->name ?? 'طالب';

                return EventData::make()
                    ->id($session->id)
                    ->title($title.' - '.$studentName)
                    ->start($session->scheduled_at)
                    ->end($session->scheduled_at->addMinutes($session->duration_minutes ?? 60))
                    ->backgroundColor('#3B82F6')
                    ->borderColor('#2563EB')
                    ->textColor('#FFFFFF')
                    ->extendedProps([
                        'type' => 'individual',
                        'sessionId' => $session->id,
                        'status' => $session->status,
                        'student' => $studentName,
                    ]);
            })
            ->toArray();
    }

    public function config(): array
    {
        return [
            'firstDay' => 6, // Saturday start
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'slotMinTime' => '06:00:00',
            'slotMaxTime' => '23:00:00',
            'height' => 'auto',
            'expandRows' => true,
            'nowIndicator' => true,
            'slotDuration' => '00:30:00',
            'businessHours' => [
                'daysOfWeek' => [6, 0, 1, 2, 3, 4, 5], // Sunday to Saturday
                'startTime' => '08:00',
                'endTime' => '22:00',
            ],
            'eventColor' => '#10b981',
            'eventTextColor' => '#ffffff',
            'weekends' => true,
            'allDaySlot' => false,
            'selectMirror' => true,
            'unselectAuto' => false,
            'editable' => true,
            'eventStartEditable' => true,
            'eventDurationEditable' => true,
            'eventOverlap' => false,
        ];
    }

    /**
     * Resolve record from event ID
     */
    public function resolveEventRecord(string $eventId): ?Model
    {
        // Skip interactive course sessions (they have 'course_' prefix)
        if (str_starts_with($eventId, 'course_')) {
            return null;
        }

        // Only resolve AcademicSession records for individual sessions
        return AcademicSession::find($eventId);
    }

    /**
     * Modal actions for event management
     */
    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make('editSession')
                ->label('تعديل')
                ->icon('heroicon-o-pencil-square')
                ->modalHeading('تعديل بيانات الجلسة الأكاديمية')
                ->modalSubmitActionLabel('حفظ التغييرات')
                ->modalCancelActionLabel('إلغاء')
                ->visible(function (array $arguments): bool {
                    $eventId = $arguments['event']['id'] ?? null;

                    // Only show for individual academic sessions (not course sessions)
                    return $eventId && ! str_starts_with($eventId, 'course_');
                })
                ->fillForm(function (array $arguments): array {
                    $eventId = $arguments['event']['id'];
                    $record = AcademicSession::find($eventId);

                    if (! $record) {
                        return [];
                    }

                    return [
                        'scheduled_at' => $record->scheduled_at,
                        'description' => $record->description,
                    ];
                })
                ->form([
                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('موعد الجلسة')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(15)
                        ->minDate(fn () => Carbon::now()->toDateString())
                        ->maxDate(fn () => Carbon::now()->addMonths(6)->toDateString())
                        ->native(false)
                        ->displayFormat('Y-m-d H:i')
                        ->timezone(config('app.timezone', 'UTC'))
                        ->helperText('اختر التاريخ والوقت الجديد للجلسة'),

                    Forms\Components\Textarea::make('description')
                        ->label('ملاحظات الجلسة')
                        ->rows(3)
                        ->maxLength(500)
                        ->placeholder('اكتب أي ملاحظات حول الجلسة...'),
                ])
                ->action(function (array $arguments, array $data): void {
                    $eventId = $arguments['event']['id'];
                    $record = AcademicSession::find($eventId);

                    if (! $record) {
                        Notification::make()
                            ->title('خطأ')
                            ->body('لم يتم العثور على الجلسة')
                            ->danger()
                            ->send();

                        return;
                    }

                    $record->update([
                        'scheduled_at' => Carbon::parse($data['scheduled_at']),
                        'description' => $data['description'] ?? null,
                    ]);

                    Notification::make()
                        ->title('تم تحديث الجلسة بنجاح')
                        ->success()
                        ->send();

                    // Refresh calendar
                    $this->dispatch('refresh');
                }),

            Actions\ViewAction::make('viewSession')
                ->label('عرض التفاصيل')
                ->icon('heroicon-o-eye')
                ->modalHeading('تفاصيل الجلسة الأكاديمية')
                ->visible(function (array $arguments): bool {
                    $eventId = $arguments['event']['id'] ?? null;

                    // Only show for individual academic sessions (not course sessions)
                    return $eventId && ! str_starts_with($eventId, 'course_');
                })
                ->infolist(function (array $arguments): array {
                    $eventId = $arguments['event']['id'];
                    $record = AcademicSession::find($eventId);

                    if (! $record) {
                        return [];
                    }

                    return [
                        \Filament\Infolists\Components\TextEntry::make('title')
                            ->label('عنوان الجلسة')
                            ->state($record->title),
                        \Filament\Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->state($record->scheduled_at)
                            ->dateTime(),
                        \Filament\Infolists\Components\TextEntry::make('duration_minutes')
                            ->label('مدة الجلسة')
                            ->state(($record->duration_minutes ?? 60).' دقيقة'),
                        \Filament\Infolists\Components\TextEntry::make('status')
                            ->label('حالة الجلسة')
                            ->state($record->status)
                            ->badge(),
                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->label('ملاحظات')
                            ->state($record->description ?? 'لا توجد ملاحظات'),
                    ];
                }),
        ];
    }
}
