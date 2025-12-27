<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ChildSelectionMiddleware;
use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranSubscription;
use App\Services\ParentDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\SessionStatus;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

/**
 * Parent Subscription Controller
 *
 * Handles viewing of child subscriptions.
 * Uses session-based child selection via middleware.
 * Returns student view with parent layout for consistent design.
 */
class ParentSubscriptionController extends Controller
{
    protected ParentDataService $dataService;

    public function __construct(ParentDataService $dataService)
    {
        $this->dataService = $dataService;

        // Enforce read-only access
        $this->middleware(function ($request, $next) {
            if (!in_array($request->method(), ['GET', 'HEAD'])) {
                abort(403, 'أولياء الأمور لديهم صلاحيات مشاهدة فقط');
            }
            return $next($request);
        });
    }

    /**
     * List all subscriptions - supports filtering by child via session-based selection
     *
     * Uses the student view with parent layout for consistent design.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        // Get child IDs from middleware (session-based selection)
        $childUserIds = ChildSelectionMiddleware::getChildIds();

        // Get individual Quran subscriptions (1-to-1 sessions with teacher)
        $individualQuranSubscriptions = QuranSubscription::whereIn('student_id', $childUserIds)
            ->where('academy_id', $parent->academy_id)
            ->where('subscription_type', 'individual')
            ->with(['quranTeacher', 'package', 'individualCircle', 'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get group Quran subscriptions (group circle sessions)
        $groupQuranSubscriptions = QuranSubscription::whereIn('student_id', $childUserIds)
            ->where('academy_id', $parent->academy_id)
            ->whereIn('subscription_type', ['group', 'circle'])
            ->with(['quranTeacher', 'package', 'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get circles the children are enrolled in (for group subscriptions context)
        $enrolledCircles = QuranCircle::where('academy_id', $parent->academy_id)
            ->whereHas('students', function ($query) use ($childUserIds) {
                $query->whereIn('users.id', $childUserIds);
            })
            ->with(['quranTeacher', 'students'])
            ->get();

        // Map group subscriptions to their circles
        $groupQuranSubscriptions->each(function ($subscription) use ($enrolledCircles) {
            $subscription->circle = $enrolledCircles->first(function ($circle) use ($subscription) {
                return $circle->quran_teacher_id === $subscription->quran_teacher_id;
            });
        });

        // Get interactive course enrollments
        $courseEnrollments = InteractiveCourse::where('academy_id', $parent->academy_id)
            ->whereHas('enrollments', function ($query) use ($childUserIds) {
                $query->whereIn('student_id', $childUserIds);
            })
            ->with(['assignedTeacher', 'enrollments' => function ($query) use ($childUserIds) {
                $query->whereIn('student_id', $childUserIds);
            }])
            ->get();

        // Get academic subscriptions
        $academicSubscriptions = AcademicSubscription::whereIn('student_id', $childUserIds)
            ->where('academy_id', $parent->academy_id)
            ->with(['teacher.user', 'subject', 'gradeLevel', 'academicPackage'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Return student view with parent layout
        return view('student.subscriptions', [
            'individualQuranSubscriptions' => $individualQuranSubscriptions,
            'groupQuranSubscriptions' => $groupQuranSubscriptions,
            'enrolledCircles' => $enrolledCircles,
            'courseEnrollments' => $courseEnrollments,
            'academicSubscriptions' => $academicSubscriptions,
            'quranTrialRequests' => collect(), // Empty for parents - trial requests are student-only
            'layout' => 'parent',
        ]);
    }

    /**
     * Show subscription details
     *
     * @param Request $request
     * @param string $type
     * @param int $subscriptionId
     * @return \Illuminate\View\View
     */
    public function show(Request $request, string $type, int $subscriptionId): View
    {
        $user = Auth::user();
        $parent = $user->parentProfile;
        $children = $parent->students()->with('user')->get();
        $childUserIds = $children->pluck('user_id')->toArray();

        if ($type === 'quran') {
            $subscription = QuranSubscription::with(['quranTeacher.user', 'package', 'student'])
                ->findOrFail($subscriptionId);
        } elseif ($type === 'academic') {
            $subscription = AcademicSubscription::with(['academicTeacher.user', 'academicPackage', 'student'])
                ->findOrFail($subscriptionId);
        } elseif ($type === 'course') {
            $subscription = CourseSubscription::with(['course', 'student'])
                ->findOrFail($subscriptionId);
        } else {
            abort(404, 'نوع الاشتراك غير صحيح');
        }

        // Verify subscription belongs to one of parent's children
        if (!in_array($subscription->student_id, $childUserIds)) {
            abort(403, 'لا يمكنك الوصول إلى هذا الاشتراك');
        }

        return view('parent.subscriptions.show', [
            'parent' => $parent,
            'children' => $children,
            'subscription' => $subscription,
            'subscriptionType' => $type,
            'type' => $type,
        ]);
    }

    /**
     * Helper: Get user IDs for children based on filter
     */
    protected function getChildUserIds($children, $selectedChildId): array
    {
        if ($selectedChildId === 'all') {
            return $children->pluck('user_id')->toArray();
        }

        // Find the specific child
        $child = $children->firstWhere('id', $selectedChildId);
        if ($child) {
            return [$child->user_id];
        }

        // Fallback to all children if invalid selection
        return $children->pluck('user_id')->toArray();
    }
}
