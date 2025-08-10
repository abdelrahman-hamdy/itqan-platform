<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\QuranCircle;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\RecordedCourse;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use App\Models\User;
use Carbon\Carbon;

class TeacherProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $academy = $user->academy;
        
        // Determine teacher type and get profile
        $teacherProfile = $this->getTeacherProfile($user);
        
        if (!$teacherProfile) {
            abort(404, 'Teacher profile not found');
        }
        
        // Get teacher-specific data based on type
        if ($user->isQuranTeacher()) {
            $data = $this->getQuranTeacherData($user, $teacherProfile);
        } else {
            $data = $this->getAcademicTeacherData($user, $teacherProfile);
        }
        
        // Calculate statistics
        $stats = $this->calculateTeacherStats($user, $teacherProfile);
        
        return view('teacher.profile', array_merge($data, [
            'teacherProfile' => $teacherProfile,
            'stats' => $stats,
            'academy' => $academy
        ]));
    }
    
    public function earnings()
    {
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);
        
        if (!$teacherProfile) {
            abort(404, 'Teacher profile not found');
        }
        
        // Calculate earnings data
        $earningsData = $this->calculateEarnings($user, $teacherProfile);
        
        return view('teacher.earnings', [
            'teacherProfile' => $teacherProfile,
            'earningsData' => $earningsData
        ]);
    }
    
    public function schedule()
    {
        // Redirect to the unified schedule dashboard
        $subdomain = request()->route('subdomain') ?? 'itqan-academy';
        return redirect()->route('teacher.schedule.dashboard', ['subdomain' => $subdomain]);
    }
    
    public function students()
    {
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);
        
        if (!$teacherProfile) {
            abort(404, 'Teacher profile not found');
        }
        
        // Get teacher's students based on type
        if ($user->isQuranTeacher()) {
            $students = $this->getQuranTeacherStudents($user, $teacherProfile);
        } else {
            $students = $this->getAcademicTeacherStudents($user, $teacherProfile);
        }
        
        return view('teacher.students', [
            'teacherProfile' => $teacherProfile,
            'students' => $students
        ]);
    }

    public function showStudent($subdomain, User $student)
    {
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);
        
        if (!$teacherProfile) {
            abort(404, 'Teacher profile not found');
        }

        // Verify this teacher can access this student
        $canAccessStudent = false;
        
        if ($user->isQuranTeacher()) {
            // Check if teacher has any circles with this student
            $canAccessStudent = \App\Models\QuranIndividualCircle::where('quran_teacher_id', $teacherProfile->id)
                ->where('student_id', $student->id)
                ->exists();
                
            // Also check group circles
            if (!$canAccessStudent) {
                $canAccessStudent = \App\Models\QuranCircle::where('quran_teacher_id', $teacherProfile->id)
                    ->whereHas('students', function($query) use ($student) {
                        $query->where('student_id', $student->id);
                    })
                    ->exists();
            }
        } else {
            // For academic teachers, check if they have any subjects with this student
            // This is a simplified check - you may need to implement proper academic class relationships
            $canAccessStudent = false; // For now, only Quran teachers can access student profiles
        }

        if (!$canAccessStudent) {
            abort(403, 'غير مسموح لك بالوصول لملف هذا الطالب');
        }

        // Load student with profile and relationships
        $student->load([
            'studentProfile',
            'academy',
            'quranCircles' => function($query) use ($teacherProfile, $user) {
                if ($user->isQuranTeacher()) {
                    $query->where('quran_teacher_id', $teacherProfile->id);
                }
            },
            'quranIndividualCircles' => function($query) use ($teacherProfile, $user) {
                if ($user->isQuranTeacher()) {
                    $query->where('quran_teacher_id', $teacherProfile->id)
                          ->with(['sessions' => function($sessionQuery) {
                              $sessionQuery->orderBy('scheduled_at', 'desc');
                          }]);
                }
            }
        ]);

        // Get student's progress and performance data
        $progressData = $this->getStudentProgressData($student, $teacherProfile, $user);

        return view('teacher.student-profile', [
            'student' => $student,
            'teacherProfile' => $teacherProfile,
            'progressData' => $progressData
        ]);
    }

    private function getStudentProgressData($student, $teacherProfile, $user)
    {
        $progressData = [
            'totalSessions' => 0,
            'completedSessions' => 0,
            'upcomingSessions' => 0,
            'circles' => [],
            'recentActivity' => []
        ];

        if ($user->isQuranTeacher()) {
            // Use already loaded individual circles (filtered by teacher in showStudent method)
            $individualCircles = $student->quranIndividualCircles;

            foreach ($individualCircles as $circle) {
                $progressData['totalSessions'] += $circle->total_sessions;
                $progressData['completedSessions'] += $circle->sessions_completed;
                
                $progressData['circles'][] = [
                    'id' => $circle->id,
                    'type' => 'individual',
                    'name' => $circle->name ?? 'الحلقة الفردية',
                    'progress_percentage' => $circle->progress_percentage,
                    'status' => $circle->status,
                    'sessions_completed' => $circle->sessions_completed,
                    'total_sessions' => $circle->total_sessions,
                    'current_surah' => $circle->current_surah,
                    'verses_memorized' => $circle->verses_memorized
                ];

                // Add recent sessions to activity (use already loaded sessions)
                $completedSessions = $circle->sessions->where('status', 'completed')->take(5);
                foreach ($completedSessions as $session) {
                    $progressData['recentActivity'][] = [
                        'type' => 'session_completed',
                        'title' => $session->title ?? 'جلسة مكتملة',
                        'date' => $session->completed_at ?? $session->scheduled_at,
                        'circle_name' => $circle->name ?? 'الحلقة الفردية'
                    ];
                }
            }

            // Use already loaded group circles (filtered by teacher in showStudent method)
            $groupCircles = $student->quranCircles;

            foreach ($groupCircles as $circle) {
                $progressData['circles'][] = [
                    'id' => $circle->id,
                    'type' => 'group',
                    'name' => $circle->name,
                    'level' => $circle->level,
                    'status' => $circle->status
                ];
            }
        }

        // Sort recent activity by date
        usort($progressData['recentActivity'], function($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $progressData;
    }
    
    public function edit()
    {
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);
        
        if (!$teacherProfile) {
            abort(404, 'Teacher profile not found');
        }
        
        return view('teacher.edit-profile', [
            'teacherProfile' => $teacherProfile
        ]);
    }
    
    public function update(Request $request)
    {
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);
        
        if (!$teacherProfile) {
            abort(404, 'Teacher profile not found');
        }
        
        // Validate and update profile
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'bio_arabic' => 'nullable|string',
            'available_days' => 'nullable|array',
            'available_time_start' => 'nullable|date_format:H:i',
            'available_time_end' => 'nullable|date_format:H:i',
        ]);
        
        $teacherProfile->update($validated);
        
        // Also update user info
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? $user->phone,
        ]);
        
        return back()->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }

    
    /**
     * Get teacher profile based on user type
     */
    private function getTeacherProfile($user)
    {
        if ($user->isQuranTeacher()) {
            return QuranTeacherProfile::where('user_id', $user->id)->first();
        } elseif ($user->isAcademicTeacher()) {
            return AcademicTeacherProfile::where('user_id', $user->id)->first();
        }
        
        return null;
    }
    
    /**
     * Get Quran teacher specific data
     */
    private function getQuranTeacherData($user, $teacherProfile)
    {
        $academy = $user->academy;
        
        // Get assigned Quran circles (admin creates and assigns)
        $assignedCircles = QuranCircle::where('quran_teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->with(['students', 'academy'])
            ->get();

        // Get pending trial requests
        $pendingTrialRequests = \App\Models\QuranTrialRequest::where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->whereIn('status', ['pending', 'approved'])
            ->with(['student', 'academy'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get active subscriptions
        $activeSubscriptions = \App\Models\QuranSubscription::where('quran_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->whereIn('subscription_status', ['active', 'pending'])
            ->whereIn('payment_status', ['paid', 'pending'])
            ->with(['student', 'package', 'individualCircle'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get recent sessions
        $recentSessions = \App\Models\QuranSession::where('quran_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->whereIn('status', ['scheduled', 'completed'])
            ->with(['student', 'subscription'])
            ->orderBy('scheduled_at', 'desc')
            ->limit(5)
            ->get();
            
        return [
            'assignedCircles' => $assignedCircles,
            'pendingTrialRequests' => $pendingTrialRequests,
            'activeSubscriptions' => $activeSubscriptions,
            'recentSessions' => $recentSessions,
            'teacherType' => 'quran'
        ];
    }
    
    /**
     * Get Academic teacher specific data
     */
    private function getAcademicTeacherData($user, $teacherProfile)
    {
        $academy = $user->academy;
        
        // Get created courses by teacher
        // Note: InteractiveCourses are only assigned by admin (no teacher creation)
        $createdInteractiveCourses = collect(); // Empty for now - interactive courses are admin-assigned only
            
        $createdRecordedCourses = RecordedCourse::where('instructor_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->with(['enrollments', 'academy'])
            ->get();
            
        // Get assigned courses (admin creates and assigns)
        $assignedInteractiveCourses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->with(['enrollments', 'academy'])
            ->get();
            
        // For now, recorded courses don't have assignment - only creation
        $assignedRecordedCourses = collect(); // Empty for now
            
        return [
            'createdInteractiveCourses' => $createdInteractiveCourses,
            'createdRecordedCourses' => $createdRecordedCourses,
            'assignedInteractiveCourses' => $assignedInteractiveCourses,
            'assignedRecordedCourses' => $assignedRecordedCourses,
            'teacherType' => 'academic'
        ];
    }
    
    /**
     * Calculate teacher statistics
     */
    private function calculateTeacherStats($user, $teacherProfile)
    {
        $academy = $user->academy;
        $currentMonth = Carbon::now()->format('Y-m');
        
        if ($user->isQuranTeacher()) {
            return $this->calculateQuranTeacherStats($user, $teacherProfile, $currentMonth);
        } else {
            return $this->calculateAcademicTeacherStats($user, $teacherProfile, $currentMonth);
        }
    }
    
    /**
     * Calculate Quran teacher stats
     */
    private function calculateQuranTeacherStats($user, $teacherProfile, $currentMonth)
    {
        // Total students from assigned circles
        $totalStudents = User::whereHas('quranCircles', function($query) use ($teacherProfile) {
            $query->where('quran_teacher_id', $teacherProfile->id);
        })->count();
        
        // Active circles
        $activeCircles = QuranCircle::where('quran_teacher_id', $teacherProfile->id)
            ->where('status', 'active')
            ->count();
            
        // This month sessions
        $thisMonthSessions = 0; // TODO: Implement when session tracking is ready
        
        // Monthly earnings
        $monthlyEarnings = $this->calculateMonthlyEarnings($user, $teacherProfile, $currentMonth);
        
        return [
            'totalStudents' => $totalStudents,
            'activeCircles' => $activeCircles,
            'thisMonthSessions' => $thisMonthSessions,
            'monthlyEarnings' => $monthlyEarnings,
            'teacherRating' => $teacherProfile->rating ?? 0,
        ];
    }
    
    /**
     * Calculate Academic teacher stats
     */
    private function calculateAcademicTeacherStats($user, $teacherProfile, $currentMonth)
    {
        // Total students from courses
        $totalStudents = User::whereHas('interactiveCourseEnrollments', function($query) use ($teacherProfile) {
            $query->whereHas('course', function($q) use ($teacherProfile) {
                $q->where('assigned_teacher_id', $teacherProfile->id);
            });
        })->orWhereHas('recordedCourseEnrollments', function($query) use ($teacherProfile) {
            $query->whereHas('recordedCourse', function($q) use ($teacherProfile) {
                $q->where('instructor_id', $teacherProfile->id);
            });
        })->count();
        
        // Active courses (both created and assigned)
        $activeCourses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->where('status', 'active')
            ->count();
        
        $activeCourses += RecordedCourse::where('instructor_id', $teacherProfile->id)
            ->where('status', 'active')
            ->count();
        
        // This month sessions
        $thisMonthSessions = 0; // TODO: Implement when session tracking is ready
        
        // Monthly earnings
        $monthlyEarnings = $this->calculateMonthlyEarnings($user, $teacherProfile, $currentMonth);
        
        return [
            'totalStudents' => $totalStudents,
            'activeCourses' => $activeCourses,
            'thisMonthSessions' => $thisMonthSessions,
            'monthlyEarnings' => $monthlyEarnings,
            'teacherRating' => $teacherProfile->rating ?? 0,
        ];
    }
    
    /**
     * Calculate earnings for teacher
     */
    private function calculateEarnings($user, $teacherProfile)
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $lastMonth = Carbon::now()->subMonth()->format('Y-m');
        
        // Get session price set by admin (from teacher profile or academy settings)
        $sessionPrice = $this->getTeacherSessionPrice($teacherProfile);
        
        // Current month finished sessions
        $currentMonthSessions = $this->getFinishedSessions($user, $currentMonth);
        $currentMonthEarnings = $currentMonthSessions * $sessionPrice;
        
        // Last month for comparison
        $lastMonthSessions = $this->getFinishedSessions($user, $lastMonth);
        $lastMonthEarnings = $lastMonthSessions * $sessionPrice;
        
        // Calculate growth
        $earningsGrowth = $lastMonthEarnings > 0 ? 
            (($currentMonthEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100 : 0;
        
        // Total earnings (all time)
        $totalEarnings = $this->getTotalEarnings($user, $sessionPrice);
        
        return [
            'sessionPrice' => $sessionPrice,
            'currentMonthSessions' => $currentMonthSessions,
            'currentMonthEarnings' => $currentMonthEarnings,
            'lastMonthEarnings' => $lastMonthEarnings,
            'earningsGrowth' => round($earningsGrowth, 1),
            'totalEarnings' => $totalEarnings,
            'currency' => 'ر.س', // Default to SAR, could be configurable
        ];
    }
    
    /**
     * Get teacher session price (set by admin)
     */
    private function getTeacherSessionPrice($teacherProfile)
    {
        // Price could be stored in teacher profile or academy settings
        // For now, return a default price - this should be configurable by admin
        if (isset($teacherProfile->session_price_individual)) {
            return $teacherProfile->session_price_individual;
        }
        
        // Default prices if not set
        return 100; // 100 SAR per session as default
    }
    
    /**
     * Get finished sessions count for a specific month
     */
    private function getFinishedSessions($user, $month)
    {
        // TODO: Implement when session tracking models are ready
        // This would query QuranSession or AcademicSession tables
        // for sessions where teacher_id = $user->id AND status = 'completed' 
        // AND created_at LIKE '$month%'
        
        // For now return sample data
        return rand(15, 30);
    }
    
    /**
     * Calculate monthly earnings
     */
    private function calculateMonthlyEarnings($user, $teacherProfile, $month)
    {
        $sessionPrice = $this->getTeacherSessionPrice($teacherProfile);
        $finishedSessions = $this->getFinishedSessions($user, $month);
        
        return $finishedSessions * $sessionPrice;
    }
    
    /**
     * Get total earnings (all time)
     */
    private function getTotalEarnings($user, $sessionPrice)
    {
        // TODO: Implement when session tracking is ready
        // This would sum all completed sessions for this teacher
        
        // For now return sample data
        return $sessionPrice * rand(100, 300);
    }
    
    /**
     * Get upcoming sessions
     */
    private function getUpcomingSessions($user, $teacherProfile)
    {
        // TODO: Implement when session scheduling is ready
        // This would return upcoming scheduled sessions
        
        return collect(); // Empty collection for now
    }
    
    /**
     * Get Quran teacher students
     */
    private function getQuranTeacherStudents($user, $teacherProfile)
    {
        return User::whereHas('quranCircles', function($query) use ($teacherProfile) {
            $query->where('quran_teacher_id', $teacherProfile->id);
        })->with(['studentProfile', 'quranCircles' => function($query) use ($teacherProfile) {
            $query->where('quran_teacher_id', $teacherProfile->id);
        }])->get();
    }
    
    /**
     * Get Academic teacher students
     */
    private function getAcademicTeacherStudents($user, $teacherProfile)
    {
        return User::whereHas('interactiveCourseEnrollments', function($query) use ($teacherProfile) {
            $query->whereHas('course', function($q) use ($teacherProfile) {
                $q->where('assigned_teacher_id', $teacherProfile->id);
            });
        })->orWhereHas('recordedCourseEnrollments', function($query) use ($teacherProfile) {
            $query->whereHas('recordedCourse', function($q) use ($teacherProfile) {
                $q->where('instructor_id', $teacherProfile->id);
            });
        })->with(['studentProfile'])->get();
    }
}