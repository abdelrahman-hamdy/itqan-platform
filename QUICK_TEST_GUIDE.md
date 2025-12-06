# Quick Test Guide - Scheduling System Fixes

## 🎯 What You're Testing

3 critical fixes:
1. ✅ Session count now multiplies by billing cycle
2. ✅ Renewals create new sessions
3. ✅ Field names unified (no more validator errors)

---

## ⚡ 2-Minute Quick Test

### Test 1: Session Count (MOST IMPORTANT)
```bash
# 1. Go to student enrollment page
# 2. Enroll in any academic package with:
#    - 12 sessions/month
#    - Quarterly billing (3 months)
# 3. Complete enrollment
# 4. Expected: 36 sessions created (not 8 or 12)
```

**How to Verify**:
```bash
php artisan tinker
```
```php
$sub = \App\Models\AcademicSubscription::latest()->first();
$count = $sub->academicSessions()->count();

echo "Sessions created: {$count}\n";
echo "Expected: 36\n";
echo "Result: " . ($count === 36 ? "✅ PASS" : "❌ FAIL") . "\n";
```

**Success Criteria**: Shows "✅ PASS"

---

### Test 2: Check Logs
```bash
tail -20 storage/logs/laravel.log | grep -A 5 "session creation"
```

**Expected Output**:
```
Starting session creation
  sessions_per_month: 12
  billing_cycle: quarterly
  billing_cycle_multiplier: 3
  total_sessions_to_create: 36

Session creation complete
  total_sessions_created: 36
```

**Success Criteria**: Shows "total_sessions_to_create: 36"

---

### Test 3: Calendar Display
```bash
# 1. Go to teacher calendar
# 2. Click "Schedule Sessions" for the new subscription
# 3. Expected: Dialog shows "36 sessions available to schedule"
```

**Success Criteria**: No longer shows hardcoded "8 sessions"

---

## 🔍 Detailed Verification

### Check Migration Status
```bash
php artisan migrate:status | grep "populate_standardized_date_fields"
```
**Expected**: Shows "Ran" status

### Check Field Population
```bash
php artisan tinker
```
```php
$sub = \App\Models\AcademicSubscription::first();

echo "Legacy fields:\n";
echo "  start_date: {$sub->start_date}\n";
echo "  end_date: {$sub->end_date}\n";

echo "\nStandardized fields:\n";
echo "  starts_at: {$sub->starts_at}\n";
echo "  ends_at: {$sub->ends_at}\n";

echo "\nMatch: " .
     ($sub->start_date->eq($sub->starts_at) && $sub->end_date->eq($sub->ends_at)
      ? "✅ YES" : "❌ NO") . "\n";
```

**Success Criteria**: Both field sets match

---

## 🧪 Test Different Billing Cycles

### Monthly (Control Test)
```
Package: 12 sessions/month
Billing: Monthly
Expected: 12 sessions (12 × 1)
```

### Quarterly (Main Test)
```
Package: 12 sessions/month
Billing: Quarterly
Expected: 36 sessions (12 × 3)
```

### Yearly (Edge Case)
```
Package: 12 sessions/month
Billing: Yearly
Expected: 144 sessions (12 × 12)
```

---

## ❌ What to Look For (Failures)

### Failure Sign #1: Wrong Session Count
- Shows 8 or 12 sessions for quarterly
- **Cause**: Billing cycle multiplication not working
- **Fix**: Check logs for errors

### Failure Sign #2: Validator Error
- Error: "Call to undefined property: starts_at"
- **Cause**: Migration didn't run or failed
- **Fix**: Run `php artisan migrate`

### Failure Sign #3: Null Fields
- `starts_at` is NULL in database
- **Cause**: Field syncing not working
- **Fix**: Check AcademicSubscription model boot method

---

## 🐛 Troubleshooting

### Issue: Migration Already Ran
```bash
# Check status
php artisan migrate:status

# If shows "Ran", migration already executed ✅
```

### Issue: Cache Problems
```bash
# Clear everything
php artisan optimize:clear
php artisan filament:optimize-clear
php artisan config:clear
php artisan view:clear

# Hard refresh browser
# Windows/Linux: Ctrl + Shift + R
# Mac: Cmd + Shift + R
```

### Issue: Old Data
```bash
# Delete test subscriptions
php artisan tinker
```
```php
\App\Models\AcademicSubscription::where('student_id', YOUR_TEST_STUDENT_ID)->delete();
\App\Models\AcademicSession::where('student_id', YOUR_TEST_STUDENT_ID)->delete();
```

---

## ✅ Success Indicators

If you see ALL of these, refactor is successful:

1. ✅ Quarterly subscription creates 36 sessions (not 12)
2. ✅ Logs show "billing_cycle_multiplier: 3"
3. ✅ Calendar shows all 36 sessions available
4. ✅ No validator errors in logs
5. ✅ Both date field sets populated in database

---

## 📞 If Test Fails

1. Check Laravel logs: `tail -50 storage/logs/laravel.log`
2. Check migration: `php artisan migrate:status`
3. Clear all caches: `php artisan optimize:clear`
4. Verify field syncing: Check model's creating hook
5. Report specific error message

---

## 🎉 Expected Results

**Before Fix**:
- Quarterly: 8-12 sessions ❌
- Logs: Only "sessions_per_month: 12"
- Renewals: 0 new sessions ❌

**After Fix**:
- Quarterly: 36 sessions ✅
- Logs: "billing_cycle_multiplier: 3, total_sessions_to_create: 36"
- Renewals: Correct new sessions ✅

---

## ⏱️ Time Required

- Quick Test: **2 minutes**
- Full Verification: **5 minutes**
- All Billing Cycles: **10 minutes**

---

## 📋 Test Report Template

```
Date: __________
Tester: __________

[ ] Test 1: Quarterly subscription creates 36 sessions
[ ] Test 2: Logs show correct multiplication
[ ] Test 3: Calendar displays all sessions
[ ] Test 4: Both date fields populated
[ ] Test 5: No validator errors

Result: PASS / FAIL
Notes: _______________
```

---

## 🚀 Ready to Deploy?

If all tests pass:
- ✅ Code is production-ready
- ✅ No breaking changes
- ✅ Backward compatible
- ✅ Safe to deploy

**Next Step**: Deploy to production and monitor first few enrollments! 🎊
