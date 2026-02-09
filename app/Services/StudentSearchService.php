<?php

namespace App\Services;

use App\Enums\CircleEnrollmentStatus;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Service for student search functionality.
 *
 * Extracted from StudentProfileController to reduce controller size.
 * Handles searching across courses, teachers, and circles.
 */
class StudentSearchService
{
    /**
     * Search across all entities for a student.
     *
     * @param  User  $user  The student user
     * @param  string  $query  The search query
     * @param  int  $limit  Maximum results per entity type
     * @return array Search results organized by type
     */
    public function search(User $user, string $query, int $limit = 10): array
    {
        $academy = $user->academy;

        return [
            'interactive_courses' => $this->searchInteractiveCourses($academy, $query, $limit),
            'recorded_courses' => $this->searchRecordedCourses($academy, $query, $limit),
            'quran_teachers' => $this->searchQuranTeachers($academy, $query, $limit),
            'academic_teachers' => $this->searchAcademicTeachers($academy, $query, $limit),
            'quran_circles' => $this->searchQuranCircles($academy, $query, $limit),
        ];
    }

    /**
     * Search interactive courses.
     */
    public function searchInteractiveCourses($academy, string $query, int $limit = 10): Collection
    {
        return InteractiveCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->with(['assignedTeacher'])
            ->limit($limit)
            ->get();
    }

    /**
     * Search recorded courses.
     */
    public function searchRecordedCourses($academy, string $query, int $limit = 10): Collection
    {
        return RecordedCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Search Quran teachers.
     * Note: Personal info (first_name, last_name) is on User model, not profile
     */
    public function searchQuranTeachers($academy, string $query, int $limit = 10): Collection
    {
        return QuranTeacherProfile::where('academy_id', $academy->id)
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('bio_arabic', 'like', "%{$query}%")
                    ->orWhere('bio_english', 'like', "%{$query}%")
                    ->orWhereHas('user', function ($userQuery) use ($query) {
                        $userQuery->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('name', 'like', "%{$query}%");
                    });
            })
            ->with(['user'])
            ->limit($limit)
            ->get();
    }

    /**
     * Search academic teachers.
     * Note: Personal info (first_name, last_name) is on User model, not profile
     */
    public function searchAcademicTeachers($academy, string $query, int $limit = 10): Collection
    {
        return AcademicTeacherProfile::where('academy_id', $academy->id)
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('bio_arabic', 'like', "%{$query}%")
                    ->orWhere('bio_english', 'like', "%{$query}%")
                    ->orWhereHas('user', function ($userQuery) use ($query) {
                        $userQuery->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('name', 'like', "%{$query}%");
                    });
            })
            ->with(['user'])
            ->limit($limit)
            ->get();
    }

    /**
     * Search Quran circles.
     */
    public function searchQuranCircles($academy, string $query, int $limit = 10): Collection
    {
        return QuranCircle::where('academy_id', $academy->id)
            ->where('status', true)
            ->where('enrollment_status', CircleEnrollmentStatus::OPEN)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('circle_code', 'like', "%{$query}%");
            })
            ->with(['teacher'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get total count of search results.
     */
    public function getTotalCount(array $results): int
    {
        return collect($results)->sum(fn ($collection) => $collection->count());
    }
}
