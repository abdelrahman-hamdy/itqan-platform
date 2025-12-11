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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
            'is_quran_teacher' => $user->isQuranTeacher(),
            'is_academic_teacher' => $user->isAcademicTeacher(),
        ];

        if ($user->isQuranTeacher() && $user->quranTeacherProfile) {
            $qp = $user->quranTeacherProfile;
            $profile['quran_profile'] = [
                'id' => $qp->id,
                'bio' => $qp->bio,
                'bio_arabic' => $qp->bio_arabic,
                'bio_english' => $qp->bio_english,
                'qualifications' => $qp->qualifications,
                'certifications' => $qp->certifications ?? [],
                'specializations' => $qp->specializations ?? [],
                'teaching_style' => $qp->teaching_style,
                'years_of_experience' => $qp->years_of_experience,
                'hourly_rate' => $qp->hourly_rate,
                'currency' => $qp->currency ?? 'SAR',
                'availability' => $qp->availability ?? [],
                'rating' => round($qp->rating ?? 0, 1),
                'total_reviews' => $qp->total_reviews ?? 0,
                'total_students' => $qp->total_students ?? 0,
                'is_available' => $qp->is_available ?? true,
                'status' => $qp->status,
            ];
        }

        if ($user->isAcademicTeacher() && $user->academicTeacherProfile) {
            $ap = $user->academicTeacherProfile;
            $profile['academic_profile'] = [
                'id' => $ap->id,
                'bio' => $ap->bio,
                'bio_arabic' => $ap->bio_arabic,
                'bio_english' => $ap->bio_english,
                'qualifications' => $ap->qualifications,
                'certifications' => $ap->certifications ?? [],
                'subjects' => $ap->subjects ?? [],
                'grade_levels' => $ap->grade_levels ?? [],
                'teaching_methodology' => $ap->teaching_methodology,
                'years_of_experience' => $ap->years_of_experience,
                'hourly_rate' => $ap->hourly_rate,
                'currency' => $ap->currency ?? 'SAR',
                'availability' => $ap->availability ?? [],
                'rating' => round($ap->rating ?? 0, 1),
                'total_reviews' => $ap->total_reviews ?? 0,
                'total_students' => $ap->total_students ?? 0,
                'is_available' => $ap->is_available ?? true,
                'status' => $ap->status,
            ];
        }

        return $this->success([
            'profile' => $profile,
        ], __('Profile retrieved successfully'));
    }

    /**
     * Update teacher profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            // User fields
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],

            // Quran teacher fields
            'quran_bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'quran_bio_arabic' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'quran_bio_english' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'quran_qualifications' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'quran_certifications' => ['sometimes', 'array'],
            'quran_specializations' => ['sometimes', 'array'],
            'quran_teaching_style' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'quran_availability' => ['sometimes', 'array'],
            'quran_is_available' => ['sometimes', 'boolean'],

            // Academic teacher fields
            'academic_bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'academic_bio_arabic' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'academic_bio_english' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'academic_qualifications' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'academic_certifications' => ['sometimes', 'array'],
            'academic_subjects' => ['sometimes', 'array'],
            'academic_grade_levels' => ['sometimes', 'array'],
            'academic_teaching_methodology' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'academic_availability' => ['sometimes', 'array'],
            'academic_is_available' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $data = $validator->validated();

        // Update user
        $userUpdates = array_filter([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        if (!empty($userUpdates)) {
            $user->update($userUpdates);
        }

        // Update Quran profile
        if ($user->isQuranTeacher() && $user->quranTeacherProfile) {
            $quranUpdates = [];

            foreach ([
                'quran_bio' => 'bio',
                'quran_bio_arabic' => 'bio_arabic',
                'quran_bio_english' => 'bio_english',
                'quran_qualifications' => 'qualifications',
                'quran_certifications' => 'certifications',
                'quran_specializations' => 'specializations',
                'quran_teaching_style' => 'teaching_style',
                'quran_availability' => 'availability',
                'quran_is_available' => 'is_available',
            ] as $requestKey => $dbKey) {
                if (isset($data[$requestKey])) {
                    $quranUpdates[$dbKey] = $data[$requestKey];
                }
            }

            if (!empty($quranUpdates)) {
                $user->quranTeacherProfile->update($quranUpdates);
            }
        }

        // Update Academic profile
        if ($user->isAcademicTeacher() && $user->academicTeacherProfile) {
            $academicUpdates = [];

            foreach ([
                'academic_bio' => 'bio',
                'academic_bio_arabic' => 'bio_arabic',
                'academic_bio_english' => 'bio_english',
                'academic_qualifications' => 'qualifications',
                'academic_certifications' => 'certifications',
                'academic_subjects' => 'subjects',
                'academic_grade_levels' => 'grade_levels',
                'academic_teaching_methodology' => 'teaching_methodology',
                'academic_availability' => 'availability',
                'academic_is_available' => 'is_available',
            ] as $requestKey => $dbKey) {
                if (isset($data[$requestKey])) {
                    $academicUpdates[$dbKey] = $data[$requestKey];
                }
            }

            if (!empty($academicUpdates)) {
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
     *
     * @param Request $request
     * @return JsonResponse
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
            'avatar' => asset('storage/' . $path),
        ], __('Avatar updated successfully'));
    }

    /**
     * Change password.
     *
     * @param Request $request
     * @return JsonResponse
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
        if (!Hash::check($request->current_password, $user->password)) {
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
