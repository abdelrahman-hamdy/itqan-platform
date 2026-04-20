<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\TeacherEarning;
use Carbon\Carbon;

/**
 * Service for teacher earnings display and reporting.
 *
 * Handles earnings statistics, grouping, filtering, and payout history
 * for the teacher-facing earnings dashboard.
 */
class TeacherEarningsDisplayService
{
    /**
     * Get real earnings statistics from TeacherEarning model.
     */
    public function getEarningsStats(string $teacherType, int $teacherId, int $academyId, ?int $year = null, ?int $month = null): array
    {
        $baseQuery = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId);

        if ($year && $month) {
            $selectedMonthEarnings = (clone $baseQuery)->forMonth($year, $month)->sum('amount');
            $selectedMonthSessions = (clone $baseQuery)->forMonth($year, $month)->count();

            $prevDate = Carbon::create($year, $month, 1)->subMonth();
            $prevMonthEarnings = (clone $baseQuery)->forMonth($prevDate->year, $prevDate->month)->sum('amount');

            $changePercent = $prevMonthEarnings > 0
                ? (($selectedMonthEarnings - $prevMonthEarnings) / $prevMonthEarnings) * 100
                : 0;
        } else {
            $selectedMonthEarnings = $baseQuery->sum('amount');
            $selectedMonthSessions = $baseQuery->count();
            $changePercent = 0;
        }

        $allTimeEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->sum('amount');

        $unpaidEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->unpaid()
            ->sum('amount');

        $finalizedEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->where('is_finalized', true)
            ->sum('amount');

