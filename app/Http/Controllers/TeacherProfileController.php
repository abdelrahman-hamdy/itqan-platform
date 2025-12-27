<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\SessionStatus;

class TeacherProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Determine teacher type and get profile
        $teacherProfile = $this->getTeacherProfile($user);

        if (! $teacherProfile) {
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
            'academy' => $academy,
        ]));
    }

    public function earnings(Request $request)
    {
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);
        $academy = $user->academy;

        if (! $teacherProfile) {
            abort(404, 'Teacher profile not found');
        }

        // Determine teacher type
        $teacherType = $user->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';
        $teacherId = $teacherProfile->id;
        $academyId = $user->academy_id;

        // Get currency from academy settings or default
        $currencyLabel = $academy->currency?->getLabel() ?? 'ريال سعودي (SAR)';

        // Get timezone from academy settings or default
        $timezone = $academy->timezone?->value ?? 'Asia/Riyadh';

        // Get month filter (default to current month)
        $selectedMonth = $request->get('month', now()->format('Y-m'));
        $isAllTime = $selectedMonth === 'all';

        // Parse selected month
        if (!$isAllTime) {
            [$year, $month] = explode('-', $selectedMonth);
            $year = (int) $year;
            $month = (int) $month;
        } else {
            $year = null;
            $month = null;
        }

        // Get available months for filter
        $availableMonths = $this->getAvailableMonths($teacherType, $teacherId, $academyId);

        // Get real earnings data
        $earningsStats = $this->getRealEarningsStats($teacherType, $teacherId, $academyId, $currencyLabel, $year, $month);

        // Get earnings grouped by source (circle/course/class)
        $earningsBySource = $this->getEarningsGroupedBySource($teacherType, $teacherId, $academyId, $user, $year, $month);

        // Get payout history
        $payoutHistory = $this->getPayoutHistory($teacherType, $teacherId, $academyId);

        // Get current month payout
        $currentMonthPayout = TeacherPayout::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->whereYear('payout_month', now()->year)
            ->whereMonth('payout_month', now()->month)
            ->first();

        return view('teacher.earnings', [
            'teacherProfile' => $teacherProfile,
            'teacherType' => $teacherType,
            'academy' => $academy,
            'currency' => $currencyLabel,
            'timezone' => $timezone,
            'stats' => $earningsStats,
            'earningsBySource' => $earningsBySource,
            'payoutHistory' => $payoutHistory,
            'currentMonthPayout' => $currentMonthPayout,
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'isAllTime' => $isAllTime,
        ]);
    }

    public function schedule()
    {
        // Redirect to the teacher profile page
        $subdomain = request()->route('subdomain') ?? 'itqan-academy';

        return redirect()->route('teacher.profile', ['subdomain' => $subdomain]);
    }

    public function students()
    {
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);

        if (! $teacherProfile) {
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
            'students' => $students,
        ]);
    }

    public function showStudent($subdomain, User $student)
    {
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);

        if (! $teacherProfile) {
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
            if (! $canAccessStudent) {
                $canAccessStudent = \App\Models\QuranCircle::where('quran_teacher_id', $teacherProfile->id)
                    ->whereHas('students', function ($query) use ($student) {
                        $query->where('student_id', $student->id);
                    })
                    ->exists();
            }
        } else {
            // For academic teachers, check if they have any subjects with this student
            // This is a simplified check - you may need to implement proper academic class relationships
            $canAccessStudent = false; // For now, only Quran teachers can access student profiles
        }

        if (! $canAccessStudent) {
            abort(403, 'غير مسموح لك بالوصول لملف هذا الطالب');
        }

        // Load student with profile and relationships
        $student->load([
            'studentProfile',
            'academy',
            'quranCircles' => function ($query) use ($user) {
                if ($user->isQuranTeacher()) {
                    $query->where('quran_teacher_id', $user->id);
                }
            },
            'quranIndividualCircles' => function ($query) use ($user) {
                if ($user->isQuranTeacher()) {
                    $query->where('quran_teacher_id', $user->id)
                        ->with(['sessions' => function ($sessionQuery) {
                            $sessionQuery->orderBy('scheduled_at', 'desc');
                        }]);
                }
            },
        ]);

        // Get student's progress and performance data
        $progressData = $this->getStudentProgressData($student, $teacherProfile, $user);

        return view('teacher.student-profile', [
            'student' => $student,
            'teacherProfile' => $teacherProfile,
            'progressData' => $progressData,
        ]);
    }

    private function getStudentProgressData($student, $teacherProfile, $user)
    {
        $progressData = [
            'totalSessions' => 0,
            'completedSessions' => 0,
            'upcomingSessions' => 0,
            'circles' => [],
            'recentActivity' => [],
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
                    'verses_memorized' => $circle->verses_memorized,
                ];

                // Add recent sessions to activity (use already loaded sessions)
                $completedSessions = $circle->sessions->where('status', SessionStatus::COMPLETED->value)->take(5);
                foreach ($completedSessions as $session) {
                    $progressData['recentActivity'][] = [
                        'type' => 'session_completed',
                        'title' => $session->title ?? 'جلسة مكتملة',
                        'date' => $session->completed_at ?? $session->scheduled_at,
                        'circle_name' => $circle->name ?? 'الحلقة الفردية',
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
                    'status' => $circle->status,
                ];
            }
        }

        // Sort recent activity by date
        usort($progressData['recentActivity'], function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $progressData;
    }

    public function edit()
    {
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);

        if (! $teacherProfile) {
            abort(404, 'Teacher profile not found');
        }

        return view('teacher.edit-profile', [
            'teacherProfile' => $teacherProfile,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $teacherProfile = $this->getTeacherProfile($user);

        if (! $teacherProfile) {
            abort(404, 'Teacher profile not found');
        }

        // Build validation rules based on teacher type
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'bio_arabic' => 'nullable|string|max:1000',
            'bio_english' => 'nullable|string|max:1000',
            'available_days' => 'nullable|array',
            'available_time_start' => 'nullable|date_format:H:i',
            'available_time_end' => 'nullable|date_format:H:i',
            'teaching_experience_years' => 'nullable|integer|min:0|max:50',
            'certifications' => 'nullable|array',
            'languages' => 'nullable|array',
        ];

        // Add Quran teacher specific fields
        if ($user->isQuranTeacher()) {
            $rules['educational_qualification'] = 'nullable|string|in:bachelor,master,phd,diploma,other';
        }

        // Add Academic teacher specific fields
        if ($user->isAcademicTeacher()) {
            $rules['education_level'] = 'nullable|string|in:diploma,bachelor,master,phd,other';
            $rules['university'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($teacherProfile->avatar && \Storage::disk('public')->exists($teacherProfile->avatar)) {
                \Storage::disk('public')->delete($teacherProfile->avatar);
            }

            $path = $request->file('avatar')->store(
                $user->isQuranTeacher() ? 'avatars/quran-teachers' : 'avatars/academic-teachers',
                'public'
            );
            $validated['avatar'] = $path;
        }

        // Update teacher profile
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
        $assignedCircles = QuranCircle::where('quran_teacher_id', $user->id)
            ->where('academy_id', $user->academy_id)
            ->with(['students', 'academy'])
            ->get();

        // Get pending and scheduled trial requests
        $pendingTrialRequests = \App\Models\QuranTrialRequest::where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->whereIn('status', ['pending', 'approved', 'scheduled'])
            ->with(['student', 'academy', 'trialSession'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get active subscriptions
        $activeSubscriptions = \App\Models\QuranSubscription::where('quran_teacher_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('status', ['active', 'pending'])
            ->whereIn('payment_status', ['paid', 'pending'])
            ->with(['student', 'package', 'individualCircle'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get recent sessions
        $recentSessions = \App\Models\QuranSession::where('quran_teacher_id', $user->id)
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
            'teacherType' => 'quran',
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

        $createdRecordedCourses = collect(); // Recorded courses are not linked to specific teachers

        // Get assigned courses (admin creates and assigns)
        $assignedInteractiveCourses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->with(['enrollments', 'academy'])
            ->orderBy('created_at', 'desc')
            ->get();

        // For now, recorded courses don't have assignment - only creation
        $assignedRecordedCourses = collect(); // Empty for now

        // Get private academic lessons (subscriptions)
        $privateLessons = \App\Models\AcademicSubscription::where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $academy->id)
            ->with(['student', 'subject', 'gradeLevel'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'createdInteractiveCourses' => $createdInteractiveCourses,
            'createdRecordedCourses' => $createdRecordedCourses,
            'assignedInteractiveCourses' => $assignedInteractiveCourses,
            'assignedRecordedCourses' => $assignedRecordedCourses,
            'privateLessons' => $privateLessons,
            'teacherType' => 'academic',
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
        $totalStudents = User::whereHas('quranCircles', function ($query) use ($user) {
            $query->where('quran_teacher_id', $user->id);
        })->count();

        // Active circles
        $activeCircles = QuranCircle::where('quran_teacher_id', $user->id)
            ->where('status', true)
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
        $totalStudents = User::whereHas('interactiveCourseEnrollments', function ($query) use ($teacherProfile) {
            $query->whereHas('course', function ($q) use ($teacherProfile) {
                $q->where('assigned_teacher_id', $teacherProfile->id);
            });
        })->count();

        // Active courses (both created and assigned)
        $activeCourses = InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
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
     * Get real earnings statistics from TeacherEarning model
     */
    private function getRealEarningsStats(string $teacherType, int $teacherId, int $academyId, string $currency, ?int $year = null, ?int $month = null): array
    {
        $baseQuery = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId);

        // If year and month are provided, filter by them
        if ($year && $month) {
            $selectedMonthEarnings = (clone $baseQuery)->forMonth($year, $month)->sum('amount');
            $selectedMonthSessions = (clone $baseQuery)->forMonth($year, $month)->count();

            // Get previous month for comparison
            $prevDate = Carbon::create($year, $month, 1)->subMonth();
            $prevMonthEarnings = (clone $baseQuery)->forMonth($prevDate->year, $prevDate->month)->sum('amount');

            $changePercent = 0;
            if ($prevMonthEarnings > 0) {
                $changePercent = (($selectedMonthEarnings - $prevMonthEarnings) / $prevMonthEarnings) * 100;
            }
        } else {
            // All time
            $selectedMonthEarnings = $baseQuery->sum('amount');
            $selectedMonthSessions = $baseQuery->count();
            $changePercent = 0;
        }

        // All-time earnings (always show total)
        $allTimeEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->sum('amount');

        // Unpaid earnings (always show all unpaid, not filtered by month)
        $unpaidEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->unpaid()
            ->sum('amount');

        // Total paid earnings
        $paidEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->whereNotNull('payout_id')
            ->sum('amount');

        // Last payout status
        $lastPayout = TeacherPayout::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->latest('payout_month')
            ->first();

        return [
            'selectedMonth' => $selectedMonthEarnings,
            'changePercent' => round($changePercent, 1),
            'allTimeEarnings' => $allTimeEarnings,
            'sessionsCount' => $selectedMonthSessions,
            'unpaidEarnings' => $unpaidEarnings,
            'paidEarnings' => $paidEarnings,
            'lastPayout' => $lastPayout,
        ];
    }

    /**
     * Get earnings grouped by source (circle/course/class)
     */
    private function getEarningsGroupedBySource(string $teacherType, int $teacherId, int $academyId, $user, ?int $year = null, ?int $month = null)
    {
        $query = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->with(['session']);

        // Apply month filter if provided
        if ($year && $month) {
            $query->forMonth($year, $month);
        }

        $earnings = $query->get();

        $grouped = [];

        foreach ($earnings as $earning) {
            $source = $this->determineEarningSource($earning, $user);

            if (!isset($grouped[$source['key']])) {
                $grouped[$source['key']] = [
                    'name' => $source['name'],
                    'type' => $source['type'],
                    'total' => 0,
                    'sessions_count' => 0,
                    'earnings' => collect([]),
                ];
            }

            $grouped[$source['key']]['total'] += $earning->amount;
            $grouped[$source['key']]['sessions_count']++;
            $grouped[$source['key']]['earnings']->push($earning);
        }

        return collect($grouped)->sortByDesc('total');
    }

    /**
     * Get available months for filtering
     */
    private function getAvailableMonths(string $teacherType, int $teacherId, int $academyId): array
    {
        // Get all unique months where teacher has earnings
        $months = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->selectRaw('YEAR(session_completed_at) as year, MONTH(session_completed_at) as month')
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $availableMonths = [];

        foreach ($months as $monthData) {
            if ($monthData->year && $monthData->month) {
                $date = Carbon::create($monthData->year, $monthData->month, 1);
                $availableMonths[] = [
                    'value' => $date->format('Y-m'),
                    'label' => $date->locale('ar')->translatedFormat('F Y'),
                ];
            }
        }

        return $availableMonths;
    }

    /**
     * Determine the source of an earning (which circle/course/class)
     */
    private function determineEarningSource($earning, $user)
    {
        $session = $earning->session;

        if (!$session) {
            return [
                'key' => 'unknown',
                'name' => 'مصدر غير معروف',
                'type' => 'unknown',
            ];
        }

        // Quran Session
        if ($session instanceof \App\Models\QuranSession) {
            if ($session->individualCircle) {
                return [
                    'key' => 'individual_circle_' . $session->individualCircle->id,
                    'name' => $session->individualCircle->name ?? 'حلقة فردية - ' . $session->student?->name,
                    'type' => 'individual_circle',
                ];
            } elseif ($session->circle) {
                return [
                    'key' => 'group_circle_' . $session->circle->id,
                    'name' => $session->circle->name,
                    'type' => 'group_circle',
                ];
            }
        }

        // Academic Session
        if ($session instanceof \App\Models\AcademicSession) {
            $lessonName = $session->academicIndividualLesson
                ? ($session->academicIndividualLesson->subject?->name . ' - ' . $session->student?->name)
                : 'درس أكاديمي - ' . $session->student?->name;

            return [
                'key' => 'academic_lesson_' . ($session->academic_individual_lesson_id ?? $session->id),
                'name' => $lessonName,
                'type' => 'academic_lesson',
            ];
        }

        // Interactive Course Session
        if ($session instanceof \App\Models\InteractiveCourseSession) {
            return [
                'key' => 'interactive_course_' . $session->course->id,
                'name' => $session->course->title,
                'type' => 'interactive_course',
            ];
        }

        return [
            'key' => 'other_' . $session->id,
            'name' => 'جلسة - ' . $session->id,
            'type' => 'other',
        ];
    }

    /**
     * Get all earnings with full details
     */
    private function getAllEarningsWithDetails(string $teacherType, int $teacherId, int $academyId)
    {
        return TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->with(['session', 'payout'])
            ->latest('session_completed_at')
            ->get();
    }

    /**
     * Get payout history
     */
    private function getPayoutHistory(string $teacherType, int $teacherId, int $academyId)
    {
        return TeacherPayout::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->latest('payout_month')
            ->limit(12)
            ->get();
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
        return User::whereHas('quranCircles', function ($query) use ($teacherProfile) {
            $query->where('quran_teacher_id', $teacherProfile->id);
        })->with(['studentProfile', 'quranCircles' => function ($query) use ($teacherProfile) {
            $query->where('quran_teacher_id', $teacherProfile->id);
        }])->get();
    }

    /**
     * Get Academic teacher students
     */
    private function getAcademicTeacherStudents($user, $teacherProfile)
    {
        return User::whereHas('interactiveCourseEnrollments', function ($query) use ($teacherProfile) {
            $query->whereHas('course', function ($q) use ($teacherProfile) {
                $q->where('assigned_teacher_id', $teacherProfile->id);
            });
        })->with(['studentProfile'])->get();
    }
}
