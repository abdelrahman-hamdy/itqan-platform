<?php

namespace App\Http\Controllers;

use App\Models\QuranTeacherProfile;
use App\Models\QuranPackage;
use App\Models\QuranTrialRequest;
use App\Models\QuranSubscription;
use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PublicQuranTeacherController extends Controller
{
    /**
     * Display a listing of Quran teachers for an academy
     */
    public function index(Request $request)
    {
        // Get the current academy from the request
        $academy = $request->academy ?? Academy::where('subdomain', 'itqan-academy')->first();
        
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
    public function show(Request $request, $teacherCode)
    {
        // Get the current academy from the request
        $academy = $request->academy ?? Academy::where('subdomain', 'itqan-academy')->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        // Find the teacher by code within the academy
        $teacher = QuranTeacherProfile::where('academy_id', $academy->id)
            ->where('teacher_code', $teacherCode)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['academy', 'user'])
            ->first();

        if (!$teacher) {
            abort(404, 'Teacher not found');
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

        // Check if teacher offers trial sessions (this would be a setting)
        $offersTrialSessions = true; // This could be a teacher setting later

        return view('public.quran-teachers.show', compact(
            'academy', 
            'teacher', 
            'packages', 
            'stats', 
            'offersTrialSessions'
        ));
    }

    /**
     * Show trial session booking form
     */
    public function showTrialBooking(Request $request, $teacherCode)
    {
        $academy = $request->academy ?? Academy::where('subdomain', 'itqan-academy')->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        $teacher = QuranTeacherProfile::where('academy_id', $academy->id)
            ->where('teacher_code', $teacherCode)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->first();

        if (!$teacher) {
            abort(404, 'Teacher not found');
        }

        return view('public.quran-teachers.trial-booking', compact('academy', 'teacher'));
    }

    /**
     * Show subscription booking form
     */
    public function showSubscriptionBooking(Request $request, $teacherCode, $packageId)
    {
        $academy = $request->academy ?? Academy::where('subdomain', 'itqan-academy')->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        $teacher = QuranTeacherProfile::where('academy_id', $academy->id)
            ->where('teacher_code', $teacherCode)
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

        return view('public.quran-teachers.subscription-booking', compact('academy', 'teacher', 'package'));
    }

    /**
     * Process trial session booking request
     */
    public function submitTrialRequest(Request $request, $teacherCode)
    {
        $academy = $request->academy ?? Academy::where('subdomain', 'itqan-academy')->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        $teacher = QuranTeacherProfile::where('academy_id', $academy->id)
            ->where('teacher_code', $teacherCode)
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
            'student_name' => 'required|string|max:255',
            'student_age' => 'nullable|integer|min:5|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'current_level' => 'required|in:beginner,basic,intermediate,advanced,expert',
            'learning_goals' => 'required|array|min:1',
            'learning_goals.*' => 'in:reading,tajweed,memorization,improvement',
            'preferred_time' => 'nullable|in:morning,afternoon,evening',
            'notes' => 'nullable|string|max:1000',
            'agree_terms' => 'required|accepted',
        ], [
            'student_name.required' => 'اسم الطالب مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'current_level.required' => 'المستوى الحالي مطلوب',
            'learning_goals.required' => 'يجب اختيار هدف واحد على الأقل',
            'learning_goals.min' => 'يجب اختيار هدف واحد على الأقل',
            'agree_terms.required' => 'يجب الموافقة على الشروط والأحكام',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Create the trial request
            $trialRequest = QuranTrialRequest::create([
                'academy_id' => $academy->id,
                'student_id' => $user->id,
                'teacher_id' => $teacher->id,
                'student_name' => $request->student_name,
                'student_age' => $request->student_age,
                'phone' => $request->phone,
                'email' => $request->email ?: $user->email,
                'current_level' => $request->current_level,
                'learning_goals' => $request->learning_goals,
                'preferred_time' => $request->preferred_time,
                'notes' => $request->notes,
                'status' => QuranTrialRequest::STATUS_PENDING,
                'created_by' => $user->id,
            ]);

            // TODO: Send notification to teacher
            // TODO: Send confirmation email to student

            return redirect()->route('student.profile', ['subdomain' => $academy->subdomain])
                ->with('success', 'تم إرسال طلب الجلسة التجريبية بنجاح! سيتواصل معك المعلم خلال 24 ساعة');

        } catch (\Exception $e) {
            Log::error('Error creating trial request: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إرسال الطلب. يرجى المحاولة مرة أخرى')
                ->withInput();
        }
    }

    /**
     * Process subscription booking request
     */
    public function submitSubscriptionRequest(Request $request, $teacherCode, $packageId)
    {
        $academy = $request->academy ?? Academy::where('subdomain', 'itqan-academy')->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        $teacher = QuranTeacherProfile::where('academy_id', $academy->id)
            ->where('teacher_code', $teacherCode)
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

        // Validate the request
        $validator = Validator::make($request->all(), [
            'student_name' => 'required|string|max:255',
            'student_age' => 'nullable|integer|min:5|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'current_level' => 'required|in:beginner,basic,intermediate,advanced,expert',
            'learning_goals' => 'required|array|min:1',
            'learning_goals.*' => 'in:reading,tajweed,memorization,improvement',
            'preferred_days' => 'nullable|array',
            'preferred_days.*' => 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            'preferred_time' => 'nullable|in:morning,afternoon,evening',
            'notes' => 'nullable|string|max:1000',
            'agree_terms' => 'required|accepted',
        ], [
            'student_name.required' => 'اسم الطالب مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'billing_cycle.required' => 'دورة الفوترة مطلوبة',
            'current_level.required' => 'المستوى الحالي مطلوب',
            'learning_goals.required' => 'يجب اختيار هدف واحد على الأقل',
            'learning_goals.min' => 'يجب اختيار هدف واحد على الأقل',
            'agree_terms.required' => 'يجب الموافقة على الشروط والأحكام',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Calculate the price based on billing cycle
            $price = $package->getPriceForBillingCycle($request->billing_cycle);
            
            if (!$price) {
                return redirect()->back()
                    ->with('error', 'دورة الفوترة المختارة غير متاحة لهذه الباقة')
                    ->withInput();
            }

            // Check if student already has active subscription with this teacher
            $existingSubscription = QuranSubscription::where('academy_id', $academy->id)
                ->where('student_id', $user->id)
                ->where('quran_teacher_id', $teacher->id)
                ->whereIn('subscription_status', ['active', 'pending'])
                ->whereIn('payment_status', ['current', 'pending'])
                ->first();

            if ($existingSubscription) {
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

            // Create the subscription
            $subscription = QuranSubscription::create([
                'academy_id' => $academy->id,
                'student_id' => $user->id,
                'quran_teacher_id' => $teacher->id,
                'package_id' => $package->id,
                'subscription_type' => 'private', // Default to private sessions
                'total_sessions' => $package->sessions_per_month,
                'sessions_used' => 0,
                'sessions_remaining' => $package->sessions_per_month,
                'total_price' => $price,
                'discount_amount' => 0,
                'final_price' => $price,
                'currency' => $package->currency ?? $academy->currency ?? 'SAR',
                'billing_cycle' => $request->billing_cycle,
                'payment_status' => 'pending',
                'subscription_status' => 'pending',
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
                    'student_name' => $request->student_name,
                    'student_age' => $request->student_age,
                    'phone' => $request->phone,
                    'email' => $request->email ?: $user->email,
                    'learning_goals' => $request->learning_goals,
                    'preferred_days' => $request->preferred_days,
                    'preferred_time' => $request->preferred_time,
                ],
                'created_by' => $user->id,
            ]);

            // Redirect to payment page
            return redirect()->route('quran.subscription.payment', [
                'subdomain' => $academy->subdomain, 
                'subscription' => $subscription->id
            ])->with('success', 'تم إنشاء الاشتراك بنجاح! يرجى إكمال عملية الدفع');

        } catch (\Exception $e) {
            Log::error('Error creating subscription request: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إرسال الطلب. يرجى المحاولة مرة أخرى')
                ->withInput();
        }
    }
}