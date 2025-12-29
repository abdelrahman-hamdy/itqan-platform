# Service Interfaces Quick Reference

## Quick Lookup Table

| Service | Interface | Location |
|---------|-----------|----------|
| UnifiedSessionStatusService | SessionStatusServiceInterface | `app/Contracts/SessionStatusServiceInterface.php` |
| EarningsCalculationService | EarningsCalculationServiceInterface | `app/Contracts/EarningsCalculationServiceInterface.php` |
| MeetingAttendanceService | MeetingAttendanceServiceInterface | `app/Contracts/MeetingAttendanceServiceInterface.php` |
| HomeworkService | HomeworkServiceInterface | `app/Contracts/HomeworkServiceInterface.php` |
| StudentDashboardService | StudentDashboardServiceInterface | `app/Contracts/StudentDashboardServiceInterface.php` |
| QuizService | QuizServiceInterface | `app/Contracts/QuizServiceInterface.php` |
| SearchService | SearchServiceInterface | `app/Contracts/SearchServiceInterface.php` |
| StudentStatisticsService | StudentStatisticsServiceInterface | `app/Contracts/StudentStatisticsServiceInterface.php` |
| CircleEnrollmentService | CircleEnrollmentServiceInterface | `app/Contracts/CircleEnrollmentServiceInterface.php` |
| CalendarService | CalendarServiceInterface | `app/Contracts/CalendarServiceInterface.php` |
| SubscriptionRenewalService | SubscriptionRenewalServiceInterface | `app/Contracts/SubscriptionRenewalServiceInterface.php` |
| CertificateService | CertificateServiceInterface | `app/Contracts/CertificateServiceInterface.php` |
| LiveKitService | LiveKitServiceInterface | `app/Contracts/LiveKitServiceInterface.php` |
| PaymentService | PaymentServiceInterface | `app/Contracts/PaymentServiceInterface.php` |
| AttendanceEventService | AttendanceEventServiceInterface | `app/Contracts/AttendanceEventServiceInterface.php` |

## Usage Patterns

### Pattern 1: Constructor Injection (Recommended)

```php
use App\Contracts\SessionStatusServiceInterface;

class SessionController extends Controller
{
    public function __construct(
        private SessionStatusServiceInterface $sessionStatus
    ) {}

    public function complete(BaseSession $session)
    {
        $this->sessionStatus->transitionToCompleted($session);
    }
}
```

### Pattern 2: Method Injection

```php
use App\Contracts\HomeworkServiceInterface;

class HomeworkController extends Controller
{
    public function store(
        Request $request,
        HomeworkServiceInterface $homeworkService
    ) {
        $homework = $homeworkService->createAcademicHomework($request->validated());
    }
}
```

### Pattern 3: Manual Resolution

```php
use App\Contracts\QuizServiceInterface;

$quizService = app(QuizServiceInterface::class);
$quiz = $quizService->createQuiz($data);
```

## Common Use Cases

### Session Management

```php
use App\Contracts\SessionStatusServiceInterface;

// Transition session to ready
$sessionStatus->transitionToReady($session);

// Check if should auto-complete
if ($sessionStatus->shouldAutoComplete($session)) {
    $sessionStatus->transitionToCompleted($session);
}

// Process multiple sessions
$results = $sessionStatus->processStatusTransitions($sessions);
```

### Attendance Tracking

```php
use App\Contracts\MeetingAttendanceServiceInterface;

// Handle user joining
$attendanceService->handleUserJoin($session, $user);

// Calculate final attendance
$results = $attendanceService->calculateFinalAttendance($session);

// Get statistics
$stats = $attendanceService->getAttendanceStatistics($session);
```

### Homework Management

```php
use App\Contracts\HomeworkServiceInterface;

// Create homework
$homework = $homeworkService->createAcademicHomework([
    'title' => 'Math Assignment',
    'description' => 'Complete exercises',
    'due_date' => now()->addWeek(),
]);

// Submit homework
$submission = $homeworkService->submitAcademicHomework(
    $homeworkId,
    $studentId,
    ['text' => 'My solution', 'files' => $files]
);

// Grade homework
$graded = $homeworkService->gradeAcademicHomework(
    $submissionId,
    85.5,
    'Good work!'
);
```

### Student Dashboard

```php
use App\Contracts\StudentDashboardServiceInterface;

// Load all dashboard data (cached)
$data = $dashboardService->loadDashboardData($user);

// Get specific data
$circles = $dashboardService->getQuranCircles($user, $academy);
$courses = $dashboardService->getInteractiveCourses($studentProfile, $academy);

// Clear cache when data changes
$dashboardService->clearStudentCache($userId, $academyId);
```

### Quiz Management

```php
use App\Contracts\QuizServiceInterface;

// Create quiz with questions
$quiz = $quizService->createQuiz(
    ['title' => 'Chapter 1 Quiz', 'passing_score' => 70],
    $questions
);

// Assign to course
$assignment = $quizService->assignQuiz($quiz, $course);

// Start attempt
$attempt = $quizService->startAttempt($assignment, $studentId);

// Submit answers
$result = $quizService->submitAttempt($attempt, $answers);
```

### Search

