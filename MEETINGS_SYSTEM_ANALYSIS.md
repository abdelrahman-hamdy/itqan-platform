# Meetings Management System - Comprehensive Analysis

## Executive Summary

The Itqan Platform implements a sophisticated, multi-layered meetings management system built on **LiveKit** for video conferencing. The system supports multiple session types (Quran, Academic, Interactive Courses) with automatic meeting creation, lifecycle management, attendance tracking, and comprehensive cleanup mechanisms.

---

## 1. MEETING CREATION & MANAGEMENT

### 1.1 Core Meeting Lifecycle

**Meeting Flow:**
```
Session Created → Scheduled → Ready → Ongoing → Completed → Cleanup
```

### 1.2 Meeting Generation Methods

#### Primary Methods: `BaseSession` Model

**File:** `/app/Models/BaseSession.php`

```php
// Core meeting generation (lines 302-343)
public function generateMeetingLink(array $options = []): string
```

**Functionality:**
- Creates meeting via LiveKit service
- Checks for existing valid meetings (prevents recreation)
- Merges default options with custom parameters
- Updates session with meeting metadata
- Returns meeting URL

**Key Fields Set:**
- `meeting_link` - URL for joining
- `meeting_id` - Unique meeting identifier
- `meeting_platform` - 'livekit'
- `meeting_source` - Platform name
- `meeting_data` - Complete meeting configuration
- `meeting_room_name` - LiveKit room identifier
- `meeting_expires_at` - Expiration timestamp

#### Automatic Meeting Creation Service

**File:** `/app/Services/AutoMeetingCreationService.php`

**Main Methods:**
1. `createMeetingsForAllAcademies()` - Process all active academies
2. `createMeetingsForAcademy(Academy $academy)` - Academy-specific creation
3. `getEligibleSessions(Academy $academy, VideoSettings $videoSettings)` - Filter sessions needing meetings

**Eligibility Criteria:**
- Academy has auto-creation enabled
- Session is in SCHEDULED status
- No meeting room exists (`meeting_room_name` is null)
- Session scheduled within 2-hour window
- Current time is past meeting creation time (per VideoSettings)
- Time and day restrictions satisfied

**Creation Logic (lines 155-197):**
```php
// Look-ahead window: 2 hours
$startTime = now()
$endTime = now()->addHours(2)

// Filter by:
// 1. Status = SCHEDULED
// 2. No meeting_room_name
// 3. Scheduled between start/end
// 4. Time-based eligibility
// 5. Day-based eligibility
```

### 1.3 Meeting Options Configuration

**Default Options (lines 253-279):**
```php
$options = [
    'max_participants' => academy_setting or 50,
    'recording_enabled' => academy_setting or false,
    'session_type' => 'quran' or 'academic',
    'max_duration' => session duration or 120,
]
```

**Teacher-Specific Overrides:**
If teacher settings exist:
- Max participants
- Recording enabled flag
- Video quality
- Audio quality
- Screen sharing toggle
- Chat toggle
- Mute on join
- Theme preference

---

## 2. LIVEKIT INTEGRATION

### 2.1 LiveKit Service Architecture

**File:** `/app/Services/LiveKitService.php`

### 2.2 Core Components

#### Configuration (lines 26-43)
```php
$this->apiKey = config('livekit.api_key')
$this->apiSecret = config('livekit.api_secret')
$this->serverUrl = config('livekit.server_url') // wss://
$this->apiUrl = config('livekit.api_url') // https:// for backend
```

#### Room Service Client
- Uses `RoomServiceClient` from Agence104\LiveKit SDK
- Initialized only with valid credentials
- `isConfigured()` method validates readiness

### 2.3 Key LiveKit Methods

#### 1. Create Room (lines 59-179)
```php
public function createMeeting(
    Academy $academy,
    string $sessionType,
    int $sessionId,
    Carbon $startTime,
    array $options = []
): array
```

**Process:**
1. Generate deterministic room name: `{academy-slug}-{session-type}-session-{id}`
2. Check if room already exists (prevent recreation)
3. Create room options object with settings
4. Call `$roomService->createRoom($roomOptions)`
5. Return comprehensive meeting data structure

**Room Options Configuration (lines 480-492):**
```php
RoomCreateOptions::class
  ->setName($roomName)
  ->setMaxParticipants(50)
  ->setEmptyTimeout(300) // 5 minutes
  ->setMetadata([
      'created_by' => 'itqan_platform',
      'session_type' => 'quran',
      'recording_enabled' => false,
      'created_at' => now()->toISOString(),
  ])
```

#### 2. Generate Access Token (lines 184-236)
```php
public function generateParticipantToken(
    string $roomName,
    User $user,
    array $permissions = []
): string
```

