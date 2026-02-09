<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\CircleEnrollmentStatus;
use App\Enums\InteractiveCourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicTeacherProfile;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranCircleEnrollment;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Global Search API Controller for Students
 *
 * Provides unified search across:
 * - Quran Teachers
 * - Academic Teachers
 * - Quran Circles
 * - Interactive Courses
 * - Recorded Courses
 */
class SearchController extends Controller
{
    use ApiResponses;

    /**
     * Global search across all entities.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', $request->get('query', ''));
        $filter = $request->get('filter', 'all'); // all, quran_teachers, academic_teachers, quran_circles, courses
        $perPage = min((int) $request->get('per_page', 20), 50);

        if (empty($query) || strlen($query) < 2) {
            return $this->success([
                'results' => [],
                'counts' => [
                    'all' => 0,
                    'quran_teachers' => 0,
                    'academic_teachers' => 0,
                    'quran_circles' => 0,
                    'interactive_courses' => 0,
                    'recorded_courses' => 0,
                ],
            ], __('Search query too short'));
        }

        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? current_academy();
        $academyId = $academy?->id;

        // Get enrolled course IDs for the student
        $enrolledCourseIds = CourseSubscription::where('student_id', $user->id)
            ->whereIn('status', ['active', 'enrolled'])
            ->pluck('course_id')
            ->toArray();

        // Get enrolled circle IDs for the student
        $enrolledCircleIds = QuranCircleEnrollment::where('student_id', $user->id)
            ->where('status', QuranCircleEnrollment::STATUS_ENROLLED)
            ->pluck('circle_id')
            ->toArray();

        $results = collect();

        // Search based on filter
        if ($filter === 'all' || $filter === 'quran_teachers') {
            $quranTeachers = $this->searchQuranTeachers($query, $academyId, $perPage);
            $results = $results->merge($quranTeachers);
        }

        if ($filter === 'all' || $filter === 'academic_teachers') {
            $academicTeachers = $this->searchAcademicTeachers($query, $academyId, $perPage);
            $results = $results->merge($academicTeachers);
        }

        if ($filter === 'all' || $filter === 'quran_circles') {
            $circles = $this->searchQuranCircles($query, $academyId, $enrolledCircleIds, $perPage);
            $results = $results->merge($circles);
        }

        if ($filter === 'all' || $filter === 'courses' || $filter === 'interactive_courses') {
            $interactiveCourses = $this->searchInteractiveCourses($query, $academyId, $enrolledCourseIds, $perPage);
            $results = $results->merge($interactiveCourses);
        }

        if ($filter === 'all' || $filter === 'courses' || $filter === 'recorded_courses') {
            $recordedCourses = $this->searchRecordedCourses($query, $academyId, $enrolledCourseIds, $perPage);
            $results = $results->merge($recordedCourses);
        }

        // Get counts for each type (for tab badges)
        $counts = $this->getCounts($query, $academyId);

        // Sort results by relevance (exact match first, then partial)
        $sortedResults = $this->sortByRelevance($results, $query);

        // Limit total results if filter is 'all'
        if ($filter === 'all') {
            $sortedResults = $sortedResults->take($perPage);
        }

        return $this->success([
            'results' => $sortedResults->values()->toArray(),
            'counts' => $counts,
            'query' => $query,
            'filter' => $filter,
        ], __('Search completed successfully'));
    }

    /**
     * Search Quran teachers.
     */
    protected function searchQuranTeachers(string $query, ?int $academyId, int $limit): Collection
    {
        $teachers = QuranTeacherProfile::where('academy_id', $academyId)
            ->whereHas('user', fn ($uq) => $uq->where('active_status', true))
            ->where(function ($q) use ($query) {
                $q->whereHas('user', function ($userQuery) use ($query) {
                    $userQuery->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%");
                })
                    ->orWhere('bio_arabic', 'like', "%{$query}%")
                    ->orWhere('educational_qualification', 'like', "%{$query}%");
            })
            ->with('user')
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();

        return $teachers->map(function ($teacher) {
            return [
                'id' => (string) $teacher->id,
                'type' => 'quran_teacher',
                'title' => $teacher->user?->name ?? $teacher->full_name ?? 'معلم قرآن',
                'subtitle' => $teacher->educational_qualification ?? 'معلم قرآن كريم',
                'description' => $teacher->bio_arabic,
                'image_url' => $teacher->user?->avatar ? asset('storage/'.$teacher->user->avatar) : null,
                'rating' => round($teacher->rating ?? 0, 1),
                'reviews_count' => $teacher->total_reviews ?? 0,
                'price' => $teacher->hourly_rate,
                'is_enrolled' => false, // Teachers don't have enrollment
                'metadata' => [
                    'experience_years' => $teacher->teaching_experience_years,
                    'total_students' => $teacher->total_students ?? 0,
                    'specializations' => $teacher->specializations ?? [],
                ],
            ];
        });
    }

