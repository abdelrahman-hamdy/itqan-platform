# API Resources Complete Guide

This document provides a comprehensive overview of all API Resource classes in the Itqan Platform, organized by domain. These resources ensure consistent API responses across all endpoints.

## Overview

All API Resources are located in `/Users/abdelrahmanhamdy/web/itqan-platform/app/Http/Resources/Api/V1/` and follow Laravel's Resource pattern for transforming models into JSON responses.

## Resource Organization

### 1. Session Resources

#### SessionResource.php
**Location:** `Session/SessionResource.php`

**Purpose:** Polymorphic base resource for all session types (QuranSession, AcademicSession, InteractiveCourseSession)

**Key Features:**
- Status with color, icon, and label
- Meeting data (link, room name, platform)
- Attendance status and participant count
- Scheduling information (scheduled_at, started_at, ended_at)
- Cancellation and rescheduling data
- Teacher feedback and session notes
- Polymorphic attendance relationship

**Usage Example:**
```php
use App\Http\Resources\Api\V1\Session\SessionResource;

return SessionResource::make($session);
```

#### SessionCollection.php
**Location:** `Session/SessionCollection.php`

**Purpose:** Collection wrapper with metadata

**Metadata Includes:**
- Total count
- Status breakdown (scheduled, live, completed, cancelled)

---

### 2. Subscription Resources

#### SubscriptionResource.php
**Location:** `Subscription/SubscriptionResource.php`

**Purpose:** Polymorphic base resource for all subscription types (QuranSubscription, AcademicSubscription, CourseSubscription)

**Key Features:**
- Package information (name, description, features)
- Status with color and icon
- Pricing details (monthly, quarterly, yearly, discounts)
- Billing cycle and payment status
- Date ranges (starts_at, ends_at, next_billing_date)
- Auto-renewal settings
- Progress percentage
- Certificate information
- Student and academy relationships

**Usage Example:**
```php
use App\Http\Resources\Api\V1\Subscription\SubscriptionResource;

return SubscriptionResource::make($subscription);
```

#### SubscriptionCollection.php
**Location:** `Subscription/SubscriptionCollection.php`

**Purpose:** Collection wrapper with financial metadata

**Metadata Includes:**
- Total count
- Status breakdown
- Total revenue calculation

---

### 3. Teacher Resources

#### TeacherResource.php
**Location:** `Teacher/TeacherResource.php`

**Purpose:** Full teacher profile for both Quran and Academic teachers

**Key Features:**
- Teacher type detection (quran/academic)
- Approval status
- User information (name, email, phone, avatar)
- Bio in Arabic and English
- Educational qualifications
- Pricing (individual and group session rates)
- Statistics (rating, reviews, students, sessions)
- Academic-specific: subjects and grade levels
- Avatar URL generation with fallback to ui-avatars

**Usage Example:**
```php
use App\Http\Resources\Api\V1\Teacher\TeacherResource;

return TeacherResource::make($teacher);
```

#### TeacherCollection.php
**Location:** `Teacher/TeacherCollection.php` (**NEW**)

**Purpose:** Collection wrapper with teacher statistics

**Metadata Includes:**
- Total count
- Type breakdown (quran vs academic)
- Active teacher count
- Average rating across all teachers

---

### 4. Student Resources

#### StudentResource.php
**Location:** `Student/StudentResource.php`

**Purpose:** Full student profile data

**Key Features:**
- Student code
- User information with avatar URL
- Personal information (birth date, age, gender, nationality)
- Grade level
- Parent information
- Enrollment date
- Active subscriptions count
- Academy relationship

**Usage Example:**
```php
use App\Http\Resources\Api\V1\Student\StudentResource;

return StudentResource::make($student);
```

#### StudentListResource.php
**Location:** `Student/StudentListResource.php`

**Purpose:** Lightweight student resource for lists (already exists)

#### StudentCollection.php
**Location:** `Student/StudentCollection.php` (**NEW**)

**Purpose:** Collection wrapper with student demographics

