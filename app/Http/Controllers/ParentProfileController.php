<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Http\Requests\UpdateParentProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Parent Profile Controller
 *
 * Handles parent profile viewing and editing.
 */
class ParentProfileController extends Controller
{
    /**
     * Show parent profile (main dashboard page)
     */
    public function index(Request $request): View
    {
        $this->authorize('viewDashboard', \App\Models\ParentProfile::class);

        $user = Auth::user();
        $parent = $user->parentProfile;

        if (! $parent) {
            abort(404, 'لم يتم العثور على الملف الشخصي لولي الأمر');
        }

        // Get children (from middleware or fallback)
        $children = $request->input('_parent_children') ?? $parent->students()->with('user')->get();
        $selectedChild = $request->input('_selected_child');

        // Get StudentProfile IDs (for models that use StudentProfile.id)
        $childrenProfileIds = $selectedChild
            ? [$selectedChild->id]
            : $children->pluck('id')->toArray();

        // Get User IDs (for models that reference User.id as student_id)
        // Note: QuranSession, AcademicSession, QuranSubscription, etc. use User.id for student_id
        $childrenUserIds = $selectedChild
            ? [$selectedChild->user_id]
            : $children->pluck('user_id')->toArray();

        \Log::info('[Parent Profile] Children IDs collected', [
            'children_count' => $children->count(),
            'selected_child_id' => $selectedChild?->id,
            'children_profile_ids' => $childrenProfileIds,
            'children_user_ids' => $childrenUserIds,
        ]);

        // Calculate aggregate statistics
        // Note: Most models use User.id for student_id, so we pass $childrenUserIds
        $stats = [
            'total_children' => $children->count(),
            'quranCirclesCount' => $this->countQuranCircles($childrenUserIds),
            'activeQuranSubscriptions' => $this->countQuranPrivateSubscriptions($childrenUserIds),
            'interactiveCoursesCount' => $this->countInteractiveCourses($childrenUserIds),
            'academicSubscriptionsCount' => $this->countAcademicSubscriptions($childrenUserIds),
            'active_subscriptions' => $this->countActiveSubscriptions($childrenUserIds),
            'upcoming_sessions' => $this->countUpcomingSessions($childrenUserIds),
            'total_certificates' => $this->countCertificates($childrenUserIds),
            'total_payments' => $this->countTotalPayments($childrenUserIds),
            'attendance_rate' => $this->calculateAttendanceRate($childrenUserIds),
        ];

        // Get learning sections data (similar to student profile)
        // Note: These methods use User.id for student_id queries
        $quranCircles = $this->getQuranCircles($childrenUserIds);
        $quranPrivateSessions = $this->getQuranPrivateSubscriptions($childrenUserIds);
        $interactiveCourses = $this->getInteractiveCourses($childrenUserIds);
        $academicPrivateSessions = $this->getAcademicSubscriptions($childrenUserIds);
        $recordedCourses = $this->getRecordedCourses($childrenUserIds);

        // Get per-child statistics for mini-cards (only when viewing all)
        // Note: getChildStats expects User.id (not StudentProfile.id)
        $childrenWithStats = $children->map(function ($child) {
            $childStats = $this->getChildStats($child->user_id);
            $child->stats = $childStats;

            return $child;
        });

        // Get upcoming sessions for children (uses User.id for student_id)
        $upcomingSessions = $this->getUpcomingSessions($childrenUserIds, 5);

        // Get Quran trial requests (uses User.id for student_id)
        $quranTrialRequests = $this->getQuranTrialRequests($childrenUserIds);

        return view('parent.profile', [
            'parent' => $parent,
            'user' => $user,
            'children' => $childrenWithStats,
            'stats' => $stats,
            'upcomingSessions' => $upcomingSessions,
            'selectedChild' => $selectedChild,
            // Learning sections (same as student profile)
            'quranCircles' => $quranCircles,
            'quranPrivateSessions' => $quranPrivateSessions,
            'interactiveCourses' => $interactiveCourses,
            'academicPrivateSessions' => $academicPrivateSessions,
            'recordedCourses' => $recordedCourses,
            'quranTrialRequests' => $quranTrialRequests,
        ]);
    }

    /**
     * Show edit profile form
     *
     * @return \Illuminate\View\View
     */
    public function edit(): View|RedirectResponse
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        if (! $parent) {
            return redirect()->route('parent.profile')
                ->with('error', 'لم يتم العثور على الملف الشخصي لولي الأمر');
        }

        // Get relationship type options
        $relationshipTypes = \App\Enums\RelationshipType::cases();

