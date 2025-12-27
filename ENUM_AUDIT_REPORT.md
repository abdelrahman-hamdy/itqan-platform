# Comprehensive Enum Usage Audit Report

## Executive Summary

This report documents all instances where string literals are used instead of enum constants across the Laravel application. The audit covered the following enums:

1. **SessionStatus** (`app/Enums/SessionStatus.php`)
2. **AttendanceStatus** (`app/Enums/AttendanceStatus.php`)
3. **SubscriptionStatus** (`app/Enums/SubscriptionStatus.php`)
4. **SubscriptionPaymentStatus** (`app/Enums/SubscriptionPaymentStatus.php`)
5. **PaymentResultStatus** (`app/Enums/PaymentResultStatus.php`)

**Note:** No separate `PaymentStatus` or `HomeworkStatus` enums exist. Payment-related statuses use `SubscriptionPaymentStatus` and `PaymentResultStatus`. Homework submission statuses are currently stored as plain strings without enum definitions.

---

## 1. SessionStatus Violations

### Enum Definition
```php
enum SessionStatus: string
{
    case UNSCHEDULED = 'unscheduled';
    case SCHEDULED = 'scheduled';
    case READY = 'ready';
    case ONGOING = 'ongoing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case ABSENT = 'absent';
}
```

### Violations Found

#### 'unscheduled' (1 violation)

**app/Models/QuranIndividualCircle.php:204**
```php
$unscheduled = $this->sessions()->where('status', 'unscheduled')->count();
```
❌ Should use: `SessionStatus::UNSCHEDULED->value`

---

#### 'scheduled' (60+ violations)

**app/Console/Commands/GenerateTestData.php:550**
```php
'status' => $i < 1 ? 'completed' : 'scheduled',
```
❌ Should use: `SessionStatus::SCHEDULED->value`

**app/Services/Scheduling/Validators/AcademicLessonValidator.php:286**
```php
->whereIn('status', ['completed', 'scheduled', 'in_progress'])
```
❌ Should use: `[SessionStatus::COMPLETED->value, SessionStatus::SCHEDULED->value, 'in_progress']`

**app/Services/Scheduling/Validators/IndividualCircleValidator.php:193**
```php
->whereIn('status', ['scheduled', 'in_progress', 'completed'])
```
❌ Should use: `[SessionStatus::SCHEDULED->value, 'in_progress', SessionStatus::COMPLETED->value]`

**app/Services/Scheduling/Validators/IndividualCircleValidator.php:244**
```php
->whereIn('status', ['completed', 'scheduled', 'in_progress'])
```
❌ Should use: Similar to above

**app/Services/AcademicSessionSchedulingService.php:64**
```php
'attendance_status' => 'scheduled',
```
❌ **CRITICAL ERROR**: 'scheduled' is not a valid `AttendanceStatus`. Should use `AttendanceStatus::ABSENT->value` or appropriate status.

**app/Http/Controllers/ParentCalendarController.php:142**
```php
'scheduled' => 0,
```
✅ Valid - used as array key for statistics

**app/Services/Scheduling/Validators/TrialSessionValidator.php:115**
```php
->whereIn('status', ['scheduled', 'in_progress', 'completed'])
```
❌ Should use enum values

**app/Services/Scheduling/Validators/InteractiveCourseValidator.php:67, 152, 192, 217**
```php
->whereIn('status', ['scheduled', 'in_progress', 'completed'])
```
❌ Should use enum values (4 occurrences)

**app/Http/Controllers/UnifiedInteractiveCourseController.php:293**
```php
->orWhere('status', 'scheduled')
```
❌ Should use: `SessionStatus::SCHEDULED->value`

**app/Http/Controllers/TeacherProfileController.php:388**
```php
->whereIn('status', ['pending', 'approved', 'scheduled'])
```
❌ Mixed context - 'scheduled' should use enum

**app/Http/Controllers/TeacherProfileController.php:407**
```php
->whereIn('status', ['scheduled', 'completed'])
```
❌ Should use enum values

**app/Http/Controllers/UnifiedQuranTeacherController.php:199**
```php
->whereIn('status', ['pending', 'approved', 'scheduled', 'completed'])
```
❌ Should use enum values for 'scheduled' and 'completed'

**app/Http/Controllers/UnifiedQuranTeacherController.php:248**
```php
->whereIn('status', ['pending', 'approved', 'scheduled', 'completed'])
```
❌ Same as above

**app/Services/Calendar/QuranSessionStrategy.php:127**
```php
'status' => $isScheduled ? 'scheduled' : 'not_scheduled',
```
❌ Should use: `SessionStatus::SCHEDULED->value` (note: 'not_scheduled' is not a valid enum value)

**app/Services/Calendar/QuranSessionStrategy.php:195**
```php
->whereIn('status', ['pending', 'approved', 'scheduled'])
```
❌ Mixed context - 'scheduled' should use enum

**app/Http/Controllers/UnifiedMeetingController.php:195**
```php
if ($session->status->value === 'ready' || $session->status->value === 'scheduled') {
```
❌ Should use: `SessionStatus::READY` and `SessionStatus::SCHEDULED` (compare enum objects directly)

**app/Http/Controllers/StudentProfileController.php:1742**
```php
->whereIn('status', ['scheduled', 'ongoing'])
```
❌ Should use enum values

**app/Http/Controllers/Api/V1/Teacher/MeetingController.php:106**
```php
$canJoin = in_array($statusValue, ['scheduled', 'live', 'in_progress']);
```
❌ Should use enum values (note: 'live' and 'in_progress' are not valid SessionStatus values)

