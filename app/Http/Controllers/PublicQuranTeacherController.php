<?php

namespace App\Http\Controllers;

use App\Models\QuranTeacherProfile;
use App\Models\QuranPackage;
use App\Models\QuranTrialRequest;
use App\Models\QuranSubscription;
use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PublicQuranTeacherController extends Controller
{
    /**
     * Display a listing of Quran teachers for an academy
     */
    public function index(Request $request, $subdomain)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        // Get active and approved Quran teachers for this academy
        $teachers = QuranTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['academy'])
            ->withCount(['quranSessions', 'quranCircles'])
            ->paginate(12);

        return view('public.quran-teachers.index', compact('academy', 'teachers'));
    }

        /**
     * Display the specified teacher profile
     */
    public function show(Request $request, $subdomain, $teacherId)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        // Find the teacher by ID within the academy
        $teacher = QuranTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['academy', 'user'])
            ->first();

        if (!$teacher) {
            abort(404, 'Teacher not found');
        }

        // Check if teacher offers trial sessions
        if (!$teacher->offers_trial_sessions) {
            return redirect()->route('public.quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id])
                ->with('error', 'هذا المعلم لا يقدم جلسات تجريبية حالياً');
        }

        // Get available packages for this academy
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

        // Check if teacher offers trial sessions
        $offersTrialSessions = $teacher->offers_trial_sessions;

        // Check if user has existing trial request with this teacher
        $existingTrialRequest = null;
        if (Auth::check() && Auth::user()->user_type === 'student') {
            $existingTrialRequest = QuranTrialRequest::where('academy_id', $academy->id)
                ->where('student_id', Auth::id())
                ->where('teacher_id', $teacher->id)
                ->whereIn('status', ['pending', 'approved', 'scheduled', 'completed'])
                ->first();
        }

        return view('public.quran-teachers.show', compact(
            'academy', 
            'teacher', 
            'packages', 
            'stats', 
            'offersTrialSessions',
            'existingTrialRequest'
        ));
    }

    /**
     * Show trial session booking form
     */
    public function showTrialBooking(Request $request, $subdomain, $teacherId)
    {
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        $teacher = QuranTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->first();

        if (!$teacher) {
            abort(404, 'Teacher not found');
        }

        // Load student profile for the authenticated user
        $user = Auth::user();
        if ($user) {
            $user->load('studentProfile');
        }

        return view('public.quran-teachers.trial-booking', compact('academy', 'teacher'));
    }

    /**
     * Show subscription booking form
     */
    public function showSubscriptionBooking(Request $request, $subdomain, $teacherId, $packageId)
    {
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        $teacher = QuranTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->first();

        if (!$teacher) {
            abort(404, 'Teacher not found');
        }

        $package = QuranPackage::where('academy_id', $academy->id)
            ->where('id', $packageId)
            ->where('is_active', true)
            ->first();

        if (!$package) {
            abort(404, 'Package not found');
        }

        // Load student profile for the authenticated user
        $user = Auth::user();
        if ($user) {
            $user->load('studentProfile');
        }

        return view('public.quran-teachers.subscription-booking', compact('academy', 'teacher', 'package'));
    }

    /**
     * Process trial session booking request
     */
    public function submitTrialRequest(Request $request, $subdomain, $teacherId)
    {
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        $teacher = QuranTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->first();

        if (!$teacher) {
            abort(404, 'Teacher not found');
        }

        // Check if user is authenticated and is a student
        if (!Auth::check() || Auth::user()->user_type !== 'student') {
            return redirect()->route('login', ['subdomain' => $academy->subdomain])
                ->with('error', 'يجب تسجيل الدخول كطالب لحجز جلسة تجريبية');
        }

        $user = Auth::user();

        // Debug: Log form submission
        Log::info('Trial form submitted', [
            'user_id' => $user->id,
            'teacher_id' => $teacherId,
            'form_data' => $request->all()
        ]);

        // Check if student already has a trial request with this teacher
        $existingRequest = QuranTrialRequest::where('academy_id', $academy->id)
            ->where('student_id', $user->id)
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', ['pending', 'approved', 'scheduled', 'completed'])
            ->first();

        if ($existingRequest) {
            return redirect()->back()
                ->with('error', 'لديك طلب جلسة تجريبية مسبق مع هذا المعلم');
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'current_level' => 'required|in:beginner,elementary,intermediate,advanced,expert,hafiz',
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
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Get student profile for data
            $studentProfile = $user->studentProfile;
            
            // Create the trial request
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
                'status' => QuranTrialRequest::STATUS_PENDING,
                'created_by' => $user->id,
            ]);

            // TODO: Send notification to teacher
            // TODO: Send confirmation email to student

            return redirect()->route('public.quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id])
                ->with('success', 'تم إرسال طلب الجلسة التجريبية بنجاح! سيتواصل معك المعلم خلال 24 ساعة');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إرسال الطلب. يرجى المحاولة مرة أخرى')
                ->withInput();
        }
    }

    /**
     * Process subscription booking request
     */
    public function submitSubscriptionRequest(Request $request, $subdomain, $teacherId, $packageId)
    {
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        $teacher = QuranTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->first();

        if (!$teacher) {
            abort(404, 'Teacher not found');
        }

        $package = QuranPackage::where('academy_id', $academy->id)
            ->where('id', $packageId)
            ->where('is_active', true)
            ->first();

        if (!$package) {
            abort(404, 'Package not found');
        }

        // Check if user is authenticated and is a student
        if (!Auth::check() || Auth::user()->user_type !== 'student') {
            return redirect()->route('login', ['subdomain' => $academy->subdomain])
                ->with('error', 'يجب تسجيل الدخول كطالب للاشتراك');
        }

        $user = Auth::user();

        // Debug: Log form submission
        Log::info('Subscription form submitted', [
            'user_id' => $user->id,
            'teacher_id' => $teacherId,
            'package_id' => $packageId,
            'form_data' => $request->all()
        ]);

        // Validate the request
        $validator = Validator::make($request->all(), [
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'current_level' => 'required|in:beginner,elementary,intermediate,advanced,expert,hafiz',
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
            // Log start of subscription process
            Log::info('Starting subscription creation process', [
                'user_id' => $user->id,
                'teacher_id' => $teacherId,
                'package_id' => $packageId,
                'billing_cycle' => $request->billing_cycle,
                'current_level' => $request->current_level
            ]);

            // Calculate the price based on billing cycle
            $price = $package->getPriceForBillingCycle($request->billing_cycle);
            
            if (!$price) {
                Log::warning('Invalid billing cycle for package', [
                    'package_id' => $packageId,
                    'billing_cycle' => $request->billing_cycle,
                    'available_prices' => [
                        'monthly' => $package->monthly_price,
                        'quarterly' => $package->quarterly_price,
                        'yearly' => $package->yearly_price
                    ]
                ]);
                return redirect()->back()
                    ->with('error', 'دورة الفوترة المختارة غير متاحة لهذه الباقة')
                    ->withInput();
            }

            Log::info('Price calculated successfully', ['price' => $price, 'billing_cycle' => $request->billing_cycle]);

            // Check if student already has active subscription with this teacher
            $existingSubscription = QuranSubscription::where('academy_id', $academy->id)
                ->where('student_id', $user->id)
                ->where('quran_teacher_id', $teacher->id)
                ->whereIn('subscription_status', ['active', 'pending'])
                ->whereIn('payment_status', ['paid', 'current', 'pending'])
                ->first();

            if ($existingSubscription) {
                Log::warning('Student already has active subscription', [
                    'existing_subscription_id' => $existingSubscription->id,
                    'student_id' => $user->id,
                    'teacher_id' => $teacherId
                ]);
                return redirect()->back()
                    ->with('error', 'لديك اشتراك نشط أو معلق مع هذا المعلم')
                    ->withInput();
            }

            // Calculate subscription dates
            $startDate = now();
            $endDate = match($request->billing_cycle) {
                'monthly' => $startDate->copy()->addMonth(),
                'quarterly' => $startDate->copy()->addMonths(3),
                'yearly' => $startDate->copy()->addYear(),
                default => $startDate->copy()->addMonth(),
            };

            Log::info('Subscription dates calculated', [
                'start_date' => $startDate->toDateTimeString(),
                'end_date' => $endDate->toDateTimeString()
            ]);

            // Get student profile for data
            $studentProfile = $user->studentProfile;
            
            // Prepare subscription data
            $subscriptionData = [
                'academy_id' => $academy->id,
                'student_id' => $user->id,
                'quran_teacher_id' => $teacher->id,
                'package_id' => $package->id,
                'subscription_code' => QuranSubscription::generateSubscriptionCode($academy->id),
                'subscription_type' => 'individual', // Default to individual sessions
                'total_sessions' => $package->sessions_per_month,
                'sessions_used' => 0,
                'sessions_remaining' => $package->sessions_per_month,
                'total_price' => $price,
                'discount_amount' => 0,
                'final_price' => $price,
                'currency' => $package->getFormattedCurrency(),
                'billing_cycle' => $request->billing_cycle,
                'payment_status' => 'pending', // Start with pending, then update to paid after fake payment
                'subscription_status' => 'pending', // Start with pending
                'trial_sessions' => 0,
                'trial_used' => 0,
                'is_trial_active' => false,
                'current_surah' => 1, // Start with Al-Fatiha
                'current_verse' => 1,
                'verses_memorized' => 0,
                'memorization_level' => $request->current_level,
                'progress_percentage' => 0,
                'starts_at' => $startDate,
                'expires_at' => $endDate,
                'auto_renew' => true,
                'next_payment_at' => $endDate,
                'notes' => $request->notes,
                'metadata' => [
                    'student_name' => $studentProfile->full_name ?? $user->name,
                    'student_age' => $studentProfile && $studentProfile->birth_date ? $studentProfile->birth_date->diffInYears(now()) : null,
                    'phone' => $studentProfile->phone ?? $user->phone,
                    'email' => $user->email,
                    'learning_goals' => $request->learning_goals,
                    'preferred_days' => $request->preferred_days,
                    'preferred_time' => $request->preferred_time,
                ],
                'created_by' => $user->id,
            ];

            Log::info('Creating subscription with data', ['subscription_data' => $subscriptionData]);
            
            // Create the subscription
            $subscription = QuranSubscription::create($subscriptionData);

            Log::info('Subscription created successfully', ['subscription_id' => $subscription->id]);

            // Simulate fake payment processing
            Log::info('Simulating payment processing for subscription', ['subscription_id' => $subscription->id]);
            
            // Update subscription to mark as paid and active (fake payment success)
            $subscription->update([
                'payment_status' => 'paid',
                'subscription_status' => 'active',
                'last_payment_at' => now()
            ]);

            Log::info('Fake payment processed successfully', [
                'subscription_id' => $subscription->id,
                'final_status' => 'active',
                'payment_status' => 'paid'
            ]);

            // Redirect back to teacher profile with success message
            return redirect()->route('public.quran-teachers.show', [
                'subdomain' => $academy->subdomain, 
                'teacher' => $teacher->id
            ])->with('success', 'تم إنشاء الاشتراك بنجاح! تم قبول الدفع وأصبح الاشتراك نشطاً. يمكنك الآن حجز الجلسات مع المعلم');

        } catch (\Exception $e) {
            // Log the actual error for debugging with more context
            Log::error('Subscription creation failed', [
                'user_id' => $user->id,
                'teacher_id' => $teacherId,
                'package_id' => $packageId,
                'academy_id' => $academy->id,
                'request_data' => $request->all(),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            // Return a more helpful error message in development
            $errorMessage = 'حدث خطأ أثناء إرسال الطلب. يرجى المحاولة مرة أخرى';
            if (config('app.debug')) {
                $errorMessage .= ' - ' . $e->getMessage();
            }
            
            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }
    }
}