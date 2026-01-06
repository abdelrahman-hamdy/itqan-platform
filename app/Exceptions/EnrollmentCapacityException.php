<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Exception for enrollment capacity exceeded.
 *
 * Used when attempting to enroll in a circle or course
 * that has reached its maximum capacity.
 */
class EnrollmentCapacityException extends Exception
{
    protected string $entityType;

    protected string $entityId;

    protected int $currentCount;

    protected int $maxCapacity;

    protected ?string $entityName;

    public function __construct(
        string $message,
        string $entityType,
        string $entityId,
        int $currentCount,
        int $maxCapacity,
        ?string $entityName = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->currentCount = $currentCount;
        $this->maxCapacity = $maxCapacity;
        $this->entityName = $entityName;
    }

    /**
     * Create exception for full circle
     */
    public static function circleFull(
        string $circleId,
        int $currentCount,
        int $maxCapacity,
        ?string $circleName = null
    ): self {
        $nameDisplay = $circleName ? sprintf('"%s"', $circleName) : sprintf('بالمعرف %s', $circleId);

        $message = sprintf(
            'الحلقة %s ممتلئة بالكامل. العدد الحالي: %d من أصل %d',
            $nameDisplay,
            $currentCount,
            $maxCapacity
        );

        return new self($message, 'circle', $circleId, $currentCount, $maxCapacity, $circleName);
    }

    /**
     * Create exception for full course
     */
    public static function courseFull(
        string $courseId,
        int $currentCount,
        int $maxCapacity,
        ?string $courseName = null
    ): self {
        $nameDisplay = $courseName ? sprintf('"%s"', $courseName) : sprintf('بالمعرف %s', $courseId);

        $message = sprintf(
            'الدورة %s ممتلئة بالكامل. العدد الحالي: %d من أصل %d',
            $nameDisplay,
            $currentCount,
            $maxCapacity
        );

        return new self($message, 'course', $courseId, $currentCount, $maxCapacity, $courseName);
    }

    /**
     * Create exception for full lesson
     */
    public static function lessonFull(
        string $lessonId,
        int $currentCount,
        int $maxCapacity,
        ?string $lessonName = null
    ): self {
        $nameDisplay = $lessonName ? sprintf('"%s"', $lessonName) : sprintf('بالمعرف %s', $lessonId);

        $message = sprintf(
            'الدرس %s وصل للحد الأقصى من الطلاب. العدد الحالي: %d من أصل %d',
            $nameDisplay,
            $currentCount,
            $maxCapacity
        );

        return new self($message, 'lesson', $lessonId, $currentCount, $maxCapacity, $lessonName);
    }

    /**
     * Create exception for waiting list full
     */
    public static function waitingListFull(
        string $entityType,
        string $entityId,
        int $currentWaitingCount,
        int $maxWaitingCapacity,
        ?string $entityName = null
    ): self {
        $typeLabel = match ($entityType) {
            'circle' => 'الحلقة',
            'course' => 'الدورة',
            'lesson' => 'الدرس',
            default => 'الكيان',
        };

        $nameDisplay = $entityName ? sprintf('"%s"', $entityName) : sprintf('بالمعرف %s', $entityId);

        $message = sprintf(
            'قائمة الانتظار لـ %s %s ممتلئة. العدد الحالي: %d من أصل %d',
            $typeLabel,
            $nameDisplay,
            $currentWaitingCount,
            $maxWaitingCapacity
        );

        return new self(
            $message,
            $entityType.'_waiting_list',
            $entityId,
            $currentWaitingCount,
            $maxWaitingCapacity,
            $entityName
        );
    }

    /**
     * Create exception for gender-specific capacity
     */
    public static function genderCapacityFull(
        string $entityType,
        string $entityId,
        string $gender,
        int $currentCount,
        int $maxCapacity,
        ?string $entityName = null
    ): self {
        $genderLabel = $gender === 'male' ? 'الذكور' : 'الإناث';

        $typeLabel = match ($entityType) {
            'circle' => 'الحلقة',
            'course' => 'الدورة',
            'lesson' => 'الدرس',
            default => 'الكيان',
        };

        $nameDisplay = $entityName ? sprintf('"%s"', $entityName) : sprintf('بالمعرف %s', $entityId);

        $message = sprintf(
            'وصل %s %s للحد الأقصى من طلاب %s. العدد الحالي: %d من أصل %d',
            $typeLabel,
            $nameDisplay,
            $genderLabel,
            $currentCount,
            $maxCapacity
        );

        return new self($message, $entityType, $entityId, $currentCount, $maxCapacity, $entityName);
    }

    /**
     * Get entity type
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Get entity ID
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }

    /**
     * Get current count
     */
    public function getCurrentCount(): int
    {
        return $this->currentCount;
    }

    /**
     * Get max capacity
     */
    public function getMaxCapacity(): int
    {
        return $this->maxCapacity;
    }

    /**
     * Get entity name
     */
    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    /**
     * Get available slots
     */
    public function getAvailableSlots(): int
    {
        return max(0, $this->maxCapacity - $this->currentCount);
    }

    /**
     * Report the exception
     */
    public function report(): void
    {
        Log::info('Enrollment capacity reached', [
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'entity_name' => $this->entityName,
            'current_count' => $this->currentCount,
            'max_capacity' => $this->maxCapacity,
            'available_slots' => $this->getAvailableSlots(),
            'message' => $this->message,
        ]);
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
            'error' => [
                'type' => 'enrollment_capacity_exceeded',
                'entity_type' => $this->entityType,
                'entity_id' => $this->entityId,
                'entity_name' => $this->entityName,
                'current_count' => $this->currentCount,
                'max_capacity' => $this->maxCapacity,
                'available_slots' => $this->getAvailableSlots(),
            ],
        ], 409); // 409 Conflict
    }
}
