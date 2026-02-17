<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Notifications\ResetPasswordNotification;
use Exception;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Rules\PasswordRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    use ApiResponses;

    /**
     * Send password reset link to user's email.
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

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

        if (! $user) {
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

        // Send reset email
        try {
            $user->notify(new ResetPasswordNotification($token, $academy));

            Log::info('Password reset email sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'academy_id' => $academy->id,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send password reset email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // Don't reveal error to user for security
        }

        return $this->success(
            ['email' => $request->email],
            __('If an account exists with this email, you will receive a password reset link.')
        );
    }

    /**
     * Verify reset token is valid.
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

        if (! $record) {
            return $this->error(
                __('Invalid or expired reset token.'),
                400,
                'INVALID_RESET_TOKEN'
            );
        }

        // Verify token
        if (! Hash::check($request->token, $record->token)) {
            return $this->error(
                __('Invalid or expired reset token.'),
                400,
                'INVALID_RESET_TOKEN'
            );
        }

        // Check if token is expired (60 minutes)
        $createdAt = Carbon::parse($record->created_at);
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
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => PasswordRules::reset(),
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Find reset record
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record) {
            return $this->error(
                __('Invalid or expired reset token.'),
                400,
                'INVALID_RESET_TOKEN'
            );
        }

        // Verify token
        if (! Hash::check($request->token, $record->token)) {
            return $this->error(
                __('Invalid or expired reset token.'),
                400,
                'INVALID_RESET_TOKEN'
            );
        }

        // Check if token is expired (60 minutes)
        $createdAt = Carbon::parse($record->created_at);
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

        if (! $user) {
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

        // Send password changed notification for security awareness
        try {
            $user->notify(new PasswordChangedNotification(
                $academy,
                $request->ip(),
                $request->userAgent()
            ));

            Log::info('Password changed notification sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'academy_id' => $academy->id,
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to send password changed notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the password reset if notification fails
        }

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
