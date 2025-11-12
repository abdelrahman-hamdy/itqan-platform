<?php

namespace App\Http\Controllers;

use App\Enums\Country;
use App\Models\AcademicProgress;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\RecordedCourse;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StudentProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $studentProfile = $user->studentProfileUnscoped;
        $academy = $user->academy;

        // Get student's Quran circles
        $quranCircles = QuranCircle::where('academy_id', $academy->id)
            ->whereHas('students', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with(['students'])
            ->get();

        // Manually load teacher data for each circle
        foreach ($quranCircles as $circle) {
            if ($circle->quran_teacher_id) {
                $circle->teacherData = \App\Models\User::find($circle->quran_teacher_id);
            }
        }

        // Get student's Quran private sessions
        $quranPrivateSessions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('subscription_type', 'individual')
            ->where('subscription_status', 'active')
            ->whereHas('individualCircle', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->with(['package', 'individualCircle', 'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(5);
            }])
            ->get();

        // Manually load teacher data for each subscription
        foreach ($quranPrivateSessions as $subscription) {
            if ($subscription->quran_teacher_id) {
                $subscription->teacherData = \App\Models\User::find($subscription->quran_teacher_id);
            }
        }

        // Get student's Quran trial requests
        $quranTrialRequests = QuranTrialRequest::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get student's interactive courses
        $interactiveCourses = InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with(['assignedTeacher', 'enrollments' => function ($query) use ($user) {
                $query->where('student_id', $user->id);
            }])
            ->get();

        // Get student's recorded courses
        $recordedCourses = RecordedCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with([
                'enrollments' => function ($query) use ($user) {
                    $query->where('student_id', $user->id)
                        ->select('id', 'recorded_course_id', 'student_id', 'status', 'progress_percentage',
                            'completed_lessons', 'total_lessons', 'watch_time_minutes');
                },
                'subject',
                'gradeLevel',
            ])
            ->select('id', 'academy_id', 'title', 'description', 'thumbnail_url', 'difficulty_level', 'subject_id', 'grade_level_id', 'price', 'avg_rating', 'total_enrollments')
            ->get();

        // Get student's academic private sessions
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

        // Calculate statistics
        $stats = $this->calculateStudentStats($user);

        return view('student.profile', compact(
            'quranCircles',
            'quranPrivateSessions',
            'quranTrialRequests',
            'interactiveCourses',
            'recordedCourses',
            'academicPrivateSessions',
            'stats'
        ));
    }

    private function calculateStudentStats($user)
    {
        $academy = $user->academy;

        // Calculate total learning hours (simplified calculation)
        $totalHours = 24; // This would be calculated from actual session data
        $hoursGrowth = 12; // Percentage growth this month

        // Count active interactive courses
        $activeInteractiveCourses = InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->count();

        // Count active recorded courses
        $activeRecordedCourses = RecordedCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function ($query) use ($user) {
                $query->where('student_id', $user->id)->where('status', 'active');
            })
            ->count();

        // Total active courses
        $activeCourses = $activeInteractiveCourses + $activeRecordedCourses;

        // Count completed lessons
        $completedLessons = AcademicProgress::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('progress_status', 'completed')
            ->count();

        // Calculate real Quran progress
        $quranSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->get();

        $quranProgress = $quranSubscriptions->avg('progress_percentage') ?? 0;
        $quranPages = $quranSubscriptions->sum('verses_memorized') ?? 0;

        // Count Quran trial requests
        $quranTrialRequestsCount = QuranTrialRequest::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->count();

        // Count active Quran subscriptions
        $activeQuranSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('subscription_status', 'active')
            ->count();

        // Count active Quran circles
        $quranCirclesCount = QuranCircle::where('academy_id', $academy->id)
            ->whereHas('students', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->count();

        // Count academic private sessions (placeholder for now)
        // TODO: Replace with actual academic sessions count when model is available
        $academicPrivateSessionsCount = 0;

        return [
            'totalHours' => $totalHours,
            'hoursGrowth' => $hoursGrowth,
            'activeCourses' => $activeCourses,
            'activeInteractiveCourses' => $activeInteractiveCourses,
            'activeRecordedCourses' => $activeRecordedCourses,
            'completedLessons' => $completedLessons,
            'quranProgress' => round($quranProgress, 1),
            'quranPages' => $quranPages,
            'quranTrialRequestsCount' => $quranTrialRequestsCount,
            'activeQuranSubscriptions' => $activeQuranSubscriptions,
            'quranCirclesCount' => $quranCirclesCount,
            'academicPrivateSessionsCount' => $academicPrivateSessionsCount,
        ];
    }

    public function edit()
    {
        $user = Auth::user();
        $studentProfile = $user->studentProfileUnscoped;

        // Handle case where student profile doesn't exist or was orphaned
        if (! $studentProfile) {
            // Try to create a basic student profile if one doesn't exist
            $studentProfile = $this->createBasicStudentProfile($user);

            if (! $studentProfile) {
                return redirect()->route('student.profile')
                    ->with('error', 'لم يتم العثور على الملف الشخصي للطالب. يرجى التواصل مع الدعم الفني.');
            }
        }

        // Get grade levels for the current academy
        $gradeLevels = \App\Models\AcademicGradeLevel::where('academy_id', $user->academy_id)
            ->active()
            ->ordered()
            ->get();

        // Get countries for the nationality dropdown
        $countries = Country::toArray();

        return view('student.edit-profile', compact('studentProfile', 'gradeLevels', 'countries'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $studentProfile = $user->studentProfileUnscoped;

        // Handle case where student profile doesn't exist or was orphaned
        if (! $studentProfile) {
            $studentProfile = $this->createBasicStudentProfile($user);

            if (! $studentProfile) {
                return redirect()->back()
                    ->with('error', 'لم يتم العثور على الملف الشخصي للطالب. يرجى التواصل مع الدعم الفني.');
            }
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

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($studentProfile->avatar) {
                Storage::disk('public')->delete($studentProfile->avatar);
            }

            // Store new avatar
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $studentProfile->update($validated);

        return redirect()->route('student.profile', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy'])
            ->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }

    public function subscriptions()
    {
        $user = Auth::user();
        $academy = $user->academy;

        $quranSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['quranTeacher', 'package', 'individualCircle', 'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        $quranTrialRequests = QuranTrialRequest::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher'])
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

        return view('student.subscriptions', compact('quranSubscriptions', 'quranTrialRequests', 'courseEnrollments', 'academicSubscriptions'));
    }

    public function payments()
    {
        $user = Auth::user();

        // This would fetch actual payment data from your payment system
        $payments = collect([
            [
                'id' => 1,
                'amount' => 150,
                'currency' => 'SAR',
                'description' => 'اشتراك دائرة القرآن - مارس 2024',
                'status' => 'completed',
                'date' => now()->subDays(5),
                'method' => 'بطاقة ائتمان',
            ],
            [
                'id' => 2,
                'amount' => 200,
                'currency' => 'SAR',
                'description' => 'كورس الرياضيات التفاعلي',
                'status' => 'completed',
                'date' => now()->subDays(15),
                'method' => 'تحويل بنكي',
            ],
        ]);

        return view('student.payments', compact('payments'));
    }

    public function progress()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get academic progress
        $academicProgress = AcademicProgress::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['course', 'teacher'])
            ->get();

        // Calculate overall progress
        $totalLessons = $academicProgress->count();
        $completedLessons = $academicProgress->where('status', 'completed')->count();
        $overallProgress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        return view('student.progress', compact('academicProgress', 'overallProgress'));
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

    /**
     * Create a basic student profile for users who don't have one
     * This can happen when grade levels are deleted and relationships become orphaned
     */
    private function createBasicStudentProfile($user)
    {
        try {
            // Check if a profile already exists but might be orphaned
            $existingProfile = StudentProfile::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->first();

            if ($existingProfile) {
                return $existingProfile;
            }

            // Generate a unique student code
            $studentCode = 'STU'.str_pad($user->id, 6, '0', STR_PAD_LEFT);

            // Check for existing student code and make it unique
            $counter = 1;
            $originalCode = $studentCode;
            while (StudentProfile::where('student_code', $studentCode)->exists()) {
                $studentCode = $originalCode.'-'.$counter;
                $counter++;
            }

            // Find the default grade level for the user's academy
            $defaultGradeLevel = \App\Models\AcademicGradeLevel::where('academy_id', $user->academy_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->first();

            // Create a basic student profile
            $studentProfile = StudentProfile::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name ?? 'طالب',
                'last_name' => $user->last_name ?? 'جديد',
                'student_code' => $studentCode,
                'grade_level_id' => $defaultGradeLevel?->id, // Can be null initially
                'enrollment_date' => now(),
                'notes' => 'تم إنشاء الملف الشخصي تلقائياً بعد حل مشكلة البيانات المفقودة',
            ]);

            return $studentProfile;

        } catch (\Exception $e) {
            Log::error('Failed to create basic student profile for user '.$user->id.': '.$e->getMessage());

            return null;
        }
    }

    public function quranCircles()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get all available Quran circles for this academy
        $availableCircles = QuranCircle::where('academy_id', $academy->id)
            ->where('status', true)
            ->where('enrollment_status', 'open')
            ->with(['quranTeacher', 'students'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Get student's enrolled circles
        $enrolledCircles = $user->quranCircles()
            ->where('academy_id', $academy->id)
            ->with(['quranTeacher', 'students'])
            ->get();

        return view('student.quran-circles', compact(
            'availableCircles',
            'enrolledCircles'
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

        return view('student.circle-detail', compact(
            'circle',
            'isEnrolled',
            'canEnroll',
            'academy',
            'upcomingSessions',
            'pastSessions'
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

        if (! $circle) {
            return response()->json(['error' => 'Circle not found'], 404);
        }

        // Check if student is already enrolled
        $isEnrolled = $circle->students()->where('users.id', $user->id)->exists();
        if ($isEnrolled) {
            return response()->json(['error' => 'You are already enrolled in this circle'], 400);
        }

        // Check if circle is available for enrollment
        if ($circle->status !== true || $circle->enrollment_status !== 'open') {
            return response()->json(['error' => 'This circle is not open for enrollment'], 400);
        }

        if ($circle->enrolled_students >= $circle->max_students) {
            return response()->json(['error' => 'This circle is full'], 400);
        }

        try {
            DB::transaction(function () use ($circle, $user) {
                // Enroll student in circle
                $circle->students()->attach($user->id, [
                    'enrolled_at' => now(),
                    'status' => 'enrolled',
                    'attendance_count' => 0,
                    'missed_sessions' => 0,
                    'makeup_sessions_used' => 0,
                    'current_level' => 'beginner',
                ]);

                // Update circle enrollment count
                $circle->increment('enrolled_students');

                // Check if circle is now full
                if ($circle->enrolled_students >= $circle->max_students) {
                    $circle->update(['enrollment_status' => 'full']);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيلك في الحلقة بنجاح!',
                'redirect_url' => route('student.quran-circles', ['subdomain' => $academy->subdomain]),
            ]);

        } catch (\Exception $e) {
            Log::error('Error enrolling student in circle: '.$e->getMessage());

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

        if (! $circle) {
            return response()->json(['error' => 'Circle not found'], 404);
        }

        // Check if student is enrolled
        $isEnrolled = $circle->students()->where('users.id', $user->id)->exists();
        if (! $isEnrolled) {
            return response()->json(['error' => 'You are not enrolled in this circle'], 400);
        }

        try {
            DB::transaction(function () use ($circle, $user) {
                // Remove student from circle
                $circle->students()->detach($user->id);

                // Update circle enrollment count
                $circle->decrement('enrolled_students');

                // If circle was full, open it for enrollment
                if ($circle->enrollment_status === 'full') {
                    $circle->update(['enrollment_status' => 'open']);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء تسجيلك من الحلقة بنجاح',
                'redirect_url' => route('student.quran-circles', ['subdomain' => $academy->subdomain]),
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing student from circle: '.$e->getMessage());

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

        // Get student's existing subscribed teacher IDs (regardless of status)
        $subscribedTeacherIds = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('subscription_status', ['active', 'pending', 'paused'])
            ->pluck('quran_teacher_id')
            ->toArray();

        // Get all active and approved Quran teachers for this academy, excluding already subscribed
        // Fix: use user_id instead of id since quran_teacher_id points to User model
        $quranTeachers = QuranTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->whereNotIn('user_id', $subscribedTeacherIds)
            ->with(['user', 'quranCircles', 'quranSessions'])
            ->withCount(['quranSessions as total_sessions'])
            ->orderBy('rating', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Calculate additional stats for each teacher
        $quranTeachers->getCollection()->transform(function ($teacher) {
            // Count active students from subscriptions
            // Fix: use user_id instead of id since quran_teacher_id points to User model
            $activeStudents = QuranSubscription::where('quran_teacher_id', $teacher->user_id)
                ->where('subscription_status', 'active')
                ->distinct('student_id')
                ->count();

            $teacher->active_students_count = $activeStudents;

            // Get average rating from subscriptions reviews
            // Fix: use user_id instead of id since quran_teacher_id points to User model
            $averageRating = QuranSubscription::where('quran_teacher_id', $teacher->user_id)
                ->whereNotNull('rating')
                ->avg('rating');

            $teacher->average_rating = $averageRating ? round($averageRating, 1) : null;

            return $teacher;
        });

        // Get student's active subscriptions to show which teachers they're already learning with
        $activeSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('subscription_status', ['active', 'pending'])
            ->with(['package', 'sessions'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get available packages for this academy
        $availablePackages = QuranPackage::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        return view('student.quran-teachers', compact(
            'quranTeachers',
            'activeSubscriptions',
            'availablePackages'
        ));
    }

    public function interactiveCourses()
    {
        $user = Auth::user();
        $academy = $user->academy;
        $studentId = $user->studentProfile->id ?? $user->id;

        // Get all available interactive courses for this academy
        $availableCourses = InteractiveCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->where('enrollment_deadline', '>=', now()->toDateString())
            ->with(['assignedTeacher', 'subject', 'gradeLevel', 'enrollments' => function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Get student's enrolled courses
        $enrolledCourses = InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function ($query) use ($studentId) {
                $query->where('student_id', $studentId)
                      ->whereIn('enrollment_status', ['enrolled', 'completed']);
            })
            ->with(['assignedTeacher', 'subject', 'gradeLevel', 'enrollments' => function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            }])
            ->get();

        return view('student.interactive-courses', compact(
            'availableCourses',
            'enrolledCourses'
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
                    'meeting'
                ])->orderBy('scheduled_date')->orderBy('scheduled_time');
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
        $isEnrolled = null;
        $enrollmentStats = [];

        if ($isStudent) {
            $studentId = $user->studentProfile ? $user->studentProfile->id : $user->id;
            $isEnrolled = $courseModel->enrollments->where('student_id', $studentId)->first();

            // CRITICAL: Students can only view courses they are enrolled in
            if (!$isEnrolled || !in_array($isEnrolled->enrollment_status, ['enrolled', 'completed'])) {
                // Not enrolled or enrollment not active, redirect to public course page
                return redirect()->route('interactive-courses.show', ['subdomain' => $subdomain, 'course' => $course])
                    ->with('error', 'يجب التسجيل في الكورس أولاً للوصول إلى محتواه');
            }

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
                $scheduledDateTime = \Carbon\Carbon::parse(
                    $session->scheduled_date->format('Y-m-d') . ' ' .
                    $session->scheduled_time->format('H:i:s')
                );
                return $scheduledDateTime->gte($now) || $session->status === 'in-progress';
            })
            ->values();

        $pastSessions = $courseModel->sessions
            ->filter(function ($session) use ($now) {
                $scheduledDateTime = \Carbon\Carbon::parse(
                    $session->scheduled_date->format('Y-m-d') . ' ' .
                    $session->scheduled_time->format('H:i:s')
                );
                return $scheduledDateTime->lt($now) && $session->status !== 'in-progress';
            })
            ->sortByDesc(function ($session) {
                return $session->scheduled_date->format('Y-m-d') . ' ' . $session->scheduled_time->format('H:i:s');
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
            'course.teacher.user',
            'course.subject',
            'course.gradeLevel',
            'homework',
            'attendances' => function ($q) use ($user) {
                $q->where('student_id', $user->id);
            },
            'meeting'
        ])->findOrFail($sessionId);

        // Ensure session's course belongs to the academy
        if ($session->course->academy_id !== $academy->id) {
            abort(403, 'Access denied to this session');
        }

        // Verify enrollment in the course
        $enrollment = \App\Models\InteractiveCourseEnrollment::where([
            'course_id' => $session->course_id,
            'student_id' => $user->id,
            'status' => 'active'
        ])->first();

        if (!$enrollment) {
            abort(403, 'You must be enrolled in this course to view sessions');
        }

        $student = $user->studentProfile;

        // Get attendance record for this student
        $attendance = $session->attendances->where('student_id', $student->id)->first();

        // Get homework submission if homework exists
        $homeworkSubmission = null;
        if ($session->homework()->count() > 0) {
            $homework = $session->homework()->first();
            if ($homework) {
                $homeworkSubmission = $homework->submissions()
                    ->where('student_id', $student->id)
                    ->first();
            }
        }

        return view('student.interactive-sessions.show', compact(
            'session',
            'attendance',
            'homeworkSubmission',
            'student',
            'enrollment'
        ));
    }

    public function addInteractiveSessionFeedback(Request $request, $subdomain, $sessionId)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback_text' => 'nullable|string|max:1000'
        ]);

        $user = Auth::user();
        $session = \App\Models\InteractiveCourseSession::findOrFail($sessionId);

        // Verify enrollment and session completion
        $enrollment = \App\Models\InteractiveCourseEnrollment::where([
            'course_id' => $session->course_id,
            'student_id' => $user->id,
            'status' => 'active'
        ])->firstOrFail();

        if ($session->status !== 'completed') {
            return back()->with('error', 'Cannot submit feedback for incomplete session');
        }

        $student = $user->studentProfile;

        // Create or update feedback
        // Note: You may need to create an InteractiveSessionFeedback model
        // For now, we'll use a generic approach that can be adapted
        \DB::table('interactive_session_feedback')->updateOrInsert(
            [
                'session_id' => $sessionId,
                'student_id' => $student->id
            ],
            [
                'rating' => $validated['rating'],
                'feedback_text' => $validated['feedback_text'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        return back()->with('success', 'Feedback submitted successfully');
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
            app(\App\Services\HomeworkService::class)->submitHomework(
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

    public function academicTeachers()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get student's academic subscriptions with teacher info
        $mySubscriptions = AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->with(['academicTeacher'])
            ->get();

        // Get all active and approved academic teachers for this academy
        $academicTeachers = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['user'])
            ->withCount(['interactiveCourses as active_courses_count'])
            ->orderBy('rating', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Calculate additional stats for each teacher
        $academicTeachers->getCollection()->transform(function ($teacher) {
            // Get subjects and grade levels
            $teacher->subjects = $teacher->subjects;
            $teacher->gradeLevels = $teacher->gradeLevels;

            // Calculate average rating from interactive courses or use profile rating
            $teacher->average_rating = $teacher->rating ?? 4.8;

            // Get active students count (this could be expanded based on actual enrollment system)
            $teacher->students_count = $teacher->total_students ?? 0;

            // Format hourly rate
            $teacher->hourly_rate = $teacher->session_price_individual;

            // Set bio from profile or generate default
            $teacher->bio = $teacher->notes ?? 'معلم أكاديمي مؤهل متخصص في التدريس';

            // Set experience years
            $teacher->experience_years = $teacher->teaching_experience_years;

            // Set qualification
            $teacher->qualification = $teacher->qualification_degree ?? $teacher->education_level;

            return $teacher;
        });

        // Get student's academic progress to show which teachers they're learning with
        $academicProgress = AcademicProgress::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher', 'subject', 'subscription'])
            ->get();

        return view('student.academic-teachers', compact(
            'academicTeachers',
            'academicProgress',
            'mySubscriptions'
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

        // Get progress summary using the AcademicProgressService
        $progressService = app(\App\Services\AcademicProgressService::class);
        $progressSummary = $progressService->getProgressSummary($subscription);

        return view('student.academic-subscription-detail', compact('subscription', 'upcomingSessions', 'pastSessions', 'progressSummary'));
    }
}
