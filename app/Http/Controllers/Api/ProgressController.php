<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLessonProgressRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Lesson;
use App\Models\RecordedCourse;
use App\Models\StudentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProgressController extends Controller
{
    use ApiResponses;

    /**
     * Get course progress
     */
    public function getCourseProgress($courseId): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized();
        }

        $course = RecordedCourse::findOrFail($courseId);
        $totalLessons = $course->lessons()->where('is_published', true)->count();

        $completedLessons = StudentProgress::where('user_id', $user->id)
            ->where('recorded_course_id', $courseId)
            ->where('is_completed', true)
            ->count();

        $progressPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        return $this->success([
            'progress_percentage' => $progressPercentage,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
        ]);
    }

    /**
     * Get lesson progress
     */
    public function getLessonProgress($courseId, $lessonId): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized();
        }

        $lesson = Lesson::findOrFail($lessonId);
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
    public function updateLessonProgress(UpdateLessonProgressRequest $request, $courseId, $lessonId): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized();
        }

        $lesson = Lesson::findOrFail($lessonId);
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
        ]);
    }

    /**
     * Mark lesson as complete
     */
    public function markLessonComplete($courseId, $lessonId): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized();
        }

        $lesson = Lesson::findOrFail($lessonId);
        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);
        $progress->markAsCompleted();

        return $this->success([
            'progress' => [
                'is_completed' => true,
                'completed_at' => $progress->completed_at,
            ],
        ], __('api.lesson.marked_complete'));
    }

    /**
     * Toggle lesson completion status
     */
    public function toggleLessonCompletion($courseId, $lessonId): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized();
        }

        $lesson = Lesson::findOrFail($lessonId);
        $progress = StudentProgress::getOrCreate($user, $lesson->recordedCourse, $lesson);

        if ($progress->is_completed) {
            // Mark as incomplete
            $progress->update([
                'is_completed' => false,
                'completed_at' => null,
            ]);
            $message = __('api.lesson.marked_incomplete');
        } else {
            // Mark as complete
            $progress->markAsCompleted();
            $message = __('api.lesson.marked_complete');
        }

        return $this->success([
            'progress' => [
                'is_completed' => $progress->is_completed,
                'progress_percentage' => $progress->progress_percentage,
                'completed_at' => $progress->completed_at,
            ],
        ], $message);
    }
}
