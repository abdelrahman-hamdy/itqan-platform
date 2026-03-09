<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\RecordingStatus;
use App\Http\Controllers\Controller;
use App\Models\InteractiveCourse;
use App\Models\SessionRecording;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RecordingListController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of session recordings for the authenticated academic teacher.
     */
    public function index(Request $request, $subdomain = null): View
    {
        $user = Auth::user();
        $profileId = $user->academicTeacherProfile?->id;

        if (! $profileId) {
            abort(403);
        }

        // Get teacher's course IDs for scoping
        $teacherCourseIds = InteractiveCourse::where('assigned_teacher_id', $profileId)
            ->pluck('id');

        $query = SessionRecording::query()
            ->where('recordable_type', \App\Models\InteractiveCourseSession::class)
            ->whereHas('recordable', function ($q) use ($teacherCourseIds) {
                $q->whereIn('course_id', $teacherCourseIds);
            })
            ->with(['recordable.course:id,title'])
            ->where('status', RecordingStatus::COMPLETED);

        // Filter by course
        if ($request->filled('course_id')) {
            $query->whereHas('recordable', fn ($q) => $q->where('course_id', $request->course_id));
        }

        $recordings = $query->latest('started_at')->paginate(15)->withQueryString();

        // Get teacher's courses for filter dropdown
        $courses = InteractiveCourse::where('assigned_teacher_id', $profileId)
            ->pluck('title', 'id');

        // Stats
        $totalRecordings = SessionRecording::where('recordable_type', \App\Models\InteractiveCourseSession::class)
            ->whereHas('recordable', fn ($q) => $q->whereIn('course_id', $teacherCourseIds))
            ->where('status', RecordingStatus::COMPLETED)
            ->count();

        return view('teacher.recordings.index', compact(
            'recordings',
            'courses',
            'totalRecordings',
        ));
    }
}
