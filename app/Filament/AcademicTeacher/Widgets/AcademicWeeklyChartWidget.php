<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AcademicWeeklyChartWidget extends ChartWidget
{
    // Prevent auto-discovery - Dashboard explicitly adds this widget
    protected static bool $isDiscoverable = false;

    protected ?string $heading = 'نشاط الجلسات - آخر 7 أيام';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user->isAcademicTeacher() || ! $user->academicTeacherProfile) {
            return ['datasets' => [], 'labels' => []];
        }

        $teacher = $user->academicTeacherProfile;

        // Get data for the last 7 days
        $labels = [];
        $academicScheduledData = [];
        $courseSessionsData = [];
        $completedData = [];

        $daysAr = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $daysAr[$date->dayOfWeek];

            // Count all academic sessions for this day
            $academicCount = AcademicSession::where('academic_teacher_id', $teacher->id)
                ->whereDate('scheduled_at', $date)
                ->count();
            $academicScheduledData[] = $academicCount;

            // Count interactive course sessions for this day
            $courseCount = InteractiveCourseSession::whereHas('course', function ($q) use ($teacher) {
                $q->where('assigned_teacher_id', $teacher->id);
            })
                ->whereDate('scheduled_at', $date)
                ->count();
            $courseSessionsData[] = $courseCount;

            // Count completed sessions (both types) for this day
            $completedAcademic = AcademicSession::where('academic_teacher_id', $teacher->id)
                ->whereDate('scheduled_at', $date)
                ->where('status', SessionStatus::COMPLETED->value)
                ->count();

            $completedCourse = InteractiveCourseSession::whereHas('course', function ($q) use ($teacher) {
                $q->where('assigned_teacher_id', $teacher->id);
            })
                ->whereDate('scheduled_at', $date)
                ->where('status', SessionStatus::COMPLETED->value)
                ->count();

            $completedData[] = $completedAcademic + $completedCourse;
        }

        return [
            'datasets' => [
                [
                    'label' => 'الدروس الفردية',
                    'data' => $academicScheduledData,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'pointBackgroundColor' => '#3B82F6',
                    'pointBorderColor' => '#3B82F6',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'جلسات الدورات',
                    'data' => $courseSessionsData,
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'pointBackgroundColor' => '#F59E0B',
                    'pointBorderColor' => '#F59E0B',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'المكتملة',
                    'data' => $completedData,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'pointBackgroundColor' => '#10B981',
                    'pointBorderColor' => '#10B981',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'rtl' => true,
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                        'font' => [
                            'family' => 'Tajawal',
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'rtl' => true,
                    'bodyFont' => [
                        'family' => 'Tajawal',
                    ],
                    'titleFont' => [
                        'family' => 'Tajawal',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'family' => 'Tajawal',
                        ],
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                    'ticks' => [
                        'stepSize' => 1,
                        'precision' => 0,
                        'font' => [
                            'family' => 'Tajawal',
                        ],
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
}
