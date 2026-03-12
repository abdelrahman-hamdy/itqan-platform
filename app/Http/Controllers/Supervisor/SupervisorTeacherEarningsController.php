<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorTeacherEarningsController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $academyId = $this->getAcademyId();
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
        $allTeacherIds = array_merge($quranTeacherIds, $academicTeacherIds);

        // Build the teacher list for the filter dropdown
        $teachersList = [];
        if (! empty($allTeacherIds)) {
            $teachersList = User::whereIn('id', $allTeacherIds)
                ->select('id', 'first_name', 'last_name', 'user_type')
                ->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'type' => $u->user_type,
                ])
                ->toArray();
        }

        // Resolve profile IDs for the earnings query (TeacherEarning uses morph: profile type + profile ID)
        $quranProfileIds = ! empty($quranTeacherIds)
            ? QuranTeacherProfile::whereIn('user_id', $quranTeacherIds)->pluck('id')->toArray()
            : [];
        $academicProfileIds = ! empty($academicTeacherIds)
            ? AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)->pluck('id')->toArray()
            : [];

        // If a specific teacher_id filter is set, validate it belongs to assigned set
        $currentTeacherId = $request->input('teacher_id');
        if ($currentTeacherId && ! in_array((int) $currentTeacherId, $allTeacherIds)) {
            $currentTeacherId = null;
        }

        // Build scope conditions for assigned teachers
        $scopeQuery = function ($query) use ($quranProfileIds, $academicProfileIds, $currentTeacherId) {
            if ($currentTeacherId) {
                // Filter to a specific teacher
                $user = User::find($currentTeacherId);
                if ($user && $user->user_type === 'quran_teacher') {
                    $profileId = QuranTeacherProfile::where('user_id', $currentTeacherId)->value('id');
                    $query->where('teacher_type', 'quran_teacher')->where('teacher_id', $profileId);
                } elseif ($user && $user->user_type === 'academic_teacher') {
                    $profileId = AcademicTeacherProfile::where('user_id', $currentTeacherId)->value('id');
                    $query->where('teacher_type', 'academic_teacher')->where('teacher_id', $profileId);
                } else {
                    $query->whereRaw('1 = 0'); // No results
                }
            } else {
                // All assigned teachers
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

        // Parse month filter
        $currentMonth = $request->input('month');
        $currentStatus = $request->input('status', 'all');

        // Stats query (all assigned teachers, current month or all time)
        $statsBase = TeacherEarning::where('academy_id', $academyId)->where($scopeQuery);

        $now = Carbon::now();
        $totalEarningsThisMonth = (clone $statsBase)->forMonth($now->year, $now->month)->sum('amount');
        $totalEarningsAllTime = (clone $statsBase)->sum('amount');
        $finalizedAmount = (clone $statsBase)->finalized()->sum('amount');
        $disputedAmount = (clone $statsBase)->disputed()->sum('amount');
        $sessionsCount = (clone $statsBase)->count();

        $stats = [
            'totalEarningsThisMonth' => $totalEarningsThisMonth,
            'totalEarningsAllTime' => $totalEarningsAllTime,
            'finalizedAmount' => $finalizedAmount,
            'disputedAmount' => $disputedAmount,
            'sessionsCount' => $sessionsCount,
        ];

        // Filtered earnings list
        $earningsQuery = TeacherEarning::where('academy_id', $academyId)
            ->where($scopeQuery)
            ->with([
                'teacher',
                'session' => function ($morphTo) {
                    $morphTo->morphWith([
                        \App\Models\QuranSession::class => ['individualCircle', 'circle'],
                        \App\Models\AcademicSession::class => ['academicIndividualLesson'],
                        \App\Models\InteractiveCourseSession::class => ['course'],
                    ]);
                },
            ]);

        if ($currentMonth) {
            $parts = explode('-', $currentMonth);
            if (count($parts) === 2) {
                $earningsQuery->forMonth((int) $parts[0], (int) $parts[1]);
            }
        }

        if ($currentStatus === 'finalized') {
            $earningsQuery->finalized();
        } elseif ($currentStatus === 'pending') {
            $earningsQuery->unpaid();
        } elseif ($currentStatus === 'disputed') {
            $earningsQuery->disputed();
        }

        $earnings = $earningsQuery->orderByDesc('session_completed_at')->paginate(15);

        // Build a map of teacher profile ID → user for name resolution
        $profileUserMap = [];
        if (! empty($quranTeacherIds)) {
            $quranProfiles = QuranTeacherProfile::whereIn('user_id', $quranTeacherIds)->with('user')->get();
            foreach ($quranProfiles as $p) {
                $profileUserMap['quran_teacher_'.$p->id] = $p->user;
            }
        }
        if (! empty($academicTeacherIds)) {
            $academicProfiles = AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)->with('user')->get();
            foreach ($academicProfiles as $p) {
                $profileUserMap['academic_teacher_'.$p->id] = $p->user;
            }
        }

        // Available months for dropdown
        $availableMonths = TeacherEarning::where('academy_id', $academyId)
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

        return view('supervisor.teacher-earnings.index', [
            'earnings' => $earnings,
            'stats' => $stats,
            'availableMonths' => $availableMonths,
            'teachers' => $teachersList,
            'profileUserMap' => $profileUserMap,
            'currentTeacherId' => $currentTeacherId,
            'currentMonth' => $currentMonth,
            'currentStatus' => $currentStatus,
        ]);
    }

    public function dispute(Request $request, $subdomain, TeacherEarning $earning): RedirectResponse
    {
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

        $quranProfileIds = ! empty($quranTeacherIds)
            ? QuranTeacherProfile::whereIn('user_id', $quranTeacherIds)->pluck('id')->toArray()
            : [];
        $academicProfileIds = ! empty($academicTeacherIds)
            ? AcademicTeacherProfile::whereIn('user_id', $academicTeacherIds)->pluck('id')->toArray()
            : [];

        $belongsToAssigned = false;
        if ($earning->teacher_type === 'quran_teacher' && in_array($earning->teacher_id, $quranProfileIds)) {
            $belongsToAssigned = true;
        }
        if ($earning->teacher_type === 'academic_teacher' && in_array($earning->teacher_id, $academicProfileIds)) {
            $belongsToAssigned = true;
        }

        abort_unless($belongsToAssigned, 403);
    }
}
