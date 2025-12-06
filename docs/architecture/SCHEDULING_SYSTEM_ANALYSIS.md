# 📅 SCHEDULING SYSTEM COMPREHENSIVE ANALYSIS & REFACTOR PLAN

## **Executive Summary**

This document outlines a comprehensive refactor plan for the calendar scheduling system to make it subscription-aware, prevent human errors, and accurately reflect scheduling status for different entity types.

---

## **ENTITY TYPES & THEIR CHARACTERISTICS**

### **1. Trial Sessions** ⭐ (SIMPLEST)
**Model:** `QuranTrialRequest`
**Sessions:** Exactly 1 session
**Duration:** Fixed (usually 30 minutes)
**Status Logic:**
- `not_scheduled`: No session created yet
- `scheduled`: Session created with date/time
- `completed`: Trial finished
- `cancelled`: Trial cancelled

**Validation Rules:**
- ✅ Can only create 1 session
- ✅ Must be scheduled at least 1 hour in future
- ✅ Cannot conflict with teacher's other sessions
- ✅ Trial request must be in 'pending' or 'approved' status

**Current Implementation:** ✅ Working correctly
**Improvements Needed:** ❌ None

---

### **2. Interactive Courses** ⭐⭐ (MODERATE)
**Model:** `InteractiveCourse`
**Sessions:** Fixed number (e.g., 8, 12, 16 sessions)
**Duration:** Fixed per course
**Status Logic:**
- `not_scheduled`: 0 sessions scheduled
- `partially_scheduled`: 1 to (total-1) sessions scheduled
- `fully_scheduled`: All sessions scheduled
- `in_progress`: Course has started (at least 1 session completed)
- `completed`: All sessions completed

**Validation Rules:**
- ✅ Cannot schedule more than `total_sessions` count
- ✅ Each session must be numbered (1, 2, 3, ...)
- ✅ Sessions should follow course curriculum sequence
- ✅ Must respect course start_date and end_date if set
- ✅ Cannot conflict with teacher's schedule
- ✅ Weekly sessions should not exceed recommended frequency (e.g., 2-3 per week for 12-week course)

**Current Implementation:** ⚠️ Partial
**Improvements Needed:**
- Add `total_sessions` validation
- Add curriculum sequence enforcement
- Add date range validation
- Add recommended frequency calculation

**Recommended Frequency Formula:**
```php
$courseWeeks = $course->duration_weeks ?? 12;
$totalSessions = $course->total_sessions ?? 16;
$recommendedPerWeek = ceil($totalSessions / $courseWeeks);
$maxPerWeek = $recommendedPerWeek + 1; // Allow 1 extra per week for flexibility
```

---

### **3. Group Quran Circles (Continuous)** ⭐⭐⭐⭐ (COMPLEX)
**Model:** `QuranCircle`
**Sessions:** Continuous (no fixed end)
**Duration:** Monthly sessions count (e.g., 8, 12, 16 per month)
**Status Logic:**
- `not_scheduled`: No future sessions scheduled
- `partially_scheduled`: Has future sessions but < 1 month ahead
- `actively_scheduled`: Has sessions scheduled for next 4+ weeks
- `active`: Circle is running (has ongoing or upcoming sessions)
- `paused`: Temporarily stopped
- `closed`: Permanently ended

**Validation Rules:**
- ✅ Monthly session count should match circle settings (e.g., 12 sessions/month)
- ✅ Weekly days count = monthly_sessions / 4 (approximate)
- ⚠️ Should allow teacher to schedule MORE sessions if needed (flexibility)
- ✅ Must prevent scheduling more than `max_weekly_sessions` per week
- ✅ Sessions should align with circle's weekly schedule pattern
- ✅ Cannot conflict with teacher's schedule

**Current Implementation:** ⚠️ Needs improvement
**Improvements Needed:**
- Change status from binary (scheduled/not_scheduled) to staged:
  - Check if has sessions in next 30 days
  - Check if weekly pattern matches monthly target
- Add `max_weekly_sessions` validation (not hard limit, but warning)
- Allow "extend scheduling" to add more sessions beyond current range

**Monthly Session Validation Logic:**
```php
// FLEXIBLE approach (recommended)
$monthlyTarget = $circle->monthly_sessions_count; // e.g., 12
$recommendedWeeklyDays = ceil($monthlyTarget / 4); // e.g., 3 days
$maxWeeklyDays = $recommendedWeeklyDays + 2; // Allow flexibility: 5 days max

// Don't hard-enforce, but warn if exceeded
if ($selectedDaysCount > $maxWeeklyDays) {
    // Show warning, don't block
    $warning = "⚠️ You selected {$selectedDaysCount} days which may exceed the monthly target of {$monthlyTarget} sessions.";
}
```

