<?php

namespace App\Http\Controllers;

use App\Models\AcademicPackage;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use Illuminate\Http\Request;

class PublicAcademicTeacherController extends Controller
{
    /**
     * Display a listing of Academic teachers for an academy
     */
    public function index($subdomain)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Get active and approved Academic teachers for this academy
        $teachers = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['user', 'academy', 'subjects', 'gradeLevels'])
            ->paginate(12);

        // Calculate minimum price for each teacher from their packages
        foreach ($teachers as $teacher) {
            $packageIds = $this->getTeacherPackageIds($teacher, $academy);

            if (! empty($packageIds)) {
                $packages = AcademicPackage::where('academy_id', $academy->id)
                    ->where('is_active', true)
                    ->whereIn('id', $packageIds)
                    ->get();
            } else {
                // Fallback to all packages if no defaults are set
                $packages = AcademicPackage::where('academy_id', $academy->id)
                    ->where('is_active', true)
                    ->get();
            }

            // Calculate minimum monthly price from packages
            if ($packages->count() > 0) {
                $teacher->minimum_price = $packages->min('monthly_price');
            }
        }

        return view('public.academic-teachers.index', compact('academy', 'teachers'));
    }

    /**
     * Display the specified academic teacher profile
     */
    public function show(Request $request, $subdomain, $teacher)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            abort(404, 'Academy not found');
        }

        // Get the teacher profile
        $teacher = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->where('id', $teacher)
            ->where('is_active', true)
            ->where('approval_status', 'approved')
            ->with(['user', 'subjects', 'gradeLevels'])
            ->first();

        if (! $teacher) {
            abort(404, 'Teacher not found');
        }

        // Get teacher's statistics
        $teacher->load(['user', 'subjects', 'gradeLevels']);

        // Calculate additional metrics
        $teacher->students_count = $teacher->total_students ?? 0;
        $teacher->hourly_rate = $teacher->session_price_individual;
        $teacher->bio = $teacher->bio_arabic ?? $teacher->bio_english ?? 'معلم أكاديمي مؤهل متخصص في التدريس';
        $teacher->experience_years = $teacher->teaching_experience_years;
        $teacher->qualification = $teacher->qualification_degree ?? $teacher->education_level;

        // Get available academic packages for this teacher
        // If teacher has specific packages selected, show those
        // Otherwise, show default packages from academy settings
        $packageIds = $this->getTeacherPackageIds($teacher, $academy);

        if (! empty($packageIds)) {
            $packages = AcademicPackage::where('academy_id', $academy->id)
                ->where('is_active', true)
                ->whereIn('id', $packageIds)
                ->orderBy('sort_order')
                ->orderBy('monthly_price')
                ->get();
        } else {
            // Fallback to all packages if no defaults are set
            $packages = AcademicPackage::where('academy_id', $academy->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('monthly_price')
                ->get();
        }

        // Get teacher's interactive courses
        $interactiveCourses = \App\Models\InteractiveCourse::where('academy_id', $academy->id)
            ->where('assigned_teacher_id', $teacher->id)
            ->where('status', 'published')
            ->with(['subject', 'gradeLevel'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate teacher statistics
        $stats = [
            'total_students' => $teacher->total_students ?? 0,
            'total_sessions' => $teacher->total_sessions ?? 0,
            'experience_years' => $teacher->teaching_experience_years ?? 0,
            'rating' => $teacher->rating ?? 0,
        ];

        return view('public.academic-teachers.show', compact('academy', 'teacher', 'packages', 'stats', 'interactiveCourses'));
    }

    /**
     * Get package IDs for a teacher - either their selected packages or academy defaults
     */
    private function getTeacherPackageIds($teacher, $academy): array
    {
        // First, check if teacher has specific packages assigned
        if (! empty($teacher->package_ids)) {
            $teacherPackageIds = $teacher->package_ids;

            // Ensure it's an array
            if (is_string($teacherPackageIds)) {
                $teacherPackageIds = json_decode($teacherPackageIds, true) ?: [];
            }

            if (is_array($teacherPackageIds) && ! empty($teacherPackageIds)) {
                return $teacherPackageIds;
            }
        }

        // If teacher has no packages assigned, check academy default packages
        $academySettings = \App\Models\AcademicSettings::where('academy_id', $academy->id)->first();

        if ($academySettings && ! empty($academySettings->default_package_ids)) {
            $defaultPackageIds = $academySettings->default_package_ids;

            // Ensure it's an array
            if (is_string($defaultPackageIds)) {
                $defaultPackageIds = json_decode($defaultPackageIds, true) ?: [];
            }

            if (is_array($defaultPackageIds) && ! empty($defaultPackageIds)) {
                return $defaultPackageIds;
            }
        }

        // If no teacher packages and no default packages, return empty array
        // The controller will then show all packages as fallback
        return [];
    }
}