**app/Models/QuranCircleSchedule.php:248, 352**
```php
'status' => 'scheduled',
```
❌ Should use: `SessionStatus::SCHEDULED->value` (2 occurrences)

**app/Models/AcademicSession.php:102**
```php
'attendance_status' => AttendanceStatus::ABSENT->value,  // Fixed: 'scheduled' is not a valid attendance status
```
✅ Already fixed with comment

**app/Models/QuranIndividualCircle.php:127**
```php
return $this->sessions()->whereIn('status', ['scheduled', 'in_progress']);
```
❌ Should use enum values

**app/Models/QuranTrialRequest.php:54**
```php
const STATUS_SCHEDULED = 'scheduled';
```
❌ Should use enum constant instead of model constant

**app/Models/InteractiveCourseSession.php:35**
```php
'status' => 'scheduled',
```
❌ Should use: `SessionStatus::SCHEDULED->value`

**app/Models/SessionSchedule.php:213, 317, 392, 426**
```php
->where('status', 'scheduled')
```
❌ Should use enum value (4 occurrences)

**app/Http/Controllers/Api/V1/ParentApi/ReportController.php:226**
```php
$upcomingSessions = $sessions->whereIn('status', ['scheduled', 'live']);
```
❌ Should use enum values (note: 'live' is not a valid SessionStatus value)

**app/Models/QuranSession.php:1051**
```php
'status' => 'scheduled',
```
❌ Should use: `SessionStatus::SCHEDULED->value`

**app/Models/QuranCircle.php:737**
```php
'status' => 'scheduled',
```
❌ Should use enum value

**app/Http/Controllers/ParentProfileController.php:218, 223, 312, 317**
```php
->whereIn('status', ['scheduled', 'pending', 'ready'])
```
❌ Should use enum values (4 occurrences)

**Filament Widgets (Multiple files)**
- `app/Filament/Teacher/Widgets/TeacherCalendarWidget.php:93, 115, 138, 296, 608, 621`
- `app/Filament/AcademicTeacher/Widgets/AcademicQuickActionsWidget.php:45, 54`
- `app/Filament/AcademicTeacher/Widgets/AcademicFullCalendarWidget.php:86, 152, 267, 280`
- `app/Filament/Teacher/Widgets/QuickActionsWidget.php:45`
- `app/Filament/Teacher/Widgets/RecentSessionsWidget.php:87`

❌ All should use enum values

**Filament Resources (Multiple files)**
- `app/Filament/Teacher/Resources/QuranSessionResource.php:363`
- `app/Filament/Teacher/Resources/QuranTrialRequestResource.php:79, 404, 447`
- `app/Filament/Teacher/Resources/QuranSessionResource/Pages/CreateQuranSession.php:26`
- `app/Filament/Resources/QuranTrialRequestResource.php:216, 424`
- `app/Filament/AcademicTeacher/Resources/AcademicSessionResource.php:226, 298`
- `app/Filament/AcademicTeacher/Resources/InteractiveCourseSessionResource.php:277, 299`
- `app/Filament/Resources/InteractiveCourseSessionResource.php:241, 263`
- `app/Filament/Resources/AcademicSessionResource.php:227, 302`

❌ All should use enum values

---

#### 'ready' (20+ violations)

**app/Traits/HasRecording.php:60**
```php
if (!in_array($this->status?->value, ['ready', 'ongoing'])) {
```
❌ Should use: `[SessionStatus::READY->value, SessionStatus::ONGOING->value]`

**app/Traits/HasMeetings.php:25**
```php
if (! in_array($this->status->value, ['ready', 'ongoing']) || $this->meeting_room_name) {
```
❌ Same as above

**app/Helpers/TimeHelper.php:237**
```php
'type' => 'ready',
```
✅ Valid - used as a different type field, not session status

**Filament Widgets**
- `app/Filament/Teacher/Widgets/TeacherCalendarWidget.php:93, 115, 138`
- `app/Filament/AcademicTeacher/Widgets/AcademicQuickActionsWidget.php:45, 54`
- `app/Filament/AcademicTeacher/Widgets/AcademicFullCalendarWidget.php:86`

❌ All should use enum values

**Filament Resources**
- `app/Filament/Resources/InteractiveCourseSessionResource.php:241, 263`
- `app/Filament/AcademicTeacher/Resources/InteractiveCourseSessionResource.php:277, 299`

❌ All should use enum values

**Controllers**
- `app/Http/Controllers/InteractiveCourseRecordingController.php:98`
- `app/Http/Controllers/UnifiedMeetingController.php:195`
- `app/Http/Controllers/ParentProfileController.php:218, 223, 312, 317`

❌ All should use enum values

**Models**
- `app/Models/BaseSession.php:222` - Default fallback value
❌ Should use: `SessionStatus::READY->value`

---

#### 'ongoing' (25+ violations)

**app/Traits/HasRecording.php:60**
```php
if (!in_array($this->status?->value, ['ready', 'ongoing'])) {
```
❌ Should use enum value

**app/Traits/HasMeetings.php:25**
```php
if (! in_array($this->status->value, ['ready', 'ongoing']) || $this->meeting_room_name) {
```
❌ Should use enum value

**Filament Widgets**
- `app/Filament/Teacher/Widgets/TeacherCalendarWidget.php:93, 115, 138, 177, 243, 301`
- `app/Filament/AcademicTeacher/Widgets/AcademicFullCalendarWidget.php:86`
- `app/Filament/Teacher/Widgets/RecentSessionsWidget.php:88`

