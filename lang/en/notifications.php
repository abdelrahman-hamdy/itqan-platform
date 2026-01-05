<?php

return [
    'categories' => [
        'session' => 'Sessions',
        'attendance' => 'Attendance',
        'homework' => 'Homework',
        'payment' => 'Payments',
        'meeting' => 'Meetings',
        'progress' => 'Progress',
        'system' => 'System',
        'review' => 'Reviews',
        'trial' => 'Trial Sessions',
        'alert' => 'Important Alerts',
    ],

    'types' => [
        // Session Notifications
        'session_scheduled' => [
            'title' => 'New Session Scheduled',
            'message' => 'Session :session_title has been scheduled with :teacher_name at :start_time',
        ],
        'session_reminder' => [
            'title' => 'Session Reminder',
            'message' => 'Your session :session_title will start in :minutes minutes at :start_time',
        ],
        'session_started' => [
            'title' => 'Session Started',
            'message' => 'Session :session_title has started now',
        ],
        'session_completed' => [
            'title' => 'Session Completed',
            'message' => 'Session :session_title has been completed successfully',
        ],
        'session_cancelled' => [
            'title' => 'Session Cancelled',
            'message' => 'Session :session_title has been cancelled',
        ],
        'session_rescheduled' => [
            'title' => 'Session Rescheduled',
            'message' => 'Session :session_title has been rescheduled to :new_time',
        ],

        // Attendance Notifications
        'attendance_marked_present' => [
            'title' => 'Attendance Marked Present',
            'message' => 'Your attendance has been marked as present for :session_title on :date',
        ],
        'attendance_marked_absent' => [
            'title' => 'Attendance Marked Absent',
            'message' => 'Your attendance has been marked as absent for :session_title on :date',
        ],
        'attendance_marked_late' => [
            'title' => 'Attendance Marked Late',
            'message' => 'Your attendance has been marked as late for :session_title on :date',
        ],
        'attendance_report_ready' => [
            'title' => 'Attendance Report Ready',
            'message' => 'Your attendance report for :period is now ready',
        ],

        // Homework Notifications
        'homework_assigned' => [
            'title' => 'New Homework',
            'message' => 'You have new homework from :teacher_name for :session_title - Due: :due_date',
        ],
        'homework_submitted' => [
            'title' => 'Homework Received',
            'message' => 'Your homework for :session_title has been received successfully',
        ],
        'homework_submitted_teacher' => [
            'title' => 'New Homework Submission',
            'message' => 'Student :student_name has submitted homework for :session_title',
        ],
        'homework_graded' => [
            'title' => 'Homework Graded',
            'message' => 'Your homework for :session_title has been graded - Score: :grade',
        ],
        'homework_deadline_reminder' => [
            'title' => 'Homework Deadline Reminder',
            'message' => 'Homework for :session_title is due in :hours hours',
        ],

        // Payment Notifications
        'payment_success' => [
            'title' => 'Payment Successful',
            'message' => 'Payment of :amount :currency successful - :description',
        ],
        'payment_failed' => [
            'title' => 'Payment Failed',
            'message' => 'Payment of :amount :currency failed - Please try again',
        ],
        'subscription_expiring' => [
            'title' => 'Subscription Expiring Soon',
            'message' => 'Your subscription to :subscription_name will expire on :expiry_date',
        ],
        'subscription_expired' => [
            'title' => 'Subscription Expired',
            'message' => 'Your subscription to :subscription_name has expired',
        ],
        'subscription_activated' => [
            'title' => 'Subscription Activated',
            'message' => 'Your subscription to :subscription_name has been activated successfully',
        ],
        'subscription_renewed' => [
            'title' => 'Subscription Renewed',
            'message' => 'Your subscription to :subscription_name has been renewed successfully',
        ],
        'invoice_generated' => [
            'title' => 'New Invoice',
            'message' => 'A new invoice for :amount :currency has been generated',
        ],

        // Teacher Payout Notifications
        'payout_approved' => [
            'title' => 'Payout Approved',
            'message' => 'Your payout for :month has been approved for :amount :currency',
        ],
        'payout_paid' => [
            'title' => 'Payout Completed',
            'message' => 'Your payout for :month of :amount :currency has been paid - Reference: :reference',
        ],

        // Meeting Notifications
        'meeting_room_ready' => [
            'title' => 'Meeting Room Ready',
            'message' => 'The meeting room for :session_title is now ready',
        ],
        'meeting_recording_available' => [
            'title' => 'Recording Available',
            'message' => 'The recording for :session_title is now available',
        ],
        'meeting_technical_issue' => [
            'title' => 'Technical Issue',
            'message' => 'A technical issue occurred in the meeting - :issue_description',
        ],

        // Academic Progress Notifications
        'progress_report_available' => [
            'title' => 'Progress Report Available',
            'message' => 'Your progress report for :period is now available',
        ],
        'achievement_unlocked' => [
            'title' => 'Achievement Unlocked!',
            'message' => 'Congratulations! You unlocked: :achievement_name',
        ],
        'certificate_earned' => [
            'title' => 'Certificate Earned',
            'message' => 'Congratulations! You got a certificate from :teacher_name',
        ],
        'course_completed' => [
            'title' => 'Course Completed',
            'message' => 'Congratulations! You successfully completed :course_name',
        ],

        // Quiz Notifications
        'quiz_assigned' => [
            'title' => 'New Quiz',
            'message' => 'A new quiz has been assigned: :quiz_title',
        ],
        'quiz_completed' => [
            'title' => 'Quiz Completed',
            'message' => 'You completed the quiz :quiz_title',
        ],
        'quiz_completed_teacher' => [
            'title' => 'Quiz Completed',
            'message' => 'Student :student_name completed quiz :quiz_title with score :score out of :passing_score',
        ],
        'quiz_passed' => [
            'title' => 'Quiz Passed!',
            'message' => 'Congratulations! You passed :quiz_title with a score of :score out of :passing_score',
        ],
        'quiz_failed' => [
            'title' => 'Quiz Not Passed',
            'message' => 'You did not reach the passing score for :quiz_title. Your score: :score out of :passing_score',
        ],
        'quiz_deadline_24h' => [
            'title' => 'Reminder: Quiz Deadline Tomorrow',
            'message' => 'The deadline for quiz ":quiz_title" is in 24 hours. Complete it before time runs out!',
        ],
        'quiz_deadline_1h' => [
            'title' => 'Urgent: Quiz Deadline in 1 Hour!',
            'message' => 'The deadline for quiz ":quiz_title" is in just 1 hour! Complete the quiz now.',
        ],

        // Review Notifications
        'review_received' => [
            'title' => 'New Review',
            'message' => 'You received a new review from :student_name - :rating stars',
        ],
        'review_approved' => [
            'title' => 'Your Review Was Approved',
            'message' => 'Your review has been approved and published successfully',
        ],

        // Trial Session Notifications
        'trial_request_received' => [
            'title' => 'New Trial Session Request',
            'message' => 'You have a new trial session request from :student_name',
        ],
        'trial_request_approved' => [
            'title' => 'Your Request Was Approved',
            'message' => 'Your trial session request with :teacher_name has been approved',
        ],
        'trial_session_scheduled' => [
            'title' => 'Trial Session Scheduled',
            'message' => 'Your trial session with :teacher_name has been scheduled for :scheduled_time',
        ],
        'trial_session_completed' => [
            'title' => 'Trial Session Completed',
            'message' => 'Your trial session with :teacher_name has been completed - You can now subscribe',
        ],
        'trial_session_reminder' => [
            'title' => 'Trial Session Reminder',
            'message' => 'Your trial session with :teacher_name starts in one hour',
        ],

        // Trial Session Notifications (role-specific)
        'trial_session_completed_student' => [
            'title' => 'Trial Session Completed',
            'message' => 'Your trial session with :teacher_name has been completed - You can now subscribe',
        ],
        'trial_session_completed_teacher' => [
            'title' => 'Trial Session Completed',
            'message' => 'Your trial session with student :student_name has been completed',
        ],
        'trial_session_reminder_student' => [
            'title' => 'Trial Session Reminder',
            'message' => 'Your trial session with :teacher_name starts in one hour',
        ],
        'trial_session_reminder_teacher' => [
            'title' => 'Trial Session Reminder',
            'message' => 'You have a trial session with student :student_name starting in one hour',
        ],
        'trial_session_reminder_parent' => [
            'title' => 'Trial Session Reminder',
            'message' => ':student_name has a trial session with :teacher_name starting in one hour',
        ],

        // Session Notifications (role-specific for parents)
        'session_reminder_parent' => [
            'title' => 'Session Reminder',
            'message' => ':student_name\'s session (:session_title) starts in :minutes minutes',
        ],
        'session_started_parent' => [
            'title' => 'Session Started',
            'message' => ':student_name\'s session (:session_title) has started',
        ],
        'session_completed_parent' => [
            'title' => 'Session Completed',
            'message' => ':student_name\'s session (:session_title) has been completed',
        ],

        // System Notifications
        'account_verified' => [
            'title' => 'Account Verified',
            'message' => 'Your account has been verified successfully',
        ],
        'password_changed' => [
            'title' => 'Password Changed',
            'message' => 'Your password has been changed successfully',
        ],
        'profile_updated' => [
            'title' => 'Profile Updated',
            'message' => 'Your profile information has been updated successfully',
        ],
        'system_maintenance' => [
            'title' => 'System Maintenance',
            'message' => 'The system will undergo maintenance at :maintenance_time',
        ],
    ],

    'actions' => [
        'view' => 'View',
        'mark_as_read' => 'Mark as Read',
        'mark_all_as_read' => 'Mark All as Read',
        'delete' => 'Delete',
        'settings' => 'Notification Settings',
    ],

    'empty' => [
        'title' => 'No Notifications',
        'message' => 'You have no notifications at the moment',
        'filtered_message' => 'No notifications found matching your selected filters.',
    ],

    'page' => [
        'title' => 'Notifications',
        'page_title_suffix' => 'Notifications - ',
        'description' => 'Track all your notifications and updates',
        'breadcrumb' => [
            'home' => 'Home',
            'notifications' => 'Notifications',
        ],
        'filters' => [
            'category' => 'Category',
            'all' => 'All',
            'unread_only' => 'Unread Only',
        ],
        'view_all' => 'View All Notifications',
        'loading' => 'Loading...',
    ],

    'preferences' => [
        'title' => 'Notification Preferences',
        'email' => 'Email',
        'push' => 'Push Notifications',
        'sms' => 'SMS',
    ],
];