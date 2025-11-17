# 🚀 LiveKit Cloud Webhook Setup Guide

**Quick guide to configure LiveKit Cloud webhooks for local development**

---

## ⚡ **QUICK START (5 Minutes)**

### **Step 1: Install Tunnel Service**

Pick ONE of these options:

#### **Option A: ngrok (Recommended for testing)**
```bash
# Install via Homebrew (macOS)
brew install ngrok

# Or download from https://ngrok.com/download
```

#### **Option B: Expose (Laravel-friendly)**
```bash
# Install via Composer
composer global require beyondcode/expose

# Or download from https://expose.dev
```

---

### **Step 2: Start Tunnel**

#### **If using ngrok:**
```bash
ngrok http https://itqan-platform.test
```

**Output:**
```
Session Status                online
Account                       your-account (Plan: Free)
Forwarding                    https://abc123xyz.ngrok-free.app -> https://itqan-platform.test
```

**Copy the HTTPS forwarding URL:** `https://abc123xyz.ngrok-free.app`

#### **If using Expose:**
```bash
expose share itqan-platform.test
```

**Output:**
```
Expose                        Share URL: https://abcdef.expose.dev
Forwarding                    https://abcdef.expose.dev → https://itqan-platform.test
```

**Copy the Share URL:** `https://abcdef.expose.dev`

---

### **Step 3: Configure LiveKit Cloud Webhooks**

1. **Open LiveKit Cloud Dashboard:**
   ```
   https://cloud.livekit.io/projects/test-rn3dlic1/settings
   ```

2. **Navigate to "Webhooks" section**

3. **Add New Webhook:**
   - **URL:** `https://YOUR-TUNNEL-URL/webhooks/livekit`
     - Example with ngrok: `https://abc123xyz.ngrok-free.app/webhooks/livekit`
     - Example with Expose: `https://abcdef.expose.dev/webhooks/livekit`

   - **Events to Enable:**
     - ✅ `participant_joined`
     - ✅ `participant_left`
     - ✅ `room_started`
     - ✅ `room_finished`

4. **Save Configuration**

---

### **Step 4: Test the Configuration**

#### **Terminal 1: Watch Laravel Logs**
```bash
tail -f storage/logs/laravel.log | grep "WEBHOOK"
```

#### **Terminal 2: Keep Tunnel Running**
```bash
# Leave ngrok or expose running
# Don't close this terminal!
```

#### **Browser: Join a Meeting**

