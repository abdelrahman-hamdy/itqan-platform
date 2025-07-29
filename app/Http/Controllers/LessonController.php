<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\StudentProgress;
use App\Models\CourseSubscription;
use App\Models\RecordedCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LessonController extends Controller
{
    /**
     * Display the specified lesson
     */
    public function show(RecordedCourse $course, Lesson $lesson)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Check if user is enrolled in the course
        $enrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment && !$lesson->is_free_preview) {
            return redirect()->route('courses.show', $course)
                ->with('error', 'يجب التسجيل في الدورة أولاً للوصول للدروس');
        }

        // Check if lesson is accessible
        if (!$lesson->isAccessibleBy($user)) {
            return redirect()->route('courses.show', $course)
                ->with('error', 'لا يمكنك الوصول لهذا الدرس');
        }

        $lesson->load(['section', 'quiz', 'recordedCourse.instructor']);
        
        // Get or create progress record
        $progress = StudentProgress::getOrCreate($user, $course, $lesson);
        
        // Get navigation lessons
        $previousLesson = $lesson->getPreviousLesson();
        $nextLesson = $lesson->getNextLesson();
        
        // Get course sections with lessons for sidebar
        $courseSections = $course->sections()
            ->with(['lessons' => function($query) {
                $query->published()->ordered();
            }])
            ->ordered()
            ->get();

        return view('lessons.show', compact(
            'course',
            'lesson', 
            'progress',
            'enrollment',
            'previousLesson', 
            'nextLesson',
            'courseSections'
        ));
    }

    /**
     * Update lesson progress via AJAX
     */
    public function updateProgress(Request $request, RecordedCourse $course, Lesson $lesson)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        
        // Validate enrollment
        $enrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment && !$lesson->is_free_preview) {
            return response()->json(['error' => 'Not enrolled'], 403);
        }

        $validated = $request->validate([
            'current_position' => 'required|integer|min:0',
            'watch_time' => 'required|integer|min:0',
            'is_completed' => 'boolean'
        ]);

        DB::transaction(function() use ($user, $course, $lesson, $validated, $enrollment) {
            // Update lesson progress
            $progress = StudentProgress::getOrCreate($user, $course, $lesson);
            
            $progress->updateProgress(
                $validated['current_position'],
                $lesson->video_duration_seconds
            );

            // If lesson is completed, update course enrollment progress
            if ($validated['is_completed'] && $enrollment) {
                $enrollment->updateProgress();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ التقدم'
        ]);
    }

    /**
     * Mark lesson as completed
     */
    public function markCompleted(Request $request, RecordedCourse $course, Lesson $lesson)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        
        // Check enrollment
        $enrollment = CourseSubscription::where('student_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment && !$lesson->is_free_preview) {
            return response()->json(['error' => 'Not enrolled'], 403);
        }

        DB::transaction(function() use ($user, $course, $lesson, $enrollment) {
            $progress = StudentProgress::getOrCreate($user, $course, $lesson);
            $progress->markAsCompleted();

            // Update course enrollment progress
            if ($enrollment) {
                $enrollment->updateProgress();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'تم إكمال الدرس',
            'next_lesson_url' => $lesson->getNextLesson() ? 
                route('lessons.show', [$course, $lesson->getNextLesson()]) : null
        ]);
    }

    /**
     * Add bookmark to lesson
     */
    public function addBookmark(Request $request, RecordedCourse $course, Lesson $lesson)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $progress = StudentProgress::getOrCreate($user, $course, $lesson);
        
        $progress->addBookmark();

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة العلامة المرجعية'
        ]);
    }

    /**
     * Remove bookmark from lesson
     */
    public function removeBookmark(Request $request, RecordedCourse $course, Lesson $lesson)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $progress = StudentProgress::getOrCreate($user, $course, $lesson);
        
        $progress->removeBookmark();

        return response()->json([
            'success' => true,
            'message' => 'تم إزالة العلامة المرجعية'
        ]);
    }

    /**
     * Add note to lesson
     */
    public function addNote(Request $request, RecordedCourse $course, Lesson $lesson)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'note' => 'required|string|max:1000'
        ]);

        $user = Auth::user();
        $progress = StudentProgress::getOrCreate($user, $course, $lesson);
        
        $progress->addNote($validated['note']);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الملاحظة'
        ]);
    }

    /**
     * Rate lesson
     */
    public function rate(Request $request, RecordedCourse $course, Lesson $lesson)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:500'
        ]);

        $user = Auth::user();
        $progress = StudentProgress::getOrCreate($user, $course, $lesson);
        
        $progress->addRating($validated['rating'], $validated['review'] ?? null);

        // Update lesson stats
        $lesson->updateStats();

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ التقييم'
        ]);
    }

    /**
     * Get lesson notes
     */
    public function getNotes(RecordedCourse $course, Lesson $lesson)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $progress = StudentProgress::where('user_id', $user->id)
            ->where('recorded_course_id', $course->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        $notes = $progress ? $progress->getNotesArray() : [];

        return response()->json([
            'success' => true,
            'notes' => $notes
        ]);
    }

    /**
     * Download lesson materials (if available)
     */
    public function downloadMaterials(RecordedCourse $course, Lesson $lesson)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Check if user has access
        if (!$lesson->isAccessibleBy($user)) {
            abort(403, 'لا يمكنك الوصول لمواد هذا الدرس');
        }

        if (!$lesson->is_downloadable || !$lesson->attachments) {
            abort(404, 'لا توجد مواد قابلة للتحميل');
        }

        // This would handle file downloads
        // Implementation depends on your file storage system
        return response()->json([
            'success' => true,
            'download_links' => $lesson->attachments
        ]);
    }

    /**
     * Get lesson transcript
     */
    public function getTranscript(RecordedCourse $course, Lesson $lesson)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        
        if (!$lesson->isAccessibleBy($user)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return response()->json([
            'success' => true,
            'transcript' => $lesson->transcript
        ]);
    }
} 