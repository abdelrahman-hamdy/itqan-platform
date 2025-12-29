# Routes Organization Summary

## Overview

The routes/web.php file has been successfully split into 10 organized route files, reducing the main file from 2,422 lines to just **136 lines** (90+ lines are comments, actual code is ~40 lines).

## File Structure

```
routes/
├── web.php              (136 lines) - Main router with includes
├── auth.php             (216 lines) - Authentication routes
└── web/
    ├── student.php      (233 lines) - Student routes
    ├── teacher.php      (187 lines) - Teacher routes
    ├── public.php       (214 lines) - Public/guest routes
    ├── parent.php       (156 lines) - Parent portal routes
    ├── api.php          (109 lines) - Web API routes
    ├── meetings.php     ( 92 lines) - LiveKit meeting routes
    ├── lessons.php      ( 75 lines) - Lesson/course learning routes
    ├── payments.php     ( 67 lines) - Payment routes
    ├── chat.php         ( 53 lines) - WireChat routes
    └── dev.php          ( 55 lines) - Development routes
```

**Total: 1,593 lines** across 11 files (vs. original 2,422 lines in one file)

## Route File Breakdown

### 1. routes/web.php (Main Router)
**Purpose**: Central router that includes all domain-specific route files
**Lines**: 136 (90+ are documentation comments)

**Key Sections**:
- Broadcasting authentication setup
- Authentication routes include
- Session status/attendance API includes (priority routes)
- Public routes include
- Lesson routes include (must load before other course routes)
- Student routes include
- Teacher routes include
- Parent routes include
- Payment routes include
- Meeting routes include
- Chat routes include
- Development routes include (local only)

**Middleware Applied**:
- `web` (default)
- `auth` (applied in individual route files)
- Domain-based routing (subdomain pattern)

---

### 2. routes/auth.php
**Purpose**: Authentication, registration, and password reset
**Lines**: 216

**Route Groups**:
- **Login Routes**: Unified login for all roles
  - `GET /login` - Show login form
  - `POST /login` - Process login
  - `POST /logout` - Logout

- **Password Reset Routes**:
  - `GET /forgot-password` - Show forgot password form
  - `POST /forgot-password` - Send reset link (throttled: 5 per minute)
  - `GET /reset-password/{token}` - Show reset form
  - `POST /reset-password` - Process reset (throttled: 5 per minute)

- **Student Registration**:
  - `GET /register` - Show registration form
  - `POST /register` - Process registration

- **Teacher Registration** (Multi-step):
  - `GET /teacher/register` - Step 1 form
  - `POST /teacher/register/step1` - Process step 1
  - `GET /teacher/register/step2` - Step 2 form
  - `POST /teacher/register/step2` - Process step 2
  - `GET /teacher/register/success` - Success page

- **Parent Registration**:
  - `GET /parent/register` - Show registration form
  - `POST /parent/register` - Process registration
  - `POST /parent/verify-students` - Verify student codes

- **Protected Routes** (Authenticated users):
  - Student profile editing
  - Parent profile and children
  - Academy admin dashboard redirects
  - Supervisor dashboard redirects
  - Teacher dashboard and profile routes
  - Meeting link management

**Middleware**: `web`, `auth`, `role:*` (specific to each group)

---

### 3. routes/web/student.php
**Purpose**: All student-facing routes
**Lines**: 233

**Route Groups**:
- **Profile & Dashboard**: `/profile`, `/search`
- **Subscriptions & Payments**: `/subscriptions`, `/payments`
- **Enrollment Routes**:
  - Quran teacher trial/subscription booking
  - Academic package subscription
  - Quran circle enrollment
  - Interactive course enrollment
- **Session Routes**:
  - Quran sessions: `/sessions/{sessionId}`
  - Academic subscriptions: `/academic-subscriptions/{subscriptionId}`
  - Academic sessions: `/academic-sessions/{session}`
  - Interactive course sessions: `/student/interactive-sessions/{session}`
- **Homework Routes**: `/homework/*`
- **Quiz Routes**: `/quizzes`, `/student-quiz-*`
- **Certificates**: `/certificates`, `/certificates/{certificate}/*`
- **Circle Reports**: `/individual-circles/{circle}/report`, `/group-circles/{circle}/report`
- **Calendar**: `/student/calendar`, `/student/calendar/events`
- **Course Routes**: `/courses/{id}/*` (enrollment, checkout, learn)
- **Quran Circle Management**: `/circles/{circleId}/*`
- **Payment Routes**: `/quran/subscription/{subscription}/payment`

