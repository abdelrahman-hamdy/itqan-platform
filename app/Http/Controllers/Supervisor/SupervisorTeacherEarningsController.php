<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupervisorTeacherEarningsController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        if (! $this->canManageTeacherEarnings()) {
            abort(403);
        }

        $academyId = $this->getAcademyId();
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
        $allTeacherIds = array_merge($quranTeacherIds, $academicTeacherIds);

        [$quranProfileIds, $academicProfileIds] = $this->resolveProfileIds($quranTeacherIds, $academicTeacherIds);
        $profileUserMap = $this->buildProfileUserMap($quranTeacherIds, $academicTeacherIds);
        $teachersList = $this->buildTeachersList($profileUserMap);

        $currentTeacherId = $request->input('teacher_id');
        if ($currentTeacherId && ! in_array((int) $currentTeacherId, $allTeacherIds)) {
            $currentTeacherId = null;
        }

        $scopeQuery = $this->buildTeacherScopeQuery($quranProfileIds, $academicProfileIds, $teachersList, $currentTeacherId ? (int) $currentTeacherId : null);

        $currentMonth = $request->input('month');
        $currentStatus = $request->input('status', 'all');

        $statsBase = TeacherEarning::where('academy_id', $academyId)->where($scopeQuery);

        $now = Carbon::now();
        $stats = [
            'totalEarningsThisMonth' => (clone $statsBase)->forMonth($now->year, $now->month)->sum('amount'),
            'totalEarningsAllTime' => (clone $statsBase)->sum('amount'),
            'finalizedAmount' => (clone $statsBase)->finalized()->sum('amount'),
            'disputedAmount' => (clone $statsBase)->disputed()->sum('amount'),
            'sessionsCount' => (clone $statsBase)->count(),
        ];

        $earningsQuery = TeacherEarning::where('academy_id', $academyId)
            ->where($scopeQuery)
            ->with([
                'teacher',
                'session' => function ($morphTo) {
                    $morphTo->morphWith([
                        QuranSession::class => ['individualCircle', 'circle'],
                        AcademicSession::class => ['academicIndividualLesson'],
                        InteractiveCourseSession::class => ['course'],
                    ]);
                },
            ]);

        $this->applyMonthFilter($earningsQuery, $currentMonth);

        if ($currentStatus === 'finalized') {
            $earningsQuery->finalized();
        } elseif ($currentStatus === 'pending') {
            $earningsQuery->unpaid();
        } elseif ($currentStatus === 'disputed') {
            $earningsQuery->disputed();
        }

        $earnings = $earningsQuery->orderByDesc('session_completed_at')->paginate(15);

        return view('supervisor.teacher-earnings.index', [
            'earnings' => $earnings,
            'stats' => $stats,
            'availableMonths' => $this->getAvailableMonths($academyId, $scopeQuery),
            'teachers' => $teachersList,
            'profileUserMap' => $profileUserMap,
            'currentTeacherId' => $currentTeacherId,
            'currentMonth' => $currentMonth,
            'currentStatus' => $currentStatus,
            'activeTab' => 'details',
        ]);
    }

    public function teacherSummary(Request $request, $subdomain = null): View
    {
        if (! $this->canManageTeacherEarnings()) {
            abort(403);
        }

        $academyId = $this->getAcademyId();
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
        $allTeacherIds = array_merge($quranTeacherIds, $academicTeacherIds);

        [$quranProfileIds, $academicProfileIds] = $this->resolveProfileIds($quranTeacherIds, $academicTeacherIds);
        $profileUserMap = $this->buildProfileUserMap($quranTeacherIds, $academicTeacherIds);
        $teachersList = $this->buildTeachersList($profileUserMap);

        $currentTeacherId = $request->input('teacher_id');
        if ($currentTeacherId && ! in_array((int) $currentTeacherId, $allTeacherIds)) {
            $currentTeacherId = null;
        }

        $scopeQuery = $this->buildTeacherScopeQuery($quranProfileIds, $academicProfileIds, $teachersList, $currentTeacherId ? (int) $currentTeacherId : null);

        $currentMonth = $request->input('month');

        $query = TeacherEarning::where('academy_id', $academyId)->where($scopeQuery);
        $this->applyMonthFilter($query, $currentMonth);

        $rawEarnings = (clone $query)
            ->select(
                'teacher_type',
                'teacher_id',
                'session_type',
                'calculation_method',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as sessions_count')
            )
            ->groupBy('teacher_type', 'teacher_id', 'session_type', 'calculation_method')
            ->get();

        $teacherSummaries = [];
        foreach ($rawEarnings as $row) {
            $key = $row->teacher_type.'_'.$row->teacher_id;

            if (! isset($teacherSummaries[$key])) {
                $teacherSummaries[$key] = [
                    'teacher_type' => $row->teacher_type,
                    'teacher_id' => $row->teacher_id,
                    'quran_individual' => ['amount' => 0, 'details' => []],
                    'quran_group' => ['amount' => 0, 'details' => []],
                    'academic' => ['amount' => 0, 'details' => []],
                    'interactive' => ['amount' => 0, 'details' => []],
                    'total' => 0,
                    'sessions_count' => 0,
                ];
            }

            $teacherSummaries[$key]['total'] += $row->total_amount;
            $teacherSummaries[$key]['sessions_count'] += $row->sessions_count;

            $detail = [
                'method' => $row->calculation_method,
                'sessions_count' => $row->sessions_count,
                'amount' => $row->total_amount,
            ];

            if ($row->session_type === QuranSession::class) {
                $isGroup = in_array($row->calculation_method, ['group_rate', 'per_student']);
                $source = $isGroup ? 'quran_group' : 'quran_individual';
            } elseif ($row->session_type === AcademicSession::class) {
                $source = 'academic';
            } elseif ($row->session_type === InteractiveCourseSession::class) {
                $source = 'interactive';
            } else {
                continue;
            }

            $teacherSummaries[$key][$source]['amount'] += $row->total_amount;
            $teacherSummaries[$key][$source]['details'][] = $detail;
        }

        usort($teacherSummaries, fn ($a, $b) => $b['total'] <=> $a['total']);

        return view('supervisor.teacher-earnings.teacher-summary', [
            'teacherSummaries' => $teacherSummaries,
            'profileUserMap' => $profileUserMap,
            'availableMonths' => $this->getAvailableMonths($academyId, $scopeQuery),
            'teachers' => $teachersList,
            'currentTeacherId' => $currentTeacherId,
            'currentMonth' => $currentMonth,
            'activeTab' => 'summary',
        ]);
    }

    public function dispute(Request $request, $subdomain, TeacherEarning $earning): RedirectResponse
    {
        if (! $this->canManageTeacherEarnings()) {
            abort(403);
        }

        $this->authorize('update', $earning);
        $this->validateEarningBelongsToAssignedTeachers($earning);

        $request->validate([
            'dispute_notes' => 'required|string|max:1000',
        ]);

        if ($earning->is_disputed) {
            return back()->with('error', __('supervisor.teacher_earnings.already_disputed'));
        }

        $earning->update([
            'is_disputed' => true,
            'dispute_notes' => $request->input('dispute_notes'),
        ]);

        return back()->with('success', __('supervisor.teacher_earnings.disputed_success'));
    }

    public function resolve(Request $request, $subdomain, TeacherEarning $earning): RedirectResponse
    {
        if (! $this->canManageTeacherEarnings()) {
            abort(403);
        }

        $this->authorize('update', $earning);
        $this->validateEarningBelongsToAssignedTeachers($earning);

        $request->validate([
            'resolution_notes' => 'nullable|string|max:500',
        ]);

        if (! $earning->is_disputed) {
            return back()->with('error', __('supervisor.teacher_earnings.not_disputed'));
        }

        $resolutionNote = $request->input('resolution_notes', '');
        $previousNotes = $earning->dispute_notes ?? '';

        $updatedNotes = $previousNotes;
        if ($resolutionNote) {
            $updatedNotes .= "\n\n--- ".__('supervisor.teacher_earnings.resolved_at', ['date' => now()->format('Y-m-d H:i')])." ---\n".$resolutionNote;
        }

        $earning->update([
            'is_disputed' => false,
            'is_finalized' => true,
            'dispute_notes' => mb_substr($updatedNotes, 0, 2000),
        ]);

        return back()->with('success', __('supervisor.teacher_earnings.resolved_success'));
    }

    private function validateEarningBelongsToAssignedTeachers(TeacherEarning $earning): void
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
        [$quranProfileIds, $academicProfileIds] = $this->resolveProfileIds($quranTeacherIds, $academicTeacherIds);

        $belongsToAssigned = false;
        if ($earning->teacher_type === 'quran_teacher' && in_array($earning->teacher_id, $quranProfileIds)) {
            $belongsToAssigned = true;
        }
        if ($earning->teacher_type === 'academic_teacher' && in_array($earning->teacher_id, $academicProfileIds)) {
            $belongsToAssigned = true;
        }

        abort_unless($belongsToAssigned, 403);
    }

    private function resolveProfileIds(array $quranTeacherIds, array $academicTeacherIds): array
    {
        $quranProfileIds = ! empty($quranTeacherIds)
            ? QuranTeacherProfile::whereIn('user_id', $quranTeacherIds)->pluck('id')->toArray()
            : [];
        $academicProfileIds = ! empty($academicTeacherIds)
            ? AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)->pluck('id')->toArray()
            : [];

        return [$quranProfileIds, $academicProfileIds];
    }

    /**
     * Build teacher scope query. Uses $teachersList to determine teacher type
     * for filtered queries, avoiding extra User::find() calls.
     */
    private function buildTeacherScopeQuery(array $quranProfileIds, array $academicProfileIds, array $teachersList, ?int $filterTeacherId = null): \Closure
    {
        return function ($query) use ($quranProfileIds, $academicProfileIds, $teachersList, $filterTeacherId) {
            if ($filterTeacherId) {
                $teacher = collect($teachersList)->firstWhere('id', $filterTeacherId);
                if ($teacher && $teacher['type'] === 'quran_teacher') {
                    $profileId = QuranTeacherProfile::where('user_id', $filterTeacherId)->value('id');
                    $query->where('teacher_type', 'quran_teacher')->where('teacher_id', $profileId);
                } elseif ($teacher && $teacher['type'] === 'academic_teacher') {
                    $profileId = AcademicTeacherProfile::where('user_id', $filterTeacherId)->value('id');
                    $query->where('teacher_type', 'academic_teacher')->where('teacher_id', $profileId);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $query->where(function ($q) use ($quranProfileIds, $academicProfileIds) {
                    if (! empty($quranProfileIds)) {
                        $q->orWhere(function ($sub) use ($quranProfileIds) {
                            $sub->where('teacher_type', 'quran_teacher')
                                ->whereIn('teacher_id', $quranProfileIds);
                        });
                    }
                    if (! empty($academicProfileIds)) {
                        $q->orWhere(function ($sub) use ($academicProfileIds) {
                            $sub->where('teacher_type', 'academic_teacher')
                                ->whereIn('teacher_id', $academicProfileIds);
                        });
                    }
                    if (empty($quranProfileIds) && empty($academicProfileIds)) {
                        $q->whereRaw('1 = 0');
                    }
                });
            }
        };
    }

    private function buildProfileUserMap(array $quranTeacherIds, array $academicTeacherIds): array
    {
        $map = [];
        if (! empty($quranTeacherIds)) {
            foreach (QuranTeacherProfile::whereIn('user_id', $quranTeacherIds)->with('user')->get() as $p) {
                $map['quran_teacher_'.$p->id] = $p->user;
            }
        }
        if (! empty($academicTeacherIds)) {
            foreach (AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)->with('user')->get() as $p) {
                $map['academic_teacher_'.$p->id] = $p->user;
            }
        }

        return $map;
    }

    private function buildTeachersList(array $profileUserMap): array
    {
        return collect($profileUserMap)
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'type' => $user->user_type,
            ])
            ->unique('id')
            ->values()
            ->toArray();
    }

    private function getAvailableMonths(int $academyId, \Closure $scopeQuery): array
    {
        return TeacherEarning::where('academy_id', $academyId)
            ->where($scopeQuery)
            ->selectRaw('YEAR(session_completed_at) as year, MONTH(session_completed_at) as month')
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->filter(fn ($m) => $m->year && $m->month)
            ->map(fn ($m) => [
                'value' => sprintf('%04d-%02d', $m->year, $m->month),
                'label' => Carbon::create($m->year, $m->month, 1)->locale('ar')->translatedFormat('F Y'),
            ])
            ->toArray();
    }

    private function applyMonthFilter($query, ?string $month): void
    {
        if (! $month) {
            return;
        }
        $parts = explode('-', $month);
        if (count($parts) === 2) {
            $query->forMonth((int) $parts[0], (int) $parts[1]);
        }
    }
}
