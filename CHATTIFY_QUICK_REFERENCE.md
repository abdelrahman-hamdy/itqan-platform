# Chattify Integration - Quick Reference Guide
## Itqan Platform

### Key Files & Locations

```
Configuration:
  /config/chatify.php                    (bridge configuration)
  /config/chat.php                       (main configuration)
  /config/broadcasting.php               (Reverb setup)
  /config/reverb.php                     (WebSocket config)
  /routes/channels.php                   (broadcast channels)

Models:
  /app/Models/User.php                   (getChatifyName, getChatifyAvatar, chat relations)
  /app/Models/ChMessage.php              (message model - minimal, extends Chatify)
  /app/Models/ChatGroup.php              (group chat model - 272 lines)
  /app/Models/ChatGroupMember.php        (membership model - 91 lines)

Controllers:
  /app/Http/Controllers/vendor/Chatify/MessagesController.php (1833 lines - ALL chat logic)

Services:
  /app/Services/ChatPermissionService.php (303 lines - permission checking + caching)
  /app/Services/ChatGroupService.php     (group management)

Views:
  /resources/views/components/chat/chat-layout.blade.php       (main layout)
  /resources/views/components/chat/chat-interface.blade.php    (message display)

JavaScript:
  /public/js/chat-system-reverb.js       (real-time system)

Styles:
  /public/css/chat-enhanced.css          (chat styling - RTL support)

Routes:
  /routes/chatify/web.php                (all chat routes)

Events:
  /app/Events/MessageSentEvent.php
  /app/Events/MessageReadEvent.php
  /app/Events/MessageDeliveredEvent.php

Migrations:
  2025_09_01_195332_add_academy_id_to_chatify_tables.php
  2025_09_01_201722_create_chat_groups_table.php
  2025_11_12_enhance_chat_system.php
```

### Database Tables

```
CHATTIFY CORE (Munafio/Chatify package):
  ch_messages         (UUIDs, from_id, to_id, body, attachment, seen, timestamps)
  ch_favorites        (user_id, favorite_id, created_at)

CUSTOM EXTENSIONS:
  ch_messages additions:
    ├── academy_id (multi-tenant)
    ├── message_type (text|file|voice|image)
    ├── is_read, read_at, delivered_at
    ├── is_edited, edited_at
    ├── is_pinned, pinned_at, pinned_by
    ├── voice_duration
    ├── reply_to (threading)
    └── forwarded_from (forwarding)

GROUP CHAT:
  chat_groups
  chat_group_members
    ├── role (admin|moderator|member|observer)
    ├── can_send_messages, is_muted
    ├── joined_at, last_read_at, unread_count

ENHANCED FEATURES:
  message_reactions
  chat_message_edits
  chat_blocked_users
  push_subscriptions
  typing_indicators

USER EXTENSIONS:
  users table additions:
    ├── chat_settings (JSON)
    ├── last_typing_at
    └── last_seen_at
```

### Route Summary

| Method | Route | Handler | Purpose |
|--------|-------|---------|---------|
| GET | /chat | index | Load chat interface |
| POST | /chat/sendMessage | send | Send direct message |
| POST | /chat/fetchMessages | fetch | Get conversation messages |
| POST | /chat/makeSeen | seen | Mark as seen |
| POST | /chat/chat/auth | pusherAuth | WebSocket auth |
| GET | /chat/{id} | index | Load chat with user (dynamic route) |
| POST | /chat/groups/send | sendToGroup | Send group message |
| POST | /chat/groups/create | createGroup | Create group |
| GET | /chat/groups | getGroups | List user's groups |
| POST | /chat/typing | typing | Send typing indicator |
| POST | /chat/messages/{id}/read | markRead | Mark as read |
| GET | /chat/online-users | getOnlineUsers | Get online status |

**All routes:** Protected by `['web', 'auth']` middleware

### Authentication & Authorization

**Permission Logic (ChatPermissionService + MessagesController)**

```
Super Admin           → Can message anyone
Academy Admin         → Can message all in academy
Supervisor           → Can message all in academy
Quran Teacher        → Can message students they teach + admin
Academic Teacher     → Can message students they teach + admin
Student              → Can message teachers + parents + admin
Parent               → Can message children + their teachers + admin
```

**Checks:**
1. Same academy requirement (except super admin)
2. Relationship verification:
   - Teacher-student: via quran_sessions, academic_sessions, subscriptions, circles
   - Parent-student: via parent_students table
3. Permission caching: 1-hour TTL for performance
4. Blocking check: chat_blocked_users table

### Real-time Configuration

