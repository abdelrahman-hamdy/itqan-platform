---
trigger: always_on
alwaysApply: true
---
# Google Calendar & Meet Integration Guide

## Overview

This guide covers integrating Google Calendar API and Google Meet for automated session scheduling and video conferencing in the Itqan platform.

## Google Calendar API Setup

### Service Account Configuration
```php
// app/Services/GoogleCalendarService.php
class GoogleCalendarService
{
    protected Google_Client $client;
    protected Google_Service_Calendar $service;
    
    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('app/google-calendar/credentials.json'));
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
        $this->service = new Google_Service_Calendar($this->client);
    }
    
    public function createEventWithMeetLink(array $eventData): Google_Service_Calendar_Event
    {
        $event = new Google_Service_Calendar_Event([
            'summary' => $eventData['title'],
            'description' => $eventData['description'],
            'start' => ['dateTime' => $eventData['start_time']],
            'end' => ['dateTime' => $eventData['end_time']],
            'attendees' => $this->formatAttendees($eventData['attendees']),
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => 'session_' . $eventData['session_id'] . '_' . time(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
                ]
            ]
        ]);
        
        return $this->service->events->insert(
            $eventData['calendar_id'], 
            $event, 
            ['conferenceDataVersion' => 1] // CRITICAL: Required for Meet links
        );
    }
}
```

### OAuth 2.0 Authentication Flow
```php
// app/Http/Controllers/GoogleAuthController.php
class GoogleAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/google-calendar/credentials.json'));
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $client->setRedirectUri(route('google.callback'));
        
        $authUrl = $client->createAuthUrl();
        return redirect($authUrl);
    }
    
    public function handleCallback(Request $request)
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/google-calendar/credentials.json'));
        $client->authenticate($request->get('code'));
        
        $token = $client->getAccessToken();
        
        // Store token securely per user/tenant
        auth()->user()->update([
            'google_calendar_token' => encrypt(json_encode($token))
        ]);
        
        return redirect()->route('settings.integrations')
            ->with('success', 'تم ربط Google Calendar بنجاح');
    }
}
```

## Meet Link Generation Requirements

### Conference Data Structure
```php
// ✅ DO: Proper conferenceData structure for Meet links
public function generateMeetLink(QuranSession $session): string
{
    $eventData = [
        'title' => "جلسة قرآن - {$session->title}",
        'description' => "جلسة مع الأستاذ {$session->teacher->name}",
        'start_time' => $session->start_time->toISOString(),
        'end_time' => $session->end_time->toISOString(),
        'session_id' => $session->id,
        'calendar_id' => $session->teacher->google_calendar_id,
        'attendees' => $this->getSessionAttendees($session),
    ];
    
    $event = $this->createEventWithMeetLink($eventData);
    
    // Save Google event details
    $session->update([
        'google_event_id' => $event->getId(),
        'meet_url' => $event->getHangoutLink(),
        'google_calendar_link' => $event->getHtmlLink(),
    ]);
    
    return $event->getHangoutLink();
}

// ✅ DO: Always include conferenceDataVersion parameter
$options = ['conferenceDataVersion' => 1];
$event = $service->events->insert($calendarId, $event, $options);

// ✅ DO: Generate unique request IDs
'requestId' => 'session_' . $session->id . '_' . time()

// ✅ DO: Set proper conference solution key
'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
```

## Automated Session Creation

### Pre-Session Link Generation Job
```php
// app/Jobs/CreateGoogleMeetSessionJob.php
class CreateGoogleMeetSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        private QuranSession $session
    ) {}
    
    public function handle(GoogleCalendarService $calendar): void
    {
        try {
            // Generate Meet link 15 minutes before session
            if ($this->session->start_time->diffInMinutes(now()) <= 15) {
                $meetLink = $calendar->generateMeetLink($this->session);
                
                // Notify participants
                $this->notifyParticipants($meetLink);
            }
        } catch (Exception $e) {
            Log::error('Failed to create Google Meet session', [
                'session_id' => $this->session->id,
                'error' => $e->getMessage()
            ]);
            
            // Retry job with exponential backoff
            $this->release(60);
        }
    }
    
    private function notifyParticipants(string $meetLink): void
    {
        // Send notifications to teacher and students
        $this->session->teacher->user->notify(
            new SessionMeetLinkNotification($this->session, $meetLink)
        );
        
        foreach ($this->session->students as $student) {
            $student->user->notify(
                new SessionMeetLinkNotification($this->session, $meetLink)
            );
        }
    }
}
```