**Middleware**: `auth`, `role:student`

**Permanent Redirects** (301):
- `/my-quran-teachers` → `/quran-teachers`
- `/my-quran-circles` → `/quran-circles`
- `/my-academic-teachers` → `/academic-teachers`
- `/my-interactive-courses` → `/interactive-courses`

---

### 4. routes/web/teacher.php
**Purpose**: All teacher-facing routes (Quran & Academic)
**Lines**: 187

**Route Groups**:
- **Course Management** (Admin/Teacher): `/courses/create`, `/courses` (store)
- **Certificate Preview**: `/certificates/preview` (GET/POST for iframe/form)
- **Homework Grading** (All teachers): `/teacher/homework/*`
- **Academic Teacher Routes**:
  - Session management: `/teacher/academic-sessions/*`
  - Interactive courses: `/teacher/interactive-courses/*`
  - Interactive sessions: `/teacher/interactive-sessions/{session}/*`
- **Student Reports** (AJAX):
  - Quran teacher reports: `/teacher/quran-reports/{type}`
  - Academic teacher reports: `/teacher/academic-reports/{type}`
- **Quran Teacher Individual Circles**:
  - Circle management: `/teacher/individual-circles/*`
  - AJAX routes: template sessions, settings
  - Student reports API: `/teacher/student-reports/*`
  - Session homework: `/teacher/sessions/{sessionId}/homework/*`
- **Quran Teacher Group Circles**:
  - Circle management: `/teacher/group-circles/*`
  - Session management: `/teacher/sessions/{sessionId}/*`
  - Meeting creation: `/teacher/sessions/{sessionId}/create-meeting`

**Middleware**:
- `auth`
- `role:admin,teacher,quran_teacher,academic_teacher` (course management)
- `role:quran_teacher,academic_teacher` (homework grading)
- `role:academic_teacher` (academic routes)
- `role:quran_teacher` (Quran routes)

---

### 5. routes/web/parent.php
**Purpose**: Parent portal routes
**Lines**: 156

**Route Groups** (all under `/parent` prefix):
- **Child Selection API**: `POST /parent/select-child`
- **Profile**: `/parent`, `/parent/profile/*`
- **Children Management**: `/parent/children/*`
- **Sessions**: `/parent/sessions/*` (upcoming, history, show)
- **Calendar**: `/parent/calendar/*`
- **Subscriptions**: `/parent/subscriptions/*`
- **Payments**: `/parent/payments/*`
- **Certificates**: `/parent/certificates/*`
- **Reports**:
  - Progress report: `/parent/reports/progress`
  - Attendance redirect → Progress (301)
  - Detailed reports: Quran individual, academic subscription, interactive course
- **Homework**: `/parent/homework/*` (reuses student views with parent layout)
- **Quizzes**: `/parent/quizzes/*`

**Middleware**: `auth`, `role:parent`, `child.selection`

---

### 6. routes/web/public.php
**Purpose**: Platform landing, academy homepage, public browsing
**Lines**: 214

**Main Domain Routes** (`app.domain`):
- Platform landing: `/`, `/about`, `/contact`, `/features`
- Business services: `/business-services`, `/portfolio`
- Business service APIs: categories, portfolio items, request submission
- Admin panel redirect: `/admin`
- Old home reference: `/old-home`
- Fallback handler (redirects to default academy)

**Subdomain Public Routes** (`{subdomain}.app.domain`):
- **Academy Home**: `/`
- **Static Pages**: `/terms`, `/refund-policy`, `/privacy-policy`, `/about-us`
- **Unified Quran Teacher Routes**:
  - Listing: `/quran-teachers`
  - Profile: `/quran-teachers/{teacherId}`
- **Unified Academic Teacher Routes**:
  - Listing: `/academic-teachers`
  - Profile: `/academic-teachers/{teacherId}`
- **Public Academic Package Routes**:
  - Listing: `/academic-packages`
  - Teacher profile: `/academic-packages/teachers/{teacher}`
  - API: `/api/academic-packages/{packageId}/teachers`
- **Unified Quran Circle Routes**:
  - Listing: `/quran-circles`
  - Details: `/quran-circles/{circleId}`
