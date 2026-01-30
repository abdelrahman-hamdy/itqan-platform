<?php

namespace App\Http\Controllers;

use App\Enums\CircleEnrollmentStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\SessionStatus;
use App\Http\Requests\CancelQuranCircleRequest;
use App\Http\Requests\EnrollStudentRequest;
use App\Http\Requests\StoreQuranCircleRequest;
use App\Http\Requests\UpdateQuranCircleRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuranCircleController extends Controller
{
    use ApiResponses;

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
        $this->authorize('viewAny', QuranCircle::class);

        $academy = $this->getCurrentAcademy();

        $query = QuranCircle::with(['quranTeacher', 'academy'])
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
            $query->whereColumn('max_students', '>', 'enrolled_students');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('circle_code', 'like', "%{$search}%");
            });
        }

        $circles = $query->orderBy('created_at', 'desc')->paginate(20);

        if ($request->expectsJson()) {
            return $this->success($circles, 'قائمة حلقات القرآن تم جلبها بنجاح');
        }

        return view('quran.circles.index', compact('circles', 'academy'));
    }

    /**
     * Show the form for creating a new circle
     */
    public function create(): View
    {
        $this->authorize('create', QuranCircle::class);

        $academy = $this->getCurrentAcademy();

        $teachers = QuranTeacherProfile::with('user')
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->get();

        return view('quran.circles.create', compact('academy', 'teachers'));
    }

    /**
     * Store a newly created circle
     */
    public function store(StoreQuranCircleRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', QuranCircle::class);

        $academy = $this->getCurrentAcademy();

        $validated = $request->validated();

        try {
            DB::beginTransaction();

            // Verify teacher belongs to academy
            $teacher = QuranTeacherProfile::where('id', $validated['quran_teacher_id'])
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
                return $this->created($circle->load('quranTeacher'), 'تم إنشاء دائرة القرآن بنجاح');
            }

            return redirect()
                ->route('academies.quran.circles.show', [$academy->slug, $circle->id])
                ->with('success', 'تم إنشاء دائرة القرآن بنجاح');

        } catch (\Exception $e) {
            DB::rollback();

            if ($request->expectsJson()) {
                return $this->serverError('حدث خطأ أثناء إنشاء دائرة القرآن: '.$e->getMessage());
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء إنشاء دائرة القرآن: '.$e->getMessage()]);
        }
    }

    /**
     * Display the specified circle
     */
    public function show(QuranCircle $circle): View|JsonResponse
    {
        $this->authorize('view', $circle);

        $circle->load([
            'quranTeacher',
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
            },
        ]);

        // Calculate circle statistics
        $stats = [
            'enrolled_students' => $circle->enrollments()->where('status', EnrollmentStatus::ENROLLED->value)->count(),
            'completed_students' => $circle->enrollments()->where('status', SessionStatus::COMPLETED->value)->count(),
            'dropped_students' => $circle->enrollments()->where('status', 'dropped')->count(),
            'sessions_completed' => $circle->quranSessions()->completed()->count(),
            'sessions_remaining' => max(0, $circle->total_sessions - $circle->sessions_completed),
            'average_attendance' => $this->calculateAverageAttendance($circle),
            'completion_rate' => $circle->completion_rate,
            'average_rating' => $circle->average_rating,
            'certificates_issued' => $circle->enrollments()->where('certificate_issued', true)->count(),
        ];

        if (request()->expectsJson()) {
            return $this->success([
                'success' => true,
                'data' => $circle,
                'stats' => $stats,
                'message' => 'تم جلب بيانات دائرة القرآن بنجاح',
            ]);
        }

        return view('quran.circles.show', compact('circle', 'stats'));
    }

    /**
     * Show the form for editing the circle
     */
    public function edit(QuranCircle $circle): View
    {
        $this->authorize('update', $circle);

        $academy = $this->getCurrentAcademy();

        $teachers = QuranTeacherProfile::with('user')
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->get();

        $circle->load('quranTeacher');

        return view('quran.circles.edit', compact('circle', 'teachers', 'academy'));
    }

    /**
     * Update the specified circle
     */
    public function update(UpdateQuranCircleRequest $request, QuranCircle $circle): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $circle);

        $validated = $request->validated();

        try {
            $validated['updated_by'] = Auth::id();

            $circle->update($validated);

            if ($request->expectsJson()) {
                return $this->success($circle->fresh(['quranTeacher']), 'تم تحديث دائرة القرآن بنجاح');
            }

            return redirect()
                ->route('academies.quran.circles.show', [$circle->academy->slug, $circle->id])
                ->with('success', 'تم تحديث دائرة القرآن بنجاح');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return $this->serverError('حدث خطأ أثناء تحديث دائرة القرآن');
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
        $this->authorize('publish', $circle);

        try {
            if ($circle->status !== 'planning') {
                throw new \Exception('لا يمكن تفعيل هذه الدائرة في حالتها الحالية');
            }

            $circle->update([
                'status' => 'pending',
                'enrollment_status' => 'open',
            ]);

            if (request()->expectsJson()) {
                return $this->success($circle->fresh(), 'تم نشر دائرة القرآن للتسجيل');
            }

            return back()->with('success', 'تم نشر دائرة القرآن للتسجيل');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return $this->error($e->getMessage(), 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Start a circle (activate)
     */
    public function start(QuranCircle $circle): JsonResponse|RedirectResponse
    {
        $this->authorize('start', $circle);

        try {
            if (! in_array($circle->status, ['pending', 'planning'])) {
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
                return $this->success($circle->fresh(), 'تم بدء دائرة القرآن بنجاح');
            }

            return back()->with('success', 'تم بدء دائرة القرآن بنجاح');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return $this->error($e->getMessage(), 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Complete a circle
     */
    public function complete(QuranCircle $circle): JsonResponse|RedirectResponse
    {
        $this->authorize('complete', $circle);

        try {
            if ($circle->status !== 'active') {
                throw new \Exception('لا يمكن إكمال هذه الدائرة في حالتها الحالية');
            }

            DB::beginTransaction();

            $circle->update([
                'status' => SessionStatus::COMPLETED,
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
                    'status' => SessionStatus::COMPLETED,
                    'completion_date' => now(),
                    'certificate_issued' => true,
                ]);
            }

            DB::commit();

            if (request()->expectsJson()) {
                return $this->success($circle->fresh(), 'تم إكمال دائرة القرآن وإصدار الشهادات');
            }

            return back()->with('success', 'تم إكمال دائرة القرآن وإصدار الشهادات');

        } catch (\Exception $e) {
            DB::rollback();

            if (request()->expectsJson()) {
                return $this->error($e->getMessage(), 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel a circle
     */
    public function cancel(CancelQuranCircleRequest $request, QuranCircle $circle): JsonResponse|RedirectResponse
    {
        $this->authorize('cancel', $circle);

        try {
            if (in_array($circle->status, ['completed', 'cancelled'])) {
                throw new \Exception('الدائرة مكتملة أو ملغية بالفعل');
            }

            DB::beginTransaction();

            $circle->update([
                'status' => SessionStatus::CANCELLED,
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
                ->where('status', SessionStatus::SCHEDULED->value)
                ->update(['status' => SessionStatus::CANCELLED]);

            DB::commit();

            if ($request->expectsJson()) {
                return $this->success($circle->fresh(), 'تم إلغاء دائرة القرآن');
            }

            return back()->with('info', 'تم إلغاء دائرة القرآن');

        } catch (\Exception $e) {
            DB::rollback();

            if ($request->expectsJson()) {
                return $this->error($e->getMessage(), 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Enroll a student in a circle
     */
    public function enroll(EnrollStudentRequest $request, QuranCircle $circle): JsonResponse|RedirectResponse
    {
        $this->authorize('enroll', $circle);

        try {
            if ($circle->enrollment_status !== CircleEnrollmentStatus::OPEN) {
                throw new \Exception('التسجيل مغلق لهذه الدائرة');
            }

            if ($circle->enrolled_students >= $circle->max_students) {
                throw new \Exception('الدائرة ممتلئة');
            }

            $student = User::findOrFail($request->student_id);

            // Authorize enrollment of this specific student
            $this->authorize('enrollStudent', [$circle, $student]);

            // Check if student already enrolled
            if ($circle->enrollments()->where('student_id', $student->id)->exists()) {
                throw new \Exception('الطالب مسجل بالفعل في هذه الدائرة');
            }

            DB::beginTransaction();

            // Create enrollment
            $circle->enrollments()->create([
                'student_id' => $student->id,
                'enrolled_at' => now(),
                'status' => EnrollmentStatus::ENROLLED->value,
                'current_level' => 'beginner',
            ]);

            // Update circle stats
            $circle->increment('enrolled_students');

            DB::commit();

            if ($request->expectsJson()) {
                return $this->success(null, 'تم تسجيل الطالب في الدائرة بنجاح');
            }

            return back()->with('success', 'تم تسجيل الطالب في الدائرة بنجاح');

        } catch (\Exception $e) {
            DB::rollback();

            if ($request->expectsJson()) {
                return $this->error($e->getMessage(), 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get available circles for enrollment
     */
    public function available(Request $request): JsonResponse
    {
        $this->authorize('viewAvailable', QuranCircle::class);

        $academy = $this->getCurrentAcademy();

        $query = QuranCircle::with('quranTeacher')
            ->where('academy_id', $academy->id)
            ->where('status', EnrollmentStatus::PENDING->value)
            ->where('enrollment_status', 'open')
            ->whereColumn('enrolled_students', '<', 'max_students');

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

        return $this->success($circles, 'تم جلب الحلقات المتاحة للتسجيل بنجاح');
    }

    // Private helper methods
    private function getCurrentAcademy(): Academy
    {
        return Auth::user()->academy ?? Academy::where('slug', request()->route('academy'))->firstOrFail();
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
