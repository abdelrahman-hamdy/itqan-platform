<?php

namespace App\Filament\Resources\QuranTeacherProfileResource\Widgets;

use App\Models\QuranTeacherProfile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuranTeachersStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected static bool $isDiscoverable = false;

    protected function getStats(): array
    {
        $total = QuranTeacherProfile::count();
        $active = QuranTeacherProfile::whereHas('user', fn ($q) => $q->where('active_status', true))->count();
        $inactive = $total - $active;

        $male = QuranTeacherProfile::where('gender', 'male')->count();
        $female = QuranTeacherProfile::where('gender', 'female')->count();

        $offersTrial = QuranTeacherProfile::where('offers_trial_sessions', true)->count();

        $avgExperience = QuranTeacherProfile::whereNotNull('teaching_experience_years')
            ->where('teaching_experience_years', '>', 0)
            ->avg('teaching_experience_years');

        return [
            Stat::make('إجمالي المعلمين', $total)
                ->description("{$active} نشط · {$inactive} غير نشط")
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('المعلمون النشطون', $active)
                ->description($total > 0 ? round(($active / $total) * 100) . '% من الإجمالي' : '-')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('غير النشطين', $inactive)
                ->description($inactive > 0 ? 'بحاجة للمراجعة' : 'لا يوجد')
                ->descriptionIcon($inactive > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($inactive > 0 ? 'warning' : 'success'),

            Stat::make('معلمون', $male)
                ->description("معلمات: {$female}")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('يقدمون جلسات تجريبية', $offersTrial)
                ->description($total > 0 ? round(($offersTrial / $total) * 100) . '% من الإجمالي' : '-')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make('متوسط سنوات الخبرة', $avgExperience ? number_format($avgExperience, 1) . ' سنة' : '-')
                ->description('للمعلمين ذوي الخبرة')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('gray'),
        ];
    }
}
