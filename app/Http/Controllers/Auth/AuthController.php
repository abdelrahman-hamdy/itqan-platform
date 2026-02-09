<?php

namespace App\Http\Controllers\Auth;

use App\Constants\DefaultAcademy;
use App\Enums\UserType;
use App\Helpers\CountryList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Models\UserSession;
use App\Notifications\ResetPasswordNotification;
use App\Rules\PasswordRules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm(Request $request): \Illuminate\View\View
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

        // Store the intended redirect URL if provided
        if ($request->has('redirect')) {
            $redirectUrl = $request->input('redirect');

            // Security: Validate redirect URL to prevent open redirect attacks
            if ($this->isValidRedirectUrl($redirectUrl, $request)) {
                $request->session()->put('url.intended', $redirectUrl);
            }
        }

        return view('auth.login', compact('academy'));
    }

    /**
     * Handle login attempt
     */
    public function login(LoginRequest $request): \Illuminate\Http\RedirectResponse
    {
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

            // Check for intended URL in session (set by middleware or showLoginForm)
            if ($request->session()->has('url.intended')) {
                $intendedUrl = $request->session()->pull('url.intended');

                return redirect()->to($intendedUrl);
            }

            // Redirect based on user type
            return $this->redirectBasedOnUserType($user, $academy);
        }

        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->withInput();
    }

    /**
     * Handle logout
     */
    public function logout(Request $request): \Illuminate\Http\RedirectResponse
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

        $subdomain = $request->route('subdomain') ?? DefaultAcademy::subdomain();

        return redirect()->route('login', ['subdomain' => $subdomain]);
    }

    /**
     * Show registration form for students
     */
    public function showStudentRegistration(Request $request): \Illuminate\View\View
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

        // Get countries for nationality field (unified list matching phone input)
        $countries = CountryList::toSelectArray();

        return view('auth.student-register', compact('academy', 'gradeLevels', 'countries'));
    }

    /**
     * Handle student registration
     */
    public function registerStudent(Request $request): \Illuminate\Http\RedirectResponse
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
            'password' => PasswordRules::create(),
            'parent_phone' => 'nullable|string|max:20',
            'birth_date' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'nationality' => 'required|string|in:'.CountryList::validationRule(),
            'grade_level' => 'required|exists:academic_grade_levels,id',
        ], [
            'first_name.required' => 'الاسم الأول مطلوب',
            'last_name.required' => 'اسم العائلة مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'phone.required' => 'رقم الهاتف مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.letters' => 'كلمة المرور يجب أن تحتوي على حرف واحد على الأقل',
            'password.numbers' => 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
            'birth_date.required' => 'تاريخ الميلاد مطلوب',
            'birth_date.before' => 'تاريخ الميلاد يجب أن يكون في الماضي',
            'gender.required' => 'الجنس مطلوب',
            'nationality.required' => 'الجنسية مطلوبة',
            'nationality.in' => 'الجنسية المختارة غير صحيحة',
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
                'user_type' => UserType::STUDENT->value,
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
                'user_type' => UserType::STUDENT->value,
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
                'nationality' => $request->nationality,
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
                'nationality' => $request->nationality,
                'grade_level_id' => $request->grade_level,
                'parent_phone' => $request->parent_phone,
            ]);
        }

        // Auto-login the user
        Auth::login($user);

        // Send email verification notification
        $user->sendEmailVerificationNotification();

        $subdomain = $user->academy->subdomain ?? DefaultAcademy::subdomain();

        return redirect()->route('student.profile', ['subdomain' => $subdomain])->with('success', 'تم التسجيل بنجاح! مرحباً بك في منصة إتقان');
    }

    /**
     * Show teacher registration form
     */
    public function showTeacherRegistration(Request $request): \Illuminate\View\View
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
    public function registerTeacherStep1(Request $request): \Illuminate\Http\RedirectResponse
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
    public function showTeacherRegistrationStep2(Request $request): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
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
    public function registerTeacherStep2(Request $request): \Illuminate\Http\RedirectResponse
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
            'password' => PasswordRules::create(),
            'education_level' => 'required|in:diploma,bachelor,master,phd,other',
            'university' => 'required|string|max:255',
            'years_experience' => 'required|integer|min:0|max:50',
        ];

        if ($teacherType === 'academic_teacher') {
            $rules['subjects'] = 'required|array|min:1';
            $rules['grade_levels'] = 'required|array|min:1';
            $rules['available_days'] = 'required|array|min:1';
        }

        if ($teacherType === 'quran_teacher') {
            $rules['certifications'] = 'nullable|array';
            $rules['certifications.*'] = 'string|max:255';
        }

        $validator = Validator::make($request->all(), $rules, [
            'first_name.required' => 'الاسم الأول مطلوب',
            'last_name.required' => 'اسم العائلة مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'phone.required' => 'رقم الهاتف مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.letters' => 'كلمة المرور يجب أن تحتوي على حرف واحد على الأقل',
            'password.numbers' => 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
            'education_level.required' => 'المؤهل التعليمي مطلوب',
            'university.required' => 'الجامعة مطلوبة',
            'years_experience.required' => 'سنوات الخبرة مطلوبة',
            'subjects.required' => 'المواد الدراسية مطلوبة',
            'subjects.min' => 'يجب اختيار مادة واحدة على الأقل',
            'grade_levels.required' => 'المستويات الدراسية مطلوبة',
            'grade_levels.min' => 'يجب اختيار مستوى واحد على الأقل',
            'available_days.required' => 'الأيام المتاحة مطلوبة',
            'available_days.min' => 'يجب اختيار يوم واحد على الأقل',
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
            'active_status' => false, // Will be activated after approval
            'education_level' => $request->education_level,
            'university' => $request->university,
            'years_experience' => $request->years_experience,
        ]);

        // Create teacher profile manually (automatic creation is disabled for teachers)
        // Personal info (first_name, last_name, email, phone) is stored ONLY in users table
        // Profile tables only store professional/teaching-related info
        try {
            if ($teacherType === 'quran_teacher') {
                QuranTeacherProfile::create([
                    'user_id' => $user->id,
                    'academy_id' => $academy->id,
                    'educational_qualification' => $request->education_level,
                    'teaching_experience_years' => $request->years_experience,
                    'is_active' => false,
                    // teacher_code will be auto-generated by model boot method
                    // certifications is an array of strings (certificates/ijazat)
                    'certifications' => $request->certifications ?? [],
                ]);
            } else {
                AcademicTeacherProfile::create([
                    'user_id' => $user->id,
                    'academy_id' => $academy->id,
                    'education_level' => $request->education_level,
                    'university' => $request->university,
                    'teaching_experience_years' => $request->years_experience,
                    // Don't json_encode - model has array cast
                    'subject_ids' => $request->subjects ?? [],
                    'grade_level_ids' => $request->grade_levels ?? [],
                    'available_days' => $request->available_days ?? [],
                    'certifications' => [],
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

        // Send email verification notification (independent of admin approval)
        $user->sendEmailVerificationNotification();

        // Clear session
        $request->session()->forget('teacher_type');

        return redirect()->route('teacher.register.success', ['subdomain' => $request->route('subdomain')])->with('success', 'تم تقديم طلب التسجيل بنجاح! سنتواصل معك قريباً');
    }

    /**
     * Show teacher registration success page
     */
    public function showTeacherRegistrationSuccess(Request $request): \Illuminate\View\View
    {
        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            abort(404, 'Academy not found or inactive');
        }

        return view('auth.teacher-register-success', compact('academy'));
    }

    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm(Request $request): \Illuminate\View\View
    {
        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            abort(404, 'Academy not found or inactive');
        }

        return view('auth.forgot-password', compact('academy'));
    }

    /**
     * Send password reset link to user's email
     */
    public function sendResetLink(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            return back()->withErrors(['email' => 'الأكاديمية غير موجودة أو غير نشطة'])->withInput();
        }

        // Find user in this academy
        $user = User::where('email', $request->email)
            ->where('academy_id', $academy->id)
            ->first();

        // Always show success message (security best practice - prevent user enumeration)
        $successMessage = 'إذا كان هذا البريد الإلكتروني مسجلاً لدينا، ستتلقى رابط إعادة تعيين كلمة المرور خلال دقائق.';

        if ($user) {
            // Generate reset token
            $token = Str::random(64);

            // Store token in password_reset_tokens table
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'email' => $request->email,
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // Send reset email notification
            try {
                $user->notify(new ResetPasswordNotification($token, $academy));
            } catch (\Exception $e) {
                Log::error('Failed to send password reset email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('status', $successMessage);
    }

    /**
     * Show reset password form
     */
    public function showResetPasswordForm(Request $request, string $token): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            abort(404, 'Academy not found or inactive');
        }

        $email = $request->query('email');

        if (! $email) {
            return redirect()->route('password.request', ['subdomain' => $subdomain])
                ->withErrors(['email' => 'رابط إعادة التعيين غير صالح']);
        }

        // Verify token exists and is not expired
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $record) {
            return redirect()->route('password.request', ['subdomain' => $subdomain])
                ->withErrors(['email' => 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية']);
        }

        // Check if token is expired (60 minutes)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->diffInMinutes(now()) > 60) {
            // Delete expired token
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return redirect()->route('password.request', ['subdomain' => $subdomain])
                ->withErrors(['email' => 'انتهت صلاحية رابط إعادة التعيين. يرجى طلب رابط جديد.']);
        }

        return view('auth.reset-password', compact('academy', 'token', 'email'));
    }

    /**
     * Reset user's password
     */
    public function resetPassword(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => PasswordRules::reset(),
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'token.required' => 'رمز إعادة التعيين مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.letters' => 'كلمة المرور يجب أن تحتوي على حرف واحد على الأقل',
            'password.numbers' => 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy || ! $academy->is_active) {
            return back()->withErrors(['email' => 'الأكاديمية غير موجودة أو غير نشطة'])->withInput();
        }

        // Find reset record
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record) {
            return back()->withErrors(['email' => 'رابط إعادة التعيين غير صالح أو منتهي الصلاحية'])->withInput();
        }

        // Verify token
        if (! Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'رابط إعادة التعيين غير صالح'])->withInput();
        }

        // Check if token is expired (60 minutes)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->diffInMinutes(now()) > 60) {
            // Delete expired token
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return redirect()->route('password.request', ['subdomain' => $subdomain])
                ->withErrors(['email' => 'انتهت صلاحية رابط إعادة التعيين. يرجى طلب رابط جديد.']);
        }

        // Find user in this academy
        $user = User::where('email', $request->email)
            ->where('academy_id', $academy->id)
            ->first();

        if (! $user) {
            return back()->withErrors(['email' => 'لم يتم العثور على حساب بهذا البريد الإلكتروني'])->withInput();
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete reset token
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Invalidate all existing sessions for security
        UserSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false, 'logout_at' => now()]);

        return redirect()->route('login', ['subdomain' => $subdomain])
            ->with('success', 'تم تغيير كلمة المرور بنجاح! يمكنك الآن تسجيل الدخول بكلمة المرور الجديدة.');
    }

    /*
    |--------------------------------------------------------------------------
    | Email Verification Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Show email verification notice page
     */
    public function showVerificationNotice(Request $request): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectBasedOnUserType($user, $user->academy);
        }

        $subdomain = $request->route('subdomain');
        $academy = Academy::where('subdomain', $subdomain)->first();

        return view('auth.verify-email', compact('academy'));
    }

    /**
     * Handle email verification
     *
     * This method handles the email verification link clicked from the email.
     * It manually validates the signed URL to avoid middleware redirect issues.
     */
    public function verifyEmail(Request $request): \Illuminate\View\View
    {
        // Get route parameters explicitly by name to avoid parameter order issues
        // (subdomain is captured from domain routing and can interfere with positional params)
        $subdomain = $request->route('subdomain');
        $id = $request->route('id');
        $hash = $request->route('hash');

        Log::info('Email verification attempt', [
            'id' => $id,
            'subdomain' => $subdomain,
        ]);

        $academy = Academy::where('subdomain', $subdomain)->first();

        if (! $academy) {
            Log::error('Academy not found for verification', ['subdomain' => $subdomain]);
            abort(404, 'Academy not found');
        }

        // Manually validate the signed URL
        if (! $request->hasValidSignature()) {
            Log::warning('Invalid or expired verification link', [
                'id' => $id,
                'subdomain' => $subdomain,
            ]);

            return view('auth.verify-email-error', [
                'academy' => $academy,
                'error' => 'expired',
                'message' => __('auth.verification.link_expired'),
            ]);
        }

        $user = User::where('id', (int) $id)
            ->where('academy_id', $academy->id)
            ->first();

        if (! $user) {
            Log::warning('User not found for verification', [
                'id' => $id,
                'academy_id' => $academy->id,
                'subdomain' => $subdomain,
            ]);

            return view('auth.verify-email-error', [
                'academy' => $academy,
                'error' => 'invalid',
                'message' => __('auth.verification.invalid_link'),
            ]);
        }

        $expectedHash = sha1($user->getEmailForVerification());
        if (! hash_equals($expectedHash, $hash)) {
            Log::warning('Hash mismatch for verification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'expected_hash' => $expectedHash,
                'received_hash' => $hash,
            ]);

            return view('auth.verify-email-error', [
                'academy' => $academy,
                'error' => 'invalid',
                'message' => __('auth.verification.invalid_link'),
            ]);
        }

        // Already verified - still show success
        if ($user->hasVerifiedEmail()) {
            Log::info('Email already verified', ['user_id' => $user->id]);

            return view('auth.verify-email-success', compact('academy'));
        }

        // Mark email as verified
        $user->markEmailAsVerified();

        Log::info('Email verified successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return view('auth.verify-email-success', compact('academy'));
    }

    /**
     * Resend verification email
     */
    public function resendVerificationEmail(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $subdomain = $request->route('subdomain') ?? DefaultAcademy::subdomain();

            return redirect()->route('student.profile', ['subdomain' => $subdomain])
                ->with('info', __('auth.verification.already_verified'));
        }

        $user->sendEmailVerificationNotification();

        return back()->with('success', __('auth.verification.email_sent'));
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
            $subdomain = $academy ? $academy->subdomain : ($user->academy->subdomain ?? DefaultAcademy::subdomain());

            return redirect()->route('teacher.dashboard', ['subdomain' => $subdomain]);
        }

        if ($user->isAcademicTeacher()) {
            // Academic teachers go to profile page
            $subdomain = $academy ? $academy->subdomain : ($user->academy->subdomain ?? DefaultAcademy::subdomain());

            return redirect()->route('teacher.profile', ['subdomain' => $subdomain]);
        }

        if ($user->isSupervisor()) {
            // Supervisors go to supervisor dashboard
            $subdomain = $academy ? $academy->subdomain : ($user->academy->subdomain ?? DefaultAcademy::subdomain());

            return redirect()->route('supervisor.dashboard', ['subdomain' => $subdomain]);
        }

        // Students and parents go to profile page (no dashboard)
        if ($user->isStudent()) {
            // Get the subdomain from the user's academy
            $subdomain = $user->academy->subdomain ?? DefaultAcademy::subdomain();

            return redirect()->route('student.profile', ['subdomain' => $subdomain]);
        }

        if ($user->isParent()) {
            // Get the subdomain from the user's academy
            $subdomain = $user->academy->subdomain ?? DefaultAcademy::subdomain();

            return redirect()->route('parent.profile', ['subdomain' => $subdomain]);
        }

        // Fallback
        return redirect('/');
    }

    /**
     * Validate redirect URL to prevent open redirect attacks
     *
     * @param  string  $url  The URL to validate
     * @param  \Illuminate\Http\Request  $request  The current request
     * @return bool True if the URL is safe to redirect to
     */
    protected function isValidRedirectUrl(string $url, $request): bool
    {
        // Block dangerous protocols
        $blockedProtocols = ['javascript:', 'data:', 'vbscript:', 'file:'];
        $lowerUrl = strtolower(trim($url));
        foreach ($blockedProtocols as $protocol) {
            if (str_starts_with($lowerUrl, $protocol)) {
                return false;
            }
        }

        // Parse the URL
        $parsedUrl = parse_url($url);

        // Allow relative URLs (no host specified)
        if (! isset($parsedUrl['host'])) {
            // Ensure path doesn't start with // (protocol-relative URL)
            if (str_starts_with($url, '//')) {
                return false;
            }

            return true;
        }

        // In production, enforce HTTPS for absolute URLs to prevent downgrade attacks
        if (app()->isProduction()) {
            $scheme = $parsedUrl['scheme'] ?? 'http';
            if (strtolower($scheme) !== 'https') {
                return false;
            }
        }

        // For absolute URLs, validate the host matches
        $requestHost = $request->getHost();
        $baseDomain = config('app.domain', 'itqan-platform.test');

        // Allow same host
        if ($parsedUrl['host'] === $requestHost) {
            return true;
        }

        // Allow subdomains of the base domain
        if (str_ends_with($parsedUrl['host'], '.'.$baseDomain)) {
            return true;
        }

        return false;
    }
}
