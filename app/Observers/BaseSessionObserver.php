<?php

namespace App\Observers;

use App\Enums\NotificationType;
use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Jobs\CalculateSessionEarningsJob;
use App\Jobs\CalculateSessionForAttendance;
use App\Jobs\CreateSessionMeetingJob;
use App\Models\BaseSession;
use App\Models\MeetingAttendance;
use App\Models\User;
use App\Services\Notification\NotificationUrlBuilder;
use App\Services\NotificationService;
use App\Services\SessionTransitionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * BaseSession Observer
 *
 * - Enforces subscription schedulability as a model-level guard
 *   for every session create path (teacher UI, supervisor UI, services,
 *   auto-schedule, etc.). Sessions created via `AcademicSession::withoutEvents()`
 *   (cycle bootstrap from SubscriptionRenewalService / activateFromPayment)
 *   bypass this guard by design.
 * - Handles meeting creation when session status changes.
 * - Handles reschedule notifications, attendance bootstrapping, and
 *   post-completion earnings jobs.
 */
class BaseSessionObserver
{
    /**
     * Handle the BaseSession "creating" event — subscription schedulability guard.
     *
     * Sessions should never be created against a subscription that is not
     * schedulable right now (status !== ACTIVE, or payment pending without grace).
     * This observer is the catch-all net for the ~6 direct-`create()` call sites
     * so that no new code path can accidentally bypass the validator layer.
     *
     * Bypassed automatically when the session is created inside a
     * `Model::withoutEvents()` block (used by the Academic lesson bootstrap
     * that pre-allocates UNSCHEDULED sessions for a new cycle).
     */
    public function creating(BaseSession $session): void
    {
        $subscription = $this->resolveSubscriptionFor($session);

        if ($subscription === null) {
            // Group sessions, trial sessions, and interactive course sessions
            // don't have a per-student subscription — those paths have their
            // own validators.
            return;
        }

        if (! $subscription->isSchedulable()) {
            throw \App\Exceptions\SubscriptionException::notSchedulable($subscription->id);
        }
    }

