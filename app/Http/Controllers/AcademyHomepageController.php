<?php

namespace App\Http\Controllers;

use App\Models\AcademicTeacher;
use App\Models\Academy;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use Illuminate\Http\Request;

class AcademyHomepageController extends Controller
{
    public function show(Request $request)
    {
        // Get the current academy from the request (set by middleware)
        $academy = $request->academy ?? Academy::first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Get Quran circles for this academy
        $quranCircles = QuranCircle::where('academy_id', $academy->id)
            ->where(function ($query) {
                $query->where('status', true)
                    ->orWhereIn('status', ['active', 'ongoing']);
            })
            ->where('enrollment_status', 'open')
            ->with(['quranTeacher'])
            ->withCount('students')
            ->take(4)
            ->get();

        // Get Quran teachers for this academy
        $quranTeachers = QuranTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['user'])
            ->take(4)
            ->get();

        // Get interactive courses for this academy
        $interactiveCourses = InteractiveCourse::where('academy_id', $academy->id)
            ->where('status', 'available')
            ->take(4)
            ->get();

        // Get academic teachers for this academy
        $academicTeachers = AcademicTeacher::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->take(4)
            ->get();

        // Get recorded courses for this academy
        $recordedCourses = RecordedCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->take(3)
            ->get();

        return view('academy.homepage', compact(
            'academy',
            'quranCircles',
            'quranTeachers',
            'interactiveCourses',
            'academicTeachers',
            'recordedCourses'
        ));
    }
}
