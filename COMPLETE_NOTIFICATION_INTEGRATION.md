# Complete Notification Integration - Implementation Guide

## ✅ Already Integrated (DONE)

1. **Session Notifications** - Fully integrated
   - Session scheduled, reminder, started, completed, cancelled
   - Location: `SessionStatusService.php`

2. **Attendance Notifications** - Just integrated
   - Present, absent, late notifications
   - Location: `MeetingAttendanceService.php` + `SessionStatusService.php`

3. **Notification URLs** - Fixed to be object-specific
   - All URLs now point to specific object pages

---

## 🔧 Remaining Integrations (TODO)

### 1. Homework Notifications

#### File: `app/Models/QuranSession.php`

Add this method or find where homework is assigned:

```php
// After homework is assigned (in update() or save() methods)
protected static function boot()
{
    parent::boot();

    static::updated(function ($session) {
        // Check if homework fields were just set
        if ($session->isDirty('quran_homework_memorization') && $session->quran_homework_memorization) {
            $session->notifyHomeworkAssigned('memorization');
        }
        if ($session->isDirty('quran_homework_recitation') && $session->quran_homework_recitation) {
            $session->notifyHomeworkAssigned('recitation');
        }
        if ($session->isDirty('quran_homework_review') && $session->quran_homework_review) {
            $session->notifyHomeworkAssigned('review');
        }
    });
}

public function notifyHomeworkAssigned($type)
{
    try {
        $student = $this->student;
        if (!$student) return;

        $notification Service = app(\App\Services\NotificationService::class);
        $notificationService->sendHomeworkAssignedNotification(
            $this,
            $student,
            null  // No specific homework ID for Quran homework
        );
    } catch (\Exception $e) {
        \Log::error('Failed to send homework notification', [
            'session_id' => $this->id,
            'type' => $type,
            'error' => $e->getMessage(),
        ]);
    }
}
```

#### File: `app/Models/AcademicSession.php`

```php
// After academic homework is assigned
protected static function boot()
{
    parent::boot();

    static::updated(function ($session) {
        // Check if homework was just assigned
        if ($session->isDirty('homework_description') && $session->homework_description) {
            $session->notifyHomeworkAssigned();
        }
    });
}

public function notifyHomeworkAssigned()
{
    try {
        $student = $this->student;
        if (!$student) return;

        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->sendHomeworkAssignedNotification(
            $this,
            $student,
            null
        );
    } catch (\Exception $e) {
        \Log::error('Failed to send homework notification', [
            'session_id' => $this->id,
            'error' => $e->getMessage(),
        ]);
    }
}
```

---

### 2. Payment Notifications

#### File: `app/Services/PaymentService.php`

Find the method that processes successful payments (likely `processPayment` or `handleWebhook`):

```php
// After payment is confirmed as successful
public function processSuccessfulPayment($payment, $subscription = null)
{
    // ... existing payment processing code ...

    // Prepare notification data
    $notificationData = [
        'amount' => $payment->amount,
        'currency' => $payment->currency ?? 'SAR',
        'description' => $payment->description ?? 'الاشتراك',
        'payment_id' => $payment->id,
        'transaction_id' => $payment->transaction_id ?? null,
    ];

    // Add subscription context if available
    if ($subscription) {
        $notificationData['subscription_id'] = $subscription->id;

        if ($subscription instanceof \App\Models\QuranSubscription) {
            $notificationData['subscription_type'] = 'quran';
            $notificationData['circle_id'] = $subscription->quran_circle_id;
        } elseif ($subscription instanceof \App\Models\AcademicSubscription) {
            $notificationData['subscription_type'] = 'academic';
        }
    }

    // Send notification
    try {
        $user = $payment->user;
        if ($user) {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->sendPaymentSuccessNotification($user, $notificationData);
        }
    } catch (\Exception $e) {
        \Log::error('Failed to send payment success notification', [
            'payment_id' => $payment->id,
            'error' => $e->getMessage(),
        ]);
    }

    return $payment;
}
```

