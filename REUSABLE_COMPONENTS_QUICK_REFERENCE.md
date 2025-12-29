# Reusable Components Quick Reference

Quick guide to reusable components in the Itqan Platform codebase.

## Controllers

### HasParentChildren Trait

**File**: `/app/Http/Controllers/Traits/HasParentChildren.php`

**When to use**: When you need to filter children by a selected child ID in parent controllers.

**Quick Example**:
```php
use App\Http\Controllers\Traits\HasParentChildren;

class MyParentController extends Controller
{
    use HasParentChildren;

    public function index(Request $request)
    {
        $children = auth()->user()->parentProfile->students;
        $selectedChildId = $request->get('child_id', 'all');

        $childUserIds = $this->getChildUserIds($children, $selectedChildId);
        // Returns array of user_id values
    }
}
```

### ApiResponses Trait

**File**: `/app/Http/Controllers/Traits/ApiResponses.php`

**When to use**: In all API controllers for standardized JSON responses.

**Quick Example**:
```php
use App\Http\Controllers\Traits\ApiResponses;

class MyApiController extends Controller
{
    use ApiResponses;

    public function index()
    {
        return $this->successResponse(
            data: $data,
            message: 'Data retrieved successfully'
        );
    }

    public function store()
    {
        return $this->errorResponse(
            message: 'Validation failed',
            errors: $errors,
            code: 422
        );
    }
}
```

## Services

### SessionFetchingService

**File**: `/app/Services/SessionFetchingService.php`

**When to use**: When you need to fetch sessions across all types (Quran, Academic, Interactive).

**Quick Examples**:

```php
use App\Services\SessionFetchingService;

// In constructor
public function __construct(
    protected SessionFetchingService $sessionFetchingService
) {
}

// Get today's sessions
$todaySessions = $this->sessionFetchingService->getTodaySessions($userId);

// Get upcoming sessions (next 7 days)
$upcomingSessions = $this->sessionFetchingService->getUpcomingSessions($userId);

// Get recent sessions (past 7 days)
$recentSessions = $this->sessionFetchingService->getRecentSessions($userId);

// For parent dashboards (optimized for multiple children)
$upcomingSessions = $this->sessionFetchingService->getAllChildrenUpcomingSessions($childUserIds);

// Get today's count only (faster)
$count = $this->sessionFetchingService->getTodaySessionsCount($userId);
```

### ParentChildVerificationService

**File**: `/app/Services/ParentChildVerificationService.php`

**When to use**: When you need to verify parent-child relationships.

**Quick Examples**:
```php
use App\Services\ParentChildVerificationService;

public function __construct(
    protected ParentChildVerificationService $verificationService
) {
}

// Get all child user IDs
$childUserIds = $this->verificationService->getChildUserIds($parent);

// Verify session belongs to parent's child
$this->verificationService->verifySessionBelongsToParent($parent, $session);

// Verify payment belongs to parent's child
$this->verificationService->verifyPaymentBelongsToParent($parent, $payment);
```

### CalendarService

**File**: `/app/Services/CalendarService.php`

**When to use**: When you need to fetch calendar events for users.

**Quick Examples**:
```php
use App\Services\CalendarService;

public function __construct(
    protected CalendarService $calendarService
) {
}

// Get events for a date range
$events = $this->calendarService->getEventsForUser(
    user: $user,
    startDate: $startDate,
    endDate: $endDate
);

// Events are pre-formatted with color, title, URL, etc.
```

### ApiResponseService

**File**: `/app/Services/ApiResponseService.php`

**When to use**: For standardized API responses (usually via ApiResponses trait).

**Quick Examples**:
```php
use App\Services\ApiResponseService;

// Success response
return ApiResponseService::success(
    data: $data,
    message: 'Operation successful'
);

// Error response
return ApiResponseService::error(
    message: 'Error occurred',
    code: 400
);

// Not found
return ApiResponseService::notFound('Resource not found');

// Validation error
return ApiResponseService::validationError($validator->errors());
```

## Traits

### AttendanceCalculatorTrait

**File**: `/app/Services/Traits/AttendanceCalculatorTrait.php`

**When to use**: When you need to calculate attendance status based on join time and duration.

