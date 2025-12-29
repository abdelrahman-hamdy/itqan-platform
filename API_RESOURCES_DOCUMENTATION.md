# API Resources Documentation

This document provides a comprehensive guide to all API Resource classes in the Itqan Platform.

## Overview

The platform now has **21 API Resource classes** organized by domain, providing consistent and optimized JSON responses for the API.

## Directory Structure

```
app/Http/Resources/Api/V1/
├── Academy/
│   └── AcademyBrandingResource.php          # Academy branding/configuration
├── Attendance/
│   └── AttendanceResource.php               # Meeting attendance records
├── Circle/
│   ├── CircleResource.php                   # Full circle data (group/individual)
│   └── CircleListResource.php               # Minimal circle data for listings
├── Homework/
│   ├── HomeworkResource.php                 # Homework assignments
│   └── HomeworkSubmissionResource.php       # Student submissions
├── Payment/
│   └── PaymentResource.php                  # Payment transactions
├── Quiz/
│   ├── QuizResource.php                     # Quiz data
│   └── QuizResultResource.php               # Quiz attempts and results
├── Session/
│   ├── SessionResource.php                  # Base session (polymorphic)
│   ├── SessionCollection.php                # Session collection wrapper
│   ├── QuranSessionResource.php             # Quran session specific
│   ├── AcademicSessionResource.php          # Academic session specific
│   └── InteractiveSessionResource.php       # Interactive course session
├── Student/
│   ├── StudentResource.php                  # Full student profile
│   └── StudentListResource.php              # Minimal student data
├── Subscription/
│   ├── SubscriptionResource.php             # Base subscription (polymorphic)
│   └── SubscriptionCollection.php           # Subscription collection wrapper
├── Teacher/
│   ├── TeacherResource.php                  # Full teacher profile
│   └── TeacherListResource.php              # Minimal teacher data
└── User/
    └── UserResource.php                     # User data with profile

```

## Resource Patterns

### 1. Polymorphic Base Resources

#### SessionResource (Base)
- Supports all session types: QuranSession, AcademicSession, InteractiveCourseSession
- Common fields: status, scheduling, meeting data, attendance, feedback
- Extended by type-specific resources

**Usage:**
```php
use App\Http\Resources\Api\V1\Session\SessionResource;

// Polymorphic usage - automatically detects type
return SessionResource::collection($sessions);

// Or use type-specific resources
use App\Http\Resources\Api\V1\Session\QuranSessionResource;
return new QuranSessionResource($quranSession);
```

#### SubscriptionResource (Base)
- Supports: QuranSubscription, AcademicSubscription, CourseSubscription
- Common fields: status, pricing, billing, dates, progress
- Polymorphic design

### 2. Full vs List Resources

**Full Resources:** Detailed data for single resource views
- `TeacherResource` - Complete profile with bio, qualifications, statistics
- `StudentResource` - Full profile with parent info, subscriptions
- `CircleResource` - Complete circle data with schedule, students

**List Resources:** Minimal data optimized for collections
- `TeacherListResource` - Just name, avatar, rating, status
- `StudentListResource` - Just name, avatar, grade, student code
- `CircleListResource` - Just name, teacher, active status

### 3. Collection Resources

Provide metadata alongside data:

**SessionCollection:**
```json
{
  "data": [...],
  "meta": {
    "total": 25,
    "statuses": {
      "scheduled": 10,
      "completed": 12,
      "cancelled": 3
    }
  }
}
```

**SubscriptionCollection:**
```json
{
  "data": [...],
  "meta": {
    "total": 15,
    "statuses": {...},
    "total_revenue": 12500.00
  }
}
```

## Key Features

### 1. Conditional Fields with `when()` and `whenLoaded()`

All resources use proper conditional loading to avoid N+1 queries:

```php
// Only include if relationship is loaded
'teacher' => $this->whenLoaded('teacher', function () {
    return new TeacherListResource($this->teacher);
}),

// Only include if condition is met
'meeting_password' => $this->when($this->meeting_password, $this->meeting_password),
```

