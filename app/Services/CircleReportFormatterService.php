<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;

/**
 * Circle Report Formatter Service
 *
 * Handles report formatting and presentation for Quran circle data.
 * Transforms raw data into structured report arrays with proper formatting
 * and calculated statistics.
 */
class CircleReportFormatterService
{
    public function __construct(
        protected CircleDataFetcherService $dataFetcher
    ) {}

    /**
     * Generate comprehensive report for individual circle
     */
    public function getIndividualCircleReport(QuranIndividualCircle $circle, ?array $dateRange = null): array
    {
        $data = $this->dataFetcher->fetchIndividualCircleData($circle, $dateRange);

        // Calculate analytics
        $attendance = $this->dataFetcher->calculateAttendanceStats(
            $data['completed_sessions'],
            $data['session_reports']
        );

        $progress = $this->dataFetcher->calculateProgressStats(
            $circle,
            $data['completed_sessions'],
            $data['session_reports']
        );

        $trends = $this->dataFetcher->generateTrendData(
            $data['completed_sessions'],
            $data['session_reports']
        );

        return [
            'circle' => $circle,
            'student' => $data['student'],
            'subscription' => $data['subscription'],
            'teacher' => $data['teacher'],

            // Analytics
            'attendance' => $attendance,
            'progress' => array_merge($progress, [
                'current_position' => $this->formatCurrentPosition($circle),
            ]),

            // Performance Trends (for charts)
            'trends' => $trends,

            // Session History
            'sessions' => $data['sessions'],
            'session_reports' => $data['session_reports'],

            // Overall Stats
            'overall' => [
                'started_at' => $circle->started_at,
                'total_sessions_planned' => $circle->total_sessions,
                'sessions_completed' => $circle->sessions_completed,
                'sessions_remaining' => $circle->sessions_remaining,
                'progress_percentage' => $circle->progress_percentage ?? 0,
            ],
        ];
    }

    /**
     * Generate comprehensive report for group circle (all students)
     */
    public function getGroupCircleReport(QuranCircle $circle): array
    {
        $data = $this->dataFetcher->fetchGroupCircleData($circle);

        // Generate individual reports for each student
        $studentReports = [];
        $aggregateStats = [
            'total_students' => $data['students']->count(),
            'total_sessions' => $data['sessions']->count(),
            'total_attendance_rate' => 0,
            'total_average_performance' => 0,
            'students_with_reports' => 0,
        ];

        foreach ($data['students'] as $student) {
            $report = $this->getStudentReportInGroupCircle($circle, $student);
            $studentReports[$student->id] = $report;

            // Aggregate for overall stats
            if ($report['attendance']['total_sessions'] > 0) {
                $aggregateStats['total_attendance_rate'] += $report['attendance']['attendance_rate'];
                $aggregateStats['students_with_reports']++;
            }

            if (isset($report['progress']['average_overall_performance']) && $report['progress']['average_overall_performance'] > 0) {
                $aggregateStats['total_average_performance'] += $report['progress']['average_overall_performance'];
            }
        }

        // Calculate averages
        if ($aggregateStats['students_with_reports'] > 0) {
            $aggregateStats['average_attendance_rate'] = round($aggregateStats['total_attendance_rate'] / $aggregateStats['students_with_reports'], 1);
            $aggregateStats['average_performance'] = round($aggregateStats['total_average_performance'] / $aggregateStats['students_with_reports'], 1);
        } else {
            $aggregateStats['average_attendance_rate'] = 0;
            $aggregateStats['average_performance'] = 0;
        }

        return [
            'circle' => $circle,
            'students' => $data['students'],
            'sessions' => $data['sessions'],
            'student_reports' => $studentReports,
            'aggregate_stats' => $aggregateStats,

            // Overall circle info
            'overall' => [
                'created_at' => $circle->created_at,
                'sessions_completed' => $circle->sessions_completed ?? $data['sessions']->whereIn('status', [SessionStatus::COMPLETED->value])->count(),
                'enrolled_students' => $data['students']->count(),
                'max_students' => $circle->max_students,
            ],
        ];
    }

    /**
     * Generate report for specific student in group circle
     */
    public function getStudentReportInGroupCircle(QuranCircle $circle, User $student, ?array $dateRange = null): array
    {
        $data = $this->dataFetcher->fetchStudentDataInGroupCircle($circle, $student, $dateRange);

        // Calculate analytics
        $attendance = $this->dataFetcher->calculateAttendanceStats(
            $data['completed_sessions'],
            $data['session_reports']
        );

        $progress = $this->dataFetcher->calculateProgressStatsForStudent(
            $data['completed_sessions'],
            $data['session_reports']
        );

        $trends = $this->dataFetcher->generateTrendData(
            $data['completed_sessions'],
            $data['session_reports']
        );

        return [
            'student' => $data['student'],
            'enrollment' => [
                'enrolled_at' => $data['enrolled_at'],
                'status' => $data['enrollment']->status ?? 'active',
                'attendance_count' => $data['enrollment']->attendance_count ?? 0,
                'missed_sessions' => $data['enrollment']->missed_sessions ?? 0,
            ],

            // Analytics
            'attendance' => $attendance,
            'progress' => $progress,

            // Performance Trends
            'trends' => $trends,

            // Session History
            'sessions' => $data['all_sessions'],
            'session_reports' => $data['session_reports'],
        ];
    }

    /**
     * Format current position in Quran (page and face)
     */
    protected function formatCurrentPosition(QuranIndividualCircle $circle): string
    {
        if (! $circle->current_page || ! $circle->current_face) {
            return 'لم يتم تحديد الموضع بعد';
        }

        $faceName = $circle->current_face == 1 ? 'الوجه الأول' : 'الوجه الثاني';

        return "الصفحة {$circle->current_page} - {$faceName}";
    }
}