**Process:**
1. Create participant identity: `{user_id}_{slugified_name}`
2. Build metadata with name, role, user_id
3. Configure access token options (3-hour TTL)
4. Set video grant permissions:
   - `canPublish` - Default true
   - `canSubscribe` - Default true
   - `roomAdmin` - For teachers/admins
5. Return JWT token

**Metadata Example:**
```json
{
  "name": "أحمد محمد",
  "role": "teacher",
  "user_id": 123
}
```

#### 3. Get Room Information (lines 304-370)
```php
public function getRoomInfo(string $roomName): ?array
```

**Returns:**
```php
[
    'room_name' => string,
    'room_sid' => string,
    'participant_count' => int,
    'created_at' => Carbon,
    'participants' => [
        [
            'id' => identity,
            'name' => display_name,
            'joined_at' => Carbon,
            'is_publisher' => bool,
        ]
    ],
    'is_active' => bool,
]
```

#### 4. End Meeting (lines 375-401)
```php
public function endMeeting(string $roomName): bool
```

**Process:**
1. List all participants in room
2. Remove each participant individually
3. Delete the room
4. Log completion

#### 5. Recording Management (lines 241-299)

**Current Status:** Not fully implemented in SDK
- Logs requests for debugging
- Throws exception (requires LiveKit Dashboard)
- S3 bucket configuration available:
  - `livekit.recording.s3_bucket`
  - `livekit.recording.s3_region`

### 2.4 Webhook Handling (lines 431-461)

**Supported Events:**
- `room_started` - Room created
- `room_finished` - Room ended
- `participant_joined` - User joined
- `participant_left` - User left
- `recording_finished` - Recording complete

**Handler Structure:**
```php
public function handleWebhook(array $webhookData): void
{
    switch ($event) {
        case 'room_started':
            $this->handleRoomStarted($webhookData);
        case 'room_finished':
            $this->handleRoomFinished($webhookData);
        // ... etc
    }
}
```

**Current Implementation:** Webhook handlers are stubbed (lines 518-546)
- `handleRoomStarted()` - Update session status
- `handleRoomFinished()` - Mark completed, calculate duration
- `handleParticipantJoined()` - Track attendance
- `handleParticipantLeft()` - Update attendance
- `handleRecordingFinished()` - Process recordings

---

## 3. MEETING ROOMS & TOKENS

### 3.1 Room Naming Convention

**Deterministic Formula (lines 465-472):**
```php
private function generateRoomName(
    Academy $academy,
    string $sessionType,
    int $sessionId
): string
{
    $academySlug = Str::slug($academy->subdomain); // e.g., 'itqan-academy'
    $sessionSlug = Str::slug($sessionType);         // e.g., 'quran'
    return "{$academySlug}-{$sessionSlug}-session-{$sessionId}";
    // Result: 'itqan-academy-quran-session-42'
}
```

**Benefits:**
- No timestamps (prevents recreation attempts)
- Session-ID-based (enables reuse)
- Academy-scoped (multi-tenant support)
- Easily parseable

### 3.2 Token Structure & Permissions

**Token Payload:**
```php
AccessTokenOptions
  ->setIdentity("{user_id}_{slugified_name}")
  ->setMetadata([
      'name' => 'أحمد محمد',
      'role' => 'teacher',
      'user_id' => 123,
  ])
  ->setTtl(3600 * 3); // 3 hours

VideoGrant
  ->setRoomJoin()
  ->setRoomName($roomName)
  ->setCanPublish(true)
  ->setCanSubscribe(true)
  ->setRoomAdmin() // For teachers/admins only
```

**Role-Based Permissions (lines 209-212):**
```php
if ($this->isTeacher($user) || $this->isAdmin($user)) {
    $videoGrant->setRoomAdmin(); // Full control
}
```

### 3.3 Room Lifecycle Timing

**Empty Timeout (lines 320-336):**
```php
private function calculateEmptyTimeout(QuranSession $session): int
{
    // If scheduled, keep room alive until session end + 30 minutes
    $sessionEnd = $session->scheduled_at->addMinutes($session->duration_minutes ?? 60);
    $minutesUntilEnd = now()->diffInMinutes($sessionEnd);
    
    if ($minutesUntilEnd > 0) {
        return ($minutesUntilEnd + 30) * 60; // Convert to seconds
    }
    
    return 30 * 60; // Default: 30 minutes
}
```

**Max Duration (lines 341-347):**
```php
// Session duration + 1 hour buffer for overtime
return ($baseDuration + 60) * 60; // Convert to seconds
```

---

## 4. AUTOMATIC MEETING CREATION & SCHEDULING

### 4.1 Automatic Creation Service

**File:** `/app/Services/AutoMeetingCreationService.php`

**Main Process Flow:**

