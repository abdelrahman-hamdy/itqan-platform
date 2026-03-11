<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorTrialSessionsController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();

        // Trial requests reference teacher_id as QuranTeacherProfile.id
        $profileIds = QuranTeacherProfile::whereIn('user_id', $quranTeacherIds)->pluck('id')->toArray();

        $baseQuery = QuranTrialRequest::whereIn('teacher_id', $profileIds);

        if ($request->teacher_id) {
            $filterProfileIds = QuranTeacherProfile::where('user_id', $request->teacher_id)->pluck('id')->toArray();
            $baseQuery->whereIn('teacher_id', $filterProfileIds);
        }

        // Stats from DB (before search/status/date filters)
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'scheduled' => (clone $baseQuery)->where('status', 'scheduled')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
        ];

        // Apply filters
        $query = clone $baseQuery;
        $query->with(['student', 'academy', 'trialSession.meeting', 'teacher.user']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhere('student_name', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $trialRequests = $query->latest()->paginate(15)->withQueryString();

        $teachers = User::whereIn('id', $quranTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'type_label' => __('supervisor.teachers.teacher_type_quran'),
        ])->toArray();

        return view('supervisor.trial-sessions.index', compact('trialRequests', 'teachers', 'stats'));
    }

    public function show($subdomain, $trialRequestId): View
    {
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();
        $profileIds = QuranTeacherProfile::whereIn('user_id', $quranTeacherIds)->pluck('id')->toArray();

        $trialRequest = QuranTrialRequest::whereIn('teacher_id', $profileIds)
            ->with(['student.studentProfile', 'trialSession.meeting', 'trialSession.attendances', 'academy'])
            ->findOrFail($trialRequestId);

        $teacherProfile = QuranTeacherProfile::find($trialRequest->teacher_id);
        $teacher = $teacherProfile?->user;

        return view('supervisor.trial-sessions.show', compact('trialRequest', 'teacher'));
    }
}
