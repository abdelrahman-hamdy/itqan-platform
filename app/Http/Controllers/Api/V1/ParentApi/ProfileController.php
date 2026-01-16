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

class ProfileController extends Controller
{
    use ApiResponses;

    /**
     * Get parent profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        return $this->success([
            'profile' => [
                'id' => $parentProfile->id,
                'user_id' => $user->id,
                'first_name' => $parentProfile->first_name,
                'last_name' => $parentProfile->last_name,
                'full_name' => $parentProfile->first_name.' '.$parentProfile->last_name,
                'email' => $user->email,
                'phone' => $parentProfile->phone ?? $user->phone,
                'secondary_phone' => $parentProfile->secondary_phone,
                'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
                'parent_code' => $parentProfile->parent_code,
                'occupation' => $parentProfile->occupation,
                'relationship_type' => $parentProfile->relationship_type?->value,
                'address' => $parentProfile->address,
                'preferred_contact_method' => $parentProfile->preferred_contact_method,
                'children_count' => $parentProfile->students()->count(),
                'created_at' => $parentProfile->created_at->toISOString(),
            ],
        ], __('Profile retrieved successfully'));
    }

    /**
     * Update parent profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'secondary_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'occupation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'relationship_type' => ['sometimes', 'nullable', 'string', 'in:father,mother,other'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'preferred_contact_method' => ['sometimes', 'nullable', 'string', 'in:phone,email,sms,whatsapp'],
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
            'secondary_phone',
            'occupation',
            'relationship_type',
            'address',
            'preferred_contact_method',
        ]));

        if (! empty($profileData)) {
            $parentProfile->update($profileData);
        }

        // Update user name if first/last name changed
        if (isset($data['first_name']) || isset($data['last_name'])) {
            $user->update([
                'name' => ($data['first_name'] ?? $parentProfile->first_name).' '.
                         ($data['last_name'] ?? $parentProfile->last_name),
            ]);
        }

        // Update phone on user if provided
        if (isset($data['phone'])) {
            $user->update(['phone' => $data['phone']]);
        }

        // Reload parent profile
        $parentProfile->refresh();

        return $this->success([
            'profile' => [
                'id' => $parentProfile->id,
                'first_name' => $parentProfile->first_name,
                'last_name' => $parentProfile->last_name,
                'full_name' => $parentProfile->first_name.' '.$parentProfile->last_name,
                'email' => $user->email,
                'phone' => $parentProfile->phone ?? $user->phone,
                'secondary_phone' => $parentProfile->secondary_phone,
                'occupation' => $parentProfile->occupation,
                'relationship_type' => $parentProfile->relationship_type?->value,
                'address' => $parentProfile->address,
                'preferred_contact_method' => $parentProfile->preferred_contact_method,
            ],
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

        // Revoke other tokens (optional - keep current session)
        // $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return $this->success([
            'message' => __('Password changed successfully'),
        ], __('Password changed successfully'));
    }

    /**
     * Delete account (soft delete).
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
        if (! Hash::check($request->password, $user->password)) {
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
