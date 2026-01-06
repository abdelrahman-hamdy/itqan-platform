<?php

namespace App\Services\Unified;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * UnifiedSessionFetchingService
 *
 * PURPOSE:
 * Eliminates 50+ duplicate session queries scattered across the codebase.
 * Provides a single, consistent way to fetch sessions of all types with:
 * - Normalized data format
 * - Caching
 * - Calendar event formatting
 *
 * USAGE:
 * $service = app(UnifiedSessionFetchingService::class);
 *
 * // Get all sessions for students
 * $sessions = $service->getForStudents($studentIds, $academyId);
 *
 * // Get upcoming sessions
 * $upcoming = $service->getUpcoming($studentIds, $academyId);
 *
 * // Get calendar events
 * $events = $service->getCalendarEvents($studentIds, $academyId, $start, $end);
 *
 * SESSION TYPES:
 * - quran: QuranSession (both group and individual circles)
 * - academic: AcademicSession (individual academic lessons)
 * - interactive: InteractiveCourseSession (group interactive courses)
 *
 * NORMALIZED OUTPUT:
 * Each session is returned as an array with consistent keys:
 * - id, type, type_label, session_code, title
 * - scheduled_at, duration_minutes, status
 * - teacher_name, teacher_avatar, student_name
 * - meeting_link, can_join, color, icon
 * - model (the original Eloquent model for advanced use)
 */
