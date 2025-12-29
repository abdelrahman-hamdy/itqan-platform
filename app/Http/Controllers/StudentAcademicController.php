<?php

namespace App\Http\Controllers;

use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Services\Student\StudentAcademicService;
use App\Services\StudentSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class StudentAcademicController extends Controller
{
    public function __construct(
        protected StudentAcademicService $academicService,
        protected StudentSearchService $searchService
    ) {}

    public function academicTeachers(Request $request): View
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Authorize viewing subscriptions
        $this->authorize('viewAny', \App\Models\AcademicSubscription::class);

        // Get student's academic subscriptions with teacher info using service
        $mySubscriptions = $this->academicService->getSubscriptionsByTeacher($user);

        // Get active subscriptions count
        $activeSubscriptionsCount = $mySubscriptions->count();

        // Get all subjects and grade levels for filters
        $subjects = \App\Models\AcademicSubject::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $gradeLevels = \App\Models\AcademicGradeLevel::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Build teachers query with filters
        $query = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['user']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('subject')) {
            $query->whereJsonContains('subject_ids', (int) $request->subject);
        }

        if ($request->filled('grade_level')) {
            $query->whereJsonContains('grade_level_ids', (int) $request->grade_level);
        }

        if ($request->filled('experience')) {
            $experienceRange = $request->experience;
            if ($experienceRange === '1-3') {
                $query->whereBetween('teaching_experience_years', [1, 3]);
            } elseif ($experienceRange === '3-5') {
                $query->whereBetween('teaching_experience_years', [3, 5]);
            } elseif ($experienceRange === '5-10') {
                $query->whereBetween('teaching_experience_years', [5, 10]);
            } elseif ($experienceRange === '10+') {
                $query->where('teaching_experience_years', '>=', 10);
            }
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // Get paginated teachers
        $academicTeachers = $query->withCount(['interactiveCourses as active_courses_count'])
            ->orderBy('rating', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->appends($request->except('page'));

        // Get all active packages for calculating minimum price
        $allPackages = \App\Models\AcademicPackage::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->get();

        // Calculate additional stats for each teacher
        $academicTeachers->getCollection()->transform(function ($teacher) use ($mySubscriptions, $allPackages) {
            // Mark if student is subscribed to this teacher
            $teacher->is_subscribed = $mySubscriptions->where('teacher_id', $teacher->id)->isNotEmpty();
            $teacher->my_subscription = $mySubscriptions->where('teacher_id', $teacher->id)->first();

            // Calculate average rating from interactive courses or use profile rating
            $teacher->average_rating = $teacher->rating ?? 4.8;

            // Get active students count
            $teacher->students_count = $teacher->total_students ?? 0;

            // Format hourly rate
            $teacher->hourly_rate = $teacher->session_price_individual;

            // Set bio from profile
            $teacher->bio = $teacher->bio_arabic ?? $teacher->notes ?? 'معلم أكاديمي مؤهل متخصص في التدريس';

            // Set experience years
            $teacher->experience_years = $teacher->teaching_experience_years;

            // Set qualification label from education_level
            $educationLabels = ['diploma' => 'دبلوم', 'bachelor' => 'بكالوريوس', 'master' => 'ماجستير', 'phd' => 'دكتوراه', 'other' => 'أخرى'];
            $teacher->qualification = $educationLabels[$teacher->education_level] ?? $teacher->education_level;

            // Calculate minimum price from available packages
            if ($allPackages->count() > 0) {
                $teacher->minimum_price = $allPackages->min('monthly_price');
            }

            return $teacher;
        });

        // Get student's academic subscriptions to show which teachers they're learning with using service
        $academicProgress = $this->academicService->getAcademicProgress($user);

        return view('student.academic-teachers', compact(
            'academicTeachers',
            'academicProgress',
            'mySubscriptions',
            'activeSubscriptionsCount',
            'subjects',
            'gradeLevels'
        ));
    }

    /**
     * Show academic session details for student
     */
    public function showAcademicSession(Request $request, $subdomain, $sessionId): View
    {
        $user = Auth::user();

        // Get session details using service
        $session = $this->academicService->getSessionDetails($user, $sessionId);

        if (! $session) {
            abort(404, 'Academic session not found');
        }

        // Authorize viewing this specific session
        $this->authorize('view', $session);

        return view('student.academic-session-detail', compact('session'));
    }

    /**
     * Show academic subscription details for student
     */
    public function showAcademicSubscription(Request $request, $subdomain, $subscriptionId): View
    {
        $user = Auth::user();

        // Get subscription details using service
        $data = $this->academicService->getSubscriptionDetails($user, $subscriptionId);

        if (! $data) {
            abort(404, 'Academic subscription not found');
        }

        // Authorize viewing this specific subscription
        $this->authorize('view', $data['subscription']);

        return view('student.academic-subscription-detail', [
            'subscription' => $data['subscription'],
            'upcomingSessions' => $data['upcomingSessions'],
            'pastSessions' => $data['pastSessions'],
            'progressSummary' => $data['progressSummary'],
        ]);
    }

    /**
     * Search for courses, teachers, and content
     */
    public function search(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $academy = $user->academy;
        $subdomain = $academy->subdomain ?? 'itqan-academy';
        $query = $request->input('q', '');

        // If empty query, redirect back
        if (empty(trim($query))) {
            return redirect()->route('student.profile', ['subdomain' => $subdomain])
                ->with('error', 'الرجاء إدخال كلمة بحث');
        }

        // Use search service for all searches
        $results = $this->searchService->search($user, $query);
        $totalResults = $this->searchService->getTotalCount($results);

        return view('student.search', [
            'query' => $query,
            'totalResults' => $totalResults,
            'interactiveCourses' => $results['interactive_courses'],
            'recordedCourses' => $results['recorded_courses'],
            'quranTeachers' => $results['quran_teachers'],
            'academicTeachers' => $results['academic_teachers'],
            'quranCircles' => $results['quran_circles'],
            'academy' => $academy,
            'subdomain' => $subdomain,
        ]);
    }
}
