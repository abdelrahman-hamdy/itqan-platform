# Server-Side Permission Enforcement for LiveKit Meetings

## 🎯 Overview

This implementation prevents students from re-enabling their microphone or camera when the teacher has disabled these permissions. It uses **server-side enforcement via LiveKit webhooks** combined with client-side UI controls for a smooth user experience.

### Key Features
✅ **Server-side enforcement** - Students cannot bypass restrictions
✅ **LiveKit webhook-based** - Automatic track muting when students try to publish
✅ **Redis-cached permissions** - Fast permission checks with minimal database load
✅ **Client-side UI updates** - Disabled buttons provide immediate visual feedback
✅ **Real-time sync** - Permission changes propagate within 5 seconds

---

## 🔧 How It Works

### Teacher Disables Permission (Example: Microphone)

```
1. Teacher toggles mic permission OFF in settings panel
   ↓
2. Frontend calls: POST /livekit/participants/mute-all-students
   ↓
3. LiveKitController->muteAllStudents():
   - Stores permission in Redis: microphone_allowed = false
   - Mutes all active student audio tracks server-side
   ↓
4. Students' audio tracks immediately muted
   ↓
5. Student tries to re-enable microphone
   ↓
6. LiveKit sends webhook: track_published (AUDIO)
   ↓
7. LiveKitWebhookController->handleTrackPublished():
   - Checks Redis: Is mic allowed? → NO
   - Immediately mutes the track server-side
   ↓
8. Student's mic stays muted (cannot bypass)
```

### Client-Side UI Enforcement

```
1. Student joins meeting
   ↓
2. LiveKitControls->fetchAndEnforceRoomPermissions():
   - Fetches permissions from: GET /livekit/rooms/permissions
   - Receives: { microphone_allowed: false, camera_allowed: false }
   ↓
3. LiveKitControls->enforcePermissionsOnUI():
   - Disables mic/camera buttons
   - Adds opacity and cursor-not-allowed styles
   - Updates button titles with Arabic message
   ↓
4. Student sees greyed-out buttons with tooltip:
   "المعلم لم يسمح بإستخدام الميكروفون"
   ↓
5. Permission polling every 5 seconds:
   - Continuously checks for permission changes
   - Updates UI when teacher re-enables permissions
```

---

## 📁 Architecture Components

### 1. RoomPermissionService
**File**: `app/Services/RoomPermissionService.php`

**Purpose**: Centralized permission management using Redis cache

**Methods**:
- `setMicrophonePermission($roomName, $allowed, $userId)` - Update mic permission
- `setCameraPermission($roomName, $allowed, $userId)` - Update camera permission
- `getRoomPermissions($roomName)` - Get all permissions for a room
- `isMicrophoneAllowed($roomName)` - Check if mic is allowed
- `isCameraAllowed($roomName)` - Check if camera is allowed
- `clearRoomPermissions($roomName)` - Clear permissions when room closes

**Storage**:
- Redis cache key: `livekit:room:permissions:{roomName}`
- TTL: 24 hours
- Structure:
  ```json
  {
    "microphone_allowed": true,
    "camera_allowed": true,
    "updated_at": "2025-11-16T10:30:00Z",
    "updated_by": 42
  }
  ```

### 2. LiveKitController Updates
**File**: `app/Http/Controllers/LiveKitController.php`

#### New API Endpoint: `getRoomPermissions()`
**Route**: `GET /livekit/rooms/permissions?room_name={name}`

**Purpose**: Allow clients to fetch current room permissions

**Response**:
```json
{
  "success": true,
  "permissions": {
    "microphone_allowed": false,
    "camera_allowed": true
  }
}
```

#### Updated: `muteAllStudents()`
**Lines**: 283-295

**New Behavior**:
- Stores permission state in Redis before muting tracks
- `allowed = !muted` (inverted logic)

```php
$permissionService = app(\App\Services\RoomPermissionService::class);
$allowed = ! $muted;
$permissionService->setMicrophonePermission($roomName, $allowed, auth()->id());
```

#### Updated: `disableAllStudentsCamera()`
**Lines**: 400-411

**New Behavior**:
- Stores permission state in Redis before disabling tracks
- `allowed = !disabled` (inverted logic)

