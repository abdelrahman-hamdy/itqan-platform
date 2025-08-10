<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\QuranCircle;
use App\Models\QuranTeacherProfile;
use App\Models\QuranPackage;
use App\Models\InteractiveCourse;
use App\Models\RecordedCourse;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranSubscription;
use App\Models\QuranTrialRequest;
use App\Models\QuranSession;
use App\Models\CourseSubscription;
use App\Models\AcademicProgress;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;

class StudentProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $studentProfile = $user->studentProfileUnscoped;
        $academy = $user->academy;

        // Get student's Quran circles
        $quranCircles = QuranCircle::where('academy_id', $academy->id)
            ->whereHas('students', function($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with(['quranTeacher.user', 'students'])
            ->get();

        // Get student's Quran private sessions
        $quranPrivateSessions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('subscription_type', 'individual')
            ->with(['quranTeacher', 'package', 'individualCircle', 'sessions' => function($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(5);
            }])
            ->get();

        // Get student's Quran trial requests
        $quranTrialRequests = QuranTrialRequest::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get student's interactive courses
        $interactiveCourses = InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with(['assignedTeacher', 'enrollments' => function($query) use ($user) {
                $query->where('student_id', $user->id);
            }])
            ->get();

        // Get student's recorded courses
        $recordedCourses = RecordedCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with([
                'enrollments' => function($query) use ($user) {
                    $query->where('student_id', $user->id)
                          ->select('id', 'recorded_course_id', 'student_id', 'status', 'progress_percentage', 
                                   'completed_lessons', 'total_lessons', 'watch_time_minutes');
                },
                'instructor' => function($query) {
                    $query->select('id', 'user_id');
                },
                'instructor.user' => function($query) {
                    $query->select('id', 'first_name', 'last_name');
                }
            ])
            ->select('id', 'academy_id', 'instructor_id', 'title', 'total_lessons')
            ->get();

        // Get student's academic private sessions (using dummy data for now)
        // TODO: Replace with actual academic sessions model when available
        $academicPrivateSessions = collect();

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
            ->whereHas('enrollments', function($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->count();

        // Count active recorded courses
        $activeRecordedCourses = RecordedCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function($query) use ($user) {
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
            ->whereHas('students', function($query) use ($user) {
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
        if (!$studentProfile) {
            // Try to create a basic student profile if one doesn't exist
            $studentProfile = $this->createBasicStudentProfile($user);
            
            if (!$studentProfile) {
                return redirect()->route('student.profile')
                    ->with('error', 'لم يتم العثور على الملف الشخصي للطالب. يرجى التواصل مع الدعم الفني.');
            }
        }
        
        // Get grade levels for the current academy
        $gradeLevels = \App\Models\GradeLevel::forAcademy($user->academy_id)
            ->active()
            ->ordered()
            ->get();
            
        return view('student.edit-profile', compact('studentProfile', 'gradeLevels'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $studentProfile = $user->studentProfileUnscoped;
        
        // Handle case where student profile doesn't exist or was orphaned
        if (!$studentProfile) {
            $studentProfile = $this->createBasicStudentProfile($user);
            
            if (!$studentProfile) {
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
            'nationality' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:20',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
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
            ->with(['quranTeacher', 'package', 'individualCircle', 'sessions' => function($query) {
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
            ->whereHas('enrollments', function($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with(['assignedTeacher', 'enrollments' => function($query) use ($user) {
                $query->where('student_id', $user->id);
            }])
            ->get();

        return view('student.subscriptions', compact('quranSubscriptions', 'quranTrialRequests', 'courseEnrollments'));
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
                'method' => 'بطاقة ائتمان'
            ],
            [
                'id' => 2,
                'amount' => 200,
                'currency' => 'SAR',
                'description' => 'كورس الرياضيات التفاعلي',
                'status' => 'completed',
                'date' => now()->subDays(15),
                'method' => 'تحويل بنكي'
            ]
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
                'status' => 'issued'
            ],
            [
                'id' => 2,
                'title' => 'شهادة إتمام كورس الرياضيات',
                'course' => 'الرياضيات للصف الثالث',
                'teacher' => 'الأستاذة ليلى محمد',
                'date' => now()->subDays(15),
                'status' => 'issued'
            ]
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
            $studentCode = 'STU' . str_pad($user->id, 6, '0', STR_PAD_LEFT);
            
            // Check for existing student code and make it unique
            $counter = 1;
            $originalCode = $studentCode;
            while (StudentProfile::where('student_code', $studentCode)->exists()) {
                $studentCode = $originalCode . '-' . $counter;
                $counter++;
            }
            
            // Find the default grade level for the user's academy
            $defaultGradeLevel = \App\Models\GradeLevel::where('academy_id', $user->academy_id)
                ->where('is_active', true)
                ->orderBy('level')
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
                'academic_status' => 'enrolled',
                'notes' => 'تم إنشاء الملف الشخصي تلقائياً بعد حل مشكلة البيانات المفقودة'
            ]);
            
            return $studentProfile;
            
        } catch (\Exception $e) {
            Log::error('Failed to create basic student profile for user ' . $user->id . ': ' . $e->getMessage());
            return null;
        }
    }

    public function quranProfile()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get student's Quran circles
        $quranCircles = QuranCircle::where('academy_id', $academy->id)
            ->whereHas('students', function($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with(['quranTeacher.user', 'students', 'sessions' => function($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(5);
            }])
            ->get();

        // Get student's Quran subscriptions
        $quranSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['quranTeacher', 'package', 'sessions' => function($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(10);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get student's Quran trial requests
        $quranTrialRequests = QuranTrialRequest::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate Quran-specific statistics
        $quranStats = [
            'totalCircles' => $quranCircles->count(),
            'activeSubscriptions' => $quranSubscriptions->where('subscription_status', 'active')->count(),
            'completedSessions' => $quranSubscriptions->sum(function($subscription) {
                return $subscription->sessions->where('status', 'completed')->count();
            }),
            'totalTrialRequests' => $quranTrialRequests->count(),
            'averageProgress' => round($quranSubscriptions->avg('progress_percentage') ?? 0, 1),
            'totalVersesMemorized' => $quranSubscriptions->sum('verses_memorized'),
            'upcomingSessions' => $quranSubscriptions->sum(function($subscription) {
                return $subscription->sessions->where('scheduled_at', '>', now())->where('status', 'scheduled')->count();
            }),
        ];

        return view('student.quran-profile', compact(
            'quranCircles',
            'quranSubscriptions', 
            'quranTrialRequests',
            'quranStats'
        ));
    }

    public function quranCircles()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get all available Quran circles for this academy
        $availableCircles = QuranCircle::where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('enrollment_status', 'open')
            ->with(['quranTeacher', 'students'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Get student's enrolled circles
        $enrolledCircles = QuranCircle::where('academy_id', $academy->id)
            ->whereHas('students', function($query) use ($user) {
                $query->where('users.id', $user->id);
            })
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

        if (!$circle) {
            abort(404, 'Circle not found');
        }

        // Check if student is already enrolled
        $isEnrolled = $circle->students()->where('users.id', $user->id)->exists();

        // Check if circle is available for enrollment
        $canEnroll = $circle->status === 'active' && 
                     $circle->enrollment_status === 'open' && 
                     $circle->enrolled_students < $circle->max_students &&
                     !$isEnrolled;

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
            $upcomingSessions = $allSessions->where('scheduled_at', '>', $now)->take(10);
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
            ->with(['subscription', 'quranTeacher', 'sessions' => function($query) {
                $query->orderBy('scheduled_at', 'asc');
            }])
            ->first();

        if (!$individualCircle) {
            abort(404, 'Individual circle not found or you do not have access');
        }

        // Get upcoming and past sessions
        $now = now();
        $allSessions = $individualCircle->sessions;
        
        $upcomingSessions = $allSessions->where('scheduled_at', '>', $now)
            ->where('status', '!=', 'cancelled')
            ->take(10);
            
        $pastSessions = $allSessions->where('scheduled_at', '<=', $now)
            ->whereIn('status', ['completed', 'attended'])
            ->sortByDesc('scheduled_at')
            ->take(5);
            
        $templateSessions = $allSessions->where('is_template', true)
            ->where('is_scheduled', false);

        return view('student.individual-circle-detail', compact(
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

        // Check if student is already enrolled
        $isEnrolled = $circle->students()->where('users.id', $user->id)->exists();
        if ($isEnrolled) {
            return response()->json(['error' => 'You are already enrolled in this circle'], 400);
        }

        // Check if circle is available for enrollment
        if ($circle->status !== 'active' || $circle->enrollment_status !== 'open') {
            return response()->json(['error' => 'This circle is not open for enrollment'], 400);
        }

        if ($circle->enrolled_students >= $circle->max_students) {
            return response()->json(['error' => 'This circle is full'], 400);
        }

        try {
            DB::transaction(function() use ($circle, $user) {
                // Enroll student in circle
                $circle->students()->attach($user->id, [
                    'enrolled_at' => now(),
                    'status' => 'enrolled',
                    'attendance_count' => 0,
                    'missed_sessions' => 0,
                    'makeup_sessions_used' => 0,
                    'current_level' => 'beginner'
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
                'redirect_url' => route('student.quran-circles', ['subdomain' => $academy->subdomain])
            ]);

        } catch (\Exception $e) {
            Log::error('Error enrolling student in circle: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى'
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

        // Check if student is enrolled
        $isEnrolled = $circle->students()->where('users.id', $user->id)->exists();
        if (!$isEnrolled) {
            return response()->json(['error' => 'You are not enrolled in this circle'], 400);
        }

        try {
            DB::transaction(function() use ($circle, $user) {
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
                'redirect_url' => route('student.quran-circles', ['subdomain' => $academy->subdomain])
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing student from circle: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'حدث خطأ أثناء إلغاء التسجيل. يرجى المحاولة مرة أخرى'
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
        $quranTeachers = QuranTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->whereNotIn('id', $subscribedTeacherIds)
            ->with(['user', 'quranCircles', 'quranSessions'])
            ->withCount(['quranSessions as total_sessions'])
            ->orderBy('rating', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Calculate additional stats for each teacher
        $quranTeachers->getCollection()->transform(function ($teacher) {
            // Count active students from subscriptions
            $activeStudents = QuranSubscription::where('quran_teacher_id', $teacher->id)
                ->where('subscription_status', 'active')
                ->distinct('student_id')
                ->count();
            
            $teacher->active_students_count = $activeStudents;
            
            // Get average rating from subscriptions reviews
            $averageRating = QuranSubscription::where('quran_teacher_id', $teacher->id)
                ->whereNotNull('rating')
                ->avg('rating');
            
            $teacher->average_rating = $averageRating ? round($averageRating, 1) : null;
            
            return $teacher;
        });

        // Get student's active subscriptions to show which teachers they're already learning with
        $activeSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('subscription_status', ['active', 'pending'])
            ->with(['quranTeacher.user', 'package', 'sessions'])
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

        // Get all available interactive courses for this academy
        $availableCourses = InteractiveCourse::where('academy_id', $academy->id)
            ->where('status', 'available')
            ->with(['assignedTeacher', 'subject', 'gradeLevel'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Get student's enrolled courses
        $enrolledCourses = InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with(['assignedTeacher', 'subject', 'gradeLevel', 'enrollments' => function($query) use ($user) {
                $query->where('student_id', $user->id);
            }])
            ->get();

        return view('student.interactive-courses', compact(
            'availableCourses',
            'enrolledCourses'
        ));
    }

    public function academicTeachers()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get all active academic teachers for this academy
        $academicTeachers = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Get student's academic progress to show which teachers they're learning with
        $academicProgress = AcademicProgress::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher', 'course'])
            ->get();

        return view('student.academic-teachers', compact(
            'academicTeachers',
            'academicProgress'
        ));
    }

    public function recordedCourses()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get search and filter parameters
        $search = request('search');
        $category = request('category');
        $level = request('level');
        $instructor = request('instructor');
        $status = request('status', 'all');

        // Base query for recorded courses
        $query = RecordedCourse::where('academy_id', $academy->id)
            ->where('status', 'published')
            ->with([
                'instructor.user',
                'enrollments' => function($enrollmentQuery) use ($user) {
                    $enrollmentQuery->where('student_id', $user->id);
                }
            ]);

        // Apply search filter
        if ($search) {
            $query->where(function($searchQuery) use ($search) {
                $searchQuery->where('title', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply category filter
        if ($category) {
            $query->where('category', $category);
        }

        // Apply level filter
        if ($level) {
            $query->where('level', $level);
        }

        // Apply instructor filter
        if ($instructor) {
            $query->where('instructor_id', $instructor);
        }

        // Apply enrollment status filter
        if ($status !== 'all') {
            if ($status === 'enrolled') {
                $query->whereHas('enrollments', function($enrollmentQuery) use ($user) {
                    $enrollmentQuery->where('student_id', $user->id);
                });
            } elseif ($status === 'not_enrolled') {
                $query->whereDoesntHave('enrollments', function($enrollmentQuery) use ($user) {
                    $enrollmentQuery->where('student_id', $user->id);
                });
            }
        }

        // Get paginated results
        $courses = $query->orderBy('created_at', 'desc')->paginate(12);

        // Get filter options
        $categories = RecordedCourse::where('academy_id', $academy->id)
            ->where('status', 'published')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort();

        $levels = RecordedCourse::where('academy_id', $academy->id)
            ->where('status', 'published')
            ->distinct()
            ->pluck('level')
            ->filter()
            ->sort();

        $instructors = \App\Models\AcademicTeacher::where('academy_id', $academy->id)
            ->whereHas('recordedCourses', function($courseQuery) {
                $courseQuery->where('status', 'published');
            })
            ->with('user')
            ->get();

        return view('student.recorded-courses', compact(
            'courses',
            'categories',
            'levels',
            'instructors',
            'academy'
        ));
    }

    /**
     * Show Quran sessions for a specific subscription
     */
    public function quranSessions(Request $request, $subscriptionId = null)
    {
        // Extract subscription ID from URL path if parameter binding failed
        if ($subscriptionId === 'itqan-academy' || $subscriptionId === $request->route('subdomain')) {
            $pathSegments = explode('/', trim($request->path(), '/'));
            $subscriptionId = end($pathSegments);
        }

        Log::info('QuranSessions method called - FIXED', [
            'original_param' => $request->route('subscriptionId'),
            'extracted_subscriptionId' => $subscriptionId,
            'path' => $request->path(),
            'url' => $request->url(),
            'user_id' => Auth::id()
        ]);

        $user = Auth::user();
        $academy = $user->academy;
        
        // Find the subscription and ensure it belongs to the current user and academy
        $subscription = QuranSubscription::where('id', $subscriptionId)
            ->where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->firstOrFail();

        // Get sessions for this subscription
        $sessions = $subscription->sessions()
            ->with(['quranTeacher'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate(10);

        return view('student.quran-sessions', compact('subscription', 'sessions'));
    }

    /**
     * Show course detail page for student
     */
    public function courseShow(Request $request, $courseId)
    {
        $user = Auth::user();
        $academy = $request->academy;

        // Find the course
        $course = RecordedCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('status', 'published')
            ->with(['instructor', 'category', 'sections.lessons'])
            ->first();

        if (!$course) {
            abort(404, 'Course not found');
        }

        // Check if user is enrolled
        $enrollment = CourseSubscription::where('student_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        return view('student.course-detail', compact('course', 'enrollment', 'academy'));
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

        if (!$enrollment) {
            abort(404, 'Certificate not found or course not completed');
        }

        // Generate certificate (placeholder for now)
        // In a real implementation, you would generate a PDF certificate
        return response()->json([
            'message' => 'Certificate download functionality will be implemented soon',
            'enrollment' => $enrollment->id,
            'course' => $enrollment->course->title,
            'completion_date' => $enrollment->completed_at?->format('Y-m-d')
        ]);
    }
} 