    /**
     * Search Academic teachers.
     */
    protected function searchAcademicTeachers(string $query, ?int $academyId, int $limit): Collection
    {
        $teachers = AcademicTeacherProfile::where('academy_id', $academyId)
            ->whereHas('user', fn ($uq) => $uq->where('active_status', true))
            ->where(function ($q) use ($query) {
                $q->whereHas('user', function ($userQuery) use ($query) {
                    $userQuery->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%");
                })
                    ->orWhere('bio_arabic', 'like', "%{$query}%")
                    ->orWhere('education_level', 'like', "%{$query}%");
            })
            ->with('user')
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();

        return $teachers->map(function ($teacher) {
            return [
                'id' => (string) $teacher->id,
                'type' => 'academic_teacher',
                'title' => $teacher->user?->name ?? $teacher->full_name ?? 'معلم أكاديمي',
                'subtitle' => $teacher->education_level ?? 'معلم أكاديمي',
                'description' => $teacher->bio_arabic,
                'image_url' => $teacher->user?->avatar ? asset('storage/'.$teacher->user->avatar) : null,
                'rating' => round($teacher->rating ?? 0, 1),
                'reviews_count' => $teacher->total_reviews ?? 0,
                'price' => $teacher->hourly_rate,
                'is_enrolled' => false,
                'metadata' => [
                    'experience_years' => $teacher->teaching_experience_years,
                    'total_students' => $teacher->total_students ?? 0,
                    'subject_ids' => $teacher->subject_ids ?? [],
                    'grade_level_ids' => $teacher->grade_level_ids ?? [],
                ],
            ];
        });
    }

