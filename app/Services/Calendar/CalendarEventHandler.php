<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Enums\CalendarSessionType;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Filament\Shared\Traits\ValidatesConflicts;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use App\ValueObjects\CalendarEventId;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Calendar Event Handler Service
 *
 * Consolidates all drag-and-drop and resize logic for calendar events.
 * This replaces ~500 lines of duplicated code across TeacherCalendarWidget
 * and AcademicFullCalendarWidget.
 *
 * Key responsibilities:
 * - Handle event drag (reschedule)
 * - Handle event resize (duration change)
 * - Validate subscription constraints for ALL session types
 * - Validate time conflicts
 * - Enforce business rules
 *
 * @see \App\Filament\Shared\Widgets\UnifiedCalendarWidget
 */
class CalendarEventHandler
{
    use ValidatesConflicts;

    /**
     * Handle an event being dragged to a new time.
     *
     * @param  CalendarEventId  $eventId  The event ID
     * @param  Carbon  $newStart  New start time (in academy timezone)
     * @param  Carbon  $newEnd  New end time (in academy timezone)
     * @param  CalendarConfiguration  $config  Calendar configuration
     */
    public function handleEventDrop(
        CalendarEventId $eventId,
        Carbon $newStart,
        Carbon $newEnd,
        CalendarConfiguration $config
    ): EventHandlerResult {
        try {
            $session = $eventId->resolve();
            $timezone = AcademyContextService::getTimezone();

            // Check if session type allows moving
            if (! $eventId->type->isMovable()) {
                return EventHandlerResult::revert(
                    'لا يمكن تحريك هذا النوع من الجلسات',
                    'type'
                );
            }

            // Check if session is in a final state
            $status = $this->getSessionStatus($session);
            if ($status && $status->isFinal()) {
                return EventHandlerResult::revert(
                    'لا يمكن تحريك جلسة مكتملة أو ملغاة',
                    'status'
                );
            }

            // Check if session can be rescheduled based on status
            if ($status && ! $status->canReschedule()) {
                return EventHandlerResult::revert(
                    'لا يمكن إعادة جدولة جلسة بهذه الحالة',
                    'status'
                );
            }

            // Check if moving to past (compare in same timezone)
            $now = AcademyContextService::nowInAcademyTimezone();
            if ($newStart->isBefore($now)) {
                return EventHandlerResult::revert(
                    'لا يمكن جدولة جلسة في وقت ماضي',
                    'past'
                );
            }

            // Validate subscription constraints
            $subscriptionResult = $this->validateSubscriptionConstraints($session, $newStart, $eventId->type);
            if (! $subscriptionResult->isSuccess()) {
                return $subscriptionResult;
            }

            // Validate conflicts
            // Note: Do NOT convert to UTC - Eloquent handles timezone based on APP_TIMEZONE
            try {
                $this->validateSessionConflicts([
                    'scheduled_at' => $newStart->copy(),
                    'duration_minutes' => $session->duration_minutes ?? 60,
                    'teacher_id' => $this->getTeacherId($session, $eventId->type),
                ], $session->id, $this->getSessionTypeForConflict($eventId->type));
            } catch (\Exception $e) {
                return EventHandlerResult::revert($e->getMessage(), 'conflict');
            }

            // Perform the update
            return DB::transaction(function () use ($session, $newStart) {
                // Store old time for rescheduling audit
                $oldScheduledAt = $session->scheduled_at;

                $session->update([
                    'scheduled_at' => $newStart->copy(),
                    'rescheduled_from' => $oldScheduledAt,
                    'rescheduled_to' => $newStart->copy(),
                ]);

                // Clear meeting data if exists (will be regenerated)
                if (method_exists($session, 'clearMeetingData')) {
                    $session->clearMeetingData();
                }

                Log::info('Calendar event moved', [
                    'session_id' => $session->id,
                    'session_type' => get_class($session),
                    'from' => $oldScheduledAt?->toIso8601String(),
                    'to' => $newStart->toIso8601String(),
                    'user_id' => Auth::id(),
                ]);

                return EventHandlerResult::success('تم تحديث موعد الجلسة بنجاح');
            });
        } catch (\Exception $e) {
            Log::error('Calendar event drop failed', [
                'event_id' => $eventId->toString(),
                'error' => $e->getMessage(),
            ]);

            return EventHandlerResult::error('حدث خطأ أثناء تحديث الموعد');
        }
    }

