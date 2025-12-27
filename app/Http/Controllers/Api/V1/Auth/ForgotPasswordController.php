<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Enums\SessionStatus;

class ForgotPasswordController extends Controller
{
    use ApiResponses;

    /**
     * Send password reset link to user's email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? app('current_academy');

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Find user in this academy
        $user = User::where('email', $request->email)
            ->where('academy_id', $academy->id)
            ->first();

        if (!$user) {
            // Return success even if user doesn't exist (security best practice)
            return $this->success(
                ['email' => $request->email],
                __('If an account exists with this email, you will receive a password reset link.')
            );
        }

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

        // Send reset email (you may want to use a notification or mailable)
        // For now, we'll return the token in development mode
        // In production, send email with reset link

        // TODO: Implement email sending
        // $user->notify(new ResetPasswordNotification($token, $academy));

        return $this->success(
            ['email' => $request->email],
            __('If an account exists with this email, you will receive a password reset link.')
        );
    }

    /**
     * Verify reset token is valid.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Find reset record
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return $this->error(
                __('Invalid or expired reset token.'),
                400,
                'INVALID_RESET_TOKEN'
            );
        }

        // Verify token
        if (!Hash::check($request->token, $record->token)) {
            return $this->error(
                __('Invalid or expired reset token.'),
                400,
                'INVALID_RESET_TOKEN'
            );
        }

        // Check if token is expired (60 minutes)
        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->diffInMinutes(now()) > 60) {
            return $this->error(
                __('Reset token has expired. Please request a new one.'),
                400,
                'RESET_TOKEN_EXPIRED'
            );
        }

        return $this->success(
            ['valid' => true],
            __('Token is valid. You may reset your password.')
        );
    }

    /**
     * Reset password using token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? app('current_academy');

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Find reset record
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return $this->error(
                __('Invalid or expired reset token.'),
                400,
                'INVALID_RESET_TOKEN'
            );
        }

        // Verify token
        if (!Hash::check($request->token, $record->token)) {
            return $this->error(
                __('Invalid or expired reset token.'),
                400,
                'INVALID_RESET_TOKEN'
            );
        }

        // Check if token is expired (60 minutes)
        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->diffInMinutes(now()) > 60) {
            return $this->error(
                __('Reset token has expired. Please request a new one.'),
                400,
                'RESET_TOKEN_EXPIRED'
            );
        }

        // Find user in this academy
        $user = User::where('email', $request->email)
            ->where('academy_id', $academy->id)
            ->first();

        if (!$user) {
            return $this->error(
                __('No account found with this email in this academy.'),
                404,
                'USER_NOT_FOUND'
            );
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete reset token
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        return $this->success(
            ['email' => $request->email],
            __('Password has been reset successfully. Please login with your new password.')
        );
    }
}