```
Schedule (every N minutes)
    ↓
CreateScheduledMeetingsCommand::handle()
    ↓
AutoMeetingCreationService::createMeetingsForAllAcademies()
    ↓
For Each Academy:
    - Check if auto-creation enabled (VideoSettings)
    - Get eligible sessions (within 2-hour window)
    - For each session:
        - Create meeting (generateMeetingLink)
        - Mark meeting_auto_generated = true
        - Record meeting_created_at
    ↓
Return results (created, failed counts)
```

### 4.2 Scheduling & Timing Controls

**Configuration:** `VideoSettings` Model

**Key Controls:**
- `auto_create_meetings` - Enable/disable auto-creation
- `meeting_creation_minutes_before` - When to create (before scheduled time)
- `meeting_creation_time_start` - Daily time window start
- `meeting_creation_time_end` - Daily time window end
- `meeting_creation_blocked_days` - Days to skip (JSON array)

**Timing Logic (lines 180-186):**
```php
$createAt = $videoSettings->getMeetingCreationTime($scheduledAt);
return now()->gte($createAt); // Create if current time >= creation time
```

### 4.3 Meeting Creation Command

**File:** `/app/Console/Commands/CreateScheduledMeetingsCommand.php`

**Usage:**
```bash
# Process all academies
php artisan meetings:create-scheduled

# Process specific academy
php artisan meetings:create-scheduled --academy-id=1

# Dry run (preview what would be created)
php artisan meetings:create-scheduled --dry-run -v

# Verbose output
php artisan meetings:create-scheduled -v
```

**Output Example:**
```
🎥 Starting automatic meeting creation process...
📅 Current time: 2025-11-12 10:30:00
🌍 Processing all active academies...

📊 Overall Results:
  • Academies processed: 5
  • Total sessions processed: 23
  • Total meetings created: 20
  • Total meetings failed: 0

📈 System Statistics:
  • Total auto-generated meetings: 245
  • Active meetings: 18
  • Meetings created today: 23
  • Meetings created this week: 156
  • Academies with auto-creation enabled: 5

⚡ Process completed in 2.43 seconds
✅ Meeting creation process completed successfully
```

### 4.4 Statistics & Monitoring (lines 370-385)

```php
public function getStatistics(): array
{
    return [
        'total_auto_generated_meetings' => QuranSession::where('meeting_auto_generated', true)->count(),
        'active_meetings' => QuranSession::whereNotNull('meeting_room_name')
            ->whereIn('status', [SessionStatus::SCHEDULED, SessionStatus::ONGOING])
            ->count(),
        'meetings_created_today' => QuranSession::where('meeting_auto_generated', true)
            ->whereDate('meeting_created_at', today())
            ->count(),
        'meetings_created_this_week' => QuranSession::where('meeting_auto_generated', true)
            ->whereBetween('meeting_created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count(),
        'academies_with_auto_creation_enabled' => VideoSettings::where('auto_create_meetings', true)->count(),
    ];
}
```

---

## 5. MEETING LIFECYCLE & CLEANUP

### 5.1 Session Status Transitions

**Status Enum:** `SessionStatus`

```
SCHEDULED
    ↓ (meeting room created)
READY
    ↓ (first participant joins)
ONGOING
    ↓ (scheduled end time passed)
COMPLETED
    ↓ (optional)
CANCELLED/ABSENT
```

### 5.2 Automatic Cleanup Service

**File:** `/app/Services/AutoMeetingCreationService.php`

**Cleanup Method (lines 284-365):**
```php
public function cleanupExpiredMeetings(): array
```

**Eligibility for Cleanup:**
1. Meeting room exists (`meeting_room_name` not null)
2. Status is SCHEDULED or ONGOING
3. Session has scheduled time
4. Current time >= scheduled_end_time + buffer
5. Academy auto_end_meetings is enabled

**Cleanup Actions:**
1. End meeting on LiveKit server
2. Update session status to COMPLETED
3. Log cleanup activity

**Results Return:**
```php
[
    'sessions_checked' => int,
    'meetings_ended' => int,
    'meetings_failed_to_end' => int,
    'errors' => array,
]
```

### 5.3 Cleanup Command

**File:** `/app/Console/Commands/CleanupExpiredMeetingsCommand.php`

**Usage:**
```bash
# Perform actual cleanup
php artisan meetings:cleanup-expired

# Dry run (preview what would be cleaned)
php artisan meetings:cleanup-expired --dry-run -v

# Verbose output
php artisan meetings:cleanup-expired -v
```

**Output Example:**
```
🧹 Starting expired meetings cleanup process...
📅 Current time: 2025-11-12 10:30:00

📊 Cleanup Results:
  • Sessions checked: 156
  • Meetings ended: 23
  • Meetings failed to end: 0

📈 System Statistics After Cleanup:
  • Total auto-generated meetings: 245
  • Active meetings: 2
  • Meetings created today: 23
  • Meetings created this week: 156

⚡ Cleanup completed in 1.24 seconds
✅ Meeting cleanup process completed successfully
```

