<?php

namespace App\Http\Controllers;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\UserType;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicPackage;
use App\Models\AcademicSubject;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\Payment;
use App\Services\AcademyContextService;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PublicAcademicPackageController extends Controller
{
    use ApiResponses;

    /**
     * Display academic packages and teachers for browsing
     */
    public function index(Request $request, $subdomain): \Illuminate\View\View
    {
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            abort(404, 'Academy not found or inactive');
        }

        // Get all active academic packages for this academy
        $packages = AcademicPackage::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->with(['academy'])
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        // Get all active academic teachers
        $teachers = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->with(['user', 'subjects', 'gradeLevels'])
            ->get();

        // Get subjects and grade levels for filtering
        $subjects = AcademicSubject::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $gradeLevels = AcademicGradeLevel::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('public.academic-packages.index', compact(
            'academy',
            'packages',
            'teachers',
            'subjects',
            'gradeLevels'
        ));
    }

    /**
     * Show subscription booking form for specific package and teacher
     */
    public function showSubscriptionForm(Request $request, $subdomain, $teacherId, $packageId): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        $teacher = AcademicTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->with(['user', 'subjects', 'gradeLevels'])
            ->first();

        if (! $teacher) {
            abort(404, 'Teacher not found');
        }

        $package = AcademicPackage::where('academy_id', $academy->id)
            ->where('id', $packageId)
            ->where('is_active', true)
            ->first();

        if (! $package) {
            abort(404, 'Package not found');
        }

        // Check if user is authenticated
        if (! Auth::check()) {
            return redirect()->route('login', ['subdomain' => $academy->subdomain])
                ->with('info', __('payments.subscription.login_to_continue'));
        }

        $user = Auth::user();

        // Check if user is a student
        if ($user->user_type !== UserType::STUDENT->value) {
            return redirect()->back()
                ->with('error', __('payments.subscription.student_only'));
        }

        // Get the selected pricing period from query string (default to monthly)
        $selectedPeriod = $request->query('period', 'monthly');

        // Validate period value
        if (! in_array($selectedPeriod, ['monthly', 'quarterly', 'yearly'])) {
            $selectedPeriod = 'monthly';
        }

        return view('public.academic-packages.subscription-booking', compact(
            'academy',
            'teacher',
            'package',
            'user',
            'selectedPeriod'
        ));
    }

    /**
     * Submit subscription request for academic package
     */
    public function submitSubscriptionRequest(Request $request, $subdomain, $teacherId, $packageId): \Illuminate\Http\RedirectResponse
    {
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Log subscription attempt
        \Log::info('Academic subscription request received', [
            'user_id' => Auth::id(),
            'teacher_id' => $teacherId,
            'package_id' => $packageId,
        ]);

        $teacher = AcademicTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->first();

        if (! $teacher) {
            abort(404, 'Teacher not found');
        }

        $package = AcademicPackage::where('academy_id', $academy->id)
            ->where('id', $packageId)
            ->where('is_active', true)
            ->first();

        if (! $package) {
            abort(404, 'Package not found');
        }

        // Check if user is authenticated and is a student
        if (! Auth::check() || Auth::user()->user_type !== UserType::STUDENT->value) {
            return redirect()->route('login', ['subdomain' => $academy->subdomain])
                ->with('error', __('payments.subscription.login_required'));
        }

        $user = Auth::user();

        // Log form submission
        // Validate the request
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|integer|exists:academic_subjects,id',
            'grade_level_id' => 'required|integer|exists:academic_grade_levels,id',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'preferred_schedule' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ], [
            'subject_id.required' => 'اختيار المادة الدراسية مطلوب',
            'subject_id.exists' => 'المادة الدراسية المختارة غير صحيحة',
            'grade_level_id.required' => 'اختيار المرحلة الدراسية مطلوب',
            'grade_level_id.exists' => 'المرحلة الدراسية المختارة غير صحيحة',
            'billing_cycle.required' => 'اختيار دورة الفوترة مطلوب',
        ]);

        if ($validator->fails()) {
            Log::warning('Academic subscription validation failed', [
                'errors' => $validator->errors()->toArray(),
                'user_id' => $user->id,
            ]);

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Calculate the price based on billing cycle
            $price = $package->getPriceForBillingCycle($request->billing_cycle);

            if (! $price) {
                Log::warning('Invalid billing cycle for academic package', [
                    'package_id' => $packageId,
                    'billing_cycle' => $request->billing_cycle,
                ]);

                return redirect()->back()
                    ->with('error', __('payments.subscription.billing_cycle_unavailable'))
                    ->withInput();
            }

            // Check for existing subscriptions using SubscriptionService
            $subscriptionService = app(SubscriptionService::class);
            $duplicateKeyValues = [
                'teacher_id' => $teacher->id,
                'academic_package_id' => $package->id,
                'subject_id' => $request->subject_id,
            ];

            $existing = $subscriptionService->findExistingSubscription(
                SubscriptionService::TYPE_ACADEMIC,
                $academy->id,
                $user->id,
                $duplicateKeyValues
            );

            // Block if active subscription exists for this teacher/package/subject combination
            if ($existing['active']) {
                return redirect()->back()
                    ->with('error', __('payments.subscription.already_subscribed'))
                    ->withInput();
            }

            // Cancel any existing pending subscriptions for this combination
            // (allows user to create a new subscription request)
            $subscriptionService->cancelDuplicatePending(
                SubscriptionService::TYPE_ACADEMIC,
                $academy->id,
                $user->id,
                $duplicateKeyValues
            );

            // CRITICAL FIX: Do NOT set start/end dates during subscription creation!
            // Dates should remain NULL until payment is confirmed.
            // They will be calculated and set in activateFromPayment() after payment succeeds.
            //
            // Setting dates here causes subscriptions to appear "active" even when payment fails,
            // because UI components check for existence of start/end dates.

            // Get sessions per month from package
            $sessionsPerMonth = $package->sessions_per_month ?? 8;

            // Get subject and grade level names from the database using IDs
            $subjectName = null;
            $gradeLevelName = null;

            // Get subject name
            $subject = AcademicSubject::where('id', $request->subject_id)
                ->where('academy_id', $academy->id)
                ->first();
            if ($subject) {
                $subjectName = $subject->name;
            }

            // Get grade level name
            $gradeLevel = AcademicGradeLevel::where('id', $request->grade_level_id)
                ->where('academy_id', $academy->id)
                ->first();
            if ($gradeLevel) {
                $gradeLevelName = $gradeLevel->getDisplayName();
            }

            // Generate unique subscription code using the model's method
            $subscriptionCode = AcademicSubscription::generateSubscriptionCode($academy->id, 'SUB');

            // Prepare subscription data (only valid database columns)
            $subscriptionData = [
                'academy_id' => $academy->id,
                'student_id' => $user->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $request->subject_id,
                'grade_level_id' => $request->grade_level_id,
                'subject_name' => $subjectName,
                'grade_level_name' => $gradeLevelName,
                'academic_package_id' => $package->id,
                'subscription_code' => $subscriptionCode,
                'subscription_type' => 'private',
                'sessions_per_week' => max(1, intval($sessionsPerMonth / 4.33)),
                'session_duration_minutes' => $package->session_duration_minutes,
                'sessions_per_month' => $sessionsPerMonth,
                'monthly_price' => $price,
                'monthly_amount' => $price,
                'discount_amount' => 0,
                'final_monthly_amount' => $price,
                'final_price' => $price,
                'currency' => getCurrencyCode(null, $academy), // Always use academy's configured currency
                'billing_cycle' => $request->billing_cycle,
                // CRITICAL: dates set to NULL - will be calculated in activateFromPayment()
                'start_date' => null,
                'starts_at' => null,
                'end_date' => null,
                'ends_at' => null,
                'next_billing_date' => null,
                'weekly_schedule' => [
                    'preferred_days' => $request->input('preferred_days', []),
                    'preferred_time' => $request->preferred_time,
                ],
                'timezone' => $academy->timezone?->value ?? AcademyContextService::getTimezone(),
                'auto_create_google_meet' => true,
                'status' => SessionSubscriptionStatus::PENDING->value,
                'payment_status' => 'pending',
                'has_trial_session' => false,
                'trial_session_used' => false,
                'pause_days_remaining' => 0,
                'auto_renewal' => true,
                'renewal_reminder_days' => 7,
                'completion_rate' => 0,
                'progress_percentage' => 0,
                'student_notes' => $request->notes,
            ];

            DB::beginTransaction();

            // Create subscription in PENDING state (lesson + sessions created after payment succeeds)
            $subscription = AcademicSubscription::create($subscriptionData);

            // Calculate tax (15% VAT)
            $taxAmount = round($price * 0.15, 2);
            $totalAmount = $price + $taxAmount;

            // Get payment gateway (use provided or academy default)
            $paymentSettings = $academy->getPaymentSettings();
            $gateway = $request->payment_gateway ?? $paymentSettings->getDefaultGateway() ?? config('payments.default', 'paymob');

            // Create payment record
            $payment = Payment::create([
                'academy_id' => $academy->id,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'payment_code' => Payment::generatePaymentCode($academy->id, 'ASP'),
                'payment_method' => $gateway,
                'payment_gateway' => $gateway,
                'payment_type' => 'subscription',
                'amount' => $totalAmount,
                'net_amount' => $price,
                'currency' => getCurrencyCode(null, $academy), // Always use academy's configured currency
                'tax_amount' => $taxAmount,
                'tax_percentage' => 15,
                'status' => 'pending',
                'payment_status' => 'pending',
                'created_by' => $user->id,
            ]);

            DB::commit();

            Log::info('Academic subscription created successfully', [
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'teacher_id' => $teacher->id,
                'package_id' => $package->id,
            ]);

            // Get student profile for customer data
            $studentProfile = $user->studentProfile;

            // Process payment with configured gateway - get redirect URL
            $paymentService = app(PaymentService::class);
            $result = $paymentService->processPayment($payment, [
                'customer_name' => $studentProfile->full_name ?? $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $studentProfile->phone ?? $user->phone ?? '',
            ]);

            // If we got a redirect URL, redirect to payment gateway
            if (! empty($result['redirect_url'])) {
                return redirect()->away($result['redirect_url']);
            }

            // If we got an iframe URL (Paymob checkout), redirect to it
            if (! empty($result['iframe_url'])) {
                return redirect()->away($result['iframe_url']);
            }

            // If payment failed immediately, redirect to payment page so student can retry
            if (! ($result['success'] ?? false)) {
                $payment->update(['status' => 'failed', 'payment_status' => 'failed']);

                return redirect()->route('academic.subscription.payment', [
                    'subdomain' => $academy->subdomain,
                    'subscription' => $subscription->id,
                ])->with('error', __('payments.subscription.payment_init_failed').': '.($result['error'] ?? __('payments.subscription.unknown_error')));
            }

            // Fallback - redirect to payment page
            return redirect()->route('academic.subscription.payment', [
                'subdomain' => $academy->subdomain,
                'subscription' => $subscription->id,
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Failed to create academic subscription', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'teacher_id' => $teacher->id,
                'package_id' => $package->id,
                'request_data' => $request->all(),
                'subject_name' => $subjectName ?? 'NULL',
                'grade_level_name' => $gradeLevelName ?? 'NULL',
                'price' => $price,
                'subscription_data_preview' => [
                    'academy_id' => $academy->id,
                    'student_id' => $user->id,
                    'teacher_id' => $teacher->id,
                    'billing_cycle' => $request->billing_cycle,
                    'status' => 'active',
                    'payment_status' => 'pending',
                ],
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', __('payments.subscription.subscription_creation_error').': '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show teacher profile for academic packages
     */
    public function showTeacher(Request $request, $subdomain, $teacherId): \Illuminate\View\View
    {
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            abort(404, 'Academy not found or inactive');
        }

        $teacher = AcademicTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->with(['user', 'subjects', 'gradeLevels'])
            ->first();

        if (! $teacher) {
            abort(404, 'Teacher not found');
        }

        // Get teacher's available packages
        $packages = AcademicPackage::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        // Get teacher stats
        $stats = [
            'rating' => $teacher->rating ?? 0,
            'total_students' => $teacher->total_students ?? 0,
            'total_sessions' => $teacher->total_sessions ?? 0,
            'years_experience' => $teacher->experience_years ?? 0,
            'active_subscriptions' => \App\Models\AcademicSubscription::where('teacher_id', $teacher->id)
                ->whereIn('status', [
                    SessionSubscriptionStatus::ACTIVE->value,
                    SessionSubscriptionStatus::PENDING->value,
                ])
                ->count(),
        ];

        return view('student.academic-teacher-detail', compact(
            'academy',
            'teacher',
            'packages',
            'stats'
        ));
    }

    /**
     * API: Get teachers available for a specific package
     */
    public function getPackageTeachers(Request $request, $subdomain, $packageId): \Illuminate\Http\JsonResponse
    {
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            return $this->notFound('Academy not found');
        }

        $package = AcademicPackage::where('academy_id', $academy->id)
            ->where('id', $packageId)
            ->where('is_active', true)
            ->first();

        if (! $package) {
            return $this->notFound('Package not found');
        }

        // Get teachers that match this package's subjects and grade levels
        $teachers = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->with(['user', 'subjects', 'gradeLevels'])
            ->whereHas('subjects', function ($query) use ($package) {
                if ($package->subject_ids) {
                    $subjectIds = is_array($package->subject_ids) ? $package->subject_ids : json_decode($package->subject_ids, true);
                    if ($subjectIds) {
                        $query->whereIn('subjects.id', $subjectIds);
                    }
                }
            })
            ->get()
            ->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->user->name,
                    'subjects' => $teacher->subjects->pluck('name')->toArray(),
                    'grade_levels' => $teacher->gradeLevels->pluck('name')->toArray(),
                    'experience_years' => $teacher->experience_years,
                    'bio' => $teacher->bio,
                ];
            });

        return $this->success([
            'teachers' => $teachers,
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
            ],
        ]);
    }

    /**
     * Get package IDs for a teacher - either their selected packages or academy defaults
     */
    private function getTeacherPackageIds($teacher, $academy): array
    {
        // First, check if teacher has specific packages assigned
        if (! empty($teacher->package_ids)) {
            $teacherPackageIds = $teacher->package_ids;

            // Ensure it's an array
            if (is_string($teacherPackageIds)) {
                $teacherPackageIds = json_decode($teacherPackageIds, true) ?: [];
            }

            if (is_array($teacherPackageIds) && ! empty($teacherPackageIds)) {
                return $teacherPackageIds;
            }
        }

        // If teacher has no packages assigned, check academy default packages
        $academySettings = \App\Models\AcademicSettings::where('academy_id', $academy->id)->first();

        if ($academySettings && ! empty($academySettings->default_package_ids)) {
            $defaultPackageIds = $academySettings->default_package_ids;

            // Ensure it's an array
            if (is_string($defaultPackageIds)) {
                $defaultPackageIds = json_decode($defaultPackageIds, true) ?: [];
            }

            if (is_array($defaultPackageIds) && count($defaultPackageIds) > 0) {
                return $defaultPackageIds;
            }
        }

        // If no teacher packages and no default packages, return empty array
        // The controller will then show all packages as fallback
        return [];
    }
}