❌ All should use enum values

**Controllers**
- `app/Http/Controllers/UnifiedQuranCircleController.php:183`
- `app/Http/Controllers/InteractiveCourseRecordingController.php:98`
- `app/Http/Controllers/AcademicSessionController.php:386`
- `app/Http/Controllers/AcademyHomepageController.php:29`
- `app/Http/Controllers/StudentProfileController.php:452, 1742`

❌ All should use enum values

**Filament Resources**
- `app/Filament/Resources/InteractiveCourseSessionResource.php:252`
- `app/Filament/Resources/AcademicSessionResource.php:316`
- `app/Filament/AcademicTeacher/Resources/InteractiveCourseSessionResource.php:288`
- `app/Filament/AcademicTeacher/Resources/AcademicSessionResource.php:312`

❌ All should use enum values

**Models**
- `app/Models/QuranCircle.php:329, 584` - Status labels and default values
- `app/Models/BaseSession.php:680` - Comment mentions 'ongoing'

❌ Should use enum values

---

#### 'completed' (150+ violations)

Too numerous to list individually. Found in:
- Multiple controllers (Parent, Student, Teacher, API endpoints)
- Service classes (Attendance, Reporting, Subscription)
- Models (Payment, Session, Subscription, Recording)
- Filament resources and widgets
- Statistics and dashboard calculations

**Pattern Examples:**
```php
->where('status', 'completed')
->whereIn('status', ['completed', 'cancelled'])
if ($status === 'completed')
'status' => 'completed'
```

❌ All should use: `SessionStatus::COMPLETED->value`

---

#### 'cancelled' (80+ violations)

Found extensively in:
- Payment models and services
- Session models and controllers
- Subscription resources
- API endpoints
- Filament resources

**Pattern Examples:**
```php
->where('status', 'cancelled')
->whereNotIn('status', ['cancelled', 'completed'])
if ($status === 'cancelled')
'status' => 'cancelled'
```

❌ All should use: `SessionStatus::CANCELLED->value`

---

#### 'absent' (30+ violations)

**app/Services/QuranCircleReportService.php:222, 231, 252**
```php
'absent' => 0,
$absent = $sessionReports->where('attendance_status', 'absent')->count();
'absent' => $absent,
```
❌ Should use: `AttendanceStatus::ABSENT->value`

**app/Models/QuranIndividualCircle.php:132**
```php
return $this->sessions()->whereIn('status', ['completed', 'absent']);
```
❌ Should use: `SessionStatus::ABSENT->value`

**app/Models/QuranSession.php:445, 471, 485, 673**
```php
$this->recordSessionAttendance('absent');
'attendance_status' => 'absent',
'absent' => 'غائب',
```
❌ Should use enum values

**app/Http/Controllers/ParentReportController.php:490, 538, 539, 562, 563, 592, 598, 621, 627**
Multiple occurrences in reporting logic
❌ Should use enum values

**app/Models/InteractiveCourseSession.php:137**
```php
->where('attendance_status', 'absent');
```
❌ Should use: `AttendanceStatus::ABSENT->value`

---

#### Invalid Status Values Found

**'in_progress'** - Used in multiple files but NOT defined in SessionStatus enum:
- `app/Services/SessionManagementService.php:233, 327`
- `app/Services/Scheduling/Validators/*.php` (multiple files)
- `app/Models/QuranIndividualCircle.php:127`
- `app/Filament/Teacher/Resources/QuranSessionResource.php:366, 374`

⚠️ **CRITICAL**: Either add 'in_progress' to SessionStatus enum OR remove its usage

**'live'** - Used but NOT defined in SessionStatus enum:
- `app/Http/Controllers/UnifiedInteractiveCourseController.php:294`
- `app/Http/Controllers/Api/V1/Teacher/MeetingController.php:106`
- `app/Http/Controllers/Api/V1/ParentApi/ReportController.php:226`

⚠️ **CRITICAL**: Either add 'live' to SessionStatus enum OR remove its usage

---

## 2. AttendanceStatus Violations

### Enum Definition
```php
enum AttendanceStatus: string
{
    case ATTENDED = 'attended';
    case LATE = 'late';
    case LEAVED = 'leaved';
    case ABSENT = 'absent';
}
```

### Violations Found

#### 'attended' (40+ violations)

**app/Console/Commands/GenerateTestData.php:894, 918**
```php
'attendance_status' => 'attended',
```
❌ Should use: `AttendanceStatus::ATTENDED->value`

**app/Services/QuranCircleReportService.php:221, 230, 234, 241, 251, 560**
```php
'attended' => 0,
$attended = $sessionReports->where('attendance_status', 'attended')->count();
$avgDuration = $sessionReports->where('attendance_status', 'attended')
if ($report->attendance_status === 'attended') {
'attended' => $attended,
if ($report && $report->attendance_status === 'attended') {
```
❌ All should use: `AttendanceStatus::ATTENDED->value` (6 occurrences)

**app/Services/Attendance/AcademicReportService.php:259, 297**
```php
'attended' => 0,
'attended' => $attended,
```
❌ Should use enum value

**app/Services/Attendance/InteractiveReportService.php:222, 268**
```php
'attended' => 0,
'attended' => $attended,
```
❌ Should use enum value

**app/Models/QuranSession.php:391, 672**
```php
$session->recordSessionAttendance('attended');
'attended' => 'حاضر',
```
❌ Should use enum value

