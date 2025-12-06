# Notification System Implementation

## Overview
A comprehensive notification system has been implemented for the Itqan Platform with the following features:
- **8 Categories** of notifications with distinct icons and colors
- **40+ Notification Types** covering all major app events
- **Real-time Broadcasting** using Laravel Reverb
- **Action URLs** for each notification to navigate to relevant pages
- **Multi-tenant Support** with tenant isolation
- **Arabic & English Translations**
- **Browser Push Notifications** support
- **Flexible Architecture** for easy extensibility

## Architecture Components

### 1. Database Structure
Enhanced `notifications` table with new fields:
- `notification_type` - Specific notification type enum value
- `category` - Notification category for grouping
- `icon` - Icon component name for display
- `icon_color` - Color scheme for the icon
- `action_url` - URL to navigate when notification is clicked
- `metadata` - JSON field for additional context
- `is_important` - Flag for high-priority notifications
- `tenant_id` - Multi-tenant isolation

### 2. Core Classes

#### Enums
- **`NotificationCategory`** - 8 categories (Session, Attendance, Homework, Payment, Meeting, Progress, Chat, System)
- **`NotificationType`** - 40+ specific notification types

#### Services
- **`NotificationService`** - Central service for sending and managing notifications
  - Send notifications to single or multiple users
  - Mark as read/unread
  - Delete notifications
  - Get paginated notifications with filtering
  - Type-specific helper methods

#### Events
- **`NotificationSent`** - Broadcasted when new notification is created

#### Livewire Component
- **`NotificationCenter`** - Real-time notification UI component
  - Live notification counter
  - Category filtering
  - Mark as read/all as read
  - Delete notifications
  - Real-time updates via Laravel Echo

### 3. UI Components

#### Notification Center (`livewire/notification-center.blade.php`)
- **Bell Icon** with unread count badge
- **Dropdown Panel** with:
  - Category filter tabs
  - Notification list with icons and colors
  - Action buttons (view, mark as read, delete)
  - Pagination support
  - Real-time updates

#### Integration Points
- Added to `app-navigation.blade.php` component
- Available in both student and teacher interfaces
- Responsive design with RTL support

## Notification Categories & Types

### 1. Session Notifications (📚)
- `SESSION_SCHEDULED` - New session scheduled
- `SESSION_REMINDER` - Reminder before session starts
- `SESSION_STARTED` - Session has begun
- `SESSION_COMPLETED` - Session finished
- `SESSION_CANCELLED` - Session cancelled
- `SESSION_RESCHEDULED` - Session time changed

### 2. Attendance Notifications (✅)
- `ATTENDANCE_MARKED_PRESENT` - Marked as present
- `ATTENDANCE_MARKED_ABSENT` - Marked as absent
- `ATTENDANCE_MARKED_LATE` - Marked as late
- `ATTENDANCE_REPORT_READY` - Report available

### 3. Homework Notifications (📝)
- `HOMEWORK_ASSIGNED` - New homework assigned
- `HOMEWORK_SUBMITTED` - Submission received
- `HOMEWORK_GRADED` - Homework graded
- `HOMEWORK_DEADLINE_REMINDER` - Deadline approaching

### 4. Payment Notifications (💳)
- `PAYMENT_SUCCESS` - Payment successful
- `PAYMENT_FAILED` - Payment failed
- `SUBSCRIPTION_EXPIRING` - Subscription ending soon
- `SUBSCRIPTION_EXPIRED` - Subscription ended
- `INVOICE_GENERATED` - New invoice created

### 5. Meeting Notifications (📹)
- `MEETING_ROOM_READY` - Room available to join
- `MEETING_PARTICIPANT_JOINED` - Someone joined
- `MEETING_PARTICIPANT_LEFT` - Someone left
- `MEETING_RECORDING_AVAILABLE` - Recording ready
- `MEETING_TECHNICAL_ISSUE` - Technical problem

### 6. Academic Progress (📈)
- `PROGRESS_REPORT_AVAILABLE` - New report ready
- `ACHIEVEMENT_UNLOCKED` - Achievement earned
- `CERTIFICATE_EARNED` - Certificate awarded
- `COURSE_COMPLETED` - Course finished

### 7. Chat Notifications (💬)
- `CHAT_MESSAGE_RECEIVED` - New message
- `CHAT_MENTIONED` - Mentioned in chat
- `CHAT_GROUP_ADDED` - Added to group

### 8. System Notifications (⚙️)
- `ACCOUNT_VERIFIED` - Account verified
- `PASSWORD_CHANGED` - Password updated
- `PROFILE_UPDATED` - Profile changed
- `SYSTEM_MAINTENANCE` - Maintenance scheduled

## Usage Examples

### Send a Simple Notification
```php
use App\Services\NotificationService;
use App\Enums\NotificationType;

$notificationService = app(NotificationService::class);

$notificationService->send(
    $user,
    NotificationType::SESSION_SCHEDULED,
    [
        'session_title' => 'Quran Memorization',
        'teacher_name' => 'Teacher Ahmed',
        'start_time' => '2025-11-20 10:00',
    ],
    '/student/sessions/123'
);
```