### 5.4 Session Persistence & Grace Periods

**File:** `/app/Services/SessionMeetingService.php`

**Purpose:** Keep meetings alive even if teacher disconnects

**Methods:**
```php
// Mark session as persistent
markSessionPersistent(QuranSession $session, ?int $durationMinutes = null)

// Check if should persist
shouldSessionPersist(QuranSession $session): bool

// Get persistence info
getSessionPersistenceInfo(QuranSession $session): ?array

// Remove persistence
removeSessionPersistence(QuranSession $session)
```

**Persistence Duration:**
```php
$duration = $durationMinutes ?? $session->duration_minutes ?? 60;
$expirationMinutes = $duration + 30; // Session duration + 30 minute grace

Cache::put(
    "session_meeting:{$session->id}:persistence",
    [...],
    now()->addMinutes($expirationMinutes)
);
```

### 5.5 Session Processing & Auto-Completion

**Main Processing Method (lines 166-241):**
```php
public function processScheduledSessions(): array
```

**Actions:**
1. Create meetings for sessions starting within 15 minutes
2. Update status of active sessions to ONGOING
3. Clean up expired sessions (2+ hours past end time)

**Result:**
```php
[
    'started' => int,      // Meetings auto-created
    'updated' => int,      // Status updated to ONGOING
    'cleaned' => int,      // Expired sessions cleaned
    'errors' => int,       // Failures encountered
]
```

---

## 6. MEETING ATTENDANCE TRACKING

### 6.1 Meeting Attendance Model

**File:** `/app/Models/MeetingAttendance.php`

**Database Table:** `meeting_attendances`

**Key Fields:**
```php
// Identification
- session_id        // FK to session (quran_sessions or academic_sessions)
- user_id          // FK to users
- user_type        // 'student' | 'teacher' | 'supervisor'
- session_type     // 'individual' | 'group' | 'academic'

// Timing
- first_join_time       // When user first joined
- last_leave_time       // When user last left
- total_duration_minutes // Total time in meeting
- join_leave_cycles     // JSON array of join/leave events

// Calculation
- attendance_calculated_at  // When final calculation done
- attendance_status        // 'present' | 'absent' | 'late' | 'partial'
- attendance_percentage    // Decimal(5,2)
- is_calculated           // Boolean flag

// Session Metadata
- session_duration_minutes
- session_start_time
- session_end_time
- join_count              // Number of times joined
- leave_count             // Number of times left
```

**Indices for Performance:**
```sql
UNIQUE (session_id, user_id)
INDEX (session_id, attendance_status)
INDEX (user_id, session_type)
INDEX (attendance_calculated_at, is_calculated)
INDEX (first_join_time, last_leave_time)
```

### 6.2 Attendance Service

**File:** `/app/Services/MeetingAttendanceService.php`

**Key Methods:**

#### 1. Handle User Join (lines 24-60)
```php
public function handleUserJoin(MeetingCapable $session, User $user): bool
```

- Create or retrieve attendance record
- Record join event with timestamp
- Transition session to ONGOING if READY

#### 2. Handle User Leave (lines 65-107)
```php
public function handleUserLeave(MeetingCapable $session, User $user): bool
```

- Find attendance record
- Record leave event
- Calculate duration since join

#### 3. Calculate Final Attendance (lines 214-271)
```php
public function calculateFinalAttendance(MeetingCapable $session): array
```

**Calculation Logic:**
1. Get all meeting attendances for session
2. For each uncalculated attendance:
   - Call `calculateFinalAttendance()` on record
   - Determines status (present, absent, late, partial)
   - Calculates percentage of session attended
3. Update session participants_count

**Status Determination (lines 267-293):**
```php
private function determineAttendanceStatus(
    Carbon $sessionStartTime,
    int $sessionDuration,
    int $graceMinutes  // e.g., 15 minutes
): string
{
    if (!$this->first_join_time) return 'absent';
    
    $percentage = ($this->total_duration_minutes / $sessionDuration) * 100;
    
    if ($percentage < 30) return 'absent';
    if ($percentage < 80) return 'partial';
    
    // 80%+ attendance: check if late
    $lateThreshold = $sessionStartTime->copy()->addMinutes($graceMinutes);
    return $this->first_join_time->isAfter($lateThreshold) ? 'late' : 'present';
}
```

#### 4. Reconnection Detection (lines 310-350)
```php
public function handleReconnection(MeetingCapable $session, User $user): bool
```

- Check if user left < 2 minutes ago
- Merge with last cycle if reconnecting
- Prevents breaking join/leave cycles

