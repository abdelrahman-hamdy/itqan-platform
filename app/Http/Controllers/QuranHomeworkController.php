<?php

namespace App\Http\Controllers;

use App\Models\QuranHomework;
use App\Models\QuranTeacher;
use App\Models\QuranSubscription;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuranHomeworkController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('tenant');
    }

    /**
     * Display a listing of homework assignments
     */
    public function index(Request $request): View|JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $query = QuranHomework::with([
                'quranTeacher.user',
                'student',
                'subscription',
                'circle',
                'session',
                'academy'
            ])
            ->where('academy_id', $academy->id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('homework_type')) {
            $query->where('homework_type', $request->homework_type);
        }

        if ($request->filled('teacher_id')) {
            $query->where('quran_teacher_id', $request->teacher_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('due_today')) {
            $query->dueToday();
        }

        if ($request->filled('overdue')) {
            $query->overdue();
        }

        if ($request->filled('needs_evaluation')) {
            $query->needsEvaluation();
        }

        $homework = $query->orderBy('due_date', 'asc')
                         ->orderBy('priority', 'desc')
                         ->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $homework,
                'message' => 'قائمة الواجبات تم جلبها بنجاح'
            ]);
        }

        return view('quran.homework.index', compact('homework', 'academy'));
    }

    /**
     * Show the form for creating a new homework assignment
     */
    public function create(Request $request): View
    {
        $academy = $this->getCurrentAcademy();
        
        $teachers = QuranTeacher::with('user')
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('approval_status', 'approved')
            ->get();

        $students = User::role('student')
            ->whereHas('academies', function ($q) use ($academy) {
                $q->where('academies.id', $academy->id);
            })
            ->get();

        $subscriptions = QuranSubscription::with('student')
            ->where('academy_id', $academy->id)
            ->where('subscription_status', 'active')
            ->get();

        $circles = QuranCircle::where('academy_id', $academy->id)
            ->where('status', 'active')
            ->get();

        $sessions = QuranSession::with(['subscription.student', 'circle'])
            ->where('academy_id', $academy->id)
            ->where('session_date', '>=', now()->subDays(7))
            ->get();

        $preSelectedData = [];
        if ($request->filled('session_id')) {
            $session = $sessions->find($request->session_id);
            if ($session) {
                $preSelectedData = [
                    'session' => $session,
                    'teacher_id' => $session->quran_teacher_id,
                    'subscription_id' => $session->quran_subscription_id,
                    'circle_id' => $session->circle_id,
                ];
            }
        }

        return view('quran.homework.create', compact(
            'academy',
            'teachers', 
            'students',
            'subscriptions',
            'circles',
            'sessions',
            'preSelectedData'
        ));
    }

    /**
     * Store a newly created homework assignment
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $validated = $request->validate([
            'quran_teacher_id' => 'required|exists:quran_teachers,id',
            'student_id' => 'required|exists:users,id',
            'subscription_id' => 'nullable|exists:quran_subscriptions,id',
            'circle_id' => 'nullable|exists:quran_circles,id',
            'session_id' => 'nullable|exists:quran_sessions,id',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'homework_type' => 'required|in:memorization,recitation,review,research,writing,listening,practice',
            'priority' => 'required|in:low,medium,high,urgent',
            'difficulty_level' => 'required|in:very_easy,easy,medium,hard,very_hard',
            'estimated_duration_minutes' => 'required|integer|min:15|max:480',
            'instructions' => 'nullable|string|max:1000',
            'requirements' => 'nullable|array',
            'learning_objectives' => 'nullable|array',
            'surah_assignment' => 'nullable|integer|min:1|max:114',
            'verse_from' => 'nullable|integer|min:1',
            'verse_to' => 'nullable|integer|min:1|gte:verse_from',
            'memorization_required' => 'boolean',
            'recitation_required' => 'boolean',
            'tajweed_focus_areas' => 'nullable|array',
            'pronunciation_notes' => 'nullable|string|max:500',
            'repetition_count_required' => 'nullable|integer|min:1|max:100',
            'audio_submission_required' => 'boolean',
            'video_submission_required' => 'boolean',
            'written_submission_required' => 'boolean',
            'practice_materials' => 'nullable|array',
            'reference_materials' => 'nullable|array',
            'due_date' => 'required|date|after:now',
            'submission_method' => 'required|in:audio,video,text,file,live,mixed',
            'evaluation_criteria' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Verify teacher belongs to academy
            $teacher = QuranTeacher::where('id', $validated['quran_teacher_id'])
                ->where('academy_id', $academy->id)
                ->firstOrFail();

            // Verify student has access to academy
            $student = User::findOrFail($validated['student_id']);
            if (!$student->academies()->where('academies.id', $academy->id)->exists()) {
                throw new \Exception('الطالب غير مسجل في هذه الأكاديمية');
            }

            // Calculate total verses if verse range provided
            $totalVerses = 0;
            if ($validated['verse_from'] && $validated['verse_to']) {
                $totalVerses = $validated['verse_to'] - $validated['verse_from'] + 1;
            }

            $homeworkData = array_merge($validated, [
                'academy_id' => $academy->id,
                'homework_code' => $this->generateHomeworkCode($academy->id),
                'total_verses' => $totalVerses,
                'assigned_at' => now(),
                'status' => 'assigned',
                'submission_status' => 'not_submitted',
                'attempts_count' => 0,
                'completion_percentage' => 0,
                'time_spent_minutes' => 0,
                'parent_reviewed' => false,
                'extension_requested' => false,
                'extension_granted' => false,
                'late_submission' => false,
                'late_penalty_applied' => false,
                'follow_up_required' => false,
                'created_by' => Auth::id(),
            ]);

            $homework = QuranHomework::create($homeworkData);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $homework->load(['student', 'quranTeacher.user']),
                    'message' => 'تم إنشاء الواجب بنجاح'
                ], 201);
            }

            return redirect()
                ->route('academies.quran.homework.show', [$academy->slug, $homework->id])
                ->with('success', 'تم إنشاء الواجب بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إنشاء الواجب: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء إنشاء الواجب: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified homework
     */
    public function show(QuranHomework $homework): View|JsonResponse
    {
        $this->ensureHomeworkBelongsToAcademy($homework);
        
        $homework->load([
            'quranTeacher.user',
            'student',
            'subscription',
            'circle',
            'session',
            'academy'
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $homework,
                'message' => 'تم جلب بيانات الواجب بنجاح'
            ]);
        }

        return view('quran.homework.show', compact('homework'));
    }

    /**
     * Show the form for editing homework
     */
    public function edit(QuranHomework $homework): View
    {
        $this->ensureHomeworkBelongsToAcademy($homework);
        
        if ($homework->status !== 'assigned') {
            abort(403, 'لا يمكن تعديل هذا الواجب في حالته الحالية');
        }

        $academy = $this->getCurrentAcademy();
        
        $teachers = QuranTeacher::with('user')
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('approval_status', 'approved')
            ->get();

        $homework->load(['student', 'quranTeacher.user']);
        
        return view('quran.homework.edit', compact('homework', 'teachers', 'academy'));
    }

    /**
     * Update the specified homework
     */
    public function update(Request $request, QuranHomework $homework): RedirectResponse|JsonResponse
    {
        $this->ensureHomeworkBelongsToAcademy($homework);
        
        if ($homework->status !== 'assigned') {
            throw new \Exception('لا يمكن تعديل هذا الواجب في حالته الحالية');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'homework_type' => 'required|in:memorization,recitation,review,research,writing,listening,practice',
            'priority' => 'required|in:low,medium,high,urgent',
            'difficulty_level' => 'required|in:very_easy,easy,medium,hard,very_hard',
            'estimated_duration_minutes' => 'required|integer|min:15|max:480',
            'instructions' => 'nullable|string|max:1000',
            'requirements' => 'nullable|array',
            'learning_objectives' => 'nullable|array',
            'surah_assignment' => 'nullable|integer|min:1|max:114',
            'verse_from' => 'nullable|integer|min:1',
            'verse_to' => 'nullable|integer|min:1|gte:verse_from',
            'memorization_required' => 'boolean',
            'recitation_required' => 'boolean',
            'tajweed_focus_areas' => 'nullable|array',
            'pronunciation_notes' => 'nullable|string|max:500',
            'repetition_count_required' => 'nullable|integer|min:1|max:100',
            'audio_submission_required' => 'boolean',
            'video_submission_required' => 'boolean',
            'written_submission_required' => 'boolean',
            'practice_materials' => 'nullable|array',
            'reference_materials' => 'nullable|array',
            'due_date' => 'required|date|after:now',
            'submission_method' => 'required|in:audio,video,text,file,live,mixed',
            'evaluation_criteria' => 'nullable|array',
        ]);

        try {
            // Recalculate total verses if verse range changed
            $totalVerses = 0;
            if ($validated['verse_from'] && $validated['verse_to']) {
                $totalVerses = $validated['verse_to'] - $validated['verse_from'] + 1;
            }

            $validated['total_verses'] = $totalVerses;
            $validated['updated_by'] = Auth::id();
            
            $homework->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $homework->fresh(['student', 'quranTeacher.user']),
                    'message' => 'تم تحديث الواجب بنجاح'
                ]);
            }

            return redirect()
                ->route('academies.quran.homework.show', [$homework->academy->slug, $homework->id])
                ->with('success', 'تم تحديث الواجب بنجاح');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث الواجب'
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء تحديث الواجب']);
        }
    }

    /**
     * Submit homework by student
     */
    public function submit(Request $request, QuranHomework $homework): JsonResponse|RedirectResponse
    {
        $this->ensureHomeworkBelongsToAcademy($homework);
        
        if (!$homework->can_submit) {
            throw new \Exception('لا يمكن تسليم هذا الواجب في الحالة الحالية');
        }

        $validated = $request->validate([
            'submission_text' => 'nullable|string|max:2000',
            'submission_notes' => 'nullable|string|max:1000',
            'audio_recording' => 'nullable|file|mimes:mp3,wav,m4a|max:50000',
            'video_recording' => 'nullable|file|mimes:mp4,mov,avi|max:100000',
            'submission_files.*' => 'nullable|file|max:10000',
            'time_spent_minutes' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            $submissionData = [
                'submission_text' => $validated['submission_text'] ?? null,
                'submission_notes' => $validated['submission_notes'] ?? null,
                'time_spent_minutes' => $validated['time_spent_minutes'] ?? 0,
            ];

            // Handle file uploads
            if ($request->hasFile('audio_recording')) {
                $audioPath = $request->file('audio_recording')->store(
                    "homework/{$homework->academy_id}/audio",
                    'public'
                );
                $submissionData['audio_recording_url'] = $audioPath;
            }

            if ($request->hasFile('video_recording')) {
                $videoPath = $request->file('video_recording')->store(
                    "homework/{$homework->academy_id}/video",
                    'public'
                );
                $submissionData['video_recording_url'] = $videoPath;
            }

            if ($request->hasFile('submission_files')) {
                $filePaths = [];
                foreach ($request->file('submission_files') as $file) {
                    $filePaths[] = $file->store(
                        "homework/{$homework->academy_id}/files",
                        'public'
                    );
                }
                $submissionData['submission_files'] = $filePaths;
            }

            $homework->submit($submissionData);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $homework->fresh(),
                    'message' => 'تم تسليم الواجب بنجاح'
                ]);
            }

            return back()->with('success', 'تم تسليم الواجب بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تسليم الواجب: ' . $e->getMessage()
                ], 422);
            }

            return back()->withErrors(['error' => 'حدث خطأ أثناء تسليم الواجب: ' . $e->getMessage()]);
        }
    }

    /**
     * Evaluate submitted homework
     */
    public function evaluate(Request $request, QuranHomework $homework): JsonResponse|RedirectResponse
    {
        $this->ensureHomeworkBelongsToAcademy($homework);
        
        if (!$homework->can_evaluate) {
            throw new \Exception('لا يمكن تقييم هذا الواجب في الحالة الحالية');
        }

        $validated = $request->validate([
            'quality_score' => 'required|numeric|min:0|max:10',
            'accuracy_score' => 'required|numeric|min:0|max:10',
            'effort_score' => 'required|numeric|min:0|max:10',
            'teacher_feedback' => 'required|string|max:1000',
            'improvement_areas' => 'nullable|array',
            'strengths_noted' => 'nullable|array',
            'next_steps' => 'nullable|array',
            'follow_up_required' => 'boolean',
            'follow_up_notes' => 'nullable|string|max:500',
            'bonus_points' => 'nullable|numeric|min:0|max:2',
        ]);

        try {
            $homework->evaluate($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $homework->fresh(),
                    'message' => 'تم تقييم الواجب بنجاح'
                ]);
            }

            return back()->with('success', 'تم تقييم الواجب بنجاح');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Request extension for homework
     */
    public function requestExtension(Request $request, QuranHomework $homework): JsonResponse|RedirectResponse
    {
        $this->ensureHomeworkBelongsToAcademy($homework);
        
        $validated = $request->validate([
            'extension_reason' => 'required|string|max:500',
            'requested_due_date' => 'required|date|after:due_date',
        ]);

        try {
            if ($homework->extension_requested) {
                throw new \Exception('تم طلب التمديد مسبقاً لهذا الواجب');
            }

            $homework->requestExtension(
                $validated['extension_reason'],
                $validated['requested_due_date']
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $homework->fresh(),
                    'message' => 'تم طلب تمديد الموعد النهائي'
                ]);
            }

            return back()->with('info', 'تم طلب تمديد الموعد النهائي');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Grant extension for homework
     */
    public function grantExtension(Request $request, QuranHomework $homework): JsonResponse|RedirectResponse
    {
        $this->ensureHomeworkBelongsToAcademy($homework);
        
        $validated = $request->validate([
            'new_due_date' => 'required|date|after:due_date',
            'approval_note' => 'nullable|string|max:300',
        ]);

        try {
            if (!$homework->extension_requested) {
                throw new \Exception('لم يتم طلب تمديد لهذا الواجب');
            }

            $homework->grantExtension(
                $validated['new_due_date'],
                $validated['approval_note'] ?? null
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $homework->fresh(),
                    'message' => 'تم منح التمديد بنجاح'
                ]);
            }

            return back()->with('success', 'تم منح التمديد بنجاح');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get student's homework list
     */
    public function studentHomework(User $student, Request $request): JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $filters = [
            'status' => $request->get('status'),
            'homework_type' => $request->get('homework_type'),
            'priority' => $request->get('priority'),
        ];

        $homework = QuranHomework::getStudentHomework(
            $student->id,
            $academy->id,
            array_filter($filters)
        );

        return response()->json([
            'success' => true,
            'data' => $homework,
            'message' => 'تم جلب واجبات الطالب بنجاح'
        ]);
    }

    /**
     * Get teacher's homework overview
     */
    public function teacherHomework(QuranTeacher $teacher, Request $request): JsonResponse
    {
        $this->ensureTeacherBelongsToAcademy($teacher);
        
        $academy = $this->getCurrentAcademy();
        
        $filters = [
            'status' => $request->get('status'),
            'needs_evaluation' => $request->get('needs_evaluation'),
            'overdue' => $request->get('overdue'),
        ];

        $homework = QuranHomework::getTeacherHomework(
            $teacher->id,
            $academy->id,
            array_filter($filters)
        );

        return response()->json([
            'success' => true,
            'data' => $homework,
            'message' => 'تم جلب واجبات المعلم بنجاح'
        ]);
    }

    /**
     * Get overdue homework for academy
     */
    public function overdueHomework(): JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $homework = QuranHomework::getOverdueHomework($academy->id);

        return response()->json([
            'success' => true,
            'data' => $homework,
            'message' => 'تم جلب الواجبات المتأخرة بنجاح'
        ]);
    }

    /**
     * Get upcoming deadlines
     */
    public function upcomingDeadlines(Request $request): JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        $days = $request->get('days', 3);
        
        $homework = QuranHomework::getUpcomingDeadlines($academy->id, $days);

        return response()->json([
            'success' => true,
            'data' => $homework,
            'message' => 'تم جلب المواعيد النهائية القريبة بنجاح'
        ]);
    }

    // Private helper methods
    private function getCurrentAcademy(): Academy
    {
        return Auth::user()->academy ?? Academy::where('slug', request()->route('academy'))->firstOrFail();
    }

    private function ensureHomeworkBelongsToAcademy(QuranHomework $homework): void
    {
        $academy = $this->getCurrentAcademy();
        
        if ($homework->academy_id !== $academy->id) {
            abort(404, 'الواجب غير موجود');
        }
    }

    private function ensureTeacherBelongsToAcademy(QuranTeacher $teacher): void
    {
        $academy = $this->getCurrentAcademy();
        
        if ($teacher->academy_id !== $academy->id) {
            abort(404, 'المعلم غير موجود');
        }
    }

    private function generateHomeworkCode(int $academyId): string
    {
        $count = QuranHomework::where('academy_id', $academyId)->count() + 1;
        return 'QH-' . $academyId . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
} 