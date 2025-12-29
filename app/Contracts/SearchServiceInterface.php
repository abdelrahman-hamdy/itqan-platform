<?php

namespace App\Contracts;

use App\Models\StudentProfile;
use Illuminate\Support\Collection;

/**
 * Interface for unified search service.
 *
 * Handles comprehensive search across all student-accessible resources
 * including circles, courses, teachers, and sessions.
 */
interface SearchServiceInterface
{
    /**
     * Search across all student-accessible resources.
     *
     * @param  string  $query  Search query
     * @param  StudentProfile|null  $student  Optional student for personalized results
     * @param  array  $filters  Optional filters (level, subject, status, etc.)
     * @return Collection Results organized by resource type
     */
    public function searchAll(string $query, ?StudentProfile $student = null, array $filters = []): Collection;

    /**
     * Get total results count from search results.
     *
     * @param  Collection  $results  Search results from searchAll()
     * @return int Total number of results across all types
     */
    public function getTotalResultsCount(Collection $results): int;
}
