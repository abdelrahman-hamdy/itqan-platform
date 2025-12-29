# Service Interfaces Quick Reference Guide

Quick lookup for the five new service interfaces and their most commonly used methods.

## 1. SubscriptionServiceInterface

**Use When**: Creating, activating, canceling, or querying subscriptions

**Common Operations**:

```php
use App\Contracts\SubscriptionServiceInterface;

// Inject in constructor
public function __construct(private SubscriptionServiceInterface $subscriptions) {}

// Create subscription
$subscription = $this->subscriptions->create('quran', [
    'student_id' => 1,
    'academy_id' => 1,
    'package_id' => 5,
    // ... package data
]);

// Activate after payment
$this->subscriptions->activate($subscription, $amountPaid);

// Cancel subscription
$this->subscriptions->cancel($subscription, 'Reason');

// Get student's subscriptions
$subscriptions = $this->subscriptions->getStudentSubscriptions($studentId);

// Get active subscriptions
$active = $this->subscriptions->getActiveSubscriptions($studentId);

// Find by code
$subscription = $this->subscriptions->findByCode('QS-1-001');

// Get expiring subscriptions
$expiring = $this->subscriptions->getExpiringSoon($academyId, 7);
```

---

## 2. NotificationServiceInterface

**Use When**: Sending or managing notifications

**Common Operations**:

```php
use App\Contracts\NotificationServiceInterface;
use App\Enums\NotificationType;

// Inject in constructor
public function __construct(private NotificationServiceInterface $notifications) {}

// Send custom notification
$this->notifications->send(
    $user,
    NotificationType::CUSTOM,
    ['message' => 'Hello!'],
    'https://url.com',
    ['key' => 'value'],
    isImportant: true
);

// Session notifications
$this->notifications->sendSessionScheduledNotification($session, $student);
$this->notifications->sendSessionReminderNotification($session, $student);
$this->notifications->sendHomeworkAssignedNotification($session, $student);

// Payment notifications
$this->notifications->sendPaymentSuccessNotification($user, [
    'amount' => 100,
    'subscription_code' => 'QS-1-001'
]);

// Mark as read
$this->notifications->markAsRead($notificationId, $user);

// Get unread count
$count = $this->notifications->getUnreadCount($user);

// Get paginated notifications
$notifications = $this->notifications->getNotifications($user, 20);
```

---

## 3. AutoMeetingCreationServiceInterface

**Use When**: Scheduling automatic meeting creation or cleanup

**Common Operations**:

```php
use App\Contracts\AutoMeetingCreationServiceInterface;

// Inject in constructor
public function __construct(private AutoMeetingCreationServiceInterface $meetingCreator) {}

// Process all academies (in scheduled command)
$results = $this->meetingCreator->createMeetingsForAllAcademies();
// Returns: ['total_academies_processed' => 5, 'meetings_created' => 12, ...]

// Process single academy
$results = $this->meetingCreator->createMeetingsForAcademy($academy);

// Clean up expired meetings
$results = $this->meetingCreator->cleanupExpiredMeetings();

// Get statistics
$stats = $this->meetingCreator->getStatistics();
// Returns: ['total_auto_generated_meetings' => 150, 'active_meetings' => 5, ...]

// Test meeting creation
$result = $this->meetingCreator->testMeetingCreation($session);
```

---

## 4. RecordingServiceInterface

**Use When**: Starting, stopping, or managing session recordings

**Common Operations**:

```php
use App\Contracts\RecordingServiceInterface;

// Inject in constructor
public function __construct(private RecordingServiceInterface $recordings) {}

// Start recording
$recording = $this->recordings->startRecording($session);

// Stop recording
$this->recordings->stopRecording($recording);

// Process webhook from LiveKit
$this->recordings->processEgressWebhook($webhookPayload);

// Get session recordings
$recordings = $this->recordings->getSessionRecordings($session);

// Delete recording
$this->recordings->deleteRecording($recording, removeFile: false);

// Get statistics
$stats = $this->recordings->getRecordingStatistics([
    'session_type' => QuranSession::class,
    'date_from' => now()->startOfMonth(),
]);
// Returns: ['total_count' => 50, 'total_size_bytes' => 500000, ...]
```

---

## 5. UnifiedSessionStatusServiceInterface

**Use When**: Managing session status transitions

**Common Operations**:

