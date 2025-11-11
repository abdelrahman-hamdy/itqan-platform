<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\QuranTrialRequest;
use App\Models\QuranSubscription;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class QuranTeacherOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static bool $isLazy = false;

    public function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user->isQuranTeacher()) {
            return [];
        }

        $teacher = $user->quranTeacherProfile;
        
        if (!$teacher) {
            return [];
        }

        // Get teacher-specific counts
        $pendingTrialRequests = QuranTrialRequest::where('teacher_id', $teacher->id)
            ->where('status', 'pending')
            ->count();
        
        $totalTrialRequests = QuranTrialRequest::where('teacher_id', $teacher->id)->count();
        
        $activeSubscriptions = QuranSubscription::where('quran_teacher_id', $teacher->id)
            ->where('subscription_status', 'active')
            ->where('payment_status', 'current')
            ->count();
        
        $totalSubscriptions = QuranSubscription::where('quran_teacher_id', $teacher->id)->count();
        
        // Today's sessions
        $todaySessions = QuranSession::where('quran_teacher_id', $teacher->id)
            ->whereDate('scheduled_at', today())
            ->count();
            
        // This week's sessions
        $weekSessions = QuranSession::where('quran_teacher_id', $teacher->id)
            ->whereBetween('scheduled_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])
            ->count();
        
        // Total sessions completed
        $completedSessions = QuranSession::where('quran_teacher_id', $teacher->id)
            ->where('status', 'completed')
            ->count();
        
        // Upcoming sessions (next 7 days)
        $upcomingSessions = QuranSession::where('quran_teacher_id', $teacher->id)
            ->whereBetween('scheduled_at', [
                now(),
                now()->addDays(7)
            ])
            ->where('status', 'scheduled')
            ->count();

        // Calculate growth rates
        $subscriptionsLastMonth = QuranSubscription::where('quran_teacher_id', $teacher->id)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $subscriptionGrowth = $subscriptionsLastMonth > 0 ? 
            round((($activeSubscriptions - $subscriptionsLastMonth) / $subscriptionsLastMonth) * 100, 1) : 0;

        $sessionsLastWeek = QuranSession::where('quran_teacher_id', $teacher->id)
            ->whereBetween('scheduled_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek()
            ])
            ->count();
        $sessionGrowth = $sessionsLastWeek > 0 ? 
            round((($weekSessions - $sessionsLastWeek) / $sessionsLastWeek) * 100, 1) : 0;

        return [
            Stat::make('طلبات الجلسات التجريبية', $pendingTrialRequests)
                ->description($pendingTrialRequests > 0 ? 'في انتظار الرد' : 'لا توجد طلبات معلقة')
                ->descriptionIcon($pendingTrialRequests > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingTrialRequests > 0 ? 'warning' : 'success')
                ->url('/teacher/schedule/dashboard'),

            Stat::make('الاشتراكات النشطة', $activeSubscriptions)
                ->description($subscriptionGrowth >= 0 ? "+{$subscriptionGrowth}% هذا الشهر" : "{$subscriptionGrowth}% هذا الشهر")
                ->descriptionIcon($subscriptionGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($subscriptionGrowth >= 0 ? 'success' : 'danger')
                ->chart($this->getSubscriptionChart()),

            Stat::make('جلسات اليوم', $todaySessions)
                ->description("من إجمالي {$weekSessions} جلسة هذا الأسبوع")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart($this->getSessionChart()),

            Stat::make('الجلسات القادمة', $upcomingSessions)
                ->description('خلال الأسبوع القادم')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),

            Stat::make('إجمالي الجلسات المكتملة', $completedSessions)
                ->description($sessionGrowth >= 0 ? "+{$sessionGrowth}% هذا الأسبوع" : "{$sessionGrowth}% هذا الأسبوع")
                ->descriptionIcon($sessionGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($sessionGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('التقييم العام', $teacher->rating ? round($teacher->rating, 1) : 'جديد')
                ->description($teacher->total_reviews ? "من {$teacher->total_reviews} تقييم" : 'لا يوجد تقييمات بعد')
                ->descriptionIcon('heroicon-m-star')
                ->color($teacher->rating ? ($teacher->rating >= 4 ? 'success' : 'warning') : 'gray'),
        ];
    }

    private function getSubscriptionChart(): array
    {
        $user = Auth::user();
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return [];
        }
        
        $teacher = $user->quranTeacherProfile;
        
        // Get subscription creations for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = QuranSubscription::where('quran_teacher_id', $teacher->id)
                ->whereDate('created_at', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    private function getSessionChart(): array
    {
        $user = Auth::user();
        if (!$user->isQuranTeacher() || !$user->quranTeacherProfile) {
            return [];
        }
        
        $teacher = $user->quranTeacherProfile;
        
        // Get sessions for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = QuranSession::where('quran_teacher_id', $teacher->id)
                ->whereDate('scheduled_at', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }
}