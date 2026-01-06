<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Models\Academy;
use App\Models\ParentProfile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AcademyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ParentRegistrationController extends Controller
{
    use ApiResponses;

    public function showRegistrationForm(): \Illuminate\View\View
    {
        $academyContextService = app(AcademyContextService::class);
        $academy = $academyContextService->getCurrentAcademy();

        return view('auth.parent-register', compact('academy'));
    }

    /**
     * Verify student codes by parent phone number
     * API endpoint for real-time verification during registration
     */
    public function verifyStudentCodes(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'parent_phone' => 'required|string',
            'parent_phone_country_code' => 'required|string',
            'student_codes' => 'required|string', // Comma-separated student codes
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $academyContextService = app(AcademyContextService::class);
        $academyId = $academyContextService->getCurrentAcademyId();

        $studentCodes = array_map('trim', explode(',', $request->student_codes));
        $parentPhone = $request->parent_phone;
        $countryCode = $request->parent_phone_country_code;

        // Normalize phone number - create full E164 format
        $fullPhoneNumber = $countryCode.$parentPhone;

        // Find students matching the codes and parent phone
        // Compare against full phone number (E164 format) or separate fields
        $students = StudentProfile::whereHas('gradeLevel', function ($query) use ($academyId) {
            $query->where('academy_id', $academyId);
        })
            ->whereIn('student_code', $studentCodes)
            ->where(function ($query) use ($fullPhoneNumber, $parentPhone, $countryCode) {
                // Match either full E164 format or separate fields
                $query->where('parent_phone', $fullPhoneNumber)
                    ->orWhere(function ($q) use ($parentPhone, $countryCode) {
                        $q->where('parent_phone', $parentPhone)
                            ->where('parent_phone_country_code', $countryCode);
                    });
            })
            ->get();

        $verified = [];
        $unverified = [];
        $alreadyHasParent = [];

        foreach ($studentCodes as $code) {
            $student = $students->firstWhere('student_code', $code);
            if ($student) {
                // Check if student already has a parent account using Eloquent relationship
                $hasParent = $student->parent_id !== null ||
                             $student->parentProfiles()->exists();

                if ($hasParent) {
                    $alreadyHasParent[] = [
                        'code' => $code,
                        'name' => $student->full_name,
                        'grade' => $student->gradeLevel?->getDisplayName() ?? 'N/A',
                    ];
                } else {
                    $verified[] = [
                        'code' => $code,
                        'name' => $student->full_name,
                        'grade' => $student->gradeLevel?->getDisplayName() ?? 'N/A',
                        'id' => $student->id,
                    ];
                }
            } else {
                $unverified[] = $code;
            }
        }

        // Build appropriate message
        $message = '';
        if (count($verified) > 0) {
            $message = 'تم التحقق من '.count($verified).' طالب/طالبة بنجاح';
        }
        if (count($alreadyHasParent) > 0) {
            $message .= ($message ? '. ' : '').count($alreadyHasParent).' طالب/طالبة لديهم حساب ولي أمر بالفعل';
        }
        if (count($unverified) > 0) {
            $message .= ($message ? '. ' : '').'لم يتم العثور على '.count($unverified).' طالب/طالبة';
        }
        if (! $message) {
            $message = 'لم يتم العثور على أي طلاب يطابقون الرموز المدخلة ورقم الهاتف';
        }

        return $this->success([
            'verified' => $verified,
            'unverified' => $unverified,
            'already_has_parent' => $alreadyHasParent,
            'message' => $message,
        ], count($verified) > 0); // Only success if at least one verified
    }

    /**
     * Register a new parent account
     */
    public function register(Request $request): \Illuminate\Http\RedirectResponse
    {
        $academyContextService = app(AcademyContextService::class);
        $academyId = $academyContextService->getCurrentAcademyId();

        // Pre-validate and store verified students in session for form repopulation
        $studentCodes = array_map('trim', explode(',', $request->student_codes));
        $fullPhoneNumber = $request->parent_phone_country_code.$request->parent_phone;

        $students = StudentProfile::whereHas('gradeLevel', function ($query) use ($academyId) {
            $query->where('academy_id', $academyId);
        })
            ->whereIn('student_code', $studentCodes)
            ->where(function ($query) use ($fullPhoneNumber, $request) {
                $query->where('parent_phone', $fullPhoneNumber)
                    ->orWhere(function ($q) use ($request) {
                        $q->where('parent_phone', $request->parent_phone)
                            ->where('parent_phone_country_code', $request->parent_phone_country_code);
                    });
            })
            ->get();

        // Build verified students array (only include students without existing parent)
        $verifiedStudents = [];
        foreach ($students as $student) {
            $hasParent = $student->parent_id !== null ||
                        $student->parentProfiles()->exists();

            $verifiedStudents[] = [
                'code' => $student->student_code,
                'name' => $student->full_name,
                'grade' => $student->gradeLevel?->getDisplayName() ?? 'N/A',
                'id' => $student->id,
                'has_parent' => $hasParent,
            ];
        }

        // Store verified students in session for form repopulation if validation fails
        session(['verified_students' => $verifiedStudents]);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', function ($attribute, $value, $fail) use ($academyId) {
                // Check parent_profiles for this academy (scoped by academy_id)
                $parentProfileExists = ParentProfile::where('email', $value)
                    ->where('academy_id', $academyId)
                    ->exists();

                // Check users table for this academy (scoped by academy_id)
                // With composite unique constraint (email, academy_id), same email can exist in different academies
                $userExists = User::where('email', $value)
                    ->where('academy_id', $academyId)
                    ->exists();

                if ($parentProfileExists || $userExists) {
                    $fail('البريد الإلكتروني مسجل بالفعل في هذه الأكاديمية');
                }
            }],
            'parent_phone' => 'required|string|max:20',
            'parent_phone_country_code' => 'required|string|max:5',
            'parent_phone_country' => 'required|string|max:2',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'student_codes' => 'required|string', // Comma-separated student codes
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
        ], [
            // Arabic validation messages
            'first_name.required' => 'الاسم الأول مطلوب',
            'first_name.string' => 'الاسم الأول يجب أن يكون نصاً',
            'first_name.max' => 'الاسم الأول يجب ألا يتجاوز 255 حرفاً',

            'last_name.required' => 'اسم العائلة مطلوب',
            'last_name.string' => 'اسم العائلة يجب أن يكون نصاً',
            'last_name.max' => 'اسم العائلة يجب ألا يتجاوز 255 حرفاً',

            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صالح',
            'email.unique' => 'البريد الإلكتروني مسجل بالفعل',

            'parent_phone.required' => 'رقم الهاتف مطلوب',
            'parent_phone.string' => 'رقم الهاتف يجب أن يكون نصاً',
            'parent_phone.max' => 'رقم الهاتف يجب ألا يتجاوز 20 رقماً',

            'parent_phone_country_code.required' => 'رمز الدولة مطلوب',
            'parent_phone_country.required' => 'رمز الدولة مطلوب',

            'password.required' => 'كلمة المرور مطلوبة',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل مع أحرف كبيرة وصغيرة وأرقام',

            'student_codes.required' => 'رموز الطلاب مطلوبة',

            'occupation.max' => 'المهنة يجب ألا تتجاوز 255 حرفاً',
            'address.max' => 'العنوان يجب ألا يتجاوز 500 حرف',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check if students were found
        if ($students->isEmpty()) {
            return back()
                ->withErrors(['student_codes' => 'لم يتم العثور على أي طلاب يطابقون الرموز المدخلة ورقم الهاتف.'])
                ->withInput();
        }

        // Filter out students that already have a parent account using Eloquent relationship
        $studentsWithoutParent = $students->filter(function ($student) {
            return $student->parent_id === null &&
                   ! $student->parentProfiles()->exists();
        });

        // Check if all students already have parents using Eloquent relationship
        if ($studentsWithoutParent->isEmpty()) {
            $studentsWithParent = $students->filter(function ($student) {
                return $student->parent_id !== null ||
                       $student->parentProfiles()->exists();
            });

            $errorMessage = 'جميع الطلاب المدخلين لديهم حساب ولي أمر بالفعل. الطلاب: '.
                          $studentsWithParent->pluck('full_name')->implode('، ');

            return back()
                ->withErrors(['student_codes' => $errorMessage])
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Create user account FIRST
            // The User model boot() hook will automatically create the ParentProfile
            $user = User::create([
                'academy_id' => $academyId,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->parent_phone, // Use parent_phone from verification step
                'password' => Hash::make($request->password),
                'user_type' => 'parent',
                // Note: Email verification is now required - removed auto-verification
                'active_status' => true, // Parents are automatically active upon registration
            ]);

            // Refresh user to load relationships created by boot() hook
            $user->refresh();

            // Get the automatically created parent profile
            $parentProfile = $user->parentProfile;

            if (! $parentProfile) {
                // Fallback: manually create if boot() hook didn't work
                $parentProfile = ParentProfile::create([
                    'user_id' => $user->id,
                    'academy_id' => $academyId,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->parent_phone,
                    'relationship_type' => 'father', // Default
                    'preferred_contact_method' => 'phone', // Default
                ]);
            }

            // Update parent profile with additional fields (occupation, address)
            $parentProfile->update([
                'occupation' => $request->occupation,
                'address' => $request->address,
            ]);

            // Auto-link only students WITHOUT existing parent to this parent
            // NOTE: The StudentProfileObserver::updated() automatically handles the
            // many-to-many relationship (parent_student_relationships) when parent_id changes.
            // We only need to update parent_id here - the observer does the rest.
            foreach ($studentsWithoutParent as $student) {
                // Update direct parent_id relationship
                // This triggers StudentProfileObserver which auto-syncs the pivot table
                $student->update(['parent_id' => $parentProfile->id]);
            }

            DB::commit();

            // Log the parent in
            auth()->login($user);

            // Send email verification notification
            $user->sendEmailVerificationNotification();

            return redirect()->route('parent.profile')
                ->with('success', 'تم إنشاء حسابك بنجاح! مرحباً بك في المنصة.');

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error('Parent registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Check if it's a duplicate email error (composite unique constraints)
            if ($e->getCode() == 23000 && (
                str_contains($e->getMessage(), 'parent_profiles_email_academy_unique') ||
                str_contains($e->getMessage(), 'users_email_academy_unique')
            )) {
                return back()
                    ->withErrors(['email' => 'هذا البريد الإلكتروني مسجل بالفعل في هذه الأكاديمية. يرجى استخدام بريد إلكتروني آخر.'])
                    ->withInput();
            }

            // Check if it's a duplicate parent-student relationship error
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'parent_student_relationships')) {
                return back()
                    ->withErrors(['student_codes' => 'أحد الطلاب مرتبط بالفعل بحساب ولي أمر. يرجى التحقق من رموز الطلاب.'])
                    ->withInput();
            }

            return back()
                ->withErrors(['error' => 'حدث خطأ أثناء إنشاء الحساب. يرجى المحاولة مرة أخرى.'])
                ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Parent registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['error' => 'حدث خطأ أثناء إنشاء الحساب. يرجى المحاولة مرة أخرى.'])
                ->withInput();
        }
    }
}