    /**
     * Handle an event being resized (duration change).
     *
     * @param  CalendarEventId  $eventId  The event ID
     * @param  Carbon  $newStart  New start time (in academy timezone)
     * @param  Carbon  $newEnd  New end time (in academy timezone)
     * @param  CalendarConfiguration  $config  Calendar configuration
     */
    public function handleEventResize(
        CalendarEventId $eventId,
        Carbon $newStart,
        Carbon $newEnd,
        CalendarConfiguration $config
    ): EventHandlerResult {
        try {
            $session = $eventId->resolve();

            // Check if session type allows resize
            if (! $eventId->type->isResizable()) {
                return EventHandlerResult::revert(
                    'لا يمكن تغيير مدة هذا النوع من الجلسات',
                    'type'
                );
            }

            // Check if session is in a final state
            $status = $this->getSessionStatus($session);
            if ($status && $status->isFinal()) {
                return EventHandlerResult::revert(
                    'لا يمكن تغيير مدة جلسة مكتملة أو ملغاة',
                    'status'
                );
            }

            // Calculate new duration
            $newDuration = (int) $newStart->diffInMinutes($newEnd);

            // Validate duration bounds
            $durationResult = $this->validateDuration($newDuration);
            if (! $durationResult->isSuccess()) {
                return $durationResult;
            }

            // Validate conflicts with new duration
            try {
                $this->validateSessionConflicts([
                    'scheduled_at' => $session->scheduled_at,
                    'duration_minutes' => $newDuration,
                    'teacher_id' => $this->getTeacherId($session, $eventId->type),
                ], $session->id, $this->getSessionTypeForConflict($eventId->type));
            } catch (\Exception $e) {
                return EventHandlerResult::revert($e->getMessage(), 'conflict');
            }

            // Perform the update
            return DB::transaction(function () use ($session, $newDuration) {
                $oldDuration = $session->duration_minutes;

                $session->update([
                    'duration_minutes' => $newDuration,
                ]);

                Log::info('Calendar event resized', [
                    'session_id' => $session->id,
                    'session_type' => get_class($session),
                    'old_duration' => $oldDuration,
                    'new_duration' => $newDuration,
                    'user_id' => Auth::id(),
                ]);

                return EventHandlerResult::success("تم تحديث مدة الجلسة إلى {$newDuration} دقيقة");
            });
        } catch (\Exception $e) {
            Log::error('Calendar event resize failed', [
                'event_id' => $eventId->toString(),
                'error' => $e->getMessage(),
            ]);

            return EventHandlerResult::error('حدث خطأ أثناء تحديث المدة');
        }
    }

    /**
     * Validate subscription constraints for a session.
     */
    public function validateSubscriptionConstraints(
        Model $session,
        Carbon $newStart,
        CalendarSessionType $type
    ): EventHandlerResult {
        return match ($type) {
            CalendarSessionType::QURAN_INDIVIDUAL => $this->validateQuranIndividualSubscription($session, $newStart),
            CalendarSessionType::QURAN_GROUP => $this->validateQuranGroupSubscription($session, $newStart),
            CalendarSessionType::QURAN_TRIAL => EventHandlerResult::success(''),
            CalendarSessionType::ACADEMIC_PRIVATE => $this->validateAcademicSubscription($session, $newStart),
            CalendarSessionType::INTERACTIVE_COURSE => $this->validateCourseConstraints($session, $newStart),
        };
    }

    /**
     * Validate Quran individual session subscription.
     */
    protected function validateQuranIndividualSubscription(QuranSession $session, Carbon $newStart): EventHandlerResult
    {
        if ($session->session_type !== 'individual' || ! $session->individual_circle_id) {
            return EventHandlerResult::success('');
        }

        $circle = $session->individualCircle;
        $subscription = $circle?->subscription;

        if (! $subscription) {
            return EventHandlerResult::success('');
        }

        // Check subscription status
        if ($subscription->status !== SessionSubscriptionStatus::ACTIVE) {
            return EventHandlerResult::revert(
                'الاشتراك غير نشط. لا يمكن تحريك الجلسة.',
                'subscription'
            );
        }

        // Check subscription date range
        if ($subscription->starts_at && $newStart->isBefore($subscription->starts_at)) {
            return EventHandlerResult::revert(
                'لا يمكن جدولة الجلسة قبل تاريخ بدء الاشتراك ('.$subscription->starts_at->format('Y/m/d').')',
                'subscription'
            );
        }

        if ($subscription->ends_at && $newStart->isAfter($subscription->ends_at)) {
            return EventHandlerResult::revert(
                'لا يمكن جدولة الجلسة بعد تاريخ انتهاء الاشتراك ('.$subscription->ends_at->format('Y/m/d').')',
                'subscription'
            );
        }

        return EventHandlerResult::success('');
    }

    /**
     * Validate Quran group session subscription.
     */
    protected function validateQuranGroupSubscription(QuranSession $session, Carbon $newStart): EventHandlerResult
    {
        // Group sessions have circle-level constraints
        $circle = $session->circle;

        if (! $circle) {
            return EventHandlerResult::success('');
        }

        // Check if circle is active (QuranCircle uses 'status' boolean property)
        if (! $circle->status) {
            return EventHandlerResult::revert(
                'الحلقة غير نشطة. لا يمكن تحريك الجلسة.',
                'subscription'
            );
        }

        return EventHandlerResult::success('');
    }

