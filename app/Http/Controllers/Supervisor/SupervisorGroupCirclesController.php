<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\CircleEnrollmentStatus;
use App\Enums\DifficultyLevel;
use App\Enums\WeekDays;
use App\Models\QuranCircle;
use App\Models\SponsoredEnrollmentRequest;
use App\Models\User;
use App\Services\Circle\CircleFreeEnrollmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupervisorGroupCirclesController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        $baseQuery = $this->scopedCircleQuery();

        if ($request->teacher_id) {
            $baseQuery->where('quran_teacher_id', $request->teacher_id);
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', true)->count(),
            'full' => (clone $baseQuery)->where('enrollment_status', CircleEnrollmentStatus::FULL)->count(),
            'totalStudents' => (int) (clone $baseQuery)->sum('enrolled_students'),
        ];

        $query = clone $baseQuery;
        $query->with(['quranTeacher', 'schedule']);

        if ($request->status) {
            match ($request->status) {
                'active' => $query->where('status', true),
                'inactive' => $query->where('status', false),
                'full' => $query->where('enrollment_status', CircleEnrollmentStatus::FULL),
                'open' => $query->where('enrollment_status', CircleEnrollmentStatus::OPEN),
                default => null,
            };
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $circles = $query->latest()->paginate(15)->withQueryString();

        $teachers = $this->getTeachersForFilter('quran');

        return view('supervisor.group-circles.index', compact('circles', 'teachers', 'stats'));
    }

    public function show($subdomain, $circleId): View
    {
        $isAdmin = $this->isAdminUser();
        $quranTeacherIds = $this->getAssignedQuranTeacherIds();

        $eagerLoad = ['quranTeacher', 'students', 'sessions' => fn ($q) => $q->orderBy('scheduled_at', 'desc'), 'schedule'];

        $circle = $this->scopedCircleQuery()
            ->with($eagerLoad)
            ->findOrFail($circleId);

        $teacher = $circle->quranTeacher;

        $quranTeachers = User::whereIn('id', $quranTeacherIds)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $sponsoredRequests = SponsoredEnrollmentRequest::where('circle_id', $circle->id)
            ->with(['student', 'reviewer'])
            ->latest()
            ->get();
        $pendingSponsoredCount = $sponsoredRequests->where('status', SponsoredEnrollmentRequest::STATUS_PENDING)->count();

        return view('supervisor.group-circles.show', compact(
            'circle',
            'teacher',
            'isAdmin',
            'quranTeachers',
            'sponsoredRequests',
            'pendingSponsoredCount'
        ));
    }

    public function update(Request $request, $subdomain, $circleId): RedirectResponse
    {
        $circle = $this->scopedCircleQuery()->findOrFail($circleId);

        $weekDayValues = WeekDays::values();
        $difficultyValues = DifficultyLevel::values();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'age_group' => 'required|in:children,youth,adults,all_ages',
            'gender_type' => 'required|in:male,female,mixed',
            'specialization' => 'required|in:memorization,recitation,interpretation,arabic_language,complete',
            'memorization_level' => ['required', Rule::in($difficultyValues)],
            'description' => 'nullable|string|max:500',
            'max_students' => 'required|integer|min:1|max:20',
            'monthly_fee' => 'required|integer|min:0',
            'monthly_sessions_count' => 'required|in:4,8,12,16,20',
            'schedule_days' => 'nullable|array',
            'schedule_days.*' => Rule::in($weekDayValues),
            'schedule_time' => 'nullable|string',
            'recording_enabled' => 'required|in:0,1',
            'show_recording_to_teacher' => 'required|in:0,1',
            'show_recording_to_student' => 'required|in:0,1',
            'allow_sponsored_requests' => 'required|in:0,1',
            'is_enrolled_only' => 'required|in:0,1',
            'supervisor_notes' => 'nullable|string|max:2000',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $validated['recording_enabled'] = (bool) $validated['recording_enabled'];
        $validated['show_recording_to_teacher'] = (bool) $validated['show_recording_to_teacher'];
        $validated['show_recording_to_student'] = (bool) $validated['show_recording_to_student'];
        $validated['allow_sponsored_requests'] = (bool) $validated['allow_sponsored_requests'];
        $validated['is_enrolled_only'] = (bool) $validated['is_enrolled_only'];

        if ($this->isAdminUser()) {
            unset($validated['supervisor_notes']);
        } else {
            unset($validated['admin_notes']);
        }

        $circle->update($validated);

        return redirect()->back()->with('success', __('supervisor.common.updated_successfully'));
    }

    public function toggleStatus($subdomain, $circleId): RedirectResponse
    {
        $circle = $this->scopedCircleQuery()->findOrFail($circleId);

        $circle->update(['status' => ! $circle->status]);

        return redirect()->back()->with('success', __('supervisor.group_circles.status_updated'));
    }

    public function toggleEnrollment($subdomain, $circleId): RedirectResponse
    {
        $circle = $this->scopedCircleQuery()->findOrFail($circleId);

        $newStatus = $circle->enrollment_status === CircleEnrollmentStatus::OPEN
            ? CircleEnrollmentStatus::CLOSED
            : CircleEnrollmentStatus::OPEN;

        $circle->update(['enrollment_status' => $newStatus]);

        return redirect()->back()->with('success', __('supervisor.group_circles.enrollment_status_updated'));
    }

    public function changeTeacher(Request $request, $subdomain, $circleId): RedirectResponse
    {
        $circle = $this->scopedCircleQuery()->findOrFail($circleId);

        $request->validate([
            'quran_teacher_id' => ['required', Rule::in($this->getAssignedQuranTeacherIds())],
        ]);

        $circle->update(['quran_teacher_id' => $request->quran_teacher_id]);

        return redirect()->back()->with('success', __('supervisor.group_circles.teacher_changed'));
    }

    public function destroy($subdomain, $circleId): RedirectResponse
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $circle = $this->scopedCircleQuery()->findOrFail($circleId);
        $circle->delete();

        return redirect()->route('manage.group-circles.index', ['subdomain' => $subdomain])
            ->with('success', __('supervisor.group_circles.circle_deleted'));
    }

    public function approveSponsoredRequest($subdomain, $circleId, $sponsoredRequestId): RedirectResponse
    {
        $circle = $this->scopedCircleQuery()->findOrFail($circleId);

        $sponsoredRequest = SponsoredEnrollmentRequest::where('circle_id', $circle->id)
            ->where('id', $sponsoredRequestId)
            ->where('status', SponsoredEnrollmentRequest::STATUS_PENDING)
            ->firstOrFail();

        $student = User::findOrFail($sponsoredRequest->student_id);
        $academy = $circle->academy;

        $sponsoredRequest->update([
            'status' => SponsoredEnrollmentRequest::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $result = app(CircleFreeEnrollmentService::class)->enrollImmediately($student, $circle, $academy, createSubscription: true);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['error'] ?? __('supervisor.common.error_occurred'));
        }

        return redirect()->back()->with('success', __('supervisor.group_circles.request_approved'));
    }

    public function rejectSponsoredRequest(Request $request, $subdomain, $circleId, $sponsoredRequestId): RedirectResponse
    {
        $circle = $this->scopedCircleQuery()->findOrFail($circleId);

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $sponsoredRequest = SponsoredEnrollmentRequest::where('circle_id', $circle->id)
            ->where('id', $sponsoredRequestId)
            ->where('status', SponsoredEnrollmentRequest::STATUS_PENDING)
            ->firstOrFail();

        $sponsoredRequest->update([
            'status' => SponsoredEnrollmentRequest::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->back()->with('success', __('supervisor.group_circles.request_rejected'));
    }

    /**
     * Scoped query: admins see all circles, supervisors see only their assigned teachers' circles.
     */
    private function scopedCircleQuery(): Builder
    {
        $query = QuranCircle::query();

        if (! $this->isAdminUser()) {
            $query->whereIn('quran_teacher_id', $this->getAssignedQuranTeacherIds());
        }

        return $query;
    }

    private function getTeachersForFilter(string $type): array
    {
        $ids = $type === 'quran' ? $this->getAssignedQuranTeacherIds() : $this->getAssignedAcademicTeacherIds();

        return User::whereIn('id', $ids)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'type_label' => $type === 'quran' ? __('supervisor.teachers.teacher_type_quran') : __('supervisor.teachers.teacher_type_academic'),
        ])->toArray();
    }
}
