# Trial Session Management Guide

## Overview
This guide explains the complete flow for managing trial sessions from both teacher and student perspectives.

## Issues Fixed

### 1. Student Access to Trial Sessions ✅
**Problem**: Students couldn't see a link to join their scheduled trial sessions.

**Root Cause**:
- Controller wasn't loading the `trialSession` relationship
- View was using incorrect relationship name (`scheduled_session` instead of `trialSession`)

**Fix Applied**:
- Updated `StudentProfileController.php`:
  - Line 71: Added `'trialSession'` to eager loading (meeting data stored in session fields)
  - Line 297: Same fix for subscriptions page
- Updated `resources/views/student/profile.blade.php`:
  - Line 407-408: Changed `$trialRequest->scheduled_session` to `$trialRequest->trialSession`

**Result**: Students now see "دخول الجلسة" (Enter Session) button when trial is scheduled.

### 2. Calendar Display Issue ✅
**Problem**: Trial sessions weren't showing in teacher's calendar.

**Root Cause**: Calendar was filtering by `'in_progress'` status which doesn't exist in SessionStatus enum. The correct value is `'ongoing'`.

**Fixes Applied**:
- `TeacherCalendarWidget.php` (3 changes):
  - Line 98: Updated filter to `['scheduled', 'ready', 'ongoing', 'completed']`
  - Line 132: Changed `'in_progress'` to `'ongoing'`
  - Line 139: Changed condition to use `'ongoing'`
- `Calendar.php` (2 changes):
  - Line 166: Updated filter to include `'ongoing'`
  - Line 229: Same fix for session counting

## Complete Trial Session Flow

### 1. Student Requests Trial Session

**Student Actions**:
1. Browse Quran teachers at: `/student/quran-teachers`
2. Click on a teacher to view profile
3. Click "حجز جلسة تجريبية مجانية" (Book Free Trial Session)
4. Fill out trial booking form:
   - Current level (beginner/elementary/intermediate/advanced/expert/hafiz)
   - Learning goals (reading/tajweed/memorization/improvement)
   - Preferred time (morning/afternoon/evening)
   - Additional notes
5. Submit request

**System Actions**:
- Creates `QuranTrialRequest` record with status `'pending'`
- Sends notification to teacher
- Student sees request in profile with "في انتظار الرد" (Awaiting Reply) status

---

### 2. Teacher Schedules Trial Session

**Teacher Access Points**:
1. **Calendar Page** (`/teacher/calendar`):
   - Switch to "طلبات الجلسات التجريبية" (Trial Requests) tab
   - See list of pending trial requests
   - Click on a request to select it
   - Click "جدولة الجلسة التجريبية" button

**Scheduling Form**:
- Select date and time (validates for conflicts)
- Add optional teacher message to student
- System validates:
  - No schedule conflicts with other sessions
  - Date is in the future
  - Not beyond 2 months

**What Happens on Submit**:
```php
// Creates QuranSession with:
- session_type: 'trial'
- status: SessionStatus::SCHEDULED
- duration_minutes: 30
- trial_request_id: linked to request
- LiveKit meeting room automatically generated

// QuranSessionObserver automatically:
- Links session to trial request
- Updates trial request status to 'scheduled'
- Sets up status synchronization
```

