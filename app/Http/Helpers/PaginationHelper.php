<?php

namespace App\Http\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * PaginationHelper - Standardized pagination formatting
 *
 * Provides consistent pagination structure across all API endpoints.
 * This helper ensures that both LengthAwarePaginator instances and
 * manual array pagination produce the same response format.
 *
 * Standard pagination format:
 * {
 *     "current_page": 1,
 *     "per_page": 15,
 *     "total": 150,
 *     "total_pages": 10,
 *     "has_more": true,
 *     "from": 1,
 *     "to": 15
 * }
 */
class PaginationHelper
{
    /**
     * Default items per page
     */
    public const DEFAULT_PER_PAGE = 15;

    /**
     * Maximum items per page
     */
    public const MAX_PER_PAGE = 100;

    /**
     * Build pagination metadata from a LengthAwarePaginator
     */
    public static function fromPaginator(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'total_pages' => $paginator->lastPage(),
            'has_more' => $paginator->hasMorePages(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * Build pagination metadata from array/collection with manual pagination
     *
     * @param  int  $total  Total number of items
     * @param  int  $page  Current page number
     * @param  int  $perPage  Items per page
     */
    public static function fromArray(int $total, int $page, int $perPage): array
    {
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;
        $offset = ($page - 1) * $perPage;
        $from = $total > 0 ? $offset + 1 : null;
        $to = $total > 0 ? min($offset + $perPage, $total) : null;

        return [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Paginate an array or collection manually
     *
     * Returns both the sliced items and pagination metadata
     *
     * @param array|Collection $items Items to paginate
     * @param  Request  $request  Request with page/per_page parameters
     * @param  int|null  $perPage  Override per_page from request
     * @return array{items: array, pagination: array}
     */
    public static function paginateArray($items, Request $request, ?int $perPage = null): array
    {
        $itemsArray = is_array($items) ? $items : $items->values()->all();
        $total = count($itemsArray);

        $page = self::getPage($request);
        $perPage = $perPage ?? self::getPerPage($request);
        $offset = ($page - 1) * $perPage;

        $paginatedItems = array_slice($itemsArray, $offset, $perPage);
        $pagination = self::fromArray($total, $page, $perPage);

        return [
            'items' => $paginatedItems,
            'pagination' => $pagination,
        ];
    }

    /**
     * Get validated page number from request
     */
    public static function getPage(Request $request): int
    {
        $page = (int) $request->get('page', 1);

        return max($page, 1);
    }

    /**
     * Get validated per_page value from request
     *
     * @param  int  $default  Default per page value
     * @param  int  $max  Maximum allowed per page
     */
    public static function getPerPage(
        Request $request,
        int $default = self::DEFAULT_PER_PAGE,
        int $max = self::MAX_PER_PAGE
    ): int {
        $perPage = (int) $request->get('per_page', $default);

        return min(max($perPage, 1), $max);
    }

    /**
     * Build pagination with items from paginator
     *
     * Convenience method that returns both items and pagination in standard format
     *
     * @return array{items: array, pagination: array}
     */
    public static function extractFromPaginator(LengthAwarePaginator $paginator): array
    {
        return [
            'items' => $paginator->items(),
            'pagination' => self::fromPaginator($paginator),
        ];
    }
}
