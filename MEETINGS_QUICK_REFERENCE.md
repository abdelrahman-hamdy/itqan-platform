# Meetings Management System - Quick Reference Guide

## Key Files Overview

### Models
| File | Purpose | Key Methods |
|------|---------|-------------|
| `app/Models/BaseSession.php` | Abstract base for all sessions | `generateMeetingLink()`, `endMeeting()`, `generateParticipantToken()`, `getRoomInfo()` |
| `app/Models/MeetingAttendance.php` | Attendance tracking | `recordJoin()`, `recordLeave()`, `calculateFinalAttendance()`, `isCurrentlyInMeeting()` |
| `app/Models/QuranSession.php` | Quran-specific sessions | Implements BaseSession |
| `app/Models/AcademicSession.php` | Academic sessions | Implements BaseSession |

### Services
| File | Purpose | Key Methods |
|------|---------|-------------|
| `app/Services/LiveKitService.php` | LiveKit API integration | `createMeeting()`, `generateParticipantToken()`, `getRoomInfo()`, `endMeeting()` |
| `app/Services/AutoMeetingCreationService.php` | Auto-creation scheduler | `createMeetingsForAllAcademies()`, `cleanupExpiredMeetings()`, `getStatistics()` |
| `app/Services/SessionMeetingService.php` | Quran meeting management | `ensureMeetingAvailable()`, `processScheduledSessions()`, `getSessionTiming()` |
| `app/Services/AcademicSessionMeetingService.php` | Academic meeting management | `ensureMeetingAvailable()`, `getSessionTiming()` |
| `app/Services/MeetingAttendanceService.php` | Attendance tracking | `handleUserJoin()`, `handleUserLeave()`, `calculateFinalAttendance()` |
| `app/Services/MeetingDataChannelService.php` | Real-time communication | `sendTeacherControlCommand()` |

### Controllers
| File | Purpose | Key Endpoints |
|------|---------|---------------|
| `app/Http/Controllers/LiveKitMeetingController.php` | Meeting API | `createMeeting()`, `getParticipantToken()`, `getRoomInfo()`, `endMeeting()` |
| `app/Http/Controllers/UnifiedMeetingController.php` | Unified API | Same endpoints as above |

### Commands
| File | Purpose | Command |
|------|---------|---------|
| `app/Console/Commands/CreateScheduledMeetingsCommand.php` | Auto-create meetings | `php artisan meetings:create-scheduled` |
| `app/Console/Commands/CleanupExpiredMeetingsCommand.php` | Auto-cleanup | `php artisan meetings:cleanup-expired` |

---

## Quick Start Guide

### 1. Create a Meeting for a Session

**Automatic (Preferred):**
```bash
php artisan meetings:create-scheduled --academy-id=1 --dry-run -v
php artisan meetings:create-scheduled --academy-id=1
```

**Manual via API:**
```bash
POST /api/meetings/create
{
  "session_id": 42,
  "max_participants": 50,
  "recording_enabled": false,
  "max_duration": 120
}
```

**Programmatic:**
```php
$session = QuranSession::find(42);
$meetingUrl = $session->generateMeetingLink([
    'max_participants' => 50,
    'recording_enabled' => false,
    'max_duration' => 120,
]);
```

### 2. Get Access Token for User

**API:**
```bash
GET /api/meetings/42/token?session_type=quran&can_publish=true&can_subscribe=true
```

**Programmatic:**
```php
$session = QuranSession::find(42);
$user = Auth::user();
$token = $session->generateParticipantToken($user, [
    'can_publish' => true,
    'can_subscribe' => true,
]);
```

### 3. Track User Attendance

**On Join:**
```php
$session = QuranSession::find(42);
$user = Auth::user();
$attendanceService->handleUserJoin($session, $user);
```

**On Leave:**
```php
$attendanceService->handleUserLeave($session, $user);
```

**Calculate Final Attendance:**
```php
$results = $attendanceService->calculateFinalAttendance($session);
// Returns: ['session_id', 'calculated_count', 'errors', 'attendances']
```

### 4. Get Room Status

**API:**
```bash
GET /api/meetings/42/info
```

**Programmatic:**
```php
$session = QuranSession::find(42);
$roomInfo = $session->getRoomInfo();
// Returns: ['room_name', 'participant_count', 'participants', 'is_active']
```

### 5. End a Meeting

**API:**
```bash
POST /api/meetings/42/end
```

**Programmatic:**
```php
$session = QuranSession::find(42);
$success = $session->endMeeting();
```

### 6. Run Scheduled Maintenance

**Create Meetings:**
```bash
php artisan meetings:create-scheduled
```

**Cleanup Expired:**
```bash
php artisan meetings:cleanup-expired
```

**Dry Run (Preview):**
```bash
php artisan meetings:create-scheduled --dry-run -v
php artisan meetings:cleanup-expired --dry-run -v
```

---

## Configuration

### Environment Variables
```
LIVEKIT_API_KEY=your-key
LIVEKIT_API_SECRET=your-secret
LIVEKIT_SERVER_URL=wss://livekit.example.com
LIVEKIT_API_URL=https://livekit.example.com
LIVEKIT_RECORDING_S3_BUCKET=bucket-name
LIVEKIT_RECORDING_S3_REGION=us-east-1
```

### VideoSettings Configuration
```php
// Per Academy
$videoSettings = VideoSettings::forAcademy($academy);

$videoSettings->auto_create_meetings = true;
$videoSettings->auto_end_meetings = true;
$videoSettings->meeting_creation_minutes_before = 15;
$videoSettings->meeting_creation_time_start = '06:00';
$videoSettings->meeting_creation_time_end = '22:00';
$videoSettings->meeting_creation_blocked_days = ['friday'];
$videoSettings->default_max_participants = 50;
$videoSettings->enable_recording_by_default = false;
$videoSettings->save();
```

