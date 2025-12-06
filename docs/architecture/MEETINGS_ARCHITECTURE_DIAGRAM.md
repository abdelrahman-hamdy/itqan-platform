# Meetings Management System - Architecture Diagrams

## 1. Overall System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT LAYER                             │
│  (Web/Mobile UI - Vue.js, JavaScript)                            │
└────────────────┬──────────────────────────────────────────────────┘
                 │ HTTP/WebSocket
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                    API CONTROLLERS LAYER                          │
│                                                                   │
│  ┌─────────────────────────┐  ┌──────────────────────────────┐  │
│  │ LiveKitMeetingController│  │ UnifiedMeetingController     │  │
│  │ - createMeeting()       │  │ - createMeeting()           │  │
│  │ - getParticipantToken() │  │ - getParticipantToken()     │  │
│  │ - getRoomInfo()         │  │ - getRoomInfo()             │  │
│  │ - endMeeting()          │  │ - endMeeting()              │  │
│  └─────────────────────────┘  │ - recordLeave()             │  │
│                                └──────────────────────────────┘  │
└────────────────┬──────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                    SERVICE LAYER                                 │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │           LiveKit Integration Services                    │   │
│  │  ┌─────────────────────────────────────────────────────┐ │   │
│  │  │ LiveKitService                                      │ │   │
│  │  │ - createMeeting()     → LiveKit Room Creation       │ │   │
│  │  │ - generateToken()     → JWT Token Generation        │ │   │
│  │  │ - getRoomInfo()       → Room Status & Participants  │ │   │
│  │  │ - endMeeting()        → Room Termination            │ │   │
│  │  │ - handleWebhook()     → Event Processing            │ │   │
│  │  └─────────────────────────────────────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │           Meeting Scheduling Services                    │   │
│  │  ┌─────────────────────────────────────────────────────┐ │   │
│  │  │ AutoMeetingCreationService                          │ │   │
│  │  │ - createMeetingsForAllAcademies()                  │ │   │
│  │  │ - createMeetingsForAcademy()                       │ │   │
│  │  │ - cleanupExpiredMeetings()                         │ │   │
│  │  │ - getStatistics()                                  │ │   │
│  │  └─────────────────────────────────────────────────────┘ │   │
│  │  ┌─────────────────────────────────────────────────────┐ │   │
│  │  │ SessionMeetingService (Quran)                       │ │   │
│  │  │ - ensureMeetingAvailable()                         │ │   │
│  │  │ - processScheduledSessions()                       │ │   │
│  │  │ - getSessionTiming()                               │ │   │
│  │  └─────────────────────────────────────────────────────┘ │   │
│  │  ┌─────────────────────────────────────────────────────┐ │   │
│  │  │ AcademicSessionMeetingService                       │ │   │
│  │  │ - ensureMeetingAvailable()                         │ │   │
│  │  │ - getSessionTiming()                               │ │   │
│  │  └─────────────────────────────────────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │           Attendance Tracking Services                   │   │
│  │  ┌─────────────────────────────────────────────────────┐ │   │
│  │  │ MeetingAttendanceService                            │ │   │
│  │  │ - handleUserJoin()                                 │ │   │
│  │  │ - handleUserLeave()                                │ │   │
│  │  │ - calculateFinalAttendance()                       │ │   │
│  │  │ - getAttendanceStatistics()                        │ │   │
│  │  │ - handleReconnection()                             │ │   │
│  │  └─────────────────────────────────────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │           Real-Time Communication Services              │   │
│  │  ┌─────────────────────────────────────────────────────┐ │   │
│  │  │ MeetingDataChannelService                           │ │   │
│  │  │ - sendTeacherControlCommand()                      │ │   │
│  │  │ - sendViaMultipleChannels()                        │ │   │
│  │  │   ├─ LiveKit Data Channel (Primary)                │ │   │
│  │  │   ├─ WebSocket/Broadcasting (Secondary)            │ │   │
│  │  │   ├─ Database Polling (Tertiary)                   │ │   │
│  │  │   └─ Server-Sent Events (Quaternary)               │ │   │
│  │  └─────────────────────────────────────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
└────────────────┬──────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                    MODEL LAYER                                   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ BaseSession (Abstract)                                   │   │
│  │ [Trait: HasMeetings]                                     │   │
│  │ - generateMeetingLink()                                  │   │
│  │ - generateParticipantToken()                             │   │
│  │ - getRoomInfo()                                          │   │
│  │ - endMeeting()                                           │   │
│  │ - isMeetingValid()                                       │   │
│  │ - getMeetingStats()                                      │   │
│  │                                                          │   │
│  │  Implementations:                                        │   │
│  │  ├─ QuranSession                                        │   │
│  │  ├─ AcademicSession                                     │   │
│  │  └─ InteractiveCourseSession                            │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ MeetingAttendance                                        │   │
│  │ - recordJoin()                                           │   │
│  │ - recordLeave()                                          │   │
│  │ - calculateFinalAttendance()                             │   │
│  │ - isCurrentlyInMeeting()                                 │   │
│  │ - autoCloseStaleCycles()                                 │   │
│  │                                                          │   │
│  │ Scopes:                                                  │   │
│  │  - present(), absent(), late(), partial()               │   │
│  │  - calculated(), notCalculated()                         │   │
│  │  - forSession(), forUser()                               │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
└────────────────┬──────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                    DATA LAYER                                    │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ Database Tables                                          │   │
│  │  ├─ quran_sessions                                      │   │
│  │  │  └─ meeting_link, meeting_room_name, meeting_data... │   │
│  │  ├─ academic_sessions                                   │   │
│  │  │  └─ meeting_link, meeting_room_name, meeting_data... │   │
│  │  ├─ interactive_course_sessions                         │   │
│  │  │  └─ meeting_link, meeting_room_name, meeting_data... │   │
│  │  └─ meeting_attendances                                 │   │
│  │     ├─ session_id, user_id, user_type, session_type    │   │
│  │     ├─ first_join_time, last_leave_time               │   │
│  │     ├─ join_leave_cycles, total_duration_minutes      │   │
│  │     └─ attendance_status, attendance_percentage        │   │
│  │                                                          │   │
│  │ Cache (Redis)                                            │   │
│  │  └─ session_meeting:{session_id}:persistence           │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
└────────────────┬──────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                   EXTERNAL SERVICES                              │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ LiveKit Server                                           │   │
│  │  ├─ Room Management                                      │   │
│  │  ├─ Participant Tracking                                 │   │
│  │  ├─ Media Streaming                                      │   │
│  │  ├─ Recording (future)                                   │   │
│  │  └─ Webhooks                                             │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ AWS S3 (Recording Storage - future)                     │   │
│  │  └─ Meeting Recordings                                   │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Meeting Lifecycle Flow

