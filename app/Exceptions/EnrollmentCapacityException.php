<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exception for enrollment capacity exceeded.
 *
 * Used when attempting to enroll in a circle or course
 * that has reached its maximum capacity.
 */
class EnrollmentCapacityException extends Exception
{
    protected ?int $circleId;

    protected int $maxCapacity;

    protected int $currentEnrollment;

    public function __construct(
        string $message = 'الحلقة ممتلئة. يرجى اختيار حلقة أخرى.',
        ?int $circleId = null,
        int $maxCapacity = 0,
        int $currentEnrollment = 0
    ) {
        parent::__construct($message, 400);
        $this->circleId = $circleId;
        $this->maxCapacity = $maxCapacity;
        $this->currentEnrollment = $currentEnrollment;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error' => 'enrollment_capacity_exceeded',
        ];

        if ($this->circleId) {
            $response['circle_id'] = $this->circleId;
        }

        if ($this->maxCapacity > 0) {
            $response['max_capacity'] = $this->maxCapacity;
            $response['current_enrollment'] = $this->currentEnrollment;
        }

        return response()->json($response, 400);
    }

    /**
     * Get the circle ID.
     */
    public function getCircleId(): ?int
    {
        return $this->circleId;
    }

    /**
     * Get the max capacity.
     */
    public function getMaxCapacity(): int
    {
        return $this->maxCapacity;
    }

    /**
     * Get the current enrollment.
     */
    public function getCurrentEnrollment(): int
    {
        return $this->currentEnrollment;
    }
}