#### 5. Attendance Statistics (lines 355-375)
```php
public function getAttendanceStatistics(MeetingCapable $session): array
```

**Returns:**
```php
[
    'total_participants' => int,
    'present' => int,
    'late' => int,
    'partial' => int,
    'absent' => int,
    'average_attendance_percentage' => float,
    'total_meeting_duration' => int,
]
```

#### 6. Export Attendance Data (lines 420-439)
```php
public function exportAttendanceData(MeetingCapable $session): array
```

Returns array of attendance records with full details

### 6.3 Automatic Cycle Closure

**Auto-Close Stale Cycles (lines 312-349):**

Problem: Participants may lose connection without sending explicit leave event

Solution:
```php
private function autoCloseStaleCycles(): void
{
    foreach ($cycles as $index => $cycle) {
        if (isset($cycle['joined_at']) && !isset($cycle['left_at'])) {
            $joinTime = Carbon::parse($cycle['joined_at']);
            
            // If join was 30+ minutes ago, auto-close
            if ($joinTime->diffInMinutes(now()) > 30) {
                $cycles[$index]['left_at'] = $joinTime->copy()->addMinutes(30)->toISOString();
                $cycles[$index]['duration_minutes'] = 30;
                $hasChanges = true;
                
                Log::info('Auto-closed stale attendance cycle', [
                    'session_id' => $this->session_id,
                    'join_time' => $joinTime,
                ]);
            }
        }
    }
}
```

### 6.4 Join/Leave Cycle Structure

**Join/Leave Cycles JSON:**
```json
[
  {
    "joined_at": "2025-11-12T10:00:00+00:00",
    "left_at": "2025-11-12T10:30:00+00:00",
    "duration_minutes": 30
  },
  {
    "joined_at": "2025-11-12T10:31:00+00:00",
    "left_at": null  // Still in meeting
  }
]
```

---

## 7. CONTROLLERS & API ENDPOINTS

### 7.1 LiveKit Meeting Controller

**File:** `/app/Http/Controllers/LiveKitMeetingController.php`

#### Endpoints:

##### 1. Create Meeting
```
POST /api/meetings/create
POST /sessions/{sessionId}/create-meeting
```

**Parameters:**
```php
- session_id (required) - Session to create meeting for
- max_participants (optional) - Default 50
- recording_enabled (optional) - Default false
- max_duration (optional) - Default 120 minutes
```

**Validation:**
- Session must exist
- User must have permission (teacher, admin, supervisor)
- Meeting must not already exist

**Response:**
```json
{
  "success": true,
  "data": {
    "session_id": 42,
    "meeting_url": "https://example.com/meeting/academy-quran-session-42",
    "room_name": "academy-quran-session-42",
    "meeting_id": "academy-quran-session-42",
    "platform": "livekit",
    "created_at": "2025-11-12T10:00:00Z"
  }
}
```

##### 2. Get Participant Token
```
GET /api/meetings/{sessionId}/token
```

**Parameters:**
```php
- session_type (optional) - 'quran' | 'academic'
- can_publish (optional) - Default true
- can_subscribe (optional) - Default true
```

