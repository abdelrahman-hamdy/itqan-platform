<?php

return [
    'categories' => [
        'session' => 'Sessions',
        'attendance' => 'Attendance',
        'homework' => 'Homework',
        'payment' => 'Payments',
        'meeting' => 'Meetings',
        'progress' => 'Progress',
        'chat' => 'Messages',
        'system' => 'System',
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
        'invoice_generated' => [
            'title' => 'New Invoice',
            'message' => 'A new invoice for :amount :currency has been generated',
        ],

        // Meeting Notifications
        'meeting_room_ready' => [
            'title' => 'Meeting Room Ready',
            'message' => 'The meeting room for :session_title is now ready',
        ],
        'meeting_participant_joined' => [
            'title' => 'Participant Joined',
            'message' => ':participant_name has joined the meeting',
        ],
        'meeting_participant_left' => [
            'title' => 'Participant Left',
            'message' => ':participant_name has left the meeting',
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
            'message' => 'Congratulations! You earned :certificate_name certificate',
        ],
        'course_completed' => [
            'title' => 'Course Completed',
            'message' => 'Congratulations! You successfully completed :course_name',
        ],

        // Chat Notifications
        'chat_message_received' => [
            'title' => 'New Message',
            'message' => 'You have a new message from :sender_name',
        ],
        'chat_mentioned' => [
            'title' => 'You were mentioned',
            'message' => ':sender_name mentioned you in :chat_name',
        ],
        'chat_group_added' => [
            'title' => 'Added to Group',
            'message' => 'You were added to :group_name',
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
    ],

    'preferences' => [
        'title' => 'Notification Preferences',
        'email' => 'Email',
        'push' => 'Push Notifications',
        'sms' => 'SMS',
    ],
];