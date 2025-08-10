<?php

namespace App\Http\Controllers;

use App\Models\QuranTeacher;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class QuranTeacherController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('tenant'); // Ensure proper tenant isolation
    }

    /**
     * Display a listing of Quran teachers
     */
    public function index(Request $request): View|JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $query = QuranTeacher::with(['user', 'academy'])
            ->where('academy_id', $academy->id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('specialization')) {
            $query->where('specialization', $request->specialization);
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sort_by')) {
            $sortBy = $request->sort_by;
            $sortDirection = $request->get('sort_direction', 'asc');
            
            if (in_array($sortBy, ['rating', 'total_sessions', 'total_students', 'created_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $teachers = $query->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $teachers,
                'message' => 'قائمة معلمي القرآن تم جلبها بنجاح'
            ]);
        }

        return view('quran.teachers.index', compact('teachers', 'academy'));
    }

    /**
     * Show the form for creating a new Quran teacher
     */
    public function create(): View
    {
        $academy = $this->getCurrentAcademy();
        
        return view('quran.teachers.create', compact('academy'));
    }

    /**
     * Store a newly created Quran teacher
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $validated = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('quran_teacher_profiles')->where('academy_id', $academy->id)
            ],
            'specialization' => 'required|in:memorization,recitation,interpretation,arabic_language,general',
            'has_ijazah' => 'boolean',
            'ijazah_type' => 'nullable|in:memorization,recitation,ten_readings,teaching,general',
            'ijazah_chain' => 'nullable|string|max:1000',
            'memorization_level' => 'required|in:beginner,elementary,intermediate,advanced,expert',
            'teaching_experience_years' => 'required|integer|min:0|max:50',
            'available_grade_levels' => 'nullable|array',
            'teaching_methods' => 'nullable|array',
            'hourly_rate_individual' => 'required|numeric|min:0|max:1000',
            'hourly_rate_group' => 'required|numeric|min:0|max:1000',
            'currency' => 'required|string|size:3',
            'max_students_per_circle' => 'required|integer|min:3|max:100',
            'preferred_session_duration' => 'required|integer|min:30|max:120',
            'available_days' => 'nullable|array',
            'available_times' => 'nullable|array',
            'bio_ar' => 'nullable|string|max:2000',
            'bio_en' => 'nullable|string|max:2000',
            'certifications' => 'nullable|array',
            'achievements' => 'nullable|array',
            'teaching_philosophy' => 'nullable|string|max:1500',
        ]);

        try {
            DB::beginTransaction();

            $validated['academy_id'] = $academy->id;
            $validated['created_by'] = Auth::id();
            
            $teacher = QuranTeacher::create($validated);

            // Update user role to include Quran teacher
            $user = User::find($validated['user_id']);
            $user->assignRole('quran_teacher');

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $teacher->load('user'),
                    'message' => 'تم إضافة معلم القرآن بنجاح'
                ], 201);
            }

            return redirect()
                ->route('academies.quran.teachers.show', [$academy->slug, $teacher->id])
                ->with('success', 'تم إضافة معلم القرآن بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إضافة معلم القرآن'
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء إضافة معلم القرآن']);
        }
    }

    /**
     * Display the specified Quran teacher
     */
    public function show(QuranTeacher $teacher): View|JsonResponse
    {
        $this->ensureTeacherBelongsToAcademy($teacher);
        
        $teacher->load([
            'user',
            'academy',
            'quranSubscriptions.student',
            'quranCircles',
            'quranSessions' => function ($q) {
                $q->latest()->limit(10);
            },
            'progress' => function ($q) {
                $q->latest()->limit(5);
            }
        ]);

        // Calculate teacher statistics
        $stats = [
            'total_subscriptions' => $teacher->quranSubscriptions()->count(),
            'active_subscriptions' => $teacher->quranSubscriptions()->active()->count(),
            'total_circles' => $teacher->quranCircles()->count(),
            'active_circles' => $teacher->quranCircles()->active()->count(),
            'total_sessions_conducted' => $teacher->quranSessions()->completed()->count(),
            'average_session_rating' => $teacher->quranSessions()->whereNotNull('teacher_rating')->avg('teacher_rating'),
            'students_count' => $teacher->quranSubscriptions()->distinct('student_id')->count() + 
                              $teacher->quranCircles()->withCount('enrollments')->get()->sum('enrollments_count'),
            'completion_rate' => $this->calculateCompletionRate($teacher),
        ];

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $teacher,
                'stats' => $stats,
                'message' => 'تم جلب بيانات معلم القرآن بنجاح'
            ]);
        }

        return view('quran.teachers.show', compact('teacher', 'stats'));
    }

    /**
     * Show the form for editing the Quran teacher
     */
    public function edit(QuranTeacher $teacher): View
    {
        $this->ensureTeacherBelongsToAcademy($teacher);
        
        $teacher->load('user', 'academy');
        
        return view('quran.teachers.edit', compact('teacher'));
    }

    /**
     * Update the specified Quran teacher
     */
    public function update(Request $request, QuranTeacher $teacher): RedirectResponse|JsonResponse
    {
        $this->ensureTeacherBelongsToAcademy($teacher);
        
        $validated = $request->validate([
            'specialization' => 'required|in:memorization,recitation,interpretation,arabic_language,general',
            'has_ijazah' => 'boolean',
            'ijazah_type' => 'nullable|in:memorization,recitation,ten_readings,teaching,general',
            'ijazah_chain' => 'nullable|string|max:1000',
            'memorization_level' => 'required|in:beginner,elementary,intermediate,advanced,expert',
            'teaching_experience_years' => 'required|integer|min:0|max:50',
            'available_grade_levels' => 'nullable|array',
            'teaching_methods' => 'nullable|array',
            'hourly_rate_individual' => 'required|numeric|min:0|max:1000',
            'hourly_rate_group' => 'required|numeric|min:0|max:1000',
            'currency' => 'required|string|size:3',
            'max_students_per_circle' => 'required|integer|min:3|max:100',
            'preferred_session_duration' => 'required|integer|min:30|max:120',
            'available_days' => 'nullable|array',
            'available_times' => 'nullable|array',
            'bio_ar' => 'nullable|string|max:2000',
            'bio_en' => 'nullable|string|max:2000',
            'certifications' => 'nullable|array',
            'achievements' => 'nullable|array',
            'teaching_philosophy' => 'nullable|string|max:1500',
            'status' => 'nullable|in:active,inactive,suspended,pending',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $validated['updated_by'] = Auth::id();
            
            $teacher->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $teacher->fresh(['user', 'academy']),
                    'message' => 'تم تحديث بيانات معلم القرآن بنجاح'
                ]);
            }

            return redirect()
                ->route('academies.quran.teachers.show', [$teacher->academy->slug, $teacher->id])
                ->with('success', 'تم تحديث بيانات معلم القرآن بنجاح');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث بيانات معلم القرآن'
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء تحديث بيانات معلم القرآن']);
        }
    }

    /**
     * Remove the specified Quran teacher
     */
    public function destroy(QuranTeacher $teacher): RedirectResponse|JsonResponse
    {
        $this->ensureTeacherBelongsToAcademy($teacher);
        
        try {
            DB::beginTransaction();

            // Check if teacher has active subscriptions or circles
            $hasActiveRelations = $teacher->quranSubscriptions()->active()->exists() ||
                                $teacher->quranCircles()->active()->exists();

            if ($hasActiveRelations) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا يمكن حذف المعلم لوجود اشتراكات أو حلقات نشطة'
                    ], 422);
                }

                return back()->withErrors(['error' => 'لا يمكن حذف المعلم لوجود اشتراكات أو حلقات نشطة']);
            }

            $teacher->delete();

            // Remove teacher role from user if no other teacher records exist
            $user = $teacher->user;
            if (!QuranTeacher::where('user_id', $user->id)->exists()) {
                $user->removeRole('quran_teacher');
            }

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف معلم القرآن بنجاح'
                ]);
            }

            return redirect()
                ->route('academies.quran.teachers.index', $teacher->academy->slug)
                ->with('success', 'تم حذف معلم القرآن بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف معلم القرآن'
                ], 500);
            }

            return back()->withErrors(['error' => 'حدث خطأ أثناء حذف معلم القرآن']);
        }
    }

    /**
     * Approve a Quran teacher
     */
    public function approve(Request $request, QuranTeacher $teacher): JsonResponse|RedirectResponse
    {
        $this->ensureTeacherBelongsToAcademy($teacher);
        
        try {
            $teacher->update([
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'status' => 'active'
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $teacher->fresh(),
                    'message' => 'تم اعتماد معلم القرآن بنجاح'
                ]);
            }

            return back()->with('success', 'تم اعتماد معلم القرآن بنجاح');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء اعتماد معلم القرآن'
                ], 500);
            }

            return back()->withErrors(['error' => 'حدث خطأ أثناء اعتماد معلم القرآن']);
        }
    }

    /**
     * Reject a Quran teacher
     */
    public function reject(Request $request, QuranTeacher $teacher): JsonResponse|RedirectResponse
    {
        $this->ensureTeacherBelongsToAcademy($teacher);
        
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            $teacher->update([
                'approval_status' => 'rejected',
                'approved_by' => Auth::id(),
                'status' => 'inactive',
                'notes' => ($teacher->notes ? $teacher->notes . "\n\n" : '') . 
                          'سبب الرفض: ' . $request->rejection_reason
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $teacher->fresh(),
                    'message' => 'تم رفض طلب معلم القرآن'
                ]);
            }

            return back()->with('info', 'تم رفض طلب معلم القرآن');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء رفض طلب معلم القرآن'
                ], 500);
            }

            return back()->withErrors(['error' => 'حدث خطأ أثناء رفض طلب معلم القرآن']);
        }
    }

    /**
     * Get teacher availability
     */
    public function availability(QuranTeacher $teacher): JsonResponse
    {
        $this->ensureTeacherBelongsToAcademy($teacher);
        
        $availability = [
            'available_days' => $teacher->available_days,
            'available_times' => $teacher->available_times,
            'upcoming_sessions' => $teacher->quranSessions()
                ->where('session_date', '>=', now())
                ->orderBy('session_date')
                ->limit(10)
                ->get(['session_date', 'start_time', 'end_time', 'status']),
            'max_students_per_circle' => $teacher->max_students_per_circle,
            'preferred_session_duration' => $teacher->preferred_session_duration
        ];

        return response()->json([
            'success' => true,
            'data' => $availability,
            'message' => 'تم جلب جدول المعلم بنجاح'
        ]);
    }

    /**
     * Get teacher statistics
     */
    public function statistics(QuranTeacher $teacher): JsonResponse
    {
        $this->ensureTeacherBelongsToAcademy($teacher);
        
        $stats = [
            'overview' => [
                'total_students' => $teacher->total_students,
                'total_sessions' => $teacher->total_sessions,
                'rating' => $teacher->rating,
                'total_reviews' => $teacher->total_reviews,
            ],
            'subscriptions' => [
                'total' => $teacher->quranSubscriptions()->count(),
                'active' => $teacher->quranSubscriptions()->active()->count(),
                'completed' => $teacher->quranSubscriptions()->completed()->count(),
                'cancelled' => $teacher->quranSubscriptions()->cancelled()->count(),
            ],
            'circles' => [
                'total' => $teacher->quranCircles()->count(),
                'active' => $teacher->quranCircles()->active()->count(),
                'completed' => $teacher->quranCircles()->completed()->count(),
            ],
            'sessions' => [
                'total' => $teacher->quranSessions()->count(),
                'completed' => $teacher->quranSessions()->completed()->count(),
                'cancelled' => $teacher->quranSessions()->cancelled()->count(),
                'average_rating' => $teacher->quranSessions()->whereNotNull('teacher_rating')->avg('teacher_rating'),
            ],
            'financial' => [
                'total_earnings' => $this->calculateTotalEarnings($teacher),
                'current_month_earnings' => $this->calculateMonthlyEarnings($teacher),
                'hourly_rate_individual' => $teacher->hourly_rate_individual,
                'hourly_rate_group' => $teacher->hourly_rate_group,
            ],
            'performance' => [
                'completion_rate' => $this->calculateCompletionRate($teacher),
                'student_retention_rate' => $this->calculateRetentionRate($teacher),
                'average_progress_improvement' => $this->calculateAverageProgressImprovement($teacher),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'تم جلب إحصائيات المعلم بنجاح'
        ]);
    }

    /**
     * Get available teachers for a specific academy
     */
    public function available(Request $request): JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $teachers = QuranTeacher::with('user')
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('approval_status', 'approved');

        if ($request->filled('specialization')) {
            $teachers->where('specialization', $request->specialization);
        }

        if ($request->filled('memorization_level')) {
            $teachers->where('memorization_level', $request->memorization_level);
        }

        $teachers = $teachers->get();

        return response()->json([
            'success' => true,
            'data' => $teachers,
            'message' => 'تم جلب قائمة المعلمين المتاحين بنجاح'
        ]);
    }

    // Private helper methods
    private function getCurrentAcademy(): Academy
    {
        return Auth::user()->academy ?? Academy::where('slug', request()->route('academy'))->firstOrFail();
    }

    private function ensureTeacherBelongsToAcademy(QuranTeacher $teacher): void
    {
        $academy = $this->getCurrentAcademy();
        
        if ($teacher->academy_id !== $academy->id) {
            abort(404, 'معلم القرآن غير موجود');
        }
    }

    private function calculateTotalEarnings(QuranTeacher $teacher): float
    {
        $subscriptionEarnings = $teacher->quranSubscriptions()
            ->where('payment_status', 'paid')
            ->sum('total_price');

        $sessionEarnings = $teacher->quranSessions()
            ->completed()
            ->sum('teacher_fee');

        return $subscriptionEarnings + $sessionEarnings;
    }

    private function calculateMonthlyEarnings(QuranTeacher $teacher): float
    {
        $subscriptionEarnings = $teacher->quranSubscriptions()
            ->where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_price');

        $sessionEarnings = $teacher->quranSessions()
            ->completed()
            ->whereMonth('session_date', now()->month)
            ->whereYear('session_date', now()->year)
            ->sum('teacher_fee');

        return $subscriptionEarnings + $sessionEarnings;
    }

    private function calculateCompletionRate(QuranTeacher $teacher): float
    {
        $totalSessions = $teacher->quranSessions()->count();
        
        if ($totalSessions === 0) {
            return 0;
        }

        $completedSessions = $teacher->quranSessions()->completed()->count();
        
        return round(($completedSessions / $totalSessions) * 100, 2);
    }

    private function calculateRetentionRate(QuranTeacher $teacher): float
    {
        $totalSubscriptions = $teacher->quranSubscriptions()->count();
        
        if ($totalSubscriptions === 0) {
            return 0;
        }

        $renewedSubscriptions = $teacher->quranSubscriptions()
            ->where('auto_renew', true)
            ->whereNotNull('last_payment_at')
            ->count();
        
        return round(($renewedSubscriptions / $totalSubscriptions) * 100, 2);
    }

    private function calculateAverageProgressImprovement(QuranTeacher $teacher): float
    {
        return $teacher->progress()
            ->whereNotNull('progress_percentage')
            ->avg('progress_percentage') ?? 0;
    }
} 