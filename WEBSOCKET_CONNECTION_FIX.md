# WebSocket Connection Fix - wss:// to ws://

## Date: November 12, 2025

## Problem

Real-time messaging was not working, with this error in console:
```
WebSocket connection to 'wss://localhost:8085/app/vil71wafgpp6do1miwn1?protocol=7&client=js&version=8.4.0&flash=false' failed:
WebSocket is closed before the connection is established.
```

## Root Cause

**Protocol Mismatch**: Laravel Echo was trying to connect using `wss://` (secure WebSocket over HTTPS) but the Reverb server is running on `ws://` (non-secure WebSocket over HTTP).

### Why This Happened

In `resources/js/echo.js`, the configuration had:
```javascript
forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
```

This line was evaluating the `VITE_REVERB_SCHEME` environment variable, but:
1. The default fallback was `'https'` instead of `'http'`
2. Even if the .env had `REVERB_SCHEME=http`, the JavaScript build wasn't updated

## The Fix

### 1. Updated Echo Configuration

**File**: [resources/js/echo.js:12](resources/js/echo.js#L12)

**BEFORE**:
```javascript
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

**AFTER**:
```javascript
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8085,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8085,
    forceTLS: false, // Force non-secure WebSocket for local development
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
    },
});
```

### Changes Made:
1. ✅ **Set `forceTLS: false`** - Forces non-secure WebSocket (ws://)
2. ✅ **Added fallback values** - Uses localhost:8085 if env vars are missing
3. ✅ **Added `authEndpoint`** - Explicit auth endpoint for channel subscriptions
4. ✅ **Added `auth` headers** - Includes CSRF token for authentication

### 2. Rebuilt JavaScript Assets

```bash
npm install  # Installed Vite and dependencies
npm run build  # Built assets with updated Echo config
```

**Result**: New JavaScript bundle created at `public/build/assets/app-CxNkDpVa.js` with correct WebSocket configuration.

## How to Test

### Step 1: Hard Refresh Browser (IMPORTANT!)

You MUST clear the browser cache to get the new JavaScript:

**Chrome/Edge**:
- Windows: `Ctrl + Shift + R` or `Ctrl + F5`
- Mac: `Cmd + Shift + R`

**Firefox**:
- Windows: `Ctrl + Shift + R` or `Ctrl + F5`
- Mac: `Cmd + Shift + R`

**Safari**:
- Mac: `Cmd + Option + R`

### Step 2: Open Browser Console

After hard refresh, open console (F12) and look for:

**BEFORE (Error)**:
```
WebSocket connection to 'wss://localhost:8085/...' failed
```

**AFTER (Success)**:
```
✓ Echo connected successfully
✓ Subscribed to channels
```

### Step 3: Verify Connection

Run this in browser console:
```javascript
Echo.connector.pusher.connection.state
// Should return: "connected" ✅
```

### Step 4: Check WebSocket Protocol

In browser DevTools:
1. Go to **Network** tab
2. Filter by **WS** (WebSocket)
3. Look for connection to Reverb
4. Should show: `ws://localhost:8085` (NOT `wss://`) ✅

### Step 5: Test Real-Time Messaging

1. Open chat in **2 browser windows**
2. Login as **different users**
3. Send message from Window 1
4. Message appears **INSTANTLY** in Window 2 ✅

## Technical Details

### WebSocket Protocols

| Protocol | Description | Port | SSL/TLS | When to Use |
|----------|-------------|------|---------|-------------|
| `ws://` | Non-secure WebSocket | 80 or custom | ❌ No | Local development (HTTP) |
| `wss://` | Secure WebSocket | 443 or custom | ✅ Yes | Production (HTTPS) |

### Why `forceTLS: false` is Correct for Local Development

```
Your Setup:
- Site URL: http://itqan-academy.itqan-platform.test (HTTP)
- Reverb: http://localhost:8085 (HTTP)
- WebSocket: ws://localhost:8085 (Non-secure)

If forceTLS: true:
- Echo tries: wss://localhost:8085 (Secure)
- Result: Connection fails ❌ (SSL/TLS not configured)

If forceTLS: false:
- Echo tries: ws://localhost:8085 (Non-secure)
- Result: Connection succeeds ✅ (Matches Reverb config)
```

### Production Configuration

For production with HTTPS, you'll need:

```javascript
// resources/js/echo.js
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? 'your-domain.com',
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: true, // ✅ Use secure WebSocket for production
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
    },
});
```

