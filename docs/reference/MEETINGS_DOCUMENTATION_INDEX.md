# Meetings Management System - Complete Documentation Index

## Overview

This directory contains comprehensive documentation for the Itqan Platform's meetings management system, which handles video conferencing through LiveKit integration, automatic meeting creation, and detailed attendance tracking.

---

## Documents Included

### 1. MEETINGS_SYSTEM_ANALYSIS.md (1,414 lines - 35 KB)

**Most comprehensive technical reference**

Contains detailed analysis of:
- Meeting creation and management flow
- LiveKit integration architecture
- Meeting room naming and token generation
- Automatic meeting creation scheduling
- Meeting lifecycle and cleanup processes
- Attendance tracking mechanisms
- Controllers and API endpoints
- Data channel services for real-time communication
- Database schema design
- Routes and API structure
- Console commands
- Configuration and environment setup
- Architecture patterns and design principles
- Error handling and validation
- Performance considerations
- Multi-tenant support

**Use this document when:**
- You need deep technical understanding
- Debugging complex meeting flows
- Understanding attendance calculation logic
- Reviewing database schema
- Implementing new features
- Understanding service layer architecture

---

### 2. MEETINGS_QUICK_REFERENCE.md (389 lines - 9.9 KB)

**Quick lookup guide with code examples**

Organized sections:
- Key files overview (Models, Services, Controllers, Commands)
- Quick start guide for common tasks
- Configuration instructions
- Database schema quick reference
- API endpoints summary
- Attendance status levels
- Common tasks with code examples
- Troubleshooting guide
- Performance optimization tips
- Key dependencies

**Use this document when:**
- You need quick answers
- Looking for code examples
- Setting up configuration
- Troubleshooting issues
- Finding specific endpoints
- Understanding quick task execution

---

### 3. MEETINGS_ARCHITECTURE_DIAGRAM.md (892 lines - 64 KB)

**Visual representations and flows**

Contains ASCII diagrams for:
1. Overall system architecture
2. Meeting lifecycle flow
3. Automatic creation & cleanup flow
4. Token generation & access flow
5. Attendance tracking flow
6. Authorization & permission matrix
7. Key component interactions

**Use this document when:**
- Understanding system flow visually
- Learning architecture relationships
- Training new developers
- Understanding data flow
- Reviewing permission structures
- Explaining system to stakeholders

---

## Quick Navigation by Use Case

### I want to understand how meetings are created

1. Read: **MEETINGS_QUICK_REFERENCE.md** - Section "1. Create a Meeting for a Session"
2. Deep dive: **MEETINGS_SYSTEM_ANALYSIS.md** - Section "1. MEETING CREATION & MANAGEMENT"
3. Visualize: **MEETINGS_ARCHITECTURE_DIAGRAM.md** - Diagram "2. Meeting Lifecycle Flow"

### I need to track user attendance

1. Quick start: **MEETINGS_QUICK_REFERENCE.md** - Section "3. Track User Attendance"
2. Details: **MEETINGS_SYSTEM_ANALYSIS.md** - Section "6. MEETING ATTENDANCE TRACKING"
3. Flow: **MEETINGS_ARCHITECTURE_DIAGRAM.md** - Diagram "5. Attendance Tracking Flow"

### I want to set up auto-creation of meetings

1. Quick reference: **MEETINGS_QUICK_REFERENCE.md** - Section "Configuration"
2. Full details: **MEETINGS_SYSTEM_ANALYSIS.md** - Section "4. AUTOMATIC MEETING CREATION & SCHEDULING"
3. Process flow: **MEETINGS_ARCHITECTURE_DIAGRAM.md** - Diagram "3. Auto Creation & Cleanup Flow"

### I need to debug a LiveKit integration issue

1. Quick troubleshooting: **MEETINGS_QUICK_REFERENCE.md** - Section "Troubleshooting"
2. Implementation details: **MEETINGS_SYSTEM_ANALYSIS.md** - Section "2. LIVEKIT INTEGRATION"
3. Configuration: **MEETINGS_QUICK_REFERENCE.md** - Section "Configuration"

### I'm implementing new API endpoints

1. Existing endpoints: **MEETINGS_QUICK_REFERENCE.md** - Section "API Endpoints Summary"
2. Controller implementation: **MEETINGS_SYSTEM_ANALYSIS.md** - Section "7. CONTROLLERS & API ENDPOINTS"
3. Authorization: **MEETINGS_ARCHITECTURE_DIAGRAM.md** - Diagram "6. Authorization & Permission Matrix"

