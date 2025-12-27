<?php

namespace App\Filament\Widgets;

use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\PaymentStatus;

class SuperAdminStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        if (!AcademyContextService::isSuperAdmin()) {
            return [];
        }

        $isGlobalView = AcademyContextService::isGlobalViewMode();
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        if ($isGlobalView || !$currentAcademy) {
            return $this->getGlobalStats();
        }

        return $this->getAcademyStats($currentAcademy);
    }

    private function getGlobalStats(): array
    {
        // Academies
        $totalAcademies = Academy::count();
        $activeAcademies = Academy::where('is_active', true)->where('maintenance_mode', false)->count();
        $inactiveAcademies = $totalAcademies - $activeAcademies;

        // Users
        $totalStudents = User::where('user_type', 'student')->count();
        $totalQuranTeachers = User::where('user_type', 'quran_teacher')->count();
        $totalAcademicTeachers = User::where('user_type', 'academic_teacher')->count();
        $totalParents = User::where('user_type', 'parent')->count();
        $totalUsers = $totalStudents + $totalQuranTeachers + $totalAcademicTeachers + $totalParents;
        $activeUsers = User::whereIn('user_type', ['student', 'quran_teacher', 'academic_teacher', 'parent'])
            ->where('status', SubscriptionStatus::ACTIVE->value)->count();
        $inactiveUsers = $totalUsers - $activeUsers;

        // Total Income (all time)
        $totalIncome = Payment::where('status', PaymentStatus::COMPLETED->value)->sum('amount');

        // Sessions
        $totalQuranSessions = QuranSession::count();
        $totalAcademicSessions = AcademicSession::count();
        $totalInteractiveSessions = InteractiveCourseSession::count();
        $totalSessions = $totalQuranSessions + $totalAcademicSessions + $totalInteractiveSessions;

        // Passed sessions (completed or status indicates past)
        $passedQuran = QuranSession::where('scheduled_at', '<', now())->count();
        $passedAcademic = AcademicSession::where('scheduled_at', '<', now())->count();
        $passedInteractive = InteractiveCourseSession::where('scheduled_at', '<', now())->count();
        $passedSessions = $passedQuran + $passedAcademic + $passedInteractive;

        // Scheduled sessions (future)
        $scheduledSessions = $totalSessions - $passedSessions;

        return [
            // Row 1: Academies, Users, Income, Sessions
            Stat::make('الأكاديميات', number_format($totalAcademies))
                ->description($activeAcademies . ' نشطة، ' . $inactiveAcademies . ' غير نشطة')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make('المستخدمين', number_format($totalUsers))
                ->description($activeUsers . ' نشط، ' . $inactiveUsers . ' غير نشط')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('إجمالي الإيرادات', number_format($totalIncome, 0) . ' ر.س')
                ->description('إجمالي الدخل')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('إجمالي الجلسات', number_format($totalSessions))
                ->description($passedSessions . ' منتهية، ' . $scheduledSessions . ' مجدولة')
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

    private function getAcademyStats($academy): array
    {
        // Users for specific academy
        $totalStudents = User::where('academy_id', $academy->id)->where('user_type', 'student')->count();
        $totalQuranTeachers = User::where('academy_id', $academy->id)->where('user_type', 'quran_teacher')->count();
        $totalAcademicTeachers = User::where('academy_id', $academy->id)->where('user_type', 'academic_teacher')->count();
        $totalParents = User::where('academy_id', $academy->id)->where('user_type', 'parent')->count();
        $totalUsers = $totalStudents + $totalQuranTeachers + $totalAcademicTeachers + $totalParents;
        $activeUsers = User::where('academy_id', $academy->id)
            ->whereIn('user_type', ['student', 'quran_teacher', 'academic_teacher', 'parent'])
            ->where('status', SubscriptionStatus::ACTIVE->value)->count();
        $inactiveUsers = $totalUsers - $activeUsers;

        // Total Income for academy
        $totalIncome = Payment::where('academy_id', $academy->id)->where('status', PaymentStatus::COMPLETED->value)->sum('amount');

        // Sessions for academy
        $totalQuranSessions = QuranSession::where('academy_id', $academy->id)->count();
        $totalAcademicSessions = AcademicSession::where('academy_id', $academy->id)->count();
        $totalSessions = $totalQuranSessions + $totalAcademicSessions;

        $passedQuran = QuranSession::where('academy_id', $academy->id)->where('scheduled_at', '<', now())->count();
        $passedAcademic = AcademicSession::where('academy_id', $academy->id)->where('scheduled_at', '<', now())->count();
        $passedSessions = $passedQuran + $passedAcademic;
        $scheduledSessions = $totalSessions - $passedSessions;

        return [
            // Row 1: Users, Income, Sessions (no academies for single academy view)
            Stat::make('المستخدمين', number_format($totalUsers))
                ->description($activeUsers . ' نشط، ' . $inactiveUsers . ' غير نشط')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('إجمالي الإيرادات', number_format($totalIncome, 0) . ' ر.س')
                ->description('إجمالي الدخل')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('إجمالي الجلسات', number_format($totalSessions))
                ->description($passedSessions . ' منتهية، ' . $scheduledSessions . ' مجدولة')
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

    public static function canView(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }
}