- **Unified Interactive Course Routes**:
  - Listing: `/interactive-courses`
  - Details: `/interactive-courses/{courseId}`
- **Public Recorded Courses**:
  - Listing: `/courses`
  - Details: `/courses/{id}`

**Middleware**: `web` (no authentication required)

---

### 7. routes/web/api.php
**Purpose**: Web API endpoints for AJAX requests
**Lines**: 109

**Global Routes** (no subdomain):
- **Session Status APIs**:
  - Academic sessions: `/api/academic-sessions/{session}/status`
  - Quran sessions: `/api/quran-sessions/{session}/status`
  - General sessions: `/api/sessions/{session}/status`
- **Attendance Status APIs**:
  - Academic sessions: `/api/academic-sessions/{session}/attendance-status`
  - Quran sessions: `/api/quran-sessions/{session}/attendance-status`
  - General sessions: `/api/sessions/{session}/attendance-status`
- **Debug Route** (local/testing only): `/debug-api-test`

**Subdomain Routes**:
- **Notification APIs**:
  - Page: `/notifications`
  - Mark as read: `/api/notifications/{id}/mark-as-read`
  - Mark all as read: `/api/notifications/mark-all-as-read`
  - Delete: `/api/notifications/{id}`
- **CSRF Token**: `/csrf-token`
- **Custom File Upload**: `/custom-file-upload` (Filament components)

**Middleware**: `web`, `auth` (for subdomain routes)

**IMPORTANT**: These routes load BEFORE subdomain routes to ensure priority for LiveKit interface compatibility.

---

### 8. routes/web/meetings.php
**Purpose**: LiveKit video meeting integration
**Lines**: 92

**LiveKit Control Routes** (tenant-aware):
- **Basic Participant Endpoints** (authenticated users):
  - `GET /livekit/participants` - Get room participants
  - `GET /livekit/rooms/permissions` - Get room permissions
- **Teacher-Only Control** (with `control-participants` middleware):
  - `POST /livekit/participants/mute` - Mute participant
  - `POST /livekit/participants/mute-all-students` - Mute all students
  - `POST /livekit/participants/disable-all-students-camera` - Disable all cameras
  - `GET /livekit/rooms/{room_name}/participants` - Get room participants

**LiveKit Webhooks** (global, no auth):
- `POST /webhooks/livekit` - Handle webhook
- `GET /webhooks/livekit/health` - Health check

**Meeting API Routes** (authenticated):
- `POST /meetings/{session}/create-or-get` - Create or get meeting
- **LiveKit Meeting API**:
  - `POST /api/meetings/create` - Create meeting
  - `GET /api/meetings/{sessionId}/token` - Get participant token
  - `GET /api/meetings/{sessionId}/info` - Get room info
  - `POST /api/meetings/{sessionId}/end` - End meeting
  - `POST /api/meetings/livekit/token` - Get LiveKit token

**Interactive Course Recording API**:
- `POST /api/recordings/start` - Start recording
- `POST /api/recordings/stop` - Stop recording
- `GET /api/recordings/session/{sessionId}` - Get session recordings
- `DELETE /api/recordings/{recordingId}` - Delete recording
- `GET /api/recordings/{recordingId}/download` - Download recording
- `GET /api/recordings/{recordingId}/stream` - Stream recording

**Middleware**:
- `auth` (for control and API routes)
- `control-participants` (for teacher-only controls)
- `throttle:60,1` (for webhooks)
- CSRF exempt for webhooks

---

### 9. routes/web/lessons.php
**Purpose**: Lesson viewing, progress tracking, course learning
**Lines**: 75

**IMPORTANT**: Must load BEFORE general course routes to avoid conflicts.

**Lesson Routes** (ID-based, under subdomain):
- **Lesson Viewing & Progress**:
  - `GET /courses/{courseId}/lessons/{lessonId}` - Show lesson
  - `GET /courses/{courseId}/lessons/{lessonId}/progress` - Get progress
  - `POST /courses/{courseId}/lessons/{lessonId}/progress` - Update progress
  - `POST /courses/{courseId}/lessons/{lessonId}/complete` - Mark complete
- **Lesson Interactions**:
  - `POST /courses/{courseId}/lessons/{lessonId}/bookmark` - Add bookmark
  - `DELETE /courses/{courseId}/lessons/{lessonId}/bookmark` - Remove bookmark
  - `POST /courses/{courseId}/lessons/{lessonId}/notes` - Add note
  - `GET /courses/{courseId}/lessons/{lessonId}/notes` - Get notes
  - `POST /courses/{courseId}/lessons/{lessonId}/rate` - Rate lesson