```
┌──────────────────────────────────────────────────────────────────┐
│                     SESSION CREATED                               │
│                (status = null initially)                           │
└────────────────────┬─────────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────────┐
│                    SESSION SCHEDULED                              │
│              status = SCHEDULED                                   │
│              scheduled_at = set                                   │
│              meeting_room_name = null                             │
└────────────────────┬─────────────────────────────────────────────┘
                     │
                     │ [Periodic: meetings:create-scheduled command]
                     │  OR [Manual: createMeeting API]
                     │
                     ▼
┌──────────────────────────────────────────────────────────────────┐
│                   MEETING CREATED                                 │
│   generateMeetingLink() → LiveKit room creation                  │
│                                                                   │
│   Fields Set:                                                     │
│   - meeting_room_name = "academy-quran-session-42"               │
│   - meeting_link = "https://app.com/meeting/..."                 │
│   - meeting_id = room identifier                                  │
│   - meeting_data = complete config (JSON)                         │
│   - meeting_expires_at = now + 3 hours                            │
│   - meeting_auto_generated = true (if auto-created)              │
│   - status transitions to READY                                   │
└────────────────────┬─────────────────────────────────────────────┘
                     │
                     │ [Participants join via token]
                     │ GET /api/meetings/{id}/token → JWT
                     │ Participant joins LiveKit room
                     │
                     ▼
┌──────────────────────────────────────────────────────────────────┐
│                  FIRST PARTICIPANT JOINS                          │
│                                                                   │
│  MeetingAttendanceService::handleUserJoin()                      │
│    ├─ Create MeetingAttendance record                            │
│    ├─ Record first join time                                     │
│    ├─ Increment join_count = 1                                   │
│    ├─ Add to join_leave_cycles array                             │
│    └─ Transition session status: READY → ONGOING                │
│                                                                   │
│  User in meeting_attendances table:                              │
│  - first_join_time = now()                                       │
│  - join_leave_cycles = [{joined_at: "...", left_at: null}]      │
│  - is_currently_in_meeting = true                                │
└────────────────────┬─────────────────────────────────────────────┘
                     │
                     │ [During meeting]
                     │ - Participants join/leave
                     │ - Each join/leave creates cycle entry
                     │ - Reconnections within 2 min merge cycles
                     │ - Auto-close stale cycles after 30 min
                     │
                     ▼
┌──────────────────────────────────────────────────────────────────┐
│                    MEETING ONGOING                                │
│               status = ONGOING                                    │
│         Participants in and out as needed                         │
│                                                                   │
│  Each Participant Join:                                          │
│  - recordJoin() → adds join time to cycles                       │
│  - join_count++                                                  │
│                                                                   │
│  Each Participant Leave:                                         │
│  - recordLeave() → marks left_at in last cycle                   │
│  - Calculates cycle duration_minutes                              │
│  - Updates total_duration_minutes                                │
│  - leave_count++                                                 │
└────────────────────┬─────────────────────────────────────────────┘
                     │
                     │ [Scheduled end time reached]
                     │ OR [Manual: endMeeting() API called]
                     │ OR [Cleanup: meetings:cleanup-expired]
                     │
                     ▼
┌──────────────────────────────────────────────────────────────────┐
│                   MEETING ENDS                                    │
│                                                                   │
│  LiveKitService::endMeeting()                                    │
│    ├─ Disconnect all participants from room                      │
│    ├─ Delete LiveKit room                                        │
│    └─ Remove session persistence (if set)                        │
│                                                                   │
│  Session status → COMPLETED (or ABSENT for no-shows)             │
│  ended_at = now()                                                │
└────────────────────┬─────────────────────────────────────────────┘
                     │
                     │ [After meeting ends]
                     │ Calculate final attendance
                     │
                     ▼
┌──────────────────────────────────────────────────────────────────┐
│              ATTENDANCE CALCULATION                                │
│                                                                   │
│  For each MeetingAttendance record:                              │
│                                                                   │
│  1. Auto-close any stale cycles (>30 min without leave)          │
│                                                                   │
│  2. Calculate attendance_percentage:                              │
│     = (total_duration_minutes / session_duration_minutes) * 100  │
│                                                                   │
│  3. Determine attendance_status:                                 │
│     if never_joined → 'absent'                                   │
│     if percentage < 30% → 'absent'                               │
│     if percentage 30-79% → 'partial'                             │
│     if percentage >= 80%:                                        │
│       ├─ if joined before grace_period → 'present'              │
│       └─ if joined after grace_period → 'late'                  │
│                                                                   │
│  4. Set is_calculated = true                                     │
│                                                                   │
│  attendance_calculated_at = now()                                │
│                                                                   │
└────────────────────┬─────────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────────┐
│                  SESSION COMPLETED                                │
│           All attendance data finalized                           │
│           Ready for reporting and analysis                        │
└──────────────────────────────────────────────────────────────────┘
```

