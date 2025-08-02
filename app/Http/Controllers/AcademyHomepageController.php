<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranTeacher;
use App\Models\RecordedCourse;
use App\Models\InteractiveCourse;
use App\Models\AcademicTeacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademyHomepageController extends Controller
{
    /**
     * Display the academy homepage
     */
    public function index(Request $request, string $subdomain): View
    {
        // Get academy from subdomain (should be set by middleware)
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }
        
        if (!$academy->is_active) {
            abort(503, 'Academy is currently unavailable');
        }
        
        if ($academy->maintenance_mode) {
            abort(503, 'Academy is currently under maintenance');
        }

        // Get academy statistics
        $stats = $this->getAcademyStats($academy);
        
        // Get featured services
        $services = $this->getFeaturedServices($academy);
        
        return view('academy.homepage', [
            'academy' => $academy,
            'stats' => $stats,
            'services' => $services,
        ]);
    }
    
    /**
     * Get academy statistics for counters
     */
    private function getAcademyStats(Academy $academy): array
    {
        return [
            'total_students' => $academy->students()->count(),
            'total_teachers' => $academy->teachers()->count(),
            'active_courses' => $this->getActiveCoursesCount($academy),
            'quran_circles' => QuranCircle::where('academy_id', $academy->id)
                ->where('status', 'active')
                ->count(),
            'completion_rate' => $this->getAverageCompletionRate($academy),
        ];
    }
    
    /**
     * Get featured services for homepage sections
     */
    private function getFeaturedServices(Academy $academy): array
    {
        return [
            'quran_circles' => QuranCircle::where('academy_id', $academy->id)
                ->where('status', 'active')
                ->where('enrollment_status', 'open')
                ->with(['quranTeacher.user'])
                ->limit(3)
                ->get(),
                
            'quran_teachers' => User::where('academy_id', $academy->id)
                ->where('user_type', 'quran_teacher')
                ->whereHas('quranTeacherProfile', function($query) {
                    $query->where('is_active', true)
                          ->where('approval_status', 'approved');
                })
                ->with('quranTeacherProfile')
                ->limit(3)
                ->get(),
                
            'interactive_courses' => InteractiveCourse::where('academy_id', $academy->id)
                ->where('is_published', true)
                ->where('status', 'active')
                ->where('enrollment_deadline', '>', now())
                ->with(['assignedTeacher.user', 'subject', 'gradeLevel'])
                ->limit(3)
                ->get(),
                
            'academic_teachers' => User::where('academy_id', $academy->id)
                ->where('user_type', 'academic_teacher')
                ->whereHas('academicTeacherProfile', function($query) {
                    $query->where('is_active', true)
                          ->where('approval_status', 'approved');
                })
                ->with(['academicTeacherProfile', 'subjects'])
                ->limit(3)
                ->get(),
                
            'recorded_courses' => RecordedCourse::where('academy_id', $academy->id)
                ->where('is_published', true)
                ->where('status', 'published')
                ->where('is_featured', true)
                ->with(['instructor.user', 'subject', 'gradeLevel'])
                ->orderBy('total_enrollments', 'desc')
                ->limit(6)
                ->get(),
        ];
    }
    
    /**
     * Get total active courses count
     */
    private function getActiveCoursesCount(Academy $academy): int
    {
        $recordedCount = RecordedCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->count();
            
        $interactiveCount = InteractiveCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->where('status', 'active')
            ->count();
            
        return $recordedCount + $interactiveCount;
    }
    
    /**
     * Get average completion rate across all courses
     * Note: Since completion_rate column doesn't exist yet, we'll use avg_rating as a proxy
     * or provide a default value. In the future, this can be calculated from student progress data.
     */
    private function getAverageCompletionRate(Academy $academy): int
    {
        // Option 1: Use avg_rating as a proxy (convert 0-5 rating to percentage)
        $avgRating = RecordedCourse::where('academy_id', $academy->id)
            ->where('is_published', true)
            ->avg('avg_rating');
            
        if ($avgRating > 0) {
            // Convert 0-5 rating to percentage (e.g., 4.5/5 = 90%)
            return (int) round(($avgRating / 5) * 100);
        }
        
        // Option 2: Calculate from enrollment vs completion data (future implementation)
        // This would require student progress tracking
        
        // Default to 85% completion rate for now
        return 85;
    }
}