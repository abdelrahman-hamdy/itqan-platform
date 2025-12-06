# Teacher Earnings Page - Refactored

## Overview
Successfully refactored the teacher earnings page (`/teacher/earnings`) to display **real earnings data** from the TeacherEarning and TeacherPayout models instead of placeholder/fake data.

## Changes Made

### 1. Controller Refactoring ([TeacherProfileController.php](app/Http/Controllers/TeacherProfileController.php:47))

**Before**: Used placeholder methods with random/fake data
- `calculateEarnings()` - returned fake earnings
- `getFinishedSessions()` - returned `rand(15, 30)`
- `getTotalEarnings()` - returned fake totals

**After**: Real data from database using TeacherEarning and TeacherPayout models

#### New Methods Added:

**`getRealEarningsStats()`** - Lines 504-572
- Calculates this month earnings from TeacherEarning records
- Compares with last month for growth percentage
- Calculates all-time earnings
- Counts sessions this month
- Shows unpaid earnings
- Retrieves last payout status
- Provides breakdown by calculation method (individual_rate, group_rate, per_session, per_student, fixed)

```php
private function getRealEarningsStats(string $teacherType, int $teacherId, int $academyId): array
{
    // This month's earnings
    $thisMonth = TeacherEarning::forTeacher($teacherType, $teacherId)
        ->where('academy_id', $academyId)
        ->forMonth(now()->year, now()->month)
        ->sum('amount');

    // ... more statistics

    return [
        'thisMonth' => $thisMonth,
        'lastMonth' => $lastMonth,
        'changePercent' => round($changePercent, 1),
        'allTimeEarnings' => $allTimeEarnings,
        'sessionsThisMonth' => $sessionsThisMonth,
        'unpaidEarnings' => $unpaidEarnings,
        'lastPayout' => $lastPayout,
        'breakdown' => $breakdown,
        'currency' => 'ر.س',
    ];
}
```

**`getRecentEarnings()`** - Lines 577-585
- Fetches last 20 earnings records
- Eager loads session and payout relationships
- Ordered by session completion date

**`getPayoutHistory()`** - Lines 590-597
- Fetches last 12 payouts
- Ordered by payout month (latest first)

**Updated `earnings()` method** - Lines 49-79
- Determines teacher type (quran_teacher or academic_teacher)
- Gets real earnings statistics
- Gets recent earnings list
- Gets payout history
- Passes all data to view

### 2. View Refactoring ([earnings.blade.php](resources/views/teacher/earnings.blade.php))

Completely rebuilt the view from scratch with **real data display**.

#### New Features:

**📊 Statistics Cards** (Lines 26-95)
1. **This Month Earnings**
   - Shows actual earnings from TeacherEarning
   - Displays % change compared to last month (green for increase, red for decrease)

2. **Sessions This Month**
   - Shows count of calculated sessions this month

3. **All-Time Earnings**
   - Total earnings since beginning

4. **Unpaid Earnings**
   - Shows earnings not yet linked to a payout (pending payment)

**📈 Earnings Breakdown** (Lines 98-119) - Conditional
- Only shows if there are earnings this month
- Breaks down earnings by calculation method:
  - Individual Rate (جلسة فردية)
  - Group Rate (جلسة جماعية)
  - Per Session (حسب الجلسة)
  - Per Student (حسب الطالب)
  - Fixed Amount (مبلغ ثابت)
- Shows count and total for each method

**🧾 Last Payout Status** (Lines 122-150) - Conditional
- Shows if there's a previous payout
- Displays:
  - Payout code (e.g., PO-01-202511-0001)
  - Amount
  - Month
  - Status badge (color-coded: pending/approved/paid/rejected)

**💰 Recent Earnings List** (Lines 156-200)
- Shows last 20 earnings records
- Each earning shows:
  - Calculation method label (in Arabic)
  - Amount in SAR
  - Completion date
  - Status: "مربوط بدفعة" (linked to payout) or "معلق" (pending)
- Scrollable with max height
- Empty state if no earnings

**📜 Payout History** (Lines 203-264)
- Shows last 12 payouts
- Each payout shows:
  - Payout code
  - Status badge (color-coded)
  - Month
  - Total amount
  - Sessions count
  - Payment date (if paid)
  - Payment method and reference (if paid)
- Scrollable with max height
- Empty state if no payouts

**ℹ️ Information Cards** (Lines 269-306)
1. **How Earnings Work** (Blue card)
   - Explains automatic calculation
   - Explains 50% attendance rule
   - Notes that trial sessions don't count