---

## 3. Automatic Meeting Creation & Cleanup Flow

```
┌─────────────────────────────────────────────────────────────────┐
│         Laravel Task Scheduler (app/Console/Kernel.php)           │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Every 5-10 minutes:                                      │   │
│  │ php artisan meetings:create-scheduled                   │   │
│  └───────────────┬───────────────────────────────────────────┘   │
│                  │                                                │
│                  ▼                                                │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Every 30 minutes:                                        │   │
│  │ php artisan meetings:cleanup-expired                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
└──────────────────────────────────────────────────────────────────┘


┌──────────────────────────────────────────────────────────────────┐
│            CREATE SCHEDULED MEETINGS PROCESS                      │
│                                                                   │
│  CreateScheduledMeetingsCommand::handle()                        │
│  └─ AutoMeetingCreationService::createMeetingsForAllAcademies() │
│                                                                   │
│     For each active Academy:                                      │
│     │                                                             │
│     ├─ Get VideoSettings for academy                              │
│     │  ├─ auto_create_meetings = true? (if false, skip)          │
│     │  └─ meeting_creation_time_start/end (time window)          │
│     │                                                             │
│     ├─ Get eligible sessions:                                     │
│     │  └─ WHERE status = SCHEDULED                               │
│     │  └─ AND meeting_room_name IS NULL                          │
│     │  └─ AND scheduled_at BETWEEN now AND now+2hours           │
│     │  └─ AND time within allowed window                         │
│     │  └─ AND day not in blocked_days                            │
│     │                                                             │
│     └─ For each eligible session:                                │
│        │                                                          │
│        ├─ Is it time to create? (based on creation_minutes)     │
│        │                                                          │
│        ├─ Build meeting options:                                 │
│        │  ├─ max_participants (from academy or teacher settings) │
│        │  ├─ recording_enabled (from settings)                   │
│        │  ├─ session_type (quran/academic)                       │
│        │  └─ max_duration (from session.duration_minutes)        │
│        │                                                          │
│        ├─ Call session.generateMeetingLink($options)             │
│        │  └─ LiveKitService::createMeeting()                     │
│        │     └─ Generate room name (deterministic)               │
│        │     └─ Create LiveKit room                              │
│        │     └─ Return meeting_data                              │
│        │                                                          │
│        ├─ Update session:                                        │
│        │  ├─ meeting_room_name = "..."                           │
│        │  ├─ meeting_link = "..."                                │
│        │  ├─ meeting_auto_generated = true                       │
│        │  ├─ meeting_created_at = now()                          │
│        │  └─ status = READY (if configured)                      │
│        │                                                          │
│        └─ Log success/failure                                    │
│                                                                   │
│     ┌─ Return results:                                           │
│     │  ├─ meetings_created (count)                               │
│     │  ├─ meetings_failed (count)                                │
│     │  ├─ sessions_processed (count)                             │
│     │  └─ errors (array of failures)                             │
│     │                                                             │
└──────────────────────────────────────────────────────────────────┘


┌──────────────────────────────────────────────────────────────────┐
│           CLEANUP EXPIRED MEETINGS PROCESS                        │
│                                                                   │
│  CleanupExpiredMeetingsCommand::handle()                         │
│  └─ AutoMeetingCreationService::cleanupExpiredMeetings()         │
│                                                                   │
│     Get sessions to cleanup:                                      │
│     └─ WHERE meeting_room_name IS NOT NULL                       │
│     └─ AND status IN (SCHEDULED, ONGOING)                        │
│     └─ AND scheduled_at IS NOT NULL                              │
│     └─ AND now >= (scheduled_at + duration + buffer)             │
│     └─ AND academy.auto_end_meetings = true                      │
│                                                                   │
│     For each expired session:                                     │
│     │                                                             │
│     ├─ Call session.endMeeting()                                  │
│     │  └─ LiveKitService::endMeeting()                           │
│     │     ├─ List all participants                               │
│     │     ├─ Remove each participant                             │
│     │     └─ Delete room                                         │
│     │                                                             │
│     ├─ Update session status:                                    │
│     │  ├─ status = COMPLETED (or ABSENT if no attendees)        │
│     │  └─ meeting_ended_at = now()                               │
│     │                                                             │
│     ├─ Calculate final attendance                                │
│     │  (MeetingAttendanceService::calculateFinalAttendance)      │
│     │                                                             │
│     └─ Log cleanup                                               │
│                                                                   │
│     ┌─ Return results:                                           │
│     │  ├─ sessions_checked (count)                               │
│     │  ├─ meetings_ended (count)                                 │
│     │  ├─ meetings_failed_to_end (count)                         │
│     │  └─ errors (array of failures)                             │
│     │                                                             │
└──────────────────────────────────────────────────────────────────┘
```

