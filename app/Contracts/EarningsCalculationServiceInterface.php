<?php

namespace App\Contracts;

use App\Models\BaseSession;
use App\Models\TeacherEarning;

/**
 * Interface for teacher earnings calculation service.
 *
 * Handles earnings calculation for all session types with proper validation
 * and idempotency checks.
 */
interface EarningsCalculationServiceInterface
{
    /**
     * Calculate earnings for a completed session.
     *
     * @param  BaseSession  $session  The completed session
     * @return TeacherEarning|null The created earning record or null if not eligible/failed
     */
    public function calculateSessionEarnings(BaseSession $session): ?TeacherEarning;

    /**
     * Clear teacher profile cache.
     *
     * @param  string  $type  Teacher type ('quran' or 'academic')
     * @param  int  $id  Teacher ID
     * @return void
     */
    public function clearTeacherCache(string $type, int $id): void;
}
