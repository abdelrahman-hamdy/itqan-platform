<?php

namespace App\Filament\Resources\QuranTeacherProfileResource\Widgets;

use App\Models\QuranTeacherProfile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuranTeachersStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static bool $isDiscoverable = false;

    protected function getStats(): array
    {
        $total = QuranTeacherProfile::count();
        $active = QuranTeacherProfile::whereHas('user', fn ($q) => $q->where('active_status', true))->count();
        $inactive = $total - $active;

        $male = QuranTeacherProfile::where('gender', 'male')->count();
        $female = QuranTeacherProfile::where('gender', 'female')->count();

        $offersTrial = QuranTeacherProfile::where('offers_trial_sessions', true)->count();

        $withoutCircles = QuranTeacherProfile::whereHas('user', fn ($q) => $q->where('active_status', true))
            ->whereDoesntHave('quranCircles', fn ($q) => $q->where('status', true))
            ->whereDoesntHave('subscriptions', fn ($q) => $q->where('status', 'active'))
            ->count();

        return [
            Stat::make('إجمالي المعلمين', $total)
                ->description("{$active} نشط · {$inactive} غير نشط | {$male} معلم · {$female} معلمة")
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('يقدمون جلسات تجريبية', $offersTrial)
                ->description($total > 0 ? round(($offersTrial / $total) * 100) . '% من الإجمالي' : '-')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make('معلمون بدون طلاب', $withoutCircles)
                ->description($withoutCircles > 0 ? 'نشطون بدون حلقات أو اشتراكات' : 'جميع المعلمين لديهم طلاب')
                ->descriptionIcon($withoutCircles > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($withoutCircles > 0 ? 'warning' : 'success'),
        ];
    }
}
