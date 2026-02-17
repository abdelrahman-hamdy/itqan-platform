<?php

namespace App\Filament\Supervisor\Widgets;

use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SessionsChartWidget extends ChartWidget
{
    protected static bool $isDiscoverable = false;

    protected ?string $heading = 'الجلسات خلال الأسبوع';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $user = Auth::user();
        $profile = $user?->supervisorProfile;

        if (! $profile) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $quranTeacherIds = $profile->getAssignedQuranTeacherIds();
        $academicTeacherIds = $profile->getAssignedAcademicTeacherIds();
        $interactiveCourseIds = $profile->getDerivedInteractiveCourseIds();

        // Get academic teacher profile IDs
        $academicProfileIds = [];
        if (! empty($academicTeacherIds)) {
            $academicProfileIds = AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)
                ->pluck('id')->toArray();
        }

        // Generate last 7 days labels
        $labels = [];
        $quranData = [];
        $academicData = [];
        $interactiveData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->translatedFormat('D');

            // Quran sessions count
            $quranCount = 0;
            if (! empty($quranTeacherIds)) {
                $quranCount = QuranSession::whereIn('quran_teacher_id', $quranTeacherIds)
                    ->whereDate('scheduled_at', $date)
                    ->count();
            }
            $quranData[] = $quranCount;

            // Academic sessions count
            $academicCount = 0;
            if (! empty($academicProfileIds)) {
                $academicCount = AcademicSession::whereIn('academic_teacher_id', $academicProfileIds)
                    ->whereDate('scheduled_at', $date)
                    ->count();
            }
            $academicData[] = $academicCount;

            // Interactive course sessions count
            $interactiveCount = 0;
            if (! empty($interactiveCourseIds)) {
                $interactiveCount = InteractiveCourseSession::whereIn('course_id', $interactiveCourseIds)
                    ->whereDate('scheduled_at', $date)
                    ->count();
            }
            $interactiveData[] = $interactiveCount;
        }

        $datasets = [];

        if (! empty($quranTeacherIds)) {
            $datasets[] = [
                'label' => 'جلسات القرآن',
                'data' => $quranData,
                'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                'borderColor' => 'rgb(34, 197, 94)',
                'fill' => true,
            ];
        }

        if (! empty($academicProfileIds)) {
            $datasets[] = [
                'label' => 'جلسات أكاديمية',
                'data' => $academicData,
                'backgroundColor' => 'rgba(234, 179, 8, 0.5)',
                'borderColor' => 'rgb(234, 179, 8)',
                'fill' => true,
            ];
        }

        if (! empty($interactiveCourseIds)) {
            $datasets[] = [
                'label' => 'جلسات الدورات',
                'data' => $interactiveData,
                'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                'borderColor' => 'rgb(59, 130, 246)',
                'fill' => true,
            ];
        }

        return [
            'datasets' => $datasets,
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
