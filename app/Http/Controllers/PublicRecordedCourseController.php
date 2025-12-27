<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\RecordedCourse;
use Illuminate\Http\Request;
use App\Enums\SessionStatus;

class PublicRecordedCourseController extends Controller
{
    /**
     * Display a listing of recorded courses for an academy
     */
    public function index(Request $request, $subdomain)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Get published recorded courses for this academy
        $courses = RecordedCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->with(['academy'])
            ->paginate(12);

        return view('public.recorded-courses.index', compact('academy', 'courses'));
    }

    /**
     * Display the specified recorded course
     */
    public function show(Request $request, $subdomain, $courseId)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Find the course by ID within the academy
        $course = RecordedCourse::where('id', $courseId)
            ->where('academy_id', $academy->id)
            ->where('is_published', true)
            ->with(['academy', 'lessons'])
            ->first();

        if (! $course) {
            abort(404, 'Course not found');
        }

        return view('public.recorded-courses.show', compact('academy', 'course'));
    }
}
