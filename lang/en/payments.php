<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payments English Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for payment-related pages and messages.
    |
    */

    'title' => 'Payments & Invoices',
    'my_payments' => 'My Payments',
    'payment_history' => 'Payment History',
    'invoices' => 'Invoices',
    'payment_details' => 'Payment Details',
    'invoice_details' => 'Invoice Details',

    // Status
    'status' => 'Status',
    'pending' => 'Pending',
    'processing' => 'Processing',
    'completed' => 'Completed',
    'failed' => 'Failed',
    'cancelled' => 'Cancelled',
    'refunded' => 'Refunded',
    'partially_refunded' => 'Partially Refunded',

    // Payment info
    'payment_code' => 'Payment Code',
    'payment_date' => 'Payment Date',
    'payment_method' => 'Payment Method',
    'payment_gateway' => 'Payment Gateway',
    'amount' => 'Amount',
    'fees' => 'Fees',
    'tax' => 'Tax',
    'discount' => 'Discount',
    'net_amount' => 'Net Amount',
    'total' => 'Total',
    'currency' => 'Currency',
    'description' => 'Description',
    'receipt' => 'Receipt',
    'receipt_number' => 'Receipt Number',

    // Subscription info
    'subscription' => 'Subscription',
    'subscription_type' => 'Subscription Type',
    'quran_subscription' => 'Quran Subscription',
    'academic_subscription' => 'Academic Subscription',
    'course_subscription' => 'Course Subscription',

    // Payment methods
    'credit_card' => 'Credit Card',
    'debit_card' => 'Debit Card',
    'bank_transfer' => 'Bank Transfer',
    'wallet' => 'E-Wallet',
    'cash' => 'Cash',
    'mada' => 'Mada',
    'visa' => 'Visa',
    'mastercard' => 'Mastercard',
    'apple_pay' => 'Apple Pay',
    'stc_pay' => 'STC Pay',

    // Actions
    'view_details' => 'View Details',
    'download_receipt' => 'Download Receipt',
    'download_invoice' => 'Download Invoice',
    'print_receipt' => 'Print Receipt',
    'request_refund' => 'Request Refund',
    'view_subscription' => 'View Subscription',

    // Filters
    'all_payments' => 'All Payments',
    'successful_payments' => 'Successful Payments',
    'pending_payments' => 'Pending Payments',
    'failed_payments' => 'Failed Payments',
    'filter_by_status' => 'Filter by Status',
    'filter_by_date' => 'Filter by Date',
    'filter_by_method' => 'Filter by Method',
    'search_payments' => 'Search Payments',

    // Summary
    'total_payments' => 'Total Payments',
    'total_spent' => 'Total Spent',
    'this_month' => 'This Month',
    'last_month' => 'Last Month',
    'this_year' => 'This Year',
    'last_payment' => 'Last Payment',

    // Messages
    'no_payments' => 'No Payments',
    'no_payments_message' => 'You haven\'t made any payments yet. When you subscribe to any service, your payments will appear here.',
    'payment_successful' => 'Payment Successful',
    'payment_failed' => 'Payment Failed',
    'payment_pending' => 'Payment Pending',
    'refund_requested' => 'Refund Requested',
    'refund_processed' => 'Refund Processed',

    // Time periods
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    'last_7_days' => 'Last 7 Days',
    'last_30_days' => 'Last 30 Days',
    'custom_range' => 'Custom Range',

    // Empty states
    'no_results' => 'No Results',
    'no_results_message' => 'We couldn\'t find any payments matching your criteria.',

    // Quran Subscription Payment Page
    'quran_payment' => [
        'page_title' => 'Quran Subscription Payment',
        'header_subtitle' => 'Quran Subscription Payment',
        'secure_payment' => 'Secure Payment',
        'complete_payment' => 'Complete Payment',
        'choose_method' => 'Choose your preferred payment method to complete your subscription',
        'payment_method_label' => 'Payment Method *',
        'credit_card_title' => 'Credit Card',
        'credit_card_desc' => 'Visa, MasterCard',
        'mada_title' => 'Mada',
        'mada_desc' => 'Saudi Debit Cards',
        'stc_pay_title' => 'STC Pay',
        'stc_pay_desc' => 'Mobile Payment',
        'bank_transfer_title' => 'Bank Transfer',
        'bank_transfer_desc' => 'Direct Transfer',
        'card_number' => 'Card Number *',
        'cardholder_name' => 'Cardholder Name *',
        'cardholder_placeholder' => 'As printed on the card',
        'expiry_month' => 'Month *',
        'expiry_year' => 'Year *',
        'month_placeholder' => 'Month',
        'year_placeholder' => 'Year',
        'cvv' => 'CVV *',
        'security_title' => 'Transaction Security',
        'security_message' => 'All transactions are encrypted and protected with the highest security standards. We do not store your credit card information.',
        'pay_button' => 'Pay :amount :currency',
        'subscription_details' => 'Subscription Details',
        'quran_teacher' => 'Quran Teacher',
        'package_label' => 'Package:',
        'group_circle' => 'Group Circle',
        'custom_subscription' => 'Custom Subscription',
        'subscription_type_label' => 'Subscription Type:',
        'private_sessions' => 'Private Sessions',
        'group_sessions' => 'Group Sessions',
        'sessions_count' => 'Number of Sessions:',
        'sessions_unit' => 'session',
        'subscription_duration' => 'Subscription Duration:',
        'billing_monthly' => 'One Month',
        'billing_quarterly' => 'Three Months',
        'billing_yearly' => 'One Year',
        'payment_summary' => 'Payment Summary',
        'subscription_price' => 'Subscription Price:',
        'discount_label' => 'Discount:',
        'price_after_discount' => 'Price After Discount:',
        'vat_label' => 'VAT (15%):',
        'total_amount' => 'Total Amount:',
        'need_help' => 'Need Help?',
        'help_message' => 'If you encounter any issues during payment, contact us',
        'processing_payment' => 'Processing Payment...',
        'processing_message' => 'Please do not close this page or press the back button',
        'payment_error' => 'An error occurred during payment',
        'connection_error' => 'Connection error. Please try again',
    ],

    // Subscription Flow Messages
    'subscription' => [
        // Success messages
        'created_successfully' => 'Subscription created successfully',
        'enrolled_successfully' => 'You have been successfully enrolled in the course! You can now follow sessions and educational content.',
        'enrollment_pending' => 'Enrollment request created. Please complete the payment process.',

        // Error messages
        'login_required' => 'You must be logged in as a student to subscribe',
        'login_to_continue' => 'Please login to continue',
        'student_only' => 'You must be a student to subscribe to academic packages',
        'complete_profile_first' => 'You must complete your student profile before enrolling in a course',
        'billing_cycle_unavailable' => 'The selected billing cycle is not available for this package',
        'already_subscribed' => 'You already have an active subscription with this teacher for this subject',
        'already_subscribed_or_pending' => 'You already have an active or pending subscription with this teacher',
        'already_enrolled' => 'You are already enrolled in this course',
        'enrollment_closed' => 'Sorry, enrollment for this course is currently closed',
        'payment_init_failed' => 'Failed to initiate payment process',
        'subscription_creation_error' => 'An error occurred while creating the subscription',
        'enrollment_error' => 'An error occurred during enrollment',
        'request_error' => 'An error occurred while submitting your request. Please try again',
        'unknown_error' => 'Unknown error',
    ],

    // Trial Session Messages
    'trial' => [
        'login_required' => 'You must be logged in as a student to book a trial session',
        'already_requested' => 'You already have a trial session request with this teacher',
        'request_success' => 'Your trial session request has been sent successfully! The teacher will contact you within 24 hours',
    ],

    // Notifications
    'notifications' => [
        'payment' => 'Payment',
        'generic_subscription' => 'Subscription',
        'quran_individual_subscription' => 'Individual Quran Subscription',
        'quran_group_subscription' => 'Group Quran Subscription',
    ],

    // Subscription Types
    'subscription_types' => [
        'individual' => 'Individual',
        'group' => 'Group',
        'academic' => 'Academic',
    ],

    // Academic
    'academic' => [
        'subject' => 'Subject',
    ],
];
