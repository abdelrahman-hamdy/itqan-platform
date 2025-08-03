<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\QuranCircle;
use App\Models\QuranTeacherProfile;
use App\Models\InteractiveCourse;
use App\Models\RecordedCourse;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranSubscription;
use App\Models\AcademicProgress;
use App\Models\StudentProfile;

class StudentProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $studentProfile = $user->studentProfileUnscoped;
        $academy = $user->academy;

        // Get student's Quran circles
        $quranCircles = QuranCircle::where('academy_id', $academy->id)
            ->whereHas('students', function($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with(['teacher', 'students'])
            ->get();

        // Get student's Quran private sessions
        $quranPrivateSessions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('subscription_type', 'private')
            ->with(['teacher', 'student'])
            ->get();

        // Get student's interactive courses
        $interactiveCourses = InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with(['teacher', 'enrollments' => function($query) use ($user) {
                $query->where('student_id', $user->id);
            }])
            ->get();

        // Get student's recorded courses
        $recordedCourses = RecordedCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with(['enrollments' => function($query) use ($user) {
                $query->where('student_id', $user->id);
            }])
            ->get();

        // Calculate statistics
        $stats = $this->calculateStudentStats($user);

        return view('student.profile', compact(
            'quranCircles',
            'quranPrivateSessions', 
            'interactiveCourses',
            'recordedCourses',
            'stats'
        ));
    }

    private function calculateStudentStats($user)
    {
        $academy = $user->academy;

        // Calculate total learning hours (simplified calculation)
        $totalHours = 24; // This would be calculated from actual session data
        $hoursGrowth = 12; // Percentage growth this month

        // Count active courses
        $activeCourses = InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->count();

        // Count completed lessons
        $completedLessons = AcademicProgress::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('progress_status', 'completed')
            ->count();

        // Calculate Quran progress (simplified)
        $quranProgress = 75; // This would be calculated from actual Quran progress data
        $quranPages = 12; // Number of pages memorized

        // Achievement points (simplified)
        $achievementPoints = 850;
        $achievementsCount = 8;

        // Count active Quran circles
        $quranCirclesCount = QuranCircle::where('academy_id', $academy->id)
            ->whereHas('students', function($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->count();

        return [
            'totalHours' => $totalHours,
            'hoursGrowth' => $hoursGrowth,
            'activeCourses' => $activeCourses,
            'completedLessons' => $completedLessons,
            'quranProgress' => $quranProgress,
            'quranPages' => $quranPages,
            'achievementPoints' => $achievementPoints,
            'achievementsCount' => $achievementsCount,
            'quranCirclesCount' => $quranCirclesCount,
        ];
    }

    public function edit()
    {
        $user = Auth::user();
        $studentProfile = $user->studentProfileUnscoped;
        
        // Handle case where student profile doesn't exist or was orphaned
        if (!$studentProfile) {
            // Try to create a basic student profile if one doesn't exist
            $studentProfile = $this->createBasicStudentProfile($user);
            
            if (!$studentProfile) {
                return redirect()->route('student.profile')
                    ->with('error', 'لم يتم العثور على الملف الشخصي للطالب. يرجى التواصل مع الدعم الفني.');
            }
        }
        
        return view('student.edit-profile', compact('studentProfile'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $studentProfile = $user->studentProfileUnscoped;
        
        // Handle case where student profile doesn't exist or was orphaned
        if (!$studentProfile) {
            $studentProfile = $this->createBasicStudentProfile($user);
            
            if (!$studentProfile) {
                return redirect()->back()
                    ->with('error', 'لم يتم العثور على الملف الشخصي للطالب. يرجى التواصل مع الدعم الفني.');
            }
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'nationality' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:20',
        ]);

        $studentProfile->update($validated);

        return redirect()->route('student.profile', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy'])
            ->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }

    public function settings()
    {
        $user = Auth::user();
        
        return view('student.settings', compact('user'));
    }

    public function subscriptions()
    {
        $user = Auth::user();
        $academy = $user->academy;

        $quranSubscriptions = QuranSubscription::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['teacher', 'package'])
            ->get();

        $courseEnrollments = InteractiveCourse::where('academy_id', $academy->id)
            ->whereHas('enrollments', function($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->with(['teacher', 'enrollments' => function($query) use ($user) {
                $query->where('student_id', $user->id);
            }])
            ->get();

        return view('student.subscriptions', compact('quranSubscriptions', 'courseEnrollments'));
    }

    public function payments()
    {
        $user = Auth::user();
        
        // This would fetch actual payment data from your payment system
        $payments = collect([
            [
                'id' => 1,
                'amount' => 150,
                'currency' => 'SAR',
                'description' => 'اشتراك دائرة القرآن - مارس 2024',
                'status' => 'completed',
                'date' => now()->subDays(5),
                'method' => 'بطاقة ائتمان'
            ],
            [
                'id' => 2,
                'amount' => 200,
                'currency' => 'SAR',
                'description' => 'كورس الرياضيات التفاعلي',
                'status' => 'completed',
                'date' => now()->subDays(15),
                'method' => 'تحويل بنكي'
            ]
        ]);

        return view('student.payments', compact('payments'));
    }

    public function progress()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // Get academic progress
        $academicProgress = AcademicProgress::where('student_id', $user->id)
            ->where('academy_id', $academy->id)
            ->with(['course', 'teacher'])
            ->get();

        // Calculate overall progress
        $totalLessons = $academicProgress->count();
        $completedLessons = $academicProgress->where('status', 'completed')->count();
        $overallProgress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        return view('student.progress', compact('academicProgress', 'overallProgress'));
    }

    public function certificates()
    {
        $user = Auth::user();
        $academy = $user->academy;

        // This would fetch actual certificates from your system
        $certificates = collect([
            [
                'id' => 1,
                'title' => 'شهادة إتمام دورة القرآن الكريم',
                'course' => 'دائرة الحفظ المتقدم',
                'teacher' => 'الأستاذ أحمد محمد',
                'date' => now()->subDays(30),
                'status' => 'issued'
            ],
            [
                'id' => 2,
                'title' => 'شهادة إتمام كورس الرياضيات',
                'course' => 'الرياضيات للصف الثالث',
                'teacher' => 'الأستاذة ليلى محمد',
                'date' => now()->subDays(15),
                'status' => 'issued'
            ]
        ]);

        return view('student.certificates', compact('certificates'));
    }
    
    /**
     * Create a basic student profile for users who don't have one
     * This can happen when grade levels are deleted and relationships become orphaned
     */
    private function createBasicStudentProfile($user)
    {
        try {
            // Check if a profile already exists but might be orphaned
            $existingProfile = StudentProfile::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->first();
                
            if ($existingProfile) {
                return $existingProfile;
            }
            
            // Generate a unique student code
            $studentCode = 'STU' . str_pad($user->id, 6, '0', STR_PAD_LEFT);
            
            // Check for existing student code and make it unique
            $counter = 1;
            $originalCode = $studentCode;
            while (StudentProfile::where('student_code', $studentCode)->exists()) {
                $studentCode = $originalCode . '-' . $counter;
                $counter++;
            }
            
            // Find the default grade level for the user's academy
            $defaultGradeLevel = \App\Models\GradeLevel::where('academy_id', $user->academy_id)
                ->where('is_active', true)
                ->orderBy('level')
                ->first();
            
            // Create a basic student profile
            $studentProfile = StudentProfile::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name ?? 'طالب',
                'last_name' => $user->last_name ?? 'جديد',
                'student_code' => $studentCode,
                'grade_level_id' => $defaultGradeLevel?->id, // Can be null initially
                'enrollment_date' => now(),
                'academic_status' => 'enrolled',
                'notes' => 'تم إنشاء الملف الشخصي تلقائياً بعد حل مشكلة البيانات المفقودة'
            ]);
            
            return $studentProfile;
            
        } catch (\Exception $e) {
            \Log::error('Failed to create basic student profile for user ' . $user->id . ': ' . $e->getMessage());
            return null;
        }
    }
} 