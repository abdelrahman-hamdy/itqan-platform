<?php

namespace App\Filament\Academy\Widgets;

use App\Enums\SessionSubscriptionStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class QuranAcademyOverviewWidget extends BaseWidget
{
    protected string $view = 'filament.widgets.collapsible-stats-overview-widget';

    protected ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    protected function getHeading(): ?string
    {
        return 'نظرة عامة على القرآن';
    }

    public function getStats(): array
    {
        $academy = Auth::user()->academy;

        if (! $academy) {
            return [];
        }

        // Get academy-specific counts
        $totalTeachers = QuranTeacherProfile::where('academy_id', $academy->id)->count();
        $activeTeachers = QuranTeacherProfile::where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->count();

        $pendingApprovals = QuranTeacherProfile::where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', false))
            ->count();

        $totalTrialRequests = QuranTrialRequest::where('academy_id', $academy->id)->count();
        $pendingTrials = QuranTrialRequest::where('academy_id', $academy->id)
            ->where('status', SessionSubscriptionStatus::PENDING->value)
            ->count();

        $activeSubscriptions = QuranSubscription::where('academy_id', $academy->id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->where('payment_status', \App\Enums\SubscriptionPaymentStatus::PAID->value)
            ->count();

        $totalSessions = QuranSession::where('academy_id', $academy->id)->count();
        $sessionsThisMonth = QuranSession::where('academy_id', $academy->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Today's sessions
        $todaySessions = QuranSession::where('academy_id', $academy->id)
            ->whereDate('scheduled_at', today())
            ->count();

        // Calculate growth rates
        $teachersLastMonth = QuranTeacherProfile::where('academy_id', $academy->id)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $teacherGrowth = $teachersLastMonth > 0 ?
            round((($activeTeachers - $teachersLastMonth) / $teachersLastMonth) * 100, 1) : 0;

        $subscriptionsLastMonth = QuranSubscription::where('academy_id', $academy->id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $subscriptionGrowth = $subscriptionsLastMonth > 0 ?
            round((($activeSubscriptions - $subscriptionsLastMonth) / $subscriptionsLastMonth) * 100, 1) : 0;

        return [
            Stat::make('معلمو القرآن النشطون', $activeTeachers)
                ->description($teacherGrowth >= 0 ? "+{$teacherGrowth}% هذا الشهر" : "{$teacherGrowth}% هذا الشهر")
                ->descriptionIcon($teacherGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($teacherGrowth >= 0 ? 'success' : 'danger')
                ->chart($this->getTeacherChart()),

            Stat::make('الاشتراكات النشطة', $activeSubscriptions)
                ->description($subscriptionGrowth >= 0 ? "+{$subscriptionGrowth}% هذا الشهر" : "{$subscriptionGrowth}% هذا الشهر")
                ->descriptionIcon($subscriptionGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($subscriptionGrowth >= 0 ? 'success' : 'danger')
                ->chart($this->getSubscriptionChart()),

            Stat::make('طلبات الموافقة المعلقة', $pendingApprovals)
                ->description($pendingApprovals > 0 ? 'يحتاج مراجعة' : 'لا توجد طلبات معلقة')
                ->descriptionIcon($pendingApprovals > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingApprovals > 0 ? 'warning' : 'success'),

            Stat::make('طلبات الجلسات التجريبية', $pendingTrials)
                ->description($pendingTrials > 0 ? 'في الانتظار' : 'لا توجد طلبات معلقة')
                ->descriptionIcon($pendingTrials > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingTrials > 0 ? 'warning' : 'success'),

            Stat::make('جلسات اليوم', $todaySessions)
                ->description("من إجمالي {$sessionsThisMonth} جلسة هذا الشهر")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart($this->getSessionChart()),

            Stat::make('إجمالي المعلمين', $totalTeachers)
                ->description('معلمين مسجلين في الأكاديمية')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }

    private function getTeacherChart(): array
    {
        $academy = Auth::user()->academy;
        if (! $academy) {
            return [];
        }

        // Get teacher registrations for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = QuranTeacherProfile::where('academy_id', $academy->id)
                ->whereDate('created_at', $date)
                ->count();
            $data[] = $count;
        }

        return $data;
    }

    private function getSubscriptionChart(): array
    {
        $academy = Auth::user()->academy;
        if (! $academy) {
            return [];
        }

        // Get subscription creations for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = QuranSubscription::where('academy_id', $academy->id)
                ->whereDate('created_at', $date)
                ->count();
            $data[] = $count;
        }

        return $data;
    }

    private function getSessionChart(): array
    {
        $academy = Auth::user()->academy;
        if (! $academy) {
            return [];
        }

        // Get sessions for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = QuranSession::where('academy_id', $academy->id)
                ->whereDate('scheduled_at', $date)
                ->count();
            $data[] = $count;
        }

        return $data;
    }
}
