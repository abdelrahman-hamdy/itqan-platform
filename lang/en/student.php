<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Student Pages Translation Lines (English)
    |--------------------------------------------------------------------------
    |
    | Translation keys for student-facing pages and interfaces
    |
    */

    // Subscriptions Page
    'subscriptions' => [
        'title' => 'Subscriptions',
        'parent_title' => 'Children Subscriptions',
        'description' => 'Manage all your subscriptions and enrolled courses',
        'parent_description' => 'Track children subscriptions and enrolled courses',
        'total_count' => 'Total Subscriptions',
        'active_count' => 'Active',

        // Filters
        'filters_title' => 'Filter Results',
        'status_label' => 'Status',
        'status_all' => 'All',
        'status_active' => 'Active',
        'status_inactive' => 'Inactive',
        'type_label' => 'Subscription Type',
        'type_all' => 'All Types',
        'type_quran_individual' => 'Individual Quran',
        'type_quran_group' => 'Group Circle',
        'type_academic' => 'Academic Lessons',
        'type_course' => 'Interactive Courses',
        'apply_filters' => 'Apply Filters',
        'reset_filters' => 'Reset',

        // Subscription Types
        'quran_individual_label' => 'Individual Quran',
        'quran_group_label' => 'Group Circle',
        'academic_label' => 'Academic Lessons',
        'course_label' => 'Interactive Course',

        // Status Labels
        'enrolled' => 'Enrolled',
        'completed' => 'Completed',

        // Actions
        'view_details' => 'View Details',
        'toggle_renew_disable' => 'Disable Auto-Renew',
        'toggle_renew_enable' => 'Enable Auto-Renew',
        'cancel' => 'Cancel',

        // Progress
        'sessions_label' => 'session',
        'sessions_remaining' => 'remaining',
        'students_label' => 'student',
        'progress_complete' => 'complete',

        // Billing
        'billing_monthly' => 'Monthly',
        'billing_quarterly' => 'Quarterly',
        'billing_yearly' => 'Yearly',
        'next_billing' => 'Renewal',
        'auto_renew_enabled' => 'Auto-Renew: Enabled',
        'auto_renew_disabled' => 'Auto-Renew: Disabled',

        // Empty States
        'no_results' => 'No Results',
        'no_subscriptions' => 'No Current Subscriptions',
        'parent_no_subscriptions' => 'No Children Subscriptions',
        'no_results_description' => 'No subscriptions found matching search criteria',
        'no_subscriptions_description' => 'Start your learning journey by subscribing to one of our educational programs',
        'parent_no_subscriptions_description' => 'No children subscriptions registered yet',
        'browse_teachers' => 'Browse Teachers',

        // Trial Requests
        'trial_requests_title' => 'Trial Sessions',
        'trial_session' => 'Trial Session',
        'teacher_not_specified' => 'Teacher Not Specified',
        'enter_session' => 'Enter Session',
        'subscribe_now' => 'Subscribe Now',
        'pending_approval' => 'Pending Approval',
        'request_rejected' => 'Request Rejected',

        // Modals
        'modal_cancel' => 'Cancel',
        'modal_confirm' => 'Confirm',
        'confirm_toggle_renew_title' => 'Auto-Renewal',
        'confirm_toggle_renew_enable' => 'Are you sure you want to enable auto-renewal for this subscription?',
        'confirm_toggle_renew_disable' => 'Are you sure you want to disable auto-renewal for this subscription?',
        'confirm_cancel_title' => 'Cancel Subscription',
        'confirm_cancel_message' => 'Are you sure you want to cancel this subscription? This action cannot be undone.',

        // Fallback Labels
        'individual_subscription' => 'Individual Subscription',
        'quran_circle' => 'Quran Circle',
        'subject_not_specified' => 'Subject Not Specified',
    ],

    // Interactive Course Detail
    'interactive_course' => [
        // Breadcrumb
        'courses_index' => 'Interactive Courses',

        // Status
        'status_finished' => 'Finished',
        'status_ongoing' => 'Ongoing',
        'status_enrollment_closed' => 'Enrollment Closed',
        'status_available' => 'Available for Enrollment',

        // Difficulty Levels
        'difficulty_beginner' => 'Beginner',
        'difficulty_intermediate' => 'Intermediate',
        'difficulty_advanced' => 'Advanced',

        // Sections
        'teacher_title' => 'Instructor',
        'learning_outcomes_title' => 'What You Will Learn',
        'prerequisites_title' => 'Prerequisites',
        'schedule_title' => 'Weekly Schedule',

        // Teacher Info
        'years_experience' => 'years experience',
        'total_students' => 'students',
        'certifications_title' => 'Certificates and Courses',
        'view_profile' => 'View Profile',
        'contact_teacher' => 'Contact Teacher',

        // Languages
        'languages' => [
            'Arabic' => 'Arabic',
            'English' => 'English',
            'French' => 'French',
            'Spanish' => 'Spanish',
            'German' => 'German',
            'Turkish' => 'Turkish',
            'Urdu' => 'Urdu',
        ],

        // Tabs
        'sessions_tab' => 'Sessions',
        'quizzes_tab' => 'Quizzes',
        'reviews_tab' => 'Reviews',
        'no_sessions_scheduled' => 'No sessions scheduled yet',

        // Enrollment Card
        'enrollment_status_title' => 'Enrollment Status',
        'enrolled_badge' => 'Enrolled in Course',
        'enrollment_date' => 'Enrollment Date',
        'course_progress' => 'Course Progress',
        'payment_status' => 'Payment Status',
        'enroll_title' => 'Course Enrollment',
        'enroll_button' => 'Enroll Now',
        'confirm_enrollment_title' => 'Confirm Course Enrollment',
        'confirm_enrollment_message' => 'Are you sure you want to enroll in this course?',
        'confirm_enrollment_with_fee' => 'An enrollment fee will be charged',
        'yes_enroll' => 'Yes, Enroll Now',

        // Course Information
        'course_info_title' => 'Course Information',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'enrollment_deadline' => 'Enrollment Deadline',
        'deadline_passed' => 'Enrollment Deadline Passed',

        // Countdown
        'countdown_days' => 'days',
        'countdown_hours' => 'hours',
        'countdown_minutes' => 'minutes',
        'countdown_seconds' => 'seconds',
    ],

    // Calendar Page
    'calendar' => [
        'title' => 'Calendar & Sessions',
        'parent_title' => 'Children Sessions Calendar',
        'description' => 'View all your sessions, circles, and courses',
        'parent_description' => 'View all children sessions, circles, and courses',

        // Navigation
        'previous_month' => 'Previous Month',
        'next_month' => 'Next Month',
        'today' => 'Today',

        // Legend
        'legend_scheduled' => 'Scheduled',
        'legend_ongoing' => 'Ongoing',
        'legend_completed' => 'Completed',
        'legend_cancelled' => 'Cancelled',

        // Days
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',

        // Months
        'months' => [
            'january' => 'January',
            'february' => 'February',
            'march' => 'March',
            'april' => 'April',
            'may' => 'May',
            'june' => 'June',
            'july' => 'July',
            'august' => 'August',
            'september' => 'September',
            'october' => 'October',
            'november' => 'November',
            'december' => 'December',
        ],

        // Modal
        'more_sessions' => 'more sessions',
        'duration' => 'minutes',
        'teacher_label' => 'Responsible Teacher',
        'student_label' => 'Student',

        // Session Types
        'quran_individual_session' => 'Individual Quran Session',
        'quran_circle_session' => 'Group Quran Circle',
        'course_session' => 'Interactive Course Session',
        'academic_session' => 'Academic Lesson',
        'session_default' => 'Session',

        // Status Labels
        'status_scheduled' => 'Scheduled',
        'status_ongoing' => 'Ongoing',
        'status_completed' => 'Completed',
        'status_cancelled' => 'Cancelled',
    ],

    // Search Page
    'search' => [
        'title' => 'Search Results',
        'results_for' => 'Search for',
        'search_for' => 'Searching for',
        'total_results' => 'Total Results',

        // No Results
        'no_results_title' => 'No Results Found',
        'no_results_description' => 'We could not find any results matching your search for',
        'no_results_suggestion' => 'Try using different or more general keywords.',
        'back_home' => 'Back to Home',

        // Result Sections
        'interactive_courses' => 'Interactive Courses',
        'recorded_courses' => 'Recorded Courses',
        'quran_circles' => 'Quran Circles',
        'quran_teachers' => 'Quran Teachers',
        'academic_teachers' => 'Academic Teachers',
        'view_all' => 'View All',

        // Circle Card
        'circle_title_default' => 'Quran Circle',
        'quran_teacher_default' => 'Quran Teacher',
        'teacher_label' => 'Teacher',
        'per_month' => 'SAR/month',
        'view_details' => 'View Details',

        // Teacher Card
        'academic_teacher_default' => 'Academic Teacher',
        'years_experience' => 'years experience',
        'view_profile' => 'View Profile',
    ],

    // Quran Circles Content
    'quran_circles' => [
        'title' => 'Quran Circles',
        'description' => 'Join Quran circles and participate in memorizing and reciting the Book of Allah',
        'my_active_circles' => 'My Active Circles',

        // Filters
        'filters_title' => 'Filter Results',
        'search_label' => 'Search',
        'search_placeholder' => 'Search for a circle...',
        'enrollment_status_label' => 'Enrollment Status',
        'status_all' => 'All',
        'status_my_circles' => 'My Circles',
        'status_available' => 'Available for Enrollment',
        'status_open' => 'Open',
        'status_full' => 'Full',
        'memorization_level_label' => 'Memorization Level',
        'level_all' => 'All Levels',
        'level_beginner' => 'Beginner',
        'level_intermediate' => 'Intermediate',
        'level_advanced' => 'Advanced',
        'schedule_days_label' => 'Study Days',
        'all_days' => 'All Days',
        'days_selected' => 'days',
        'apply_filters' => 'Apply Filters',
        'reset_filters' => 'Reset',

        // Days
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',

        // Results
        'circles_available' => 'circles available',
        'showing_results' => 'Showing',
        'of_total' => 'of',

        // Pagination
        'page_label' => 'Page',
        'of_pages' => 'of',
        'previous' => 'Previous',
        'next' => 'Next',
        'circles_of_total' => 'out of',
        'circles_label' => 'circles',

        // Empty State
        'no_circles_title' => 'No Circles Available',
        'no_results_description' => 'We could not find circles matching your search criteria. Try adjusting filters.',
        'no_circles_description' => 'No Quran circles available at the moment. New circles will be added soon.',
        'reset_filters_button' => 'Reset Filters',
        'back_to_profile' => 'Back to Profile',
        'back_to_home' => 'Back to Home',
    ],

    // Common Elements
    'common' => [
        'teacher_not_specified' => 'Teacher Not Specified',
        'subject_not_specified' => 'Subject Not Specified',
        'loading' => 'Loading...',
        'error' => 'An Error Occurred',
        'success' => 'Operation Successful',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'save' => 'Save',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'view' => 'View',
        'close' => 'Close',
    ],
];
