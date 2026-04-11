<?php

namespace App\Filament\Shared\Actions;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Services\SessionCountingService;
use Exception;
use Filament\Actions\Action;
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
     * Toggle whether a completed session counts for teacher earnings.
     */
    public static function toggleCountsForTeacher(): Action
    {
        return Action::make('toggle_counts_teacher')
            ->label(fn (Model $record) => $record->counts_for_teacher
                ? __('settings.counts_for_teacher').': ✓'
                : __('settings.counts_for_teacher').': ✗')
            ->icon('heroicon-o-calculator')
            ->color(fn (Model $record) => $record->counts_for_teacher ? 'success' : 'danger')
            ->requiresConfirmation()
            ->action(function (Model $record) {
                $newValue = ! $record->counts_for_teacher;
                app(SessionCountingService::class)->setCountsForTeacher(
                    $record,
                    $newValue,
                    auth()->id()
                );

                Notification::make()
                    ->title($newValue
                        ? __('settings.counts_for_teacher').' ✓'
                        : __('settings.counts_for_teacher').' ✗')
                    ->success()
                    ->send();
            })
            ->visible(fn (Model $record) => $record->status === SessionStatus::COMPLETED);
    }

    /**
     * Toggle whether a completed session counts for student subscription.
     *
     * Note: This action is intended for individual sessions (QuranSession, AcademicSession)
     * where a single student attendance record can be toggled. For group sessions,
     * use per-attendance toggles in the attendance table instead.
     */
    public static function toggleCountsForSubscription(): Action
    {
        // Helper: get the student's MeetingAttendance row (single source of truth).
        $getStudentAttendance = fn (Model $record) => $record->meetingAttendances()
            ->where('user_type', 'student')
            ->first();

        return Action::make('toggle_counts_subscription')
            ->label(function (Model $record) use ($getStudentAttendance) {
                $attendance = $getStudentAttendance($record);
                // Fall back to the session-level flag when the attendance flag is NULL,
                // matching the supervisor row/card/show view semantics.
                $counts = $attendance?->counts_for_subscription
                    ?? (bool) ($record->subscription_counted ?? false);

                return $counts
                    ? __('settings.counts_for_subscription').': ✓'
                    : __('settings.counts_for_subscription').': ✗';
            })
            ->icon('heroicon-o-academic-cap')
            ->color(function (Model $record) use ($getStudentAttendance) {
                $attendance = $getStudentAttendance($record);
                $counts = $attendance?->counts_for_subscription
                    ?? (bool) ($record->subscription_counted ?? false);

                return $counts ? 'success' : 'danger';
            })
            ->requiresConfirmation()
            ->action(function (Model $record) use ($getStudentAttendance) {
                $attendance = $getStudentAttendance($record);
                if (! $attendance) {
                    Notification::make()
                        ->title(__('supervisor.observation.session_not_found'))
                        ->danger()
                        ->send();

                    return;
                }

                $newValue = ! ($attendance->counts_for_subscription
                    ?? (bool) ($record->subscription_counted ?? false));

                app(SessionCountingService::class)->setCountsForSubscription(
                    $attendance,
                    $record,
                    $newValue,
                    auth()->id()
                );

                Notification::make()
                    ->title($newValue
                        ? __('settings.counts_for_subscription').' ✓'
                        : __('settings.counts_for_subscription').' ✗')
                    ->success()
                    ->send();
            })
            ->visible(fn (Model $record) => $record->status === SessionStatus::COMPLETED);
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
        $actions = [
            static::startSession(),
            static::completeSession(),
            static::cancelSession(role: $role),
            static::joinMeeting(),
        ];

        if ($role === 'admin') {
            $actions[] = static::toggleCountsForTeacher();
            $actions[] = static::toggleCountsForSubscription();
        }

        return $actions;
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
