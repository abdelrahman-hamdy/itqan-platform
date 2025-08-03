<?php

namespace App\Http\Controllers;

use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranCircle;
use App\Models\QuranTeacher;
use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QuranSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('tenant');
    }

    /**
     * Display a listing of Quran sessions
     */
    public function index(Request $request): View|JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $query = QuranSession::with([
                'quranTeacher.user',
                'subscription.student',
                'circle',
                'academy'
            ])
            ->where('academy_id', $academy->id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('session_type')) {
            $query->where('session_type', $request->session_type);
        }

        if ($request->filled('teacher_id')) {
            $query->where('quran_teacher_id', $request->teacher_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('session_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('session_date', '<=', $request->date_to);
        }

        if ($request->filled('today')) {
            $query->whereDate('session_date', today());
        }

        if ($request->filled('upcoming')) {
            $query->where('session_date', '>=', now())
                  ->where('status', 'scheduled');
        }

        $sessions = $query->orderBy('session_date', 'desc')
                         ->orderBy('start_time', 'desc')
                         ->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $sessions,
                'message' => 'قائمة جلسات القرآن تم جلبها بنجاح'
            ]);
        }

        return view('quran.sessions.index', compact('sessions', 'academy'));
    }

    /**
     * Show the form for creating a new session
     */
    public function create(Request $request): View
    {
        $academy = $this->getCurrentAcademy();
        
        $teachers = QuranTeacher::with('user')
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('approval_status', 'approved')
            ->get();

        $subscriptions = null;
        $circles = null;
        $preSelectedData = [];

        // Pre-populate based on context
        if ($request->filled('subscription_id')) {
            $subscription = QuranSubscription::with('student')
                ->where('academy_id', $academy->id)
                ->findOrFail($request->subscription_id);
            
            $preSelectedData = [
                'subscription' => $subscription,
                'session_type' => 'individual',
                'teacher_id' => $subscription->quran_teacher_id,
            ];
        } elseif ($request->filled('circle_id')) {
            $circle = QuranCircle::where('academy_id', $academy->id)
                ->findOrFail($request->circle_id);
            
            $preSelectedData = [
                'circle' => $circle,
                'session_type' => 'group',
                'teacher_id' => $circle->quran_teacher_id,
            ];
        } else {
            // Load all for general creation
            $subscriptions = QuranSubscription::with('student')
                ->where('academy_id', $academy->id)
                ->where('subscription_status', 'active')
                ->get();

            $circles = QuranCircle::where('academy_id', $academy->id)
                ->where('status', 'active')
                ->get();
        }

        return view('quran.sessions.create', compact(
            'academy', 
            'teachers', 
            'subscriptions', 
            'circles', 
            'preSelectedData'
        ));
    }

    /**
     * Store a newly created session
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $validated = $request->validate([
            'quran_teacher_id' => 'required|exists:users,id',
            'session_type' => 'required|in:individual,group',
            'quran_subscription_id' => 'nullable|exists:quran_subscriptions,id',
            'circle_id' => 'nullable|exists:quran_circles,id',
            'session_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'duration_minutes' => 'required|integer|min:30|max:120',
            'session_title' => 'required|string|max:100',
            'session_description' => 'nullable|string|max:500',
            'lesson_objectives' => 'nullable|array',
            'materials_needed' => 'nullable|array',
            'location_type' => 'required|in:online,physical,hybrid',
            'physical_location' => 'nullable|string|max:200',
            'online_platform' => 'nullable|string|max:100',
            'meeting_link' => 'nullable|url',
            'teacher_fee' => 'nullable|numeric|min:0|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Verify teacher belongs to academy
            $teacher = QuranTeacher::where('id', $validated['quran_teacher_id'])
                ->where('academy_id', $academy->id)
                ->firstOrFail();

            // Validate session context
            if ($validated['session_type'] === 'individual') {
                if (!$validated['quran_subscription_id']) {
                    throw new \Exception('يجب اختيار اشتراك للجلسة الفردية');
                }

                $subscription = QuranSubscription::where('id', $validated['quran_subscription_id'])
                    ->where('academy_id', $academy->id)
                    ->where('subscription_status', 'active')
                    ->firstOrFail();

                if ($subscription->sessions_remaining <= 0) {
                    throw new \Exception('لا توجد جلسات متبقية في هذا الاشتراك');
                }
            } else {
                if (!$validated['circle_id']) {
                    throw new \Exception('يجب اختيار دائرة للجلسة الجماعية');
                }

                $circle = QuranCircle::where('id', $validated['circle_id'])
                    ->where('academy_id', $academy->id)
                    ->where('status', 'active')
                    ->firstOrFail();
            }

            // Check for scheduling conflicts
            $conflictingSessions = QuranSession::where('quran_teacher_id', $teacher->id)
                ->whereDate('session_date', $validated['session_date'])
                ->where('status', 'scheduled')
                ->where(function ($q) use ($validated) {
                    $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhere(function ($q2) use ($validated) {
                          $q2->where('start_time', '<=', $validated['start_time'])
                             ->where('end_time', '>=', $validated['end_time']);
                      });
                })->exists();

            if ($conflictingSessions) {
                throw new \Exception('المعلم لديه جلسة أخرى في نفس الوقت المحدد');
            }

            $sessionData = array_merge($validated, [
                'academy_id' => $academy->id,
                'session_code' => $this->generateSessionCode($academy->id),
                'status' => 'scheduled',
                'created_by' => Auth::id(),
            ]);

            $session = QuranSession::create($sessionData);

            // Update related models
            if ($validated['session_type'] === 'individual') {
                $subscription->decrement('sessions_remaining');
                $subscription->increment('sessions_used');
            } else {
                $circle->increment('sessions_completed');
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $session->load(['quranTeacher.user', 'subscription.student', 'circle']),
                    'message' => 'تم جدولة الجلسة بنجاح'
                ], 201);
            }

            return redirect()
                ->route('academies.quran.sessions.show', [$academy->slug, $session->id])
                ->with('success', 'تم جدولة الجلسة بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء جدولة الجلسة: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء جدولة الجلسة: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified session
     */
    public function show(QuranSession $session): View|JsonResponse
    {
        $this->ensureSessionBelongsToAcademy($session);
        
        $session->load([
            'quranTeacher.user',
            'subscription.student',
            'circle.enrollments.student',
            'academy',
            'homework',
            'progress'
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $session,
                'message' => 'تم جلب بيانات الجلسة بنجاح'
            ]);
        }

        return view('quran.sessions.show', compact('session'));
    }

    /**
     * Start a session
     */
    public function start(QuranSession $session): JsonResponse|RedirectResponse
    {
        $this->ensureSessionBelongsToAcademy($session);
        
        try {
            if ($session->status !== 'scheduled') {
                throw new \Exception('لا يمكن بدء هذه الجلسة في حالتها الحالية');
            }

            $session->update([
                'status' => 'in_progress',
                'actual_start_time' => now(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $session->fresh(),
                    'message' => 'تم بدء الجلسة بنجاح'
                ]);
            }

            return back()->with('success', 'تم بدء الجلسة بنجاح');

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
     * Complete a session with evaluation
     */
    public function complete(Request $request, QuranSession $session): JsonResponse|RedirectResponse
    {
        $this->ensureSessionBelongsToAcademy($session);
        
        $validated = $request->validate([
            'lesson_summary' => 'required|string|max:1000',
            'topics_covered' => 'nullable|array',
            'student_performance' => 'nullable|string|max:500',
            'homework_assigned' => 'nullable|string|max:500',
            'next_session_focus' => 'nullable|string|max:500',
            'teacher_notes' => 'nullable|string|max:1000',
            'attendance_notes' => 'nullable|string|max:500',
            'session_rating' => 'nullable|integer|min:1|max:5',
        ]);

        try {
            if (!in_array($session->status, ['scheduled', 'in_progress'])) {
                throw new \Exception('لا يمكن إكمال هذه الجلسة في حالتها الحالية');
            }

            $updateData = array_merge($validated, [
                'status' => 'completed',
                'actual_end_time' => now(),
                'updated_by' => Auth::id(),
            ]);

            $session->update($updateData);

            // Update teacher stats
            $session->quranTeacher->increment('total_sessions');

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $session->fresh(),
                    'message' => 'تم إكمال الجلسة بنجاح'
                ]);
            }

            return back()->with('success', 'تم إكمال الجلسة بنجاح');

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
     * Cancel a session
     */
    public function cancel(Request $request, QuranSession $session): JsonResponse|RedirectResponse
    {
        $this->ensureSessionBelongsToAcademy($session);
        
        $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        try {
            if (!in_array($session->status, ['scheduled', 'in_progress'])) {
                throw new \Exception('لا يمكن إلغاء هذه الجلسة في حالتها الحالية');
            }

            DB::beginTransaction();

            $session->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_at' => now(),
            ]);

            // Restore session count for individual sessions
            if ($session->session_type === 'individual' && $session->subscription) {
                $session->subscription->increment('sessions_remaining');
                $session->subscription->decrement('sessions_used');
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $session->fresh(),
                    'message' => 'تم إلغاء الجلسة'
                ]);
            }

            return back()->with('info', 'تم إلغاء الجلسة');

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
     * Reschedule a session
     */
    public function reschedule(Request $request, QuranSession $session): JsonResponse|RedirectResponse
    {
        $this->ensureSessionBelongsToAcademy($session);
        
        $validated = $request->validate([
            'session_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reschedule_reason' => 'required|string|max:500',
        ]);

        try {
            if ($session->status !== 'scheduled') {
                throw new \Exception('لا يمكن إعادة جدولة هذه الجلسة في حالتها الحالية');
            }

            // Check for conflicts
            $conflictingSessions = QuranSession::where('quran_teacher_id', $session->quran_teacher_id)
                ->where('id', '!=', $session->id)
                ->whereDate('session_date', $validated['session_date'])
                ->where('status', 'scheduled')
                ->where(function ($q) use ($validated) {
                    $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhere(function ($q2) use ($validated) {
                          $q2->where('start_time', '<=', $validated['start_time'])
                             ->where('end_time', '>=', $validated['end_time']);
                      });
                })->exists();

            if ($conflictingSessions) {
                throw new \Exception('المعلم لديه جلسة أخرى في نفس الوقت المحدد');
            }

            $session->update([
                'session_date' => $validated['session_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'reschedule_reason' => $validated['reschedule_reason'],
                'rescheduled_at' => now(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $session->fresh(),
                    'message' => 'تم إعادة جدولة الجلسة بنجاح'
                ]);
            }

            return back()->with('success', 'تم إعادة جدولة الجلسة بنجاح');

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
     * Record student attendance for group sessions
     */
    public function recordAttendance(Request $request, QuranSession $session): JsonResponse
    {
        $this->ensureSessionBelongsToAcademy($session);
        
        $request->validate([
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:users,id',
            'attendance.*.attended' => 'required|boolean',
            'attendance.*.notes' => 'nullable|string|max:200',
        ]);

        try {
            if ($session->session_type !== 'group') {
                throw new \Exception('تسجيل الحضور متاح فقط للجلسات الجماعية');
            }

            if (!in_array($session->status, ['in_progress', 'completed'])) {
                throw new \Exception('لا يمكن تسجيل الحضور لهذه الجلسة في حالتها الحالية');
            }

            DB::beginTransaction();

            $attendanceData = [];
            foreach ($request->attendance as $record) {
                $attendanceData[$record['student_id']] = [
                    'attended' => $record['attended'],
                    'attendance_notes' => $record['notes'] ?? null,
                ];

                // Update student enrollment attendance count
                if ($record['attended'] && $session->circle) {
                    $session->circle->enrollments()
                        ->where('student_id', $record['student_id'])
                        ->increment('attendance_count');
                }
            }

            $session->update([
                'attendance_recorded' => true,
                'attendance_data' => $attendanceData,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الحضور بنجاح'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get teacher's upcoming sessions
     */
    public function teacherSessions(QuranTeacher $teacher): JsonResponse
    {
        $this->ensureTeacherBelongsToAcademy($teacher);
        
        $sessions = QuranSession::with(['subscription.student', 'circle'])
            ->where('quran_teacher_id', $teacher->id)
            ->where('session_date', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
            'message' => 'تم جلب جلسات المعلم القادمة بنجاح'
        ]);
    }

    /**
     * Get today's sessions
     */
    public function todaySessions(): JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $sessions = QuranSession::with([
                'quranTeacher.user',
                'subscription.student',
                'circle'
            ])
            ->where('academy_id', $academy->id)
            ->whereDate('session_date', today())
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
            'message' => 'تم جلب جلسات اليوم بنجاح'
        ]);
    }

    // Private helper methods
    private function getCurrentAcademy(): Academy
    {
        return Auth::user()->academy ?? Academy::where('slug', request()->route('academy'))->firstOrFail();
    }

    private function ensureSessionBelongsToAcademy(QuranSession $session): void
    {
        $academy = $this->getCurrentAcademy();
        
        if ($session->academy_id !== $academy->id) {
            abort(404, 'الجلسة غير موجودة');
        }
    }

    private function ensureTeacherBelongsToAcademy(QuranTeacher $teacher): void
    {
        $academy = $this->getCurrentAcademy();
        
        if ($teacher->academy_id !== $academy->id) {
            abort(404, 'المعلم غير موجود');
        }
    }

    private function generateSessionCode(int $academyId): string
    {
        $count = QuranSession::where('academy_id', $academyId)->count() + 1;
        return 'QSE-' . $academyId . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
} 