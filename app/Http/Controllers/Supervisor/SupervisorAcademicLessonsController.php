<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\AcademicSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorAcademicLessonsController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $query = AcademicSubscription::whereHas('lesson', function ($q) use ($academicTeacherProfileIds) {
            $q->whereIn('academic_teacher_id', $academicTeacherProfileIds);
        })->with(['student', 'lesson.academicTeacher.user', 'lesson.subject', 'lesson.gradeLevel']);

        if ($request->teacher_id) {
            $profileIds = \App\Models\AcademicTeacherProfile::where('user_id', $request->teacher_id)->pluck('id')->toArray();
            $query->whereHas('lesson', function ($q) use ($profileIds) {
                $q->whereIn('academic_teacher_id', $profileIds);
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->latest()->paginate(15)->withQueryString();

        $academicTeacherIds = $this->getAssignedAcademicTeacherIds();
        $teachers = User::whereIn('id', $academicTeacherIds)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'type_label' => __('supervisor.teachers.teacher_type_academic'),
        ])->toArray();

        return view('supervisor.academic-lessons.index', compact('subscriptions', 'teachers'));
    }

    public function show($subdomain, $subscriptionId): View
    {
        $academicTeacherProfileIds = $this->getAssignedAcademicTeacherProfileIds();

        $subscription = AcademicSubscription::whereHas('lesson', function ($q) use ($academicTeacherProfileIds) {
            $q->whereIn('academic_teacher_id', $academicTeacherProfileIds);
        })->with([
            'student',
            'lesson.academicTeacher.user',
            'lesson.subject',
            'lesson.gradeLevel',
            'certificate',
        ])->findOrFail($subscriptionId);

        // Load sessions
        $upcomingSessions = $subscription->lesson
            ? $subscription->lesson->sessions()->where('scheduled_at', '>', now())->orderBy('scheduled_at')->get()
            : collect();
        $pastSessions = $subscription->lesson
            ? $subscription->lesson->sessions()->where('scheduled_at', '<=', now())->orderBy('scheduled_at', 'desc')->get()
            : collect();

        $teacher = $subscription->lesson?->academicTeacher?->user;

        return view('supervisor.academic-lessons.show', compact('subscription', 'upcomingSessions', 'pastSessions', 'teacher'));
    }
}