**app/Models/InteractiveCourseSession.php:128, 253, 294**
```php
->where('attendance_status', 'attended');
'attendance_status' => 'attended',
'attendance_count' => $this->attendances()->where('attendance_status', 'attended')->count()
```
❌ All should use enum value (3 occurrences)

**app/Livewire/Student/AttendanceStatus.php:169**
```php
'attended' => 'bg-green-500',
```
✅ Valid - used as array key for CSS classes

---

#### 'present' (15+ violations)

**app/Services/MeetingAttendanceService.php:472**
```php
'present' => $attendances->where('attendance_status', AttendanceStatus::ATTENDED->value)->count(),
```
✅ Valid - 'present' is a display key, mapping correctly to `AttendanceStatus::ATTENDED`

**app/Services/Reports/InteractiveCourseReportService.php:87, 180, 312**
```php
return in_array($status, [AttendanceStatus::ATTENDED->value, 'present', AttendanceStatus::LATE->value]);
```
❌ Should use only enum values, not 'present' literal

**app/Services/Attendance/BaseReportSyncService.php:390**
```php
'present' => $reports->where('attendance_status', AttendanceStatus::ATTENDED->value)->count(),
```
✅ Valid - 'present' is a display key

**app/Http/Controllers/ParentReportController.php:591, 597, 620, 626**
```php
'present' => $quranPresent,
'present' => $academicPresent,
'present' => 0,
```
✅ Valid - used as array keys for statistics

---

#### 'late' (40+ violations)

**app/Models/InteractiveCourseHomework.php:97, 102, 146, 159, 234**
Homework submission status - NOT attendance status
✅ Valid - different context

**app/Models/QuranSession.php:674**
```php
'late' => 'متأخر',
```
❌ Should use: `AttendanceStatus::LATE->value` (if this is status labels array)

**app/Models/AcademicSessionReport.php:184, 197**
```php
'late' => 0,
'late' => $late,
```
✅ Valid - used as array keys for statistics

**app/Models/InteractiveCourseSession.php:146**
```php
->where('attendance_status', 'late');
```
❌ Should use: `AttendanceStatus::LATE->value`

**app/Services/MeetingAttendanceService.php:473**
```php
'late' => $attendances->where('attendance_status', AttendanceStatus::LATE->value)->count(),
```
✅ Correctly uses enum

**app/Services/Attendance/AcademicReportService.php:261, 299**
```php
'late' => 0,
'late' => $late,
```
✅ Valid - used as array keys

**app/Services/Attendance/BaseReportSyncService.php:391**
```php
'late' => $reports->where('attendance_status', AttendanceStatus::LATE->value)->count(),
```
✅ Correctly uses enum

**app/Services/Attendance/InteractiveReportService.php:224, 270**
```php
'late' => 0,
'late' => $late,
```
✅ Valid - used as array keys

**app/Services/QuranCircleReportService.php:223, 253**
```php
'late' => 0,
'late' => $late,
```
✅ Valid - used as array keys

**app/Filament/AcademicTeacher/Widgets/PendingHomeworkWidget.php:67, 73**
Homework submission status - different context
✅ Valid

---

#### 'leaved' (10 violations)

**app/Services/MeetingAttendanceService.php:474**
```php
'partial' => $attendances->where('attendance_status', AttendanceStatus::LEAVED->value)->count(),
```
✅ Correctly uses enum (note: display key is 'partial' but uses correct enum)

**app/Services/Attendance/BaseReportSyncService.php:392**
```php
'partial' => $reports->where('attendance_status', AttendanceStatus::LEAVED->value)->count(),
```
✅ Correctly uses enum

**app/Models/QuranSession.php:675**
```php
'leaved' => 'غادر مبكراً',
```
❌ Should use: `AttendanceStatus::LEAVED->value` (if this is status labels array)

**app/Livewire/Student/AttendanceStatus.php:171**
```php
'leaved' => 'bg-orange-500',
```
✅ Valid - used as array key for CSS classes

---

#### 'absent' - See SessionStatus section above (shared value)

**Summary:** 'absent' is used in both `SessionStatus::ABSENT` and `AttendanceStatus::ABSENT`, creating potential confusion. All attendance-related usage should use `AttendanceStatus::ABSENT->value`.

---

## 3. SubscriptionStatus Violations

### Enum Definition
```php
enum SubscriptionStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case REFUNDED = 'refunded';
}
```

### Violations Found

#### 'pending' (100+ violations)

Found extensively in:
- Payment controllers and services
- Subscription models
- Service requests
- Homework services (different context - homework submission status)
- Teacher approval workflows
- Statistics and dashboard calculations

**Pattern Examples:**
```php
->where('status', 'pending')
->whereIn('status', ['pending', 'active'])
'status' => 'pending',
if ($this->status === 'pending')
```

❌ Should use: `SubscriptionStatus::PENDING->value` (when referring to subscription status)

**Notable Examples:**

**app/Models/QuranSubscription.php:122, 123, 570**
```php
'status' => 'pending',
'payment_status' => 'pending',
default => 'pending',
```
❌ Should use enum values

**app/Models/AcademicSubscription.php:161, 162**
```php
'status' => 'pending',
'payment_status' => 'pending',
```
❌ Should use enum values

**app/Models/CourseSubscription.php:173, 174**
```php
'status' => 'pending',
'payment_status' => 'pending',
```
❌ Should use enum values

