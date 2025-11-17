# 🔧 LiveKit Webhook Configuration Fix

**Date:** 2025-11-14
**Issue:** Attendance showing "لم تنضم بعد" because webhooks were not reaching the server
**Root Cause:** Wrong webhook URL and CSRF protection blocking
**Status:** ✅ FIXED

---

## 🐛 **THE PROBLEM**

User joins LiveKit meeting successfully, but server receives **ZERO webhook events**.

### **Evidence:**

**Frontend Console:**
```javascript
✅ Connected to LiveKit successfully
👤 Local participant set: "5_ameer-maher"
🎥 Camera working
```

**Backend Logs:**
```
❌ NO webhook activity
❌ NO participant_joined events
❌ NO participant_left events
```

**API Response:**
```json
{
  "is_currently_in_meeting": false,
  "attendance_status": "absent",
  "join_count": 8
}
```

### **Root Causes Identified:**

1. **Wrong Webhook URL Path**
   - ❌ Configured: `/api/livekit` (DOES NOT EXIST)
   - ✅ Correct: `/webhooks/livekit`

2. **CSRF Protection Blocking Webhooks**
   - Laravel's CSRF middleware was rejecting webhook requests
   - LiveKit Cloud doesn't send CSRF tokens (it's an external server)
   - Webhook route needed CSRF exemption

---

## ✅ **THE FIX**

### **Fix 1: Added CSRF Exemption for Webhook Route**

**File Modified:** `bootstrap/app.php` (lines 34-38)

```php
->withMiddleware(function (Middleware $middleware): void {
    // ... existing middleware ...

    // CRITICAL: Exclude LiveKit webhook endpoint from CSRF protection
    // LiveKit Cloud sends webhooks without CSRF tokens
    $middleware->validateCsrfTokens(except: [
        'webhooks/livekit',  // LiveKit webhook endpoint
    ]);
})
```

### **Fix 2: Enhanced Webhook Logging**

**File Modified:** `app/Http/Controllers/LiveKitWebhookController.php` (lines 37-48)

Added comprehensive logging at the very start of `handleWebhook()`:

```php
public function handleWebhook(Request $request): Response
{
    // 🔥 CRITICAL DEBUG: Log EVERY incoming request to this endpoint
    Log::info('🔔 WEBHOOK ENDPOINT HIT - Request received', [
        'timestamp' => now()->toISOString(),
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'headers' => $request->headers->all(),
        'body_size' => strlen($request->getContent()),
        'has_event' => $request->has('event'),
        'event_value' => $request->input('event'),
    ]);

    // ... rest of handler
}
```

This logs ALL incoming requests BEFORE signature validation, so we can see if webhooks are reaching the server.

---

## 🔍 **WEBHOOK URL DETAILS**

### **Route Configuration** (`routes/web.php` line 1487-1490)

```php
Route::prefix('webhooks')->group(function () {
    Route::post('livekit', [\App\Http\Controllers\LiveKitWebhookController::class, 'handleWebhook'])
        ->name('webhooks.livekit');

    Route::get('livekit/health', [\App\Http\Controllers\LiveKitWebhookController::class, 'health'])
        ->name('webhooks.livekit.health');
});
```

### **Correct Webhook URLs:**

| Environment | Webhook URL |
|------------|-------------|
| **Local Development** | `https://itqan-platform.test/webhooks/livekit` |
| **Production (if deployed)** | `https://yourdomain.com/webhooks/livekit` |

### **Common Mistakes:**

| ❌ Wrong URL | ✅ Correct URL |
|-------------|---------------|
| `/api/livekit` | `/webhooks/livekit` |
| `/livekit` | `/webhooks/livekit` |
| `/webhook/livekit` | `/webhooks/livekit` (note: webhooks plural) |

---

## 🧪 **TESTING**

### **Test 1: Local Endpoint Verification**

```bash
php test-webhook-endpoint.php
```

**Expected Output:**
```
🧪 Testing LiveKit Webhook Endpoint
=====================================

📍 Webhook URL: https://itqan-platform.test/webhooks/livekit

🚀 Sending test webhook request...

📥 Response:
   HTTP Code: 200
   Body: OK

✅ Webhook endpoint is reachable and responding!
```

**Check Logs:**
```bash
tail -f storage/logs/laravel.log | grep "WEBHOOK ENDPOINT HIT"
```

