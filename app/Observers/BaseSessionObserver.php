<?php

namespace App\Observers;

use App\Enums\NotificationType;
use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Jobs\CalculateSessionEarningsJob;
use App\Models\BaseSession;
use App\Models\User;
use App\Services\Notification\NotificationUrlBuilder;
use App\Services\NotificationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * BaseSession Observer
 *
 * Automatically handles meeting creation when session status changes.
 * This ensures meetings are created immediately without waiting for cron jobs.
 */
class BaseSessionObserver
{
    /**
     * Handle the BaseSession "updating" event.
     * This runs BEFORE the model is saved to the database.
     */
    public function updating(BaseSession $session): void
    {
        // Check if status is changing to ready or ongoing
        if ($session->isDirty('status')) {
            $newStatus = $session->status;
            $oldStatus = $session->getOriginal('status');

            // Convert to enum if string
            if (is_string($newStatus)) {
                $newStatus = SessionStatus::from($newStatus);
            }
            if (is_string($oldStatus)) {
                $oldStatus = SessionStatus::from($oldStatus);
            }

            // If status is changing to ready or ongoing, ensure meeting exists
            if (in_array($newStatus, [SessionStatus::READY, SessionStatus::ONGOING])) {
                // Check if meeting room doesn't exist yet
                if (empty($session->meeting_room_name)) {
                    try {
                        Log::info('Auto-creating meeting room for session', [
                            'session_id' => $session->id,
                            'session_type' => $session->getMeetingType(),
                        ]);

                        // Generate meeting link (this creates the LiveKit room)
                        $session->generateMeetingLink();

                        Log::info('Meeting room created successfully', [
                            'session_id' => $session->id,
                            'room_name' => $session->meeting_room_name,
                        ]);
                    } catch (Exception $e) {
                        Log::error('Failed to auto-create meeting room', [
                            'session_id' => $session->id,
                            'error' => $e->getMessage(),
                        ]);
                        report($e); // BP-003: Report to error tracker

                        // Don't throw - let the status update proceed
                        // The cron job will retry meeting creation later
                    }
                } else {
                    Log::debug('Meeting room already exists for session', [
                        'session_id' => $session->id,
                        'room_name' => $session->meeting_room_name,
                    ]);
                }
            }
        }
    }

