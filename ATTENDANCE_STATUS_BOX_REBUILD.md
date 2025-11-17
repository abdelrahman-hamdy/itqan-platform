# Attendance Status Box - Rebuilt with Real-Time Updates

## Overview

The attendance status box ("حالة الحضور") has been rebuilt as a **dynamic Livewire component** that:
- ✅ Auto-updates every 10 seconds
- ✅ Updates immediately when webhooks are received
- ✅ Shows different states based on session timing
- ✅ Displays calculated attendance after session ends

## Component Structure

### 1. Livewire Component
**File**: `app/Livewire/Student/AttendanceStatus.php`

**Properties**:
- `sessionId`, `sessionType`, `userId` - Session and user identifiers
- `status` - Current state (waiting, preparation, in_meeting, completed)
- `attendanceText` - Main status message
- `attendanceTime` - Subtitle/timing information
- `duration` - Total attendance duration in minutes
- `dotColor` - Color of status indicator dot
- `showProgress` - Whether to show progress bar
- `attendancePercentage` - Percentage of session attended

**Methods**:
- `mount()` - Initialize component with session data
- `updateAttendanceStatus()` - Main update logic (runs every 10s)
- `refreshAttendance()` - Event listener for real-time webhook updates
- State setters: `setWaitingState()`, `setPreparationState()`, `setLiveState()`, `setCompletedState()`

### 2. Blade View
**File**: `resources/views/livewire/student/attendance-status.blade.php`

**Features**:
- Auto-polling every 10 seconds: `wire:poll.10s="updateAttendanceStatus"`
- Animated status dot with color changes
- Progress bar after session ends
- Detailed join/leave times
- Loading indicator during updates

### 3. Integration
**File**: `resources/views/components/meetings/livekit-interface.blade.php` (Line 2041-2046)

**Replaced**:
```php
<!-- OLD: Static HTML -->
<div class="attendance-status">
    <div class="attendance-text">جاري التحميل...</div>
</div>
```

**With**:
```php
<!-- NEW: Dynamic Livewire Component -->
@livewire('student.attendance-status', [
    'sessionId' => $session->id,
    'sessionType' => $isAcademicSession ? 'academic' : 'quran',
    'userId' => auth()->id()
])
```

## State Flow

### State 1: Waiting (Before Preparation Time)
**Condition**: `now < preparation_start` (10 minutes before session)

**Display**:
- Text: "في انتظار بدء الجلسة"
- Time: "الجلسة تبدأ في XX:XX"
- Dot: Blue (`bg-blue-400`)

### State 2: Preparation Time
**Condition**: `preparation_start ≤ now < session_start`

**Display**:
- Text: "وقت التحضير - يمكنك الدخول الآن"
- Time: "الجلسة تبدأ خلال X دقيقة"
- Dot: Yellow, pulsing (`bg-yellow-400 animate-pulse`)

### State 3: Session Live (Not Joined)
**Condition**: `session_start ≤ now < session_end` AND no attendance record

**Display**:
- Text: "الجلسة جارية - لم تنضم بعد"
- Time: "الجلسة تنتهي خلال X دقيقة"
- Dot: Red, pulsing (`bg-red-400 animate-pulse`)

### State 4: Session Live (Joined, Still In)
**Condition**: `session_start ≤ now < session_end` AND joined AND no leave time

**Display**:
- Text: "أنت في الجلسة الآن"
- Time: "انضممت الساعة XX:XX"
- Dot: Green, pulsing (`bg-green-500 animate-pulse`)

### State 5: Session Live (Left Early)
**Condition**: `session_start ≤ now < session_end` AND joined AND has leave time

**Display**:
- Text: "غادرت الجلسة"
- Time: "المدة: X دقيقة"
- Dot: Orange (`bg-orange-400`)

### State 6: Session Ended (Calculated)
**Condition**: `now ≥ session_end` AND attendance calculated

**Display** (depends on duration):

**If duration = 0**:
- Text: "لم تحضر الجلسة"
- Time: "غائب"
- Dot: Red (`bg-red-500`)

**If attendance ≥ 80%**:
- Text: "حاضر"
- Time: "المدة: X من Y دقيقة"
- Dot: Green (`bg-green-500`)
- Progress bar: Green, showing percentage

**If attendance 50-79%**:
- Text: "حضور جزئي"
- Time: "المدة: X من Y دقيقة"
- Dot: Yellow (`bg-yellow-500`)
- Progress bar: Yellow, showing percentage