### 2. Enum Handling

Enums are returned with both value and label:

```php
'status' => [
    'value' => $this->status->value,
    'label' => $this->status->label(),
    'color' => $this->status->color(),
    'icon' => $this->status->icon(),
],
```

### 3. Date Formatting

All dates use ISO 8601 format:

```php
'created_at' => $this->created_at->toISOString(),
'scheduled_at' => $this->scheduled_at?->toISOString(),  // Nullable
```

### 4. File URLs

Helper methods convert storage paths to full URLs:

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

### 5. Avatar Handling

Smart avatar resolution with fallback to UI Avatars:

```php
protected function getAvatarUrl(): ?string
{
    // Check model avatar
    if ($this->avatar) {
        return $this->processAvatarPath($this->avatar);
    }

    // Check user avatar
    if ($this->user?->avatar) {
        return $this->processAvatarPath($this->user->avatar);
    }

    // Fallback to generated avatar
    return 'https://ui-avatars.com/api/?name=' . urlencode($this->user?->name ?? 'User') . '&background=0ea5e9&color=fff';
}
```

## Resource Details

### Session Resources

#### SessionResource (Base)
**Fields:**
- Basic: id, type, session_code, title, description
- Status: value, label, color, icon
- Scheduling: scheduled_at, started_at, ended_at, duration
- Meeting: link, room_name, platform, password, expires_at
- Attendance: status, participants_count
- Feedback: session_notes, teacher_feedback
- Cancellation: cancelled_at, reason, type
- Rescheduling: rescheduled_from, rescheduled_to, reason

#### QuranSessionResource
**Extends SessionResource with:**
- session_type (individual/circle)
- teacher (TeacherListResource)
- student (for individual, StudentListResource)
- circle/individual_circle info
- quran_progress (current_surah, current_page)
- lesson_content
- homework (assigned, details)
- subscription info
- subscription_counted flag

#### AcademicSessionResource
**Extends SessionResource with:**
- session_type (individual/group)
- teacher (TeacherListResource)
- student (StudentListResource)
- individual_lesson (subject info)
- lesson_content
- homework (description, file_url)
- recording (enabled, url)
- subscription info
- subscription_counted flag

#### InteractiveSessionResource
**Extends SessionResource with:**
- scheduled_date + scheduled_time (instead of scheduled_at)
- course (id, title, code, status, enrollments_count)
- teacher (via course)
- lesson_title, lesson_description, content_materials
- homework (description, due_date, file_url)
- recording (enabled, url, available_until)
- session_order

### Subscription Resources

#### SubscriptionResource (Base)
**Fields:**
- Basic: id, type, subscription_code
- Package: name (ar/en), description (ar/en), features
- Status: value, label, color, icon
- Pricing: monthly/quarterly/yearly prices, discount, final_price, currency
- Billing: cycle, payment_status
- Dates: starts_at, ends_at, next_billing_date, last_payment_date
- Renewal: auto_renew, renewal_reminder_sent_at
- Cancellation: cancelled_at, cancellation_reason
- Progress: progress_percentage
- Certificate: issued, issued_at
- Relations: student, academy

### Teacher Resources

#### TeacherResource (Full)
**Fields:**
- Basic: id, teacher_code, type (quran/academic)
- Status: is_active, approval_status
- User: email, name, phone, avatar_url
- Profile: bio (arabic/english)
- Qualifications: educational_qualification/education_level, experience_years
- Pricing: session_price_individual, session_price_group, currency
- Statistics: rating, total_reviews, total_students, total_sessions
- Academic-specific: subjects, grade_levels
- Academy info

#### TeacherListResource (Minimal)
**Fields:**
- id, teacher_code, type, name, avatar_url, rating, is_active

### Student Resources

#### StudentResource (Full)
**Fields:**
- Basic: id, student_code
- User: email, name, phone, avatar_url
- Personal: birth_date, age, gender, nationality
- Academic: grade_level
- Parent: parent info
- Enrollment: enrollment_date
- Academy info
- active_subscriptions_count

