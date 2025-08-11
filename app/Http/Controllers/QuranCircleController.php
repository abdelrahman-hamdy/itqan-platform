<?php

namespace App\Http\Controllers;

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
use Carbon\Carbon;

class QuranCircleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('tenant');
    }

    /**
     * Display a listing of Quran circles
     */
    public function index(Request $request): View|JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $query = QuranCircle::with(['quranTeacher.user', 'academy'])
            ->withCount('enrollments')
            ->where('academy_id', $academy->id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('teacher_id')) {
            $query->where('quran_teacher_id', $request->teacher_id);
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        if ($request->filled('available_spots')) {
            $query->whereRaw('max_students > enrolled_students');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('circle_code', 'like', "%{$search}%");
            });
        }

        $circles = $query->orderBy('created_at', 'desc')->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $circles,
                'message' => 'قائمة حلقات القرآن تم جلبها بنجاح'
            ]);
        }

        return view('quran.circles.index', compact('circles', 'academy'));
    }

    /**
     * Show the form for creating a new circle
     */
    public function create(): View
    {
        $academy = $this->getCurrentAcademy();
        
        $teachers = QuranTeacher::with('user')
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('approval_status', 'approved')
            ->get();

        return view('quran.circles.create', compact('academy', 'teachers'));
    }

    /**
     * Store a newly created circle
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $validated = $request->validate([
            'quran_teacher_id' => 'required|exists:users,id',
            'name_ar' => 'required|string|max:100',
            'name_en' => 'nullable|string|max:100',
            'description_ar' => 'nullable|string|max:500',
            'description_en' => 'nullable|string|max:500',
            'level' => 'required|in:beginner,elementary,intermediate,advanced,expert',
            'target_age_group' => 'required|in:children,teenagers,adults,seniors,mixed',
            'min_age' => 'required|integer|min:5|max:80',
            'max_age' => 'required|integer|min:5|max:80|gte:min_age',
            'max_students' => 'required|integer|min:3|max:100',
            'price_per_student' => 'required|numeric|min:0|max:300',
            'billing_cycle' => 'required|in:weekly,monthly,quarterly,yearly',
            'day_of_week' => 'required|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'duration_minutes' => 'required|integer|in:30,60',
            'circle_type' => 'required|in:memorization,recitation,interpretation,general',
            'curriculum_focus' => 'nullable|array',
            'learning_objectives' => 'nullable|array',
            'prerequisites' => 'nullable|string|max:500',
            'enrollment_start_date' => 'required|date|after_or_equal:today',
            'enrollment_end_date' => 'required|date|after:enrollment_start_date',
            'circle_start_date' => 'required|date|after:enrollment_end_date',
            'circle_end_date' => 'nullable|date|after:circle_start_date',
            'total_sessions' => 'required|integer|min:4|max:52',
            'location_type' => 'required|in:online,physical,hybrid',
            'physical_location' => 'nullable|string|max:200',
            'online_platform' => 'nullable|string|max:100',
            'meeting_link' => 'nullable|url',
            'materials_required' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Verify teacher belongs to academy
            $teacher = QuranTeacher::where('id', $validated['quran_teacher_id'])
                ->where('academy_id', $academy->id)
                ->firstOrFail();

            // Check teacher availability on selected day/time
            $conflictingCircles = QuranCircle::where('quran_teacher_id', $teacher->id)
                ->where('day_of_week', $validated['day_of_week'])
                ->where('status', 'active')
                ->where(function ($q) use ($validated) {
                    $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhere(function ($q2) use ($validated) {
                          $q2->where('start_time', '<=', $validated['start_time'])
                             ->where('end_time', '>=', $validated['end_time']);
                      });
                })->exists();

            if ($conflictingCircles) {
                throw new \Exception('المعلم لديه دائرة أخرى في نفس الوقت المحدد');
            }

            $circleData = array_merge($validated, [
                'academy_id' => $academy->id,
                'circle_code' => QuranCircle::generateCircleCode($academy->id),
                'enrolled_students' => 0,
                'sessions_completed' => 0,
                'currency' => $teacher->currency,
                'status' => 'planning',
                'enrollment_status' => 'closed',
                'completion_rate' => 0,
                'average_rating' => 0,
                'total_reviews' => 0,
                'created_by' => Auth::id(),
            ]);

            $circle = QuranCircle::create($circleData);

            // Update teacher stats
            $teacher->increment('total_circles');

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $circle->load('quranTeacher.user'),
                    'message' => 'تم إنشاء دائرة القرآن بنجاح'
                ], 201);
            }

            return redirect()
                ->route('academies.quran.circles.show', [$academy->slug, $circle->id])
                ->with('success', 'تم إنشاء دائرة القرآن بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إنشاء دائرة القرآن: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء إنشاء دائرة القرآن: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified circle
     */
    public function show(QuranCircle $circle): View|JsonResponse
    {
        $this->ensureCircleBelongsToAcademy($circle);
        
        $circle->load([
            'quranTeacher.user',
            'academy',
            'enrollments.student',
            'quranSessions' => function ($q) {
                $q->latest()->limit(10);
            },
            'progress' => function ($q) {
                $q->latest()->limit(5);
            },
            'homework' => function ($q) {
                $q->latest()->limit(5);
            }
        ]);

        // Calculate circle statistics
        $stats = [
            'enrolled_students' => $circle->enrollments()->where('status', 'enrolled')->count(),
            'completed_students' => $circle->enrollments()->where('status', 'completed')->count(),
            'dropped_students' => $circle->enrollments()->where('status', 'dropped')->count(),
            'sessions_completed' => $circle->quranSessions()->completed()->count(),
            'sessions_remaining' => max(0, $circle->total_sessions - $circle->sessions_completed),
            'average_attendance' => $this->calculateAverageAttendance($circle),
            'completion_rate' => $circle->completion_rate,
            'average_rating' => $circle->average_rating,
            'certificates_issued' => $circle->enrollments()->where('certificate_issued', true)->count(),
        ];

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $circle,
                'stats' => $stats,
                'message' => 'تم جلب بيانات دائرة القرآن بنجاح'
            ]);
        }

        return view('quran.circles.show', compact('circle', 'stats'));
    }

    /**
     * Show the form for editing the circle
     */
    public function edit(QuranCircle $circle): View
    {
        $this->ensureCircleBelongsToAcademy($circle);
        
        $academy = $this->getCurrentAcademy();
        
        $teachers = QuranTeacher::with('user')
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('approval_status', 'approved')
            ->get();

        $circle->load('quranTeacher.user');
        
        return view('quran.circles.edit', compact('circle', 'teachers', 'academy'));
    }

    /**
     * Update the specified circle
     */
    public function update(Request $request, QuranCircle $circle): RedirectResponse|JsonResponse
    {
        $this->ensureCircleBelongsToAcademy($circle);
        
        $validated = $request->validate([
            'name_ar' => 'required|string|max:100',
            'name_en' => 'nullable|string|max:100',
            'description_ar' => 'nullable|string|max:500',
            'description_en' => 'nullable|string|max:500',
            'level' => 'required|in:beginner,elementary,intermediate,advanced,expert',
            'target_age_group' => 'required|in:children,teenagers,adults,seniors,mixed',
            'min_age' => 'required|integer|min:5|max:80',
            'max_age' => 'required|integer|min:5|max:80|gte:min_age',
            'max_students' => 'required|integer|min:3|max:100',
            'price_per_student' => 'required|numeric|min:0|max:300',
            'billing_cycle' => 'required|in:weekly,monthly,quarterly,yearly',
            'day_of_week' => 'required|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'duration_minutes' => 'required|integer|in:30,60',
            'curriculum_focus' => 'nullable|array',
            'learning_objectives' => 'nullable|array',
            'prerequisites' => 'nullable|string|max:500',
            'enrollment_end_date' => 'nullable|date',
            'circle_end_date' => 'nullable|date',
            'location_type' => 'required|in:online,physical,hybrid',
            'physical_location' => 'nullable|string|max:200',
            'online_platform' => 'nullable|string|max:100',
            'meeting_link' => 'nullable|url',
            'materials_required' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:planning,inactive,pending,active,ongoing,completed,cancelled,suspended',
        ]);

        try {
            $validated['updated_by'] = Auth::id();
            
            $circle->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $circle->fresh(['quranTeacher.user']),
                    'message' => 'تم تحديث دائرة القرآن بنجاح'
                ]);
            }

            return redirect()
                ->route('academies.quran.circles.show', [$circle->academy->slug, $circle->id])
                ->with('success', 'تم تحديث دائرة القرآن بنجاح');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث دائرة القرآن'
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء تحديث دائرة القرآن']);
        }
    }

    /**
     * Publish a circle for enrollment
     */
    public function publish(QuranCircle $circle): JsonResponse|RedirectResponse
    {
        $this->ensureCircleBelongsToAcademy($circle);
        
        try {
            if ($circle->status !== 'planning') {
                throw new \Exception('لا يمكن تفعيل هذه الدائرة في حالتها الحالية');
            }

            $circle->update([
                'status' => 'pending',
                'enrollment_status' => 'open',
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $circle->fresh(),
                    'message' => 'تم نشر دائرة القرآن للتسجيل'
                ]);
            }

            return back()->with('success', 'تم نشر دائرة القرآن للتسجيل');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Start a circle (activate)
     */
    public function start(QuranCircle $circle): JsonResponse|RedirectResponse
    {
        $this->ensureCircleBelongsToAcademy($circle);
        
        try {
            if (!in_array($circle->status, ['pending', 'planning'])) {
                throw new \Exception('لا يمكن بدء هذه الدائرة في حالتها الحالية');
            }

            if ($circle->enrolled_students < 3) {
                throw new \Exception('يجب أن يكون لديك على الأقل 3 طلاب مسجلين لبدء الدائرة');
            }

            $circle->update([
                'status' => 'active',
                'enrollment_status' => 'closed',
                'actual_start_date' => now(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $circle->fresh(),
                    'message' => 'تم بدء دائرة القرآن بنجاح'
                ]);
            }

            return back()->with('success', 'تم بدء دائرة القرآن بنجاح');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Complete a circle
     */
    public function complete(QuranCircle $circle): JsonResponse|RedirectResponse
    {
        $this->ensureCircleBelongsToAcademy($circle);
        
        try {
            if ($circle->status !== 'active') {
                throw new \Exception('لا يمكن إكمال هذه الدائرة في حالتها الحالية');
            }

            DB::beginTransaction();

            $circle->update([
                'status' => 'completed',
                'enrollment_status' => 'closed',
                'actual_end_date' => now(),
                'completion_rate' => 100,
            ]);

            // Mark eligible students as completed and issue certificates
            $eligibleStudents = $circle->enrollments()
                ->where('status', 'enrolled')
                ->where('attendance_count', '>=', $circle->sessions_completed * 0.8) // 80% attendance requirement
                ->get();

            foreach ($eligibleStudents as $enrollment) {
                $enrollment->update([
                    'status' => 'completed',
                    'completion_date' => now(),
                    'certificate_issued' => true,
                ]);
            }

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $circle->fresh(),
                    'message' => 'تم إكمال دائرة القرآن وإصدار الشهادات'
                ]);
            }

            return back()->with('success', 'تم إكمال دائرة القرآن وإصدار الشهادات');

        } catch (\Exception $e) {
            DB::rollback();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel a circle
     */
    public function cancel(Request $request, QuranCircle $circle): JsonResponse|RedirectResponse
    {
        $this->ensureCircleBelongsToAcademy($circle);
        
        $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        try {
            if (in_array($circle->status, ['completed', 'cancelled'])) {
                throw new \Exception('الدائرة مكتملة أو ملغية بالفعل');
            }

            DB::beginTransaction();

            $circle->update([
                'status' => 'cancelled',
                'enrollment_status' => 'closed',
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_at' => now(),
            ]);

            // Update enrolled students status
            $circle->enrollments()
                ->where('status', 'enrolled')
                ->update(['status' => 'dropped']);

            // Cancel upcoming sessions
            $circle->quranSessions()
                ->where('session_date', '>', now())
                ->where('status', 'scheduled')
                ->update(['status' => 'cancelled']);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $circle->fresh(),
                    'message' => 'تم إلغاء دائرة القرآن'
                ]);
            }

            return back()->with('info', 'تم إلغاء دائرة القرآن');

        } catch (\Exception $e) {
            DB::rollback();
            
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
     * Enroll a student in a circle
     */
    public function enroll(Request $request, QuranCircle $circle): JsonResponse|RedirectResponse
    {
        $this->ensureCircleBelongsToAcademy($circle);
        
        $request->validate([
            'student_id' => 'required|exists:users,id'
        ]);

        try {
            if ($circle->enrollment_status !== 'open') {
                throw new \Exception('التسجيل مغلق لهذه الدائرة');
            }

            if ($circle->enrolled_students >= $circle->max_students) {
                throw new \Exception('الدائرة ممتلئة');
            }

            $student = User::findOrFail($request->student_id);
            
            // Check if student already enrolled
            if ($circle->enrollments()->where('student_id', $student->id)->exists()) {
                throw new \Exception('الطالب مسجل بالفعل في هذه الدائرة');
            }

            DB::beginTransaction();

            // Create enrollment
            $circle->enrollments()->create([
                'student_id' => $student->id,
                'enrolled_at' => now(),
                'status' => 'enrolled',
                'current_level' => 'beginner',
            ]);

            // Update circle stats
            $circle->increment('enrolled_students');

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل الطالب في الدائرة بنجاح'
                ]);
            }

            return back()->with('success', 'تم تسجيل الطالب في الدائرة بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            
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
     * Get available circles for enrollment
     */
    public function available(Request $request): JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $query = QuranCircle::with('quranTeacher.user')
            ->where('academy_id', $academy->id)
            ->where('status', 'pending')
            ->where('enrollment_status', 'open')
            ->whereRaw('enrolled_students < max_students');

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('target_age_group')) {
            $query->where('target_age_group', $request->target_age_group);
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        $circles = $query->get();

        return response()->json([
            'success' => true,
            'data' => $circles,
            'message' => 'تم جلب الحلقات المتاحة للتسجيل بنجاح'
        ]);
    }

    // Private helper methods
    private function getCurrentAcademy(): Academy
    {
        return Auth::user()->academy ?? Academy::where('slug', request()->route('academy'))->firstOrFail();
    }

    private function ensureCircleBelongsToAcademy(QuranCircle $circle): void
    {
        $academy = $this->getCurrentAcademy();
        
        if ($circle->academy_id !== $academy->id) {
            abort(404, 'دائرة القرآن غير موجودة');
        }
    }

    private function calculateAverageAttendance(QuranCircle $circle): float
    {
        $totalSessions = $circle->quranSessions()->completed()->count();
        
        if ($totalSessions === 0) {
            return 0;
        }

        $totalAttendance = $circle->enrollments()
            ->where('status', 'enrolled')
            ->sum('attendance_count');

        $enrolledStudents = $circle->enrollments()
            ->where('status', 'enrolled')
            ->count();

        if ($enrolledStudents === 0) {
            return 0;
        }

        return round(($totalAttendance / ($totalSessions * $enrolledStudents)) * 100, 2);
    }
} 