### I need to understand database schema

1. Overview: **MEETINGS_QUICK_REFERENCE.md** - Section "Database Schema Reference"
2. Detailed schema: **MEETINGS_SYSTEM_ANALYSIS.md** - Section "9. DATABASE SCHEMA"
3. Models: **MEETINGS_SYSTEM_ANALYSIS.md** - Section "6. MODEL LAYER"

### I'm training new developers

1. Start with: **MEETINGS_QUICK_REFERENCE.md** - Section "Quick Start Guide"
2. Then: **MEETINGS_ARCHITECTURE_DIAGRAM.md** - All diagrams
3. Reference: **MEETINGS_SYSTEM_ANALYSIS.md** - For detailed questions

---

## Key Topics Quick Reference

| Topic | Document | Section |
|-------|----------|---------|
| **Meeting Creation** | ANALYSIS | 1. Meeting Creation & Management |
| **LiveKit Integration** | ANALYSIS | 2. LiveKit Integration |
| **Room Naming & Tokens** | ANALYSIS | 3. Meeting Rooms & Tokens |
| **Auto-Scheduling** | ANALYSIS | 4. Automatic Meeting Creation |
| **Lifecycle & Cleanup** | ANALYSIS | 5. Meeting Lifecycle & Cleanup |
| **Attendance Tracking** | ANALYSIS | 6. Meeting Attendance Tracking |
| **API Controllers** | ANALYSIS | 7. Controllers & API Endpoints |
| **Data Channels** | ANALYSIS | 8. Data Channel Service |
| **Database Schema** | ANALYSIS | 9. Database Schema |
| **Routes & APIs** | ANALYSIS | 10. Routes & API Structure |
| **Console Commands** | ANALYSIS | 11. Console Commands |
| **Configuration** | ANALYSIS/QUICK | 12. Configuration / Configuration section |
| **Architecture Patterns** | ANALYSIS | 13. Architecture Patterns |
| **Error Handling** | ANALYSIS | 14. Error Handling & Validation |
| **Performance** | ANALYSIS | 15. Performance Considerations |
| **Multi-Tenant** | ANALYSIS | 16. Multi-Tenant Support |
| **System Flow** | DIAGRAMS | All diagrams |
| **API Examples** | QUICK | Quick Start Guide |
| **Troubleshooting** | QUICK | Troubleshooting section |

---

## File Structure

```
Itqan Platform Root
├── MEETINGS_DOCUMENTATION_INDEX.md (this file)
├── MEETINGS_SYSTEM_ANALYSIS.md (comprehensive analysis)
├── MEETINGS_QUICK_REFERENCE.md (quick lookup guide)
├── MEETINGS_ARCHITECTURE_DIAGRAM.md (visual flows)
│
├── app/
│   ├── Models/
│   │   ├── BaseSession.php
│   │   ├── MeetingAttendance.php
│   │   ├── QuranSession.php
│   │   └── AcademicSession.php
│   │
│   ├── Services/
│   │   ├── LiveKitService.php
│   │   ├── AutoMeetingCreationService.php
│   │   ├── SessionMeetingService.php
│   │   ├── AcademicSessionMeetingService.php
│   │   ├── MeetingAttendanceService.php
│   │   └── MeetingDataChannelService.php
│   │
│   ├── Http/Controllers/
│   │   ├── LiveKitMeetingController.php
│   │   └── UnifiedMeetingController.php
│   │
│   └── Console/Commands/
│       ├── CreateScheduledMeetingsCommand.php
│       └── CleanupExpiredMeetingsCommand.php
│
├── database/
│   └── migrations/
│       ├── *_create_meeting_attendances_table.php
│       ├── *_add_meeting_fields_to_sessions_tables.php
│       └── *_add_livekit_to_meeting_source_enum.php
│
├── routes/
│   └── api.php (meeting endpoints)
│
└── config/
    └── livekit.php (LiveKit configuration)
```

---

## Common Code Patterns

### Creating a Meeting Programmatically
```php
$session = QuranSession::find($sessionId);
$meetingUrl = $session->generateMeetingLink([
    'max_participants' => 50,
    'recording_enabled' => false,
    'max_duration' => 120,
]);
```

### Getting Access Token
```php
$session = QuranSession::find($sessionId);
$user = Auth::user();
$token = $session->generateParticipantToken($user, [
    'can_publish' => true,
    'can_subscribe' => true,
]);
```

### Tracking Attendance
```php
// On join
$attendanceService->handleUserJoin($session, $user);

// On leave
$attendanceService->handleUserLeave($session, $user);

// Calculate after session ends
$results = $attendanceService->calculateFinalAttendance($session);
```