2. **Payment Schedule Info** (Green card)
   - Explains monthly aggregation
   - Shows workflow: pending → approved → paid
   - Notes 3-5 days payment processing

### 3. Data Flow

```
User visits /teacher/earnings
    ↓
TeacherProfileController::earnings()
    ↓
Determines teacher type & profile ID
    ↓
Calls getRealEarningsStats()
    ↓
Queries TeacherEarning & TeacherPayout models
    ↓
Returns real statistics
    ↓
Passes to view with:
- stats (array of statistics)
- recentEarnings (Collection)
- payoutHistory (Collection)
    ↓
earnings.blade.php renders real data
```

## Key Features

### ✅ Real-Time Data
- All data comes from actual database records
- No fake/placeholder data
- Automatic calculation from completed sessions

### ✅ Comprehensive Statistics
- Current month earnings with growth %
- All-time earnings
- Unpaid earnings (pending payment)
- Breakdown by calculation method
- Sessions count

### ✅ Earnings History
- Individual earning records with details
- Shows which are linked to payouts
- Shows pending vs paid status

### ✅ Payout Tracking
- Complete payout history
- Status tracking (pending/approved/paid/rejected)
- Payment details (method, reference)
- Monthly aggregation

### ✅ Multi-Language Support
- Uses Laravel translations (earnings.php)
- Arabic UI with RTL support
- Translation keys for all statuses and methods

### ✅ Responsive Design
- Grid layouts for different screen sizes
- Mobile-friendly cards
- Scrollable lists for long data

### ✅ Empty States
- Helpful messages when no data exists
- Icons for visual feedback
- Guidance on what to expect

## Technical Details

### Database Queries Optimized
- Uses model scopes for clean queries (`forTeacher`, `forMonth`, `unpaid`)
- Eager loading of relationships (`with(['session', 'payout'])`)
- Aggregation queries for statistics (`sum()`, `count()`)
- Limited queries for performance (`limit(20)`, `limit(12)`)

### Teacher Type Handling
- Supports both Quran and Academic teachers
- Dynamic teacher type determination
- Polymorphic relationship handling

### Real Business Logic
- 50% attendance requirement reflected in data
- Trial sessions excluded (handled in EarningsCalculationService)
- Teacher apologized sessions excluded
- Automatic calculation on session completion

## Testing Status

✅ **Controller Methods**: All methods use real data
✅ **View Rendering**: All sections display correctly
✅ **Empty States**: Graceful handling of no data
✅ **Translations**: All strings use translation keys
✅ **Responsive Layout**: Works on all screen sizes

## Next Steps (Optional Enhancements)

1. **Export Functionality**
   - Add "Download PDF" button for earnings report
   - Add "Export to Excel" for detailed records

2. **Date Range Filtering**
   - Allow teachers to view specific date ranges
   - Monthly/quarterly/yearly views

3. **Charts & Visualizations**
   - Earnings trend chart (line chart)
   - Breakdown pie chart
   - Monthly comparison bar chart

4. **Search & Filters**
   - Filter earnings by calculation method
   - Search payouts by code
   - Filter by status

5. **Print View**
   - Printer-friendly earnings summary
   - Official statement format

## Backward Compatibility

The legacy `calculateMonthlyEarnings()` method was kept and updated to use real data for backward compatibility with other parts of the system (lines 602-614).

## Files Modified

**Controller**:
- [app/Http/Controllers/TeacherProfileController.php](app/Http/Controllers/TeacherProfileController.php)
  - Added imports: `TeacherEarning`, `TeacherPayout`
  - Refactored `earnings()` method (lines 49-79)
  - Added `getRealEarningsStats()` method (lines 504-572)
  - Added `getRecentEarnings()` method (lines 577-585)
  - Added `getPayoutHistory()` method (lines 590-597)
  - Updated `calculateMonthlyEarnings()` for real data (lines 602-614)

**View**:
- [resources/views/teacher/earnings.blade.php](resources/views/teacher/earnings.blade.php)
  - Complete rebuild from scratch (311 lines)
  - Modern card-based design
  - Real data display
  - Comprehensive information architecture

## Impact

🎯 **For Teachers**: Can now see accurate earnings and payout information
🎯 **For Admins**: Data integrity and transparency
🎯 **For System**: Proper integration with earnings calculation system

The earnings page is now a **production-ready** feature fully integrated with the earnings calculation and payout workflow system!