**Status Determination:**
```php
function getGroupCircleSchedulingStatus(QuranCircle $circle): string
{
    $now = now();
    $oneMonthAhead = $now->copy()->addMonth();

    $futureSessionsCount = $circle->sessions()
        ->where('scheduled_at', '>', $now)
        ->where('scheduled_at', '<=', $oneMonthAhead)
        ->count();

    $monthlyTarget = $circle->monthly_sessions_count ?? 12;

    if ($futureSessionsCount === 0) {
        return 'not_scheduled'; // No sessions ahead
    } elseif ($futureSessionsCount < $monthlyTarget * 0.5) {
        return 'needs_scheduling'; // Less than 50% of monthly target
    } else {
        return 'actively_scheduled'; // Good for next month
    }
}
```

---

### **4. Individual Quran Circles (Subscription-Based)** ⭐⭐⭐⭐⭐ (MOST COMPLEX)
**Model:** `QuranIndividualCircle` + `QuranSubscription`
**Sessions:** Based on subscription package
**Duration:** Subscription period (monthly/quarterly/yearly)
**Renewability:** Can be renewed when expires
**Status Logic:**
- `not_scheduled`: Has remaining sessions but none scheduled
- `partially_scheduled`: Some sessions scheduled, some remaining
- `fully_scheduled`: All subscription sessions scheduled
- `active`: Subscription active and running
- `expired`: Subscription ended
- `renewed`: New subscription started