    /**
     * Validate Academic session subscription.
     * This was MISSING in the old AcademicFullCalendarWidget.
     */
    protected function validateAcademicSubscription(AcademicSession $session, Carbon $newStart): EventHandlerResult
    {
        // Get subscription through the lesson relationship
        $lesson = $session->academicIndividualLesson;
        $subscription = $lesson?->subscription ?? $session->subscription;

        if (! $subscription) {
            return EventHandlerResult::success('');
        }

        // Check subscription status
        if ($subscription->status !== SessionSubscriptionStatus::ACTIVE) {
            return EventHandlerResult::revert(
                'الاشتراك غير نشط. لا يمكن تحريك الجلسة.',
                'subscription'
            );
        }

        // Check subscription date range
        if ($subscription->starts_at && $newStart->isBefore($subscription->starts_at)) {
            return EventHandlerResult::revert(
                'لا يمكن جدولة الجلسة قبل تاريخ بدء الاشتراك ('.$subscription->starts_at->format('Y/m/d').')',
                'subscription'
            );
        }

        if ($subscription->ends_at && $newStart->isAfter($subscription->ends_at)) {
            return EventHandlerResult::revert(
                'لا يمكن جدولة الجلسة بعد تاريخ انتهاء الاشتراك ('.$subscription->ends_at->format('Y/m/d').')',
                'subscription'
            );
        }

        return EventHandlerResult::success('');
    }

    /**
     * Validate Interactive Course session constraints.
     */
    protected function validateCourseConstraints(InteractiveCourseSession $session, Carbon $newStart): EventHandlerResult
    {
        $course = $session->course;

        if (! $course) {
            return EventHandlerResult::success('');
        }

        // Check if course is published/active
        if (! $course->is_published) {
            return EventHandlerResult::revert(
                'الدورة غير منشورة. لا يمكن تحريك الجلسة.',
                'course'
            );
        }

        // Check course date range
        if ($course->start_date && $newStart->startOfDay()->isBefore($course->start_date)) {
            return EventHandlerResult::revert(
                'لا يمكن جدولة الجلسة قبل تاريخ بدء الدورة ('.$course->start_date->format('Y/m/d').')',
                'course'
            );
        }

        if ($course->end_date && $newStart->startOfDay()->isAfter($course->end_date)) {
            return EventHandlerResult::revert(
                'لا يمكن جدولة الجلسة بعد تاريخ انتهاء الدورة ('.$course->end_date->format('Y/m/d').')',
                'course'
            );
        }

        return EventHandlerResult::success('');
    }

    /**
     * Validate duration is within acceptable bounds.
     */
    protected function validateDuration(int $durationMinutes): EventHandlerResult
    {
        $minDuration = 15; // 15 minutes minimum
        $maxDuration = 180; // 3 hours maximum

        if ($durationMinutes < $minDuration) {
            return EventHandlerResult::revert(
                "الحد الأدنى لمدة الجلسة {$minDuration} دقيقة",
                'duration'
            );
        }

        if ($durationMinutes > $maxDuration) {
            return EventHandlerResult::revert(
                "الحد الأقصى لمدة الجلسة {$maxDuration} دقيقة",
                'duration'
            );
        }

        return EventHandlerResult::success('');
    }

    /**
     * Get the session status as a SessionStatus enum.
     */
    protected function getSessionStatus(Model $session): ?SessionStatus
    {
        $status = $session->status;

        if ($status instanceof SessionStatus) {
            return $status;
        }

        if (is_string($status)) {
            return SessionStatus::tryFrom($status);
        }

        return null;
    }

    /**
     * Get the teacher ID for conflict checking.
     */
    protected function getTeacherId(Model $session, CalendarSessionType $type): int
    {
        return match ($type) {
            CalendarSessionType::QURAN_INDIVIDUAL,
            CalendarSessionType::QURAN_GROUP,
            CalendarSessionType::QURAN_TRIAL => $session->quran_teacher_id ?? Auth::id(),
            CalendarSessionType::ACADEMIC_PRIVATE => $session->academic_teacher_id ?? Auth::id(),
            // For interactive courses, get the user_id from the teacher profile, not the profile ID
            CalendarSessionType::INTERACTIVE_COURSE => $session->course?->assignedTeacher?->user_id ?? Auth::id(),
        };
    }

    /**
     * Get the session type string for conflict validation.
     */
    protected function getSessionTypeForConflict(CalendarSessionType $type): string
    {
        return match ($type) {
            CalendarSessionType::QURAN_INDIVIDUAL,
            CalendarSessionType::QURAN_GROUP,
            CalendarSessionType::QURAN_TRIAL => 'quran',
            CalendarSessionType::ACADEMIC_PRIVATE,
            CalendarSessionType::INTERACTIVE_COURSE => 'academic',
        };
    }
}
