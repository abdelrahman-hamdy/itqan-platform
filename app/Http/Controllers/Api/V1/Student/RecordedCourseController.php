<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\CourseType;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\CourseSubscription;
use App\Models\Lesson;
use App\Models\RecordedCourse;
use App\Models\StudentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RecordedCourseController extends Controller
{
    use ApiResponses;

    /**
     * Get all enrolled recorded courses for the student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->get('status'); // enrolled, completed, all
        $perPage = min(
            (int) $request->get('per_page', config('api.pagination.default_per_page', 15)),
            config('api.pagination.max_per_page', 50)
        );

        $query = CourseSubscription::where('student_id', $user->id)
            ->where('course_type', CourseType::RECORDED)
            ->whereNotNull('recorded_course_id')
            ->with([
                'recordedCourse' => function ($q) {
                    $q->with(['subject', 'gradeLevel']);
                },
            ]);

        if ($status && $status !== 'all') {
            $statusEnum = match ($status) {
                'enrolled' => EnrollmentStatus::ENROLLED,
                'completed' => EnrollmentStatus::COMPLETED,
                'pending' => EnrollmentStatus::PENDING,
                default => null,
            };

            if ($statusEnum) {
                $query->where('status', $statusEnum);
            }
        } else {
            // By default, show active and completed courses
            $query->whereIn('status', [EnrollmentStatus::ENROLLED, EnrollmentStatus::COMPLETED]);
        }

        $paginator = $query->orderBy('enrolled_at', 'desc')->paginate($perPage);

        $courses = collect($paginator->items())->map(function ($subscription) {
            $course = $subscription->recordedCourse;
            if (! $course) {
                return null;
            }

            return [
                'id' => $course->id,
                'subscription_id' => $subscription->id,
                'title' => $course->title,
                'description' => $course->description,
                'thumbnail_url' => $course->thumbnail_url
                    ? asset('storage/'.$course->thumbnail_url)
                    : null,
                'duration_formatted' => $course->duration_formatted,
                'total_duration_minutes' => $course->total_duration_minutes,
                'total_lessons' => $course->total_lessons,
                'total_sections' => $course->total_sections ?? 0,
                'difficulty_level' => $course->difficulty_level,
                'avg_rating' => (float) ($course->avg_rating ?? 0),
                'total_reviews' => $course->total_reviews ?? 0,
                'subject' => $course->subject?->name,
                'grade_level' => $course->gradeLevel?->name,
                'progress' => [
                    'percentage' => (float) ($subscription->progress_percentage ?? 0),
                    'completed_lessons' => $subscription->completed_lessons ?? 0,
                    'total_lessons' => $subscription->total_lessons ?? $course->total_lessons ?? 0,
                ],
                'status' => $subscription->status->value ?? $subscription->status,
                'status_label' => $subscription->status->label ?? $subscription->status,
                'enrolled_at' => $subscription->enrolled_at?->toISOString(),
                'completion_date' => $subscription->completion_date?->toISOString(),
                'last_accessed_at' => $subscription->last_accessed_at?->toISOString(),
                'access_status' => $subscription->access_status,
                'can_access' => $subscription->canAccess(),
            ];
        })->filter()->values()->toArray();

        return $this->success([
            'courses' => $courses,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'stats' => [
                'enrolled' => CourseSubscription::where('student_id', $user->id)
                    ->where('course_type', CourseType::RECORDED)
                    ->where('status', EnrollmentStatus::ENROLLED)
                    ->count(),
                'completed' => CourseSubscription::where('student_id', $user->id)
                    ->where('course_type', CourseType::RECORDED)
                    ->where('status', EnrollmentStatus::COMPLETED)
                    ->count(),
                'in_progress' => CourseSubscription::where('student_id', $user->id)
                    ->where('course_type', CourseType::RECORDED)
                    ->where('status', EnrollmentStatus::ENROLLED)
                    ->where('progress_percentage', '>', 0)
                    ->where('progress_percentage', '<', 100)
                    ->count(),
            ],
        ], __('Recorded courses retrieved successfully'));
    }

    /**
     * Get a specific recorded course details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $subscription = CourseSubscription::where('student_id', $user->id)
            ->where('course_type', CourseType::RECORDED)
            ->where('recorded_course_id', $id)
            ->whereIn('status', [EnrollmentStatus::ENROLLED, EnrollmentStatus::COMPLETED, EnrollmentStatus::PENDING])
            ->with([
                'recordedCourse' => function ($q) {
                    $q->with(['subject', 'gradeLevel', 'sections.lessons' => function ($q) {
                        $q->where('is_published', true)->orderBy('id');
                    }]);
                },
            ])
            ->first();

        if (! $subscription) {
            return $this->notFound(__('Course not found or you are not enrolled.'));
        }

        if (! $subscription->canAccess()) {
            return $this->error(
                __('You do not have access to this course.'),
                403,
                'ACCESS_DENIED'
            );
        }

        $course = $subscription->recordedCourse;

        // Get user's progress for all lessons in this course
        $progressRecords = StudentProgress::where('user_id', $user->id)
            ->where('recorded_course_id', $id)
            ->whereNotNull('lesson_id')
            ->get()
            ->keyBy('lesson_id');

        // Format sections with lessons and progress
        $sections = $course->sections->map(function ($section) use ($progressRecords) {
            return [
                'id' => $section->id,
                'title' => $section->title,
                'description' => $section->description,
                'order' => $section->order ?? 1,
                'lessons_count' => $section->lessons->count(),
                'lessons' => $section->lessons->map(function ($lesson) use ($progressRecords) {
                    $progress = $progressRecords->get($lesson->id);

                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'description' => $lesson->description,
                        'duration_minutes' => $lesson->duration_minutes ?? 0,
                        'order' => $lesson->order,
                        'is_free_preview' => $lesson->is_free_preview,
                        'has_video' => ! empty($lesson->video_url),
                        'has_attachments' => ! empty($lesson->attachments),
                        'progress' => [
                            'percentage' => (float) ($progress?->progress_percentage ?? 0),
                            'is_completed' => (bool) ($progress?->is_completed ?? false),
                            'is_bookmarked' => (bool) ($progress?->is_bookmarked ?? false),
                            'last_position_seconds' => $progress?->current_position_seconds ?? 0,
                        ],
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        // Get next lesson to continue
        $nextLesson = $subscription->getNextLesson();

        return $this->success([
            'course' => [
                'id' => $course->id,
                'subscription_id' => $subscription->id,
                'title' => $course->title,
                'description' => $course->description,
                'thumbnail_url' => $course->thumbnail_url
                    ? asset('storage/'.$course->thumbnail_url)
                    : null,
                'duration_formatted' => $course->duration_formatted,
                'total_duration_minutes' => $course->total_duration_minutes,
                'total_lessons' => $course->total_lessons,
                'total_sections' => $course->total_sections ?? 0,
                'difficulty_level' => $course->difficulty_level,
                'prerequisites' => $course->prerequisites ?? [],
                'learning_outcomes' => $course->learning_outcomes ?? [],
                'avg_rating' => (float) ($course->avg_rating ?? 0),
                'total_reviews' => $course->total_reviews ?? 0,
                'subject' => $course->subject?->name,
                'grade_level' => $course->gradeLevel?->name,
                'progress' => [
                    'percentage' => (float) ($subscription->progress_percentage ?? 0),
                    'completed_lessons' => $subscription->completed_lessons ?? 0,
                    'total_lessons' => $subscription->total_lessons ?? $course->total_lessons ?? 0,
                    'status' => $subscription->progress_percentage >= 100 ? 'completed' : ($subscription->progress_percentage > 0 ? 'in_progress' : 'not_started'),
                ],
                'status' => $subscription->status->value ?? $subscription->status,
                'status_label' => $subscription->status->label ?? $subscription->status,
                'enrolled_at' => $subscription->enrolled_at?->toISOString(),
                'completion_date' => $subscription->completion_date?->toISOString(),
                'last_accessed_at' => $subscription->last_accessed_at?->toISOString(),
                'access_status' => $subscription->access_status,
                'can_earn_certificate' => $subscription->can_earn_certificate,
                'certificate_issued' => $subscription->certificate_issued,
                'next_lesson' => $nextLesson ? [
                    'id' => $nextLesson->id,
                    'title' => $nextLesson->title,
                ] : null,
                'sections' => $sections,
            ],
        ], __('Course retrieved successfully'));
    }

    /**
     * Get lessons for a specific course (flat list).
     */
    public function lessons(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $subscription = CourseSubscription::where('student_id', $user->id)
            ->where('course_type', CourseType::RECORDED)
            ->where('recorded_course_id', $id)
            ->whereIn('status', [EnrollmentStatus::ENROLLED, EnrollmentStatus::COMPLETED])
            ->first();

        if (! $subscription || ! $subscription->canAccess()) {
            return $this->notFound(__('Course not found or you do not have access.'));
        }

        $course = RecordedCourse::with(['lessons' => function ($q) {
            $q->where('is_published', true)->orderBy('id');
        }])->find($id);

        // Get user's progress for all lessons
        $progressRecords = StudentProgress::where('user_id', $user->id)
            ->where('recorded_course_id', $id)
            ->whereNotNull('lesson_id')
            ->get()
            ->keyBy('lesson_id');

        $lessons = $course->lessons->map(function ($lesson) use ($progressRecords) {
            $progress = $progressRecords->get($lesson->id);

            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'duration_minutes' => $lesson->duration_minutes ?? 0,
                'order' => $lesson->order,
                'section_id' => $lesson->course_section_id,
                'is_free_preview' => $lesson->is_free_preview,
                'is_downloadable' => $lesson->is_downloadable,
                'video_url' => $lesson->video_url,
                'video_quality' => $lesson->video_quality,
                'has_attachments' => ! empty($lesson->attachments),
                'attachments_count' => is_array($lesson->attachments) ? count($lesson->attachments) : 0,
                'learning_objectives' => $lesson->learning_objectives ?? [],
                'progress' => [
                    'percentage' => (float) ($progress?->progress_percentage ?? 0),
                    'is_completed' => (bool) ($progress?->is_completed ?? false),
                    'is_bookmarked' => (bool) ($progress?->is_bookmarked ?? false),
                    'last_position_seconds' => $progress?->current_position_seconds ?? 0,
                    'notes_count' => count($progress?->getNotesArray() ?? []),
                    'rating' => $progress?->rating,
                ],
            ];
        })->values()->toArray();

        return $this->success([
            'lessons' => $lessons,
            'total' => count($lessons),
            'course_progress' => [
                'percentage' => (float) ($subscription->progress_percentage ?? 0),
                'completed_lessons' => $subscription->completed_lessons ?? 0,
                'total_lessons' => $subscription->total_lessons ?? count($lessons),
            ],
        ], __('Lessons retrieved successfully'));
    }

    /**
     * Get a specific lesson details.
     *
     * @param  int  $id  Course ID
     * @param  int  $lessonId  Lesson ID
     */
    public function lesson(Request $request, int $id, int $lessonId): JsonResponse
    {
        $user = $request->user();

        $subscription = CourseSubscription::where('student_id', $user->id)
            ->where('course_type', CourseType::RECORDED)
            ->where('recorded_course_id', $id)
            ->whereIn('status', [EnrollmentStatus::ENROLLED, EnrollmentStatus::COMPLETED])
            ->first();

        $lesson = Lesson::where('id', $lessonId)
            ->where('recorded_course_id', $id)
            ->where('is_published', true)
            ->first();

        if (! $lesson) {
            return $this->notFound(__('Lesson not found.'));
        }

        // Check access - allow free preview or enrolled students
        if (! $lesson->is_free_preview && (! $subscription || ! $subscription->canAccess())) {
            return $this->error(
                __('You do not have access to this lesson.'),
                403,
                'ACCESS_DENIED'
            );
        }

        // Get or create progress record
        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);

        // Update last accessed
        $progress->update(['last_accessed_at' => now()]);
        if ($subscription) {
            $subscription->update(['last_accessed_at' => now()]);
        }

        // Get next/previous lessons
        $nextLesson = $lesson->getNextLesson();
        $previousLesson = $lesson->getPreviousLesson();

        return $this->success([
            'lesson' => [
                'id' => $lesson->id,
                'course_id' => $lesson->recorded_course_id,
                'section_id' => $lesson->course_section_id,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'duration_minutes' => $lesson->duration_minutes ?? 0,
                'order' => $lesson->order,
                'video_url' => $lesson->video_url,
                'video_quality' => $lesson->video_quality,
                'transcript' => $lesson->transcript,
                'is_free_preview' => $lesson->is_free_preview,
                'is_downloadable' => $lesson->is_downloadable,
                'learning_objectives' => $lesson->learning_objectives ?? [],
                'attachments' => $this->formatAttachments($lesson->attachments ?? []),
                'progress' => [
                    'percentage' => (float) ($progress->progress_percentage ?? 0),
                    'is_completed' => (bool) $progress->is_completed,
                    'is_bookmarked' => (bool) $progress->is_bookmarked,
                    'last_position_seconds' => $progress->current_position_seconds ?? 0,
                    'total_time_seconds' => $progress->total_time_seconds ?? 0,
                    'notes' => $progress->getNotesArray(),
                    'rating' => $progress->rating,
                    'review_text' => $progress->review_text,
                ],
                'navigation' => [
                    'previous' => $previousLesson ? [
                        'id' => $previousLesson->id,
                        'title' => $previousLesson->title,
                    ] : null,
                    'next' => $nextLesson ? [
                        'id' => $nextLesson->id,
                        'title' => $nextLesson->title,
                    ] : null,
                ],
            ],
        ], __('Lesson retrieved successfully'));
    }

    /**
     * Update lesson progress.
     *
     * @param  int  $id  Course ID
     * @param  int  $lessonId  Lesson ID
     */
    public function updateProgress(Request $request, int $id, int $lessonId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_position_seconds' => ['required', 'integer', 'min:0'],
            'total_duration_seconds' => ['nullable', 'integer', 'min:0'],
            'mark_completed' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();

        $subscription = CourseSubscription::where('student_id', $user->id)
            ->where('course_type', CourseType::RECORDED)
            ->where('recorded_course_id', $id)
            ->whereIn('status', [EnrollmentStatus::ENROLLED, EnrollmentStatus::COMPLETED])
            ->first();

        if (! $subscription || ! $subscription->canAccess()) {
            return $this->error(
                __('You do not have access to this course.'),
                403,
                'ACCESS_DENIED'
            );
        }

        $lesson = Lesson::where('id', $lessonId)
            ->where('recorded_course_id', $id)
            ->where('is_published', true)
            ->first();

        if (! $lesson) {
            return $this->notFound(__('Lesson not found.'));
        }

        // Get or create progress record
        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);

        if ($request->boolean('mark_completed')) {
            $progress->markAsCompleted();
        } else {
            $progress->updateProgress(
                $request->integer('current_position_seconds'),
                $request->integer('total_duration_seconds')
            );
        }

        // Update course subscription progress
        $subscription->updateRecordedCourseProgress();

        return $this->success([
            'progress' => [
                'lesson_id' => $lesson->id,
                'percentage' => (float) $progress->progress_percentage,
                'is_completed' => (bool) $progress->is_completed,
                'current_position_seconds' => $progress->current_position_seconds,
            ],
            'course_progress' => [
                'percentage' => (float) $subscription->fresh()->progress_percentage,
                'completed_lessons' => $subscription->completed_lessons,
                'total_lessons' => $subscription->total_lessons,
            ],
        ], __('Progress updated successfully'));
    }

    /**
     * Get lesson materials for download.
     *
     * @param  int  $id  Course ID
     * @param  int  $lessonId  Lesson ID
     */
    public function materials(Request $request, int $id, int $lessonId): JsonResponse
    {
        $user = $request->user();

        $subscription = CourseSubscription::where('student_id', $user->id)
            ->where('course_type', CourseType::RECORDED)
            ->where('recorded_course_id', $id)
            ->whereIn('status', [EnrollmentStatus::ENROLLED, EnrollmentStatus::COMPLETED])
            ->first();

        if (! $subscription || ! $subscription->canAccess()) {
            return $this->error(
                __('You do not have access to this course.'),
                403,
                'ACCESS_DENIED'
            );
        }

        $lesson = Lesson::where('id', $lessonId)
            ->where('recorded_course_id', $id)
            ->where('is_published', true)
            ->first();

        if (! $lesson) {
            return $this->notFound(__('Lesson not found.'));
        }

        $materials = $this->formatAttachments($lesson->attachments ?? []);

        // Also get course-level materials if any
        $course = RecordedCourse::find($id);
        $courseMaterials = $this->formatAttachments($course->materials ?? []);

        return $this->success([
            'lesson_materials' => $materials,
            'course_materials' => $courseMaterials,
            'total' => count($materials) + count($courseMaterials),
        ], __('Materials retrieved successfully'));
    }

    /**
     * Add a note to a lesson.
     *
     * @param  int  $id  Course ID
     * @param  int  $lessonId  Lesson ID
     */
    public function addNote(Request $request, int $id, int $lessonId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'note' => ['required', 'string', 'max:2000'],
            'timestamp_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();

        $subscription = CourseSubscription::where('student_id', $user->id)
            ->where('course_type', CourseType::RECORDED)
            ->where('recorded_course_id', $id)
            ->whereIn('status', [EnrollmentStatus::ENROLLED, EnrollmentStatus::COMPLETED])
            ->first();

        if (! $subscription || ! $subscription->canAccess()) {
            return $this->error(
                __('You do not have access to this course.'),
                403,
                'ACCESS_DENIED'
            );
        }

        $lesson = Lesson::where('id', $lessonId)
            ->where('recorded_course_id', $id)
            ->where('is_published', true)
            ->first();

        if (! $lesson) {
            return $this->notFound(__('Lesson not found.'));
        }

        // Get or create progress record
        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);

        // Update position if timestamp provided
        if ($request->has('timestamp_seconds')) {
            $progress->update(['current_position_seconds' => $request->integer('timestamp_seconds')]);
        }

        $progress->addNote($request->input('note'));

        // Update notes count on subscription
        $subscription->increment('notes_count');

        return $this->created([
            'notes' => $progress->getNotesArray(),
            'notes_count' => count($progress->getNotesArray()),
        ], __('Note added successfully'));
    }

    /**
     * Rate a lesson.
     *
     * @param  int  $id  Course ID
     * @param  int  $lessonId  Lesson ID
     */
    public function rate(Request $request, int $id, int $lessonId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review_text' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();

        $subscription = CourseSubscription::where('student_id', $user->id)
            ->where('course_type', CourseType::RECORDED)
            ->where('recorded_course_id', $id)
            ->whereIn('status', [EnrollmentStatus::ENROLLED, EnrollmentStatus::COMPLETED])
            ->first();

        if (! $subscription || ! $subscription->canAccess()) {
            return $this->error(
                __('You do not have access to this course.'),
                403,
                'ACCESS_DENIED'
            );
        }

        $lesson = Lesson::where('id', $lessonId)
            ->where('recorded_course_id', $id)
            ->where('is_published', true)
            ->first();

        if (! $lesson) {
            return $this->notFound(__('Lesson not found.'));
        }

        // Get or create progress record
        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);

        $progress->addRating($request->integer('rating'), $request->input('review_text'));

        // Update lesson stats
        $lesson->updateStats();

        return $this->success([
            'rating' => $progress->rating,
            'review_text' => $progress->review_text,
        ], __('Rating submitted successfully'));
    }

    /**
     * Toggle lesson bookmark.
     *
     * @param  int  $id  Course ID
     * @param  int  $lessonId  Lesson ID
     */
    public function toggleBookmark(Request $request, int $id, int $lessonId): JsonResponse
    {
        $user = $request->user();

        $subscription = CourseSubscription::where('student_id', $user->id)
            ->where('course_type', CourseType::RECORDED)
            ->where('recorded_course_id', $id)
            ->whereIn('status', [EnrollmentStatus::ENROLLED, EnrollmentStatus::COMPLETED])
            ->first();

        if (! $subscription || ! $subscription->canAccess()) {
            return $this->error(
                __('You do not have access to this course.'),
                403,
                'ACCESS_DENIED'
            );
        }

        $lesson = Lesson::where('id', $lessonId)
            ->where('recorded_course_id', $id)
            ->where('is_published', true)
            ->first();

        if (! $lesson) {
            return $this->notFound(__('Lesson not found.'));
        }

        // Get or create progress record
        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);

        $wasBookmarked = $progress->is_bookmarked;
        $progress->toggleBookmark();

        // Update bookmarks count on subscription
        if ($wasBookmarked) {
            $subscription->decrement('bookmarks_count');
        } else {
            $subscription->increment('bookmarks_count');
        }

        return $this->success([
            'is_bookmarked' => $progress->fresh()->is_bookmarked,
        ], $progress->is_bookmarked
            ? __('Lesson bookmarked')
            : __('Bookmark removed')
        );
    }

    /**
     * Get all bookmarked lessons.
     *
     * @param  int  $id  Course ID
     */
    public function bookmarks(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $subscription = CourseSubscription::where('student_id', $user->id)
            ->where('course_type', CourseType::RECORDED)
            ->where('recorded_course_id', $id)
            ->whereIn('status', [EnrollmentStatus::ENROLLED, EnrollmentStatus::COMPLETED])
            ->first();

        if (! $subscription || ! $subscription->canAccess()) {
            return $this->error(
                __('You do not have access to this course.'),
                403,
                'ACCESS_DENIED'
            );
        }

        $bookmarkedProgress = StudentProgress::where('user_id', $user->id)
            ->where('recorded_course_id', $id)
            ->whereNotNull('lesson_id')
            ->whereNotNull('bookmarked_at')
            ->with('lesson')
            ->orderBy('bookmarked_at', 'desc')
            ->get();

        $bookmarks = $bookmarkedProgress->map(function ($progress) {
            if (! $progress->lesson) {
                return null;
            }

            return [
                'lesson_id' => $progress->lesson_id,
                'lesson_title' => $progress->lesson->title,
                'lesson_order' => $progress->lesson->order,
                'bookmarked_at' => $progress->bookmarked_at?->toISOString(),
                'progress_percentage' => (float) $progress->progress_percentage,
                'is_completed' => (bool) $progress->is_completed,
            ];
        })->filter()->values()->toArray();

        return $this->success([
            'bookmarks' => $bookmarks,
            'total' => count($bookmarks),
        ], __('Bookmarks retrieved successfully'));
    }

    /**
     * Format attachments for response.
     */
    protected function formatAttachments(array $attachments): array
    {
        return collect($attachments)->map(function ($attachment) {
            $path = is_array($attachment) ? ($attachment['path'] ?? $attachment['url'] ?? '') : $attachment;
            $name = is_array($attachment) ? ($attachment['name'] ?? basename($path)) : basename($path);
            $size = is_array($attachment) ? ($attachment['size'] ?? null) : null;
            $mime = is_array($attachment) ? ($attachment['mime'] ?? null) : null;

            return [
                'name' => $name,
                'url' => $path ? asset('storage/'.$path) : null,
                'size' => $size,
                'size_formatted' => $size ? $this->formatFileSize($size) : null,
                'mime_type' => $mime,
            ];
        })->filter(fn ($a) => ! empty($a['url']))->values()->toArray();
    }

    /**
     * Format file size for display.
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1).' MB';
        }

        return round($bytes / 1073741824, 1).' GB';
    }
}