**app/Services/CircleEnrollmentService.php:69, 261**
```php
'payment_status' => ($circle->monthly_fee && $circle->monthly_fee > 0) ? 'pending' : 'paid',
```
❌ Should use: `SubscriptionPaymentStatus::PENDING->value` and `SubscriptionPaymentStatus::PAID->value`

---

#### 'active' (100+ violations)

Found extensively in:
- Subscription queries across all models
- Circle enrollment services
- Search services
- Course enrollment queries
- Statistics calculations
- Interactive course status

**Pattern Examples:**
```php
->where('status', 'active')
->whereIn('status', ['active', 'pending'])
'status' => 'active',
if ($subscription->status === 'active')
```

❌ Should use: `SubscriptionStatus::ACTIVE->value`

**Notable Examples:**

**app/Services/CircleEnrollmentService.php:70, 262**
```php
'status' => 'active',
```
❌ Should use enum value

**app/Services/SearchService.php:99, 174, 196, 234**
Multiple occurrences in search logic
❌ Should use enum value

**app/Filament/Widgets/QuranOverviewWidget.php:36**
```php
$activeSubscriptions = QuranSubscription::where('status', 'active')
```
❌ Should use enum value

**app/Filament/Widgets/SuperAdminMonthlyStatsWidget.php:50, 51**
```php
$activeQuranSubs = QuranSubscription::where('status', 'active')->count();
$activeAcademicSubs = AcademicSubscription::where('status', 'active')->count();
```
❌ Should use enum value (2 occurrences)

---

#### 'paused' (10 violations)

**app/Models/SessionSchedule.php:60**
```php
const STATUS_PAUSED = 'paused';
```
❌ Should use enum constant instead of model constant

**app/Filament/Resources/AcademicSubscriptionResource/Pages/ViewAcademicSubscription.php:47, 70**
```php
'status' => 'paused',
->visible(fn () => $this->record->status === 'paused'),
```
❌ Should use: `SubscriptionStatus::PAUSED->value` (2 occurrences)

**app/Filament/Resources/QuranSubscriptionResource/Pages/ViewQuranSubscription.php:47, 70**
```php
'status' => 'paused',
->visible(fn () => $this->record->status === 'paused'),
```
❌ Should use enum value (2 occurrences)

**app/Filament/Resources/QuranSubscriptionResource.php:387**
```php
'warning' => 'paused',
```
❌ Should use enum value

**app/Http/Controllers/ParentReportController.php:311**
```php
'paused' => 'متوقف مؤقتاً',
```
✅ Valid - used as array key for display labels

---

#### 'expired' (25 violations)

**app/Models/Subscription.php:151, 165**
```php
'expired' => 'منتهي الصلاحية',
'expired' => 'danger',
```
✅ Valid - used as array keys for labels and colors

**app/Services/SubscriptionService.php:332, 348, 363**
```php
'expired' => 0,
'expired' => $modelClass::where('academy_id', $academyId)->where('status', SubscriptionStatus::EXPIRED->value)->count(),
$stats['expired'] += $typeStats['expired'];
```
✅ Partially correct - query uses enum, but keys could be more explicit

**app/Filament/Resources/QuranSubscriptionResource.php:386**
```php
'danger' => fn ($state): bool => in_array($state instanceof \App\Enums\SubscriptionStatus ? $state->value : $state, ['expired', 'suspended']),
```
❌ Should use: `[SubscriptionStatus::EXPIRED->value, 'suspended']`

**app/Filament/Resources/AcademicSubscriptionResource/Pages/ViewAcademicSubscription.php:95**
```php
->visible(fn () => !in_array($this->record->status, ['cancelled', 'expired'])),
```
❌ Should use enum values

**app/Filament/Resources/QuranSubscriptionResource/Pages/ViewQuranSubscription.php:95, 122**
```php
->visible(fn () => !in_array($this->record->status, ['cancelled', 'expired'])),
->visible(fn () => in_array($this->record->status, ['expired', 'active'])),
```
❌ Should use enum values (2 occurrences)

---

#### 'cancelled' - See SessionStatus section (shared value)

**app/Models/QuranSubscription.php:568**
```php
SubscriptionStatus::CANCELLED => 'cancelled',
```
✅ Correctly uses enum constant

**app/Services/SubscriptionService.php:333, 350, 364**
```php
'cancelled' => 0,
'cancelled' => $modelClass::where('academy_id', $academyId)->where('status', SubscriptionStatus::CANCELLED->value)->count(),
$stats['cancelled'] += $typeStats['cancelled'];
```
✅ Partially correct

---

#### 'completed' - See SessionStatus section (shared value)

Used in both session and subscription contexts. When referring to subscriptions, should use `SubscriptionStatus::COMPLETED->value`.

---

#### 'refunded' (20 violations)

**app/Models/Payment.php:128, 185, 200, 239, 333, 475**
```php
return $query->where('status', 'refunded');
'refunded' => 'مسترد',
'refunded' => 'info',
return in_array($this->status, ['refunded', 'partially_refunded']);
$status = $totalRefunded >= $this->amount ? 'refunded' : 'partially_refunded';
'total_refunded' => self::where('academy_id', $academyId)->whereIn('status', ['refunded', 'partially_refunded'])->sum('refund_amount'),
```
❌ Should use: `SubscriptionStatus::REFUNDED->value` or `PaymentResultStatus::REFUNDED->value` (context-dependent)

**app/Models/PaymentAuditLog.php:124, 127**
```php
'action' => 'refunded',
'status_to' => 'refunded',
```
❌ Should use appropriate enum value

