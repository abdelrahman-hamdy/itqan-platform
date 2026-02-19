<?php

/**
 * English translations for Filament Admin Panel
 *
 * This file contains translations for Filament resources, forms, tables, and actions.
 * Use these keys with __('filament.key') pattern.
 */

return [
    // Form Labels
    'gender' => 'Gender',
    'phone' => 'Phone Number',
    'email' => 'Email',
    'name' => 'Name',
    'avatar' => 'Avatar',
    'is_active' => 'Active',
    'is_active_helper' => 'Is this record active?',
    'status' => 'Status',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    'last_login_at' => 'Last Login',
    'academy' => 'Academy',
    'description' => 'Description',
    'notes' => 'Notes',

    // Table Labels
    'table' => [
        'empty' => 'No data available',
        'search' => 'Search',
        'filters' => 'Filters',
        'actions' => 'Actions',
    ],

    // Tab Labels
    'tabs' => [
        'all' => 'All',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending',
        'pending_approval' => 'Pending Approval',
        'pending_requests' => 'Pending Requests',
        'approved' => 'Approved',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
        'paused' => 'Paused',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'individual' => 'Individual',
        'group' => 'Group',
        'published' => 'Published',
        'draft' => 'Draft',
        'free' => 'Free',
        'paid' => 'Paid',
        'quran' => 'Quran Teachers',
        'academic' => 'Academic Teachers',
        'unpaid' => 'Unpaid',
        'disputed' => 'Disputed',
        'rejected' => 'Rejected',
    ],

    // Actions
    'actions' => [
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'create' => 'Create',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'approve' => 'Approve',
        'approve_teacher' => 'Approve Teacher',
        'approve_confirm_heading' => 'Confirm Approval',
        'approve_confirm_description' => 'Are you sure you want to approve this teacher?',
        'reject' => 'Reject',
        'reject_confirm_heading' => 'Confirm Rejection',
        'reject_confirm_description' => 'Are you sure you want to reject this request?',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'activate_confirm_heading' => 'Confirm Activation',
        'activate_confirm_description' => 'Are you sure you want to activate this record?',
        'deactivate_confirm_heading' => 'Confirm Deactivation',
        'deactivate_confirm_description' => 'Are you sure you want to deactivate this record?',
        'finalize' => 'Finalize',
        'dispute' => 'Dispute',
        'pay' => 'Pay',
        'mark_paid' => 'Mark as Paid',
        'restore' => 'Restore',
        'force_delete' => 'Force Delete',
        'restore_selected' => 'Restore Selected',
        'force_delete_selected' => 'Force Delete Selected',
    ],

    // Filters
    'filters' => [
        'from_date' => 'From Date',
        'to_date' => 'To Date',
        'active_status' => 'Active Status',
        'upcoming' => 'Upcoming',
        'ongoing' => 'Ongoing',
        'date_range' => 'Date Range',
        'status' => 'Status',
        'type' => 'Type',
        'trashed' => 'Deleted Records',
        'today_sessions' => 'Today\'s Sessions',
        'this_week_sessions' => 'This Week\'s Sessions',
    ],

    // Navigation Groups
    'nav_groups' => [
        'dashboard' => 'Dashboard',
        'user_management' => 'User Management',
        'quran_management' => 'Quran Management',
        'academic_management' => 'Academic Management',
        'recorded_courses' => 'Recorded Courses',
        'teacher_settings' => 'Teacher Settings',
        'settings' => 'Settings',
        'reports' => 'Reports',
        'payments' => 'Payments',
        'system_management' => 'System Management',
        'academy_management' => 'Academy Management',
        'quran_memorization' => 'Quran Memorization',
        'interactive_courses' => 'Interactive Courses',
        'certificates' => 'Certificates',
        'developer_tools' => 'Developer Tools',
        'exams' => 'Exams',
        'reviews' => 'Reviews & Ratings',
        'reports_attendance' => 'Reports & Attendance',
    ],

    // Common Labels
    'all' => 'All',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'yes' => 'Yes',
    'no' => 'No',
    'none' => 'None',
    'unknown' => 'Unknown',
    'not_set' => 'Not Set',

    // Resource-specific
    'children_count' => 'Number of Children',
    'has_active_subscription' => 'Has Active Subscription',
    'subscription_code' => 'Subscription Code',
    'session_count' => 'Session Count',
    'remaining_sessions' => 'Remaining Sessions',
    'completed_sessions' => 'Completed Sessions',
    'total_amount' => 'Total Amount',
    'monthly_amount' => 'Monthly Amount',

    // Teacher-specific
    'teacher' => 'Teacher',
    'teacher_type' => 'Teacher Type',
    'quran_teacher' => 'Quran Teacher',
    'academic_teacher' => 'Academic Teacher',
    'approval_status' => 'Approval Status',
    'linked_to_account' => 'Linked to Account',

    // Student-specific
    'student' => 'Student',
    'grade_level' => 'Grade Level',
    'parent' => 'Parent',

    // Subscription-specific
    'subscription' => [
        'subscription' => 'Subscription',
        'payment_current' => 'Current',
        'payment_overdue' => 'Overdue',
    ],
    'package' => 'Package',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'billing_cycle' => 'Billing Cycle',

    // Payment-specific
    'payment' => 'Payment',
    'payment_method' => 'Payment Method',
    'payment_status' => 'Payment Status',
    'amount' => 'Amount',
    'paid_at' => 'Paid At',

    // Payout-specific
    'payout' => 'Payout',
    'payout_code' => 'Payout Code',
    'payout_status' => 'Payout Status',

    // Earning-specific
    'earning' => 'Earning',
    'earning_month' => 'Earning Month',
    'calculation_method' => 'Calculation Method',
    'is_finalized' => 'Finalized',
    'is_disputed' => 'Disputed',
    'dispute_notes' => 'Dispute Notes',

    // Circle-specific
    'circle' => [
        'circle' => 'Circle',
        'circle_type' => 'Circle Type',
        'memorization_level' => 'Memorization Level',
        'enrollment_status' => 'Enrollment Status',
        'enrollment_open' => 'Open',
        'enrollment_closed' => 'Closed',
        'enrollment_full' => 'Full',
        'age_group' => 'Age Group',
        'children' => 'Children',
        'youth' => 'Youth',
        'adults' => 'Adults',
        'all_ages' => 'All Ages',
        'male' => 'Male',
        'female' => 'Female',
        'mixed' => 'Mixed',
        'available_spots' => 'Has Available Spots',
    ],
    'gender_type' => 'Gender Type',
    'max_students' => 'Max Students',
    'current_students' => 'Current Students',
    'available_spots' => 'Available Spots',

    // Course-specific
    'course' => [
        'course' => 'Course',
        'course_title' => 'Course Title',
        'subject' => 'Subject',
        'is_free' => 'Price Type',
    ],
    'difficulty_level' => 'Difficulty Level',
    'is_published' => 'Published',
    'price' => 'Price',
    'enrollment_count' => 'Enrollment Count',

    // Session-specific
    'session' => 'Session',
    'session_status' => 'Session Status',
    'scheduled_at' => 'Scheduled At',
    'duration' => 'Duration',
    'attendance_status' => 'Attendance Status',

    // Dashboard Widget Labels
    'academic_teachers_active' => 'Active Academic Teachers',
    'academic_subscriptions_active' => 'Active Academic Subscriptions',
    'pending_subscriptions' => 'Pending Subscriptions',
    'today_sessions' => 'Today\'s Sessions',
    'active_courses' => 'Active Courses',
    'published_courses' => 'Published courses',
    'from_total' => 'From :count total',
    'from_total_this_month' => 'From :count sessions this month',
    'this_month' => 'This month',
    'needs_review' => 'Needs review',
    'no_pending_requests' => 'No pending requests',

    // Messages
    'messages' => [
        'created' => 'Created successfully',
        'updated' => 'Updated successfully',
        'deleted' => 'Deleted successfully',
        'approved' => 'Approved successfully',
        'rejected' => 'Rejected',
        'activated' => 'Activated',
        'deactivated' => 'Deactivated',
        'confirm_delete' => 'Are you sure you want to delete this record?',
        'no_records' => 'No records found',
    ],
];
