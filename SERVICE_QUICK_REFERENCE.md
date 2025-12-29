# Service Layer Quick Reference

Quick reference guide for developers working with the refactored service layer.

---

## When to Use Which Service

### Session Status Management

**Need to**: Change a single session's status
**Use**: `UnifiedSessionStatusService` (Facade)
```php
use App\Services\UnifiedSessionStatusService;

public function __construct(
    protected UnifiedSessionStatusService $statusService
) {}

public function startSession(BaseSession $session)
{
    $this->statusService->transitionToOngoing($session);
}
```

**Need to**: Process status transitions in batch (cron jobs)
**Use**: `SessionSchedulerService` (Direct injection)
```php
use App\Services\SessionSchedulerService;

public function __construct(
    protected SessionSchedulerService $schedulerService
) {}

public function handle()
{
    $sessions = QuranSession::scheduled()->get();
    $this->schedulerService->processStatusTransitions($sessions);
}
```

---

### Earnings Calculation

**Need to**: Calculate earnings for a session
**Use**: `EarningsCalculationService` (Facade)
```php
use App\Services\EarningsCalculationService;

public function __construct(
    protected EarningsCalculationService $earningsService
) {}

public function handle(BaseSession $session)
{
    $earning = $this->earningsService->calculateSessionEarnings($session);
}
```

**Need to**: Just calculate amount without persisting
**Use**: `EarningsCalculatorService` (Direct injection)
```php
use App\Services\EarningsCalculatorService;

public function __construct(
    protected EarningsCalculatorService $calculator
) {}

public function preview(BaseSession $session)
{
    $amount = $this->calculator->calculateEarnings($session);
    // Does not save to database
}
```

---

### Attendance Tracking

**Need to**: Handle LiveKit events (join/leave)
**Use**: `MeetingAttendanceService` (Facade)
```php
use App\Services\MeetingAttendanceService;

public function __construct(
    protected MeetingAttendanceService $attendanceService
) {}

public function participantJoined(BaseSession $session, User $user)
{
    $this->attendanceService->handleUserJoin($session, $user);
}
```

**Need to**: Only calculate attendance statistics
**Use**: `AttendanceCalculationService` (Direct injection)
```php
use App\Services\AttendanceCalculationService;

public function __construct(
    protected AttendanceCalculationService $calculator
) {}

public function getStats(BaseSession $session)
{
    return $this->calculator->getAttendanceStatistics($session);
}
```

---

### Homework Management

**Need to**: Get all homework types for a student
**Use**: `UnifiedHomeworkService`
```php
use App\Services\UnifiedHomeworkService;

public function __construct(
    protected UnifiedHomeworkService $homeworkService
) {}

public function studentHomework(int $studentId, int $academyId)
{
    // Returns Academic + Interactive + Quran homework
    return $this->homeworkService->getStudentHomework(
        $studentId,
        $academyId,
        status: 'pending',
        type: null // null = all types
    );
}
```

**Need to**: Only academic homework operations
**Use**: `HomeworkService` (Academic-specific)
```php
use App\Services\HomeworkService;

public function __construct(
    protected HomeworkService $homeworkService
) {}

public function createHomework(array $data)
{
    return $this->homeworkService->createAcademicHomework($data);
}
```

---

### Circle Reports

**Need to**: Generate a circle report
**Use**: `QuranCircleReportService` (Facade)
```php
use App\Services\QuranCircleReportService;

public function __construct(
    protected QuranCircleReportService $reportService
) {}

public function show(QuranIndividualCircle $circle)
{
    return $this->reportService->getIndividualCircleReport($circle);
}
```

**Need to**: Just fetch data without formatting
**Use**: `CircleDataFetcherService` (Direct injection)
```php
use App\Services\CircleDataFetcherService;

public function __construct(
    protected CircleDataFetcherService $dataFetcher
) {}

public function export(QuranIndividualCircle $circle)
{
    $data = $this->dataFetcher->fetchIndividualCircleData($circle);
    // Raw data for custom processing
}
```

---

### Subscription Management

**Need to**: Any subscription operation
**Use**: `SubscriptionService` (All-in-one)
```php
use App\Services\SubscriptionService;

public function __construct(
    protected SubscriptionService $subscriptionService
) {}

// Create subscription
public function subscribe(array $data)
{
    return $this->subscriptionService->createQuranSubscription($data);
}

// Get student subscriptions
public function studentSubs(int $studentId)
{
    return $this->subscriptionService->getStudentSubscriptions($studentId);
}

// Activate after payment
public function activate(BaseSubscription $sub, float $amount)
{
    return $this->subscriptionService->activate($sub, $amount);
}
```

---

## Service Injection Patterns