### Scheduled Command for Link Generation
```php
// app/Console/Commands/GenerateMeetLinksCommand.php
class GenerateMeetLinksCommand extends Command
{
    protected $signature = 'sessions:generate-meet-links';
    protected $description = 'Generate Google Meet links for upcoming sessions';
    
    public function handle(): void
    {
        $upcomingSessions = QuranSession::where('start_time', '>=', now())
            ->where('start_time', '<=', now()->addMinutes(15))
            ->whereNull('meet_url')
            ->where('status', 'scheduled')
            ->get();
            
        $this->info("Found {$upcomingSessions->count()} sessions needing Meet links");
        
        foreach ($upcomingSessions as $session) {
            CreateGoogleMeetSessionJob::dispatch($session);
            $this->info("Dispatched job for session {$session->id}");
        }
    }
}

// In app/Console/Kernel.php (if using Laravel 10) or routes/console.php (Laravel 11)
Schedule::command('sessions:generate-meet-links')->everyMinute();
```

## Error Handling & Token Management

### Token Refresh Handling
```php
// app/Services/GoogleCalendarService.php
public function refreshTokenIfNeeded(User $user): void
{
    if (!$user->google_calendar_token) {
        throw new Exception('No Google Calendar token found for user');
    }
    
    $token = json_decode(decrypt($user->google_calendar_token), true);
    $this->client->setAccessToken($token);
    
    if ($this->client->isAccessTokenExpired()) {
        $refreshToken = $this->client->getRefreshToken();
        
        if (!$refreshToken) {
            throw new Exception('No refresh token available. User needs to re-authenticate.');
        }
        
        $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        $newToken = $this->client->getAccessToken();
        
        $user->update([
            'google_calendar_token' => encrypt(json_encode($newToken))
        ]);
        
        Log::info('Refreshed Google Calendar token', ['user_id' => $user->id]);
    }
}
```

### API Rate Limiting & Retry Logic
```php
public function createEventWithRetry(array $eventData, int $maxRetries = 3): ?Google_Service_Calendar_Event
{
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            return $this->createEventWithMeetLink($eventData);
        } catch (Google_Service_Exception $e) {
            if ($e->getCode() === 429) { // Rate limit exceeded
                $waitTime = pow(2, $attempt); // Exponential backoff
                sleep($waitTime);
                $attempt++;
                continue;
            }
            
            if ($e->getCode() === 401) { // Unauthorized
                // Try to refresh token
                $this->refreshTokenIfNeeded(auth()->user());
                $attempt++;
                continue;
            }
            
            // Other errors, don't retry
            throw $e;
        }
    }
    
    Log::error('Failed to create Google Calendar event after retries', [
        'event_data' => $eventData,
        'max_retries' => $maxRetries
    ]);
    
    return null;
}
```

## Multi-Tenancy Considerations

### Tenant-Specific Calendar Access
```php
// app/Services/TenantAwareGoogleCalendarService.php
class TenantAwareGoogleCalendarService extends GoogleCalendarService
{
    public function __construct(private Academy $academy)
    {
        parent::__construct();
        $this->setTenantCredentials();
    }
    
    private function setTenantCredentials(): void
    {
        $settings = $this->academy->google_settings;
        
        if ($settings && $settings->credentials) {
            $this->client->setAuthConfig(json_decode($settings->credentials, true));
        } else {
            throw new Exception('No Google Calendar credentials configured for academy: ' . $this->academy->name);
        }
    }
    
    public function getAcademyCalendar(): string
    {
        return $this->academy->google_settings->calendar_id ?? 'primary';
    }
}
```

## Security Best Practices

### Credential Management
```php
// ✅ DO: Store credentials securely
// In .env
GOOGLE_CALENDAR_CREDENTIALS_PATH=storage/app/google-calendar/credentials.json

// ✅ DO: Encrypt sensitive tokens in database
protected $casts = [
    'google_calendar_token' => 'encrypted',
    'google_settings' => 'encrypted',
];

// ✅ DO: Use service account for server-to-server operations
$this->client->useApplicationDefaultCredentials();
```

