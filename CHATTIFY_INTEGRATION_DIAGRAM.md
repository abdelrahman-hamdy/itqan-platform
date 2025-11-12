# Chattify Integration Architecture Diagram
## Itqan Platform

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         FRONTEND (User Interface)                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌───────────────────────┐  ┌──────────────────────┐ ┌────────────────┐ │
│  │  chat-layout.blade    │  │ chat-interface.blade │ │ chat-enhanced  │ │
│  │  ├─ RTL/LTR support   │  │ ├─ Message display  │ │     .css       │ │
│  │  ├─ Role-based nav    │  │ ├─ Timestamps       │ │                │ │
│  │  └─ Meta tags (CSRF)  │  │ ├─ Read/delivered   │ └────────────────┘ │
│  └───────────────────────┘  │ └─ Status badge     │                    │
│                                 └──────────────────────┘                 │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │            JavaScript Real-time System                           │   │
│  │  (chat-system-reverb.js)                                         │   │
│  │  ├─ Echo initialization (Reverb)                                │   │
│  │  ├─ Private channel listeners                                    │   │
│  │  ├─ Message received handler                                     │   │
│  │  ├─ Typing indicators                                            │   │
│  │  ├─ Notification permissions                                     │   │
│  │  └─ Service worker integration                                   │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                           │                                              │
│                           ▼                                              │
│                    Pusher/Echo.js                                       │
│                   (WebSocket Client)                                    │
└─────────────────────────────────────────────────────────────────────────┘
                             │
                    ┌────────┴────────┐
                    │    HTTP/WS      │
                    ▼                 ▼
        ┌─────────────────────────────────┐
        │    REVERB (WebSocket Server)    │
        ├─────────────────────────────────┤
        │  Port: 8085 (configurable)      │
        │  App Key: vil71wafgpp6do1miwn1 │
        │  Broadcasting: private/presence │
        └─────────────────────────────────┘
                    │
                    │ HTTP requests
                    │
                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         LARAVEL BACKEND                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌──────────────────────────┐  Routes & Middleware                      │