### Facade Service (Recommended for Most Cases)
```php
class MyController extends Controller
{
    public function __construct(
        protected UnifiedSessionStatusService $statusService,
        protected EarningsCalculationService $earningsService,
        protected MeetingAttendanceService $attendanceService
    ) {}

    // Use services through their facades
}
```

### Direct Service Injection (For Specific Needs)
```php
class MyAdvancedController extends Controller
{
    public function __construct(
        // Inject specific implementation services when needed
        protected SessionTransitionService $transitionService,
        protected EarningsCalculatorService $calculator,
        protected CircleDataFetcherService $dataFetcher
    ) {}

    // Use services directly for advanced control
}
```

### Mixed Approach (When You Need Both)
```php
class MyComplexService
{
    public function __construct(
        // Facade for public API
        protected UnifiedSessionStatusService $statusService,

        // Direct for specific operations
        protected SessionSchedulerService $schedulerService,
        protected AttendanceCalculationService $attendanceCalc
    ) {}

    public function complexOperation()
    {
        // Use facade for standard operations
        $this->statusService->transitionToOngoing($session);

        // Use direct service for specific needs
        $stats = $this->attendanceCalc->getAttendanceStatistics($session);
    }
}
```

---

## Common Patterns

### Pattern 1: Transaction Safety
```php
use Illuminate\Support\Facades\DB;

public function createWithEarnings(BaseSession $session)
{
    return DB::transaction(function () use ($session) {
        // Lock row
        $session = BaseSession::lockForUpdate()->find($session->id);

        // Perform operations
        $this->statusService->transitionToCompleted($session);
        $earning = $this->earningsService->calculateSessionEarnings($session);

        return $earning;
    });
}
```

### Pattern 2: Service Composition
```php
class ComplexOperationService
{
    public function __construct(
        protected UnifiedSessionStatusService $statusService,
        protected MeetingAttendanceService $attendanceService,
        protected EarningsCalculationService $earningsService
    ) {}

    public function completeSession(BaseSession $session)
    {
        // Calculate final attendance
        $attendance = $this->attendanceService->calculateFinalAttendance($session);

        // Transition status
        $this->statusService->transitionToCompleted($session);

        // Calculate earnings
        $earning = $this->earningsService->calculateSessionEarnings($session);

        return [
            'attendance' => $attendance,
            'earning' => $earning,
        ];
    }
}
```

### Pattern 3: Strategy Selection (Homework)
```php
public function getHomework(int $studentId, string $type)
{
    // UnifiedHomeworkService handles strategy selection internally
    return $this->homeworkService->getStudentHomework(
        $studentId,
        $academyId,
        status: null,
        type: $type // 'academic', 'interactive', 'quran', or null for all
    );
}
```

---

## Error Handling

### Facade Services (Recommended)
```php
try {
    $this->statusService->transitionToOngoing($session);
} catch (\Exception $e) {
    Log::error('Status transition failed', [
        'session_id' => $session->id,
        'error' => $e->getMessage()
    ]);
    return false;
}
```

### With Throw on Error Option
```php
// Some services support throwOnError parameter
$success = $this->statusService->transitionToOngoing(
    $session,
    throwOnError: false // Return false instead of throwing
);

if (!$success) {
    // Handle failure
}
```

---

## Performance Tips

### 1. Eager Loading
```php
// Always eager load relationships before passing to services
$sessions = QuranSession::with([
    'quranTeacher.user',
    'meetingAttendances',
    'studentReport'
])->get();

foreach ($sessions as $session) {
    $this->earningsService->calculateSessionEarnings($session);
}
```

### 2. Batch Processing
```php
// Use scheduler service for batch operations
$sessions = QuranSession::scheduled()
    ->whereBetween('scheduled_at', [now()->subMinutes(5), now()])
    ->get();

// More efficient than individual transitions
$this->schedulerService->processStatusTransitions($sessions);
```

### 3. Caching
```php
use Illuminate\Support\Facades\Cache;

// Services handle caching internally, but you can also cache at controller level
public function getReport(QuranIndividualCircle $circle)
{
    return Cache::remember(
        "circle.report.{$circle->id}",
        3600,
        fn() => $this->reportService->getIndividualCircleReport($circle)
    );
}
```

---

## Testing Examples

### Unit Test (Isolated Service)
```php
use Tests\TestCase;
use App\Services\SessionTransitionService;

class SessionTransitionServiceTest extends TestCase
{
    public function test_transition_to_ongoing()
    {
        $session = QuranSession::factory()->create([
            'status' => SessionStatus::READY
        ]);

        $service = new SessionTransitionService();
        $result = $service->transitionToOngoing($session);

        $this->assertTrue($result);
        $this->assertEquals(SessionStatus::ONGOING, $session->fresh()->status);
    }
}
```

