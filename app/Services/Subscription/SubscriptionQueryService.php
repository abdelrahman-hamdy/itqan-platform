<?php

namespace App\Services\Subscription;

use App\Enums\EnrollmentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use Illuminate\Database\Eloquent\Collection;

/**
 * Handles all read-only subscription queries.
 *
 * Provides unified access to subscriptions across all three types:
 * QuranSubscription, AcademicSubscription, and CourseSubscription.
 *
 * Extracted from SubscriptionService to isolate query/retrieval logic.
 */
class SubscriptionQueryService
{
    /**
     * Get all subscriptions for a student
     *
     * Returns a unified collection of all subscription types
     */
    public function getStudentSubscriptions(int $studentId, ?int $academyId = null): Collection
    {
        $subscriptions = collect();

        // Get Quran subscriptions
        $quranQuery = QuranSubscription::where('student_id', $studentId);
        if ($academyId) {
            $quranQuery->where('academy_id', $academyId);
        }
        $subscriptions = $subscriptions->merge($quranQuery->get());

        // Get Academic subscriptions
        $academicQuery = AcademicSubscription::where('student_id', $studentId);
        if ($academyId) {
            $academicQuery->where('academy_id', $academyId);
        }
        $subscriptions = $subscriptions->merge($academicQuery->get());

        // Get Course subscriptions
        $courseQuery = CourseSubscription::where('student_id', $studentId);
        if ($academyId) {
            $courseQuery->where('academy_id', $academyId);
        }
        $subscriptions = $subscriptions->merge($courseQuery->get());

        // Sort by created_at descending
        return $subscriptions->sortByDesc('created_at')->values();
    }

    /**
     * Get active subscriptions for a student
     */
    public function getActiveSubscriptions(int $studentId, ?int $academyId = null): Collection
    {
        return $this->getStudentSubscriptions($studentId, $academyId)
            ->filter(fn ($sub) => $sub->isActive());
    }

    /**
     * Get all subscriptions for an academy
     *
     * Note: This method accepts SessionSubscriptionStatus for Quran/Academic subscriptions.
     * CourseSubscription uses EnrollmentStatus but we map common statuses.
     */
    public function getAcademySubscriptions(int $academyId, ?SessionSubscriptionStatus $status = null): Collection
    {
        $subscriptions = collect();

        $quranQuery = QuranSubscription::where('academy_id', $academyId);
        $academicQuery = AcademicSubscription::where('academy_id', $academyId);
        $courseQuery = CourseSubscription::where('academy_id', $academyId);

        if ($status) {
            $quranQuery->where('status', $status);
            $academicQuery->where('status', $status);

            // Map SessionSubscriptionStatus to EnrollmentStatus for CourseSubscription
            $enrollmentStatus = match ($status) {
                SessionSubscriptionStatus::PENDING => EnrollmentStatus::PENDING,
                SessionSubscriptionStatus::ACTIVE => EnrollmentStatus::ENROLLED,
                SessionSubscriptionStatus::CANCELLED => EnrollmentStatus::CANCELLED,
                SessionSubscriptionStatus::PAUSED => null, // Courses don't have paused status
            };

            if ($enrollmentStatus) {
                $courseQuery->where('status', $enrollmentStatus);
            } else {
                // If no mapping, don't include courses for this status
                $courseQuery->whereRaw('1 = 0');
            }
        }

        return $subscriptions
            ->merge($quranQuery->get())
            ->merge($academicQuery->get())
            ->merge($courseQuery->get())
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Find a subscription by code
     */
    public function findByCode(string $code): ?\App\Models\BaseSubscription
    {
        // Determine type from code prefix
        $prefix = strtoupper(substr($code, 0, 2));

        $subscription = match ($prefix) {
            'QS' => QuranSubscription::where('subscription_code', $code)->first(),
            'AS' => AcademicSubscription::where('subscription_code', $code)->first(),
            'CS' => CourseSubscription::where('subscription_code', $code)->first(),
            default => null,
        };

        // If not found by prefix, search all types
        if (! $subscription) {
            $subscription = QuranSubscription::where('subscription_code', $code)->first()
                ?? AcademicSubscription::where('subscription_code', $code)->first()
                ?? CourseSubscription::where('subscription_code', $code)->first();
        }

        return $subscription;
    }

    /**
     * Find a subscription by ID and type
     */
    public function findById(int $id, string $type): ?\App\Models\BaseSubscription
    {
        $modelClass = $this->resolveModelClass($type);

        return $modelClass::find($id);
    }

    /**
     * Get subscriptions expiring within N days
     */
    public function getExpiringSoon(int $academyId, int $days = 7): Collection
    {
        $subscriptions = collect();

        // Only Quran and Academic have time-based expiry with auto-renewal
        $subscriptions = $subscriptions->merge(
            QuranSubscription::where('academy_id', $academyId)
                ->expiringSoon($days)
                ->get()
        );

        $subscriptions = $subscriptions->merge(
            AcademicSubscription::where('academy_id', $academyId)
                ->expiringSoon($days)
                ->get()
        );

        // Course subscriptions with timed access
        $subscriptions = $subscriptions->merge(
            CourseSubscription::where('academy_id', $academyId)
                ->where('lifetime_access', false)
                ->where('status', EnrollmentStatus::ENROLLED)
                ->whereBetween('ends_at', [now(), now()->addDays($days)])
                ->get()
        );

        return $subscriptions->sortBy('ends_at')->values();
    }

    /**
     * Get subscriptions due for renewal
     */
    public function getDueForRenewal(int $academyId): Collection
    {
        $subscriptions = collect();

        // Only Quran and Academic support auto-renewal
        $subscriptions = $subscriptions->merge(
            QuranSubscription::where('academy_id', $academyId)
                ->dueForRenewal()
                ->get()
        );

        $subscriptions = $subscriptions->merge(
            AcademicSubscription::where('academy_id', $academyId)
                ->dueForRenewal()
                ->get()
        );

        return $subscriptions->sortBy('next_billing_date')->values();
    }

    /**
     * Get unified subscription summaries for display
     *
     * Returns array of subscription summary data suitable for views
     */
    public function getSubscriptionSummaries(int $studentId, ?int $academyId = null): array
    {
        $subscriptions = $this->getStudentSubscriptions($studentId, $academyId);

        return $subscriptions->map(function ($subscription) {
            return $subscription->getSubscriptionSummary();
        })->toArray();
    }

    /**
     * Get active subscription summaries grouped by type
     */
    public function getActiveSubscriptionsByType(int $studentId, ?int $academyId = null): array
    {
        $subscriptions = $this->getActiveSubscriptions($studentId, $academyId);

        return [
            'quran' => $subscriptions->filter(fn ($s) => $s instanceof QuranSubscription)
                ->map(fn ($s) => $s->getSubscriptionSummary())->values()->toArray(),
            'academic' => $subscriptions->filter(fn ($s) => $s instanceof AcademicSubscription)
                ->map(fn ($s) => $s->getSubscriptionSummary())->values()->toArray(),
            'course' => $subscriptions->filter(fn ($s) => $s instanceof CourseSubscription)
                ->map(fn ($s) => $s->getSubscriptionSummary())->values()->toArray(),
        ];
    }

    /**
     * Resolve the model class string for a subscription type.
     * Mirrors SubscriptionService::getModelClass() without the full service dependency.
     */
    private function resolveModelClass(string $type): string
    {
        return match ($type) {
            'quran' => QuranSubscription::class,
            'academic' => AcademicSubscription::class,
            'course' => CourseSubscription::class,
            default => throw new \InvalidArgumentException("Unknown subscription type: {$type}"),
        };
    }
}
