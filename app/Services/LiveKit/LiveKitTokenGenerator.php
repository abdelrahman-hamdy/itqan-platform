<?php

namespace App\Services\LiveKit;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LiveKitTokenGenerator
{
    private ?string $apiKey;

    private ?string $apiSecret;

    public function __construct()
    {
        $this->apiKey = config('livekit.api_key', '');
        $this->apiSecret = config('livekit.api_secret', '');
    }

    /**
     * Check if token generator is properly configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->apiSecret);
    }

    /**
     * Generate access token for participant to join room
     */
    public function generateParticipantToken(
        string $roomName,
        User $user,
        array $permissions = []
    ): string {
        try {
            // Create participant identity and metadata with Arabic name
            $participantIdentity = $user->id.'_'.Str::slug($user->first_name.'_'.$user->last_name);

            // Get avatar data for the participant
            $avatarData = $this->getUserAvatarData($user);

            $metadata = json_encode([
                'name' => $user->name, // Full Arabic name
                'role' => $this->getUserRole($user),
                'user_id' => $user->id,
                'avatarUrl' => $avatarData['avatarUrl'],
                'defaultAvatarUrl' => $avatarData['defaultAvatarUrl'],
                'userType' => $avatarData['userType'],
                'gender' => $avatarData['gender'],
            ]);

            $tokenOptions = (new AccessTokenOptions)
                ->setIdentity($participantIdentity)
                ->setMetadata($metadata)
                ->setTtl($permissions['ttl'] ?? config('business.tokens.livekit_ttl', 10800)); // Default 3 hours

            $videoGrant = (new VideoGrant)
                ->setRoomJoin()
                ->setRoomName($roomName)
                ->setCanPublish($permissions['can_publish'] ?? true)
                ->setCanSubscribe($permissions['can_subscribe'] ?? true);

            // Additional permissions for teachers/admins
            if ($this->isTeacher($user) || $this->isAdmin($user)) {
                $videoGrant->setRoomAdmin();
            }

            $token = (new AccessToken($this->apiKey, $this->apiSecret))
                ->init($tokenOptions)
                ->setGrant($videoGrant)
                ->toJwt();

            Log::info('Generated LiveKit access token', [
                'user_id' => $user->id,
                'room_name' => $roomName,
                'permissions' => $permissions,
            ]);

            return $token;

        } catch (\Exception $e) {
            Log::error('Failed to generate LiveKit token', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'room_name' => $roomName,
            ]);

            throw new \Exception('Failed to generate access token: '.$e->getMessage());
        }
    }

    /**
     * Generate JWT token for Egress API calls
     */
    public function generateEgressToken(?int $ttl = null): string
    {
        $ttl = $ttl ?? config('business.tokens.livekit_ttl', 10800);
        try {
            $tokenOptions = (new AccessTokenOptions)
                ->setIdentity('egress-service')
                ->setTtl($ttl);

            // Add video grant with recording/egress permissions
            $grant = new VideoGrant;
            $grant->setRoomRecord(true);
            $grant->setRoomAdmin(true);

            $token = new AccessToken($this->apiKey, $this->apiSecret, $tokenOptions);
            $token->setGrant($grant);

            return $token->toJwt();

        } catch (\Exception $e) {
            Log::error('Failed to generate Egress token', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to generate Egress token: '.$e->getMessage());
        }
    }

    /**
     * Generate room token with admin privileges
     */
    public function generateRoomToken(string $roomName, string $identity = 'admin', ?int $ttl = null): string
    {
        $ttl = $ttl ?? config('business.tokens.livekit_ttl', 10800);
        try {
            $tokenOptions = (new AccessTokenOptions)
                ->setIdentity($identity)
                ->setTtl($ttl);

            $videoGrant = (new VideoGrant)
                ->setRoomJoin()
                ->setRoomName($roomName)
                ->setRoomAdmin()
                ->setCanPublish(true)
                ->setCanSubscribe(true);

            $token = (new AccessToken($this->apiKey, $this->apiSecret))
                ->init($tokenOptions)
                ->setGrant($videoGrant)
                ->toJwt();

            Log::info('Generated LiveKit room token', [
                'room_name' => $roomName,
                'identity' => $identity,
            ]);

            return $token;

        } catch (\Exception $e) {
            Log::error('Failed to generate room token', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
            ]);

            throw new \Exception('Failed to generate room token: '.$e->getMessage());
        }
    }

    /**
     * Generate general-purpose token with custom grants
     */
    public function generateToken(
        string $identity,
        ?string $roomName = null,
        array $grants = [],
        ?int $ttl = null
    ): string {
        $ttl = $ttl ?? config('business.tokens.livekit_ttl', 10800);
        try {
            $tokenOptions = (new AccessTokenOptions)
                ->setIdentity($identity)
                ->setTtl($ttl);

            $videoGrant = new VideoGrant;

            // Apply custom grants
            if ($roomName) {
                $videoGrant->setRoomJoin()->setRoomName($roomName);
            }

            if ($grants['can_publish'] ?? false) {
                $videoGrant->setCanPublish(true);
            }

            if ($grants['can_subscribe'] ?? false) {
                $videoGrant->setCanSubscribe(true);
            }

            if ($grants['room_admin'] ?? false) {
                $videoGrant->setRoomAdmin();
            }

            if ($grants['room_record'] ?? false) {
                $videoGrant->setRoomRecord(true);
            }

            $token = (new AccessToken($this->apiKey, $this->apiSecret))
                ->init($tokenOptions)
                ->setGrant($videoGrant)
                ->toJwt();

            Log::info('Generated custom LiveKit token', [
                'identity' => $identity,
                'room_name' => $roomName,
                'grants' => $grants,
            ]);

            return $token;

        } catch (\Exception $e) {
            Log::error('Failed to generate custom token', [
                'error' => $e->getMessage(),
                'identity' => $identity,
            ]);

            throw new \Exception('Failed to generate token: '.$e->getMessage());
        }
    }

    /**
     * Get user role for token metadata
     */
    private function getUserRole(User $user): string
    {
        if ($this->isAdmin($user)) {
            return 'admin';
        }

        if ($this->isTeacher($user)) {
            return 'teacher';
        }

        return 'student';
    }

    /**
     * Check if user is a teacher
     */
    private function isTeacher(User $user): bool
    {
        return in_array($user->user_type, ['quran_teacher', 'academic_teacher']);
    }

    /**
     * Check if user is an admin
     */
    private function isAdmin(User $user): bool
    {
        return in_array($user->user_type, ['admin', 'super_admin']);
    }

    /**
     * Get user avatar data for meeting metadata
     */
    private function getUserAvatarData(User $user): array
    {
        // Detect user type
        $userType = $user->user_type ?? 'student';

        // Get avatar path from user or related profiles
        $avatarPath = $user->avatar
            ?? $user->studentProfile?->avatar
            ?? $user->quranTeacherProfile?->avatar
            ?? $user->academicTeacherProfile?->avatar
            ?? null;

        // Get gender from user or profiles
        $gender = $user->gender
            ?? $user->studentProfile?->gender
            ?? $user->quranTeacherProfile?->gender
            ?? $user->academicTeacherProfile?->gender
            ?? $user->supervisorProfile?->gender
            ?? 'male';

        // Build avatar URL if avatar path exists
        $avatarUrl = $avatarPath ? asset('storage/'.$avatarPath) : null;

        // Get default avatar URL based on user type and gender
        $defaultAvatarUrl = $this->getDefaultAvatarUrl($userType, $gender);

        return [
            'avatarUrl' => $avatarUrl,
            'defaultAvatarUrl' => $defaultAvatarUrl,
            'userType' => $userType,
            'gender' => $gender,
        ];
    }

    /**
     * Get default avatar URL based on user type and gender
     */
    private function getDefaultAvatarUrl(string $userType, string $gender): ?string
    {
        $genderPrefix = $gender === 'female' ? 'female' : 'male';

        return match ($userType) {
            'quran_teacher' => asset("app-design-assets/{$genderPrefix}-quran-teacher-avatar.png"),
            'academic_teacher' => asset("app-design-assets/{$genderPrefix}-academic-teacher-avatar.png"),
            'student' => asset("app-design-assets/{$genderPrefix}-student-avatar.png"),
            'supervisor' => asset("app-design-assets/{$genderPrefix}-supervisor-avatar.png"),
            default => asset("app-design-assets/{$genderPrefix}-student-avatar.png"),
        };
    }
}