### Send to Multiple Users
```php
$users = User::where('user_type', 'student')->get();

$notificationService->send(
    $users,
    NotificationType::SYSTEM_MAINTENANCE,
    [
        'maintenance_time' => '2025-11-25 02:00',
    ],
    null,
    ['scheduled_duration' => '2 hours'],
    true // Mark as important
);
```

### Type-Specific Helpers
```php
// Session scheduled
$notificationService->sendSessionScheduledNotification($session, $student);

// Homework assigned
$notificationService->sendHomeworkAssignedNotification($session, $student, [
    'due_date' => '2025-11-25',
    'instructions' => 'Complete exercises 1-5'
]);

// Payment success
$notificationService->sendPaymentSuccessNotification($user, [
    'amount' => 500,
    'currency' => 'SAR',
    'description' => 'Monthly subscription',
    'payment_id' => 'PAY_123'
]);
```

### Mark Notifications as Read
```php
// Mark single notification
$notificationService->markAsRead($notificationId, $user);

// Mark all as read
$notificationService->markAllAsRead($user);
```

### Get User Notifications
```php
// Get paginated notifications
$notifications = $notificationService->getNotifications($user, 15);

// Get filtered by category
$sessionNotifications = $notificationService->getNotifications($user, 15, 'session');

// Get unread count
$unreadCount = $notificationService->getUnreadCount($user);
```

## Integration Points

### 1. Session Status Changes
Notifications are automatically sent when sessions transition between states:
- **Ready** → Send reminder (30 min before)
- **Started** → Notify session has begun
- **Completed** → Notify session finished

### 2. Attendance Tracking
When attendance is marked, notifications are sent to:
- Students about their attendance status
- Parents (if enabled) about child's attendance

### 3. Homework Workflow
- Teachers assign → Students notified
- Students submit → Teachers notified
- Teachers grade → Students notified

### 4. Payment Events
Integrated with payment gateway callbacks to notify:
- Successful payments
- Failed payment attempts
- Subscription status changes

## Testing

### Manual Testing
Use the test command to send sample notifications:
```bash
# Test all notification types for default student
php artisan notifications:test

# Test for specific user
php artisan notifications:test 123

# Test specific type
php artisan notifications:test 123 --type=session
```

### Browser Testing
1. Open the application in browser
2. Click the notification bell icon
3. Verify:
   - Unread count displays correctly
   - Dropdown opens with notifications
   - Category filters work
   - Mark as read updates UI
   - Click on notification navigates to correct page

### Real-time Testing
1. Open application in two browsers
2. Login as different users
3. Send notification to one user
4. Verify notification appears in real-time

## Future Enhancements

### 1. User Preferences (Next Phase)
- Allow users to enable/disable specific notification types
- Choose delivery channels (in-app, email, SMS, push)
- Set quiet hours

### 2. Mobile Push Notifications
- Integrate with FCM/APNs for mobile apps
- Device registration and management
- Push notification templates

### 3. Email Notifications
- Email templates for each notification type
- Digest emails (daily/weekly summaries)
- Unsubscribe management

### 4. SMS Notifications
- Critical notifications via SMS
- Integration with SMS gateway
- Phone number verification

### 5. Notification Templates
- Admin-configurable templates
- Dynamic content placeholders
- Multi-language template management

### 6. Analytics
- Notification delivery rates
- Click-through rates
- User engagement metrics

## Troubleshooting

### Notifications Not Appearing
1. Check user is authenticated
2. Verify broadcasting is configured (`config/broadcasting.php`)
3. Check Laravel Echo is initialized
4. Verify notification was created in database

### Real-time Updates Not Working
1. Check Reverb/WebSocket server is running
2. Verify broadcasting credentials
3. Check browser console for WebSocket errors
4. Ensure user has permission for channel

### Wrong Notification Content
1. Check translation files (`lang/ar/notifications.php`)
2. Verify data array has required keys
3. Check notification type matches expected format

## Configuration

### Environment Variables
```env
# Broadcasting
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Broadcasting Channels
Defined in `routes/channels.php`:
```php
// User-specific notification channel
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

## Best Practices

1. **Always include action_url** - Makes notifications actionable
2. **Use appropriate category** - Helps users filter and prioritize
3. **Mark important notifications** - For critical events only
4. **Include relevant metadata** - For debugging and analytics
5. **Test with multiple user types** - Student, Teacher, Parent, Admin
6. **Handle failures gracefully** - Log errors, don't break flow
7. **Clean old notifications** - Run cleanup job periodically
8. **Use type-specific helpers** - Consistent formatting
9. **Translate all content** - Support Arabic and English
10. **Consider user timezone** - Display times in user's timezone

## Migration Rollback
If you need to rollback the notification system:
```bash
php artisan migrate:rollback --step=1
```

This will remove the notification enhancements from the database.