**Authorization:** User must be able to join session

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJhbGc...",
    "server_url": "wss://livekit.example.com",
    "room_name": "academy-quran-session-42",
    "participant_identity": "123_ahmed-muhammad",
    "permissions": {
      "can_publish": true,
      "can_subscribe": true
    },
    "expires_at": "2025-11-12T13:00:00Z"
  }
}
```

##### 3. Get Room Info
```
GET /api/meetings/{sessionId}/info
```

**Returns:**
- Room status and participant count
- List of current participants
- Room created timestamp
- Activity status

**Fallback:** If room not on server, attempts recreation and provides database fallback

##### 4. End Meeting
```
POST /api/meetings/{sessionId}/end
```

**Actions:**
- Disconnects all participants
- Deletes LiveKit room
- Updates session status
- Logs termination

#### Authorization Methods

**canManageSession()** (lines 288-311):
- Super admin → Can manage any session
- Academy admin → Can manage in their academy
- Supervisor → Can manage in their academy
- Teachers → Can manage their own sessions

**canJoinSession()** (lines 325-421):
- Teachers/admins → Can join any session
- Students → Can join if:
  - Direct assignee (student_id)
  - Circle member
  - Subscription holder
  - Individual circle/lesson participant
- Parents → Can join children's sessions

### 7.2 Unified Meeting Controller

**File:** `/app/Http/Controllers/UnifiedMeetingController.php`

Provides API endpoints for meeting operations:
- POST `/api/meeting/create`
- POST `/api/meeting/token`
- GET `/api/meeting/info`
- POST `/api/meeting/end`
- POST `/api/meeting/leave` - Record user departure

---

## 8. DATA CHANNEL SERVICE

### 8.1 Real-Time Communication

**File:** `/app/Services/MeetingDataChannelService.php`

**Purpose:** Handle teacher commands, participant state sync, real-time messaging

**Key Features:**

#### Teacher Control Commands
```php
sendTeacherControlCommand(
    QuranSession $session,
    User $teacher,
    string $command,
    array $data = [],
    array $targetParticipants = []
): array
```

**Command Topics:**
- `teacher_controls` - Control commands
- `participant_management` - Add/remove participants
- `hand_raising` - Request to speak
- `session_announcements` - Broadcast messages
- `system_notifications` - System events

#### Multi-Channel Delivery (lines 82-98)
Ensures reliability through redundancy:
1. **Primary:** LiveKit Data Channel
2. **Secondary:** WebSocket (Pusher/Broadcasting)
3. **Tertiary:** Database polling fallback
4. **Quaternary:** Server-Sent Events (SSE)

#### State Management
- Persistent command state with cache
- Message retry mechanism (up to 3 attempts)
- Acknowledgment tracking for critical commands
- Priority-based message ordering

---

## 9. DATABASE SCHEMA

### 9.1 Meeting Fields on Sessions Tables

**Base Session Tables:**
- `quran_sessions`
- `academic_sessions`
- `interactive_course_sessions`

**Meeting Columns:**
```sql
-- Meeting Identification
meeting_link VARCHAR(255)           -- URL to join meeting
meeting_id VARCHAR(255)             -- LiveKit meeting identifier
meeting_password VARCHAR(255)       -- Optional password
meeting_source VARCHAR(50)          -- Platform (livekit)
meeting_platform VARCHAR(50)        -- Platform variant
meeting_data JSON                   -- Complete meeting configuration

-- Room Management
meeting_room_name VARCHAR(255)      -- LiveKit room name
meeting_auto_generated BOOLEAN      -- Was auto-created
meeting_expires_at TIMESTAMP        -- When meeting expires

-- Attendance
attendance_status VARCHAR(50)       -- Attendance outcome
participants_count INT              -- Number of participants
attendance_notes TEXT               -- Additional notes
```

### 9.2 Meeting Attendances Table

```sql
CREATE TABLE meeting_attendances (
  id BIGINT PRIMARY KEY
  
  -- Relationships
  session_id BIGINT FOREIGN KEY → quran_sessions
  user_id BIGINT FOREIGN KEY → users
  
  -- Classification
  user_type ENUM('student', 'teacher', 'supervisor')
  session_type ENUM('individual', 'group')
  
  -- Timing
  first_join_time TIMESTAMP
  last_leave_time TIMESTAMP
  total_duration_minutes INT DEFAULT 0
  
  -- Cycle Tracking
  join_leave_cycles JSON
  
  -- Calculation
  attendance_calculated_at TIMESTAMP
  attendance_status ENUM('present', 'absent', 'late', 'partial')
  attendance_percentage DECIMAL(5,2)
  
  -- Session Details
  session_duration_minutes INT
  session_start_time TIMESTAMP
  session_end_time TIMESTAMP
  
  -- Counters
  join_count INT DEFAULT 0
  leave_count INT DEFAULT 0
  is_calculated BOOLEAN DEFAULT false
  
  -- Indices
  UNIQUE (session_id, user_id)
  INDEX (session_id, attendance_status)
  INDEX (user_id, session_type)
  INDEX (attendance_calculated_at, is_calculated)
  INDEX (first_join_time, last_leave_time)
);
```

### 9.3 Video Settings

**Configuration Model:** `VideoSettings`

**Key Settings:**
```php
auto_create_meetings BOOLEAN
auto_end_meetings BOOLEAN
meeting_creation_minutes_before INT
meeting_creation_time_start TIME
meeting_creation_time_end TIME
meeting_creation_blocked_days JSON
default_max_participants INT
enable_recording_by_default BOOLEAN
```

---

## 10. ROUTES & API STRUCTURE

### 10.1 Web Routes

```php
// Meeting creation
POST /sessions/{sessionId}/create-meeting
  → LiveKitMeetingController@createMeeting

// Old endpoint for compatibility
POST /meetings/{session}/create-or-get
  → MeetingController@createOrGet
```

### 10.2 API Routes

```php
// Meeting operations
POST /api/meeting/create              → UnifiedMeetingController@createMeeting
POST /api/meeting/token               → UnifiedMeetingController@getParticipantToken
GET  /api/meeting/info                → UnifiedMeetingController@getRoomInfo
POST /api/meeting/end                 → UnifiedMeetingController@endMeeting
POST /api/meeting/leave               → UnifiedMeetingController@recordLeave

