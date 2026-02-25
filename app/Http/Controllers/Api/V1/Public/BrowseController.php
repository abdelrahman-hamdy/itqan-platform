<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\CircleEnrollmentStatus;
use App\Enums\InteractiveCourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public browse endpoints – no authentication required.
 * Returns public-facing data only (no subscription or enrollment status).
 */
class BrowseController extends Controller
{
    use ApiResponses;

    /**
     * List published Quran teachers for the academy (public).
     */
    public function quranTeachers(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $query = QuranTeacherProfile::where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->with(['user']);

        if ($request->filled('gender')) {
            $query->whereHas('user', fn ($q) => $q->where('gender', $request->gender));
        }

        $search = $request->get('search');
        if ($search && mb_strlen($search) < 2) {
            $search = null; // Ignore searches shorter than 2 characters
        }
        if ($search) {
            $query->whereHas('user', fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
            );
        }

        if ($request->filled('min_experience')) {
            $query->where('teaching_experience_years', '>=', $request->min_experience);
        }

        if ($request->filled('max_experience')) {
            $query->where('teaching_experience_years', '<=', $request->max_experience);
        }

        $teachers = $query->orderBy('rating', 'desc')
            ->paginate(min((int) $request->get('per_page', 15), 50));

        return $this->success([
            'teachers' => collect($teachers->items())->map(fn ($teacher) => [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'name' => $teacher->user?->name ?? $teacher->full_name,
                'avatar' => $teacher->user?->avatar ? asset('storage/'.$teacher->user->avatar) : null,
                'bio' => $teacher->bio_arabic,
                'educational_qualification' => $teacher->educational_qualification ? (
                    is_string($teacher->educational_qualification)
                        ? $teacher->educational_qualification
                        : $teacher->educational_qualification->value
                ) : null,
                'certifications' => $teacher->certifications ?? [],
                'teaching_experience_years' => $teacher->teaching_experience_years ?? 0,
                'rating' => round($teacher->rating ?? 0, 1),
                'total_reviews' => $teacher->total_reviews ?? 0,
                'total_students' => $teacher->total_students ?? 0,
                'languages' => $teacher->languages ?? [],
                'is_subscribed' => false,
                'subscription_id' => null,
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($teachers),
        ], __('Teachers retrieved successfully'));
    }

    /**
     * List published Academic teachers for the academy (public).
     */
    public function academicTeachers(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $query = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->with(['user']);

        if ($request->filled('subject_id')) {
            $subjectId = $request->subject_id;
            $query->where(fn ($q) => $q
                ->whereJsonContains('subject_ids', (int) $subjectId)
                ->orWhereJsonContains('subject_ids', (string) $subjectId)
            );
        }

        if ($request->filled('grade_level_id')) {
            $gradeLevelId = $request->grade_level_id;
            $query->where(fn ($q) => $q
                ->whereJsonContains('grade_level_ids', (int) $gradeLevelId)
                ->orWhereJsonContains('grade_level_ids', (string) $gradeLevelId)
            );
        }

        $search = $request->get('search');
        if ($search && mb_strlen($search) < 2) {
            $search = null; // Ignore searches shorter than 2 characters
        }
        if ($search) {
            $query->whereHas('user', fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
            );
        }

        $teachers = $query->orderBy('rating', 'desc')
            ->paginate(min((int) $request->get('per_page', 15), 50));

        return $this->success([
            'teachers' => collect($teachers->items())->map(fn ($teacher) => [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'name' => $teacher->user?->name ?? $teacher->full_name,
                'avatar' => $teacher->user?->avatar ? asset('storage/'.$teacher->user->avatar) : null,
                'bio' => $teacher->bio_arabic,
                'education_level' => $teacher->education_level?->value,
                'university' => $teacher->university,
                'teaching_experience_years' => $teacher->teaching_experience_years,
                'certifications' => $teacher->certifications ?? [],
                'subject_ids' => $teacher->subject_ids ?? [],
                'grade_level_ids' => $teacher->grade_level_ids ?? [],
                'rating' => round($teacher->rating ?? 0, 1),
                'total_reviews' => $teacher->total_reviews ?? 0,
                'total_students' => $teacher->total_students ?? 0,
                'is_subscribed' => false,
                'subscription_id' => null,
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($teachers),
        ], __('Teachers retrieved successfully'));
    }

    /**
     * List open Quran circles for the academy (public).
     */
    public function quranCircles(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $query = QuranCircle::where('academy_id', $academy->id)
            ->where('status', true)
            ->where('enrollment_status', CircleEnrollmentStatus::OPEN)
            ->with(['quranTeacherProfile.user']);

        if ($request->filled('gender')) {
            $query->where('target_gender', $request->gender);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('day')) {
            $query->whereJsonContains('schedule_days', $request->day);
        }

        $search = $request->get('search');
        if ($search && mb_strlen($search) < 2) {
            $search = null; // Ignore searches shorter than 2 characters
        }
        if ($search) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('quranTeacher', fn ($u) => $u
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                )
            );
        }

        $circles = $query->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->get('per_page', 15), 50));

        return $this->success([
            'circles' => collect($circles->items())->map(fn ($circle) => [
                'id' => $circle->id,
                'name' => $circle->name,
                'description' => $circle->description,
                'teacher_id' => $circle->quranTeacherProfile?->id,
                'teacher_name' => $circle->quranTeacherProfile?->user?->name ?? $circle->quranTeacherProfile?->full_name,
                'teacher_avatar' => $circle->quranTeacherProfile?->user?->avatar
                    ? asset('storage/'.$circle->quranTeacherProfile->user->avatar)
                    : null,
                'target_gender' => $circle->target_gender,
                'level' => $circle->level,
                'current_students' => $circle->current_students_count ?? 0,
                'max_students' => $circle->max_students,
                'schedule_days' => $circle->schedule_days ?? [],
                'start_time' => $circle->start_time,
                'end_time' => $circle->end_time,
                'monthly_price' => $circle->monthly_price,
                'is_full' => ($circle->current_students_count ?? 0) >= $circle->max_students,
                'is_enrolled' => false,
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($circles),
        ], __('Circles retrieved successfully'));
    }

    /**
     * List published interactive courses for the academy (public).
     */
    public function interactiveCourses(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $query = InteractiveCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->where('status', InteractiveCourseStatus::PUBLISHED)
            ->with(['assignedTeacher.user', 'category']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $search = $request->get('search');
        if ($search && mb_strlen($search) < 2) {
            $search = null; // Ignore searches shorter than 2 characters
        }
        if ($search) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            );
        }

        $courses = $query->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->get('per_page', 15), 50));

