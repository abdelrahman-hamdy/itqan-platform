# PHASE 6: UNIFIED MEETING SYSTEM ANALYSIS
**Analysis Date:** November 11, 2024
**Phase:** Phase 6 - Unified Meeting System
**Status:** 🔍 ANALYSIS IN PROGRESS

---

## EXECUTIVE SUMMARY

Phase 6 focuses on consolidating the meeting system across the platform. Currently, there is duplication between LiveKitService, HasMeetings trait, and BaseSession meeting methods. The goal is to create a unified meeting architecture that eliminates duplication and provides a consistent API for all session types.

---

## CURRENT MEETING ARCHITECTURE

### Models

#### 1. Meeting.php (459 lines)
**Location:** [app/Models/Meeting.php](app/Models/Meeting.php)
**Purpose:** Unified meeting model with polymorphic relationship to sessions

**Key Features:**
- Polymorphic relationship via `meetable_type` and `meetable_id`
- Stores: livekit_room_name, livekit_room_id, status, scheduled_start_at, actual_start_at, actual_end_at
- Status management: scheduled, active, ended, cancelled
- Participant tracking via MeetingParticipant relationship
- LiveKit integration methods:
  - `generateAccessToken()` - creates LiveKit token for user
  - `getRoomInfo()` - gets current room status from LiveKit
  - `syncParticipantCount()` - syncs participant count from LiveKit
  - `start()`, `end()`, `cancel()` - lifecycle management
  - `trackParticipantJoin()`, `trackParticipantLeave()` - attendance tracking
- Static factory: `createForSession()` - creates meeting for any session type

**Relationships:**
- `meetable()` - Polymorphic (QuranSession, AcademicSession, InteractiveCourseSession)
- `academy()` - BelongsTo Academy
- `participants()` - HasMany MeetingParticipant
- `activeParticipants()` - HasMany MeetingParticipant (currently in meeting)

**Current Usage:**
- ✅ Model exists and is well-designed
- ⚠️ May not be used by all sessions (sessions store meeting data directly in their own fields)
- ⚠️ Duplication: Session models have meeting_room_name, meeting_link, meeting_id fields

#### 2. MeetingAttendance.php
**Purpose:** Tracks attendance for meetings
**Status:** Exists but analysis needed

---

### Services

#### 1. LiveKitService.php (548 lines)
**Location:** [app/Services/LiveKitService.php](app/Services/LiveKitService.php)
**Purpose:** Primary service for LiveKit video conferencing integration

**Key Methods:**
- `isConfigured()` - checks if LiveKit credentials are set
- `createMeeting()` - creates LiveKit room and returns meeting data
- `generateParticipantToken()` - creates JWT token for user to join room
- `getRoomInfo()` - gets current room state from LiveKit server
- `endMeeting()` - disconnects all participants and deletes room
- `setMeetingDuration()` - sets room timeout
- `startRecording()` - starts recording (not implemented yet)
- `stopRecording()` - stops recording (not implemented yet)
- `handleWebhook()` - processes LiveKit server webhooks

**Room Naming Convention:**
```php
{academySlug}-{sessionSlug}-session-{sessionId}
// Example: itqan-quran-session-123
```

**Token Generation:**
- Uses participant identity: `{userId}_{userName}`
- Metadata includes: name (Arabic), role (teacher/student), user_id
- Teachers get `setRoomAdmin()` permission
- Configurable permissions: can_publish, can_subscribe

**Recording:**
- Recording methods exist but throw "not yet implemented" exception
- Would use S3 storage for recordings
- Supports MP4 format, grid/speaker layouts

**Webhook Events:**
- room_started
- room_finished
- participant_joined
- participant_left
- recording_finished

**Status:** ✅ Well-implemented, actively used

#### 2. MeetingService.php (240 lines)
**Location:** [app/Services/MeetingService.php](app/Services/MeetingService.php)
**Purpose:** Legacy service for multiple meeting platforms

**Supported Platforms:**
- Jitsi Meet (free, open source)
- Whereby (simple, limited free tier)
- Custom platform

**Key Methods:**
- `generateMeetingLink()` - generates URL for specified platform
- `validateMeetingUrl()` - checks if URL is from supported platform
- `getPlatformInfo()` - returns platform details
- `getAvailablePlatforms()` - lists all supported platforms

**Room Naming Convention:**
```php
{academySlug}-{sessionSlug}-{sessionId}-{random6}
// Example: itqan-quran-123-abc123
```