// Detailed endpoints
POST /api/meetings/create
GET  /api/meetings/{sessionId}/token
POST /api/meetings/{sessionId}/recording/start
POST /api/meetings/{sessionId}/recording/stop
GET  /api/meetings/{sessionId}/info
POST /api/meetings/{sessionId}/end
```

---

## 11. CONSOLE COMMANDS

### 11.1 Create Scheduled Meetings Command

```bash
php artisan meetings:create-scheduled [OPTIONS]

Options:
  --academy-id=ID   Process only specific academy
  --dry-run         Preview without creating
  -v, --verbose     Detailed output
```

**Frequency:** Typically run every 5-10 minutes via scheduler

### 11.2 Cleanup Expired Meetings Command

```bash
php artisan meetings:cleanup-expired [OPTIONS]

Options:
  --dry-run     Preview without cleaning
  -v, --verbose Detailed output
```

**Frequency:** Typically run every 30 minutes via scheduler

---

## 12. CONFIGURATION & ENVIRONMENT

### 12.1 LiveKit Configuration

**File:** `config/livekit.php`

```php
'api_key' => env('LIVEKIT_API_KEY')
'api_secret' => env('LIVEKIT_API_SECRET')
'server_url' => env('LIVEKIT_SERVER_URL') // wss://
'api_url' => env('LIVEKIT_API_URL')       // https://
```

**Environment Example:**
```
LIVEKIT_API_KEY=your-key-here
LIVEKIT_API_SECRET=your-secret-here
LIVEKIT_SERVER_URL=wss://livekit.example.com
LIVEKIT_API_URL=https://livekit.example.com
```

### 12.2 Recording Configuration

```php
'recording' => [
    's3_bucket' => env('LIVEKIT_RECORDING_S3_BUCKET'),
    's3_region' => env('LIVEKIT_RECORDING_S3_REGION'),
]
```

---

## 13. ARCHITECTURE PATTERNS

### 13.1 Service Layer Design

```
Controller (HTTP Interface)
    ↓
Controller validates request & auth
    ↓
Service Layer (Business Logic)
    ├── LiveKitService (API calls)
    ├── AutoMeetingCreationService (Scheduling)
    ├── SessionMeetingService (Quran-specific)
    ├── AcademicSessionMeetingService (Academic-specific)
    ├── MeetingAttendanceService (Attendance tracking)
    └── MeetingDataChannelService (Real-time comms)
    ↓
Model Layer (Data & Relationships)
    ├── BaseSession (Abstract parent)
    ├── QuranSession (Quran implementation)
    ├── AcademicSession (Academic implementation)
    └── MeetingAttendance (Attendance records)
    ↓