**Metadata Includes:**
- Total count
- Grade level breakdown
- Gender breakdown

---

### 5. Payment Resources

#### PaymentResource.php
**Location:** `Payment/PaymentResource.php`

**Purpose:** Payment transaction data for all payment types

**Key Features:**
- Payment code
- Payable polymorphic relationship
- Amount and currency
- Status with color coding
- Payment method and gateway
- Transaction ID
- Gateway response (admin only)
- Refund information
- User relationship
- Payment and refund dates

**Usage Example:**
```php
use App\Http\Resources\Api\V1\Payment\PaymentResource;

return PaymentResource::make($payment);
```

#### PaymentCollection.php
**Location:** `Payment/PaymentCollection.php` (**NEW**)

**Purpose:** Collection wrapper with financial statistics

**Metadata Includes:**
- Total count
- Status breakdown
- Financial summary:
  - Total amount
  - Paid amount
  - Pending amount
  - Refunded amount
- Payment method breakdown

---

### 6. Attendance Resources

#### AttendanceResource.php
**Location:** `Attendance/AttendanceResource.php`

**Purpose:** Meeting attendance records for all session types

**Key Features:**
- Session reference (polymorphic)
- User information with type
- Timing (first join, last leave, last heartbeat)
- Duration tracking
- Attendance status with label
- Attendance percentage
- Join/leave cycle tracking
- Session timing
- Calculation status

**Usage Example:**
```php
use App\Http\Resources\Api\V1\Attendance\AttendanceResource;

return AttendanceResource::make($attendance);
```

#### AttendanceCollection.php
**Location:** `Attendance/AttendanceCollection.php` (**NEW**)

**Purpose:** Collection wrapper with attendance statistics

**Metadata Includes:**
- Total count
- Status breakdown
- Statistics:
  - Average duration
  - Total duration
  - Attendance rate percentage

---

### 7. Homework Resources

#### HomeworkResource.php
**Location:** `Homework/HomeworkResource.php`

**Purpose:** Homework assignment data for all session types

**Key Features:**
- Assignment details (title, description, instructions)
- Attachment URLs
- Status with color coding
- Session reference (polymorphic)
- Due date
- Student and teacher relationships
- Submission relationship
- Grading information (grade, max grade, feedback)
- Graded by and graded at

**Usage Example:**
```php
use App\Http\Resources\Api\V1\Homework\HomeworkResource;

return HomeworkResource::make($homework);
```

#### HomeworkSubmissionResource.php
**Location:** `Homework/HomeworkSubmissionResource.php`

**Purpose:** Homework submission data (already exists)

#### HomeworkCollection.php
**Location:** `Homework/HomeworkCollection.php` (**NEW**)

**Purpose:** Collection wrapper with homework statistics

**Metadata Includes:**
- Total count
- Status breakdown
- Statistics:
  - Submitted count
  - Graded count
  - Pending count
  - Average grade

---

### 8. Quiz Resources

#### QuizResource.php
**Location:** `Quiz/QuizResource.php`

**Purpose:** Quiz data for all quiz types

**Key Features:**
- Quiz details (title, description, instructions)
- Configuration (total questions, marks, duration)
- Difficulty level
- Settings (published, shuffle, show answers, retake, max attempts)
- Quizzable polymorphic relationship
- Questions array with options
- Creator information
- Attempts count

**Usage Example:**
```php
use App\Http\Resources\Api\V1\Quiz\QuizResource;

return QuizResource::make($quiz);
```

#### QuizAttemptResource.php
**Location:** `Quiz/QuizAttemptResource.php` (**NEW**)

**Purpose:** Quiz attempt data including score and completion status

**Key Features:**
- Attempt number
- Quiz reference with passing marks
- Student information
- Timing (started, completed, time taken, remaining time)
- Scoring details (marks obtained, percentage, passed, grade)
- Status and completion flag
- Answers (conditionally shown)
- Feedback and teacher comments
- Auto-grading information
- Question statistics (correct, incorrect, unanswered, accuracy)

