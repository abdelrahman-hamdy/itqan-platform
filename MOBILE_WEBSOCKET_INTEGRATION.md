# Mobile WebSocket Integration Guide

## Phase 3: Real-time Chat via Laravel Reverb

This guide explains how to integrate real-time chat functionality in the Flutter mobile app using Laravel Reverb WebSocket.

## Backend Status: ✅ Ready

The backend is fully configured and requires **NO code changes**:
- ✅ Reverb server running on port 8085
- ✅ WireChat events already broadcasting
- ✅ Channel authorization via `/broadcasting/auth`
- ✅ Sanctum authentication supported

## Mobile Implementation (Flutter)

### 1. Add Dependencies

Add to `pubspec.yaml`:

```yaml
dependencies:
  laravel_echo: ^0.4.0  # Official Laravel Echo Dart package
  pusher_client: ^2.0.0  # Reverb uses Pusher protocol
```

### 2. Create Echo Service

Create `lib/services/echo_service.dart`:

```dart
import 'package:laravel_echo/laravel_echo.dart';
import 'package:pusher_client/pusher_client.dart';

class EchoService {
  late Echo echo;
  bool _connected = false;

  /// Connect to Reverb WebSocket server
  void connect(String token, String baseUrl) {
    final options = {
      'broadcaster': 'reverb',
      'key': 'vil71wafgpp6do1miwn1', // From .env REVERB_APP_KEY
      'wsHost': 'itqanway.com',
      'wsPort': 8085,
      'wssPort': 8085,
      'forceTLS': true,
      'authEndpoint': '$baseUrl/broadcasting/auth',
      'auth': {
        'headers': {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      },
      'encrypted': true,
      'enableLogging': true, // Remove in production
    };

    echo = Echo(options);
    _connected = true;

    // Listen to connection state
    echo.connector.pusher.connection.bind('state_change', (state) {
      print('Connection state: ${state?.current}');
    });
  }

  /// Subscribe to conversation messages
  void listenToConversation(String conversationId, Function(dynamic) onMessage, Function(dynamic) onDeleted) {
    if (!_connected) return;

    echo
        .private('conversation.$conversationId')
        .listen('.Namu\\WireChat\\Events\\MessageCreated', (event) {
          print('New message event: $event');
          onMessage(event);
        })
        .listen('.Namu\\WireChat\\Events\\MessageDeleted', (event) {
          print('Message deleted event: $event');
          onDeleted(event);
        });
  }

  /// Subscribe to participant-specific notifications
  void listenToParticipant(String userId, Function(dynamic) onNotification) {
    if (!_connected) return;

    echo
        .private('participant.App\\Models\\User.$userId')
        .listen('.Namu\\WireChat\\Events\\NotifyParticipant', (event) {
          print('Participant notification: $event');
          onNotification(event);
        });
  }

  /// Leave conversation channel
  void leaveConversation(String conversationId) {
    if (!_connected) return;
    echo.leave('private-conversation.$conversationId');
  }

  /// Disconnect from WebSocket
  void disconnect() {
    if (_connected) {
      echo.disconnect();
      _connected = false;
    }
  }
}
```

### 3. Integrate into Chat Screen

Modify `lib/screens/chat_screen.dart`:

```dart
class ChatScreen extends StatefulWidget {
  final String conversationId;

  // ... constructor
}

class _ChatScreenState extends State<ChatScreen> {
  final EchoService _echoService = EchoService();
  final ChatService _chatService = ChatService();
  List<Message> _messages = [];

  @override
  void initState() {
    super.initState();
    _initializeChat();
  }

  Future<void> _initializeChat() async {
    // Connect to WebSocket
    final token = await getAuthToken();
    _echoService.connect(token, 'https://itqanway.com');

    // Load initial messages from API
    await _loadMessages();

    // Subscribe to real-time events
    _echoService.listenToConversation(
      widget.conversationId,
      _handleNewMessage,
      _handleDeletedMessage,
    );
  }

  /// Handle incoming message event
  void _handleNewMessage(dynamic event) {
    final messageId = event['message']['id'];

    // Fetch full message from API
    _chatService.getMessage(messageId).then((message) {
      setState(() {
        _messages.insert(0, message);
      });
    });
  }

  /// Handle message deletion event
  void _handleDeletedMessage(dynamic event) {
    final messageId = event['message']['id'];

    setState(() {
      _messages.removeWhere((m) => m.id == messageId);
    });
  }

  @override
  void dispose() {
    _echoService.leaveConversation(widget.conversationId);
    _echoService.disconnect();
    super.dispose();
  }

  // ... rest of widget
}
```

