# Notification Integration Guide

This guide shows how to integrate notifications with the remaining features in the Itqan Platform.

## Quick Reference

```php
// Inject NotificationService in your service constructor
protected NotificationService $notificationService;

public function __construct(NotificationService $notificationService)
{
    $this->notificationService = $notificationService;
}

// Send a notification
$this->notificationService->send(
    $user,                              // User or Collection of users
    NotificationType::SESSION_SCHEDULED, // Notification type
    ['session_title' => 'My Session'],  // Data for translation
    '/sessions/123',                     // Action URL (optional)
    ['session_id' => 123],              // Metadata (optional)
    true                                 // Is important (optional)
);
```

---

## 1. Attendance Notifications

### File: `app/Services/MeetingAttendanceService.php`

**Integration Points:**

#### A. When Attendance is Marked (Present/Absent/Late)

Add after attendance record is created/updated:

```php
// In recordAttendance() or updateAttendance() method
// After successful attendance save

use App\Services\NotificationService;
use App\Enums\NotificationType;

// Send notification to student
if ($attendance->student) {
    $status = $attendance->status; // 'present', 'absent', 'late'

    app(NotificationService::class)->sendAttendanceMarkedNotification(
        $attendance,
        $attendance->student->user,
        $status
    );
}

// Optional: Send to parent if they exist
if ($attendance->student && $attendance->student->parent) {
    app(NotificationService::class)->sendAttendanceMarkedNotification(
        $attendance,
        $attendance->student->parent->user,
        $status
    );
}
```

#### Example Implementation:

```php
public function recordAttendance($sessionId, $userId, $status)
{
    // ... existing attendance logic ...

    $attendance = MeetingAttendance::create([
        'session_id' => $sessionId,
        'user_id' => $userId,
        'status' => $status,
        // ... other fields
    ]);

    // ADD THIS: Send notification
    $student = User::find($userId);
    if ($student) {
        app(NotificationService::class)->sendAttendanceMarkedNotification(
            $attendance,
            $student,
            $status
        );
    }

    return $attendance;
}
```

---

## 2. Homework Notifications

### File: `app/Services/HomeworkService.php`

**Integration Points:**

#### A. When Homework is Assigned

```php
// In assignHomework() method
// After homework is created/assigned

use App\Services\NotificationService;
use App\Enums\NotificationType;

// Get the session and students
$session = $homework->session; // or however you get the session
$students = $session->students; // or however you get students

// Send notification to each student
foreach ($students as $student) {
    app(NotificationService::class)->sendHomeworkAssignedNotification(
        $session,
        $student->user,
        [
            'title' => $homework->title,
            'due_date' => $homework->due_date?->format('Y-m-d'),
            'description' => $homework->description,
        ]
    );
}
```

#### B. When Homework is Submitted

```php
// In submitHomework() method
// After submission is saved

$teacher = $session->teacher;
if ($teacher) {
    app(NotificationService::class)->send(
        $teacher->user,
        NotificationType::HOMEWORK_SUBMITTED,
        [
            'student_name' => $student->full_name,
            'homework_title' => $homework->title,
            'session_title' => $session->title ?? 'Session',
        ],
        "/teacher/homework/{$submission->id}",
        ['submission_id' => $submission->id]
    );
}
```

#### C. When Homework is Graded

```php
// In gradeHomework() method
// After grade is saved

app(NotificationService::class)->send(
    $student->user,
    NotificationType::HOMEWORK_GRADED,
    [
        'homework_title' => $homework->title,
        'grade' => $submission->grade,
        'feedback' => $submission->feedback ?? '',
    ],
    "/student/homework/{$submission->id}",
    [
        'submission_id' => $submission->id,
        'grade' => $submission->grade,
    ]
);
```

#### Example Implementation:

```php
public function assignHomework($sessionId, $homeworkData)
{
    // ... existing logic to create homework ...

    $homework = Homework::create($homeworkData);
    $session = Session::find($sessionId);

    // ADD THIS: Send notifications to all students
    $students = $session->students;
    foreach ($students as $student) {
        app(NotificationService::class)->sendHomeworkAssignedNotification(
            $session,
            $student->user,
            [
                'title' => $homework->title,
                'due_date' => $homework->due_date?->format('Y-m-d'),
            ]
        );
    }

    return $homework;
}
```

---

## 3. Payment Notifications

### File: `app/Services/PaymentService.php`

**Integration Points:**

#### A. When Payment Succeeds

