<?php

namespace App\Http\Controllers;

use App\Enums\Country;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSession;
use App\Models\AcademicSubject;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\Payment;
use App\Models\QuranCircle;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\RecordedCourse;
use App\Models\StudentProfile;
use App\Services\Attendance\InteractiveReportService;
use App\Services\CircleEnrollmentService;
use App\Services\HomeworkService;
use App\Services\Reports\InteractiveCourseReportService;
use App\Services\StudentDashboardService;
use App\Services\StudentProfileService;
use App\Services\StudentSearchService;
use App\Services\StudentStatisticsService;
use App\Services\StudentSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StudentProfileController extends Controller
{
    public function __construct(
        protected StudentDashboardService $dashboardService,
        protected StudentStatisticsService $statisticsService,
        protected StudentProfileService $profileService,
        protected CircleEnrollmentService $circleEnrollmentService,
        protected StudentSubscriptionService $subscriptionService,
        protected StudentSearchService $searchService,
        protected InteractiveCourseReportService $interactiveReportService,
        protected InteractiveReportService $attendanceReportService,
        protected HomeworkService $homeworkService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Load dashboard data using service
        $dashboardData = $this->dashboardService->loadDashboardData($user);

        // Get academic private sessions (still inline for now)
        $academicPrivateSessions = AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->with(['academicTeacher', 'academicPackage'])
            ->get();

        // Get recent academic sessions for each subscription
        foreach ($academicPrivateSessions as $subscription) {
            $subscription->recentSessions = AcademicSession::where('academic_subscription_id', $subscription->id)
                ->orderBy('scheduled_at', 'desc')
                ->limit(5)
                ->get();
        }

        // Calculate statistics using service
        $stats = $this->statisticsService->calculate($user);

        return view('student.profile', [
            'quranCircles' => $dashboardData['circles'],
            'quranPrivateSessions' => $dashboardData['privateSessions'],
            'quranTrialRequests' => $dashboardData['trialRequests'],
            'interactiveCourses' => $dashboardData['interactiveCourses'],
            'recordedCourses' => $dashboardData['recordedCourses'],
            'academicPrivateSessions' => $academicPrivateSessions,
            'stats' => $stats,
        ]);
    }

    public function edit()
    {
        $user = Auth::user();
        $studentProfile = $this->profileService->getOrCreateProfile($user);

        if (!$studentProfile) {
            return redirect()->route('student.profile')
                ->with('error', 'لم يتم العثور على الملف الشخصي للطالب. يرجى التواصل مع الدعم الفني.');
        }

        $gradeLevels = $this->profileService->getGradeLevels($user);
        $countries = Country::toArray();

        return view('student.edit-profile', compact('studentProfile', 'gradeLevels', 'countries'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $studentProfile = $this->profileService->getOrCreateProfile($user);

        if (!$studentProfile) {
            return redirect()->back()
                ->with('error', 'لم يتم العثور على الملف الشخصي للطالب. يرجى التواصل مع الدعم الفني.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'nationality' => 'nullable|string|in:' . implode(',', array_keys(Country::toArray())),
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:20',
            'grade_level_id' => 'nullable|exists:academic_grade_levels,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $this->profileService->updateProfile(
            $studentProfile,
            $validated,
            $request->file('avatar')
        );

        return redirect()->route('student.profile', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy'])
            ->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }

    public function subscriptions()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get individual Quran subscriptions (1-to-1 sessions with teacher)
        $individualQuranSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('subscription_type', 'individual')
            ->with(['quranTeacher', 'package', 'individualCircle', 'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get group Quran subscriptions (group circle sessions)
        $groupQuranSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('subscription_type', ['group', 'circle'])
            ->with(['quranTeacher', 'package', 'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get circles the student is enrolled in (for group subscriptions context)
        $enrolledCircles = QuranCircle::where('academy_id', $academy->id)
            ->whereHas('students', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with(['quranTeacher', 'students'])
            ->get();

        // Map group subscriptions to their circles
        $groupQuranSubscriptions->each(function ($subscription) use ($enrolledCircles) {
            $subscription->circle = $enrolledCircles->first(function ($circle) use ($subscription) {
                return $circle->quran_teacher_id === $subscription->quran_teacher_id;
            });
        });

        $quranTrialRequests = QuranTrialRequest::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher', 'trialSession'])
            ->orderBy('created_at', 'desc')
            ->get();

        $courseEnrollments = InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with(['assignedTeacher', 'enrollments' => function ($query) use ($user) {
                $query->where('student_id', $user->id);
            }])
            ->get();

        // Get academic subscriptions
        $academicSubscriptions = AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher.user', 'subject', 'gradeLevel', 'academicPackage'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.subscriptions', compact(
            'individualQuranSubscriptions',
            'groupQuranSubscriptions',
            'enrolledCircles',
            'quranTrialRequests',
            'courseEnrollments',
            'academicSubscriptions'
        ));
    }

    public function payments(Request $request)
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Fetch all payments for the current user
        $paymentsQuery = Payment::where('user_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['subscription'])
            ->orderBy('payment_date', 'desc');

        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $paymentsQuery->where('status', $request->status);
        }

        if ($request->has('date_from') && !empty($request->date_from)) {
            $paymentsQuery->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $paymentsQuery->whereDate('payment_date', '<=', $request->date_to);
        }

        $payments = $paymentsQuery->paginate(15);

        // Calculate statistics
        $stats = [
            'total_payments' => Payment::where('user_id', $user->id)
                ->where('academy_id', $academy->id)
                ->count(),
            'successful_payments' => Payment::where('user_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('status', 'completed')
                ->count(),
        ];

        return view('student.payments', compact('payments', 'stats'));
    }

    /**
     * Toggle auto-renewal for a subscription
     */
    public function toggleAutoRenew(Request $request, string $subdomain, string $type, string $id)
    {
        $user = Auth::user();
        $subdomain = $user->academy->subdomain ?? 'itqan-academy';

        $result = $this->subscriptionService->toggleAutoRenew($user, $type, $id);

        if (!$result['success']) {
            return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                ->with('error', $result['error']);
        }

        return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
            ->with('success', $result['message']);
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Request $request, string $subdomain, string $type, string $id)
    {
        $user = Auth::user();
        $subdomain = $user->academy->subdomain ?? 'itqan-academy';

        $result = $this->subscriptionService->cancelSubscription($user, $type, $id);

        if (!$result['success']) {
            return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                ->with('error', $result['error']);
        }

        return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
            ->with('success', $result['message']);
    }

    public function certificates()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // This would fetch actual certificates from your system
        $certificates = collect([
            [
                'id' => 1,
                'title' => 'شهادة إتمام دورة القرآن الكريم',
                'course' => 'دائرة الحفظ المتقدم',
                'teacher' => 'الأستاذ أحمد محمد',
                'date' => now()->subDays(30),
                'status' => 'issued',
            ],
            [
                'id' => 2,
                'title' => 'شهادة إتمام كورس الرياضيات',
                'course' => 'الرياضيات للصف الثالث',
                'teacher' => 'الأستاذة ليلى محمد',
                'date' => now()->subDays(15),
                'status' => 'issued',
            ],
        ]);

        return view('student.certificates', compact('certificates'));
    }

    public function quranCircles(Request $request)
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

            $englishDays = array_map(function($arabicDay) use ($arabicToEnglish) {
                return $arabicToEnglish[$arabicDay] ?? $arabicDay;
            }, $request->schedule_days);

            $query->where(function($q) use ($englishDays) {
                foreach ($englishDays as $day) {
                    $q->orWhereJsonContains('schedule_days', $day);
                }
            });
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name_ar', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('name_en', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('description_ar', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('description_en', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Sort: Enrolled circles first, then by creation date
        $circles = $query->get()->sortByDesc(function($circle) use ($enrolledCircleIds) {
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
    public function showCircle(Request $request, $subdomain, $circleId)
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
                     $circle->enrollment_status === 'open' &&
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
                ->where('status', 'completed')
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
                ->whereIn('status', ['active', 'pending'])
                ->with(['package', 'quranTeacherUser'])
                ->first();

            // If no subscription exists for this enrollment, create one
            if (!$subscription) {
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
                    'currency' => $circle->currency ?? 'SAR',
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
    public function showIndividualCircle(Request $request, $subdomain, $circleId)
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
    public function enrollInCircle(Request $request, $subdomain, $circleId)
    {
        $user = Auth::user();
        $academy = $user->academy;

        $circle = QuranCircle::where('academy_id', $academy->id)
            ->where('id', $circleId)
            ->first();

        if (!$circle) {
            return response()->json(['error' => 'Circle not found'], 404);
        }

        try {
            $result = $this->circleEnrollmentService->enroll($user, $circle);

            if (!$result['success']) {
                return response()->json(['error' => $result['error']], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'redirect_url' => route('student.quran-circles', ['subdomain' => $academy->subdomain]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى',
            ], 500);
        }
    }

    /**
     * Leave a circle
     */
    public function leaveCircle(Request $request, $subdomain, $circleId)
    {
        $user = Auth::user();
        $academy = $user->academy;

        $circle = QuranCircle::where('academy_id', $academy->id)
            ->where('id', $circleId)
            ->first();

        if (!$circle) {
            return response()->json(['error' => 'Circle not found'], 404);
        }

        try {
            $result = $this->circleEnrollmentService->leave($user, $circle);

            if (!$result['success']) {
                return response()->json(['error' => $result['error']], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'redirect_url' => route('student.quran-circles', ['subdomain' => $academy->subdomain]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'حدث خطأ أثناء إلغاء التسجيل. يرجى المحاولة مرة أخرى',
            ], 500);
        }
    }

    public function quranTeachers()
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
            ->map(fn($group) => $group->first());

        // Build query for Quran teachers with filters
        $query = QuranTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved');

        // Apply search filter
        if (request('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
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
            $query->whereHas('user', function($userQuery) {
                $userQuery->where('gender', request('gender'));
            });
        }

        // Apply schedule days filter
        if (request('schedule_days') && is_array(request('schedule_days'))) {
            $query->where(function($q) {
                foreach (request('schedule_days') as $day) {
                    $q->orWhereJsonContains('available_days', $day);
                }
            });
        }

        // Get all active and approved Quran teachers for this academy
        $quranTeachers = $query
            ->with(['user', 'quranCircles', 'quranSessions'])
            ->withCount(['quranSessions as total_sessions'])
            ->orderByRaw('CASE WHEN user_id IN (' . implode(',', $subscriptionsByTeacherId->keys()->toArray() ?: [0]) . ') THEN 0 ELSE 1 END')
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
                ->where('status', 'active')
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

    public function interactiveCourses(Request $request)
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Ensure user has a student profile
        if (!$user->studentProfile) {
            return redirect()->route('student.profile')
                ->with('error', 'يجب إكمال الملف الشخصي للطالب أولاً');
        }

        $studentId = $user->studentProfile->id;

        // Get all interactive courses with student enrollment data
        $query = InteractiveCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->where('enrollment_deadline', '>=', now()->toDateString())
            ->with(['assignedTeacher', 'subject', 'gradeLevel', 'enrollments' => function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            }]);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('description', 'LIKE', '%'.$request->search.'%');
            });
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', $request->grade_level_id);
        }

        // Order by enrollment status (enrolled courses first), then by creation date
        $allCourses = $query->get()->sortByDesc(function ($course) {
            return $course->enrollments->isNotEmpty() ? 1 : 0;
        })->values();

        // Paginate manually
        $perPage = 12;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        $courses = new \Illuminate\Pagination\LengthAwarePaginator(
            $allCourses->slice($offset, $perPage),
            $allCourses->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Count enrolled courses for the stats
        $enrolledCoursesCount = InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function ($query) use ($studentId) {
                $query->where('student_id', $studentId)
                      ->whereIn('enrollment_status', ['enrolled', 'completed']);
            })
            ->count();

        // Get filter options (only subjects and grade levels that have interactive courses)
        $subjects = AcademicSubject::where('academy_id', $academy->id)
            ->whereHas('interactiveCourses', function ($query) {
                $query->where('is_published', true)
                      ->where('enrollment_deadline', '>=', now()->toDateString());
            })
            ->orderBy('name')
            ->get();

        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)
            ->whereHas('interactiveCourses', function ($query) {
                $query->where('is_published', true)
                      ->where('enrollment_deadline', '>=', now()->toDateString());
            })
            ->orderBy('name')
            ->get();

        return view('student.interactive-courses', compact(
            'courses',
            'enrolledCoursesCount',
            'subjects',
            'gradeLevels'
        ));
    }

    public function showInteractiveCourse($subdomain, $course)
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'User not authenticated');
        }

        // Get academy from subdomain parameter
        $academy = \App\Models\Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy (security check)
        if ($user->academy_id !== $academy->id) {
            abort(403, 'Access denied to this academy');
        }

        // Find the course and ensure it belongs to the academy
        $courseModel = InteractiveCourse::where('id', $course)
            ->where('academy_id', $academy->id)
            ->firstOrFail();

        // Determine user type and permissions
        $userType = $user->user_type;
        $isTeacher = $userType === 'academic_teacher';
        $isStudent = $userType === 'student';

        $student = $isStudent ? $user->studentProfile : null;

        // Load course relationships with student-specific session data
        $courseModel->load([
            'assignedTeacher.user',
            'subject',
            'gradeLevel',
            'enrollments.student.user',
            'sessions' => function ($query) use ($student) {
                $query->with([
                    'attendances' => function ($q) use ($student) {
                        if ($student) {
                            $q->where('student_id', $student->id);
                        }
                    },
                    'homework.submissions' => function ($q) use ($student) {
                        if ($student) {
                            $q->where('student_id', $student->id);
                        }
                    },
                    // Meeting info is stored directly on the session (meeting_link, meeting_id, etc.)
                ])->orderBy('scheduled_at');
            },
        ]);

        // For teachers: Check if they have access to this course (either created or assigned)
        if ($isTeacher) {
            $teacherProfile = $user->academicTeacherProfile;
            $isAssignedTeacher = false;
            $isCreatedByCourse = $courseModel->created_by === $user->id;

            // Check if teacher is assigned to course
            if ($teacherProfile) {
                $isAssignedTeacher = $courseModel->assigned_teacher_id === $teacherProfile->id;
            } else {
                // If no teacher profile, check if assigned_teacher_id references a teacher with this user_id
                if ($courseModel->assigned_teacher_id) {
                    $assignedTeacher = \App\Models\AcademicTeacher::find($courseModel->assigned_teacher_id);
                    if ($assignedTeacher && $assignedTeacher->user_id === $user->id) {
                        $isAssignedTeacher = true;
                    }
                }
            }

            if (! $isAssignedTeacher && ! $isCreatedByCourse) {
                abort(403, 'Access denied - not assigned to or creator of this course');
            }
        }

        // For students: Check enrollment and get enrollment data
        $isEnrolled = false;
        $enrollmentStats = [];

        if ($isStudent) {
            // Ensure user has a student profile
            if (!$user->studentProfile) {
                abort(403, 'يجب إكمال الملف الشخصي للطالب أولاً');
            }

            $studentId = $user->studentProfile->id;
            $enrollment = $courseModel->enrollments->where('student_id', $studentId)->first();

            // Check if student is enrolled with active status
            if ($enrollment && in_array($enrollment->enrollment_status, ['enrolled', 'completed'])) {
                $isEnrolled = true;
            }

            // Always show enrollment stats for students (whether enrolled or not)
            $enrollmentStats = [
                'total_enrolled' => $courseModel->enrollments->count(),
                'available_spots' => max(0, $courseModel->max_students - $courseModel->enrollments->count()),
                'enrollment_deadline' => $courseModel->enrollment_deadline,
            ];
        }

        // For teachers: Get additional teacher data
        $teacherData = [];
        if ($isTeacher) {
            $teacherData = [
                'total_students' => $courseModel->enrollments->count(),
                'total_sessions' => $courseModel->sessions->count(),
                'completed_sessions' => $courseModel->sessions->where('status', 'completed')->count(),
                'upcoming_sessions' => $courseModel->sessions->where('session_date', '>', now())->count(),
            ];
        }

        // Separate sessions into upcoming and past
        $now = now();
        $upcomingSessions = $courseModel->sessions
            ->filter(function ($session) use ($now) {
                // Use the scheduled_at accessor which handles date/time concatenation
                $scheduledDateTime = $session->scheduled_at;
                return $scheduledDateTime && ($scheduledDateTime->gte($now) || $session->status === 'in-progress');
            })
            ->values();

        $pastSessions = $courseModel->sessions
            ->filter(function ($session) use ($now) {
                // Use the scheduled_at accessor which handles date/time concatenation
                $scheduledDateTime = $session->scheduled_at;
                return $scheduledDateTime && $scheduledDateTime->lt($now) && $session->status !== 'in-progress';
            })
            ->sortByDesc(function ($session) {
                return $session->scheduled_at ? $session->scheduled_at->timestamp : 0;
            })
            ->values();

        // Choose view based on user type
        $viewName = $isTeacher ? 'teacher.interactive-course-detail' : 'student.interactive-course-detail';

        return view($viewName, [
            'course' => $courseModel,
            'isEnrolled' => $isEnrolled,
            'enrollmentStats' => $enrollmentStats,
            'teacherData' => $teacherData,
            'userType' => $userType,
            'isTeacher' => $isTeacher,
            'isStudent' => $isStudent,
            'upcomingSessions' => $upcomingSessions,
            'pastSessions' => $pastSessions,
            'student' => $student,
        ]);
    }

    public function showInteractiveCourseSession($subdomain, $sessionId)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'User not authenticated');
        }

        // Get academy from subdomain parameter
        $academy = \App\Models\Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy (security check)
        if ($user->academy_id !== $academy->id) {
            abort(403, 'Access denied to this academy');
        }

        // Find the session with relationships
        $session = \App\Models\InteractiveCourseSession::with([
            'course.assignedTeacher.user',
            'course.subject',
            'course.gradeLevel',
            'course.enrolledStudents.student.user',  // Load enrolled students with their User data
            'homework',
            'attendances',  // Load all attendances, filter later by student_profile id
            'meetingAttendances',  // Load meeting attendance data
            // Note: studentReports relationship doesn't exist on InteractiveCourseSession yet
            // Meeting info is stored directly on the session (meeting_link, meeting_id, etc.)
        ])->findOrFail($sessionId);

        // Ensure session's course belongs to the academy
        if ($session->course->academy_id !== $academy->id) {
            abort(403, 'Access denied to this session');
        }

        // Check user permission: either enrolled student or assigned teacher
        $isTeacher = false;
        $enrollment = null;

        if ($user->isAcademicTeacher()) {
            // Check if user is the assigned teacher for this course
            $teacherProfile = $user->academicTeacherProfile;
            if ($teacherProfile && $session->course->assigned_teacher_id === $teacherProfile->id) {
                $isTeacher = true;
            } else {
                abort(403, 'You are not the assigned teacher for this course');
            }
        } elseif ($user->isStudent()) {
            // Get the student profile
            $studentProfile = $user->studentProfile;
            if (!$studentProfile) {
                abort(403, 'Student profile not found');
            }

            // Verify enrollment in the course
            $enrollment = \App\Models\InteractiveCourseEnrollment::where([
                'course_id' => $session->course_id,
                'student_id' => $studentProfile->id,
                'enrollment_status' => 'enrolled'
            ])->first();

            if (!$enrollment) {
                abort(403, 'You must be enrolled in this course to view sessions');
            }
        } else {
            abort(403, 'Access denied');
        }

        $student = $isTeacher ? null : $user->studentProfile;

        // Get student-specific data only for students
        $attendance = null;
        $homeworkSubmission = null;

        if (!$isTeacher && $student) {
            // Get attendance record for this student
            $attendance = $session->attendances->where('student_id', $student->id)->first();

            // Get homework submission if homework exists
            if ($session->homework()->count() > 0) {
                $homework = $session->homework()->first();
                if ($homework) {
                    $homeworkSubmission = $homework->submissions()
                        ->where('student_id', $student->id)
                        ->first();
                }
            }
        }

        // Determine view type for conditional rendering
        $viewType = $isTeacher ? 'teacher' : 'student';

        // Return appropriate view based on user role
        if ($isTeacher) {
            return view('teacher.interactive-course-sessions.show', compact(
                'session',
                'viewType'
            ));
        } else {
            return view('student.interactive-course-sessions.show', compact(
                'session',
                'attendance',
                'homeworkSubmission',
                'student',
                'enrollment',
                'viewType'
            ));
        }
    }

    /**
     * Display comprehensive report for an interactive course (Teacher view)
     */
    public function interactiveCourseReport($subdomain, $courseId)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'User not authenticated');
        }

        // Verify user is an academic teacher
        if (!$user->isAcademicTeacher()) {
            abort(403, 'Access denied - teachers only');
        }

        // Get academy from subdomain
        $academy = \App\Models\Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy
        if ($user->academy_id !== $academy->id) {
            abort(403, 'Access denied to this academy');
        }

        // Load course with relationships
        $course = \App\Models\InteractiveCourse::with([
            'subject',
            'gradeLevel',
            'assignedTeacher.user',
            'enrollments.student.user',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            },
            'sessions.studentReports',
        ])->findOrFail($courseId);

        // Verify teacher is assigned to this course
        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $course->assigned_teacher_id !== $teacherProfile->id) {
            abort(403, 'You are not the assigned teacher for this course');
        }

        // Get report service
        $reportService = $this->interactiveReportService;

        // Generate comprehensive report using DTOs
        $reportData = $reportService->getCourseOverviewReport($course);

        return view('reports.interactive-course.course-overview', $reportData);
    }

    /**
     * Display individual student report for an interactive course (Teacher view)
     */
    public function interactiveCourseStudentReport($subdomain, $courseId, $studentId)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'User not authenticated');
        }

        // Verify user is an academic teacher
        if (!$user->isAcademicTeacher()) {
            abort(403, 'Access denied - teachers only');
        }

        // Get academy from subdomain
        $academy = \App\Models\Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy
        if ($user->academy_id !== $academy->id) {
            abort(403, 'Access denied to this academy');
        }

        // Load course with relationships
        $course = \App\Models\InteractiveCourse::with([
            'subject',
            'gradeLevel',
            'assignedTeacher.user',
            'enrollments.student.user',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            },
            'sessions.studentReports',
        ])->findOrFail($courseId);

        // Verify teacher is assigned to this course
        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $course->assigned_teacher_id !== $teacherProfile->id) {
            abort(403, 'You are not the assigned teacher for this course');
        }

        // Get student
        $student = \App\Models\User::findOrFail($studentId);

        // Verify student is enrolled in this course
        $enrollment = $course->enrollments->first(function ($e) use ($student) {
            return $e->student?->user?->id === $student->id || $e->student_id === $student->id;
        });

        if (!$enrollment) {
            abort(404, 'الطالب غير مسجل في هذا الكورس');
        }

        // Get report service
        $reportService = $this->attendanceReportService;

        // Calculate metrics for this specific student
        $performance = $reportService->calculatePerformance($course, $student->id);
        $attendance = $reportService->calculateAttendance($course, $student->id);
        $progress = $reportService->calculateProgress($course);

        // Add homework metrics for this student
        $studentReports = $course->sessions->flatMap(function ($session) use ($student) {
            return $session->studentReports->where('student_id', $student->id);
        });

        $homeworkAssigned = $course->sessions->filter(function ($session) {
            return !empty($session->homework_description);
        })->count();

        $homeworkSubmitted = $studentReports->whereNotNull('homework_submitted_at')->count();
        $homeworkCompletionRate = $homeworkAssigned > 0 ? round(($homeworkSubmitted / $homeworkAssigned) * 100) : 0;

        $progress['homework_assigned'] = $homeworkAssigned;
        $progress['homework_submitted'] = $homeworkSubmitted;
        $progress['homework_completion_rate'] = $homeworkCompletionRate;

        return view('reports.interactive-course.teacher-student-report', [
            'course' => $course,
            'student' => $student,
            'enrollment' => $enrollment,
            'performance' => $performance,
            'attendance' => $attendance,
            'progress' => $progress,
        ]);
    }

    /**
     * Display comprehensive report for an interactive course (Student view)
     */
    public function studentInteractiveCourseReport($subdomain, $courseId)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'User not authenticated');
        }

        // Verify user is a student
        if (!$user->isStudent()) {
            abort(403, 'Access denied - students only');
        }

        // Get academy from subdomain
        $academy = \App\Models\Academy::where('subdomain', $subdomain)->firstOrFail();

        // Ensure user belongs to this academy
        if ($user->academy_id !== $academy->id) {
            abort(403, 'Access denied to this academy');
        }

        // Load course with relationships
        $course = \App\Models\InteractiveCourse::with([
            'subject',
            'gradeLevel',
            'assignedTeacher.user',
            'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            },
            'sessions.studentReports',
        ])->findOrFail($courseId);

        // Verify student is enrolled in this course
        $studentProfile = $user->studentProfile;
        if (!$studentProfile) {
            abort(403, 'Student profile not found');
        }

        $enrollment = \App\Models\InteractiveCourseEnrollment::where([
            'course_id' => $course->id,
            'student_id' => $studentProfile->id,
            'enrollment_status' => 'enrolled'
        ])->first();

        if (!$enrollment) {
            abort(403, 'You must be enrolled in this course to view the report');
        }

        // Get report service
        $reportService = $this->interactiveReportService;

        // Generate student report using DTOs
        $reportData = $reportService->getStudentReport($course, $studentProfile);

        return view('reports.interactive-course.student-report', $reportData);
    }

    public function addInteractiveSessionFeedback(Request $request, $subdomain, $sessionId)
    {
        $validated = $request->validate([
            'feedback' => 'required|string|max:1000'
        ]);

        $user = Auth::user();
        $session = \App\Models\InteractiveCourseSession::findOrFail($sessionId);

        // Verify enrollment and session completion
        $studentProfile = $user->studentProfile;
        if (!$studentProfile) {
            return response()->json(['success' => false, 'message' => 'Student profile not found'], 403);
        }

        $enrollment = \App\Models\InteractiveCourseEnrollment::where([
            'course_id' => $session->course_id,
            'student_id' => $studentProfile->id,
            'enrollment_status' => 'enrolled'
        ])->firstOrFail();

        if ($session->status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'لا يمكن إضافة تقييم لجلسة لم تكتمل'], 400);
        }

        // Update session with student feedback
        $session->update([
            'student_feedback' => $validated['feedback']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال تقييمك بنجاح'
        ]);
    }

    public function updateInteractiveSessionContent(Request $request, $subdomain, $sessionId)
    {
        $validated = $request->validate([
            'lesson_content' => 'nullable|string|max:5000',
        ]);

        $user = Auth::user();
        $session = \App\Models\InteractiveCourseSession::findOrFail($sessionId);

        // Verify teacher is assigned to this course
        if (!$user->isAcademicTeacher()) {
            return response()->json(['success' => false, 'message' => 'غير مسموح لك بالوصول'], 403);
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $session->course->assigned_teacher_id !== $teacherProfile->id) {
            return response()->json(['success' => false, 'message' => 'غير مسموح لك بتعديل هذه الجلسة'], 403);
        }

        // Update session
        $session->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ المحتوى بنجاح',
            'session' => $session->fresh()
        ]);
    }

    public function assignInteractiveSessionHomework(Request $request, $subdomain, $sessionId)
    {
        $validated = $request->validate([
            'homework_description' => 'required|string|max:2000',
            'homework_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240'
        ]);

        $user = Auth::user();
        $session = \App\Models\InteractiveCourseSession::findOrFail($sessionId);

        // Verify teacher is assigned to this course
        if (!$user->isAcademicTeacher()) {
            abort(403, 'غير مسموح لك بالوصول');
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $session->course->assigned_teacher_id !== $teacherProfile->id) {
            abort(403, 'غير مسموح لك بتعيين واجب لهذه الجلسة');
        }

        // Handle file upload
        $homeworkFilePath = null;
        if ($request->hasFile('homework_file')) {
            $homeworkFilePath = $request->file('homework_file')->store(
                "tenants/{$session->course->academy_id}/interactive-homework",
                'public'
            );
        }

        // Update session with homework
        $session->update([
            'homework_description' => $validated['homework_description'],
            'homework_file' => $homeworkFilePath,
        ]);

        // Return JSON for AJAX requests, redirect for regular form submissions
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'تم تعيين الواجب بنجاح']);
        }

        return redirect()->back()->with('success', 'تم تعيين الواجب بنجاح');
    }

    /**
     * Update homework for interactive session (Teacher)
     */
    public function updateInteractiveSessionHomework(Request $request, $subdomain, $sessionId)
    {
        $validated = $request->validate([
            'homework_description' => 'required|string|max:2000',
            'homework_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240'
        ]);

        $user = Auth::user();
        $session = \App\Models\InteractiveCourseSession::findOrFail($sessionId);

        // Verify teacher is assigned to this course
        if (!$user->isAcademicTeacher()) {
            abort(403, 'غير مسموح لك بالوصول');
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $session->course->assigned_teacher_id !== $teacherProfile->id) {
            abort(403, 'غير مسموح لك بتحديث واجب هذه الجلسة');
        }

        // Handle file upload
        $homeworkFilePath = $session->homework_file; // Keep existing file if no new file uploaded
        if ($request->hasFile('homework_file')) {
            // Delete old file if exists
            if ($session->homework_file) {
                Storage::disk('public')->delete($session->homework_file);
            }

            $homeworkFilePath = $request->file('homework_file')->store(
                "tenants/{$session->course->academy_id}/interactive-homework",
                'public'
            );
        }

        // Update session with homework
        $session->update([
            'homework_description' => $validated['homework_description'],
            'homework_file' => $homeworkFilePath,
        ]);

        // Return JSON for AJAX requests, redirect for regular form submissions
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'تم تحديث الواجب بنجاح']);
        }

        return redirect()->back()->with('success', 'تم تحديث الواجب بنجاح');
    }

    public function submitInteractiveCourseHomework(Request $request, $subdomain, $sessionId)
    {
        $validated = $request->validate([
            'homework_id' => 'required|exists:interactive_course_homework,id',
            'answer_text' => 'nullable|string',
            'files.*' => 'nullable|file|max:10240' // 10MB max
        ]);

        $user = Auth::user();
        $session = \App\Models\InteractiveCourseSession::findOrFail($sessionId);

        // Verify enrollment
        $enrollment = \App\Models\InteractiveCourseEnrollment::where([
            'course_id' => $session->course_id,
            'student_id' => $user->id,
            'status' => 'active'
        ])->firstOrFail();

        $homework = \App\Models\InteractiveCourseHomework::findOrFail($validated['homework_id']);
        $student = $user->studentProfile;

        // Use existing HomeworkService if available
        if (class_exists(\App\Services\HomeworkService::class)) {
            $this->homeworkService->submitHomework(
                $homework,
                $student,
                $validated
            );
        } else {
            // Fallback: Direct submission creation
            $submissionData = [
                'homework_id' => $homework->id,
                'student_id' => $student->id,
                'answer_text' => $validated['answer_text'] ?? null,
                'status' => 'pending',
                'submitted_at' => now()
            ];

            // Handle file uploads if present
            if ($request->hasFile('files')) {
                $files = [];
                foreach ($request->file('files') as $file) {
                    $path = $file->store('homework-submissions', 'public');
                    $files[] = $path;
                }
                $submissionData['files'] = json_encode($files);
            }

            \DB::table('interactive_course_homework_submissions')->updateOrInsert(
                [
                    'homework_id' => $homework->id,
                    'student_id' => $student->id
                ],
                $submissionData
            );
        }

        return back()->with('success', 'Homework submitted successfully');
    }

    public function academicTeachers(Request $request)
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get student's academic subscriptions with teacher info
        $mySubscriptions = AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->with(['academicTeacher'])
            ->get();

        // Get active subscriptions count
        $activeSubscriptionsCount = $mySubscriptions->count();

        // Get all subjects and grade levels for filters
        $subjects = \App\Models\AcademicSubject::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $gradeLevels = \App\Models\AcademicGradeLevel::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Build teachers query with filters
        $query = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['user']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('subject')) {
            $query->whereJsonContains('subject_ids', (int) $request->subject);
        }

        if ($request->filled('grade_level')) {
            $query->whereJsonContains('grade_level_ids', (int) $request->grade_level);
        }

        if ($request->filled('experience')) {
            $experienceRange = $request->experience;
            if ($experienceRange === '1-3') {
                $query->whereBetween('teaching_experience_years', [1, 3]);
            } elseif ($experienceRange === '3-5') {
                $query->whereBetween('teaching_experience_years', [3, 5]);
            } elseif ($experienceRange === '5-10') {
                $query->whereBetween('teaching_experience_years', [5, 10]);
            } elseif ($experienceRange === '10+') {
                $query->where('teaching_experience_years', '>=', 10);
            }
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // Get paginated teachers
        $academicTeachers = $query->withCount(['interactiveCourses as active_courses_count'])
            ->orderBy('rating', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->appends($request->except('page'));

        // Get all active packages for calculating minimum price
        $allPackages = \App\Models\AcademicPackage::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->get();

        // Calculate additional stats for each teacher
        $academicTeachers->getCollection()->transform(function ($teacher) use ($mySubscriptions, $allPackages) {
            // Mark if student is subscribed to this teacher
            $teacher->is_subscribed = $mySubscriptions->where('teacher_id', $teacher->id)->isNotEmpty();
            $teacher->my_subscription = $mySubscriptions->where('teacher_id', $teacher->id)->first();

            // Calculate average rating from interactive courses or use profile rating
            $teacher->average_rating = $teacher->rating ?? 4.8;

            // Get active students count
            $teacher->students_count = $teacher->total_students ?? 0;

            // Format hourly rate
            $teacher->hourly_rate = $teacher->session_price_individual;

            // Set bio from profile
            $teacher->bio = $teacher->bio_arabic ?? $teacher->notes ?? 'معلم أكاديمي مؤهل متخصص في التدريس';

            // Set experience years
            $teacher->experience_years = $teacher->teaching_experience_years;

            // Set qualification label from education_level
            $educationLabels = ['diploma' => 'دبلوم', 'bachelor' => 'بكالوريوس', 'master' => 'ماجستير', 'phd' => 'دكتوراه', 'other' => 'أخرى'];
            $teacher->qualification = $educationLabels[$teacher->education_level] ?? $teacher->education_level;

            // Calculate minimum price from available packages
            if ($allPackages->count() > 0) {
                $teacher->minimum_price = $allPackages->min('monthly_price');
            }

            return $teacher;
        });

        // Get student's academic subscriptions to show which teachers they're learning with
        // (Replacing AcademicProgress - now using subscriptions with sessions data)
        $academicProgress = AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['academicTeacher', 'subject', 'sessions'])
            ->get();

        return view('student.academic-teachers', compact(
            'academicTeachers',
            'academicProgress',
            'mySubscriptions',
            'activeSubscriptionsCount',
            'subjects',
            'gradeLevels'
        ));
    }

    /**
     * Download certificate for completed course
     */
    public function downloadCertificate(Request $request, $enrollmentId)
    {
        $user = Auth::user();

        // Find the enrollment
        $enrollment = CourseSubscription::where('id', $enrollmentId)
            ->where('student_id', $user->id)
            ->where('status', 'completed')
            ->with(['course', 'student'])
            ->first();

        if (! $enrollment) {
            abort(404, 'Certificate not found or course not completed');
        }

        // Generate certificate (placeholder for now)
        // In a real implementation, you would generate a PDF certificate
        return response()->json([
            'message' => 'Certificate download functionality will be implemented soon',
            'enrollment' => $enrollment->id,
        ]);
    }

    /**
     * Show academic session details for student
     */
    public function showAcademicSession(Request $request, $subdomain, $sessionId)
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Find the academic session
        $session = AcademicSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where('student_id', $user->id)
            ->with([
                'academicTeacher.user',
                'academicSubscription.academicPackage',
                'sessionReports',
                'academy',
            ])
            ->first();

        if (! $session) {
            abort(404, 'Academic session not found');
        }

        return view('student.academic-session-detail', compact('session'));
    }

    /**
     * Show academic subscription details for student
     */
    public function showAcademicSubscription(Request $request, $subdomain, $subscriptionId)
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Find the academic subscription
        $subscription = AcademicSubscription::where('id', $subscriptionId)
            ->where('academy_id', $academy->id)
            ->where('student_id', $user->id)
            ->with([
                'academicTeacher.user',
                'subject',
                'gradeLevel',
                'academicPackage',
                'sessions' => function ($query) {
                    $query->orderBy('scheduled_at');
                },
            ])
            ->first();

        if (! $subscription) {
            abort(404, 'Academic subscription not found');
        }

        // Get sessions for this subscription
        $upcomingSessions = \App\Models\AcademicSession::where('academic_subscription_id', $subscription->id)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->orderBy('scheduled_at')
            ->with(['student', 'academicTeacher'])
            ->get();

        $pastSessions = \App\Models\AcademicSession::where('academic_subscription_id', $subscription->id)
            ->whereIn('status', ['completed', 'cancelled'])
            ->orderByDesc('scheduled_at')
            ->with(['student', 'academicTeacher'])
            ->get();

        // Calculate progress summary from sessions (replacing AcademicProgressService)
        $allSessions = $subscription->sessions()->get();
        $totalSessions = $allSessions->count();
        $completedSessions = $allSessions->where('status', \App\Enums\SessionStatus::COMPLETED)->count();
        $missedSessions = $allSessions->where('status', \App\Enums\SessionStatus::ABSENT)->count();
        $attendanceRate = $subscription->attendance_rate;

        // Get homework/assignment data from session reports
        $sessionReports = \App\Models\AcademicSessionReport::whereIn('session_id', $allSessions->pluck('id'))
            ->where('student_id', $subscription->student_id)
            ->get();
        $totalAssignments = $sessionReports->count();
        $completedAssignments = $sessionReports->whereNotNull('homework_degree')->count();
        $homeworkCompletionRate = $totalAssignments > 0 ? round(($completedAssignments / $totalAssignments) * 100) : 0;
        $overallGrade = $sessionReports->whereNotNull('homework_degree')->avg('homework_degree');
        $overallGrade = $overallGrade ? round($overallGrade * 10) : null; // Convert 0-10 to 0-100

        // Get last and next session dates
        $lastSession = $allSessions->where('status', \App\Enums\SessionStatus::COMPLETED)->sortByDesc('scheduled_at')->first();
        $nextSession = $upcomingSessions->first();

        // Calculate consecutive missed sessions
        $consecutiveMissed = 0;
        $sortedSessions = $allSessions->sortByDesc('scheduled_at');
        foreach ($sortedSessions as $session) {
            if ($session->status === \App\Enums\SessionStatus::ABSENT) {
                $consecutiveMissed++;
            } else {
                break;
            }
        }

        $progressSummary = [
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'sessions_completed' => $completedSessions,
            'sessions_planned' => $totalSessions,
            'sessions_missed' => $missedSessions,
            'progress_percentage' => $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0,
            'attendance_rate' => $attendanceRate,
            'total_assignments' => $totalAssignments,
            'completed_assignments' => $completedAssignments,
            'homework_completion_rate' => $homeworkCompletionRate,
            'overall_grade' => $overallGrade,
            'needs_support' => $attendanceRate < 60 || ($overallGrade !== null && $overallGrade < 60),
            'progress_status' => $attendanceRate >= 80 ? 'ممتاز' : ($attendanceRate >= 60 ? 'جيد' : 'يحتاج تحسين'),
            'engagement_level' => $attendanceRate >= 80 ? 'عالي' : ($attendanceRate >= 60 ? 'متوسط' : 'منخفض'),
            'last_session' => $lastSession?->scheduled_at,
            'next_session' => $nextSession?->scheduled_at,
            'consecutive_missed' => $consecutiveMissed,
        ];

        return view('student.academic-subscription-detail', compact('subscription', 'upcomingSessions', 'pastSessions', 'progressSummary'));
    }

    /**
     * Search for courses, teachers, and content
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        $academy = $user->academy;
        $subdomain = $academy->subdomain ?? 'itqan-academy';
        $query = $request->input('q', '');

        // If empty query, redirect back
        if (empty(trim($query))) {
            return redirect()->route('student.profile', ['subdomain' => $subdomain])
                ->with('error', 'الرجاء إدخال كلمة بحث');
        }

        // Use search service for all searches
        $results = $this->searchService->search($user, $query);
        $totalResults = $this->searchService->getTotalCount($results);

        return view('student.search', [
            'query' => $query,
            'totalResults' => $totalResults,
            'interactiveCourses' => $results['interactive_courses'],
            'recordedCourses' => $results['recorded_courses'],
            'quranTeachers' => $results['quran_teachers'],
            'academicTeachers' => $results['academic_teachers'],
            'quranCircles' => $results['quran_circles'],
            'academy' => $academy,
            'subdomain' => $subdomain,
        ]);
    }
}