### Integration Test (Facade Service)
```php
use Tests\TestCase;
use App\Services\UnifiedSessionStatusService;

class UnifiedSessionStatusServiceTest extends TestCase
{
    public function test_complete_session_workflow()
    {
        $session = QuranSession::factory()->create([
            'status' => SessionStatus::READY
        ]);

        $service = app(UnifiedSessionStatusService::class);

        // Test full workflow
        $service->transitionToOngoing($session);
        $this->assertEquals(SessionStatus::ONGOING, $session->fresh()->status);

        $service->transitionToCompleted($session);
        $this->assertEquals(SessionStatus::COMPLETED, $session->fresh()->status);
    }
}
```

### Mocking Services
```php
use Tests\TestCase;
use App\Services\EarningsCalculationService;
use Mockery;

class PaymentControllerTest extends TestCase
{
    public function test_calculate_payment()
    {
        // Mock the service
        $mock = Mockery::mock(EarningsCalculationService::class);
        $mock->shouldReceive('calculateSessionEarnings')
            ->once()
            ->andReturn(new TeacherEarning(['amount' => 100]));

        $this->app->instance(EarningsCalculationService::class, $mock);

        // Test controller
        $response = $this->get('/payments/calculate/1');
        $response->assertStatus(200);
    }
}
```

---

## Migration from Old Code

### Before (Fat Service)
```php
// Old code with mixed responsibilities
class OldSessionService
{
    public function updateSession($session)
    {
        // 500+ lines of mixed logic
        // Validation, status updates, earnings, attendance, etc.
    }
}
```

### After (Clean Separation)
```php
// New code with clear separation
class SessionController
{
    public function __construct(
        protected UnifiedSessionStatusService $statusService,
        protected EarningsCalculationService $earningsService,
        protected MeetingAttendanceService $attendanceService
    ) {}

    public function complete(BaseSession $session)
    {
        $this->attendanceService->calculateFinalAttendance($session);
        $this->statusService->transitionToCompleted($session);
        $this->earningsService->calculateSessionEarnings($session);
    }
}
```

---

## Debugging Tips

### Enable Service Logging
```php
// In service constructors
public function __construct()
{
    if (config('app.debug')) {
        Log::info('Service initialized: ' . static::class);
    }
}

// In methods
public function transitionToOngoing(BaseSession $session)
{
    Log::debug('Transitioning session', [
        'session_id' => $session->id,
        'from' => $session->status,
        'to' => SessionStatus::ONGOING
    ]);

    // ... logic
}
```

### Check Service Bindings
```php
// In tinker or test
php artisan tinker

>>> app(App\Services\UnifiedSessionStatusService::class)
=> App\Services\UnifiedSessionStatusService

>>> app(App\Contracts\SessionStatusServiceInterface::class)
=> App\Services\UnifiedSessionStatusService // Should resolve to facade
```

### Verify Service Dependencies
```php
// Check constructor dependencies
$service = app(UnifiedSessionStatusService::class);
$reflection = new ReflectionClass($service);
$constructor = $reflection->getConstructor();
$params = $constructor->getParameters();

foreach ($params as $param) {
    echo $param->getType() . "\n";
}
// Should show: SessionTransitionService, SessionSchedulerService
```

---

## Quick Lookup Table

| Need to... | Use This Service | Method |
|-----------|-----------------|--------|
| Change session status | UnifiedSessionStatusService | transitionTo*() |
| Batch status updates | SessionSchedulerService | processStatusTransitions() |
| Calculate earnings | EarningsCalculationService | calculateSessionEarnings() |
| Track attendance | MeetingAttendanceService | handleUserJoin/Leave() |
| Get homework | UnifiedHomeworkService | getStudentHomework() |
| Create academic homework | HomeworkService | createAcademicHomework() |
| Generate circle report | QuranCircleReportService | getIndividualCircleReport() |
| Manage subscriptions | SubscriptionService | create/activate/cancel() |
| Get attendance stats | AttendanceCalculationService | getAttendanceStatistics() |
| Send notifications | AttendanceNotificationService | broadcastAttendanceUpdate() |

---

## Best Practices

### ✅ Do
- Inject facade services in controllers
- Use direct services in complex operations when needed
- Leverage dependency injection
- Write unit tests for isolated services
- Use transactions for multi-step operations
- Eager load relationships before service calls
- Cache expensive report generations

### ❌ Don't
- Instantiate services with `new` (use DI)
- Mix facade and direct service calls for same operation
- Skip eager loading (causes N+1 queries)
- Perform database operations in controllers
- Hardcode service dependencies
- Bypass services to access models directly
- Forget to handle exceptions

---

**Quick Reference Version**: 1.0
**Last Updated**: 2025-12-29
**For Questions**: See SERVICE_REFACTORING_COMPLETE.md
