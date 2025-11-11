<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\CourseSubscription;
use App\Models\RecordedCourse;
use App\Models\StudentProgress;
use App\Services\HomeworkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show student dashboard
     */
    public function index(Request $request, HomeworkService $homeworkService)
    {
        $user = Auth::user();
        $academy = $this->getCurrentAcademy();

        // Get enrolled courses
        $enrolledCourses = CourseSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['recordedCourse.sections'])
            ->orderBy('enrolled_at', 'desc')
            ->get();

        // Separate courses by status
        $activeCourses = $enrolledCourses->where('status', 'active');
        $completedCourses = $enrolledCourses->where('status', 'completed');
        $inProgressCourses = $activeCourses->where('progress_percentage', '>', 0)
            ->where('progress_percentage', '<', 100);

        // Get recent activity
        $recentProgress = StudentProgress::where('user_id', $user->id)
            ->whereHas('recordedCourse', function ($query) use ($academy) {
                $query->where('academy_id', $academy->id);
            })
            ->with(['recordedCourse', 'lesson'])
            ->orderBy('last_accessed_at', 'desc')
            ->take(10)
            ->get();

        // Calculate statistics
        $stats = $this->calculateStudentStats($user, $academy);

        // Get achievements
        $achievements = $this->getStudentAchievements($user, $academy);

        // Get recommended courses
        $recommendedCourses = $this->getRecommendedCourses($user, $academy);

        // Get homework statistics
        $homeworkStats = $homeworkService->getStudentHomeworkStatistics($user->id, $academy->id);

        // Get pending homework (limited to 5 most recent)
        $pendingHomework = collect($homeworkService->getStudentHomework($user->id, $academy->id))
            ->filter(fn($hw) => !in_array($hw['status'], ['submitted', 'late', 'graded', 'returned']))
            ->take(5);

        return view('student.dashboard', compact(
            'enrolledCourses',
            'activeCourses',
            'completedCourses',
            'inProgressCourses',
            'recentProgress',
            'stats',
            'achievements',
            'recommendedCourses',
            'academy',
            'homeworkStats',
            'pendingHomework'
        ));
    }

    /**
     * Show student's enrolled courses
     */
    public function courses(Request $request)
    {
        $user = Auth::user();
        $academy = $this->getCurrentAcademy();

        $query = CourseSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['recordedCourse.subject']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by completion
        if ($request->filled('completion')) {
            switch ($request->completion) {
                case 'not_started':
                    $query->where('progress_percentage', 0);
                    break;
                case 'in_progress':
                    $query->where('progress_percentage', '>', 0)
                        ->where('progress_percentage', '<', 100);
                    break;
                case 'completed':
                    $query->where('progress_percentage', 100);
                    break;
            }
        }

        // Search
        if ($request->filled('search')) {
            $query->whereHas('recordedCourse', function ($q) use ($request) {
                $q->where('title', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('description', 'LIKE', '%'.$request->search.'%');
            });
        }

        $enrollments = $query->orderBy('enrolled_at', 'desc')->paginate(12);

        return view('student.courses', compact('enrollments', 'academy'));
    }

    /**
     * Show detailed progress for a specific course
     */
    public function courseProgress(CourseSubscription $enrollment)
    {
        if ($enrollment->student_id !== Auth::id()) {
            abort(403);
        }

        $enrollment->load([
            'recordedCourse.sections.lessons',
        ]);

        // Get detailed progress for each lesson
        $lessonsProgress = StudentProgress::where('user_id', Auth::id())
            ->where('recorded_course_id', $enrollment->recorded_course_id)
            ->whereNotNull('lesson_id')
            ->with('lesson')
            ->get()
            ->keyBy('lesson_id');

        // Calculate section progress
        $sectionsProgress = [];
        foreach ($enrollment->recordedCourse->sections as $section) {
            $totalLessons = $section->lessons->count();
            $completedLessons = 0;

            foreach ($section->lessons as $lesson) {
                $progress = $lessonsProgress->get($lesson->id);
                if ($progress && $progress->is_completed) {
                    $completedLessons++;
                }
            }

            $sectionsProgress[$section->id] = [
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'progress_percentage' => $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0,
            ];
        }

        return view('student.course-progress', compact(
            'enrollment',
            'lessonsProgress',
            'sectionsProgress'
        ));
    }

    /**
     * Show student's certificates
     */
    public function certificates()
    {
        $user = Auth::user();
        $academy = $this->getCurrentAcademy();

        $certificates = CourseSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('certificate_issued', true)

            ->orderBy('certificate_issued_at', 'desc')
            ->get();

        return view('student.certificates', compact('certificates', 'academy'));
    }

    /**
     * Download certificate
     */
    public function downloadCertificate(CourseSubscription $enrollment)
    {
        if ($enrollment->student_id !== Auth::id()) {
            abort(403);
        }

        if (! $enrollment->certificate_issued || ! $enrollment->completion_certificate_url) {
            abort(404, 'الشهادة غير متوفرة');
        }

        return redirect($enrollment->completion_certificate_url);
    }

    /**
     * Show student's bookmarked lessons
     */
    public function bookmarks()
    {
        $user = Auth::user();
        $academy = $this->getCurrentAcademy();

        $bookmarkedLessons = StudentProgress::where('user_id', $user->id)
            ->whereNotNull('bookmarked_at')
            ->whereHas('recordedCourse', function ($query) use ($academy) {
                $query->where('academy_id', $academy->id);
            })
            ->with(['recordedCourse', 'lesson.section'])
            ->orderBy('bookmarked_at', 'desc')
            ->paginate(15);

        return view('student.bookmarks', compact('bookmarkedLessons', 'academy'));
    }

    /**
     * Show student's notes
     */
    public function notes()
    {
        $user = Auth::user();
        $academy = $this->getCurrentAcademy();

        $notesProgress = StudentProgress::where('user_id', $user->id)
            ->whereNotNull('notes')
            ->whereHas('recordedCourse', function ($query) use ($academy) {
                $query->where('academy_id', $academy->id);
            })
            ->with(['recordedCourse', 'lesson.section'])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return view('student.notes', compact('notesProgress', 'academy'));
    }

    /**
     * Show learning analytics
     */
    public function analytics(Request $request)
    {
        $user = Auth::user();
        $academy = $this->getCurrentAcademy();

        $period = $request->get('period', '30_days');
        $startDate = $this->getStartDate($period);

        // Watch time statistics
        $watchTimeStats = StudentProgress::where('user_id', $user->id)
            ->whereHas('recordedCourse', function ($query) use ($academy) {
                $query->where('academy_id', $academy->id);
            })
            ->where('last_accessed_at', '>=', $startDate)
            ->selectRaw('DATE(last_accessed_at) as date')
            ->selectRaw('SUM(watch_time_seconds) as total_watch_time')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Completion statistics
        $completionStats = StudentProgress::where('user_id', $user->id)
            ->whereHas('recordedCourse', function ($query) use ($academy) {
                $query->where('academy_id', $academy->id);
            })
            ->where('completed_at', '>=', $startDate)
            ->selectRaw('DATE(completed_at) as date')
            ->selectRaw('COUNT(*) as lessons_completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Subject progress
        $subjectProgress = DB::table('student_progress')
            ->join('recorded_courses', 'student_progress.recorded_course_id', '=', 'recorded_courses.id')
            ->join('academic_subjects', 'recorded_courses.subject_id', '=', 'academic_subjects.id')
            ->where('student_progress.user_id', $user->id)
            ->where('recorded_courses.academy_id', $academy->id)
            ->select('academic_subjects.name')
            ->selectRaw('COUNT(*) as total_lessons')
            ->selectRaw('SUM(CASE WHEN student_progress.is_completed = 1 THEN 1 ELSE 0 END) as completed_lessons')
            ->selectRaw('AVG(student_progress.progress_percentage) as avg_progress')
            ->groupBy('academic_subjects.id', 'academic_subjects.name')
            ->get();

        return view('student.analytics', compact(
            'watchTimeStats',
            'completionStats',
            'subjectProgress',
            'period',
            'academy'
        ));
    }

    /**
     * Calculate student statistics
     */
    private function calculateStudentStats($user, $academy)
    {
        $enrollments = CourseSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->get();

        $totalProgress = StudentProgress::where('user_id', $user->id)
            ->whereHas('recordedCourse', function ($query) use ($academy) {
                $query->where('academy_id', $academy->id);
            })
            ->get();

        return [
            'total_enrollments' => $enrollments->count(),
            'active_courses' => $enrollments->where('status', 'active')->count(),
            'completed_courses' => $enrollments->where('status', 'completed')->count(),
            'certificates_earned' => $enrollments->where('certificate_issued', true)->count(),
            'total_watch_time' => $totalProgress->sum('watch_time_seconds'),
            'total_lessons_completed' => $totalProgress->where('is_completed', true)->count(),
            'avg_progress' => $enrollments->avg('progress_percentage') ?? 0,
            'current_streak' => $this->calculateLearningStreak($user, $academy),
        ];
    }

    /**
     * Get student achievements
     */
    private function getStudentAchievements($user, $academy)
    {
        $achievements = [];
        $stats = $this->calculateStudentStats($user, $academy);

        // Define achievements
        $achievementRules = [
            'first_course' => ['threshold' => 1, 'field' => 'total_enrollments', 'title' => 'الدورة الأولى', 'icon' => 'play-circle'],
            'course_collector' => ['threshold' => 5, 'field' => 'total_enrollments', 'title' => 'جامع الدورات', 'icon' => 'collection'],
            'course_master' => ['threshold' => 10, 'field' => 'total_enrollments', 'title' => 'سيد الدورات', 'icon' => 'academic-cap'],
            'first_completion' => ['threshold' => 1, 'field' => 'completed_courses', 'title' => 'الإنجاز الأول', 'icon' => 'check-circle'],
            'completionist' => ['threshold' => 5, 'field' => 'completed_courses', 'title' => 'مكمل المهام', 'icon' => 'clipboard-check'],
            'certificate_earner' => ['threshold' => 1, 'field' => 'certificates_earned', 'title' => 'حامل الشهادات', 'icon' => 'certificate'],
            'dedication' => ['threshold' => 3600, 'field' => 'total_watch_time', 'title' => 'المثابرة', 'icon' => 'clock'],
            'speed_learner' => ['threshold' => 50, 'field' => 'total_lessons_completed', 'title' => 'متعلم سريع', 'icon' => 'lightning-bolt'],
        ];

        foreach ($achievementRules as $key => $rule) {
            if ($stats[$rule['field']] >= $rule['threshold']) {
                $achievements[] = [
                    'key' => $key,
                    'title' => $rule['title'],
                    'icon' => $rule['icon'],
                    'earned' => true,
                    'progress' => 100,
                ];
            }
        }

        return $achievements;
    }

    /**
     * Get recommended courses for student
     */
    private function getRecommendedCourses($user, $academy)
    {
        // Get subjects from enrolled courses
        $enrolledSubjects = CourseSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with('recordedCourse')
            ->get()
            ->pluck('recordedCourse.subject_id')
            ->filter()
            ->unique();

        // Recommend courses from same subjects or beginner level
        $recommended = RecordedCourse::where('academy_id', $academy->id)
            ->published()
            ->whereNotIn('id', function ($query) use ($user) {
                $query->select('recorded_course_id')
                    ->from('course_subscriptions')
                    ->where('student_id', $user->id);
            })
            ->where(function ($query) use ($enrolledSubjects) {
                $query->whereIn('subject_id', $enrolledSubjects)
                    ->orWhere('level', 'beginner')
                    ->orWhere('is_featured', true);
            })
            ->with(['instructor', 'subject'])
            ->take(6)
            ->get();

        return $recommended;
    }

    /**
     * Calculate learning streak
     */
    private function calculateLearningStreak($user, $academy)
    {
        $streak = 0;
        $currentDate = now()->startOfDay();

        while (true) {
            $hasActivity = StudentProgress::where('user_id', $user->id)
                ->whereHas('recordedCourse', function ($query) use ($academy) {
                    $query->where('academy_id', $academy->id);
                })
                ->whereDate('last_accessed_at', $currentDate)
                ->exists();

            if ($hasActivity) {
                $streak++;
                $currentDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Get start date based on period
     */
    private function getStartDate(string $period)
    {
        switch ($period) {
            case '7_days':
                return now()->subDays(7);
            case '30_days':
                return now()->subDays(30);
            case '90_days':
                return now()->subDays(90);
            case '1_year':
                return now()->subYear();
            default:
                return now()->subDays(30);
        }
    }

    /**
     * Get current academy from subdomain
     */
    private function getCurrentAcademy()
    {
        $subdomain = request()->route('subdomain') ?? 'itqan-academy';

        return Academy::where('subdomain', $subdomain)->firstOrFail();
    }
}