---

## 4. Token Generation & Access Flow

```
┌────────────────────────────────────┐
│    User Requests to Join Session   │
│                                    │
│   GET /api/meetings/{id}/token     │
└────────────────┬───────────────────┘
                 │
                 ▼
┌────────────────────────────────────┐
│   Controller: getParticipantToken()│
│                                    │
│   ├─ Find session by ID             │
│   ├─ Verify authorization           │
│   │  └─ canJoinSession(user)        │
│   └─ Check meeting exists           │
│      └─ meeting_room_name not null  │
└────────────────┬───────────────────┘
                 │
                 ▼
┌────────────────────────────────────┐
│  session.generateParticipantToken()│
│  (BaseSession method)              │
│                                    │
│  ├─ Get LiveKitService             │
│  ├─ Check meeting_room_name exists │
│  └─ Call service method            │
└────────────────┬───────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────────────┐
│  LiveKitService::generateParticipantToken()            │
│                                                        │
│  ├─ Build participant identity:                       │
│  │  "{user_id}_{slugified_name}"                      │
│  │  e.g., "123_ahmed-muhammad"                         │
│  │                                                     │
│  ├─ Build metadata JSON:                              │
│  │  {                                                  │
│  │    "name": "أحمد محمد",                              │
│  │    "role": "teacher" or "student",                  │
│  │    "user_id": 123                                   │
│  │  }                                                  │
│  │                                                     │
│  ├─ Create AccessTokenOptions:                        │
│  │  ├─ setIdentity(participant_identity)              │
│  │  ├─ setMetadata(metadata_json)                     │
│  │  └─ setTtl(3600 * 3) // 3 hours                    │
│  │                                                     │
│  ├─ Create VideoGrant permissions:                    │
│  │  ├─ setRoomJoin()                                  │
│  │  ├─ setRoomName(meeting_room_name)                 │
│  │  ├─ setCanPublish(true/false)                      │
│  │  ├─ setCanSubscribe(true/false)                    │
│  │  └─ setRoomAdmin() [for teachers/admins only]      │
│  │                                                     │
│  ├─ Generate JWT token:                               │
│  │  AccessToken($apiKey, $apiSecret)                  │
│  │    ->init($tokenOptions)                           │
│  │    ->setGrant($videoGrant)                         │
│  │    ->toJwt()                                       │
│  │                                                     │
│  └─ Return JWT token string                           │
└────────────────┬───────────────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────┐
│    Return Response to Client       │
│                                    │
│  {                                  │
│    "success": true,                 │
│    "data": {                        │
│      "access_token": "eyJh...",    │
│      "server_url": "wss://...",    │
│      "room_name": "academy-...",   │
│      "participant_identity": "...", │
│      "permissions": {...},          │
│      "expires_at": "2025-11-12..."  │
│    }                                │
│  }                                  │
└────────────────┬───────────────────┘
                 │
                 ▼
┌────────────────────────────────────┐
│    Client-Side:                    │
│    LiveKit Browser SDK             │
│                                    │
│  connect(server_url, access_token) │
│    ├─ Connect to WebSocket         │
│    ├─ Authenticate with JWT        │
│    ├─ Join room (room_name)         │
│    └─ Establish media streams       │
└────────────────────────────────────┘
```

