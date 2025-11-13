# 🔍 WireChat Debugging Guide

## 🎯 Quick Start Testing

### 1. Open Browser Console
1. Navigate to: **https://itqan-platform.test/chats**
2. Open Developer Tools: Press **F12** or **Cmd+Option+I** (Mac)
3. Go to **Console** tab

### 2. What You Should See Immediately

✅ **Expected Console Output:**
```
🔍 WireChat Debug Mode Activated
✅ Laravel Echo Loaded
📡 Echo Configuration
  Broadcaster: reverb
  Options: {wsHost: "itqan-platform.test", wsPort: 8085, ...}
🔌 WebSocket Connection Monitor
  connecting → connected
✅ WebSocket Connected!
  Socket ID: 12345.67890
🔐 Private Channel Subscription
  Channel: conversation.1
👂 Listening for event: .Namu\WireChat\Events\MessageCreated on conversation.1
```

❌ **If You See Errors:**
```
❌ WebSocket Connection Failed
❌ Auth Failed: 403
❌ Laravel Echo NOT LOADED
```

## 🧪 Testing Message Flow

### Step 1: Monitor Backend Logs
Open a terminal and run:
```bash
./monitor-chat.sh
```

You should see:
```
✓ Reverb running (PID: 72390)
✓ Queue worker running (PID: 72427)
📊 Monitoring both logs...
```

### Step 2: Send a Test Message
1. In the chat interface, type a message: "Test 123"
2. Click Send

### Step 3: Check Console Output

**✅ What You Should See in Browser Console:**
```javascript
// 1. Message sending via Livewire
📤 Livewire Request Sent
  Component: namu.wire-chat.livewire.chat.chat
  Method: sendMessage
  Payload: {body: "Test 123"}

// 2. Response from server
📥 Livewire Response Received
  Component: namu.wire-chat.livewire.chat.chat
  Response: {effects: {...}}

// 3. Broadcasting auth (if new channel)
🔑 Broadcasting Auth Request
  URL: /broadcasting/auth
  Method: POST
✅ Auth Success

// 4. Message broadcast received
📨 Event Received: .Namu\WireChat\Events\MessageCreated
  Channel: conversation.1
  Data: {message: {...}}

// 5. DOM update
➕ Message Added to DOM
```

**✅ What You Should See in Terminal (monitor-chat.sh):**
```
[01:45:23][QUEUE] Processing: Namu\WireChat\Events\MessageCreated
[01:45:23][QUEUE] 📨 MessageCreated broadcast sent
[01:45:23][QUEUE] Processed: Namu\WireChat\Events\MessageCreated
[01:45:24][REVERB] Message sent to conversation.1
```

## 🐛 Common Issues & Solutions

### Issue 1: WebSocket Not Connecting

**Symptoms:**
```
❌ WebSocket connection failed
State: failed
```

**Debug Steps:**
```bash
# 1. Check services
./chat-status.sh

# 2. Check Reverb logs
tail -f /tmp/wirechat-logs/reverb.log

# 3. Test WebSocket manually
curl -k https://itqan-platform.test:8085
```

**Solution:**
```bash
./restart-chat.sh
```

### Issue 2: Messages Not Broadcasting

**Symptoms:**
- Message appears for sender only
- No events in console
- Queue logs empty

**Debug Steps:**
```bash
# 1. Check queue is processing
./monitor-chat.sh

# 2. Check queue connection
php artisan queue:failed

# 3. Check Redis connection
redis-cli ping  # Should return "PONG"
```

**Solution:**
```bash
# Clear failed jobs
php artisan queue:flush

# Restart services
./restart-chat.sh
```

### Issue 3: 403 Authentication Error

**Symptoms:**
```
❌ Auth Failed: 403
Channel: conversation.1
```

**Debug Steps:**
```bash
# Check routes/channels.php authorization
# Verify user belongs to conversation
```

**In Browser Console:**
```javascript
// Check current user
wirechatDebug.echoStatus()

// Test specific channel
wirechatDebug.testBroadcast(1) // Replace 1 with conversation ID
```