1. Go to a Quran session page (session #96 for example)
2. Click "Join Meeting"
3. Allow camera/microphone
4. Wait for connection

#### **Expected Log Output (Terminal 1):**
```
[2025-11-14 01:30:00] local.INFO: 🔔 WEBHOOK ENDPOINT HIT - Request received
  {
    "event_value": "participant_joined",
    "room": "session-96",
    "participant_identity": "5_ameer_maher"
  }

[2025-11-14 01:30:00] local.INFO: Participant joined session
  {
    "session_id": 96,
    "user_id": 5,
    "participant_identity": "5_ameer_maher"
  }
```

#### **Expected UI Behavior:**

**Before joining:**
- Status: "لم تنضم بعد" (Not joined yet)
- Duration: 0 minutes

**After joining (within 2-5 seconds):**
- Status: "في الجلسة الآن" (In meeting now)
- Duration: Incrementing (0, 1, 2, 3... minutes)
- Green pulsing dot

---

## 🔍 **TROUBLESHOOTING**

### **Issue 1: No webhook logs appearing**

**Check 1: Is tunnel still running?**
```bash
# Check if ngrok/expose is still active
# Should see "Forwarding" or "Share URL" in terminal
```

**Check 2: Is webhook URL correct in LiveKit Cloud?**
- Must end with `/webhooks/livekit` (plural "webhooks")
- Must use HTTPS tunnel URL, not `localhost`
- Example: `https://abc123.ngrok-free.app/webhooks/livekit` ✅
- NOT: `https://abc123.ngrok-free.app/api/livekit` ❌

**Check 3: Are events enabled in LiveKit Cloud?**
- Must have `participant_joined` and `participant_left` checked

---

### **Issue 2: Webhook logs appear but attendance not updating**

**Check database for open cycles:**
```bash
php artisan tinker
```

```php
$att = \App\Models\MeetingAttendance::where('session_id', 96)
    ->where('user_id', 5)
    ->first();

// Check cycles
dd($att->join_leave_cycles);

// Should see open cycle:
// [
//   [
//     "joined_at" => "2025-11-14T01:30:00.000000Z",
//     "left_at" => null  // ← Open cycle!
//   ]
// ]
```

**Check if verification is working:**
```php
$att->isCurrentlyInMeeting();  // Should return TRUE if you're in the meeting
```

---

### **Issue 3: Tunnel URL keeps changing**

**Problem:** Every time you restart ngrok/expose, you get a new URL and have to update LiveKit Cloud.

**Solution A: ngrok with reserved domain (Paid)**
- Sign up for ngrok paid plan
- Get a reserved domain (stays the same)

**Solution B: Use expose with subdomain (Free)**
```bash
expose share itqan-platform.test --subdomain=myproject

# Always gives you: https://myproject.expose.dev
```

**Solution C: Just for production**
- Use tunnel only during development/testing
- For production, use real domain without tunnel

---

## 📋 **QUICK REFERENCE**

### **Tunnel URLs**
```bash
# ngrok
ngrok http https://itqan-platform.test
# Copy from: Forwarding → https://xxxxxx.ngrok-free.app

# Expose
expose share itqan-platform.test
# Copy from: Share URL → https://xxxxxx.expose.dev
```

### **Webhook URL Format**
```
https://YOUR-TUNNEL-URL/webhooks/livekit

✅ Correct examples:
- https://abc123.ngrok-free.app/webhooks/livekit
- https://myproject.expose.dev/webhooks/livekit

❌ Wrong examples:
- http://localhost:8000/webhooks/livekit (not accessible from internet)
- https://abc123.ngrok-free.app/api/livekit (wrong path)
- https://abc123.ngrok-free.app/webhook/livekit (singular, not plural)
```

### **LiveKit Cloud Settings**
```
URL: https://cloud.livekit.io/projects/test-rn3dlic1/settings
Section: Webhooks
Required Events:
  - participant_joined
  - participant_left
  - room_started
  - room_finished
```

### **Test Commands**
```bash
# Watch logs for webhooks
tail -f storage/logs/laravel.log | grep "WEBHOOK"

# Test local endpoint
php test-webhook-endpoint.php

# Check attendance in database
php diagnose-attendance.php
```

---

## ✅ **SUCCESS CHECKLIST**

- [ ] Tunnel service installed (ngrok or expose)
- [ ] Tunnel running and showing HTTPS URL
- [ ] LiveKit Cloud webhook configured with tunnel URL
- [ ] Webhook URL ends with `/webhooks/livekit`
- [ ] Events enabled: `participant_joined`, `participant_left`
- [ ] Joined a meeting as test user
- [ ] Saw "🔔 WEBHOOK ENDPOINT HIT" in logs
- [ ] Saw "Participant joined session" in logs
- [ ] Attendance status changed to "في الجلسة الآن"
- [ ] Duration incrementing

**If all checked: YOU'RE DONE! 🎉**

---

## 🎯 **WHAT'S NEXT**

Once webhooks are working locally, the attendance system is complete!

**For production deployment:**
1. Remove tunnel service
2. Update LiveKit Cloud webhook URL to production domain
3. Deploy code with CSRF exemption
4. Test with real users

**System is now:**
- ✅ Self-healing (stale cycle detection)
- ✅ Real-time (webhook-based)
- ✅ Accurate (LiveKit presence verification)
- ✅ Resilient (multi-layer validation)

---

**Need help? Check the comprehensive guide: `WEBHOOK_CONFIGURATION_FIX.md`**