---

## Database Schema Reference

### Meeting Attendances
```sql
-- Join to find attendance for a session
SELECT * FROM meeting_attendances
WHERE session_id = 42
AND session_type = 'individual'
ORDER BY first_join_time DESC;

-- Find calculated attendances
SELECT * FROM meeting_attendances
WHERE is_calculated = true
AND attendance_calculated_at IS NOT NULL;

-- Attendance statistics
SELECT 
  attendance_status,
  COUNT(*) as count,
  AVG(attendance_percentage) as avg_percentage
FROM meeting_attendances
WHERE session_id = 42
GROUP BY attendance_status;
```

### Sessions with Meetings
```sql
-- Sessions with active meetings
SELECT * FROM quran_sessions
WHERE meeting_room_name IS NOT NULL
AND status IN ('SCHEDULED', 'READY', 'ONGOING');

-- Auto-generated meetings
SELECT * FROM quran_sessions
WHERE meeting_auto_generated = true
AND DATE(meeting_created_at) = CURDATE();
```

---

## API Endpoints Summary

### Create Meeting
```
POST /api/meetings/create
POST /sessions/{sessionId}/create-meeting

Required: session_id
Optional: max_participants, recording_enabled, max_duration
```

### Get Token
```
GET /api/meetings/{sessionId}/token

Optional: session_type, can_publish, can_subscribe
Returns: access_token, server_url, room_name, expires_at
```

### Get Room Info
```
GET /api/meetings/{sessionId}/info

Returns: room_name, participant_count, participants, is_active
```

### End Meeting
```
POST /api/meetings/{sessionId}/end

Returns: success status
```

### Record Leave
```
POST /api/meeting/leave

Marks user as left from meeting
```

---

## Attendance Status Levels

| Status | Definition | Threshold |
|--------|-----------|-----------|
| present | Attended 80%+ and on time | >= 80% AND joined before grace period |
| late | Attended 80%+ but arrived late | >= 80% AND joined after grace period |
| partial | Attended 30-79% | 30% to 79% |
| absent | Attended < 30% or never joined | < 30% |

**Grace Period:** Typically 15 minutes (configurable per circle)

---

## Common Tasks

### Check Meeting Status
```php
$session = QuranSession::find($id);
echo $session->status;                    // SCHEDULED, READY, ONGOING, COMPLETED
echo $session->meeting_room_name;         // LiveKit room name
echo $session->meeting_link;              // URL to join
echo $session->meeting_expires_at;        // When meeting expires
```

### Get Attendance for Session
```php
$attendances = MeetingAttendance::where('session_id', $sessionId)
    ->calculated()
    ->get();

foreach ($attendances as $att) {
    echo $att->user->name;               // Participant name
    echo $att->attendance_status;        // present/late/partial/absent
    echo $att->attendance_percentage;    // 0-100
    echo $att->total_duration_minutes;   // Minutes attended
}
```

### Monitor Auto-Creation
```php
$stats = $autoMeetingService->getStatistics();
echo $stats['total_auto_generated_meetings'];
echo $stats['active_meetings'];
echo $stats['meetings_created_today'];
echo $stats['meetings_created_this_week'];
echo $stats['academies_with_auto_creation_enabled'];
```

### Handle Reconnection
```php
$isReconnect = $attendanceService->handleReconnection($session, $user);
if ($isReconnect) {
    // Merged with previous cycle
} else {
    // New join
}
```

---

## Troubleshooting

### Meeting not created
1. Check `VideoSettings::auto_create_meetings` is true
2. Session must be SCHEDULED status
3. Session must be within 2-hour look-ahead window
4. Current time must be past meeting creation time
5. Check logs for LiveKit API errors

### Token generation fails
1. Session must have `meeting_room_name`
2. User must have join permission
3. Check LiveKit credentials in config

### Room not found
1. Auto-recreation will be attempted
2. Check LiveKit server availability
3. Verify `LIVEKIT_API_KEY` and `LIVEKIT_API_SECRET`

### Attendance not calculated
1. Call `calculateFinalAttendance()` after session ends
2. Check `is_calculated` flag (false = needs calculation)
3. Verify session has `circle` with `late_join_grace_period_minutes`

---

## Performance Tips

1. **Use eager loading** when fetching sessions with attendance:
   ```php
   $sessions = QuranSession::with('meetingAttendances')->get();
   ```

2. **Batch calculate attendance** after sessions end:
   ```php
   $attendanceService->processCompletedSessions($completedSessions);
   ```

3. **Run cleanup commands regularly** (every 30 mins):
   ```php
   // Schedule in app/Console/Kernel.php
   $schedule->command('meetings:cleanup-expired')->everyThirtyMinutes();
   ```

4. **Monitor statistics** periodically:
   ```php
   $stats = $autoMeetingService->getStatistics();
   // Store in cache or dashboard
   ```

---

## Key Dependencies

- **Agence104/LiveKit SDK** - LiveKit API client
- **Illuminate/Support** - Cache, Queue, Broadcasting
- **Laravel Framework** - Models, Services, Commands

---

## Support & Documentation

- Full analysis: `MEETINGS_SYSTEM_ANALYSIS.md`
- LiveKit docs: https://docs.livekit.io/
- Configuration: `config/livekit.php`
- Migrations: `database/migrations/*meeting*`
