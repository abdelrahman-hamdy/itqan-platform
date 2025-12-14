<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use Carbon\Carbon;
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

        if (!$teacherProfile) {
            return [];
        }

        $teacherType = 'quran_teacher';
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
            $changePercent = (($thisMonth - $lastMonth) / $lastMonth) * 100;
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

        // Last payout status
        $lastPayout = TeacherPayout::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->latest('payout_month')
            ->first();

        $lastPayoutDescription = $lastPayout
            ? __('earnings.last_payout') . ': ' . number_format($lastPayout->total_amount, 2) . ' ' . __('earnings.currency')
            : __('earnings.no_payouts_found');

        return [
            Stat::make(__('earnings.this_month'), number_format($thisMonth, 2) . ' ' . __('earnings.currency'))
                ->description($changePercent > 0 ? "+{$changePercent}%" : "{$changePercent}%")
                ->descriptionIcon($changePercent > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($changePercent > 0 ? 'success' : ($changePercent < 0 ? 'danger' : 'gray'))
                ->chart(array_fill(0, 7, rand(10, 100))), // Placeholder chart data

            Stat::make(__('earnings.all_time_earnings'), number_format($allTimeEarnings, 2) . ' ' . __('earnings.currency'))
                ->description($sessionsThisMonth . ' ' . __('earnings.sessions') . ' ' . __('earnings.this_month'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make($lastPayout ? __('earnings.status.' . $lastPayout->status) : __('earnings.no_payouts_found'), $lastPayout ? $lastPayout->month_name : '-')
                ->description($lastPayoutDescription)
                ->descriptionIcon('heroicon-m-calendar')
                ->color($lastPayout ? $lastPayout->status_color : 'gray'),
        ];
    }
}
