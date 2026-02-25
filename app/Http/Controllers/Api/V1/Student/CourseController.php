<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\InteractiveCourseStatus;
use App\Enums\ReviewStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\CourseReview;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use ApiResponses;

    /**
     * Get list of interactive courses.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? current_academy();

        $filter = $request->get('filter'); // enrolled, available, completed

        if ($filter === 'enrolled') {
            // Get courses the student is enrolled in
            // course_subscriptions.student_id references User.id
            $enrolledCourseIds = CourseSubscription::where('student_id', $user->id)
                ->where('status', 'active')
                ->pluck('interactive_course_id');

            $query = InteractiveCourse::whereIn('id', $enrolledCourseIds);
        } elseif ($filter === 'completed') {
            // Get completed courses
            $completedCourseIds = CourseSubscription::where('student_id', $user->id)
                ->where('status', SessionStatus::COMPLETED->value)
                ->pluck('interactive_course_id');

            $query = InteractiveCourse::whereIn('id', $completedCourseIds);
        } else {
            // Get all available courses
            // interactive_courses uses is_published (boolean) and status='published'
            $query = InteractiveCourse::where('academy_id', $academy->id)
                ->where('is_published', true)
                ->where('status', InteractiveCourseStatus::PUBLISHED);
        }

        $query->with(['assignedTeacher.user', 'category']);

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $courses = $query->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->get('per_page', 15), 100));

        // Get enrollment status for each course
        $enrolledCourses = CourseSubscription::where('student_id', $user->id)
            ->whereIn('interactive_course_id', collect($courses->items())->pluck('id'))
            ->get()
            ->keyBy('interactive_course_id');

        return $this->success([
            'courses' => collect($courses->items())->map(function ($course) use ($enrolledCourses) {
                $enrollment = $enrolledCourses->get($course->id);

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->short_description ?? substr($course->description, 0, 200),
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
                    'is_enrolled' => $enrollment !== null,
                    'enrollment_status' => $enrollment?->status,
                    'progress_percentage' => $enrollment?->progress_percentage ?? 0,
                    'start_date' => $course->start_date?->toDateString(),
                    'end_date' => $course->end_date?->toDateString(),
                ];
            })->toArray(),
            'pagination' => PaginationHelper::fromPaginator($courses),
        ], __('Courses retrieved successfully'));
    }

    /**
     * Get a specific interactive course.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? current_academy();

        $course = InteractiveCourse::where('id', $id)
            ->where('academy_id', $academy->id)
            ->with([
                'assignedTeacher.user',
                'category',
                'sessions' => function ($q) {
                    $q->orderBy('session_number');
                },
            ])
            ->first();

        if (! $course) {
            return $this->notFound(__('Course not found.'));
        }

        // Get enrollment (course_subscriptions.student_id references User.id)
        $enrollment = CourseSubscription::where('interactive_course_id', $id)
            ->where('student_id', $user->id)
            ->first();

        $isEnrolled = $enrollment !== null;

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
                'total_sessions' => $course->total_sessions,
                'price' => $course->price,
                'currency' => $course->currency ?? getCurrencyCode(null, $course->academy),
                'is_free' => $course->is_free ?? $course->price == 0,
                'what_you_learn' => $course->what_you_learn ?? [],
                'requirements' => $course->requirements ?? [],
                'target_audience' => $course->target_audience ?? [],
                'teacher' => $course->assignedTeacher?->user ? [
                    'id' => $course->assignedTeacher->user->id,
                    'name' => $course->assignedTeacher->user->name,
                    'avatar' => $course->assignedTeacher->user->avatar
                        ? asset('storage/'.$course->assignedTeacher->user->avatar)
                        : null,
                    'bio' => $course->assignedTeacher->bio_arabic,
                ] : null,
                'rating' => round($course->rating ?? 0, 1),
                'total_reviews' => $course->total_reviews ?? 0,
                'total_enrollments' => $course->total_enrollments ?? 0,
                'is_enrolled' => $isEnrolled,
                'enrollment' => $enrollment ? [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status,
                    'progress_percentage' => $enrollment->progress_percentage ?? 0,
                    'enrolled_at' => $enrollment->created_at->toISOString(),
                    'completed_sessions' => $enrollment->completed_sessions ?? 0,
                ] : null,
                'start_date' => $course->start_date?->toDateString(),
                'end_date' => $course->end_date?->toDateString(),
                'sessions' => $isEnrolled ? $course->sessions->map(fn ($session) => [
                    'id' => $session->id,
                    'session_number' => $session->session_number,
                    'title' => $session->title,
                    'description' => $session->description,
                    'scheduled_at' => $session->scheduled_at?->toISOString(),
                    'duration_minutes' => $session->duration_minutes,
                    'status' => $session->status->value ?? $session->status,
                    'is_live' => $session->status->value === SessionStatus::ONGOING->value,
                ])->toArray() : [],
                'curriculum' => ! $isEnrolled ? $course->sessions->map(fn ($session) => [
                    'session_number' => $session->session_number,
                    'title' => $session->title,
                    'duration_minutes' => $session->duration_minutes,
                ])->toArray() : null,
            ],
        ], __('Course retrieved successfully'));
    }

    /**
     * Get quizzes for an interactive course.
     */
    public function quizzes(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? current_academy();

        $course = InteractiveCourse::where('id', $id)
            ->where('academy_id', $academy->id)
            ->first();

        if (! $course) {
            return $this->notFound(__('Course not found.'));
        }

        // Get quiz assignments for this course
        $assignments = QuizAssignment::where('assignable_type', InteractiveCourse::class)
            ->where('assignable_id', $id)
            ->with('quiz.questions')
            ->visible()
            ->orderBy('created_at', 'desc')
            ->get();

        // Get student profile to find their attempts
        $studentProfile = $user->studentProfile;
        $studentProfileId = $studentProfile?->id;

        $quizzes = $assignments->map(function ($assignment) use ($studentProfileId) {
            $quiz = $assignment->quiz;
            if (! $quiz) {
                return null;
            }

            // Get the student's best completed attempt
            $bestAttempt = $studentProfileId
                ? QuizAttempt::where('quiz_assignment_id', $assignment->id)
                    ->where('student_id', $studentProfileId)
                    ->whereNotNull('submitted_at')
                    ->orderByDesc('score')
                    ->first()
                : null;

            $attemptCount = $studentProfileId
                ? QuizAttempt::where('quiz_assignment_id', $assignment->id)
                    ->where('student_id', $studentProfileId)
                    ->whereNotNull('submitted_at')
                    ->count()
                : 0;

            $status = 'available';
            if ($bestAttempt) {
                $status = $bestAttempt->passed ? 'completed' : 'failed';
            } elseif ($studentProfileId && QuizAttempt::where('quiz_assignment_id', $assignment->id)->where('student_id', $studentProfileId)->whereNull('submitted_at')->exists()) {
                $status = 'in_progress';
            }

            return [
                'id' => $assignment->id,
                'quiz_id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'duration_minutes' => $quiz->duration_minutes,
                'passing_score' => $quiz->passing_score,
                'questions_count' => $quiz->questions->count(),
                'available_from' => $assignment->available_from?->toISOString(),
                'available_until' => $assignment->available_until?->toISOString(),
                'status' => $status,
                'best_score' => $bestAttempt?->score,
                'attempts_count' => $attemptCount,
                'passed' => $bestAttempt?->passed ?? false,
            ];
        })->filter()->values()->toArray();

        return $this->success([
            'quizzes' => $quizzes,
            'total' => count($quizzes),
        ], __('Quizzes retrieved successfully'));
    }

    /**
     * Get materials for an interactive course (shared files from sessions).
     */
    public function materials(Request $request, int $id): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $course = InteractiveCourse::where('id', $id)
            ->where('academy_id', $academy->id)
            ->first();

        if (! $course) {
            return $this->notFound(__('Course not found.'));
        }

        // Interactive courses do not have a dedicated materials system yet.
        // Materials are shared live during sessions via the meeting interface.
        return $this->success([
            'materials' => [],
            'total' => 0,
        ], __('Materials retrieved successfully'));
    }

    /**
     * Get progress for an enrolled student in an interactive course.
     */
    public function progress(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $academy = $request->attributes->get('academy') ?? current_academy();

        $course = InteractiveCourse::where('id', $id)
            ->where('academy_id', $academy->id)
            ->with('sessions')
            ->first();

        if (! $course) {
            return $this->notFound(__('Course not found.'));
        }

        $enrollment = CourseSubscription::where('interactive_course_id', $id)
            ->where('student_id', $user->id)
            ->first();

        if (! $enrollment) {
            return $this->success([
                'is_enrolled' => false,
                'progress' => null,
            ], __('Student is not enrolled in this course'));
        }

        $totalSessions = $course->total_sessions ?? $course->sessions->count();
        $completedSessions = $course->sessions()
            ->where('status', SessionStatus::COMPLETED->value)
            ->count();

        $progressPercentage = $totalSessions > 0
            ? round(($completedSessions / $totalSessions) * 100)
            : 0;

        // Quiz stats
        $studentProfile = $user->studentProfile;
        $quizAssignmentIds = QuizAssignment::where('assignable_type', InteractiveCourse::class)
            ->where('assignable_id', $id)
            ->pluck('id');

        $quizzesTotal = $quizAssignmentIds->count();
        $quizzesTaken = 0;
        $averageScore = 0;

        if ($studentProfile && $quizzesTotal > 0) {
            $attempts = QuizAttempt::whereIn('quiz_assignment_id', $quizAssignmentIds)
                ->where('student_id', $studentProfile->id)
                ->whereNotNull('submitted_at')
                ->get();

            $quizzesTaken = $attempts->unique('quiz_assignment_id')->count();
            $averageScore = $attempts->isNotEmpty() ? (int) round($attempts->avg('score')) : 0;
        }

        return $this->success([
            'is_enrolled' => true,
            'progress' => [
                'total_sessions' => $totalSessions,
                'completed_sessions' => $completedSessions,
                'progress_percentage' => $progressPercentage,
                'quizzes_total' => $quizzesTotal,
                'quizzes_taken' => $quizzesTaken,
                'average_quiz_score' => $averageScore,
                'certificate_progress' => $progressPercentage / 100,
                'enrolled_at' => $enrollment->created_at->toISOString(),
            ],
        ], __('Progress retrieved successfully'));
    }

    /**
     * Get approved reviews for an interactive course.
     */
    public function reviews(Request $request, int $id): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $course = InteractiveCourse::where('id', $id)
            ->where('academy_id', $academy->id)
            ->first();

        if (! $course) {
            return $this->notFound(__('Course not found.'));
        }

        $reviews = CourseReview::where('reviewable_type', InteractiveCourse::class)
            ->where('reviewable_id', $id)
            ->where('is_approved', true)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $averageRating = $reviews->isNotEmpty() ? round($reviews->avg('rating'), 1) : 0;

        $ratingCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($reviews as $review) {
            $star = max(1, min(5, (int) round($review->rating)));
            $ratingCounts[$star]++;
        }

        return $this->success([
            'reviews' => $reviews->map(fn ($review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->review,
                'created_at' => $review->created_at->toISOString(),
                'is_verified' => true,
                'reviewer_id' => $review->user?->id,
                'reviewer_name' => $review->user?->name ?? '',
                'reviewer_avatar_url' => $review->user?->avatar
                    ? asset('storage/'.$review->user->avatar)
                    : null,
                'reviewer_gender' => $review->user?->gender ?? 'male',
            ])->toArray(),
            'summary' => [
                'average_rating' => $averageRating,
                'total_reviews' => $reviews->count(),
                'five_star' => $ratingCounts[5],
                'four_star' => $ratingCounts[4],
                'three_star' => $ratingCounts[3],
                'two_star' => $ratingCounts[2],
                'one_star' => $ratingCounts[1],
            ],
        ], __('Reviews retrieved successfully'));
    }
}
