# Data Transfer Objects (DTOs) Implementation Summary

This document provides an overview of the 7 DTOs implemented to replace array returns and improve type safety across the Itqan Platform codebase.

## Overview

All DTOs follow these conventions:
- **PHP 8.2+ readonly classes** with `declare(strict_types=1)`
- **Static factory methods** for common use cases (`success()`, `failure()`, etc.)
- **`fromArray()` method** for array deserialization
- **`toArray()` method** for array serialization
- **Comprehensive PHPDoc** for IDE autocomplete support
- **Helper methods** for common operations

---

## 1. PaymentProcessingResult

**Location:** `/app/DTOs/PaymentProcessingResult.php`

**Purpose:** Used by PaymentService to return payment processing outcomes with type safety.

### Properties
```php
public bool $success              // Payment success status
public ?Payment $payment          // Payment model instance
public float $amount              // Payment amount
public string $currency           // Currency code (default: 'SAR')
public ?string $transactionId     // Internal transaction ID
public ?string $gatewayReference  // Gateway reference/receipt
public ?string $paymentUrl        // Payment redirect URL
public ?string $errorMessage      // Error message on failure
public array $errors              // Validation errors
public array $metadata            // Additional metadata
```

### Usage Examples
```php
// Success case
$result = PaymentProcessingResult::success(
    payment: $payment,
    amount: 500.00,
    currency: 'SAR',
    transactionId: 'TXN-12345',
    gatewayReference: 'PAYMOB-67890'
);

// Failure case
$result = PaymentProcessingResult::failure(
    errorMessage: 'Insufficient funds',
    amount: 500.00,
    errors: ['account' => 'Balance too low']
);

// From array
$result = PaymentProcessingResult::fromArray($data);

// Helper methods
$result->isSuccessful();           // true/false
$result->requiresRedirect();       // true if paymentUrl exists
$result->getFormattedAmount();     // "500.00 SAR"
```

---

## 2. SubscriptionToggleResult

**Location:** `/app/DTOs/SubscriptionToggleResult.php`

**Purpose:** Used by StudentSubscriptionService to return subscription status toggle results.

### Properties
```php
public bool $success                    // Operation success status
public ?BaseSubscription $subscription  // Subscription instance
public ?string $previousStatus          // Previous status
public ?string $newStatus               // New status
public string $message                  // Human-readable message
public array $metadata                  // Additional metadata
```

### Usage Examples
```php
// Success case
$result = SubscriptionToggleResult::success(
    subscription: $subscription,
    previousStatus: 'active',
    newStatus: 'suspended',
    message: 'Subscription suspended successfully'
);

// Failure case
$result = SubscriptionToggleResult::failure(
    message: 'Cannot suspend expired subscription',
    subscription: $subscription,
    previousStatus: 'expired'
);

// Helper methods
$result->isSuccessful();         // true/false
$result->hasStatusChanged();     // true if status changed
```

---

## 3. CalendarEvent

**Location:** `/app/DTOs/CalendarEvent.php`

**Purpose:** Used by CalendarService to return structured calendar event data compatible with FullCalendar.

### Properties
```php
public string|int $id          // Event unique identifier
public string $title           // Event title
public Carbon $start           // Start datetime
public Carbon $end             // End datetime
public string $type            // Event type
public ?string $status         // Event status
public ?string $color          // Display color (hex)
public ?string $url            // Detail page URL
public ?int $teacherId         // Teacher ID
public ?string $teacherName    // Teacher name
public ?int $studentId         // Student ID
public ?string $studentName    // Student name
public array $metadata         // Additional metadata
public bool $allDay            // All-day event flag
public bool $editable          // Editable flag
```

### Usage Examples
```php
// Create session event
$event = CalendarEvent::forSession(
    id: $session->id,
    title: 'Quran Session',
    start: Carbon::parse($session->scheduled_at),
    end: Carbon::parse($session->scheduled_at)->addHour(),
    sessionType: 'quran',
    status: 'scheduled',
    url: route('teacher.sessions.show', $session)
);

// From existing sessions
$event = CalendarEvent::fromQuranSession($quranSession);
$event = CalendarEvent::fromAcademicSession($academicSession);
$event = CalendarEvent::fromInteractiveCourseSession($interactiveSession);

// Helper methods
$event->getDurationInMinutes();  // 60
$event->isPast();                // true/false
$event->isOngoing();             // true/false
$event->isUpcoming();            // true/false
$event->toFullCalendarFormat();  // FullCalendar-compatible array
```

---

