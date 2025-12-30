<?php

/**
 * English translations for Enum labels
 *
 * This file contains translations for all enum labels used in the application.
 * Enums should use __('enums.enum_name.case_value') for their labels.
 *
 * @see App\Enums
 */

return [
    // Session Status
    'session_status' => [
        'unscheduled' => 'Unscheduled',
        'scheduled' => 'Scheduled',
        'ready' => 'Ready to Start',
        'ongoing' => 'Ongoing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'absent' => 'Student Absent',
    ],

    // Attendance Status
    'attendance_status' => [
        'attended' => 'Attended',
        'late' => 'Late',
        'left' => 'Left Early',
        'absent' => 'Absent',
    ],

    // Meeting Event Type
    'meeting_event_type' => [
        'joined' => 'Joined',
        'left' => 'Left',
    ],

    // Meeting Status
    'meeting_status' => [
        'not_created' => 'Not Created',
        'ready' => 'Ready',
        'active' => 'Active',
        'ended' => 'Ended',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
    ],

    // Payment Status
    'payment_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
        'partially_refunded' => 'Partially Refunded',
    ],

    // Subscription Status
    'subscription_status' => [
        'pending' => 'Pending Payment',
        'active' => 'Active',
        'paused' => 'Paused',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
        'completed' => 'Completed',
        'refunded' => 'Refunded',
    ],

    // Session Duration
    'session_duration' => [
        'thirty_minutes' => '30 minutes',
        'forty_five_minutes' => '45 minutes',
        'sixty_minutes' => '1 hour',
    ],

    // Difficulty Level
    'difficulty_level' => [
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
    ],

    // Relationship Type (Parent to Child)
    'relationship_type' => [
        'father' => 'Father',
        'mother' => 'Mother',
        'other' => 'Other',
    ],

    // Homework Submission Status
    'homework_submission_status' => [
        'not_started' => 'Not Started',
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'late' => 'Late',
        'graded' => 'Graded',
        'returned' => 'Returned for Review',
        'resubmitted' => 'Resubmitted',
    ],

    // Homework Status
    'homework_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'in_progress' => 'In Progress',
        'archived' => 'Archived',
    ],

    // Billing Cycle
    'billing_cycle' => [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
        'lifetime' => 'Lifetime',
    ],

    // Certificate Type
    'certificate_type' => [
        'recorded_course' => 'Recorded Course',
        'interactive_course' => 'Interactive Course',
        'quran_subscription' => 'Quran Circle',
        'academic_subscription' => 'Academic Sessions',
    ],

    // Certificate Template Style
    'certificate_template_style' => [
        'template_1' => 'Template 1',
        'template_2' => 'Template 2',
        'template_3' => 'Template 3',
        'template_4' => 'Template 4',
        'template_5' => 'Template 5',
        'template_6' => 'Template 6',
        'template_7' => 'Template 7',
        'template_8' => 'Template 8',
    ],

    // Interactive Course Status
    'interactive_course_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'active' => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    // Recording Status
    'recording_status' => [
        'recording' => 'Recording',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'deleted' => 'Deleted',
    ],

    // Week Days
    'week_days' => [
        'sunday' => 'Sunday',
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
    ],

    // Educational Qualification
    'educational_qualification' => [
        'diploma' => 'Diploma',
        'bachelor' => 'Bachelor\'s',
        'master' => 'Master\'s',
        'phd' => 'PhD',
        'other' => 'Other',
    ],

    // Teaching Language
    'teaching_language' => [
        'arabic' => 'Arabic',
        'english' => 'English',
        'french' => 'French',
        'german' => 'German',
    ],

    // Approval Status
    'approval_status' => [
        'pending' => 'Pending Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    // Review Status
    'review_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    // Business Request Status
    'business_request_status' => [
        'pending' => 'Pending',
        'reviewed' => 'Reviewed',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
    ],

    // Enrollment Status
    'enrollment_status' => [
        'pending' => 'Pending',
        'enrolled' => 'Enrolled',
        'active' => 'Active',
        'completed' => 'Completed',
        'dropped' => 'Dropped',
        'suspended' => 'Suspended',
    ],

    // Lesson Status
    'lesson_status' => [
        'pending' => 'Pending',
        'active' => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    // Payout Status
    'payout_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'paid' => 'Paid',
        'rejected' => 'Rejected',
    ],

    // Session Request Status
    'session_request_status' => [
        'pending' => 'Pending',
        'agreed' => 'Agreed',
        'paid' => 'Paid',
        'scheduled' => 'Scheduled',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
    ],

    // Trial Request Status
    'trial_request_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'scheduled' => 'Scheduled',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show' => 'No Show',
    ],

    // Subscription Payment Status
    'subscription_payment_status' => [
        'pending' => 'Pending Payment',
        'paid' => 'Paid',
        'failed' => 'Payment Failed',
        'refunded' => 'Refunded',
    ],

    // Gradient Palette
    'gradient_palette' => [
        'ocean_breeze' => 'Ocean Breeze',
        'sunset_glow' => 'Sunset Glow',
        'forest_mist' => 'Forest Mist',
        'purple_dream' => 'Purple Dream',
        'warm_flame' => 'Warm Flame',
    ],

    // Country
    'country' => [
        'SA' => 'Saudi Arabia',
        'AE' => 'United Arab Emirates',
        'EG' => 'Egypt',
        'QA' => 'Qatar',
        'KW' => 'Kuwait',
        'BH' => 'Bahrain',
        'OM' => 'Oman',
        'JO' => 'Jordan',
        'LB' => 'Lebanon',
        'IQ' => 'Iraq',
        'SY' => 'Syria',
        'YE' => 'Yemen',
        'PS' => 'Palestine',
        'MA' => 'Morocco',
        'DZ' => 'Algeria',
        'TN' => 'Tunisia',
        'LY' => 'Libya',
        'SD' => 'Sudan',
        'SO' => 'Somalia',
        'DJ' => 'Djibouti',
        'KM' => 'Comoros',
        'MR' => 'Mauritania',
    ],

    // Currency
    'currency' => [
        'SAR' => 'Saudi Riyal (SAR)',
        'AED' => 'UAE Dirham (AED)',
        'EGP' => 'Egyptian Pound (EGP)',
        'QAR' => 'Qatari Riyal (QAR)',
        'KWD' => 'Kuwaiti Dinar (KWD)',
        'BHD' => 'Bahraini Dinar (BHD)',
        'OMR' => 'Omani Rial (OMR)',
        'JOD' => 'Jordanian Dinar (JOD)',
        'LBP' => 'Lebanese Pound (LBP)',
        'IQD' => 'Iraqi Dinar (IQD)',
        'SYP' => 'Syrian Pound (SYP)',
        'YER' => 'Yemeni Rial (YER)',
        'ILS' => 'Israeli Shekel (ILS)',
        'MAD' => 'Moroccan Dirham (MAD)',
        'DZD' => 'Algerian Dinar (DZD)',
        'TND' => 'Tunisian Dinar (TND)',
        'LYD' => 'Libyan Dinar (LYD)',
        'SDG' => 'Sudanese Pound (SDG)',
        'SOS' => 'Somali Shilling (SOS)',
        'DJF' => 'Djiboutian Franc (DJF)',
        'KMF' => 'Comorian Franc (KMF)',
        'MRU' => 'Mauritanian Ouguiya (MRU)',
    ],

    // Notification Type (common ones)
    'notification_type' => [
        'session_reminder' => 'Session Reminder',
        'session_cancelled' => 'Session Cancelled',
        'session_rescheduled' => 'Session Rescheduled',
        'payment_received' => 'Payment Received',
        'payment_failed' => 'Payment Failed',
        'homework_assigned' => 'Homework Assigned',
        'homework_graded' => 'Homework Graded',
        'subscription_expiring' => 'Subscription Expiring Soon',
        'subscription_renewed' => 'Subscription Renewed',
    ],

    // Notification Category
    'notification_category' => [
        'session' => 'Sessions',
        'attendance' => 'Attendance',
        'homework' => 'Homework',
        'payment' => 'Payments',
        'meeting' => 'Meetings',
        'progress' => 'Progress',
        'system' => 'System',
    ],

    // Payment Flow Type
    'payment_flow_type' => [
        'redirect' => 'Redirect',
        'iframe' => 'Embedded Form',
        'api_only' => 'Direct API',
    ],

    // Payment Result Status
    'payment_result_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'success' => 'Success',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
        'partially_refunded' => 'Partially Refunded',
        'expired' => 'Expired',
    ],

    // Tailwind Color
    'tailwind_color' => [
        'red' => 'Red',
        'orange' => 'Orange',
        'amber' => 'Amber',
        'yellow' => 'Yellow',
        'lime' => 'Lime',
        'green' => 'Green',
        'emerald' => 'Emerald',
        'teal' => 'Teal',
        'cyan' => 'Cyan',
        'sky' => 'Sky',
        'blue' => 'Blue',
        'indigo' => 'Indigo',
        'violet' => 'Violet',
        'purple' => 'Purple',
        'fuchsia' => 'Fuchsia',
        'pink' => 'Pink',
        'rose' => 'Rose',
    ],

    // Timezone
    'timezone' => [
        'Asia/Riyadh' => 'Riyadh (GMT+3)',
        'Asia/Dubai' => 'Dubai (GMT+4)',
        'Africa/Cairo' => 'Cairo (GMT+2)',
        'Asia/Qatar' => 'Qatar (GMT+3)',
        'Asia/Kuwait' => 'Kuwait (GMT+3)',
        'Asia/Bahrain' => 'Bahrain (GMT+3)',
        'Asia/Muscat' => 'Muscat (GMT+4)',
        'Asia/Amman' => 'Amman (GMT+2)',
        'Asia/Beirut' => 'Beirut (GMT+2)',
        'Asia/Baghdad' => 'Baghdad (GMT+3)',
        'Asia/Damascus' => 'Damascus (GMT+2)',
        'Asia/Aden' => 'Aden (GMT+3)',
        'Asia/Gaza' => 'Gaza (GMT+2)',
        'Africa/Casablanca' => 'Casablanca (GMT+1)',
        'Africa/Algiers' => 'Algiers (GMT+1)',
        'Africa/Tunis' => 'Tunis (GMT+1)',
        'Africa/Tripoli' => 'Tripoli (GMT+2)',
        'Africa/Khartoum' => 'Khartoum (GMT+2)',
        'Africa/Mogadishu' => 'Mogadishu (GMT+3)',
        'Africa/Djibouti' => 'Djibouti (GMT+3)',
        'Indian/Comoro' => 'Comoros (GMT+3)',
        'Africa/Nouakchott' => 'Nouakchott (GMT+0)',
    ],

    // Circle Status
    'circle_status' => [
        'planning' => 'Planning',
        'pending' => 'Pending Start',
        'active' => 'Active',
        'ongoing' => 'Ongoing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'suspended' => 'Suspended',
        'inactive' => 'Inactive',
    ],

    // User Type
    'user_type' => [
        'student' => 'Student',
        'parent' => 'Parent',
        'quran_teacher' => 'Quran Teacher',
        'academic_teacher' => 'Academic Teacher',
        'supervisor' => 'Supervisor',
        'admin' => 'Academy Admin',
        'super_admin' => 'System Admin',
    ],

    // Payment Method
    'payment_method' => [
        'credit_card' => 'Credit Card',
        'debit_card' => 'Debit Card',
        'bank_transfer' => 'Bank Transfer',
        'cash' => 'Cash',
        'wallet' => 'E-Wallet',
        'paypal' => 'PayPal',
        'apple_pay' => 'Apple Pay',
        'google_pay' => 'Google Pay',
        'stc_pay' => 'STC Pay',
    ],

    // Quran Specialization
    'quran_specialization' => [
        'memorization' => 'Memorization',
        'recitation' => 'Recitation',
        'tajweed' => 'Tajweed',
        'review' => 'Review',
    ],

    // Memorization Level
    'memorization_level' => [
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
        'complete' => 'Hafiz',
    ],

    // Age Group
    'age_group' => [
        'children' => 'Children',
        'youth' => 'Youth',
        'adults' => 'Adults',
        'seniors' => 'Seniors',
        'mixed' => 'Mixed',
    ],

    // Gender (for individuals)
    'gender' => [
        'male' => 'Male',
        'female' => 'Female',
        'teacher_male' => 'Male Teacher',
        'teacher_female' => 'Female Teacher',
    ],

    // Gender Type (for groups/circles)
    'gender_type' => [
        'male' => 'Male',
        'female' => 'Female',
        'mixed' => 'Mixed',
    ],

    // Schedule Status
    'schedule_status' => [
        'active' => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'suspended' => 'Suspended',
    ],

    // Quran Learning Level
    'quran_learning_level' => [
        'beginner' => 'Beginner (Cannot read)',
        'elementary' => 'Elementary (Read slowly)',
        'intermediate' => 'Intermediate (Read fluently)',
        'advanced' => 'Advanced (Memorized parts)',
        'expert' => 'Expert (Memorized 10+ parts)',
        'hafiz' => 'Hafiz (Complete Quran)',
    ],

    // Learning Goal
    'learning_goal' => [
        'reading' => 'Learn proper reading',
        'tajweed' => 'Learn Tajweed rules',
        'memorization' => 'Memorize Quran',
        'improvement' => 'Improve performance',
    ],

    // Time Slot
    'time_slot' => [
        'morning' => 'Morning (6:00 - 12:00)',
        'afternoon' => 'Afternoon (12:00 - 18:00)',
        'evening' => 'Evening (18:00 - 22:00)',
    ],
];