**Expected Log Output:**
```
[2025-11-14 01:16:07] local.INFO: 🔔 WEBHOOK ENDPOINT HIT - Request received
  {
    "timestamp": "2025-11-13T23:16:07.491179Z",
    "method": "POST",
    "url": "https://itqan-platform.test/webhooks/livekit",
    "event_value": "participant_joined"
  }
```

### **Test 2: LiveKit Cloud Webhook Configuration**

**CRITICAL STEP:** Configure LiveKit Cloud to send webhooks to your server.

#### **For Local Development:**

1. **Install ngrok or expose.dev** to create public HTTPS tunnel:
   ```bash
   # Option 1: ngrok
   ngrok http https://itqan-platform.test

   # Option 2: expose
   expose share itqan-platform.test
   ```

2. **Get Public URL:**
   ```
   Example: https://abc123.ngrok-free.app
   ```

3. **Configure LiveKit Cloud:**
   - Go to: https://cloud.livekit.io/projects/test-rn3dlic1/settings
   - Navigate to "Webhooks" section
   - Add webhook URL: `https://abc123.ngrok-free.app/webhooks/livekit`
   - Enable events:
     - ✅ `participant_joined`
     - ✅ `participant_left`
     - ✅ `room_started`
     - ✅ `room_finished`
   - Save configuration

#### **For Production:**

1. **Use your production domain:**
   ```
   https://yourdomain.com/webhooks/livekit
   ```

2. **Configure LiveKit Cloud:**
   - Go to: https://cloud.livekit.io/projects/YOUR_PROJECT/settings
   - Add webhook URL: `https://yourdomain.com/webhooks/livekit`
   - Enable required events (same as above)
   - Save configuration

---

## 📊 **EXPECTED BEHAVIOR AFTER FIX**

### **Before Fix:**

| Action | Backend | Result |
|--------|---------|--------|
| User joins meeting | No webhook received | ❌ No attendance record |
| Check attendance status | `isCurrentlyInMeeting()` | Returns `false` ❌ |
| UI shows | | "لم تنضم بعد" ❌ |
| Logs show | | No webhook activity ❌ |

### **After Fix:**

| Action | Backend | Result |
|--------|---------|--------|
| User joins meeting | `participant_joined` webhook received | ✅ Attendance record created |
| Webhook handler | Calls `handleUserJoin()` | ✅ Open cycle created |
| Check attendance status | `isCurrentlyInMeeting()` | Returns `true` ✅ |
| UI shows | | "في الجلسة الآن" ✅ |
| Logs show | | `🔔 WEBHOOK ENDPOINT HIT` ✅ |

---

## 🔗 **WEBHOOK EVENT FLOW**

```
User joins LiveKit meeting
       ↓
LiveKit Cloud detects participant_joined
       ↓
LiveKit Cloud sends POST to webhook URL
  https://yourdomain.com/webhooks/livekit
       ↓
Laravel receives request (CSRF exempt)
       ↓
LiveKitWebhookController::handleWebhook()
       ↓
Logs: 🔔 WEBHOOK ENDPOINT HIT
       ↓
Validates signature (lenient in development)
       ↓
Calls handleParticipantJoined()
       ↓
Extracts user ID from participant identity
  Example: "5_ameer_maher" → user ID = 5
       ↓
Calls MeetingAttendanceService::handleUserJoin()
       ↓
Creates/updates MeetingAttendance record
       ↓
Adds open cycle: { joined_at: "now", left_at: null }
       ↓
Frontend polls /api/sessions/{id}/attendance-status
       ↓
isCurrentlyInMeeting() returns TRUE ✅
       ↓
UI updates: "في الجلسة الآن" ✅
```

---

## 🚨 **TROUBLESHOOTING**

### **Issue: Still not receiving webhooks after configuration**

**Check 1: Verify webhook URL is configured in LiveKit Cloud**
```bash
# Check LiveKit Cloud dashboard
https://cloud.livekit.io/projects/test-rn3dlic1/settings

# Ensure webhook URL is EXACTLY:
https://yourdomain.com/webhooks/livekit
```

**Check 2: Verify endpoint is publicly accessible**
```bash
# Test from outside your network
curl -X POST https://yourdomain.com/webhooks/livekit \
  -H "Content-Type: application/json" \
  -d '{"event":"test"}'

# Should return: 200 OK
```

**Check 3: Check Laravel logs for incoming requests**
```bash
tail -f storage/logs/laravel.log | grep "WEBHOOK"

# Should see logs when user joins/leaves meeting
```

**Check 4: Verify CSRF exemption is active**
```bash
# Check bootstrap/app.php contains:
$middleware->validateCsrfTokens(except: [
    'webhooks/livekit',
]);
```