**If attendance < 50%**:
- Text: "متأخر"
- Time: "المدة: X من Y دقيقة"
- Dot: Orange (`bg-orange-500`)
- Progress bar: Red, showing percentage

### State 7: Session Ended (Not Calculated)
**Condition**: `now ≥ session_end` AND attendance not calculated yet

**Display**:
- Text: "الجلسة انتهت - سيتم حساب الحضور قريباً"
- Time: "يرجى الانتظار..."
- Dot: Gray (`bg-gray-400`)

## Real-Time Updates

### 1. Automatic Polling
Component refreshes every 10 seconds automatically via `wire:poll.10s`

### 2. Webhook-Triggered Updates
When webhooks are received, the component updates immediately:

**Flow**:
1. LiveKit sends webhook (join/leave)
2. `LiveKitWebhookController` processes webhook
3. `AttendanceEventService` records attendance
4. Service dispatches Livewire event: `attendance-updated`
5. Component listens with `#[On('attendance-updated')]`
6. Component refreshes instantly

**Code in AttendanceEventService**:
```php
private function dispatchAttendanceUpdate(int $sessionId, int $userId): void
{
    event(new \Livewire\Event('attendance-updated', [
        'sessionId' => $sessionId,
        'userId' => $userId,
    ]));
}
```

## Visual Indicators

### Status Dot Colors
- **Blue**: Waiting for session
- **Yellow (pulsing)**: Preparation time
- **Red (pulsing)**: Session live, not joined
- **Green (pulsing)**: Currently in meeting
- **Orange**: Left session early
- **Green (solid)**: Attended ≥80%
- **Yellow (solid)**: Attended 50-79%
- **Orange (solid)**: Attended <50%
- **Red (solid)**: Absent
- **Gray**: Loading/calculating

### Progress Bar
- Only shown after session ends and attendance calculated
- Color matches attendance status:
  - Green: ≥80%
  - Yellow: 50-79%
  - Red: <50%

## Files Modified

1. **app/Livewire/Student/AttendanceStatus.php** - NEW component class
2. **resources/views/livewire/student/attendance-status.blade.php** - NEW component view
3. **resources/views/components/meetings/livekit-interface.blade.php** - Replaced static HTML with Livewire component
4. **app/Services/AttendanceEventService.php** - Added event dispatching for real-time updates

## Testing

### 1. Before Session
Visit session page 15+ minutes before start time.
**Expected**: Blue dot, "في انتظار بدء الجلسة"

### 2. Preparation Time
Visit session page 5 minutes before start time.
**Expected**: Yellow pulsing dot, "وقت التحضير - يمكنك الدخول الآن"

### 3. During Session (Not Joined)
Session is live but you haven't joined.
**Expected**: Red pulsing dot, "الجلسة جارية - لم تنضم بعد"

### 4. During Session (Joined)
Join the session via LiveKit.
**Expected**:
- Green pulsing dot immediately after webhook
- "أنت في الجلسة الآن"
- "انضممت الساعة XX:XX"

### 5. During Session (Left)
Leave the session.
**Expected**:
- Orange dot immediately after webhook
- "غادرت الجلسة"
- "المدة: X دقيقة"

### 6. After Session Ends
Wait for calculation job to run (every 10 seconds locally).
**Expected**:
- Progress bar appears
- Attendance percentage displayed
- Status: حاضر/حضور جزئي/متأخر/غائب

## Configuration

### Polling Interval
Change in view: `wire:poll.10s` → `wire:poll.5s` for faster updates

### Attendance Thresholds
Change in `setCompletedState()`:
```php
if ($this->attendancePercentage >= 80) {  // Present
if ($this->attendancePercentage >= 50) {  // Partial
else {  // Late
```

### Preparation Time
Defaults to 10 minutes before session:
```php
$this->preparationStart = $session->scheduled_at->copy()->subMinutes(10);
```

## Benefits

✅ **Real-time**: Updates immediately when webhooks received
✅ **Time-aware**: Different messages for each phase
✅ **Informative**: Clear status and timing information
✅ **Visual**: Color-coded status dots with animations
✅ **Accurate**: Based on webhook data (source of truth)
✅ **Auto-updating**: Polls every 10 seconds automatically
✅ **User-friendly**: Arabic messages, clear feedback

---

**Status**: ✅ Implemented and Ready
**Date**: 2025-11-15
**Impact**: Greatly improves student experience with real-time attendance feedback