## 4. MeetingData

**Location:** `/app/DTOs/MeetingData.php`

**Purpose:** Used by LiveKitService to return meeting connection data for video conferencing.

### Properties
```php
public string $roomName         // LiveKit room name
public string $token            // JWT access token
public string $serverUrl        // LiveKit server URL
public string $participantName  // Participant display name
public string $participantId    // Participant unique ID
public array $metadata          // Additional metadata (role, etc.)
```

### Usage Examples
```php
// For teacher
$meetingData = MeetingData::forTeacher(
    roomName: 'session-123',
    token: $jwtToken,
    serverUrl: config('livekit.server_url'),
    teacherId: $teacher->id,
    teacherName: $teacher->name
);

// For student
$meetingData = MeetingData::forStudent(
    roomName: 'session-123',
    token: $jwtToken,
    serverUrl: config('livekit.server_url'),
    studentId: $student->id,
    studentName: $student->name
);

// Helper methods
$meetingData->isTeacher();  // true/false
$meetingData->isStudent();  // true/false
$meetingData->getRole();    // 'teacher' or 'student'
$meetingData->toJson();     // Frontend-friendly format
```

---

## 5. FamilyStatistics

**Location:** `/app/DTOs/FamilyStatistics.php`

**Purpose:** Used by ParentDashboardService to return aggregated family statistics.

### Properties
```php
public int $totalChildren           // Number of children
public int $activeSubscriptions     // Active subscriptions count
public int $upcomingSessions        // Upcoming sessions count
public float $totalPayments         // Total payments (SAR)
public float $attendanceRate        // Attendance rate (0-100)
public int $completedSessions       // Completed sessions count
public int $totalCertificates       // Certificates count
public array $childrenStats         // Per-child statistics
public array $recentActivities      // Recent activities
```

### Usage Examples
```php
// From parent data
$stats = FamilyStatistics::fromParentData(
    children: $parent->children,
    subscriptionCounts: ['active' => 3],
    sessionCounts: ['upcoming' => 5, 'completed' => 20, 'attendance_rate' => 85.5],
    paymentData: ['total' => 1500.00]
);

// Empty state
$stats = FamilyStatistics::empty();

// Helper methods
$stats->getFormattedPayments();              // "1,500.00 SAR"
$stats->getFormattedAttendanceRate();        // "85.5%"
$stats->hasActiveEngagement();               // true/false
$stats->getAverageSubscriptionsPerChild();   // 1.5
$stats->getAttendanceStatus();               // 'excellent', 'good', 'fair', 'needs_improvement'
```

---

## 6. NotificationPayload

**Location:** `/app/DTOs/NotificationPayload.php`

**Purpose:** Used by NotificationService to structure notification data for database, broadcasting, and display.

### Properties
```php
public string $type          // Notification type identifier
public string $title         // Notification title
public string $body          // Notification body
public ?string $actionUrl    // Action URL (click target)
public ?string $icon         // Icon identifier/class
public ?string $color        // Display color
public array $metadata       // Additional metadata
public bool $isImportant     // Importance flag
```

### Usage Examples
```php
// Session notification
$notification = NotificationPayload::forSession(
    title: 'Session Starting Soon',
    body: 'Your Quran session starts in 10 minutes',
    sessionType: 'quran',
    actionUrl: route('student.sessions.show', $session),
    isImportant: true
);

// Payment notification
$notification = NotificationPayload::forPayment(
    title: 'Payment Successful',
    body: 'Your payment of 500 SAR has been processed',
    isSuccess: true,
    actionUrl: route('payments.show', $payment)
);

// Homework notification
$notification = NotificationPayload::forHomework(
    title: 'New Homework Assigned',
    body: 'Complete the homework by tomorrow',
    homeworkType: 'memorization',
    actionUrl: route('student.homework.show', $homework)
);

// Helper methods
$notification->getPriority();              // 'high' or 'normal'
$notification->toDatabaseNotification();   // Database format
$notification->toBroadcastPayload();       // Broadcasting format
```

---

## 7. SessionOperationResult

**Location:** `/app/DTOs/SessionOperationResult.php`

**Purpose:** Used by SessionManagementService to return session operation results.

### Properties
```php
public bool $success              // Operation success status
public ?BaseSession $session      // Session instance
public string $operation          // Operation type (create, update, cancel, etc.)
public ?string $previousStatus    // Previous session status
public ?string $newStatus         // New session status
public string $message            // Human-readable message
public array $metadata            // Additional metadata
```

