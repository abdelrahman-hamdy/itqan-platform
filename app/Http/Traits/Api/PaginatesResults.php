<?php

namespace App\Http\Traits\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait PaginatesResults
{
    /**
     * Apply common filters to the query
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        // Date range filtering
        if ($request->filled('date_from')) {
            $dateField = $this->getDateFilterField();
            $query->whereDate($dateField, '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $dateField = $this->getDateFilterField();
            $query->whereDate($dateField, '<=', $request->date_to);
        }

        // Status filter (supports comma-separated values)
        if ($request->filled('status')) {
            $statuses = array_map('trim', explode(',', $request->status));
            $query->whereIn('status', $statuses);
        }

        // Type filter (supports comma-separated values)
        if ($request->filled('type')) {
            $types = array_map('trim', explode(',', $request->type));
            $typeField = $this->getTypeFilterField();
            $query->whereIn($typeField, $types);
        }

        // Search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $searchableFields = $this->getSearchableFields();

            if (! empty($searchableFields)) {
                $query->where(function ($q) use ($searchableFields, $searchTerm) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                    }
                });
            }
        }

        return $query;
    }

    /**
     * Apply sorting to the query
     */
    protected function applySorting(Builder $query, Request $request): Builder
    {
        $sortBy = $request->get('sort_by', $this->getDefaultSortField());
        $sortOrder = strtolower($request->get('sort_order', 'desc'));

        // Validate sort order
        if (! in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        // Validate sort field
        $allowedSortFields = $this->getAllowedSortFields();
        if (! in_array($sortBy, $allowedSortFields)) {
            $sortBy = $this->getDefaultSortField();
        }

        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Apply eager loading based on include parameter
     */
    protected function applyIncludes(Builder $query, Request $request): Builder
    {
        if ($request->filled('include')) {
            $includes = array_map('trim', explode(',', $request->include));
            $allowedIncludes = $this->getAllowedIncludes();

            $validIncludes = array_intersect($includes, $allowedIncludes);

            if (! empty($validIncludes)) {
                $query->with($validIncludes);
            }
        }

        return $query;
    }

    /**
     * Get paginated results
     */
    protected function getPaginated(Builder $query, Request $request)
    {
        $perPage = $this->getValidatedPerPage($request);

        return $query->paginate($perPage);
    }

    /**
     * Apply all common query modifiers and return paginated results
     */
    protected function paginateWithFilters(Builder $query, Request $request)
    {
        $query = $this->applyFilters($query, $request);
        $query = $this->applySorting($query, $request);
        $query = $this->applyIncludes($query, $request);

        return $this->getPaginated($query, $request);
    }

    /**
     * Get validated per_page value
     */
    protected function getValidatedPerPage(Request $request): int
    {
        $perPage = (int) $request->get('per_page', $this->getDefaultPerPage());
        $maxPerPage = $this->getMaxPerPage();

        return min(max($perPage, 1), $maxPerPage);
    }

    /**
     * Get allowed sort fields (override in controller)
     */
    protected function getAllowedSortFields(): array
    {
        return ['id', 'created_at', 'updated_at'];
    }

    /**
     * Get default sort field (override in controller)
     */
    protected function getDefaultSortField(): string
    {
        return 'created_at';
    }

    /**
     * Get searchable fields (override in controller)
     */
    protected function getSearchableFields(): array
    {
        return [];
    }

    /**
     * Get allowed includes for eager loading (override in controller)
     */
    protected function getAllowedIncludes(): array
    {
        return [];
    }

    /**
     * Get date filter field (override in controller)
     */
    protected function getDateFilterField(): string
    {
        return 'created_at';
    }

    /**
     * Get type filter field (override in controller)
     */
    protected function getTypeFilterField(): string
    {
        return 'type';
    }

    /**
     * Get default per page value
     */
    protected function getDefaultPerPage(): int
    {
        return 15;
    }

    /**
     * Get maximum per page value
     */
    protected function getMaxPerPage(): int
    {
        return 100;
    }
}