#### StudentListResource (Minimal)
**Fields:**
- id, student_code, name, avatar_url, grade_level

### Other Resources

#### AttendanceResource
**Fields:**
- session_id, session_type
- user info
- Timing: first_join_time, last_leave_time, last_heartbeat_at
- Duration: total_duration_minutes, session_duration_minutes
- Status: attendance_status, attendance_percentage
- Tracking: join_count, leave_count, join_leave_cycles
- Calculation: is_calculated, attendance_calculated_at

#### HomeworkResource
**Fields:**
- title, description, instructions
- attachment_url
- status (value, label, color)
- session reference (polymorphic)
- due_date
- student, teacher
- submission (HomeworkSubmissionResource)
- Grading: grade, max_grade, feedback, graded_at, graded_by

#### HomeworkSubmissionResource
**Fields:**
- homework_id
- student info
- content, notes
- attachment_url
- status (value, label, color)
- Grading: grade, feedback, graded_at, graded_by
- Timing: submitted_at, is_late

#### QuizResource
**Fields:**
- title, description, instructions
- Configuration: total_questions, total_marks, passing_marks, duration_minutes
- difficulty_level
- Settings: is_published, shuffle_questions, show_correct_answers, allow_retake, max_attempts
- quizzable reference (polymorphic)
- questions array
- created_by
- attempts_count

#### QuizResultResource
**Fields:**
- quiz info
- student info
- attempt_number
- Timing: started_at, completed_at, time_taken_minutes
- Scores: score, percentage, passed
- Answers: total_questions, correct_answers, incorrect_answers, unanswered
- Detailed answers (conditional)
- feedback, is_completed

#### PaymentResource
**Fields:**
- payment_code
- payable (polymorphic)
- amount, currency
- status (value, label, color)
- payment_method, payment_gateway
- transaction_id
- gateway_response (admin only)
- user info
- Dates: paid_at, refunded_at
- Refund: refund_amount, refund_reason

#### CircleResource (Full)
**Fields:**
- id, type (individual/group), circle_name, description
- teacher (TeacherListResource)
- student (for individual circles)
- Schedule: weekly_days, session_time, session_duration
- Capacity: max_students, current_students, available_spots
- Status: is_active, status
- Dates: start_date, end_date
- subscription (for individual)
- sessions_count, completed_sessions_count
- academy info

#### CircleListResource (Minimal)
**Fields:**
- id, type, circle_name, teacher_name, is_active, students_count

## Usage Examples

### 1. Basic Resource Usage

```php
use App\Http\Resources\Api\V1\Session\QuranSessionResource;

// Single resource
$session = QuranSession::with(['quranTeacher', 'student', 'attendances'])->find($id);
return new QuranSessionResource($session);

// Collection
$sessions = QuranSession::with(['quranTeacher', 'student'])->get();
return QuranSessionResource::collection($sessions);
```

### 2. Using Collection Resources

```php
use App\Http\Resources\Api\V1\Session\SessionCollection;

$sessions = QuranSession::with(['quranTeacher'])->get();
return new SessionCollection($sessions);

// Returns:
// {
//   "data": [...],
//   "meta": {
//     "total": 25,
//     "statuses": {"scheduled": 10, "completed": 12, ...}
//   }
// }
```

### 3. Polymorphic Resources

```php
use App\Http\Resources\Api\V1\Session\SessionResource;

// Automatically handles all session types
$sessions = collect([
    QuranSession::find(1),
    AcademicSession::find(2),
    InteractiveCourseSession::find(3),
]);

return SessionResource::collection($sessions);
```

### 4. Eager Loading for Performance

```php
// Efficient loading
$students = Student::with([
    'user',
    'gradeLevel',
    'parentProfile.user',
    'quranSubscriptions',
    'academicSubscriptions'
])->get();

return StudentResource::collection($students);

// Resources use whenLoaded() to only include loaded relationships
```

### 5. Using List Resources for Performance