**app/Services/PaymentService.php:202**
```php
'status' => $newRefundedTotal >= $fullAmount ? 'refunded' : 'partially_refunded',
```
❌ Should use: `PaymentResultStatus::REFUNDED->value` and `PaymentResultStatus::PARTIALLY_REFUNDED->value`

---

## 4. SubscriptionPaymentStatus Violations

### Enum Definition
```php
enum SubscriptionPaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
}
```

### Violations Found

#### 'pending' - See SubscriptionStatus section (shared value, different context)

When used for payment status specifically, should use `SubscriptionPaymentStatus::PENDING->value`.

#### 'paid' (50+ violations)

**app/Models/QuranSubscription.php:123**
```php
'payment_status' => 'pending',
```
Note: Should be 'paid' in some contexts
❌ Should use: `SubscriptionPaymentStatus::PAID->value`

**app/Models/Payment.php:113, 224, 284**
```php
->where('payment_status', 'paid');
return $this->status === 'completed' && $this->payment_status === 'paid';
'payment_status' => 'paid',
```
❌ Should use enum value (3 occurrences)

**app/Services/CircleEnrollmentService.php:69, 261**
```php
'payment_status' => ($circle->monthly_fee && $circle->monthly_fee > 0) ? 'pending' : 'paid',
```
❌ Should use: `SubscriptionPaymentStatus::PAID->value`

**app/Services/PayoutService.php:270, 287**
```php
'status' => 'paid',
$this->sendPayoutNotification($payout, 'paid', null, $paymentDetails['reference'] ?? null);
```
❌ Should use enum value

**app/Models/Subscription.php:218**
```php
'payment_status' => 'paid'
```
❌ Should use enum value

**app/Models/InteractiveCourseEnrollment.php:78, 133**
```php
'paid' => 'مدفوع',
return $query->where('payment_status', 'paid');
```
❌ Should use enum value

**app/Console/Commands/GenerateTestData.php:566**
```php
'payment_status' => 'paid',
```
❌ Should use enum value

**app/Http/Controllers/UnifiedInteractiveCourseController.php:310**
```php
'payment_status' => 'paid', // Mark as paid (bypassing payment for now)
```
❌ Should use enum value

---

#### 'failed' (30 violations)

**app/Models/Payment.php:123, 234, 302, 303, 473**
```php
return $query->where('status', 'failed');
return $this->status === 'failed';
'status' => 'failed',
'payment_status' => 'failed',
'failed_payments' => self::where('academy_id', $academyId)->where('status', 'failed')->count(),
```
❌ Should use: `SubscriptionPaymentStatus::FAILED->value` (5 occurrences)

**app/Traits/HasRecording.php:316**
```php
'failed_recordings' => $allRecordings->where('status', 'failed')->count(),
```
✅ Valid - different context (recording status)

**app/Models/SessionRecording.php:117, 169, 256, 271, 314**
Recording status - different context
✅ Valid

**app/Services/RecordingService.php:336**
```php
'failed_count' => $recordings->where('status', 'failed')->count(),
```
✅ Valid - recording status

**app/Services/MeetingDataChannelService.php:129, 154, 205, 251**
Data channel status - different context
✅ Valid

---

#### 'refunded' - See SubscriptionStatus section

Should use `SubscriptionPaymentStatus::REFUNDED->value` when referring to payment status specifically.

---

## 5. PaymentResultStatus Violations

### Enum Definition
```php
enum PaymentResultStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case EXPIRED = 'expired';
}
```

### Violations Found

#### 'processing' (10 violations)

**app/Services/ParentDashboardService.php:91**
```php
->whereIn('status', ['pending', 'processing'])
```
❌ Should use: `[PaymentResultStatus::PENDING->value, PaymentResultStatus::PROCESSING->value]`

**app/Services/Payment/PaymentStateMachine.php:24, 25**
```php
'pending' => ['processing', 'failed', 'cancelled', 'expired'],
'processing' => ['success', 'failed', 'cancelled'],
```
❌ Should use enum values in state transitions

**app/Services/Payment/PaymentStateMachine.php:103**
```php
return in_array(strtolower($status), ['pending', 'processing']);
```
❌ Should use enum values

---

#### 'success' (30 violations)

**app/Models/PaymentWebhookEvent.php:114**
```php
'status' => ($obj['success'] ?? false) ? 'success' : 'failed',
```
❌ Should use: `PaymentResultStatus::SUCCESS->value` and `PaymentResultStatus::FAILED->value`

**app/Services/Payment/PaymentStateMachine.php:25, 26**
```php
'processing' => ['success', 'failed', 'cancelled'],
'success' => ['refunded', 'partially_refunded'],
```
❌ Should use enum values

**Filament Resources** (multiple files)
Badge colors and filters use 'success' as a Filament color name
✅ Valid - these are Filament UI color classes, not payment statuses

---

#### 'partially_refunded' (10 violations)

**app/Models/Payment.php:239, 333, 475**
```php
return in_array($this->status, ['refunded', 'partially_refunded']);
$status = $totalRefunded >= $this->amount ? 'refunded' : 'partially_refunded';
'total_refunded' => self::where('academy_id', $academyId)->whereIn('status', ['refunded', 'partially_refunded'])->sum('refund_amount'),
```
❌ Should use: `PaymentResultStatus::PARTIALLY_REFUNDED->value` (3 occurrences)