### Access Control
```php
// app/Policies/GoogleCalendarPolicy.php
class GoogleCalendarPolicy
{
    public function connect(User $user): bool
    {
        return $user->hasRole(['teacher', 'academy_admin']);
    }
    
    public function createEvent(User $user, QuranSession $session): bool
    {
        return $user->can('manage', $session) && $user->hasValidGoogleCalendarToken();
    }
}

// In controller
public function createSession(CreateSessionRequest $request): JsonResponse
{
    $this->authorize('create', QuranSession::class);
    
    // Additional checks for Google Calendar integration
    if (!auth()->user()->hasValidGoogleCalendarToken()) {
        return response()->json([
            'message' => 'يرجى ربط حسابك مع Google Calendar أولاً',
            'action_required' => 'connect_google_calendar'
        ], 400);
    }
    
    // Proceed with session creation
}
```

## Testing Calendar Integration

### Mock Google Services in Tests
```php
// tests/Feature/GoogleCalendarIntegrationTest.php
class GoogleCalendarIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_creates_session_with_meet_link(): void
    {
        // Mock Google Calendar service
        $mockService = Mockery::mock(Google_Service_Calendar::class);
        $mockEvent = Mockery::mock(Google_Service_Calendar_Event::class);
        
        $mockEvent->shouldReceive('getId')->andReturn('google_event_123');
        $mockEvent->shouldReceive('getHangoutLink')
                  ->andReturn('https://meet.google.com/abc-def-ghi');
        
        $mockService->events = Mockery::mock();
        $mockService->events->shouldReceive('insert')
                           ->andReturn($mockEvent);
        
        app()->instance(Google_Service_Calendar::class, $mockService);
        
        // Create session
        $teacher = QuranTeacher::factory()->create();
        $this->actingAs($teacher->user);
        
        $response = $this->postJson('/api/sessions', [
            'title' => 'درس تحفيظ سورة الفاتحة',
            'start_time' => now()->addHour()->toISOString(),
            'end_time' => now()->addHours(2)->toISOString(),
            'teacher_id' => $teacher->id,
        ]);
        
        $response->assertStatus(201);
        $session = QuranSession::first();
        $this->assertNotNull($session->meet_url);
        $this->assertEquals('google_event_123', $session->google_event_id);
    }
}
```

## Common Pitfalls to Avoid

```php
// ❌ DON'T: Forget conferenceDataVersion parameter
$this->service->events->insert($calendarId, $event); // Missing parameter

// ❌ DON'T: Use static request IDs
'requestId' => 'static_id' // Should be unique per request

// ❌ DON'T: Ignore token expiration
$this->client->setAccessToken($token); // Check if expired first

// ❌ DON'T: Block the main thread for API calls
$this->createGoogleCalendarEvent($data); // Use queued jobs instead

// ❌ DON'T: Store unencrypted sensitive data
'google_token' => $token // Should be encrypted

// ❌ DON'T: Create events without proper error handling
$event = $this->service->events->insert($calendarId, $event);
// Should wrap in try-catch with retry logic
```

## Arabic Content Handling

### Localized Event Creation
```php
// ✅ DO: Create events with proper Arabic content
$event = new Google_Service_Calendar_Event([
    'summary' => "درس القرآن الكريم - {$session->title}",
    'description' => "جلسة حفظ مع الأستاذ {$teacher->name}\n" .
                    "الطلاب: " . $session->students->pluck('name')->join('، ') . "\n" .
                    "رابط الجلسة سيتم إرساله قبل الموعد بـ 15 دقيقة",
    'location' => 'منصة إتقان - جلسة افتراضية',
]);
```

### Timezone Handling for Arabic Regions
```php
// ✅ DO: Handle different timezones properly
public function createEventForAcademy(Academy $academy, array $eventData): Google_Service_Calendar_Event
{
    $timezone = $academy->timezone ?? 'Asia/Riyadh';
    
    $event = new Google_Service_Calendar_Event([
        'start' => [
            'dateTime' => $eventData['start_time'],
            'timeZone' => $timezone
        ],
        'end' => [
            'dateTime' => $eventData['end_time'], 
            'timeZone' => $timezone
        ]
    ]);
    
    return $this->service->events->insert($academy->google_calendar_id, $event);
}
```