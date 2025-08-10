<?php

namespace App\Services;

use App\Models\Academy;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MeetingService
{
    /**
     * Meeting platforms available
     */
    const PLATFORM_JITSI = 'jitsi';
    const PLATFORM_WHEREBY = 'whereby'; 
    const PLATFORM_CUSTOM = 'custom';

    /**
     * Default meeting platform
     */
    private string $defaultPlatform = self::PLATFORM_JITSI;

    /**
     * Generate a meeting link for a session
     */
    public function generateMeetingLink(
        Academy $academy, 
        string $sessionType, 
        int $sessionId, 
        Carbon $startTime,
        string $platform = null
    ): array {
        $platform = $platform ?: $this->defaultPlatform;
        
        switch ($platform) {
            case self::PLATFORM_JITSI:
                return $this->generateJitsiMeeting($academy, $sessionType, $sessionId, $startTime);
                
            case self::PLATFORM_WHEREBY:
                return $this->generateWherebyMeeting($academy, $sessionType, $sessionId, $startTime);
                
            case self::PLATFORM_CUSTOM:
                return $this->generateCustomMeeting($academy, $sessionType, $sessionId, $startTime);
                
            default:
                return $this->generateJitsiMeeting($academy, $sessionType, $sessionId, $startTime);
        }
    }

    /**
     * Generate Jitsi Meet URL (Free, no API required)
     */
    private function generateJitsiMeeting(Academy $academy, string $sessionType, int $sessionId, Carbon $startTime): array
    {
        // Create unique room name
        $roomName = $this->generateRoomName($academy, $sessionType, $sessionId);
        
        // Jitsi Meet URL format
        $meetingUrl = "https://meet.jit.si/{$roomName}";
        
        // Generate meeting info
        return [
            'platform' => self::PLATFORM_JITSI,
            'meeting_url' => $meetingUrl,
            'room_name' => $roomName,
            'meeting_id' => $roomName,
            'join_info' => [
                'web_url' => $meetingUrl,
                'mobile_url' => $meetingUrl,
                'dial_in' => null, // Jitsi supports dial-in but requires setup
            ],
            'settings' => [
                'password_required' => false,
                'waiting_room' => false,
                'auto_record' => false,
            ],
            'features' => [
                'screen_sharing' => true,
                'chat' => true,
                'recording' => true,
                'breakout_rooms' => false,
                'whiteboard' => false,
            ],
            'created_at' => now(),
            'expires_at' => null, // Jitsi rooms don't expire
        ];
    }

    /**
     * Generate Whereby meeting URL (Simple but may have limits)
     */
    private function generateWherebyMeeting(Academy $academy, string $sessionType, int $sessionId, Carbon $startTime): array
    {
        // Create unique room name
        $roomName = $this->generateRoomName($academy, $sessionType, $sessionId);
        
        // Whereby URL format (free tier has limitations)
        $meetingUrl = "https://whereby.com/{$roomName}";
        
        return [
            'platform' => self::PLATFORM_WHEREBY,
            'meeting_url' => $meetingUrl,
            'room_name' => $roomName,
            'meeting_id' => $roomName,
            'join_info' => [
                'web_url' => $meetingUrl,
                'mobile_url' => $meetingUrl,
                'dial_in' => null,
            ],
            'settings' => [
                'password_required' => false,
                'waiting_room' => false,
                'auto_record' => false,
            ],
            'features' => [
                'screen_sharing' => true,
                'chat' => true,
                'recording' => false, // Limited on free tier
                'breakout_rooms' => false,
                'whiteboard' => false,
            ],
            'created_at' => now(),
            'expires_at' => $startTime->copy()->addHours(24), // 24 hour limit
        ];
    }

    /**
     * Generate custom meeting room (for future integration with other platforms)
     */
    private function generateCustomMeeting(Academy $academy, string $sessionType, int $sessionId, Carbon $startTime): array
    {
        $roomName = $this->generateRoomName($academy, $sessionType, $sessionId);
        $customDomain = config('app.meeting_domain', 'meet.itqan.com');
        $meetingUrl = "https://{$customDomain}/room/{$roomName}";
        
        return [
            'platform' => self::PLATFORM_CUSTOM,
            'meeting_url' => $meetingUrl,
            'room_name' => $roomName,
            'meeting_id' => $roomName,
            'join_info' => [
                'web_url' => $meetingUrl,
                'mobile_url' => $meetingUrl,
                'dial_in' => null,
            ],
            'settings' => [
                'password_required' => false,
                'waiting_room' => true,
                'auto_record' => false,
            ],
            'features' => [
                'screen_sharing' => true,
                'chat' => true,
                'recording' => true,
                'breakout_rooms' => false,
                'whiteboard' => false,
            ],
            'created_at' => now(),
            'expires_at' => null,
        ];
    }

    /**
     * Generate unique room name
     */
    private function generateRoomName(Academy $academy, string $sessionType, int $sessionId): string
    {
        // Format: academyname-sessiontype-id-randomstring
        $academySlug = Str::slug($academy->subdomain);
        $sessionSlug = Str::slug($sessionType);
        $randomString = Str::random(6);
        
        return "{$academySlug}-{$sessionSlug}-{$sessionId}-{$randomString}";
    }

    /**
     * Validate meeting URL
     */
    public function validateMeetingUrl(string $url): bool
    {
        // Check if URL is valid and from supported platforms
        $supportedDomains = [
            'meet.jit.si',
            'whereby.com', 
            config('app.meeting_domain', 'meet.itqan.com')
        ];
        
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return false;
        }
        
        return in_array($parsedUrl['host'], $supportedDomains);
    }

    /**
     * Get platform info
     */
    public function getPlatformInfo(string $platform): array
    {
        $platforms = [
            self::PLATFORM_JITSI => [
                'name' => 'Jitsi Meet',
                'description' => 'مجاني بالكامل، آمن ومفتوح المصدر',
                'features' => ['مكالمات فيديو عالية الجودة', 'مشاركة الشاشة', 'الدردشة', 'التسجيل'],
                'limits' => 'بلا حدود',
                'setup_required' => false,
            ],
            self::PLATFORM_WHEREBY => [
                'name' => 'Whereby',
                'description' => 'سهل الاستخدام، واجهة بسيطة',
                'features' => ['مكالمات فيديو', 'مشاركة الشاشة', 'الدردشة'],
                'limits' => '4 مشاركين في الإصدار المجاني',
                'setup_required' => false,
            ],
            self::PLATFORM_CUSTOM => [
                'name' => 'Custom Platform',
                'description' => 'منصة مخصصة للأكاديمية',
                'features' => ['حسب الإعداد', 'قابل للتخصيص'],
                'limits' => 'حسب الخطة',
                'setup_required' => true,
            ],
        ];
        
        return $platforms[$platform] ?? [];
    }

    /**
     * Get available platforms
     */
    public function getAvailablePlatforms(): array
    {
        return [
            self::PLATFORM_JITSI => $this->getPlatformInfo(self::PLATFORM_JITSI),
            self::PLATFORM_WHEREBY => $this->getPlatformInfo(self::PLATFORM_WHEREBY),
            self::PLATFORM_CUSTOM => $this->getPlatformInfo(self::PLATFORM_CUSTOM),
        ];
    }
}
