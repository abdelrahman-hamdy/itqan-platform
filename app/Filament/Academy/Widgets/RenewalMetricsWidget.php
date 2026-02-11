<?php

namespace App\Filament\Academy\Widgets;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\SavedPaymentMethod;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RenewalMetricsWidget extends BaseWidget
{
    protected static string $view = 'filament.widgets.collapsible-stats-overview-widget';

    protected static ?string $pollingInterval = '120s'; // Refresh every 2 minutes

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'مقاييس التجديد التلقائي';
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $academy = Filament::getTenant();

        if (!$academy) {
            return [];
        }

        // Get today's renewal stats from metadata
        $todayRenewals = $this->getTodayRenewalStats($academy->id);

        // Calculate at-risk subscriptions (auto-renew without saved card)
        $atRiskCount = $this->getAtRiskSubscriptionsCount($academy->id);

        // Calculate success rate
        $successRate = $todayRenewals['total'] > 0
            ? round(($todayRenewals['successful'] / $todayRenewals['total']) * 100, 1)
            : 0;

        // Determine success rate color
        $successRateColor = match (true) {
            $successRate >= 95 => 'success',
            $successRate >= 85 => 'warning',
            default => 'danger',
        };

        return [
            // Today's Renewals
            Stat::make('تجديدات اليوم', number_format($todayRenewals['total']))
                ->description($todayRenewals['successful'].' ناجح')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($todayRenewals['total'] > 0 ? 'info' : 'gray'),

            // Success Rate
            Stat::make('معدل النجاح', $successRate.'%')
                ->description($todayRenewals['total'] > 0 ? 'من إجمالي '.$todayRenewals['total'].' تجديد' : 'لا توجد تجديدات اليوم')
                ->descriptionIcon($successRateColor === 'success' ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($successRateColor),

            // At-Risk Subscriptions
            Stat::make('اشتراكات معرضة للخطر', number_format($atRiskCount))
                ->description('تجديد تلقائي بدون بطاقة')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($atRiskCount > 0 ? 'warning' : 'success'),

            // Failed Renewals Today
            Stat::make('التجديدات الفاشلة', number_format($todayRenewals['failed']))
                ->description($this->getFailureReasonsText($todayRenewals['failed']))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($todayRenewals['failed'] > 0 ? 'danger' : 'success'),
        ];
    }

    /**
     * Get today's renewal statistics from subscription metadata
     */
    private function getTodayRenewalStats(int $academyId): array
    {
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();

        // Get Quran subscriptions that might have been renewed today
        $quranRenewals = QuranSubscription::where('academy_id', $academyId)
            ->where('auto_renew', true)
            ->whereNotNull('metadata')
            ->get()
            ->filter(function ($subscription) use ($today, $tomorrow) {
                $metadata = $subscription->metadata ?? [];
                if (!isset($metadata['last_renewal_at'])) {
                    return false;
                }

                try {
                    $lastRenewal = \Carbon\Carbon::parse($metadata['last_renewal_at']);
                    return $lastRenewal->between($today, $tomorrow);
                } catch (\Exception $e) {
                    return false;
                }
            });

        // Get Academic subscriptions that might have been renewed today
        $academicRenewals = AcademicSubscription::where('academy_id', $academyId)
            ->where('auto_renew', true)
            ->whereNotNull('metadata')
            ->get()
            ->filter(function ($subscription) use ($today, $tomorrow) {
                $metadata = $subscription->metadata ?? [];
                if (!isset($metadata['last_renewal_at'])) {
                    return false;
                }

                try {
                    $lastRenewal = \Carbon\Carbon::parse($metadata['last_renewal_at']);
                    return $lastRenewal->between($today, $tomorrow);
                } catch (\Exception $e) {
                    return false;
                }
            });

        $total = $quranRenewals->count() + $academicRenewals->count();
        $successful = 0;
        $failed = 0;

        // Count successful vs failed
        foreach ($quranRenewals as $subscription) {
            $metadata = $subscription->metadata ?? [];
            if (isset($metadata['last_renewal_success']) && $metadata['last_renewal_success']) {
                $successful++;
            } else {
                $failed++;
            }
        }

        foreach ($academicRenewals as $subscription) {
            $metadata = $subscription->metadata ?? [];
            if (isset($metadata['last_renewal_success']) && $metadata['last_renewal_success']) {
                $successful++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
        ];
    }

    /**
     * Get count of subscriptions with auto-renew enabled but no saved card
     */
    private function getAtRiskSubscriptionsCount(int $academyId): int
    {
        // Get all active subscriptions with auto-renew enabled
        $quranSubscriptions = QuranSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('auto_renew', true)
            ->get();

        $academicSubscriptions = AcademicSubscription::where('academy_id', $academyId)
            ->where('status', SessionSubscriptionStatus::ACTIVE)
            ->where('auto_renew', true)
            ->get();

        $atRiskCount = 0;

        // Check each subscription's student for saved card
        foreach ($quranSubscriptions as $subscription) {
            if ($subscription->student_id && !$this->hasSavedCard($subscription->student_id)) {
                $atRiskCount++;
            }
        }

        foreach ($academicSubscriptions as $subscription) {
            if ($subscription->student_id && !$this->hasSavedCard($subscription->student_id)) {
                $atRiskCount++;
            }
        }

        return $atRiskCount;
    }

    /**
     * Check if user has a valid saved card
     */
    private function hasSavedCard(int $userId): bool
    {
        return SavedPaymentMethod::where('user_id', $userId)
            ->where('gateway', 'paymob')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Get failure reasons description text
     */
    private function getFailureReasonsText(int $failedCount): string
    {
        if ($failedCount === 0) {
            return 'لا توجد فشل في التجديدات اليوم';
        }

        return 'يرجى مراجعة السجلات للتفاصيل';
    }
}
