<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\QuranCircle;
use App\Models\QuranTeacherProfile;
use App\Models\QuranPackage;
use App\Models\InteractiveCourse;
use App\Models\RecordedCourse;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranSubscription;
use App\Models\QuranTrialRequest;
use App\Models\AcademicProgress;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\Log;

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
            ->with(['teacher', 'students'])
            ->get();

        // Get student's Quran private sessions
        $quranPrivateSessions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('subscription_type', 'private')
            ->with(['quranTeacher', 'package', 'sessions' => function($query) {
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
            ->with(['teacher', 'enrollments' => function($query) use ($user) {
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

        // Calculate statistics
        $stats = $this->calculateStudentStats($user);

        return view('student.profile', compact(
            'quranCircles',
            'quranPrivateSessions',
            'quranTrialRequests',
            'interactiveCourses',
            'recordedCourses',
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
        
        return view('student.edit-profile', compact('studentProfile'));
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
        ]);

        $studentProfile->update($validated);

        return redirect()->route('student.profile', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy'])
            ->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }

    public function settings()
    {
        $user = Auth::user();
        
        return view('student.settings', compact('user'));
    }

    public function subscriptions()
    {
        $user = Auth::user();
        $academy = $user->academy;

        $quranSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['quranTeacher', 'package', 'sessions' => function($query) {
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
            ->with(['teacher', 'enrollments' => function($query) use ($user) {
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
            ->with(['teacher', 'students', 'sessions' => function($query) {
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
            ->where('status', 'available')
            ->with(['teacher', 'students'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Get student's enrolled circles
        $enrolledCircles = QuranCircle::where('academy_id', $academy->id)
            ->whereHas('students', function($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with(['teacher', 'students'])
            ->get();

        return view('student.quran-circles', compact(
            'availableCircles',
            'enrolledCircles'
        ));
    }

    public function quranTeachers()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get all active Quran teachers for this academy
        $quranTeachers = QuranTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->with(['user', 'quranCircles'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Get student's active subscriptions to show which teachers they're already learning with
        $activeSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('subscription_status', 'active')
            ->with('quranTeacher')
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
            ->with(['teacher', 'subject', 'gradeLevel'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Get student's enrolled courses
        $enrolledCourses = InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with(['teacher', 'subject', 'gradeLevel', 'enrollments' => function($query) use ($user) {
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

        $instructors = \App\Models\AcademicTeacherProfile::where('academy_id', $academy->id)
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
} 