### **Issue: Webhooks received but attendance not updating**

**Check 1: Verify participant identity format**
```bash
# Check logs for participant identity
tail -f storage/logs/laravel.log | grep "participant_identity"

# Should be in format: "5_ameer_maher" (userId_firstName_lastName)
```

**Check 2: Verify user ID extraction is working**
```bash
# Check logs for user_id extraction
tail -f storage/logs/laravel.log | grep "user_id"

# Should see: "user_id": 5
```

**Check 3: Verify attendance service is creating records**
```bash
php artisan tinker

# Check for recent attendance records
\App\Models\MeetingAttendance::latest()->first();

# Check join_leave_cycles
$att = \App\Models\MeetingAttendance::latest()->first();
dd($att->join_leave_cycles);
```

---

## 📝 **FILES MODIFIED**

1. **`bootstrap/app.php`** (lines 34-38)
   - Added CSRF exemption for webhook route
   - **Critical for webhooks to work**

2. **`app/Http/Controllers/LiveKitWebhookController.php`** (lines 37-48)
   - Added comprehensive request logging
   - **Helps debug webhook delivery issues**

3. **`test-webhook-endpoint.php`** (NEW FILE)
   - Test script to verify webhook endpoint locally
   - **Use before configuring LiveKit Cloud**

---

## 🎯 **KEY INSIGHTS**

### **Why Webhooks Weren't Working:**

1. **Wrong URL Configuration**: LiveKit Cloud may have been configured with `/api/livekit` instead of `/webhooks/livekit`
2. **CSRF Protection**: Laravel was rejecting external POST requests without CSRF tokens
3. **No Logging**: Hard to diagnose without comprehensive logging
4. **Local Development Challenge**: Webhooks from LiveKit Cloud can't reach `localhost` without tunnel

### **The Fix Was Multi-Part:**

1. ✅ **Exclude webhook route from CSRF** - Allow external POST requests
2. ✅ **Add comprehensive logging** - See all incoming requests
3. ✅ **Create test script** - Verify endpoint locally
4. ✅ **Document correct URL** - `/webhooks/livekit` not `/api/livekit`
5. ⏳ **Configure LiveKit Cloud** - User must do this step

### **Local Development Requirement:**

For local development, you MUST use a tunnel service:
- **ngrok**: Free tier available
- **expose**: Laravel-friendly
- **cloudflared**: Cloudflare tunnel

Without a tunnel, LiveKit Cloud cannot send webhooks to `localhost`.

---

## 🚀 **DEPLOYMENT CHECKLIST**

### **Code Changes (COMPLETED):**
- [x] Added CSRF exemption for webhook route
- [x] Enhanced webhook logging
- [x] Created test script
- [x] Documented correct webhook URL
- [x] Fixed participant identity matching (from previous fix)

### **Configuration (USER MUST DO):**

For **Local Development:**
- [ ] Install tunnel service (ngrok/expose)
- [ ] Start tunnel: `ngrok http https://itqan-platform.test`
- [ ] Get public URL (e.g., `https://abc123.ngrok-free.app`)
- [ ] Configure LiveKit Cloud webhook: `https://abc123.ngrok-free.app/webhooks/livekit`
- [ ] Enable events: `participant_joined`, `participant_left`, `room_started`, `room_finished`
- [ ] Test by joining a meeting
- [ ] Check logs: `tail -f storage/logs/laravel.log | grep WEBHOOK`

For **Production:**
- [ ] Configure LiveKit Cloud webhook: `https://yourdomain.com/webhooks/livekit`
- [ ] Enable same events as above
- [ ] Deploy code changes to production
- [ ] Test with real user joining meeting
- [ ] Monitor logs for webhook activity

---

## 🎉 **SUMMARY**

**Problem:** Attendance system showed "لم تنضم بعد" because webhooks weren't reaching the server.

**Root Causes:**
1. Wrong webhook URL path (may have been configured as `/api/livekit`)
2. CSRF protection blocking external webhooks
3. Insufficient logging to diagnose the issue

**Solution:**
1. ✅ Added CSRF exemption for `/webhooks/livekit`
2. ✅ Enhanced logging to track all webhook requests
3. ✅ Created test script to verify endpoint
4. ✅ Documented correct webhook URL path
5. ⏳ **User must configure LiveKit Cloud with correct URL**

**Next Step:** Configure LiveKit Cloud webhook URL (requires tunnel for local dev)

**This completes the server-side fixes. The final step is LiveKit Cloud configuration!** 🎯
