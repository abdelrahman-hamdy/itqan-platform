<?php

namespace App\Enums;

/**
 * Notification Type Enum
 *
 * Defines all notification types in the system.
 * Each type maps to a category and has specific title/message templates.
 *
 * Categories:
 * - Session: Scheduling, reminders, completions
 * - Attendance: Presence tracking
 * - Homework: Assignments and grading
 * - Payment: Transactions and subscriptions
 * - Meeting: Video conferencing
 * - Progress: Academic achievements
 * - System: Account and system updates
 *
 * @see \App\Models\Notification
 * @see \App\Services\NotificationService
 */
enum NotificationType: string
{
    // Session Notifications
    case SESSION_SCHEDULED = 'session_scheduled';
    case SESSION_REMINDER = 'session_reminder';
    case SESSION_STARTED = 'session_started';
    case SESSION_COMPLETED = 'session_completed';
    case SESSION_CANCELLED = 'session_cancelled';
    case SESSION_RESCHEDULED = 'session_rescheduled';

    // Attendance Notifications
    case ATTENDANCE_MARKED_PRESENT = 'attendance_marked_present';
    case ATTENDANCE_MARKED_ABSENT = 'attendance_marked_absent';
    case ATTENDANCE_MARKED_LATE = 'attendance_marked_late';
    case ATTENDANCE_REPORT_READY = 'attendance_report_ready';

    // Homework Notifications
    case HOMEWORK_ASSIGNED = 'homework_assigned';
    case HOMEWORK_SUBMITTED = 'homework_submitted';
    case HOMEWORK_GRADED = 'homework_graded';
    case HOMEWORK_DEADLINE_REMINDER = 'homework_deadline_reminder';

    // Payment Notifications
    case PAYMENT_SUCCESS = 'payment_success';
    case PAYMENT_FAILED = 'payment_failed';
    case SUBSCRIPTION_EXPIRING = 'subscription_expiring';
    case SUBSCRIPTION_EXPIRED = 'subscription_expired';
    case SUBSCRIPTION_ACTIVATED = 'subscription_activated';
    case SUBSCRIPTION_RENEWED = 'subscription_renewed';
    case INVOICE_GENERATED = 'invoice_generated';

    // Meeting Notifications
    case MEETING_ROOM_READY = 'meeting_room_ready';
    case MEETING_PARTICIPANT_JOINED = 'meeting_participant_joined';
    case MEETING_PARTICIPANT_LEFT = 'meeting_participant_left';
    case MEETING_RECORDING_AVAILABLE = 'meeting_recording_available';
    case MEETING_TECHNICAL_ISSUE = 'meeting_technical_issue';

    // Academic Progress Notifications
    case PROGRESS_REPORT_AVAILABLE = 'progress_report_available';
    case ACHIEVEMENT_UNLOCKED = 'achievement_unlocked';
    case CERTIFICATE_EARNED = 'certificate_earned';
    case COURSE_COMPLETED = 'course_completed';

    // Quiz Notifications
    case QUIZ_ASSIGNED = 'quiz_assigned';
    case QUIZ_COMPLETED = 'quiz_completed';
    case QUIZ_PASSED = 'quiz_passed';
    case QUIZ_FAILED = 'quiz_failed';

    // Review Notifications
    case REVIEW_RECEIVED = 'review_received';
    case REVIEW_APPROVED = 'review_approved';

    // Teacher Payout Notifications
    case PAYOUT_APPROVED = 'payout_approved';
    case PAYOUT_REJECTED = 'payout_rejected';
    case PAYOUT_PAID = 'payout_paid';

    // System Notifications
    case ACCOUNT_VERIFIED = 'account_verified';
    case PASSWORD_CHANGED = 'password_changed';
    case PROFILE_UPDATED = 'profile_updated';
    case SYSTEM_MAINTENANCE = 'system_maintenance';

    /**
     * Get the category for this notification type
     */
    public function getCategory(): NotificationCategory
    {
        return match ($this) {
            self::SESSION_SCHEDULED,
            self::SESSION_REMINDER,
            self::SESSION_STARTED,
            self::SESSION_COMPLETED,
            self::SESSION_CANCELLED,
            self::SESSION_RESCHEDULED => NotificationCategory::SESSION,

            self::ATTENDANCE_MARKED_PRESENT,
            self::ATTENDANCE_MARKED_ABSENT,
            self::ATTENDANCE_MARKED_LATE,
            self::ATTENDANCE_REPORT_READY => NotificationCategory::ATTENDANCE,

            self::HOMEWORK_ASSIGNED,
            self::HOMEWORK_SUBMITTED,
            self::HOMEWORK_GRADED,
            self::HOMEWORK_DEADLINE_REMINDER => NotificationCategory::HOMEWORK,

            self::PAYMENT_SUCCESS,
            self::PAYMENT_FAILED,
            self::SUBSCRIPTION_EXPIRING,
            self::SUBSCRIPTION_EXPIRED,
            self::SUBSCRIPTION_ACTIVATED,
            self::SUBSCRIPTION_RENEWED,
            self::INVOICE_GENERATED,
            self::PAYOUT_APPROVED,
            self::PAYOUT_REJECTED,
            self::PAYOUT_PAID => NotificationCategory::PAYMENT,

            self::MEETING_ROOM_READY,
            self::MEETING_PARTICIPANT_JOINED,
            self::MEETING_PARTICIPANT_LEFT,
            self::MEETING_RECORDING_AVAILABLE,
            self::MEETING_TECHNICAL_ISSUE => NotificationCategory::MEETING,

            self::PROGRESS_REPORT_AVAILABLE,
            self::ACHIEVEMENT_UNLOCKED,
            self::CERTIFICATE_EARNED,
            self::COURSE_COMPLETED,
            self::QUIZ_ASSIGNED,
            self::QUIZ_COMPLETED,
            self::QUIZ_PASSED,
            self::QUIZ_FAILED,
            self::REVIEW_RECEIVED,
            self::REVIEW_APPROVED => NotificationCategory::PROGRESS,

            self::ACCOUNT_VERIFIED,
            self::PASSWORD_CHANGED,
            self::PROFILE_UPDATED,
            self::SYSTEM_MAINTENANCE => NotificationCategory::SYSTEM,
        };
    }

    /**
     * Get the title translation key for this notification type
     */
    public function getTitleKey(): string
    {
        return "notifications.types.{$this->value}.title";
    }

    /**
     * Get the message translation key for this notification type
     */
    public function getMessageKey(): string
    {
        return "notifications.types.{$this->value}.message";
    }

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}