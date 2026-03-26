<?php

namespace App\Filament\Shared\Actions;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Services\SessionTransitionService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Shared session status actions for Filament tables
 *
 * Provides consistent session lifecycle actions across all panels:
 * - Start session: SCHEDULED/READY → ONGOING
 * - Complete session: ONGOING → COMPLETED
 * - Cancel session: SCHEDULED/READY → CANCELLED
 * - Mark absent: For attendance tracking
 */
class SessionStatusActions
{
    /**
     * Get the start session action
     */
    public static function startSession(): Action
    {
        return Action::make('start_session')
            ->label('بدء الجلسة')
            ->icon('heroicon-o-play')
            ->color('success')
            ->visible(fn (Model $record): bool => static::canStart($record))
            ->action(function (Model $record) {
                if (method_exists($record, 'markAsOngoing')) {
                    $record->markAsOngoing();
                } else {
                    $record->update([
                        'status' => SessionStatus::ONGOING->value,
                        'started_at' => now(),
                    ]);
                }
            });
    }

    /**
     * Get the complete session action
     */
    public static function completeSession(): Action
    {
        return Action::make('complete_session')
            ->label('إنهاء الجلسة')
            ->icon('heroicon-o-check')
            ->color('success')
            ->visible(fn (Model $record): bool => static::isOngoing($record))
            ->action(function (Model $record) {
                if (method_exists($record, 'markAsCompleted')) {
                    $record->markAsCompleted();
                    // Note: markAsCompleted() now handles updateSubscriptionUsage() internally
                } else {
                    // Fallback for models without markAsCompleted() method
                    $updateData = [
                        'status' => SessionStatus::COMPLETED->value,
                        'ended_at' => now(),
                    ];

                    // Calculate actual duration if started_at exists
                    if ($record->started_at) {
                        $updateData['actual_duration_minutes'] = now()->diffInMinutes($record->started_at);
                    }

                    // Set attendance to attended for individual sessions
                    if ($record->session_type === 'individual' || ! isset($record->session_type)) {
                        $updateData['attendance_status'] = AttendanceStatus::ATTENDED->value;
                    }

                    $record->update($updateData);

                    // Update subscription usage if applicable
                    if (method_exists($record, 'updateSubscriptionUsage')) {
                        $record->updateSubscriptionUsage();
                    }
                }
            });
    }

