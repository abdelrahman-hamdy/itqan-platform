<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Rules\PasswordRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    use ApiResponses;

    /**
     * Get student profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->studentProfile()->first();

        if (! $profile) {
            return $this->notFound(__('Student profile not found.'));
        }

        // Load related data
        $profile->load(['gradeLevel', 'parentProfiles']);

        return $this->success([
            'profile' => [
                'id' => $profile->id,
                'user_id' => $user->id,
                'student_code' => $profile->student_code,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'full_name' => $profile->full_name,
                'email' => $profile->email ?? $user->email,
                'phone' => $profile->phone ?? $user->phone,
                'avatar' => $profile->avatar ? asset('storage/'.$profile->avatar) : null,
                'birth_date' => $profile->birth_date?->toDateString(),
                'age' => $profile->birth_date ? $profile->birth_date->age : null,
                'gender' => $profile->gender,
                'nationality' => $profile->nationality,
                'address' => $profile->address,
                'grade_level' => $profile->gradeLevel ? [
                    'id' => $profile->gradeLevel->id,
                    'name' => $profile->gradeLevel->getDisplayName(),
                ] : null,
                'enrollment_date' => $profile->enrollment_date?->toDateString(),
                'parent_phone' => $profile->parent_phone,
                'emergency_contact' => $profile->emergency_contact,
                'parents' => $profile->parentProfiles->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->first_name.' '.$p->last_name,
                    'relationship' => $p->pivot->relationship_type ?? 'guardian',
                    'phone' => $p->phone,
                ])->toArray(),
            ],
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'user_type' => $user->user_type,
                'active_status' => $user->active_status,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
            ],
        ], __('Profile retrieved successfully'));
    }

    /**
     * Update student profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->studentProfile()->first();

        if (! $profile) {
            return $this->notFound(__('Student profile not found.'));
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'birth_date' => ['sometimes', 'date', 'before:today'],
            'date_of_birth' => ['sometimes', 'date', 'before:today'], // Mobile app sends this
            'gender' => ['sometimes', 'in:male,female'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'grade_level_id' => ['sometimes', 'nullable', 'integer', 'exists:academic_grade_levels,id'],
            'parent_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'emergency_contact' => ['sometimes', 'nullable', 'string', 'max:255'],
            'current_password' => ['required_with:new_password', 'string'],
            'new_password' => ['sometimes', 'string', 'confirmed', PasswordRules::rule()],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Update password if provided
        if ($request->filled('new_password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return $this->error(
                    __('Current password is incorrect.'),
                    422,
                    'INVALID_CURRENT_PASSWORD'
                );
            }

            $user->update([
                'password' => Hash::make($request->new_password),
            ]);
        }

        // Update profile fields
        $profileData = $request->only([
            'first_name',
            'last_name',
            'phone',
            'birth_date',
            'gender',
            'nationality',
            'address',
            'grade_level_id',
            'parent_phone',
            'emergency_contact',
        ]);

        // Handle date_of_birth from mobile app (maps to birth_date)
        if ($request->filled('date_of_birth') && ! $request->filled('birth_date')) {
            $profileData['birth_date'] = $request->date_of_birth;
        }

        if (! empty($profileData)) {
            $profile->update($profileData);
        }

        // Update user fields
        $userData = [];
        if ($request->filled('first_name') || $request->filled('last_name')) {
            $userData['first_name'] = $request->first_name ?? $profile->first_name;
            $userData['last_name'] = $request->last_name ?? $profile->last_name;
        }
        if ($request->filled('phone')) {
            $userData['phone'] = $request->phone;
        }

        if (! empty($userData)) {
            $user->update($userData);
        }

        // Refresh and return updated profile data
        $profile->refresh();
        $profile->load(['gradeLevel', 'parentProfiles']);

        return $this->success([
            'profile' => [
                'id' => $profile->id,
                'user_id' => $user->id,
                'student_code' => $profile->student_code,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'full_name' => $profile->full_name,
                'email' => $profile->email ?? $user->email,
                'phone' => $profile->phone ?? $user->phone,
                'avatar' => $profile->avatar ? asset('storage/'.$profile->avatar) : null,
                'birth_date' => $profile->birth_date?->toDateString(),
                'age' => $profile->birth_date ? $profile->birth_date->age : null,
                'gender' => $profile->gender,
                'nationality' => $profile->nationality,
                'address' => $profile->address,
                'grade_level' => $profile->gradeLevel ? [
                    'id' => $profile->gradeLevel->id,
                    'name' => $profile->gradeLevel->getDisplayName(),
                ] : null,
                'enrollment_date' => $profile->enrollment_date?->toDateString(),
                'parent_phone' => $profile->parent_phone,
                'emergency_contact' => $profile->emergency_contact,
                'parents' => $profile->parentProfiles->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->first_name.' '.$p->last_name,
                    'relationship' => $p->pivot->relationship_type ?? 'guardian',
                    'phone' => $p->phone,
                ])->toArray(),
            ],
        ], __('Profile updated successfully'));
    }

    /**
     * Update student avatar.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $profile = $user->studentProfile()->first();

        if (! $profile) {
            return $this->notFound(__('Student profile not found.'));
        }

        // Delete old avatar if exists
        if ($profile->avatar && Storage::disk('public')->exists($profile->avatar)) {
            Storage::disk('public')->delete($profile->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars/students', 'public');

        $profile->update(['avatar' => $path]);

        // Also update user avatar
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->update(['avatar' => $path]);

        return $this->success([
            'avatar' => asset('storage/'.$path),
        ], __('Avatar updated successfully'));
    }

    /**
     * Change student password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed', PasswordRules::rule()],
        ], [
            'current_password.required' => __('Current password is required.'),
            ...PasswordRules::messagesEn('new_password'),
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();

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
            'password' => Hash::make($request->new_password),
        ]);

        return $this->success(null, __('Password changed successfully.'));
    }

    /**
     * Delete user account (soft delete).
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:DELETE,حذف'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        if (! Hash::check($request->password, $user->password)) {
            return $this->error(
                __('Password is incorrect.'),
                422,
                'INVALID_PASSWORD'
            );
        }

        $user->tokens()->delete();
        $user->delete();

        return $this->success([
            'deleted' => true,
        ], __('Account deleted successfully'));
    }
}
