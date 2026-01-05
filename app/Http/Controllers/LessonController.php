<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\SessionStatus;
use App\Http\Traits\Api\ApiResponses;
use App\Http\Requests\AddLessonNoteRequest;
use App\Http\Requests\RateLessonRequest;
use App\Http\Requests\UpdateLessonProgressRequest;
use App\Models\CourseSubscription;
use App\Models\Lesson;
use App\Models\RecordedCourse;
use App\Models\StudentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonController extends Controller
{
    use ApiResponses;
    /**
     * Display the specified lesson
     */
    public function show($subdomain, $courseId, $lessonId): View|RedirectResponse
    {
        // Manually resolve the models
        $course = RecordedCourse::findOrFail($courseId);
        $lesson = Lesson::findOrFail($lessonId);

        // Check if lesson is a free preview - allow access without authentication
        if ($lesson->is_free_preview) {
            // Allow access to free preview lessons without authentication
            $user = null;
            $enrollment = null;
        } else {
            // For non-free lessons, require authentication
            if (! Auth::check()) {
                // Get subdomain from current academy context or use default
                $academy = current_academy() ?? \App\Models\Academy::where('subdomain', 'itqan-academy')->first();
                $subdomain = $academy ? $academy->subdomain : 'itqan-academy';

                return redirect()->route('login', ['subdomain' => $subdomain]);
            }

            $user = Auth::user();

            // Check if user is enrolled in the course
            $enrollment = CourseSubscription::where('student_id', $user->id)
                ->where('recorded_course_id', $course->id)
                ->where('status', EnrollmentStatus::ENROLLED->value)
                ->first();

            if (! $enrollment && ! $lesson->is_free_preview) {
                // Redirect to course page using the correct route with subdomain
                $academy = current_academy() ?? $course->academy;
                $subdomain = $academy ? $academy->subdomain : 'itqan-academy';

                return redirect()->route('courses.show', ['subdomain' => $subdomain, 'id' => $course->id])
                    ->with('error', 'يجب التسجيل في الدورة أولاً للوصول للدروس');
            }

            // Authorize view access
            $this->authorize('view', $lesson);
        }

        $lesson->load(['section', 'quiz', 'recordedCourse']);

        // Get or create progress record (only for authenticated users)
        $progress = $user ? StudentProgress::getOrCreate($user, $course, $lesson) : null;

        // Get navigation lessons
        $previousLesson = $lesson->getPreviousLesson();
        $nextLesson = $lesson->getNextLesson();

        // Get course sections with lessons for sidebar
        $courseSections = $course->sections()
            ->with(['lessons' => function ($query) {
                $query->published()->ordered();
            }])
            ->ordered()
            ->get();

        // Get academy context for the view
        $academy = current_academy() ?? $course->academy;

        // Determine if user is enrolled
        $isEnrolled = $user ? $course->isEnrolledBy($user) : false;

        // Calculate progress percentage for enrolled users
        $progressPercentage = 0;
        $completedLessons = 0;
        $totalLessons = $course->lessons->count();

        if ($isEnrolled && $user) {
            $completedLessons = StudentProgress::where('user_id', $user->id)
                ->where('recorded_course_id', $course->id)
                ->where('is_completed', true)
                ->count();
            $progressPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
        }

        return view('student.lesson-detail', compact(
            'course',
            'lesson',
            'progress',
            'enrollment',
            'previousLesson',
            'nextLesson',
            'courseSections',
            'academy',
            'isEnrolled',
            'progressPercentage',
            'completedLessons',
            'totalLessons'
        ));
    }

    /**
     * Mark lesson as completed
     */
    public function markCompleted(Request $request, $subdomain, $courseId, $lessonId): JsonResponse
    {
        // Manually resolve the models
        $course = RecordedCourse::findOrFail($courseId);
        $lesson = Lesson::findOrFail($lessonId);

        if (! Auth::check()) {
            return $this->unauthorized('Unauthorized');
        }

        $user = Auth::user();

        // Check enrollment
        $enrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if (! $enrollment && ! $lesson->is_free_preview) {
            return $this->forbidden('Not enrolled');
        }

        DB::transaction(function () use ($user, $course, $lesson, $enrollment) {
            $progress = StudentProgress::getOrCreate($user, $course, $lesson);
            $progress->markAsCompleted();

            // Update course enrollment progress
            if ($enrollment) {
                $enrollment->updateProgress();
            }
        });

        return $this->success([
            'success' => true,
            'message' => 'تم إكمال الدرس',
            'next_lesson_url' => $lesson->getNextLesson() ?
                route('lessons.show', [$course, $lesson->getNextLesson()]) : null,
        ]);
    }

    /**
     * Add bookmark to lesson
     */
    public function addBookmark(Request $request, $subdomain, $courseId, $lessonId): JsonResponse
    {
        // Manually resolve the models
        $course = RecordedCourse::findOrFail($courseId);
        $lesson = Lesson::findOrFail($lessonId);

        if (! Auth::check()) {
            return $this->unauthorized('Unauthorized');
        }

        $user = Auth::user();
        $progress = StudentProgress::getOrCreate($user, $course, $lesson);

        $progress->addBookmark();

        return $this->success(null, 'تم إضافة العلامة المرجعية');
    }

    /**
     * Remove bookmark from lesson
     */
    public function removeBookmark(Request $request, $subdomain, $courseId, $lessonId): JsonResponse
    {
        // Manually resolve the models
        $course = RecordedCourse::findOrFail($courseId);
        $lesson = Lesson::findOrFail($lessonId);

        if (! Auth::check()) {
            return $this->unauthorized('Unauthorized');
        }

        $user = Auth::user();
        $progress = StudentProgress::getOrCreate($user, $course, $lesson);

        $progress->removeBookmark();

        return $this->success(null, 'تم إزالة العلامة المرجعية');
    }

    /**
     * Add note to lesson
     */
    public function addNote(AddLessonNoteRequest $request, $subdomain, $courseId, $lessonId): JsonResponse
    {
        // Manually resolve the models
        $course = RecordedCourse::findOrFail($courseId);
        $lesson = Lesson::findOrFail($lessonId);

        if (! Auth::check()) {
            return $this->unauthorized('Unauthorized');
        }

        $user = Auth::user();
        $progress = StudentProgress::getOrCreate($user, $course, $lesson);

        $progress->addNote($request->note);

        return $this->success(null, 'تم حفظ الملاحظة');
    }

    /**
     * Rate lesson
     */
    public function rate(RateLessonRequest $request, $subdomain, $courseId, $lessonId): JsonResponse
    {
        // Manually resolve the models
        $course = RecordedCourse::findOrFail($courseId);
        $lesson = Lesson::findOrFail($lessonId);

        if (! Auth::check()) {
            return $this->unauthorized('Unauthorized');
        }

        $user = Auth::user();
        $progress = StudentProgress::getOrCreate($user, $course, $lesson);

        $progress->addRating($request->rating, $request->review ?? null);

        // Update lesson stats
        $lesson->updateStats();

        return $this->success(null, 'تم حفظ التقييم');
    }

    /**
     * Get lesson notes
     */
    public function getNotes($subdomain, $courseId, $lessonId): JsonResponse
    {
        // Manually resolve the models
        $course = RecordedCourse::findOrFail($courseId);
        $lesson = Lesson::findOrFail($lessonId);

        if (! Auth::check()) {
            return $this->unauthorized('Unauthorized');
        }

        $user = Auth::user();
        $progress = StudentProgress::where('user_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        $notes = $progress ? $progress->getNotesArray() : [];

        return $this->success(['notes' => $notes]);
    }

    /**
     * Download lesson materials (if available)
     */
    public function downloadMaterials($subdomain, $courseId, $lessonId): RedirectResponse|JsonResponse
    {
        // Manually resolve the models
        $course = RecordedCourse::findOrFail($courseId);
        $lesson = Lesson::findOrFail($lessonId);

        if (! Auth::check()) {
            $subdomain = request()->route('subdomain') ?? 'itqan-academy';

            return redirect()->route('login', ['subdomain' => $subdomain]);
        }

        $user = Auth::user();

        // Authorize download access
        $this->authorize('downloadMaterials', $lesson);

        if (! $lesson->is_downloadable || ! $lesson->attachments) {
            abort(404, 'لا توجد مواد قابلة للتحميل');
        }

        // This would handle file downloads
        // Implementation depends on your file storage system
        return $this->success(['download_links' => $lesson->attachments]);
    }

    /**
     * Handle CORS preflight requests for video serving
     */
    public function serveVideoOptions($subdomain, $courseId, $lessonId): Response
    {
        return response('', 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Range, Content-Range, Content-Length',
            'Access-Control-Max-Age' => '86400',
        ]);
    }

    /**
     * Serve video file for playback
     */
    public function serveVideo($subdomain, $courseId, $lessonId): RedirectResponse|JsonResponse|Response|BinaryFileResponse
    {
        $course = RecordedCourse::findOrFail($courseId);
        $lesson = Lesson::findOrFail($lessonId);

        // Check if lesson is a free preview - allow access without authentication
        if ($lesson->is_free_preview) {
            // Allow access to free preview lessons without authentication
            $user = null;
        } else {
            // For non-free lessons, require authentication
            if (! Auth::check()) {
                return redirect()->route('login', ['subdomain' => $subdomain]);
            }

            $user = Auth::user();

            // Authorization check
            $this->authorize('view', $lesson);
        }

        if (! $lesson->video_url) {
            abort(404, 'لا يوجد رابط فيديو لهذا الدرس');
        }

        // Check both public and storage paths
        $publicPath = public_path($lesson->video_url);
        $storagePath = storage_path('app/public/'.$lesson->video_url);

        $filePath = null;
        if (file_exists($publicPath)) {
            $filePath = $publicPath;
        } elseif (file_exists($storagePath)) {
            $filePath = $storagePath;
        }

        if (! $filePath) {
            // If video file doesn't exist in either location, return a placeholder response
            return $this->error('الفيديو غير متاح حالياً', 404, 'VIDEO_NOT_AVAILABLE');
        }

        // Check if file is a valid video (basic check)
        $fileSize = filesize($filePath);
        if ($fileSize < 1000) { // Less than 1KB is likely not a real video
            return $this->error('الفيديو غير متاح حالياً', 404, 'VIDEO_NOT_AVAILABLE');
        }

        // Check if file is actually a valid MP4 (basic header check)
        $handle = fopen($filePath, 'rb');
        $header = fread($handle, 8);
        fclose($handle);

        // MP4 files should start with specific bytes
        if (substr($header, 4, 4) !== 'ftyp') {
            return $this->error('الفيديو غير متاح حالياً', 404, 'VIDEO_NOT_AVAILABLE');
        }

        // Serve the video file with proper range request support
        $fileName = basename($lesson->video_url);
        $fileSize = filesize($filePath);

        // Handle range requests for video streaming
        $range = request()->header('Range');

        if ($range) {
            // Parse range header
            $ranges = explode('=', $range);
            $offsets = explode('-', $ranges[1]);
            $offset = intval($offsets[0]);
            $length = intval($offsets[1]) ?: $fileSize - 1;

            if ($offset > $fileSize) {
                return response('Requested Range Not Satisfiable', 416);
            }

            $length = min($length, $fileSize - 1);
            $actualLength = $length - $offset + 1;

            // Set partial content headers
            $headers = [
                'Content-Type' => 'video/mp4',
                'Content-Length' => $actualLength,
                'Content-Range' => "bytes $offset-$length/$fileSize",
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'private, max-age=3600',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
                'Access-Control-Allow-Headers' => 'Range, Content-Range, Content-Length',
            ];

            // Only allow download if the lesson is downloadable
            if ($lesson->is_downloadable) {
                $headers['Content-Disposition'] = 'inline; filename="'.$fileName.'"';
            } else {
                $headers['Content-Disposition'] = 'inline';
            }

            // Read and return the requested range
            $file = fopen($filePath, 'rb');
            fseek($file, $offset);
            $content = fread($file, $actualLength);
            fclose($file);

            return response($content, 206, $headers);
        } else {
            // Full file request
            $headers = [
                'Content-Type' => 'video/mp4',
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'private, max-age=3600',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
                'Access-Control-Allow-Headers' => 'Range, Content-Range, Content-Length',
            ];

            // Only allow download if the lesson is downloadable
            if ($lesson->is_downloadable) {
                $headers['Content-Disposition'] = 'inline; filename="'.$fileName.'"';
            } else {
                $headers['Content-Disposition'] = 'inline';
            }

            return response()->file($filePath, $headers);
        }
    }

    /**
     * Get lesson progress
     */
    public function getProgress($courseId, $lessonId): JsonResponse
    {
        $lesson = Lesson::findOrFail($lessonId);
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized('Unauthorized');
        }

        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);

        return $this->success([
            'progress' => [
                'current_position_seconds' => $progress->current_position_seconds,
                'progress_percentage' => $progress->progress_percentage,
                'is_completed' => $progress->is_completed,
                'total_time_seconds' => $progress->total_time_seconds,
            ],
        ]);
    }

    /**
     * Update lesson progress
     */
    public function updateProgress($courseId, $lessonId, UpdateLessonProgressRequest $request): JsonResponse
    {
        $lesson = Lesson::findOrFail($lessonId);
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized('Unauthorized');
        }

        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);
        $progress->updateProgress(
            (int) $request->current_time,
            (int) $request->total_time
        );

        return $this->success([
            'progress' => [
                'current_position_seconds' => $progress->current_position_seconds,
                'progress_percentage' => $progress->progress_percentage,
                'is_completed' => $progress->is_completed,
            ],
        ], 'Progress updated successfully');
    }

    /**
     * Mark lesson as complete
     */
    public function markComplete($courseId, $lessonId): JsonResponse
    {
        $lesson = Lesson::findOrFail($lessonId);
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized('Unauthorized');
        }

        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);
        $progress->markAsCompleted();

        return $this->success([
            'progress' => [
                'is_completed' => true,
                'completed_at' => $progress->completed_at,
            ],
        ], 'Lesson marked as complete');
    }

    /**
     * Get lesson transcript
     */
    public function getTranscript($subdomain, $courseId, $lessonId): JsonResponse
    {
        // Manually resolve the models
        $course = RecordedCourse::findOrFail($courseId);
        $lesson = Lesson::findOrFail($lessonId);

        if (! Auth::check()) {
            return $this->unauthorized('Unauthorized');
        }

        $user = Auth::user();

        if (! $lesson->isAccessibleBy($user)) {
            return $this->forbidden('Access denied');
        }

        return $this->success(['transcript' => $lesson->transcript]);
    }
}
