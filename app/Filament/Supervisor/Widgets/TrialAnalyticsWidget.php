<?php

namespace App\Filament\Supervisor\Widgets;

use App\Enums\TrialRequestStatus;
use App\Models\QuranTrialRequest;
use App\Services\TrialConversionService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Widget showing trial session analytics for supervisors
 *
 * Displays:
 * - Total trial requests
 * - Completion rate
 * - Conversion rate (trials to subscriptions)
 * - Average trial rating
 */
class TrialAnalyticsWidget extends BaseWidget
{
    // Prevent auto-discovery - Dashboard explicitly adds this widget
    protected static bool $isDiscoverable = false;

    protected static ?int $sort = 3;

    protected function getHeading(): ?string
    {
        return 'تحليلات الجلسات التجريبية';
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $profile = $user?->supervisorProfile;

        if (!$profile) {
            return [];
        }

        // Get academy from profile
        $academyId = $profile->academy_id;
        if (!$academyId) {
            return [];
        }

        // Get conversion stats from service
        $conversionService = app(TrialConversionService::class);
        $stats = $conversionService->getConversionStats($academyId);

        // Get status breakdown
        $statusCounts = $this->getStatusBreakdown($academyId);

        // Build stats array
        $result = [];

        // Total trials stat
        $result[] = Stat::make('طلبات تجريبية', $stats['total_trials'])
            ->description($this->formatStatusBreakdown($statusCounts))
            ->descriptionIcon('heroicon-o-clipboard-document-list')
            ->color('primary');

        // Completion rate stat
        $completionColor = $stats['completion_rate'] >= 70 ? 'success' : ($stats['completion_rate'] >= 40 ? 'warning' : 'danger');
        $result[] = Stat::make('معدل الإكمال', $stats['completion_rate'] . '%')
            ->description($stats['completed_trials'] . ' من ' . $stats['total_trials'] . ' طلب')
            ->descriptionIcon('heroicon-o-check-circle')
            ->color($completionColor);

        // Conversion rate stat (completed trials to subscriptions)
        $conversionColor = $stats['conversion_rate'] >= 50 ? 'success' : ($stats['conversion_rate'] >= 25 ? 'warning' : 'danger');
        $result[] = Stat::make('معدل التحويل', $stats['conversion_rate'] . '%')
            ->description($stats['converted_trials'] . ' اشتراك من ' . $stats['completed_trials'] . ' تجربة')
            ->descriptionIcon('heroicon-o-arrow-path')
            ->color($conversionColor);

        // Average rating stat
        if ($stats['average_rating']) {
            $ratingColor = $stats['average_rating'] >= 4 ? 'success' : ($stats['average_rating'] >= 3 ? 'warning' : 'danger');
            $stars = str_repeat('★', (int) round($stats['average_rating'])) . str_repeat('☆', 5 - (int) round($stats['average_rating']));
            $result[] = Stat::make('متوسط التقييم', $stats['average_rating'] . ' / 5')
                ->description($stars)
                ->descriptionIcon('heroicon-o-star')
                ->color($ratingColor);
        } else {
            $result[] = Stat::make('متوسط التقييم', 'لا توجد تقييمات')
                ->description('لم يتم تقييم أي جلسة بعد')
                ->descriptionIcon('heroicon-o-star')
                ->color('gray');
        }

        return $result;
    }

    /**
     * Get status breakdown counts
     */
    private function getStatusBreakdown(int $academyId): array
    {
        return [
            'pending' => QuranTrialRequest::where('academy_id', $academyId)
                ->where('status', TrialRequestStatus::PENDING)
                ->count(),
            'scheduled' => QuranTrialRequest::where('academy_id', $academyId)
                ->where('status', TrialRequestStatus::SCHEDULED)
                ->count(),
            'completed' => QuranTrialRequest::where('academy_id', $academyId)
                ->where('status', TrialRequestStatus::COMPLETED)
                ->count(),
            'cancelled' => QuranTrialRequest::where('academy_id', $academyId)
                ->where('status', TrialRequestStatus::CANCELLED)
                ->count(),
        ];
    }

    /**
     * Format status breakdown for display
     */
    private function formatStatusBreakdown(array $counts): string
    {
        $parts = [];

        if ($counts['pending'] > 0) {
            $parts[] = "قيد الانتظار: {$counts['pending']}";
        }
        if ($counts['scheduled'] > 0) {
            $parts[] = "مجدولة: {$counts['scheduled']}";
        }
        if ($counts['completed'] > 0) {
            $parts[] = "مكتملة: {$counts['completed']}";
        }

        return implode(' | ', $parts) ?: 'لا توجد طلبات';
    }
}