---

## 5. Attendance Tracking Flow

```
┌─────────────────────────────────────┐
│   User Joins Meeting via LiveKit    │
│        (client-side event)          │
└──────────────┬──────────────────────┘
               │
               │ WebSocket/HTTP callback
               ▼
┌──────────────────────────────────────────────────┐
│  MeetingAttendanceController                     │
│  (or via webhook/data channel)                   │
│                                                  │
│  POST /api/meeting/join                         │
│  {                                               │
│    "session_id": 42,                             │
│    "user_id": 123,                               │
│    "session_type": "individual"                 │
│  }                                               │
└──────────────┬───────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────┐
│  MeetingAttendanceService::handleUserJoin()      │
│                                                  │
│  ├─ Find or create MeetingAttendance:            │
│  │  MeetingAttendance::findOrCreateForUser()    │
│  │    └─ firstOrCreate([session_id, user_id])  │
│  │       with defaults:                         │
│  │       - user_type: student/teacher           │
│  │       - session_type: individual/group       │
│  │       - join_leave_cycles: []                │
│  │       - join_count: 0                        │
│  │       - leave_count: 0                       │
│  │       - total_duration_minutes: 0            │
│  │       - is_calculated: false                 │
│  │                                              │
│  ├─ Call recordJoin() on attendance:            │
│  │  ├─ Check if already in meeting              │
│  │  │  (has open cycle with joined_at only)    │
│  │  ├─ If not, add new cycle:                   │
│  │  │  {                                         │
│  │  │    "joined_at": "2025-11-12T10:00:00Z",  │
│  │  │    "left_at": null                        │
│  │  │  }                                         │
│  │  ├─ Set first_join_time = now() (if first)  │
│  │  └─ Increment join_count                     │
│  │                                              │
│  ├─ Transition session status:                  │
│  │  if session.status == READY:                 │
│  │    └─ SessionStatusService::transitionToOngoing()
│  │       └─ session.status = ONGOING            │
│  │                                              │
│  └─ Log join event                              │
└──────────────┬───────────────────────────────────┘
               │
               │ [Time passes - participant active]
               │
               ▼
┌──────────────────────────────────────────────────┐
│   User Leaves Meeting                           │
│      (disconnect, browser close, etc.)          │
└──────────────┬───────────────────────────────────┘
               │
               │ WebSocket/HTTP callback
               ▼
┌──────────────────────────────────────────────────┐
│  MeetingAttendanceController                     │
│                                                  │
│  POST /api/meeting/leave                        │
│  {                                               │
│    "session_id": 42,                             │
│    "user_id": 123                                │
│  }                                               │
└──────────────┬───────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────┐
│  MeetingAttendanceService::handleUserLeave()     │
│                                                  │
│  ├─ Find MeetingAttendance record                │
│  │                                              │
│  ├─ Call recordLeave() on attendance:            │
│  │  ├─ Find last open cycle (joined but no left)│
│  │  ├─ If found:                                │
│  │  │  ├─ Set left_at = now()                   │
│  │  │  ├─ Calculate cycle duration:             │
│  │  │  │  duration = diffInMinutes(left_at -   │
│  │  │  │             joined_at)                │
│  │  │  ├─ Update join_leave_cycles              │
│  │  │  ├─ Set last_leave_time = now()          │
│  │  │  ├─ Increment leave_count                 │
│  │  │  └─ Recalculate total_duration_minutes:  │
│  │  │     sum all cycle duration_minutes       │
│  │  └─ Else: log warning (leave without join)   │
│  │                                              │
│  └─ Log leave event                             │
└──────────────┬───────────────────────────────────┘
               │
               │ [Rejoins possible - cycles continue]
               │ [Reconnection within 2 min merges]
               │
               ▼
┌──────────────────────────────────────────────────┐
│      Meeting Ends (Scheduled or Manual)          │
│                                                  │
│   OR Session.endMeeting() called                │
│   OR Cleanup timeout triggered                  │
└──────────────┬───────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────┐
│  Auto-Close Stale Cycles                        │
│  (MeetingAttendance::autoCloseStaleCycles)      │
│                                                  │
│  For each unclosed cycle:                       │
│    if joined_at > 30 minutes ago:               │
│      ├─ Estimate leave_at = joined_at + 30min  │
│      ├─ Mark cycle as closed                    │
│      ├─ Set duration_minutes = 30               │
│      └─ Log auto-closure                        │
└──────────────┬───────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────┐
│  Calculate Final Attendance                     │
│  (MeetingAttendance::calculateFinalAttendance) │
│                                                  │
│  For each MeetingAttendance.is_calculated==false:
│                                                  │
│  1. Get session duration from circle config     │
│     grace_minutes = circle.late_join_grace ...  │
│     session_duration = session.duration_minutes │
│                                                 │
│  2. Calculate attendance_percentage:            │
│     % = (total_duration_minutes /               │
│          session_duration_minutes) * 100        │
│                                                 │
│  3. Determine attendance_status:                │
│     ├─ if never joined: 'absent'               │
│     ├─ if % < 30: 'absent'                     │
│     ├─ if % < 80: 'partial'                    │
│     └─ if % >= 80:                             │
│        ├─ if first_join < grace period:        │
│        │  'present'                             │
│        └─ else: 'late'                          │
│                                                 │
│  4. Update attendance record:                   │
│     ├─ is_calculated = true                    │
│     ├─ attendance_status = determined status   │
│     ├─ attendance_percentage = calculated %    │
│     ├─ session_duration_minutes = from config  │
│     ├─ session_start_time = session.scheduled_ │
│     ├─ session_end_time = start + duration    │
│     └─ attendance_calculated_at = now()        │
│                                                  │
│  5. Log calculation                             │
└──────────────┬───────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────┐
│   Final Attendance Data Available               │
│                                                  │
│   Example Record:                               │
│   {                                              │
│     "session_id": 42,                            │
│     "user_id": 123,                              │
│     "first_join_time": "2025-11-12T10:00:00",  │
│     "last_leave_time": "2025-11-12T10:45:00",  │
│     "total_duration_minutes": 42,               │
│     "attendance_status": "present",              │
│     "attendance_percentage": 87.5,              │
│     "join_count": 1,                            │
│     "leave_count": 1,                           │
│     "is_calculated": true                       │
│   }                                              │
│                                                  │
│   Ready for:                                     │
│   - Reporting & Analytics                       │
│   - Grade Calculation                           │
│   - Compliance Records                          │
│   - Student Reports                             │
└──────────────────────────────────────────────────┘
```