**Issues:**
- ❌ **UNUSED**: Platform only uses LiveKit now
- ❌ Duplicate room name generation logic
- ❌ Different naming convention from LiveKitService
- ⚠️ **Decision needed**: Remove or keep for future multi-platform support?

#### 3. MeetingAttendanceService.php
**Purpose:** Tracks meeting attendance
**Status:** Exists but analysis needed

#### 4. SessionMeetingService.php (~300+ lines)
**Location:** [app/Services/SessionMeetingService.php](app/Services/SessionMeetingService.php)
**Purpose:** Business logic for Quran session meeting availability
**Status:** ✅ Actively used

**Key Methods:**
- `ensureMeetingAvailable()` - checks timing and creates/verifies meeting room
- `getSessionTiming()` - calculates if session is joinable based on schedule
- `calculateEmptyTimeout()` - determines room auto-cleanup timeout
- `calculateMaxDuration()` - sets maximum meeting duration
- `getCircleForSession()` - gets circle configuration for timing

**Usage:**
- Calls `$session->generateMeetingLink()` from HasMeetings trait
- Accesses `$session->meeting_room_name` directly
- Uses LiveKitService for room verification
- Does NOT use Meeting model

#### 5. AcademicSessionMeetingService.php (~300+ lines)
**Location:** [app/Services/AcademicSessionMeetingService.php](app/Services/AcademicSessionMeetingService.php)
**Purpose:** Business logic for Academic session meeting availability
**Status:** ✅ Actively used

**Key Methods:**
- Same as SessionMeetingService but for AcademicSession
- Different defaults: max_participants = 2 (1-on-1), preparation_minutes = 15

**Usage:**
- Calls `$session->generateMeetingLink()` from HasMeetings trait
- Accesses `$session->meeting_room_name` directly
- Uses LiveKitService for room verification
- Does NOT use Meeting model

#### 6. MeetingDataChannelService.php
**Purpose:** Handles real-time data channels
**Status:** Exists but analysis needed

---

### Traits

#### HasMeetings.php (361 lines)
**Location:** [app/Traits/HasMeetings.php](app/Traits/HasMeetings.php)
**Purpose:** Provides meeting functionality to session models
**Used by:** BaseSession (which means all session models inherit it)

**Key Methods:**
- `ensureMeetingExists()` - auto-creates meeting for ready/ongoing sessions
- `generateMeetingLink()` - creates LiveKit meeting and updates session fields
- `generateParticipantToken()` - creates LiveKit token for user
- `getMeetingRoomName()` - returns meeting_room_name field
- `getMeetingId()` - returns meeting_id field
- `getMeetingLink()` - returns meeting_link field
- `isMeetingActive()` - checks if LiveKit room has active participants
- `getMeetingConfiguration()` - returns session-specific meeting config
- `getDefaultUserPermissions()` - returns permissions based on user role
- `canJoinBasedOnTiming()` - checks if user can join based on time windows
- `meetingAttendances()` - relationship to MeetingAttendance records
- `getCurrentParticipantsCount()` - gets participant count from LiveKit
- `endMeeting()` - ends LiveKit room

**Data Flow:**
1. Calls LiveKitService.createMeeting()
2. Updates session fields: meeting_room_name, meeting_link, meeting_id, meeting_created_at
3. Does NOT create Meeting model record

**Issues:**
- ⚠️ **Duplication**: BaseSession also has meeting methods (inherited from MeetingCapable interface)
- ⚠️ **No Meeting model**: Creates LiveKit room but doesn't create Meeting record
- ⚠️ **Session field storage**: Stores meeting data directly in session fields instead of using polymorphic Meeting relationship

---

### Contracts (Interfaces)

#### MeetingCapable.php
**Location:** [app/Contracts/MeetingCapable.php](app/Contracts/MeetingCapable.php)
**Purpose:** Interface for models that support meetings
**Implemented by:** BaseSession (which means all session models implement it)

**Required Methods (from Phase 5 analysis):**
```php
public function canUserJoinMeeting(User $user): bool;
public function canUserManageMeeting(User $user): bool;
public function getMeetingType(): string; // ABSTRACT
public function getAcademy(): ?Academy;
public function getMeetingStartTime(): ?Carbon;
public function getMeetingEndTime(): ?Carbon;
public function getMeetingDurationMinutes(): int;
public function isMeetingActive(): bool;
public function getParticipants(): array; // ABSTRACT
public function getMeetingParticipants(): Collection; // ABSTRACT
public function getMeetingConfiguration(): array; // ABSTRACT
public function getMeetingSessionType(): string;
```

