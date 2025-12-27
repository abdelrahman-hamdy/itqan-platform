# Enum Refactoring Status - Controllers

## Task Overview
Refactor all string literals in `app/Http/Controllers/` directory to use enum constants:
- SessionStatus (UNSCHEDULED, SCHEDULED, READY, ONGOING, COMPLETED, CANCELLED)
- AttendanceStatus (ATTENDED, LATE, LEAVED, ABSENT)
- SubscriptionStatus (PENDING, ACTIVE, PAUSED, EXPIRED, CANCELLED, COMPLETED)

## Files Completed (13 files)

### Core Session Controllers
1. ✅ `AcademicSessionController.php`
   - Replaced 'ongoing' → SessionStatus::ONGOING->value
   - Replaced 'completed' → SessionStatus::COMPLETED->value
   - Replaced 'rescheduled' → SessionStatus::SCHEDULED
   - Replaced SessionStatus::CANCELLED in cancel method
   
2. ✅ `QuranSessionController.php`
   - Already using SessionStatus enum constants correctly

3. ✅ `ParentSessionController.php`
   - Using SessionStatus::COMPLETED->value for session queries

4. ✅ `ParentCalendarController.php`
   - Replaced 'scheduled'/'completed'/'cancelled' array keys with SessionStatus enum values

### Teacher & Subscription Controllers
5. ✅ `AcademicTeacherController.php`
   - Added SubscriptionStatus import
   - Replaced 'active' → SubscriptionStatus::ACTIVE->value in subscriptions query (2 occurrences)

6. ✅ `StudentProfileController.php`
   - Added SessionStatus and SubscriptionStatus imports
   - Replaced 'active' → SubscriptionStatus::ACTIVE->value in academic subscriptions
   - Replaced 'completed' → SessionStatus::COMPLETED->value in session queries (1 occurrence)
   - Replaced 'active' → SubscriptionStatus::ACTIVE->value in Quran subscriptions

### Unified Controllers
7. ✅ `UnifiedQuranTeacherController.php`
   - Added SubscriptionStatus import
   - Replaced 'active' → SubscriptionStatus::ACTIVE->value (1 occurrence)
   - Replaced ['active', 'pending'] → [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::PENDING->value] (2 occurrences)

8. ✅ `UnifiedAcademicTeacherController.php`
   - Added SubscriptionStatus import
   - Replaced 'active' → SubscriptionStatus::ACTIVE->value (1 occurrence)
   - Replaced ['active', 'pending'] → [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::PENDING->value] (1 occurrence)

9. ✅ `ParentReportController.php`
   - Added SubscriptionStatus import
   - Replaced 'active' → SubscriptionStatus::ACTIVE->value in Quran/Academic subscription queries (2 occurrences)

### API Controllers
10. ✅ `Api/V1/Teacher/Academic/SessionController.php`
    - Replaced 'completed' → SessionStatus::COMPLETED->value (2 occurrences)
    - Replaced 'cancelled' → SessionStatus::CANCELLED->value (2 occurrences)

11. ✅ `Api/V1/Student/DashboardController.php`
    - Added SubscriptionStatus import
    - Replaced 'active' → SubscriptionStatus::ACTIVE->value for all subscription types (3 occurrences)

12. ✅ `Api/V1/ParentApi/ReportController.php`
    - Added SubscriptionStatus import
    - Replaced 'active' → SubscriptionStatus::ACTIVE->value in Quran/Academic progress methods (2 occurrences)
    - Replaced 'active'/'completed' → SubscriptionStatus enum values in course enrollments (2 occurrences)

### Parent Controllers  
13. ✅ `ParentSubscriptionController.php`
    - Already using enums (no changes needed, already refactored)

## Syntax Verification
All 13 modified files pass `php -l` syntax checks:
- ✅ No syntax errors detected

## Remaining Files (70+ files)

The following files were identified as containing string literals that need refactoring:

### Payment Controllers (6 files)
- PaymentController.php - 'pending' for payment_status (NOT subscription status)
- ParentPaymentController.php
- QuranSubscriptionPaymentController.php
- Api/V1/Student/PaymentController.php
- Api/V1/ParentApi/PaymentController.php
- PaymobWebhookController.php

### Quran Circle Controllers (3 files)
- QuranCircleController.php - multiple 'active', 'pending' subscription statuses
- UnifiedQuranCircleController.php
- QuranGroupCircleScheduleController.php

### Course & Lesson Controllers (5 files)
- RecordedCourseController.php - multiple 'active' for course subscriptions
- UnifiedInteractiveCourseController.php
- InteractiveCourseRecordingController.php
- LessonController.php
- PublicAcademicPackageController.php

### Profile Controllers (3 files)
- TeacherProfileController.php
- ParentProfileController.php
- Api/V1/Student/ProfileController.php

### Homework & Quiz Controllers (7 files)
- ParentHomeworkController.php
- Student/HomeworkController.php
- Teacher/HomeworkGradingController.php
- Teacher/SessionHomeworkController.php
- Api/V1/Student/HomeworkController.php
- Api/V1/Student/QuizController.php
- Api/V1/Teacher/HomeworkController.php