    /**
     * Handle the BaseSession "updated" event.
     * This runs AFTER the model is saved to the database.
     */
    public function updated(BaseSession $session): void
    {
        // CRITICAL: Regenerate meeting when session is rescheduled
        // This ensures the meeting data stays in sync with the session time
        if ($session->wasChanged('scheduled_at')) {
            $oldTime = $session->getOriginal('scheduled_at');
            $newTime = $session->scheduled_at;

            Log::info('Session rescheduled - checking if meeting needs update', [
                'session_id' => $session->id,
                'session_type' => $session->getMeetingType(),
                'has_meeting' => ! empty($session->meeting_room_name),
            ]);

            // If meeting exists, regenerate it with new time
            if (! empty($session->meeting_room_name)) {
                try {
                    // Clear old meeting data first
                    $session->meeting_room_name = null;
                    $session->meeting_link = null;
                    $session->meeting_id = null;
                    $session->meeting_data = null;
                    $session->meeting_expires_at = null;

                    // Generate new meeting with updated time
                    $session->generateMeetingLink();

                    // Save without triggering observer again
                    $session->saveQuietly();

                    Log::info('Meeting regenerated successfully for rescheduled session', [
                        'session_id' => $session->id,
                        'new_room' => $session->meeting_room_name,
                    ]);
                } catch (Exception $e) {
                    Log::error('Failed to regenerate meeting for rescheduled session', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                    report($e);
                }
            }

            // Send rescheduled notification to participants
            $this->notifySessionRescheduled($session, $oldTime, $newTime);
        }

        // Log status changes for debugging
        if ($session->wasChanged('status')) {
            $oldStatus = $session->getOriginal('status');
            $newStatus = $session->status;

            Log::info('Session status changed', [
                'session_id' => $session->id,
                'session_type' => $session->getMeetingType(),
                'old_status' => is_string($oldStatus) ? $oldStatus : $oldStatus->value,
                'new_status' => is_string($newStatus) ? $newStatus : $newStatus->value,
            ]);

            // Trigger earnings calculation when session becomes completed
            $newStatusEnum = is_string($newStatus) ? SessionStatus::from($newStatus) : $newStatus;

            if ($newStatusEnum === SessionStatus::COMPLETED) {
                try {
                    Log::info('Dispatching earnings calculation job', [
                        'session_id' => $session->id,
                    ]);

                    dispatch(new CalculateSessionEarningsJob($session));
                } catch (Exception $e) {
                    Log::error('Failed to dispatch earnings calculation job', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                    report($e);
                }
            }

            // Notify admin when a session is cancelled
            if ($newStatusEnum === SessionStatus::CANCELLED) {
                $this->notifyAdminSessionCancelled($session);
            }
        }
    }

    /**
     * Send rescheduled notification to session participants.
     */
    private function notifySessionRescheduled(BaseSession $session, $oldTime, $newTime): void
    {
        try {
            $notificationService = app(NotificationService::class);
            $urlBuilder = app(NotificationUrlBuilder::class);

            $data = [
                'session_title' => $session->title ?? class_basename($session),
                'session_type' => class_basename($session),
                'old_time' => $oldTime ? Carbon::parse($oldTime)->format('Y-m-d H:i') : '',
                'new_time' => $newTime ? Carbon::parse($newTime)->format('Y-m-d H:i') : '',
            ];

            $metadata = ['session_id' => $session->id, 'session_type' => get_class($session)];

            // Individual sessions (Quran/Academic) have a direct student relationship
            if (method_exists($session, 'student') && $session->student) {
                $student = $session->student;

                if ($student instanceof User) {
                    $actionUrl = $urlBuilder->getSessionUrl($session, $student);
                    $notificationService->send($student, NotificationType::SESSION_RESCHEDULED, $data, $actionUrl, $metadata, true);
                } elseif (method_exists($student, 'user') && $student->user) {
                    // StudentProfile model - get the user
                    $actionUrl = $urlBuilder->getSessionUrl($session, $student->user);
                    $notificationService->send($student->user, NotificationType::SESSION_RESCHEDULED, $data, $actionUrl, $metadata, true);
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to send session rescheduled notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify academy admins when a session is cancelled.
     */
    private function notifyAdminSessionCancelled(BaseSession $session): void
    {
        try {
            $notificationService = app(NotificationService::class);
            $urlBuilder = app(NotificationUrlBuilder::class);

            $academyId = $session->academy_id;
            if (! $academyId) {
                return;
            }

            // Find academy admins
            $admins = User::where('academy_id', $academyId)
                ->where('user_type', UserType::ADMIN->value)
                ->get();

            if ($admins->isEmpty()) {
                return;
            }

            $data = [
                'session_type' => class_basename($session),
                'session_id' => $session->id,
                'cancellation_reason' => $session->cancellation_reason ?? '',
            ];

            foreach ($admins as $admin) {
                $actionUrl = $urlBuilder->getSessionUrl($session, $admin);
                $notificationService->send(
                    $admin,
                    NotificationType::TEACHER_SESSION_CANCELLED,
                    $data,
                    $actionUrl,
                    ['session_id' => $session->id],
                    true
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send session cancelled admin notification', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the BaseSession "created" event.
     */
    public function created(BaseSession $session): void
    {
        // If session is created with ready/ongoing status, create meeting immediately
        $status = is_string($session->status)
            ? SessionStatus::from($session->status)
            : $session->status;

        if (in_array($status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            if (empty($session->meeting_room_name)) {
                try {
                    // CONC-001: Use transaction with lock to prevent duplicate meeting creation
                    \Illuminate\Support\Facades\DB::transaction(function () use ($session) {
                        $freshSession = $session->fresh();
                        if ($freshSession && empty($freshSession->meeting_room_name)) {
                            Log::info('Auto-creating meeting for newly created ready/ongoing session', [
                                'session_id' => $freshSession->id,
                                'status' => $freshSession->status instanceof SessionStatus ? $freshSession->status->value : $freshSession->status,
                            ]);

                            $freshSession->generateMeetingLink();
                            $freshSession->save();

                            // Sync back to original model
                            $session->meeting_room_name = $freshSession->meeting_room_name;
                            $session->meeting_link = $freshSession->meeting_link;

                            Log::info('Meeting created for new session', [
                                'session_id' => $freshSession->id,
                                'room_name' => $freshSession->meeting_room_name,
                            ]);
                        }
                    });
                } catch (Exception $e) {
                    Log::error('Failed to create meeting for new session', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                    report($e);
                }
            }
        }
    }
}
