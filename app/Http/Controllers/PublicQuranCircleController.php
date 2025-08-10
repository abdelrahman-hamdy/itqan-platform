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
    public function show(Request $request, $subdomain, $circleId)
    {
        // Get the current academy from subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }

        // Find the circle by ID within the academy
        $circle = QuranCircle::where('id', $circleId)
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->with(['academy', 'quranTeacher.user', 'students'])
            ->first();

        if (!$circle) {
            abort(404, 'Circle not found');
        }

        // Calculate circle statistics
        $stats = [
            'total_students' => $circle->enrolled_students ?? 0,
            'available_spots' => $circle->available_spots ?? 0,
            'sessions_completed' => $circle->sessions_completed ?? 0,
            'rating' => $circle->avg_rating ?? 0,
        ];

        // Check if user is already enrolled in this circle
        $isEnrolled = false;
        if (Auth::check() && Auth::user()->user_type === 'student') {
            $isEnrolled = $circle->students()->where('user_id', Auth::id())->exists();
        }

        return view('public.quran-circles.show', compact(
            'academy', 
            'circle', 
            'stats', 
            'isEnrolled'
        ));
    }

    /**
     * Show circle enrollment form
     */
    public function showEnrollment(Request $request, $subdomain, $circleId)
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

        $circle = QuranCircle::where('id', $circleId)
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('enrollment_status', 'open')
            ->first();

        if (!$circle) {
            abort(404, 'Circle not found or not available for enrollment');
        }

        // Check if circle is full
        if ($circle->is_full) {
            return redirect()->route('public.quran-circles.show', ['subdomain' => $academy->subdomain, 'circle' => $circle->id])
                ->with('error', 'الحلقة مكتملة العدد');
        }

        // Load student profile for the authenticated user
        $user = Auth::user();
        if ($user) {
            $user->load('studentProfile');
        }

        return view('public.quran-circles.enrollment', compact('academy', 'circle'));
    }

    /**
     * Process circle enrollment request
     */
    public function submitEnrollment(Request $request, $subdomain, $circleId)
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

        $circle = QuranCircle::where('id', $circleId)
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where('enrollment_status', 'open')
            ->first();

        if (!$circle) {
            abort(404, 'Circle not found or not available for enrollment');
        }

        $user = Auth::user();

        // Debug: Log form submission
        Log::info('Circle enrollment form submitted', [
            'user_id' => $user->id,
            'circle_id' => $circleId,
            'form_data' => $request->all()
        ]);

        // Check if student is already enrolled in this circle
        if ($circle->students()->where('user_id', $user->id)->exists()) {
            return redirect()->back()
                ->with('error', 'أنت مسجل بالفعل في هذه الحلقة');
        }

        // Check if circle is full
        if ($circle->is_full) {
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
            $circle->enrollStudent($user, [
                'current_level' => $request->current_level,
                'progress_notes' => json_encode([
                    'learning_goals' => $request->learning_goals,
                    'notes' => $request->notes,
                    'enrollment_date' => now()->toDateString()
                ])
            ]);

            // TODO: Send notification to teacher and academy admin
            // TODO: Send confirmation email to student

            return redirect()->route('public.quran-circles.show', ['subdomain' => $academy->subdomain, 'circle' => $circle->id])
                ->with('success', 'تم التسجيل في الحلقة بنجاح! سيتواصل معك المعلم قريباً لتحديد موعد البداية');

        } catch (\Exception $e) {
            Log::error('Circle enrollment failed', [
                'user_id' => $user->id,
                'circle_id' => $circleId,
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