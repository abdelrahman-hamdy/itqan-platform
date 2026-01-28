<?php

namespace App\Http\Controllers\Api\V1\Teacher\Academic;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Certificate;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use ApiResponses;

    /**
     * Get assigned interactive courses.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
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
            'courses' => collect($courses->items())->map(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->short_description ?? substr($course->description ?? '', 0, 200),
                'thumbnail' => $course->thumbnail ? asset('storage/'.$course->thumbnail) : null,
                'category' => $course->category?->name,
                'level' => $course->level,
                'status' => $course->status,
                'is_active' => $course->is_active,
                'enrollments_count' => $course->enrollments_count,
                'sessions_count' => $course->sessions_count,
                'price' => $course->price,
                'currency' => $course->currency ?? getCurrencyCode(null, $course->academy),
                'start_date' => $course->start_date?->toDateString(),
                'end_date' => $course->end_date?->toDateString(),
                'created_at' => $course->created_at->toISOString(),
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($courses),
        ], __('Courses retrieved successfully'));
    }

    /**
     * Get course detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $course = InteractiveCourse::where('id', $id)
            ->where('assigned_teacher_id', $academicTeacherId)
            ->withCount(['enrollments', 'sessions', 'quizzes'])
            ->with(['category', 'sessions' => function ($q) {
                $q->orderBy('session_number');
            }])
            ->first();

        if (! $course) {
            return $this->notFound(__('Course not found.'));
        }

        // Calculate session statistics
        $allSessions = $course->sessions()->get();
        $sessionsStats = [
            'total' => $allSessions->count(),
            'completed' => $allSessions->where('status', SessionStatus::COMPLETED)->count(),
            'scheduled' => $allSessions->whereIn('status', [SessionStatus::SCHEDULED, SessionStatus::READY])->count(),
            'cancelled' => $allSessions->where('status', SessionStatus::CANCELLED)->count(),
        ];

        // Count certificates issued for students in this course
        $studentIds = CourseSubscription::where('course_id', $id)->pluck('user_id')->filter()->toArray();
        $certificatesIssued = Certificate::where('certificateable_type', InteractiveCourse::class)
            ->where('certificateable_id', $course->id)
            ->whereIn('student_id', $studentIds)
            ->count();

        $studentsStats = [
            'total' => $course->enrollments_count,
            'certificates_issued' => $certificatesIssued,
        ];

        return $this->success([
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'short_description' => $course->short_description,
                'thumbnail' => $course->thumbnail ? asset('storage/'.$course->thumbnail) : null,
                'preview_video' => $course->preview_video ? asset('storage/'.$course->preview_video) : null,
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
                'quizzes_count' => $course->quizzes_count ?? 0,
                'price' => $course->price,
                'currency' => $course->currency ?? getCurrencyCode(null, $course->academy),
                'what_you_learn' => $course->what_you_learn ?? [],
                'requirements' => $course->requirements ?? [],
                'sessions_stats' => $sessionsStats,
                'students_stats' => $studentsStats,
                'quick_actions' => [
                    'can_issue_certificate' => $course->enrollments_count > $certificatesIssued,
                ],
                'start_date' => $course->start_date?->toDateString(),
                'end_date' => $course->end_date?->toDateString(),
                'sessions' => $course->sessions->map(fn ($session) => [
                    'id' => $session->id,
                    'session_number' => $session->session_number,
                    'title' => $session->title,
                    'description' => $session->description,
                    'scheduled_at' => $session->scheduled_at?->toISOString(),
                    'duration_minutes' => $session->duration_minutes,
                    'status' => $session->status->value ?? $session->status,
                ])->toArray(),
                'created_at' => $course->created_at->toISOString(),
            ],
        ], __('Course retrieved successfully'));
    }

    /**
     * Get course students.
     */
    public function students(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $course = InteractiveCourse::where('id', $id)
            ->where('assigned_teacher_id', $academicTeacherId)
            ->first();

        if (! $course) {
            return $this->notFound(__('Course not found.'));
        }

        // Check if teacher can chat (has supervisor)
        $canChat = $user->hasSupervisor();

        $query = CourseSubscription::where('course_id', $id)
            ->with(['user']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $enrollments = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        // Get all certificates for this course
        $studentIds = collect($enrollments->items())->pluck('user_id')->filter()->toArray();
        $certificates = Certificate::where('certificateable_type', InteractiveCourse::class)
            ->where('certificateable_id', $course->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        return $this->success([
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'students' => collect($enrollments->items())->map(function ($enrollment) use ($certificates, $canChat) {
                $certificate = $certificates->get($enrollment->user_id);

                $certificateData = $certificate ? [
                    'issued' => true,
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'issued_at' => $certificate->issued_at?->toISOString(),
                    'view_url' => $certificate->view_url,
                    'download_url' => $certificate->download_url,
                ] : ['issued' => false];

                return [
                    'id' => $enrollment->id,
                    'user_id' => $enrollment->user_id,
                    'name' => $enrollment->user?->name,
                    'email' => $enrollment->user?->email,
                    'avatar' => $enrollment->user?->avatar
                        ? asset('storage/'.$enrollment->user->avatar)
                        : null,
                    'phone' => $enrollment->user?->phone,
                    'status' => $enrollment->status,
                    'progress_percentage' => $enrollment->progress_percentage ?? 0,
                    'completed_sessions' => $enrollment->completed_sessions ?? 0,
                    'enrolled_at' => $enrollment->created_at->toISOString(),
                    'certificate' => $certificateData,
                    'can_chat' => $canChat,
                ];
            })->toArray(),
            'pagination' => PaginationHelper::fromPaginator($enrollments),
        ], __('Course students retrieved successfully'));
    }

    /**
     * Get certificates for a course.
     */
    public function certificates(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $course = InteractiveCourse::where('id', $id)
            ->where('assigned_teacher_id', $academicTeacherId)
            ->first();

        if (! $course) {
            return $this->notFound(__('Course not found.'));
        }

        // Get all student IDs from enrollments
        $studentIds = CourseSubscription::where('course_id', $id)->pluck('user_id')->filter()->toArray();

        // Get all certificates for this course
        $certificates = Certificate::where('certificateable_type', InteractiveCourse::class)
            ->where('certificateable_id', $course->id)
            ->whereIn('student_id', $studentIds)
            ->with('student')
            ->orderBy('issued_at', 'desc')
            ->get();

        return $this->success([
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'certificates' => $certificates->map(fn ($cert) => [
                'id' => $cert->id,
                'certificate_number' => $cert->certificate_number,
                'student' => [
                    'id' => $cert->student?->id,
                    'name' => $cert->student?->name,
                    'avatar' => $cert->student?->avatar
                        ? asset('storage/'.$cert->student->avatar)
                        : null,
                ],
                'issued_at' => $cert->issued_at?->toISOString(),
                'view_url' => $cert->view_url,
                'download_url' => $cert->download_url,
            ])->toArray(),
            'total' => $certificates->count(),
        ], __('Course certificates retrieved successfully'));
    }
}
