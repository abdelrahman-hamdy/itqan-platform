# Chat API Documentation for Mobile Integration

## Overview

This document provides comprehensive documentation for integrating the Itqan Chat System with mobile applications (iOS, Android, Flutter, React Native, etc.).

**Base URL:** `https://your-domain.com/api/chat`

**Authentication:** All endpoints require Bearer token authentication via Laravel Sanctum.

## Table of Contents

1. [Authentication](#authentication)
2. [Real-time WebSocket Connection](#real-time-websocket-connection)
3. [API Endpoints](#api-endpoints)
4. [Response Format](#response-format)
5. [Error Handling](#error-handling)
6. [Code Examples](#code-examples)

---

## Authentication

### Getting Authentication Token

First, obtain an authentication token from your login endpoint:

```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": { ... },
    "token": "your_sanctum_token_here"
  }
}
```

### Using the Token

Include the token in all subsequent requests:

```http
Authorization: Bearer your_sanctum_token_here
```

---

## Real-time WebSocket Connection

The chat system uses Laravel Reverb for real-time messaging via WebSockets.

### Connection URL

```
ws://your-domain.com:8085/app/vil71wafgpp6do1miwn1?protocol=7&client=js&version=1.0.0
```

### Subscribing to Private Channel

After establishing WebSocket connection, subscribe to your private channel:

```javascript
// 1. Wait for connection_established event
{
  "event": "pusher:connection_established",
  "data": {
    "socket_id": "123456.789"
  }
}

// 2. Authenticate with server
POST /api/chat/auth
Content-Type: application/x-www-form-urlencoded

socket_id=123456.789&channel_name=private-chat.USER_ID

// 3. Subscribe with auth signature
{
  "event": "pusher:subscribe",
  "data": {
    "channel": "private-chat.USER_ID",
    "auth": "signature_from_server"
  }
}
```

### Receiving Messages

Listen for `message.new` events on your channel:

```json
{
  "event": "message.new",
  "channel": "private-chat.123",
  "data": {
    "id": 456,
    "from_id": 789,
    "to_id": 123,
    "body": "Hello!",
    "attachment": null,
    "seen": false,
    "created_at": "2025-01-10T12:30:00.000000Z"
  }
}
```

---

## API Endpoints

### 1. Get Contacts List

Get list of users the current user can message.

**Endpoint:** `GET /api/chat/contacts`

**Parameters:**
- `per_page` (optional, integer) - Items per page (default: 20, max: 50)
- `page` (optional, integer) - Page number (default: 1)
- `search` (optional, string) - Search by name

**Example Request:**
```http
GET /api/chat/contacts?per_page=20&page=1&search=john
Authorization: Bearer your_token
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "John Doe",
      "email": "john@example.com",
      "user_type": "student",
      "avatar": "https://your-domain.com/storage/avatars/john.jpg",
      "is_online": true,
      "last_seen": "2025-01-10T12:25:00.000000Z",
      "last_message": {
        "id": 456,
        "body": "Hey, how are you?",
        "is_own": false,
        "created_at": "2025-01-10T12:20:00.000000Z"
      },
      "unread_count": 3
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3
  }
}
```

---

### 2. Get Messages for Conversation

Fetch message history with a specific contact.

**Endpoint:** `GET /api/chat/messages`

**Parameters:**
- `contact_id` (required, integer) - ID of the contact
- `per_page` (optional, integer) - Messages per page (default: 30, max: 100)
- `page` (optional, integer) - Page number (default: 1)

**Example Request:**
```http
GET /api/chat/messages?contact_id=123&per_page=30&page=1
Authorization: Bearer your_token
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 456,
      "body": "Hello!",
      "from_id": 789,
      "to_id": 123,
      "is_own": true,
      "seen": false,
      "attachment": null,
      "created_at": "2025-01-10T12:20:00.000000Z",
      "updated_at": "2025-01-10T12:20:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 30,
    "total": 145,
    "last_page": 5
  }
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "You are not allowed to message this user"
}
```

---

### 3. Send Message

Send a new message to a contact.

**Endpoint:** `POST /api/chat/messages`

**Parameters:**
- `to_id` (required, integer) - Recipient user ID
- `message` (required, string, max: 5000) - Message text
- `attachment` (optional, file) - File attachment (max size from config)

**Example Request:**
```http
POST /api/chat/messages
Authorization: Bearer your_token
Content-Type: application/json

{
  "to_id": 123,
  "message": "Hello, how are you?"
}
```

**With File Attachment:**
```http
POST /api/chat/messages
Authorization: Bearer your_token
Content-Type: multipart/form-data

to_id=123
message=Check this out!
attachment=[file]
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Message sent successfully",
  "data": {
    "id": 789,
    "body": "Hello, how are you?",
    "from_id": 456,
    "to_id": 123,
    "is_own": true,
    "seen": false,
    "attachment": null,
    "created_at": "2025-01-10T12:30:00.000000Z",
    "updated_at": "2025-01-10T12:30:00.000000Z"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "to_id": ["The to_id field is required."],
    "message": ["The message must not be greater than 5000 characters."]
  }
}
```

---

### 4. Mark Messages as Read

Mark all messages from a contact as read.

**Endpoint:** `POST /api/chat/messages/mark-read`

**Parameters:**
- `contact_id` (required, integer) - Contact user ID

**Example Request:**
```http
POST /api/chat/messages/mark-read
Authorization: Bearer your_token
Content-Type: application/json

{
  "contact_id": 123
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Messages marked as read"
}
```

---

### 5. Get Unread Count

Get total count of unread messages.

**Endpoint:** `GET /api/chat/unread-count`

**Example Request:**
```http
GET /api/chat/unread-count
Authorization: Bearer your_token
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "unread_count": 12
  }
}
```

---

### 6. Search Users

Search for users to start a conversation.

**Endpoint:** `GET /api/chat/search`

**Parameters:**
- `query` (required, string, min: 2) - Search query
- `per_page` (optional, integer) - Results per page (default: 10, max: 50)

**Example Request:**
```http
GET /api/chat/search?query=john&per_page=10
Authorization: Bearer your_token
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "John Doe",
      "email": "john@example.com",
      "user_type": "teacher",
      "avatar": "https://your-domain.com/storage/avatars/john.jpg",
      "is_online": true,
      "last_seen": "2025-01-10T12:25:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 3,
    "last_page": 1
  }
}
```

---

### 7. Get User Info

Get detailed information about a specific user.

**Endpoint:** `GET /api/chat/user-info`

**Parameters:**
- `user_id` (required, integer) - Target user ID

**Example Request:**
```http
GET /api/chat/user-info?user_id=123
Authorization: Bearer your_token
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    "user_type": "student",
    "avatar": "https://your-domain.com/storage/avatars/john.jpg",
    "is_online": true,
    "last_seen": "2025-01-10T12:25:00.000000Z",
    "last_message": {
      "id": 456,
      "body": "Hey!",
      "is_own": false,
      "created_at": "2025-01-10T12:20:00.000000Z"
    },
    "unread_count": 2
  }
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "You are not allowed to view this user"
}
```

---

### 8. Delete Message

Delete a message (only sender can delete their own messages).

**Endpoint:** `DELETE /api/chat/messages`

**Parameters:**
- `message_id` (required, integer) - Message ID to delete

**Example Request:**
```http
DELETE /api/chat/messages
Authorization: Bearer your_token
Content-Type: application/json

{
  "message_id": 789
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Message deleted successfully"
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "You can only delete your own messages"
}
```

---

## Response Format

### Success Response Structure

All successful responses follow this structure:

```json
{
  "success": true,
  "data": { ... },
  "meta": { ... } // Only for paginated responses
}
```

### Error Response Structure

All error responses follow this structure:

```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... } // Only for validation errors (422)
}
```

### HTTP Status Codes

- `200` - Success
- `201` - Resource created
- `422` - Validation error
- `403` - Forbidden (permission denied)
- `404` - Not found
- `500` - Server error

---

## Error Handling

### Common Error Scenarios

1. **Authentication Error (401)**
```json
{
  "message": "Unauthenticated."
}
```
**Solution:** Check if token is valid and included in request headers.

2. **Permission Denied (403)**
```json
{
  "success": false,
  "message": "You are not allowed to message this user"
}
```
**Solution:** User doesn't have permission to perform this action based on their role.

3. **Validation Error (422)**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "contact_id": ["The contact_id field is required."]
  }
}
```
**Solution:** Fix the request parameters according to the errors object.

---

## Code Examples

### Flutter/Dart Example

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class ChatAPI {
  final String baseUrl = 'https://your-domain.com/api/chat';
  final String token;

  ChatAPI(this.token);

  Future<List<Contact>> getContacts({int page = 1, int perPage = 20}) async {
    final response = await http.get(
      Uri.parse('$baseUrl/contacts?page=$page&per_page=$perPage'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return (data['data'] as List)
          .map((contact) => Contact.fromJson(contact))
          .toList();
    } else {
      throw Exception('Failed to load contacts');
    }
  }

  Future<Message> sendMessage(int toId, String messageText) async {
    final response = await http.post(
      Uri.parse('$baseUrl/messages'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: json.encode({
        'to_id': toId,
        'message': messageText,
      }),
    );

    if (response.statusCode == 201) {
      final data = json.decode(response.body);
      return Message.fromJson(data['data']);
    } else {
      throw Exception('Failed to send message');
    }
  }

  Future<List<Message>> getMessages(int contactId, {int page = 1}) async {
    final response = await http.get(
      Uri.parse('$baseUrl/messages?contact_id=$contactId&page=$page'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return (data['data'] as List)
          .map((message) => Message.fromJson(message))
          .toList();
    } else {
      throw Exception('Failed to load messages');
    }
  }
}
```

### Swift/iOS Example

```swift
import Foundation

class ChatAPI {
    let baseURL = "https://your-domain.com/api/chat"
    let token: String

    init(token: String) {
        self.token = token
    }

    func getContacts(page: Int = 1, perPage: Int = 20, completion: @escaping (Result<ContactsResponse, Error>) -> Void) {
        guard let url = URL(string: "\(baseURL)/contacts?page=\(page)&per_page=\(perPage)") else { return }

        var request = URLRequest(url: url)
        request.addValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.addValue("application/json", forHTTPHeaderField: "Accept")

        URLSession.shared.dataTask(with: request) { data, response, error in
            if let error = error {
                completion(.failure(error))
                return
            }

            guard let data = data else { return }

            do {
                let contactsResponse = try JSONDecoder().decode(ContactsResponse.self, from: data)
                completion(.success(contactsResponse))
            } catch {
                completion(.failure(error))
            }
        }.resume()
    }

    func sendMessage(toId: Int, message: String, completion: @escaping (Result<MessageResponse, Error>) -> Void) {
        guard let url = URL(string: "\(baseURL)/messages") else { return }

        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.addValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.addValue("application/json", forHTTPHeaderField: "Content-Type")
        request.addValue("application/json", forHTTPHeaderField: "Accept")

        let body: [String: Any] = ["to_id": toId, "message": message]
        request.httpBody = try? JSONSerialization.data(withJSONObject: body)

        URLSession.shared.dataTask(with: request) { data, response, error in
            if let error = error {
                completion(.failure(error))
                return
            }

            guard let data = data else { return }

            do {
                let messageResponse = try JSONDecoder().decode(MessageResponse.self, from: data)
                completion(.success(messageResponse))
            } catch {
                completion(.failure(error))
            }
        }.resume()
    }
}
```

### React Native/JavaScript Example

```javascript
const CHAT_API_BASE_URL = 'https://your-domain.com/api/chat';

class ChatAPI {
  constructor(token) {
    this.token = token;
  }

  async getContacts(page = 1, perPage = 20, search = '') {
    const response = await fetch(
      `${CHAT_API_BASE_URL}/contacts?page=${page}&per_page=${perPage}&search=${search}`,
      {
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Accept': 'application/json',
        },
      }
    );

    if (!response.ok) {
      throw new Error('Failed to fetch contacts');
    }

    return await response.json();
  }

  async sendMessage(toId, message) {
    const response = await fetch(`${CHAT_API_BASE_URL}/messages`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        to_id: toId,
        message: message,
      }),
    });

    if (!response.ok) {
      throw new Error('Failed to send message');
    }

    return await response.json();
  }

  async getMessages(contactId, page = 1) {
    const response = await fetch(
      `${CHAT_API_BASE_URL}/messages?contact_id=${contactId}&page=${page}`,
      {
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Accept': 'application/json',
        },
      }
    );

    if (!response.ok) {
      throw new Error('Failed to fetch messages');
    }

    return await response.json();
  }

  async markAsRead(contactId) {
    const response = await fetch(`${CHAT_API_BASE_URL}/messages/mark-read`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        contact_id: contactId,
      }),
    });

    if (!response.ok) {
      throw new Error('Failed to mark messages as read');
    }

    return await response.json();
  }
}

export default ChatAPI;
```

---

## Permission System

The chat system has a sophisticated permission system that controls who can message whom:

### Role-Based Permissions

1. **Super Admin** - Can message anyone
2. **Academy Admin** - Can message all users in their academy
3. **Supervisor** - Can message all users in their academy
4. **Student** - Can message:
   - Their teachers (Quran & Academic)
   - Their parents
   - Academy admin & supervisors
5. **Teacher** - Can message:
   - Their students
   - Academy admin & supervisors
6. **Parent** - Can message:
   - Their children
   - Their children's teachers
   - Academy admin

### Permission Caching

Permissions are cached for 1 hour to improve performance. The cache is automatically cleared when user relationships change.

---

## Best Practices

1. **Always handle pagination** - Don't try to load all contacts/messages at once
2. **Implement pull-to-refresh** - Allow users to refresh contacts and messages
3. **Cache data locally** - Store messages and contacts in local database
4. **Handle offline mode** - Queue messages when offline and send when back online
5. **WebSocket reconnection** - Implement automatic reconnection with exponential backoff
6. **Error handling** - Always show user-friendly error messages
7. **Rate limiting** - Respect API rate limits (implement retry with backoff)
8. **Token refresh** - Implement token refresh mechanism before expiry

---

## Support

For issues or questions, please contact the development team or open an issue in the project repository.

**Last Updated:** 2025-01-10
**API Version:** 1.0
