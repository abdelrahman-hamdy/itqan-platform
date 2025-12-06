# Quran Individual Circle Scheduling Fix

## 🐛 BUG FIXED

**Issue**: Error when scheduling individual circle sessions: "حدث خطأ أثناء إنشاء الجدول: لا توجد جلسات متبقية في الاشتراك" (No remaining sessions in subscription)

**Impact**: Teachers couldn't schedule sessions for individual circles even when 36 unscheduled sessions were available.

---

## 🔍 ROOT CAUSE ANALYSIS

### Problem Discovery
Using tinker investigation revealed:
```
Circle ID: 1
  total_sessions: 36
  subscription_id: 2
  Subscription: SOFT DELETED (deleted at: 2025-12-01 19:03:34)
  scheduled_count: 0
  remaining: 36
```

**Key Finding**: Some individual circles had soft-deleted subscriptions but still had valid `total_sessions` values.

### Why It Failed
1. User enrolled in quarterly subscription → 36 sessions created ✅
2. Subscription was later soft-deleted (for testing/cleanup)
3. Circle kept `subscription_id = 2` but `QuranSubscription::find(2)` returned `null`
4. **Validator bug**: When subscription is null, returned `remaining_sessions: 0` ❌
5. Scheduling dialog showed error: "No remaining sessions"

