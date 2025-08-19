<?php

namespace App\Http\Controllers;

use App\Models\QuranCircle;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PublicQuranCircleController extends Controller
{
    /**
     * Display a listing of Quran circles for an academy
     */
    public function index(Request $request, $subdomain)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        // Get active circles that are open for enrollment
        $circles = QuranCircle::where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('enrollment_status', 'open')
            ->with(['academy', 'quranTeacher.user'])
            ->withCount(['students', 'sessions'])
            ->paginate(12);

        return view('public.quran-circles.index', compact('academy', 'circles'));
    }

    /**
     * Display the specified circle details
     */
    public function show(Request $request, $subdomain, $circle)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        // Find the circle by ID within the academy
        $circleModel = QuranCircle::where('id', $circle)
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->with(['academy', 'quranTeacher.user', 'students', 'schedule'])
            ->first();

        if (!$circleModel) {
            abort(404, 'Circle not found');
        }

        // Calculate circle statistics
        $stats = [
            'total_students' => $circleModel->enrolled_students ?? 0,
            'available_spots' => $circleModel->available_spots ?? 0,
            'sessions_completed' => $circleModel->sessions_completed ?? 0,
            'rating' => $circleModel->avg_rating ?? 0,
        ];

        // Check user role and permissions
        $userRole = 'guest';
        $isEnrolled = false;
        $isTeacher = false;
        
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user->user_type === 'student') {
                $userRole = 'student';
                $isEnrolled = $circleModel->students()->where('user_id', Auth::id())->exists();
            } elseif ($user->user_type === 'quran_teacher' && $user->quranTeacherProfile && $circleModel->quran_teacher_id === $user->quranTeacherProfile->id) {
                $userRole = 'teacher';
                $isTeacher = true;
            }
        }

        // For teachers, load additional data
        $teacherData = [];
        if ($isTeacher) {
            // Load sessions for this circle
            $teacherData['recentSessions'] = $circleModel->sessions()
                ->with('student')
                ->latest()
                ->limit(5)
                ->get();
                
            $teacherData['upcomingSessions'] = $circleModel->sessions()
                ->with('student')
                ->where('scheduled_at', '>', now())
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get();
        }

        // Determine which view to use based on user role
        $viewName = $userRole === 'teacher' ? 'teacher.group-circles.show' : 'public.quran-circles.show';

        return view($viewName, compact(
            'academy', 
            'stats', 
            'isEnrolled',
            'isTeacher',
            'userRole',
            'teacherData'
        ) + ['circle' => $circleModel]);
    }

    /**
     * Show circle enrollment form
     */
    public function showEnrollment(Request $request, $subdomain, $circle)
    {
        // Check if user is authenticated and is a student
        if (!Auth::check() || Auth::user()->user_type !== 'student') {
            return redirect()->route('login', ['subdomain' => $subdomain])
                ->with('error', 'يجب تسجيل الدخول كطالب للانضمام للحلقة');
        }

        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        $circleModel = QuranCircle::where('id', $circle)
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('enrollment_status', 'open')
            ->first();

        if (!$circleModel) {
            abort(404, 'Circle not found or not available for enrollment');
        }

        // Check if circle is full
        if ($circleModel->is_full) {
            return redirect()->route('public.quran-circles.show', ['subdomain' => $academy->subdomain, 'circle' => $circleModel->id])
                ->with('error', 'الحلقة مكتملة العدد');
        }

        // Load student profile for the authenticated user
        $user = Auth::user();
        if ($user) {
            $user->load('studentProfile');
        }

        return view('public.quran-circles.enrollment', compact('academy') + ['circle' => $circleModel]);
    }

    /**
     * Process circle enrollment request
     */
    public function submitEnrollment(Request $request, $subdomain, $circle)
    {
        // Check if user is authenticated and is a student
        if (!Auth::check() || Auth::user()->user_type !== 'student') {
            return redirect()->route('login', ['subdomain' => $subdomain])
                ->with('error', 'يجب تسجيل الدخول كطالب للانضمام للحلقة');
        }

        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        $circleModel = QuranCircle::where('id', $circle)
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('enrollment_status', 'open')
            ->first();

        if (!$circleModel) {
            abort(404, 'Circle not found or not available for enrollment');
        }

        $user = Auth::user();

        // Debug: Log form submission
        Log::info('Circle enrollment form submitted', [
            'user_id' => $user->id,
            'circle_id' => $circle,
            'form_data' => $request->all()
        ]);

        // Check if student is already enrolled in this circle
        if ($circleModel->students()->where('user_id', $user->id)->exists()) {
            return redirect()->back()
                ->with('error', 'أنت مسجل بالفعل في هذه الحلقة');
        }

        // Check if circle is full
        if ($circleModel->is_full) {
            return redirect()->back()
                ->with('error', 'الحلقة مكتملة العدد');
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'current_level' => 'required|in:beginner,elementary,intermediate,advanced,expert,hafiz',
            'learning_goals' => 'required|array|min:1',
            'learning_goals.*' => 'in:reading,tajweed,memorization,improvement',
            'notes' => 'nullable|string|max:1000',
        ], [
            'current_level.required' => 'المستوى الحالي مطلوب',
            'learning_goals.required' => 'يجب اختيار هدف واحد على الأقل',
            'learning_goals.min' => 'يجب اختيار هدف واحد على الأقل',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Get student profile for data
            $studentProfile = $user->studentProfile;
            
            // Enroll the student in the circle
            $circleModel->enrollStudent($user, [
                'current_level' => $request->current_level,
                'progress_notes' => json_encode([
                    'learning_goals' => $request->learning_goals,
                    'notes' => $request->notes,
                    'enrollment_date' => now()->toDateString()
                ])
            ]);

            // TODO: Send notification to teacher and academy admin
            // TODO: Send confirmation email to student

            return redirect()->route('public.quran-circles.show', ['subdomain' => $academy->subdomain, 'circle' => $circleModel->id])
                ->with('success', 'تم التسجيل في الحلقة بنجاح! سيتواصل معك المعلم قريباً لتحديد موعد البداية');

        } catch (\Exception $e) {
            Log::error('Circle enrollment failed', [
                'user_id' => $user->id,
                'circle_id' => $circle,
                'academy_id' => $academy->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى')
                ->withInput();
        }
    }
}