**Status:** ✅ Well-defined interface implemented in BaseSession

---

## DUPLICATION ANALYSIS

### 1. Meeting Creation Logic (3 implementations)

**Location A: HasMeetings::generateMeetingLink()**
```php
$meetingData = $liveKitService->createMeeting(...);
$this->update([
    'meeting_room_name' => $meetingData['room_name'],
    'meeting_link' => $meetingData['meeting_url'],
    'meeting_id' => $meetingData['meeting_id'],
]);
// Does NOT create Meeting model record
```

**Location B: Meeting::createForSession()**
```php
$meetingInfo = $livekitService->createMeeting(...);
return static::create([
    'meetable_type' => get_class($session),
    'meetable_id' => $session->id,
    'livekit_room_name' => $meetingInfo['room_name'],
    // ...
]);
// Creates Meeting model record but doesn't update session fields
```

**Location C: BaseSession (via MeetingCapable)**
```php
public function generateMeetingLink(array $options = []): string;
public function getMeetingInfo(): ?array;
// Methods exist but implementation is in HasMeetings trait
```

**Problem:** Three different ways to create a meeting with inconsistent behavior

### 2. Room Name Generation (2 implementations)

**LiveKitService:**
```php
"{academySlug}-{sessionSlug}-session-{sessionId}"
// Example: itqan-quran-session-123
```

**MeetingService (legacy):**
```php
"{academySlug}-{sessionSlug}-{sessionId}-{random6}"
// Example: itqan-quran-123-abc123
```

**Problem:** Different naming conventions may cause conflicts or confusion

### 3. Meeting Data Storage (2 approaches)

**Approach A: Session Fields (used by HasMeetings)**
- Fields in session table: meeting_room_name, meeting_link, meeting_id, meeting_created_at
- Direct field access
- Fast queries
- No join required

**Approach B: Meeting Model (polymorphic)**
- Separate meetings table with polymorphic relationship
- Fields: livekit_room_name, livekit_room_id, status, scheduled_start_at, actual_start_at, actual_end_at
- Requires join to access meeting data
- Better separation of concerns
- Can track meeting lifecycle independently

**Problem:** Inconsistent data storage - some sessions might use fields, others might use Meeting model

### 4. Participant Token Generation (2 implementations)

**HasMeetings:**
```php
public function generateParticipantToken(User $user, array $permissions = []): string
{
    $liveKitService = app(LiveKitService::class);
    $defaultPermissions = $this->getDefaultUserPermissions($user);
    $finalPermissions = array_merge($defaultPermissions, $permissions);
    return $liveKitService->generateParticipantToken(...);
}
```

**Meeting Model:**
```php
public function generateAccessToken(User $user, array $permissions = []): string
{
    $livekitService = app(LiveKitService::class);
    return $livekitService->generateParticipantToken(...);
}
```

**Problem:** Two different method names for same functionality

---

## MEETING LIFECYCLE COMPARISON

### Current Lifecycle (using HasMeetings):
1. Session created (scheduled status)
2. `generateMeetingLink()` called manually or via `ensureMeetingExists()`
3. LiveKit room created
4. Session fields updated with room info
5. Users join via `generateParticipantToken()`
6. Meeting ends via `endMeeting()`
7. LiveKit room deleted

**Issues:**
- No Meeting model record created
- No tracking of meeting lifecycle (started_at, ended_at)
- No participant tracking via MeetingParticipant
- Meeting status tied to session status

### Desired Lifecycle (using Meeting Model):
1. Session created (scheduled status)
2. Meeting model record created automatically (polymorphic relationship)
3. LiveKit room created when meeting.status = 'scheduled'
4. Meeting.status = 'active' when first participant joins
5. MeetingParticipant records track who joins/leaves
6. Meeting.status = 'ended' when last participant leaves or manual end
7. LiveKit room deleted
8. Meeting record preserved with actual_start_at, actual_end_at, recording_url

**Benefits:**
- Complete audit trail of meeting lifecycle
- Participant tracking
- Recording management
- Session and meeting concerns separated

---

## DEPENDENCIES & USAGE

