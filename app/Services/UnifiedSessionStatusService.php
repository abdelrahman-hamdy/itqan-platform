<?php

namespace App\Services;

use App\Contracts\SessionStatusServiceInterface;
use App\Contracts\UnifiedSessionStatusServiceInterface;
use App\Models\BaseSession;
use Illuminate\Support\Collection;

/**
 * Unified Session Status Service (Facade)
 *
 * This service acts as a facade coordinating between:
 * - SessionTransitionService: Individual status transitions
 * - SessionSchedulerService: Batch processing for cron jobs
 *
 * Maintains backward compatibility with existing code while delegating
 * to smaller, focused services.
 */
class UnifiedSessionStatusService implements SessionStatusServiceInterface, UnifiedSessionStatusServiceInterface
{
    public function __construct(
        protected SessionTransitionService $transitionService,
        protected SessionSchedulerService $schedulerService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function transitionToReady(BaseSession $session, bool $throwOnError = false): bool
    {
        return $this->transitionService->transitionToReady($session, $throwOnError);
    }

    /**
     * {@inheritdoc}
     */
    public function transitionToOngoing(BaseSession $session, bool $throwOnError = false): bool
    {
        return $this->transitionService->transitionToOngoing($session, $throwOnError);
    }

    /**
     * {@inheritdoc}
     */
    public function transitionToCompleted(BaseSession $session, bool $throwOnError = false): bool
    {
        return $this->transitionService->transitionToCompleted($session, $throwOnError);
    }

    /**
     * {@inheritdoc}
     */
    public function transitionToCancelled(
        BaseSession $session,
        ?string $reason = null,
        ?int $cancelledBy = null,
        bool $throwOnError = false
    ): bool {
        return $this->transitionService->transitionToCancelled($session, $reason, $cancelledBy, $throwOnError);
    }

    /**
     * {@inheritdoc}
     */
    public function transitionToAbsent(BaseSession $session): bool
    {
        return $this->transitionService->transitionToAbsent($session);
    }

    /**
     * {@inheritdoc}
     */
    public function shouldTransitionToReady(BaseSession $session): bool
    {
        return $this->schedulerService->shouldTransitionToReady($session);
    }

    /**
     * {@inheritdoc}
     */
    public function shouldTransitionToAbsent(BaseSession $session): bool
    {
        return $this->schedulerService->shouldTransitionToAbsent($session);
    }

    /**
     * {@inheritdoc}
     */
    public function shouldAutoComplete(BaseSession $session): bool
    {
        return $this->schedulerService->shouldAutoComplete($session);
    }

    /**
     * {@inheritdoc}
     */
    public function processStatusTransitions(Collection $sessions): array
    {
        return $this->schedulerService->processStatusTransitions($sessions);
    }
}