│  │  routes/chatify/web.php  │  ├─ ['web', 'auth'] middleware          │
│  ├──────────────────────────┤  ├─ Academy context from user.academy_id │
│  │ GET  /chat               │  └─ Dynamic ID routes for users/groups   │
│  │ POST /chat/sendMessage   │                                           │
│  │ POST /chat/fetchMessages │                                           │
│  │ POST /chat/groups/* ...  │                                           │
│  │ GET  /chat/{id}          │                                           │
│  └──────────────────────────┘                                           │
│                                                                           │
│  ┌───────────────────────────────────────────┐                         │
│  │  MessagesController (1833 lines)          │                         │
│  ├───────────────────────────────────────────┤                         │
│  │ Core Methods:                             │                         │
│  │  ├─ send()                                │                         │
│  │  ├─ sendToGroup()                         │                         │
│  │  ├─ fetch()                               │                         │
│  │  ├─ fetchGroupMessages()                  │                         │
│  │  ├─ markRead(), markDelivered()           │                         │
│  │  ├─ createGroup(), addGroupMember()       │                         │
│  │  ├─ typing(), getOnlineUsers()            │                         │
│  │  ├─ favorite(), search()                  │                         │
│  │  └─ canMessage() [Auth check]             │                         │
│  └───────────────────────────────────────────┘                         │
│           │                                                             │
│           │ Uses                                                        │
│           ▼                                                             │
│  ┌─────────────────────────────────────────┐                          │
│  │  ChatPermissionService (303 lines)      │                          │
│  ├─────────────────────────────────────────┤                          │
│  │  canMessage()                           │                          │
│  │  ├─ Check academy match                 │                          │
│  │  ├─ Check role hierarchy                │                          │
│  │  └─ Verify relationships                │                          │
│  │      ├─ Teacher-student (sessions)      │                          │
│  │      ├─ Parent-child (parent_students)  │                          │
│  │      └─ Subscriptions/circles           │                          │
│  │                                          │                          │
│  │  Caching:                               │                          │
│  │  ├─ 1-hour TTL on permission checks     │                          │
│  │  └─ Cache key: sorted user IDs          │                          │
│  └─────────────────────────────────────────┘                          │
│           │                                                             │
│           │ Queries                                                     │
│           ▼                                                             │
│  ┌─────────────────────────────────────────┐                          │
│  │  Database Models                        │                          │
│  ├─────────────────────────────────────────┤                          │
│  │  User.php                               │                          │
│  │  ├─ getChatifyName()                    │                          │
│  │  ├─ getChatifyAvatar()                  │                          │
│  │  ├─ chatGroups() [BelongsToMany]       │                          │
│  │  └─ ownedChatGroups() [HasMany]        │                          │
│  │                                         │                          │
│  │  ChatGroup.php (272 lines)             │                          │
│  │  ├─ members() [BelongsToMany]          │                          │
│  │  ├─ messages() [HasMany]                │                          │
│  │  ├─ canSendMessage()                    │                          │
│  │  └─ addMember(), removeMember()         │                          │
│  │                                         │                          │
│  │  ChatGroupMember.php (91 lines)        │                          │
│  │  ├─ isAdmin(), isModerator()            │                          │
│  │  ├─ canManageGroup()                    │                          │
│  │  └─ markAsRead(), incrementUnread()    │                          │
│  │                                         │                          │
│  │  ChMessage.php (minimal)                │                          │
│  │  └─ Uses Chatify's UUID trait           │                          │
│  └─────────────────────────────────────────┘                          │
│                                                                           │
│  ┌─────────────────────────────────────────┐                          │
│  │  Broadcasting Events                    │                          │
│  ├─────────────────────────────────────────┤                          │
│  │  MessageSentEvent                       │                          │
│  │  ├─ Broadcast to: chat.{senderId},     │                          │
│  │  │                 chat.{receiverId}   │                          │
│  │  └─ Data: senderId, receiverId, ...    │                          │
│  │                                         │                          │
│  │  MessageReadEvent                       │                          │
│  │  ├─ Broadcast to: chat.{userId},       │                          │
│  │  │                 chat.{senderId}    │                          │
│  │  └─ Notifies sender of read status     │                          │
│  │                                         │                          │
│  │  MessageDeliveredEvent                  │                          │
│  │  └─ Similar delivery tracking          │                          │
│  └─────────────────────────────────────────┘                          │
│           │                                                             │
│           │ Dispatch after DB save                                     │
│           ▼                                                             │
│  ┌────────────────────────────────────────┐                           │
│  │  Broadcast::channel() [routes/channels.php]                         │
│  ├────────────────────────────────────────┤                           │
│  │  chat.{userId}                         │                           │
│  │  ├─ Private channel                    │                           │
│  │  └─ Auth: user_id === authenticated    │                           │
│  │                                        │                           │
│  │  conversation.{conversationId}         │                           │
│  │  ├─ Private conversation               │                           │
│  │  └─ Auth: has messages check           │                           │
│  │                                        │                           │
│  │  presence-group.{groupId}              │                           │
│  │  ├─ Presence channel                   │                           │
│  │  └─ Auth: is group member check        │                           │
│  │                                        │                           │
│  │  presence-chat.{conversationId}        │                           │
│  │  └─ Who's typing indicator             │                           │
│  └────────────────────────────────────────┘                           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
                             │
                             ▼
        ┌─────────────────────────────────┐
        │      DATABASE (MySQL/PostgreSQL) │
        ├─────────────────────────────────┤
        │                                 │
        │  ch_messages                    │ ← Chattify core
        │  ├─ id (UUID)                   │
        │  ├─ from_id, to_id              │
        │  ├─ body, attachment            │
        │  ├─ seen, created_at            │
        │  └─ Custom: academy_id,         │
        │    message_type, is_read,       │
        │    delivered_at, is_edited,     │
        │    is_pinned, voice_duration,   │
        │    reply_to, forwarded_from     │
        │                                 │
        │  ch_favorites                   │ ← Chattify core
        │  ├─ user_id, favorite_id        │
        │  └─ Custom: academy_id          │
        │                                 │
        │  chat_groups                    │ ← Custom
        │  ├─ id, name, type              │
        │  ├─ academy_id, owner_id        │
        │  ├─ max_members                 │
        │  └─ Entity links (circle, etc.) │
        │                                 │
        │  chat_group_members             │ ← Custom
        │  ├─ group_id, user_id           │
        │  ├─ role (admin|moderator|...)  │
        │  ├─ can_send_messages, is_muted │
        │  └─ unread tracking             │
        │                                 │
        │  message_reactions              │ ← Custom enhancement
        │  message_edits                  │ ← Custom enhancement
        │  chat_blocked_users             │ ← Custom enhancement
        │  push_subscriptions             │ ← Custom enhancement
        │  typing_indicators              │ ← Custom enhancement
        │                                 │
        │  users                          │ ← Extended
        │  ├─ chat_settings (JSON)        │
        │  ├─ last_typing_at              │
        │  └─ last_seen_at                │
        │                                 │
        └─────────────────────────────────┘
```

---

## Permission Flow Diagram

```
User A wants to message User B
        │
        ▼
    Auth Middleware
    (user logged in?)
        │
        ▼
MessagesController::canMessage()
        │
        ├──→ Same Academy? ✓ (or super admin)
        │
        ├──→ Role-based check:
        │    ├─ Super Admin?           → Allow
        │    ├─ Academy Admin?         → Allow
        │    ├─ Supervisor?            → Allow
        │    ├─ Student?
        │    │  └─ Can message teacher? (session/subscription check)
        │    │  └─ Can message parent?  (parent_students check)
        │    │  └─ Can message admin?   → Allow
        │    ├─ Teacher?
        │    │  └─ Can message student? (relationship check)
        │    │  └─ Can message admin?   → Allow
        │    └─ Parent?
        │       └─ Can message child?   (parent_students check)
        │       └─ Can message teacher? (child's relationships)
        │       └─ Can message admin?   → Allow
        │
        ├──→ Check blocked list
        │    (chat_blocked_users)
        │
        ├──→ Cache check (1-hour TTL)
        │    (on subsequent calls)
        │
        ▼
    Allow / Deny
    Dispatch event or return 403
```

---

## Real-time Message Flow Diagram

```
User A sends message
        │
        ▼
POST /chat/sendMessage
        │
        ├─ Validate auth & permission
        ├─ Save to ch_messages
        │  └─ academy_id, timestamps, etc.
        │
        ├─ Dispatch MessageSentEvent
        │  └─ senderId, receiverId, academyId
        │
        └─ Broadcast to Reverb
           ├─ private-chat.{senderId}
           └─ private-chat.{receiverId}
                    │
                    ▼
           Reverb WebSocket Server
           (stores for subscribers)
                    │
              ┌─────┴─────┐
              │           │
              ▼           ▼
    User A's browser  User B's browser
    (if online)       (if online)
        │                 │
        ▼                 ▼
    Echo listener  Echo listener
    "message.sent"  "message.sent"
        │                 │
        ├─ Show in UI  ├─ Show in UI
        ├─ Scroll      ├─ Sound notif
        └─ Check read  └─ Badge count
                          │
                          ▼
                    User B sees message
                          │
                          ▼
                   User B clicks/opens
                          │
                          ▼
                   MessageReadEvent
                          │
                          ├─ Update is_read
                          ├─ Set read_at
                          │
                          └─ Broadcast to
                             private-chat.{senderId}
                                  │
                                  ▼
                         User A sees "read" badge
```

---

## Group Chat Structure Diagram

```
chat_groups (parent)
├─ id
├─ name
├─ type (quran_circle, academic_session, etc.)
├─ academy_id
├─ owner_id (user)
└─ Settings & metadata

        │
        ├─ 1:1 relation
        └─ quran_circle_id → quran_circles.id
           academic_session_id → academic_sessions.id
           interactive_course_id → interactive_courses.id
           etc.


chat_group_members (join table)
├─ id
├─ group_id → chat_groups.id
├─ user_id → users.id
├─ role (admin | moderator | member | observer)
├─ can_send_messages
├─ is_muted
└─ unread_count


ch_messages (messages in group)
├─ id
├─ from_id (sender)
├─ to_id (NULL for group messages)
├─ group_id → chat_groups.id
├─ body, attachment
├─ message_type (text|file|voice|image)
├─ is_read, read_at
├─ is_edited, edited_at
├─ is_pinned, pinned_at, pinned_by
└─ voice_duration (for voice messages)


Related Tables (enhanced features):
├─ message_reactions
│  ├─ message_id → ch_messages.id
│  ├─ user_id
│  └─ reaction (emoji/type)
│
├─ chat_message_edits
│  ├─ message_id → ch_messages.id
│  ├─ edited_by
│  ├─ original_body
│  └─ edited_body
│
└─ chat_blocked_users
   ├─ user_id
   └─ blocked_user_id
```

---

## Configuration Dependency Diagram

```
config/chat.php (main source of truth)
├─ name
├─ storage_disk_name
├─ routes (prefix, middleware, namespace)
├─ api_routes
├─ reverb (WebSocket config)
├─ user_avatar (folder, default)
├─ gravatar (enabled, size, imageset)
├─ attachments (folder, allowed types, max size)
├─ colors (palette)
├─ sounds (enabled, path)
├─ pagination (per_page, contacts_per_page)
└─ cache (enabled, ttl, prefix)
     │
     ├─ Referenced by config/chatify.php
     │  (bridge for Chatify compatibility)
     │
     ├─ Loaded in MessagesController
     │  └─ config('chat.*')
     │
     ├─ Used in ChatPermissionService
     │  └─ config('chat.cache.*')
     │
     ├─ Loaded in Views
     │  └─ config('chat.sounds')
     │
     └─ Referenced in Broadcasting
        └─ config('chat.reverb.*')


config/broadcasting.php
├─ default: 'reverb'
└─ connections.reverb
   ├─ driver: 'reverb'
   ├─ key (REVERB_APP_KEY)
   ├─ secret (REVERB_APP_SECRET)
   ├─ app_id (REVERB_APP_ID)
   └─ options (host, port, scheme)
        │
        └─ Used by Laravel Echo (browser)
           └─ To establish WebSocket connection


config/reverb.php (server config)
├─ servers.reverb
│  ├─ host: '0.0.0.0'
│  ├─ port: 8080 (server listens here)
│  └─ max_request_size: 10,000
│
└─ apps.apps[]
   ├─ key (REVERB_APP_KEY)
   ├─ secret (REVERB_APP_SECRET)
   ├─ app_id (REVERB_APP_ID)
   └─ options (host, port, scheme)
        │
        └─ Used by: php artisan reverb:start
           (starts WebSocket server with these credentials)
```

---

## User Type Permission Matrix

```
                 | Student | Parent | QTeacher | ATeacher | Supervisor | Admin | Super Admin
─────────────────┼─────────┼────────┼──────────┼──────────┼────────────┼──────┼────────────
Can message:    │         │        │          │          │            │      │
  - Anyone      │    ✗    │   ✗    │    ✗     │    ✗     │     ✓      │  ✓   │     ✓
  - In Academy  │    ✗    │   ✗    │    ✗     │    ✗     │     ✓      │  ✓   │     ✓
  - With link   │    ✓    │   ✓    │    ✓     │    ✓     │     N/A    │  N/A │     N/A
  - Own group   │    ✓    │   ✓    │    ✓     │    ✓     │     ✓      │  ✓   │     ✓
  - Other group │    ✓    │   ✓    │    ✓     │    ✓     │     ✓      │  ✓   │     ✓

Academy scope:  │   Own   │  Own   │   Own    │   Own    │    Own     │ Own  │    Any

Messaging links:
  Student  ────→ Teachers (via relationships)
         ────→ Parents (via parent_students)
         ────→ Admin/Supervisors (allowed)

  Parent   ────→ Own children (via parent_students)
         ────→ Children's teachers (via student relationships)
         ────→ Admin (allowed)

  Teacher  ────→ Own students (via sessions/subscriptions/circles)
         ────→ Admin/Supervisors (allowed)

  Admin/Sup ───→ All in academy

  Super Admin ──→ All users everywhere
```

---

**File:** CHATTIFY_INTEGRATION_DIAGRAM.md  
**Last Updated:** 2025-11-12  
**Version:** 1.0
