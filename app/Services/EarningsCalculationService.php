<?php

namespace App\Services;

use App\Contracts\EarningsCalculationServiceInterface;
use App\Models\BaseSession;
use App\Models\TeacherEarning;

/**
 * Earnings Calculation Service (Facade)
 *
 * This service acts as a facade coordinating between:
 * - EarningsCalculatorService: Pure calculation logic
 * - EarningsReportService: Report generation and persistence
 *
 * Maintains backward compatibility with existing code while delegating
 * to smaller, focused services.
 */
class EarningsCalculationService implements EarningsCalculationServiceInterface
{
    public function __construct(
        protected EarningsCalculatorService $calculator,
        protected EarningsReportService $reportService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function calculateSessionEarnings(BaseSession $session): ?TeacherEarning
    {
        return $this->reportService->calculateSessionEarnings($session);
    }

    /**
     * {@inheritdoc}
     */
    public function clearTeacherCache(string $type, int $id): void
    {
        $this->calculator->clearTeacherCache($type, $id);
    }
}
