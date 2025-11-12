# WireChat Real-Time Debugging Guide

## Quick Test Steps

### 1. Clear Browser Cache (IMPORTANT!)
**Option A:** Open incognito window (`Ctrl+Shift+N` or `Cmd+Shift+N`)
**Option B:** Hard refresh (`Ctrl+Shift+R` or `Cmd+Shift+R`)

### 2. Open Chat URL
```
https://2.itqan-platform.test/chat/3
```
Login as any user who has access to conversation ID 3

### 3. Check Console Output (F12)

#### Expected Initial Output:
```javascript
🔗 WireChat Real-Time Bridge (v2)
✅ Livewire detected. Initializing...
🚀 Initializing WireChat bridge...
👤 Current User ID: [your_user_id]
🔍 Current URL: https://2.itqan-platform.test/chat/3
🔍 Pathname: /chat/3
🔍 Extracted conversation ID: 3
📡 Found current conversation: 3
📡 Subscribing to: private-conversation.3
✅ WireChat bridge initialized
✅ Subscribed to private-conversation.3
👥 Subscribing to presence channel: online.academy.[X]
```

#### If Conversation Not Subscribing:
Look for these debug messages:
```javascript
🔍 Current URL: [actual_url]
🔍 Pathname: [actual_path]
🔍 Extracted conversation ID: none
⚠️  No conversation ID found in URL
```

### 4. Send Test Message

From another browser/user, send a message to the same conversation.

#### Expected Output When Message Received:
```javascript
📨 MessageCreated event received!
🎯 Handling MessageCreated event for conversation 3
📋 Message info: {messageId: 114, messageConversationId: 3}
🔄 Refreshing WireChat component...
✅ Livewire event dispatched: message-received
✅ Refreshed component: [component_name]
```

### 5. Check Network Tab

In browser DevTools:
1. Go to Network tab
2. Filter by "WS" (WebSocket)
3. Click on the WebSocket connection
4. Go to "Messages" sub-tab
5. Look for:
   - `subscribe` messages for `private-conversation.3`
   - `message` events with `MessageCreated` data

## Troubleshooting

### If URL Shows Different Pattern

If your URL doesn't match `/chat/3` pattern, tell me the actual URL format. Common variations:
- `/messages/3`
- `/conversations/3`
- `/chat?id=3`
- Subdomain routing might affect the path

### If No Subscription Happens

Check these in console:
1. Is `window.Echo` defined?
2. Is `window.Livewire` defined?
3. Any errors in red?

### If Subscription Works but No Messages

1. Check if you're a participant:
   - User must be in the conversation
   - Check database: `wirechat_participants` table

2. Check WebSocket connection:
   - Should show "101 Switching Protocols"
   - Connection to port 8085

3. Check authorization:
   - Look for 403 errors
   - Check `/broadcasting/auth` requests

## Backend Test

Run this to create a test message:
```bash
./test-message-flow.sh
```

This creates a message from User 1 to conversation 3.

## What Changed

### 1. Added URL Debugging
Shows exact URL being parsed to find conversation ID

### 2. Simplified Event Listener
Now only listens to the correct format: `.Namu\\WireChat\\Events\\MessageCreated`

### 3. Fixed Presence Initialization
Moved to proper location within initialization flow

### 4. Removed Duplicate Code
Cleaned up redundant presence initialization

## Current Status

- ✅ Backend broadcasting works (verified)
- ✅ Presence channels work (user join/leave)
- ❓ Conversation subscription (needs verification)
- ❓ Message events (needs verification)

## Next Steps

1. **Clear cache and reload page**
2. **Copy ALL console output**
3. **Tell me the exact URL format** you're using
4. **Check if you see subscription messages** in console

The debugging will reveal why messages aren't working while presence is.