        return [
            'selectedMonth' => $selectedMonthEarnings,
            'changePercent' => round($changePercent, 1),
            'allTimeEarnings' => $allTimeEarnings,
            'sessionsCount' => $selectedMonthSessions,
            'unpaidEarnings' => $unpaidEarnings,
            'finalizedEarnings' => $finalizedEarnings,
        ];
    }

    /**
     * Get earnings grouped by source (circle/course/class).
     */
    public function getEarningsGroupedBySource(string $teacherType, int $teacherId, int $academyId, $user, ?int $year = null, ?int $month = null)
    {
        $query = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->with([
                'session' => function ($morphTo) {
                    $morphTo->morphWith([
                        QuranSession::class => ['individualCircle', 'circle', 'student'],
                        AcademicSession::class => ['academicIndividualLesson.subject', 'student'],
                        InteractiveCourseSession::class => ['course'],
                    ]);
                },
            ]);

        if ($year && $month) {
            $query->forMonth($year, $month);
        }

        $earnings = $query->get();

        $grouped = [];

        foreach ($earnings as $earning) {
            $source = $this->determineEarningSource($earning, $user);

            if (! isset($grouped[$source['key']])) {
                $grouped[$source['key']] = [
                    'name' => $source['name'],
                    'type' => $source['type'],
                    'total' => 0,
                    'sessions_count' => 0,
                    'earnings' => collect([]),
                ];
            }

            $grouped[$source['key']]['total'] += $earning->amount;
            $grouped[$source['key']]['sessions_count']++;
            $grouped[$source['key']]['earnings']->push($earning);
        }

        return collect($grouped)->sortByDesc('total');
    }

    /**
     * Get available months for filtering.
     */
    public function getAvailableMonths(string $teacherType, int $teacherId, int $academyId): array
    {
        $months = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->selectRaw('YEAR(session_completed_at) as year, MONTH(session_completed_at) as month')
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $availableMonths = [];

        foreach ($months as $monthData) {
            if ($monthData->year && $monthData->month) {
                $date = Carbon::create($monthData->year, $monthData->month, 1);
                $availableMonths[] = [
                    'value' => $date->format('Y-m'),
                    'label' => $date->locale('ar')->translatedFormat('F Y'),
                ];
            }
        }

        return $availableMonths;
    }

    /**
     * Calculate monthly earnings for a teacher.
     */
    public function calculateMonthlyEarnings(string $teacherType, int $teacherId, int $academyId, Carbon $month): float
    {
        return TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->forMonth($month->year, $month->month)
            ->sum('amount');
    }

    /**
     * Eager-load map for TeacherEarning::session morphTo across the three
     * concrete session types. Use with: `->with(['session' => fn ($morphTo) =>
     * $morphTo->morphWith($service->sessionMorphMap())])`.
     */
    public function sessionMorphMap(): array
    {
        return [
            QuranSession::class => ['individualCircle', 'circle', 'student'],
            AcademicSession::class => ['academicIndividualLesson.subject', 'student'],
            InteractiveCourseSession::class => ['course'],
        ];
    }

    /**
     * Apply month/source/date-range filters to a TeacherEarning query.
     *
     * Date range overrides month when set. Source format is `{type}_{id}`
     * where type is one of: `individual_circle`, `group_circle`,
     * `academic_lesson`, `interactive_course`.
     */
    public function applyFilters($query, ?string $month, ?string $source, ?string $startDate, ?string $endDate): void
    {
        if ($startDate || $endDate) {
            if ($startDate) {
                $query->where('session_completed_at', '>=', Carbon::parse($startDate)->startOfDay());
            }
            if ($endDate) {
                $query->where('session_completed_at', '<=', Carbon::parse($endDate)->endOfDay());
            }
        } elseif ($month) {
            $parts = explode('-', $month);
            if (count($parts) === 2) {
                $query->forMonth((int) $parts[0], (int) $parts[1]);
            }
        }

        if ($source) {
            $this->applySourceFilter($query, $source);
        }
    }

    /**
     * Apply a single-source filter. Source key format: `{type}_{id}`.
     */
    private function applySourceFilter($query, string $source): void
    {
        $lastUnderscore = strrpos($source, '_');
        if ($lastUnderscore === false) {
            return;
        }

        $sourceType = substr($source, 0, $lastUnderscore);
        $sourceId = (int) substr($source, $lastUnderscore + 1);

        if ($sourceId <= 0) {
            return;
        }

        // Eloquent's whereHas() on a morphTo relation does not work — Laravel
        // can't infer which target table to subquery. Use whereHasMorph() with
        // an explicit type, which is the canonical fix for polymorphic filters.
        match ($sourceType) {
            'individual_circle' => $query->whereHasMorph(
                'session',
                [QuranSession::class],
                fn ($q) => $q->where('individual_circle_id', $sourceId),
            ),
            'group_circle' => $query->whereHasMorph(
                'session',
                [QuranSession::class],
                fn ($q) => $q->where('circle_id', $sourceId),
            ),
            'academic_lesson' => $query->whereHasMorph(
                'session',
                [AcademicSession::class],
                fn ($q) => $q->where('academic_individual_lesson_id', $sourceId),
            ),
            'interactive_course' => $query->whereHasMorph(
                'session',
                [InteractiveCourseSession::class],
                fn ($q) => $q->where('course_id', $sourceId),
            ),
            default => null,
        };
    }

    /**
     * Compute the four headline stats (total / finalized / unpaid / duration)
     * over an already-filtered TeacherEarning query in a single SQL round-trip.
     */
    public function computeStats($baseQuery): array
    {
        $row = (clone $baseQuery)->selectRaw("
            COALESCE(SUM(amount), 0) as total_earnings,
            COALESCE(SUM(CASE WHEN is_finalized = 1 THEN amount ELSE 0 END), 0) as finalized_amount,
            COALESCE(SUM(CASE WHEN is_finalized = 0 AND is_disputed = 0 THEN amount ELSE 0 END), 0) as unpaid_amount,
            COALESCE(SUM(JSON_EXTRACT(calculation_metadata, '$.duration_minutes')), 0) as total_duration_minutes
        ")->first();

        return [
            'total_earnings' => (float) $row->total_earnings,
            'finalized_amount' => (float) $row->finalized_amount,
            'unpaid_amount' => (float) $row->unpaid_amount,
            'total_duration_minutes' => (int) $row->total_duration_minutes,
        ];
    }

    /**
     * Build the dynamic sources list (for filter dropdowns) from the teacher's
     * existing earnings. Each entry: `{ value, label, type }`.
     */
    public function buildSourcesList(string $teacherType, int $teacherId, int $academyId, $user): array
    {
        $allEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->with(['session' => fn ($morphTo) => $morphTo->morphWith($this->sessionMorphMap())])
            ->get();

        $sources = [];
        foreach ($allEarnings as $earning) {
            $source = $this->determineEarningSource($earning, $user);
            if (! isset($sources[$source['key']])) {
                $sources[$source['key']] = [
                    'value' => $source['key'],
                    'label' => $source['name'],
                    'type' => $this->normalizeSourceType($source['type']),
                ];
            }
        }

        return array_values($sources);
    }

    /**
     * Map an internal source-type (`individual_circle` / `group_circle` /
     * `academic_lesson` / `interactive_course`) to the public API key set.
     */
    private function normalizeSourceType(string $internalType): string
    {
        return match ($internalType) {
            'individual_circle' => 'quran_individual',
            'group_circle' => 'quran_group',
            'academic_lesson' => 'academic_lesson',
            'interactive_course' => 'interactive_course',
            default => $internalType,
        };
    }

    /**
     * Determine the source of an earning (which circle/course/class).
     */
    public function determineEarningSource($earning, $user): array
    {
        $session = $earning->session;

        if (! $session) {
            return [
                'key' => 'unknown_'.$earning->id,
                'name' => 'جلسة محذوفة - #'.$earning->session_id,
                'type' => 'unknown',
            ];
        }

        if ($session instanceof QuranSession) {
            if ($session->individualCircle) {
                return [
                    'key' => 'individual_circle_'.$session->individualCircle->id,
                    'name' => $session->individualCircle->name ?? 'حلقة فردية - '.$session->student?->name,
                    'type' => 'individual_circle',
                ];
            } elseif ($session->circle) {
                return [
                    'key' => 'group_circle_'.$session->circle->id,
                    'name' => $session->circle->name,
                    'type' => 'group_circle',
                ];
            } else {
                // Quran session without circle (shouldn't happen normally)
                return [
                    'key' => 'quran_session_'.$session->id,
                    'name' => 'جلسة قرآن - '.$session->student?->name,
                    'type' => 'individual_circle',
                ];
            }
        }

        if ($session instanceof AcademicSession) {
            $lessonName = $session->academicIndividualLesson
                ? ($session->academicIndividualLesson->subject?->name.' - '.$session->student?->name)
                : 'درس أكاديمي - '.$session->student?->name;

            return [
                'key' => 'academic_lesson_'.($session->academic_individual_lesson_id ?? $session->id),
                'name' => $lessonName,
                'type' => 'academic_lesson',
            ];
        }

        if ($session instanceof InteractiveCourseSession) {
            $courseName = $session->course?->title ?? 'دورة تفاعلية';
            $courseId = $session->course?->id ?? $session->id;

            return [
                'key' => 'interactive_course_'.$courseId,
                'name' => $courseName,
                'type' => 'interactive_course',
            ];
        }

        return [
            'key' => 'other_'.$session->id,
            'name' => get_class($session).' - #'.$session->id,
            'type' => 'other',
        ];
    }
}
