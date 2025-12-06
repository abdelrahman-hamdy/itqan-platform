# Permission Control Testing & Troubleshooting Guide

## ✅ Fixes Applied

### Issue 1: 404 Error on Camera Control Route
**Root Cause**: JavaScript was using `this.config?.meetingConfig?.roomName` which might not match the actual LiveKit room name.

**Fix**: Updated to use actual LiveKit room object:
```javascript
// OLD (WRONG):
const roomName = this.config?.meetingConfig?.roomName || `session-${window.sessionId}`;

// NEW (CORRECT):
const roomName = this.room?.name || this.config?.meetingConfig?.roomName || `session-${window.sessionId}`;
```

**Files Updated**:
- `public/js/livekit/controls.js` - Lines 127, 1287, 1356
- Assets rebuilt ✅

### Issue 2: Microphone Not Muting
**Possible Causes**:
1. The room name mismatch (now fixed)
2. Track isn't published yet when mute is called
3. LiveKit API call failing silently

**Testing Steps Below** 👇

---

## 🧪 Testing Steps

### Step 1: Clear Browser Cache
```bash
# Teacher browser: Open DevTools
# Press: Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)
# Or: Right-click refresh button → "Empty Cache and Hard Reload"
```

### Step 2: Join Meeting with Both Users

**Teacher**:
1. Open browser (Chrome/Edge recommended)
2. Navigate to Quran session
3. Join meeting
4. Open DevTools Console (F12)
5. Check for room name: Look for LiveKit connection logs

**Student**:
1. Open different browser or incognito window
2. Navigate to same Quran session
3. Join meeting
4. Enable microphone ✅
5. Enable camera ✅
6. Verify both are working

### Step 3: Test Microphone Control

**Teacher Actions**:
1. Open settings panel (gear icon)
2. See two toggles:
   - السماح بالميكروفون (Microphone)
   - السماح بالكاميرا (Camera)
3. Toggle microphone OFF
4. Check console for logs

**Expected Console Logs (Teacher)**:
```
🎤 Teacher toggling all students microphones: MUTED
✅ All students microphones toggled successfully via API: { success: true, affected_participants: 1 }
```

**Expected Behavior (Student)**:
- Microphone immediately muted ✅
- Mic button disabled (greyed out) within 5 seconds ✅
- Tooltip: "المعلم لم يسمح بإستخدام الميكروفون"

**If Still Not Working**:
- Check Laravel logs (Step 5)
- Verify webhook is configured (Step 6)

### Step 4: Test Camera Control

**Teacher Actions**:
1. Toggle camera OFF in settings
2. Check console

**Expected Console Logs (Teacher)**:
```
📹 Teacher toggling all students cameras: DISABLED
✅ All students cameras toggled successfully via API: { success: true, affected_participants: 1 }
```

**Expected Behavior (Student)**:
- Camera immediately turns off ✅
- Camera button disabled (greyed out) within 5 seconds ✅
- Tooltip: "المعلم لم يسمح بإستخدام الكاميرا"

### Step 5: Check Laravel Logs

Open terminal and run:
```bash
php artisan pail
```

**Look For**:

**Permission Set**:
```
Room microphone permission updated
  room_name: session-quran-135
  allowed: false
  updated_by: 42
```

**Mute API Call**:
```
LiveKitController::muteAllStudents - Processing request
  room_name: session-quran-135
  muted: true
```

**Success**:
```
Bulk mute/unmute students action
  room: session-quran-135
  muted: true
  affected_tracks: 1
  teacher: 42
```

**If You See**:
```
Failed to list participants
  room: session-quran-135
  error: Room not found
```
This means the room name doesn't match LiveKit's actual room name.

### Step 6: Verify LiveKit Room Name

**In Student/Teacher Console**:
```javascript
// Type this in browser console:
window.livekitRoom?.name
```

**Should Return Something Like**:
```
"session-quran-135"
```

**Compare** this with what the JavaScript is sending in the API call.

---

## 🔍 Troubleshooting

### Problem: "Room not found or LiveKit server unavailable"

**Check 1: Verify Room Name Match**
```javascript
// In browser console when in meeting:
console.log('Room Name:', window.livekitRoom?.name);
console.log('Config Name:', window.livekitMeetingConfig?.roomName);
```

