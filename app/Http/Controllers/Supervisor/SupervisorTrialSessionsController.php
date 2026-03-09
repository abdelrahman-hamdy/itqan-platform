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

        $query = QuranTrialRequest::whereIn('teacher_id', $profileIds)
            ->with(['student', 'academy', 'trialSession.meeting']);

        if ($request->teacher_id) {
            $filterProfileIds = QuranTeacherProfile::where('user_id', $request->teacher_id)->pluck('id')->toArray();
            $query->whereIn('teacher_id', $filterProfileIds);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $trialRequests = $query->latest()->paginate(15)->withQueryString();

        $teachers = User::whereIn('id', $quranTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'type_label' => __('supervisor.teachers.teacher_type_quran'),
        ])->toArray();

        return view('supervisor.trial-sessions.index', compact('trialRequests', 'teachers'));
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