        return $this->success([
            'courses' => collect($courses->items())->map(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->short_description ?? substr($course->description ?? '', 0, 200),
                'thumbnail' => $course->thumbnail ? asset('storage/'.$course->thumbnail) : null,
                'category' => $course->category?->name,
                'level' => $course->level,
                'duration_hours' => $course->duration_hours,
                'total_sessions' => $course->total_sessions,
                'price' => $course->price,
                'currency' => $course->currency ?? getCurrencyCode(null, $course->academy),
                'is_free' => $course->is_free ?? $course->price == 0,
                'teacher' => $course->assignedTeacher?->user ? [
                    'id' => $course->assignedTeacher->user->id,
                    'name' => $course->assignedTeacher->user->name,
                    'avatar' => $course->assignedTeacher->user->avatar
                        ? asset('storage/'.$course->assignedTeacher->user->avatar)
                        : null,
                ] : null,
                'rating' => round($course->rating ?? 0, 1),
                'total_enrollments' => $course->total_enrollments ?? 0,
                'is_enrolled' => false,
                'start_date' => $course->start_date?->toDateString(),
                'end_date' => $course->end_date?->toDateString(),
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($courses),
        ], __('Courses retrieved successfully'));
    }

    /**
     * List published recorded courses for the academy (public).
     */
    public function recordedCourses(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $query = RecordedCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->with(['subject', 'gradeLevel']);

        $search = $request->get('search');
        if ($search && mb_strlen($search) < 2) {
            $search = null; // Ignore searches shorter than 2 characters
        }
        if ($search) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            );
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty_level', $request->difficulty);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        $courses = $query->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->get('per_page', 15), 50));

        return $this->success([
            'courses' => collect($courses->items())->map(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'thumbnail_url' => $course->thumbnail_url
                    ? asset('storage/'.$course->thumbnail_url)
                    : null,
                'duration_formatted' => $course->duration_formatted,
                'total_duration_minutes' => $course->total_duration_minutes,
                'total_lessons' => $course->total_lessons,
                'difficulty_level' => $course->difficulty_level,
                'avg_rating' => (float) ($course->avg_rating ?? 0),
                'total_reviews' => $course->total_reviews ?? 0,
                'price' => $course->price ?? 0,
                'currency' => $course->currency ?? getCurrencyCode(null, $academy),
                'is_free' => ($course->price ?? 0) == 0,
                'subject' => $course->subject?->name,
                'grade_level' => $course->gradeLevel?->name,
                'is_enrolled' => false,
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($courses),
        ], __('Courses retrieved successfully'));
    }
}