### Usage Examples
```php
// Session creation
$result = SessionOperationResult::created(
    session: $session,
    message: 'Session created successfully'
);

// Session update
$result = SessionOperationResult::updated(
    session: $session,
    message: 'Session updated successfully'
);

// Session cancellation
$result = SessionOperationResult::cancelled(
    session: $session,
    previousStatus: 'scheduled',
    message: 'Session cancelled due to teacher unavailability',
    metadata: ['reason' => 'Teacher sick leave']
);

// Session rescheduling
$result = SessionOperationResult::rescheduled(
    session: $session,
    message: 'Session rescheduled to next week'
);

// Generic success/failure
$result = SessionOperationResult::success(
    session: $session,
    operation: 'status_change',
    previousStatus: 'scheduled',
    newStatus: 'live'
);

$result = SessionOperationResult::failure(
    operation: 'cancel',
    message: 'Cannot cancel session less than 2 hours before start'
);

// Helper methods
$result->isSuccessful();         // true/false
$result->hasStatusChanged();     // true if status changed
$result->getOperationType();     // 'create', 'update', etc.
```

---

## Benefits of Using DTOs

### 1. Type Safety
- IDE autocomplete for all properties
- Static analysis catches errors at development time
- No more "undefined array key" warnings

### 2. Consistency
- Standardized data structure across the application
- Easy to refactor when changing service contracts
- Clear documentation of expected data

### 3. Readability
- Self-documenting code with descriptive property names
- Factory methods make intent clear (`success()`, `failure()`)
- Helper methods reduce boilerplate

### 4. Maintainability
- Single source of truth for data structures
- Easy to add validation in constructors
- Clear deprecation path for schema changes

### 5. Testing
- Easy to create test fixtures with factory methods
- Type-safe assertions in tests
- Mock objects work better with defined types

---

## Migration Guide

### Before (Array Returns)
```php
// Service method
public function processPayment($payment): array
{
    if ($payment->process()) {
        return [
            'success' => true,
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Payment failed',
    ];
}

// Controller usage
$result = $this->paymentService->processPayment($payment);
if ($result['success']) {
    $transactionId = $result['transaction_id']; // No IDE autocomplete
}
```

### After (DTO Returns)
```php
// Service method
public function processPayment($payment): PaymentProcessingResult
{
    if ($payment->process()) {
        return PaymentProcessingResult::success(
            payment: $payment,
            amount: $payment->amount,
            transactionId: $payment->transaction_id
        );
    }
    
    return PaymentProcessingResult::failure(
        errorMessage: 'Payment failed',
        amount: $payment->amount
    );
}

// Controller usage
$result = $this->paymentService->processPayment($payment);
if ($result->isSuccessful()) {
    $transactionId = $result->transactionId; // Full IDE autocomplete
}
```

---

## Common Patterns

### 1. Service Layer Returns
```php
class SomeService
{
    public function performOperation(): SessionOperationResult
    {
        try {
            // ... operation logic
            return SessionOperationResult::success(...);
        } catch (Exception $e) {
            return SessionOperationResult::failure(
                operation: 'operation_name',
                message: $e->getMessage()
            );
        }
    }
}
```

### 2. Controller Response
```php
public function store(Request $request)
{
    $result = $this->service->createSession($request->validated());
    
    if ($result->isSuccessful()) {
        return redirect()
            ->route('sessions.show', $result->session)
            ->with('success', $result->message);
    }
    
    return back()
        ->withErrors(['error' => $result->message])
        ->withInput();
}
```

### 3. API Response
```php
public function store(Request $request)
{
    $result = $this->service->createSession($request->validated());
    
    return response()->json(
        $result->toArray(),
        $result->isSuccessful() ? 201 : 422
    );
}
```

---

## Next Steps

1. **Gradually migrate existing array returns** to use appropriate DTOs
2. **Update service layer methods** to return DTOs instead of arrays
3. **Update tests** to use DTO factory methods
4. **Add validation** in DTO constructors where appropriate
5. **Consider creating additional DTOs** for other complex data structures

---

## File Locations

All DTOs are located in: `/app/DTOs/`

- `PaymentProcessingResult.php` (4.7KB)
- `SubscriptionToggleResult.php` (3.4KB)
- `CalendarEvent.php` (9.4KB)
- `MeetingData.php` (3.8KB)
- `FamilyStatistics.php` (6.1KB)
- `NotificationPayload.php` (5.4KB)
- `SessionOperationResult.php` (5.8KB)

Total: 7 DTOs, ~38.7KB of type-safe data structures.
