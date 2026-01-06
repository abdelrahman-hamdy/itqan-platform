<?php

namespace App\Services\Unified;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * UnifiedSubscriptionFetchingService
 *
 * PURPOSE:
 * Eliminates 40+ duplicate subscription queries scattered across the codebase.
 * Provides a single, consistent way to fetch subscriptions of all types with:
 * - Normalized data format
 * - Caching
 * - Statistics aggregation
 *
 * USAGE:
 * $service = app(UnifiedSubscriptionFetchingService::class);
 *
 * // Get all subscriptions for a student
 * $subscriptions = $service->getForStudent($studentId, $academyId);
 *
 * // Get active subscriptions only
 * $active = $service->getActive($studentId, $academyId);
 *
 * // Get grouped by type
 * $grouped = $service->getGroupedByType($studentId, $academyId);
 *
 * SUBSCRIPTION TYPES:
 * - quran: QuranSubscription (individual and group Quran circles)
 * - academic: AcademicSubscription (private academic lessons)
 * - course: CourseSubscription (recorded and interactive courses)
 *
 * NORMALIZED OUTPUT:
 * Each subscription is returned as an array with consistent keys:
 * - id, type, type_label, status, status_label
 * - start_date, end_date, sessions info
 * - teacher_name, student_name
 * - progress_percent, context info
 * - model (the original Eloquent model for advanced use)
 */
