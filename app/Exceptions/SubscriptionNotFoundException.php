<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exception for subscription not found errors.
 *
 * Used when a subscription cannot be found or doesn't exist.
 */
class SubscriptionNotFoundException extends Exception
{
    protected ?int $subscriptionId;

    protected ?string $subscriptionType;

    public function __construct(
        string $message = 'الاشتراك غير موجود',
        ?int $subscriptionId = null,
        ?string $subscriptionType = null
    ) {
        parent::__construct($message, 404);
        $this->subscriptionId = $subscriptionId;
        $this->subscriptionType = $subscriptionType;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error' => 'subscription_not_found',
        ];

        if ($this->subscriptionId) {
            $response['subscription_id'] = $this->subscriptionId;
        }

        if ($this->subscriptionType) {
            $response['subscription_type'] = $this->subscriptionType;
        }

        return response()->json($response, 404);
    }

    /**
     * Get the subscription ID.
     */
    public function getSubscriptionId(): ?int
    {
        return $this->subscriptionId;
    }

    /**
     * Get the subscription type.
     */
    public function getSubscriptionType(): ?string
    {
        return $this->subscriptionType;
    }
}
