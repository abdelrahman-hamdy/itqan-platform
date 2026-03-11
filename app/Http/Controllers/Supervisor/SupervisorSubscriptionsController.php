<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SupervisorSubscriptionsController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $subscriptions = collect();

        // Load Quran subscriptions
        if (!empty($quranTeacherIds)) {
            $quranSubs = QuranSubscription::whereIn('quran_teacher_id', $quranTeacherIds)
                ->with(['student', 'quranTeacherUser'])
                ->get()
                ->map(fn ($sub) => [
                    'id' => $sub->id,
                    'type' => 'quran',
                    'model' => $sub,
                    'student_name' => $sub->student?->name ?? '-',
                    'teacher_name' => $sub->quranTeacherUser?->name ?? '-',
                    'status' => $sub->status,
                    'sessions_total' => $sub->total_sessions ?? 0,
                    'sessions_completed' => $sub->total_sessions_completed ?? 0,
                    'sessions_remaining' => $sub->sessions_remaining ?? (($sub->total_sessions ?? 0) - ($sub->total_sessions_completed ?? 0)),
                    'start_date' => $sub->starts_at,
                    'end_date' => $sub->ends_at,
                    'created_at' => $sub->created_at,
                ]);
            $subscriptions = $subscriptions->merge($quranSubs);
        }

        // Load Academic subscriptions
        if (!empty($academicTeacherProfileIds)) {
            $academicSubs = AcademicSubscription::whereIn('teacher_id', $academicTeacherProfileIds)
                ->with(['student', 'teacher.user'])
                ->get()
                ->map(fn ($sub) => [
                    'id' => $sub->id,
                    'type' => 'academic',
                    'model' => $sub,
                    'student_name' => $sub->student?->name ?? '-',
                    'teacher_name' => $sub->teacher?->user?->name ?? '-',
                    'status' => $sub->status,
                    'sessions_total' => $sub->total_sessions ?? 0,
                    'sessions_completed' => $sub->total_sessions_completed ?? 0,
                    'sessions_remaining' => $sub->sessions_remaining ?? (($sub->total_sessions ?? 0) - ($sub->total_sessions_completed ?? 0)),
                    'start_date' => $sub->starts_at,
                    'end_date' => $sub->ends_at,
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

        // Apply filters
        $filtered = $subscriptions;

        if ($type = $request->input('type')) {
            $filtered = $filtered->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $filtered = $filtered->filter(fn ($s) => $s['status']->value === $status);
        }

        if ($search = $request->input('search')) {
            $search = mb_strtolower($search);
            $filtered = $filtered->filter(function ($s) use ($search) {
                return str_contains(mb_strtolower($s['student_name']), $search)
                    || str_contains(mb_strtolower($s['teacher_name']), $search);
            });
        }

        // Sort by newest
        $filtered = $filtered->sortByDesc('created_at');

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
            'filteredCount' => $filteredValues->count(),
            'isAdmin' => $isAdmin,
        ]);
    }

    public function create(Request $request, $subdomain = null): View
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        $quranTeachers = !empty($quranTeacherIds)
            ? User::whereIn('id', $quranTeacherIds)->get()
            : collect();

        $academicTeachers = !empty($academicTeacherIds)
            ? User::whereIn('id', $academicTeacherIds)->get()
            : collect();

        $students = User::where('user_type', 'student')->where('active_status', true)->get();

        return view('supervisor.subscriptions.create', compact(
            'quranTeachers',
            'academicTeachers',
            'students',
        ));
    }

    public function store(Request $request, $subdomain = null): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:quran,academic',
            'student_id' => 'required|exists:users,id',
            'teacher_id' => 'required|integer',
            'total_sessions' => 'required|integer|min:1',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $academyId = $this->getAcademyId();

        if ($request->type === 'quran') {
            QuranSubscription::create([
                'academy_id' => $academyId,
                'student_id' => $request->student_id,
                'quran_teacher_id' => $request->teacher_id,
                'total_sessions' => $request->total_sessions,
                'sessions_remaining' => $request->total_sessions,
                'status' => SessionSubscriptionStatus::ACTIVE,
                'payment_status' => 'paid',
                'starts_at' => $request->starts_at,
                'ends_at' => $request->ends_at,
                'billing_cycle' => 'monthly',
                'final_price' => 0,
                'currency' => 'SAR',
            ]);
        } else {
            AcademicSubscription::create([
                'academy_id' => $academyId,
                'student_id' => $request->student_id,
                'teacher_id' => $request->teacher_id,
                'total_sessions' => $request->total_sessions,
                'sessions_remaining' => $request->total_sessions,
                'status' => SessionSubscriptionStatus::ACTIVE,
                'payment_status' => 'paid',
                'starts_at' => $request->starts_at,
                'ends_at' => $request->ends_at,
                'billing_cycle' => 'monthly',
                'final_price' => 0,
                'currency' => 'SAR',
            ]);
        }

        return redirect()->route('manage.subscriptions.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.subscriptions.created_successfully'));
    }

    public function show(Request $request, $subdomain, string $type, $id): View
    {
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

    public function edit(Request $request, $subdomain, string $type, $id): View
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $subscription = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($subscription, $type);

        return view('supervisor.subscriptions.edit', [
            'subscription' => $subscription,
            'type' => $type,
        ]);
    }

    public function update(Request $request, $subdomain, string $type, $id): RedirectResponse
    {
        if (!$this->isAdminUser()) {
            abort(403);
        }

        $subscription = $this->resolveSubscription($type, $id);
        $this->ensureSubscriptionInScope($subscription, $type);

        $validator = Validator::make($request->all(), [
            'total_sessions' => 'required|integer|min:1',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $subscription->update([
            'total_sessions' => $request->total_sessions,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'notes' => $request->notes,
        ]);

        return redirect()->route('manage.subscriptions.show', ['subdomain' => $subdomain, 'type' => $type, 'id' => $id])
            ->with('success', __('supervisor.subscriptions.updated_successfully'));
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
        if (!$this->isAdminUser()) {
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

        $subscription->update([
            'ends_at' => ($subscription->ends_at ?? now())->addDays($request->extend_days),
        ]);

        return redirect()->back()->with('success', __('supervisor.subscriptions.extended_successfully'));
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
        if (!$this->isAdminUser()) {
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
            if (!in_array($subscription->quran_teacher_id, $ids)) {
                abort(403);
            }
        } else {
            $ids = $this->getAssignedAcademicTeacherProfileIds();
            if (!in_array($subscription->teacher_id, $ids)) {
                abort(403);
            }
        }
    }
}
