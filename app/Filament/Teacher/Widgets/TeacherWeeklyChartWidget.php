<?php

namespace App\Filament\Teacher\Widgets;

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class TeacherWeeklyChartWidget extends ChartWidget
{
    // Prevent auto-discovery - not needed on main dashboard
    protected static bool $isDiscoverable = false;

    protected static ?string $heading = 'الجلسات - آخر 7 أيام';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            return ['datasets' => [], 'labels' => []];
        }

        $teacher = $user->quranTeacherProfile;

        // Get data for the last 7 days
        $labels = [];
        $completedData = [];
        $scheduledData = [];

        $daysAr = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $daysAr[$date->dayOfWeek];

            // Count completed sessions for this day
            $completedCount = QuranSession::where('quran_teacher_id', $teacher->id)
                ->whereDate('scheduled_at', $date)
                ->where('status', SessionStatus::COMPLETED->value)
                ->count();
            $completedData[] = $completedCount;

            // Count all scheduled sessions for this day
            $scheduledCount = QuranSession::where('quran_teacher_id', $teacher->id)
                ->whereDate('scheduled_at', $date)
                ->count();
            $scheduledData[] = $scheduledCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'الجلسات المجدولة',
                    'data' => $scheduledData,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'الجلسات المكتملة',
                    'data' => $completedData,
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
