<?php

namespace App\Services;

use App\Models\User;
use App\Models\GoogleToken;
use App\Models\PlatformGoogleAccount;
use App\Models\QuranSession;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Calendar_ConferenceData;
use Google_Service_Calendar_CreateConferenceRequest;
use Google_Service_Calendar_ConferenceSolutionKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GoogleCalendarService
{
    private Google_Client $client;
    private Google_Service_Calendar $service;
    private ?User $currentUser = null;
    private ?GoogleToken $currentToken = null;

    public function __construct()
    {
        $this->initializeClient();
    }

    /**
     * Initialize Google API client
     */
    private function initializeClient(): void
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName(config('app.name'));
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $this->client->setScopes([
            Google_Service_Calendar::CALENDAR,
            Google_Service_Calendar::CALENDAR_EVENTS,
        ]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        
        $this->service = new Google_Service_Calendar($this->client);
    }

    /**
     * Get OAuth authorization URL for teacher
     */
    public function getAuthUrl(User $user): string
    {
        $this->currentUser = $user;
        
        // Add state parameter for security
        $state = [
            'user_id' => $user->id,
            'academy_id' => $user->academy_id,
            'timestamp' => now()->timestamp,
            'hash' => hash('sha256', $user->id . $user->academy_id . config('app.key'))
        ];
        
        $this->client->setState(base64_encode(json_encode($state)));
        
        return $this->client->createAuthUrl();
    }

    /**
     * Handle OAuth callback and store tokens
     */
    public function handleCallback(string $code, string $state): GoogleToken
    {
        // Verify state parameter
        $stateData = json_decode(base64_decode($state), true);
        if (!$this->verifyState($stateData)) {
            throw new \Exception('Invalid OAuth state parameter');
        }
        
        $user = User::findOrFail($stateData['user_id']);
        $this->currentUser = $user;
        
        // Exchange code for tokens
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        
        if (isset($token['error'])) {
            throw new \Exception('OAuth error: ' . $token['error_description']);
        }
        
        // Get user info from Google
        $this->client->setAccessToken($token);
        $googleUser = $this->getGoogleUserInfo();
        
        // Store/update user Google info
        $user->update([
            'google_id' => $googleUser['id'],
            'google_email' => $googleUser['email'],
            'google_connected_at' => now(),
            'google_disconnected_at' => null,
            'google_calendar_enabled' => true,
            'google_permissions' => $token['scope'] ?? null,
        ]);
        
        // Store encrypted tokens
        $googleToken = GoogleToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'token_status' => 'active'
            ],
            [
                'academy_id' => $user->academy_id,
                'access_token' => encrypt($token['access_token']),
                'refresh_token' => isset($token['refresh_token']) ? encrypt($token['refresh_token']) : null,
                'expires_at' => isset($token['expires_in']) ? now()->addSeconds($token['expires_in']) : null,
                'token_type' => $token['token_type'] ?? 'Bearer',
                'scope' => explode(' ', $token['scope'] ?? ''),
                'token_status' => 'active',
                'refresh_count' => 0,
                'last_used_at' => now(),
                'consecutive_errors' => 0,
            ]
        );
        
        $this->currentToken = $googleToken;
        
        Log::info('Google OAuth successful', [
            'user_id' => $user->id,
            'academy_id' => $user->academy_id,
            'google_email' => $googleUser['email']
        ]);
        
        return $googleToken;
    }

    /**
     * Create Google Calendar event with Meet link for session
     */
    public function createSessionMeeting(QuranSession $session, ?User $forUser = null): array
    {
        try {
            $user = $forUser ?? $session->quranTeacher->user;
            
            if (!$this->setUserContext($user)) {
                // Fallback to platform account
                return $this->createSessionMeetingWithFallback($session);
            }
            
            $eventData = $this->buildEventData($session);
            $event = $this->createCalendarEventWithMeet($eventData);
            
            // Update session with meeting info
            $session->update([
                'google_event_id' => $event->getId(),
                'google_calendar_id' => 'primary',
                'google_meet_url' => $event->getHangoutLink(),
                'google_meet_id' => $this->extractMeetId($event->getHangoutLink()),
                'meeting_source' => 'google',
                'meeting_created_at' => now(),
                'created_by_user_id' => $user->id,
                'meeting_creation_error' => null,
                'retry_count' => 0,
            ]);
            
            Log::info('Google Meet created successfully', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'meet_url' => $event->getHangoutLink()
            ]);
            
            return [
                'success' => true,
                'event_id' => $event->getId(),
                'meet_url' => $event->getHangoutLink(),
                'calendar_url' => $event->getHtmlLink(),
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to create Google Meet', [
                'session_id' => $session->id,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
            
            // Update session with error info
            $session->update([
                'meeting_creation_error' => $e->getMessage(),
                'last_error_at' => now(),
                'retry_count' => ($session->retry_count ?? 0) + 1,
            ]);
            
            // Try fallback if available
            if ($session->retry_count < 3) {
                return $this->createSessionMeetingWithFallback($session);
            }
            
            throw $e;
        }
    }

    /**
     * Create meeting using platform fallback account
     */
    private function createSessionMeetingWithFallback(QuranSession $session): array
    {
        $fallbackAccount = PlatformGoogleAccount::where('academy_id', $session->academy_id)
            ->where('account_type', 'fallback')
            ->where('is_active', true)
            ->first();
            
        if (!$fallbackAccount) {
            throw new \Exception('No fallback Google account available for academy');
        }
        
        // Check daily usage limit
        if ($fallbackAccount->daily_usage >= $fallbackAccount->daily_limit) {
            throw new \Exception('Fallback account daily limit exceeded');
        }
        
        // Set up client with fallback account
        $this->client->setAccessToken([
            'access_token' => decrypt($fallbackAccount->access_token),
            'refresh_token' => $fallbackAccount->refresh_token ? decrypt($fallbackAccount->refresh_token) : null,
            'expires_in' => $fallbackAccount->expires_at ? $fallbackAccount->expires_at->diffInSeconds(now()) : 3600,
        ]);
        
        $eventData = $this->buildEventData($session, $fallbackAccount);
        $event = $this->createCalendarEventWithMeet($eventData);
        
        // Update usage tracking
        $fallbackAccount->increment('sessions_created');
        $fallbackAccount->increment('daily_usage');
        $fallbackAccount->update(['last_used_at' => now()]);
        
        // Update session
        $session->update([
            'google_event_id' => $event->getId(),
            'google_calendar_id' => 'primary',
            'google_meet_url' => $event->getHangoutLink(),
            'google_meet_id' => $this->extractMeetId($event->getHangoutLink()),
            'meeting_source' => 'platform',
            'meeting_created_at' => now(),
            'meeting_creation_error' => null,
        ]);
        
        Log::warning('Used fallback account for meeting creation', [
            'session_id' => $session->id,
            'fallback_account_id' => $fallbackAccount->id,
            'meet_url' => $event->getHangoutLink()
        ]);
        
        return [
            'success' => true,
            'event_id' => $event->getId(),
            'meet_url' => $event->getHangoutLink(),
            'fallback_used' => true,
        ];
    }

    /**
     * Set user context and authenticate
     */
    private function setUserContext(User $user): bool
    {
        $this->currentUser = $user;
        
        $token = GoogleToken::where('user_id', $user->id)
            ->where('token_status', 'active')
            ->first();
            
        if (!$token) {
            return false;
        }
        
        $this->currentToken = $token;
        
        // Check if token needs refresh
        if ($token->expires_at && $token->expires_at->isPast()) {
            if (!$this->refreshToken($token)) {
                return false;
            }
        }
        
        // Set access token
        $this->client->setAccessToken([
            'access_token' => decrypt($token->access_token),
            'refresh_token' => $token->refresh_token ? decrypt($token->refresh_token) : null,
            'expires_in' => $token->expires_at ? $token->expires_at->diffInSeconds(now()) : 3600,
        ]);
        
        // Update last used
        $token->update(['last_used_at' => now()]);
        
        return true;
    }

    /**
     * Refresh expired token
     */
    private function refreshToken(GoogleToken $token): bool
    {
        try {
            if (!$token->refresh_token) {
                throw new \Exception('No refresh token available');
            }
            
            $this->client->setAccessToken([
                'refresh_token' => decrypt($token->refresh_token)
            ]);
            
            $newToken = $this->client->fetchAccessTokenWithRefreshToken();
            
            if (isset($newToken['error'])) {
                throw new \Exception('Token refresh failed: ' . $newToken['error_description']);
            }
            
            // Update token
            $token->update([
                'access_token' => encrypt($newToken['access_token']),
                'expires_at' => isset($newToken['expires_in']) ? now()->addSeconds($newToken['expires_in']) : null,
                'refresh_count' => $token->refresh_count + 1,
                'last_refreshed_at' => now(),
                'consecutive_errors' => 0,
                'last_error' => null,
            ]);
            
            Log::info('Token refreshed successfully', [
                'user_id' => $token->user_id,
                'refresh_count' => $token->refresh_count
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'user_id' => $token->user_id,
                'error' => $e->getMessage()
            ]);
            
            $token->update([
                'consecutive_errors' => $token->consecutive_errors + 1,
                'last_error' => $e->getMessage(),
                'last_error_at' => now(),
            ]);
            
            // Mark token as expired after 3 consecutive failures
            if ($token->consecutive_errors >= 3) {
                $token->update(['token_status' => 'expired']);
                
                // Notify user and admin
                $this->notifyTokenExpired($token->user);
            }
            
            return false;
        }
    }

    /**
     * Build event data for Google Calendar
     */
    private function buildEventData(QuranSession $session, ?PlatformGoogleAccount $fallbackAccount = null): array
    {
        $teacher = $session->quranTeacher;
        $student = $session->student;
        $circle = $session->circle;
        
        // Build title
        if ($circle) {
            $title = "حلقة {$circle->name_ar}";
        } else {
            $title = "جلسة قرآن مع {$student->name}";
        }
        
        // Build description
        $description = $this->buildEventDescription($session);
        
        // Build attendees list
        $attendees = [];
        if ($student) {
            $attendees[] = ['email' => $student->email];
        }
        if ($circle) {
            foreach ($circle->students as $circleStudent) {
                $attendees[] = ['email' => $circleStudent->email];
            }
        }
        
        return [
            'summary' => $title,
            'description' => $description,
            'start' => [
                'dateTime' => $session->scheduled_at->toISOString(),
                'timeZone' => config('app.timezone'),
            ],
            'end' => [
                'dateTime' => $session->scheduled_at->copy()
                    ->addMinutes($session->duration_minutes)->toISOString(),
                'timeZone' => config('app.timezone'),
            ],
            'attendees' => $attendees,
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => 'session_' . $session->id . '_' . uniqid(),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet'
                    ]
                ]
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 60],
                    ['method' => 'email', 'minutes' => 15],
                ]
            ],
        ];
    }

    /**
     * Create calendar event with Meet link
     */
    private function createCalendarEventWithMeet(array $eventData): Google_Service_Calendar_Event
    {
        $event = new Google_Service_Calendar_Event($eventData);
        
        return $this->service->events->insert('primary', $event, [
            'conferenceDataVersion' => 1,
            'sendUpdates' => 'all'
        ]);
    }

    /**
     * Build event description
     */
    private function buildEventDescription(QuranSession $session): string
    {
        $teacher = $session->quranTeacher;
        $description = "جلسة تحفيظ القرآن الكريم\n\n";
        $description .= "المعلم: {$teacher->user->name}\n";
        
        if ($session->student) {
            $description .= "الطالب: {$session->student->name}\n";
        }
        
        if ($session->circle) {
            $description .= "الحلقة: {$session->circle->name_ar}\n";
            $description .= "عدد الطلاب: " . $session->circle->students->count() . "\n";
        }
        
        $description .= "المدة: {$session->duration_minutes} دقيقة\n";
        $description .= "رمز الجلسة: {$session->session_code}\n\n";
        $description .= "ملاحظات:\n";
        $description .= "- يرجى الانضمام قبل 5 دقائق من الموعد\n";
        $description .= "- تأكد من اتصال الإنترنت والميكروفون\n";
        $description .= "- في حالة وجود مشاكل تقنية، تواصل مع الدعم الفني\n";
        
        return $description;
    }

    /**
     * Extract Google Meet ID from URL
     */
    private function extractMeetId(?string $hangoutLink): ?string
    {
        if (!$hangoutLink) {
            return null;
        }
        
        // Extract ID from URL like: https://meet.google.com/abc-defg-hij
        preg_match('/meet\.google\.com\/(.+)$/', $hangoutLink, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Get Google user info
     */
    private function getGoogleUserInfo(): array
    {
        $oauth2 = new \Google_Service_Oauth2($this->client);
        $userInfo = $oauth2->userinfo->get();
        
        return [
            'id' => $userInfo->getId(),
            'email' => $userInfo->getEmail(),
            'name' => $userInfo->getName(),
            'picture' => $userInfo->getPicture(),
        ];
    }

    /**
     * Verify OAuth state parameter
     */
    private function verifyState(array $stateData): bool
    {
        if (!isset($stateData['user_id'], $stateData['academy_id'], $stateData['timestamp'], $stateData['hash'])) {
            return false;
        }
        
        // Check timestamp (should be within last hour)
        if (now()->timestamp - $stateData['timestamp'] > 3600) {
            return false;
        }
        
        // Verify hash
        $expectedHash = hash('sha256', $stateData['user_id'] . $stateData['academy_id'] . config('app.key'));
        return hash_equals($expectedHash, $stateData['hash']);
    }

    /**
     * Notify user and admin of token expiration
     */
    private function notifyTokenExpired(User $user): void
    {
        // Implementation will be added with notification system
        Log::warning('Google token expired', ['user_id' => $user->id]);
    }

    /**
     * Disconnect user's Google account
     */
    public function disconnectUser(User $user): bool
    {
        try {
            // Revoke token from Google
            $token = GoogleToken::where('user_id', $user->id)
                ->where('token_status', 'active')
                ->first();
                
            if ($token) {
                $this->client->setAccessToken(['access_token' => decrypt($token->access_token)]);
                $this->client->revokeToken();
                
                $token->update(['token_status' => 'revoked']);
            }
            
            // Update user record
            $user->update([
                'google_connected_at' => null,
                'google_disconnected_at' => now(),
                'google_calendar_enabled' => false,
            ]);
            
            Log::info('Google account disconnected', ['user_id' => $user->id]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to disconnect Google account', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Check if user has valid Google connection
     */
    public function hasValidConnection(User $user): bool
    {
        return GoogleToken::where('user_id', $user->id)
            ->where('token_status', 'active')
            ->where('consecutive_errors', '<', 3)
            ->exists();
    }
}