```php
// In processPayment() or handlePaymentSuccess() method
// After payment is confirmed

use App\Services\NotificationService;
use App\Enums\NotificationType;

app(NotificationService::class)->sendPaymentSuccessNotification(
    $user,
    [
        'amount' => $payment->amount,
        'currency' => $payment->currency ?? 'SAR',
        'description' => $payment->description ?? 'اشتراك في الخدمة',
        'payment_id' => $payment->id,
        'transaction_id' => $payment->transaction_id,
    ]
);
```

#### B. When Payment Fails

```php
// In handlePaymentFailure() method

app(NotificationService::class)->send(
    $user,
    NotificationType::PAYMENT_FAILED,
    [
        'amount' => $payment->amount,
        'currency' => $payment->currency ?? 'SAR',
        'reason' => $payment->failure_reason ?? 'فشل الدفع',
    ],
    '/student/payments',
    ['payment_id' => $payment->id],
    true  // Mark as important
);
```

#### C. When Subscription is Expiring

```php
// In checkExpiringSubscriptions() or similar method
// This should be a scheduled command that runs daily

$expiringSubscriptions = Subscription::where('end_date', '<=', now()->addDays(7))
    ->where('end_date', '>', now())
    ->where('status', 'active')
    ->get();

foreach ($expiringSubscriptions as $subscription) {
    $daysLeft = now()->diffInDays($subscription->end_date);

    app(NotificationService::class)->send(
        $subscription->student->user,
        NotificationType::SUBSCRIPTION_EXPIRING,
        [
            'subscription_name' => $subscription->name ?? 'الاشتراك',
            'days_left' => $daysLeft,
            'expiry_date' => $subscription->end_date->format('Y-m-d'),
        ],
        '/student/subscriptions',
        ['subscription_id' => $subscription->id],
        true
    );
}
```

#### Example Implementation:

```php
public function processPayment($paymentData)
{
    // ... existing payment processing logic ...

    $payment = Payment::create($paymentData);

    // Process payment with gateway
    $result = $this->paymentGateway->charge($payment);

    if ($result->isSuccessful()) {
        $payment->update(['status' => 'completed']);

        // ADD THIS: Send success notification
        app(NotificationService::class)->sendPaymentSuccessNotification(
            $payment->user,
            [
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'SAR',
                'description' => $payment->description,
                'payment_id' => $payment->id,
                'transaction_id' => $result->transactionId,
            ]
        );
    } else {
        $payment->update([
            'status' => 'failed',
            'failure_reason' => $result->message,
        ]);

        // ADD THIS: Send failure notification
        app(NotificationService::class)->send(
            $payment->user,
            NotificationType::PAYMENT_FAILED,
            [
                'amount' => $payment->amount,
                'currency' => $payment->currency ?? 'SAR',
                'reason' => $result->message,
            ],
            '/student/payments',
            ['payment_id' => $payment->id],
            true
        );
    }

    return $payment;
}
```

---

## 4. Meeting Notifications

### Already Integrated ✅

Meeting notifications are already integrated in `SessionStatusService.php`:
- Meeting room ready (via `sendSessionReadyNotifications()`)
- Session started
- Session completed

**Additional improvements possible:**

#### A. When Participant Joins (for teachers)

```php
// In LiveKitWebhookController.php
// When participant_joined event is received

if ($participant->identity !== $session->teacher->id) {
    app(NotificationService::class)->send(
        $session->teacher->user,
        NotificationType::MEETING_PARTICIPANT_JOINED,
        [
            'participant_name' => $participant->name,
            'session_title' => $session->title,
        ],
        "/teacher/sessions/{$session->id}",
        [
            'session_id' => $session->id,
            'participant_id' => $participant->id,
        ]
    );
}
```

---

## 5. Certificate Notifications

### Already Integrated ✅

Certificate notifications are already implemented in:
- `app/Notifications/CertificateIssuedNotification.php`

This is a queued notification that sends both email and database notifications.

**Usage:**

```php
use App\Notifications\CertificateIssuedNotification;

$certificate->student->user->notify(new CertificateIssuedNotification($certificate));
```

---

## 6. Subscription Renewal Notifications

### File: Create new console command

Create: `app/Console/Commands/CheckExpiringSubscriptions.php`

