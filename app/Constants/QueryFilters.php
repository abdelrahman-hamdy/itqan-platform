<?php

namespace App\Constants;

/**
 * Centralized query filter parameter names for consistent API/form handling.
 */
final class QueryFilters
{
    // ============================================
    // Common Filter Parameters
    // ============================================

    public const STATUS = 'status';
    public const TYPE = 'type';
    public const SEARCH = 'search';
    public const QUERY = 'q';

    // ============================================
    // Date Range Filters
    // ============================================

    public const DATE_FROM = 'date_from';
    public const DATE_TO = 'date_to';
    public const START_DATE = 'start_date';
    public const END_DATE = 'end_date';
    public const MONTH = 'month';
    public const YEAR = 'year';

    // ============================================
    // Pagination Parameters
    // ============================================

    public const PAGE = 'page';
    public const PER_PAGE = 'per_page';
    public const LIMIT = 'limit';
    public const OFFSET = 'offset';

    // ============================================
    // Sorting Parameters
    // ============================================

    public const SORT_BY = 'sort_by';
    public const SORT_ORDER = 'sort_order';
    public const ORDER_BY = 'order_by';
    public const DIRECTION = 'direction';

    // ============================================
    // Entity-specific Filters
    // ============================================

    public const STUDENT_ID = 'student_id';
    public const TEACHER_ID = 'teacher_id';
    public const ACADEMY_ID = 'academy_id';
    public const CIRCLE_ID = 'circle_id';
    public const COURSE_ID = 'course_id';
    public const SUBSCRIPTION_ID = 'subscription_id';

    // ============================================
    // Special Filter Values
    // ============================================

    /** "All" value for status/type filters */
    public const VALUE_ALL = 'all';

    // ============================================
    // Default Pagination Values
    // ============================================

    /** Default items per page for lists */
    public const DEFAULT_PER_PAGE = 15;

    /** Default items per page for payments */
    public const PAYMENTS_PER_PAGE = 15;

    /** Default recent sessions limit */
    public const RECENT_SESSIONS_LIMIT = 5;

    /** Default upcoming sessions limit */
    public const UPCOMING_SESSIONS_LIMIT = 10;
}