    /**
     * Search Quran circles.
     */
    protected function searchQuranCircles(string $query, ?int $academyId, array $enrolledCircleIds, int $limit): Collection
    {
        $circles = QuranCircle::where('academy_id', $academyId)
            ->where('status', 'active')
            ->where('enrollment_status', CircleEnrollmentStatus::OPEN)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereHas('quranTeacher.user', function ($userQuery) use ($query) {
                        $userQuery->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('name', 'like', "%{$query}%");
                    });
            })
            ->with('quranTeacher.user')
            ->limit($limit)
            ->get();

        return $circles->map(function ($circle) use ($enrolledCircleIds) {
            $teacherName = $circle->quranTeacher?->user?->name ?? $circle->quranTeacher?->full_name ?? 'معلم';

            return [
                'id' => (string) $circle->id,
                'type' => 'quran_circle',
                'title' => $circle->name,
                'subtitle' => "مع الشيخ {$teacherName}",
                'description' => $circle->description,
                'image_url' => $circle->quranTeacher?->user?->avatar
                    ? asset('storage/'.$circle->quranTeacher->user->avatar)
                    : null,
                'rating' => null,
                'reviews_count' => null,
                'price' => $circle->monthly_price,
                'is_enrolled' => in_array($circle->id, $enrolledCircleIds),
                'metadata' => [
                    'level' => $circle->level,
                    'target_gender' => $circle->target_gender,
                    'max_students' => $circle->max_students,
                    'current_students' => $circle->current_students_count ?? 0,
                    'schedule_days' => $circle->schedule_days ?? [],
                    'is_full' => ($circle->current_students_count ?? 0) >= $circle->max_students,
                ],
            ];
        });
    }

    /**
     * Search Interactive courses.
     */
    protected function searchInteractiveCourses(string $query, ?int $academyId, array $enrolledCourseIds, int $limit): Collection
    {
        $courses = InteractiveCourse::where('academy_id', $academyId)
            ->where('is_published', true)
            ->where('status', InteractiveCourseStatus::PUBLISHED)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('short_description', 'like', "%{$query}%");
            })
            ->with(['assignedTeacher.user', 'category'])
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();

        return $courses->map(function ($course) use ($enrolledCourseIds) {
            $teacherName = $course->assignedTeacher?->user?->name ?? 'معلم';

            return [
                'id' => (string) $course->id,
                'type' => 'interactive_course',
                'title' => $course->title,
                'subtitle' => "دورة تفاعلية مع {$teacherName}",
                'description' => $course->short_description ?? substr($course->description ?? '', 0, 200),
                'image_url' => $course->thumbnail ? asset('storage/'.$course->thumbnail) : null,
                'rating' => round($course->rating ?? 0, 1),
                'reviews_count' => $course->total_reviews ?? 0,
                'price' => $course->price,
                'is_enrolled' => in_array($course->id, $enrolledCourseIds),
                'metadata' => [
                    'category' => $course->category?->name,
                    'level' => $course->level,
                    'duration_hours' => $course->duration_hours,
                    'total_sessions' => $course->total_sessions,
                    'total_enrollments' => $course->total_enrollments ?? 0,
                    'start_date' => $course->start_date?->toDateString(),
                ],
            ];
        });
    }

    /**
     * Search Recorded courses.
     */
    protected function searchRecordedCourses(string $query, ?int $academyId, array $enrolledCourseIds, int $limit): Collection
    {
        $courses = RecordedCourse::where('academy_id', $academyId)
            ->where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->with(['subject', 'gradeLevel'])
            ->orderByDesc('avg_rating')
            ->limit($limit)
            ->get();

        return $courses->map(function ($course) use ($enrolledCourseIds) {
            return [
                'id' => (string) $course->id,
                'type' => 'recorded_course',
                'title' => $course->title,
                'subtitle' => $course->subject?->name ?? 'دورة مسجلة',
                'description' => $course->description ? substr($course->description, 0, 200) : null,
                'image_url' => $course->thumbnail_url ? asset('storage/'.$course->thumbnail_url) : null,
                'rating' => round($course->avg_rating ?? 0, 1),
                'reviews_count' => $course->total_reviews ?? 0,
                'price' => $course->price ?? 0,
                'is_enrolled' => in_array($course->id, $enrolledCourseIds),
                'metadata' => [
                    'subject' => $course->subject?->name,
                    'grade_level' => $course->gradeLevel?->name,
                    'difficulty_level' => $course->difficulty_level,
                    'total_lessons' => $course->total_lessons ?? 0,
                    'duration_formatted' => $course->duration_formatted,
                ],
            ];
        });
    }

    /**
     * Get counts for each search type.
     */
    protected function getCounts(string $query, ?int $academyId): array
    {
        $quranTeachersCount = QuranTeacherProfile::where('academy_id', $academyId)
            ->whereHas('user', fn ($uq) => $uq->where('active_status', true))
            ->where(function ($q) use ($query) {
                $q->whereHas('user', function ($userQuery) use ($query) {
                    $userQuery->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%");
                })
                    ->orWhere('bio_arabic', 'like', "%{$query}%");
            })
            ->count();

        $academicTeachersCount = AcademicTeacherProfile::where('academy_id', $academyId)
            ->whereHas('user', fn ($uq) => $uq->where('active_status', true))
            ->where(function ($q) use ($query) {
                $q->whereHas('user', function ($userQuery) use ($query) {
                    $userQuery->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%");
                })
                    ->orWhere('bio_arabic', 'like', "%{$query}%");
            })
            ->count();

        $circlesCount = QuranCircle::where('academy_id', $academyId)
            ->where('status', 'active')
            ->where('enrollment_status', CircleEnrollmentStatus::OPEN)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->count();

        $interactiveCoursesCount = InteractiveCourse::where('academy_id', $academyId)
            ->where('is_published', true)
            ->where('status', InteractiveCourseStatus::PUBLISHED)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->count();

        $recordedCoursesCount = RecordedCourse::where('academy_id', $academyId)
            ->where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->count();

        $totalCount = $quranTeachersCount + $academicTeachersCount + $circlesCount +
            $interactiveCoursesCount + $recordedCoursesCount;

        return [
            'all' => $totalCount,
            'quran_teachers' => $quranTeachersCount,
            'academic_teachers' => $academicTeachersCount,
            'quran_circles' => $circlesCount,
            'interactive_courses' => $interactiveCoursesCount,
            'recorded_courses' => $recordedCoursesCount,
        ];
    }

    /**
     * Sort results by relevance.
     */
    protected function sortByRelevance(Collection $results, string $query): Collection
    {
        $queryLower = mb_strtolower($query);

        return $results->sortByDesc(function ($result) use ($queryLower) {
            $titleLower = mb_strtolower($result['title']);
            $score = 0;

            // Exact match in title
            if ($titleLower === $queryLower) {
                $score += 100;
            }
            // Title starts with query
            elseif (str_starts_with($titleLower, $queryLower)) {
                $score += 75;
            }
            // Title contains query as whole word
            elseif (preg_match('/\b'.preg_quote($queryLower, '/').'\b/u', $titleLower)) {
                $score += 50;
            }
            // Title contains query
            elseif (str_contains($titleLower, $queryLower)) {
                $score += 25;
            }

            // Boost by rating
            if (isset($result['rating']) && $result['rating'] > 0) {
                $score += $result['rating'] * 2;
            }

            // Boost if enrolled
            if ($result['is_enrolled']) {
                $score += 10;
            }

            return $score;
        });
    }

    /**
     * Get recent search suggestions (auto-complete).
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (empty($query) || strlen($query) < 2) {
            return $this->success(['suggestions' => []], __('Query too short'));
        }

        $academy = $request->attributes->get('academy') ?? current_academy();
        $academyId = $academy?->id;

        $suggestions = collect();

        // Get teacher names
        $teacherNames = QuranTeacherProfile::where('academy_id', $academyId)
            ->whereHas('user', function ($q) use ($query) {
                $q->where('active_status', true)->where('name', 'like', "%{$query}%");
            })
            ->with('user')
            ->limit(3)
            ->get()
            ->pluck('user.name');

        $suggestions = $suggestions->merge($teacherNames);

        // Get circle names
        $circleNames = QuranCircle::where('academy_id', $academyId)
            ->where('name', 'like', "%{$query}%")
            ->limit(3)
            ->pluck('name');

        $suggestions = $suggestions->merge($circleNames);

        // Get course titles
        $courseTitles = InteractiveCourse::where('academy_id', $academyId)
            ->where('title', 'like', "%{$query}%")
            ->limit(3)
            ->pluck('title');

        $suggestions = $suggestions->merge($courseTitles);

        return $this->success([
            'suggestions' => $suggestions->unique()->take(10)->values()->toArray(),
        ], __('Suggestions retrieved successfully'));
    }
}