```php
use App\Contracts\UnifiedSessionStatusServiceInterface;

// Inject in constructor
public function __construct(private UnifiedSessionStatusServiceInterface $statusService) {}

// Manual transitions (with error handling)
try {
    $this->statusService->transitionToReady($session, throwOnError: true);
    $this->statusService->transitionToOngoing($session, throwOnError: true);
    $this->statusService->transitionToCompleted($session, throwOnError: true);
} catch (SessionOperationException $e) {
    // Handle invalid transition
}

// Cancel session
$this->statusService->transitionToCancelled(
    $session,
    reason: 'Teacher unavailable',
    cancelledBy: auth()->id()
);

// Mark as absent (individual sessions)
$this->statusService->transitionToAbsent($session);

// Check if should transition
if ($this->statusService->shouldTransitionToReady($session)) {
    $this->statusService->transitionToReady($session);
}

// Batch processing (in scheduled command)
$sessions = QuranSession::scheduled()->get();
$results = $this->statusService->processStatusTransitions($sessions);
// Returns: ['transitions_to_ready' => 5, 'transitions_to_completed' => 3, ...]
```

---

## Dependency Injection Tips

### In Controllers

```php
class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionServiceInterface $subscriptions
    ) {}
}
```

### In Commands

```php
class ProcessSessionStatusCommand extends Command
{
    public function __construct(
        private UnifiedSessionStatusServiceInterface $statusService
    ) {
        parent::__construct();
    }
}
```

### In Jobs

```php
class CreateMeetingsJob implements ShouldQueue
{
    public function handle(AutoMeetingCreationServiceInterface $meetingCreator)
    {
        $meetingCreator->createMeetingsForAllAcademies();
    }
}
```

### In Livewire Components

```php
class SessionManagement extends Component
{
    public function __construct(
        private UnifiedSessionStatusServiceInterface $statusService
    ) {}

    public function completeSession($sessionId)
    {
        $session = QuranSession::find($sessionId);
        $this->statusService->transitionToCompleted($session);
    }
}
```

---

## Testing with Interfaces

```php
use App\Contracts\SubscriptionServiceInterface;
use Mockery;

public function test_subscription_creation()
{
    // Mock the interface
    $mock = Mockery::mock(SubscriptionServiceInterface::class);

    // Set expectations
    $mock->shouldReceive('create')
        ->once()
        ->with('quran', Mockery::any())
        ->andReturn(new QuranSubscription(['id' => 1]));

    // Bind to container
    $this->app->instance(SubscriptionServiceInterface::class, $mock);

    // Test your code...
}
```

---

## Error Handling Patterns

### Subscription Activation

```php
try {
    $subscription = $this->subscriptions->activate($subscription, $amountPaid);
} catch (\Exception $e) {
    Log::error('Subscription activation failed', [
        'subscription_id' => $subscription->id,
        'error' => $e->getMessage()
    ]);
    throw $e;
}
```

### Session Status Transitions

```php
use App\Exceptions\SessionOperationException;

try {
    $this->statusService->transitionToCompleted($session, throwOnError: true);
} catch (SessionOperationException $e) {
    return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'type' => $e->getErrorType()
    ], 422);
}
```

### Recording Operations

```php
try {
    $recording = $this->recordings->startRecording($session);
} catch (\Exception $e) {
    Log::error('Failed to start recording', [
        'session_id' => $session->id,
        'error' => $e->getMessage()
    ]);
    return false;
}
```

---

## Best Practices

1. **Always type-hint interfaces** in constructor dependency injection
2. **Use throwOnError parameter** when you need strict error handling
3. **Log errors** with context for debugging
4. **Check return values** for boolean methods (they return false on failure)
5. **Use array results** from batch operations for monitoring
6. **Mock interfaces** in tests, not concrete implementations

---

## Related Files

- Interface Definitions: `/app/Contracts/`
- Service Implementations: `/app/Services/`
- Service Provider: `/app/Providers/AppServiceProvider.php`
- Full Documentation: `NEW_SERVICE_INTERFACES.md`

---

## Common Patterns Across All Interfaces

All interfaces follow these patterns:

1. **Comprehensive Documentation**: Every method has PHPDoc
2. **Type Safety**: Full type hints for parameters and returns
3. **Consistent Naming**: Verb-based method names (create, get, send, transition)
4. **Array Returns for Batch Operations**: Provide detailed results
5. **Optional Parameters**: Use nullable types with sensible defaults
