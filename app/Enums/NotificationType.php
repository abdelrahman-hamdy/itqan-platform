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

    // Trial Session Notifications
    case TRIAL_REQUEST_RECEIVED = 'trial_request_received';
    case TRIAL_REQUEST_APPROVED = 'trial_request_approved';
    case TRIAL_SESSION_SCHEDULED = 'trial_session_scheduled';
    case TRIAL_SESSION_COMPLETED = 'trial_session_completed';

    // Trial Session Notifications (role-specific)
    case TRIAL_SESSION_COMPLETED_STUDENT = 'trial_session_completed_student';
    case TRIAL_SESSION_COMPLETED_TEACHER = 'trial_session_completed_teacher';
    case TRIAL_SESSION_REMINDER_STUDENT = 'trial_session_reminder_student';
    case TRIAL_SESSION_REMINDER_TEACHER = 'trial_session_reminder_teacher';
    case TRIAL_SESSION_REMINDER_PARENT = 'trial_session_reminder_parent';
    case TRIAL_SESSION_CANCELLED = 'trial_session_cancelled';

    // Session Notifications (role-specific for parents)
    case SESSION_REMINDER_PARENT = 'session_reminder_parent';
    case SESSION_STARTED_PARENT = 'session_started_parent';
    case SESSION_COMPLETED_PARENT = 'session_completed_parent';

    // Attendance Notifications
    case ATTENDANCE_MARKED_PRESENT = 'attendance_marked_present';
    case ATTENDANCE_MARKED_ABSENT = 'attendance_marked_absent';
    case ATTENDANCE_MARKED_LATE = 'attendance_marked_late';
    case ATTENDANCE_REPORT_READY = 'attendance_report_ready';

    // Homework Notifications
    case HOMEWORK_ASSIGNED = 'homework_assigned';
    case HOMEWORK_SUBMITTED = 'homework_submitted';
    case HOMEWORK_SUBMITTED_TEACHER = 'homework_submitted_teacher';  // Teacher notification
    case HOMEWORK_GRADED = 'homework_graded';
    case HOMEWORK_DEADLINE_REMINDER = 'homework_deadline_reminder';

    // Payment Notifications
    case PAYMENT_SUCCESS = 'payment_success';
    case PAYMENT_FAILED = 'payment_failed';
    case SUBSCRIPTION_EXPIRING = 'subscription_expiring';
    case GRACE_PERIOD_EXPIRING = 'grace_period_expiring';
    case SUBSCRIPTION_EXPIRED = 'subscription_expired';
    case SUBSCRIPTION_ACTIVATED = 'subscription_activated';
    case SUBSCRIPTION_RENEWED = 'subscription_renewed';
    case INVOICE_GENERATED = 'invoice_generated';

    // Meeting Notifications (participant joined/left are handled as in-page toasts only)
    case MEETING_ROOM_READY = 'meeting_room_ready';
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
    case QUIZ_COMPLETED_TEACHER = 'quiz_completed_teacher';  // Teacher notification
    case QUIZ_PASSED = 'quiz_passed';
    case QUIZ_FAILED = 'quiz_failed';
    case QUIZ_DEADLINE_24H = 'quiz_deadline_24h';  // 24 hours before deadline
    case QUIZ_DEADLINE_1H = 'quiz_deadline_1h';    // 1 hour before deadline (urgent)

    // Review Notifications
    case REVIEW_RECEIVED = 'review_received';
    case REVIEW_APPROVED = 'review_approved';

    // Teacher Payout Notifications
    case PAYOUT_APPROVED = 'payout_approved';
    case PAYOUT_REJECTED = 'payout_rejected';
    case PAYOUT_PAID = 'payout_paid';

    // Admin-Specific Notifications
    case NEW_STUDENT_ENROLLED = 'new_student_enrolled';
    case NEW_TRIAL_REQUEST_ADMIN = 'new_trial_request_admin';
    case NEW_PAYMENT_RECEIVED = 'new_payment_received';
    case TEACHER_SESSION_CANCELLED = 'teacher_session_cancelled';
    case SUBSCRIPTION_RENEWAL_FAILED_BATCH = 'subscription_renewal_failed_batch';
    case NEW_STUDENT_SUBSCRIPTION_TEACHER = 'new_student_subscription_teacher';

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
            // Regular session notifications
            self::SESSION_SCHEDULED,
            self::SESSION_REMINDER,
            self::SESSION_STARTED,
            self::SESSION_COMPLETED,
            self::SESSION_CANCELLED,
            self::SESSION_RESCHEDULED,
            self::SESSION_REMINDER_PARENT,
            self::SESSION_STARTED_PARENT,
            self::SESSION_COMPLETED_PARENT => NotificationCategory::SESSION,

            // Trial session notifications - orange with gift icon
            self::TRIAL_REQUEST_RECEIVED,
            self::TRIAL_REQUEST_APPROVED,
            self::TRIAL_SESSION_SCHEDULED,
            self::TRIAL_SESSION_COMPLETED,
            self::TRIAL_SESSION_COMPLETED_STUDENT,
            self::TRIAL_SESSION_COMPLETED_TEACHER,
            self::TRIAL_SESSION_REMINDER_STUDENT,
            self::TRIAL_SESSION_REMINDER_TEACHER,
            self::TRIAL_SESSION_REMINDER_PARENT,
            self::TRIAL_SESSION_CANCELLED => NotificationCategory::TRIAL,

            // Attendance notifications
            self::ATTENDANCE_MARKED_PRESENT,
            self::ATTENDANCE_MARKED_ABSENT,
            self::ATTENDANCE_MARKED_LATE,
            self::ATTENDANCE_REPORT_READY => NotificationCategory::ATTENDANCE,

            // Homework notifications
            self::HOMEWORK_ASSIGNED,
            self::HOMEWORK_SUBMITTED,
            self::HOMEWORK_SUBMITTED_TEACHER,
            self::HOMEWORK_GRADED,
            self::HOMEWORK_DEADLINE_REMINDER => NotificationCategory::HOMEWORK,

            // Normal payment notifications
            self::PAYMENT_SUCCESS,
            self::SUBSCRIPTION_ACTIVATED,
            self::SUBSCRIPTION_RENEWED,
            self::INVOICE_GENERATED,
            self::PAYOUT_APPROVED,
            self::PAYOUT_REJECTED,
            self::PAYOUT_PAID => NotificationCategory::PAYMENT,

            // Admin notifications
            self::NEW_STUDENT_ENROLLED => NotificationCategory::SYSTEM,
            self::NEW_TRIAL_REQUEST_ADMIN => NotificationCategory::TRIAL,
            self::NEW_PAYMENT_RECEIVED => NotificationCategory::PAYMENT,
            self::TEACHER_SESSION_CANCELLED => NotificationCategory::SESSION,
            self::NEW_STUDENT_SUBSCRIPTION_TEACHER => NotificationCategory::PAYMENT,

            // Alert notifications - red for urgent/negative
            self::PAYMENT_FAILED,
            self::SUBSCRIPTION_EXPIRING,
            self::GRACE_PERIOD_EXPIRING,
            self::SUBSCRIPTION_EXPIRED,
            self::QUIZ_FAILED,
            self::QUIZ_DEADLINE_1H,
            self::SUBSCRIPTION_RENEWAL_FAILED_BATCH => NotificationCategory::ALERT,

            // Meeting notifications
            self::MEETING_ROOM_READY,
            self::MEETING_RECORDING_AVAILABLE,
            self::MEETING_TECHNICAL_ISSUE => NotificationCategory::MEETING,

            // Progress notifications
            self::PROGRESS_REPORT_AVAILABLE,
            self::ACHIEVEMENT_UNLOCKED,
            self::CERTIFICATE_EARNED,
            self::COURSE_COMPLETED,
            self::QUIZ_ASSIGNED,
            self::QUIZ_COMPLETED,
            self::QUIZ_COMPLETED_TEACHER,
            self::QUIZ_PASSED,
            self::QUIZ_DEADLINE_24H => NotificationCategory::PROGRESS,

            // Review notifications - yellow with star
            self::REVIEW_RECEIVED,
            self::REVIEW_APPROVED => NotificationCategory::REVIEW,

            // System notifications
            self::ACCOUNT_VERIFIED,
            self::PASSWORD_CHANGED,
            self::PROFILE_UPDATED,
            self::SYSTEM_MAINTENANCE => NotificationCategory::SYSTEM,
        };
    }

    /**
     * Get the icon for this notification type.
     * Returns specific icon for overrides, or category default.
     */
    public function getIcon(): string
    {
        return match ($this) {
            // Attendance icons
            self::ATTENDANCE_MARKED_LATE => 'heroicon-o-exclamation-triangle',
            self::ATTENDANCE_MARKED_ABSENT => 'heroicon-o-exclamation-triangle',

            // Certificate uses academic cap icon
            self::CERTIFICATE_EARNED => 'heroicon-o-academic-cap',

            // Quiz notifications use clipboard icon
            self::QUIZ_ASSIGNED,
            self::QUIZ_COMPLETED,
            self::QUIZ_COMPLETED_TEACHER,
            self::QUIZ_PASSED => 'heroicon-o-clipboard-document-list',
            self::QUIZ_FAILED => 'heroicon-o-clipboard-document-list',

            // Quiz deadline reminders use clock icon
            self::QUIZ_DEADLINE_24H => 'heroicon-o-clock',
            self::QUIZ_DEADLINE_1H => 'heroicon-o-exclamation-circle',

            // Subscription notifications use arrow-path (renewal) icon
            self::SUBSCRIPTION_RENEWED => 'heroicon-o-arrow-path',
            self::SUBSCRIPTION_ACTIVATED => 'heroicon-o-check-badge',
            self::SUBSCRIPTION_EXPIRING => 'heroicon-o-clock',
            self::GRACE_PERIOD_EXPIRING => 'heroicon-o-exclamation-triangle',
            self::SUBSCRIPTION_EXPIRED => 'heroicon-o-x-circle',

            // Admin-specific icons
            self::NEW_STUDENT_ENROLLED => 'heroicon-o-user-plus',
            self::NEW_PAYMENT_RECEIVED => 'heroicon-o-banknotes',
            self::TEACHER_SESSION_CANCELLED => 'heroicon-o-x-mark',
            self::SUBSCRIPTION_RENEWAL_FAILED_BATCH => 'heroicon-o-exclamation-triangle',
            self::NEW_STUDENT_SUBSCRIPTION_TEACHER => 'heroicon-o-user-plus',

            // Default: use category icon
            default => $this->getCategory()->getIcon(),
        };
    }

    /**
     * Get the Filament-compatible color name for this notification type.
     * Used for Filament database notification rendering (iconColor).
     * Returns specific color for type overrides, or category default.
     */
    public function getFilamentColor(): string
    {
        return match ($this) {
            self::ATTENDANCE_MARKED_LATE => 'warning',
            self::ATTENDANCE_MARKED_ABSENT => 'danger',
            self::CERTIFICATE_EARNED => 'warning',
            self::QUIZ_ASSIGNED,
            self::QUIZ_COMPLETED,
            self::QUIZ_COMPLETED_TEACHER,
            self::QUIZ_PASSED => 'info',
            self::QUIZ_DEADLINE_24H => 'warning',
            self::SUBSCRIPTION_RENEWED,
            self::SUBSCRIPTION_ACTIVATED => 'success',
            self::NEW_STUDENT_ENROLLED => 'success',
            self::NEW_PAYMENT_RECEIVED => 'success',
            self::TEACHER_SESSION_CANCELLED => 'danger',
            self::SUBSCRIPTION_RENEWAL_FAILED_BATCH => 'danger',
            self::NEW_STUDENT_SUBSCRIPTION_TEACHER => 'success',
            default => $this->getCategory()->getFilamentColor(),
        };
    }

    /**
     * Get the Tailwind color class for this notification type.
     * Returns specific color for overrides, or category default.
     */
    public function getTailwindColor(): string
    {
        return match ($this) {
            // Late attendance = yellow/warning
            self::ATTENDANCE_MARKED_LATE => 'bg-yellow-100 text-yellow-800',

            // Absent attendance = red/danger
            self::ATTENDANCE_MARKED_ABSENT => 'bg-red-100 text-red-800',

            // Certificate = orange
            self::CERTIFICATE_EARNED => 'bg-orange-100 text-orange-800',

            // Quiz notifications = indigo (keeping progress color but with distinct icon)
            self::QUIZ_ASSIGNED,
            self::QUIZ_COMPLETED,
            self::QUIZ_PASSED => 'bg-indigo-100 text-indigo-800',

            // Quiz deadline reminder = orange/warning
            self::QUIZ_DEADLINE_24H => 'bg-orange-100 text-orange-800',

            // Subscription notifications = teal (distinct from payment cyan)
            self::SUBSCRIPTION_RENEWED,
            self::SUBSCRIPTION_ACTIVATED => 'bg-teal-100 text-teal-800',

            // Admin-specific colors
            self::NEW_STUDENT_ENROLLED => 'bg-green-100 text-green-800',
            self::NEW_PAYMENT_RECEIVED => 'bg-green-100 text-green-800',
            self::TEACHER_SESSION_CANCELLED => 'bg-red-100 text-red-800',
            self::SUBSCRIPTION_RENEWAL_FAILED_BATCH => 'bg-red-100 text-red-800',
            self::NEW_STUDENT_SUBSCRIPTION_TEACHER => 'bg-green-100 text-green-800',

            // Default: use category color
            default => $this->getCategory()->getTailwindColor(),
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
