<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Collection;

/**
 * Trait HasParentChildren
 *
 * Provides reusable methods for parent-child relationship operations.
 * Eliminates duplication of getChildUserIds() across multiple controllers.
 *
 * Used in:
 * - ParentPaymentController
 * - ParentSessionController
 * - ParentSubscriptionController
 */
trait HasParentChildren
{
    /**
     * Get user IDs for children based on filter.
     *
     * This method handles the common pattern of filtering children by a selected child ID
     * or returning all children when 'all' is selected.
     *
     * @param Collection $children Collection of Student models
     * @param string|int|null $selectedChildId Either 'all' or a specific child ID
     * @return array Array of user_id values
     */
    protected function getChildUserIds(Collection $children, $selectedChildId): array
    {
        if ($selectedChildId === 'all') {
            return $children->pluck('user_id')->toArray();
        }

        // Find the specific child
        $child = $children->firstWhere('id', $selectedChildId);
        if ($child) {
            return [$child->user_id];
        }

        // Fallback to all children if invalid selection
        return $children->pluck('user_id')->toArray();
    }
}