class UnifiedSubscriptionFetchingService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get all subscriptions for a student
     *
     * @param  int  $studentId  Student user ID
     * @param  int  $academyId  Academy ID to scope to
     * @param  SessionSubscriptionStatus|null  $status  Filter by status
     * @param  array  $types  Subscription types to include: 'quran', 'academic', 'course'
     * @param  bool  $useCache  Enable caching
     * @return Collection Normalized subscription array
     */
    public function getForStudent(
        int $studentId,
        int $academyId,
        ?SessionSubscriptionStatus $status = null,
        array $types = ['quran', 'academic', 'course'],
        bool $useCache = true
    ): Collection {
        $cacheKey = $this->buildCacheKey('student', $studentId, $academyId, $status, $types);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $subscriptions = collect();

        if (in_array('quran', $types)) {
            $subscriptions = $subscriptions->merge(
                $this->fetchQuranSubscriptions($studentId, $academyId, $status)
            );
        }

        if (in_array('academic', $types)) {
            $subscriptions = $subscriptions->merge(
                $this->fetchAcademicSubscriptions($studentId, $academyId, $status)
            );
        }

        if (in_array('course', $types)) {
            $subscriptions = $subscriptions->merge(
                $this->fetchCourseSubscriptions($studentId, $academyId, $status)
            );
        }

        $result = $subscriptions
            ->map(fn ($subscription) => $this->normalizeSubscription($subscription))
            ->sortByDesc('created_at')
            ->values();

        if ($useCache) {
            Cache::put($cacheKey, $result, self::CACHE_TTL);
        }

        return $result;
    }

    /**
     * Get subscriptions for multiple students (useful for parent dashboards)
     */
    public function getForStudents(
        array $studentIds,
        int $academyId,
        ?SessionSubscriptionStatus $status = null,
        array $types = ['quran', 'academic', 'course']
    ): Collection {
        if (empty($studentIds)) {
            return collect();
        }

        $subscriptions = collect();

        if (in_array('quran', $types)) {
            $subscriptions = $subscriptions->merge(
                QuranSubscription::query()
                    ->where('academy_id', $academyId)
                    ->whereIn('student_id', $studentIds)
                    ->when($status, fn ($q) => $q->where('status', $status))
                    ->with(['student', 'quranTeacher', 'package', 'individualCircle', 'circle'])
                    ->get()
            );
        }

        if (in_array('academic', $types)) {
            $subscriptions = $subscriptions->merge(
                AcademicSubscription::query()
                    ->where('academy_id', $academyId)
                    ->whereIn('student_id', $studentIds)
                    ->when($status, fn ($q) => $q->where('status', $status))
                    ->with(['student', 'academicTeacher.user', 'lesson'])
                    ->get()
            );
        }

        if (in_array('course', $types)) {
            $subscriptions = $subscriptions->merge(
                CourseSubscription::query()
                    ->where('academy_id', $academyId)
                    ->whereIn('student_id', $studentIds)
                    ->when($status, fn ($q) => $q->where('status', $status))
                    ->with(['student', 'recordedCourse', 'interactiveCourse.assignedTeacher.user'])
                    ->get()
            );
        }

        return $subscriptions
            ->map(fn ($subscription) => $this->normalizeSubscription($subscription))
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Get only active subscriptions for a student
     */
    public function getActive(
        int $studentId,
        int $academyId,
        array $types = ['quran', 'academic', 'course']
    ): Collection {
        return $this->getForStudent(
            studentId: $studentId,
            academyId: $academyId,
            status: SessionSubscriptionStatus::ACTIVE,
            types: $types,
            useCache: true
        );
    }

    /**
     * Get subscriptions grouped by type
     */
    public function getGroupedByType(
        int $studentId,
        int $academyId,
        ?SessionSubscriptionStatus $status = null
    ): array {
        $all = $this->getForStudent($studentId, $academyId, $status);

        return [
            'quran' => $all->where('type', 'quran')->values(),
            'academic' => $all->where('type', 'academic')->values(),
            'course' => $all->where('type', 'course')->values(),
        ];
    }

    /**
     * Get subscription counts by status
     */
    public function countByStatus(
        int $studentId,
        int $academyId,
        array $types = ['quran', 'academic', 'course']
    ): array {
        $all = $this->getForStudent($studentId, $academyId, null, $types);

        return [
            'active' => $all->where('status', 'active')->count(),
            'pending' => $all->where('status', 'pending')->count(),
            'paused' => $all->where('status', 'paused')->count(),
            'cancelled' => $all->where('status', 'cancelled')->count(),
            'enrolled' => $all->where('status', 'enrolled')->count(),
            'completed' => $all->where('status', 'completed')->count(),
            'total' => $all->count(),
        ];
    }

    /**
     * Get subscription summary for dashboard
     */
    public function getSummary(
        int $studentId,
        int $academyId
    ): array {
        $all = $this->getForStudent($studentId, $academyId);
        $active = $all->where('status', SessionSubscriptionStatus::ACTIVE->value);

        return [
            'total_subscriptions' => $all->count(),
            'active_subscriptions' => $active->count(),
            'quran_subscriptions' => $all->where('type', 'quran')->count(),
            'academic_subscriptions' => $all->where('type', 'academic')->count(),
            'course_subscriptions' => $all->where('type', 'course')->count(),
            'total_sessions_remaining' => $active->sum('sessions_remaining'),
            'total_sessions_used' => $active->sum('sessions_used'),
            'expiring_soon' => $active->filter(function ($sub) {
                return $sub['end_date'] && $sub['end_date']->lt(now()->addDays(7));
            })->count(),
        ];
    }

    /**
     * Check if student has any active subscription
     */
    public function hasActiveSubscription(
        int $studentId,
        int $academyId,
        ?string $type = null
    ): bool {
        $types = $type ? [$type] : ['quran', 'academic', 'course'];

        return $this->getActive($studentId, $academyId, $types)->isNotEmpty();
    }

    /**
     * Get a single subscription by ID and type
     */
    public function getById(int $id, string $type): ?array
    {
        $model = match ($type) {
            'quran' => QuranSubscription::with(['student', 'quranTeacher', 'package'])->find($id),
            'academic' => AcademicSubscription::with(['student', 'academicTeacher.user', 'lesson'])->find($id),
            'course' => CourseSubscription::with(['student', 'recordedCourse', 'interactiveCourse'])->find($id),
            default => null,
        };

        return $model ? $this->normalizeSubscription($model) : null;
    }

    // ========================================
    // PRIVATE FETCH METHODS
    // ========================================

    private function fetchQuranSubscriptions(
        int $studentId,
        int $academyId,
        ?SessionSubscriptionStatus $status
    ): Collection {
        return QuranSubscription::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['student', 'quranTeacher', 'package', 'individualCircle', 'circle'])
            ->get();
    }

    private function fetchAcademicSubscriptions(
        int $studentId,
        int $academyId,
        ?SessionSubscriptionStatus $status
    ): Collection {
        return AcademicSubscription::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['student', 'academicTeacher.user', 'lesson'])
            ->get();
    }

    private function fetchCourseSubscriptions(
        int $studentId,
        int $academyId,
        ?SessionSubscriptionStatus $status
    ): Collection {
        return CourseSubscription::query()
            ->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['student', 'recordedCourse', 'interactiveCourse.assignedTeacher.user'])
            ->get();
    }

    // ========================================
    // NORMALIZATION
    // ========================================

    /**
     * Normalize any subscription model to a consistent array format
     */
    private function normalizeSubscription($subscription): array
    {
        $type = match (true) {
            $subscription instanceof QuranSubscription => 'quran',
            $subscription instanceof AcademicSubscription => 'academic',
            $subscription instanceof CourseSubscription => 'course',
            default => 'unknown',
        };

        $status = $subscription->status instanceof SessionSubscriptionStatus
            ? $subscription->status
            : SessionSubscriptionStatus::tryFrom($subscription->status);

        return [
            'id' => $subscription->id,
            'type' => $type,
            'type_label' => $this->getTypeLabel($type),
            'title' => $this->getTitle($subscription, $type),
            'status' => $status?->value ?? $subscription->status,
            'status_label' => $status?->label() ?? $subscription->status,
            'status_color' => $status?->color() ?? 'gray',
            'status_badge_classes' => $status?->badgeClasses() ?? 'bg-gray-100 text-gray-800',
            'is_active' => $status === SessionSubscriptionStatus::ACTIVE,
            'can_access' => $status?->canAccess() ?? false,
            'can_renew' => $status?->canRenew() ?? false,

            // Dates
            'start_date' => $subscription->start_date,
            'end_date' => $subscription->end_date,
            'start_date_formatted' => $subscription->start_date?->translatedFormat('d M Y'),
            'end_date_formatted' => $subscription->end_date?->translatedFormat('d M Y'),
            'days_remaining' => $this->getDaysRemaining($subscription),
            'is_expiring_soon' => $this->isExpiringSoon($subscription),
            'created_at' => $subscription->created_at,

            // Sessions (for session-based subscriptions)
            'sessions_total' => $this->getSessionsTotal($subscription, $type),
            'sessions_used' => $this->getSessionsUsed($subscription, $type),
            'sessions_remaining' => $this->getSessionsRemaining($subscription, $type),
            'progress_percent' => $this->getProgressPercent($subscription, $type),

            // People
            'student_id' => $subscription->student_id,
            'student_name' => $subscription->student?->name,
            'teacher_name' => $this->getTeacherName($subscription, $type),
            'teacher_avatar' => $this->getTeacherAvatar($subscription, $type),

            // Pricing
            'price' => $subscription->total_price ?? $subscription->price_paid ?? 0,
            'currency' => $subscription->currency ?? 'SAR',

            // Type-specific context
            'context' => $this->getContext($subscription, $type),
            'color' => $this->getColor($type),
            'icon' => $this->getIcon($type),

            // Original model for advanced operations
            'model' => $subscription,
        ];
    }

    // ========================================
    // HELPERS
    // ========================================

    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'quran' => __('اشتراك قرآني'),
            'academic' => __('اشتراك أكاديمي'),
            'course' => __('اشتراك دورة'),
            default => __('اشتراك'),
        };
    }

    private function getTitle($subscription, string $type): string
    {
        return match ($type) {
            'quran' => $subscription->package?->name
                ?? $subscription->individualCircle?->name
                ?? $subscription->circle?->name
                ?? __('اشتراك قرآني'),
            'academic' => $subscription->lesson?->name
                ?? $subscription->subject_name
                ?? __('اشتراك أكاديمي'),
            'course' => $subscription->recordedCourse?->title
                ?? $subscription->interactiveCourse?->title
                ?? __('اشتراك دورة'),
            default => __('اشتراك'),
        };
    }

    private function getTeacherName($subscription, string $type): ?string
    {
        return match ($type) {
            'quran' => $subscription->quranTeacher?->name,
            'academic' => $subscription->academicTeacher?->user?->name,
            'course' => $subscription->interactiveCourse?->assignedTeacher?->user?->name
                ?? $subscription->recordedCourse?->teacher?->name,
            default => null,
        };
    }

    private function getTeacherAvatar($subscription, string $type): ?string
    {
        return match ($type) {
            'quran' => $subscription->quranTeacher?->avatar_url,
            'academic' => $subscription->academicTeacher?->user?->avatar_url,
            'course' => $subscription->interactiveCourse?->assignedTeacher?->user?->avatar_url,
            default => null,
        };
    }

    private function getSessionsTotal($subscription, string $type): int
    {
        return match ($type) {
            'quran' => $subscription->total_sessions ?? 0,
            'academic' => $subscription->total_sessions ?? $subscription->sessions_per_week * 4 ?? 0,
            'course' => $subscription->total_lessons ?? 0,
            default => 0,
        };
    }

    private function getSessionsUsed($subscription, string $type): int
    {
        return match ($type) {
            'quran' => $subscription->sessions_used ?? 0,
            'academic' => $subscription->total_sessions_completed ?? 0,
            'course' => $subscription->completed_lessons ?? 0,
            default => 0,
        };
    }

    private function getSessionsRemaining($subscription, string $type): int
    {
        return match ($type) {
            'quran' => $subscription->sessions_remaining ?? 0,
            'academic' => max(0, ($subscription->total_sessions ?? 0) - ($subscription->total_sessions_completed ?? 0)),
            'course' => max(0, ($subscription->total_lessons ?? 0) - ($subscription->completed_lessons ?? 0)),
            default => 0,
        };
    }

    private function getProgressPercent($subscription, string $type): float
    {
        $total = $this->getSessionsTotal($subscription, $type);
        if ($total <= 0) {
            return 0;
        }

        $used = $this->getSessionsUsed($subscription, $type);

        return round(($used / $total) * 100, 1);
    }

    private function getDaysRemaining($subscription): ?int
    {
        if (! $subscription->end_date) {
            return null;
        }

        $days = now()->diffInDays($subscription->end_date, false);

        return max(0, (int) $days);
    }

    private function isExpiringSoon($subscription): bool
    {
        if (! $subscription->end_date) {
            return false;
        }

        return $subscription->end_date->lte(now()->addDays(7));
    }

    private function getContext($subscription, string $type): array
    {
        return match ($type) {
            'quran' => [
                'subscription_type' => $subscription->subscription_type, // 'individual' or 'circle'
                'is_individual' => $subscription->subscription_type === 'individual',
                'circle_name' => $subscription->circle?->name ?? $subscription->individualCircle?->name,
                'package_name' => $subscription->package?->name,
                'memorization_level' => $subscription->memorization_level,
                'has_trial' => $subscription->is_trial_active ?? false,
            ],
            'academic' => [
                'subject_name' => $subscription->subject_name,
                'grade_level' => $subscription->grade_level_name,
                'sessions_per_week' => $subscription->sessions_per_week,
                'weekly_schedule' => $subscription->weekly_schedule,
                'has_trial' => $subscription->has_trial_session,
                'trial_used' => $subscription->trial_session_used,
            ],
            'course' => [
                'course_type' => $subscription->course_type, // 'recorded' or 'interactive'
                'is_recorded' => $subscription->course_type === 'recorded',
                'is_interactive' => $subscription->course_type === 'interactive',
                'lifetime_access' => $subscription->lifetime_access ?? false,
                'attendance_count' => $subscription->attendance_count ?? 0,
                'final_grade' => $subscription->final_grade,
                'quiz_passed' => $subscription->quiz_passed ?? false,
            ],
            default => [],
        };
    }

    private function getColor(string $type): string
    {
        return match ($type) {
            'quran' => '#10B981',      // Green (Emerald)
            'academic' => '#3B82F6',   // Blue
            'course' => '#8B5CF6',     // Purple (Violet)
            default => '#6B7280',      // Gray
        };
    }

    private function getIcon(string $type): string
    {
        return match ($type) {
            'quran' => 'heroicon-o-book-open',
            'academic' => 'heroicon-o-academic-cap',
            'course' => 'heroicon-o-play-circle',
            default => 'heroicon-o-credit-card',
        };
    }

    private function buildCacheKey(...$params): string
    {
        return 'unified_subscriptions:'.md5(serialize($params));
    }

    // ========================================
    // CACHE MANAGEMENT
    // ========================================

    /**
     * Clear subscription cache for a specific student
     */
    public function clearCacheForStudent(int $studentId, int $academyId): void
    {
        Cache::forget("unified_subscriptions:student_{$studentId}_{$academyId}");
    }

    /**
     * Clear all subscription cache for an academy
     */
    public function clearCacheForAcademy(int $academyId): void
    {
        // Relies on TTL expiration without tagged cache
    }

    /**
     * Clear all unified subscription cache
     */
    public function clearAllCache(): void
    {
        // Relies on TTL expiration without tagged cache
    }
}