### Running Auto-Creation
```bash
php artisan meetings:create-scheduled --academy-id=1
php artisan meetings:cleanup-expired
```

---

## API Quick Reference

```
POST   /api/meetings/create              - Create meeting for session
GET    /api/meetings/{id}/token          - Get participant access token
GET    /api/meetings/{id}/info           - Get room info & participants
POST   /api/meetings/{id}/end            - End meeting
POST   /api/meeting/join                 - Record user join
POST   /api/meeting/leave                - Record user leave
```

---

## Database Quick Reference

### Key Tables
- `quran_sessions` - Quran session records with meeting fields
- `academic_sessions` - Academic session records with meeting fields
- `interactive_course_sessions` - Interactive course sessions
- `meeting_attendances` - Attendance tracking (join/leave cycles)

### Key Columns on Sessions
- `meeting_room_name` - LiveKit room identifier
- `meeting_link` - URL to join
- `meeting_id` - Meeting unique ID
- `meeting_data` - Full meeting configuration (JSON)
- `meeting_auto_generated` - Whether auto-created
- `meeting_expires_at` - Meeting expiration time

### Attendance Table Fields
- `session_id, user_id` - Unique key
- `join_leave_cycles` - JSON array of join/leave events
- `attendance_status` - 'present', 'late', 'partial', 'absent'
- `attendance_percentage` - 0-100% of session attended
- `is_calculated` - Whether final calculation done

---

## Configuration Quick Reference

### Environment Variables
```
LIVEKIT_API_KEY=your-api-key
LIVEKIT_API_SECRET=your-api-secret
LIVEKIT_SERVER_URL=wss://your-livekit-server.com
LIVEKIT_API_URL=https://your-livekit-server.com
```

### VideoSettings (Per Academy)
- `auto_create_meetings` - Enable auto-creation
- `auto_end_meetings` - Auto-end expired meetings
- `meeting_creation_minutes_before` - When to create before session
- `meeting_creation_time_start` - Daily start time
- `meeting_creation_time_end` - Daily end time
- `meeting_creation_blocked_days` - Days to skip

---

## Understanding the Documentation Structure

### MEETINGS_SYSTEM_ANALYSIS.md
- **Best for:** Deep understanding, implementation details, debugging
- **Structure:** Numbered sections (1-18)
- **Length:** 1,414 lines
- **Technical Level:** Advanced
- **Includes:** Code examples, SQL, configuration details

### MEETINGS_QUICK_REFERENCE.md
- **Best for:** Quick lookup, code examples, common tasks
- **Structure:** Flat sections with tables and code blocks
- **Length:** 389 lines
- **Technical Level:** Intermediate
- **Includes:** Copy-paste code examples, quick commands

### MEETINGS_ARCHITECTURE_DIAGRAM.md
- **Best for:** Understanding flow, visual learners, training
- **Structure:** ASCII diagrams with annotations
- **Length:** 892 lines
- **Technical Level:** All levels
- **Includes:** System diagrams, flow charts, interaction matrices

---

## Staying Up to Date

These documents were created based on the codebase as of November 12, 2025.

When updating code:
1. Update the relevant code file
2. Update the corresponding section in these docs
3. Update diagrams if architecture changes
4. Keep QUICK_REFERENCE synchronized with code examples

---

## Related Documentation

See also:
- `.env.example` - Environment variable templates
- `config/livekit.php` - LiveKit configuration file
- `app/Traits/HasMeetings.php` - Meeting functionality trait
- `app/Contracts/MeetingCapable.php` - Meeting interface
- Test files for usage examples

---

## Getting Help

### For Quick Questions
→ Start with **MEETINGS_QUICK_REFERENCE.md**

### For Understanding How Something Works
→ Check **MEETINGS_ARCHITECTURE_DIAGRAM.md** for flow, then **MEETINGS_SYSTEM_ANALYSIS.md** for details

### For Implementation Details
→ Refer to **MEETINGS_SYSTEM_ANALYSIS.md** with the relevant code files open

### For Troubleshooting
→ Check "Troubleshooting" section in **MEETINGS_QUICK_REFERENCE.md**

### For New Feature Implementation
→ Follow the architecture patterns in **MEETINGS_SYSTEM_ANALYSIS.md** section 13

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-11-12 | Initial comprehensive documentation created |

---

**Last Updated:** November 12, 2025  
**Coverage:** Complete Meetings Management System  
**Scope:** Production-ready implementation analysis
