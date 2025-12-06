<?php

return [
    // Navigation & Headings
    'earnings' => 'Earnings',
    'my_earnings' => 'My Earnings',
    'my_earnings_and_payouts' => 'My Earnings & Payouts',
    'payout_history' => 'Payout History',
    'earnings_details' => 'Earnings Details',
    'manage_payouts' => 'Manage Payouts',
    'teacher_payouts' => 'Teacher Payouts',
    'payouts' => 'Payouts',

    // Stats & Overview
    'this_month' => 'This Month',
    'last_month' => 'Last Month',
    'total_earnings' => 'Total Earnings',
    'all_time_earnings' => 'All-Time Earnings',
    'selected_period_earnings' => 'Selected Period Earnings',
    'completed_sessions' => 'Completed Sessions',
    'pending_earnings' => 'Pending Earnings',
    'paid_earnings' => 'Paid Earnings',
    'unpaid_earnings' => 'Unpaid Earnings',
    'counted_session' => 'Counted Session',
    'awaiting_payment' => 'Awaiting Payment',

    // Payout Statuses
    'pending_payout' => 'Pending Approval',
    'approved_payout' => 'Approved',
    'paid_payout' => 'Paid',
    'rejected_payout' => 'Rejected',

    'status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'paid' => 'Paid',
        'rejected' => 'Rejected',
    ],

    // Payment Types (Interactive Courses)
    'payment_type' => 'Payment Type',
    'payment_types' => [
        'fixed' => 'Fixed amount for entire course',
        'per_student' => 'Amount per enrolled student',
        'per_session' => 'Amount per session conducted',
    ],

    'payment_type_short' => [
        'fixed' => 'Fixed Amount',
        'per_student' => 'Per Student',
        'per_session' => 'Per Session',
    ],

    // Calculation Methods
    'calculation_method' => 'Calculation Method',
    'calculation_methods' => [
        'individual_rate' => 'Individual Session',
        'group_rate' => 'Group Session',
        'per_session' => 'Per Session',
        'per_student' => 'Per Student',
        'fixed' => 'Fixed Amount',
    ],

    // Table Columns
    'session_code' => 'Session Code',
    'session_type' => 'Session Type',
    'session_date' => 'Session Date',
    'amount' => 'Amount',
    'earning_amount' => 'Earning Amount',
    'total_amount' => 'Total Amount',
    'sessions_count' => 'Sessions Count',
    'calculated_at' => 'Calculated At',
    'payout_code' => 'Payout Code',
    'payout_month' => 'Payout Month',
    'payout_date' => 'Payout Date',
    'teacher_name' => 'Teacher Name',
    'teacher_type' => 'Teacher Type',

    // Teacher Types
    'teacher_types' => [
        'quran_teacher' => 'Quran Teacher',
        'academic_teacher' => 'Academic Teacher',
    ],

    // Filters
    'filter_by_month' => 'Filter by Month',
    'filter_by_status' => 'Filter by Status',
    'filter_by_session_type' => 'Filter by Session Type',
    'filter_by_teacher_type' => 'Filter by Teacher Type',
    'select_month' => 'Select Month',
    'select_status' => 'Select Status',
    'all_statuses' => 'All Statuses',
    'all_months' => 'All Months',
    'all_time' => 'All Time',

    // Actions
    'view_details' => 'View Details',
    'approve' => 'Approve',
    'reject' => 'Reject',
    'mark_as_paid' => 'Mark as Paid',
    'generate_payout' => 'Generate Payout',
    'generate_payouts' => 'Generate Payouts',
    'download_pdf' => 'Download PDF',
    'export' => 'Export',

    // Messages
    'no_earnings_found' => 'No earnings found',
    'no_payouts_found' => 'No payouts found',
    'no_earnings_yet' => 'No Earnings Yet',
    'no_payouts_yet' => 'No Payouts Yet',
    'earnings_will_appear_after_sessions' => 'Your earnings will appear here after completing sessions',
    'payouts_will_appear_when_issued' => 'Payouts will appear here when issued by management',
    'track_your_earnings_description' => 'Track your earnings from completed sessions and monthly payouts',
    'earnings_calculated' => 'Earnings calculated successfully',
    'payout_generated' => 'Payout generated successfully',
    'payout_approved' => 'Payout approved successfully',
    'payout_rejected' => 'Payout rejected',
    'payout_paid' => 'Payout marked as paid',

    // Payout Generation
    'generate_monthly_payout' => 'Generate Monthly Payout',
    'select_period' => 'Select Period',
    'select_teacher_type' => 'Select Teacher Type',
    'year' => 'Year',
    'month' => 'Month',
    'review_summary' => 'Review Summary',
    'confirm_generation' => 'Confirm Generation',

    // Approval/Rejection
    'approve_payout' => 'Approve Payout',
    'reject_payout' => 'Reject Payout',
    'approval_notes' => 'Approval Notes',
    'rejection_reason' => 'Rejection Reason',
    'rejection_reason_required' => 'Rejection reason is required',
    'confirm_approval' => 'Confirm Approval',
    'confirm_rejection' => 'Confirm Rejection',

    // Payment Details
    'mark_payout_as_paid' => 'Mark Payout as Paid',
    'payment_method' => 'Payment Method',
    'payment_reference' => 'Payment Reference',
    'payment_notes' => 'Payment Notes',
    'payment_methods' => [
        'bank_transfer' => 'Bank Transfer',
        'cash' => 'Cash',
        'check' => 'Check',
    ],

    // Breakdown
    'earnings_breakdown' => 'Earnings Breakdown',
    'earnings_by_source' => 'Earnings by Source',
    'by_session_type' => 'By Session Type',
    'by_calculation_method' => 'By Calculation Method',
    'session_details' => 'Session Details:',

    // Source Types
    'source_types' => [
        'individual_circle' => 'Individual Circle',
        'group_circle' => 'Group Circle',
        'academic_lesson' => 'Academic Lesson',
        'interactive_course' => 'Interactive Course',
    ],

    // Validation & Warnings
    'validation_errors' => 'Validation Errors',
    'disputed_earnings' => 'Disputed Earnings',
    'uncalculated_earnings' => 'Uncalculated Earnings',
    'amount_mismatch' => 'Amount Mismatch',
    'low_session_count' => 'Low Session Count',
    'high_amount_warning' => 'Warning: High Amount',

    // Months
    'months' => [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ],

    // Helper Text
    'currency' => 'SAR',
    'sar' => 'Saudi Riyal',
    'session' => 'Session',
    'sessions' => 'Sessions',
    'last_payout' => 'Last Payout',
    'next_payout' => 'Next Payout',
    'finalized' => 'Finalized',
    'not_finalized' => 'Not Finalized',
    'disputed' => 'Disputed',
    'current_month_payout' => 'Current Month Payout',
    'amount_label' => 'Amount',
    'notes_label' => 'Notes:',
    'date_not_specified' => 'Date Not Specified',
    'paid_status' => 'Paid',
    'pending_status' => 'Pending',
];