```php
// For listings/dropdowns - use minimal resources
$teachers = QuranTeacherProfile::with('user')->get();
return TeacherListResource::collection($teachers);

// For detail pages - use full resources
$teacher = QuranTeacherProfile::with(['user', 'academicSubjects', 'gradeLevels'])->find($id);
return new TeacherResource($teacher);
```

## Best Practices

### 1. Always Use Eager Loading
```php
// ❌ Bad - N+1 queries
$sessions = QuranSession::all();
return QuranSessionResource::collection($sessions);

// ✅ Good - Single query
$sessions = QuranSession::with(['quranTeacher', 'student', 'academy'])->get();
return QuranSessionResource::collection($sessions);
```

### 2. Use Appropriate Resource Type
```php
// ❌ Bad - Using full resource for listing
return TeacherResource::collection($teachers);

// ✅ Good - Using list resource for listing
return TeacherListResource::collection($teachers);

// ✅ Good - Using full resource for details
return new TeacherResource($teacher);
```

### 3. Handle Null Values Gracefully
```php
// All resources use null-safe operators
'birth_date' => $this->birth_date?->format('Y-m-d'),
'avatar_url' => $this->user?->avatar ?? $this->getDefaultAvatar(),
```

### 4. Use Conditional Fields
```php
// Only include when relationship is loaded
'teacher' => $this->whenLoaded('teacher', function () {
    return new TeacherListResource($this->teacher);
}),

// Only include when condition is met
'password' => $this->when($this->meeting_password, $this->meeting_password),
```

### 5. Return Enums Properly
```php
// ✅ Good - Returns both value and label
'status' => [
    'value' => $this->status->value,
    'label' => $this->status->label(),
    'color' => $this->status->color(),
],

// ❌ Bad - Returns only value
'status' => $this->status->value,
```

## Testing Resources

```php
// Example test
public function test_quran_session_resource_structure()
{
    $session = QuranSession::factory()
        ->for(QuranTeacherProfile::factory())
        ->for(Student::factory())
        ->create();

    $resource = new QuranSessionResource($session->load(['quranTeacher', 'student']));
    $data = $resource->toArray(request());

    $this->assertArrayHasKey('id', $data);
    $this->assertArrayHasKey('session_type', $data);
    $this->assertArrayHasKey('teacher', $data);
    $this->assertArrayHasKey('student', $data);
    $this->assertArrayHasKey('status', $data);
    $this->assertIsArray($data['status']);
    $this->assertArrayHasKey('value', $data['status']);
    $this->assertArrayHasKey('label', $data['status']);
}
```

## Performance Considerations

1. **Eager Loading**: Always eager load relationships to avoid N+1 queries
2. **List Resources**: Use minimal list resources for collections
3. **Conditional Loading**: Use `whenLoaded()` to only include loaded relationships
4. **Pagination**: Combine with Laravel pagination for large datasets
5. **Caching**: Consider caching expensive resource transformations

## Migration from Direct Model Serialization

```php
// ❌ Before - Direct model serialization
return response()->json($session);

// ✅ After - Using resource
return new QuranSessionResource($session);

// Benefits:
// - Consistent structure
// - Proper enum handling
// - Conditional fields
// - File URL transformation
// - Avatar fallbacks
// - Type safety
```

## Summary

The API Resource layer provides:

- ✅ **21 resource classes** organized by domain
- ✅ **Polymorphic support** for sessions and subscriptions
- ✅ **Full and List variants** for optimal performance
- ✅ **Collection resources** with metadata
- ✅ **Proper enum handling** with labels and colors
- ✅ **Conditional loading** to prevent N+1 queries
- ✅ **Consistent date formatting** (ISO 8601)
- ✅ **Smart avatar resolution** with fallbacks
- ✅ **File URL transformation** for storage paths
- ✅ **Null-safe operations** throughout
- ✅ **Type hints** on all methods
- ✅ **Comprehensive documentation** with examples

This creates a robust, consistent, and performant API response layer for the Itqan Platform.
