# LiveKit Attendance Tracking Solution

## Overview
Track meeting attendance in Laravel using self-hosted LiveKit server with webhooks - no client-side operations needed.

---

## How It Works (The Big Picture)

### **1. User Joins Meeting**
- User clicks "Join Meeting" in your Laravel app
- Laravel generates a token and sends it to the browser
- Browser connects directly to LiveKit server (not through Laravel)

### **2. LiveKit Tracks & Notifies**
- LiveKit server detects when users join/leave
- **LiveKit automatically sends HTTP POST requests (webhooks) to your Laravel app**
- These webhooks contain: event type, user data, timestamp

### **3. Laravel Stores Data**
- Your Laravel app receives webhooks as normal HTTP requests
- Controller saves join/leave data to database
- Works automatically in the background

### **4. Generate Reports**
- After meeting ends, query database for attendance data
- Get first join time and total duration for each user

---

## Setup Steps

### **Step 1: Install LiveKit Server**

Use Docker to run LiveKit on your server:

```yaml
# docker-compose.yml
version: '3.9'
services:
  livekit:
    image: livekit/livekit-server:latest
    command: --config /etc/livekit.yaml
    network_mode: "host"
    volumes:
      - ./livekit.yaml:/etc/livekit.yaml
```

### **Step 2: Configure LiveKit (THIS IS THE KEY!)**

In `livekit.yaml`, tell LiveKit where to send webhooks:

```yaml
port: 7880

keys:
  your-api-key: your-api-secret

webhook:
  api_key: your-webhook-secret-key
  urls:
    - https://your-laravel-app.com/api/livekit/webhook

room:
  auto_create: true
```

**This tells LiveKit: "Send all join/leave events to this Laravel URL"**

### **Step 3: Create Database Table**

```php
Schema::create('meeting_sessions', function (Blueprint $table) {
    $table->id();
    $table->string('room_name');
    $table->string('participant_identity');
    $table->string('participant_name');
    $table->timestamp('joined_at');
    $table->timestamp('left_at')->nullable();
    $table->integer('duration_seconds')->nullable();
});
```

### **Step 4: Create Laravel Webhook Route**

```php
// routes/api.php
Route::post('/livekit/webhook', [LiveKitWebhookController::class, 'handle']);
```

**This route must be publicly accessible so LiveKit can reach it**

### **Step 5: Create Webhook Controller**

```php
class LiveKitWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify webhook is from LiveKit
        if ($request->header('Authorization') !== env('LIVEKIT_WEBHOOK_KEY')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $event = $request->input('event');
        
        if ($event === 'participant_joined') {
            MeetingSession::create([
                'room_name' => $request->input('room.name'),
                'participant_identity' => $request->input('participant.identity'),
                'participant_name' => $request->input('participant.name'),
                'joined_at' => now(),
            ]);
        }
        
        if ($event === 'participant_left') {
            $session = MeetingSession::where('room_name', $request->input('room.name'))
                ->where('participant_identity', $request->input('participant.identity'))
                ->whereNull('left_at')
                ->latest()
                ->first();
                
            if ($session) {
                $leftAt = now();
                $session->update([
                    'left_at' => $leftAt,
                    'duration_seconds' => $leftAt->diffInSeconds($session->joined_at),
                ]);
            }
        }

        return response()->json(['success' => true]);
    }
}
```

### **Step 6: Get Attendance Report**

```php
// After meeting ends
$report = MeetingSession::where('room_name', 'room-123')
    ->get()
    ->groupBy('participant_identity')
    ->map(function($sessions) {
        return [
            'name' => $sessions->first()->participant_name,
            'first_join_time' => $sessions->first()->joined_at,
            'total_duration' => $sessions->sum('duration_seconds'),
        ];
    });
```

---

## The Critical Connection

**The magic happens in the LiveKit config file:**

```yaml
webhook:
  urls:
    - https://your-laravel-app.com/api/livekit/webhook
```

**This line tells LiveKit server to automatically POST data to your Laravel app whenever events occur.**

Your Laravel route receives these as normal HTTP requests - no special SDK needed for receiving webhooks!

---

## Flow Diagram

```
User Browser ──connects──> LiveKit Server
                                │
                                │ (detects join/leave)
                                │
                                │ HTTP POST
                                ↓
                    Laravel App (/api/livekit/webhook)
                                │
                                ↓
                           Database
```

---

## Key Points

✅ **No client-side tracking needed** - LiveKit server handles everything

✅ **Webhooks are just HTTP POST requests** - Laravel receives them like any API call

✅ **Configuration is the key** - You tell LiveKit your Laravel URL in the config file

✅ **Works for unlimited parallel meetings** - Each webhook is an independent database insert

✅ **One query after meeting** - Get full attendance report from your database

---

## Summary

1. Configure LiveKit with your Laravel webhook URL
2. LiveKit sends POST requests automatically when users join/leave
3. Laravel stores the data in database
4. Query database for attendance reports

**That's it!**