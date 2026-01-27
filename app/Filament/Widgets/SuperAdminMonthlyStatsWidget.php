<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourseSession;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuperAdminMonthlyStatsWidget extends BaseWidget
{
    protected static string $view = 'filament.widgets.collapsible-stats-overview-widget';

    protected static ?string $pollingInterval = '60s';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'إحصائيات هذا الشهر';
    }

    protected function getStats(): array
    {
        if (! AcademyContextService::isSuperAdmin()) {
            return [];
        }

        $isGlobalView = AcademyContextService::isGlobalViewMode();
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        if ($isGlobalView || ! $currentAcademy) {
            return $this->getGlobalMonthlyStats();
        }

        return $this->getAcademyMonthlyStats($currentAcademy);
    }

    private function getGlobalMonthlyStats(): array
    {
        // Active Subscriptions
        $activeQuranSubs = QuranSubscription::where('status', SessionSubscriptionStatus::ACTIVE->value)->count();
        $activeAcademicSubs = AcademicSubscription::where('status', SessionSubscriptionStatus::ACTIVE->value)->count();
        $totalActiveSubs = $activeQuranSubs + $activeAcademicSubs;

        // This Month Sessions
        $monthQuranSessions = QuranSession::whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->count();
        $monthAcademicSessions = AcademicSession::whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->count();
        $monthInteractiveSessions = InteractiveCourseSession::whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->count();
        $monthSessions = $monthQuranSessions + $monthAcademicSessions + $monthInteractiveSessions;

        // Revenue - This Month
        $thisMonthRevenue = Payment::where('status', PaymentStatus::COMPLETED->value)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // Last Month Revenue for comparison
        $lastMonthRevenue = Payment::where('status', PaymentStatus::COMPLETED->value)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');

        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        // New Users This Month
        $newStudents = User::where('user_type', 'student')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $newTeachers = User::whereIn('user_type', ['quran_teacher', 'academic_teacher'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $newParents = User::where('user_type', 'parent')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $newUsers = $newStudents + $newTeachers + $newParents;

        return [
            Stat::make('الاشتراكات النشطة', number_format($totalActiveSubs))
                ->description($activeQuranSubs.' قرآن، '.$activeAcademicSubs.' أكاديمي')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('info'),

            Stat::make('جلسات هذا الشهر', number_format($monthSessions))
                ->description($monthQuranSessions.' قرآن، '.$monthAcademicSessions.' أكاديمي، '.$monthInteractiveSessions.' تفاعلي')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),

            Stat::make('إيرادات الشهر', number_format($thisMonthRevenue, 0).' ر.س')
                ->description($revenueGrowth >= 0 ? '+'.$revenueGrowth.'% عن الشهر الماضي' : $revenueGrowth.'% عن الشهر الماضي')
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('مستخدمين جدد', number_format($newUsers))
                ->description($newStudents.' طالب، '.$newTeachers.' معلم، '.$newParents.' ولي أمر')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),
        ];
    }

    private function getAcademyMonthlyStats($academy): array
    {
        // Active Subscriptions for academy
        $activeQuranSubs = QuranSubscription::where('academy_id', $academy->id)->where('status', SessionSubscriptionStatus::ACTIVE->value)->count();
        $activeAcademicSubs = AcademicSubscription::where('academy_id', $academy->id)->where('status', SessionSubscriptionStatus::ACTIVE->value)->count();
        $totalActiveSubs = $activeQuranSubs + $activeAcademicSubs;

        // This Month Sessions for academy
        $monthQuranSessions = QuranSession::where('academy_id', $academy->id)
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->count();
        $monthAcademicSessions = AcademicSession::where('academy_id', $academy->id)
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->count();
        $monthSessions = $monthQuranSessions + $monthAcademicSessions;

        // Revenue - This Month for academy
        $thisMonthRevenue = Payment::where('academy_id', $academy->id)
            ->where('status', PaymentStatus::COMPLETED->value)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $lastMonthRevenue = Payment::where('academy_id', $academy->id)
            ->where('status', PaymentStatus::COMPLETED->value)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');

        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        // New Users This Month for academy
        $newStudents = User::where('academy_id', $academy->id)
            ->where('user_type', 'student')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $newTeachers = User::where('academy_id', $academy->id)
            ->whereIn('user_type', ['quran_teacher', 'academic_teacher'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $newParents = User::where('academy_id', $academy->id)
            ->where('user_type', 'parent')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $newUsers = $newStudents + $newTeachers + $newParents;

        return [
            Stat::make('الاشتراكات النشطة', number_format($totalActiveSubs))
                ->description($activeQuranSubs.' قرآن، '.$activeAcademicSubs.' أكاديمي')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('info'),

            Stat::make('جلسات هذا الشهر', number_format($monthSessions))
                ->description($monthQuranSessions.' قرآن، '.$monthAcademicSessions.' أكاديمي')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),

            Stat::make('إيرادات الشهر', number_format($thisMonthRevenue, 0).' ر.س')
                ->description($revenueGrowth >= 0 ? '+'.$revenueGrowth.'% عن الشهر الماضي' : $revenueGrowth.'% عن الشهر الماضي')
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('مستخدمين جدد', number_format($newUsers))
                ->description($newStudents.' طالب، '.$newTeachers.' معلم، '.$newParents.' ولي أمر')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),
        ];
    }

    public static function canView(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }
}
