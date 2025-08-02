<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use App\Models\Academy;
use App\Models\StudentProfile;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            
            if (!$academy || !$academy->is_active) {
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
            
            if (!$academy || !$academy->is_active) {
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
            if (!$user->isActive()) {
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

        return redirect()->route('login');
    }

    /**
     * Show registration form for students
     */
    public function showStudentRegistration(Request $request)
    {
        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy || !$academy->is_active) {
            abort(404, 'Academy not found or inactive');
        }

        return view('auth.student-register', compact('academy'));
    }

    /**
     * Handle student registration
     */
    public function registerStudent(Request $request)
    {
        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy || !$academy->is_active) {
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
            'grade_level' => 'required|exists:academic_grade_levels,id',
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

        // Create user
        $user = User::create([
            'academy_id' => $academy->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'user_type' => 'student',
            'status' => 'active',
            'is_active' => true,
            'email_verification_token' => Str::random(64),
        ]);

        // Create student profile
        StudentProfile::create([
            'user_id' => $user->id,
            'academy_id' => $academy->id,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'grade_level_id' => $request->grade_level,
            'parent_phone' => $request->parent_phone,
        ]);

        // Send email verification
        // TODO: Implement email verification

        // Auto-login the user
        Auth::login($user);

        return redirect()->route('student.profile')->with('success', 'تم التسجيل بنجاح! مرحباً بك في منصة إتقان');
    }

    /**
     * Show teacher registration form
     */
    public function showTeacherRegistration(Request $request)
    {
        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy || !$academy->is_active) {
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
        
        return redirect()->route('teacher.register.step2');
    }

    /**
     * Show teacher registration step 2 (teacher-specific form)
     */
    public function showTeacherRegistrationStep2(Request $request)
    {
        $teacherType = $request->session()->get('teacher_type');
        
        if (!$teacherType) {
            return redirect()->route('teacher.register', ['subdomain' => $request->route('subdomain')]);
        }

        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy || !$academy->is_active) {
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
        
        if (!$teacherType) {
            return redirect()->route('teacher.register', ['subdomain' => $request->route('subdomain')]);
        }

        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy || !$academy->is_active) {
            return back()->withErrors(['email' => 'Academy not found or inactive'])->withInput();
        }

        // Validation rules based on teacher type
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'qualification_degree' => 'required|string|max:255',
            'university' => 'required|string|max:255',
            'years_experience' => 'required|integer|min:0|max:50',
        ];

        if ($teacherType === 'quran_teacher') {
            $rules['has_ijazah'] = 'required|boolean';
            $rules['ijazah_details'] = 'required_if:has_ijazah,1|nullable|string|max:500';
        } else {
            $rules['subjects'] = 'required|array|min:1';
            $rules['subjects.*'] = 'exists:academic_subjects,id';
            $rules['grade_levels'] = 'required|array|min:1';
            $rules['grade_levels.*'] = 'exists:academic_grade_levels,id';
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
            'has_ijazah.required' => 'هل لديك إجازة؟',
            'ijazah_details.required_if' => 'تفاصيل الإجازة مطلوبة',
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
            'is_active' => false, // Will be activated after approval
            'email_verification_token' => Str::random(64),
            'qualification_degree' => $request->qualification_degree,
            'university' => $request->university,
            'years_experience' => $request->years_experience,
        ]);

        // Create teacher profile based on type
        if ($teacherType === 'quran_teacher') {
            QuranTeacherProfile::create([
                'user_id' => $user->id,
                'academy_id' => $academy->id,
                'qualification_degree' => $request->qualification_degree,
                'university' => $request->university,
                'years_experience' => $request->years_experience,
                'has_ijazah' => $request->has_ijazah,
                'ijazah_details' => $request->ijazah_details,
            ]);
        } else {
            AcademicTeacherProfile::create([
                'user_id' => $user->id,
                'academy_id' => $academy->id,
                'qualification_degree' => $request->qualification_degree,
                'university' => $request->university,
                'years_experience' => $request->years_experience,
            ]);
        }

        // Clear session
        $request->session()->forget('teacher_type');

        return redirect()->route('teacher.register.success')->with('success', 'تم تقديم طلب التسجيل بنجاح! سنتواصل معك قريباً');
    }

    /**
     * Show teacher registration success page
     */
    public function showTeacherRegistrationSuccess()
    {
        return view('auth.teacher-register-success');
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

        if ($user->isTeacher()) {
            return redirect('/teacher');
        }

        if ($user->isSupervisor()) {
            return redirect('/supervisor');
        }

        // Students and parents go to profile page (no dashboard)
        if ($user->isStudent()) {
            return redirect()->route('student.profile');
        }

        if ($user->isParent()) {
            return redirect()->route('parent.profile');
        }

        // Fallback
        return redirect('/');
    }
}