```php
use App\Contracts\SearchServiceInterface;

// Search all resources
$results = $searchService->searchAll('physics', $student, [
    'subject_id' => 5,
    'level' => 'intermediate'
]);

// Get total count
$count = $searchService->getTotalResultsCount($results);
```

### Statistics

```php
use App\Contracts\StudentStatisticsServiceInterface;

// Calculate all statistics (cached)
$stats = $statisticsService->calculate($user);

// Access specific stats
echo "Attendance Rate: {$stats['attendanceRate']}%";
echo "Completed Sessions: {$stats['totalCompletedSessions']}";
echo "Quran Progress: {$stats['quranProgress']}%";
```

### Circle Enrollment

```php
use App\Contracts\CircleEnrollmentServiceInterface;

// Check if can enroll
if ($enrollmentService->canEnroll($user, $circle)) {
    // Enroll student
    $result = $enrollmentService->enroll($user, $circle);

    if ($result['success']) {
        echo $result['message'];
    }
}

// Check enrollment status
$isEnrolled = $enrollmentService->isEnrolled($user, $circle);

// Get subscription
$subscription = $enrollmentService->getOrCreateSubscription($user, $circle);
```

### Earnings Calculation

```php
use App\Contracts\EarningsCalculationServiceInterface;

// Calculate earnings for completed session
$earning = $earningsService->calculateSessionEarnings($session);

if ($earning) {
    echo "Amount: {$earning->amount}";
    echo "Method: {$earning->calculation_method}";
}
```

## Testing with Interfaces

### Unit Test Example

```php
use App\Contracts\SessionStatusServiceInterface;
use Mockery;

public function test_session_completion()
{
    // Create mock
    $mock = Mockery::mock(SessionStatusServiceInterface::class);

    // Define expectations
    $mock->shouldReceive('transitionToCompleted')
         ->once()
         ->with(Mockery::type(BaseSession::class))
         ->andReturn(true);

    // Bind mock to container
    $this->app->instance(SessionStatusServiceInterface::class, $mock);

    // Run test
    $response = $this->post('/sessions/1/complete');

    $response->assertSuccessful();
}
```

### Integration Test Example

```php
use App\Contracts\HomeworkServiceInterface;

public function test_homework_submission_flow()
{
    // Use real implementation
    $homeworkService = app(HomeworkServiceInterface::class);

    // Create homework
    $homework = $homeworkService->createAcademicHomework([
        'title' => 'Test Assignment',
        'due_date' => now()->addWeek(),
    ]);

    // Submit
    $submission = $homeworkService->submitAcademicHomework(
        $homework->id,
        $this->student->id,
        ['text' => 'Solution']
    );

    // Assert
    $this->assertNotNull($submission);
    $this->assertEquals('submitted', $submission->submission_status);
}
```

## Interface Resolution Verification

Run this command to verify all interfaces are properly bound:

```bash
php artisan tinker --execute="
    echo 'SessionStatus: ' . get_class(app(\App\Contracts\SessionStatusServiceInterface::class)) . PHP_EOL;
    echo 'Earnings: ' . get_class(app(\App\Contracts\EarningsCalculationServiceInterface::class)) . PHP_EOL;
    echo 'Attendance: ' . get_class(app(\App\Contracts\MeetingAttendanceServiceInterface::class)) . PHP_EOL;
    echo 'Homework: ' . get_class(app(\App\Contracts\HomeworkServiceInterface::class)) . PHP_EOL;
    echo 'Dashboard: ' . get_class(app(\App\Contracts\StudentDashboardServiceInterface::class)) . PHP_EOL;
    echo 'Quiz: ' . get_class(app(\App\Contracts\QuizServiceInterface::class)) . PHP_EOL;
    echo 'Search: ' . get_class(app(\App\Contracts\SearchServiceInterface::class)) . PHP_EOL;
    echo 'Statistics: ' . get_class(app(\App\Contracts\StudentStatisticsServiceInterface::class)) . PHP_EOL;
    echo 'Enrollment: ' . get_class(app(\App\Contracts\CircleEnrollmentServiceInterface::class)) . PHP_EOL;
"
```

Expected output:
```
SessionStatus: App\Services\UnifiedSessionStatusService
Earnings: App\Services\EarningsCalculationService
Attendance: App\Services\MeetingAttendanceService
Homework: App\Services\HomeworkService
Dashboard: App\Services\StudentDashboardService
Quiz: App\Services\QuizService
Search: App\Services\SearchService
Statistics: App\Services\StudentStatisticsService
Enrollment: App\Services\CircleEnrollmentService
```

## Best Practices

1. **Always use constructor injection** for services in controllers
2. **Type-hint the interface**, not the concrete class
3. **Mock interfaces in unit tests**, use real implementations in integration tests
4. **Keep interfaces focused** - each should have a single, clear purpose
5. **Document all methods** with PHPDoc including return types and exceptions
6. **Version interfaces carefully** - breaking changes require new interfaces
7. **Use helper methods** for common operations to keep interfaces clean

## Related Files

- Service implementations: `app/Services/`
- Interface definitions: `app/Contracts/`
- Service provider: `app/Providers/AppServiceProvider.php`
- Full documentation: `SERVICE_INTERFACES_IMPLEMENTATION.md`

---

**Last Updated**: December 29, 2025
**Version**: 1.0
