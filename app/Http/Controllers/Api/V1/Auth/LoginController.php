<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\Academy\AcademyBrandingResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Http\Traits\Api\ApiResponses;
use App\Models\DeviceToken;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    use ApiResponses;

    /**
     * Handle user login and return token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        // Find user by email AND academy_id (users can have same email in different academies)
        $user = User::where('email', $request->email)
            ->where('academy_id', $academy->id)
            ->first();

        // If not found, check for system-wide super_admin (academy_id is null)
        if (! $user) {
            $user = User::where('email', $request->email)
                ->whereNull('academy_id')
                ->where('user_type', 'super_admin')
                ->first();
        }

        // Verify credentials
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error(
                __('Invalid email or password'),
                401,
                'INVALID_CREDENTIALS'
            );
        }

        // Check if user is active
        if (! $user->isActive()) {
            return $this->error(
                __('Your account is inactive. Please contact support.'),
                403,
                'ACCOUNT_INACTIVE'
            );
        }

        // Create Sanctum token with abilities based on user type
        $abilities = $this->getTokenAbilities($user);
        $deviceName = $request->input('device_name', 'mobile-app');
        $token = $user->createToken(
            $deviceName,
            $abilities,
            now()->addDays(30)
        );

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // Create user session record for tracking
        $this->createUserSession($user, $request);

        // Store FCM token if provided
        $this->storeFcmToken($user, $request);

        // Load relationships for response
        $user->load(['academy', 'studentProfile', 'parentProfile', 'quranTeacherProfile', 'academicTeacherProfile', 'supervisorProfile']);

        return $this->success([
            'user' => new UserResource($user),
            'academy' => new AcademyBrandingResource($academy),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(30)->toISOString(),
            'abilities' => $abilities,
        ], __('Login successful'));
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Remove FCM token if provided
        $fcmToken = $request->input('fcm_token');
        if ($fcmToken) {
            DeviceToken::where('user_id', $user->id)
                ->where('token', $fcmToken)
                ->delete();
        }

        // Revoke the current access token
        $user->currentAccessToken()->delete();

        return $this->success(null, __('Logged out successfully'));
    }

    /**
     * Get authenticated user info.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['academy', 'studentProfile', 'parentProfile', 'quranTeacherProfile', 'academicTeacherProfile', 'supervisorProfile']);

        $academy = $request->attributes->get('academy') ?? current_academy();

        return $this->success([
            'user' => new UserResource($user),
            'academy' => new AcademyBrandingResource($academy),
        ], __('User info retrieved successfully'));
    }

    /**
     * Get token abilities based on user type.
     */
    protected function getTokenAbilities(User $user): array
    {
        $baseAbilities = ['read', 'write'];

        return match ($user->user_type) {
            UserType::STUDENT->value => [...$baseAbilities, 'student:*'],
            UserType::PARENT->value => [...$baseAbilities, 'parent:*'],
            UserType::QURAN_TEACHER->value => [...$baseAbilities, 'teacher:*', 'quran:*'],
            UserType::ACADEMIC_TEACHER->value => [...$baseAbilities, 'teacher:*', 'academic:*'],
            UserType::SUPERVISOR->value => [...$baseAbilities, 'supervisor:*', 'observe:*'],
            UserType::ADMIN->value => [...$baseAbilities, 'admin:*', 'observe:*'],
            UserType::SUPER_ADMIN->value => [...$baseAbilities, 'admin:*', 'observe:*', 'super:*'],
            default => $baseAbilities,
        };
    }

    /**
     * Store FCM device token if provided in login request.
     */
    protected function storeFcmToken(User $user, Request $request): void
    {
        $fcmToken = $request->input('fcm_token');
        if (! $fcmToken) {
            return;
        }

        try {
            $platform = 'android';
            $userAgent = $request->userAgent() ?? '';
            if (preg_match('/iphone|ipad|ipod|ios/i', $userAgent)) {
                $platform = 'ios';
            }

            DeviceToken::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'token' => $fcmToken,
                ],
                [
                    'platform' => $platform,
                    'device_name' => $request->input('device_name', 'mobile-app'),
                    'last_used_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to store FCM token during login', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create user session record for tracking.
     */
    protected function createUserSession(User $user, Request $request): void
    {
        try {
            $userAgent = $request->userAgent();
            $parsed = $this->parseUserAgent($userAgent);

            UserSession::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent,
                'device_type' => $parsed['device_type'] ?? 'mobile',
                'browser' => $parsed['browser'] ?? 'Mobile App',
                'platform' => $parsed['platform'] ?? 'Unknown',
                'logged_in_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Don't fail login if session tracking fails
            \Log::warning('Failed to create user session', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Parse user agent string.
     */
    protected function parseUserAgent(?string $userAgent): array
    {
        if (! $userAgent) {
            return [
                'device_type' => 'unknown',
                'browser' => 'Unknown',
                'platform' => 'Unknown',
            ];
        }

        $deviceType = 'desktop';
        $browser = 'Unknown';
        $platform = 'Unknown';

        // Detect device type
        if (preg_match('/mobile|android|iphone|ipad|ipod/i', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            $deviceType = 'tablet';
        }

        // Detect platform
        if (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $platform = 'iOS';
        } elseif (preg_match('/windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        }

        // Detect browser
        if (preg_match('/chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/edge/i', $userAgent)) {
            $browser = 'Edge';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
        ];
    }

    /**
     * Resend verification email (API)
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success([
                'already_verified' => true,
            ], __('auth.verification.already_verified'));
        }

        $user->sendEmailVerificationNotification();

        return $this->success([
            'sent' => true,
        ], __('auth.verification.email_sent'));
    }

    /**
     * Get email verification status (API)
     */
    public function verificationStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'email_verified' => $user->hasVerifiedEmail(),
            'email_verified_at' => $user->email_verified_at?->toISOString(),
        ]);
    }
}
