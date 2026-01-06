<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class EarningsOverviewWidget extends BaseWidget
{
    // Prevent auto-discovery - not needed on main dashboard
    protected static bool $isDiscoverable = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();
        $teacherProfile = $user->quranTeacherProfile;

        if (! $teacherProfile) {
            return [];
        }

        // Use the proper polymorphic class name
        $teacherType = \App\Models\QuranTeacherProfile::class;
        $teacherId = $teacherProfile->id;
        $academyId = $teacherProfile->academy_id;

        // This month's earnings
        $thisMonth = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->forMonth(now()->year, now()->month)
            ->sum('amount');

        // Last month's earnings for comparison
        $lastMonth = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->forMonth(now()->subMonth()->year, now()->subMonth()->month)
            ->sum('amount');

        // Calculate percentage change
        $changePercent = 0;
        if ($lastMonth > 0) {
            $changePercent = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
        }

        // All-time earnings
        $allTimeEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->sum('amount');

        // Completed sessions this month
        $sessionsThisMonth = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->forMonth(now()->year, now()->month)
            ->count();

        // Get last 7 days of earnings for chart
        $chartData = $this->getWeeklyChartData($teacherType, $teacherId, $academyId);

        // Last payout status
        $lastPayout = TeacherPayout::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->latest('payout_month')
            ->first();

        $lastPayoutDescription = $lastPayout
            ? __('earnings.last_payout').': '.number_format((float) $lastPayout->total_amount, 2).' '.__('earnings.currency')
            : __('earnings.no_payouts_found');

        return [
            Stat::make(__('earnings.this_month'), number_format($thisMonth, 2).' '.__('earnings.currency'))
                ->description($changePercent > 0 ? "+{$changePercent}%" : "{$changePercent}%")
                ->descriptionIcon($changePercent > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($changePercent > 0 ? 'success' : ($changePercent < 0 ? 'danger' : 'gray'))
                ->chart($chartData),

            Stat::make(__('earnings.all_time_earnings'), number_format($allTimeEarnings, 2).' '.__('earnings.currency'))
                ->description($sessionsThisMonth.' '.__('earnings.sessions').' '.__('earnings.this_month'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make($lastPayout ? __('earnings.status.'.$lastPayout->status->value) : __('earnings.no_payouts_found'), $lastPayout ? $lastPayout->month_name : '-')
                ->description($lastPayoutDescription)
                ->descriptionIcon('heroicon-m-calendar')
                ->color($lastPayout ? $lastPayout->status_color : 'gray'),
        ];
    }

    /**
     * Get weekly chart data for the last 7 days.
     */
    protected function getWeeklyChartData(string $teacherType, int $teacherId, ?int $academyId): array
    {
        $chartData = [];
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        // Get earnings grouped by date
        $query = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->whereBetween('session_completed_at', [$startDate, $endDate])
            ->selectRaw('DATE(session_completed_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date');

        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        $earnings = $query->pluck('total', 'date')->toArray();

        // Fill in all 7 days (including days with no earnings)
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = (float) ($earnings[$date] ?? 0);
        }

        return $chartData;
    }
}