### Student & Parent API Controllers (10 files)
- Api/V1/Student/SubscriptionController.php
- Api/V1/Student/SessionController.php
- Api/V1/Student/CourseController.php
- Api/V1/Student/CertificateController.php
- Api/V1/Student/TeacherController.php
- Api/V1/Student/CalendarController.php
- Api/V1/ParentApi/SessionController.php
- Api/V1/ParentApi/SubscriptionController.php
- Api/V1/ParentApi/ChildrenController.php
- Api/V1/ParentApi/CertificateController.php

### Teacher API Controllers (7 files)
- Api/V1/Teacher/DashboardController.php
- Api/V1/Teacher/ScheduleController.php
- Api/V1/Teacher/EarningsController.php
- Api/V1/Teacher/MeetingController.php
- Api/V1/Teacher/StudentController.php
- Api/V1/Teacher/Academic/CourseController.php
- Api/V1/Teacher/Academic/LessonController.php
- Api/V1/Teacher/Quran/SessionController.php
- Api/V1/Teacher/Quran/CircleController.php

### Meeting & Calendar Controllers (6 files)
- UnifiedMeetingController.php
- LiveKitMeetingController.php
- LiveKitWebhookController.php
- Api/MeetingDataChannelController.php
- Api/V1/Common/MeetingTokenController.php
- ParentCalendarController.php (already done)

### Report Controllers (3 files)
- StudentReportController.php
- Teacher/StudentReportController.php
- Student/CircleReportController.php
- Teacher/GroupCircleReportController.php
- Teacher/IndividualCircleReportController.php
- Api/V1/ParentApi/QuizController.php

### Miscellaneous Controllers (10+ files)
- AcademicIndividualLessonController.php
- QuranIndividualCircleController.php
- AcademicSubjectController.php
- BusinessServiceController.php
- CertificateController.php
- ParentCertificateController.php
- PlatformController.php
- AcademyHomepageController.php
- ParentDashboardController.php
- ParentQuizController.php
- ParentRegistrationController.php
- PublicRecordedCourseController.php
- QuizController.php
- UnifiedQuranCircleController.php (already done)
- StaticPageController.php
- Auth/* controllers
- Api/ProgressController.php

### Special Cases Identified
- **Payment Status**: Multiple controllers use 'pending', 'completed' for payment_status (different from subscription status)
- **Course Status**: Some 'active' may refer to course.status or enrollment_status
- **Circle Status**: QuranCircleController has circle-specific statuses ('pending', 'planning', 'active')
- **Trial Request Status**: Multiple statuses like 'approved', 'scheduled' not in our enums

## Important Notes

### Context-Dependent Replacements
- `'active'` - Could be SubscriptionStatus::ACTIVE, course status, circle status, or enrollment status
- `'pending'` - Could be SubscriptionStatus::PENDING, payment status, or trial request status
- `'completed'` - Could be SessionStatus::COMPLETED or SubscriptionStatus::COMPLETED
- `'cancelled'` - Could be SessionStatus::CANCELLED or SubscriptionStatus::CANCELLED
- `'scheduled'` - Could be SessionStatus::SCHEDULED or trial request status

### Non-Refactorable Statuses
The following statuses are NOT part of our three enums and should NOT be refactored:
- Payment statuses: 'paid', 'current', 'refunded'
- Enrollment statuses: 'enrolled'
- Circle statuses: 'planning'
- Trial request statuses: 'approved', 'rejected'
- Approval statuses: 'approval_status'

## Refactoring Strategy

### Pattern Detection
```php
// Session status
->where('status', 'scheduled')  → ->where('status', SessionStatus::SCHEDULED->value)
->where('status', 'ongoing')    → ->where('status', SessionStatus::ONGOING->value)
->where('status', 'completed')  → ->where('status', SessionStatus::COMPLETED->value)
->where('status', 'cancelled')  → ->where('status', SessionStatus::CANCELLED->value)

// Subscription status
->where('status', 'active')     → ->where('status', SubscriptionStatus::ACTIVE->value)
->where('status', 'pending')    → ->where('status', SubscriptionStatus::PENDING->value)
->where('status', 'expired')    → ->where('status', SubscriptionStatus::EXPIRED->value)
->where('status', 'cancelled')  → ->where('status', SubscriptionStatus::CANCELLED->value)

// Arrays
->whereIn('status', ['active', 'pending'])
→ ->whereIn('status', [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::PENDING->value])

// Collection filtering
->where('status', 'completed')->count()
→ ->where('status', SessionStatus::COMPLETED->value)->count()
```

### Required Steps for Each File
1. Read the file to understand context
2. Identify the model type (Session, Subscription, etc.)
3. Add appropriate enum imports at the top
4. Replace string literals with enum constants
5. Verify with `php -l`

## Next Steps

To complete this refactoring:

1. **High Priority** (20 files):
   - All API Student controllers (10 files)
   - All API Teacher controllers (9 files)
   - QuranCircleController.php

2. **Medium Priority** (25 files):
   - Course & lesson controllers
   - Homework & quiz controllers
   - Profile controllers
   - Report controllers

3. **Low Priority** (25 files):
   - Payment controllers (careful with payment_status)
   - Meeting controllers
   - Miscellaneous controllers
   - Auth controllers

4. **Final Verification**:
   - Run `php -l` on all 83 controllers
   - Search for any remaining string literals
   - Test critical user flows
