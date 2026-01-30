<?php

namespace App\Http\Controllers;

use App\Enums\CircleEnrollmentStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranCircle;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Services\CircleEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentQuranController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected CircleEnrollmentService $circleEnrollmentService
    ) {}

    public function quranCircles(Request $request): View
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get student's enrolled circle IDs
        $enrolledCircleIds = $user->quranCircles()
            ->where('academy_id', $academy->id)
            ->pluck('quran_circles.id')
            ->toArray();

        // Build query for all circles (both enrolled and available)
        $query = QuranCircle::where('academy_id', $academy->id)
            ->where('status', true)
            ->with(['quranTeacher', 'students'])
            ->withCount('students as students_count');

        // Apply filters
        if ($request->filled('enrollment_status')) {
            if ($request->enrollment_status === 'enrolled') {
                $query->whereIn('id', $enrolledCircleIds);
            } elseif ($request->enrollment_status === 'available') {
                $query->whereNotIn('id', $enrolledCircleIds)
                    ->where('enrollment_status', 'open');
            } else {
                $query->where('enrollment_status', $request->enrollment_status);
            }
        }

        if ($request->filled('memorization_level')) {
            $query->where('memorization_level', $request->memorization_level);
        }

        if ($request->filled('schedule_days') && is_array($request->schedule_days)) {
            // Map Arabic day names to English for database query
            $arabicToEnglish = [
                'السبت' => 'saturday',
                'الأحد' => 'sunday',
                'الاثنين' => 'monday',
                'الثلاثاء' => 'tuesday',
                'الأربعاء' => 'wednesday',
                'الخميس' => 'thursday',
                'الجمعة' => 'friday',
            ];

            $englishDays = array_map(function ($arabicDay) use ($arabicToEnglish) {
                return $arabicToEnglish[$arabicDay] ?? $arabicDay;
            }, $request->schedule_days);

            $query->where(function ($q) use ($englishDays) {
                foreach ($englishDays as $day) {
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

        // Sort: Enrolled circles first, then by creation date
        $circles = $query->get()->sortByDesc(function ($circle) use ($enrolledCircleIds) {
            return in_array($circle->id, $enrolledCircleIds) ? 1 : 0;
        })->values();

        // Paginate manually
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

        // Get available memorization levels from circles
        $levels = QuranCircle::where('academy_id', $academy->id)
            ->where('status', true)
            ->distinct()
            ->pluck('memorization_level')
            ->filter()
            ->values();

        return view('student.quran-circles', compact(
            'paginatedCircles',
            'enrolledCircleIds',
            'levels'
        ));
    }

    /**
     * Show circle details for enrollment
     */
    public function showCircle(Request $request, $subdomain, $circleId): View
    {
        $user = Auth::user();
        $academy = $user->academy;

        $circle = QuranCircle::where('academy_id', $academy->id)
            ->where('id', $circleId)
            ->with(['quranTeacher', 'students', 'academy'])
            ->first();

        if (! $circle) {
            abort(404, 'Circle not found');
        }

        // Check if student is already enrolled
        $isEnrolled = $circle->students()->where('users.id', $user->id)->exists();

        // Check if circle is available for enrollment
        $canEnroll = $circle->status === true &&
                     $circle->enrollment_status === CircleEnrollmentStatus::OPEN &&
                     $circle->enrolled_students < $circle->max_students &&
                     ! $isEnrolled;

        // Get upcoming sessions for enrolled students (only if enrolled)
        $upcomingSessions = collect();
        $pastSessions = collect();

        if ($isEnrolled) {
            // Get all sessions for this circle (auto-generated by cron job)
            $allSessions = $circle->sessions()
                ->with(['quranTeacher'])
                ->orderBy('scheduled_at', 'asc')
                ->get();

            $now = now();
            // Include both upcoming sessions and ongoing sessions
            $upcomingSessions = $allSessions->filter(function ($session) use ($now) {
                return $session->scheduled_at > $now || $session->status->value === 'ongoing' || $session->status === \App\Enums\SessionStatus::ONGOING;
            })->take(10);

            $pastSessions = $allSessions->where('scheduled_at', '<=', $now)
                ->where('status', SessionStatus::COMPLETED->value)
                ->sortByDesc('scheduled_at')
                ->take(5);
        }

        // Get active group subscription for this circle if student is enrolled
        $subscription = null;
        if ($isEnrolled && $circle->quran_teacher_id) {
            // Try to find subscription by matching teacher and subscription type
            $subscription = \App\Models\QuranSubscription::where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('quran_teacher_id', $circle->quran_teacher_id)
                ->where('subscription_type', 'group')
                ->whereIn('status', [SessionSubscriptionStatus::ACTIVE->value, SessionSubscriptionStatus::PENDING->value])
                ->with(['package', 'quranTeacherUser'])
                ->first();

            // If no subscription exists for this enrollment, create one
            if (! $subscription) {
                $subscription = \App\Models\QuranSubscription::create([
                    'academy_id' => $academy->id,
                    'student_id' => $user->id,
                    'quran_teacher_id' => $circle->quran_teacher_id,
                    'subscription_code' => \App\Models\QuranSubscription::generateSubscriptionCode($academy->id),
                    'subscription_type' => 'group',
                    'total_sessions' => $circle->sessions_per_month ?? 8,
                    'sessions_used' => 0,
                    'sessions_remaining' => $circle->sessions_per_month ?? 8,
                    'total_price' => $circle->monthly_fee ?? 0,
                    'discount_amount' => 0,
                    'final_price' => $circle->monthly_fee ?? 0,
                    'currency' => $circle->currency ?? getCurrencyCode(null, $circle->academy),
                    'billing_cycle' => 'monthly',
                    'payment_status' => ($circle->monthly_fee && $circle->monthly_fee > 0) ? 'pending' : 'paid',
                    'status' => 'active',
                    'memorization_level' => $circle->memorization_level ?? 'beginner',
                    'starts_at' => now(),
                    'next_payment_at' => ($circle->monthly_fee && $circle->monthly_fee > 0) ? now()->addMonth() : null,
                    'auto_renew' => true,
                ]);

                // Reload with relationships
                $subscription->load(['package', 'quranTeacherUser']);
            }
        }

        return view('student.circle-detail', compact(
            'circle',
            'isEnrolled',
            'canEnroll',
            'academy',
            'upcomingSessions',
            'pastSessions',
            'subscription'
        ));
    }

    /**
     * Show individual circle details for a student
     */
    public function showIndividualCircle(Request $request, $subdomain, $circleId): View
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Find the individual circle that belongs to this student
        $individualCircle = \App\Models\QuranIndividualCircle::where('id', $circleId)
            ->where('student_id', $user->id)
            ->with(['subscription', 'quranTeacher', 'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'asc');
            }])
            ->first();

        if (! $individualCircle) {
            abort(404, 'Individual circle not found or you do not have access');
        }

        // Get upcoming and past sessions
        $now = now();
        $allSessions = $individualCircle->sessions;

        // CRITICAL FIX: Include all active session statuses for students
        $upcomingSessions = $allSessions->filter(function ($session) use ($now) {
            // Show if: future sessions, or any active/ongoing sessions regardless of time
            return ($session->scheduled_at > $now ||
                    in_array($session->status, [
                        \App\Enums\SessionStatus::ONGOING,
                        \App\Enums\SessionStatus::READY,
                        \App\Enums\SessionStatus::UNSCHEDULED, // Include unscheduled sessions
                    ])) && $session->status !== \App\Enums\SessionStatus::CANCELLED;
        })->sortBy('scheduled_at')->take(10);

        // CRITICAL FIX: Include completed sessions and any past sessions with attendance data
        $pastSessions = $allSessions->filter(function ($session) use ($now) {
            return $session->scheduled_at <= $now &&
                   in_array($session->status, [
                       \App\Enums\SessionStatus::COMPLETED,
                       \App\Enums\SessionStatus::ABSENT,
                       \App\Enums\SessionStatus::CANCELLED,
                       // Include sessions that ended but might have attendance data
                   ]);
        })->sortByDesc('scheduled_at')->take(10);

        $templateSessions = $allSessions->where('is_template', true)
            ->where('is_scheduled', false);

        return view('student.individual-circles.show', compact(
            'individualCircle',
            'upcomingSessions',
            'pastSessions',
            'templateSessions',
            'academy'
        ));
    }

    /**
     * Enroll student in a circle
     */
    public function enrollInCircle(Request $request, $subdomain, $circleId): JsonResponse
    {
        $user = Auth::user();
        $academy = $user->academy;

        $circle = QuranCircle::where('academy_id', $academy->id)
            ->where('id', $circleId)
            ->first();

        if (! $circle) {
            return $this->notFound('Circle not found');
        }

        try {
            $result = $this->circleEnrollmentService->enroll($user, $circle);

            if (! $result['success']) {
                return $this->error($result['error'], 400);
            }

            return $this->success(
                ['redirect_url' => route('student.quran-circles', ['subdomain' => $academy->subdomain])],
                $result['message']
            );
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى');
        }
    }

    /**
     * Leave a circle
     */
    public function leaveCircle(Request $request, $subdomain, $circleId): JsonResponse
    {
        $user = Auth::user();
        $academy = $user->academy;

        $circle = QuranCircle::where('academy_id', $academy->id)
            ->where('id', $circleId)
            ->first();

        if (! $circle) {
            return $this->notFound('Circle not found');
        }

        try {
            $result = $this->circleEnrollmentService->leave($user, $circle);

            if (! $result['success']) {
                return $this->error($result['error'], 400);
            }

            return $this->success(
                ['redirect_url' => route('student.quran-circles', ['subdomain' => $academy->subdomain])],
                $result['message']
            );
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء إلغاء التسجيل. يرجى المحاولة مرة أخرى');
        }
    }

    public function quranTeachers(): View
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get student's active/pending subscriptions mapped by teacher ID
        // Prioritize subscriptions with individual circles when multiple exist for same teacher
        $subscriptions = QuranSubscription::where('quran_subscriptions.student_id', $user->id)
            ->where('quran_subscriptions.academy_id', $academy->id)
            ->whereIn('quran_subscriptions.status', ['active', 'pending'])
            ->leftJoin('quran_individual_circles', 'quran_subscriptions.id', '=', 'quran_individual_circles.subscription_id')
            ->select('quran_subscriptions.*')
            ->orderByRaw('quran_individual_circles.id IS NOT NULL DESC')
            ->orderBy('quran_subscriptions.created_at', 'desc')
            ->with(['package', 'sessions', 'individualCircle'])
            ->get();

        // Group by teacher and take first (prioritized) subscription for each
        $subscriptionsByTeacherId = $subscriptions
            ->groupBy('quran_teacher_id')
            ->map(fn ($group) => $group->first());

        // Build query for Quran teachers with filters
        $query = QuranTeacherProfile::where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true));

        // Apply search filter
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Apply experience filter
        if (request('experience')) {
            $experience = request('experience');
            if ($experience === '1-3') {
                $query->whereBetween('teaching_experience_years', [1, 3]);
            } elseif ($experience === '3-5') {
                $query->whereBetween('teaching_experience_years', [3, 5]);
            } elseif ($experience === '5-10') {
                $query->whereBetween('teaching_experience_years', [5, 10]);
            } elseif ($experience === '10+') {
                $query->where('teaching_experience_years', '>=', 10);
            }
        }

        // Apply gender filter (via user relationship)
        if (request('gender')) {
            $query->whereHas('user', function ($userQuery) {
                $userQuery->where('gender', request('gender'));
            });
        }

        // Apply schedule days filter
        if (request('schedule_days') && is_array(request('schedule_days'))) {
            $query->where(function ($q) {
                foreach (request('schedule_days') as $day) {
                    $q->orWhereJsonContains('available_days', $day);
                }
            });
        }

        // Get all active and approved Quran teachers for this academy
        $teacherIds = $subscriptionsByTeacherId->keys()->toArray() ?: [0];
        $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
        $quranTeachers = $query
            ->with(['user', 'quranCircles', 'quranSessions'])
            ->withCount(['quranSessions as total_sessions'])
            ->orderByRaw("CASE WHEN user_id IN ({$placeholders}) THEN 0 ELSE 1 END", $teacherIds)
            ->orderBy('rating', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Calculate additional stats and subscription info for each teacher
        $quranTeachers->getCollection()->transform(function ($teacher) use ($subscriptionsByTeacherId) {
            // Check if student is subscribed to this teacher
            $teacher->my_subscription = $subscriptionsByTeacherId->get($teacher->user_id);
            $teacher->is_subscribed = $teacher->my_subscription !== null;

            // Count active students from subscriptions
            $activeStudents = QuranSubscription::where('quran_teacher_id', $teacher->user_id)
                ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                ->distinct('student_id')
                ->count();

            $teacher->active_students_count = $activeStudents;

            // Get average rating from subscriptions reviews
            $averageRating = QuranSubscription::where('quran_teacher_id', $teacher->user_id)
                ->whereNotNull('rating')
                ->avg('rating');

            $teacher->average_rating = $averageRating ? round($averageRating, 1) : null;

            return $teacher;
        });

        // Count of active subscriptions for stats box
        $activeSubscriptionsCount = $subscriptionsByTeacherId->count();

        // Get available packages for this academy
        $availablePackages = QuranPackage::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        return view('student.quran-teachers', compact(
            'quranTeachers',
            'activeSubscriptionsCount',
            'availablePackages'
        ));
    }
}
