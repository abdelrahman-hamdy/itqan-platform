<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\AcademicSession;
use App\Models\Academy;
use App\Models\InteractiveCourseSession;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\AcademyContextService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SuperAdminStatsWidget extends BaseWidget
{
    protected string $view = 'filament.widgets.collapsible-stats-overview-widget';

    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'الإحصائيات العامة';
    }

    protected function getColumns(): int|array|null
    {
        return ['default' => 2, 'sm' => 2, 'lg' => 4];
    }

    protected function getStats(): array
    {
        if (! AcademyContextService::isSuperAdmin()) {
            return [];
        }

        $isGlobalView = AcademyContextService::isGlobalViewMode();
        $currentAcademy = AcademyContextService::getCurrentAcademy();

        if ($isGlobalView || ! $currentAcademy) {
            return $this->getGlobalStats();
        }

        return $this->getAcademyStats($currentAcademy);
    }

    private function getGlobalStats(): array
    {
        $s = Cache::remember('superadmin_stats_global', 300, function () {
            $academyStats = Academy::selectRaw('COUNT(*) as total, SUM(CASE WHEN is_active = 1 AND maintenance_mode = 0 THEN 1 ELSE 0 END) as active')
                ->first();

            $users = $this->countUsersByTypeAndStatus();

            $totalIncome = Payment::where('status', PaymentStatus::COMPLETED->value)->sum('amount');

            $quranStats = QuranSession::selectRaw('COUNT(*) as total, SUM(CASE WHEN scheduled_at < ? THEN 1 ELSE 0 END) as passed', [now()])->first();
            $academicStats = AcademicSession::selectRaw('COUNT(*) as total, SUM(CASE WHEN scheduled_at < ? THEN 1 ELSE 0 END) as passed', [now()])->first();
            $interactiveStats = InteractiveCourseSession::selectRaw('COUNT(*) as total, SUM(CASE WHEN scheduled_at < ? THEN 1 ELSE 0 END) as passed', [now()])->first();

            return [
                'totalAcademies' => $academyStats->total,
                'activeAcademies' => (int) $academyStats->active,
                'users' => $users,
                'totalIncome' => $totalIncome,
                'totalSessions' => $quranStats->total + $academicStats->total + $interactiveStats->total,
                'passedSessions' => (int) $quranStats->passed + (int) $academicStats->passed + (int) $interactiveStats->passed,
            ];
        });

        $totalAcademies = $s['totalAcademies'];
        $activeAcademies = $s['activeAcademies'];
        $inactiveAcademies = $totalAcademies - $activeAcademies;
        $totalStudents = $s['users']['byType'][UserType::STUDENT->value] ?? 0;
        $totalQuranTeachers = $s['users']['byType'][UserType::QURAN_TEACHER->value] ?? 0;
        $totalAcademicTeachers = $s['users']['byType'][UserType::ACADEMIC_TEACHER->value] ?? 0;
        $totalParents = $s['users']['byType'][UserType::PARENT->value] ?? 0;
        $totalSupervisors = $s['users']['byType'][UserType::SUPERVISOR->value] ?? 0;
        $totalUsers = $totalStudents + $totalQuranTeachers + $totalAcademicTeachers + $totalParents;
        $activeUsers = ($s['users']['activeByType'][UserType::STUDENT->value] ?? 0)
            + ($s['users']['activeByType'][UserType::QURAN_TEACHER->value] ?? 0)
            + ($s['users']['activeByType'][UserType::ACADEMIC_TEACHER->value] ?? 0)
            + ($s['users']['activeByType'][UserType::PARENT->value] ?? 0);
        $inactiveUsers = $totalUsers - $activeUsers;
        $totalIncome = $s['totalIncome'];
        $totalSessions = $s['totalSessions'];
        $passedSessions = $s['passedSessions'];
        $scheduledSessions = $totalSessions - $passedSessions;

        return [
            // Row 1: Academies, Users, Income, Sessions
            Stat::make('الأكاديميات', number_format($totalAcademies))
                ->description($activeAcademies.' نشطة، '.$inactiveAcademies.' غير نشطة')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

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

            Stat::make('المشرفون', number_format($totalSupervisors))
                ->description('إجمالي المشرفين')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning'),
        ];
    }

    private function getAcademyStats($academy): array
    {
        $s = Cache::remember("superadmin_stats_academy:{$academy->id}", 300, function () use ($academy) {
            $users = $this->countUsersByTypeAndStatus($academy->id);

            $totalIncome = Payment::where('academy_id', $academy->id)
                ->where('status', PaymentStatus::COMPLETED->value)->sum('amount');

            $quranStats = QuranSession::where('academy_id', $academy->id)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN scheduled_at < ? THEN 1 ELSE 0 END) as passed', [now()])
                ->first();
            $academicStats = AcademicSession::where('academy_id', $academy->id)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN scheduled_at < ? THEN 1 ELSE 0 END) as passed', [now()])
                ->first();

            return [
                'users' => $users,
                'totalIncome' => $totalIncome,
                'totalSessions' => $quranStats->total + $academicStats->total,
                'passedSessions' => (int) $quranStats->passed + (int) $academicStats->passed,
            ];
        });

        $totalStudents = $s['users']['byType'][UserType::STUDENT->value] ?? 0;
        $totalQuranTeachers = $s['users']['byType'][UserType::QURAN_TEACHER->value] ?? 0;
        $totalAcademicTeachers = $s['users']['byType'][UserType::ACADEMIC_TEACHER->value] ?? 0;
        $totalParents = $s['users']['byType'][UserType::PARENT->value] ?? 0;
        $totalSupervisors = $s['users']['byType'][UserType::SUPERVISOR->value] ?? 0;
        $totalUsers = $totalStudents + $totalQuranTeachers + $totalAcademicTeachers + $totalParents;
        $activeUsers = ($s['users']['activeByType'][UserType::STUDENT->value] ?? 0)
            + ($s['users']['activeByType'][UserType::QURAN_TEACHER->value] ?? 0)
            + ($s['users']['activeByType'][UserType::ACADEMIC_TEACHER->value] ?? 0)
            + ($s['users']['activeByType'][UserType::PARENT->value] ?? 0);
        $inactiveUsers = $totalUsers - $activeUsers;
        $totalIncome = $s['totalIncome'];
        $totalSessions = $s['totalSessions'];
        $passedSessions = $s['passedSessions'];
        $scheduledSessions = $totalSessions - $passedSessions;

        return [
            // Row 1: Users, Income, Sessions (no academies for single academy view)
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

            Stat::make('المشرفون', number_format($totalSupervisors))
                ->description('إجمالي المشرفين')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning'),
        ];
    }

    /**
     * Batch user counts by type and active_status in a single query.
     *
     * @return array{byType: array<string, int>, activeByType: array<string, int>}
     */
    private function countUsersByTypeAndStatus(?int $academyId = null): array
    {
        $query = User::select('user_type', 'active_status', DB::raw('COUNT(*) as total'))
            ->whereIn('user_type', [
                UserType::STUDENT->value,
                UserType::QURAN_TEACHER->value,
                UserType::ACADEMIC_TEACHER->value,
                UserType::PARENT->value,
                UserType::SUPERVISOR->value,
            ])
            ->when($academyId, fn ($q) => $q->where('academy_id', $academyId))
            ->groupBy('user_type', 'active_status')
            ->get();

        $byType = [];
        $activeByType = [];
        foreach ($query as $row) {
            $byType[$row->user_type] = ($byType[$row->user_type] ?? 0) + $row->total;
            if ($row->active_status) {
                $activeByType[$row->user_type] = ($activeByType[$row->user_type] ?? 0) + $row->total;
            }
        }

        return ['byType' => $byType, 'activeByType' => $activeByType];
    }

    public static function canView(): bool
    {
        return AcademyContextService::isSuperAdmin();
    }
}
