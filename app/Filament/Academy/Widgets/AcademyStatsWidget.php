<?php

namespace App\Filament\Academy\Widgets;

use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class AcademyStatsWidget extends BaseWidget
{
    protected string $view = 'filament.widgets.collapsible-stats-overview-widget';

    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'الإحصائيات العامة';
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $academy = Filament::getTenant();

        if (! $academy) {
            return [];
        }

        $cacheKey = "academy_stats_{$academy->id}";

        $data = Cache::remember($cacheKey, 60, function () use ($academy) {
            // Users for this academy
            $totalStudents = User::where('academy_id', $academy->id)->where('user_type', UserType::STUDENT->value)->count();
            $totalQuranTeachers = User::where('academy_id', $academy->id)->where('user_type', UserType::QURAN_TEACHER->value)->count();
            $totalAcademicTeachers = User::where('academy_id', $academy->id)->where('user_type', UserType::ACADEMIC_TEACHER->value)->count();
            $totalParents = User::where('academy_id', $academy->id)->where('user_type', UserType::PARENT->value)->count();
            $totalUsers = $totalStudents + $totalQuranTeachers + $totalAcademicTeachers + $totalParents;
            $activeUsers = User::where('academy_id', $academy->id)
                ->whereIn('user_type', [UserType::STUDENT->value, UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value, UserType::PARENT->value])
                ->where('active_status', true)
                ->count();
            $inactiveUsers = $totalUsers - $activeUsers;

            // Total Income for academy
            $totalIncome = Payment::where('academy_id', $academy->id)->where('status', PaymentStatus::COMPLETED->value)->sum('amount');

            // Sessions for academy
            $totalQuranSessions = QuranSession::where('academy_id', $academy->id)->count();
            $totalAcademicSessions = AcademicSession::where('academy_id', $academy->id)->count();
            // Interactive sessions through course relationship
            $totalInteractiveSessions = InteractiveCourseSession::whereHas('course', function ($q) use ($academy) {
                $q->where('academy_id', $academy->id);
            })->count();
            $totalSessions = $totalQuranSessions + $totalAcademicSessions + $totalInteractiveSessions;

            $passedQuran = QuranSession::where('academy_id', $academy->id)->where('scheduled_at', '<', now())->count();
            $passedAcademic = AcademicSession::where('academy_id', $academy->id)->where('scheduled_at', '<', now())->count();
            $passedInteractive = InteractiveCourseSession::whereHas('course', function ($q) use ($academy) {
                $q->where('academy_id', $academy->id);
            })->where('scheduled_at', '<', now())->count();
            $passedSessions = $passedQuran + $passedAcademic + $passedInteractive;
            $scheduledSessions = $totalSessions - $passedSessions;

            return compact(
                'totalUsers', 'activeUsers', 'inactiveUsers',
                'totalIncome',
                'totalSessions', 'passedSessions', 'scheduledSessions',
                'totalStudents', 'totalQuranTeachers', 'totalAcademicTeachers', 'totalParents'
            );
        });

        return [
            // Row 1: Users, Income, Sessions
            Stat::make('المستخدمين', number_format($data['totalUsers']))
                ->description($data['activeUsers'].' نشط، '.$data['inactiveUsers'].' غير نشط')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('إجمالي الإيرادات', number_format($data['totalIncome'], 0).' '.getCurrencySymbol())
                ->description('إجمالي الدخل')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('إجمالي الجلسات', number_format($data['totalSessions']))
                ->description($data['passedSessions'].' منتهية، '.$data['scheduledSessions'].' مجدولة')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            // Row 2: Students, Quran Teachers, Academic Teachers, Parents
            Stat::make('الطلاب', number_format($data['totalStudents']))
                ->description('إجمالي الطلاب')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('معلمو القرآن', number_format($data['totalQuranTeachers']))
                ->description('إجمالي معلمي القرآن')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('emerald'),

            Stat::make('المعلمون الأكاديميون', number_format($data['totalAcademicTeachers']))
                ->description('إجمالي المعلمين الأكاديميين')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('purple'),

            Stat::make('أولياء الأمور', number_format($data['totalParents']))
                ->description('إجمالي أولياء الأمور')
                ->descriptionIcon('heroicon-m-home')
                ->color('gray'),
        ];
    }
}