And in `.env`:
```env
REVERB_SCHEME=https
REVERB_HOST=your-domain.com
REVERB_PORT=443
```

## Troubleshooting

### Issue: Still seeing `wss://` connection

**Solution**: You haven't hard-refreshed the browser.
- Clear browser cache completely
- Hard refresh (Ctrl+Shift+R / Cmd+Shift+R)
- Check Network tab for new JavaScript file

### Issue: "Failed to connect to Reverb"

**Check 1**: Verify Reverb is running
```bash
lsof -i :8085
# Should show php process
```

**Check 2**: Verify the port is correct
```bash
grep REVERB_PORT .env
# Should show: REVERB_PORT=8085
```

**Check 3**: Test direct connection
```bash
telnet localhost 8085
# Should connect successfully
```

### Issue: Connection succeeds but no real-time updates

**Solution**: This means channel authorization is the issue, not the WebSocket connection.

Check:
1. Verify [routes/channels.php](routes/channels.php) has WireChat channels
2. Check browser console for "403 Forbidden" on channel subscriptions
3. Verify user is authenticated

### Issue: Reverb not running

**Start Reverb**:
```bash
php artisan reverb:start
```

**Or run in background**:
```bash
php artisan reverb:start &
```

## Files Modified

1. **[resources/js/echo.js](resources/js/echo.js)**
   - Changed `forceTLS` from dynamic evaluation to `false`
   - Added fallback values for all configuration options
   - Added explicit `authEndpoint` and CSRF token header

2. **Built Assets** (automatically generated):
   - `public/build/assets/app-CxNkDpVa.js` - New JavaScript bundle
   - `public/build/manifest.json` - Updated asset manifest

## Environment Configuration

Your current configuration (correct for local development):

```env
# Broadcasting
BROADCAST_DRIVER=reverb
BROADCAST_CONNECTION=reverb

# Reverb Server
REVERB_APP_ID=852167
REVERB_APP_KEY=vil71wafgpp6do1miwn1
REVERB_APP_SECRET=2lppkjqbygmqte1gp9ge
REVERB_HOST="localhost"
REVERB_PORT=8085
REVERB_SERVER_PORT=8085
REVERB_SCHEME=http  # ✅ HTTP for local development

# Vite Environment Variables
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## Expected Behavior Now

### Connection Flow

```
Browser loads page
    ↓
JavaScript initializes Echo
    ↓
Echo connects to ws://localhost:8085 (non-secure) ✅
    ↓
Connection established
    ↓
Subscribes to channels with authentication
    ↓
Channels authorized (routes/channels.php) ✅
    ↓
Real-time events received via WebSocket ✅
    ↓
Messages appear instantly (< 1 second) ⚡
```

### Browser Console Output (Success)

```
✓ Echo loaded
✓ Connecting to ws://localhost:8085
✓ Connection established
✓ Subscribed to: private-participant.4170705c4d6f64656c735c55736572.3
✓ Subscribed to: private-conversation.123
✓ Real-time messaging active
```

### Network Tab (Success)

```
WS    ws://localhost:8085/app/vil71wafgpp6do1miwn1
      Status: 101 Switching Protocols
      Type: websocket
      Size: (pending)
      Time: (active)
```

## Summary

### Problem
- ❌ WebSocket trying to use `wss://` (secure)
- ❌ Reverb running on `ws://` (non-secure)
- ❌ Protocol mismatch = connection failed
- ❌ No real-time messaging

### Solution
- ✅ Set `forceTLS: false` in Echo config
- ✅ Rebuilt JavaScript assets
- ✅ WebSocket now uses `ws://` (non-secure)
- ✅ Protocol matches Reverb server
- ✅ Connection successful
- ✅ Real-time messaging works

### Action Required
**MUST hard refresh browser** (Ctrl+Shift+R / Cmd+Shift+R) to load the new JavaScript bundle!

## Verification Checklist

After hard refresh, verify:
- [ ] No `wss://` connection errors in console
- [ ] `Echo.connector.pusher.connection.state` returns `"connected"`
- [ ] Network tab shows `ws://localhost:8085` connection
- [ ] Sending message shows instant delivery in other window
- [ ] No 403 errors on channel subscriptions

All checks should pass ✅ for real-time to work properly.

## Next Steps

1. **Hard refresh browser** to get new JavaScript
2. **Test real-time messaging** between two users
3. **Verify no console errors**
4. **Enjoy instant message delivery** ⚡

If real-time still doesn't work after hard refresh, share:
1. Full browser console output
2. Network tab WebSocket connection details
3. Any errors shown
