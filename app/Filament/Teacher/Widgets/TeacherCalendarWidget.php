<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\QuranSession;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class TeacherCalendarWidget extends FullCalendarWidget
{
    public Model|string|null $model = QuranSession::class;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    // Properties for circle filtering
    public ?int $selectedCircleId = null;

    public ?string $selectedCircleType = null;

    // Event listeners
    protected $listeners = [
        'refresh-calendar' => 'refreshCalendar',
    ];

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
     * Fetch events for the calendar
     */
    public function fetchEvents(array $fetchInfo): array
    {
        $userId = Auth::id();
        if (! $userId) {
            return [];
        }

        // For sessions, we need to check user ID for both individual and group circles

        $query = QuranSession::query()
            ->where('quran_teacher_id', $userId) // Both individual and group circles use user ID
            ->where('scheduled_at', '>=', $fetchInfo['start'])
            ->where('scheduled_at', '<=', $fetchInfo['end'])
            ->whereNotNull('scheduled_at')
            ->whereNull('deleted_at'); // Ensure we don't show deleted sessions

        // Apply circle filtering if selected
        if ($this->selectedCircleId && $this->selectedCircleType) {
            if ($this->selectedCircleType === 'group') {
                $query->where('circle_id', $this->selectedCircleId);
            } elseif ($this->selectedCircleType === 'individual') {
                $query->where('individual_circle_id', $this->selectedCircleId);
            }
        }

        return $query
            ->with(['circle', 'individualCircle', 'individualCircle.subscription', 'individualCircle.subscription.package', 'student', 'trialRequest'])
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->get()
            ->map(function (QuranSession $session) {
                // Determine session type and details
                $sessionType = $session->session_type;
                $isPassed = $session->scheduled_at < now();

                if ($sessionType === 'trial') {
                    // Get student name from trial request or student record
                    $studentName = $session->student->name ??
                                 $session->trialRequest->student_name ??
                                 'طالب تجريبي';
                    $title = "جلسة تجريبية: {$studentName}";
                    $color = '#eab308'; // yellow-500 for trial sessions
                } elseif ($sessionType === 'group' || ! empty($session->circle_id)) {
                    $circle = $session->circle;
                    $circleName = $circle ? $circle->name : 'حلقة محذوفة';
                    $sessionNumber = $session->monthly_session_number ? "({$session->monthly_session_number})" : '';
                    $title = "حلقة جماعية: {$circleName} {$sessionNumber}";
                    $color = '#22c55e'; // green-500 for group circles
                } else {
                    // Individual session
                    $circle = $session->individualCircle;
                    $studentName = $session->student->name ?? ($circle ? $circle->name : null) ?? 'حلقة فردية';
                    $sessionNumber = $session->monthly_session_number ? "({$session->monthly_session_number})" : '';
                    $title = "حلقة فردية: {$studentName} {$sessionNumber}";
                    $color = '#6366f1'; // indigo-500 for individual circles
                }

                // Status-based color overrides (only for non-trial sessions)
                if ($sessionType !== 'trial') {
                    $color = match ($session->status) {
                        'cancelled' => '#ef4444', // red for cancelled
                        'in_progress' => '#3b82f6', // blue for in progress
                        default => $color // keep the type-based color for scheduled/completed
                    };
                }

                // Add strikethrough class for passed sessions
                $classNames = '';
                if ($isPassed && $session->status !== 'in_progress') {
                    $classNames = 'event-passed';
                }

                $eventData = EventData::make()
                    ->id($session->id)
                    ->title($title)
                    ->start($session->scheduled_at)
                    ->end($session->scheduled_at->addMinutes($session->duration_minutes))
                    ->backgroundColor($color)
                    ->borderColor($color)
                    ->textColor('#ffffff')
                    ->extendedProps([
                        'sessionType' => $session->session_type,
                        'status' => $session->status,
                        'circleId' => $session->circle_id,
                        'individualCircleId' => $session->individual_circle_id,
                        'studentId' => $session->student_id,
                        'duration' => $session->duration_minutes,
                        'monthlySessionNumber' => $session->monthly_session_number,
                        'sessionMonth' => $session->session_month,
                        'countsTowardSubscription' => $session->counts_toward_subscription,
                        'isMovable' => $session->session_type === 'individual', // Only individual sessions are movable
                        'isPassed' => $isPassed,
                        'classNames' => $classNames,
                    ]);

                // Note: CSS class for passed sessions is handled via JavaScript in the calendar view

                // Note: Editability is controlled through the onEventDrop and onEventResize methods
                // Individual sessions can be moved and resized, group sessions cannot

                return $eventData;
            })
            ->toArray();
    }

    /**
     * Form schema for regular session editing
     */
    public function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label('عنوان الجلسة')
                ->required()
                ->maxLength(255)
                ->default('جلسة قرآن كريم'),

            Forms\Components\Textarea::make('description')
                ->label('وصف الجلسة')
                ->rows(3)
                ->maxLength(500)
                ->placeholder('اكتب وصف أو ملاحظات حول الجلسة...')
                ->default('جلسة تحفيظ وتلاوة القرآن الكريم'),

            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label('موعد الجلسة')
                ->required()
                ->seconds(false)
                ->minutesStep(15)
                ->minDate(fn () => Carbon::now()->toDateString())
                ->maxDate(function (?QuranSession $record) {
                    if ($record && $record->session_type === 'individual' && $record->individualCircle?->subscription?->ends_at) {
                        return $record->individualCircle->subscription->ends_at->toDateString();
                    }

                    return Carbon::now()->addMonths(6)->toDateString();
                })
                ->native(false)
                ->displayFormat('Y-m-d H:i')
                ->timezone(config('app.timezone', 'UTC'))
                ->rules([
                    function (?QuranSession $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($record) {
                            if (! $value || ! $record) {
                                return;
                            }

                            $scheduledAt = Carbon::parse($value);

                            // Check if date is beyond subscription end date (for individual sessions only)
                            if ($record->session_type === 'individual' && $record->individualCircle?->subscription?->ends_at) {
                                $subscriptionEnd = $record->individualCircle->subscription->ends_at->endOfDay();
                                if ($scheduledAt->isAfter($subscriptionEnd)) {
                                    $endDate = $subscriptionEnd->format('Y-m-d');
                                    $fail("لا يمكن جدولة الجلسة بعد تاريخ انتهاء الاشتراك: {$endDate}");
                                }
                            }

                            // Check for session conflicts
                            $conflictData = [
                                'scheduled_at' => $scheduledAt,
                                'duration_minutes' => $record->duration_minutes ?? 60,
                                'quran_teacher_id' => Auth::id(),
                            ];

                            try {
                                $this->validateSessionConflicts($conflictData, $record->id);
                            } catch (\Exception $e) {
                                $fail($e->getMessage());
                            }
                        };
                    },
                ])
                ->helperText(function (?QuranSession $record) {
                    if ($record && $record->session_type === 'individual' && $record->individualCircle?->subscription?->ends_at) {
                        $endDate = $record->individualCircle->subscription->ends_at->format('Y-m-d');

                        return "يمكن جدولة الجلسة حتى تاريخ انتهاء الاشتراك: {$endDate}";
                    }

                    return 'اختر تاريخ ووقت الجلسة';
                })
                ->default(fn (?QuranSession $record) => $record?->scheduled_at),
        ];
    }

    /**
     * Form schema for trial session editing (date and notes only)
     */
    public function getTrialFormSchema(): array
    {
        return [
            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label('موعد الجلسة')
                ->required()
                ->seconds(false)
                ->minutesStep(15)
                ->minDate(fn () => Carbon::now()->toDateString())
                ->maxDate(fn () => Carbon::now()->addMonths(2)->toDateString())
                ->native(false)
                ->displayFormat('Y-m-d H:i')
                ->timezone(config('app.timezone', 'UTC'))
                ->helperText('اختر التاريخ والوقت الجديد للجلسة التجريبية')
                ->rules([
                    function (?QuranSession $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($record) {
                            if (! $value || ! $record) {
                                return;
                            }

                            $scheduledAt = Carbon::parse($value);

                            // Check if date is in the past
                            if ($scheduledAt->isPast()) {
                                $fail('لا يمكن جدولة الجلسة في وقت ماضي');
                            }

                            // Check for session conflicts
                            $conflictData = [
                                'scheduled_at' => $scheduledAt,
                                'duration_minutes' => 30, // Trial sessions are 30 minutes
                                'quran_teacher_id' => Auth::id(),
                            ];

                            try {
                                $this->validateSessionConflicts($conflictData, $record->id);
                            } catch (\Exception $e) {
                                $fail($e->getMessage());
                            }
                        };
                    },
                ])
                ->default(fn (?QuranSession $record) => $record?->scheduled_at),

            Forms\Components\Textarea::make('description')
                ->label('ملاحظات إضافية')
                ->rows(3)
                ->maxLength(500)
                ->placeholder('اكتب أي ملاحظات للطالب حول تعديل الموعد...')
                ->helperText('سيتم إرسال هذه الملاحظات مع إشعار تغيير الموعد'),
        ];
    }

    /**
     * Header actions for creating sessions - removed as scheduling is now done via circles section
     */
    protected function headerActions(): array
    {
        return [
            // No actions - scheduling is handled by the circles management section
        ];
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
                ->modalHeading(function (QuranSession $record) {
                    return $record->session_type === 'trial'
                        ? 'تعديل موعد الجلسة التجريبية'
                        : 'تعديل بيانات الجلسة';
                })
                ->modalSubmitActionLabel('حفظ التغييرات')
                ->modalCancelActionLabel('إلغاء')
                ->form(function (QuranSession $record) {
                    if ($record->session_type === 'trial') {
                        return [
                            Forms\Components\DateTimePicker::make('scheduled_at')
                                ->label('موعد الجلسة')
                                ->required()
                                ->seconds(false)
                                ->minutesStep(15)
                                ->minDate(fn () => Carbon::now()->toDateString())
                                ->maxDate(fn () => Carbon::now()->addMonths(2)->toDateString())
                                ->native(false)
                                ->displayFormat('Y-m-d H:i')
                                ->timezone(config('app.timezone', 'UTC'))
                                ->helperText('اختر التاريخ والوقت الجديد للجلسة التجريبية')
                                ->rules([
                                    function () use ($record) {
                                        return function (string $attribute, $value, \Closure $fail) use ($record) {
                                            if (! $value || ! $record) {
                                                return;
                                            }

                                            $scheduledAt = Carbon::parse($value);

                                            // Check if date is in the past
                                            if ($scheduledAt->isPast()) {
                                                $fail('لا يمكن جدولة الجلسة في وقت ماضي');
                                            }

                                            // Check for session conflicts
                                            $conflictData = [
                                                'scheduled_at' => $scheduledAt,
                                                'duration_minutes' => 30, // Trial sessions are 30 minutes
                                                'quran_teacher_id' => Auth::id(),
                                            ];

                                            try {
                                                $this->validateSessionConflicts($conflictData, $record->id);
                                            } catch (\Exception $e) {
                                                $fail($e->getMessage());
                                            }
                                        };
                                    },
                                ])
                                ->default($record->scheduled_at),

                            Forms\Components\Textarea::make('description')
                                ->label('ملاحظات إضافية')
                                ->rows(3)
                                ->maxLength(500)
                                ->placeholder('اكتب أي ملاحظات للطالب حول تعديل الموعد...')
                                ->helperText('سيتم إرسال هذه الملاحظات مع إشعار تغيير الموعد')
                                ->default($record->description),
                        ];
                    } else {
                        return $this->getFormSchema();
                    }
                })
                ->mountUsing(function (QuranSession $record, Forms\Form $form, array $arguments) {
                    if ($record->session_type === 'trial') {
                        // For trial sessions, only populate time and description
                        $data = [
                            'scheduled_at' => $record->scheduled_at,
                            'description' => $record->description,
                        ];
                    } else {
                        // For regular sessions, populate all fields
                        $data = [
                            'title' => $record->title,
                            'description' => $record->description,
                            'scheduled_at' => $record->scheduled_at,
                        ];
                    }

                    $form->fill($data);
                })
                ->using(function (QuranSession $record, array $data): QuranSession {
                    if ($record->session_type === 'trial') {
                        // For trial sessions, only update time and description
                        $updateData = [
                            'scheduled_at' => Carbon::parse($data['scheduled_at']),
                            'description' => $data['description'] ?? $record->description,
                        ];

                        // Also update the linked trial request if it exists
                        if ($record->trial_request_id) {
                            $trialRequest = \App\Models\QuranTrialRequest::find($record->trial_request_id);
                            if ($trialRequest) {
                                $trialRequest->update([
                                    'scheduled_at' => $updateData['scheduled_at'],
                                    'teacher_response' => $data['description'] ?? $trialRequest->teacher_response,
                                ]);
                            }
                        }
                    } else {
                        // For regular sessions, update all fields
                        $updateData = [
                            'title' => $data['title'],
                            'description' => $data['description'] ?? null,
                        ];

                        // Add date/time fields for individual and group sessions
                        if (in_array($record->session_type, ['individual', 'group']) && isset($data['scheduled_at'])) {
                            $updateData['scheduled_at'] = Carbon::parse($data['scheduled_at']);
                        }
                    }

                    $record->update($updateData);

                    // Refresh calendar to show updated data
                    $this->dispatch('refresh');

                    return $record;
                })
                ->successNotificationTitle(function (QuranSession $record) {
                    return $record->session_type === 'trial'
                        ? 'تم تحديث موعد الجلسة التجريبية بنجاح'
                        : 'تم تحديث بيانات الجلسة بنجاح';
                })
                ->extraModalFooterActions([
                    Action::make('view_full_edit')
                        ->label('عرض الصفحة الكاملة للتعديل')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('gray')
                        ->size('sm')
                        ->url(function (QuranSession $record) {
                            if ($record->session_type === 'trial') {
                                // For trial sessions, go to trial request resource
                                return route('filament.teacher.resources.quran-trial-requests.edit', [
                                    'tenant' => filament()->getTenant(),
                                    'record' => $record->trial_request_id,
                                ]);
                            } else {
                                // For regular sessions, go to session resource
                                return route('filament.teacher.resources.quran-sessions.edit', [
                                    'tenant' => filament()->getTenant(),
                                    'record' => $record,
                                ]);
                            }
                        })
                        ->openUrlInNewTab()
                        ->visible(fn (QuranSession $record) => Auth::id() === $record->quran_teacher_id),
                ]),

            Actions\DeleteAction::make('deleteSession')
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalDescription('هل أنت متأكد من حذف هذه الجلسة؟ لن يمكن التراجع عن هذا الإجراء.')
                ->successNotificationTitle('تم حذف الجلسة بنجاح')
                ->visible(fn (QuranSession $record) => in_array($record->session_type, ['individual', 'group']) && Auth::id() === $record->quran_teacher_id)
                ->before(function (QuranSession $record) {
                    // Update the circle to recalculate available sessions
                    if ($record->individualCircle) {
                        $record->individualCircle->updateSessionCounts();
                    }
                    // For group sessions, we don't need to update counts as they're tracked differently
                    // Group circles use the schedule-based system for session management
                }),
        ];
    }

    /**
     * View action for session details
     */
    protected function viewAction(): Action
    {
        return Actions\ViewAction::make('viewSession')
            ->label('عرض التفاصيل')
            ->icon('heroicon-o-eye')
            ->modalHeading(function (QuranSession $record) {
                if ($record->session_type === 'trial') {
                    $studentName = $record->student->name ??
                                 $record->trialRequest->student_name ??
                                 'طالب تجريبي';

                    return "تفاصيل الجلسة التجريبية: {$studentName}";
                }

                return "تفاصيل الجلسة: {$record->title}";
            })
            ->infolist(function (QuranSession $record) {
                if ($record->session_type === 'trial') {
                    // Trial session infolist - same fields as edit form but read-only
                    return [
                        \Filament\Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->dateTime()
                            ->timezone(config('app.timezone', 'UTC')),

                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->label('ملاحظات إضافية')
                            ->placeholder('لا توجد ملاحظات'),
                    ];
                } else {
                    // Regular session infolist - show all fields
                    return [
                        \Filament\Infolists\Components\TextEntry::make('title')
                            ->label('عنوان الجلسة'),

                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->label('وصف الجلسة')
                            ->placeholder('لا يوجد وصف'),

                        \Filament\Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('موعد الجلسة')
                            ->dateTime()
                            ->timezone(config('app.timezone', 'UTC')),

                        \Filament\Infolists\Components\TextEntry::make('duration_minutes')
                            ->label('مدة الجلسة')
                            ->formatStateUsing(fn ($state) => ($state ?? 60).' دقيقة'),

                        \Filament\Infolists\Components\TextEntry::make('status')
                            ->label('حالة الجلسة')
                            ->badge()
                            ->color(fn ($state): string => match ($state instanceof \App\Enums\SessionStatus ? $state->value : $state) {
                                'unscheduled' => 'gray',
                                'scheduled' => 'warning',
                                'ready' => 'info',
                                'ongoing' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                'absent' => 'warning',
                                'teacher_absent' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\SessionStatus ? $state->value : $state) {
                                'unscheduled' => 'غير مجدولة',
                                'scheduled' => 'مجدولة',
                                'ready' => 'جاهزة للبدء',
                                'ongoing' => 'جارية',
                                'completed' => 'مكتملة',
                                'cancelled' => 'ملغية',
                                'absent' => 'غياب الطالب',
                                'teacher_absent' => 'غياب المعلم',
                                default => $state instanceof \App\Enums\SessionStatus ? $state->value : $state,
                            }),
                    ];
                }
            });
    }

    /**
     * Handle event drop (drag and drop)
     */
    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        // FIRST: Validate everything before allowing any visual changes
        $record = QuranSession::find($event['id']);
        if (! $record) {
            return false;
        }

        // Both individual and group sessions can now be moved
        if (! in_array($record->session_type, ['individual', 'group'])) {
            Notification::make()
                ->title('غير مسموح')
                ->body('نوع الجلسة غير مدعوم للتحريك.')
                ->warning()
                ->send();

            // Force calendar refresh to revert visual changes
            $this->dispatch('refresh');

            return false;
        }

        $newStart = Carbon::parse($event['start']);
        $newEnd = Carbon::parse($event['end']);

        // Validate the new date is not in the past
        if ($newStart->isPast()) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن جدولة الجلسات في الماضي. يرجى اختيار تاريخ ووقت مستقبلي.')
                ->warning()
                ->send();

            // Force calendar refresh to revert visual changes
            $this->dispatch('refresh');

            return false;
        }

        // Validate the new time doesn't conflict
        try {
            $conflictData = [
                'scheduled_at' => $newStart,
                'duration_minutes' => $newStart->diffInMinutes($newEnd),
                'quran_teacher_id' => Auth::id(),
            ];

            $this->validateSessionConflicts($conflictData, $record->id);
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();

            // Force calendar refresh to revert visual changes
            $this->dispatch('refresh');

            return false;
        }

        // If we reach here, all validations passed
        try {
            // Update the record
            $record->update([
                'scheduled_at' => $newStart,
                'duration_minutes' => $newStart->diffInMinutes($newEnd),
            ]);

            // Now call parent to allow the visual change
            $result = parent::onEventDrop($event, $oldEvent, $relatedEvents, $delta, $oldResource, $newResource);

            Notification::make()
                ->title('تم تحديث موعد الجلسة بنجاح')
                ->body('تم تحديث موعد الجلسة الفردية بنجاح')
                ->success()
                ->send();

            return $result;

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();

            // Force calendar refresh to revert visual changes
            $this->dispatch('refresh');

            return false;
        }
    }

    /**
     * Handle event resize
     */
    public function onEventResize(array $event, array $oldEvent, array $relatedEvents, array $endDelta, array $startDelta): bool
    {
        // FIRST: Validate everything before allowing any visual changes
        $record = QuranSession::find($event['id']);
        if (! $record) {
            return false;
        }

        // Check if this is a group session - should not be resizable
        if ($record->session_type === 'group') {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن تغيير مدة جلسات الحلقات الجماعية يدوياً.')
                ->warning()
                ->send();

            // Force calendar refresh to revert visual changes
            $this->dispatch('refresh');

            return false;
        }

        // Only individual sessions can be resized
        if ($record->session_type !== 'individual') {
            Notification::make()
                ->title('غير مسموح')
                ->body('يمكن تغيير مدة الجلسات الفردية فقط.')
                ->warning()
                ->send();

            // Force calendar refresh to revert visual changes
            $this->dispatch('refresh');

            return false;
        }

        $newStart = Carbon::parse($event['start']);
        $newEnd = Carbon::parse($event['end']);
        $newDuration = $newStart->diffInMinutes($newEnd);

        // Validate the new date is not in the past
        if ($newStart->isPast()) {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن تغيير مدة الجلسات في الماضي.')
                ->warning()
                ->send();

            // Force calendar refresh to revert visual changes
            $this->dispatch('refresh');

            return false;
        }

        // Validate duration is acceptable
        if (! in_array($newDuration, [30, 45, 60, 90])) {
            Notification::make()
                ->title('خطأ في المدة')
                ->body('مدة الجلسة يجب أن تكون 30، 45، 60، أو 90 دقيقة')
                ->danger()
                ->send();

            // Force calendar refresh to revert visual changes
            $this->dispatch('refresh');

            return false;
        }

        // Validate no conflicts with new duration
        try {
            $conflictData = [
                'scheduled_at' => $newStart,
                'duration_minutes' => $newDuration,
                'quran_teacher_id' => Auth::id(),
            ];

            $this->validateSessionConflicts($conflictData, $record->id);
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();

            // Force calendar refresh to revert visual changes
            $this->dispatch('refresh');

            return false;
        }

        // If we reach here, all validations passed
        try {
            // Update duration
            $record->update([
                'duration_minutes' => $newDuration,
            ]);

            // Now call parent to allow the visual change
            $result = parent::onEventResize($event, $oldEvent, $relatedEvents, $endDelta, $startDelta);

            Notification::make()
                ->title('تم تحديث مدة الجلسة بنجاح')
                ->body('تم تحديث مدة الجلسة الفردية بنجاح')
                ->success()
                ->send();

            return $result;

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في تحديث الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();

            // Force calendar refresh to revert visual changes
            $this->dispatch('refresh');

            return false;
        }
    }

    /**
     * Validate session conflicts
     */
    protected function validateSessionConflicts(array $data, ?int $excludeId = null): void
    {
        $scheduledAt = Carbon::parse($data['scheduled_at']);
        $duration = $data['duration_minutes'];
        $teacherId = $data['quran_teacher_id'] ?? Auth::id();

        $endTime = $scheduledAt->copy()->addMinutes($duration);

        // Check for conflicts with existing sessions
        $conflicts = QuranSession::where('quran_teacher_id', $teacherId)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->where(function ($query) use ($scheduledAt, $endTime) {
                $query->where(function ($q) use ($scheduledAt, $endTime) {
                    // New session starts during existing session
                    $q->whereRaw('? BETWEEN scheduled_at AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)', [$scheduledAt])
                      // New session ends during existing session
                        ->orWhereRaw('? BETWEEN scheduled_at AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)', [$endTime])
                      // New session completely contains existing session
                        ->orWhere(function ($subQ) use ($scheduledAt, $endTime) {
                            $subQ->where('scheduled_at', '>=', $scheduledAt)
                                ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) <= ?', [$endTime]);
                        });
                });
            })
            ->first();

        if ($conflicts) {
            $conflictTime = $conflicts->scheduled_at->format('Y/m/d H:i');
            throw new \Exception("يوجد تعارض مع جلسة أخرى في {$conflictTime}. المعلم لا يمكنه أن يكون في مكانين في نفس الوقت!");
        }

        // Check if trying to schedule in the past
        if ($scheduledAt < now()) {
            throw new \Exception('لا يمكن جدولة جلسة في وقت ماضي');
        }
    }

    /**
     * Handle calendar refresh event
     */
    public function refreshCalendar(): void
    {
        // Force the calendar to refresh its events
        $this->dispatch('refresh');
    }
}
