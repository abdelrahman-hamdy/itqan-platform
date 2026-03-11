<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\RecordedCourse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorRecordedCoursesController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $query = RecordedCourse::with(['instructor'])
            ->withCount(['sections', 'lessons', 'enrollments']);

        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('instructor_id')) {
            $query->where('created_by', $request->instructor_id);
        }

        $courses = $query->latest()->paginate(15)->withQueryString();

        $totalCourses = RecordedCourse::count();
        $publishedCount = RecordedCourse::where('is_published', true)->count();
        $draftCount = RecordedCourse::where('is_published', false)->count();
        $totalEnrollments = RecordedCourse::withCount('enrollments')->get()->sum('enrollments_count');

        $instructors = RecordedCourse::whereNotNull('created_by')
            ->with('instructor:id,name')
            ->get()
            ->pluck('instructor')
            ->filter()
            ->unique('id')
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->values()
            ->toArray();

        return view('supervisor.recorded-courses.index', compact(
            'courses',
            'totalCourses',
            'publishedCount',
            'draftCount',
            'totalEnrollments',
            'instructors',
        ));
    }

    public function show(Request $request, $subdomain = null, $course = null): View
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $course = RecordedCourse::with([
            'instructor',
            'sections.lessons',
            'enrollments.student',
        ])->findOrFail($course);

        return view('supervisor.recorded-courses.show', compact('course'));
    }

    public function togglePublish(Request $request, $subdomain = null, $course = null)
    {
        if (! $this->isAdminUser()) {
            abort(403);
        }

        $course = RecordedCourse::findOrFail($course);
        $course->update(['is_published' => ! $course->is_published]);

        $message = $course->is_published
            ? __('supervisor.recorded_courses.published_success')
            : __('supervisor.recorded_courses.unpublished_success');

        return redirect()->back()->with('success', $message);
    }
}
