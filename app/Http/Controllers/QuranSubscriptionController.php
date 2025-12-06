<?php

namespace App\Http\Controllers;

use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QuranSubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('tenant');
    }

    /**
     * Display a listing of Quran subscriptions
     */
    public function index(Request $request): View|JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $query = QuranSubscription::with(['student', 'quranTeacher.user', 'academy'])
            ->where('academy_id', $academy->id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('package_type')) {
            $query->where('package_type', $request->package_type);
        }

        if ($request->filled('teacher_id')) {
            $query->where('quran_teacher_id', $request->teacher_id);
        }

        if ($request->filled('student_search')) {
            $search = $request->student_search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('expiring_soon')) {
            $query->where('expires_at', '<=', now()->addDays(7))
                  ->where('status', 'active');
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $subscriptions,
                'message' => 'قائمة اشتراكات القرآن تم جلبها بنجاح'
            ]);
        }

        return view('quran.subscriptions.index', compact('subscriptions', 'academy'));
    }

    /**
     * Show the form for creating a new subscription
     */
    public function create(Request $request): View
    {
        $academy = $this->getCurrentAcademy();
        
        $teachers = QuranTeacherProfile::with('user')
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('approval_status', 'approved')
            ->get();

        $students = User::role('student')
            ->whereHas('academies', function ($q) use ($academy) {
                $q->where('academies.id', $academy->id);
            })
            ->get();

        $preSelectedTeacher = null;
        if ($request->filled('teacher_id')) {
            $preSelectedTeacher = $teachers->find($request->teacher_id);
        }

        return view('quran.subscriptions.create', compact('academy', 'teachers', 'students', 'preSelectedTeacher'));
    }

    /**
     * Store a newly created subscription
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        // DEBUG: Check if academic subscription requests are wrongly coming here
        \Log::error('QURAN SUBSCRIPTION STORE CALLED', [
            'path' => $request->path(),
            'method' => $request->method(),
            'data' => $request->all(),
        ]);
        
        $academy = $this->getCurrentAcademy();
        
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'quran_teacher_id' => 'required|exists:users,id',
            'package_name' => 'required|string|max:100',
            'package_type' => 'required|in:basic,standard,premium,intensive,custom',
            'total_sessions' => 'required|integer|min:4|max:32',
            'price_per_session' => 'required|numeric|min:0|max:500',
            'billing_cycle' => 'required|in:weekly,monthly,quarterly,yearly',
            'trial_sessions' => 'nullable|integer|min:0|max:3',
            'starts_at' => 'required|date|after_or_equal:today',
            'auto_renew' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Verify teacher belongs to academy
            $teacher = QuranTeacherProfile::where('id', $validated['quran_teacher_id'])
                ->where('academy_id', $academy->id)
                ->firstOrFail();

            // Verify student has access to academy
            $student = User::findOrFail($validated['student_id']);
            if (!$student->academies()->where('academies.id', $academy->id)->exists()) {
                throw new \Exception('الطالب غير مسجل في هذه الأكاديمية');
            }

            // Calculate pricing
            $totalPrice = $validated['price_per_session'] * $validated['total_sessions'];
            $currency = $teacher->currency;

            // Set expiry date based on billing cycle
            $startsAt = Carbon::parse($validated['starts_at']);
            $expiresAt = match($validated['billing_cycle']) {
                'weekly' => $startsAt->copy()->addWeeks(1),
                'monthly' => $startsAt->copy()->addMonth(),
                'quarterly' => $startsAt->copy()->addMonths(3),
                'yearly' => $startsAt->copy()->addYear(),
                default => $startsAt->copy()->addMonth()
            };

            $subscriptionData = array_merge($validated, [
                'academy_id' => $academy->id,
                'subscription_code' => QuranSubscription::generateSubscriptionCode($academy->id),
                'subscription_type' => 'individual',
                'sessions_used' => 0,
                'sessions_remaining' => $validated['total_sessions'],
                'total_price' => $totalPrice,
                'currency' => $currency,
                'expires_at' => $expiresAt,
                'payment_status' => 'pending',
                'status' => 'pending',
                'trial_used' => 0,
                'is_trial_active' => ($validated['trial_sessions'] ?? 0) > 0,
                'memorization_level' => 'beginner',
                'progress_percentage' => 0,
                'created_by' => Auth::id(),
            ]);

            $subscription = QuranSubscription::create($subscriptionData);

            // Update teacher stats
            $teacher->increment('total_students');

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $subscription->load(['student', 'quranTeacher.user']),
                    'message' => 'تم إنشاء الاشتراك بنجاح'
                ], 201);
            }

            return redirect()
                ->route('academies.quran.subscriptions.show', [$academy->slug, $subscription->id])
                ->with('success', 'تم إنشاء الاشتراك بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إنشاء الاشتراك: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء إنشاء الاشتراك: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified subscription
     */
    public function show(QuranSubscription $subscription): View|JsonResponse
    {
        $this->ensureSubscriptionBelongsToAcademy($subscription);
        
        $subscription->load([
            'student',
            'quranTeacher.user',
            'academy',
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

        // Calculate subscription statistics
        $stats = [
            'sessions_completed' => $subscription->quranSessions()->completed()->count(),
            'sessions_cancelled' => $subscription->quranSessions()->cancelled()->count(),
            'sessions_remaining' => $subscription->sessions_remaining,
            'progress_percentage' => $subscription->progress_percentage,
            'days_remaining' => $subscription->expires_at ? now()->diffInDays($subscription->expires_at, false) : null,
            'average_session_rating' => $subscription->quranSessions()->whereNotNull('student_rating')->avg('student_rating'),
            // Note: homework stats removed - Quran homework is now tracked through QuranSession model fields
            // and graded through student session reports (oral evaluation)
        ];

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $subscription,
                'stats' => $stats,
                'message' => 'تم جلب بيانات الاشتراك بنجاح'
            ]);
        }

        return view('quran.subscriptions.show', compact('subscription', 'stats'));
    }

    /**
     * Show the form for editing the subscription
     */
    public function edit(QuranSubscription $subscription): View
    {
        $this->ensureSubscriptionBelongsToAcademy($subscription);
        
        $academy = $this->getCurrentAcademy();
        
        $teachers = QuranTeacherProfile::with('user')
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('approval_status', 'approved')
            ->get();

        $subscription->load(['student', 'quranTeacher.user', 'academy']);
        
        return view('quran.subscriptions.edit', compact('subscription', 'teachers', 'academy'));
    }

    /**
     * Update the specified subscription
     */
    public function update(Request $request, QuranSubscription $subscription): RedirectResponse|JsonResponse
    {
        $this->ensureSubscriptionBelongsToAcademy($subscription);
        
        $validated = $request->validate([
            'package_name' => 'required|string|max:100',
            'package_type' => 'required|in:basic,standard,premium,intensive,custom',
            'price_per_session' => 'required|numeric|min:0|max:500',
            'billing_cycle' => 'required|in:weekly,monthly,quarterly,yearly',
            'auto_renew' => 'boolean',
            'notes' => 'nullable|string|max:500',
            'status' => 'nullable|in:active,expired,paused,cancelled,pending,suspended',
            'payment_status' => 'nullable|in:paid,pending,failed,refunded,cancelled',
        ]);

        try {
            // Recalculate total price if sessions or price changed
            if (isset($validated['price_per_session'])) {
                $validated['total_price'] = $validated['price_per_session'] * $subscription->total_sessions;
            }

            $validated['updated_by'] = Auth::id();
            
            $subscription->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $subscription->fresh(['student', 'quranTeacher.user']),
                    'message' => 'تم تحديث الاشتراك بنجاح'
                ]);
            }

            return redirect()
                ->route('academies.quran.subscriptions.show', [$subscription->academy->slug, $subscription->id])
                ->with('success', 'تم تحديث الاشتراك بنجاح');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث الاشتراك'
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء تحديث الاشتراك']);
        }
    }

    /**
     * Activate a subscription
     */
    public function activate(QuranSubscription $subscription): JsonResponse|RedirectResponse
    {
        $this->ensureSubscriptionBelongsToAcademy($subscription);
        
        try {
            if ($subscription->status !== 'pending') {
                throw new \Exception('لا يمكن تفعيل هذا الاشتراك في حالته الحالية');
            }

            $subscription->update([
                'status' => 'active',
                'payment_status' => 'paid',
                'last_payment_at' => now(),
                'next_payment_at' => $this->calculateNextPaymentDate($subscription),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $subscription->fresh(),
                    'message' => 'تم تفعيل الاشتراك بنجاح'
                ]);
            }

            return back()->with('success', 'تم تفعيل الاشتراك بنجاح');

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
     * Pause a subscription
     */
    public function pause(Request $request, QuranSubscription $subscription): JsonResponse|RedirectResponse
    {
        $this->ensureSubscriptionBelongsToAcademy($subscription);
        
        $request->validate([
            'pause_reason' => 'required|string|max:500'
        ]);

        try {
            if ($subscription->status !== 'active') {
                throw new \Exception('لا يمكن إيقاف هذا الاشتراك في حالته الحالية');
            }

            $subscription->update([
                'status' => 'paused',
                'paused_at' => now(),
                'pause_reason' => $request->pause_reason,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $subscription->fresh(),
                    'message' => 'تم إيقاف الاشتراك مؤقتاً'
                ]);
            }

            return back()->with('info', 'تم إيقاف الاشتراك مؤقتاً');

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
     * Resume a paused subscription
     */
    public function resume(QuranSubscription $subscription): JsonResponse|RedirectResponse
    {
        $this->ensureSubscriptionBelongsToAcademy($subscription);
        
        try {
            if ($subscription->status !== 'paused') {
                throw new \Exception('الاشتراك غير متوقف');
            }

            // Extend expiry date by the paused duration
            $pausedDuration = now()->diffInDays($subscription->paused_at);
            $newExpiryDate = $subscription->expires_at->addDays($pausedDuration);

            $subscription->update([
                'status' => 'active',
                'expires_at' => $newExpiryDate,
                'paused_at' => null,
                'pause_reason' => null,
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $subscription->fresh(),
                    'message' => 'تم استئناف الاشتراك بنجاح'
                ]);
            }

            return back()->with('success', 'تم استئناف الاشتراك بنجاح');

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
     * Cancel a subscription
     */
    public function cancel(Request $request, QuranSubscription $subscription): JsonResponse|RedirectResponse
    {
        $this->ensureSubscriptionBelongsToAcademy($subscription);
        
        $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        try {
            if (in_array($subscription->status, ['cancelled', 'expired'])) {
                throw new \Exception('الاشتراك ملغي بالفعل أو منتهي الصلاحية');
            }

            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $request->cancellation_reason,
                'auto_renew' => false,
            ]);

            // Cancel any upcoming sessions
            $subscription->quranSessions()
                ->where('session_date', '>', now())
                ->where('status', 'scheduled')
                ->update(['status' => 'cancelled']);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $subscription->fresh(),
                    'message' => 'تم إلغاء الاشتراك'
                ]);
            }

            return back()->with('info', 'تم إلغاء الاشتراك');

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
     * Renew a subscription
     */
    public function renew(Request $request, QuranSubscription $subscription): JsonResponse|RedirectResponse
    {
        $this->ensureSubscriptionBelongsToAcademy($subscription);
        
        try {
            if (!in_array($subscription->status, ['expired', 'active'])) {
                throw new \Exception('لا يمكن تجديد هذا الاشتراك في حالته الحالية');
            }

            // Create new subscription based on current one
            $newExpiryDate = $this->calculateNextPaymentDate($subscription);
            
            $subscription->update([
                'status' => 'active',
                'payment_status' => 'paid',
                'expires_at' => $newExpiryDate,
                'last_payment_at' => now(),
                'next_payment_at' => $this->calculateNextPaymentDate($subscription, $newExpiryDate),
                'sessions_used' => 0,
                'sessions_remaining' => $subscription->total_sessions,
                'trial_used' => 0,
                'is_trial_active' => false,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $subscription->fresh(),
                    'message' => 'تم تجديد الاشتراك بنجاح'
                ]);
            }

            return back()->with('success', 'تم تجديد الاشتراك بنجاح');

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
     * Get student's active subscriptions
     */
    public function studentSubscriptions(User $student): JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        
        $subscriptions = QuranSubscription::with(['quranTeacher.user'])
            ->where('academy_id', $academy->id)
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
            'message' => 'تم جلب اشتراكات الطالب بنجاح'
        ]);
    }

    /**
     * Get expiring subscriptions
     */
    public function expiring(Request $request): JsonResponse
    {
        $academy = $this->getCurrentAcademy();
        $days = $request->get('days', 7);
        
        $subscriptions = QuranSubscription::with(['student', 'quranTeacher.user'])
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('expires_at', '<=', now()->addDays($days))
            ->orderBy('expires_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
            'message' => 'تم جلب الاشتراكات المنتهية الصلاحية بنجاح'
        ]);
    }

    // Private helper methods
    private function getCurrentAcademy(): Academy
    {
        return Auth::user()->academy ?? Academy::where('slug', request()->route('academy'))->firstOrFail();
    }

    private function ensureSubscriptionBelongsToAcademy(QuranSubscription $subscription): void
    {
        $academy = $this->getCurrentAcademy();
        
        if ($subscription->academy_id !== $academy->id) {
            abort(404, 'الاشتراك غير موجود');
        }
    }



    private function calculateNextPaymentDate(QuranSubscription $subscription, Carbon $baseDate = null): Carbon
    {
        $baseDate = $baseDate ?? now();
        
        return match($subscription->billing_cycle) {
            'weekly' => $baseDate->copy()->addWeeks(1),
            'monthly' => $baseDate->copy()->addMonth(),
            'quarterly' => $baseDate->copy()->addMonths(3),
            'yearly' => $baseDate->copy()->addYear(),
            default => $baseDate->copy()->addMonth()
        };
    }
} 