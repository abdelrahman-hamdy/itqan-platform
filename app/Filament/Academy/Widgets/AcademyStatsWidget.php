<?php

namespace App\Filament\Academy\Widgets;

use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\UserType;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AcademyStatsWidget extends BaseWidget
{
    protected static string $view = 'filament.widgets.collapsible-stats-overview-widget';

    protected static ?string $pollingInterval = '60s';

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

        // Users for this academy
        $totalStudents = User::where('academy_id', $academy->id)->where('user_type', UserType::STUDENT->value)->count();
        $totalQuranTeachers = User::where('academy_id', $academy->id)->where('user_type', UserType::QURAN_TEACHER->value)->count();
        $totalAcademicTeachers = User::where('academy_id', $academy->id)->where('user_type', UserType::ACADEMIC_TEACHER->value)->count();
        $totalParents = User::where('academy_id', $academy->id)->where('user_type', UserType::PARENT->value)->count();
        $totalUsers = $totalStudents + $totalQuranTeachers + $totalAcademicTeachers + $totalParents;
        $activeUsers = User::where('academy_id', $academy->id)
            ->whereIn('user_type', [UserType::STUDENT->value, UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value, UserType::PARENT->value])
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)->count();
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

        return [
            // Row 1: Users, Income, Sessions
            Stat::make('المستخدمين', number_format($totalUsers))
                ->description($activeUsers.' نشط، '.$inactiveUsers.' غير نشط')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('إجمالي الإيرادات', number_format($totalIncome, 0).' '.getCurrencySymbol())
                ->description('إجمالي الدخل')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('إجمالي الجلسات', number_format($totalSessions))
                ->description($passedSessions.' منتهية، '.$scheduledSessions.' مجدولة')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            // Row 2: Students, Quran Teachers, Academic Teachers, Parents
            Stat::make('الطلاب', number_format($totalStudents))
                ->description('إجمالي الطلاب')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('معلمو القرآن', number_format($totalQuranTeachers))
                ->description('إجمالي معلمي القرآن')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('emerald'),

            Stat::make('المعلمون الأكاديميون', number_format($totalAcademicTeachers))
                ->description('إجمالي المعلمين الأكاديميين')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('purple'),

            Stat::make('أولياء الأمور', number_format($totalParents))
                ->description('إجمالي أولياء الأمور')
                ->descriptionIcon('heroicon-m-home')
                ->color('gray'),
        ];
    }
}
