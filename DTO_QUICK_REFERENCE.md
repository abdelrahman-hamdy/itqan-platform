# DTO Quick Reference Card

Quick reference for all 7 Data Transfer Objects in the Itqan Platform.

## 1. PaymentProcessingResult

**Used by:** PaymentService  
**Purpose:** Payment processing outcomes

```php
// Success
PaymentProcessingResult::success($payment, $amount, 'SAR', $txnId);

// Failure
PaymentProcessingResult::failure($errorMsg, $amount, 'SAR', $errors);

// Check
$result->isSuccessful();
$result->getFormattedAmount();  // "500.00 SAR"
```

## 2. SubscriptionToggleResult

**Used by:** StudentSubscriptionService  
**Purpose:** Subscription status changes

```php
// Success
SubscriptionToggleResult::success($subscription, $prevStatus, $newStatus);

// Failure
SubscriptionToggleResult::failure($message, $subscription);

// Check
$result->hasStatusChanged();
```

## 3. CalendarEvent

**Used by:** CalendarService  
**Purpose:** Calendar event data (FullCalendar compatible)

```php
// Create
CalendarEvent::forSession($id, $title, $start, $end, $type);
CalendarEvent::fromQuranSession($session);
CalendarEvent::fromAcademicSession($session);

// Check
$event->isPast();
$event->isOngoing();
$event->isUpcoming();
$event->getDurationInMinutes();
```

## 4. MeetingData

**Used by:** LiveKitService  
**Purpose:** Video meeting connection data

```php
// Create
MeetingData::forTeacher($room, $token, $url, $teacherId, $name);
MeetingData::forStudent($room, $token, $url, $studentId, $name);

// Check
$data->isTeacher();
$data->isStudent();
$data->getRole();
```

## 5. FamilyStatistics

**Used by:** ParentDashboardService  
**Purpose:** Parent dashboard statistics

```php
// Create
FamilyStatistics::fromParentData($children, $subs, $sessions, $payments);
FamilyStatistics::empty();

// Format
$stats->getFormattedPayments();         // "1,500.00 SAR"
$stats->getFormattedAttendanceRate();   // "85.5%"
$stats->getAttendanceStatus();          // 'excellent', 'good', etc.
```

## 6. NotificationPayload

**Used by:** NotificationService  
**Purpose:** Notification data structure

```php
// Create
NotificationPayload::forSession($title, $body, $type, $url);
NotificationPayload::forPayment($title, $body, $success, $url);
NotificationPayload::forHomework($title, $body, $type, $url);

// Convert
$notification->toDatabaseNotification();
$notification->toBroadcastPayload();
```

## 7. SessionOperationResult

**Used by:** SessionManagementService  
**Purpose:** Session operation results

```php
// Create
SessionOperationResult::created($session);
SessionOperationResult::updated($session);
SessionOperationResult::cancelled($session, $prevStatus);
SessionOperationResult::rescheduled($session);
SessionOperationResult::success($session, $operation);
SessionOperationResult::failure($operation, $message);

// Check
$result->isSuccessful();
$result->hasStatusChanged();
```

---

## Common Patterns

### Service Return Type
```php
public function performAction(): SessionOperationResult
{
    return SessionOperationResult::success($session, 'action');
}
```

### Controller Success/Failure
```php
$result = $this->service->performAction();

if ($result->isSuccessful()) {
    return redirect()->with('success', $result->message);
}

return back()->withErrors(['error' => $result->message]);
```

### API Response
```php
$result = $this->service->performAction();

return response()->json(
    $result->toArray(),
    $result->isSuccessful() ? 200 : 422
);
```

---

## All DTOs Support

- `fromArray($data)` - Create from array
- `toArray()` - Convert to array
- Readonly properties (PHP 8.2+)
- Full PHPDoc for IDE support
- Helper methods for common operations
