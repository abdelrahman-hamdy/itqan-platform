<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\Academy\AcademyBrandingResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Http\Traits\Api\ApiResponses;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Enums\SessionStatus;

class LoginController extends Controller
{
    use ApiResponses;

    /**
     * Handle user login and return token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? app('current_academy');

        // Find user by email AND academy_id (users can have same email in different academies)
        $user = User::where('email', $request->email)
            ->where('academy_id', $academy->id)
            ->first();

        // Verify credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error(
                __('Invalid email or password'),
                401,
                'INVALID_CREDENTIALS'
            );
        }

        // Check if user is active
        if (!$user->isActive()) {
            return $this->error(
                __('Your account is inactive. Please contact support.'),
                403,
                'ACCOUNT_INACTIVE'
            );
        }

        // Check if user type is allowed for mobile app
        if (!in_array($user->user_type, ['student', 'parent', 'quran_teacher', 'academic_teacher'])) {
            return $this->error(
                __('This account type is not supported on the mobile app.'),
                403,
                'UNSUPPORTED_USER_TYPE'
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

        // Load relationships for response
        $user->load(['academy', 'studentProfile', 'parentProfile', 'quranTeacherProfile', 'academicTeacherProfile']);

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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the current access token
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, __('Logged out successfully'));
    }

    /**
     * Get authenticated user info.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['academy', 'studentProfile', 'parentProfile', 'quranTeacherProfile', 'academicTeacherProfile']);

        $academy = $request->attributes->get('academy') ?? app('current_academy');

        return $this->success([
            'user' => new UserResource($user),
            'academy' => new AcademyBrandingResource($academy),
        ], __('User info retrieved successfully'));
    }

    /**
     * Get token abilities based on user type.
     *
     * @param User $user
     * @return array
     */
    protected function getTokenAbilities(User $user): array
    {
        $baseAbilities = ['read', 'write'];

        return match ($user->user_type) {
            'student' => [...$baseAbilities, 'student:*'],
            'parent' => [...$baseAbilities, 'parent:*'],
            'quran_teacher' => [...$baseAbilities, 'teacher:*', 'quran:*'],
            'academic_teacher' => [...$baseAbilities, 'teacher:*', 'academic:*'],
            default => $baseAbilities,
        };
    }

    /**
     * Create user session record for tracking.
     *
     * @param User $user
     * @param Request $request
     * @return void
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
     *
     * @param string|null $userAgent
     * @return array
     */
    protected function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
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
}
