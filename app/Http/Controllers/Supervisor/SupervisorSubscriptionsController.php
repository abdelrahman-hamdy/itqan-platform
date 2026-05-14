<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionType;
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
        if (! $this->canAccessSubscriptions()) {
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
            'canManage' => $this->canManageSubscriptions(),
            'isAdmin' => $isAdmin,
            'filterUser' => $filterUser,
        ]);
    }

    public function show(Request $request, $subdomain, string $type, $id): View
    {
        if (! $this->canAccessSubscriptions()) {
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
        if ($type === SubscriptionType::QURAN->value && $subscription->quran_teacher_id) {
            $teacherUser = \App\Models\User::find($subscription->quran_teacher_id);
        } elseif ($type === SubscriptionType::ACADEMIC->value && $subscription->teacher) {
            $teacherUser = $subscription->teacher?->user;
        }

        // Renewal chain
        $renewedBy = $subscription->renewedBySubscription;

        // Renewal modal needs the active-package set so the supervisor can
        // pick a different package on renew (INV-H2 — admins are not bound
        // by the previous-package-active rule that constrains students).
        $renewalOptions = app(\App\Services\Subscription\SubscriptionRenewalService::class)
            ->getRenewalOptions($subscription);

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
            'canManage' => $this->canManageSubscriptions(),
            'renewalOptions' => $renewalOptions,
        ]);
    }

    // ========================================================================
    // Quick Actions (all POST, manage permission required)
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
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $subscription = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($subscription, $type);

        // Delegate to the model so paused_at + pause_reason are stamped and
        // canPause()/transition gating fires. The previous raw-update path
        // left 40+ subs in PAUSED status with paused_at=null, which broke
        // resume()'s ends_at time compensation.
        $reason = trim((string) $request->input('pause_reason', ''));
        if ($reason === '') {
            $reason = __('supervisor.subscriptions.pause_reason_manual_default');
        }

        try {
            $subscription->pause($reason);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', __('supervisor.subscriptions.paused_success'));
    }

    public function resume(Request $request, $subdomain, string $type, $id): RedirectResponse
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $subscription = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($subscription, $type);

        // Delegate to the model so ends_at is extended by the paused
        // duration and any SUSPENDED sessions in window are restored to
        // SCHEDULED. The raw-update path left 52 subs ACTIVE with stale
        // paused_at, and 16 sessions stranded in SUSPENDED on prod.
        try {
            $subscription->resume();
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', __('supervisor.subscriptions.resumed_success'));
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
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($sub, $type);

        \Illuminate\Support\Facades\DB::transaction(function () use ($sub) {
            if ($sub->isPending()) {
                // Pending: cancel subscription + associated pending payments
                $sub->cancelAsDuplicateOrExpired(config('subscriptions.cancellation_reasons.admin'));
                $sub->payments()->where('status', PaymentStatus::PENDING->value)->update(['status' => PaymentStatus::CANCELLED->value]);
            } else {
                // Active/Paused: cancel + suspend future sessions
                $sub->update([
                    'status' => SessionSubscriptionStatus::CANCELLED,
                    'cancelled_at' => now(),
                    'auto_renew' => false,
                ]);
                // Suspend future sessions (recoverable on reactivation).
                // Subscription isolation: do NOT auto-rebind these sessions to
                // another active subscription for the same student/teacher.
                // Each subscription is a self-contained unit — restoration must
                // happen via extend()/resume()/renew() on this same subscription.
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

        if ($type === SubscriptionType::QURAN->value) {
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
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $subscriptionId);
        $this->ensureSubscriptionInScope($sub, $type);

        $validator = Validator::make($request->all(), [
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'payment_mode' => 'sometimes|in:paid,unpaid',
            'package_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        try {
            $service = app(\App\Services\Subscription\SubscriptionRenewalService::class);

            // Supervisor renew with package change: validate the picked package
            // is an active package on THIS academy (INV-H2 — admin/supervisor
            // path bypasses the "previous package must be active" student
            // gate, but the chosen package itself still has to be active +
            // tenant-scoped to avoid cross-academy data bleed).
            $packageId = $request->filled('package_id') ? (int) $request->package_id : null;
            if ($packageId !== null) {
                $available = $service->getRenewalOptions($sub);
                $validPackageIds = collect($available['packages'])->pluck('id')->all();
                if (! in_array($packageId, $validPackageIds, true)) {
                    return redirect()->back()->with('error', __('subscriptions.errors.invalid_package'));
                }
            }

            $options = array_filter([
                'billing_cycle' => $request->billing_cycle,
                'payment_mode' => $request->input('payment_mode', 'paid'),
                'package_id' => $packageId,
            ], fn ($v) => $v !== null);

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
        if (! $this->canManageSubscriptions()) {
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
        if (! $this->canManageSubscriptions()) {
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

    // ========================================================================
    // Cycle data-fix tool (F2)
    // ========================================================================

    /**
     * Cycle inspector — read-only diagnostic view for a single cycle.
     *
     * Shows the cycle row, the live `session_consumption` truth-source for
     * INV-B3, the invariant violations scoped to this cycle, the sessions
     * anchored here, payments, and the queued-sibling anchor check (INV-A5).
     * Operators land here before touching anything via the editor.
     */
    public function inspectCycle(Request $request, $subdomain, string $type, int $subscription, int $cycle): View
    {
        if (! $this->canAccessSubscriptions()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $subscription);
        $this->ensureSubscriptionInScope($sub, $type);

        /** @var \App\Models\SubscriptionCycle $cycleRow */
        $cycleRow = \App\Models\SubscriptionCycle::query()
            ->where('subscribable_type', $sub->getMorphClass())
            ->where('subscribable_id', $sub->id)
            ->where('id', $cycle)
            ->firstOrFail();

        // Sessions anchored to this cycle (subscription_cycle_id stamp).
        $cycleSessions = $sub->sessions()
            ->where('subscription_cycle_id', $cycleRow->id)
            ->orderBy('scheduled_at')
            ->get();

        // Active + reversed consumption rows for this cycle, ordered by recency.
        $consumptionRows = \App\Models\SessionConsumption::query()
            ->where('cycle_id', $cycleRow->id)
            ->orderByDesc('consumed_at')
            ->get();

        // Precompute the session_id → has-consumption-row map from the already-
        // loaded $consumptionRows. The blade uses an in_array() lookup against
        // this list to render the "clean / has data" tag without per-row queries.
        $consumedSessionIds = $consumptionRows->pluck('session_id')->unique()->all();

        // Payments tied to this cycle.
        $cyclePayments = \App\Models\Payment::query()
            ->where('subscription_cycle_id', $cycleRow->id)
            ->orderByDesc('created_at')
            ->get();

        // Live truth-source count for INV-B3 — what the reconciler would
        // derive `cycle.sessions_used` to. Operator compares this to the
        // stored column to spot pre-reconcile drift.
        $derivedSessionsUsed = $consumptionRows->whereNull('reversed_at')->count();

        // Invariant violations scoped to this cycle. Filter on the
        // `context.cycle_id` payload from the checker's violation rows.
        $allViolations = app(\App\Services\Subscription\SubscriptionInvariantChecker::class)->check($sub);
        $cycleViolations = array_values(array_filter(
            $allViolations,
            fn ($v) => ($v['context']['cycle_id'] ?? null) === $cycleRow->id,
        ));

        // Queued-sibling anchor info for INV-A5 visualisation.
        $activeCycle = $sub->currentCycle;
        $queuedCycle = $sub->queuedCycle()->first();

        return view('supervisor.subscriptions.cycle-inspect', [
            'subscription' => $sub,
            'cycle' => $cycleRow,
            'type' => $type,
            'subdomain' => $subdomain,
            'cycleSessions' => $cycleSessions,
            'consumptionRows' => $consumptionRows,
            'consumedSessionIds' => $consumedSessionIds,
            'cyclePayments' => $cyclePayments,
            'derivedSessionsUsed' => $derivedSessionsUsed,
            'cycleViolations' => $cycleViolations,
            'allViolations' => $allViolations,
            'activeCycle' => $activeCycle,
            'queuedCycle' => $queuedCycle,
            'canManage' => $this->canManageSubscriptions(),
        ]);
    }

    /**
     * Apply a validated patch to a SubscriptionCycle row.
     *
     * Flow:
     *   1. `EditCycleRequest` strips forbidden fields (sessions_used etc.).
     *   2. `CycleEditValidator` detects block-on-conflict cases — sessions
     *      that would be orphaned, queue overlap, sessions_used > new total.
     *   3. On conflicts: flash a structured error payload + redirect back to
     *      the inspector. NO db writes happen.
     *   4. On no conflicts: delegate to `SubscriptionLifecycle::adminEditCycle`
     *      which wraps in lock + audit + reconciler.
     */
    public function editCycle(
        \App\Http\Requests\Supervisor\EditCycleRequest $request,
        $subdomain,
        string $type,
        int $subscription,
        int $cycle,
    ): RedirectResponse {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $subscription);
        $this->ensureSubscriptionInScope($sub, $type);

        /** @var \App\Models\SubscriptionCycle $cycleRow */
        $cycleRow = \App\Models\SubscriptionCycle::query()
            ->where('subscribable_type', $sub->getMorphClass())
            ->where('subscribable_id', $sub->id)
            ->where('id', $cycle)
            ->firstOrFail();

        $patch = $request->validated();

        $conflicts = app(\App\Support\Subscriptions\CycleEditValidator::class)
            ->validate($sub, $cycleRow, $patch);

        if (! empty($conflicts)) {
            return redirect()->back()
                ->with('error', __('supervisor.subscriptions.cycle_edit_conflicts'))
                ->with('cycle_edit_conflicts', $conflicts)
                ->withInput();
        }

        try {
            app(\App\Services\Subscription\SubscriptionLifecycle::class)
                ->adminEditCycle($sub, $cycleRow, $patch, auth()->user());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', $e->getMessage() ?: __('subscriptions.generic_error'));
        }

        return redirect()->route('manage.subscriptions.cycles.inspect', [
            'subdomain' => $subdomain,
            'type' => $type,
            'subscription' => $sub->id,
            'cycle' => $cycleRow->id,
        ])->with('success', __('supervisor.subscriptions.cycle_edit_success'));
    }

    /**
     * Reverse an active consumption row. Delegates straight to
     * `SubscriptionConsumption::reverse` which already wraps in
     * lock + audit + reconciler per §6.
     */
    public function reverseConsumption(Request $request, $subdomain, string $type, int $subscription, int $cycle, int $consumption): RedirectResponse
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $subscription);
        $this->ensureSubscriptionInScope($sub, $type);

        $row = $this->resolveOwnedConsumptionRow($sub, $cycle, $consumption);

        try {
            app(\App\Services\Subscription\SubscriptionConsumption::class)
                ->reverse($row, 'admin_data_fix', auth()->user());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', __('subscriptions.generic_error'));
        }

        return redirect()->back()->with('success', __('supervisor.subscriptions.consumption_reversed_success'));
    }

    /**
     * Promote an existing consumption row to admin_manual (or re-record a
     * previously-reversed row as admin_manual).
     *
     * P5 cascade: admin_manual is the top precedence so this is always a
     * no-resistance write. Keeps the existing consumption_type to avoid
     * accidental data loss.
     */
    public function promoteConsumption(Request $request, $subdomain, string $type, int $subscription, int $cycle, int $consumption): RedirectResponse
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $subscription);
        $this->ensureSubscriptionInScope($sub, $type);

        $row = $this->resolveOwnedConsumptionRow($sub, $cycle, $consumption);

        // Resolve the session row the consumption belongs to so we can route
        // through `record()` (the precedence-aware writer). `record()` keys on
        // (session, subscription) and updates in place when an existing row
        // is found, so this both promotes-source AND re-records-after-reverse.
        $session = $row->session;
        $student = $row->studentUser;

        if ($session === null || $student === null) {
            return redirect()->back()->with('error', __('supervisor.subscriptions.consumption_missing_session_or_student'));
        }

        try {
            app(\App\Services\Subscription\SubscriptionConsumption::class)
                ->record(
                    $session,
                    $student,
                    $sub,
                    \App\Models\SessionConsumption::SOURCE_ADMIN_MANUAL,
                    auth()->user(),
                    $row->consumption_type,
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', $e->getMessage() ?: __('subscriptions.generic_error'));
        }

        return redirect()->back()->with('success', __('supervisor.subscriptions.consumption_promoted_success'));
    }

    /**
     * Record a fresh consumption row for a session that has none — used when
     * auto-attendance missed firing but admin can confirm the outcome.
     */
    public function recordConsumptionForSession(Request $request, $subdomain, string $type, int $subscription, int $cycle, int $session): RedirectResponse
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $subscription);
        $this->ensureSubscriptionInScope($sub, $type);

        $validated = $request->validate([
            'consumption_type' => 'required|in:attended,late,left,absent_counted',
        ]);

        $sessionRow = $this->resolveOwnedSession($sub, $cycle, $session);
        $student = $sub->student;

        if ($student === null) {
            return redirect()->back()->with('error', __('supervisor.subscriptions.session_missing_student'));
        }

        try {
            app(\App\Services\Subscription\SubscriptionConsumption::class)
                ->record(
                    $sessionRow,
                    $student,
                    $sub,
                    \App\Models\SessionConsumption::SOURCE_ADMIN_MANUAL,
                    auth()->user(),
                    $validated['consumption_type'],
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', $e->getMessage() ?: __('subscriptions.generic_error'));
        }

        return redirect()->back()->with('success', __('supervisor.subscriptions.consumption_recorded_success'));
    }

    /**
     * Hard-delete a clean future session row. Refuses if any
     * attendance/reports/consumption/earnings exist — operator must cancel
     * those dependencies first via the per-row actions on the inspector.
     */
    public function deleteSession(Request $request, $subdomain, string $type, int $subscription, int $cycle, int $session): RedirectResponse
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $subscription);
        $this->ensureSubscriptionInScope($sub, $type);

        $sessionRow = $this->resolveOwnedSession($sub, $cycle, $session);

        try {
            $this->assertSessionCleanForDelete($sessionRow);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        try {
            app(\App\Services\Subscription\SubscriptionLifecycle::class)
                ->adminDeleteSession($sub, $sessionRow, auth()->user());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', __('subscriptions.generic_error'));
        }

        return redirect()->back()->with('success', __('supervisor.subscriptions.session_deleted_success'));
    }

    /**
     * Soft-cancel a scheduled session (sets status=CANCELLED, leaves the row).
     */
    public function cancelSession(Request $request, $subdomain, string $type, int $subscription, int $cycle, int $session): RedirectResponse
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $sub = $this->resolveSubscription($type, $subscription);
        $this->ensureSubscriptionInScope($sub, $type);

        $sessionRow = $this->resolveOwnedSession($sub, $cycle, $session);

        try {
            app(\App\Services\Subscription\SubscriptionLifecycle::class)
                ->adminCancelSession($sub, $sessionRow, auth()->user());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', __('subscriptions.generic_error'));
        }

        return redirect()->back()->with('success', __('supervisor.subscriptions.session_cancelled_success'));
    }

    /**
     * Find a consumption row that belongs to (sub, cycle). 404 if any of the
     * key constraints don't line up — defends against id-rewriting attacks
     * where an operator with one sub's inspector open submits a consumption
     * id from a different cycle.
     */
    private function resolveOwnedConsumptionRow($sub, int $cycleId, int $consumptionId): \App\Models\SessionConsumption
    {
        return \App\Models\SessionConsumption::query()
            ->where('id', $consumptionId)
            ->where('cycle_id', $cycleId)
            ->where('subscription_id', $sub->id)
            ->where('subscription_type', $sub->getMorphClass())
            ->firstOrFail();
    }

    /**
     * Find a session anchored to (sub, cycle). 404 if mismatched.
     */
    private function resolveOwnedSession($sub, int $cycleId, int $sessionId): \App\Models\BaseSession
    {
        $session = $sub->sessions()
            ->where('id', $sessionId)
            ->where('subscription_cycle_id', $cycleId)
            ->first();

        if ($session === null) {
            abort(404);
        }

        return $session;
    }

    /**
     * Refuse to hard-delete a session that already has user-facing data.
     * "Clean" = scheduled, no attendance rows, no session reports, no
     * consumption rows, no teacher earnings.
     */
    private function assertSessionCleanForDelete(\App\Models\BaseSession $session): void
    {
        $statusValue = $session->status?->value ?? (string) $session->status;
        if ($statusValue !== \App\Enums\SessionStatus::SCHEDULED->value) {
            throw new \RuntimeException(__('supervisor.subscriptions.session_delete_blocked_status'));
        }

        if (method_exists($session, 'attendances') && $session->attendances()->exists()) {
            throw new \RuntimeException(__('supervisor.subscriptions.session_delete_blocked_attendance'));
        }

        if (method_exists($session, 'reports') && $session->reports()->exists()) {
            throw new \RuntimeException(__('supervisor.subscriptions.session_delete_blocked_reports'));
        }

        $hasConsumption = \App\Models\SessionConsumption::query()
            ->where('session_id', $session->id)
            ->where('session_type', $session->getMorphClass())
            ->exists();

        if ($hasConsumption) {
            throw new \RuntimeException(__('supervisor.subscriptions.session_delete_blocked_consumption'));
        }

        $hasEarnings = \App\Models\TeacherEarning::query()
            ->where('session_id', $session->id)
            ->where('session_type', $session->getMorphClass())
            ->exists();

        if ($hasEarnings) {
            throw new \RuntimeException(__('supervisor.subscriptions.session_delete_blocked_earnings'));
        }
    }
}
