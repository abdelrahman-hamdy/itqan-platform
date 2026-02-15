<?php

namespace App\Filament\AcademicTeacher\Resources\TeacherEarningsResource\Widgets;

use App\Models\AcademicTeacherProfile;
use App\Models\TeacherEarning;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class EarningsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            return [];
        }

        $teacherType = AcademicTeacherProfile::class;
        $teacherId = $teacherProfile->id;

        // This month's earnings
        $thisMonth = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->whereYear('earning_month', now()->year)
            ->whereMonth('earning_month', now()->month)
            ->sum('amount');

        // Last month's earnings
        $lastMonth = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->whereYear('earning_month', now()->subMonth()->year)
            ->whereMonth('earning_month', now()->subMonth()->month)
            ->sum('amount');

        // Calculate percentage change
        $changePercent = 0;
        if ($lastMonth > 0) {
            $changePercent = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
        }

        // Total unpaid earnings (not finalized and not disputed)
        $unpaidEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->unpaid()
            ->sum('amount');

        // Count of unpaid earnings
        $unpaidCount = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->unpaid()
            ->count();

        // Total all-time earnings
        $totalEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->sum('amount');

        // Total sessions this month
        $sessionsThisMonth = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->whereYear('earning_month', now()->year)
            ->whereMonth('earning_month', now()->month)
            ->count();

        $currency = getAcademyCurrency()->value;

        return [
            Stat::make('أرباح هذا الشهر', number_format($thisMonth, 2).' '.$currency)
                ->description($changePercent > 0 ? 'أعلى من الشهر السابق' : ($changePercent < 0 ? 'أقل من الشهر السابق' : 'لا تغيير'))
                ->descriptionIcon($changePercent > 0 ? 'heroicon-m-arrow-trending-up' : ($changePercent < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
                ->color($changePercent > 0 ? 'success' : ($changePercent < 0 ? 'danger' : 'gray')),

            Stat::make('أرباح معلقة (لم تصرف)', number_format($unpaidEarnings, 2).' '.$currency)
                ->description($unpaidCount.' جلسة')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('إجمالي الأرباح', number_format($totalEarnings, 2).' '.$currency)
                ->description('منذ البداية')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('جلسات هذا الشهر', $sessionsThisMonth)
                ->description('جلسة مكتملة')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),
        ];
    }

    /**
     * Get weekly chart data for the last 7 days.
     */
    protected function getWeeklyChartData(string $teacherType, int $teacherId): array
    {
        $chartData = [];
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        // Get earnings grouped by date
        $earnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->whereBetween('session_completed_at', [$startDate, $endDate])
            ->selectRaw('DATE(session_completed_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Fill in all 7 days (including days with no earnings)
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = (float) ($earnings[$date] ?? 0);
        }

        return $chartData;
    }
}