---

## 6. Authorization & Permission Matrix

```
┌─────────────────────────────────────────────────────────────┐
│               MEETING ACCESS PERMISSIONS                     │
└─────────────────────────────────────────────────────────────┘

USER TYPE         CREATE MEETING    JOIN MEETING    MANAGE MEETING
────────────────────────────────────────────────────────────────
Super Admin       ✓ All sessions    ✓ All sessions  ✓ All sessions
                  
Academy Admin      ✓ Their academy  ✓ Their acad.   ✓ Their acad.
                  
Supervisor        ✓ Their academy  ✓ Their acad.   ✓ Their acad.
                  
Teacher           ✓ Own sessions   ✓ Any session   ✓ Own sessions
(quran_teacher,   
academic_teacher) 
                  
Student           ✗ Cannot create  ✓ Enrolled      ✗ Cannot manage
                                    sessions only   
                  
Parent            ✗ Cannot create  ✓ Children's    ✗ Cannot manage
                                    sessions only


CREATE MEETING PERMISSIONS:
├─ Super Admin:       All sessions in all academies
├─ Academy Admin:     Sessions in their academy only
├─ Supervisor:        Sessions in their academy only
└─ Teachers:          Own sessions only

JOIN MEETING PERMISSIONS:
├─ Super Admin:       All sessions
├─ Admins/Teachers:   All sessions
└─ Students:          Only if:
                      ├─ Direct student (student_id match)
                      ├─ Circle member (for group sessions)
                      ├─ Subscription holder
                      ├─ Individual lesson participant
                      └─ Interactive course enrollee

MANAGE MEETING (end, moderate, etc.):
├─ Super Admin:       All sessions
├─ Academy Admin:     Sessions in their academy
├─ Supervisor:        Sessions in their academy
└─ Teachers:          Own sessions only


┌─────────────────────────────────────────────────────────────┐
│             TOKEN PERMISSION MATRIX                          │
└─────────────────────────────────────────────────────────────┘

ROLE          can_publish  can_subscribe  room_admin  comment
────────────────────────────────────────────────────────────────
Teacher       true         true           true       Full control
Student       true         true           false      Can pub/sub
Guest         false        true           false      View-only*

* Not currently used; all verified users can publish

Room Admin Permissions:
  ├─ Remove participants
  ├─ Modify room settings  
  ├─ Kick participants
  ├─ Control recording
  └─ Manage permissions


┌─────────────────────────────────────────────────────────────┐
│             QURAN SESSION SPECIFICS                          │
└─────────────────────────────────────────────────────────────┘

Session Type          Join Allowed If
────────────────────────────────────
Individual Circle     └─ student_id matches OR is teacher
Group Circle          └─ Member of circle OR is teacher
Subscription Session  └─ Subscription holder OR is teacher


┌─────────────────────────────────────────────────────────────┐
│            ACADEMIC SESSION SPECIFICS                        │
└─────────────────────────────────────────────────────────────┘

Session Type            Join Allowed If
────────────────────────────────────────
Individual Lesson       └─ Student in lesson OR is teacher
Interactive Course      └─ Enrolled in course OR is teacher
Group Class             └─ Enrolled in class OR is teacher
```