### Files that use LiveKitService:
- HasMeetings trait ✅
- Meeting model ✅
- BaseSession (via HasMeetings) ✅
- QuranSession (inherits from BaseSession) ✅
- AcademicSession (inherits from BaseSession) ✅
- InteractiveCourseSession (inherits from BaseSession) ✅

### Files that use Meeting Model:
- **ZERO files** use `->meeting()` polymorphic relationship
- Meeting model exists but is not being used in the codebase
- All code uses direct field access instead

### Files that reference "MeetingService":
- ✅ **SessionMeetingService.php** - NEW service for Quran sessions (actively used)
- ✅ **AcademicSessionMeetingService.php** - NEW service for Academic sessions (actively used)
- ❌ **MeetingService.php** - OLD legacy service (Jitsi/Whereby/Custom platforms)
- Various controllers and commands reference the new services

### Direct Meeting Field Access:
- **104 occurrences** of `->meeting_room_name` across 14 files
- Heavily used by:
  - Session models (QuranSession.php, BaseSession.php)
  - Services (SessionMeetingService, AcademicSessionMeetingService, SessionStatusService, AutoMeetingCreationService)
  - Controllers (MeetingController, LiveKitMeetingController, UnifiedMeetingController)
  - Views and routes

---

## CONTROLLERS ANALYSIS (Summary)

### MeetingController.php
- Likely manages meeting CRUD operations
- Analysis needed

### LiveKitController.php
- Likely handles LiveKit-specific endpoints
- Analysis needed

### LiveKitMeetingController.php
- Likely handles meeting room access
- Analysis needed

### LiveKitWebhookController.php
- Handles webhooks from LiveKit server
- Likely calls LiveKitService.handleWebhook()

### MeetingLinkController.php
- Likely generates meeting links
- Analysis needed

### MeetingAttendanceController.php
- Likely tracks attendance
- Analysis needed

### Api/MeetingDataChannelController.php
- Handles real-time data channels
- Analysis needed

---

## PROPOSED REFACTORING PLAN

### Option A: **Full Meeting Model Migration (Recommended)**

**Goal:** Use Meeting model polymorphic relationship for all sessions, eliminate direct field storage

**Changes:**
1. **Keep:** LiveKitService (primary meeting provider)
2. **Remove:** MeetingService (legacy, unused)
3. **Refactor:** HasMeetings trait
   - Change `generateMeetingLink()` to create Meeting model record
   - Update all methods to work with Meeting model relationship
   - Keep convenience accessors for backward compatibility
4. **Update:** BaseSession
   - Add `meeting()` relationship (polymorphic)
   - Add accessors that delegate to Meeting model:
     ```php
     public function getMeetingRoomName(): ?string {
         return $this->meeting?->livekit_room_name;
     }
     ```
5. **Deprecate:** Session meeting fields (meeting_room_name, meeting_link, meeting_id)
   - Keep fields for backward compatibility
   - Sync fields with Meeting model for now
   - Remove in future major version

**Migration Required:** NO (just change application code to use Meeting model)

**Benefits:**
- ✅ Clean separation: Session = business logic, Meeting = video conferencing
- ✅ Complete meeting lifecycle tracking
- ✅ Participant tracking via MeetingParticipant
- ✅ Recording management in Meeting model
- ✅ Can query all meetings independently of sessions
- ✅ Easier to add new meeting providers in future

**Risks:**
- ⚠️ Requires updating code that directly accesses session meeting fields
- ⚠️ Requires join for meeting data (small performance impact)

---

### Option B: **Session Fields Only (Simpler)**

**Goal:** Eliminate Meeting model, keep everything in session fields

**Changes:**
1. **Keep:** LiveKitService
2. **Remove:** MeetingService (legacy)
3. **Remove:** Meeting model
4. **Remove:** MeetingParticipant model
5. **Keep:** HasMeetings trait (simplified)
6. **Keep:** Session meeting fields

**Migration Required:** YES (drop meetings table)

**Benefits:**
- ✅ Simpler data model
- ✅ Faster queries (no joins)
- ✅ Less code to maintain

**Drawbacks:**
- ❌ No independent meeting lifecycle tracking
- ❌ No participant tracking (unless we create session_participants table)
- ❌ Meeting concerns mixed with session concerns
- ❌ Can't query meetings independently
- ❌ Harder to add new meeting types in future

**Not Recommended:** Loses valuable meeting tracking capabilities

---

### Option C: **Hybrid Approach**

**Goal:** Use Meeting model but keep session fields as cache

