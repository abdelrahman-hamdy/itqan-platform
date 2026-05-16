<?php

namespace App\Http\Controllers;

use App\Constants\DefaultAcademy;
use App\Enums\SubscriptionType;
use App\Models\AcademicSubscription;
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
            $subscription->setRelation('circle', $enrolledCircles->first(function ($circle) use ($subscription) {
                return $circle->quran_teacher_id === $subscription->quran_teacher_id;
            }));
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
        $subdomain = $user->academy->subdomain ?? DefaultAcademy::subdomain();

        $result = $this->subscriptionService->toggleAutoRenew($user, $type, $id);

        if (! $result['success']) {
            return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                ->with('error', $result['error']);
        }

        return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
            ->with('success', $result['message']);
    }

    /**
     * Cancel a subscription.
     *
     * Phase A.7 / P3 / INV-G1: student-initiated cancellation is removed.
     * Cancellation is admin-only via the supervisor panel. The route stays
     * registered so legacy bookmarks/forms don't 404, but it now redirects
     * to the subscriptions index with a localized error explaining the
     * change. The supporting service method is unused going forward — its
     * removal is scheduled for Phase E.
     */
    public function cancelSubscription(Request $request, string $subdomain, string $type, string $id): RedirectResponse
    {
        $user = Auth::user();
        $subdomain = $user->academy->subdomain ?? DefaultAcademy::subdomain();

        abort(403, __('subscriptions.errors.student_cancel_forbidden'));
    }

    /**
     * Delete a subscription (only if canceled or pending)
     */
    public function deleteSubscription(Request $request, string $subdomain, string $type, string $id): RedirectResponse
    {
        $user = Auth::user();
        $subdomain = $user->academy->subdomain ?? DefaultAcademy::subdomain();

        $result = $this->subscriptionService->deleteSubscription($user, $type, $id);

        if (! $result['success']) {
            return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                ->with('error', $result['error']);
        }

        return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
            ->with('success', $result['message']);
    }

    /**
     * Show the renewal form for a subscription.
     */
    public function showRenewForm(Request $request, string $subdomain, string $type, string $id): View|RedirectResponse
    {
        $user = Auth::user();
        $academy = $user->academy;

        $subscription = $this->resolveStudentSubscription($user, $type, $id);
        if (! $subscription) {
            return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                ->with('error', __('subscriptions.subscription_not_found'));
        }

        $renewalService = app(\App\Services\Subscription\SubscriptionRenewalService::class);
        $mode = $request->query('mode', 'renew');

        if ($mode === 'resubscribe' && ! $renewalService->canResubscribe($subscription)) {
            return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                ->with('error', __('subscriptions.cannot_resubscribe'));
        }

        if ($mode === 'renew' && ! $renewalService->canRenew($subscription)) {
            return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                ->with('error', __('subscriptions.cannot_renew'));
        }

        $options = $renewalService->getRenewalOptions($subscription);

        return view('student.subscription-renew', [
            'subscription' => $subscription,
            'type' => $type,
            'mode' => $mode,
            'options' => $options,
            'academy' => $academy,
        ]);
    }

    /**
     * Process the renewal and redirect to payment.
     *
     * Intent routing: when the current cycle is still in the hybrid
     * "active+pending payment" shape, the student's "Renew" click really
     * means "let me pay the cycle I'm already on" — bypass `renew()`
     * entirely and route to the existing subscription-payment flow,
     * which mints a fresh gateway payment for the current cycle.
     * `renew()` only runs when the current cycle is paid.
     */
    public function processRenew(Request $request, string $subdomain, string $type, string $id): RedirectResponse
    {
        $user = Auth::user();
        $subdomain = $user->academy->subdomain ?? DefaultAcademy::subdomain();

        $subscription = $this->resolveStudentSubscription($user, $type, $id);
        if (! $subscription) {
            return redirect()->route('student.subscriptions', ['subdomain' => $subdomain])
                ->with('error', __('subscriptions.subscription_not_found'));
        }

        if ($subscription->isCurrentCyclePaymentPending()) {
            $paymentRoute = $type === SubscriptionType::ACADEMIC->value
                ? 'academic.subscription.payment'
                : 'quran.subscription.payment';

            return redirect()->route($paymentRoute, [
                'subdomain' => $subdomain,
                'subscription' => $subscription->id,
            ]);
        }

        $request->validate([
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'package_id' => 'nullable|integer',
            'mode' => 'nullable|in:renew,resubscribe',
        ]);

        $renewalService = app(\App\Services\Subscription\SubscriptionRenewalService::class);
        $mode = $request->input('mode', 'renew');

        // Phase A.7 / P7 / INV-H1: student-driven renewal MUST land on a
        // currently-active package. If the previous package is retired and
        // the student didn't pick a new one — or picked one that isn't in
        // the active set — block and ask them to choose. Admin/supervisor
        // renewals bypass this (INV-H2) by going through the supervisor
        // controller, not this method.
        $availableOptions = $renewalService->getRenewalOptions($subscription);
        $validPackageIds = collect($availableOptions['packages'])->pluck('id')->all();
        // Cast to int — form submissions arrive as strings ("12"), but
        // $validPackageIds is int[]. Strict in_array below treats "12" !== 12
        // and rejects every renewal. Regression introduced 2026-05-14 in the
        // v2 simplify pass; surfaced by sub 720 (student 683) on 2026-05-16.
        $packageId = $request->filled('package_id') ? (int) $request->package_id : null;
        $previousPackageId = $availableOptions['current']['package_id'] ?? null;
        $previousIsActive = $previousPackageId !== null && in_array((int) $previousPackageId, $validPackageIds, true);

        if ($packageId) {
            if (! in_array($packageId, $validPackageIds, true)) {
                return redirect()->back()->with('error', __('subscriptions.errors.invalid_package'));
            }
        } elseif (! $previousIsActive) {
            // No package selected AND previous package isn't active — P7 block.
            return redirect()->back()->with('error', __('subscriptions.errors.previous_package_retired'));
        }

        try {
            $options = array_filter([
                'billing_cycle' => $request->billing_cycle,
                'package_id' => $packageId,
                'payment_mode' => 'unpaid',
            ]);

            $new = $mode === 'resubscribe'
                ? $renewalService->resubscribe($subscription, $options)
                : $renewalService->renew($subscription, $options);

            // Redirect to payment page for the new pending subscription
            $paymentRoute = $type === SubscriptionType::ACADEMIC->value
                ? 'academic.subscription.payment'
                : 'quran.subscription.payment';

            return redirect()->route($paymentRoute, [
                'subdomain' => $subdomain,
                'subscription' => $new->id,
            ]);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Find a subscription owned by the current student.
     */
    private function resolveStudentSubscription($user, string $type, string $id)
    {
        $modelClass = match ($type) {
            'quran' => QuranSubscription::class,
            'academic' => AcademicSubscription::class,
            default => null,
        };

        if (! $modelClass) {
            return null;
        }

        return $modelClass::where('id', $id)
            ->where('student_id', $user->id)
            ->where('academy_id', $user->academy_id)
            ->with('currentCycle')
            ->first();
    }
}
