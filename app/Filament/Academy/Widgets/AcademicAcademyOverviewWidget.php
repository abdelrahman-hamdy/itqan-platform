<?php

namespace App\Filament\Academy\Widgets;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AcademicAcademyOverviewWidget extends BaseWidget
{
    protected string $view = 'filament.widgets.collapsible-stats-overview-widget';

    protected ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected function getHeading(): ?string
    {
        return 'نظرة عامة أكاديمية';
    }

    public function getStats(): array
    {
        $academy = Auth::user()->academy;

        if (! $academy) {
            return [];
        }

        // Get academy-specific counts
        $totalTeachers = AcademicTeacherProfile::whereHas('user', function ($query) use ($academy) {
            $query->where('academy_id', $academy->id);
        })->count();

        $activeTeachers = AcademicTeacherProfile::whereHas('user', function ($query) use ($academy) {
            $query->where('academy_id', $academy->id)->where('active_status', true);
        })->count();

        $pendingApprovals = AcademicTeacherProfile::whereHas('user', function ($query) use ($academy) {
            $query->where('academy_id', $academy->id)->where('active_status', false);
        })->count();

        $activeSubscriptions = AcademicSubscription::where('academy_id', $academy->id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();

        $pendingSubscriptions = AcademicSubscription::where('academy_id', $academy->id)
            ->where('status', SessionSubscriptionStatus::PENDING->value)
            ->count();

        $totalSessions = AcademicSession::where('academy_id', $academy->id)->count();
        $sessionsThisMonth = AcademicSession::where('academy_id', $academy->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Today's sessions
        $todaySessions = AcademicSession::where('academy_id', $academy->id)
            ->whereDate('scheduled_at', today())
            ->count();

        // Interactive courses
        $activeCourses = InteractiveCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->count();

        // Calculate growth rates
        $subscriptionsLastMonth = AcademicSubscription::where('academy_id', $academy->id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $subscriptionGrowth = $subscriptionsLastMonth > 0 ?
            round((($activeSubscriptions - $subscriptionsLastMonth) / $subscriptionsLastMonth) * 100, 1) : 0;

        return [
            Stat::make(__('filament.academic_teachers_active'), $activeTeachers)
                ->description(__('filament.from_total', ['count' => $totalTeachers]))
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart($this->getTeacherChart()),

            Stat::make(__('filament.academic_subscriptions_active'), $activeSubscriptions)
                ->description($subscriptionGrowth >= 0 ? "+{$subscriptionGrowth}% ".__('filament.this_month') : "{$subscriptionGrowth}% ".__('filament.this_month'))
                ->descriptionIcon($subscriptionGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($subscriptionGrowth >= 0 ? 'success' : 'danger')
                ->chart($this->getSubscriptionChart()),

            Stat::make(__('filament.tabs.pending_approval'), $pendingApprovals)
                ->description($pendingApprovals > 0 ? __('filament.needs_review') : __('filament.no_pending_requests'))
                ->descriptionIcon($pendingApprovals > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingApprovals > 0 ? 'warning' : 'success'),

            Stat::make(__('filament.pending_subscriptions'), $pendingSubscriptions)
                ->description($pendingSubscriptions > 0 ? __('filament.tabs.pending') : __('filament.no_pending_requests'))
                ->descriptionIcon($pendingSubscriptions > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingSubscriptions > 0 ? 'warning' : 'success'),

            Stat::make(__('filament.today_sessions'), $todaySessions)
                ->description(__('filament.from_total_this_month', ['count' => $sessionsThisMonth]))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart($this->getSessionChart()),

            Stat::make(__('filament.active_courses'), $activeCourses)
                ->description(__('filament.published_courses'))
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),
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
            $count = AcademicTeacherProfile::whereHas('user', function ($query) use ($academy) {
                $query->where('academy_id', $academy->id);
            })
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
            $count = AcademicSubscription::where('academy_id', $academy->id)
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
            $count = AcademicSession::where('academy_id', $academy->id)
                ->whereDate('scheduled_at', $date)
                ->count();
            $data[] = $count;
        }

        return $data;
    }
}
