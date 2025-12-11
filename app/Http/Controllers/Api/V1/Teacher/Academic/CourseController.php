<?php

namespace App\Http\Controllers\Api\V1\Teacher\Academic;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use ApiResponses;

    /**
     * Get assigned interactive courses.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (!$academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $query = InteractiveCourse::where('assigned_teacher_id', $academicTeacherId)
            ->withCount(['enrollments', 'sessions']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $courses = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'courses' => collect($courses->items())->map(fn($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->short_description ?? substr($course->description ?? '', 0, 200),
                'thumbnail' => $course->thumbnail ? asset('storage/' . $course->thumbnail) : null,
                'category' => $course->category?->name,
                'level' => $course->level,
                'status' => $course->status,
                'is_active' => $course->is_active,
                'enrollments_count' => $course->enrollments_count,
                'sessions_count' => $course->sessions_count,
                'price' => $course->price,
                'currency' => $course->currency ?? 'SAR',
                'start_date' => $course->start_date?->toDateString(),
                'end_date' => $course->end_date?->toDateString(),
                'created_at' => $course->created_at->toISOString(),
            ])->toArray(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'total_pages' => $courses->lastPage(),
                'has_more' => $courses->hasMorePages(),
            ],
        ], __('Courses retrieved successfully'));
    }

    /**
     * Get course detail.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (!$academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $course = InteractiveCourse::where('id', $id)
            ->where('assigned_teacher_id', $academicTeacherId)
            ->withCount(['enrollments', 'sessions'])
            ->with(['category', 'sessions' => function ($q) {
                $q->orderBy('session_number');
            }])
            ->first();

        if (!$course) {
            return $this->notFound(__('Course not found.'));
        }

        return $this->success([
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'short_description' => $course->short_description,
                'thumbnail' => $course->thumbnail ? asset('storage/' . $course->thumbnail) : null,
                'preview_video' => $course->preview_video ? asset('storage/' . $course->preview_video) : null,
                'category' => $course->category ? [
                    'id' => $course->category->id,
                    'name' => $course->category->name,
                ] : null,
                'level' => $course->level,
                'language' => $course->language ?? 'ar',
                'duration_hours' => $course->duration_hours,
                'status' => $course->status,
                'is_active' => $course->is_active,
                'enrollments_count' => $course->enrollments_count,
                'sessions_count' => $course->sessions_count,
                'price' => $course->price,
                'currency' => $course->currency ?? 'SAR',
                'what_you_learn' => $course->what_you_learn ?? [],
                'requirements' => $course->requirements ?? [],
                'start_date' => $course->start_date?->toDateString(),
                'end_date' => $course->end_date?->toDateString(),
                'sessions' => $course->sessions->map(fn($session) => [
                    'id' => $session->id,
                    'session_number' => $session->session_number,
                    'title' => $session->title,
                    'description' => $session->description,
                    'scheduled_date' => $session->scheduled_date?->toDateString(),
                    'scheduled_time' => $session->scheduled_time,
                    'duration_minutes' => $session->duration_minutes,
                    'status' => $session->status->value ?? $session->status,
                ])->toArray(),
                'created_at' => $course->created_at->toISOString(),
            ],
        ], __('Course retrieved successfully'));
    }

    /**
     * Get course students.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function students(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (!$academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $course = InteractiveCourse::where('id', $id)
            ->where('assigned_teacher_id', $academicTeacherId)
            ->first();

        if (!$course) {
            return $this->notFound(__('Course not found.'));
        }

        $query = CourseSubscription::where('course_id', $id)
            ->with(['user']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $enrollments = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success([
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'students' => collect($enrollments->items())->map(fn($enrollment) => [
                'id' => $enrollment->id,
                'user_id' => $enrollment->user_id,
                'name' => $enrollment->user?->name,
                'email' => $enrollment->user?->email,
                'avatar' => $enrollment->user?->avatar
                    ? asset('storage/' . $enrollment->user->avatar)
                    : null,
                'status' => $enrollment->status,
                'progress_percentage' => $enrollment->progress_percentage ?? 0,
                'completed_sessions' => $enrollment->completed_sessions ?? 0,
                'enrolled_at' => $enrollment->created_at->toISOString(),
            ])->toArray(),
            'pagination' => [
                'current_page' => $enrollments->currentPage(),
                'per_page' => $enrollments->perPage(),
                'total' => $enrollments->total(),
                'total_pages' => $enrollments->lastPage(),
                'has_more' => $enrollments->hasMorePages(),
            ],
        ], __('Course students retrieved successfully'));
    }
}