### Issue 4: No Console Output

**Symptoms:**
- No debug messages in console
- Debug script not loading

**Solution:**
1. Verify `APP_DEBUG=true` in `.env`
2. Clear browser cache (Cmd+Shift+R)
3. Check console for JavaScript errors
4. Verify file exists: `public/js/chat-debug.js`

## 🛠️ Debug Helper Commands

Open browser console and use these commands:

```javascript
// Show Echo connection status
wirechatDebug.echoStatus()

// List all active channels
wirechatDebug.listChannels()

// Test broadcast reception for conversation ID 5
wirechatDebug.testBroadcast(5)

// Show help
wirechatDebug.help()
```

## 📊 Expected Data Flow

```
User sends message
    ↓
1. Livewire component (sendMessage method)
    ↓
2. Database (save message)
    ↓
3. Queue job dispatched (BroadcastMessage)
    ↓
4. Reverb receives broadcast
    ↓
5. Reverb sends to subscribed clients
    ↓
6. Echo receives event in browser
    ↓
7. Livewire component updates UI
    ↓
Message appears in chat
```

## 🎯 Complete Testing Checklist

- [ ] Services running: `./chat-status.sh` shows all green
- [ ] Browser console shows: "WebSocket Connected"
- [ ] Can see active channels: `wirechatDebug.listChannels()`
- [ ] Monitor shows activity: `./monitor-chat.sh`
- [ ] Send message: appears immediately
- [ ] Queue logs show: "MessageCreated broadcast sent"
- [ ] Reverb logs show: "Message sent to conversation.X"
- [ ] Browser console shows: "Event Received: MessageCreated"
- [ ] Open second browser/incognito: message appears there too
- [ ] No errors in any logs

## 📝 Logging Locations

| Log Type | Location | Command |
|----------|----------|---------|
| Reverb | `/tmp/wirechat-logs/reverb.log` | `tail -f /tmp/wirechat-logs/reverb.log` |
| Queue | `/tmp/wirechat-logs/queue-messages.log` | `tail -f /tmp/wirechat-logs/queue-messages.log` |
| Laravel | `storage/logs/laravel.log` | `tail -f storage/logs/laravel.log` |
| Browser | Developer Tools → Console | F12 |

## 🚀 Pro Debugging Tips

### 1. Monitor All Logs Simultaneously
```bash
# Terminal 1
./monitor-chat.sh

# Terminal 2  
tail -f storage/logs/laravel.log

# Terminal 3
./chat-status.sh  # Check periodically
```

### 2. Network Tab Inspection
1. Open DevTools → Network tab
2. Filter: WS (WebSocket)
3. Click the WebSocket connection
4. View Messages tab
5. Watch real-time WebSocket frames

### 3. Test with Two Browsers
1. **Browser 1:** Regular Chrome
2. **Browser 2:** Incognito/Private mode
3. Login as different users
4. Send message from Browser 1
5. Should appear in Browser 2 instantly

### 4. Redis Monitor
```bash
redis-cli monitor
```
Watch Redis commands in real-time

### 5. Database Queries
```bash
# Watch database for new messages
watch -n 1 'mysql -u root -pnewstart itqan_platform -e "SELECT id, body, created_at FROM wire_messages ORDER BY id DESC LIMIT 5"'
```

## 📞 Still Having Issues?

1. **Collect Debug Info:**
```bash
# Run all checks
./chat-status.sh
php artisan config:cache
php artisan route:list --path=chats
php artisan queue:failed

# Export logs
tar -czf wirechat-debug-$(date +%Y%m%d-%H%M%S).tar.gz \
  /tmp/wirechat-logs/ \
  storage/logs/laravel.log \
  .env \
  config/wirechat.php
```

2. **Restart Everything:**
```bash
./restart-chat.sh
```

3. **Check Documentation:**
- WireChat: https://wirechat.namuio.com
- Laravel Reverb: https://laravel.com/docs/reverb
- Laravel Echo: https://laravel.com/docs/broadcasting