**Teacher View in Calendar**:
- Trial session appears with **yellow color** (#eab308)
- Title: "جلسة تجريبية: [Student Name]"
- Can click to view/edit details
- Can access session page

---

### 3. Student Sees Scheduled Trial

**Student Profile** (`/student/profile`):
- Trial request card now shows:
  - Status badge: "مجدولة" (Scheduled) - blue
  - Green "دخول الجلسة" (Enter Session) button
  - Preferred time information
  - Teacher details

**Click Behavior**:
- Button links to: `/student/sessions/{sessionId}`
- Shows full session detail page with:
  - Trial session banner (green gradient)
  - LiveKit meeting interface
  - Student level and goals context
  - Session information

---

### 4. Teacher Manages Trial Session

**From Calendar** (before session):
- **Click on trial session** → Modal opens with:
  - View details action
  - Edit action (can change time/notes)
  - Shows trial request context

**From Session Detail Page**:
- Teacher can access: `/sessions/{sessionId}` route
- Views same interface as other sessions:
  - LiveKit meeting room
  - Student information
  - Trial context (level, goals, notes)
  - Session controls

**During Session**:
- Status automatically changes to `'ongoing'` when scheduled time arrives
- Both teacher and student can join the LiveKit room
- Meeting interface provides:
  - Video/audio controls
  - Chat
  - Screen sharing
  - Recording (if enabled)

**After Session**:
- Teacher can mark session as completed from calendar
- Teacher can provide feedback
- Status sync automatically updates trial request

---

### 5. Session Status Management

**Automatic Status Transitions**:
```
SCHEDULED → ONGOING (when scheduled time arrives)
ONGOING → COMPLETED (teacher marks complete)
SCHEDULED → CANCELLED (teacher cancels)
```

**QuranSessionObserver** handles automatic syncing:
```php
QuranSession status → QuranTrialRequest status

SCHEDULED → STATUS_SCHEDULED
COMPLETED → STATUS_COMPLETED
CANCELLED → STATUS_CANCELLED
MISSED → STATUS_NO_SHOW
```

**Teacher Can**:
1. **View session** - Click in calendar
2. **Edit time/notes** - Edit action in calendar modal
3. **Join meeting** - Access session page, click join button
4. **Complete session** - From session page or calendar
5. **Cancel session** - Edit action → Cancel

---

### 6. Meeting Access

**LiveKit Integration**:
- Meeting room created automatically when session is scheduled
- Room name format: `itqan-academy-quran-session-{sessionId}`
- Access tokens generated per user (teacher/student)
- Meeting URL: `/meeting/{roomName}`

**Join Conditions**:
- Session must be in `READY` or `ONGOING` status
- Meeting can be joined 10 minutes before scheduled time
- Auto-expires 1 hour after scheduled end time

**Both Teacher and Student**:
1. Navigate to session page
2. See LiveKit interface component
3. Click "دخول الجلسة" (Join Session) button
4. Join video call directly in browser

---

## Key Routes

### Student Routes:
```
/student/profile                              # See trial requests
/student/sessions/{sessionId}                 # Join trial session
/student/quran-teachers                       # Browse teachers
/student/quran-teachers/{teacherId}/trial     # Book trial
```

### Teacher Routes:
```
/teacher/calendar                             # Manage trials
/sessions/{sessionId}                         # Session detail
/meeting/{roomName}                           # Join meeting
```

---

## Calendar Color Coding

| Session Type | Color | Hex Code |
|-------------|-------|----------|
| Trial | Yellow | #eab308 |
| Group | Green | #22c55e |
| Individual | Indigo | #6366f1 |

**Status Overrides** (non-trial):
- Cancelled: Red (#ef4444)
- Ongoing: Blue (#3b82f6)

---

## Testing Checklist

### Student Flow:
- [ ] Can see pending trial request in profile
- [ ] Request shows correct status badge
- [ ] "Enter Session" button appears when scheduled
- [ ] Button links to correct session page
- [ ] Can access LiveKit meeting room
- [ ] Sees trial context information

### Teacher Flow:
- [ ] Trial requests appear in calendar page
- [ ] Can select and schedule trial session
- [ ] Trial session appears in calendar (yellow)
- [ ] Can click trial session to view details
- [ ] Can edit trial session time
- [ ] Can access session detail page
- [ ] Can join meeting from session page
- [ ] Can complete/cancel session

### Status Sync:
- [ ] Trial request status updates when session scheduled
- [ ] Status syncs when session status changes
- [ ] Completed session marks trial as completed
- [ ] Cancelled session marks trial as cancelled

---

## Database Schema

### QuranTrialRequest (after refactoring):
```sql
- id
- academy_id
- student_id
- teacher_id                 # QuranTeacherProfile ID
- trial_session_id          # Links to created session
- request_code
- student_name
- phone
- email
- current_level
- learning_goals (JSON)
- preferred_time
- notes
- status                    # pending/scheduled/completed/cancelled/no_show
- rating                    # After completion
- feedback                  # After completion
```

**Removed Fields** (now in QuranSession):
- ~~scheduled_at~~ → QuranSession.scheduled_at
- ~~meeting_link~~ → QuranSession.meeting_link (stored directly in session)
- ~~meeting_password~~ → Not needed for LiveKit

**Note**: Meeting data is stored directly in the QuranSession model as fields:
- `meeting_link` - LiveKit meeting URL
- `meeting_id` - Room identifier
- `meeting_room_name` - LiveKit room name
- `meeting_data` - JSON with meeting configuration

### QuranSession (trial type):
```sql
- session_type: 'trial'
- trial_request_id          # Links back to request
- All standard session fields
- Automatic LiveKit meeting via generateMeetingLink()
```

---

## Architecture Benefits

✅ **Single Source of Truth**: QuranSession manages all session data
✅ **Automatic Sync**: Observer pattern keeps trial request status updated
✅ **Unified Interface**: Same meeting system as other sessions
✅ **LiveKit Integration**: Professional video conferencing
✅ **Status Tracking**: Clear state machine for trial lifecycle
✅ **Reusable Components**: Same session page template for all types

---

## Common Issues & Solutions

### Issue: Trial session not showing in calendar
**Solution**: Check that session status is in `['scheduled', 'ready', 'ongoing', 'completed']` - NOT `'in_progress'`

### Issue: Student can't see "Enter Session" button
**Solution**: Verify `trialSession` relationship is loaded and trial request status is `'scheduled'`

### Issue: Teacher can't schedule trial
**Solution**: Check for scheduling conflicts and ensure teacher profile exists

### Issue: Meeting room not accessible
**Solution**: Verify LiveKit credentials in `.env` and meeting was generated via `generateMeetingLink()`

---

## Next Steps for Enhancement

1. **Email Notifications**:
   - Notify student when trial is scheduled
   - Remind both parties 1 hour before
   - Send completion survey to student

2. **Rating System**:
   - Allow student to rate trial session
   - Display ratings on teacher profile
   - Use for teacher recommendations

3. **Analytics**:
   - Track trial-to-subscription conversion rate
   - Monitor teacher response times
   - Analyze preferred trial times

4. **Automated Scheduling**:
   - Suggest best times based on teacher availability
   - Allow teachers to set available trial slots
   - Auto-accept trials during available slots
