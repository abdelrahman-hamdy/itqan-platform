<?php

namespace App\Services\Calendar;

use BackedEnum;
use Illuminate\Support\Collection;

class CalendarFilterService
{
    /**
     * Apply filters to calendar events
     */
    public function applyFilters(Collection $events, array $filters): Collection
    {
        $filtered = $events;

        // Apply status filter
        if (isset($filters['status'])) {
            $filtered = $this->filterByStatus($filtered, $filters['status']);
        }

        // Apply search filter
        if (isset($filters['search'])) {
            $filtered = $this->filterBySearch($filtered, $filters['search']);
        }

        return $filtered->sortBy('start_time')->values();
    }

    /**
     * Filter events by status
     */
    public function filterByStatus(Collection $events, $statusFilter): Collection
    {
        $statusFilters = (array) $statusFilter;

        return $events->filter(function ($event) use ($statusFilters) {
            $eventStatus = $event['status'] ?? null;
            // Convert enum to string if needed
            if ($eventStatus instanceof BackedEnum) {
                $eventStatus = $eventStatus->value;
            } elseif (is_object($eventStatus)) {
                $eventStatus = $eventStatus->name ?? null;
            }

            return in_array($eventStatus, $statusFilters);
        });
    }

    /**
     * Filter events by search query
     */
    public function filterBySearch(Collection $events, string $search): Collection
    {
        $search = strtolower($search);

        return $events->filter(function ($event) use ($search) {
            return str_contains(strtolower($event['title']), $search) ||
                   str_contains(strtolower($event['description'] ?? ''), $search);
        });
    }

    /**
     * Check if event type should be included
     */
    public function shouldIncludeEventType(string $type, array $filters): bool
    {
        return ! isset($filters['types']) || in_array($type, (array) $filters['types']);
    }
}
