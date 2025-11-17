# ⚡ Quick Start - Fix Attendance Now

**The server is ready. You just need to configure LiveKit Cloud webhooks.**

---

## 🎯 **What Was Wrong**

LiveKit webhooks were **never reaching your server**!

- ❌ Wrong webhook URL path (may have been `/api/livekit`)
- ❌ CSRF protection blocking webhooks
- ✅ **Fixed server-side** - endpoint now working

**Test result:** ✅ `200 OK` when testing locally

---

## ⚡ **Fix It in 3 Steps (5 minutes)**

### **Step 1: Install ngrok**
```bash
brew install ngrok
```

### **Step 2: Start tunnel**
```bash
ngrok http https://itqan-platform.test
```

**Copy the HTTPS URL** (example: `https://abc123xyz.ngrok-free.app`)

### **Step 3: Configure LiveKit Cloud**

1. Go to: https://cloud.livekit.io/projects/test-rn3dlic1/settings
2. Click "Webhooks" section
3. Add webhook: `https://YOUR-TUNNEL-URL/webhooks/livekit`
   - Example: `https://abc123xyz.ngrok-free.app/webhooks/livekit`
4. Enable events:
   - ✅ `participant_joined`
   - ✅ `participant_left`
5. **Save**

---

## ✅ **Test It Works**

### **Terminal 1: Monitor webhooks**
```bash
./monitor-webhooks.sh
```

### **Terminal 2: Keep ngrok running**
```bash
# Don't close the ngrok terminal!
```

### **Browser: Join a meeting**

1. Go to session page (session #96)
2. Click "Join Meeting"
3. **Watch Terminal 1** - should see:
   ```
   🔔 WEBHOOK ENDPOINT HIT - Request received
   Participant joined session
   ```

4. **Check frontend** - should show:
   - Status: "في الجلسة الآن" ✅
   - Duration: Incrementing ✅
   - Green pulsing dot ✅

---

## 📚 **Full Documentation**

- **Quick setup:** `LIVEKIT_WEBHOOK_SETUP.md`
- **Technical details:** `WEBHOOK_CONFIGURATION_FIX.md`
- **Complete summary:** `ATTENDANCE_SYSTEM_FINAL_FIX.md`

---

## 🚨 **If It's Not Working**

### **No webhook logs?**
- Check ngrok is still running
- Verify webhook URL in LiveKit Cloud
- Must end with `/webhooks/livekit` (plural)

### **Webhooks received but status not updating?**
```bash
php diagnose-attendance.php
```

---

**That's it! The system is ready once you configure LiveKit Cloud.** 🎉
