<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\AcademicPackage;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\AcademicSubject;
use App\Models\AcademicGradeLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PublicAcademicPackageController extends Controller
{
    /**
     * Display academic packages and teachers for browsing
     */
    public function index(Request $request, $subdomain)
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
            ->where('is_active', true)
            ->where('approval_status', 'approved')
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
    public function showSubscriptionForm(Request $request, $subdomain, $teacherId, $packageId)
    {
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        $teacher = AcademicTeacherProfile::where('id', $teacherId)
            ->where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['user'])
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
                ->with('info', 'يرجى تسجيل الدخول للمتابعة');
        }

        $user = Auth::user();

        // Check if user is a student
        if ($user->user_type !== 'student') {
            return redirect()->back()
                ->with('error', 'يجب أن تكون طالباً للاشتراك في الباقات الأكاديمية');
        }

        return view('public.academic-packages.subscription-booking', compact(
            'academy',
            'teacher',
            'package',
            'user'
        ));
    }

    /**
     * Submit subscription request for academic package
     */
    public function submitSubscriptionRequest(Request $request, $subdomain, $teacherId, $packageId)
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
            ->where('is_active', true)
            ->where('approval_status', 'approved')
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
        if (! Auth::check() || Auth::user()->user_type !== 'student') {
            return redirect()->route('login', ['subdomain' => $academy->subdomain])
                ->with('error', 'يجب تسجيل الدخول كطالب للاشتراك');
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
                    ->with('error', 'دورة الفوترة المختارة غير متاحة لهذه الباقة')
                    ->withInput();
            }

            // Check if student already has active subscription with this teacher for same subject
            $existingSubscription = AcademicSubscription::where('academy_id', $academy->id)
                ->where('student_id', $user->id)
                ->where('teacher_id', $teacher->id)
                ->where('subject_id', $request->subject_id)
                ->whereIn('status', ['active', 'pending'])
                ->whereIn('payment_status', ['current', 'pending'])
                ->first();

            if ($existingSubscription) {
                return redirect()->back()
                    ->with('error', 'لديك اشتراك نشط مع هذا المعلم في هذه المادة بالفعل')
                    ->withInput();
            }

            // Calculate dates
            $startDate = now();
            $endDate = match ($request->billing_cycle) {
                'monthly' => $startDate->copy()->addMonth(),
                'quarterly' => $startDate->copy()->addMonths(3),
                'yearly' => $startDate->copy()->addYear(),
                default => $startDate->copy()->addMonth()
            };

            // Calculate hourly rate using package's defined sessions per month
            $sessionsPerMonth = $package->sessions_per_month;
            $hourlyRate = $price / $sessionsPerMonth;

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
                $gradeLevelName = $gradeLevel->name;
            }

            // Generate unique subscription code
            $subscriptionCode = 'SUB-' . $academy->id . '-' . str_pad(
                AcademicSubscription::where('academy_id', $academy->id)->count() + 1, 
                4, 
                '0', 
                STR_PAD_LEFT
            );

            // Prepare subscription data
            $subscriptionData = [
                'academy_id' => $academy->id,
                'student_id' => $user->id,
                'teacher_id' => $teacher->id,
                'subject_id' => null, // No longer using foreign keys for subjects
                'grade_level_id' => null, // No longer using foreign keys for grade levels
                'subject_name' => $subjectName,
                'grade_level_name' => $gradeLevelName,
                'academic_package_id' => $package->id,
                'subscription_code' => $subscriptionCode,
                'subscription_type' => 'private',
                'sessions_per_week' => max(1, intval($sessionsPerMonth / 4.33)), // Convert monthly to weekly
                'session_duration_minutes' => $package->session_duration_minutes,
                'hourly_rate' => $hourlyRate,
                'sessions_per_month' => $sessionsPerMonth,
                'monthly_amount' => $price,
                'discount_amount' => 0,
                'final_monthly_amount' => $price,
                'currency' => $package->currency,
                'billing_cycle' => $request->billing_cycle,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'next_billing_date' => $endDate,
                'weekly_schedule' => [
                    'preferred_days' => $request->input('preferred_days', []),
                    'preferred_time' => $request->preferred_time,
                ],
                'timezone' => 'Asia/Riyadh',
                'auto_create_google_meet' => true,
                'status' => 'active', // Valid status value
                'payment_status' => 'pending', // Payment still pending
                'has_trial_session' => false,
                'trial_session_used' => false,
                'pause_days_remaining' => 0,
                'auto_renewal' => true,
                'renewal_reminder_days' => 7,
                'total_sessions_scheduled' => 0,
                'total_sessions_completed' => 0,
                'total_sessions_missed' => 0,
                'completion_rate' => 0,
                'notes' => $request->notes,
                'student_notes' => $request->preferred_schedule,
            ];

            DB::beginTransaction();

            // Log subscription creation attempt
            \Log::info('Creating academic subscription', [
                'student_id' => $user->id,
                'teacher_id' => $teacher->id,
                'subject' => $subjectName,
                'grade_level' => $gradeLevelName,
            ]);

            $subscription = AcademicSubscription::create($subscriptionData);

            // Create unscheduled sessions based on package sessions per month
            $this->createUnscheduledSessions($subscription, $package);

            // Update teacher stats if available
            if (method_exists($teacher, 'increment')) {
                $teacher->increment('total_students');
            }

            DB::commit();

            Log::info('Academic subscription created successfully', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'teacher_id' => $teacher->id,
                'package_id' => $package->id,
            ]);

            return redirect()
                ->back()
                ->with('success', 'تم إنشاء الاشتراك بنجاح! سيتم التواصل معك قريباً لتحديد مواعيد الجلسات.');

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
                'price' => $price ?? 'NULL',
                'subscription_data_preview' => [
                    'academy_id' => $academy->id,
                    'student_id' => $user->id,
                    'teacher_id' => $teacher->id,
                    'billing_cycle' => $request->billing_cycle,
                    'status' => 'active',
                    'payment_status' => 'pending'
                ],
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إنشاء الاشتراك: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Create unscheduled sessions for a new academic subscription
     */
    private function createUnscheduledSessions(AcademicSubscription $subscription, AcademicPackage $package)
    {
        $sessionsPerMonth = $package->sessions_per_month ?? 8;
        $sessionDuration = $package->session_duration_minutes ?? 60;

        // Create sessions for the subscription
        for ($i = 1; $i <= $sessionsPerMonth; $i++) {
            AcademicSession::create([
                'academy_id' => $subscription->academy_id,
                'academic_teacher_id' => $subscription->teacher_id,
                'academic_subscription_id' => $subscription->id,
                'student_id' => $subscription->student_id,
                'session_code' => 'AS-' . $subscription->id . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'session_sequence' => $i,
                'session_type' => 'individual',
                'is_template' => false,
                'is_generated' => true,
                'status' => \App\Enums\SessionStatus::UNSCHEDULED,
                'is_scheduled' => false,
                'title' => "جلسة {$i} - {$subscription->subject_name}",
                'description' => "جلسة في مادة {$subscription->subject_name} - {$subscription->grade_level_name}",
                'lesson_objectives' => json_encode(["أهداف الجلسة {$i}"]),
                'duration_minutes' => $sessionDuration,
                'location_type' => 'online',
                'session_notes' => '',
                'follow_up_required' => false,
                'retry_count' => 0,
                'created_by' => $subscription->student_id,
            ]);
        }

        // Update subscription totals
        $subscription->update([
            'total_sessions_scheduled' => $sessionsPerMonth,
            'sessions_completed' => 0,
            'completion_rate' => 0,
        ]);
    }

    /**
     * Show teacher profile for academic packages
     * Redirect to the proper academic teacher controller
     */
    public function showTeacher(Request $request, $subdomain, $teacherId)
    {
        // Redirect to the proper academic teacher profile page
        return redirect()->route('public.academic-teachers.show', [
            'subdomain' => $subdomain,
            'teacher' => $teacherId
        ]);
    }

    /**
     * API: Get teachers available for a specific package
     */
    public function getPackageTeachers(Request $request, $subdomain, $packageId)
    {
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            return response()->json(['error' => 'Academy not found'], 404);
        }

        $package = AcademicPackage::where('academy_id', $academy->id)
            ->where('id', $packageId)
            ->where('is_active', true)
            ->first();

        if (! $package) {
            return response()->json(['error' => 'Package not found'], 404);
        }

        // Get teachers that match this package's subjects and grade levels
        $teachers = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
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

        return response()->json([
            'teachers' => $teachers,
            'package' => [
                'id' => $package->id,
                'name' => $package->name_ar ?? $package->name_en,
            ],
        ]);
    }

    /**
     * Get package IDs for a teacher - either their selected packages or academy defaults
     */
    private function getTeacherPackageIds($teacher, $academy): array
    {
        // First, check if teacher has specific packages assigned
        if (!empty($teacher->package_ids)) {
            $teacherPackageIds = $teacher->package_ids;
            
            // Ensure it's an array
            if (is_string($teacherPackageIds)) {
                $teacherPackageIds = json_decode($teacherPackageIds, true) ?: [];
            }
            
            if (is_array($teacherPackageIds) && !empty($teacherPackageIds)) {
                return $teacherPackageIds;
            }
        }
        
        // If teacher has no packages assigned, check academy default packages
        $academySettings = \App\Models\AcademicSettings::where('academy_id', $academy->id)->first();
        
        if ($academySettings && !empty($academySettings->default_package_ids)) {
            $defaultPackageIds = $academySettings->default_package_ids;
            
            // Ensure it's an array
            if (is_string($defaultPackageIds)) {
                $defaultPackageIds = json_decode($defaultPackageIds, true) ?: [];
            }
            
            if (is_array($defaultPackageIds) && !empty($defaultPackageIds)) {
                return $defaultPackageIds;
            }
        }
        
        // If no teacher packages and no default packages, return empty array
        // The controller will then show all packages as fallback
        return [];
    }
}