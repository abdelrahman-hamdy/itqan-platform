<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\InteractiveCourse;
use Illuminate\Http\Request;

class PublicInteractiveCourseController extends Controller
{
    /**
     * Display a listing of interactive courses for an academy
     */
    public function index(Request $request, $subdomain)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Get active interactive courses for this academy
        $courses = InteractiveCourse::where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('is_published', true)
            ->with(['academy'])
            ->paginate(12);

        return view('public.interactive-courses.index', compact('academy', 'courses'));
    }

    /**
     * Display the specified interactive course
     */
    public function show(Request $request, $subdomain, $courseId)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Find the course by ID within the academy
        $course = InteractiveCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('is_published', true)
            ->with(['academy'])
            ->first();

        if (! $course) {
            abort(404, 'Course not found');
        }

        return view('public.interactive-courses.show', compact('academy', 'course'));
    }
}