```php
$permissionService = app(\App\Services\RoomPermissionService::class);
$allowed = ! $disabled;
$permissionService->setCameraPermission($roomName, $allowed, auth()->id());
```

### 3. LiveKit Webhook Handler
**File**: `app/Http/Controllers/LiveKitWebhookController.php`

#### New Event Handler: `handleTrackPublished()`
**Lines**: 627-727

**Webhook Event**: `track_published`

**Flow**:
1. Extract room name, participant identity, track data
2. Check if participant is student (via metadata or identity)
3. Fetch room permissions from RoomPermissionService
4. If track type not allowed:
   - Log enforcement action
   - Call LiveKit API to mute track immediately
   - Track stays muted server-side
5. Student cannot bypass - server always wins

**Track Types**:
- `AUDIO` - Microphone track
- `VIDEO` - Camera track

**Student Detection**:
```php
$isStudent = ($role === 'student') ||
             (!str_contains($participantIdentity, 'teacher') &&
              !str_contains($participantIdentity, 'admin'));
```

### 4. Client-Side Permission Enforcement
**File**: `public/js/livekit/controls.js`

#### New Method: `fetchAndEnforceRoomPermissions()`
**Lines**: 121-169

**Purpose**: Fetch permissions from server and enforce on UI

**Flow**:
1. Call GET /livekit/rooms/permissions
2. Store permissions locally: `this.roomPermissions`
3. Call `enforcePermissionsOnUI()`
4. Start permission polling interval

#### New Method: `enforcePermissionsOnUI()`
**Lines**: 171-223

**Purpose**: Disable/enable buttons based on permissions

**Actions**:
- If `microphoneAllowed = false`:
  - Disable mic button
  - Add `opacity-50` and `cursor-not-allowed` classes
  - Set Arabic tooltip
  - Auto-mute if currently enabled
- If `cameraAllowed = false`:
  - Disable camera button
  - Add visual disabled state
  - Set Arabic tooltip
  - Auto-disable camera if currently enabled

#### New Method: `startPermissionPolling()`
**Lines**: 225-240

**Purpose**: Poll for permission changes every 5 seconds

**Behavior**:
- Interval-based polling (not real-time WebSocket)
- Fetches latest permissions from server
- Updates UI when permissions change
- Lightweight GET request

### 5. Routes Configuration
**File**: `routes/web.php`

**New Route**:
```php
Route::get('rooms/permissions', [LiveKitController::class, 'getRoomPermissions']);
```

**Authentication**: Requires `auth` middleware (all logged-in users)

---

## 🔄 Complete Flow Example

### Scenario: Teacher Disables Microphone

**Step 1: Teacher Action**
```
Teacher clicks mic toggle in settings panel
↓
JavaScript: toggleAllStudentsMicrophones()
↓
POST /livekit/participants/mute-all-students
{
  "room_name": "session-quran-123",
  "muted": true
}
```

**Step 2: Server Processing**
```php
// LiveKitController@muteAllStudents
$permissionService->setMicrophonePermission($roomName, false, auth()->id());
// Stores in Redis: livekit:room:permissions:session-quran-123
// { microphone_allowed: false, ... }

// Mute all active student audio tracks via LiveKit API
$roomService->mutePublishedTrack([...]);
```

**Step 3: Student Tries to Re-Enable**
```
Student clicks mic button (if not disabled yet)
↓
Student's browser publishes audio track
↓
LiveKit server receives track publication
↓
LiveKit sends webhook to our server
```

**Step 4: Webhook Processing**
```
POST /livekit/webhook
{
  "event": "track_published",
  "room": { "name": "session-quran-123" },
  "participant": { "identity": "42_محمد", "metadata": "{\"role\":\"student\"}" },
  "track": { "sid": "TR_abc123", "type": "AUDIO" }
}
↓
LiveKitWebhookController@handleTrackPublished()
↓
Check permissions from Redis:
  microphone_allowed = false
↓
Decision: Mute the track immediately
↓
Call LiveKit API:
  $roomService->mutePublishedTrack([
    'room' => 'session-quran-123',
    'identity' => '42_محمد',
    'track_sid' => 'TR_abc123',
    'muted' => true
  ])
↓
Student's microphone track muted server-side
```