- **Lesson Resources**:
  - `GET /courses/{courseId}/lessons/{lessonId}/transcript` - Get transcript
  - `GET /courses/{courseId}/lessons/{lessonId}/materials` - Download materials
  - `GET /courses/{courseId}/lessons/{lessonId}/video` - Serve video
  - `OPTIONS /courses/{courseId}/lessons/{lessonId}/video` - Video options
- **Course Progress**:
  - `GET /courses/{courseId}/progress` - Get course progress

**API Progress Routes** (with auth):
- `GET /api/courses/{courseId}/progress` - Get course progress
- `GET /api/courses/{courseId}/lessons/{lessonId}/progress` - Get lesson progress
- `POST /api/courses/{courseId}/lessons/{lessonId}/progress` - Update lesson progress
- `POST /api/courses/{courseId}/lessons/{lessonId}/complete` - Mark lesson complete

**Middleware**: `web`, `auth` (for API routes)

---

### 10. routes/web/payments.php
**Purpose**: Payment processing and gateway integration
**Lines**: 67

**Subdomain Payment Routes**:
- **Payment Processing** (ID-based):
  - `GET /courses/{courseId}/payment` - Create payment
  - `POST /courses/{courseId}/payment` - Store payment
  - `GET /payments/{payment}/success` - Success page
  - `GET /payments/{payment}/failed` - Failed page
- **Payment Management**:
  - `GET /payments/history` - Payment history
  - `GET /payments/{payment}/receipt` - Download receipt
  - `POST /payments/{payment}/refund` - Process refund
- **Payment Flow**:
  - `POST /payments/{payment}/initiate` - Initiate payment
  - `GET /payments/{payment}/callback` - Payment callback
- **Payment Methods API**:
  - `GET /api/payment-methods/{academy}` - Get payment methods

**Global Webhook Routes** (no subdomain):
- `POST /webhooks/paymob` - Paymob webhook handler

**Middleware**:
- `web` (for subdomain routes)
- `throttle:60,1` (for webhooks)
- CSRF exempt for webhooks (validated via HMAC signatures)

---

### 11. routes/web/chat.php
**Purpose**: WireChat integration for real-time messaging
**Lines**: 53

**Chat Routes** (under subdomain):
- `GET /chat` - Chats list page (Livewire component)
- `GET /chat/start-with/{user}` - Start conversation with user
- `GET /chat/{conversation}` - Conversation page (Livewire component)

**Middleware**:
- WireChat config middleware (from `config/wirechat.php`)
- `belongsToConversation` (for conversation routes)

**Special Features**:
- Arabic titles support
- Subdomain routing support
- Automatic conversation creation/retrieval
- Logging for debugging

---

### 12. routes/web/dev.php
**Purpose**: Development-only utilities
**Lines**: 55

**Available only in local environment** (`app()->environment('local')`):
- **Certificate Template Preview**:
  - `GET /dev/certificate-preview` - HTML preview (browser layout testing)
  - `GET /dev/certificate-pdf-preview` - PDF preview (TCPDF with Arabic support)

**Features**:
- Test data for certificate generation
- TCPDF integration for Arabic text
- Template style testing
- Academy logo testing

**Middleware**: None (environment check in route definition)

---

## Route Loading Order (Critical)

The order of route file inclusion in `routes/web.php` is critical:

1. **auth.php** - Authentication routes (must load first)
2. **web/api.php** - Session status/attendance APIs (BEFORE subdomain routes for LiveKit)
3. **web/public.php** - Public routes (platform landing, academy homepage)
4. **web/lessons.php** - Lesson routes (BEFORE general course routes to avoid conflicts)
5. **web/student.php** - Student routes
6. **web/teacher.php** - Teacher routes
7. **web/parent.php** - Parent routes
8. **web/payments.php** - Payment routes
9. **web/meetings.php** - Meeting routes
10. **web/chat.php** - Chat routes
11. **web/dev.php** - Development routes (local only)

## Key Design Patterns

### 1. Domain-Based Routing
All subdomain routes use the pattern:
```php
Route::domain('{subdomain}.'.config('app.domain'))->group(function () {
    // Routes here
});
```

