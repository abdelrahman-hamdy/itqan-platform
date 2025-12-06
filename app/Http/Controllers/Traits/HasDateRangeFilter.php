<?php

namespace App\Http\Controllers\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Date Range Filter Trait
 *
 * Provides date range filtering functionality for report controllers.
 * Eliminates code duplication across multiple report controllers.
 */
trait HasDateRangeFilter
{
    /**
     * Get date range from request parameters
     *
     * @param Request $request
     * @return array|null Array with 'start' and 'end' Carbon instances, or null for no filtering
     */
    protected function getDateRangeFromRequest(Request $request): ?array
    {
        $period = $request->get('period', 'all');

        return match ($period) {
            'this_month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],

            'last_3_months' => [
                'start' => now()->subMonths(3)->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],

            'custom' => $this->parseCustomDateRange($request),

            default => null, // 'all' or no filtering
        };
    }

    /**
     * Parse custom date range from request
     *
     * @param Request $request
     * @return array|null
     */
    protected function parseCustomDateRange(Request $request): ?array
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($startDate && $endDate) {
            return [
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::parse($endDate)->endOfDay(),
            ];
        }

        return null;
    }

    /**
     * Get date range filter view data
     *
     * @param Request $request
     * @return array
     */
    protected function getDateRangeViewData(Request $request): array
    {
        return [
            'filterPeriod' => $request->get('period', 'all'),
            'customStartDate' => $request->get('start_date'),
            'customEndDate' => $request->get('end_date'),
        ];
    }
}
