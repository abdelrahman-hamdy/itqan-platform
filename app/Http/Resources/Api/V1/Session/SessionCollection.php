<?php

namespace App\Http\Resources\Api\V1\Session;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Session Collection Resource
 *
 * Collection wrapper for session resources with metadata
 */
class SessionCollection extends ResourceCollection
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
            ],
        ];
    }

    /**
     * Get breakdown of sessions by status
     *
     * @return array
     */
    protected function getStatusBreakdown(): array
    {
        return $this->collection->groupBy(fn($session) => $session->status->value)
            ->map(fn($group) => $group->count())
            ->toArray();
    }
}
