# Custom Exception Classes - Usage Guide

This document provides examples for using the 5 custom exception classes created for the Itqan Platform.

## Table of Contents

1. [PaymentProcessingException](#paymentprocessingexception)
2. [SubscriptionNotFoundException](#subscriptionnotfoundexception)
3. [WebhookValidationException](#webhookvalidationexception)
4. [EnrollmentCapacityException](#enrollmentcapacityexception)
5. [SessionOperationException](#sessionoperationexception)

---

## PaymentProcessingException

**Purpose**: Handle payment gateway errors, declined payments, and processing failures.

### Properties
- `$paymentId` - Payment transaction ID
- `$gatewayResponse` - Raw gateway response data
- `$errorCode` - Standardized error code

### Static Factory Methods

#### 1. fromGatewayError()
Create exception from gateway error response:

```php
use App\Exceptions\PaymentProcessingException;

// Example: Paymob gateway error
$gatewayResponse = [
    'success' => false,
    'message' => 'Insufficient funds',
    'error_code' => 'INSUFFICIENT_FUNDS'
];

throw PaymentProcessingException::fromGatewayError(
    'Paymob',
    $gatewayResponse,
    'PAY-12345'
);
```

#### 2. paymentDeclined()
Payment declined by gateway:

```php
throw PaymentProcessingException::paymentDeclined(
    'البطاقة منتهية الصلاحية',
    'PAY-12345',
    $gatewayResponse
);
```

#### 3. timeout()
Gateway timeout error:

```php
throw PaymentProcessingException::timeout(
    'Paymob',
    'PAY-12345'
);
```

#### 4. invalidAmount()
Invalid payment amount:

```php
throw PaymentProcessingException::invalidAmount(
    -100.50,
    'PAY-12345'
);
```

### Usage Example

```php
// In PaymentService
public function processPayment(string $paymentId, float $amount): PaymentResult
{
    try {
        $response = $this->gateway->charge($amount);
        
        if (!$response['success']) {
            throw PaymentProcessingException::fromGatewayError(
                $this->gatewayName,
                $response,
                $paymentId
            );
        }
        
        return new PaymentResult($response);
        
    } catch (GatewayTimeoutException $e) {
        throw PaymentProcessingException::timeout($this->gatewayName, $paymentId);
    }
}
```

### HTTP Response Example

```json
{
    "success": false,
    "message": "فشلت عملية الدفع عبر Paymob: Insufficient funds",
    "error": {
        "type": "payment_processing_error",
        "code": "INSUFFICIENT_FUNDS",
        "payment_id": "PAY-12345",
        "gateway_response": {
            "success": false,
            "message": "Insufficient funds",
            "error_code": "INSUFFICIENT_FUNDS"
        }
    }
}
```

---

## SubscriptionNotFoundException

**Purpose**: Handle missing or expired subscription lookups.

### Properties
- `$subscriptionId` - Subscription identifier
- `$subscriptionType` - Type (quran, academic, course)
- `$searchCriteria` - Search parameters used

### Static Factory Methods

#### 1. forId()
Subscription not found by ID:

```php
use App\Exceptions\SubscriptionNotFoundException;

throw SubscriptionNotFoundException::forId(
    'sub_123456',
    'quran'
);
```

#### 2. forStudent()
No active subscription for student:

```php
throw SubscriptionNotFoundException::forStudent(
    $studentId,
    'academic',
    ['status' => 'active', 'lesson_id' => $lessonId]
);
```

#### 3. forEntity()
No subscription for specific circle/lesson/course:

```php
throw SubscriptionNotFoundException::forEntity(
    'circle',
    $circleId,
    $studentId
);
```

#### 4. expired()
Subscription expired:

```php
throw SubscriptionNotFoundException::expired(
    $subscriptionId,
    'academic',
    '2024-12-01'
);
```

### Usage Example

```php
// In SubscriptionService
public function getActiveSubscriptionForStudent(
    string $studentId,
    string $type
): Subscription {
    $subscription = Subscription::where('student_id', $studentId)
        ->where('type', $type)
        ->where('status', 'active')
        ->first();
    
    if (!$subscription) {
        throw SubscriptionNotFoundException::forStudent($studentId, $type);
    }
    
    if ($subscription->end_date < now()) {
        throw SubscriptionNotFoundException::expired(
            $subscription->id,
            $type,
            $subscription->end_date->format('Y-m-d')
        );
    }
    
    return $subscription;
}
```

### HTTP Response Example

```json
{
    "success": false,
    "message": "لا يوجد اشتراك القرآن نشط للطالب: student_789",
    "error": {
        "type": "subscription_not_found",
        "subscription_id": null,
        "subscription_type": "quran",
        "search_criteria": {
            "student_id": "student_789",
            "type": "quran",
            "status": "active"
        }
    }
}
```

---

## WebhookValidationException

**Purpose**: Handle webhook signature validation, missing fields, and format errors.

### Properties
- `$webhookType` - Webhook type (paymob, livekit, etc)
- `$validationErrors` - Array of validation failures
- `$payload` - Webhook payload (sanitized in logs)

### Static Factory Methods

#### 1. invalidSignature()
Invalid webhook signature:

```php
use App\Exceptions\WebhookValidationException;

throw WebhookValidationException::invalidSignature(
    'paymob',
    $receivedSignature,
    $payload
);
```

#### 2. missingFields()
Missing required fields:

```php
throw WebhookValidationException::missingFields(
    'paymob',
    ['transaction_id', 'amount', 'status'],
    $payload
);
```

#### 3. invalidFormat()
Invalid payload format:

```php
throw WebhookValidationException::invalidFormat(
    'livekit',
    'JSON parse error',
    $payload
);
```

#### 4. unsupportedType()
Unsupported webhook type:

```php
throw WebhookValidationException::unsupportedType(
    'unknown_gateway',
    $payload
);
```

#### 5. expired()
Expired webhook (old timestamp):

```php
throw WebhookValidationException::expired(
    'paymob',
    '2024-12-01 10:00:00',
    $payload
);
```

#### 6. duplicate()
Duplicate webhook (already processed):

```php
throw WebhookValidationException::duplicate(
    'paymob',
    'webhook_12345',
    $payload
);
```

### Usage Example

```php
// In PaymobWebhookController
public function handle(Request $request)
{
    $payload = $request->all();
    
    // Validate signature
    if (!$this->validateSignature($request)) {
        throw WebhookValidationException::invalidSignature(
            'paymob',
            $request->header('X-Signature'),
            $payload
        );
    }
    
    // Check required fields
    $requiredFields = ['transaction_id', 'amount', 'status'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($payload[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        throw WebhookValidationException::missingFields(
            'paymob',
            $missingFields,
            $payload
        );
    }
    
    // Check for duplicate
    if ($this->isDuplicate($payload['transaction_id'])) {
        throw WebhookValidationException::duplicate(
            'paymob',
            $payload['transaction_id'],
            $payload
        );
    }
    
    // Process webhook...
}
```

### HTTP Response Example

```json
{
    "success": false,
    "message": "التوقيع الإلكتروني غير صالح لـ webhook من نوع: paymob",
    "error": {
        "type": "webhook_validation_error",
        "webhook_type": "paymob",
        "validation_errors": {
            "signature": "التوقيع الإلكتروني المستلم لا يطابق التوقيع المتوقع",
            "received_signature": "abc123..."
        }
    }
}
```

---

## EnrollmentCapacityException

**Purpose**: Handle circle/course/lesson capacity limits.

### Properties
- `$entityType` - Entity type (circle, course, lesson)
- `$entityId` - Entity identifier
- `$currentCount` - Current enrollment count
- `$maxCapacity` - Maximum capacity
- `$entityName` - Optional entity name

### Static Factory Methods

#### 1. circleFull()
Circle at capacity:

```php
use App\Exceptions\EnrollmentCapacityException;

throw EnrollmentCapacityException::circleFull(
    $circleId,
    30,
    30,
    'حلقة التجويد المتقدمة'
);
```

#### 2. courseFull()
Course at capacity:

```php
throw EnrollmentCapacityException::courseFull(
    $courseId,
    50,
    50,
    'دورة النحو المتقدم'
);
```

#### 3. lessonFull()
Lesson at capacity:

```php
throw EnrollmentCapacityException::lessonFull(
    $lessonId,
    10,
    10,
    'درس الرياضيات - المستوى الثاني'
);
```

#### 4. waitingListFull()
Waiting list at capacity:

```php
throw EnrollmentCapacityException::waitingListFull(
    'circle',
    $circleId,
    20,
    20,
    'حلقة التجويد المتقدمة'
);
```

#### 5. genderCapacityFull()
Gender-specific capacity reached:

```php
throw EnrollmentCapacityException::genderCapacityFull(
    'circle',
    $circleId,
    'male',
    15,
    15,
    'حلقة التجويد'
);
```

### Usage Example

```php
// In CircleEnrollmentService
public function enrollStudent(string $circleId, string $studentId): void
{
    $circle = QuranCircle::findOrFail($circleId);
    $currentCount = $circle->students()->count();
    
    if ($currentCount >= $circle->max_students) {
        throw EnrollmentCapacityException::circleFull(
            $circleId,
            $currentCount,
            $circle->max_students,
            $circle->name
        );
    }
    
    // Check gender-specific capacity
    $student = User::findOrFail($studentId);
    $genderCount = $circle->students()
        ->where('gender', $student->gender)
        ->count();
    
    $maxGenderCapacity = $circle->max_students_per_gender;
    
    if ($genderCount >= $maxGenderCapacity) {
        throw EnrollmentCapacityException::genderCapacityFull(
            'circle',
            $circleId,
            $student->gender,
            $genderCount,
            $maxGenderCapacity,
            $circle->name
        );
    }
    
    // Enroll student...
}
```

### HTTP Response Example

```json
{
    "success": false,
    "message": "الحلقة \"حلقة التجويد المتقدمة\" ممتلئة بالكامل. العدد الحالي: 30 من أصل 30",
    "error": {
        "type": "enrollment_capacity_exceeded",
        "entity_type": "circle",
        "entity_id": "circle_123",
        "entity_name": "حلقة التجويد المتقدمة",
        "current_count": 30,
        "max_capacity": 30,
        "available_slots": 0
    }
}
```

---

## SessionOperationException

**Purpose**: Handle session state machine violations and invalid operations.

### Properties
- `$sessionType` - Session type (quran, academic, interactive)
- `$sessionId` - Session identifier
- `$currentStatus` - Current session status
- `$attemptedOperation` - Operation that was attempted
- `$additionalContext` - Additional context data

### Static Factory Methods

#### 1. invalidTransition()
Invalid status transition:

```php
use App\Exceptions\SessionOperationException;

throw SessionOperationException::invalidTransition(
    'quran',
    $sessionId,
    'completed',
    'live'
);
```

#### 2. alreadyCompleted()
Session already completed:

```php
throw SessionOperationException::alreadyCompleted(
    'academic',
    $sessionId,
    'mark_attendance'
);
```

#### 3. sessionCancelled()
Session is cancelled:

```php
throw SessionOperationException::sessionCancelled(
    'quran',
    $sessionId,
    'start',
    'طلب المعلم'
);
```

#### 4. notStarted()
Session not started yet:

```php
throw SessionOperationException::notStarted(
    'academic',
    $sessionId,
    'submit_report'
);
```

#### 5. concurrentModification()
Concurrent modification conflict:

```php
throw SessionOperationException::concurrentModification(
    'quran',
    $sessionId,
    'live',
    'complete'
);
```

#### 6. missingPrerequisites()
Missing prerequisites:

```php
throw SessionOperationException::missingPrerequisites(
    'academic',
    $sessionId,
    'scheduled',
    'start',
    ['جميع الطلاب يجب أن يكونوا مسجلين', 'رابط الاجتماع مطلوب']
);
```

### Usage Example

```php
// In SessionStatusService
public function completeSession(string $sessionId): void
{
    $session = QuranSession::findOrFail($sessionId);
    
    // Check current status
    if ($session->status === 'completed') {
        throw SessionOperationException::alreadyCompleted(
            'quran',
            $sessionId,
            'complete'
        );
    }
    
    if ($session->status === 'cancelled') {
        throw SessionOperationException::sessionCancelled(
            'quran',
            $sessionId,
            'complete',
            $session->cancellation_reason
        );
    }
    
    if ($session->status === 'scheduled') {
        throw SessionOperationException::notStarted(
            'quran',
            $sessionId,
            'complete'
        );
    }
    
    // Check prerequisites
    $missingPrerequisites = [];
    
    if (!$session->meeting_id) {
        $missingPrerequisites[] = 'يجب إنشاء اجتماع أولاً';
    }
    
    if ($session->attendances()->count() === 0) {
        $missingPrerequisites[] = 'يجب تسجيل الحضور أولاً';
    }
    
    if (!empty($missingPrerequisites)) {
        throw SessionOperationException::missingPrerequisites(
            'quran',
            $sessionId,
            $session->status,
            'complete',
            $missingPrerequisites
        );
    }
    
    // Complete session...
    $session->update(['status' => 'completed']);
}
```

### HTTP Response Example

```json
{
    "success": false,
    "message": "لا يمكن إنهاء الجلسة لجلسة القرآن - الجلسة لم تبدأ بعد",
    "error": {
        "type": "session_operation_error",
        "session_type": "quran",
        "session_id": "session_456",
        "current_status": "scheduled",
        "current_status_label": "مجدولة",
        "attempted_operation": "complete",
        "allowed_transitions": ["live", "cancelled", "rescheduled"],
        "additional_context": null
    }
}
```

---

## Best Practices

### 1. Use Static Factory Methods
Always prefer static factory methods over direct constructor calls:

```php
// ✅ Good
throw PaymentProcessingException::fromGatewayError($gateway, $response, $id);

// ❌ Avoid
throw new PaymentProcessingException('Error', $id, $response);
```

### 2. Provide Context
Include as much context as possible:

```php
throw SubscriptionNotFoundException::forStudent(
    $studentId,
    'academic',
    [
        'status' => 'active',
        'lesson_id' => $lessonId,
        'start_date' => $startDate
    ]
);
```

### 3. Let Exceptions Handle Logging
All exceptions have `report()` methods that handle logging automatically:

```php
// The exception will automatically log when thrown
throw SessionOperationException::invalidTransition(...);
```

### 4. Catch Specific Exceptions
Catch specific exception types for targeted handling:

```php
try {
    $this->enrollStudent($circleId, $studentId);
} catch (EnrollmentCapacityException $e) {
    // Offer to add to waiting list
    return $this->offerWaitingList($circleId, $studentId);
} catch (SubscriptionNotFoundException $e) {
    // Redirect to subscription page
    return redirect()->route('subscriptions.create');
}
```

### 5. Arabic Error Messages
All exceptions provide user-facing Arabic messages. Use them directly in views:

```blade
@if($exception instanceof \App\Exceptions\EnrollmentCapacityException)
    <div class="alert alert-warning">
        {{ $exception->getMessage() }}
        <p>الأماكن المتاحة: {{ $exception->getAvailableSlots() }}</p>
    </div>
@endif
```

---

## Testing Examples

### Unit Test Example

```php
use App\Exceptions\PaymentProcessingException;
use Tests\TestCase;

class PaymentProcessingExceptionTest extends TestCase
{
    public function test_from_gateway_error_creates_exception_with_correct_data()
    {
        $gatewayResponse = [
            'error_code' => 'DECLINED',
            'message' => 'Card declined'
        ];
        
        $exception = PaymentProcessingException::fromGatewayError(
            'Paymob',
            $gatewayResponse,
            'PAY-123'
        );
        
        $this->assertEquals('PAY-123', $exception->getPaymentId());
        $this->assertEquals('DECLINED', $exception->getErrorCode());
        $this->assertEquals($gatewayResponse, $exception->getGatewayResponse());
        $this->assertStringContainsString('Paymob', $exception->getMessage());
    }
    
    public function test_exception_renders_correct_json_response()
    {
        $exception = PaymentProcessingException::timeout('Paymob', 'PAY-123');
        
        $request = request();
        $response = $exception->render($request);
        
        $this->assertEquals(504, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('payment_processing_error', $data['error']['type']);
        $this->assertEquals('GATEWAY_TIMEOUT', $data['error']['code']);
    }
}
```

---

## Migration Guide

### Replacing Generic Exceptions

**Before:**
```php
if (!$subscription) {
    throw new Exception('Subscription not found');
}
```

**After:**
```php
if (!$subscription) {
    throw SubscriptionNotFoundException::forId($subscriptionId, 'quran');
}
```

**Before:**
```php
if ($circle->students()->count() >= $circle->max_students) {
    throw new Exception('Circle is full');
}
```

**After:**
```php
if ($circle->students()->count() >= $circle->max_students) {
    throw EnrollmentCapacityException::circleFull(
        $circle->id,
        $circle->students()->count(),
        $circle->max_students,
        $circle->name
    );
}
```

---

## Summary

These 5 custom exception classes provide:

✅ **Structured error handling** with typed properties
✅ **Arabic error messages** for user-facing errors
✅ **Automatic logging** via `report()` methods
✅ **HTTP responses** with proper status codes
✅ **Static factory methods** for common scenarios
✅ **Rich context** for debugging and monitoring
✅ **Type safety** for exception handling

Replace generic `Exception` catches throughout the codebase with these specific exceptions for better error handling and debugging.
