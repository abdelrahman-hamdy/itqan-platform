# Itqan Platform Chat System - FIXED Implementation Guide

## 🚀 **REAL-TIME CHAT - WORKING SOLUTION**

This document provides the **FINAL WORKING CONFIGURATION** for real-time chat after systematic debugging and root cause analysis.

## ❌ **Previous Issues (NOW FIXED):**
- Missing `config/broadcasting.php` configuration
- Configuration mismatches between .env, laravel-echo-server.json, and JavaScript
- Multiple conflicting WebSocket service attempts (Pusher/Soketi/Laravel Echo Server)
- SSL/Domain configuration errors causing connection failures
- Message UI positioning on wrong side
- Cache and service restart issues

## ✅ **Current Working Solution:**

### **Service Decision: Laravel Echo Server (HTTP) ONLY**
- **NO MORE SWITCHING** between services
- Simple HTTP WebSocket server for local development
- Reliable connection without SSL complexity

# Itqan Platform Chat System - WORKING Implementation Guide

## Overview
The Itqan Platform uses an enhanced Chatify package for real-time messaging with multi-tenant isolation, role-based permissions, and Arabic/RTL support. This document provides a complete technical specification and implementation guide.

## Architecture Stack (STANDARDIZED)

### WebSocket Service: **Laravel Echo Server** (ONLY)
- **Service**: Laravel Echo Server (NOT Soketi, NOT direct Pusher)
- **Protocol**: WebSocket (ws://) for local development
- **Port**: 6001 (standard Laravel Echo Server port)
- **Configuration File**: `laravel-echo-server.json`
- **Start Command**: `npx laravel-echo-server start`

### Backend Components
- **Framework**: Laravel 11
- **Package**: munafio/chatify (enhanced with custom modifications)
- **Database**: MySQL with multi-tenant academy_id scoping
- **Authentication**: Laravel Sanctum/Session-based
- **Broadcasting**: Laravel Broadcasting with Echo

### Frontend Components
- **Client Library**: Laravel Echo (echo.iife.js)
- **UI Framework**: TailwindCSS with Arabic/RTL support
- **JavaScript**: Vanilla JS with ES6+ features
- **Real-time Events**: Pusher protocol over WebSocket

## Database Schema

### Core Tables
```sql
-- Messages table with multi-tenant support
ch_messages (
    id BIGINT PRIMARY KEY,
    from_id BIGINT,
    to_id BIGINT,
    body TEXT,
    attachment TEXT NULL,
    seen BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    academy_id BIGINT NOT NULL, -- Multi-tenant isolation
    message_type VARCHAR(50) DEFAULT 'text',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    group_id BIGINT NULL -- For group messaging
);

-- Favorites table
ch_favorites (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    favorite_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    academy_id BIGINT NOT NULL -- Multi-tenant isolation
);

-- Group chat tables (if using group functionality)
chat_groups (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    avatar VARCHAR(255) NULL,
    type VARCHAR(50),
    academy_id BIGINT NOT NULL,
    owner_id BIGINT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

chat_group_members (
    id BIGINT PRIMARY KEY,
    group_id BIGINT,
    user_id BIGINT,
    role VARCHAR(50) DEFAULT 'member',
    unread_count INT DEFAULT 0,
    joined_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Configuration Files

### 1. Laravel Echo Server Configuration (`laravel-echo-server.json`)
```json
{
  "authHost": "http://localhost:8000",
  "authEndpoint": "/broadcasting/auth",
  "clients": [
    {
      "appId": "local-app-id",
      "key": "local-app-key"
    }
  ],
  "database": "redis",
  "databaseConfig": {
    "redis": {},
    "publishPresence": true
  },
  "devMode": true,
  "host": "localhost",
  "port": "6001",
  "protocol": "http",
  "socketio": {},
  "sslCertPath": "",
  "sslKeyPath": "",
  "sslCertChainPath": "",
  "sslPassphrase": "",
  "subscribers": {
    "http": true,
    "redis": true
  },
  "apiOriginAllow": {
    "allowCors": true,
    "allowOrigin": "http://localhost:8000",
    "allowMethods": "GET,POST",
    "allowHeaders": "Origin, Content-Type, X-Auth-Token, X-Requested-With, Accept, Authorization, X-CSRF-TOKEN, X-Socket-Id"
  }
}
```

### 2. Chatify Configuration (`config/chatify.php`)
```php
<?php
return [
    'name' => 'Itqan Chat',
    'routes' => [
        'prefix' => 'chat',
        'middleware' => ['web', 'auth'],
        'namespace' => 'App\Http\Controllers\vendor\Chatify',
    ],
    'api_routes' => [
        'prefix' => 'chat/api',
        'middleware' => ['web', 'auth'],
        'namespace' => 'App\Http\Controllers\vendor\Chatify',
    ],
    'pusher' => [
        'debug' => env('APP_DEBUG', false),
        'key' => env('PUSHER_APP_KEY', 'local-app-key'),
        'secret' => env('PUSHER_APP_SECRET', 'local-app-secret'),
        'app_id' => env('PUSHER_APP_ID', 'local-app-id'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
            'host' => env('PUSHER_HOST', '127.0.0.1'),
            'port' => env('PUSHER_PORT', 6001),
            'scheme' => env('PUSHER_SCHEME', 'http'),
            'encrypted' => false,
            'useTLS' => false,
        ],
    ],
];
```

### 3. Environment Variables (`.env`)
```env
# WebSocket Configuration (Laravel Echo Server)
PUSHER_APP_ID=local-app-id
PUSHER_APP_KEY=local-app-key
PUSHER_APP_SECRET=local-app-secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

# Broadcasting
BROADCAST_DRIVER=pusher

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

## Multi-Tenant Implementation

### Academy Scoping
All chat operations MUST be scoped by `academy_id` to ensure complete data isolation between academies.

```php
// In MessagesController.php
private function getAcademyId(): int
{
    return Auth::user()->academy_id ?? 1;
}

// Example query with academy scoping
$messages = Message::where('academy_id', $this->getAcademyId())
    ->where(function($q) use ($currentUserId, $contactId) {
        $q->where('from_id', $currentUserId)->where('to_id', $contactId)
          ->orWhere('from_id', $contactId)->where('to_id', $currentUserId);
    })
    ->latest()
    ->paginate(20);
```

### Role-Based Permissions
```php
// Permission matrix implementation
private function canMessage(User $targetUser): bool
{
    $currentUser = Auth::user();
    $currentAcademyId = $this->getAcademyId();
    
    // Super Admin: Can message anyone across all academies
    if ($currentUser->hasRole(User::ROLE_SUPER_ADMIN)) {
        return true;
    }
    
    // Same academy check for all other roles
    if ($targetUser->academy_id !== $currentAcademyId) {
        return false;
    }
    
    // Role-specific permissions within same academy
    switch ($currentUser->user_type) {
        case User::ROLE_ACADEMY_ADMIN:
        case User::ROLE_SUPERVISOR:
            return true; // Can message anyone in academy
            
        case User::ROLE_STUDENT:
            return $this->studentCanMessage($currentUser, $targetUser);
            
        case User::ROLE_TEACHER:
            return $this->teacherCanMessage($currentUser, $targetUser);
            
        case User::ROLE_PARENT:
            return $this->parentCanMessage($currentUser, $targetUser);
            
        default:
            return false;
    }
}
```

## Real-Time Broadcasting

### Channel Structure
```php
// Private channels for direct messaging
"private-chatify.{user_id}"

// Group channels (if implemented)
"private-chat-group.{group_id}"
```

### Broadcasting Events
```php
// In MessagesController::send()
if (Auth::user()->id != $request['id']) {
    try {
        Chatify::push("private-chatify." . $request['id'], 'messaging', [
            'from_id' => Auth::user()->id,
            'to_id' => $request['id'],
            'message' => Chatify::messageCard($messageData, true)
        ]);
    } catch (\Exception $e) {
        \Log::warning('Pusher notification failed: ' . $e->getMessage());
    }
}
```

### Channel Authentication
```php
// In routes/channels.php
Broadcast::channel('chatify.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// In MessagesController::pusherAuth()
public function pusherAuth(Request $request)
{
    $channelName = $request['channel_name'];
    $socket_id = $request['socket_id'];
    
    // Extract user ID from channel name
    $userId = null;
    if (preg_match('/private-chatify\.(\d+)/', $channelName, $matches)) {
        $userId = $matches[1];
    }
    
    $requestUser = Auth::user();
    $authUser = $userId ? User::find($userId) : $requestUser;
    
    $auth = Chatify::pusherAuth(
        $requestUser,
        $authUser, 
        $channelName,
        $socket_id
    );
    
    return Response::json($auth, 200);
}
```

## Frontend Implementation

### JavaScript Configuration
```javascript
// In chat-system.js
class ChatSystem {
    constructor() {
        this.config = {
            // WebSocket configuration
            pusherKey: document.querySelector('meta[name="pusher-key"]')?.content || 'local-app-key',
            pusherHost: '127.0.0.1', // ALWAYS use 127.0.0.1 for local development
            pusherPort: 6001,
            pusherScheme: 'http', // NO SSL for local development
            
            // API endpoints
            apiEndpoints: {
                sendMessage: '/chat/api/sendMessage',
                fetchMessages: '/chat/api/fetchMessages',
                pusherAuth: '/chat/auth',
                // ... other endpoints
            }
        };
    }
    
    setupPusher() {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: this.config.pusherKey,
            wsHost: this.config.pusherHost, // 127.0.0.1
            wsPort: this.config.pusherPort, // 6001
            wssPort: this.config.pusherPort,
            forceTLS: false,
            encrypted: false,
            disableStats: true,
            enabledTransports: ['ws'], // WebSocket only for local
            cluster: 'mt1',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': this.config.csrfToken
                }
            },
            authEndpoint: this.config.apiEndpoints.pusherAuth
        });
    }
}
```

## Deployment Checklist

### Development Environment
1. ✅ Install Laravel Echo Server: `npm install -g laravel-echo-server`
2. ✅ Configure `laravel-echo-server.json`
3. ✅ Set environment variables in `.env`
4. ✅ Start Laravel Echo Server: `npx laravel-echo-server start`
5. ✅ Start Laravel development server: `php artisan serve`
6. ✅ Test WebSocket connection: Browser console should show successful Echo connection

### Production Environment
1. Configure SSL certificates for WebSocket server
2. Update `PUSHER_SCHEME=https` and `forceTLS=true`
3. Set proper `PUSHER_HOST` to production domain
4. Configure Redis for session/cache storage
5. Set up proper queue workers for background jobs
6. Enable SSL/TLS for all WebSocket connections

## Troubleshooting Guide

### Common Issues

#### 1. WebSocket Connection Failed
**Symptoms**: `Echo connection unavailable` in console
**Causes**: 
- Laravel Echo Server not running
- Wrong host/port configuration
- SSL/TLS mismatch
- Firewall blocking port 6001

**Solutions**:
```bash
# Check if Laravel Echo Server is running
npx laravel-echo-server status

# Start Laravel Echo Server
npx laravel-echo-server start

# Force restart if needed
npx laravel-echo-server stop && npx laravel-echo-server start

# Check configuration
cat laravel-echo-server.json
```

#### 2. Messages Not Delivered in Real-Time
**Causes**:
- Pusher authentication failing
- Wrong channel subscription
- Broadcasting not configured properly

**Debug Steps**:
```javascript
// Add debugging to chat-system.js
window.Echo.connector.pusher.connection.bind('connected', function() {
    console.log('✅ Pusher connected successfully');
});

window.Echo.connector.pusher.connection.bind('error', function(error) {
    console.error('❌ Pusher connection error:', error);
});
```

#### 3. Cross-Origin Issues
**Solution**: Update `laravel-echo-server.json` apiOriginAllow configuration:
```json
{
  "apiOriginAllow": {
    "allowCors": true,
    "allowOrigin": "*",
    "allowMethods": "GET,POST",
    "allowHeaders": "Origin, Content-Type, X-Auth-Token, X-Requested-With, Accept, Authorization, X-CSRF-TOKEN, X-Socket-Id"
  }
}
```

## API Documentation

### Chat Endpoints
```
GET    /chat                           - Chat interface (web)
POST   /chat/api/sendMessage          - Send message
POST   /chat/api/fetchMessages        - Get conversation messages
POST   /chat/api/idInfo               - Get user/contact info
GET    /chat/api/getContacts          - Get user contacts
POST   /chat/auth                     - Pusher authentication
POST   /chat/api/makeSeen             - Mark messages as read
```

### Request/Response Examples
```javascript
// Send message
POST /chat/api/sendMessage
{
    "id": "123",           // recipient user ID
    "message": "Hello!",   // message content
    "temporaryMsgId": "temp_001"
}

// Response
{
    "status": "200",
    "error": {"status": 0, "message": null},
    "message": "<div class='message-card'>...</div>",
    "tempID": "temp_001"
}
```

## Performance Optimization

### Database Indexing
```sql
-- Essential indexes for chat performance
CREATE INDEX idx_messages_conversation ON ch_messages(from_id, to_id, academy_id);
CREATE INDEX idx_messages_academy_created ON ch_messages(academy_id, created_at);
CREATE INDEX idx_messages_seen ON ch_messages(to_id, seen, academy_id);
```

### Caching Strategy
```php
// Cache user contacts for 5 minutes
$contacts = Cache::remember("user_contacts_{$userId}_{$academyId}", 300, function() {
    return $this->getContactsQuery()->get();
});

// Cache message counts
$unreadCount = Cache::remember("unread_count_{$userId}_{$contactId}", 60, function() {
    return Message::where('to_id', $userId)
        ->where('from_id', $contactId)
        ->where('seen', false)
        ->count();
});
```

## Security Considerations

### Input Validation
```php
// Message content validation
$rules = [
    'message' => 'required|string|max:2000',
    'id' => 'required|integer|exists:users,id',
];

// HTML sanitization
$message = htmlentities(trim($request['message']), ENT_QUOTES, 'UTF-8');
```

### File Upload Security
```php
// Allowed file types and size limits
$allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
$maxSize = 10 * 1024 * 1024; // 10MB

// Virus scanning (production)
// Implement file scanning before storage
```

### Rate Limiting
```php
// Apply rate limiting to chat endpoints
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/chat/api/sendMessage', 'MessagesController@send');
});
```

---

## Maintenance & Monitoring

### Health Checks
```bash
# Monitor Laravel Echo Server
ps aux | grep laravel-echo-server

# Check Redis connection
redis-cli ping

# Monitor chat activity
tail -f storage/logs/laravel.log | grep "Pusher"
```

### Performance Metrics
- Message delivery time (< 100ms)
- WebSocket connection success rate (> 99%)
- Database query performance (< 50ms average)
- File upload success rate (> 95%)

This documentation provides a complete foundation for implementing, maintaining, and troubleshooting the Itqan Platform chat system.
