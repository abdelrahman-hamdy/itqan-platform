<?php

namespace App\Filament\Widgets;

use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\QuranSubscription;
use App\Models\QuranSession;
use App\Models\Academy;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\TrialRequestStatus;

class QuranOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    protected static bool $isDiscoverable = false;

    public function getStats(): array
    {
        // Get total counts across all academies
        $totalTeachers = QuranTeacherProfile::count();
        $activeTeachers = QuranTeacherProfile::where('is_active', true)
            ->where('approval_status', 'approved')
            ->count();
        
        $pendingApprovals = QuranTeacherProfile::where('approval_status', 'pending')->count();

        $totalTrialRequests = QuranTrialRequest::count();
        $pendingTrials = QuranTrialRequest::where('status', TrialRequestStatus::PENDING->value)->count();
        
        $activeSubscriptions = QuranSubscription::where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->where('payment_status', 'current')
            ->count();
        
        $totalSessions = QuranSession::count();
        $sessionsThisMonth = QuranSession::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        // Calculate growth rates
        $teachersLastMonth = QuranTeacherProfile::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $teacherGrowth = $teachersLastMonth > 0 ? 
            round((($totalTeachers - $teachersLastMonth) / $teachersLastMonth) * 100, 1) : 0;
        
        $subscriptionsLastMonth = QuranSubscription::where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $subscriptionGrowth = $subscriptionsLastMonth > 0 ? 
            round((($activeSubscriptions - $subscriptionsLastMonth) / $subscriptionsLastMonth) * 100, 1) : 0;

        return [
            Stat::make('معلمو القرآن المعتمدون', $activeTeachers)
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
                ->color($pendingApprovals > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.quran-teacher-profiles.index', ['tableFilters[approval_status][value]' => 'pending'])),

            Stat::make('طلبات الجلسات التجريبية', $pendingTrials)
                ->description($pendingTrials > 0 ? 'في الانتظار' : 'لا توجد طلبات معلقة')
                ->descriptionIcon($pendingTrials > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingTrials > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.quran-trial-requests.index', ['tableFilters[status][value]' => 'pending'])),

            Stat::make('جلسات هذا الشهر', $sessionsThisMonth)
                ->description("من إجمالي {$totalSessions} جلسة")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart($this->getSessionChart()),

            Stat::make('إجمالي الأكاديميات', Academy::count())
                ->description('أكاديميات مسجلة')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),
        ];
    }

    private function getTeacherChart(): array
    {
        // Get teacher registrations for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = QuranTeacherProfile::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }

    private function getSubscriptionChart(): array
    {
        // Get subscription creations for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = QuranSubscription::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }

    private function getSessionChart(): array
    {
        // Get sessions for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = QuranSession::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }
}