```php
<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Models\QuranSubscription;
use App\Models\AcademicSubscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckExpiringSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expiring';
    protected $description = 'Check for expiring subscriptions and send notifications';

    public function handle()
    {
        $notificationService = app(NotificationService::class);

        // Check subscriptions expiring in 7 days, 3 days, and 1 day
        $daysToCheck = [7, 3, 1];

        foreach ($daysToCheck as $days) {
            $targetDate = now()->addDays($days)->startOfDay();

            // Quran Subscriptions
            $quranSubs = QuranSubscription::where('end_date', '>=', $targetDate)
                ->where('end_date', '<', $targetDate->copy()->endOfDay())
                ->where('status', 'active')
                ->get();

            foreach ($quranSubs as $subscription) {
                $notificationService->send(
                    $subscription->student->user,
                    NotificationType::SUBSCRIPTION_EXPIRING,
                    [
                        'subscription_name' => 'اشتراك القرآن الكريم',
                        'days_left' => $days,
                        'expiry_date' => $subscription->end_date->format('Y-m-d'),
                    ],
                    '/student/subscriptions',
                    ['subscription_id' => $subscription->id],
                    $days <= 3  // Mark as important if 3 days or less
                );
            }

            // Academic Subscriptions
            $academicSubs = AcademicSubscription::where('end_date', '>=', $targetDate)
                ->where('end_date', '<', $targetDate->copy()->endOfDay())
                ->where('status', 'active')
                ->get();

            foreach ($academicSubs as $subscription) {
                $notificationService->send(
                    $subscription->student->user,
                    NotificationType::SUBSCRIPTION_EXPIRING,
                    [
                        'subscription_name' => 'الاشتراك الأكاديمي',
                        'days_left' => $days,
                        'expiry_date' => $subscription->end_date->format('Y-m-d'),
                    ],
                    '/student/subscriptions',
                    ['subscription_id' => $subscription->id],
                    $days <= 3
                );
            }
        }

        $this->info('Subscription expiry notifications sent successfully.');
        return 0;
    }
}
```

**Schedule it in `routes/console.php`:**

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:check-expiring')->dailyAt('09:00');
```

---

## 7. Progress Report Notifications

### Integration Point

Add to the service/command that generates progress reports:

```php
// After progress report is generated

app(NotificationService::class)->send(
    $student->user,
    NotificationType::PROGRESS_REPORT_AVAILABLE,
    [
        'period' => 'هذا الشهر', // or dynamic
        'report_date' => now()->format('Y-m-d'),
    ],
    "/student/reports/{$report->id}",
    ['report_id' => $report->id]
);
```

---

## Common Patterns

### 1. Sending to Multiple Users

```php
// Send to a collection of users
$notificationService->send(
    User::whereIn('id', $studentIds)->get(),
    NotificationType::SESSION_SCHEDULED,
    $data,
    $url
);
```

### 2. Sending to Parent and Student

```php
// Send to both student and parent
$recipients = collect([$student->user]);
if ($student->parent) {
    $recipients->push($student->parent->user);
}

$notificationService->send(
    $recipients,
    NotificationType::ATTENDANCE_MARKED_PRESENT,
    $data,
    $url
);
```

### 3. Queued Notifications

For email/SMS notifications, create a custom notification class:

```php
php artisan make:notification HomeworkAssignedNotification
```

```php
class HomeworkAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['database', 'mail'];  // or ['database', 'mail', 'broadcast']
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('واجب جديد')
            ->line('تم تعيين واجب جديد لك...');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'homework_assigned',
            // ... data
        ];
    }
}
```

---

## Testing Notifications

```bash
# Test a specific notification type
php artisan notifications:test --type=payment

# Test with a specific user
php artisan notifications:test 5 --type=session

# Test all notification types
php artisan notifications:test --type=all
```

---

## Troubleshooting

### Notifications not appearing in real-time?

1. Check Reverb is running: `ps aux | grep reverb`
2. Check browser console for WebSocket errors
3. Verify user is subscribed to correct channel: `Echo.private('user.{userId}')`

### Notifications created but not queued?

1. Check queue worker is running: `ps aux | grep queue`
2. Check `failed_jobs` table for errors
3. Run: `php artisan queue:work --once` to test manually

### Broadcasting errors in Reverb logs?

1. Check `NotificationSent` event has correct `broadcastOn()` channel
2. Verify CSRF token is set in Echo auth headers
3. Check `/broadcasting/auth` endpoint is accessible

---

## Next Steps

1. **Implement attendance notifications** in `MeetingAttendanceService.php`
2. **Implement homework notifications** in `HomeworkService.php`
3. **Implement payment notifications** in `PaymentService.php`
4. **Create subscription expiry command** and schedule it
5. **Test each integration** thoroughly
6. **Monitor notification delivery** in production

---

**Need Help?**

- NotificationService methods: See `app/Services/NotificationService.php`
- Notification types: See `app/Enums/NotificationType.php`
- Translation keys: See `lang/ar/notifications.php`
- Frontend component: See `resources/views/livewire/notification-center.blade.php`
