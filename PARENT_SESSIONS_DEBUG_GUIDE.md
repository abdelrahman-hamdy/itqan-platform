# Parent Upcoming Sessions - Debug Guide

## Issue
The "Upcoming Sessions" section on the parent profile page shows empty despite scheduled sessions existing for today, tomorrow, and coming days.

## Debugging Added

### 1. Controller-Level Logging
**File**: `app/Http/Controllers/ParentProfileController.php`

**Line 47-52**: Added logging when children IDs are collected
```php
\Log::info('[Parent Profile] Children IDs collected', [
    'children_count' => $children->count(),
    'selected_child_id' => $selectedChild?->id,
    'children_profile_ids' => $childrenProfileIds,
    'children_user_ids' => $childrenUserIds,
]);
```

This will show:
- How many children the parent has
- If a specific child is selected
- The profile IDs being used
- **Most important**: The user IDs being used for session queries

### 2. Session Query Logging
**File**: `app/Http/Controllers/ParentProfileController.php`

**Lines 498-502**: Initial query parameters
```php
\Log::info('[Parent Upcoming Sessions] Searching for sessions', [
    'children_ids' => $childrenIds,
    'limit' => $limit,
    'today' => today()->toDateString(),
]);
```

**Lines 517-525**: Quran sessions found
```php
\Log::info('[Parent Upcoming Sessions] Quran sessions found', [
    'count' => $quranSessions->count(),
    'sessions' => $quranSessions->map(fn($s) => [
        'id' => $s->id,
        'student_id' => $s->student_id,
        'scheduled_at' => $s->scheduled_at?->toDateTimeString(),
        'status' => $s->status,
    ]),
]);
```

**Lines 549-557**: Academic sessions found
```php
\Log::info('[Parent Upcoming Sessions] Academic sessions found', [
    'count' => $academicSessions->count(),
    'sessions' => $academicSessions->map(fn($s) => [
        'id' => $s->id,
        'student_id' => $s->student_id,
        'scheduled_at' => $s->scheduled_at?->toDateTimeString(),
        'status' => $s->status,
    ]),
]);
```

**Lines 576-584**: Final result
```php
\Log::info('[Parent Upcoming Sessions] Final result', [
    'total_found' => count($sessions),
    'returned' => count($finalSessions),
    'sessions' => array_map(fn($s) => [
        'type' => $s['type'],
        'scheduled_at' => $s['scheduled_at']->toDateTimeString(),
        'status' => $s['status'],
    ], $finalSessions),
]);
```

## How to Debug

### Quick Method (Recommended)
Run the test script:
```bash
./test-parent-sessions.sh
```

Then open your browser and navigate to the parent profile page. The script will show real-time logs.

### Manual Method
1. Clear cache:
```bash
php artisan cache:clear
```

2. Watch logs:
```bash
php artisan pail
```

3. In another terminal, navigate to the parent profile page

4. Look for log entries with `[Parent Profile]` and `[Parent Upcoming Sessions]` prefixes

## What to Look For

### 1. Children IDs Check
```
[Parent Profile] Children IDs collected
```
**Expected**: Non-empty array of user IDs
**Problem if**: Empty array or null values

### 2. Query Parameters Check
```
[Parent Upcoming Sessions] Searching for sessions
```
**Expected**:
- `children_ids`: Array of user IDs (e.g., [1, 2, 3])
- `today`: Current date in Y-m-d format

**Problem if**:
- `children_ids` is empty
- `today` is in the past

### 3. Quran Sessions Check
```
[Parent Upcoming Sessions] Quran sessions found
```
**Expected**: `count` > 0 if sessions exist
**Problem if**:
- `count` = 0 but you know sessions exist
- `student_id` values don't match `children_ids` from step 1
- `scheduled_at` is null or in the past
- `status` is 'completed' or 'cancelled'

### 4. Academic Sessions Check
```
[Parent Upcoming Sessions] Academic sessions found
```
Same checks as Quran sessions.

### 5. Final Result Check
```
[Parent Upcoming Sessions] Final result
```
**Expected**:
- `total_found` > 0 if sessions exist
- `returned` = min(total_found, 5)

**Problem if**:
- `total_found` = 0 but previous steps showed sessions
- `returned` = 0 despite `total_found` > 0

## Common Issues and Solutions

