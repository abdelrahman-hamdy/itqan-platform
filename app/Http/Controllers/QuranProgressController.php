<?php

namespace App\Http\Controllers;

use App\Models\QuranProgress;
use App\Models\QuranSubscription;
use App\Models\QuranCircle;
use App\Models\QuranTeacher;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuranProgressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('tenant');
    }

    /**
     * Display a listing of progress records
     */
    public function index(Request $request): View|JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $query = QuranProgress::with([
                'student',
                'quranTeacher.user',
                'subscription',
                'circle',
                'session',
                'academy'
            ])
            ->where('academy_id', $academy->id);

        // Apply filters
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('quran_teacher_id', $request->teacher_id);
        }

        if ($request->filled('progress_type')) {
            $query->where('progress_type', $request->progress_type);
        }

        if ($request->filled('progress_status')) {
            $query->where('progress_status', $request->progress_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('progress_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('progress_date', '<=', $request->date_to);
        }

        $progress = $query->orderBy('progress_date', 'desc')->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => 'قائمة التقدم تم جلبها بنجاح'
            ]);
        }

        return view('quran.progress.index', compact('progress', 'academy'));
    }

    /**
     * Store a new progress record
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'quran_teacher_id' => 'required|exists:users,id',
            'quran_subscription_id' => 'nullable|exists:quran_subscriptions,id',
            'circle_id' => 'nullable|exists:quran_circles,id',
            'session_id' => 'nullable|exists:quran_sessions,id',
            'progress_type' => 'required|in:memorization,recitation,review,assessment,test,milestone',
            'current_surah' => 'nullable|integer|min:1|max:114',
            'current_verse' => 'nullable|integer|min:1',
            'target_surah' => 'nullable|integer|min:1|max:114',
            'target_verse' => 'nullable|integer|min:1',
            'verses_memorized' => 'required|integer|min:0',
            'verses_reviewed' => 'nullable|integer|min:0',
            'verses_perfect' => 'nullable|integer|min:0',
            'verses_need_work' => 'nullable|integer|min:0',
            'recitation_quality' => 'nullable|numeric|min:1|max:10',
            'tajweed_accuracy' => 'nullable|numeric|min:1|max:10',
            'fluency_level' => 'nullable|numeric|min:1|max:10',
            'confidence_level' => 'nullable|numeric|min:1|max:10',
            'retention_rate' => 'nullable|numeric|min:0|max:100',
            'common_mistakes' => 'nullable|array',
            'improvement_areas' => 'nullable|array',
            'strengths' => 'nullable|array',
            'weekly_goal' => 'nullable|integer|min:0',
            'monthly_goal' => 'nullable|integer|min:0',
            'difficulty_level' => 'nullable|in:very_easy,easy,moderate,challenging,very_challenging',
            'study_hours_this_week' => 'nullable|numeric|min:0',
            'mastery_level' => 'required|in:beginner,developing,proficient,advanced,expert,master',
            'milestones_achieved' => 'nullable|array',
            'challenges_faced' => 'nullable|array',
            'support_needed' => 'nullable|array',
            'recommendations' => 'nullable|array',
            'next_steps' => 'nullable|array',
            'teacher_notes' => 'nullable|string|max:1000',
            'parent_notes' => 'nullable|string|max:500',
            'student_feedback' => 'nullable|string|max:500',
            'overall_rating' => 'nullable|integer|min:1|max:5',
            'progress_status' => 'required|in:on_track,ahead,behind,needs_attention,excellent,struggling',
        ]);

        try {
            DB::beginTransaction();

            $progressData = array_merge($validated, [
                'academy_id' => $academy->id,
                'progress_code' => $this->generateProgressCode($academy->id),
                'progress_date' => now()->toDateString(),
                'created_by' => Auth::id(),
            ]);

            // Calculate derived fields
            $progressData['total_verses_memorized'] = $this->calculateTotalVersesMemorized(
                $validated['student_id'],
                $academy->id,
                $validated['verses_memorized']
            );

            $progressData['memorization_percentage'] = $this->calculateMemorizationPercentage(
                $progressData['total_verses_memorized']
            );

            $progressData['goal_progress'] = $this->calculateGoalProgress(
                $validated['verses_memorized'],
                $validated['weekly_goal'] ?? 0
            );

            $progress = QuranProgress::create($progressData);

            // Update related subscription/circle progress
            if ($validated['quran_subscription_id']) {
                $this->updateSubscriptionProgress($validated['quran_subscription_id'], $progressData);
            }

            if ($validated['circle_id']) {
                $this->updateCircleProgress($validated['circle_id'], $validated['student_id'], $progressData);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $progress->load(['student', 'quranTeacher.user']),
                    'message' => 'تم تسجيل التقدم بنجاح'
                ], 201);
            }

            return back()->with('success', 'تم تسجيل التقدم بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تسجيل التقدم: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء تسجيل التقدم: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified progress record
     */
    public function show(QuranProgress $progress): View|JsonResponse
    {
        $this->ensureProgressBelongsToAcademy($progress);
        
        $progress->load([
            'student',
            'quranTeacher.user',
            'subscription',
            'circle',
            'session',
            'academy'
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $progress,
                'message' => 'تم جلب بيانات التقدم بنجاح'
            ]);
        }

        return view('quran.progress.show', compact('progress'));
    }

    /**
     * Get student's progress summary
     */
    public function studentSummary(User $student): JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $progressRecords = QuranProgress::where('academy_id', $academy->id)
            ->where('student_id', $student->id)
            ->orderBy('progress_date', 'desc')
            ->limit(50)
            ->get();

        $summary = [
            'total_verses_memorized' => $progressRecords->max('total_verses_memorized') ?? 0,
            'memorization_percentage' => $progressRecords->max('memorization_percentage') ?? 0,
            'current_mastery_level' => $progressRecords->first()?->mastery_level ?? 'beginner',
            'average_recitation_quality' => $progressRecords->whereNotNull('recitation_quality')->avg('recitation_quality'),
            'average_tajweed_accuracy' => $progressRecords->whereNotNull('tajweed_accuracy')->avg('tajweed_accuracy'),
            'recent_progress_trend' => $this->calculateProgressTrend($progressRecords),
            'achievements' => $this->extractAchievements($progressRecords),
            'areas_for_improvement' => $this->extractImprovementAreas($progressRecords),
            'total_study_hours' => $progressRecords->sum('study_hours_this_week'),
            'consistency_score' => $this->calculateConsistencyScore($progressRecords),
            'last_updated' => $progressRecords->first()?->progress_date,
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
            'message' => 'تم جلب ملخص تقدم الطالب بنجاح'
        ]);
    }

    /**
     * Get teacher's students progress overview
     */
    public function teacherOverview(QuranTeacher $teacher): JsonResponse
    {
        $this->ensureTeacherBelongsToAcademy($teacher);
        
        $academy = $this->getCurrentAcademy();
        
        $studentsProgress = QuranProgress::with('student')
            ->where('academy_id', $academy->id)
            ->where('quran_teacher_id', $teacher->id)
            ->whereDate('progress_date', '>=', now()->subDays(30))
            ->get()
            ->groupBy('student_id')
            ->map(function ($studentRecords) {
                $latest = $studentRecords->first();
                return [
                    'student' => $latest->student,
                    'total_verses_memorized' => $studentRecords->max('total_verses_memorized'),
                    'memorization_percentage' => $studentRecords->max('memorization_percentage'),
                    'current_mastery_level' => $latest->mastery_level,
                    'progress_status' => $latest->progress_status,
                    'last_updated' => $latest->progress_date,
                    'trend' => $this->calculateProgressTrend($studentRecords),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $studentsProgress,
            'message' => 'تم جلب نظرة عامة على تقدم الطلاب بنجاح'
        ]);
    }

    /**
     * Get progress analytics for academy
     */
    public function analytics(Request $request): JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $dateFrom = $request->get('date_from', now()->subMonths(3)->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        
        $progressData = QuranProgress::where('academy_id', $academy->id)
            ->whereBetween('progress_date', [$dateFrom, $dateTo])
            ->get();

        $analytics = [
            'total_records' => $progressData->count(),
            'unique_students' => $progressData->unique('student_id')->count(),
            'average_memorization_percentage' => $progressData->avg('memorization_percentage'),
            'average_recitation_quality' => $progressData->whereNotNull('recitation_quality')->avg('recitation_quality'),
            'mastery_level_distribution' => $progressData->groupBy('mastery_level')->map->count(),
            'progress_status_distribution' => $progressData->groupBy('progress_status')->map->count(),
            'monthly_progress_trend' => $this->getMonthlyProgressTrend($progressData),
            'top_performing_students' => $this->getTopPerformingStudents($progressData),
            'students_needing_attention' => $progressData->where('progress_status', 'needs_attention')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'تم جلب تحليلات التقدم بنجاح'
        ]);
    }

    /**
     * Update goals for a student
     */
    public function updateGoals(Request $request, User $student): JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $validated = $request->validate([
            'weekly_goal' => 'required|integer|min:1|max:50',
            'monthly_goal' => 'required|integer|min:1|max:200',
            'target_surah' => 'nullable|integer|min:1|max:114',
            'target_verse' => 'nullable|integer|min:1',
            'target_completion_date' => 'nullable|date|after:today',
        ]);

        try {
            // Create a goal-setting progress record
            $progressData = [
                'academy_id' => $academy->id,
                'student_id' => $student->id,
                'quran_teacher_id' => Auth::user()->quranTeacher?->id ?? $request->teacher_id,
                'progress_code' => $this->generateProgressCode($academy->id),
                'progress_date' => now()->toDateString(),
                'progress_type' => 'milestone',
                'weekly_goal' => $validated['weekly_goal'],
                'monthly_goal' => $validated['monthly_goal'],
                'target_surah' => $validated['target_surah'] ?? null,
                'target_verse' => $validated['target_verse'] ?? null,
                'mastery_level' => 'developing',
                'progress_status' => 'on_track',
                'teacher_notes' => 'تم تحديث أهداف الطالب',
                'created_by' => Auth::id(),
            ];

            QuranProgress::create($progressData);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث أهداف الطالب بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الأهداف'
            ], 500);
        }
    }

    // Private helper methods
    private function getCurrentAcademy(): Academy
    {
        return Auth::user()->academy ?? Academy::where('slug', request()->route('academy'))->firstOrFail();
    }

    private function ensureProgressBelongsToAcademy(QuranProgress $progress): void
    {
        $academy = $this->getCurrentAcademy();
        
        if ($progress->academy_id !== $academy->id) {
            abort(404, 'سجل التقدم غير موجود');
        }
    }

    private function ensureTeacherBelongsToAcademy(QuranTeacher $teacher): void
    {
        $academy = $this->getCurrentAcademy();
        
        if ($teacher->academy_id !== $academy->id) {
            abort(404, 'المعلم غير موجود');
        }
    }

    private function generateProgressCode(int $academyId): string
    {
        $count = QuranProgress::where('academy_id', $academyId)->count() + 1;
        return 'QP-' . $academyId . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    private function calculateTotalVersesMemorized(int $studentId, int $academyId, int $newVerses): int
    {
        $existingTotal = QuranProgress::where('student_id', $studentId)
            ->where('academy_id', $academyId)
            ->max('total_verses_memorized') ?? 0;

        return $existingTotal + $newVerses;
    }

    private function calculateMemorizationPercentage(int $totalVerses): float
    {
        // Total verses in Quran: 6,236
        return min(100, round(($totalVerses / 6236) * 100, 2));
    }

    private function calculateGoalProgress(int $versesThisWeek, int $weeklyGoal): float
    {
        if ($weeklyGoal === 0) return 0;
        return min(100, round(($versesThisWeek / $weeklyGoal) * 100, 2));
    }

    private function updateSubscriptionProgress(int $subscriptionId, array $progressData): void
    {
        $subscription = QuranSubscription::find($subscriptionId);
        if ($subscription) {
            $subscription->update([
                'current_surah' => $progressData['current_surah'] ?? $subscription->current_surah,
                'current_verse' => $progressData['current_verse'] ?? $subscription->current_verse,
                'verses_memorized' => $progressData['total_verses_memorized'],
                'memorization_level' => $progressData['mastery_level'],
                'progress_percentage' => $progressData['memorization_percentage'],
                'last_session_at' => now(),
            ]);
        }
    }

    private function updateCircleProgress(int $circleId, int $studentId, array $progressData): void
    {
        $enrollment = DB::table('quran_circle_students')
            ->where('circle_id', $circleId)
            ->where('student_id', $studentId)
            ->first();

        if ($enrollment) {
            DB::table('quran_circle_students')
                ->where('circle_id', $circleId)
                ->where('student_id', $studentId)
                ->update([
                    'current_level' => $progressData['mastery_level'],
                    'progress_notes' => $progressData['teacher_notes'] ?? $enrollment->progress_notes,
                ]);
        }
    }

    private function calculateProgressTrend($progressRecords): string
    {
        if ($progressRecords->count() < 2) return 'stable';

        $recent = $progressRecords->take(5)->avg('total_verses_memorized');
        $previous = $progressRecords->skip(5)->take(5)->avg('total_verses_memorized');

        if ($recent > $previous * 1.1) return 'improving';
        if ($recent < $previous * 0.9) return 'declining';
        return 'stable';
    }

    private function extractAchievements($progressRecords): array
    {
        return $progressRecords->flatMap(function ($record) {
            return $record->milestones_achieved ?? [];
        })->unique()->values()->toArray();
    }

    private function extractImprovementAreas($progressRecords): array
    {
        return $progressRecords->flatMap(function ($record) {
            return $record->improvement_areas ?? [];
        })->countBy()->sortDesc()->take(3)->keys()->toArray();
    }

    private function calculateConsistencyScore($progressRecords): float
    {
        if ($progressRecords->count() < 4) return 0;

        $dates = $progressRecords->pluck('progress_date')->sort();
        $intervals = [];
        
        for ($i = 1; $i < $dates->count(); $i++) {
            $intervals[] = $dates[$i]->diffInDays($dates[$i-1]);
        }

        $avgInterval = collect($intervals)->avg();
        $stdDev = collect($intervals)->standardDeviation();
        
        // Higher consistency score for more regular intervals
        return max(0, min(10, 10 - ($stdDev / max($avgInterval, 1))));
    }

    private function getMonthlyProgressTrend($progressData): array
    {
        return $progressData->groupBy(function ($item) {
            return $item->progress_date->format('Y-m');
        })->map(function ($monthData) {
            return [
                'month' => $monthData->first()->progress_date->format('Y-m'),
                'average_verses' => $monthData->avg('verses_memorized'),
                'students_count' => $monthData->unique('student_id')->count(),
                'average_quality' => $monthData->whereNotNull('recitation_quality')->avg('recitation_quality'),
            ];
        })->values()->toArray();
    }

    private function getTopPerformingStudents($progressData): array
    {
        return $progressData->groupBy('student_id')
            ->map(function ($studentRecords) {
                $latest = $studentRecords->first();
                return [
                    'student_id' => $latest->student_id,
                    'student_name' => $latest->student->name,
                    'total_verses' => $studentRecords->max('total_verses_memorized'),
                    'memorization_percentage' => $studentRecords->max('memorization_percentage'),
                    'average_quality' => $studentRecords->whereNotNull('recitation_quality')->avg('recitation_quality'),
                ];
            })
            ->sortByDesc('total_verses')
            ->take(10)
            ->values()
            ->toArray();
    }
} 