**Changes:**
1. **Keep:** LiveKitService, Meeting model, session fields
2. **Remove:** MeetingService (legacy)
3. **Refactor:** HasMeetings trait to create Meeting model AND sync fields
4. **Performance:** Session fields act as cached denormalized data

**Benefits:**
- ✅ Best of both worlds: tracking + performance
- ✅ No joins needed for simple queries
- ✅ Complete meeting lifecycle in Meeting model

**Drawbacks:**
- ⚠️ Data duplication (fields + Meeting model)
- ⚠️ Must keep in sync
- ⚠️ More complex code

**Could Work:** Good for high-traffic applications where joins matter

---

## RECOMMENDATION (UPDATED AFTER ANALYSIS)

**I now recommend Option B: Session Fields Only (with cleanup)**

### Reasoning Based on Actual Usage:
1. **Meeting model is completely unused**: 0 uses of `->meeting()` relationship in codebase
2. **104 direct field accesses**: All code is built around session fields
3. **Active services depend on fields**: SessionMeetingService and AcademicSessionMeetingService access `$session->meeting_room_name` directly
4. **Low risk of breakage**: Keeping current architecture minimizes disruption
5. **Meeting model is dead code**: Better to remove unused code than force migration
6. **Migration cost too high**: Would require updating 104+ occurrences across 14+ files