#### Payment Failed Notification

```php
// After payment fails
public function processFailedPayment($payment)
{
    // ... existing failure handling ...

    try {
        $user = $payment->user;
        if ($user) {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->send(
                $user,
                \App\Enums\NotificationType::PAYMENT_FAILED,
                [
                    'amount' => $payment->amount,
                    'currency' => $payment->currency ?? 'SAR',
                    'reason' => $payment->failure_reason ?? 'فشل الدفع',
                ],
                '/payments',
                ['payment_id' => $payment->id],
                true  // Mark as important
            );
        }
    } catch (\Exception $e) {
        \Log::error('Failed to send payment failure notification', [
            'payment_id' => $payment->id,
            'error' => $e->getMessage(),
        ]);
    }

    return $payment;
}
```

---

### 3. Trial Request Notifications

#### File: `app/Models/QuranTrialRequest.php`

Add observer or use model events:

```php
// When trial request is approved
protected static function boot()
{
    parent::boot();

    static::updated(function ($trialRequest) {
        // If status changed to approved
        if ($trialRequest->isDirty('status') && $trialRequest->status === 'approved') {
            $trialRequest->notifyApproval();
        }

        // If status changed to rejected
        if ($trialRequest->isDirty('status') && $trialRequest->status === 'rejected') {
            $trialRequest->notifyRejection();
        }
    });
}

public function notifyApproval()
{
    try {
        $student = $this->student;
        if (!$student) return;

        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->send(
            $student,
            \App\Enums\NotificationType::ACCOUNT_VERIFIED,  // Or create TRIAL_APPROVED
            [
                'teacher_name' => $this->teacher?->full_name ?? '',
                'trial_date' => $this->trial_date?->format('Y-m-d') ?? '',
            ],
            "/quran-teachers/{$this->teacher_id}",  // Or specific trial page
            [
                'trial_request_id' => $this->id,
                'teacher_id' => $this->teacher_id,
            ],
            true
        );
    } catch (\Exception $e) {
        \Log::error('Failed to send trial approval notification', [
            'trial_request_id' => $this->id,
            'error' => $e->getMessage(),
        ]);
    }
}

public function notifyRejection()
{
    // Similar to notifyApproval but with rejection message
}
```

---

### 4. Subscription Expiring Notifications