```
Broadcast Driver:  reverb
Server:            127.0.0.1:8085
Protocol:          HTTP (configurable)
App Key:           vil71wafgpp6do1miwn1
App Secret:        auto2876cfpvt
App ID:            itqan-platform

Channels:
  - chat.{userId}              (private direct message channel)
  - conversation.{convId}      (private conversation channel)
  - presence-group.{groupId}   (presence for groups)
  - presence-chat.{convId}     (presence for conversations)
```

### User Type Methods (in User Model)

```php
getChatifyName(): string              // Display name for chat
getChatifyAvatar(): ?string           // Avatar URL or path
getChatifyInfo(): array               // Combined info object
getUserTypeLabel(): string            // Arabic localization

// Chat relationships
ownedChatGroups(): HasMany           // Groups owned by user
chatGroups(): BelongsToMany          // Groups user is member of
chatGroupMemberships(): HasMany      // Direct membership records
```

### Permission Service Methods

```php
canMessage($currentUser, $targetUser): bool
  - Returns true if user can message target user
  - Uses cached permission checks

filterAllowedContacts($currentUser, $userIds): array
  - Batch permission check
  - Returns array of messageable user IDs

clearUserCache($userId): void
  - Clear cached permissions for user

isTeacherOfStudent($teacher, $student, $academyId): bool
isParentOfStudent($parent, $student): bool
isTeacherOfParentChildren($teacher, $parent, $academyId): bool
  - Specific relationship checks
```

### Message Status States

```
Sent           → Message created, stored in DB
  ↓
Delivered      → Server received confirmation, delivered_at set
  ↓
Read           → Recipient opened message, read_at set, is_read = true
  
Additional:
  - Edited       → is_edited, edited_at set
  - Pinned       → is_pinned, pinned_at, pinned_by set
  - Reacted      → message_reactions table entry
  - Replied to   → reply_to foreign key set
  - Forwarded    → forwarded_from foreign key set
```

### Key Configuration Values

```ini
# .env settings
BROADCAST_DRIVER=reverb
REVERB_APP_KEY=vil71wafgpp6do1miwn1
REVERB_APP_SECRET=auto2876cfpvt
REVERB_HOST=127.0.0.1
REVERB_PORT=8085

# config/chat.php defaults
CHAT_ROUTES_PREFIX=chat
CHAT_API_ROUTES_PREFIX=api/chat
CHAT_CACHE_ENABLED=true
CHAT_CACHE_TTL=3600
CHAT_MAX_FILE_SIZE=150 (MB)
Pagination: 30 messages per page
```

### Important Notes for WireChat Migration

**Data to Preserve:**
- All ch_messages with extended columns
- All chat_group structure
- All permissions logic
- Academy isolation (critical)
- Message reactions and edit history
- User chat settings and preferences

**Features Heavily Customized:**
1. **Multi-tenancy** - academy_id added everywhere
2. **Group chat** - custom tables, role system
3. **Advanced messages** - reactions, edits, threading, pinning
4. **Permissions** - complex relationship-based logic
5. **Real-time** - Reverb integration with custom events

**Risk Areas:**
- Route name changes might break frontend links
- Event format differences
- Channel naming conventions
- Database column compatibility
- Permission caching strategy

### Quick Test Commands

```bash
# Check routes
php artisan route:list | grep chat

# Check database
php artisan tinker
  >>> \App\Models\ChMessage::count()
  >>> \App\Models\ChatGroup::count()
  >>> \App\Models\ChatGroupMember::count()

# Check user chat info
  >>> auth()->user()->getChatifyName()
  >>> auth()->user()->getChatifyAvatar()
  >>> auth()->user()->chatGroups()->count()

# Check permissions
  >>> app(\App\Services\ChatPermissionService::class)
  >>>   ->canMessage($user1, $user2)

# Check events
php artisan event:list
  MessageSentEvent
  MessageReadEvent
  MessageDeliveredEvent

# Reverb status
php artisan reverb:start
```

### Troubleshooting

```
No messages appear:
  → Check academy_id matches in ch_messages
  → Verify permission via ChatPermissionService
  → Check Web.php route prefix and namespace

Real-time not working:
  → Check BROADCAST_DRIVER=reverb
  → Verify Reverb server running (reverb:start)
  → Check browser console for Echo.js errors
  → Verify private channel auth in routes/channels.php

Permission denied:
  → Check both users in same academy
  → Verify relationship (teacher-student, parent-child, etc.)
  → Check ChatPermissionService::canMessage()
  → Look for entries in chat_blocked_users

Group chat issues:
  → Verify user in chat_group_members
  → Check role allows can_send_messages=true
  → Verify group_id in ch_messages
  → Check is_muted pivot field
```

---

**Last Updated:** 2025-11-12  
**Version:** 1.0  
**Full Analysis:** See CHATTIFY_COMPREHENSIVE_ANALYSIS.md
