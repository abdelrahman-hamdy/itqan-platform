<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\QuranSession;
use App\Models\QuranSubscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class TeacherAnalyticsWidget extends ChartWidget
{
    // Prevent auto-discovery - not needed on dashboard
    protected static bool $isDiscoverable = false;

    protected ?string $heading = 'تحليلات الجلسات - آخر 30 يوم';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user->isQuranTeacher() || ! $user->quranTeacherProfile) {
            return ['datasets' => [], 'labels' => []];
        }

        $teacher = $user->quranTeacherProfile;

        // Get data for the last 30 days
        $labels = [];
        $sessionsData = [];
        $subscriptionsData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('m/d');

            // Count sessions for this day
            $sessionCount = QuranSession::where('quran_teacher_id', $teacher->id)
                ->whereDate('scheduled_at', $date)
                ->count();
            $sessionsData[] = $sessionCount;

            // Count new subscriptions for this day
            $subscriptionCount = QuranSubscription::where('quran_teacher_id', $teacher->id)
                ->whereDate('created_at', $date)
                ->count();
            $subscriptionsData[] = $subscriptionCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'الجلسات المكتملة',
                    'data' => $sessionsData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'الاشتراكات الجديدة',
                    'data' => $subscriptionsData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
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
