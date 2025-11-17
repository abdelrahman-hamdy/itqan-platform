# 🔧 Development Mode Attendance Tracking

**For Local Development Only** - Production uses LiveKit webhooks

---

## What Changed

Since LiveKit Cloud webhooks **can't reach localhost**, I've added a **development-only fallback** that simulates webhook behavior:

### Backend (`routes/api.php`)
- ✅ Added `/api/sessions/meeting/join-dev` endpoint (local/development only)
- ✅ Added `/api/sessions/meeting/leave-dev` endpoint (local/development only)
- ✅ These create `MeetingAttendanceEvent` records just like webhooks would
- ❌ These endpoints **don't exist** in production (return 404)

### Frontend (`livekit-interface.blade.php`)
- ✅ Auto-detects environment (dev vs production)
- ✅ Calls dev endpoints when `APP_ENV=local`
- ✅ Uses webhook-only approach in production

---

## How It Works Now

### Development Mode (APP_ENV=local)
```
1. User clicks "Join Meeting"
2. Frontend connects to LiveKit
3. room.on('connected') fires
4. Frontend calls /api/sessions/meeting/join-dev
5. Backend creates MeetingAttendanceEvent
6. UI updates to "في الجلسة الآن"

When user leaves:
7. room.on('disconnected') fires
8. Frontend calls /api/sessions/meeting/leave-dev
9. Backend closes the event
10. UI shows final duration
```

### Production Mode (APP_ENV=production)
```
1. User joins meeting
2. LiveKit sends participant_joined webhook
3. Backend creates MeetingAttendanceEvent
4. UI updates (no frontend involvement)

When user leaves:
5. LiveKit sends participant_left webhook
6. Backend closes the event
7. UI shows final duration
```

---

## Testing in Development

1. **Clear any existing test data:**
   ```bash
   php artisan tinker --execute="
   App\Models\MeetingAttendanceEvent::where('session_id', 100)->delete();
   \Cache::forget('attendance_status_100_5');
   echo 'Test data cleared';
   "
   ```

2. **Refresh the session page** - should show "لم تنضم بعد"

3. **Click "Join Meeting"** - should connect to LiveKit

4. **Watch browser console:**
   ```
   📡 Connected to room successfully
   🔧 [DEV MODE] Using fallback attendance tracking
   🔧 [DEV] Recording join via development endpoint...
   ✅ [DEV] Join recorded: {...}
   ```

5. **UI should update to:**
   ```
   ✅ في الجلسة الآن
   ⏰ 0 دقائق
   ```

6. **Leave the meeting** (close tab or click Leave)

7. **Watch console:**
   ```
   📡 Disconnected from room
   🔧 [DEV MODE] Using fallback attendance tracking
   🔧 [DEV] Recording leave via development endpoint...
   ✅ [DEV] Leave recorded: {...}
   ```

8. **Rejoin** - should work seamlessly (no "already in meeting" error)

---

## Verifying Database Events

```bash
php artisan tinker
```

```php
// Check all events for your session
$events = App\Models\MeetingAttendanceEvent::where('session_id', 100)
    ->where('user_id', 5)
    ->orderBy('event_timestamp')
    ->get(['id', 'event_type', 'event_timestamp', 'left_at', 'duration_minutes', 'participant_sid']);

// Should show:
// Event 1: join | joined at X | left at Y | duration 5 min | SID: PA_DEV_xxx
// Event 2: join | joined at Z | left at null | duration null | SID: PA_DEV_yyy (if currently in meeting)
```

---

## Production Deployment

When deploying to production:

1. **Set environment:**
   ```env
   APP_ENV=production
   ```

2. **Configure LiveKit webhooks** in LiveKit Cloud dashboard:
   - URL: `https://your-domain.com/webhooks/livekit`
   - Events: `participant_joined`, `participant_left`

3. **Dev endpoints automatically disabled** - no code changes needed

4. **Test with real webhooks:**
   - Join meeting
   - Check logs: `tail -f storage/logs/laravel.log | grep "LIVEKIT WEBHOOK"`
   - Should see webhook processing logs

---

## Troubleshooting

### "Already in meeting" error in dev mode

**Cause:** Open event exists in database

**Fix:**
```bash
php artisan tinker --execute="
App\Models\MeetingAttendanceEvent::whereNull('left_at')->update([
    'left_at' => now(),
    'duration_minutes' => 0
]);
\Cache::flush();
echo 'All open events closed';
"
```

### Attendance not updating after join

**Check:**
1. Browser console for errors
2. Network tab - dev endpoint called?
3. Database - event created?

**Debug:**
```bash
# Check if dev endpoints exist
php artisan route:list | grep "meeting.*dev"

# Should show:
# POST api/sessions/meeting/join-dev
# POST api/sessions/meeting/leave-dev
```

### Dev endpoints return 404 in production

**This is correct!** Dev endpoints only exist when `APP_ENV=local` or `development`.

In production, webhooks handle everything automatically.

---

## Key Differences: Dev vs Production

| Feature | Development | Production |
|---------|-------------|------------|
| **Attendance Tracking** | Frontend calls dev API | LiveKit webhooks |
| **Timestamps** | Server `now()` | LiveKit exact timestamps |
| **Event IDs** | `DEV_JOIN_xxx` | Webhook UUIDs |
| **Participant SIDs** | `PA_DEV_xxx` | Real LiveKit SIDs |
| **Reliability** | Depends on frontend | 100% reliable (webhook retries) |

---

## Summary

✅ **Development:** You can now test attendance tracking without ngrok!
✅ **Production:** Webhooks provide exact timestamps and 100% reliability
✅ **Same Database Schema:** Events look identical in both modes
✅ **Auto-Detection:** No manual switching needed

**Just set `APP_ENV` correctly and it works!** 🚀