**app/Services/Payment/PaymentStateMachine.php:26, 30**
```php
'success' => ['refunded', 'partially_refunded'],
'partially_refunded' => ['refunded', 'partially_refunded'],
```
❌ Should use enum values

---

## 6. Missing Enum Definitions

### Homework Submission Status

**Current Usage:** String literals without enum definition
**Values Found:**
- 'not_started'
- 'draft'
- 'submitted'
- 'late'
- 'pending_review'
- 'under_review'
- 'graded'
- 'returned'
- 'resubmitted'

**Files Using These:**
- `app/Models/HomeworkSubmission.php`
- `app/Models/AcademicHomeworkSubmission.php`
- `app/Models/InteractiveCourseHomework.php`
- `app/Services/UnifiedHomeworkService.php`
- `app/Services/HomeworkService.php`
- Multiple Filament resources and widgets

⚠️ **RECOMMENDATION:** Create `app/Enums/HomeworkSubmissionStatus.php` enum

---

### Trial Request Status

**Current Usage:** Model constants in `app/Models/QuranTrialRequest.php`
```php
const STATUS_PENDING = 'pending';
const STATUS_APPROVED = 'approved';
const STATUS_SCHEDULED = 'scheduled';
const STATUS_COMPLETED = 'completed';
const STATUS_CANCELLED = 'cancelled';
const STATUS_REJECTED = 'rejected';
const STATUS_NO_SHOW = 'no_show';
```

⚠️ **RECOMMENDATION:** Create `app/Enums/TrialRequestStatus.php` enum

---

### Course Enrollment Status

**Current Usage:** String literals in `InteractiveCourseEnrollment` model
**Values Found:**
- 'pending'
- 'enrolled'
- 'completed'
- 'refunded'
- 'paid' (payment_status)

⚠️ **RECOMMENDATION:** Create `app/Enums/CourseEnrollmentStatus.php` enum

---

### Business Service Request Status

**Current Usage:** String literals in model
**Values Found:**
- 'pending'
- 'in_progress'
- 'completed'
- 'rejected'

⚠️ **RECOMMENDATION:** Create `app/Enums/BusinessServiceRequestStatus.php` enum

---

### Recording Status

**Current Usage:** String literals across recording models
**Values Found:**
- 'pending'
- 'in_progress'
- 'processing'
- 'completed'
- 'failed'

**Files:**
- `app/Models/SessionRecording.php`
- `app/Models/CourseRecording.php`
- `app/Traits/HasRecording.php`

⚠️ **RECOMMENDATION:** Create `app/Enums/RecordingStatus.php` enum

---

## 7. Critical Issues Summary

### 1. Invalid Enum Values Being Used

**'in_progress'** - Used extensively but NOT in SessionStatus enum:
- Found in 20+ files
- Used in session queries, scheduling validators, Filament resources
- **Action Required:** Either add to enum or refactor to use 'ongoing'

**'live'** - Used but NOT in SessionStatus enum:
- Found in 5+ files
- Used in meeting controllers and API endpoints
- **Action Required:** Either add to enum or refactor to use 'ongoing'

---

### 2. Wrong Enum Context

**app/Services/AcademicSessionSchedulingService.php:64**
```php
'attendance_status' => 'scheduled',
```
⚠️ **CRITICAL:** 'scheduled' is a SessionStatus, not an AttendanceStatus
- Should be: `AttendanceStatus::ABSENT->value` or appropriate status

---

### 3. Model Constants Instead of Enums

**app/Models/QuranTrialRequest.php**
```php
const STATUS_PENDING = 'pending';
const STATUS_APPROVED = 'approved';
// ... etc
```
❌ Should create TrialRequestStatus enum

**app/Models/SessionSchedule.php**
```php
const STATUS_PAUSED = 'paused';
const STATUS_COMPLETED = 'completed';
const STATUS_CANCELLED = 'cancelled';
```
❌ Should use SessionStatus enum

**app/Models/CourseSubscription.php**
```php
const ENROLLMENT_TYPE_PAID = 'paid';
```
❌ Should use enum

---

### 4. Shared Values Causing Confusion

**'pending'** appears in:
- SubscriptionStatus::PENDING
- SubscriptionPaymentStatus::PENDING
- PaymentResultStatus::PENDING
- Trial request status
- Homework status

**'completed'** appears in:
- SessionStatus::COMPLETED
- SubscriptionStatus::COMPLETED
- InteractiveCourseStatus::COMPLETED

**'cancelled'** appears in:
- SessionStatus::CANCELLED
- SubscriptionStatus::CANCELLED
- PaymentResultStatus::CANCELLED

**'absent'** appears in:
- SessionStatus::ABSENT
- AttendanceStatus::ABSENT

⚠️ **Requires careful context-aware refactoring**

---

## 8. Refactoring Priority

### Priority 1 - Critical (Security & Data Integrity)

1. **Fix wrong enum context:**
   - `app/Services/AcademicSessionSchedulingService.php:64` - Wrong attendance status

2. **Add missing enum values or remove usage:**
   - 'in_progress' (20+ files)
   - 'live' (5+ files)

### Priority 2 - High (Consistency & Maintainability)

1. **Session status in models:**
   - `app/Models/QuranSession.php`
   - `app/Models/AcademicSession.php`
   - `app/Models/InteractiveCourseSession.php`
   - `app/Models/BaseSession.php`

2. **Session status in services:**
   - `app/Services/SessionManagementService.php`
   - `app/Services/Scheduling/Validators/*.php` (all validators)
   - `app/Services/QuranCircleReportService.php`