### Code Location
**File**: [app/Services/Scheduling/Validators/IndividualCircleValidator.php](app/Services/Scheduling/Validators/IndividualCircleValidator.php#L236-L244)

**Before (Lines 236-244)**:
```php
if (!$subscription) {
    return [
        'remaining_sessions' => 0,  // ❌ BUG: Always returns 0
        'recommended_per_week' => 0,
        'max_per_week' => 0,
        'valid_start_date' => Carbon::now($timezone),
        'valid_end_date' => null,
        'weeks_remaining' => 0,
    ];
}
```

**Why This Was Wrong**:
- The `QuranIndividualCircle` model has its own `total_sessions` field (36)
- This field is **independent** of the subscription relationship
- Even if subscription is deleted, the circle's sessions are still valid
- Returning 0 prevented scheduling of legitimate sessions

---

## ✅ THE FIX

**File Modified**: [app/Services/Scheduling/Validators/IndividualCircleValidator.php](app/Services/Scheduling/Validators/IndividualCircleValidator.php#L236-L258)

**After (Lines 236-258)**:
```php
// CRITICAL FIX: If subscription is null (deleted/not found), use circle's total_sessions directly
// This happens when subscription is soft-deleted but circle still has valid total_sessions
if (!$subscription) {
    // Calculate remaining based on circle's total_sessions field
    $totalSessions = $this->circle->total_sessions ?? 0;
    $usedSessions = $this->circle->sessions()
        ->whereIn('status', ['completed', 'scheduled', 'in_progress'])
        ->count();
    $remainingSessions = max(0, $totalSessions - $usedSessions);

    // Default scheduling window of 12 weeks (~3 months)
    $weeksRemaining = $remainingSessions > 0 ? 12 : 0;
    $recommendedPerWeek = $weeksRemaining > 0 ? $remainingSessions / $weeksRemaining : 0;

    return [
        'remaining_sessions' => $remainingSessions,  // ✅ Correctly calculates from circle.total_sessions
        'recommended_per_week' => round($recommendedPerWeek, 1),
        'max_per_week' => $remainingSessions > 0 ? ceil($recommendedPerWeek * 1.5) : 0,
        'valid_start_date' => Carbon::now($timezone),
        'valid_end_date' => null, // No end date when subscription missing
        'weeks_remaining' => $weeksRemaining,
    ];
}
```

**What Changed**:
1. ✅ Uses circle's `total_sessions` field directly
2. ✅ Calculates remaining by subtracting scheduled/completed sessions
3. ✅ Provides sensible defaults for scheduling recommendations
4. ✅ Returns 36 remaining sessions (correct) instead of 0 (wrong)

---

## 🧪 VERIFICATION TESTING

### Test 1: Circle with Deleted Subscription
```bash
php artisan tinker
```
```php
$circle1 = \App\Models\QuranIndividualCircle::find(1);
$validator = new \App\Services\Scheduling\Validators\IndividualCircleValidator($circle1);

// Test day selection
$result = $validator->validateDaySelection(['monday', 'wednesday']);
// Result: ✅ VALID - "عدد الأيام مناسب (2 أيام أسبوعياً)"

// Test session count (within limit)
$countResult = $validator->validateSessionCount(10);
// Result: ✅ VALID - "عدد الجلسات مناسب (10 من أصل 36 متبقية)"

// Test session count (exceeds limit)
$countResult = $validator->validateSessionCount(40);
// Result: ❌ ERROR - "لا يمكن جدولة 40 جلسة. الجلسات المتبقية: 36 فقط"
```

### Test 2: Recommendations
```php
$recs = $validator->getRecommendations();
echo "Remaining sessions: {$recs['remaining_sessions']}";  // 36
echo "Recommended days: {$recs['recommended_days']}";      // 3
echo "Max days: {$recs['max_days']}";                      // 5
echo "Weeks remaining: {$recs['weeks_remaining']}";        // 12
```

**Results**: ✅ All tests pass

---

## 📊 IMPACT

### Before Fix ❌
- Circles with deleted subscriptions: **0 remaining sessions**
- Teachers: **Cannot schedule any sessions**
- Error message: "No remaining sessions"
- **36 valid sessions unusable**

### After Fix ✅
- Circles with deleted subscriptions: **36 remaining sessions** (correct!)
- Teachers: **Can schedule all 36 sessions**
- Proper validation: Rejects scheduling >36, accepts ≤36
- **All sessions now accessible**

---

## 🎯 BUSINESS LOGIC

### Why This Approach Is Correct

The `QuranIndividualCircle` model is **self-contained**:
- Has its own `total_sessions` field
- This field represents the **actual session allocation**
- Independent of subscription lifecycle

**Scenario**: Testing/cleanup workflow:
1. Student enrolls quarterly → Subscription created with 36 sessions
2. Circle created with `total_sessions = 36`
3. Testing: Subscription soft-deleted
4. **Circle still has 36 valid, unscheduled sessions**
5. Teacher should still be able to schedule those sessions

**Alternative Fix (Rejected)**:
- Include soft-deleted subscriptions in relationship: `$this->circle->subscription()->withTrashed()->first()`
- **Why rejected**: This couples the validator to subscription lifecycle unnecessarily
- **Better**: Validator should rely on circle's own data when subscription unavailable

---

## 🔐 EDGE CASES HANDLED

### Case 1: Subscription Soft-Deleted ✅
- **Scenario**: Subscription deleted during testing
- **Behavior**: Uses circle's `total_sessions` field
- **Result**: Scheduling works correctly

### Case 2: No Subscription at All ✅
- **Scenario**: Circle created without subscription (edge case)
- **Behavior**: `total_sessions` defaults to 0 if not set
- **Result**: Returns 0 remaining (correct), no error thrown

### Case 3: Active Subscription ✅
- **Scenario**: Normal operation with active subscription
- **Behavior**: Uses subscription-based calculation (unchanged)
- **Result**: Works as before

### Case 4: All Sessions Used ✅
- **Scenario**: 36 sessions all scheduled/completed
- **Behavior**: `max(0, 36 - 36) = 0`
- **Result**: Correctly shows 0 remaining

---

## 📁 FILES MODIFIED

**Single file change**:
- [app/Services/Scheduling/Validators/IndividualCircleValidator.php](app/Services/Scheduling/Validators/IndividualCircleValidator.php) (Lines 236-258)

**No database changes required** ✅
**No breaking changes** ✅
**Backward compatible** ✅

---

## 🚀 DEPLOYMENT

### Prerequisites
- ✅ PHP syntax validation: Passed
- ✅ Tested with deleted subscriptions: Works
- ✅ Tested with active subscriptions: Works
- ✅ Caches cleared

### Deployment Steps
1. Deploy code changes
2. Clear application cache: `php artisan optimize:clear`
3. Clear Filament cache: `php artisan filament:optimize-clear`
4. Test scheduling in Quran teacher calendar

### Rollback Plan
```bash
# Revert the single file
git checkout app/Services/Scheduling/Validators/IndividualCircleValidator.php
php artisan optimize:clear
```

---

## 🎉 RESOLUTION

**Status**: ✅ **FIXED**

**Summary**:
- Identified root cause: Validator returning 0 when subscription is null
- Implemented fix: Use circle's `total_sessions` field as fallback
- Tested thoroughly: All edge cases handled correctly
- Zero breaking changes, fully backward compatible

**You can now schedule individual circle sessions even when subscriptions are soft-deleted!** 🎊

---

## 📝 RELATED CONTEXT

This issue surfaced during testing after the academic subscription refactoring (which fixed similar session count bugs). The testing workflow involved deleting test subscriptions, which revealed this edge case in the Quran validator.

**Date Fixed**: December 3, 2025
**Time to Fix**: ~15 minutes (investigation + fix + testing)
**Lines Changed**: 22 lines in 1 file