### Issue 1: Children IDs Empty
**Symptom**: `children_user_ids` is an empty array
**Cause**: Parent has no children linked
**Solution**:
1. Go to parent children management page
2. Add children using student codes
3. Refresh parent profile

### Issue 2: Wrong student_id Values
**Symptom**: Sessions found but `student_id` doesn't match `children_ids`
**Cause**: Database has sessions with different student IDs
**Solution**: Verify in database:
```sql
-- Check children's user IDs
SELECT sp.id, sp.user_id, u.name
FROM student_profiles sp
JOIN users u ON sp.user_id = u.id
WHERE sp.id IN (SELECT student_id FROM parent_student_relationships WHERE parent_id = YOUR_PARENT_ID);

-- Check Quran sessions
SELECT id, student_id, scheduled_at, status
FROM quran_sessions
WHERE student_id IN (USER_IDS_FROM_ABOVE)
  AND scheduled_at >= CURDATE()
  AND status NOT IN ('completed', 'cancelled');

-- Check Academic sessions
SELECT id, student_id, scheduled_at, status
FROM academic_sessions
WHERE student_id IN (USER_IDS_FROM_ABOVE)
  AND scheduled_at >= CURDATE()
  AND status NOT IN ('completed', 'cancelled');
```

### Issue 3: All Sessions Completed/Cancelled
**Symptom**: Sessions found but all have status 'completed' or 'cancelled'
**Cause**: No active upcoming sessions
**Solution**: Create new scheduled sessions or check session status

### Issue 4: scheduled_at is NULL
**Symptom**: Sessions exist but `scheduled_at` is null
**Cause**: Sessions created without scheduling
**Solution**: Update sessions to have valid `scheduled_at` dates

### Issue 5: scheduled_at in the Past
**Symptom**: Sessions exist but `scheduled_at < today()`
**Cause**: Old sessions not marked as completed
**Solution**:
1. Run session status update command:
```bash
php artisan session:update-statuses
```
2. Or manually update in database

## Database Verification Queries

### Check Parent's Children
```sql
SELECT
    sp.id as profile_id,
    sp.user_id,
    u.name,
    u.email
FROM parent_student_relationships psr
JOIN student_profiles sp ON psr.student_id = sp.id
JOIN users u ON sp.user_id = u.id
WHERE psr.parent_id = YOUR_PARENT_PROFILE_ID;
```

### Check Upcoming Quran Sessions
```sql
SELECT
    qs.id,
    qs.student_id,
    u.name as student_name,
    qs.scheduled_at,
    qs.status,
    qt.name as teacher_name
FROM quran_sessions qs
LEFT JOIN users u ON qs.student_id = u.id
LEFT JOIN users qt ON qs.quran_teacher_id = qt.id
WHERE qs.student_id IN (USER_IDS_FROM_ABOVE)
  AND qs.scheduled_at >= CURDATE()
  AND qs.status NOT IN ('completed', 'cancelled')
ORDER BY qs.scheduled_at
LIMIT 10;
```

### Check Upcoming Academic Sessions
```sql
SELECT
    as_tbl.id,
    as_tbl.student_id,
    u.name as student_name,
    as_tbl.scheduled_at,
    as_tbl.status,
    at_user.name as teacher_name
FROM academic_sessions as_tbl
LEFT JOIN users u ON as_tbl.student_id = u.id
LEFT JOIN academic_teacher_profiles atp ON as_tbl.academic_teacher_id = atp.id
LEFT JOIN users at_user ON atp.user_id = at_user.id
WHERE as_tbl.student_id IN (USER_IDS_FROM_ABOVE)
  AND as_tbl.scheduled_at >= CURDATE()
  AND as_tbl.status NOT IN ('completed', 'cancelled')
ORDER BY as_tbl.scheduled_at
LIMIT 10;
```

## Next Steps

After running the debug script and viewing the logs:

1. **If no logs appear**: Check if you're logged in as a parent user
2. **If children_ids is empty**: Add children to parent account
3. **If sessions not found**: Check database with queries above
4. **If sessions found but not displayed**: Check the view file `resources/views/parent/profile.blade.php` lines 59-121

## Cleanup

Once debugging is complete, you may want to remove the logging statements to reduce log noise. However, it's recommended to keep them with a lower log level (e.g., `\Log::debug()` instead of `\Log::info()`).
