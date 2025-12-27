<?php

namespace App\Http\Controllers;

use App\Models\AcademicPackage;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;

class UnifiedAcademicTeacherController extends Controller
{
    /**
     * Display a listing of Academic teachers (Unified for both public and authenticated)
     */
    public function index(Request $request, $subdomain)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        $user = Auth::user();
        $isAuthenticated = (bool) $user;

        // Base query (applies to all users)
        $query = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['user', 'subjects', 'gradeLevels']);

        // Student-specific data
        $activeSubscriptionsCount = 0;
        $subscriptionsByTeacherId = collect();

        if ($isAuthenticated) {
            // Get student's academic subscriptions
            $subscriptions = AcademicSubscription::where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->whereIn('status', [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::PENDING->value])
                ->with(['academicTeacher', 'academicPackage', 'sessions'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Group subscriptions by teacher ID (take first subscription per teacher)
            $subscriptionsByTeacherId = $subscriptions
                ->groupBy('teacher_id')
                ->map(fn($group) => $group->first());

            $activeSubscriptionsCount = $subscriptionsByTeacherId->count();

            // Sort subscribed teachers first for authenticated users
            if ($subscriptionsByTeacherId->count() > 0) {
                $query->orderByRaw('CASE WHEN id IN (' . implode(',', $subscriptionsByTeacherId->keys()->toArray()) . ') THEN 0 ELSE 1 END');
            }
        }

        // Apply filters (same for both)
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

        // Get teachers
        $academicTeachers = $query->withCount(['interactiveCourses as active_courses_count'])
            ->orderBy('rating', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->appends($request->except('page'));

        // Get all active packages for calculating minimum price
        $allPackages = AcademicPackage::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->get();

        // Add subscription info and calculate minimum price for each teacher
        $academicTeachers->getCollection()->transform(function ($teacher) use ($subscriptionsByTeacherId, $allPackages, $academy, $isAuthenticated) {
            // Add subscription data for authenticated students
            if ($isAuthenticated) {
                $teacher->my_subscription = $subscriptionsByTeacherId->get($teacher->id);
                $teacher->is_subscribed = $teacher->my_subscription !== null;
            } else {
                $teacher->my_subscription = null;
                $teacher->is_subscribed = false;
            }

            // Calculate minimum price
            $packageIds = $this->getTeacherPackageIds($teacher, $academy);

            if (! empty($packageIds)) {
                $packages = $allPackages->whereIn('id', $packageIds);
            } else {
                $packages = $allPackages;
            }

            if ($packages->count() > 0) {
                $teacher->minimum_price = $packages->min('monthly_price');
            }

            return $teacher;
        });

        // Get filter options
        $subjects = \App\Models\AcademicSubject::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $gradeLevels = \App\Models\AcademicGradeLevel::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('student.academic-teachers', compact(
            'academy',
            'academicTeachers',
            'activeSubscriptionsCount',
            'subjects',
            'gradeLevels',
            'isAuthenticated'
        ));
    }

    /**
     * Display the specified academic teacher profile (Unified for both public and authenticated)
     */
    public function show(Request $request, $subdomain, $teacherId)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->firstOrFail();

        $user = Auth::user();
        $isAuthenticated = (bool) $user;

        // Get the teacher profile
        $teacher = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->where('id', $teacherId)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['user', 'subjects', 'gradeLevels'])
            ->firstOrFail();

        // Get teacher's statistics
        $stats = [
            'total_students' => $teacher->total_students ?? 0,
            'total_sessions' => $teacher->total_sessions ?? 0,
            'experience_years' => $teacher->teaching_experience_years ?? 0,
            'rating' => $teacher->rating ?? 0,
        ];

        // Get available packages for this teacher
        $packageIds = $this->getTeacherPackageIds($teacher, $academy);

        if (! empty($packageIds)) {
            $packages = AcademicPackage::where('academy_id', $academy->id)
                ->where('is_active', true)
                ->whereIn('id', $packageIds)
                ->get();
        } else {
            $packages = AcademicPackage::where('academy_id', $academy->id)
                ->where('is_active', true)
                ->get();
        }

        // Check for active subscription (authenticated only)
        $mySubscription = null;

        if ($isAuthenticated) {
            $mySubscription = AcademicSubscription::where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->where('teacher_id', $teacher->id)
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->first();
        }

        return view('student.academic-teacher-detail', compact(
            'academy',
            'teacher',
            'packages',
            'stats',
            'mySubscription',
            'isAuthenticated'
        ));
    }

    /**
     * Get teacher's package IDs from default_packages or all packages
     */
    private function getTeacherPackageIds($teacher, $academy)
    {
        // Try to get from teacher's default_packages
        if ($teacher->default_packages && is_array($teacher->default_packages)) {
            return $teacher->default_packages;
        }

        // Fallback to empty array (will use all packages)
        return [];
    }
}