**Step 5: Client-Side UI Update (Parallel)**
```
Student's permission polling fires (every 5 seconds)
↓
GET /livekit/rooms/permissions?room_name=session-quran-123
↓
Response: { microphone_allowed: false }
↓
enforcePermissionsOnUI():
  - Disable mic button
  - Add greyed-out style
  - Show tooltip: "المعلم لم يسمح بإستخدام الميكروفون"
```

---

## 🎨 UI States

### Teacher Settings Panel
**File**: `resources/views/components/meetings/livekit-interface.blade.php`
**Lines**: 2492-2524

```html
<!-- Microphone Control -->
<div class="flex items-center justify-between py-3 border-b border-gray-600">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-blue-600 rounded-lg">
            <i class="ri-mic-line"></i>
        </div>
        <div>
            <p class="text-white">السماح بالميكروفون</p>
            <p class="text-gray-400 text-xs">السماح للطلاب بإستخدام الميكروفون</p>
        </div>
    </div>
    <input type="checkbox" id="toggleAllStudentsMicSwitch" checked>
</div>

<!-- Camera Control -->
<div class="flex items-center justify-between py-3">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-purple-600 rounded-lg">
            <i class="ri-vidicon-line"></i>
        </div>
        <div>
            <p class="text-white">السماح بالكاميرا</p>
            <p class="text-gray-400 text-xs">السماح للطلاب بإستخدام الكاميرا</p>
        </div>
    </div>
    <input type="checkbox" id="toggleAllStudentsCameraSwitch" checked>
</div>
```

### Student UI States

**Permission Enabled** (Default):
```
[🎤 Microphone Button] - Normal style, clickable
[📹 Camera Button] - Normal style, clickable
```

**Permission Disabled**:
```
[🎤 Microphone Button] - Greyed out (opacity-50), disabled, cursor-not-allowed
                        Tooltip: "المعلم لم يسمح بإستخدام الميكروفون"

[📹 Camera Button] - Greyed out (opacity-50), disabled, cursor-not-allowed
                     Tooltip: "المعلم لم يسمح بإستخدام الكاميرا"
```

---

## 🧪 Testing Guide

### Test 1: Basic Permission Enforcement

1. **Setup**:
   - Open two browsers: Teacher (Browser A), Student (Browser B)
   - Both join the same meeting

2. **Test Microphone**:
   - Student: Enable microphone ✓
   - Student: Verify audio working ✓
   - Teacher: Disable mic toggle in settings
   - Expected: Student's mic immediately muted
   - Student: Try to enable microphone
   - Expected: Button disabled (greyed out) OR track immediately muted by webhook

3. **Test Camera**:
   - Student: Enable camera ✓
   - Student: Verify video working ✓
   - Teacher: Disable camera toggle in settings
   - Expected: Student's camera immediately off
   - Student: Try to enable camera
   - Expected: Button disabled (greyed out) OR track immediately disabled by webhook

### Test 2: Server-Side Webhook Enforcement

1. **Setup**:
   - Student joins meeting BEFORE teacher sets permissions
   - Student's buttons are not yet disabled client-side

2. **Test**:
   - Teacher disables microphone permission
   - Student quickly clicks mic button before UI updates
   - Expected: Track briefly publishes, then IMMEDIATELY muted by webhook
   - Check Laravel logs: Look for "🚫 Enforcing permission: Muting student track"

3. **Verify**:
   ```bash
   php artisan pail
   ```
   Should see:
   ```
   🚫 Enforcing permission: Muting student track
   ✅ Student track muted successfully by permission enforcement
   ```

### Test 3: Permission Polling

1. **Setup**:
   - Student in meeting with permissions disabled (buttons greyed out)

2. **Test**:
   - Teacher re-enables microphone permission
   - Wait up to 5 seconds
   - Expected: Student's mic button becomes enabled automatically

3. **Verify Console**:
   ```
   🔐 Fetching room permissions for students...
   ✅ Room permissions received: { microphone_allowed: true, camera_allowed: true }
   ```

### Test 4: New Student Joins

1. **Setup**:
   - Teacher already in meeting
   - Teacher disables mic + camera permissions

2. **Test**:
   - New student joins meeting
   - Expected: Student's buttons immediately disabled
   - Student cannot enable mic/camera at all