Both should match! If different, that's the problem.

**Check 2: Verify LiveKit Server**
```bash
# Check LiveKit config
cat config/livekit.php
```

Should have:
```php
'server_url' => env('LIVEKIT_SERVER_URL', 'wss://your-livekit-server.com'),
'api_key' => env('LIVEKIT_API_KEY', 'your-key'),
'api_secret' => env('LIVEKIT_API_SECRET', 'your-secret'),
```

**Check 3: Test Direct API Call**
```bash
# In browser console (when in meeting):
fetch('/livekit/participants/mute-all-students', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    credentials: 'same-origin',
    body: JSON.stringify({
        room_name: window.livekitRoom.name,  // Use actual room name
        muted: true
    })
}).then(r => r.json()).then(console.log);
```

### Problem: Microphone Not Muting

**Check 1: Is Track Published?**

Student might not have published audio track yet. In Laravel logs, look for:
```
🚫 Enforcing permission: Muting student track
  track_type: AUDIO
```

If you don't see this, webhook isn't receiving track_published events.

**Check 2: Webhook Configuration**

Verify LiveKit webhook is configured to send events to your server:
```
POST https://your-domain.com/livekit/webhook
```

Events to enable:
- `track_published`
- `participant_joined`
- `participant_left`

**Check 3: Permission Stored in Redis**

```bash
redis-cli
GET livekit:room:permissions:session-quran-135
```

Should return:
```json
{
  "microphone_allowed": false,
  "camera_allowed": true,
  "updated_at": "2025-11-16T...",
  "updated_by": 42
}
```

If empty or missing, permission isn't being stored.

### Problem: Toggle Keeps Reverting

This happens when the API call fails. Check:

1. **404 Error**: Route doesn't exist
   ```bash
   php artisan route:clear
   composer dump-autoload
   ```

2. **403 Error**: Not authenticated or not a teacher
   - Check user is logged in
   - Check user_type is 'quran_teacher' or 'academic_teacher'

3. **500 Error**: Server error
   - Check Laravel logs: `php artisan pail`

### Problem: Student Can Still Re-enable

This means webhook enforcement isn't working.

**Check Webhook Logs**:
```bash
tail -f storage/logs/laravel.log | grep "track_published"
```

Should see:
```
🚫 Enforcing permission: Muting student track
✅ Student track muted successfully by permission enforcement
```

If not seeing these logs, webhook isn't being triggered.

---

## 📊 Expected Behavior Summary

| Action | Teacher Side | Student Side |
|--------|-------------|--------------|
| **Student joins** | Sees student join notification | Mic OFF, Camera OFF by default |
| **Student enables mic** | Hears student audio | Mic ON, can speak |
| **Teacher disables mic permission** | Sees "تم كتم جميع الطلاب" notification | Mic immediately muted |
| **Student tries to re-enable mic** | N/A | Button disabled OR webhook auto-mutes within 250ms |
| **Teacher re-enables mic permission** | Toggle ON | Button enabled within 5s, can enable mic |
| **Same for camera** | Same flow | Same flow |

---

## 🎯 Quick Debugging Checklist

- [ ] Browser cache cleared (hard refresh)
- [ ] Using actual LiveKit room name (`this.room.name`)
- [ ] Assets rebuilt (`npm run build`)
- [ ] Composer autoload refreshed (`composer dump-autoload`)
- [ ] Redis is running (`redis-cli ping` → PONG)
- [ ] LiveKit server is reachable
- [ ] Webhook endpoint is accessible from LiveKit server
- [ ] User is authenticated as teacher
- [ ] Tracks are being published (check DevTools Network tab)
- [ ] Permission stored in Redis
- [ ] Laravel logs show successful API calls
- [ ] No JavaScript errors in console

---

## 🚀 Next Steps

1. **Clear cache and hard refresh**
2. **Test with two users** (teacher + student)
3. **Check console logs** on both sides
4. **Check Laravel logs**: `php artisan pail`
5. **Report back** with:
   - Console logs from teacher
   - Console logs from student
   - Laravel logs
   - Room name from both sides

This will help identify exactly where the issue is.
