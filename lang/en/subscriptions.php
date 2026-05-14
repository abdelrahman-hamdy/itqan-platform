<?php

return [
    'course_types' => [
        'interactive' => 'Interactive Course',
        'recorded' => 'Recorded Course',
        'training' => 'Training Course',
    ],

    'course' => [
        'one_time_purchase' => 'One-time purchase',
        'access_expired' => 'Course access has expired. Please renew your subscription to continue.',
        'access_expires_in' => 'Course access will expire in :days days.',
        'almost_done' => 'You are almost done with the course! Keep up the great progress.',
        'progress_percent' => 'You have completed :percent% of the course. Keep going!',
        'started_course' => 'You have started the course. Keep attending to achieve the best results.',
        'not_started' => 'The course has not started yet. Wait for the next session to begin.',
        'completed' => 'Congratulations! You have successfully completed the course.',
        'almost_done_watching' => 'You are almost done with the course! Keep up the great progress.',
        'progress_watching' => 'You have completed :percent% of the course. Keep going!',
        'started_watching' => 'You have started the course. Keep watching to complete the lessons.',
        'start_now' => 'Start the course now to achieve your learning goals.',
    ],

    'type_quran' => 'Quran Subscription',
    'type_academic' => 'Academic Subscription',
    'type_course' => 'Course Subscription',

    // Payment confirmation
    'confirm_payment' => 'Confirm Payment',
    'confirm_subscription_payment' => 'Confirm Subscription Payment',
    'confirm_payment_description' => 'Payment will be confirmed and the subscription activated if pending or cancelled.',
    'confirm_payment_grace_period' => 'Subscription is in grace period until :grace_end. Confirming payment will start a new period from the original end date (:ends_at).',
    'payment_reference_label' => 'Payment Reference (optional)',
    'payment_reference_placeholder' => 'Receipt number or transfer reference',
    'payment_confirmed_title' => 'Payment Confirmed',
    'payment_confirmed_and_activated' => 'Payment confirmed and subscription activated successfully.',
    'payment_confirmation_failed' => 'Payment Confirmation Failed',
    'payment_confirmed_by' => 'Payment confirmed by',
    'payment_reference' => 'Reference',
    'admin_payment_reference' => 'Admin payment reference',
    'manual_payment_created_by_admin' => 'Manual payment created by admin',
    'admin_confirmed_payment' => 'Payment confirmed by admin',
    'admin_confirmed_with_reference' => 'Payment confirmed by admin - Reference: :reference',

    // Sessions exhausted
    'sessions_exhausted' => 'Sessions Completed',
    'sessions_exhausted_message' => 'All available sessions have been completed. You can renew to get additional sessions.',
    'grace_period_label' => 'Grace Period',

    // Renewal
    'subscription_not_found' => 'Subscription not found',
    'renewal_already_pending' => 'A pending renewal already exists for this subscription.',
    'cannot_renew' => 'This subscription cannot be renewed in its current state.',
    'cannot_resubscribe' => 'Cannot resubscribe in current state. Subscription must be cancelled or expired.',
    'teacher_unavailable_select_new' => 'The original teacher is no longer available. Please select a new teacher.',
    'renew_subscription' => 'Renew Subscription',
    'resubscribe' => 'Re-subscribe',
    'select_package' => 'Select Package',
    'select_billing_cycle' => 'Select Billing Cycle',
    'renewal_success' => 'Subscription renewed successfully.',
    'resubscribe_success' => 'Subscription re-subscribed successfully.',
    'pay_current_cycle_first' => 'Please complete payment for the current period to continue.',
    'activation_mode' => 'Activation Mode',
    'activate_immediately' => 'Activate Immediately',
    'create_as_pending' => 'Create as Pending (requires payment)',
    'sessions_carryover' => ':count remaining sessions will carry over',

    // Admin wizard
    'create_full_subscription' => 'Create Full Subscription',
    'create_full_subscription_success' => 'Subscription created successfully with all related data.',
    'package_not_found' => 'Package not found.',
    'wizard_step1_title' => 'Subscription Type & Student',
    'wizard_step2_title' => 'Package & Pricing',
    'wizard_step3_title' => 'Payment Information',
    'wizard_step4_title' => 'Initial Progress (Optional)',
    'subscription_type_label' => 'Subscription Type',
    'type_quran_individual' => 'Quran - Individual',
    'type_quran_group' => 'Quran - Group Circle',
    'student_label' => 'Student',
    'search_student_placeholder' => 'Search by name or email...',
    'teacher_label' => 'Teacher',
    'select_teacher' => 'Select Teacher',
    'no_teachers_available' => 'No teachers available',
    'search_teacher_placeholder' => 'Search for a teacher...',
    'amount_label' => 'Amount',
    'discount_label' => 'Discount',
    'package_price_label' => 'Package Price',
    'final_price_label' => 'Final Price',
    'paid_externally_label' => 'Has the student paid outside the platform?',
    'yes_paid' => 'Yes, Paid',
    'not_paid_yet' => 'No, Not Yet',
    'sessions_per_month' => 'sessions/month',
    'payment_method_label' => 'Payment Method',
    'payment_method_manual' => 'Manual',
    'payment_method_bank' => 'Bank Transfer',
    'payment_method_cash' => 'Cash',
    'payment_method_mada' => 'Mada',
    'payment_method_other' => 'Other',
    'payment_notes_label' => 'Payment Notes',
    'consumed_sessions_label' => 'Previously Consumed Sessions',
    'consumed_sessions_help' => 'Number of sessions consumed outside the platform before creating this subscription.',
    'memorization_level_label' => 'Memorization Level',
    'level_beginner' => 'Beginner',
    'level_intermediate' => 'Intermediate',
    'level_advanced' => 'Advanced',
    'specialization_label' => 'Specialization',
    'specialization_memorization' => 'Memorization',
    'specialization_recitation' => 'Recitation',
    'specialization_tajweed' => 'Tajweed',
    'specialization_complete' => 'Complete',
    'previous_step' => 'Previous',
    'next_step' => 'Next',
    'select_circle' => 'Select Circle',
    'spots_available' => 'spots available',
    'payment_source_label' => 'Payment Source',
    'paid_outside' => 'Paid Outside Platform',
    'paid_outside_desc' => 'Student paid via cash or bank transfer',
    'paid_inside' => 'Pay via Platform',
    'paid_inside_desc' => 'Subscription pending until online payment',
    'pending_payment_notice' => 'Subscription will be created as pending. The student will need to pay via the payment gateway to activate it.',
    'learning_goals_label' => 'Learning Goals (optional)',
    'learning_goals_placeholder' => 'E.g.: Memorize Juz Amma, improve recitation...',
    'enrollment_type_label' => 'Enrollment Type',
    'normal_enrollment' => 'Normal Enrollment',
    'sponsored_enrollment' => 'Sponsored (Free)',
    'sponsored_free_notice' => 'A free sponsored subscription will be created for the student.',
    'sponsored_enrollment_notice' => 'The student will be enrolled for free as a sponsored student. No payment required.',

    // Recurring discount
    'is_recurring_discount_label' => 'Recurring discount (apply on renewal)',
    'is_recurring_discount_help' => 'When enabled, this discount will automatically apply when the subscription is renewed.',
    'recurring_discount_badge' => 'Recurring Discount',
    'recurring_discount_carried_forward' => 'Recurring discount from previous subscription.',
    'discount_optional_on_renewal' => 'Optional discount for the renewed subscription.',

    // Lifecycle error messages
    'errors' => [
        'cannot_cancel' => 'Cannot cancel subscription in its current state.',
        'cannot_pause' => 'Cannot pause subscription in its current state.',
        'cannot_resume' => 'Cannot resume subscription in its current state.',
        'no_auto_renewal_support' => 'This billing cycle does not support auto-renewal.',
        'certificate_already_issued' => 'Certificate has already been issued.',
        'certificate_not_eligible' => 'Subscription is not eligible for a certificate.',
        'invalid_package' => 'The selected package is not available.',
        'queued_cycle_exists' => 'A renewal is already prepared for this subscription and will activate automatically when the current cycle ends.',
        'current_cycle_unpaid' => 'The current subscription period is still unpaid. Please complete payment for it before renewing.',
        'cannot_cancel_paid' => 'Cannot cancel a paid subscription. It will expire automatically at the end date.',
        'cancel_reason_student' => 'Cancelled by student',
        'cannot_delete_package_with_subscriptions' => 'Cannot delete package with linked subscriptions. Deactivate it instead.',
        'delete_subscription' => 'Delete Subscription',
        'delete_subscription_heading' => 'Permanently Delete Subscription',
        'delete_subscription_warning' => 'This will permanently delete the subscription and ALL related data (sessions, circle, lessons, payments, reports). This action cannot be undone.',
        'delete_subscription_confirm' => 'Yes, Delete Permanently',
        'delete_subscription_success' => 'Subscription and all related data deleted successfully.',
        // Phase A.7 — student-initiated cancellation is removed (P3 / INV-G1).
        // Old mobile-app builds still hit the cancel endpoint; the API now
        // returns 403 with this localized message so the client can surface it.
        'student_cancel_forbidden' => 'Cancellation is handled by your academy admin. Please contact your supervisor if you need to end this subscription.',
        // Phase A.7 / P7 / INV-H1 — retired-package guards on student-driven
        // renewal screens. Admin/supervisor renewals bypass these (INV-H2).
        'previous_package_retired' => 'Your previous package is no longer available. Please pick a currently active package to continue.',
        'no_active_packages' => 'No active packages are currently available. Please contact your academy admin.',
    ],

    // Phase A.7 — short user-facing labels for the canonical primary actions
    // surfaced by `SubscriptionPresentation::primaryActionFor()`. Every
    // subscription surface (student blade, supervisor table, Filament badge,
    // mobile-app card) consumes these so labels stay consistent across roles.
    'primary_actions' => [
        'pay' => 'Pay now',
        'renew' => 'Renew',
        'resume' => 'Resume',
        'confirm_cash' => 'Confirm cash payment',
        'cancel' => 'Cancel',
        'create_new' => 'Start new subscription',
    ],

    // Type labels
    'types' => [
        'academic_private' => 'Private Academic Lessons',
        'academic_group' => 'Group Academic Lessons',
    ],

    'status' => [
        'awaiting_payment' => 'Awaiting payment',
        'awaiting_payment_long' => 'Current month is unpaid — complete payment to keep scheduling.',
        'pay_current_cycle' => 'Pay now',
    ],

    'generic_error' => 'An error occurred while processing. Please try again.',

    // Pause action
    'pause_label' => 'Pause',
    'pause_modal_heading' => 'Pause Subscription',
    'pause_modal_description' => 'The subscription will be paused temporarily and can be resumed later.',
    'pause_success' => 'Subscription paused successfully',

    // Resume action
    'resume_label' => 'Resume Subscription',
    'resume_modal_heading' => 'Resume Subscription',
    'resume_modal_description' => 'The subscription will be resumed and reactivated.',
    'resume_success' => 'Subscription resumed successfully',

    // Reactivate action
    'reactivate_label' => 'Reactivate Subscription',
    'reactivate_modal_heading' => 'Reactivate Cancelled Subscription',
    'reactivate_modal_description' => 'The cancelled subscription will be reactivated and payment confirmed. Start and end dates will be updated.',
    'reactivate_confirm_button' => 'Yes, Reactivate',
    'reactivate_success' => 'Subscription reactivated',
    'reactivate_success_body' => 'The cancelled subscription has been reactivated successfully.',

    // Extend grace period action
    'extend_grace_label' => 'Extend Grace Period',
    'extend_grace_modal_heading' => 'Extend Grace Period',
    'extend_grace_modal_description' => 'Grant the student an additional grace period. The original subscription end date (:ends_at) will not change.',
    'extend_grace_modal_description_for_paused' => 'This subscription was auto-paused because its paid window ended. Extending the grace period gives the student additional days to use any remaining sessions without a new payment. The original end date (:ends_at) will not change. For a full new billing cycle, use Renew instead.',

    // End-of-period pause banner (shown on subscription view page when
    // pause_reason === PauseReason::END_OF_PERIOD). Directs the admin to
    // Extend or Renew rather than Resume.
    'end_of_period_pause_title' => 'Paid window has ended',
    'end_of_period_pause_body' => 'This subscription was auto-paused because its paid window ended with no grace period and no queued cycle. To unblock: use "Extend Grace Period" to grant additional days on remaining sessions, or "Renew Subscription" to start a new billing cycle.',
    'not_specified' => 'not specified',
    'grace_days_label' => 'Grace Period Days',
    'day_suffix' => 'day(s)',
    'grace_calculated_from' => 'Grace period will be calculated from ',
    'grace_current_ends' => 'current grace period end: ',
    'subscription_ends_at_prefix' => 'subscription end date: ',
    'additional_days' => 'Additional days',
    'extend_grace_success' => 'Grace period extended',
    'extend_grace_success_body' => 'Grace period of :days days granted until :date',

    // Cancel extension action (remove an active grace period)
    'cancel_extension_label' => 'Cancel Grace Period',
    'cancel_extension_modal_heading' => 'Cancel Active Grace Period',
    'cancel_extension_modal_description' => 'The grace period will be removed from this subscription. If the original subscription window has ended, the subscription will be paused.',
    'cancel_extension_success' => 'Grace period cancelled',

    // Cycle-based renewal copy
    'renewal_payment_recorded_by_admin' => 'Renewal payment recorded by admin (new cycle)',
    'renewal_payment_pending' => 'Renewal payment pending — awaiting student payment',
    'payment_mode_label' => 'Payment Status',
    'payment_mode_paid' => 'Paid',
    'payment_mode_unpaid' => 'Unpaid (Pending)',
    'payment_mode_helper' => 'Paid: cycle activates as fully paid. Unpaid: cycle activates with pending payment — student attends sessions normally and pays later.',

    // Cancel action
    'cancel_label' => 'Cancel Subscription',
    'cancel_modal_heading' => 'Cancel Subscription',
    'cancel_modal_description' => 'The subscription will be cancelled along with all upcoming scheduled sessions.',
    'cancel_confirm_button' => 'Yes, Cancel Subscription',
    'cancel_success' => 'Subscription cancelled',
    'cancel_success_body' => 'Subscription cancelled along with :count scheduled sessions.',

    // Create circle action
    'create_circle_label' => 'Create Circle',
    'create_circle_modal_heading' => 'Create Individual Circle',
    'create_circle_modal_description' => 'An individual circle will be created and linked to this subscription.',
    'specialization_interpretation' => 'Interpretation',
    'circle_name_label' => 'Circle Name (optional)',
    'circle_name_placeholder' => 'Auto-generated if left empty',
    'circle_description_label' => 'Circle Description (optional)',
    'learning_objectives_label' => 'Learning Objectives (optional)',
    'learning_objectives_placeholder' => 'Add a learning objective',
    'default_session_duration_label' => 'Default Session Duration',
    'auto_activated_title' => 'Subscription auto-activated',
    'auto_activated_body' => 'Subscription activated because payment is confirmed and circle was created.',
    'create_circle_success' => 'Circle created',
    'create_circle_success_body' => 'Individual circle created: :code',

    // Cancel pending action
    'cancel_pending_label' => 'Cancel Pending Request',
    'cancel_pending_modal_heading' => 'Cancel Pending Subscription Request',
    'cancel_pending_modal_description' => 'Are you sure you want to cancel this subscription request? This action cannot be undone.',
    'cancel_pending_confirm_button' => 'Yes, Cancel Request',
    'cancel_pending_success' => 'Request cancelled',
    'cancel_pending_success_body' => 'Subscription request cancelled successfully.',

    // Bulk cancel pending action
    'bulk_cancel_pending_label' => 'Cancel Selected Pending Requests',
    'bulk_cancel_pending_modal_heading' => 'Cancel Pending Subscription Requests',
    'bulk_cancel_pending_modal_description' => 'All selected pending subscription requests will be cancelled. This action cannot be undone.',
    'bulk_cancel_pending_confirm_button' => 'Yes, Cancel Requests',
    'bulk_cancel_pending_success' => 'Requests cancelled',
    'bulk_cancel_pending_success_body' => ':count subscription requests cancelled successfully.',

    // Filters
    'request_status_label' => 'Request Status',
    'filter_all_pending' => 'All Pending Requests',
    'filter_expired_pending' => 'Expired Requests',
    'filter_valid_pending' => 'Valid Requests',
    'filter_expired_hours' => 'Expired requests (> :hours hours)',

    // Expiry-with-leftover banner (subscription expired but paid sessions remain)
    'expired_with_leftover_title' => 'Subscription expired',
    'expired_with_leftover_body' => '{1} This subscription has expired with 1 paid session still unused. It will roll into the next cycle as soon as the student renews.|[2,*] This subscription has expired with :count paid sessions still unused. Remaining sessions will roll into the next cycle as soon as the student renews.',

    // Phase A.2 — bootstrap of session_consumption rows from legacy flags
    'bootstrap_consumption' => [
        'dry_run_notice' => 'Dry-run mode — no rows will be inserted. Drop --dry-run to apply.',
        'apply_notice' => 'Inserting session_consumption rows from legacy flags...',
        'metric_header' => 'Metric',
        'count_header' => 'Count',
    ],

    // Phase A.5 — SubscriptionViewState (docs/subscription-invariants.md §1)
    // The 8 exhaustive cases that every subscription surface (mobile card,
    // student blade, supervisor table, Filament badge) renders identically.
    // `helper` placeholders: :count (sessions_remaining), :date (ends_at),
    // :grace_date (grace_period_ends_at).
    'view_state' => [
        'pending_first_payment' => [
            'label' => 'Awaiting payment',
            'helper' => 'Awaiting payment to activate the subscription',
        ],
        'active_paid' => [
            'label' => 'Active',
            'helper' => 'Active subscription — :count sessions remaining until :date',
        ],
        'active_payment_due' => [
            'label' => 'Payment due',
            'helper' => 'Subscription is active but payment is overdue — please pay',
        ],
        'grace_admin' => [
            'label' => 'Grace period',
            'helper' => 'In grace period — renew before :grace_date',
        ],
        'paused_admin' => [
            'label' => 'Paused',
            'helper' => 'Subscription is temporarily paused',
        ],
        'paused_end_of_period' => [
            'label' => 'Sessions exhausted',
            'helper' => 'All sessions used — renew to continue',
        ],
        'expired' => [
            'label' => 'Expired',
            'helper' => 'Subscription expired — renew to continue',
        ],
        'cancelled' => [
            'label' => 'Cancelled',
            'helper' => 'Subscription cancelled',
        ],
    ],
];
