<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AcademicWeeklyChartWidget extends ChartWidget
{
    protected static ?string $heading = 'الجلسات - آخر 7 أيام';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user->isAcademicTeacher() || ! $user->academicTeacherProfile) {
            return ['datasets' => [], 'labels' => []];
        }

        $teacher = $user->academicTeacherProfile;

        // Get data for the last 7 days
        $labels = [];
        $academicCompletedData = [];
        $academicScheduledData = [];
        $courseSessionsData = [];

        $daysAr = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $daysAr[$date->dayOfWeek];

            // Count completed academic sessions for this day
            $completedCount = AcademicSession::where('academic_teacher_id', $teacher->id)
                ->whereDate('scheduled_at', $date)
                ->where('status', 'completed')
                ->count();
            $academicCompletedData[] = $completedCount;

            // Count all scheduled academic sessions for this day
            $scheduledCount = AcademicSession::where('academic_teacher_id', $teacher->id)
                ->whereDate('scheduled_at', $date)
                ->count();
            $academicScheduledData[] = $scheduledCount;

            // Count interactive course sessions for this day
            $courseCount = InteractiveCourseSession::whereHas('course', function ($q) use ($teacher) {
                $q->where('assigned_teacher_id', $teacher->id);
            })
                ->whereDate('scheduled_at', $date)
                ->count();
            $courseSessionsData[] = $courseCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'الجلسات الأكاديمية',
                    'data' => $academicScheduledData,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'جلسات الدورات',
                    'data' => $courseSessionsData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'المكتملة',
                    'data' => $academicCompletedData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
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
                    'position' => 'top',
                    'rtl' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