#### Create: `app/Console/Commands/CheckExpiringSubscriptions.php`

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
    protected $description = 'Send notifications for subscriptions expiring soon';

    public function handle()
    {
        $notificationService = app(NotificationService::class);
        $count = 0;

        // Check subscriptions expiring in 7 days, 3 days, and 1 day
        foreach ([7, 3, 1] as $days) {
            $targetDate = now()->addDays($days)->startOfDay();
            $endDate = $targetDate->copy()->endOfDay();

            // Quran Subscriptions
            $quranSubs = QuranSubscription::whereBetween('end_date', [$targetDate, $endDate])
                ->where('status', 'active')
                ->with(['student', 'quranCircle'])
                ->get();

            foreach ($quranSubs as $subscription) {
                try {
                    if (!$subscription->student) continue;

                    $notificationService->send(
                        $subscription->student,
                        NotificationType::SUBSCRIPTION_EXPIRING,
                        [
                            'subscription_name' => 'حلقة ' . ($subscription->quranCircle?->name ?? 'القرآن'),
                            'days_left' => $days,
                            'expiry_date' => $subscription->end_date->format('Y-m-d'),
                        ],
                        "/circles/{$subscription->quran_circle_id}",
                        [
                            'subscription_id' => $subscription->id,
                            'circle_id' => $subscription->quran_circle_id,
                        ],
                        $days <= 3  // Important if 3 days or less
                    );
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Failed for Quran subscription {$subscription->id}: {$e->getMessage()}");
                }
            }

            // Academic Subscriptions
            $academicSubs = AcademicSubscription::whereBetween('end_date', [$targetDate, $endDate])
                ->where('status', 'active')
                ->with(['student'])
                ->get();

            foreach ($academicSubs as $subscription) {
                try {
                    if (!$subscription->student) continue;

                    $notificationService->send(
                        $subscription->student,
                        NotificationType::SUBSCRIPTION_EXPIRING,
                        [
                            'subscription_name' => 'الاشتراك الأكاديمي',
                            'days_left' => $days,
                            'expiry_date' => $subscription->end_date->format('Y-m-d'),
                        ],
                        "/academic-subscriptions/{$subscription->id}",
                        ['subscription_id' => $subscription->id],
                        $days <= 3
                    );
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Failed for Academic subscription {$subscription->id}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Sent {$count} subscription expiry notifications.");
        return 0;
    }
}
```

#### Schedule it in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:check-expiring')->dailyAt('09:00');
```

---

### 5. Meeting Recording Available Notification

#### File: `app/Services/RecordingService.php` (if it exists)

```php
// After recording is processed and available
public function notifyRecordingAvailable($recording, $session)
{
    try {
        $notificationService = app(\App\Services\NotificationService::class);

        // Get all students from the session
        $students = $session->students ?? collect([$session->student])->filter();

        foreach ($students as $student) {
            $notificationService->send(
                $student,
                \App\Enums\NotificationType::MEETING_RECORDING_AVAILABLE,
                [
                    'session_title' => $session->title ?? 'الجلسة',
                    'recording_date' => $session->start_time->format('Y-m-d'),
                ],
                "/sessions/{$session->id}",  // Or specific recording URL
                [
                    'recording_id' => $recording->id,
                    'session_id' => $session->id,
                ]
            );
        }
    } catch (\Exception $e) {
        \Log::error('Failed to send recording notification', [
            'recording_id' => $recording->id,
            'error' => $e->getMessage(),
        ]);
    }
}
```

---

## 🧪 Testing Each Integration

After implementing each feature, test it:

```bash
# Test attendance notification (complete a session)
# The session status service will automatically send notifications

# Test homework notification
# Assign homework to a session through Filament admin

# Test payment notification
# Process a payment (or use test mode)

# Test subscription expiring
php artisan subscriptions:check-expiring

# Check notifications in database
php artisan tinker --execute="echo \DB::table('notifications')->latest()->limit(5)->get();"
```

---

## 📊 Verification Checklist

After all integrations:

- [ ] Attendance notifications sent when session ends
- [ ] Homework notifications sent when homework assigned
- [ ] Payment success notifications sent after payment
- [ ] Payment failed notifications sent after failed payment
- [ ] Subscription expiring notifications sent daily
- [ ] Trial approval/rejection notifications sent
- [ ] Recording available notifications sent (if applicable)
- [ ] All notifications have specific object URLs
- [ ] Notifications appear in UI on page refresh
- [ ] Notifications appear in real-time (if Reverb running)
- [ ] Parent notifications work (for students)

---

## 🎯 Priority Order

1. **High Priority** (Complete these first):
   - ✅ Attendance notifications (DONE)
   - Homework notifications
   - Payment notifications

2. **Medium Priority**:
   - Subscription expiring notifications
   - Trial request notifications

3. **Low Priority**:
   - Recording available notifications
   - Other system notifications

---

## 📝 Notes

- All notification methods use try-catch to prevent failures from breaking main functionality
- Notifications are sent asynchronously where possible
- Parent notifications piggyback on student notifications
- URLs are specific to objects for better UX
- Notifications are tenant-scoped automatically

---

**Next Steps:**
1. Implement homework notifications (highest priority)
2. Implement payment notifications
3. Create and schedule subscription expiry command
4. Test all integrations thoroughly
5. Monitor notification delivery in production