LiveKit API (External service)
```

### 13.2 Trait-Based Meeting Functionality

**Trait:** `HasMeetings`

Applied to:
- `BaseSession` (inherited by QuranSession, AcademicSession)

**Provides:**
- Meeting link generation
- Token generation
- Room information retrieval
- Participant management
- Meeting lifecycle

### 13.3 Interface-Based Design

**Interface:** `MeetingCapable`

Ensures implementations provide:
- `getAcademy()`
- `getMeetingStartTime()`
- `getMeetingEndTime()`
- `getMeetingDurationMinutes()`
- `isMeetingActive()`
- `getMeetingSessionType()`
- `getMeetingParticipants()`
- `meetingAttendances()` relationship

---

## 14. ERROR HANDLING & VALIDATION

### 14.1 Authorization Checks

**Meeting Creation:**
- Session must exist
- User must be teacher/admin/supervisor
- Session must not already have meeting

**Token Generation:**
- Session must exist
- User must have join permission
- Meeting must be created

**Room Access:**
- User must be participant or manager
- Session must be accessible

### 14.2 Graceful Degradation

**Room Recreation (lines 216-264):**
When room exists in DB but not on LiveKit server:
1. Attempt automatic recreation
2. If fails, provide fallback response
3. Return database state as last resort

**Fallback Response:**
```json
{
  "success": true,
  "room_name": "academy-quran-session-42",
  "data": {
    "room_name": "...",
    "participant_count": 0,
    "is_active": false,
    "fallback_mode": true
  }
}
```

### 14.3 Transaction Safety

Automatic meeting creation uses transactions (lines 204-243):
```php
DB::beginTransaction();
try {
    $session->generateMeetingLink($options);
    $session->update([...]);
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    throw $e;
}
```

---

## 15. PERFORMANCE CONSIDERATIONS

### 15.1 Database Indices

**Meeting Attendances Indices:**
- `(session_id, attendance_status)` - Status queries
- `(user_id, session_type)` - User attendance history
- `(attendance_calculated_at, is_calculated)` - Calculation status filtering
- `(first_join_time, last_leave_time)` - Timing range queries

### 15.2 Caching Strategy

**Session Persistence Cache:**
```php
// Key: session_meeting:{session_id}:persistence
// TTL: Session duration + 30 minutes
// Use: Keep meeting alive during teacher disconnects
```

### 15.3 Query Optimization

**Eligible Sessions Query (lines 170-179):**
- Filter by academy_id
- Filter by status
- Filter by null meeting_room_name
- Filter by scheduled_at range
- Use `with()` to eager load relationships

---

## 16. MULTI-TENANT SUPPORT

### 16.1 Academy Isolation

**Room Naming:**
```
{academy-subdomain}-{session-type}-session-{id}
```
Ensures rooms are academy-scoped

**Query Scope:**
Most operations filtered by academy_id

**API Authorization:**
- Academy admins can only see their academy's sessions
- Teachers can only see their own sessions
- Super admin can see all

### 16.2 Session Type Support

**Supported Types:**
1. **Quran Sessions** (QuranSession model)
   - Individual or group circles
   - Teacher-student sessions

2. **Academic Sessions** (AcademicSession model)
   - Classroom sessions
   - Individual lessons

3. **Interactive Courses** (InteractiveCourseSession model)
   - Multi-student course sessions

---

## 17. KEY FINDINGS & RECOMMENDATIONS

### 17.1 Current Implementation Status

**Fully Implemented:**
- Meeting creation and room management
- Access token generation
- Attendance tracking (join/leave cycles)
- Automatic meeting creation scheduler
- Meeting cleanup and expiration
- Multi-channel data communication

**Partially Implemented:**
- Recording functionality (stubbed, requires LiveKit Dashboard)
- Webhook handlers (stubbed, need implementation)

**Not Implemented:**
- Breakout rooms
- Whiteboard functionality
- Custom layouts/themes

### 17.2 Strengths

1. **Comprehensive Attendance Tracking**
   - Detailed cycle-based join/leave tracking
   - Automatic status determination
   - Reconnection detection
   - Grace period handling

2. **Flexible Scheduling**
   - Per-academy auto-creation settings
   - Time windows and blocked days
   - Look-ahead windows for optimal room creation
   - Automatic expiration and cleanup

3. **Robust Error Handling**
   - Graceful degradation when rooms unavailable
   - Transaction safety
   - Comprehensive logging
   - Dry-run modes for testing

4. **Multi-Channel Reliability**
   - Data channel + WebSocket + Database + SSE
   - Ensures message delivery
   - Retry mechanisms
   - Acknowledgment tracking

### 17.3 Areas for Enhancement

1. **Recording**
   - Implement proper recording lifecycle
   - S3 storage integration
   - Recording status tracking

2. **Webhooks**
   - Complete webhook handler implementations
   - Event broadcasting
   - Real-time status updates

3. **Advanced Features**
   - Breakout room support
   - Custom layouts
   - Advanced analytics
   - Screen share controls

4. **Performance**
   - Cache room info more aggressively
   - Batch operations for bulk meetings
   - WebSocket for real-time updates

---

## 18. SUMMARY TABLE

| Component | Location | Purpose |
|-----------|----------|---------|
| **Models** | |
| MeetingAttendance | app/Models/MeetingAttendance.php | Attendance records |
| BaseSession | app/Models/BaseSession.php | Common session functionality |
| QuranSession | app/Models/QuranSession.php | Quran-specific sessions |
| AcademicSession | app/Models/AcademicSession.php | Academic-specific sessions |
| | |
| **Services** | |
| LiveKitService | app/Services/LiveKitService.php | LiveKit API integration |
| AutoMeetingCreationService | app/Services/AutoMeetingCreationService.php | Automatic creation |
| SessionMeetingService | app/Services/SessionMeetingService.php | Quran meeting management |
| AcademicSessionMeetingService | app/Services/AcademicSessionMeetingService.php | Academic meeting management |
| MeetingAttendanceService | app/Services/MeetingAttendanceService.php | Attendance tracking |
| MeetingDataChannelService | app/Services/MeetingDataChannelService.php | Real-time communication |
| | |
| **Controllers** | |
| LiveKitMeetingController | app/Http/Controllers/LiveKitMeetingController.php | Meeting API endpoints |
| UnifiedMeetingController | app/Http/Controllers/UnifiedMeetingController.php | Unified API |
| | |
| **Commands** | |
| CreateScheduledMeetingsCommand | app/Console/Commands/CreateScheduledMeetingsCommand.php | Auto-create scheduler |
| CleanupExpiredMeetingsCommand | app/Console/Commands/CleanupExpiredMeetingsCommand.php | Expiration cleanup |
| | |
| **Migrations** | |
| Meeting Attendances | database/migrations/2025_08_28_001220_create_meeting_attendances_table.php | Attendance schema |
| Meeting Fields | database/migrations/2025_08_10_104402_add_meeting_fields_to_sessions_tables.php | Session meeting columns |