**Validation Rules:**
- ✅ Cannot schedule more sessions than `subscription.remaining_sessions`
- ✅ Cannot schedule beyond `subscription.expires_at` date
- ✅ Must respect subscription `starts_at` (can't schedule before)
- ✅ If subscription renewed, old sessions should be marked differently
- ✅ Monthly session count should align with package (e.g., 8/month for monthly package)
- ✅ Cannot conflict with teacher's schedule
- ⚠️ Should warn if scheduling too many sessions too quickly (burnout risk)

**Current Implementation:** ⚠️ Partial
**Improvements Needed:**
- Add subscription expiry validation
- Add subscription start date validation
- Add renewal handling
- Add pacing validation (prevent scheduling all sessions in 1 week)
- Add "smart scheduling" suggestions based on subscription period

**Subscription Session Calculation:**
```php
function getSubscriptionSchedulingLimits(QuranIndividualCircle $circle): array
{
    $subscription = $circle->subscription;

    // Calculate total sessions from subscription
    $totalSessions = $subscription->total_sessions;
    $usedSessions = $circle->sessions()
        ->whereIn('status', ['completed', 'scheduled', 'in_progress'])
        ->count();
    $remainingSessions = $totalSessions - $usedSessions;

    // Calculate subscription period
    $startDate = $subscription->starts_at;
    $endDate = $subscription->expires_at;
    $daysInPeriod = $startDate->diffInDays($endDate);
    $weeksInPeriod = ceil($daysInPeriod / 7);

    // Calculate recommended pacing
    $recommendedPerWeek = $remainingSessions / max($weeksInPeriod, 1);
    $maxPerWeek = ceil($recommendedPerWeek * 1.5); // Allow 50% more for flexibility

    // Calculate valid date range
    $validStartDate = max($startDate, now());
    $validEndDate = $endDate;

    return [
        'remaining_sessions' => $remainingSessions,
        'recommended_per_week' => round($recommendedPerWeek, 1),
        'max_per_week' => $maxPerWeek,
        'valid_start_date' => $validStartDate,
        'valid_end_date' => $validEndDate,
        'weeks_remaining' => $weeksInPeriod,
    ];
}
```

**Status Determination:**
```php
function getIndividualCircleSchedulingStatus(QuranIndividualCircle $circle): array
{
    $subscription = $circle->subscription;

    // Check subscription status first
    if (!$subscription || $subscription->subscription_status !== 'active') {
        return ['status' => 'inactive', 'reason' => 'Subscription not active'];
    }

    if ($subscription->expires_at->isPast()) {
        return ['status' => 'expired', 'reason' => 'Subscription expired'];
    }

    // Calculate sessions
    $totalSessions = $subscription->total_sessions;
    $scheduledSessions = $circle->sessions()
        ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
        ->count();
    $remainingSessions = $totalSessions - $scheduledSessions;

    if ($remainingSessions <= 0) {
        return ['status' => 'fully_scheduled', 'remaining' => 0];
    }

    $futureScheduled = $circle->sessions()
        ->where('status', 'scheduled')
        ->where('scheduled_at', '>', now())
        ->count();

    if ($futureScheduled === 0) {
        return ['status' => 'not_scheduled', 'remaining' => $remainingSessions];
    } elseif ($futureScheduled < $remainingSessions) {
        return ['status' => 'partially_scheduled', 'remaining' => $remainingSessions, 'scheduled' => $futureScheduled];
    } else {
        return ['status' => 'fully_scheduled', 'remaining' => 0];
    }
}
```

---

### **5. Academic Individual Lessons (Subscription-Based)** ⭐⭐⭐⭐⭐ (MOST COMPLEX)
**Model:** `AcademicSubscription`
**Sessions:** Pre-created as 'unscheduled', then updated to 'scheduled'
**Duration:** Subscription period (monthly/quarterly/yearly)
**Renewability:** Can be renewed
**Status Logic:** Similar to Individual Quran Circles

**Current Architecture Issue:**
- Uses pre-created unscheduled sessions (different from Quran which creates on-demand)
- Should be unified to use same approach as Quran for consistency

**Validation Rules:** Same as Individual Quran Circles +
- ✅ Academic sessions may have homework/curriculum requirements
- ✅ May need to align with academic calendar (school terms)

**Recommended Change:**
- Migrate to dynamic session creation (like Quran)
- Or keep current approach but add same validation as Quran

---

## **UNIFIED SCHEDULING VALIDATION FRAMEWORK**

### **Validation Layers**

#### **Layer 1: Subscription Validation (Pre-scheduling)**
```php
interface SubscriptionValidator
{
    public function isActive(): bool;
    public function getRemainingSessions(): int;
    public function getValidDateRange(): array; // [start, end]
    public function getRecommendedPacing(): array; // [per_week, max_per_week]
}
```

#### **Layer 2: Schedule Validation (During scheduling)**
```php
interface ScheduleValidator
{
    public function validateDaySelection(array $days): ValidationResult;
    public function validateSessionCount(int $count): ValidationResult;
    public function validateDateRange(Carbon $start, Carbon $end): ValidationResult;
    public function validateWeeklyPacing(array $days, int $weeksAhead): ValidationResult;
}
```

#### **Layer 3: Conflict Validation (Final check)**
```php
interface ConflictValidator
{
    public function checkTeacherConflicts(int $teacherId, array $sessions): bool;
    public function checkRoomConflicts(array $sessions): bool; // Future enhancement
    public function checkStudentConflicts(int $studentId, array $sessions): bool;
}
```

---

## **PROPOSED SOLUTIONS**

### **Solution 1: Entity-Specific Validators**

Create validator classes for each entity type:

```php
namespace App\Services\Scheduling\Validators;

class TrialSessionValidator implements ScheduleValidator
{
    public function validate(TrialRequest $trial, array $scheduleData): ValidationResult
    {
        // Simple: just 1 session, future date, no conflicts
    }
}

class InteractiveCourseValidator implements ScheduleValidator
{
    public function validate(InteractiveCourse $course, array $scheduleData): ValidationResult
    {
        // Fixed session count, curriculum sequence, date range
    }
}

class GroupCircleValidator implements ScheduleValidator
{
    public function validate(QuranCircle $circle, array $scheduleData): ValidationResult
    {
        // Monthly target, weekly flexibility, continuous scheduling
    }
}

class IndividualCircleValidator implements ScheduleValidator
{
    public function validate(QuranIndividualCircle $circle, array $scheduleData): ValidationResult
    {
        // Subscription limits, expiry, pacing, remaining sessions
    }
}

class AcademicLessonValidator implements ScheduleValidator
{
    public function validate(AcademicSubscription $subscription, array $scheduleData): ValidationResult
    {
        // Same as IndividualCircleValidator
    }
}
```

### **Solution 2: Smart Scheduling Assistant**

Add helper methods to calculate optimal scheduling:

```php
class SchedulingAssistant
{
    public function suggestOptimalSchedule($entity): array
    {
        // Based on entity type, suggest best days/times
        // Consider:
        // - Subscription remaining time
        // - Session count
        // - Teacher availability patterns
        // - Student preferences

        return [
            'recommended_days' => ['sunday', 'tuesday', 'thursday'],
            'recommended_time' => '16:00',
            'recommended_count' => 12,
            'reason' => 'Based on 3 sessions/week for 4 weeks to complete subscription',
        ];
    }
}
```

### **Solution 3: Enhanced Form Validation**

Update the scheduling form to be context-aware:

```php
// In Calendar.php
Forms\Components\CheckboxList::make('schedule_days')
    ->label('أيام الأسبوع')
    ->required()
    ->options([...])
    ->rules([
        function () {
            return function (string $attribute, $value, \Closure $fail) {
                $entity = $this->getSelectedEntity();
                $validator = $this->getValidatorForEntity($entity);

                $result = $validator->validateDaySelection($value);

                if (!$result->isValid()) {
                    $fail($result->getMessage());
                }
            };
        },
    ])
    ->helperText(function () {
        $entity = $this->getSelectedEntity();
        $assistant = new SchedulingAssistant();
        $suggestion = $assistant->suggestOptimalSchedule($entity);

        return $suggestion['reason'];
    })
```

---

## **IMPLEMENTATION ROADMAP**

### **Phase 1: Foundation (Week 1)**
1. ✅ Fix model casts and fillable (DONE)
2. ✅ Extract common utilities (Day mapping, statistics)
3. Create `ScheduleValidator` interface
4. Create `ValidationResult` value object
5. Create base validator classes

### **Phase 2: Entity Validators (Week 2)**
1. Implement `TrialSessionValidator`
2. Implement `InteractiveCourseValidator`
3. Implement `GroupCircleValidator`
4. Implement `IndividualCircleValidator`
5. Implement `AcademicLessonValidator`

### **Phase 3: Integration (Week 3)**
1. Update Calendar.php to use validators
2. Update AcademicCalendar.php to use validators
3. Add `SchedulingAssistant` for smart suggestions
4. Update form validation with context-aware rules
5. Add visual feedback (warnings, recommendations)

### **Phase 4: Status Management (Week 4)**
1. Implement accurate status determination for each entity
2. Add status badges/indicators in UI
3. Add "needs scheduling" alerts for entities
4. Add scheduling progress indicators

### **Phase 5: Testing & Polish (Week 5)**
1. Comprehensive unit tests for validators
2. Integration tests for scheduling flows
3. UI/UX improvements
4. Documentation
5. Training materials for users

---

## **QUICK WINS (Can implement today)**

### **1. Add Subscription Expiry Validation**
```php
// In createIndividualCircleSchedule()
if ($circle->subscription->expires_at && $sessionDateTime->isAfter($circle->subscription->expires_at)) {
    throw new \Exception("لا يمكن جدولة جلسات بعد انتهاء الاشتراك في {$circle->subscription->expires_at->format('Y/m/d')}");
}
```

### **2. Add Weekly Pacing Warning**
```php
// In schedule form validation
$limits = $this->getSubscriptionSchedulingLimits($circle);
if ($selectedDaysCount > $limits['max_per_week']) {
    $warning = "⚠️ تحذير: اخترت {$selectedDaysCount} أيام أسبوعياً، الموصى به {$limits['recommended_per_week']} أيام لتوزيع الجلسات بشكل جيد";
}
```

### **3. Improve Status Determination**
```php
// Update circle status based on future sessions, not just existence
$status = $this->getGroupCircleSchedulingStatus($circle);
// Use in UI to show accurate status
```

---

## **FILES TO BE CREATED**

1. `app/Services/Scheduling/Validators/ScheduleValidatorInterface.php`
2. `app/Services/Scheduling/Validators/TrialSessionValidator.php`
3. `app/Services/Scheduling/Validators/InteractiveCourseValidator.php`
4. `app/Services/Scheduling/Validators/GroupCircleValidator.php`
5. `app/Services/Scheduling/Validators/IndividualCircleValidator.php`
6. `app/Services/Scheduling/Validators/AcademicLessonValidator.php`
7. `app/Services/Scheduling/SchedulingAssistant.php`
8. `app/Services/Scheduling/ValidationResult.php`
9. `app/Services/Scheduling/SchedulingStatusDeterminer.php`

---

## **TESTING STRATEGY**

### **Unit Tests**
- Each validator with edge cases
- Status determination logic
- Scheduling assistant suggestions

### **Integration Tests**
- Full scheduling flow for each entity type
- Conflict detection
- Subscription validation

### **E2E Tests**
- User schedules trial session
- User schedules interactive course
- User schedules group circle (continuous)
- User schedules individual circle (subscription)
- User tries to over-schedule (should be prevented)
- User tries to schedule beyond subscription (should be prevented)

---

**End of Analysis**