    /**
     * Get the cancel session action
     */
    public static function cancelSession(?string $cancelledBy = null, ?string $role = 'admin'): Action
    {
        return Action::make('cancel_session')
            ->label('إلغاء الجلسة')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('إلغاء الجلسة')
            ->modalDescription('هل أنت متأكد من إلغاء هذه الجلسة؟ لا يمكن التراجع عن هذا الإجراء.')
            ->modalSubmitActionLabel('نعم، إلغاء الجلسة')
            ->visible(fn (Model $record): bool => static::isScheduledOrOngoing($record))
            ->action(function (Model $record) use ($role) {
                try {
                    $success = false;
                    $currentStatus = $record->status;
                    $statusValue = $currentStatus instanceof SessionStatus ? $currentStatus->value : $currentStatus;

                    Log::info('Attempting to cancel session', [
                        'session_id' => $record->id,
                        'session_class' => get_class($record),
                        'current_status' => $statusValue,
                        'role' => $role,
                        'user_id' => auth()->id(),
                    ]);

                    if (method_exists($record, 'markAsCancelled')) {
                        $reason = 'ألغيت بواسطة '.match ($role) {
                            'teacher' => 'المعلم',
                            'supervisor' => 'المشرف',
                            'admin' => 'المدير',
                            default => 'النظام',
                        };
                        $success = $record->markAsCancelled($reason, auth()->user(), $role);
                    } else {
                        $success = $record->update([
                            'status' => SessionStatus::CANCELLED->value,
                            'cancelled_at' => now(),
                            'cancelled_by' => auth()->id(),
                            'cancellation_type' => $role,
                        ]);
                    }

                    if (! $success) {
                        Log::warning('Session cancellation returned false', [
                            'session_id' => $record->id,
                            'session_class' => get_class($record),
                            'current_status' => $statusValue,
                            'can_cancel' => $currentStatus instanceof SessionStatus ? $currentStatus->canCancel() : 'unknown',
                        ]);

                        Notification::make()
                            ->title(__('meetings.cancel_error'))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('meetings.session_cancelled_success'))
                        ->success()
                        ->send();

                } catch (Exception $e) {
                    Log::error('Session cancellation failed', [
                        'session_id' => $record->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    Notification::make()
                        ->title(__('meetings.cancel_error'))
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Get the mark absent action for individual sessions
     */
    public static function markAbsent(): Action
    {
        return Action::make('mark_absent')
            ->label('تسجيل غياب')
            ->icon('heroicon-o-user-minus')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('تسجيل غياب الطالب')
            ->modalDescription('سيتم تسجيل غياب الطالب عن هذه الجلسة')
            ->modalSubmitActionLabel('تأكيد الغياب')
            ->visible(fn (Model $record): bool => static::canMarkAbsent($record) &&
                ($record->session_type === 'individual' || ! isset($record->session_type))
            )
            ->action(function (Model $record) {
                if (method_exists($record, 'markAsAbsent')) {
                    $record->markAsAbsent('غياب بدون إشعار');
                } else {
                    $record->update([
                        'status' => SessionStatus::COMPLETED->value,
                        'attendance_status' => AttendanceStatus::ABSENT->value,
                        'ended_at' => now(),
                    ]);

                    // Update subscription usage if applicable
                    if (method_exists($record, 'updateSubscriptionUsage')) {
                        $record->updateSubscriptionUsage();
                    }
                }
            });
    }

    /**
     * Get the forgive absent session action (admin only)
     * Transitions ABSENT → FORGIVEN, reversing subscription usage and deleting teacher earnings.
     */
    public static function forgiveSession(): Action
    {
        return Action::make('forgive_session')
            ->label(__('sessions.actions.forgive'))
            ->icon('heroicon-o-heart')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading(__('sessions.actions.forgive_heading'))
            ->modalDescription(__('sessions.actions.forgive_description'))
            ->modalSubmitActionLabel(__('sessions.actions.forgive_confirm'))
            ->visible(fn (Model $record): bool => static::canForgive($record))
            ->form([
                Textarea::make('forgiven_reason')
                    ->label(__('sessions.fields.forgiven_reason'))
                    ->required()
                    ->maxLength(500)
                    ->rows(3),
            ])
            ->action(function (Model $record, array $data) {
                try {
                    $transitionService = app(SessionTransitionService::class);
                    $success = $transitionService->transitionToForgiven(
                        $record,
                        $data['forgiven_reason'],
                        auth()->id()
                    );

                    if ($success) {
                        Notification::make()
                            ->title(__('sessions.actions.forgive_success'))
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('sessions.actions.forgive_error'))
                            ->danger()
                            ->send();
                    }
                } catch (Exception $e) {
                    Log::error('Session forgiveness failed', [
                        'session_id' => $record->id,
                        'error' => $e->getMessage(),
                    ]);
                    report($e);

                    Notification::make()
                        ->title(__('sessions.actions.forgive_error'))
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Get the join meeting action
     */
    public static function joinMeeting(): Action
    {
        return Action::make('join_meeting')
            ->label('دخول الاجتماع')
            ->icon('heroicon-o-video-camera')
            ->url(fn (Model $record): string => $record->meeting_link ?? '#')
            ->openUrlInNewTab()
            ->visible(fn (Model $record): bool => ! empty($record->meeting_link));
    }

    /**
     * Check if session can be started (SCHEDULED or READY)
     */
    protected static function canStart(Model $record): bool
    {
        $status = $record->status;

        if ($status instanceof SessionStatus) {
            return in_array($status, [SessionStatus::SCHEDULED, SessionStatus::READY]);
        }

        return in_array($status, [SessionStatus::SCHEDULED->value, SessionStatus::READY->value]);
    }

    /**
     * Check if session is ongoing
     */
    protected static function isOngoing(Model $record): bool
    {
        $status = $record->status;

        if ($status instanceof SessionStatus) {
            return $status === SessionStatus::ONGOING;
        }

        return $status === SessionStatus::ONGOING->value;
    }

    /**
     * Check if session can be marked as absent
     * Includes COMPLETED and ABSENT for teacher correction after the fact
     */
    protected static function canMarkAbsent(Model $record): bool
    {
        $status = $record->status;

        if ($status instanceof SessionStatus) {
            return in_array($status, [SessionStatus::SCHEDULED, SessionStatus::READY, SessionStatus::ONGOING, SessionStatus::COMPLETED, SessionStatus::ABSENT]);
        }

        return in_array($status, [
            SessionStatus::SCHEDULED->value,
            SessionStatus::READY->value,
            SessionStatus::ONGOING->value,
            SessionStatus::COMPLETED->value,
            SessionStatus::ABSENT->value,
        ]);
    }

    /**
     * Check if session can be forgiven (only ABSENT individual sessions)
     */
    protected static function canForgive(Model $record): bool
    {
        $status = $record->status instanceof SessionStatus
            ? $record->status
            : SessionStatus::from($record->status);

        if (! $status->canForgive()) {
            return false;
        }

        return $record->session_type === 'individual' || ! isset($record->session_type);
    }

    /**
     * Check if session is scheduled or ongoing
     */
    protected static function isScheduledOrOngoing(Model $record): bool
    {
        $status = $record->status;

        if ($status instanceof SessionStatus) {
            return in_array($status, [SessionStatus::SCHEDULED, SessionStatus::READY, SessionStatus::ONGOING]);
        }

        return in_array($status, [
            SessionStatus::SCHEDULED->value,
            SessionStatus::READY->value,
            SessionStatus::ONGOING->value,
        ]);
    }

    /**
     * Get all standard session actions as an array
     */
    public static function all(?string $role = 'admin'): array
    {
        return [
            static::startSession(),
            static::completeSession(),
            static::cancelSession(role: $role),
            static::markAbsent(),
            static::forgiveSession(),
            static::joinMeeting(),
        ];
    }

    /**
     * Get basic session actions (start, complete, join) as an array
     */
    public static function basic(): array
    {
        return [
            static::startSession(),
            static::completeSession(),
            static::joinMeeting(),
        ];
    }
}
