<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Enums\SessionStatus;

class ProfileController extends Controller
{
    use ApiResponses;

    /**
     * Get parent profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        return $this->success([
            'profile' => [
                'id' => $parentProfile->id,
                'user_id' => $user->id,
                'first_name' => $parentProfile->first_name,
                'last_name' => $parentProfile->last_name,
                'full_name' => $parentProfile->first_name . ' ' . $parentProfile->last_name,
                'email' => $user->email,
                'phone' => $parentProfile->phone ?? $user->phone,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'address' => $parentProfile->address,
                'city' => $parentProfile->city,
                'country' => $parentProfile->country,
                'nationality' => $parentProfile->nationality,
                'preferred_language' => $parentProfile->preferred_language ?? 'ar',
                'notification_preferences' => $parentProfile->notification_preferences ?? [
                    'email' => true,
                    'sms' => true,
                    'push' => true,
                ],
                'created_at' => $parentProfile->created_at->toISOString(),
            ],
        ], __('Profile retrieved successfully'));
    }

    /**
     * Update parent profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:100'],
            'preferred_language' => ['sometimes', 'string', 'in:ar,en'],
            'notification_preferences' => ['sometimes', 'array'],
            'notification_preferences.email' => ['sometimes', 'boolean'],
            'notification_preferences.sms' => ['sometimes', 'boolean'],
            'notification_preferences.push' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $data = $validator->validated();

        // Update parent profile
        $profileData = array_intersect_key($data, array_flip([
            'first_name',
            'last_name',
            'phone',
            'address',
            'city',
            'country',
            'nationality',
            'preferred_language',
            'notification_preferences',
        ]));

        if (!empty($profileData)) {
            $parentProfile->update($profileData);
        }

        // Update user name if first/last name changed
        if (isset($data['first_name']) || isset($data['last_name'])) {
            $user->update([
                'name' => ($data['first_name'] ?? $parentProfile->first_name) . ' ' .
                         ($data['last_name'] ?? $parentProfile->last_name),
            ]);
        }

        // Update phone on user if provided
        if (isset($data['phone'])) {
            $user->update(['phone' => $data['phone']]);
        }

        return $this->success([
            'profile' => [
                'id' => $parentProfile->id,
                'first_name' => $parentProfile->first_name,
                'last_name' => $parentProfile->last_name,
                'full_name' => $parentProfile->first_name . ' ' . $parentProfile->last_name,
                'email' => $user->email,
                'phone' => $parentProfile->phone ?? $user->phone,
                'address' => $parentProfile->address,
                'city' => $parentProfile->city,
                'country' => $parentProfile->country,
                'nationality' => $parentProfile->nationality,
                'preferred_language' => $parentProfile->preferred_language,
                'notification_preferences' => $parentProfile->notification_preferences,
            ],
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

        // Revoke other tokens (optional - keep current session)
        // $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return $this->success([
            'message' => __('Password changed successfully'),
        ], __('Password changed successfully'));
    }

    /**
     * Delete account (soft delete).
     *
     * @param Request $request
     * @return JsonResponse
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

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return $this->error(
                __('Password is incorrect.'),
                422,
                'INVALID_PASSWORD'
            );
        }

        // Revoke all tokens
        $user->tokens()->delete();

        // Soft delete user
        $user->delete();

        return $this->success([
            'deleted' => true,
        ], __('Account deleted successfully'));
    }
}
