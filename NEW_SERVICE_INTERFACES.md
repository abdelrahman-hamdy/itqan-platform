# New Service Interfaces Implementation

This document describes the five new service interfaces created to improve code maintainability, testability, and adherence to SOLID principles.

## Overview

Five critical service interfaces have been created following the existing interface patterns in `app/Contracts/`:

1. **SubscriptionServiceInterface** - Unified subscription management
2. **NotificationServiceInterface** - Notification operations
3. **AutoMeetingCreationServiceInterface** - Automatic meeting creation
4. **RecordingServiceInterface** - Session recording management
5. **UnifiedSessionStatusServiceInterface** - Session status lifecycle

## Interface Details

### 1. SubscriptionServiceInterface

**Location**: `/app/Contracts/SubscriptionServiceInterface.php`

**Implementation**: `App\Services\SubscriptionService`

**Purpose**: Provides a unified interface for managing all subscription types (Quran, Academic, Course) with consistent operations.

**Key Methods**:
- `create(string $type, array $data): BaseSubscription` - Factory method for creating subscriptions
- `activate(BaseSubscription $subscription, ?float $amountPaid): BaseSubscription` - Activate after payment
- `cancel(BaseSubscription $subscription, ?string $reason): BaseSubscription` - Cancel subscription
- `getStudentSubscriptions(int $studentId, ?int $academyId): Collection` - Get all student subscriptions
- `getAcademyStatistics(int $academyId): array` - Get academy subscription statistics
- `getExpiringSoon(int $academyId, int $days): Collection` - Find subscriptions expiring soon
- `changeBillingCycle(BaseSubscription $subscription, BillingCycle $newCycle): BaseSubscription`

**Design Patterns**:
- Facade Pattern: Single entry point for subscription operations
- Factory Method: Creates appropriate subscription types
- Repository Pattern: Centralized data access

### 2. NotificationServiceInterface

**Location**: `/app/Contracts/NotificationServiceInterface.php`

**Implementation**: `App\Services\NotificationService`

**Purpose**: Defines the contract for all notification operations including sending, managing, and retrieving notifications across different contexts.

**Key Methods**:
- `send(User|Collection $users, NotificationType $type, array $data, ...): void` - Main notification sender
- `markAsRead(string $notificationId, User $user): bool` - Mark as read
- `markAllAsPanelOpened(User $user): int` - Mark as seen in panel
- `getUnreadCount(User $user): int` - Get unread count
- `getNotifications(User $user, int $perPage, ?string $category): LengthAwarePaginator` - Paginated notifications
- `sendSessionScheduledNotification(Model $session, User $student): void`
- `sendPaymentSuccessNotification(User $user, array $paymentData): void`
- `sendSubscriptionRenewedNotification(User $student, array $subscriptionData): void`

**Delegated Services**:
- `NotificationDispatcher` - Core sending logic
- `NotificationRepository` - Database operations
- `SessionNotificationBuilder` - Session notifications
- `PaymentNotificationBuilder` - Payment notifications

### 3. AutoMeetingCreationServiceInterface

**Location**: `/app/Contracts/AutoMeetingCreationServiceInterface.php`

**Implementation**: `App\Services\AutoMeetingCreationService`

**Purpose**: Handles automatic creation of LiveKit meeting rooms for sessions based on academy-specific video settings.

**Key Methods**:
- `createMeetingsForAllAcademies(): array` - Main entry point for scheduled job
- `createMeetingsForAcademy(Academy $academy): array` - Process single academy
- `cleanupExpiredMeetings(): array` - End meetings that exceeded duration
- `getStatistics(): array` - Get auto-meeting system statistics
- `testMeetingCreation(QuranSession $session): array` - Test meeting creation

**Returns**: Detailed result arrays with counts and error information for monitoring and debugging.

### 4. RecordingServiceInterface

**Location**: `/app/Contracts/RecordingServiceInterface.php`

**Implementation**: `App\Services\RecordingService`

**Purpose**: Manages session recording lifecycle via LiveKit Egress including starting, stopping, and webhook processing.

**Key Methods**:
- `startRecording(RecordingCapable $session): SessionRecording` - Start recording
- `stopRecording(SessionRecording $recording): bool` - Stop recording
- `processEgressWebhook(array $webhookData): bool` - Handle LiveKit webhooks
- `getSessionRecordings(RecordingCapable $session): Collection` - Get all recordings
- `deleteRecording(SessionRecording $recording, bool $removeFile): bool` - Delete recording
- `getRecordingStatistics(array $filters): array` - Recording statistics

**Works With**: Any session implementing `RecordingCapable` interface.

### 5. UnifiedSessionStatusServiceInterface

**Location**: `/app/Contracts/UnifiedSessionStatusServiceInterface.php`

**Implementation**: `App\Services\UnifiedSessionStatusService`

**Purpose**: Handles session lifecycle state transitions for all session types (Quran, Academic, Interactive Course).

**Key Methods**:
- `transitionToReady(BaseSession $session, bool $throwOnError): bool` - SCHEDULED → READY
- `transitionToOngoing(BaseSession $session, bool $throwOnError): bool` - READY → ONGOING
- `transitionToCompleted(BaseSession $session, bool $throwOnError): bool` - ONGOING → COMPLETED
- `transitionToCancelled(BaseSession $session, ?string $reason, ?int $cancelledBy, bool $throwOnError): bool`
- `transitionToAbsent(BaseSession $session): bool` - Individual sessions only
- `shouldTransitionToReady(BaseSession $session): bool` - Check if ready for transition
- `processStatusTransitions(Collection $sessions): array` - Batch processing for scheduled jobs

