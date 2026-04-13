<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SupervisorSubscriptionsController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $isAdmin = $this->isAdminUser();

        $quranTeacherIds = $isAdmin ? [] : $this->getAssignedQuranTeacherIds();
        $academicTeacherProfileIds = $isAdmin ? [] : $this->getAssignedAcademicTeacherProfileIds();

        $subscriptions = collect();

        // Load Quran subscriptions
        if ($isAdmin || ! empty($quranTeacherIds)) {
            $quranQuery = QuranSubscription::with(['student', 'quranTeacherUser']);
            if (! $isAdmin) {
                $quranQuery->whereIn('quran_teacher_id', $quranTeacherIds);
            }
            $quranSubs = $quranQuery
                ->get()
                ->map(fn ($sub) => [
                    'id' => $sub->id,
                    'type' => 'quran',
                    'sub_type' => $sub->subscription_type ?? 'individual',
                    'model' => $sub,
                    'student_user' => $sub->student,
                    'student_name' => $sub->student?->name ?? '-',
                    'teacher_name' => $sub->quranTeacherUser?->name ?? '-',
                    'status' => $sub->status,
                    'sessions_total' => $sub->total_sessions ?? 0,
                    'sessions_completed' => $sub->sessions_used ?? 0,
                    'sessions_remaining' => $sub->sessions_remaining ?? 0,
                    'start_date' => $sub->starts_at,
                    'end_date' => $sub->ends_at,
                    'is_extended' => ! empty($sub->metadata['extensions'] ?? []),
                    'created_at' => $sub->created_at,
                ]);
            $subscriptions = $subscriptions->merge($quranSubs);
        }

        // Load Academic subscriptions
        if ($isAdmin || ! empty($academicTeacherProfileIds)) {
            $academicQuery = AcademicSubscription::with(['student', 'teacher.user']);
            if (! $isAdmin) {
                $academicQuery->whereIn('teacher_id', $academicTeacherProfileIds);
            }
            $academicSubs = $academicQuery
                ->get()
                ->map(fn ($sub) => [
                    'id' => $sub->id,
                    'type' => 'academic',
                    'sub_type' => 'academic',
                    'model' => $sub,
                    'student_user' => $sub->student,
                    'student_name' => $sub->student?->name ?? '-',
                    'teacher_name' => $sub->teacher?->user?->name ?? '-',
                    'status' => $sub->status,
                    'sessions_total' => $sub->total_sessions ?? 0,
                    'sessions_completed' => $sub->sessions_used ?? 0,
                    'sessions_remaining' => $sub->sessions_remaining ?? 0,
                    'start_date' => $sub->starts_at,
                    'end_date' => $sub->ends_at,
                    'is_extended' => ! empty($sub->metadata['extensions'] ?? []),
                    'created_at' => $sub->created_at,
                ]);
            $subscriptions = $subscriptions->merge($academicSubs);
        }

        // Stats from unfiltered set (single pass)
        $statusCounts = $subscriptions->countBy(fn ($s) => $s['status']->value);
        $totalActive = $statusCounts[SessionSubscriptionStatus::ACTIVE->value] ?? 0;
        $totalPending = $statusCounts[SessionSubscriptionStatus::PENDING->value] ?? 0;
        $totalPaused = $statusCounts[SessionSubscriptionStatus::PAUSED->value] ?? 0;
        $totalExpired = $statusCounts[SessionSubscriptionStatus::EXPIRED->value] ?? 0;
        $totalCancelled = $statusCounts[SessionSubscriptionStatus::CANCELLED->value] ?? 0;

        // Apply filters
        $filtered = $subscriptions;

        if ($type = $request->input('type')) {
            $filtered = $filtered->where('type', $type);
        }

        if ($status = $request->input('status')) {
            if ($status === 'extended') {
                // Only show subscriptions with currently active grace period
                $filtered = $filtered->filter(function ($s) {
                    $gracePeriodEndsAt = $s['model']->metadata['grace_period_ends_at'] ?? null;

                    return $gracePeriodEndsAt && Carbon::parse($gracePeriodEndsAt)->isFuture();
                });
            } elseif ($status === 'expiring_3d') {
                $filtered = $filtered->filter(function ($s) {
                    return $s['status'] === SessionSubscriptionStatus::ACTIVE
                        && $s['end_date']
                        && $s['end_date']->between(now(), now()->addDays(3));
                });
            } elseif ($status === 'expiring_7d') {
                $filtered = $filtered->filter(function ($s) {
                    return $s['status'] === SessionSubscriptionStatus::ACTIVE
                        && $s['end_date']
                        && $s['end_date']->between(now(), now()->addDays(7));
                });
            } else {
                $filtered = $filtered->filter(fn ($s) => $s['status']->value === $status);
            }
        }

        if ($search = $request->input('search')) {
            $search = mb_strtolower($search);
            $filtered = $filtered->filter(function ($s) use ($search) {
                return str_contains(mb_strtolower($s['student_name']), $search)
                    || str_contains(mb_strtolower($s['teacher_name']), $search);
            });
        }

        // Filter by reporting student (deep-link from support tickets)
        if ($studentId = $request->input('student_id')) {
            $studentId = (int) $studentId;
            $filtered = $filtered->filter(fn ($s) => $s['student_user']?->id === $studentId);
        }

        // Filter by reporting teacher's User id (deep-link from support tickets).
        // Note: QuranSubscription.quran_teacher_id is a User id, while
        // AcademicSubscription.teacher_id points to AcademicTeacherProfile.id, so we
        // resolve the academic teacher's User id via the relationship.
        if ($teacherUserId = $request->input('teacher_user_id')) {
            $teacherUserId = (int) $teacherUserId;
            $filtered = $filtered->filter(function ($s) use ($teacherUserId) {
                $model = $s['model'];
                if ($s['type'] === 'quran') {
                    return (int) $model->quran_teacher_id === $teacherUserId;
                }

                return (int) ($model->teacher?->user_id ?? 0) === $teacherUserId;
            });
        }

        // Sort
        $sort = $request->get('sort', 'newest');
        $filtered = match ($sort) {
            'oldest' => $filtered->sortBy('created_at'),
            'expiring_soon' => $filtered->sortBy(fn ($s) => $s['end_date'] ?? now()->addYears(10)),
            'sessions_remaining' => $filtered->sortBy('sessions_remaining'),
            'student_name' => $filtered->sortBy('student_name'),
            default => $filtered->sortByDesc('created_at'),
        };

        // Paginate
        $perPage = 15;
        $page = $request->input('page', 1);
        $filteredValues = $filtered->values();
        $paginated = new LengthAwarePaginator(
            $filteredValues->forPage($page, $perPage)->values(),
            $filteredValues->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Resolve the deep-link filter user (for the "filtered by NAME" chip).
        $filterUser = null;
        if ($studentIdParam = $request->input('student_id')) {
            $filterUser = User::query()->find($studentIdParam);
        } elseif ($teacherUserIdParam = $request->input('teacher_user_id')) {
            $filterUser = User::query()->find($teacherUserIdParam);
        }

        return view('supervisor.subscriptions.index', [
            'subscriptions' => $paginated,
            'totalCount' => $subscriptions->count(),
            'totalActive' => $totalActive,
            'totalPending' => $totalPending,
            'totalPaused' => $totalPaused,
            'totalExpired' => $totalExpired,
            'totalCancelled' => $totalCancelled,
            'filteredCount' => $filteredValues->count(),
            'isAdmin' => $isAdmin,
            'canCreate' => $this->canCreateSubscriptions(),
            'filterUser' => $filterUser,
        ]);
    }

    public function show(Request $request, $subdomain, string $type, $id): View
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $subscription = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($subscription, $type);

        // Load related data
        $subscription->load([
            'student',
            'payments',
            'cycles' => fn ($q) => $q->orderBy('cycle_number', 'desc'),
            'currentCycle',
        ]);
        $subscriptionCycles = $subscription->cycles;

        // Session cycle filter
        $cycle = $request->query('cycle', 'current');
        $sessionsQuery = $subscription->sessions()->latest('scheduled_at');

        if ($cycle === 'current' && $subscription->starts_at && $subscription->ends_at) {
            $sessionsQuery->whereBetween('scheduled_at', [$subscription->starts_at, $subscription->ends_at]);
        }

        $sessions = $sessionsQuery->paginate(15);

        // Count sessions per cycle for tabs
        $currentCycleCount = 0;
        $allSessionsCount = $subscription->sessions()->count();
        if ($subscription->starts_at && $subscription->ends_at) {
            $currentCycleCount = $subscription->sessions()
                ->whereBetween('scheduled_at', [$subscription->starts_at, $subscription->ends_at])
                ->count();
        }

        // Get teacher user for avatar
        $teacherUser = null;
        if ($type === 'quran' && $subscription->quran_teacher_id) {
            $teacherUser = \App\Models\User::find($subscription->quran_teacher_id);
        } elseif ($type === 'academic' && $subscription->teacher) {
            $teacherUser = $subscription->teacher?->user;
        }

        // Renewal chain
        $renewedBy = $subscription->renewedBySubscription;

        return view('supervisor.subscriptions.show', [
            'subscription' => $subscription,
            'subscriptionCycles' => $subscriptionCycles,
            'type' => $type,
            'sessions' => $sessions,
            'cycle' => $cycle,
            'currentCycleCount' => $currentCycleCount,
            'allSessionsCount' => $allSessionsCount,
            'teacherUser' => $teacherUser,
            'renewedBy' => $renewedBy,
            'isAdmin' => $this->isAdminUser(),
        ]);
    }

    // ========================================================================
    // Quick Actions (all POST, admin-only)
    // ========================================================================

    public function activate(Request $request, $subdomain, string $type, $id): RedirectResponse
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $subscription = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($subscription, $type);

        $subscription->activate();

        return redirect()->back()->with('success', __('supervisor.subscriptions.status_updated'));
    }

    public function pause(Request $request, $subdomain, string $type, $id): RedirectResponse
    {
        return $this->changeStatus($subdomain, $type, $id, SessionSubscriptionStatus::PAUSED);
    }

    public function resume(Request $request, $subdomain, string $type, $id): RedirectResponse
    {
        return $this->changeStatus($subdomain, $type, $id, SessionSubscriptionStatus::ACTIVE);
    }

    public function extend(Request $request, $subdomain, string $type, $id): RedirectResponse
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $subscription = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($subscription, $type);

        $validator = Validator::make($request->all(), [
            'extend_days' => 'required|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Delegate to the centralized maintenance service so Filament and
        // frontend extend actions stay in lock-step (grace on current cycle,
        // status transition from PAUSED/EXPIRED back to ACTIVE, etc.).
        try {
            app(\App\Services\Subscription\SubscriptionMaintenanceService::class)
                ->extend($subscription, (int) $request->extend_days);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', __('supervisor.subscriptions.extended_successfully'));
    }

    public function cancelExtension(Request $request, $subdomain, string $type, $id): RedirectResponse
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $subscription = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($subscription, $type);

        try {
            app(\App\Services\Subscription\SubscriptionMaintenanceService::class)
                ->cancelExtension($subscription);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __('supervisor.subscriptions.no_active_extension'));
        }

        return redirect()->back()->with('success', __('supervisor.subscriptions.extension_cancelled'));
    }

    public function cancel(Request $request, $subdomain, string $type, $id): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($sub, $type);

        \Illuminate\Support\Facades\DB::transaction(function () use ($sub) {
            if ($sub->isPending()) {
                // Pending: cancel subscription + associated pending payments
                $sub->cancelAsDuplicateOrExpired(config('subscriptions.cancellation_reasons.admin'));
                $sub->payments()->where('status', 'pending')->update(['status' => 'cancelled']);
            } else {
                // Active/Paused: cancel + suspend future sessions
                $sub->update([
                    'status' => SessionSubscriptionStatus::CANCELLED,
                    'cancelled_at' => now(),
                    'auto_renew' => false,
                ]);
                // Suspend future sessions (recoverable)
                if (method_exists($sub, 'sessions')) {
                    $sub->sessions()
                        ->whereIn('status', [\App\Enums\SessionStatus::SCHEDULED->value, \App\Enums\SessionStatus::UNSCHEDULED->value, \App\Enums\SessionStatus::READY->value])
                        ->where(fn ($q) => $q->where('scheduled_at', '>', now())->orWhereNull('scheduled_at'))
                        ->update(['status' => \App\Enums\SessionStatus::SUSPENDED->value]);
                }
            }
        });

        return redirect()->back()->with('success', __('supervisor.subscriptions.cancel_success'));
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    private function changeStatus(string $subdomain, string $type, $id, SessionSubscriptionStatus $status): RedirectResponse
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $subscription = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($subscription, $type);

        $subscription->update(['status' => $status]);

        return redirect()->back()->with('success', __('supervisor.subscriptions.status_updated'));
    }

    private function resolveSubscription(string $type, $id): QuranSubscription|AcademicSubscription
    {
        return match ($type) {
            'quran' => QuranSubscription::with(['student', 'quranTeacherUser'])->findOrFail($id),
            'academic' => AcademicSubscription::with(['student', 'teacher.user'])->findOrFail($id),
            default => abort(404),
        };
    }

    private function ensureSubscriptionInScope(QuranSubscription|AcademicSubscription $subscription, string $type): void
    {
        if ($this->isAdminUser()) {
            return;
        }

        if ($type === 'quran') {
            $ids = $this->getAssignedQuranTeacherIds();
            if (! in_array($subscription->quran_teacher_id, $ids)) {
                abort(403);
            }
        } else {
            $ids = $this->getAssignedAcademicTeacherProfileIds();
            if (! in_array($subscription->teacher_id, $ids)) {
                abort(403);
            }
        }
    }

    /**
     * Renew an active/expiring subscription.
     */
    public function renew(Request $request, $subdomain, string $type, int $subscription): RedirectResponse
    {
        return $this->performRenewalAction($request, $subdomain, $type, $subscription, 'renew');
    }

    /**
     * Resubscribe from a cancelled/expired subscription.
     */
    public function resubscribe(Request $request, $subdomain, string $type, int $subscription): RedirectResponse
    {
        return $this->performRenewalAction($request, $subdomain, $type, $subscription, 'resubscribe');
    }

    /**
     * Shared logic for renew and resubscribe actions.
     */
    private function performRenewalAction(Request $request, $subdomain, string $type, int $subscriptionId, string $mode): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $subscriptionId);
        $this->ensureSubscriptionInScope($sub, $type);

        $validator = Validator::make($request->all(), [
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'payment_mode' => 'sometimes|in:paid,unpaid',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        try {
            $service = app(\App\Services\Subscription\SubscriptionRenewalService::class);
            $options = [
                'billing_cycle' => $request->billing_cycle,
                'payment_mode' => $request->input('payment_mode', 'paid'),
            ];

            $new = $mode === 'resubscribe'
                ? $service->resubscribe($sub, $options)
                : $service->renew($sub, $options);

            $successKey = $mode === 'resubscribe' ? 'subscriptions.resubscribe_success' : 'subscriptions.renewal_success';

            return redirect()->route('manage.subscriptions.show', [
                'subdomain' => $subdomain,
                'type' => $type,
                'subscription' => $new->id,
            ])->with('success', __($successKey));
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', __('subscriptions.generic_error'));
        }
    }

    /**
     * Confirm payment for a pending subscription.
     */
    public function confirmPayment(Request $request, $subdomain, string $type, int $subscription): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $subscription);
        $this->ensureSubscriptionInScope($sub, $type);

        try {
            app(\App\Services\Payment\PaymentReconciliationService::class)
                ->confirmPaymentAndActivate(
                    $sub,
                    $request->input('payment_reference'),
                );

            return redirect()->back()->with('success', __('subscriptions.payment_confirmed_and_activated'));
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', __('subscriptions.generic_error'));
        }
    }

    /**
     * Permanently delete a subscription and all linked data.
     */
    public function destroy(Request $request, $subdomain, string $type, int $subscription): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $subscription);
        $this->ensureSubscriptionInScope($sub, $type);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($sub) {
                // Delete session reports first
                if (method_exists($sub, 'sessions')) {
                    $sessionIds = $sub->sessions()->withTrashed()->pluck('id');
                    if ($sessionIds->isNotEmpty()) {
                        \App\Models\StudentSessionReport::whereIn('session_id', $sessionIds)->delete();
                        // Academic sessions may have their own report model
                        if (class_exists(\App\Models\AcademicSessionReport::class)) {
                            \App\Models\AcademicSessionReport::whereIn('session_id', $sessionIds)->delete();
                        }
                    }
                    $sub->sessions()->withTrashed()->forceDelete();
                }

                // Delete linked circle/lesson
                if ($sub instanceof \App\Models\QuranSubscription && $sub->education_unit_id) {
                    $sub->educationUnit?->forceDelete();
                }
                if ($sub instanceof \App\Models\AcademicSubscription) {
                    $sub->lesson?->forceDelete();
                }

                $sub->payments()->withTrashed()->forceDelete();
                $sub->forceDelete();
            });

            return redirect()->route('manage.subscriptions.index', ['subdomain' => $subdomain])
                ->with('success', __('supervisor.subscriptions.delete_success'));
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', __('subscriptions.generic_error'));
        }
    }
}
