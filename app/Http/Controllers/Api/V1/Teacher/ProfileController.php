<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    use ApiResponses;

    /**
     * Get teacher profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            'is_quran_teacher' => $user->isQuranTeacher(),
            'is_academic_teacher' => $user->isAcademicTeacher(),
        ];

        if ($user->isQuranTeacher() && $user->quranTeacherProfile) {
            $qp = $user->quranTeacherProfile;
            $profile['quran_profile'] = [
                'id' => $qp->id,
                'teacher_code' => $qp->teacher_code,
                'educational_qualification' => $qp->educational_qualification,
                'certifications' => $qp->certifications ?? [],
                'teaching_experience_years' => $qp->teaching_experience_years,
                'languages' => $qp->languages ?? [],
                'available_days' => $qp->available_days ?? [],
                'available_time_start' => $qp->available_time_start ? $qp->available_time_start->format('H:i') : null,
                'available_time_end' => $qp->available_time_end ? $qp->available_time_end->format('H:i') : null,
                'bio_arabic' => $qp->bio_arabic,
                'bio_english' => $qp->bio_english,
                'rating' => round($qp->rating ?? 0, 1),
                'total_reviews' => $qp->total_reviews ?? 0,
                'total_students' => $qp->total_students ?? 0,
                'is_active' => $qp->is_active ?? true,
            ];
        }

        if ($user->isAcademicTeacher() && $user->academicTeacherProfile) {
            $ap = $user->academicTeacherProfile;
            $profile['academic_profile'] = [
                'id' => $ap->id,
                'teacher_code' => $ap->teacher_code,
                'education_level' => $ap->education_level?->value,
                'university' => $ap->university,
                'certifications' => $ap->certifications ?? [],
                'teaching_experience_years' => $ap->teaching_experience_years,
                'languages' => $ap->languages ?? [],
                'available_days' => $ap->available_days ?? [],
                'available_time_start' => $ap->available_time_start ? $ap->available_time_start->format('H:i') : null,
                'available_time_end' => $ap->available_time_end ? $ap->available_time_end->format('H:i') : null,
                'bio_arabic' => $ap->bio_arabic,
                'bio_english' => $ap->bio_english,
                'rating' => round($ap->rating ?? 0, 1),
                'total_reviews' => $ap->total_reviews ?? 0,
                'is_active' => $ap->is_active ?? true,
            ];
        }

        return $this->success([
            'profile' => $profile,
        ], __('Profile retrieved successfully'));
    }

    /**
     * Update teacher profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            // User fields
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'gender' => ['sometimes', 'nullable', 'string', 'in:male,female'],

            // Quran teacher fields
            'quran_educational_qualification' => ['sometimes', 'nullable', 'string', 'max:255'],
            'quran_certifications' => ['sometimes', 'array'],
            'quran_teaching_experience_years' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'quran_languages' => ['sometimes', 'array'],
            'quran_available_days' => ['sometimes', 'array'],
            'quran_available_time_start' => ['sometimes', 'nullable', 'date_format:H:i'],
            'quran_available_time_end' => ['sometimes', 'nullable', 'date_format:H:i'],
            'quran_bio_arabic' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'quran_bio_english' => ['sometimes', 'nullable', 'string', 'max:2000'],

            // Academic teacher fields
            'academic_education_level' => ['sometimes', 'nullable', 'string', 'max:255'],
            'academic_university' => ['sometimes', 'nullable', 'string', 'max:255'],
            'academic_certifications' => ['sometimes', 'array'],
            'academic_teaching_experience_years' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'academic_languages' => ['sometimes', 'array'],
            'academic_available_days' => ['sometimes', 'array'],
            'academic_available_time_start' => ['sometimes', 'nullable', 'date_format:H:i'],
            'academic_available_time_end' => ['sometimes', 'nullable', 'date_format:H:i'],
            'academic_bio_arabic' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'academic_bio_english' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $data = $validator->validated();

        // Debug: Log received data
        \Log::info('Teacher Profile Update - Received data', [
            'user_id' => $user->id,
            'quran_bio_arabic' => $data['quran_bio_arabic'] ?? 'NOT_SET',
            'quran_bio_english' => $data['quran_bio_english'] ?? 'NOT_SET',
            'all_data' => $data,
        ]);

        // Update user fields
        $userUpdates = [];
        if (isset($data['first_name'])) {
            $userUpdates['first_name'] = $data['first_name'];
        }
        if (isset($data['last_name'])) {
            $userUpdates['last_name'] = $data['last_name'];
        }
        if (isset($data['phone'])) {
            $userUpdates['phone'] = $data['phone'];
        }
        if (isset($data['gender'])) {
            $userUpdates['gender'] = $data['gender'];
        }

        if (! empty($userUpdates)) {
            $user->update($userUpdates);
        }

        // Update Quran profile
        if ($user->isQuranTeacher() && $user->quranTeacherProfile) {
            $quranUpdates = [];

            foreach ([
                'quran_educational_qualification' => 'educational_qualification',
                'quran_certifications' => 'certifications',
                'quran_teaching_experience_years' => 'teaching_experience_years',
                'quran_languages' => 'languages',
                'quran_available_days' => 'available_days',
                'quran_available_time_start' => 'available_time_start',
                'quran_available_time_end' => 'available_time_end',
                'quran_bio_arabic' => 'bio_arabic',
                'quran_bio_english' => 'bio_english',
            ] as $requestKey => $dbKey) {
                if (isset($data[$requestKey])) {
                    $quranUpdates[$dbKey] = $data[$requestKey];
                }
            }

            if (! empty($quranUpdates)) {
                \Log::info('Teacher Profile Update - Quran updates to apply', [
                    'user_id' => $user->id,
                    'quran_updates' => $quranUpdates,
                ]);
                $user->quranTeacherProfile->update($quranUpdates);

                // Verify the update
                $user->quranTeacherProfile->refresh();
                \Log::info('Teacher Profile Update - After save', [
                    'user_id' => $user->id,
                    'bio_arabic' => $user->quranTeacherProfile->bio_arabic,
                    'bio_english' => $user->quranTeacherProfile->bio_english,
                ]);
            }
        }

        // Update Academic profile
        if ($user->isAcademicTeacher() && $user->academicTeacherProfile) {
            $academicUpdates = [];

            foreach ([
                'academic_education_level' => 'education_level',
                'academic_university' => 'university',
                'academic_certifications' => 'certifications',
                'academic_teaching_experience_years' => 'teaching_experience_years',
                'academic_languages' => 'languages',
                'academic_available_days' => 'available_days',
                'academic_available_time_start' => 'available_time_start',
                'academic_available_time_end' => 'available_time_end',
                'academic_bio_arabic' => 'bio_arabic',
                'academic_bio_english' => 'bio_english',
            ] as $requestKey => $dbKey) {
                if (isset($data[$requestKey])) {
                    $academicUpdates[$dbKey] = $data[$requestKey];
                }
            }

            if (! empty($academicUpdates)) {
                $user->academicTeacherProfile->update($academicUpdates);
            }
        }

        // Reload user
        $user->refresh();

        return $this->success([
            'message' => __('Profile updated successfully'),
        ], __('Profile updated successfully'));
    }

    /**
     * Update avatar.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Delete old avatar
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        return $this->success([
            'avatar' => asset('storage/'.$path),
        ], __('Avatar updated successfully'));
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Verify current password
        if (! Hash::check($request->current_password, $user->password)) {
            return $this->error(
                __('Current password is incorrect.'),
                422,
                'INVALID_CURRENT_PASSWORD'
            );
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->success([
            'message' => __('Password changed successfully'),
        ], __('Password changed successfully'));
    }
}