3. **Verify**:
   - Buttons should be greyed out from the start
   - No brief "enabled" state

### Test 5: Redis Permission Storage

1. **Check Redis**:
   ```bash
   redis-cli
   GET livekit:room:permissions:session-quran-123
   ```

2. **Expected Output**:
   ```json
   {
     "microphone_allowed": false,
     "camera_allowed": false,
     "updated_at": "2025-11-16T12:34:56Z",
     "updated_by": 42
   }
   ```

3. **Clear Test**:
   ```bash
   DEL livekit:room:permissions:session-quran-123
   ```

---

## 🔍 Debugging

### Enable Verbose Logging

All components log extensively. Check Laravel logs:

```bash
php artisan pail
```

### Key Log Messages

**Permission Set**:
```
Room microphone permission updated
  room_name: session-quran-123
  allowed: false
  updated_by: 42
```

**Webhook Enforcement**:
```
🚫 Enforcing permission: Muting student track
  room: session-quran-123
  participant: 42_محمد
  track_type: AUDIO
  reason: Microphone not allowed by teacher
```

**Client Permission Fetch**:
```
🔐 Fetching room permissions for students...
✅ Room permissions received: { microphone_allowed: false, camera_allowed: true }
```

### Common Issues

**Issue**: Student can still enable mic/camera
- **Check**: Redis is running? `redis-cli ping` → PONG
- **Check**: Webhook is configured correctly in LiveKit
- **Check**: track_published event is reaching webhook endpoint

**Issue**: Buttons not disabled for students
- **Check**: fetchAndEnforceRoomPermissions() being called?
- **Check**: API endpoint /livekit/rooms/permissions returns 200
- **Check**: Student is authenticated (auth middleware)

**Issue**: Permission not persisting
- **Check**: Redis cache TTL (default: 24 hours)
- **Check**: Permission service called in toggle methods

---

## 📊 Performance Considerations

### Redis Usage
- **Read operations**: Every track_published webhook (~100ms each)
- **Write operations**: Only when teacher toggles permissions
- **Cache TTL**: 24 hours (auto-expires after meeting)
- **Storage**: ~200 bytes per room

### Webhook Latency
- **Trigger time**: ~50-200ms after track published
- **Processing time**: ~10-50ms (permission check + API call)
- **Total enforcement delay**: ~60-250ms

### Permission Polling
- **Interval**: 5 seconds
- **Request size**: <1KB
- **Server load**: Minimal (Redis GET operation)
- **Can be reduced** to 2-3 seconds if needed

---

## 🚀 Future Enhancements

### Optional Improvements

1. **WebSocket-based Permission Sync** (instead of polling)
   - Use Laravel Reverb to broadcast permission changes
   - Instant UI updates (0 delay instead of 5 seconds)

2. **Permission History/Audit Log**
   - Store permission changes in database
   - Show timeline of who changed what and when

3. **Fine-grained Permissions**
   - Per-student permissions (not just room-wide)
   - Temporary permission grants
   - Scheduled permission changes

4. **Permission Presets**
   - Save common permission configurations
   - Quick toggle between "Silent Room", "Audio Only", "Full Access"

---

## 📝 Summary

**What Makes This Solution Robust**:

✅ **Server-side enforcement** - Students cannot bypass via browser DevTools
✅ **Webhook-based** - Automatic enforcement without manual polling
✅ **Redis-cached** - Fast permission checks with minimal database load
✅ **Client-side UX** - Disabled buttons prevent confusion
✅ **Polling fallback** - Ensures UI stays in sync
✅ **Logging** - Comprehensive debugging capabilities
✅ **Arabic UI** - Localized messages for students
✅ **Teacher-friendly** - Simple toggle switches

**Files Modified**:
- ✅ `app/Services/RoomPermissionService.php` - NEW
- ✅ `app/Http/Controllers/LiveKitController.php` - UPDATED
- ✅ `app/Http/Controllers/LiveKitWebhookController.php` - UPDATED
- ✅ `public/js/livekit/controls.js` - UPDATED
- ✅ `routes/web.php` - UPDATED
- ✅ `resources/views/components/meetings/livekit-interface.blade.php` - UPDATED

**Assets Built**: ✅ Ready for testing

---

**Ready to test the full implementation!** 🎉
