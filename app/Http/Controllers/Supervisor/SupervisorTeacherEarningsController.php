<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Services\TeacherEarningsExportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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

        [$quranProfileIds, $academicProfileIds, $profileUserMap] = $this->resolveProfilesAndMap($quranTeacherIds, $academicTeacherIds);
        $teachersList = $this->buildTeachersList($profileUserMap);

        $currentTeacherIds = $this->parseTeacherIdsFromRequest($request, $allTeacherIds);

        $scopeQuery = $this->buildTeacherScopeQuery($quranProfileIds, $academicProfileIds, $teachersList, $currentTeacherIds);

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $currentMonth = $request->input('month');
        $currentStatus = $request->input('status', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

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

        $this->applyDateFilters($earningsQuery, $currentMonth, $startDate, $endDate);

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
            'currentTeacherIds' => $currentTeacherIds,
            'currentMonth' => $currentMonth,
            'currentStatus' => $currentStatus,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'activeTab' => 'details',
        ]);
    }

    public function teacherSummary(Request $request, $subdomain = null): View
    {
        if (! $this->canManageTeacherEarnings()) {
            abort(403);
        }

        $data = $this->buildTeacherSummaryData($request);

        return view('supervisor.teacher-earnings.teacher-summary', [
            'teacherSummaries' => $data['teacherSummaries'],
            'profileUserMap' => $data['profileUserMap'],
            'availableMonths' => $data['availableMonths'],
            'teachers' => $data['teachersList'],
            'currentTeacherIds' => $data['currentTeacherIds'],
            'currentMonth' => $data['currentMonth'],
            'currentTeacherType' => $data['currentTeacherType'],
            'currentGender' => $data['currentGender'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
            'activeTab' => 'summary',
        ]);
    }

    public function export(Request $request, $subdomain = null): Response
    {
        if (! $this->canManageTeacherEarnings()) {
            abort(403);
        }

        $request->validate([
            'format' => 'nullable|in:pdf,excel',
        ]);

        $format = $request->input('format', 'pdf');
        $data = $this->buildTeacherSummaryData($request);

        if (empty($data['teacherSummaries'])) {
            return back()->with('error', __('supervisor.teacher_earnings.export_no_data'));
        }

        $academy = auth()->user()->academy;
        $periodLabel = $this->buildPeriodLabel($data['currentMonth'], $data['startDate'], $data['endDate']);

        $meta = [
            'academy_name' => $academy->name ?? '',
            'currency_symbol' => getTeacherEarningsCurrencySymbol(),
            'period_label' => $periodLabel,
            'generated_at' => nowInAcademyTimezone()->format('Y-m-d H:i'),
        ];

        try {
            $service = app(TeacherEarningsExportService::class);

            if ($format === 'excel') {
                return $service->generateSummaryExcel($data['teacherSummaries'], $data['profileUserMap'], $meta);
            }

            $pdfContent = $service->generateSummaryPdf($data['teacherSummaries'], $data['profileUserMap'], $meta);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('supervisor.teacher_earnings.export_no_data'));
        }

        $filename = 'teacher-earnings-'.nowInAcademyTimezone()->format('Y-m-d').'.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->header('Content-Length', strlen($pdfContent));
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

    private function resolveProfileIds(array $quranTeacherIds, array $academicTeacherIds, ?string $gender = null): array
    {
        $quranProfileIds = ! empty($quranTeacherIds)
            ? QuranTeacherProfile::whereIn('user_id', $quranTeacherIds)
                ->when($gender, fn ($q) => $q->where('gender', $gender))
                ->pluck('id')->toArray()
            : [];
        $academicProfileIds = ! empty($academicTeacherIds)
            ? AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)
                ->when($gender, fn ($q) => $q->where('gender', $gender))
                ->pluck('id')->toArray()
            : [];

        return [$quranProfileIds, $academicProfileIds];
    }

    /**
     * Build teacher scope query. Uses $teachersList to determine teacher type
     * for filtered queries, avoiding extra User::find() calls.
     */
    private function buildTeacherScopeQuery(array $quranProfileIds, array $academicProfileIds, array $teachersList, array $filterTeacherIds = []): \Closure
    {
        $teachersById = collect($teachersList)->keyBy('id');

        return function ($query) use ($quranProfileIds, $academicProfileIds, $teachersById, $filterTeacherIds) {
            if (! empty($filterTeacherIds)) {
                $quranUserIds = [];
                $academicUserIds = [];

                foreach ($filterTeacherIds as $userId) {
                    $teacher = $teachersById->get($userId);
                    if (! $teacher) {
                        continue;
                    }
                    if ($teacher['type'] === 'quran_teacher') {
                        $quranUserIds[] = $userId;
                    } elseif ($teacher['type'] === 'academic_teacher') {
                        $academicUserIds[] = $userId;
                    }
                }

                $filteredQuranProfileIds = ! empty($quranUserIds)
                    ? QuranTeacherProfile::whereIn('user_id', $quranUserIds)->pluck('id')->toArray()
                    : [];
                $filteredAcademicProfileIds = ! empty($academicUserIds)
                    ? AcademicTeacherProfile::whereIn('user_id', $academicUserIds)->pluck('id')->toArray()
                    : [];

                if (empty($filteredQuranProfileIds) && empty($filteredAcademicProfileIds)) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function ($q) use ($filteredQuranProfileIds, $filteredAcademicProfileIds) {
                    if (! empty($filteredQuranProfileIds)) {
                        $q->orWhere(function ($sub) use ($filteredQuranProfileIds) {
                            $sub->where('teacher_type', 'quran_teacher')
                                ->whereIn('teacher_id', $filteredQuranProfileIds);
                        });
                    }
                    if (! empty($filteredAcademicProfileIds)) {
                        $q->orWhere(function ($sub) use ($filteredAcademicProfileIds) {
                            $sub->where('teacher_type', 'academic_teacher')
                                ->whereIn('teacher_id', $filteredAcademicProfileIds);
                        });
                    }
                });
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

    private function parseTeacherIdsFromRequest(Request $request, array $allowedIds): array
    {
        return collect($request->input('teacher_ids', []))
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($id) => in_array($id, $allowedIds))
            ->values()
            ->toArray();
    }

    /**
     * Single query per profile table: resolves profile IDs and builds user map together.
     * Applies gender filter to both, so the teacher dropdown matches the table data.
     */
    private function resolveProfilesAndMap(array $quranTeacherIds, array $academicTeacherIds, ?string $gender = null): array
    {
        $quranProfileIds = [];
        $academicProfileIds = [];
        $map = [];

        if (! empty($quranTeacherIds)) {
            $profiles = QuranTeacherProfile::whereIn('user_id', $quranTeacherIds)
                ->when($gender, fn ($q) => $q->where('gender', $gender))
                ->with('user')
                ->get();
            $quranProfileIds = $profiles->pluck('id')->toArray();
            foreach ($profiles as $p) {
                $map['quran_teacher_'.$p->id] = $p->user;
            }
        }

        if (! empty($academicTeacherIds)) {
            $profiles = AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)
                ->when($gender, fn ($q) => $q->where('gender', $gender))
                ->with('user')
                ->get();
            $academicProfileIds = $profiles->pluck('id')->toArray();
            foreach ($profiles as $p) {
                $map['academic_teacher_'.$p->id] = $p->user;
            }
        }

        return [$quranProfileIds, $academicProfileIds, $map];
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

    /**
     * Build teacher summary data used by both teacherSummary() and export().
     */
    private function buildTeacherSummaryData(Request $request): array
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'teacher_type' => 'nullable|in:quran,academic',
            'gender' => 'nullable|in:male,female',
        ]);

        $academyId = $this->getAcademyId();
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();

        $currentTeacherType = $request->input('teacher_type');
        $currentGender = $request->input('gender');

        if ($currentTeacherType === 'quran') {
            $academicTeacherIds = [];
        } elseif ($currentTeacherType === 'academic') {
            $quranTeacherIds = [];
        }

        $allTeacherIds = array_merge($quranTeacherIds, $academicTeacherIds);

        // Single query per profile table: resolve IDs and build user map together
        [$quranProfileIds, $academicProfileIds, $profileUserMap] = $this->resolveProfilesAndMap($quranTeacherIds, $academicTeacherIds, $currentGender);
        $teachersList = $this->buildTeachersList($profileUserMap);

        $currentTeacherIds = $this->parseTeacherIdsFromRequest($request, $allTeacherIds);

        $scopeQuery = $this->buildTeacherScopeQuery($quranProfileIds, $academicProfileIds, $teachersList, $currentTeacherIds);

        $currentMonth = $request->input('month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = TeacherEarning::where('academy_id', $academyId)->where($scopeQuery);
        $this->applyDateFilters($query, $currentMonth, $startDate, $endDate);

        $allEarnings = (clone $query)->get();

        $teacherSummaries = [];
        foreach ($allEarnings as $earning) {
            $key = $earning->teacher_type.'_'.$earning->teacher_id;

            if (! isset($teacherSummaries[$key])) {
                $teacherSummaries[$key] = [
                    'teacher_type' => $earning->teacher_type,
                    'teacher_id' => $earning->teacher_id,
                    'quran_individual' => ['amount' => 0, 'details' => []],
                    'quran_group' => ['amount' => 0, 'details' => []],
                    'academic' => ['amount' => 0, 'details' => []],
                    'interactive' => ['amount' => 0, 'details' => []],
                    'total' => 0,
                    'sessions_count' => 0,
                    'total_duration_minutes' => 0,
                ];
            }

            $amount = (float) $earning->amount;
            $meta = $earning->calculation_metadata ?? [];
            $durationMinutes = $meta['duration_minutes'] ?? 60;

            $teacherSummaries[$key]['total'] += $amount;
            $teacherSummaries[$key]['sessions_count']++;
            $teacherSummaries[$key]['total_duration_minutes'] += $durationMinutes;

            if ($earning->session_type === QuranSession::class) {
                $isGroup = in_array($earning->calculation_method, ['group_rate', 'per_student']);
                $source = $isGroup ? 'quran_group' : 'quran_individual';
            } elseif ($earning->session_type === AcademicSession::class) {
                $source = 'academic';
            } elseif ($earning->session_type === InteractiveCourseSession::class) {
                $source = 'interactive';
            } else {
                continue;
            }

            $teacherSummaries[$key][$source]['amount'] += $amount;

            $detailKey = $durationMinutes.'_'.$amount;
            if (! isset($teacherSummaries[$key][$source]['details'][$detailKey])) {
                $teacherSummaries[$key][$source]['details'][$detailKey] = [
                    'duration_minutes' => $durationMinutes,
                    'rate_per_session' => $amount,
                    'sessions_count' => 0,
                    'amount' => 0,
                ];
            }
            $teacherSummaries[$key][$source]['details'][$detailKey]['sessions_count']++;
            $teacherSummaries[$key][$source]['details'][$detailKey]['amount'] += $amount;
        }

        foreach ($teacherSummaries as &$summary) {
            foreach (['quran_individual', 'quran_group', 'academic', 'interactive'] as $source) {
                $summary[$source]['details'] = array_values($summary[$source]['details']);
            }
        }
        unset($summary);

        usort($teacherSummaries, fn ($a, $b) => $b['total'] <=> $a['total']);

        return [
            'teacherSummaries' => $teacherSummaries,
            'profileUserMap' => $profileUserMap,
            'teachersList' => $teachersList,
            'availableMonths' => $this->getAvailableMonths($academyId, $scopeQuery),
            'currentTeacherIds' => $currentTeacherIds,
            'currentMonth' => $currentMonth,
            'currentTeacherType' => $currentTeacherType,
            'currentGender' => $currentGender,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    private function buildPeriodLabel(?string $month, ?string $startDate, ?string $endDate): string
    {
        if ($startDate || $endDate) {
            $parts = [];
            if ($startDate) {
                $parts[] = Carbon::parse($startDate)->format('Y-m-d');
            }
            if ($endDate) {
                $parts[] = Carbon::parse($endDate)->format('Y-m-d');
            }

            return implode(' - ', $parts);
        }

        if ($month) {
            $parts = explode('-', $month);
            if (count($parts) === 2) {
                return Carbon::create((int) $parts[0], (int) $parts[1], 1)->locale('ar')->translatedFormat('F Y');
            }
        }

        return __('supervisor.teacher_earnings.export_all_periods');
    }

    /**
     * Apply date filters. Date range takes priority over month filter.
     */
    private function applyDateFilters($query, ?string $month, ?string $startDate = null, ?string $endDate = null): void
    {
        if ($startDate || $endDate) {
            if ($startDate) {
                $query->where('session_completed_at', '>=', Carbon::parse($startDate)->startOfDay());
            }
            if ($endDate) {
                $query->where('session_completed_at', '<=', Carbon::parse($endDate)->endOfDay());
            }

            return;
        }

        if (! $month) {
            return;
        }
        $parts = explode('-', $month);
        if (count($parts) === 2) {
            $query->forMonth((int) $parts[0], (int) $parts[1]);
        }
    }
}
