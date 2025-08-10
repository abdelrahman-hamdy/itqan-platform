# Individual Circles Scheduling Test Scenarios

## Test Cases for Individual Circle Scheduling Logic

### Scenario 1: Unscheduled Circle
**Description**: Circle with 12 total sessions, 0 scheduled sessions
**Expected Status**: `not_scheduled`
**Expected Badge Color**: `warning`
**Expected Badge Text**: `غير مجدولة`
**Expected Sessions Remaining**: 12
**Expected Button**: `جدولة جلسات` (enabled)

### Scenario 2: Partially Scheduled Circle
**Description**: Circle with 12 total sessions, 2 scheduled sessions
**Expected Status**: `partially_scheduled` 
**Expected Badge Color**: `info`
**Expected Badge Text**: `مجدولة جزئياً`
**Expected Sessions Remaining**: 10
**Expected Button**: `جدولة الجلسات المتبقية` (enabled)

### Scenario 3: Fully Scheduled Circle
**Description**: Circle with 12 total sessions, 12 scheduled sessions
**Expected Status**: `fully_scheduled`
**Expected Badge Color**: `success` 
**Expected Badge Text**: `مكتملة الجدولة`
**Expected Sessions Remaining**: 0
**Expected Button**: `الجلسات مكتملة` (disabled)

## Key Fixes Implemented

### 1. **Fixed Session Counting Logic**
```php
// Before: Binary logic (scheduled/not_scheduled)
$isScheduled = $scheduledSessions > 0;

// After: Three-state logic with accurate remaining count
$remainingSessions = max(0, $totalSessions - $scheduledSessions);
$status = 'not_scheduled';
if ($scheduledSessions > 0) {
    if ($remainingSessions > 0) {
        $status = 'partially_scheduled';
    } else {
        $status = 'fully_scheduled';
    }
}
```

### 2. **Enhanced UI Status Display**
- **Not Scheduled**: Orange warning badge with "غير مجدولة"
- **Partially Scheduled**: Blue info badge with "مجدولة جزئياً" 
- **Fully Scheduled**: Green success badge with "مكتملة الجدولة"

### 3. **Smart Button Logic**
- **Unscheduled**: "جدولة جلسات" (enabled)
- **Partially Scheduled**: "جدولة الجلسات المتبقية" (enabled)
- **Fully Scheduled**: "الجلسات مكتملة" (disabled)

### 4. **Enhanced Modal Information**
Shows detailed breakdown:
- **Scheduled Sessions**: Green badge with count
- **Remaining Sessions**: Orange badge with count (if any)
- **Completion Status**: Gray badge when all scheduled

### 5. **Robust Scheduling Logic**
- Prevents double-booking same date/time
- Calculates accurate remaining sessions
- Handles conflicts gracefully
- Supports re-scheduling partially scheduled circles

## Database Verification Queries

### Check Circle Session Counts
```sql
SELECT 
    ic.id,
    ic.name,
    ic.total_sessions,
    COUNT(qs.id) as scheduled_sessions,
    (ic.total_sessions - COUNT(qs.id)) as remaining_sessions
FROM quran_individual_circles ic
LEFT JOIN quran_sessions qs ON qs.individual_circle_id = ic.id 
    AND qs.is_scheduled = 1
WHERE ic.quran_teacher_id = YOUR_TEACHER_ID
GROUP BY ic.id, ic.name, ic.total_sessions;
```

### Check Session Details
```sql
SELECT 
    scheduled_at,
    status,
    is_scheduled,
    title
FROM quran_sessions 
WHERE individual_circle_id = YOUR_CIRCLE_ID
ORDER BY scheduled_at;
```

## Testing Steps

1. **Create Test Data**: Set up circles with different session states
2. **Verify Display**: Check that badges and counters show correct information  
3. **Test Scheduling**: Verify that scheduling works for each scenario
4. **Test Restrictions**: Confirm fully scheduled circles can't be over-scheduled
5. **Test Re-scheduling**: Verify partially scheduled circles can add more sessions

## Common Issues Fixed

❌ **Before**: "12 total, 2 scheduled, 12 remaining" (incorrect)
✅ **After**: "12 total, 2 scheduled, 10 remaining" (correct)

❌ **Before**: All circles with any sessions marked "مجدولة" 
✅ **After**: Proper three-state status with accurate labels

❌ **Before**: Couldn't re-schedule partially scheduled circles
✅ **After**: Can schedule remaining sessions for partial circles

❌ **Before**: No visual difference between partial and complete scheduling
✅ **After**: Clear visual indicators for each state
