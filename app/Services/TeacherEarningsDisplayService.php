<?php

namespace App\Services;

use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
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

        $paidEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->whereNotNull('payout_id')
            ->sum('amount');

        $lastPayout = TeacherPayout::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->latest('payout_month')
            ->first();

        return [
            'selectedMonth' => $selectedMonthEarnings,
            'changePercent' => round($changePercent, 1),
            'allTimeEarnings' => $allTimeEarnings,
            'sessionsCount' => $selectedMonthSessions,
            'unpaidEarnings' => $unpaidEarnings,
            'paidEarnings' => $paidEarnings,
            'lastPayout' => $lastPayout,
        ];
    }

    /**
     * Get earnings grouped by source (circle/course/class).
     */
    public function getEarningsGroupedBySource(string $teacherType, int $teacherId, int $academyId, $user, ?int $year = null, ?int $month = null)
    {
        $query = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->with(['session']);

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
     * Get payout history for a teacher.
     */
    public function getPayoutHistory(string $teacherType, int $teacherId, int $academyId)
    {
        return TeacherPayout::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->latest('payout_month')
            ->limit(12)
            ->get();
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
     * Determine the source of an earning (which circle/course/class).
     */
    protected function determineEarningSource($earning, $user): array
    {
        $session = $earning->session;

        if (! $session) {
            return [
                'key' => 'unknown',
                'name' => 'مصدر غير معروف',
                'type' => 'unknown',
            ];
        }

        if ($session instanceof \App\Models\QuranSession) {
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
            }
        }

        if ($session instanceof \App\Models\AcademicSession) {
            $lessonName = $session->academicIndividualLesson
                ? ($session->academicIndividualLesson->subject?->name.' - '.$session->student?->name)
                : 'درس أكاديمي - '.$session->student?->name;

            return [
                'key' => 'academic_lesson_'.($session->academic_individual_lesson_id ?? $session->id),
                'name' => $lessonName,
                'type' => 'academic_lesson',
            ];
        }

        if ($session instanceof \App\Models\InteractiveCourseSession) {
            return [
                'key' => 'interactive_course_'.$session->course->id,
                'name' => $session->course->title,
                'type' => 'interactive_course',
            ];
        }

        return [
            'key' => 'other_'.$session->id,
            'name' => 'جلسة - '.$session->id,
            'type' => 'other',
        ];
    }
}
