<?php

namespace App\Filament\Teacher\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Actions;
use App\Models\QuranSession;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TeacherCalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = QuranSession::class;

    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;

    // Properties for circle filtering
    public ?int $selectedCircleId = null;
    public ?string $selectedCircleType = null;

    public function config(): array
    {
        return [
            'firstDay' => 6, // Saturday start
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay'
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
        $teacherId = Auth::id();
        
        $query = QuranSession::query()
            ->where('quran_teacher_id', $teacherId)
            ->where('scheduled_at', '>=', $fetchInfo['start'])
            ->where('scheduled_at', '<=', $fetchInfo['end'])
            ->whereNotNull('scheduled_at');

        // Apply circle filtering if selected
        if ($this->selectedCircleId && $this->selectedCircleType) {
            if ($this->selectedCircleType === 'group') {
                $query->where('circle_id', $this->selectedCircleId);
            } elseif ($this->selectedCircleType === 'individual') {
                $query->where('individual_circle_id', $this->selectedCircleId);
            }
        }
        
        return $query
            ->with(['circle', 'individualCircle', 'individualCircle.subscription', 'individualCircle.subscription.package', 'student'])
            ->get()
            ->map(function (QuranSession $session) {
                // Determine if this is a group or individual session
                $isGroup = !empty($session->circle_id);
                $circle = $isGroup ? $session->circle : $session->individualCircle;
                
                if ($isGroup) {
                    $circleName = $circle ? $circle->name : 'حلقة محذوفة';
                    $title = "حلقة جماعية: {$circleName}";
                } else {
                    $studentName = $session->student->name ?? ($circle ? $circle->name : null) ?? 'حلقة فردية';
                    $title = "حلقة فردية: {$studentName}";
                }
                
                // Color coding based on session type and status
                $color = match($session->session_type) {
                    'group' => '#f59e0b', // amber for group
                    'individual' => '#10b981', // green for individual
                    default => '#6b7280' // gray default
                };

                if ($session->status === 'completed') {
                    $color = '#22c55e'; // green for completed
                } elseif ($session->status === 'cancelled') {
                    $color = '#ef4444'; // red for cancelled
                } elseif ($session->scheduled_at < now()) {
                    $color = '#f97316'; // orange for past due
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
                        'isMovable' => $session->session_type === 'individual', // Only individual sessions are movable
                    ]);

                // Note: Editability is controlled through the onEventDrop and onEventResize methods
                // Individual sessions can be moved and resized, group sessions cannot

                return $eventData;
            })
            ->toArray();
    }

    /**
     * Form schema for session editing
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
                ->visible(fn (?QuranSession $record) => $record && $record->session_type === 'individual')
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
                            if (!$value || !$record) return;
                            
                            $scheduledAt = Carbon::parse($value);
                            
                            // Check if date is beyond subscription end date
                            if ($record->individualCircle?->subscription?->ends_at) {
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
                    }
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
                ->modalHeading('تعديل بيانات الجلسة')
                ->modalSubmitActionLabel('حفظ التغييرات')
                ->modalCancelActionLabel('إلغاء')
                ->form($this->getFormSchema())
                ->mountUsing(function (QuranSession $record, Forms\Form $form, array $arguments) {
                    $data = [
                        'title' => $record->title,
                        'description' => $record->description,
                        'scheduled_at' => $record->scheduled_at,
                    ];
                    
                    $form->fill($data);
                })
                ->using(function (QuranSession $record, array $data): QuranSession {
                    // Basic fields that all sessions can update
                    $updateData = [
                        'title' => $data['title'],
                        'description' => $data['description'] ?? null,
                    ];
                    
                    // Add date/time fields for individual sessions only
                    if ($record->session_type === 'individual' && isset($data['scheduled_at'])) {
                        $newScheduledAt = Carbon::parse($data['scheduled_at']);
                        
                        // Only update the scheduled time, not the duration
                        // All validations are handled in the form rules above
                        $updateData['scheduled_at'] = $newScheduledAt;
                    }
                    
                    $record->update($updateData);
                    
                    // Refresh calendar to show updated data
                    $this->dispatch('refresh');
                    
                    return $record;
                })
                ->successNotificationTitle('تم تحديث بيانات الجلسة بنجاح')
                ->extraModalFooterActions([
                    Action::make('view_full_edit')
                        ->label('عرض الصفحة الكاملة للتعديل')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('gray')
                        ->size('sm')
                        ->url(function (QuranSession $record) {
                            return route('filament.teacher.resources.quran-sessions.edit', [
                                'tenant' => filament()->getTenant(),
                                'record' => $record
                            ]);
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
                ->visible(fn (QuranSession $record) => $record->session_type === 'individual' && Auth::id() === $record->quran_teacher_id)
                ->before(function (QuranSession $record) {
                    // Update the individual circle to recalculate available sessions
                    if ($record->individualCircle) {
                        $record->individualCircle->updateSessionCounts();
                    }
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
            ->modalHeading(fn (QuranSession $record) => "تفاصيل الجلسة: {$record->title}");
    }

    /**
     * Handle event drop (drag and drop)
     */
    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        // FIRST: Validate everything before allowing any visual changes
        $record = QuranSession::find($event['id']);
        if (!$record) {
            return false;
        }

        // Check if this is a group session - should not be movable
        if ($record->session_type === 'group') {
            Notification::make()
                ->title('غير مسموح')
                ->body('لا يمكن تحريك جلسات الحلقات الجماعية يدوياً. يتم إنشاؤها تلقائياً بناءً على الجدول المحدد.')
                ->warning()
                ->send();
            
            // Force calendar refresh to revert visual changes
            $this->dispatch('refresh');
            return false;
        }

        // Only individual sessions can be moved
        if ($record->session_type !== 'individual') {
            Notification::make()
                ->title('غير مسموح')
                ->body('يمكن تحريك الجلسات الفردية فقط.')
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
        if (!$record) {
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
        if (!in_array($newDuration, [30, 45, 60, 90])) {
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
            ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
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
}