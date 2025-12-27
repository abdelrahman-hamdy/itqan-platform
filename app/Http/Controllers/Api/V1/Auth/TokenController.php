<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Academy\AcademyBrandingResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Http\Traits\Api\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\SessionStatus;

class TokenController extends Controller
{
    use ApiResponses;

    /**
     * Refresh the current token.
     *
     * Creates a new token and revokes the current one.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        // Get the device name from current token or request
        $deviceName = $request->input('device_name', $currentToken->name ?? 'mobile-app');

        // Get abilities from current token
        $abilities = $currentToken->abilities ?? ['read', 'write'];

        // Create new token
        $newToken = $user->createToken(
            $deviceName,
            $abilities,
            now()->addDays(30)
        );

        // Revoke old token
        $currentToken->delete();

        // Load relationships for response
        $user->load(['academy', 'studentProfile', 'parentProfile', 'quranTeacherProfile', 'academicTeacherProfile']);
        $academy = $request->attributes->get('academy') ?? app('current_academy');

        return $this->success([
            'user' => new UserResource($user),
            'academy' => new AcademyBrandingResource($academy),
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(30)->toISOString(),
            'abilities' => $abilities,
        ], __('Token refreshed successfully'));
    }

    /**
     * Validate the current token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        $user->load(['academy', 'studentProfile', 'parentProfile', 'quranTeacherProfile', 'academicTeacherProfile']);
        $academy = $request->attributes->get('academy') ?? app('current_academy');

        return $this->success([
            'valid' => true,
            'user' => new UserResource($user),
            'academy' => new AcademyBrandingResource($academy),
            'token_info' => [
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->toISOString(),
                'expires_at' => $token->expires_at?->toISOString(),
            ],
        ], __('Token is valid'));
    }

    /**
     * Revoke the current token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function revoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, __('Token revoked successfully'));
    }

    /**
     * Revoke all tokens for the user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function revokeAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $user->tokens()->count();

        $user->tokens()->delete();

        return $this->success([
            'revoked_count' => $count,
        ], __(':count tokens revoked successfully', ['count' => $count]));
    }
}