**Usage Example:**
```php
use App\Http\Resources\Api\V1\Quiz\QuizAttemptResource;

return QuizAttemptResource::make($attempt);
```

#### QuizResultResource.php
**Location:** `Quiz/QuizResultResource.php`

**Purpose:** Quiz result summary (already exists)

#### QuizCollection.php
**Location:** `Quiz/QuizCollection.php` (**NEW**)

**Purpose:** Collection wrapper with quiz statistics

**Metadata Includes:**
- Total count
- Published count
- Difficulty level breakdown
- Statistics:
  - Total questions
  - Average duration
  - Average passing marks

#### QuizAttemptCollection.php
**Location:** `Quiz/QuizAttemptCollection.php` (**NEW**)

**Purpose:** Collection wrapper with attempt statistics

**Metadata Includes:**
- Total count
- Completed vs in-progress counts
- Statistics:
  - Average score
  - Highest/lowest scores
  - Pass rate percentage
  - Average time taken

---

### 9. Circle Resources

#### CircleResource.php
**Location:** `Circle/CircleResource.php`

**Purpose:** Complete Quran circle data (both group and individual)

**Key Features:**
- Type detection (individual/group)
- Circle name and description
- Teacher relationship
- Student information (for individual circles)
- Schedule (for group circles)
- Capacity information (for group circles)
- Status and active flag
- Date range
- Subscription (for individual circles)
- Session counts
- Academy relationship

**Usage Example:**
```php
use App\Http\Resources\Api\V1\Circle\CircleResource;

return CircleResource::make($circle);
```

#### CircleListResource.php
**Location:** `Circle/CircleListResource.php`

**Purpose:** Lightweight circle resource for lists (already exists)

#### CircleCollection.php
**Location:** `Circle/CircleCollection.php` (**NEW**)

**Purpose:** Collection wrapper with circle statistics

**Metadata Includes:**
- Total count
- Type breakdown (individual vs group)
- Active circle count
- Total capacity (group circles)
- Total students enrolled

---

### 10. Academy Resources

#### AcademyBrandingResource.php
**Location:** `Academy/AcademyBrandingResource.php`

**Purpose:** Academy branding and configuration data (already exists)

**Key Features:**
- Logo and favicon URLs
- Brand colors with all shades
- Gradient palette
- Settings (active, registration, maintenance)
- Localization (country, timezone, currency)
- Contact information
- Full domain URLs

---

### 11. User Resources

#### UserResource.php
**Location:** `User/UserResource.php`

**Purpose:** User account information (already exists)

---

## Common Patterns

### 1. Resource Usage in Controllers

**Single Resource:**
```php
use App\Http\Resources\Api\V1\Session\SessionResource;

public function show($id)
{
    $session = QuranSession::with(['academy', 'attendances'])->findOrFail($id);

    return SessionResource::make($session);
}
```

**Collection:**
```php
use App\Http\Resources\Api\V1\Session\SessionResource;
use App\Http\Resources\Api\V1\Session\SessionCollection;

public function index()
{
    $sessions = QuranSession::with(['academy', 'quranTeacher'])->paginate(15);

    // Option 1: Return collection directly
    return SessionResource::collection($sessions);

    // Option 2: Use custom collection for metadata
    return new SessionCollection($sessions);
}
```

### 2. Conditional Fields

Resources use Laravel's `when()` and `whenLoaded()` helpers:

```php
// Show field only when condition is met
'refund_reason' => $this->when($this->refunded_at, $this->refund_reason),

// Show relationship only when eager loaded
'academy' => $this->whenLoaded('academy', [
    'id' => $this->academy?->id,
    'name' => $this->academy?->name,
]),
```

### 3. Enum Formatting

Enums are consistently formatted with value, label, color, and icon:

```php
'status' => [
    'value' => $this->status->value,
    'label' => $this->status->label(),
    'color' => $this->status->color(),
    'icon' => $this->status->icon(),
],
```

### 4. Date Formatting

All dates use ISO 8601 format:

```php
'created_at' => $this->created_at->toISOString(),
'scheduled_at' => $this->scheduled_at?->toISOString(),
```

