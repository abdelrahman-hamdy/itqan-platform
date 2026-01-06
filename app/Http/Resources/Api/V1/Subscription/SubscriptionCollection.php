<?php

namespace App\Http\Resources\Api\V1\Subscription;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Subscription Collection Resource
 *
 * Collection wrapper for subscription resources with metadata
 */
class SubscriptionCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'statuses' => $this->getStatusBreakdown(),
                'total_revenue' => $this->getTotalRevenue(),
            ],
        ];
    }

    /**
     * Get breakdown of subscriptions by status
     */
    protected function getStatusBreakdown(): array
    {
        return $this->collection->groupBy(fn ($subscription) => $subscription->status->value)
            ->map(fn ($group) => $group->count())
            ->toArray();
    }

    /**
     * Get total revenue from subscriptions
     */
    protected function getTotalRevenue(): float
    {
        return $this->collection->sum(fn ($subscription) => (float) $subscription->final_price);
    }
}
