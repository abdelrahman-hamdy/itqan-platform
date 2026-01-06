<?php

namespace App\Http\Controllers;

use App\Models\QuranCircle;
use App\Models\QuranSubscription;
use App\Models\QuranTrialRequest;
use App\Services\Student\StudentAcademicService;
use App\Services\Student\StudentCourseService;
use App\Services\StudentSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentSubscriptionController extends Controller
{
    public function __construct(
        protected StudentSubscriptionService $subscriptionService,
        protected StudentCourseService $courseService,
        protected StudentAcademicService $academicService
    ) {}

    public function subscriptions(): View
    {
        $this->authorize('viewAny', QuranSubscription::class);

        $user = Auth::user();
        $academy = $user->academy;

        // Get individual Quran subscriptions (1-to-1 sessions with teacher)
        $individualQuranSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('subscription_type', 'individual')
            ->with(['quranTeacher', 'package', 'individualCircle', 'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get group Quran subscriptions (group circle sessions)
        $groupQuranSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('subscription_type', ['group', 'circle'])
            ->with(['quranTeacher', 'package', 'sessions' => function ($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(5);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get circles the student is enrolled in (for group subscriptions context)
        $enrolledCircles = QuranCircle::where('academy_id', $academy->id)
            ->whereHas('students', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with(['quranTeacher', 'students'])
            ->get();

        // Map group subscriptions to their circles
        $groupQuranSubscriptions->each(function ($subscription) use ($enrolledCircles) {
            $subscription->circle = $enrolledCircles->first(function ($circle) use ($subscription) {
                return $circle->quran_teacher_id === $subscription->quran_teacher_id;
            });
        });

        $quranTrialRequests = QuranTrialRequest::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher', 'trialSession'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get course enrollments using service
        $courseEnrollments = $this->courseService->getCourseEnrollments($user);

        // Get academic subscriptions using service
        $academicSubscriptions = $this->academicService->getAllSubscriptions($user);

        return view('student.subscriptions', compact(
            'individualQuranSubscriptions',
            'groupQuranSubscriptions',
            'enrolledCircles',
            'quranTrialRequests',
            'courseEnrollments',
            'academicSubscriptions'
        ));
    }

    /**
     * Toggle auto-renewal for a subscription
     */
    public function toggleAutoRenew(Request $request, string $subdomain, string $type, string $id): RedirectResponse
    {
        $user = Auth::user();
        $subdomain = $user->academy->subdomain ?? 'itqan-academy';

        $result = $this->subscriptionService->toggleAutoRenew($user, $type, $id);

        if (! $result['success']) {
            return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                ->with('error', $result['error']);
        }

        return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
            ->with('success', $result['message']);
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Request $request, string $subdomain, string $type, string $id): RedirectResponse
    {
        $user = Auth::user();
        $subdomain = $user->academy->subdomain ?? 'itqan-academy';

        $result = $this->subscriptionService->cancelSubscription($user, $type, $id);

        if (! $result['success']) {
            return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                ->with('error', $result['error']);
        }

        return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
            ->with('success', $result['message']);
    }
}
