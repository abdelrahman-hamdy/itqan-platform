<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm(Request $request)
    {
        // Get academy from subdomain
        $subdomain = $request->route('subdomain');
        $academy = null;

        if ($subdomain) {
            $academy = Academy::where('subdomain', $subdomain)->first();

            if (! $academy || ! $academy->is_active) {
                abort(404, 'Academy not found or inactive');
            }
        }

        return view('auth.login', compact('academy'));
    }

    /**
     * Handle login attempt
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'remember' => 'boolean',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Get academy from subdomain
        $subdomain = $request->route('subdomain');
        $academy = null;

        if ($subdomain) {
            $academy = Academy::where('subdomain', $subdomain)->first();

            if (! $academy || ! $academy->is_active) {
                return back()->withErrors(['email' => 'Academy not found or inactive'])->withInput();
            }
        }

        // Attempt to authenticate
        $credentials = $request->only('email', 'password');

        // Add academy_id to credentials if we're on a subdomain
        if ($academy) {
            $credentials['academy_id'] = $academy->id;
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Check if user is active
            if (! $user->isActive()) {
                Auth::logout();

                return back()->withErrors(['email' => 'حسابك غير نشط. يرجى التواصل مع الإدارة'])->withInput();
            }

            // Update last login
            $user->update(['last_login_at' => now()]);

            // Create session record
            $this->createUserSession($user, $request);

            // Redirect based on user type
            return $this->redirectBasedOnUserType($user, $academy);
        }

        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->withInput();
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            // Deactivate current session
            $sessionId = $request->session()->getId();
            UserSession::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->update(['is_active' => false, 'logout_at' => now()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $subdomain = $request->route('subdomain') ?? 'itqan-academy';

        return redirect()->route('login', ['subdomain' => $subdomain]);
    }

    /**
     * Show registration form for students
     */
    public function showStudentRegistration(Request $request)
    {
        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            abort(404, 'Academy not found or inactive');
        }

        // Get grade levels for the academy
        $gradeLevels = \App\Models\AcademicGradeLevel::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('auth.student-register', compact('academy', 'gradeLevels'));
    }

    /**
     * Handle student registration
     */
    public function registerStudent(Request $request)
    {
        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            return back()->withErrors(['email' => 'Academy not found or inactive'])->withInput();
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'parent_phone' => 'nullable|string|max:20',
            'birth_date' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'grade_level' => 'required|exists:grade_levels,id',
        ], [
            'first_name.required' => 'الاسم الأول مطلوب',
            'last_name.required' => 'اسم العائلة مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'phone.required' => 'رقم الهاتف مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
            'birth_date.required' => 'تاريخ الميلاد مطلوب',
            'birth_date.before' => 'تاريخ الميلاد يجب أن يكون في الماضي',
            'gender.required' => 'الجنس مطلوب',
            'grade_level.required' => 'المستوى الدراسي مطلوب',
            'grade_level.exists' => 'المستوى الدراسي غير صحيح',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Create or get existing user
        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Update existing user if found
            $user->update([
                'academy_id' => $academy->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'user_type' => 'student',
                'status' => 'active',
                'active_status' => true,
            ]);
        } else {
            // Create new user
            $user = User::create([
                'academy_id' => $academy->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'user_type' => 'student',
                'status' => 'active',
                'active_status' => true,
            ]);
        }

        // Create or update student profile
        $existingProfile = StudentProfile::withoutGlobalScopes()
            ->where(function ($query) use ($user, $request) {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $request->email);
            })->first();

        if ($existingProfile) {
            // Update existing profile
            $existingProfile->update([
                'user_id' => $user->id,
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'grade_level_id' => $request->grade_level,
                'parent_phone' => $request->parent_phone,
            ]);
        } else {
            // Create new student profile
            StudentProfile::create([
                'user_id' => $user->id,
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'grade_level_id' => $request->grade_level,
                'parent_phone' => $request->parent_phone,
            ]);
        }

        // Send email verification
        // TODO: Implement email verification

        // Auto-login the user
        Auth::login($user);

        $subdomain = $user->academy->subdomain ?? 'itqan-academy';

        return redirect()->route('student.profile', ['subdomain' => $subdomain])->with('success', 'تم التسجيل بنجاح! مرحباً بك في منصة إتقان');
    }

    /**
     * Show teacher registration form
     */
    public function showTeacherRegistration(Request $request)
    {
        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            abort(404, 'Academy not found or inactive');
        }

        return view('auth.teacher-register', compact('academy'));
    }

    /**
     * Handle teacher registration step 1 (teacher type selection)
     */
    public function registerTeacherStep1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_type' => 'required|in:quran_teacher,academic_teacher',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $request->session()->put('teacher_type', $request->teacher_type);

        return redirect()->route('teacher.register.step2', ['subdomain' => $request->route('subdomain')]);
    }

    /**
     * Show teacher registration step 2 (teacher-specific form)
     */
    public function showTeacherRegistrationStep2(Request $request)
    {
        $teacherType = $request->session()->get('teacher_type');

        if (! $teacherType) {
            return redirect()->route('teacher.register', ['subdomain' => $request->route('subdomain')]);
        }

        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            abort(404, 'Academy not found or inactive');
        }

        return view('auth.teacher-register-step2', compact('academy', 'teacherType'));
    }

    /**
     * Handle teacher registration step 2
     */
    public function registerTeacherStep2(Request $request)
    {
        $teacherType = $request->session()->get('teacher_type');

        if (! $teacherType) {
            return redirect()->route('teacher.register', ['subdomain' => $request->route('subdomain')]);
        }

        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            return back()->withErrors(['email' => 'Academy not found or inactive'])->withInput();
        }

        // Validation rules based on teacher type
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'qualification_degree' => 'required|in:bachelor,master,phd,other',
            'university' => 'required|string|max:255',
            'years_experience' => 'required|integer|min:0|max:50',
        ];

        if ($teacherType === 'academic_teacher') {
            $rules['subjects'] = 'required|array|min:1';
            $rules['grade_levels'] = 'required|array|min:1';
        }

        $validator = Validator::make($request->all(), $rules, [
            'first_name.required' => 'الاسم الأول مطلوب',
            'last_name.required' => 'اسم العائلة مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'phone.required' => 'رقم الهاتف مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
            'qualification_degree.required' => 'الدرجة العلمية مطلوبة',
            'university.required' => 'الجامعة مطلوبة',
            'years_experience.required' => 'سنوات الخبرة مطلوبة',
            'subjects.required' => 'المواد الدراسية مطلوبة',
            'subjects.min' => 'يجب اختيار مادة واحدة على الأقل',
            'grade_levels.required' => 'المستويات الدراسية مطلوبة',
            'grade_levels.min' => 'يجب اختيار مستوى واحد على الأقل',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Create user
        $user = User::create([
            'academy_id' => $academy->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'user_type' => $teacherType,
            'status' => 'pending', // Requires approval
            'active_status' => false, // Will be activated after approval
            'qualification_degree' => $request->qualification_degree,
            'university' => $request->university,
            'years_experience' => $request->years_experience,
        ]);

        // Create teacher profile manually (automatic creation is disabled for teachers)
        try {
            if ($teacherType === 'quran_teacher') {
                QuranTeacherProfile::create([
                    'user_id' => $user->id,
                    'academy_id' => $academy->id,
                    'email' => $request->email,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'educational_qualification' => $request->qualification_degree,
                    'teaching_experience_years' => $request->years_experience,
                    'approval_status' => 'pending',
                    'is_active' => false,
                    // teacher_code will be auto-generated by model boot method
                    'certifications' => json_encode([
                        'qualification_degree' => $request->qualification_degree,
                        'university' => $request->university,
                    ]),
                ]);
            } else {
                AcademicTeacherProfile::create([
                    'user_id' => $user->id,
                    'academy_id' => $academy->id,
                    'email' => $request->email,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'education_level' => 'bachelor',
                    'qualification_degree' => $request->qualification_degree,
                    'university' => $request->university,
                    'teaching_experience_years' => $request->years_experience,
                    'subject_ids' => json_encode($request->subjects),
                    'grade_level_ids' => json_encode($request->grade_levels),
                    'approval_status' => 'pending',
                    'is_active' => false,
                    // teacher_code will be auto-generated by model boot method
                ]);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Log the error for debugging
            Log::error('Teacher registration failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'academy_id' => $academy->id,
                'email' => $request->email,
                'teacher_type' => $teacherType,
            ]);

            // Delete the user since profile creation failed
            $user->delete();

            // Check if it's a duplicate teacher code error
            if (str_contains($e->getMessage(), 'teacher_code_unique') || $e->getCode() === '23000') {
                return back()->withErrors(['error' => 'حدث خطأ في إنشاء رمز المعلم. يرجى المحاولة مرة أخرى.'])->withInput();
            }

            // Generic error message for other database issues
            return back()->withErrors(['error' => 'حدث خطأ في التسجيل. يرجى المحاولة مرة أخرى أو التواصل مع الدعم.'])->withInput();
        }

        // Clear session
        $request->session()->forget('teacher_type');

        return redirect()->route('teacher.register.success', ['subdomain' => $request->route('subdomain')])->with('success', 'تم تقديم طلب التسجيل بنجاح! سنتواصل معك قريباً');
    }

    /**
     * Show teacher registration success page
     */
    public function showTeacherRegistrationSuccess(Request $request)
    {
        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            abort(404, 'Academy not found or inactive');
        }

        return view('auth.teacher-register-success', compact('academy'));
    }

    /**
     * Create user session record
     */
    private function createUserSession(User $user, Request $request): void
    {
        $sessionId = $request->session()->getId();
        $userAgent = $request->userAgent();

        // Parse user agent for device info
        $deviceInfo = $this->parseUserAgent($userAgent);

        UserSession::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'login_at' => now(),
            'last_activity_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Parse user agent string
     */
    private function parseUserAgent(string $userAgent): array
    {
        $deviceType = 'desktop';
        $browser = 'unknown';
        $platform = 'unknown';

        // Simple device detection
        if (preg_match('/(android|iphone|ipad|mobile)/i', $userAgent)) {
            $deviceType = 'mobile';
        }

        // Browser detection
        if (preg_match('/chrome/i', $userAgent)) {
            $browser = 'chrome';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'firefox';
        } elseif (preg_match('/safari/i', $userAgent)) {
            $browser = 'safari';
        } elseif (preg_match('/edge/i', $userAgent)) {
            $browser = 'edge';
        }

        // Platform detection
        if (preg_match('/windows/i', $userAgent)) {
            $platform = 'windows';
        } elseif (preg_match('/mac/i', $userAgent)) {
            $platform = 'mac';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'android';
        } elseif (preg_match('/ios/i', $userAgent)) {
            $platform = 'ios';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
        ];
    }

    /**
     * Redirect user based on their type
     */
    private function redirectBasedOnUserType(User $user, ?Academy $academy)
    {
        if ($user->isSuperAdmin()) {
            return redirect('/admin');
        }

        if ($user->isAcademyAdmin()) {
            return redirect('/panel');
        }

        if ($user->isQuranTeacher()) {
            // Quran teachers go to dashboard
            $subdomain = $academy ? $academy->subdomain : ($user->academy->subdomain ?? 'itqan-academy');

            return redirect()->route('teacher.dashboard', ['subdomain' => $subdomain]);
        }

        if ($user->isAcademicTeacher()) {
            // Academic teachers go to profile page
            $subdomain = $academy ? $academy->subdomain : ($user->academy->subdomain ?? 'itqan-academy');

            return redirect()->route('teacher.profile', ['subdomain' => $subdomain]);
        }

        if ($user->isSupervisor()) {
            return redirect('/supervisor');
        }

        // Students and parents go to profile page (no dashboard)
        if ($user->isStudent()) {
            // Get the subdomain from the user's academy
            $subdomain = $user->academy->subdomain ?? 'itqan-academy';

            return redirect()->route('student.profile', ['subdomain' => $subdomain]);
        }

        if ($user->isParent()) {
            // Get the subdomain from the user's academy
            $subdomain = $user->academy->subdomain ?? 'itqan-academy';

            return redirect()->route('parent.profile', ['subdomain' => $subdomain]);
        }

        // Fallback
        return redirect('/');
    }
}
