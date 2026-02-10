<?php

namespace App\Http\Controllers;

use App\Enums\QuranLearningLevel;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\TrialRequestStatus;
use App\Enums\UserType;
use App\Models\Academy;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Services\QuranEnrollmentService;
use App\Services\TrialNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UnifiedQuranTeacherController extends Controller
{
    public function __construct(
        protected QuranEnrollmentService $enrollmentService
    ) {}

    /**
     * Display a listing of Quran teachers (Unified for both public and authenticated)
     */
    public function index(Request $request, $subdomain): \Illuminate\View\View
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        $user = Auth::user();
        $isAuthenticated = (bool) $user;

        // Base query (applies to all users)
        $query = QuranTeacherProfile::where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true));

        // Apply filters (same for both)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('experience')) {
            $experience = $request->experience;
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

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('schedule_days') && is_array($request->schedule_days)) {
            $query->where(function ($q) use ($request) {
                foreach ($request->schedule_days as $day) {
                    $q->orWhereJsonContains('available_days', $day);
                }
            });
        }

        // Student-specific data
        $activeSubscriptionsCount = 0;
        $subscriptionsByTeacherId = collect();

        if ($isAuthenticated) {
            // Get student's active/pending subscriptions
            $subscriptions = QuranSubscription::where('quran_subscriptions.student_id', $user->id)
                ->where('quran_subscriptions.academy_id', $academy->id)
                ->whereIn('quran_subscriptions.status', ['active', 'pending'])
                ->leftJoin('quran_individual_circles', 'quran_subscriptions.id', '=', 'quran_individual_circles.subscription_id')
                ->select('quran_subscriptions.*')
                ->orderByRaw('quran_individual_circles.id IS NOT NULL DESC')
                ->orderBy('quran_subscriptions.created_at', 'desc')
                ->with(['package', 'sessions', 'individualCircle'])
                ->get();

            $subscriptionsByTeacherId = $subscriptions
                ->groupBy('quran_teacher_id')
                ->map(fn ($group) => $group->first());

            $activeSubscriptionsCount = $subscriptionsByTeacherId->count();

            // Sort subscribed teachers first for authenticated users
            $teacherIds = $subscriptionsByTeacherId->keys()->toArray() ?: [0];
            $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
            $query->orderByRaw("CASE WHEN user_id IN ({$placeholders}) THEN 0 ELSE 1 END", $teacherIds);
        }

        // Get teachers
        $quranTeachers = $query
            ->with(['user', 'reviews', 'quranCircles', 'quranSessions'])
            ->withCount(['quranSessions as total_sessions'])
            ->orderBy('rating', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Add subscription info for authenticated students
        if ($isAuthenticated) {
            $quranTeachers->getCollection()->transform(function ($teacher) use ($subscriptionsByTeacherId) {
                $teacher->my_subscription = $subscriptionsByTeacherId->get($teacher->user_id);
                $teacher->is_subscribed = $teacher->my_subscription !== null;

                $activeStudents = QuranSubscription::where('quran_teacher_id', $teacher->user_id)
                    ->where('status', SessionSubscriptionStatus::ACTIVE->value)
                    ->distinct('student_id')
                    ->count();

                $teacher->active_students_count = $activeStudents;

                // Use the rating from the teacher profile (updated by TeacherReview observer)
                $teacher->average_rating = $teacher->rating ? round($teacher->rating, 1) : null;

                return $teacher;
            });
        } else {
            // For guests, just add basic withCount stats
            $quranTeachers->getCollection()->transform(function ($teacher) {
                $teacher->my_subscription = null;
                $teacher->is_subscribed = false;

                return $teacher;
            });
        }

        // Get available packages
        $availablePackages = QuranPackage::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        return view('student.quran-teachers', compact(
            'academy',
            'quranTeachers',
            'activeSubscriptionsCount',
            'availablePackages',
            'isAuthenticated'
        ));
    }

    /**
     * Display the specified teacher profile (Unified for both public and authenticated)
     */
    public function show(Request $request, $subdomain, $teacherId): \Illuminate\View\View
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        $user = Auth::user();
        $isAuthenticated = (bool) $user;

        // Find the teacher
        $teacher = QuranTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->with(['academy', 'user'])
            ->firstOrFail();

        // Get available packages
        $packages = QuranPackage::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        // Calculate teacher statistics
        $stats = [
            'total_students' => $teacher->total_students ?? 0,
            'total_sessions' => $teacher->total_sessions ?? 0,
            'experience_years' => $teacher->teaching_experience_years ?? 0,
            'rating' => $teacher->rating ?? 0,
        ];

        $offersTrialSessions = $teacher->offers_trial_sessions;

        // Check for existing trial request and subscription (authenticated only)
        $existingTrialRequest = null;
        $mySubscription = null;

        if ($isAuthenticated && $user->user_type === UserType::STUDENT->value) {
            $existingTrialRequest = QuranTrialRequest::where('academy_id', $academy->id)
                ->where('student_id', $user->id)
                ->where('teacher_id', $teacher->id)
                ->whereIn('status', [
                    TrialRequestStatus::PENDING->value,
                    TrialRequestStatus::SCHEDULED->value,
                    TrialRequestStatus::COMPLETED->value,
                ])
                ->first();

            $mySubscription = QuranSubscription::where('academy_id', $academy->id)
                ->where('student_id', $user->id)
                ->where('quran_teacher_id', $teacher->user_id)
                ->whereIn('status', [SessionSubscriptionStatus::ACTIVE->value, SessionSubscriptionStatus::PENDING->value])
                ->first();
        }

        return view('student.quran-teacher-detail', compact(
            'academy',
            'teacher',
            'packages',
            'stats',
            'offersTrialSessions',
            'existingTrialRequest',
            'mySubscription',
            'isAuthenticated'
        ));
    }

    /**
     * Show trial booking form (requires authentication)
     */
    public function showTrialForm(Request $request, $subdomain, $teacherId): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        // Must be authenticated as student
        if (! Auth::check() || Auth::user()->user_type !== UserType::STUDENT->value) {
            return redirect()->route('login', [
                'subdomain' => $academy->subdomain,
                'redirect' => route('quran-teachers.show', ['subdomain' => $subdomain, 'teacherId' => $teacherId]),
            ])->with('error', __('payments.trial.login_required'));
        }

        $teacher = QuranTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->firstOrFail();

        $user = Auth::user();

        // Check for existing trial request
        $existingRequest = QuranTrialRequest::where('academy_id', $academy->id)
            ->where('student_id', $user->id)
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', [
                TrialRequestStatus::PENDING->value,
                TrialRequestStatus::SCHEDULED->value,
                TrialRequestStatus::COMPLETED->value,
            ])
            ->first();

        if ($existingRequest) {
            return redirect()->route('quran-teachers.show', [
                'subdomain' => $academy->subdomain,
                'teacherId' => $teacher->id,
            ])->with('error', __('payments.trial.already_requested'));
        }

        return view('public.quran-teachers.trial-booking', compact('academy', 'teacher'));
    }

    /**
     * Submit trial request (requires authentication)
     */
    public function submitTrialRequest(Request $request, $subdomain, $teacherId): \Illuminate\Http\RedirectResponse
    {
        Log::info('Trial request submission started', [
            'subdomain' => $subdomain,
            'teacherId' => $teacherId,
            'user_id' => Auth::id(),
            'user_type' => Auth::user()?->user_type,
            'request_data' => $request->except(['_token']),
        ]);

        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        // Must be authenticated as student
        if (! Auth::check() || Auth::user()->user_type !== UserType::STUDENT->value) {
            Log::warning('Trial request rejected: not authenticated as student', [
                'is_authenticated' => Auth::check(),
                'user_type' => Auth::user()?->user_type,
            ]);

            return redirect()->route('login', [
                'subdomain' => $academy->subdomain,
                'redirect' => route('quran-teachers.show', ['subdomain' => $subdomain, 'teacherId' => $teacherId]),
            ])->with('error', __('payments.trial.login_required'));
        }

        $teacher = QuranTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->firstOrFail();

        $user = Auth::user();

        // Check for existing trial request
        $existingRequest = QuranTrialRequest::where('academy_id', $academy->id)
            ->where('student_id', $user->id)
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', [
                TrialRequestStatus::PENDING->value,
                TrialRequestStatus::SCHEDULED->value,
                TrialRequestStatus::COMPLETED->value,
            ])
            ->first();

        if ($existingRequest) {
            return redirect()->back()
                ->with('error', __('payments.trial.already_requested'));
        }

        // Validate
        $validator = Validator::make($request->all(), [
            'current_level' => ['required', 'in:'.implode(',', QuranLearningLevel::values())],
            'learning_goals' => 'required|array|min:1',
            'learning_goals.*' => 'in:reading,tajweed,memorization,improvement',
            'preferred_time' => 'nullable|in:morning,afternoon,evening',
            'notes' => 'nullable|string|max:1000',
        ], [
            'current_level.required' => 'المستوى الحالي مطلوب',
            'learning_goals.required' => 'يجب اختيار هدف واحد على الأقل',
            'learning_goals.min' => 'يجب اختيار هدف واحد على الأقل',
        ]);

        if ($validator->fails()) {
            Log::warning('Trial request validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->except(['_token']),
            ]);

            return redirect()->route('quran-teachers.trial.form', [
                'subdomain' => $subdomain,
                'teacher' => $teacherId,
            ])->withErrors($validator)->withInput();
        }

        Log::info('Trial request validation passed, creating record...');

        try {
            $studentProfile = $user->studentProfile;

            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $academy->id,
                'student_id' => $user->id,
                'teacher_id' => $teacher->id,
                'student_name' => $studentProfile->full_name ?? $user->name,
                'student_age' => $studentProfile && $studentProfile->birth_date ? $studentProfile->birth_date->diffInYears(now()) : null,
                'phone' => $studentProfile->phone ?? $user->phone,
                'email' => $user->email,
                'current_level' => $request->current_level,
                'learning_goals' => $request->learning_goals,
                'preferred_time' => $request->preferred_time,
                'notes' => $request->notes,
                'status' => TrialRequestStatus::PENDING,
                'created_by' => $user->id,
            ]);

            Log::info('Trial request created successfully', [
                'trial_request_id' => $trialRequest->id,
                'user_id' => $user->id,
                'teacher_id' => $teacherId,
            ]);

            // Send notification to teacher
            try {
                app(TrialNotificationService::class)->sendTrialRequestReceivedNotification($trialRequest);
            } catch (\Exception $e) {
                Log::warning('Failed to send trial request notification', [
                    'trial_request_id' => $trialRequest->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()->route('quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id])
                ->with('success', __('payments.trial.request_success'));

        } catch (\Exception $e) {
            Log::error('Trial request creation failed', [
                'user_id' => $user->id,
                'teacher_id' => $teacherId,
                'error_message' => $e->getMessage(),
            ]);

            $errorMessage = __('payments.subscription.request_error');
            if (config('app.debug')) {
                $errorMessage .= ' - '.$e->getMessage();
            }

            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }
    }

    /**
     * Show subscription booking form
     */
    public function showSubscriptionBooking(Request $request, $subdomain, $teacherId, $packageId): \Illuminate\View\View
    {
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        $teacher = QuranTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->first();

        if (! $teacher) {
            abort(404, 'Teacher not found');
        }

        $package = QuranPackage::where('academy_id', $academy->id)
            ->where('id', $packageId)
            ->where('is_active', true)
            ->first();

        if (! $package) {
            abort(404, 'Package not found');
        }

        // Load student profile for the authenticated user
        $user = Auth::user();
        if ($user) {
            $user->load('studentProfile');
        }

        // Get the selected pricing period from query string (default to monthly)
        $selectedPeriod = $request->query('period', 'monthly');

        // Validate period value
        if (! in_array($selectedPeriod, ['monthly', 'quarterly', 'yearly'])) {
            $selectedPeriod = 'monthly';
        }

        return view('public.quran-teachers.subscription-booking', compact('academy', 'teacher', 'package', 'selectedPeriod'));
    }

    /**
     * Submit subscription request
     */
    public function submitSubscriptionRequest(Request $request, $subdomain, $teacherId, $packageId): \Illuminate\Http\RedirectResponse
    {
        Log::info('Subscription request received', [
            'subdomain' => $subdomain,
            'teacherId' => $teacherId,
            'packageId' => $packageId,
            'user_id' => Auth::id(),
            'user_type' => Auth::user()?->user_type,
            'request_data' => $request->except(['_token']),
        ]);

        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        $teacher = QuranTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->first();

        if (! $teacher) {
            abort(404, 'Teacher not found');
        }

        $package = QuranPackage::where('academy_id', $academy->id)
            ->where('id', $packageId)
            ->where('is_active', true)
            ->first();

        if (! $package) {
            abort(404, 'Package not found');
        }

        // Check if user is authenticated and is a student (allow super_admin for testing)
        $user = Auth::user();
        if (! Auth::check() || ($user->user_type !== UserType::STUDENT->value && $user->user_type !== UserType::SUPER_ADMIN->value)) {
            return redirect()->route('login', ['subdomain' => $academy->subdomain])
                ->with('error', __('payments.subscription.login_required'));
        }

        $user = Auth::user();

        // Validate the request
        $validator = Validator::make($request->all(), [
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'current_level' => ['required', 'in:'.implode(',', QuranLearningLevel::values())],
            'learning_goals' => 'required|array|min:1',
            'learning_goals.*' => 'in:reading,tajweed,memorization,improvement',
            'preferred_days' => 'nullable|array',
            'preferred_days.*' => 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            'preferred_time' => 'nullable|in:morning,afternoon,evening',
            'notes' => 'nullable|string|max:1000',
        ], [
            'billing_cycle.required' => 'دورة الفوترة مطلوبة',
            'current_level.required' => 'المستوى الحالي مطلوب',
            'learning_goals.required' => 'يجب اختيار هدف واحد على الأقل',
            'learning_goals.min' => 'يجب اختيار هدف واحد على الأقل',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $result = $this->enrollmentService->createSubscriptionWithPayment(
                $academy,
                $user,
                $teacher,
                $package,
                [
                    'billing_cycle' => $request->billing_cycle,
                    'current_level' => $request->current_level,
                    'learning_goals' => $request->learning_goals,
                    'preferred_days' => $request->preferred_days,
                    'preferred_time' => $request->preferred_time,
                    'notes' => $request->notes,
                ]
            );

            if ($result['error']) {
                return redirect()->back()
                    ->with('error', $result['error'])
                    ->withInput();
            }

            if ($result['redirect_url']) {
                return redirect()->away($result['redirect_url']);
            }

            return redirect()->route('student.subscriptions', ['subdomain' => $academy->subdomain])
                ->with('info', __('payments.subscription.payment_pending'));

        } catch (\Exception $e) {
            Log::error('Subscription creation failed', [
                'user_id' => $user->id,
                'teacher_id' => $teacherId,
                'package_id' => $packageId,
                'error' => $e->getMessage(),
            ]);

            $errorMessage = __('payments.subscription.request_error');
            if (config('app.debug')) {
                $errorMessage .= ' - '.$e->getMessage();
            }

            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }
    }
}