**Quick Examples**:
```php
use App\Services\Traits\AttendanceCalculatorTrait;

class MyAttendanceService
{
    use AttendanceCalculatorTrait;

    public function processAttendance($session, $participant)
    {
        $status = $this->calculateAttendanceStatus(
            firstJoinTime: $participant->joined_at,
            sessionStartTime: $session->scheduled_at,
            sessionDurationMinutes: $session->duration_minutes,
            actualAttendanceMinutes: $participant->total_minutes,
            graceMinutes: 15
        );
        // Returns: 'attended', 'late', 'left', or 'absent'
    }

    public function getAttendancePercentage($actualMinutes, $sessionDuration)
    {
        return $this->calculateAttendancePercentage($actualMinutes, $sessionDuration);
        // Returns: 0-100 (capped at 100)
    }
}
```

## Models

### BaseSession Traits

All session models can use these traits:

**HasSessionStatus**:
```php
// Check if session can be started
if ($session->canStart()) {
    // Start session
}

// Check if session is active
if ($session->isActive()) {
    // Handle active session
}
```

**HasSessionScheduling**:
```php
// Check if session is upcoming
$upcomingSessions = QuranSession::upcoming()->get();

// Get today's sessions
$todaySessions = AcademicSession::today()->get();

// Get past sessions
$pastSessions = InteractiveCourseSession::past()->get();
```

## Enums

### All Enums Support

**Label Method**:
```php
SessionStatus::SCHEDULED->label(); // Returns localized label
AttendanceStatus::ATTENDED->label(); // Returns localized label
```

**Color Methods**:
```php
SessionStatus::ONGOING->color(); // Returns Filament color class
SessionStatus::ONGOING->hexColor(); // Returns hex color for calendars
```

**Icon Method**:
```php
SessionStatus::COMPLETED->icon(); // Returns icon class
```

**Options for Forms**:
```php
SessionStatus::options(); // ['scheduled' => 'Scheduled', ...]
SessionStatus::teacherIndividualOptions(); // Filtered for teacher use
```

## Common Patterns

### Dashboard Data Fetching

```php
use App\Services\SessionFetchingService;

public function dashboard(Request $request)
{
    $userId = auth()->id();

    // Get all session types for today
    $todaySessions = $this->sessionFetchingService->getTodaySessions($userId);

    // Get upcoming sessions
    $upcomingSessions = $this->sessionFetchingService->getUpcomingSessions($userId);

    // Process sessions for display
    $formatted = array_map(function ($item) {
        return [
            'type' => $item['type'],
            'title' => $this->getSessionTitle($item['session'], $item['type']),
            'time' => $item['session']->scheduled_at,
        ];
    }, $todaySessions);

    return view('dashboard', compact('formatted', 'upcomingSessions'));
}
```

### Parent Multi-Child Filtering

```php
use App\Http\Controllers\Traits\HasParentChildren;
use App\Services\ParentChildVerificationService;

class MyParentController extends Controller
{
    use HasParentChildren;

    public function __construct(
        protected ParentChildVerificationService $verificationService
    ) {
    }

    public function index(Request $request)
    {
        $parent = auth()->user()->parentProfile;
        $children = $this->verificationService->getChildrenWithUsers($parent);
        $selectedChildId = $request->get('child_id', 'all');

        // Get filtered user IDs
        $childUserIds = $this->getChildUserIds($children, $selectedChildId);

        // Use in queries
        $sessions = QuranSession::whereIn('student_id', $childUserIds)->get();
    }
}
```

### API Controller Pattern

```php
use App\Http\Controllers\Traits\ApiResponses;

class MyApiController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        try {
            $data = $this->someService->getData();

            return $this->successResponse(
                data: $data,
                message: 'Data retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                message: 'Failed to retrieve data',
                code: 500
            );
        }
    }

    public function store(StoreRequest $request)
    {
        $validated = $request->validated();

        $resource = $this->someService->create($validated);

        return $this->createdResponse(
            data: $resource,
            message: 'Resource created successfully'
        );
    }
}
```

## Best Practices

1. **Always use dependency injection** for services
2. **Use traits for shared controller behavior**
3. **Use services for business logic**
4. **Use enums for status/type values**
5. **Leverage existing components before creating new ones**

## Finding Components

All reusable components are located in:
- **Traits**: `/app/Http/Controllers/Traits/` and `/app/Services/Traits/`
- **Services**: `/app/Services/`
- **Enums**: `/app/Enums/`
- **Model Traits**: `/app/Models/Traits/`

Use `grep` to find usage examples:
```bash
# Find trait usage
grep -r "use HasParentChildren" app/

# Find service usage
grep -r "SessionFetchingService" app/

# Find enum usage
grep -r "SessionStatus::" app/
```