---

## Summary of Key Interactions

```
CLIENT                CONTROLLER              SERVICE              LIVEKIT
───────────────────────────────────────────────────────────────────────────

                                                                   
Create Meeting:                                                   
──────────────                                                    
  │  POST /api/meetings/create                                   
  ├──────────────────────────>  LiveKitMeetingController         
  │                              ├─ Validate request            
  │                              ├─ Check authorization        
  │                              └─> Call Session.generateMeetingLink()
  │                                  └─> LiveKitService        
  │                                      ├─ Generate room name  
  │                                      └─────────────────────> createRoom()
  │                                      ←───── room_data ──────
  │                                      └─ Update session
  │  ←── Meeting URL & room_name ──────                          
  

Get Access Token:                                                 
─────────────────                                                
  │  GET /api/meetings/{id}/token                               
  ├──────────────────────────>  LiveKitMeetingController         
  │                              ├─ Check authorization         
  │                              └─> Call Session.generateParticipantToken()
  │                                  └─> LiveKitService        
  │                                      ├─ Build identity      
  │                                      ├─ Build metadata      
  │                                      ├─ Create JWT          
  │                                      └─────> sign token
  │                                      ←──── JWT ────
  │  ←── JWT Token ─────────────                                
  

Join Room:                                                       
──────────                                                       
  │  [Client uses JWT to connect]                               
  ├──────────────────────────────────────────────────────────> WebSocket
  │                                                             connect
  │                                                             ├─ auth
  │                                                             └─ join
  │  [User in room, media streaming]                           
  │                                                             
  │  [Async: notify server of join]                            
  │  POST /api/meeting/join                                    
  ├──────────────────────────>  MeetingAttendanceController     
  │                              └─> MeetingAttendanceService  
  │                                  ├─ Find/create record    
  │                                  ├─ recordJoin()           
  │                                  └─ Update DB             
  

Leave Room:                                                      
──────────                                                       
  │  [User leaves/disconnects]                                  
  │  ────────────────────────────────────────────────────> emit leave event
  │                                                             
  │  POST /api/meeting/leave                                   
  ├──────────────────────────>  MeetingAttendanceController     
  │                              └─> MeetingAttendanceService  
  │                                  ├─ Find record            
  │                                  ├─ recordLeave()          
  │                                  └─ Update DB             
  

End Meeting:                                                     
────────────                                                    
  │  POST /api/meetings/{id}/end                               
  ├──────────────────────────>  LiveKitMeetingController        
  │                              └─> Session.endMeeting()      
  │                                  └─> LiveKitService        
  │                                      ├─ listParticipants() ─>
  │                                      ├─ removeParticipant() ─>
  │                                      └─ deleteRoom() ─────>
  │                                      ←──── success ────
  │                                      ├─ Update session
  │                                      └─ Calculate attendance
  │  ←── Success response ──────                               
```

---

These diagrams provide a visual understanding of:
1. Overall system architecture and data flow
2. Meeting lifecycle from creation to completion
3. Automatic scheduling and cleanup processes
4. Token generation and access control
5. Attendance tracking mechanics
6. Authorization and permission matrix
7. Component interactions