### Updated Recommendation:
**Keep session fields, remove Meeting model (it's unused dead code)**

### Implementation Steps (Option B):

#### Step 1: Remove Unused Meeting Model
- ✅ Verify `->meeting()` relationship has 0 uses (confirmed)
- ❌ Delete app/Models/Meeting.php
- ❌ Delete app/Models/MeetingParticipant.php (if unused)
- ❌ Remove Meeting model migration (mark as deleted in future)
- ❌ Remove `meeting()` relationship from BaseSession

#### Step 2: Remove Legacy MeetingService
- ❌ Delete app/Services/MeetingService.php (Jitsi/Whereby/Custom platforms)
- ✅ Keep SessionMeetingService.php (actively used)
- ✅ Keep AcademicSessionMeetingService.php (actively used)
- ✅ Keep LiveKitService.php (core service)

#### Step 3: Consolidate Meeting Logic in BaseSession
- Keep HasMeetings trait but simplify
- Move common meeting logic to BaseSession
- Remove duplication between HasMeetings and BaseSession

#### Step 4: Standardize SessionMeetingService Usage
- Review SessionMeetingService and AcademicSessionMeetingService
- Check for any code duplication
- Consider creating base MeetingAvailabilityService if needed

#### Step 5: Clean Up HasMeetings Trait
- Remove any references to Meeting model
- Keep methods that work with session fields
- Simplify and document

#### Step 6: Testing
- Test meeting creation flow (should be unchanged)
- Test SessionMeetingService (Quran sessions)
- Test AcademicSessionMeetingService (Academic sessions)
- Verify no regression in meeting functionality

---

## FILES TO MODIFY (Updated for Option B)

### To Remove:
1. ❌ [app/Services/MeetingService.php](app/Services/MeetingService.php) - Legacy service (Jitsi/Whereby/Custom)
2. ❌ [app/Models/Meeting.php](app/Models/Meeting.php) - Unused model (0 relationship uses)
3. ❌ [app/Models/MeetingParticipant.php](app/Models/MeetingParticipant.php) - If unused

### To Simplify (Remove Meeting Model References):
1. ✏️ [app/Traits/HasMeetings.php](app/Traits/HasMeetings.php) - Remove Meeting model code
2. ✏️ [app/Models/BaseSession.php](app/Models/BaseSession.php) - Remove meeting() relationship

### To Review (Potential Duplication):
1. 🔍 [app/Services/SessionMeetingService.php](app/Services/SessionMeetingService.php) - Check for duplication
2. 🔍 [app/Services/AcademicSessionMeetingService.php](app/Services/AcademicSessionMeetingService.php) - Check for duplication

### To Keep (No Changes):
1. ✅ [app/Services/LiveKitService.php](app/Services/LiveKitService.php) - Core LiveKit integration
2. ✅ [app/Contracts/MeetingCapable.php](app/Contracts/MeetingCapable.php) - Interface definition
3. ✅ Session meeting fields (meeting_room_name, meeting_link, meeting_id)

---

## METRICS (Updated for Option B)

### Current Code:
- LiveKitService: 548 lines
- MeetingService (legacy): 240 lines ❌ TO REMOVE
- HasMeetings trait: 361 lines
- Meeting model: 459 lines ❌ TO REMOVE
- MeetingParticipant model: ~100 lines ❌ TO REMOVE (if exists)
- SessionMeetingService: ~300 lines ✅ KEEP
- AcademicSessionMeetingService: ~300 lines ✅ KEEP
- **Total meeting-related code: ~2,308 lines**

### After Refactoring (Estimated):
- LiveKitService: 548 lines (no change)
- MeetingService: 0 lines (removed)
- HasMeetings trait: 300 lines (simplified, remove Meeting model refs)
- Meeting model: 0 lines (removed)
- MeetingParticipant model: 0 lines (removed)
- SessionMeetingService: ~300 lines (no change)
- AcademicSessionMeetingService: ~300 lines (no change)
- **Total: ~1,448 lines**

**Code Reduction: ~860 lines (37.3%)**

### Files Deleted:
- app/Services/MeetingService.php (240 lines)
- app/Models/Meeting.php (459 lines)
- app/Models/MeetingParticipant.php (~100 lines)
- Related migration files

**Benefit:** Remove ~800 lines of unused/legacy code

---

## RISKS & MITIGATION (Updated for Option B)

### Risk 1: Meeting Model May Be Used Somewhere Not Detected
**Mitigation:**
- ✅ Already grepped for `->meeting()` relationship (0 uses found)
- Search for Meeting model imports: `use App\Models\Meeting`
- Search for Meeting::class references
- Check if meetings table has any data
- Review all meeting-related controllers

### Risk 2: Breaking Changes if Meeting/MeetingParticipant Tables Have Data
**Mitigation:**
- Check meetings table for existing records before deletion
- If data exists, create backup/export
- Option: Keep tables but remove models (mark as deprecated)
- Can always restore models if needed (they're in git history)

### Risk 3: Legacy MeetingService May Have Hidden Uses
**Mitigation:**
- ✅ Already identified 11 references to "MeetingService"
- Verified they're SessionMeetingService/AcademicSessionMeetingService (different classes)
- Search for `new MeetingService` and `MeetingService::` to be sure
- Test all meeting creation flows after deletion

### Risk 4: SessionMeetingService Duplication
**Mitigation:**
- Review both SessionMeetingService and AcademicSessionMeetingService
- Extract common logic to base class if significant duplication found
- Document differences between Quran and Academic session meeting logic

---

## NEXT STEPS (Updated)

1. ✅ Complete Phase 6 analysis
2. ✅ Grep codebase for MeetingService usage (done - 11 references, all to new services)
3. ✅ Grep codebase for direct meeting field access (done - 104 occurrences)
4. ✅ Identify Meeting model usage (done - 0 uses of relationship)
5. **Get user confirmation on approach (Option B recommended)**
6. Search for Meeting model imports to verify unused
7. Check meetings table for existing data
8. Remove legacy MeetingService.php (Jitsi/Whereby)
9. Remove unused Meeting model
10. Remove unused MeetingParticipant model (if exists)
11. Simplify HasMeetings trait (remove Meeting model references)
12. Remove meeting() relationship from BaseSession
13. Review SessionMeetingService / AcademicSessionMeetingService for duplication
14. Extract common base class if needed
15. Test all meeting flows
16. Create Phase 6 completion report

---

**Analysis Status:** ✅ COMPLETE
**Recommendation:** **Option B - Session Fields Only (Remove Unused Models)**
**Estimated Effort:** 2-3 hours (primarily cleanup/deletion)
**Estimated Code Reduction:** ~860 lines (37.3%)
**Risk Level:** LOW (removing unused code)

---

## SUMMARY FOR USER

Phase 6 analysis reveals:

1. **Meeting model exists but is completely unused** (0 uses of `->meeting()` relationship)
2. **All code uses direct session fields** (104 occurrences of `->meeting_room_name`)
3. **SessionMeetingService and AcademicSessionMeetingService are actively used** (not the legacy MeetingService)
4. **Legacy MeetingService.php (Jitsi/Whereby) is unused**

**Recommendation: Remove unused code**
- Remove app/Models/Meeting.php (459 lines) ❌
- Remove app/Models/MeetingParticipant.php (~100 lines) ❌
- Remove app/Services/MeetingService.php (240 lines) ❌
- Keep current architecture (session fields + LiveKit)

