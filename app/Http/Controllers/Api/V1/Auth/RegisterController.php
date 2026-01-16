<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\EducationalQualification;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Academy\AcademyBrandingResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicTeacherProfile;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    use ApiResponses;

    /**
     * Register a new student.
     */
    public function registerStudent(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'grade_level_id' => ['required', 'exists:academic_grade_levels,id'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Check if email already exists in this academy
        $existingUser = User::where('email', $request->email)
            ->where('academy_id', $academy->id)
            ->first();

        if ($existingUser) {
            return $this->error(
                __('An account with this email already exists in this academy.'),
                409,
                'EMAIL_EXISTS'
            );
        }

        return DB::transaction(function () use ($request, $academy) {
            // Create user - User model's boot() hook will auto-create a StudentProfile
            $user = User::create([
                'academy_id' => $academy->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'user_type' => 'student',
                'active_status' => true,
            ]);

            // Refresh to load the auto-created student profile
            $user->refresh();

            // Update the auto-created student profile with registration-specific data
            $user->studentProfile->update([
                'grade_level_id' => $request->grade_level_id,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'nationality' => $request->nationality,
                'parent_phone' => $request->parent_phone,
                'enrollment_date' => now(),
            ]);

            // Create token
            $token = $user->createToken(
                'mobile-app',
                ['read', 'write', 'student:*'],
                now()->addDays(30)
            );

            // Send email verification notification
            $user->sendEmailVerificationNotification();

            // Load relationships
            $user->load(['academy', 'studentProfile']);

            return $this->created([
                'user' => new UserResource($user),
                'academy' => new AcademyBrandingResource($academy),
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays(30)->toISOString(),
            ], __('Registration successful'));
        });
    }

    /**
     * Verify student code for parent registration.
     */
    public function verifyStudentCode(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $validator = Validator::make($request->all(), [
            'student_code' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Find student by code in this academy
        $studentProfile = StudentProfile::where('student_code', $request->student_code)
            ->whereHas('gradeLevel', function ($q) use ($academy) {
                $q->where('academy_id', $academy->id);
            })
            ->first();

        if (! $studentProfile) {
            return $this->error(
                __('Student code not found in this academy.'),
                404,
                'STUDENT_CODE_NOT_FOUND'
            );
        }

        // Check if student already has this parent linked
        $existingParent = ParentStudentRelationship::where('student_id', $studentProfile->id)->exists();

        return $this->success([
            'student' => [
                'id' => $studentProfile->id,
                'name' => $studentProfile->full_name ?? ($studentProfile->first_name.' '.$studentProfile->last_name),
                'student_code' => $studentProfile->student_code,
                'grade_level' => $studentProfile->gradeLevel?->name,
                'has_parent' => $existingParent,
            ],
        ], __('Student found'));
    }

    /**
     * Register a new parent.
     */
    public function registerParent(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
            'student_code' => ['required', 'string'],
            'relationship_type' => ['required', 'in:father,mother,guardian,other'],
            'preferred_contact_method' => ['sometimes', 'in:phone,email,whatsapp'],
            'occupation' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Check if email already exists in this academy
        $existingUser = User::where('email', $request->email)
            ->where('academy_id', $academy->id)
            ->first();

        if ($existingUser) {
            return $this->error(
                __('An account with this email already exists in this academy.'),
                409,
                'EMAIL_EXISTS'
            );
        }

        // Find student by code
        $studentProfile = StudentProfile::where('student_code', $request->student_code)
            ->whereHas('gradeLevel', function ($q) use ($academy) {
                $q->where('academy_id', $academy->id);
            })
            ->first();

        if (! $studentProfile) {
            return $this->error(
                __('Student code not found in this academy.'),
                404,
                'STUDENT_CODE_NOT_FOUND'
            );
        }

        return DB::transaction(function () use ($request, $academy, $studentProfile) {
            // Create user - User model's boot() hook will auto-create a ParentProfile
            $user = User::create([
                'academy_id' => $academy->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'user_type' => 'parent',
                'active_status' => true,
            ]);

            // Refresh to load the auto-created parent profile
            $user->refresh();

            // Update the auto-created parent profile with registration-specific data
            $parentProfile = $user->parentProfile;
            $parentProfile->update([
                'relationship_type' => $request->relationship_type,
                'preferred_contact_method' => $request->input('preferred_contact_method', 'phone'),
                'occupation' => $request->occupation,
            ]);

            // Link parent to student
            ParentStudentRelationship::create([
                'parent_id' => $parentProfile->id,
                'student_id' => $studentProfile->id,
                'relationship_type' => $request->relationship_type,
            ]);

            // Create token
            $token = $user->createToken(
                'mobile-app',
                ['read', 'write', 'parent:*'],
                now()->addDays(30)
            );

            // Send email verification notification
            $user->sendEmailVerificationNotification();

            // Load relationships
            $user->load(['academy', 'parentProfile']);

            return $this->created([
                'user' => new UserResource($user),
                'academy' => new AcademyBrandingResource($academy),
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays(30)->toISOString(),
                'linked_student' => [
                    'id' => $studentProfile->id,
                    'name' => $studentProfile->first_name.' '.$studentProfile->last_name,
                    'student_code' => $studentProfile->student_code,
                ],
            ], __('Registration successful'));
        });
    }

    /**
     * Teacher registration step 1 - Select teacher type.
     */
    public function teacherStep1(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'teacher_type' => ['required', 'in:quran_teacher,academic_teacher'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Store teacher type in a temporary token for step 2
        $tempToken = encrypt([
            'teacher_type' => $request->teacher_type,
            'created_at' => now()->toISOString(),
        ]);

        return $this->success([
            'teacher_type' => $request->teacher_type,
            'registration_token' => $tempToken,
            'next_step' => 'step2',
        ], __('Step 1 completed. Please proceed to step 2.'));
    }

    /**
     * Teacher registration step 2 - Complete registration.
     */
    public function teacherStep2(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        // Get teacher type - either from registration_token (step1) or directly from request
        $teacherType = null;
        $registrationToken = $request->input('registration_token');

        if ($registrationToken) {
            // Handle potential URL encoding issues (+ becomes space)
            $registrationToken = str_replace(' ', '+', $registrationToken);

            try {
                $tokenData = decrypt($registrationToken);
                $teacherType = $tokenData['teacher_type'] ?? null;

                // Check if token is expired (1 hour)
                $createdAt = \Carbon\Carbon::parse($tokenData['created_at']);
                if ($createdAt->diffInHours(now()) > 1) {
                    return $this->error(
                        __('Registration session expired. Please start over.'),
                        400,
                        'REGISTRATION_EXPIRED'
                    );
                }
            } catch (\Exception $e) {
                return $this->error(
                    __('Invalid registration token. Please start over.'),
                    400,
                    'INVALID_REGISTRATION_TOKEN'
                );
            }
        } else {
            // Allow direct teacher_type for mobile clients that skip step1
            $teacherType = $request->input('teacher_type');
        }

        // Validate teacher_type
        if (empty($teacherType) || ! in_array($teacherType, ['quran_teacher', 'academic_teacher'])) {
            return $this->error(
                __('Invalid teacher type. Must be quran_teacher or academic_teacher.'),
                400,
                'INVALID_TEACHER_TYPE'
            );
        }

        // Common validation rules
        $rules = [
            'registration_token' => ['nullable', 'string'],
            'teacher_type' => ['required_without:registration_token', 'in:quran_teacher,academic_teacher'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
            'education_level' => ['required', new Enum(EducationalQualification::class)],
            'university' => ['nullable', 'string', 'max:255'],
            'years_experience' => ['required', 'integer', 'min:0', 'max:50'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ];

        // Add academic-specific rules
        if ($teacherType === 'academic_teacher') {
            $rules['subject_ids'] = ['required', 'array', 'min:1'];
            $rules['subject_ids.*'] = ['exists:academic_subjects,id'];
            $rules['grade_level_ids'] = ['required', 'array', 'min:1'];
            $rules['grade_level_ids.*'] = ['exists:academic_grade_levels,id'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Check if email already exists in this academy
        $existingUser = User::where('email', $request->email)
            ->where('academy_id', $academy->id)
            ->first();

        if ($existingUser) {
            return $this->error(
                __('An account with this email already exists in this academy.'),
                409,
                'EMAIL_EXISTS'
            );
        }

        return DB::transaction(function () use ($request, $academy, $teacherType) {
            // Create user (inactive until approved)
            $user = User::create([
                'academy_id' => $academy->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'user_type' => $teacherType,
                'active_status' => false, // Requires admin approval
            ]);

            // Create teacher profile based on type
            if ($teacherType === 'quran_teacher') {
                QuranTeacherProfile::create([
                    'academy_id' => $academy->id,
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'educational_qualification' => $request->education_level,
                    'university' => $request->university,
                    'teaching_experience_years' => $request->years_experience,
                    'bio_arabic' => $request->bio,
                    'is_active' => false,
                    'approval_status' => 'pending',
                ]);
            } else {
                AcademicTeacherProfile::create([
                    'academy_id' => $academy->id,
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'education_level' => $request->education_level,
                    'university' => $request->university,
                    'teaching_experience_years' => $request->years_experience,
                    'subject_ids' => $request->subject_ids,
                    'grade_level_ids' => $request->grade_level_ids,
                    'bio_arabic' => $request->bio,
                    'is_active' => false,
                    'approval_status' => 'pending',
                ]);
            }

            // Send email verification notification (independent of admin approval)
            $user->sendEmailVerificationNotification();

            // Load relationships
            $user->load(['academy', 'quranTeacherProfile', 'academicTeacherProfile']);

            // Note: No token created as teacher needs admin approval first
            return $this->created([
                'user' => new UserResource($user),
                'academy' => new AcademyBrandingResource($academy),
                'requires_approval' => true,
                'message' => __('Your registration is pending approval. You will be notified once approved.'),
            ], __('Registration submitted successfully'));
        });
    }
}
