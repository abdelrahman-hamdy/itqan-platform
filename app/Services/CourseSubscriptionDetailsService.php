<?php

namespace App\Services;

use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Enums\SessionStatus;

/**
 * Course Subscription Details Service
 *
 * Generates subscription widget data for course students
 * Handles both recorded courses and interactive courses
 */
class CourseSubscriptionDetailsService extends BaseSubscriptionDetailsService
{
    /**
     * Get subscription details for widget display
     *
     * @param BaseSubscription $subscription
     * @return array
     */
    public function getSubscriptionDetails(BaseSubscription $subscription): array
    {
        /** @var CourseSubscription $subscription */
        return [
            // Basic info
            'subscription_type' => $subscription->course_type,
            'status' => $subscription->status,
            'payment_status' => $subscription->payment_status,

            // Dates
            'starts_at' => $subscription->starts_at,
            'ends_at' => $subscription->ends_at,
            'enrolled_at' => $subscription->enrolled_at,
            'last_accessed_at' => $subscription->last_accessed_at,
            'completion_date' => $subscription->completion_date,

            // Sessions/Lessons
            'total_sessions' => $subscription->getTotalSessions(),
            'sessions_used' => $subscription->getSessionsUsed(),
            'sessions_remaining' => $subscription->getSessionsRemaining(),
            'sessions_percentage' => $this->calculateSessionsPercentage($subscription),

            // Billing (one-time purchase)
            'billing_cycle' => $subscription->billing_cycle,
            'billing_cycle_text' => 'شراء لمرة واحدة',
            'billing_cycle_ar' => 'شراء لمرة واحدة',
            'currency' => $subscription->currency,
            'total_price' => $subscription->total_price,
            'final_price' => $subscription->final_price,
            'price_paid' => $subscription->price_paid,
            'original_price' => $subscription->original_price,

            // Status badges
            'status_badge_class' => $this->getStatusBadgeClass($subscription->status),
            'payment_status_badge_class' => $this->getPaymentStatusBadgeClass($subscription->payment_status),

            // Access info
            'lifetime_access' => $subscription->lifetime_access,
            'access_status' => $subscription->access_status,
            'enrollment_type' => $subscription->enrollment_type,
            'enrollment_type_label' => $subscription->enrollment_type_label,

            // Progress
            'progress_percentage' => $subscription->progress_percentage,
            'completion_rate' => $subscription->completion_rate,

            // Course type specific
            'course_type' => $subscription->course_type,
            'is_interactive' => $subscription->course_type === CourseSubscription::COURSE_TYPE_INTERACTIVE,
            'is_recorded' => $subscription->course_type === CourseSubscription::COURSE_TYPE_RECORDED,

            // Interactive course specific
            'attendance_count' => $subscription->attendance_count ?? 0,
            'total_possible_attendance' => $subscription->total_possible_attendance ?? 0,
            'attendance_percentage' => $subscription->attendance_percentage ?? 0,
            'final_grade' => $subscription->final_grade,
            'has_passed' => $subscription->has_passed ?? false,

            // Recorded course specific
            'completed_lessons' => $subscription->completed_lessons ?? 0,
            'total_lessons' => $subscription->total_lessons ?? 0,
            'watch_time_minutes' => $subscription->watch_time_minutes ?? 0,
            'watch_time_formatted' => $subscription->watch_time_formatted ?? '0 دقيقة',

            // Certificate
            'certificate_issued' => $subscription->certificate_issued ?? false,
            'can_earn_certificate' => $subscription->can_earn_certificate ?? false,
            'completion_certificate_url' => $subscription->completion_certificate_url,

            // Quiz
            'quiz_attempts' => $subscription->quiz_attempts ?? 0,
            'quiz_passed' => $subscription->quiz_passed ?? false,
            'final_score' => $subscription->final_score,
        ];
    }

    /**
     * Get renewal message - courses don't auto-renew
     * Override base method to return null for courses
     *
     * @param BaseSubscription $subscription
     * @return string|null
     */
    public function getRenewalMessage(BaseSubscription $subscription): ?string
    {
        // Courses are one-time purchases - no renewal needed
        // But we can show access expiry warnings for non-lifetime access

        /** @var CourseSubscription $subscription */
        if ($subscription->lifetime_access) {
            return null;
        }

        if (!$subscription->ends_at) {
            return null;
        }

        $daysUntilExpiry = now()->diffInDays($subscription->ends_at, false);

        if ($daysUntilExpiry < 0) {
            return 'انتهت صلاحية الوصول للدورة. يرجى تجديد الاشتراك للمتابعة.';
        }

        if ($daysUntilExpiry <= 7 && $daysUntilExpiry > 0) {
            return "ستنتهي صلاحية الوصول للدورة بعد {$daysUntilExpiry} أيام.";
        }

        return null;
    }

    /**
     * Get progress message based on course type
     *
     * @param BaseSubscription $subscription
     * @return string|null
     */
    public function getProgressMessage(BaseSubscription $subscription): ?string
    {
        /** @var CourseSubscription $subscription */

        if ($subscription->course_type === CourseSubscription::COURSE_TYPE_INTERACTIVE) {
            // Interactive course progress
            $attendancePercentage = $subscription->attendance_percentage ?? 0;

            if ($attendancePercentage >= 90) {
                return 'أنت على وشك إنهاء الدورة! واصل التقدم الرائع.';
            }

            if ($attendancePercentage >= 50) {
                return "لقد أكملت {$attendancePercentage}% من الدورة. استمر!";
            }

            if ($attendancePercentage > 0) {
                return 'لقد بدأت الدورة. واصل الحضور لتحقيق أفضل النتائج.';
            }

            return 'لم تبدأ الدورة بعد. انتظر الجلسة القادمة للبدء.';
        }

        // Recorded course progress
        $progressPercentage = $subscription->progress_percentage ?? 0;

        if ($progressPercentage >= 100) {
            return 'مبروك! لقد أكملت الدورة بنجاح.';
        }

        if ($progressPercentage >= 90) {
            return 'أنت على وشك إنهاء الدورة! واصل التقدم الرائع.';
        }

        if ($progressPercentage >= 50) {
            return "لقد أكملت {$progressPercentage}% من الدورة. استمر!";
        }

        if ($progressPercentage > 0) {
            return 'لقد بدأت الدورة. واصل المشاهدة لإكمال الدروس.';
        }

        return 'ابدأ الدورة الآن لتحقيق أهدافك التعليمية.';
    }
}
