<?php

namespace App\Filament\Shared\Widgets;

use App\Filament\Shared\Traits\FormatsCalendarData;
use App\Filament\Shared\Traits\ValidatesConflicts;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

/**
 * Base Calendar Widget
 *
 * Provides unified calendar functionality for all teacher calendars.
 * Extends this class to create teacher-specific calendars (Quran, Academic, etc.)
 */
abstract class BaseFullCalendarWidget extends FullCalendarWidget
{
    use FormatsCalendarData;
    use ValidatesConflicts;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    // Properties for day sessions modal
    public ?string $selectedDate = null;

    public array $daySessions = [];

    // Event listeners
    protected $listeners = [
        'refresh-calendar' => 'refreshCalendar',
    ];

    /**
     * Calendar configuration
     * Shared across all calendar types
     */
    public function config(): array
    {
        // Use academy timezone for all calendar operations
        $timezone = AcademyContextService::getTimezone();

        return [
            // CRITICAL: Set timezone for FullCalendar to use academy timezone
            // This ensures all times are displayed and parsed in the academy's timezone
            'timeZone' => $timezone,
            'firstDay' => 6, // Saturday start (RTL-appropriate)
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'slotMinTime' => '00:00:00',
            'slotMaxTime' => '24:00:00',
            'scrollTime' => '08:00:00', // Initial scroll position
            'height' => 'auto',
            'expandRows' => true,
            'nowIndicator' => true,
            'slotDuration' => '00:30:00',
            'businessHours' => [
                'daysOfWeek' => [6, 0, 1, 2, 3, 4, 5], // All days
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
     * Add custom CSS classes for passed events
     */
    public function eventClassNames(): string
    {
        return <<<'JS'
            function(arg) {
                if (arg.event.extendedProps && arg.event.extendedProps.isPassed) {
                    return ["event-passed"];
                }
                return [];
            }
        JS;
    }

    /**
     * Add click handler for day cells to show day sessions modal
     * Initialized only once per calendar instance
     */
    public function eventDidMount(): string
    {
        $widgetId = $this->getId();

        return <<<JS
            function(info) {
                const calendarEl = info.el.closest('.filament-fullcalendar');

                // Initialize click handlers only once
                if (calendarEl && !calendarEl.hasAttribute('data-click-initialized')) {
                    calendarEl.setAttribute('data-click-initialized', 'true');

                    // Style day numbers as clickable
                    const dayNumbers = calendarEl.querySelectorAll('.fc-daygrid-day-number');
                    dayNumbers.forEach(dayNum => {
                        dayNum.style.cursor = 'pointer';
                        dayNum.style.color = '#3b82f6';
                    });

                    // Add click handler for day cells (NOT events)
                    calendarEl.addEventListener('click', function(e) {
                        // Skip if click is on an event
                        if (e.target.closest('.fc-event')) return;

                        // Find the date cell that was clicked
                        const dateCell = e.target.closest('.fc-daygrid-day, .fc-timegrid-col, .fc-timegrid-slot');
                        if (!dateCell) return;

                        // Extract date from cell
                        let dateStr = dateCell.dataset.date;
                        if (!dateStr && dateCell.classList.contains('fc-timegrid-slot')) {
                            const col = dateCell.closest('.fc-timegrid-col');
                            dateStr = col?.dataset.date;
                        }

                        if (!dateStr) return;

                        // Call Livewire method to show day sessions modal
                        window.Livewire.find('{$widgetId}').call('showDaySessionsModal', dateStr);
                    });

                    // Add global notification listener (once per page)
                    if (!window.__calendarNotifyListenerAdded) {
                        window.__calendarNotifyListenerAdded = true;
                        window.addEventListener('notify', function(e) {
                            const { type, message } = e.detail;
                            // Use Filament's notification system via JavaScript
                            if (window.Filament && window.Filament.notifications) {
                                new window.Filament.notifications.Notification()
                                    .title(message)
                                    .status(type)
                                    .send();
                            } else {
                                // Fallback: show a simple toast-like notification
                                const toast = document.createElement('div');
                                toast.className = 'fixed bottom-4 left-4 z-50 px-4 py-2 rounded-lg shadow-lg text-white ' +
                                    (type === 'success' ? 'bg-green-500' : type === 'warning' ? 'bg-yellow-500' : 'bg-red-500');
                                toast.textContent = message;
                                toast.style.cssText = 'animation: fadeInOut 3s ease-in-out forwards;';
                                document.body.appendChild(toast);
                                setTimeout(() => toast.remove(), 3000);
                            }
                        });
                    }
                }
            }
        JS;
    }

    /**
     * Resolve event record by ID prefix
     * Override in child classes to handle specific session types
     *
     * Default implementation calls parent - child classes should override
     */
    public function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key);
    }

    /**
     * Helper method for resolving event records in modal actions
     * Wraps resolveRecord with error handling
     */
    protected function resolveEventRecord(string $eventId): ?Model
    {
        try {
            return $this->resolveRecord($eventId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return null;
        }
    }

    /**
     * Disable default create action from vendor package
     * We handle scheduling through the unified calendar page, not widget actions
     */
    protected function getActions(): array
    {
        return [];
    }

    /**
     * Disable header actions (create button) from vendor package
     * We handle scheduling through the unified calendar page, not widget buttons
     */
    protected function headerActions(): array
    {
        return [];
    }

    /**
     * Modal actions for event management
     * Provides comprehensive edit, delete, and day sessions view actions
     */
    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make('editSession')
                ->label('تعديل')
                ->icon('heroicon-o-pencil-square')
                ->visible(function (array $arguments): bool {
                    $eventId = $arguments['event']['id'] ?? null;
                    if (! $eventId) {
                        return false;
                    }

                    // Show edit action for all session types
                    return str_starts_with($eventId, 'quran-')
                        || str_starts_with($eventId, 'academic-')
                        || str_starts_with($eventId, 'course-');
                })
                ->modalHeading(function (array $arguments) {
                    $eventId = $arguments['event']['id'] ?? null;
                    $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                    if (! $record) {
                        return 'تعديل الجلسة';
                    }

                    // Determine heading based on session type
                    if ($record instanceof QuranSession) {
                        return $record->session_type === 'trial'
                            ? 'تعديل موعد الجلسة التجريبية'
                            : 'تعديل بيانات الجلسة';
                    } elseif ($record instanceof InteractiveCourseSession) {
                        return 'تعديل جلسة الدورة التفاعلية';
                    } else {
                        return 'تعديل الدرس الأكاديمي';
                    }
                })
                ->modalSubmitActionLabel('حفظ التغييرات')
                ->modalCancelActionLabel('إلغاء')
                ->fillForm(function (array $arguments): array {
                    $eventId = $arguments['event']['id'] ?? null;
                    $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                    if (! $record) {
                        return [];
                    }

                    return [
                        'scheduled_at' => $record->scheduled_at,
                        'description' => $record->description ?? '',
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
                        ->timezone(AcademyContextService::getTimezone())
                        ->helperText('اختر التاريخ والوقت الجديد للجلسة'),

                    Forms\Components\Textarea::make('description')
                        ->label('ملاحظات الجلسة')
                        ->rows(3)
                        ->maxLength(500)
                        ->placeholder('اكتب أي ملاحظات حول الجلسة...'),
                ])
                ->action(function (array $arguments, array $data): void {
                    $eventId = $arguments['event']['id'] ?? null;
                    $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                    if (! $record) {
                        Notification::make()
                            ->title('خطأ')
                            ->body('لم يتم العثور على الجلسة')
                            ->danger()
                            ->send();

                        return;
                    }

                    $scheduledAt = Carbon::parse($data['scheduled_at']);

                    // Update session
                    $record->update([
                        'scheduled_at' => $scheduledAt,
                        'description' => $data['description'] ?? $record->description,
                    ]);

                    // Also update the linked trial request if it exists (for Quran trial sessions)
                    if ($record instanceof QuranSession && $record->session_type === 'trial' && $record->trial_request_id) {
                        $trialRequest = \App\Models\QuranTrialRequest::find($record->trial_request_id);
                        if ($trialRequest) {
                            $trialRequest->update([
                                'scheduled_at' => $scheduledAt,
                                'teacher_response' => $data['description'] ?? $trialRequest->teacher_response,
                            ]);
                        }
                    }

                    Notification::make()
                        ->title('تم تحديث الجلسة بنجاح')
                        ->success()
                        ->send();

                    $this->dispatch('refresh');
                })
                ->extraModalFooterActions([
                    Action::make('view_full_edit')
                        ->label('عرض الصفحة الكاملة للتعديل')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('gray')
                        ->size('sm')
                        ->url(function (array $arguments) {
                            $eventId = $arguments['event']['id'] ?? null;
                            $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                            if (! $record) {
                                return '#';
                            }

                            return $this->getFullEditUrl($record);
                        })
                        ->openUrlInNewTab()
                        ->visible(function (array $arguments) {
                            $eventId = $arguments['event']['id'] ?? null;
                            $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                            if (! $record) {
                                return false;
                            }

                            return $this->canViewFullEdit($record);
                        }),
                ]),

            // Delete action - only visible for Quran sessions
            Actions\DeleteAction::make('deleteSession')
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalDescription('هل أنت متأكد من حذف هذه الجلسة؟ لن يمكن التراجع عن هذا الإجراء.')
                ->successNotificationTitle('تم حذف الجلسة بنجاح')
                ->visible(function (array $arguments): bool {
                    // Only show delete action for Quran sessions
                    $eventId = $arguments['event']['id'] ?? null;
                    if (! $eventId || ! str_starts_with($eventId, 'quran-')) {
                        return false;
                    }

                    $record = $this->resolveEventRecord($eventId);
                    if (! $record) {
                        return false;
                    }

                    return in_array($record->session_type, ['individual', 'group']) && Auth::id() === $record->quran_teacher_id;
                })
                ->before(function (array $arguments) {
                    $eventId = $arguments['event']['id'] ?? null;
                    $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                    if (! $record) {
                        return;
                    }

                    // Update the circle to recalculate available sessions
                    if ($record->individualCircle) {
                        $record->individualCircle->updateSessionCounts();
                    }
                }),

            // Action for viewing all sessions for a specific day
            Action::make('viewDaySessions')
                ->label('جلسات اليوم')
                ->icon('heroicon-o-calendar-days')
                ->modalHeading(fn () => 'جلسات يوم '.($this->selectedDate
                    ? Carbon::parse($this->selectedDate)->locale('ar')->translatedFormat('l، j F Y')
                    : ''))
                ->modalContent(fn () => view('filament.widgets.day-sessions-list', [
                    'sessions' => $this->daySessions,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('إغلاق')
                ->closeModalByClickingAway(true),
        ];
    }

    /**
     * Show all sessions for a specific day
     * Override in child classes to fetch specific session types
     *
     * Default implementation shows empty modal
     */
    public function showDaySessionsModal(string $dateStr): void
    {
        $this->selectedDate = $dateStr;
        $this->daySessions = [];
        $this->mountAction('viewDaySessions');
    }

    /**
     * Edit session from day modal
     */
    public function editSessionFromDayModal(string $eventId): void
    {
        $record = $this->resolveEventRecord($eventId);

        if (!$record) {
            Notification::make()
                ->title('خطأ')
                ->body('لم يتم العثور على الجلسة')
                ->danger()
                ->send();
            return;
        }

        $this->record = $record;
        $this->replaceMountedAction('editSession', ['event' => ['id' => $eventId]]);
    }

    /**
     * View action for displaying session details when clicking on calendar events
     * Uses ViewAction with proper infolist and status badges
     */
    protected function viewAction(): Action
    {
        return Actions\ViewAction::make()
            ->label('عرض التفاصيل')
            ->icon('heroicon-o-eye')
            ->modalHeading(function (array $arguments) {
                $eventId = $arguments['event']['id'] ?? null;
                $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                if (! $record) {
                    return 'تفاصيل الجلسة';
                }

                if ($record instanceof QuranSession && $record->session_type === 'trial') {
                    $studentName = $record->student->name ??
                                 $record->trialRequest->student_name ??
                                 'طالب تجريبي';

                    return "تفاصيل الجلسة التجريبية: {$studentName}";
                }

                return "تفاصيل الجلسة: {$record->title}";
            })
            ->infolist(function (array $arguments) {
                $eventId = $arguments['event']['id'] ?? null;
                $record = $eventId ? $this->resolveEventRecord($eventId) : null;

                if (! $record) {
                    return [];
                }

                $isQuranSession = $record instanceof QuranSession;
                $isTrial = $isQuranSession && $record->session_type === 'trial';

                if ($isTrial) {
                    return [
                        Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->state($record->scheduled_at)
                            ->dateTime()
                            ->timezone(AcademyContextService::getTimezone()),
                        Infolists\Components\TextEntry::make('description')
                            ->label('ملاحظات إضافية')
                            ->state($record->description)
                            ->placeholder('لا توجد ملاحظات'),
                    ];
                } else {
                    return [
                        Infolists\Components\TextEntry::make('title')
                            ->label('عنوان الجلسة')
                            ->state($record->title),
                        Infolists\Components\TextEntry::make('description')
                            ->label('وصف الجلسة')
                            ->state($record->description)
                            ->placeholder('لا يوجد وصف'),
                        Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->state($record->scheduled_at)
                            ->dateTime()
                            ->timezone(AcademyContextService::getTimezone()),
                        Infolists\Components\TextEntry::make('duration_minutes')
                            ->label('مدة الجلسة')
                            ->state(($record->duration_minutes ?? 60).' دقيقة'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('حالة الجلسة')
                            ->state($record->status)
                            ->badge()
                            ->color(function ($state): string {
                                if ($state instanceof \App\Enums\SessionStatus) {
                                    return $state->color();
                                }
                                $statusEnum = \App\Enums\SessionStatus::tryFrom($state);
                                return $statusEnum?->color() ?? 'gray';
                            })
                            ->formatStateUsing(function ($state): string {
                                if ($state instanceof \App\Enums\SessionStatus) {
                                    return $state->label();
                                }
                                $statusEnum = \App\Enums\SessionStatus::tryFrom($state);
                                return $statusEnum?->label() ?? $state;
                            }),
                    ];
                }
            })
            ->modalFooterActions(function (Action $action): array {
                // Get the record that was already resolved by the ViewAction
                $record = $this->record;

                if (! $record) {
                    return [$action->getModalCancelAction()];
                }

                $isQuranSession = $record instanceof QuranSession;
                $isCourseSession = $record instanceof InteractiveCourseSession;

                // Build URL for view full page
                $viewFullUrl = $this->getFullEditUrl($record);

                // Build event ID with prefix for editSession action
                if ($isQuranSession) {
                    $eventId = 'quran-'.$record->id;
                } elseif ($isCourseSession) {
                    $eventId = 'course-'.$record->id;
                } else {
                    $eventId = 'academic-'.$record->id;
                }

                $editButton = Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->action(function () use ($eventId) {
                        // Replace current view modal with edit modal
                        $this->replaceMountedAction('editSession', ['event' => ['id' => $eventId]]);
                    })
                    ->visible(function () use ($record, $isQuranSession, $isCourseSession) {
                        // Don't show edit button for passed sessions
                        $scheduledAt = $record->scheduled_at;
                        if ($scheduledAt && $scheduledAt < Carbon::now()) {
                            return false;
                        }

                        // Show edit for Quran sessions
                        if ($isQuranSession) {
                            return Auth::id() === $record->quran_teacher_id;
                        }

                        // Show edit for Interactive Course sessions
                        if ($isCourseSession) {
                            $user = Auth::user();
                            if (! $user->academicTeacherProfile) {
                                return false;
                            }

                            return $record->course && $record->course->assigned_teacher_id === $user->academicTeacherProfile->id;
                        }

                        // Show edit for Academic individual sessions
                        $user = Auth::user();
                        if (! $user->academicTeacherProfile) {
                            return false;
                        }

                        // Don't allow editing AcademicSession records linked to courses
                        if ($record->interactive_course_id) {
                            return false;
                        }

                        return $record->academic_teacher_id === $user->academicTeacherProfile->id;
                    });

                $viewFullButton = Action::make('view_full')
                    ->label('فتح الصفحة الكاملة')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url($viewFullUrl)
                    ->openUrlInNewTab();

                return [
                    $editButton,
                    $viewFullButton,
                    $action->getModalCancelAction(),
                ];
            });
    }

    /**
     * Refresh calendar events
     */
    public function refreshCalendar(): void
    {
        $this->dispatch('refresh');
    }

    /**
     * Check if event can be edited
     * Override in child classes for specific logic
     */
    protected function canEditEvent(string $eventId): bool
    {
        return true; // Default: all events editable
    }

    /**
     * Get full edit page URL for session
     * Handles all session types with proper routes
     */
    protected function getFullEditUrl(Model $record): string
    {
        $panelId = filament()->getCurrentPanel()->getId();

        // Quran sessions
        if ($record instanceof QuranSession) {
            if ($record->session_type === 'trial') {
                return route('filament.teacher.resources.quran-trial-requests.view', [
                    'tenant' => filament()->getTenant(),
                    'record' => $record->trial_request_id,
                ]);
            } else {
                return route('filament.teacher.resources.quran-sessions.view', [
                    'tenant' => filament()->getTenant(),
                    'record' => $record,
                ]);
            }
        }

        // Interactive Course sessions
        if ($record instanceof InteractiveCourseSession) {
            return route("filament.{$panelId}.resources.interactive-course-sessions.edit", [
                'tenant' => filament()->getTenant(),
                'record' => $record,
            ]);
        }

        // Academic sessions
        return route("filament.{$panelId}.resources.academic-sessions.edit", [
            'tenant' => filament()->getTenant(),
            'record' => $record,
        ]);
    }

    /**
     * Check if user can view full edit page
     * Handles permissions for all session types
     */
    protected function canViewFullEdit(Model $record): bool
    {
        $user = Auth::user();

        // Quran sessions - check teacher ownership
        if ($record instanceof QuranSession) {
            return Auth::id() === $record->quran_teacher_id;
        }

        // Course sessions - check assigned_teacher_id
        if ($record instanceof InteractiveCourseSession) {
            return $user->academicTeacherProfile
                && $record->course
                && $record->course->assigned_teacher_id === $user->academicTeacherProfile->id;
        }

        // Academic sessions - check academic_teacher_id
        return $user->academicTeacherProfile
            && $record->academic_teacher_id === $user->academicTeacherProfile->id;
    }
}