        return view('parent.edit-profile', compact('parent', 'relationshipTypes'));
    }

    /**
     * Update parent profile
     *
     * @param  Request  $request
     */
    public function update(UpdateParentProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        if (! $parent) {
            return redirect()->back()
                ->with('error', 'لم يتم العثور على الملف الشخصي لولي الأمر');
        }

        $validated = $request->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($parent->avatar) {
                Storage::disk('public')->delete($parent->avatar);
            }

            // Store new avatar
            $validated['avatar'] = $request->file('avatar')->store('avatars/parents', 'public');
        }

        // Update parent profile
        $parent->update($validated);

        // Update user name as well
        $user->update([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'phone' => $validated['phone'] ?? $user->phone,
        ]);

        return redirect()->route('parent.profile', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy'])
            ->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }

    /**
     * Count active subscriptions for given children
     */
    private function countActiveSubscriptions(array $childrenIds): int
    {
        if (empty($childrenIds)) {
            return 0;
        }

        $quranCount = \App\Models\QuranSubscription::whereIn('student_id', $childrenIds)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();

        $academicCount = \App\Models\AcademicSubscription::whereIn('student_id', $childrenIds)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();

        $courseCount = \App\Models\CourseSubscription::whereIn('student_id', $childrenIds)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();

        return $quranCount + $academicCount + $courseCount;
    }

    /**
     * Count upcoming sessions for given children
     */
    private function countUpcomingSessions(array $childrenIds): int
    {
        if (empty($childrenIds)) {
            return 0;
        }

        $quranCount = \App\Models\QuranSession::whereIn('student_id', $childrenIds)
            ->where('scheduled_at', '>=', now())
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::READY->value])
            ->count();

        $academicCount = \App\Models\AcademicSession::whereIn('student_id', $childrenIds)
            ->where('scheduled_at', '>=', now())
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::READY->value])
            ->count();

        return $quranCount + $academicCount;
    }

    /**
     * Count certificates for given children
     *
     * @param  array  $childrenUserIds  User IDs (not StudentProfile IDs)
     */
    private function countCertificates(array $childrenUserIds): int
    {
        if (empty($childrenUserIds)) {
            return 0;
        }

        // Certificate.student_id references User.id
        return \App\Models\Certificate::whereIn('student_id', $childrenUserIds)->count();
    }

    /**
     * Count total payments for given children
     *
     * @param  array  $childrenUserIds  User IDs (not StudentProfile IDs)
     */
    private function countTotalPayments(array $childrenUserIds): int
    {
        if (empty($childrenUserIds)) {
            return 0;
        }

        // Payment.user_id references User.id
        return \App\Models\Payment::whereIn('user_id', $childrenUserIds)
            ->where('status', SessionStatus::COMPLETED->value)
            ->count();
    }

    /**
     * Calculate average attendance rate for given children
     */
    private function calculateAttendanceRate(array $childrenIds): float
    {
        if (empty($childrenIds)) {
            return 0;
        }

        // Get all completed sessions for these children
        $quranSessions = \App\Models\QuranSession::whereIn('student_id', $childrenIds)
            ->where('status', SessionStatus::COMPLETED->value)
            ->get();

        $academicSessions = \App\Models\AcademicSession::whereIn('student_id', $childrenIds)
            ->where('status', SessionStatus::COMPLETED->value)
            ->get();

        $totalSessions = $quranSessions->count() + $academicSessions->count();

        if ($totalSessions === 0) {
            return 100; // Default to 100% if no sessions yet
        }

        // Count attended sessions (has attendance record) - includes 'attended' and 'late' statuses
        $attendedQuran = $quranSessions->filter(fn ($s) => in_array($s->attendance_status, [AttendanceStatus::ATTENDED->value, AttendanceStatus::LATE->value]))->count();
        $attendedAcademic = $academicSessions->filter(fn ($s) => in_array($s->attendance_status, [AttendanceStatus::ATTENDED->value, AttendanceStatus::LATE->value]))->count();

        $totalAttended = $attendedQuran + $attendedAcademic;

        return round(($totalAttended / $totalSessions) * 100, 1);
    }

    /**
     * Get statistics for a specific child
     *
     * @param  int  $userId  User ID (not StudentProfile ID)
     */
    private function getChildStats(int $userId): array
    {
        // All these models use User.id for student_id
        $quranSubscriptions = \App\Models\QuranSubscription::where('student_id', $userId)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();

        $academicSubscriptions = \App\Models\AcademicSubscription::where('student_id', $userId)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();

        // Certificate.student_id references User.id
        $certificates = \App\Models\Certificate::where('student_id', $userId)->count();

        $upcomingSessions = \App\Models\QuranSession::where('student_id', $userId)
            ->where('scheduled_at', '>=', now())
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::READY->value])
            ->count();

        $upcomingSessions += \App\Models\AcademicSession::where('student_id', $userId)
            ->where('scheduled_at', '>=', now())
            ->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::READY->value])
            ->count();

        return [
            'active_subscriptions' => $quranSubscriptions + $academicSubscriptions,
            'certificates' => $certificates,
            'upcoming_sessions' => $upcomingSessions,
        ];
    }

    /**
     * Count Quran circles for children
     */
    private function countQuranCircles(array $childrenIds): int
    {
        if (empty($childrenIds)) {
            return 0;
        }

        return \App\Models\QuranSubscription::whereIn('student_id', $childrenIds)
            ->whereIn('subscription_type', ['circle', 'group'])
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();
    }

    /**
     * Count Quran private subscriptions for children
     */
    private function countQuranPrivateSubscriptions(array $childrenIds): int
    {
        if (empty($childrenIds)) {
            return 0;
        }

        return \App\Models\QuranSubscription::whereIn('student_id', $childrenIds)
            ->where('subscription_type', 'individual')
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();
    }

    /**
     * Count interactive courses for children
     */
    private function countInteractiveCourses(array $childrenIds): int
    {
        if (empty($childrenIds)) {
            return 0;
        }

        return \App\Models\CourseSubscription::whereIn('student_id', $childrenIds)
            ->where('course_type', 'interactive')
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();
    }

    /**
     * Count academic subscriptions for children
     */
    private function countAcademicSubscriptions(array $childrenIds): int
    {
        if (empty($childrenIds)) {
            return 0;
        }

        return \App\Models\AcademicSubscription::whereIn('student_id', $childrenIds)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->count();
    }

    /**
     * Get Quran circles for children (group circles)
     * Uses the students relationship on QuranCircle to find enrolled circles
     */
    private function getQuranCircles(array $childrenIds): \Illuminate\Support\Collection
    {
        if (empty($childrenIds)) {
            return collect();
        }

        // Get circles where students are enrolled via pivot table
        return \App\Models\QuranCircle::whereHas('students', function ($query) use ($childrenIds) {
            $query->whereIn('users.id', $childrenIds);
        })
            // Note: 'quranTeacher' on QuranCircle returns User directly, so no '.user' needed
            // Note: 'schedule' is singular (HasOne), not 'schedules'
            ->with(['quranTeacher', 'schedule'])
            ->get();
    }

    /**
     * Get Quran private subscriptions for children (individual circles)
     */
    private function getQuranPrivateSubscriptions(array $childrenIds): \Illuminate\Support\Collection
    {
        if (empty($childrenIds)) {
            return collect();
        }

        return \App\Models\QuranSubscription::whereIn('student_id', $childrenIds)
            ->where('subscription_type', 'individual')
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            // Note: 'student' relationship returns User directly (not StudentProfile), so no '.user' needed
            ->with(['quranTeacher.user', 'individualCircle', 'student', 'package', 'sessions' => fn ($q) => $q->where('scheduled_at', '>', now())->orderBy('scheduled_at')->limit(3)])
            ->get();
    }

    /**
     * Get interactive courses for children
     */
    private function getInteractiveCourses(array $childrenIds): \Illuminate\Support\Collection
    {
        if (empty($childrenIds)) {
            return collect();
        }

        $courseIds = \App\Models\CourseSubscription::whereIn('student_id', $childrenIds)
            ->where('course_type', 'interactive')
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereNotNull('interactive_course_id')
            ->pluck('interactive_course_id')
            ->unique();

        return \App\Models\InteractiveCourse::whereIn('id', $courseIds)
            ->with(['assignedTeacher.user', 'sessions', 'enrollments' => fn ($q) => $q->whereIn('student_id', $childrenIds)])
            ->get();
    }

    /**
     * Get academic subscriptions for children
     */
    private function getAcademicSubscriptions(array $childrenIds): \Illuminate\Support\Collection
    {
        if (empty($childrenIds)) {
            return collect();
        }

        return \App\Models\AcademicSubscription::whereIn('student_id', $childrenIds)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->with(['academicTeacher.user', 'subject', 'gradeLevel', 'student'])
            ->get();
    }

    /**
     * Get recorded courses for children
     */
    private function getRecordedCourses(array $childrenIds): \Illuminate\Support\Collection
    {
        if (empty($childrenIds)) {
            return collect();
        }

        $courseIds = \App\Models\CourseSubscription::whereIn('student_id', $childrenIds)
            ->where('course_type', 'recorded')
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereNotNull('recorded_course_id')
            ->pluck('recorded_course_id')
            ->unique();

        return \App\Models\RecordedCourse::whereIn('id', $courseIds)
            ->with(['teacher.user', 'lessons'])
            ->get();
    }

    /**
     * Get Quran trial requests for children
     */
    private function getQuranTrialRequests(array $childrenIds): \Illuminate\Support\Collection
    {
        if (empty($childrenIds)) {
            return collect();
        }

        return \App\Models\QuranTrialRequest::whereIn('student_id', $childrenIds)
            ->with(['teacher.user', 'student', 'trialSession'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();
    }

    /**
     * Get upcoming sessions for children
     */
    private function getUpcomingSessions(array $childrenIds, int $limit = 5): array
    {
        if (empty($childrenIds)) {
            \Log::info('[Parent Upcoming Sessions] No children IDs provided');

            return [];
        }

        \Log::info('[Parent Upcoming Sessions] Searching for sessions', [
            'children_ids' => $childrenIds,
            'limit' => $limit,
            'today' => today()->toDateString(),
        ]);

        $sessions = [];

        // Get Quran sessions (both individual and group)
        // Individual sessions: linked via student_id directly
        // Group sessions: linked via circle_id → quran_circle_students pivot table
        $quranSessions = \App\Models\QuranSession::where(function ($query) use ($childrenIds) {
            // Individual sessions (student_id is set)
            $query->whereIn('student_id', $childrenIds)
                // OR Group sessions (via circle enrollment)
                ->orWhereHas('circle.students', function ($q) use ($childrenIds) {
                    $q->whereIn('quran_circle_students.student_id', $childrenIds);
                });
        })
            ->whereNotNull('scheduled_at')
            ->whereDate('scheduled_at', '>=', today())
            ->orderBy('scheduled_at')
            ->with(['quranTeacher', 'student', 'circle'])
            ->limit($limit * 2)
            ->get();

        \Log::info('[Parent Upcoming Sessions] Quran sessions found', [
            'count' => $quranSessions->count(),
            'sessions' => $quranSessions->map(fn ($s) => [
                'id' => $s->id,
                'student_id' => $s->student_id,
                'circle_id' => $s->circle_id,
                'session_type' => $s->session_type,
                'scheduled_at' => $s->scheduled_at?->toDateTimeString(),
                'status' => $s->status,
            ]),
        ]);

        foreach ($quranSessions as $session) {
            // For group sessions, get a specific child's name from the session
            $childName = $session->student?->name;
            if (! $childName && $session->circle) {
                // Group session - get first enrolled child from this parent's children
                $enrolledChild = $session->circle->students()
                    ->whereIn('quran_circle_students.student_id', $childrenIds)
                    ->first();
                $childName = $enrolledChild?->name ?? 'غير محدد';
            }

            $sessions[] = [
                'type' => 'quran',
                'title' => $session->circle ? "حلقة قرآن: {$session->circle->name}" : 'جلسة قرآن',
                'teacher_name' => $session->quranTeacher?->name ?? 'غير محدد',
                'child_name' => $childName ?? 'غير محدد',
                'scheduled_at' => $session->scheduled_at,
                'session_id' => $session->id,
                'status' => $session->status,
            ];
        }

        // Get upcoming Academic sessions
        $academicSessions = \App\Models\AcademicSession::whereIn('student_id', $childrenIds)
            ->whereNotNull('scheduled_at')
            ->whereDate('scheduled_at', '>=', today())
            ->whereNotIn('status', [SessionStatus::COMPLETED->value, SessionStatus::CANCELLED->value])
            ->orderBy('scheduled_at')
            ->with(['academicTeacher.user', 'student'])
            ->limit($limit * 2) // Get more than needed
            ->get();

        \Log::info('[Parent Upcoming Sessions] Academic sessions found', [
            'count' => $academicSessions->count(),
            'sessions' => $academicSessions->map(fn ($s) => [
                'id' => $s->id,
                'student_id' => $s->student_id,
                'scheduled_at' => $s->scheduled_at?->toDateTimeString(),
                'status' => $s->status,
            ]),
        ]);

        foreach ($academicSessions as $session) {
            $sessions[] = [
                'type' => 'academic',
                'title' => 'جلسة أكاديمية',
                'teacher_name' => $session->academicTeacher?->user?->name ?? 'غير محدد',
                'child_name' => $session->student?->name ?? 'غير محدد',
                'scheduled_at' => $session->scheduled_at,
                'session_id' => $session->id,
                'status' => $session->status,
            ];
        }

        // Sort by scheduled_at and limit
        usort($sessions, fn ($a, $b) => $a['scheduled_at']->timestamp - $b['scheduled_at']->timestamp);

        $finalSessions = array_slice($sessions, 0, $limit);

        \Log::info('[Parent Upcoming Sessions] Final result', [
            'total_found' => count($sessions),
            'returned' => count($finalSessions),
            'sessions' => array_map(fn ($s) => [
                'type' => $s['type'],
                'scheduled_at' => $s['scheduled_at']->toDateTimeString(),
                'status' => $s['status'],
            ], $finalSessions),
        ]);

        return $finalSessions;
    }
}
