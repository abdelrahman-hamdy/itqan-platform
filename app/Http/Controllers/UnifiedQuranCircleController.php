<?php

namespace App\Http\Controllers;

use App\Enums\CircleEnrollmentStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\Academy;
use App\Models\QuranCircle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnifiedQuranCircleController extends Controller
{
    /**
     * Display a listing of Quran circles (Unified for both public and authenticated)
     */
    public function index(Request $request, $subdomain): \Illuminate\View\View
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        $user = Auth::user();
        $isAuthenticated = (bool) $user;

        // Student-specific data
        $enrolledCircleIds = [];

        if ($isAuthenticated) {
            // Get student's enrolled circle IDs
            $enrolledCircleIds = $user->quranCircles()
                ->where('academy_id', $academy->id)
                ->pluck('quran_circles.id')
                ->toArray();
        }

        // Base query (applies to all users)
        $query = QuranCircle::where('academy_id', $academy->id)
            ->where('status', true)
            ->with(['quranTeacher', 'students'])
            ->withCount('students as students_count');

        // For guests, only show circles open for enrollment
        if (! $isAuthenticated) {
            $query->where('enrollment_status', CircleEnrollmentStatus::OPEN);
        }

        // Apply filters (same for both)
        if ($request->filled('enrollment_status')) {
            if ($request->enrollment_status === 'enrolled' && $isAuthenticated) {
                $query->whereIn('id', $enrolledCircleIds);
            } elseif ($request->enrollment_status === 'available') {
                if ($isAuthenticated) {
                    $query->whereNotIn('id', $enrolledCircleIds)
                        ->where('enrollment_status', CircleEnrollmentStatus::OPEN);
                } else {
                    $query->where('enrollment_status', CircleEnrollmentStatus::OPEN);
                }
            } else {
                $query->where('enrollment_status', $request->enrollment_status);
            }
        }

        if ($request->filled('memorization_level')) {
            $query->where('memorization_level', $request->memorization_level);
        }

        if ($request->filled('schedule_days') && is_array($request->schedule_days)) {
            $query->where(function ($q) use ($request) {
                foreach ($request->schedule_days as $day) {
                    $q->orWhereJsonContains('schedule_days', $day);
                }
            });
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('description', 'LIKE', '%'.$request->search.'%');
            });
        }

        // For authenticated students, sort enrolled circles first
        if ($isAuthenticated && count($enrolledCircleIds) > 0) {
            $circles = $query->get()->sortByDesc(function ($circle) use ($enrolledCircleIds) {
                return in_array($circle->id, $enrolledCircleIds) ? 1 : 0;
            })->values();

            // Manual pagination
            $perPage = 12;
            $currentPage = $request->get('page', 1);
            $offset = ($currentPage - 1) * $perPage;

            $paginatedCircles = new \Illuminate\Pagination\LengthAwarePaginator(
                $circles->slice($offset, $perPage),
                $circles->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            // For guests, simple pagination
            $paginatedCircles = $query->paginate(12);
        }

        // Get available memorization levels
        $levels = QuranCircle::where('academy_id', $academy->id)
            ->where('status', true)
            ->distinct()
            ->pluck('memorization_level')
            ->filter()
            ->values();

        return view('student.quran-circles', compact(
            'academy',
            'paginatedCircles',
            'enrolledCircleIds',
            'levels',
            'isAuthenticated'
        ));
    }

    /**
     * Display the specified circle details (Unified for both public and authenticated)
     */
    public function show(Request $request, $subdomain, $circleId): \Illuminate\View\View
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        $user = Auth::user();
        $isAuthenticated = (bool) $user;

        // Find the circle
        $circle = QuranCircle::where('id', $circleId)
            ->where('academy_id', $academy->id)
            ->where('status', true)
            ->with(['academy', 'quranTeacher', 'students', 'schedule', 'sessions'])
            ->firstOrFail();

        // Calculate statistics
        $stats = [
            'total_students' => $circle->enrolled_students ?? 0,
            'available_spots' => $circle->available_spots ?? 0,
            'sessions_completed' => $circle->sessions_completed ?? 0,
            'rating' => $circle->avg_rating ?? 0,
        ];

        // Check enrollment status for authenticated students
        $isEnrolled = false;
        $canEnroll = $circle->enrollment_status === CircleEnrollmentStatus::OPEN && $circle->available_spots > 0;
        $subscription = null;
        $upcomingSessions = collect();
        $pastSessions = collect();

        if ($isAuthenticated) {
            $isEnrolled = $circle->students()->where('users.id', $user->id)->exists();
            $canEnroll = ! $isEnrolled && $circle->status === true && $circle->enrollment_status === CircleEnrollmentStatus::OPEN;

            // Get sessions and subscription for enrolled students
            if ($isEnrolled) {
                $now = now();

                // Get upcoming sessions (scheduled in the future or currently ongoing)
                $upcomingSessions = $circle->sessions()
                    ->with(['quranTeacher'])
                    ->where(function ($query) use ($now) {
                        $query->where('scheduled_at', '>', $now)
                            ->orWhere('status', SessionStatus::ONGOING->value);
                    })
                    ->orderBy('scheduled_at', 'asc')
                    ->take(10)
                    ->get();

                // Get past sessions (completed)
                $pastSessions = $circle->sessions()
                    ->with(['quranTeacher'])
                    ->where('scheduled_at', '<=', $now)
                    ->where('status', SessionStatus::COMPLETED->value)
                    ->orderBy('scheduled_at', 'desc')
                    ->take(5)
                    ->get();

                // Get active subscription for this circle
                if ($circle->quran_teacher_id) {
                    $subscription = \App\Models\QuranSubscription::where('student_id', $user->id)
                        ->where('academy_id', $academy->id)
                        ->where('quran_teacher_id', $circle->quran_teacher_id)
                        ->where('subscription_type', 'group')
                        ->whereIn('status', [SessionSubscriptionStatus::ACTIVE->value, SessionSubscriptionStatus::PENDING->value])
                        ->with(['package', 'quranTeacherUser'])
                        ->first();
                }
            }
        }

        return view('student.circle-detail', compact(
            'academy',
            'circle',
            'stats',
            'isEnrolled',
            'canEnroll',
            'isAuthenticated',
            'upcomingSessions',
            'pastSessions',
            'subscription'
        ));
    }

    /**
     * Enroll student in a circle (requires authentication)
     */
    public function enroll(Request $request, $subdomain, $circleId): \Illuminate\Http\RedirectResponse
    {
        // Must be authenticated
        if (! Auth::check()) {
            return redirect()->route('login', [
                'subdomain' => $subdomain,
                'redirect' => route('quran-circles.show', ['subdomain' => $subdomain, 'circleId' => $circleId]),
            ])->with('message', __('auth.login_required_to_join'));
        }

        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();
        $user = Auth::user();

        \Log::info('[CircleEnroll] Starting enrollment', [
            'user_id' => $user->id,
            'circle_id' => $circleId,
            'academy_id' => $academy->id,
        ]);

        // Find the circle
        $circle = QuranCircle::where('id', $circleId)
            ->where('academy_id', $academy->id)
            ->where('status', true)
            ->firstOrFail();

        \Log::info('[CircleEnroll] Circle found', [
            'circle_id' => $circle->id,
            'monthly_fee' => $circle->monthly_fee,
            'monthly_fee_type' => gettype($circle->monthly_fee),
        ]);

        // Check if already enrolled
        if ($circle->students()->where('users.id', $user->id)->exists()) {
            \Log::info('[CircleEnroll] Already enrolled');

            return redirect()->route('quran-circles.show', ['subdomain' => $subdomain, 'circleId' => $circleId])
                ->with('info', __('circles.already_enrolled'));
        }

        // Check if circle is open for enrollment
        if ($circle->enrollment_status !== CircleEnrollmentStatus::OPEN || $circle->available_spots <= 0) {
            \Log::info('[CircleEnroll] Enrollment closed');

            return redirect()->route('quran-circles.show', ['subdomain' => $subdomain, 'circleId' => $circleId])
                ->with('error', __('circles.enrollment_closed'));
        }

        // Check if circle has a fee - if so, redirect to payment flow
        $hasFee = $circle->monthly_fee && $circle->monthly_fee > 0;

        \Log::info('[CircleEnroll] Fee check', [
            'hasFee' => $hasFee,
            'monthly_fee' => $circle->monthly_fee,
        ]);

        if ($hasFee) {
            \Log::info('[CircleEnroll] PAID CIRCLE - Creating pending subscription');

            // Create pending subscription first, then redirect to payment
            $subscription = \DB::transaction(function () use ($circle, $user, $academy) {
                \Log::info('[CircleEnroll] Inside transaction - creating subscription');

                $sub = \App\Models\QuranSubscription::create([
                    'academy_id' => $academy->id,
                    'student_id' => $user->id,
                    'quran_teacher_id' => $circle->quran_teacher_id,
                    'subscription_code' => \App\Models\QuranSubscription::generateSubscriptionCode($academy->id),
                    'subscription_type' => 'group',
                    'education_unit_type' => 'App\\Models\\QuranCircle',
                    'education_unit_id' => $circle->id,
                    'total_sessions' => $circle->sessions_per_month ?? 8,
                    'sessions_used' => 0,
                    'sessions_remaining' => $circle->sessions_per_month ?? 8,
                    'total_price' => $circle->monthly_fee,
                    'discount_amount' => 0,
                    'final_price' => $circle->monthly_fee,
                    'currency' => $circle->currency ?? getCurrencyCode(null, $circle->academy),
                    'billing_cycle' => 'monthly',
                    'payment_status' => \App\Enums\SubscriptionPaymentStatus::PENDING,
                    'status' => \App\Enums\SessionSubscriptionStatus::PENDING,
                    'memorization_level' => $circle->memorization_level ?? 'beginner',
                    'starts_at' => now(),
                    'auto_renew' => true,
                ]);

                \Log::info('[CircleEnroll] Subscription created', [
                    'subscription_id' => $sub->id,
                    'status_raw' => $sub->getRawOriginal('status'),
                    'status_cast' => $sub->status,
                    'payment_status_raw' => $sub->getRawOriginal('payment_status'),
                    'payment_status_cast' => $sub->payment_status,
                ]);

                // Check if pivot was accidentally created
                $pivotExists = $circle->students()->where('quran_circle_students.student_id', $user->id)->exists();
                \Log::info('[CircleEnroll] Pivot check after subscription create', [
                    'pivot_exists' => $pivotExists,
                ]);

                return $sub;
            });

            \Log::info('[CircleEnroll] Redirecting to payment page', [
                'subscription_id' => $subscription->id,
            ]);

            // Redirect to payment page
            return redirect()->route('quran.subscription.payment', [
                'subdomain' => $subdomain,
                'subscription' => $subscription->id,
            ]);
        }

        // Free circle - enroll immediately
        \DB::transaction(function () use ($circle, $user, $academy) {
            // Enroll student in circle
            $circle->students()->attach($user->id, [
                'enrolled_at' => now(),
                'status' => 'enrolled',
                'attendance_count' => 0,
                'missed_sessions' => 0,
                'makeup_sessions_used' => 0,
                'current_level' => 'beginner',
            ]);

            // Create a free subscription
            \App\Models\QuranSubscription::create([
                'academy_id' => $academy->id,
                'student_id' => $user->id,
                'quran_teacher_id' => $circle->quran_teacher_id,
                'subscription_code' => \App\Models\QuranSubscription::generateSubscriptionCode($academy->id),
                'subscription_type' => 'group',
                'education_unit_type' => 'App\\Models\\QuranCircle',
                'education_unit_id' => $circle->id,
                'total_sessions' => $circle->sessions_per_month ?? 8,
                'sessions_used' => 0,
                'sessions_remaining' => $circle->sessions_per_month ?? 8,
                'total_price' => 0,
                'discount_amount' => 0,
                'final_price' => 0,
                'currency' => $circle->currency ?? getCurrencyCode(null, $circle->academy),
                'billing_cycle' => 'monthly',
                'payment_status' => 'paid',
                'status' => 'active',
                'memorization_level' => $circle->memorization_level ?? 'beginner',
                'starts_at' => now(),
                'auto_renew' => false,
            ]);

            // Update circle enrollment count
            $circle->increment('enrolled_students');

            // Check if circle is now full
            if ($circle->enrolled_students >= $circle->max_students) {
                $circle->update(['enrollment_status' => CircleEnrollmentStatus::FULL]);
            }
        });

        return redirect()->route('quran-circles.show', ['subdomain' => $subdomain, 'circleId' => $circleId])
            ->with('success', __('circles.enrollment_success'));
    }
}