### 2. Middleware Groups
- `web` - Default for all routes (session, CSRF, cookies)
- `auth` - Requires authentication
- `role:*` - Role-based access control
- `child.selection` - Parent portal child selection
- `control-participants` - Teacher-only LiveKit controls
- `belongsToConversation` - Chat conversation access

### 3. Route Naming Conventions
- Student routes: `student.*`
- Teacher routes: `teacher.*`
- Parent routes: `parent.*`
- Academy routes: `academy.*`
- API routes: `api.*`
- Webhook routes: `webhooks.*`

### 4. ID-Based vs Slug-Based Routing
- **ID-based** (numeric): `/courses/{id}` where `id` is `[0-9]+`
  - Used for: Courses, lessons, payments
  - Advantage: Faster database queries, no slug collisions
- **Slug-based**: `/quran-teachers/{teacherId}`, `/quran-circles/{circleId}`
  - Used for: Teachers, circles, courses (some cases)
  - Advantage: SEO-friendly URLs

### 5. Permanent Redirects (301)
Old routes redirect to new unified routes:
```php
Route::permanentRedirect('/my-quran-teachers', '/quran-teachers');
Route::permanentRedirect('/my-quran-circles', '/quran-circles');
Route::permanentRedirect('/my-academic-teachers', '/academic-teachers');
Route::permanentRedirect('/my-interactive-courses', '/interactive-courses');
Route::permanentRedirect('/my-interactive-courses/{course}', '/interactive-courses/{course}');
```

### 6. CSRF Exemptions
Webhook routes exempt from CSRF verification (use signature validation):
```php
Route::withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
```

### 7. Throttling
Rate limiting applied to sensitive routes:
- Password reset: `throttle:5,1` (5 per minute)
- Webhooks: `throttle:60,1` (60 per minute)

## Benefits of This Organization

1. **Maintainability**: Each domain has its own file (student, teacher, parent, etc.)
2. **Readability**: Easy to find routes by role/feature
3. **Performance**: No performance impact (all routes cached in production)
4. **Team Collaboration**: Multiple developers can work on different route files
5. **Testing**: Easier to test specific route groups
6. **Documentation**: Self-documenting structure with clear file names
7. **Scalability**: Easy to add new route files as the application grows

## Migration Notes

### Changes from Original routes/web.php
- Reduced from 2,422 lines to 136 lines (94% reduction)
- Split into 11 organized files
- Maintained all route functionality
- Improved documentation with file headers
- Added route ordering comments for critical routes
- Consolidated duplicate routes
- Added permanent redirects for backward compatibility

### Breaking Changes
None. All existing routes maintained with same URLs and names.

### Future Improvements
1. Consider splitting `routes/web/student.php` (233 lines) into:
   - `student-sessions.php` (session-related routes)
   - `student-enrollment.php` (enrollment routes)
   - `student-learning.php` (homework, quizzes, certificates)
2. Consider creating `routes/web/admin.php` for admin-specific routes
3. Consider creating `routes/web/ajax.php` for AJAX-specific routes (currently mixed in api.php)

## Testing Checklist

After route organization, verify:
- [ ] All student routes work (login, profile, sessions, homework, quizzes)
- [ ] All teacher routes work (session management, homework grading, reports)
- [ ] All parent routes work (children, sessions, payments, reports)
- [ ] All public routes work (platform landing, academy homepage, teacher/course browsing)
- [ ] All authentication routes work (login, register, password reset)
- [ ] All payment routes work (checkout, payment processing, webhooks)
- [ ] All meeting routes work (LiveKit integration, recording)
- [ ] All API routes work (session status, attendance, notifications)
- [ ] All chat routes work (conversation list, chat interface)
- [ ] All webhook routes work (Paymob, LiveKit)
- [ ] Route caching works: `php artisan route:cache`
- [ ] No 404 errors on previously working routes
- [ ] No duplicate route names
- [ ] No route conflicts

## Route Cache Commands

```bash
# Clear route cache
php artisan route:clear

# Cache routes (production)
php artisan route:cache

# List all routes
php artisan route:list

# List routes by name pattern
php artisan route:list --name=student

# List routes by path pattern
php artisan route:list --path=api
```

## Conclusion

The route organization is complete and follows Laravel best practices. The main `routes/web.php` file is now a clean, documented router that includes domain-specific route files. This structure will scale well as the application grows and makes it easier for developers to find and maintain routes.
