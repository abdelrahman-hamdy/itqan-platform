<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Exception for subscription not found errors.
 *
 * Used when a subscription cannot be found or doesn't exist.
 */
class SubscriptionNotFoundException extends Exception
{
    protected ?string $subscriptionId;
    protected ?string $subscriptionType;
    protected ?array $searchCriteria;

    public function __construct(
        string $message,
        ?string $subscriptionId = null,
        ?string $subscriptionType = null,
        ?array $searchCriteria = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->subscriptionId = $subscriptionId;
        $this->subscriptionType = $subscriptionType;
        $this->searchCriteria = $searchCriteria;
    }

    /**
     * Create exception for subscription not found by ID
     */
    public static function forId(
        string $subscriptionId,
        ?string $subscriptionType = null
    ): self {
        $typeLabel = match ($subscriptionType) {
            'quran' => 'القرآن',
            'academic' => 'الأكاديمي',
            'course' => 'الدورة',
            default => '',
        };

        $message = $typeLabel
            ? sprintf('لم يتم العثور على اشتراك %s بالمعرف: %s', $typeLabel, $subscriptionId)
            : sprintf('لم يتم العثور على الاشتراك بالمعرف: %s', $subscriptionId);

        return new self($message, $subscriptionId, $subscriptionType);
    }

    /**
     * Create exception for subscription not found by student
     */
    public static function forStudent(
        string $studentId,
        string $subscriptionType,
        ?array $additionalCriteria = null
    ): self {
        $typeLabel = match ($subscriptionType) {
            'quran' => 'القرآن',
            'academic' => 'الأكاديمي',
            'course' => 'الدورة',
            default => 'غير محدد',
        };

        $message = sprintf(
            'لا يوجد اشتراك %s نشط للطالب: %s',
            $typeLabel,
            $studentId
        );

        $searchCriteria = array_merge(
            ['student_id' => $studentId, 'type' => $subscriptionType],
            $additionalCriteria ?? []
        );

        return new self($message, null, $subscriptionType, $searchCriteria);
    }

    /**
     * Create exception for subscription not found by circle/lesson
     */
    public static function forEntity(
        string $entityType,
        string $entityId,
        string $studentId
    ): self {
        $entityLabel = match ($entityType) {
            'circle' => 'الحلقة',
            'lesson' => 'الدرس',
            'course' => 'الدورة',
            default => 'الكيان',
        };

        $message = sprintf(
            'لا يوجد اشتراك نشط للطالب في %s: %s',
            $entityLabel,
            $entityId
        );

        $searchCriteria = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'student_id' => $studentId,
        ];

        return new self($message, null, $entityType, $searchCriteria);
    }

    /**
     * Create exception for expired subscription
     */
    public static function expired(
        string $subscriptionId,
        string $subscriptionType,
        string $expiryDate
    ): self {
        $typeLabel = match ($subscriptionType) {
            'quran' => 'القرآن',
            'academic' => 'الأكاديمي',
            'course' => 'الدورة',
            default => '',
        };

        $message = sprintf(
            'انتهت صلاحية اشتراك %s بتاريخ: %s',
            $typeLabel,
            $expiryDate
        );

        return new self($message, $subscriptionId, $subscriptionType);
    }

    /**
     * Get subscription ID
     */
    public function getSubscriptionId(): ?string
    {
        return $this->subscriptionId;
    }

    /**
     * Get subscription type
     */
    public function getSubscriptionType(): ?string
    {
        return $this->subscriptionType;
    }

    /**
     * Get search criteria
     */
    public function getSearchCriteria(): ?array
    {
        return $this->searchCriteria;
    }

    /**
     * Report the exception
     */
    public function report(): void
    {
        Log::warning('Subscription not found', [
            'subscription_id' => $this->subscriptionId,
            'subscription_type' => $this->subscriptionType,
            'search_criteria' => $this->searchCriteria,
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
                'type' => 'subscription_not_found',
                'subscription_id' => $this->subscriptionId,
                'subscription_type' => $this->subscriptionType,
                'search_criteria' => $this->searchCriteria,
            ],
        ], 404);
    }
}