### 5. Asset URLs

File paths are converted to full URLs:

```php
protected function getFileUrl(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    if (str_starts_with($path, 'http')) {
        return $path;
    }

    return asset('storage/' . $path);
}
```

### 6. Avatar Fallbacks

User/teacher/student avatars use fallback to ui-avatars.com:

```php
protected function getAvatarUrl(): ?string
{
    if ($this->avatar) {
        return asset('storage/' . $this->avatar);
    }

    return 'https://ui-avatars.com/api/?name=' . urlencode($this->user?->name) . '&background=0ea5e9&color=fff';
}
```

## Collection Metadata Standards

All collection resources follow these metadata conventions:

### Required Fields
- `total`: Total count of items
- `statuses`: Breakdown by status (when applicable)

### Common Statistics
- Counts (submitted, completed, pending, etc.)
- Averages (duration, grade, rating, etc.)
- Percentages (attendance rate, pass rate, etc.)
- Financial totals (when applicable)

## Best Practices

### 1. Always Use Resources
Never return raw models from API endpoints. Always wrap in resources:

```php
// Bad
return response()->json($session);

// Good
return SessionResource::make($session);
```

### 2. Eager Load Relationships
Load relationships before passing to resources to avoid N+1 queries:

```php
$sessions = QuranSession::with(['academy', 'quranTeacher', 'attendances'])->get();
return SessionResource::collection($sessions);
```

### 3. Use Type Hints
Always include PHPDoc type hints for IDE support:

```php
/**
 * @mixin QuranSession
 */
class SessionResource extends JsonResource
{
    // ...
}
```

### 4. Polymorphic Type Detection
For polymorphic resources, detect type early:

```php
public function toArray(Request $request): array
{
    $isQuranTeacher = $this->resource instanceof QuranTeacherProfile;

    return [
        'type' => $isQuranTeacher ? 'quran' : 'academic',
        // ... conditional fields based on type
    ];
}
```

### 5. Protect Sensitive Data
Use conditional fields to protect sensitive data:

```php
'gateway_response' => $this->when(
    $request->user()?->isAdmin() ?? false,
    $this->gateway_response
),
```

## Integration with ApiResponseService

Resources work seamlessly with the ApiResponseService:

```php
use App\Http\Controllers\Traits\ApiResponses;
use App\Http\Resources\Api\V1\Session\SessionResource;

class SessionController extends Controller
{
    use ApiResponses;

    public function show($id)
    {
        $session = QuranSession::findOrFail($id);

        return $this->successResponse(
            data: SessionResource::make($session),
            message: __('Session retrieved successfully')
        );
    }

    public function index()
    {
        $sessions = QuranSession::paginate(15);

        return $this->successResponse(
            data: SessionResource::collection($sessions),
            message: __('Sessions retrieved successfully')
        );
    }
}
```

## Summary

### Resources Created (NEW)
1. TeacherCollection.php
2. StudentCollection.php
3. PaymentCollection.php
4. AttendanceCollection.php
5. CircleCollection.php
6. HomeworkCollection.php
7. QuizCollection.php
8. QuizAttemptResource.php
9. QuizAttemptCollection.php

### Resources Already Existing
1. SessionResource.php
2. SessionCollection.php
3. AcademicSessionResource.php
4. InteractiveSessionResource.php
5. SubscriptionResource.php
6. SubscriptionCollection.php
7. TeacherResource.php
8. TeacherListResource.php
9. StudentResource.php
10. StudentListResource.php
11. PaymentResource.php
12. AttendanceResource.php
13. HomeworkResource.php
14. HomeworkSubmissionResource.php
15. QuizResource.php
16. QuizResultResource.php
17. CircleResource.php
18. CircleListResource.php
19. AcademyBrandingResource.php
20. UserResource.php

### Total Resources: 29 (20 existing + 9 new)

All API endpoints now have consistent, well-structured resource classes for transforming data into JSON responses. Each resource follows Laravel best practices and includes proper documentation, type hints, and metadata for collections.