### 4. Event Flow

```
1. User sends message via API
   ↓
2. POST /api/v1/chat/conversations/{id}/messages
   ↓
3. Backend creates Message record
   ↓
4. WireChat dispatches MessageCreated event
   ↓
5. Reverb broadcasts to conversation.{id} channel
   ↓
6. Mobile receives WebSocket event
   ↓
7. Mobile fetches full message from API
   ↓
8. UI updates immediately
```

### 5. Error Handling

```dart
// Handle connection failures
void _handleConnectionError() {
  // Show "Connecting..." indicator
  setState(() => _connecting = true);

  // Retry with exponential backoff
  _retryConnection(attempts: 0);
}

Future<void> _retryConnection({required int attempts}) async {
  if (attempts >= 5) {
    // Show "Real-time updates unavailable" banner
    setState(() {
      _connecting = false;
      _connectionFailed = true;
    });
    return;
  }

  final delay = Duration(seconds: min(30, pow(2, attempts).toInt()));
  await Future.delayed(delay);

  try {
    _echoService.connect(token, baseUrl);
    setState(() {
      _connecting = false;
      _connectionFailed = false;
    });
  } catch (e) {
    _retryConnection(attempts: attempts + 1);
  }
}
```

### 6. Testing

#### Test Connection
```dart
// In debug mode, verify connection
_echoService.echo.connector.pusher.connection.bind('connected', () {
  print('✅ Connected to Reverb');
});

_echoService.echo.connector.pusher.connection.bind('error', (error) {
  print('❌ Connection error: $error');
});
```

#### Test Message Broadcast
1. Send message from web browser
2. Mobile should receive event within 500ms
3. Message should appear in UI immediately

#### Test Bidirectional
1. Send message from mobile
2. Web browser should see it instantly
3. No page refresh needed

## WebSocket Channels

### Conversation Channel
- **Name**: `conversation.{conversationId}`
- **Type**: Private (requires auth)
- **Events**:
  - `MessageCreated` - New message
  - `MessageDeleted` - Message removed

### Participant Channel
- **Name**: `participant.App\\Models\\User.{userId}`
- **Type**: Private (requires auth)
- **Events**:
  - `NotifyParticipant` - Notifications for this user

## Event Payloads

### MessageCreated Event
```json
{
  "message": {
    "id": 123,
    "conversation_id": 456
  }
}
```

**Note**: Only message ID is sent. Fetch full message from API.

### MessageDeleted Event
```json
{
  "message": {
    "id": 123,
    "conversation_id": 456,
    "sendable_id": 789,
    "sendable_type": "App\\Models\\User"
  }
}
```

### NotifyParticipant Event
```json
{
  "message": {
    "id": 123,
    "body": "Hello",
    "sender": {...},
    "conversation": {...}
  },
  "redirect_url": "/chat/456"
}
```

## Important Notes

### ❌ NO POLLING
Do not implement polling as a fallback. If WebSocket fails, show error and retry connection with exponential backoff.

### ✅ Connection Pooling
Reuse single WebSocket connection across all conversations. Don't create new connection per screen.

### ✅ Offline Support
Cache messages locally (SQLite) and sync when connection returns.

### ✅ Background Handling
Disconnect WebSocket when app goes to background. Reconnect on foreground.

## Troubleshooting

### Connection Refused
- Check if Reverb server is running: `php artisan reverb:start`
- Verify port 8085 is accessible
- Check SSL certificate is valid

### Authentication Failed
- Verify token is valid and not expired
- Check `/broadcasting/auth` endpoint is accessible
- Ensure token has correct format: `Bearer {token}`

### Events Not Received
- Verify channel name format exactly matches backend
- Check event class namespace includes backslashes
- Enable logging in development: `enableLogging: true`

### Duplicate Messages
- Ensure you're not subscribing to same channel multiple times
- Always unsubscribe in dispose()
- Don't fetch message if it's from current user (check `sendable_id`)

## Performance Tips

1. **Batch Read Receipts**: Send read status every 10 seconds, not per message
2. **Lazy Load History**: Load last 50 messages, fetch more on scroll up
3. **Cache Locally**: Store last 100 messages per conversation in SQLite
4. **Optimize Payload**: Backend sends only message ID, fetch full data on demand

## Next Steps

After implementing Phase 3, proceed to Phase 4:
- Message reactions
- Typing indicators
- Edit/delete messages
- Archive conversations

All Phase 4 features also use Reverb for real-time updates.