3. **Attendance status:**
   - `app/Models/QuranSession.php`
   - `app/Services/Attendance/*.php` (all services)

### Priority 3 - Medium (Code Quality)

1. **Subscription status:**
   - All subscription models
   - `app/Services/SubscriptionService.php`
   - Search and enrollment services

2. **Payment status:**
   - `app/Models/Payment.php`
   - Payment controllers and services

3. **Filament resources:**
   - All session resources
   - All subscription resources

### Priority 4 - Low (Nice to Have)

1. **Create missing enums:**
   - HomeworkSubmissionStatus
   - TrialRequestStatus
   - CourseEnrollmentStatus
   - BusinessServiceRequestStatus
   - RecordingStatus

2. **Statistics and display arrays:**
   - Dashboard calculations
   - Report services

---

## 9. Automated Refactoring Script Suggestions

### Find and Replace Patterns (with caution)

```bash
# SessionStatus
->where('status', 'scheduled') → ->where('status', SessionStatus::SCHEDULED->value)
->where('status', 'completed') → ->where('status', SessionStatus::COMPLETED->value)
->where('status', 'cancelled') → ->where('status', SessionStatus::CANCELLED->value)
->where('status', 'ongoing') → ->where('status', SessionStatus::ONGOING->value)
->where('status', 'ready') → ->where('status', SessionStatus::READY->value)

# AttendanceStatus
->where('attendance_status', 'attended') → ->where('attendance_status', AttendanceStatus::ATTENDED->value)
->where('attendance_status', 'absent') → ->where('attendance_status', AttendanceStatus::ABSENT->value)
->where('attendance_status', 'late') → ->where('attendance_status', AttendanceStatus::LATE->value)
->where('attendance_status', 'leaved') → ->where('attendance_status', AttendanceStatus::LEAVED->value)

# SubscriptionStatus
->where('status', 'active') → ->where('status', SubscriptionStatus::ACTIVE->value)
->where('status', 'pending') → ->where('status', SubscriptionStatus::PENDING->value) # Context-dependent!
->where('status', 'expired') → ->where('status', SubscriptionStatus::EXPIRED->value)
```

**⚠️ Warning:** Automated replacement requires careful review due to:
- Shared values across multiple enums
- Different contexts (session vs subscription vs payment)
- Array keys vs actual status values
- Display labels vs database values

---

## 10. Recommended Action Plan

### Phase 1: Planning & Preparation (Week 1)
1. Review this audit report with team
2. Decide on handling of 'in_progress' and 'live' status values
3. Create missing enum definitions (HomeworkSubmissionStatus, etc.)
4. Prepare comprehensive test suite for affected features

### Phase 2: Critical Fixes (Week 2)
1. Fix wrong enum context issues (Priority 1)
2. Add/remove 'in_progress' and 'live' usage
3. Test session and attendance functionality thoroughly

### Phase 3: Model & Service Layer (Weeks 3-4)
1. Refactor models to use enums (Priority 2)
2. Update service classes
3. Update scheduling validators
4. Test all business logic

### Phase 4: Controllers & API (Week 5)
1. Update all controllers
2. Update API endpoints
3. Update API documentation
4. Test all endpoints

### Phase 5: Filament Resources (Week 6)
1. Update all Filament resources
2. Update widgets and custom pages
3. Test admin interfaces

### Phase 6: Final Cleanup (Week 7)
1. Create new enums (Priority 4)
2. Refactor statistics and display logic
3. Update comments and documentation
4. Final comprehensive testing

### Phase 7: Code Review & Deployment (Week 8)
1. Comprehensive code review
2. Performance testing
3. Staging deployment and testing
4. Production deployment

---

## 11. Testing Checklist

After refactoring, verify:

- [ ] All session workflows (create, schedule, start, complete, cancel)
- [ ] Attendance tracking and reporting
- [ ] Subscription management (create, activate, expire, cancel)
- [ ] Payment processing and status transitions
- [ ] Homework submission and grading
- [ ] Filament admin interfaces (all resources)
- [ ] API endpoints (all session/subscription/payment endpoints)
- [ ] Dashboard statistics and reports
- [ ] Calendar views and filtering
- [ ] Search functionality
- [ ] Background jobs and scheduled tasks

---

## Total Violations Count

- **SessionStatus:** ~350 violations
- **AttendanceStatus:** ~100 violations
- **SubscriptionStatus:** ~200 violations
- **SubscriptionPaymentStatus:** ~80 violations
- **PaymentResultStatus:** ~50 violations

**Grand Total:** ~780+ string literal violations

---

## Conclusion

This audit reveals extensive use of string literals instead of enum constants throughout the application. While the enum definitions exist and are well-structured, their adoption in the codebase is inconsistent.

**Key Recommendations:**

1. **Immediate:** Fix critical issues (wrong enum context, invalid values)
2. **Short-term:** Refactor core models and services to use enums consistently
3. **Medium-term:** Update all controllers, APIs, and Filament resources
4. **Long-term:** Create missing enum definitions and complete refactoring

**Benefits of completing this refactoring:**

- Type safety and IDE autocomplete
- Centralized status definition and validation
- Easier refactoring and maintenance
- Reduced bugs from typos or invalid statuses
- Better code documentation
- Consistent behavior across the application

**Estimated Effort:** 6-8 weeks with dedicated developer time, including testing

---

*Report generated on 2025-12-27*
*Total files audited: 200+*
*Total violations found: 780+*