class UnifiedSessionFetchingService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get sessions for one or multiple students
     *
     * @param  array  $studentIds  Array of student user IDs
     * @param  int  $academyId  Academy ID to scope to
     * @param  SessionStatus|null  $status  Filter by status
     * @param  array  $types  Session types to include: 'quran', 'academic', 'interactive'
     * @param  Carbon|null  $from  Filter from date
     * @param  Carbon|null  $to  Filter to date
     * @param  bool  $useCache  Enable caching
     * @return Collection Normalized session array
     */
    public function getForStudents(
        array $studentIds,
        int $academyId,
        ?SessionStatus $status = null,
        array $types = ['quran', 'academic', 'interactive'],
        ?Carbon $from = null,
        ?Carbon $to = null,
        bool $useCache = true
    ): Collection {
        if (empty($studentIds)) {
            return collect();
        }

        $cacheKey = $this->buildCacheKey('students', $studentIds, $academyId, $status, $types, $from, $to);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $sessions = collect();

        if (in_array('quran', $types)) {
            $sessions = $sessions->merge($this->fetchQuranSessions($studentIds, $academyId, $status, $from, $to));
        }

        if (in_array('academic', $types)) {
            $sessions = $sessions->merge($this->fetchAcademicSessions($studentIds, $academyId, $status, $from, $to));
        }

        if (in_array('interactive', $types)) {
            $sessions = $sessions->merge($this->fetchInteractiveSessions($studentIds, $academyId, $status, $from, $to));
        }

        $result = $sessions
            ->map(fn ($session) => $this->normalizeSession($session))
            ->sortBy('scheduled_at')
            ->values();

        if ($useCache) {
            Cache::put($cacheKey, $result, self::CACHE_TTL);
        }

        return $result;
    }

    /**
     * Get sessions for a single teacher
     *
     * @param  int  $teacherId  Teacher user ID
     * @param  int  $academyId  Academy ID
     * @param  string  $teacherType  'quran' or 'academic'
     * @param  SessionStatus|null  $status  Filter by status
     * @param  Carbon|null  $from  Filter from date
     * @param  Carbon|null  $to  Filter to date
     * @return Collection Normalized session array
     */
    public function getForTeacher(
        int $teacherId,
        int $academyId,
        string $teacherType = 'quran',
        ?SessionStatus $status = null,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Collection {
        $sessions = collect();

        if ($teacherType === 'quran') {
            $sessions = QuranSession::query()
                ->where('academy_id', $academyId)
                ->where('quran_teacher_id', $teacherId)
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($from, fn ($q) => $q->where('scheduled_at', '>=', $from))
                ->when($to, fn ($q) => $q->where('scheduled_at', '<=', $to))
                ->with(['student', 'circle', 'individualCircle'])
                ->get();
        } elseif ($teacherType === 'academic') {
            // Academic teacher uses AcademicTeacherProfile, need to get profile first
            $academicSessions = AcademicSession::query()
                ->where('academy_id', $academyId)
                ->whereHas('academicTeacher', fn ($q) => $q->where('user_id', $teacherId))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($from, fn ($q) => $q->where('scheduled_at', '>=', $from))
                ->when($to, fn ($q) => $q->where('scheduled_at', '<=', $to))
                ->with(['academicTeacher.user', 'student', 'academicIndividualLesson'])
                ->get();

            $interactiveSessions = InteractiveCourseSession::query()
                ->whereHas('course', fn ($q) => $q->where('academy_id', $academyId)
                    ->whereHas('assignedTeacher', fn ($tq) => $tq->where('user_id', $teacherId))
                )
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($from, fn ($q) => $q->where('scheduled_at', '>=', $from))
                ->when($to, fn ($q) => $q->where('scheduled_at', '<=', $to))
                ->with(['course.assignedTeacher.user', 'course'])
                ->get();

            $sessions = $academicSessions->merge($interactiveSessions);
        }

        return $sessions
            ->map(fn ($session) => $this->normalizeSession($session))
            ->sortBy('scheduled_at')
            ->values();
    }

    /**
     * Get upcoming sessions for students (scheduled, within X days)
     */
    public function getUpcoming(
        array $studentIds,
        int $academyId,
        int $days = 7,
        array $types = ['quran', 'academic', 'interactive']
    ): Collection {
        return $this->getForStudents(
            studentIds: $studentIds,
            academyId: $academyId,
            status: SessionStatus::SCHEDULED,
            types: $types,
            from: now(),
            to: now()->addDays($days),
            useCache: true
        );
    }

    /**
     * Get today's sessions for students
     */
    public function getToday(
        array $studentIds,
        int $academyId,
        array $types = ['quran', 'academic', 'interactive']
    ): Collection {
        return $this->getForStudents(
            studentIds: $studentIds,
            academyId: $academyId,
            types: $types,
            from: now()->startOfDay(),
            to: now()->endOfDay(),
            useCache: false // Today's data should be fresh
        );
    }

    /**
     * Get ongoing sessions (currently live)
     */
    public function getOngoing(
        array $studentIds,
        int $academyId,
        array $types = ['quran', 'academic', 'interactive']
    ): Collection {
        return $this->getForStudents(
            studentIds: $studentIds,
            academyId: $academyId,
            status: SessionStatus::ONGOING,
            types: $types,
            useCache: false // Live data should be fresh
        );
    }

    /**
     * Get calendar events (FullCalendar compatible format)
     */
    public function getCalendarEvents(
        array $studentIds,
        int $academyId,
        Carbon $start,
        Carbon $end,
        array $types = ['quran', 'academic', 'interactive']
    ): Collection {
        return $this->getForStudents(
            studentIds: $studentIds,
            academyId: $academyId,
            types: $types,
            from: $start,
            to: $end,
            useCache: true
        )->map(fn ($session) => $this->toCalendarEvent($session));
    }

    /**
     * Get next session for a student (single upcoming session)
     */
    public function getNextSession(
        int $studentId,
        int $academyId,
        array $types = ['quran', 'academic', 'interactive']
    ): ?array {
        return $this->getUpcoming([$studentId], $academyId, 30, $types)->first();
    }

    /**
     * Count sessions by status for students
     */
    public function countByStatus(
        array $studentIds,
        int $academyId,
        array $types = ['quran', 'academic', 'interactive']
    ): array {
        $allSessions = $this->getForStudents(
            studentIds: $studentIds,
            academyId: $academyId,
            types: $types,
            useCache: true
        );

        return [
            'scheduled' => $allSessions->where('status', SessionStatus::SCHEDULED->value)->count(),
            'ongoing' => $allSessions->where('status', SessionStatus::ONGOING->value)->count(),
            'completed' => $allSessions->where('status', SessionStatus::COMPLETED->value)->count(),
            'cancelled' => $allSessions->where('status', SessionStatus::CANCELLED->value)->count(),
            'total' => $allSessions->count(),
        ];
    }

    // ========================================
    // PRIVATE FETCH METHODS
    // ========================================

    private function fetchQuranSessions(
        array $studentIds,
        int $academyId,
        ?SessionStatus $status,
        ?Carbon $from,
        ?Carbon $to
    ): Collection {
        return QuranSession::query()
            ->where('academy_id', $academyId)
            ->where(function ($query) use ($studentIds) {
                // Direct student assignment (individual circles)
                $query->whereIn('student_id', $studentIds)
                    // Or through group circle membership
                    ->orWhereHas('circle.students', fn ($q) => $q->whereIn('user_id', $studentIds));
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($from, fn ($q) => $q->where('scheduled_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('scheduled_at', '<=', $to))
            ->with(['quranTeacher', 'student', 'circle', 'individualCircle'])
            ->get();
    }

    private function fetchAcademicSessions(
        array $studentIds,
        int $academyId,
        ?SessionStatus $status,
        ?Carbon $from,
        ?Carbon $to
    ): Collection {
        return AcademicSession::query()
            ->where('academy_id', $academyId)
            ->whereIn('student_id', $studentIds)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($from, fn ($q) => $q->where('scheduled_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('scheduled_at', '<=', $to))
            ->with(['academicTeacher.user', 'student', 'academicIndividualLesson'])
            ->get();
    }

    private function fetchInteractiveSessions(
        array $studentIds,
        int $academyId,
        ?SessionStatus $status,
        ?Carbon $from,
        ?Carbon $to
    ): Collection {
        return InteractiveCourseSession::query()
            ->whereHas('course', fn ($q) => $q->where('academy_id', $academyId))
            ->whereHas('course.enrollments', fn ($q) => $q->whereIn('student_id', $studentIds)->where('status', 'enrolled')
            )
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($from, fn ($q) => $q->where('scheduled_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('scheduled_at', '<=', $to))
            ->with(['course.assignedTeacher.user', 'course'])
            ->get();
    }

    // ========================================
    // NORMALIZATION
    // ========================================

    /**
     * Normalize any session model to a consistent array format
     */
    private function normalizeSession($session): array
    {
        $type = match (true) {
            $session instanceof QuranSession => 'quran',
            $session instanceof AcademicSession => 'academic',
            $session instanceof InteractiveCourseSession => 'interactive',
            default => 'unknown',
        };

        $canJoin = $this->canJoinSession($session);

        return [
            'id' => $session->id,
            'type' => $type,
            'type_label' => $this->getTypeLabel($type),
            'session_code' => $session->session_code ?? null,
            'title' => $this->getSessionTitle($session, $type),
            'scheduled_at' => $session->scheduled_at,
            'scheduled_at_formatted' => $session->scheduled_at?->translatedFormat('l، d M H:i'),
            'scheduled_at_date' => $session->scheduled_at?->translatedFormat('d M Y'),
            'scheduled_at_time' => $session->scheduled_at?->format('H:i'),
            'duration_minutes' => $session->duration_minutes ?? 30,
            'status' => $session->status instanceof SessionStatus ? $session->status->value : $session->status,
            'status_label' => $session->status instanceof SessionStatus ? $session->status->getLabel() : $session->status,
            'teacher_name' => $this->getTeacherName($session, $type),
            'teacher_avatar' => $this->getTeacherAvatar($session, $type),
            'student_name' => $session->student?->name,
            'student_id' => $session->student_id ?? null,
            'meeting_link' => $session->meeting_link,
            'meeting_room_name' => $session->meeting_room_name,
            'can_join' => $canJoin,
            'color' => $this->getColor($type),
            'icon' => $this->getIcon($type),
            'context' => $this->getContext($session, $type),
            'model' => $session, // Full model for advanced use
        ];
    }

    /**
     * Convert normalized session to FullCalendar event format
     */
    private function toCalendarEvent(array $normalizedSession): array
    {
        $scheduledAt = $normalizedSession['scheduled_at'];

        return [
            'id' => "{$normalizedSession['type']}_{$normalizedSession['id']}",
            'title' => $normalizedSession['title'],
            'start' => $scheduledAt?->toIso8601String(),
            'end' => $scheduledAt?->addMinutes($normalizedSession['duration_minutes'])->toIso8601String(),
            'backgroundColor' => $normalizedSession['color'],
            'borderColor' => $normalizedSession['color'],
            'textColor' => '#ffffff',
            'extendedProps' => [
                'type' => $normalizedSession['type'],
                'type_label' => $normalizedSession['type_label'],
                'session_id' => $normalizedSession['id'],
                'session_code' => $normalizedSession['session_code'],
                'teacher' => $normalizedSession['teacher_name'],
                'student' => $normalizedSession['student_name'],
                'status' => $normalizedSession['status'],
                'status_label' => $normalizedSession['status_label'],
                'meeting_link' => $normalizedSession['meeting_link'],
                'can_join' => $normalizedSession['can_join'],
                'context' => $normalizedSession['context'],
            ],
        ];
    }

    // ========================================
    // HELPERS
    // ========================================

    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'quran' => __('جلسة قرآنية'),
            'academic' => __('جلسة أكاديمية'),
            'interactive' => __('جلسة دورة تفاعلية'),
            default => __('جلسة'),
        };
    }

    private function getSessionTitle($session, string $type): string
    {
        return match ($type) {
            'quran' => $session->individualCircle?->name
                ?? $session->circle?->name
                ?? $session->session_code
                ?? __('جلسة قرآنية'),
            'academic' => $session->academicIndividualLesson?->subject
                ?? $session->session_code
                ?? __('جلسة أكاديمية'),
            'interactive' => $session->course?->title
                ?? $session->title
                ?? __('جلسة دورة تفاعلية'),
            default => $session->session_code ?? __('جلسة'),
        };
    }

    private function getTeacherName($session, string $type): ?string
    {
        return match ($type) {
            'quran' => $session->quranTeacher?->name,
            'academic' => $session->academicTeacher?->user?->name,
            'interactive' => $session->course?->assignedTeacher?->user?->name,
            default => null,
        };
    }

    private function getTeacherAvatar($session, string $type): ?string
    {
        return match ($type) {
            'quran' => $session->quranTeacher?->avatar_url,
            'academic' => $session->academicTeacher?->user?->avatar_url,
            'interactive' => $session->course?->assignedTeacher?->user?->avatar_url,
            default => null,
        };
    }

    private function getColor(string $type): string
    {
        return match ($type) {
            'quran' => '#10B981',      // Green (Emerald)
            'academic' => '#3B82F6',   // Blue
            'interactive' => '#8B5CF6', // Purple (Violet)
            default => '#6B7280',      // Gray
        };
    }

    private function getIcon(string $type): string
    {
        return match ($type) {
            'quran' => 'heroicon-o-book-open',
            'academic' => 'heroicon-o-academic-cap',
            'interactive' => 'heroicon-o-play-circle',
            default => 'heroicon-o-calendar',
        };
    }

    /**
     * Get additional context based on session type
     */
    private function getContext($session, string $type): array
    {
        return match ($type) {
            'quran' => [
                'circle_name' => $session->circle?->name ?? $session->individualCircle?->name,
                'circle_type' => $session->session_type, // 'individual' or 'circle'
                'is_group' => $session->session_type === 'circle',
            ],
            'academic' => [
                'lesson_name' => $session->academicIndividualLesson?->name,
                'subject' => $session->academicIndividualLesson?->subject,
            ],
            'interactive' => [
                'course_title' => $session->course?->title,
                'session_number' => $session->session_number,
                'enrollment_count' => $session->course?->enrollments_count ?? 0,
            ],
            default => [],
        };
    }

    /**
     * Determine if a session can be joined
     */
    private function canJoinSession($session): bool
    {
        // Must be ongoing or scheduled within 15 minutes
        $status = $session->status instanceof SessionStatus ? $session->status : SessionStatus::tryFrom($session->status);

        if ($status === SessionStatus::ONGOING) {
            return true;
        }

        if ($status === SessionStatus::SCHEDULED && $session->scheduled_at) {
            $minutesUntilStart = now()->diffInMinutes($session->scheduled_at, false);

            return $minutesUntilStart <= 15 && $minutesUntilStart >= -60; // 15 min before to 60 min after
        }

        return false;
    }

    private function buildCacheKey(...$params): string
    {
        return 'unified_sessions:'.md5(serialize($params));
    }

    // ========================================
    // CACHE MANAGEMENT
    // ========================================

    /**
     * Clear all session cache for a specific student
     */
    public function clearCacheForStudent(int $studentId, int $academyId): void
    {
        // Since we use md5 cache keys, we can't easily clear by tag
        // For now, use a pattern to invalidate (or switch to tagged cache later)
        Cache::forget("unified_sessions:student_{$studentId}_{$academyId}");
    }

    /**
     * Clear all session cache for an academy
     */
    public function clearCacheForAcademy(int $academyId): void
    {
        // This would require tagged caching for proper implementation
        // For now, rely on TTL expiration
    }

    /**
     * Clear all unified session cache
     */
    public function clearAllCache(): void
    {
        // This would require tagged caching for proper implementation
        // For now, rely on TTL expiration
    }
}
