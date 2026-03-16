<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
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
        if (! $this->canManageStudents()) {
            abort(403);
        }

        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $subscriptions = collect();

        // Load Quran subscriptions
        if (! empty($quranTeacherIds)) {
            $quranSubs = QuranSubscription::whereIn('quran_teacher_id', $quranTeacherIds)
                ->with(['student', 'quranTeacherUser'])
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
        if (! empty($academicTeacherProfileIds)) {
            $academicSubs = AcademicSubscription::whereIn('teacher_id', $academicTeacherProfileIds)
                ->with(['student', 'teacher.user'])
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

        // Stats from unfiltered set
        $totalActive = $subscriptions->filter(fn ($s) => $s['status'] === SessionSubscriptionStatus::ACTIVE)->count();
        $expiringThisWeek = $subscriptions->filter(function ($s) {
            return $s['status'] === SessionSubscriptionStatus::ACTIVE
                && $s['end_date']
                && $s['end_date']->between(now(), now()->addDays(7));
        })->count();
        $totalPending = $subscriptions->filter(fn ($s) => $s['status'] === SessionSubscriptionStatus::PENDING)->count();
        $totalPaused = $subscriptions->filter(fn ($s) => $s['status'] === SessionSubscriptionStatus::PAUSED)->count();
        $totalExpired = $subscriptions->filter(fn ($s) => $s['status'] === SessionSubscriptionStatus::EXPIRED)->count();
        $totalExtended = $subscriptions->filter(function ($s) {
            $gracePeriodEndsAt = $s['model']->metadata['grace_period_ends_at'] ?? null;

            return $gracePeriodEndsAt && Carbon::parse($gracePeriodEndsAt)->isFuture();
        })->count();

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

        // Sort
        $sort = $request->get('sort', 'newest');
        $filtered = match ($sort) {
            'oldest' => $filtered->sortBy('created_at'),
            'expiring_soon' => $filtered->sortBy(fn ($s) => $s['end_date'] ?? now()->addYears(10)),
            'sessions_remaining' => $filtered->sortBy('sessions_remaining'),
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

        $isAdmin = $this->isAdminUser();

        return view('supervisor.subscriptions.index', [
            'subscriptions' => $paginated,
            'totalCount' => $subscriptions->count(),
            'totalActive' => $totalActive,
            'expiringThisWeek' => $expiringThisWeek,
            'totalPending' => $totalPending,
            'totalPaused' => $totalPaused,
            'totalExpired' => $totalExpired,
            'totalExtended' => $totalExtended,
            'filteredCount' => $filteredValues->count(),
            'isAdmin' => $isAdmin,
        ]);
    }

    public function show(Request $request, $subdomain, string $type, $id): View
    {
        if (! $this->canManageStudents()) {
            abort(403);
        }

        $subscription = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($subscription, $type);

        $sessions = $subscription->sessions()->latest('scheduled_at')->paginate(10);

        return view('supervisor.subscriptions.show', [
            'subscription' => $subscription,
            'type' => $type,
            'sessions' => $sessions,
            'isAdmin' => $this->isAdminUser(),
        ]);
    }

    // ========================================================================
    // Quick Actions (all POST, admin-only)
    // ========================================================================

    public function activate(Request $request, $subdomain, string $type, $id): RedirectResponse
    {
        return $this->changeStatus($subdomain, $type, $id, SessionSubscriptionStatus::ACTIVE);
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
        if (! $this->canManageStudents()) {
            abort(403);
        }

        $subscription = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($subscription, $type);

        $validator = Validator::make($request->all(), [
            'extend_days' => 'required|integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $graceDays = (int) $request->extend_days;
        $metadata = $subscription->metadata ?? [];

        // Calculate grace_period_ends_at: stack on existing grace period or start from ends_at
        $baseDate = isset($metadata['grace_period_ends_at'])
            ? Carbon::parse($metadata['grace_period_ends_at'])
            : ($subscription->ends_at ?? now());

        $gracePeriodEndsAt = $baseDate->copy()->addDays($graceDays);
        $metadata['grace_period_ends_at'] = $gracePeriodEndsAt->toDateTimeString();

        // Log extension in metadata (same format as Filament action)
        $metadata['extensions'] = $metadata['extensions'] ?? [];
        $metadata['extensions'][] = [
            'type' => 'grace_period',
            'grace_days' => $graceDays,
            'extended_by' => auth()->id(),
            'extended_by_name' => auth()->user()->name,
            'ends_at_at_time' => ($subscription->ends_at ?? now())->toDateTimeString(),
            'grace_period_ends_at' => $gracePeriodEndsAt->toDateTimeString(),
            'extended_at' => now()->toDateTimeString(),
        ];

        $updateData = ['metadata' => $metadata];

        // If subscription is EXPIRED or SUSPENDED, transition to ACTIVE so the student regains access
        if (in_array($subscription->status, [SessionSubscriptionStatus::EXPIRED, SessionSubscriptionStatus::SUSPENDED])) {
            $updateData['status'] = SessionSubscriptionStatus::ACTIVE;
        }

        // Only update metadata (+ status if needed) — do NOT change ends_at
        $subscription->update($updateData);

        return redirect()->back()->with('success', __('supervisor.subscriptions.extended_successfully'));
    }

    public function cancelExtension(Request $request, $subdomain, string $type, $id): RedirectResponse
    {
        if (! $this->canManageStudents()) {
            abort(403);
        }

        $subscription = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($subscription, $type);

        $metadata = $subscription->metadata ?? [];

        if (! isset($metadata['grace_period_ends_at'])) {
            return redirect()->back()->with('error', __('supervisor.subscriptions.no_active_extension'));
        }

        // Remove grace period metadata
        unset($metadata['grace_period_ends_at']);

        // Log cancellation in extensions history
        $metadata['extensions'] = $metadata['extensions'] ?? [];
        $metadata['extensions'][] = [
            'type' => 'grace_period_cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_by_name' => auth()->user()->name,
            'cancelled_at' => now()->toDateTimeString(),
        ];

        $updateData = ['metadata' => $metadata ?: null];

        // If subscription period has actually ended, revert to EXPIRED
        if ($subscription->ends_at && $subscription->ends_at->isPast()) {
            $updateData['status'] = SessionSubscriptionStatus::EXPIRED;
        }

        $subscription->update($updateData);

        return redirect()->back()->with('success', __('supervisor.subscriptions.extension_cancelled'));
    }

    public function cancel(Request $request, $subdomain, string $type, $id): RedirectResponse
    {
        return $this->changeStatus($subdomain, $type, $id, SessionSubscriptionStatus::CANCELLED);
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    private function changeStatus(string $subdomain, string $type, $id, SessionSubscriptionStatus $status): RedirectResponse
    {
        if (! $this->canManageStudents()) {
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
}