    /**
     * Resolve the subscription linked to a session, if any.
     * Returns null for group/trial/interactive-course sessions.
     *
     * Prefers eager-loaded relations (via `$session->relationLoaded(...)`) to
     * avoid N+1 queries in bulk session creation paths. Falls back to a
     * targeted fetch when no relation is loaded.
     */
    private function resolveSubscriptionFor(BaseSession $session): ?\App\Models\BaseSubscription
    {
        if ($session instanceof \App\Models\QuranSession) {
            if ($session->quran_subscription_id) {
                return $session->relationLoaded('quranSubscription')
                    ? $session->quranSubscription
                    : \App\Models\QuranSubscription::find($session->quran_subscription_id);
            }

            if ($session->individual_circle_id) {
                $circle = $session->relationLoaded('individualCircle')
                    ? $session->individualCircle
                    : \App\Models\QuranIndividualCircle::with([
                        'linkedSubscriptions',
                        'linkedSubscriptions.currentCycle',
                        'subscription',
                        'subscription.currentCycle',
                    ])->find($session->individual_circle_id);

                return $circle?->activeSubscription ?? $circle?->subscription;
            }

            return null;
        }

        if ($session instanceof \App\Models\AcademicSession) {
            if ($session->academic_subscription_id) {
                return $session->relationLoaded('academicSubscription')
                    ? $session->academicSubscription
                    : \App\Models\AcademicSubscription::find($session->academic_subscription_id);
            }

            return null;
        }

        return null;
    }

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
                // Dispatch a job to create the meeting room asynchronously.
                // This avoids blocking the HTTP request with a synchronous LiveKit API call.
                // The 'meetings' queue worker handles this with retries; the cron job also
                // creates meetings proactively so the room should usually already exist.
                if (empty($session->meeting_room_name)) {
                    dispatch(new CreateSessionMeetingJob(get_class($session), $session->id));

                    Log::info('Dispatched CreateSessionMeetingJob for session', [
                        'session_id' => $session->id,
                        'session_type' => $session->getMeetingType(),
                    ]);
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

            // If session is READY but rescheduled to a future date beyond the preparation
            // window, reset it back to SCHEDULED. Without this, a session rescheduled from
            // today to next week would stay in "ready" status indefinitely.
            if ($session->status === SessionStatus::READY && $newTime) {
                $preparationMinutes = $session->getPreparationMinutes();
                $preparationTime = $newTime->copy()->subMinutes($preparationMinutes);

                if (now()->lt($preparationTime)) {
                    $session->updateQuietly(['status' => SessionStatus::SCHEDULED]);

                    Log::info('Reset session status to SCHEDULED after reschedule to future date', [
                        'session_id' => $session->id,
                        'new_scheduled_at' => $newTime->toIso8601String(),
                        'preparation_time' => $preparationTime->toIso8601String(),
                    ]);
                }
            }

            // If meeting exists, dispatch a job to regenerate it with the new time.
            // Using a job avoids blocking the HTTP request with a synchronous LiveKit call.
            if (! empty($session->meeting_room_name)) {
                dispatch(new CreateSessionMeetingJob(get_class($session), $session->id, regenerate: true));

                Log::info('Dispatched CreateSessionMeetingJob (regenerate) for rescheduled session', [
                    'session_id' => $session->id,
                ]);
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

            $newStatusEnum = is_string($newStatus) ? SessionStatus::from($newStatus) : $newStatus;
            $oldStatusEnum = is_string($oldStatus) ? SessionStatus::tryFrom($oldStatus) : $oldStatus;

            // Fires on the first entry into the active window, including no-show
            // jumps SCHEDULED→COMPLETED that skip ONGOING. Skipped on the happy-path
            // ONGOING→COMPLETED transition so we don't re-upsert the same rows.
            $enteringActiveWindow = $newStatusEnum === SessionStatus::ONGOING
                || ($newStatusEnum === SessionStatus::COMPLETED && ! in_array($oldStatusEnum, [SessionStatus::ONGOING, SessionStatus::COMPLETED], true));

            if ($enteringActiveWindow) {
                try {
                    app(SessionTransitionService::class)->preCreateAttendanceRows($session);
                } catch (Exception $e) {
                    Log::warning('preCreateAttendanceRows failed in observer', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($newStatusEnum === SessionStatus::COMPLETED) {
                try {
                    // Full attendance + counting pipeline. Dispatch with delay so
                    // the earnings job sees counts_for_teacher already set; the
                    // job's ShouldBeUnique prevents duplicates when the LiveKit
                    // webhook also dispatches this.
                    dispatch(new CalculateSessionForAttendance($session->id, get_class($session)))
                        ->delay(now()->addMinutes(5));

                    // Earnings backup — isAlreadyCalculated() guards against duplicates.
                    dispatch(new CalculateSessionEarningsJob($session))
                        ->delay(now()->addMinutes(10));
                } catch (Exception $e) {
                    Log::error('Failed to dispatch session completion jobs', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                    report($e);
                }
            }

            if ($newStatusEnum === SessionStatus::CANCELLED) {
                $this->notifyAdminSessionCancelled($session);
            }
        }

        // Clean up stale teacher attendance when teacher is reassigned.
        // Only removes empty rows (no real attendance data) so a teacher
        // who actually taught keeps their record for historical accuracy.
        $this->cleanupStaleTeacherAttendance($session);
    }

    /**
     * Remove the old teacher's empty MeetingAttendance row when the session's
     * teacher_id changes (reassignment). Preserves rows with real join data.
     */
    private function cleanupStaleTeacherAttendance(BaseSession $session): void
    {
        $oldTeacherId = null;

        if ($session instanceof \App\Models\QuranSession && $session->wasChanged('quran_teacher_id')) {
            $oldTeacherId = $session->getOriginal('quran_teacher_id');
        } elseif ($session instanceof \App\Models\AcademicSession && $session->wasChanged('academic_teacher_id')) {
            $oldTeacherId = $session->academicTeacher?->getOriginal('user_id')
                ?? $session->getOriginal('academic_teacher_id');
        }

        if (! $oldTeacherId) {
            return;
        }

        $deleted = MeetingAttendance::where('session_id', $session->id)
            ->whereIn('user_type', MeetingAttendance::TEACHER_USER_TYPES)
            ->where('user_id', $oldTeacherId)
            ->where('total_duration_minutes', 0)
            ->delete();

        if ($deleted > 0) {
            Log::info('Cleaned up stale teacher attendance after reassignment', [
                'session_id' => $session->id,
                'old_teacher_id' => $oldTeacherId,
            ]);
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
                    // CONC-001: Use transaction with lockForUpdate to prevent duplicate meeting creation
                    // Two concurrent requests may both see meeting_room_name as empty; the lock ensures
                    // only one proceeds to create the meeting room.
                    \Illuminate\Support\Facades\DB::transaction(function () use ($session) {
                        $freshSession = $session->newQuery()->lockForUpdate()->find($session->id);
                        if (! $freshSession || ! empty($freshSession->meeting_room_name)) {
                            // Already created by a concurrent request
                            return;
                        }

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