**Session Lifecycle**:
```
SCHEDULED → READY → ONGOING → COMPLETED
                      ↓ (individual only)
                    ABSENT
    ↓
CANCELLED
```

**Delegated Services**:
- `SessionSettingsService` - Session-specific settings
- `SessionNotificationService` - Notification dispatching

## Service Provider Registration

All interfaces are registered in `AppServiceProvider::register()`:

```php
$this->app->bind(\App\Contracts\SubscriptionServiceInterface::class, \App\Services\SubscriptionService::class);
$this->app->bind(\App\Contracts\NotificationServiceInterface::class, \App\Services\NotificationService::class);
$this->app->bind(\App\Contracts\AutoMeetingCreationServiceInterface::class, \App\Services\AutoMeetingCreationService::class);
$this->app->bind(\App\Contracts\RecordingServiceInterface::class, \App\Services\RecordingService::class);
$this->app->bind(\App\Contracts\UnifiedSessionStatusServiceInterface::class, \App\Services\UnifiedSessionStatusService::class);
```

## Usage Examples

### Type-Hinted Dependency Injection

```php
use App\Contracts\SubscriptionServiceInterface;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionServiceInterface $subscriptionService
    ) {}

    public function store(Request $request)
    {
        $subscription = $this->subscriptionService->create('quran', [
            'student_id' => $request->student_id,
            'academy_id' => $request->academy_id,
            // ... more data
        ]);

        return response()->json($subscription);
    }
}
```

### Notification Service

```php
use App\Contracts\NotificationServiceInterface;
use App\Enums\NotificationType;

class SessionController extends Controller
{
    public function __construct(
        private NotificationServiceInterface $notificationService
    ) {}

    public function scheduleSession(Request $request)
    {
        $session = QuranSession::create($request->validated());

        // Send notification
        $this->notificationService->sendSessionScheduledNotification(
            $session,
            $session->student
        );

        return response()->json($session);
    }
}
```

### Session Status Service

```php
use App\Contracts\UnifiedSessionStatusServiceInterface;

class SessionStatusCommand extends Command
{
    public function __construct(
        private UnifiedSessionStatusServiceInterface $statusService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $sessions = QuranSession::scheduled()
            ->where('scheduled_at', '<=', now()->addHours(2))
            ->get();

        $results = $this->statusService->processStatusTransitions($sessions);

        $this->info("Processed {$results['transitions_to_ready']} ready transitions");
    }
}
```

## Benefits

### 1. Improved Testability
Interfaces allow easy mocking in unit tests:

```php
$mockSubscriptionService = Mockery::mock(SubscriptionServiceInterface::class);
$mockSubscriptionService->shouldReceive('create')
    ->once()
    ->andReturn($subscription);
```

### 2. Dependency Inversion (SOLID)
Controllers and commands depend on abstractions, not concrete implementations.

### 3. Easier Refactoring
Internal implementation can change without affecting consumers.

### 4. Better IDE Support
IDEs can provide autocomplete and type hints based on interface contracts.

### 5. Documentation
Interfaces serve as executable documentation of service capabilities.

### 6. Flexibility
Easy to swap implementations (e.g., for different payment gateways or notification channels).

## Interface Design Principles

All interfaces follow these guidelines:

1. **Comprehensive PHPDoc**: Every method has detailed documentation
2. **Return Type Hints**: All methods specify return types
3. **Parameter Type Hints**: All parameters are typed
4. **Descriptive Names**: Method names clearly indicate their purpose
5. **Grouped Methods**: Related methods are logically grouped with comments
6. **Usage Examples in Docs**: Interface docblocks include usage context

## Testing Recommendations

When writing tests for these services:

1. **Mock the Interface**: Always type-hint and mock the interface, not the concrete class
2. **Test Contract Compliance**: Verify implementations satisfy interface contracts
3. **Test Return Types**: Ensure returned values match interface specifications
4. **Test Exception Handling**: Verify declared exceptions are thrown correctly

Example:

```php
public function test_subscription_creation_with_interface()
{
    $mock = $this->mock(SubscriptionServiceInterface::class);

    $mock->shouldReceive('create')
        ->with('quran', Mockery::any())
        ->andReturn(new QuranSubscription(['id' => 1]));

    $this->app->instance(SubscriptionServiceInterface::class, $mock);

    // Test controller or command that uses the service
}
```

## Migration Strategy

To migrate existing code to use these interfaces:

1. **Update Constructor Injection**: Replace concrete class with interface
2. **Update Method Calls**: Ensure using interface-defined methods
3. **Update Tests**: Mock interfaces instead of concrete classes
4. **Verify Behavior**: Run existing tests to ensure no breakage

## Related Documentation

- **Existing Interfaces**: See `/app/Contracts/` for similar patterns
- **Service Architecture**: `CLAUDE.md` section on Service Layer Pattern
- **SOLID Principles**: Project follows Dependency Inversion principle

## Future Enhancements

Consider creating interfaces for these services:

1. `PaymentServiceInterface` - Already exists
2. `CalendarServiceInterface` - Already exists
3. `SessionStatusServiceInterface` - Already exists
4. `ParentDashboardServiceInterface` - Could benefit from interface
5. `CertificateServiceInterface` - Could benefit from interface
6. `ChatPermissionServiceInterface` - Could benefit from interface

## Conclusion

These five new interfaces significantly improve the architecture of the Itqan Platform by:

- Enforcing consistent service contracts
- Enabling better testing practices
- Supporting future refactoring efforts
- Providing clear documentation of service capabilities
- Adhering to SOLID design principles

All interfaces are production-ready and fully integrated with the service provider bindings.
