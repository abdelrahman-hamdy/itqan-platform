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
        'apply_filters' => 'Search',
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

        // Stats
        'stats' => [
            'total_sessions' => 'Total Sessions',
            'scheduled_sessions' => 'Scheduled Sessions',
            'completed_sessions' => 'Completed Sessions',
            'cancelled_sessions' => 'Cancelled Sessions',
        ],

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
        'modal' => [
            'date_label' => 'Date',
            'time_label' => 'Time',
            'duration_label' => 'Session Duration',
            'teacher_label' => 'Teacher',
            'description_label' => 'Session Description',
            'participants_label' => 'Session Participants',
            'view_session' => 'View Session Page',
            'close' => 'Close',
        ],

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

        // Livewire Search Page
        'page_title' => 'Search Educational Resources',
        'page_description' => 'Search courses, circles, teachers, and all available resources',
        'search_placeholder' => 'Search for a course, teacher, or subject...',
        'found_results' => 'Found :count results',
        'searching' => 'Searching...',
        'clear_search' => 'Clear Search',

        // Tabs
        'tab_all' => 'All (:count)',
        'tab_quran_circles' => 'Quran Circles (:count)',
        'tab_individual_circles' => 'My Individual Circles (:count)',
        'tab_interactive_courses' => 'Interactive Courses (:count)',
        'tab_academic_sessions' => 'My Private Lessons (:count)',
        'tab_recorded_courses' => 'Recorded Courses (:count)',
        'tab_quran_teachers' => 'Quran Teachers (:count)',
        'tab_academic_teachers' => 'Academic Teachers (:count)',

        // Section Headers
        'section_quran_circles' => 'Group Quran Circles',
        'section_individual_circles' => 'My Individual Circles',
        'section_interactive_courses' => 'Interactive Courses',
        'section_academic_sessions' => 'My Private Lessons',
        'section_recorded_courses' => 'Recorded Courses',
        'section_quran_teachers' => 'Quran Teachers',
        'section_academic_teachers' => 'Academic Teachers',

        // No Results
        'no_results' => 'No Results Found',
        'no_results_for' => 'We could not find any results for ":query"',
        'no_results_title' => 'No Results Found',
        'no_results_description' => 'We could not find any results matching your search for',
        'no_results_suggestion' => 'Try using different or more general keywords.',
        'back_home' => 'Back to Home',

        // Empty State
        'empty_title' => 'Search All Educational Resources',
        'empty_description' => 'Search for courses, circles, teachers, and available subjects. Use the search box above to get started.',
        'suggestions_title' => 'Search Examples:',
        'suggestion_math' => 'Math',
        'suggestion_quran' => 'Quran',
        'suggestion_science' => 'Science',
        'suggestion_arabic' => 'Arabic Language',

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
        'apply_filters' => 'Search',
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

    // Homework Page
    'homework' => [
        // Page Title
        'title' => 'My Homework',
        'parent_title' => 'Children Homework',
        'description' => 'View and manage all homework (Academic + Quran + Interactive Courses)',
        'parent_description' => 'View all your children homework assignments',

        // Urgent Alert
        'urgent_alert' => 'Urgent homework (due within 24 hours)',
        'urgent_hours' => 'hours',

        // Statistics
        'stats_total' => 'Total Homework',
        'stats_pending' => 'Pending',
        'stats_submitted' => 'Submitted',
        'stats_average' => 'Average',
        'stats_overdue' => 'overdue',
        'stats_graded' => 'graded',
        'stats_completion_rate' => 'completion rate',
        'stats_academic' => 'Academic',
        'stats_quran' => 'Quran',
        'stats_interactive' => 'Interactive',

        // Filters
        'filter_status_label' => 'Status',
        'filter_status_all' => 'All Statuses',
        'filter_status_pending' => 'Pending',
        'filter_status_submitted' => 'Submitted',
        'filter_status_graded' => 'Graded',
        'filter_status_overdue' => 'Overdue',
        'filter_status_late' => 'Late Submission',
        'filter_type_label' => 'Homework Type',
        'filter_type_all' => 'All Types',
        'filter_type_academic' => 'Academic',
        'filter_type_quran' => 'Quran',
        'filter_type_interactive' => 'Interactive Course',
        'filter_button' => 'Filter',
        'reset_filters' => 'Reset Filters',

        // Type Badges
        'type_academic' => 'Academic',
        'type_quran' => 'Quran',
        'type_interactive' => 'Interactive Course',

        // Badges
        'badge_view_only' => 'View Only',
        'badge_late' => 'Late by',
        'badge_late_days' => 'day',
        'badge_late_days_plural' => 'days',
        'badge_overdue' => 'Overdue',
        'badge_due_soon' => 'Due in',
        'badge_due_hours' => 'hours',

        // Quran Details
        'quran_details_title' => 'Quran Homework Details:',
        'quran_new_memorization' => 'New Memorization:',
        'quran_review' => 'Review:',
        'quran_pages' => 'page',
        'quran_pages_plural' => 'pages',

        // Meta Information
        'estimated_duration' => 'minutes',
        'teacher_feedback_title' => 'Teacher Feedback:',
        'progress_label' => 'Progress',

        // Actions
        'action_view_session' => 'View Session',
        'action_submit' => 'Submit Homework',
        'action_continue_submit' => 'Continue Submission',
        'action_view_details' => 'View Details',

        // Empty States
        'no_homework_title' => 'No Homework',
        'no_homework_filtered' => 'No homework found matching the selected criteria.',
        'no_homework_parent' => 'No homework has been assigned to your children yet.',
        'no_homework_student' => 'No homework has been assigned to you yet.',
    ],

    // Quiz Page
    'quiz' => [
        // Page Title
        'title' => 'My Quizzes',
        'description' => 'View all available quizzes and your attempt history',
        'result_title' => 'Quiz Result',

        // Counts
        'available_count' => 'available quizzes',
        'attempts_count' => 'total attempts',

        // Tabs
        'tab_available' => 'Available Quizzes',
        'tab_history' => 'Attempt History',
        'available_quiz' => 'available quiz',

        // Empty States
        'no_quizzes_title' => 'No Quizzes Available',
        'no_quizzes_description' => 'Quizzes will appear here when assigned by teachers in your circles or courses',
        'no_history_title' => 'No Previous Attempts',
        'no_history_description' => 'Your quiz attempt records will appear here after completing your first quiz',

        // Quiz Taking Page
        'time_remaining' => 'Time Remaining',
        'question' => 'question',
        'passing_score_label' => 'Passing Score:',
        'answer_all_warning' => 'Make sure to answer all questions before submitting',
        'submit_quiz' => 'Submit Quiz',
        'confirm_submit_title' => 'Confirm Quiz Submission',
        'confirm_submit_message' => 'Are you sure you want to submit the quiz?',
        'no_edit_after_submit' => 'You will not be able to modify your answers after submission',
        'cancel' => 'Cancel',
        'confirm_submission' => 'Confirm Submission',

        // Quiz Result Page
        'back_to_quizzes' => 'Back to Quizzes List',
        'best_score' => 'Best Score',
        'passed' => 'Passed',
        'failed' => 'Failed',
        'attempts_count_label' => 'Number of Attempts',
        'out_of' => 'out of',
        'passing_score' => 'Passing Score',
        'attempt_history' => 'Attempt History',
        'attempt_label' => 'Attempt',
        'score_label' => 'Score',
        'status_label' => 'Status',
        'submission_date' => 'Submission Date',
        'new_attempt' => 'New Attempt',
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
        'academy_default' => 'Itqan Academy',
        'platform_default' => 'Itqan Platform',
    ],

    // Teacher Detail Pages
    'teacher_detail' => [
        'quran_teacher_title' => 'Quran Teacher',
        'quran_teacher_description' => 'Learn Quran with',
        'quran_teacher_certified' => 'certified and qualified teacher at',
        'academic_teacher_title' => 'Academic Teacher',
        'academic_teacher_description' => 'Learn with',
        'academic_teacher_certified' => 'certified and qualified teacher at',
    ],

    // Course Session Pages
    'course_session' => [
        'interactive_session_default' => 'Interactive Session',
        'session_details' => 'Interactive Session Details',
        'interactive_course_default' => 'Interactive Course',
        'interactive_courses_breadcrumb' => 'Interactive Courses',
        'session_number' => 'Session',
        'feedback_placeholder' => 'Share your feedback about this session...',
        'submitting' => 'Submitting...',
        'submission_error' => 'Error submitting feedback',

        // Session Content
        'session_content_title' => 'Session Content',
        'student_feedback_title' => 'Your Session Feedback',
        'feedback_submitted_title' => 'Feedback Submitted',
        'feedback_notes_label' => 'Your Session Notes (Optional)',
        'submit_feedback' => 'Submit Feedback',
        'feedback_success' => 'Your feedback has been submitted successfully',

        // Live Session
        'live_session' => 'Live Session',
        'session_starting_soon' => 'Session Starting Soon',
        'session_scheduled_for' => 'This session is scheduled for :datetime',
        'join_10_minutes_before' => 'You can join 10 minutes before the start time.',
        'starts_in' => 'Starts :time',
        'homework_assignment' => 'Homework Assignment',
        'rate_session_label' => 'How would you rate this session?',
    ],

    // Circle Pages
    'circles' => [
        'quran_circles_title' => 'Quran Circles',
        'explore_description' => 'Explore available Quran circles',
        'explore_interactive' => 'Explore available interactive courses',
    ],

    // Session Detail
    'session_detail' => [
        'title_default' => 'Session Details',
        'academic_session_default' => 'Academic Session',
        'academic_description_prefix' => 'Academic session details with',
        'academic_teacher_default' => 'Academic Teacher',
        'academic_teachers_breadcrumb' => 'Academic Teachers',
        'academic_lesson_default' => 'Academic Lesson',
        'session_summary' => 'Session Summary',
        'lesson_content' => 'Lesson Content:',
        'learning_outcomes' => 'Learning Outcomes:',
        'your_rating' => 'Your Session Rating',
        'share_feedback' => 'Share your feedback about the session',
        'feedback_placeholder' => 'How was the session? What did you like? Any suggestions for improvement?',
        'submit_rating' => 'Submit Rating',
        'rating_required' => 'Please write your rating for the session',
        'rating_success' => 'Your rating has been submitted successfully',
        'rating_error' => 'An error occurred while submitting your rating. Please try again.',
        'submitting' => 'Submitting...',
        'attached_file' => 'Attached File',
        'homework_submitted' => 'Homework Submitted',
        'homework_submit_error' => 'An error occurred while submitting the homework. Please try again.',
        'quran_session_default' => 'Quran Session',
        'quran_description_prefix' => 'Quran session details with',
        'quran_teacher_default' => 'Quran Teacher',
        'quran_teachers_breadcrumb' => 'Quran Teachers',
        'quran_circle_default' => 'Quran Circle',
    ],

    // Certificate Pages
    'certificates' => [
        'title' => 'My Certificates',
        'parent_title' => 'Children Certificates',
        'description' => 'View and download all your certificates',
        'parent_description' => 'View all certificates earned by your children',
        'total_count' => 'Total Certificates',
        'filter_all' => 'All',
        'filter_recorded_courses' => 'Recorded Courses',
        'filter_recorded_courses_prefix' => 'Recorded',
        'filter_recorded_courses_suffix' => 'Courses',
        'filter_interactive_courses' => 'Interactive Courses',
        'filter_interactive_courses_prefix' => 'Interactive',
        'filter_interactive_courses_suffix' => 'Courses',
        'filter_quran' => 'Quran Circles',
        'filter_quran_prefix' => 'Quran',
        'filter_quran_suffix' => 'Circles',
        'filter_academic' => 'Academic Lessons',
        'filter_academic_prefix' => 'Academic',
        'filter_academic_suffix' => 'Lessons',
        'no_certificates_title' => 'No Certificates Yet',
        'no_certificates_student' => 'Your certificates will appear here when you complete courses or receive certificates from teachers',
        'no_certificates_parent' => 'Your children\'s certificates will appear here when they receive certificates from teachers or complete courses',
        'browse_courses' => 'Browse Courses',
    ],

    // Lesson Detail
    'lesson_detail' => [
        'recorded_courses' => 'Recorded Courses',
        'free_preview' => 'Free Preview',
        'duration' => 'Duration',
        'study_time_minutes' => 'Study Time (minutes)',
        'learning_objectives' => 'Learning Objectives',
        'notes' => 'Notes',
        'quick_actions' => 'Quick Actions',
        'return_to_learn' => 'Return to Learn Page',
        'download_video' => 'Download Video',
        'course_progress' => 'Course Progress',
        'overall_progress' => 'Overall Progress',
        'lessons_completed' => ':completed of :total lessons completed',
        'course_lessons' => 'Course Lessons',
        'minute' => 'minute',
        'video_unavailable' => 'Video not available yet',
        'video_coming_soon' => 'Video will be uploaded soon',
        'no_video' => 'No video available for this lesson',
    ],

    // Academic Subscription
    'academic_subscription' => [
        'title_default' => 'Academic Lesson',
        'description_prefix' => 'Private lesson details with',
        'academic_teacher_default' => 'Academic Teacher',
        'academic_teachers_breadcrumb' => 'Academic Teachers',
        'sessions_tab' => 'Sessions',
        'quizzes_tab' => 'Quizzes',
        'teacher_reviews_tab' => 'Teacher Reviews',
        'no_sessions_yet' => 'No sessions scheduled yet',
    ],

    // Individual Circle
    'individual_circle' => [
        'title_default' => 'Individual Circle',
        'description' => 'Individual Quran circle details',
        'quran_teachers_breadcrumb' => 'Quran Teachers',
        'sessions_tab' => 'Sessions',
        'quizzes_tab' => 'Quizzes',
        'teacher_reviews_tab' => 'Teacher Reviews',
        'no_sessions_yet' => 'No sessions scheduled yet',
    ],

    // Group Circle Detail
    'group_circle' => [
        'breadcrumb_circles' => 'Quran Circles',
        'meta_description' => 'Quran Circle Details:',
        'sessions_tab' => 'Sessions',
        'quizzes_tab' => 'Quizzes',
        'teacher_reviews_tab' => 'Teacher Reviews',
        'no_sessions_yet' => 'No sessions scheduled yet',
        'requirements_title' => 'Join Requirements',
        'enrollment_title' => 'Join Circle',
        'enroll_button' => 'Register Now for Circle',

        // Modal Messages
        'modal_enroll_title' => 'Join Circle',
        'modal_enroll_message' => 'Are you sure you want to join this circle? Your subscription will be activated immediately.',
        'modal_enroll_confirm' => 'Join Now',
        'modal_cancel' => 'Cancel',
        'modal_leave_title' => 'Cancel Enrollment',
        'modal_leave_message' => 'Are you sure you want to cancel your enrollment from this circle? You will lose access to all materials.',
        'modal_leave_confirm' => 'Cancel Enrollment',
        'modal_leave_cancel' => 'Stay in Circle',

        // Success/Error Messages
        'enroll_success' => 'You have been successfully enrolled in the circle',
        'redirecting_to_payment' => 'Redirecting to payment page...',
        'enroll_error_title' => 'Enrollment Error',
        'enroll_error_message' => 'An error occurred during enrollment',
        'leave_success' => 'Your enrollment has been successfully cancelled',
        'leave_error_title' => 'Error',
        'leave_error_message' => 'An error occurred while cancelling enrollment',
        'connection_error_title' => 'Connection Error',
        'connection_error_message' => 'An error occurred. Please try again',
        'ok_button' => 'OK',
    ],

    // Interactive Course Detail
    'course_detail' => [
        'circle_description' => 'Quran Circle Details',
    ],

    // Profile Page
    'profile' => [
        'page_title' => 'Student Profile',
        'edit_profile_title' => 'Edit Profile',
        'edit_profile_description' => 'Update your personal information',

        // Welcome Section
        'welcome' => 'Welcome,',
        'welcome_description' => 'Continue your learning journey and discover more exceptional educational content',

        // Sections
        'group_circles_title' => 'Group Quran Circles',
        'group_circles_subtitle' => 'Join Quran circles and participate in memorizing and reciting the Quran',
        'individual_circles_title' => 'Private Quran Circles',
        'individual_circles_subtitle' => 'One-on-one lessons with qualified Quran teachers',
        'interactive_courses_title' => 'Interactive Courses',
        'interactive_courses_subtitle' => 'Interactive academic courses in various subjects',
        'academic_private_title' => 'Private Lessons with Academic Teachers',
        'academic_private_subtitle' => 'One-on-one lessons with qualified academic subject teachers',

        // Actions
        'view_all_circles' => 'View All Circles',
        'view_all_quran_teachers' => 'View All Quran Teachers',
        'view_all_courses' => 'View All Courses',
        'view_all_academic_teachers' => 'View All Academic Teachers',
        'explore_courses' => 'Explore Courses',

        // Stats
        'active_circles' => 'active circle',
        'active_circle' => 'active circle',
        'active_subscription' => 'active subscription',
        'active_subscriptions' => 'active subscriptions',
        'active_course' => 'active course',
        'active_courses' => 'active courses',
        'recorded_courses' => 'course',
        'recorded_courses_plural' => 'courses',
        'available_badge' => 'Available',

        // Descriptions
        'with_teacher' => 'with',
        'quran_teacher_default' => 'Quran Teacher',
        'academic_teacher_default' => 'Academic Teacher',
        'teacher_default' => 'Teacher',
        'sessions_completed' => 'sessions completed out of',
        'grade_level' => 'Grade Level',
        'grade_level_default' => 'Grade Level',
        'monthly' => 'monthly',
        'custom_subscription' => 'Custom Subscription',
        'academic_lesson' => 'Academic Lesson',
        'lesson_label' => 'lesson',
        'progress_label' => 'Progress',

        // Recorded Courses Section
        'recorded_courses_title' => 'Recorded Courses',
        'recorded_courses_description' => 'Pre-recorded courses you can watch anytime',
        'no_recorded_courses_title' => 'No Recorded Courses',
        'no_recorded_courses_description' => 'No recorded courses found. Explore more available courses.',

        // Trial Requests Section
        'trial_requests_title' => 'Quran Trial Session Requests',
        'trial_requests_description' => 'Free trial sessions with qualified Quran teachers',
        'no_trial_requests_title' => 'No Trial Session Requests',
        'no_trial_requests_description' => 'Book a free trial session with one of our qualified Quran teachers and start your learning journey.',
        'request_trial_session' => 'Request Trial Session',
        'view_all_teachers' => 'View All Teachers',
        'requested_at' => 'Requested:',

        // Empty States
        'no_private_lessons_title' => 'No Private Lessons Yet',
        'no_private_lessons_description' => 'Start your learning journey by subscribing with one of our qualified academic teachers',
        'browse_academic_teachers' => 'Browse Academic Teachers',

        // Quran Circles Empty State
        'no_circles_title' => 'No Quran Circles Yet',
        'no_circles_description' => 'Join a group circle to memorize the Quran with qualified teachers and fellow students',
        'browse_circles' => 'Browse Quran Circles',

        // Quran Private Sessions Empty State
        'no_quran_sessions_title' => 'No Private Quran Sessions Yet',
        'no_quran_sessions_description' => 'Start your Quran learning journey with personalized one-on-one sessions',
        'browse_quran_teachers' => 'Browse Quran Teachers',

        // Interactive Courses Empty State
        'no_interactive_courses_title' => 'No Interactive Courses Yet',
        'no_interactive_courses_description' => 'Enroll in live interactive courses with expert teachers and real-time learning',
        'browse_interactive_courses' => 'Browse Courses',

        // Trial Requests Status
        'trial_status_pending' => 'Under Review',
        'trial_status_approved' => 'Approved',
        'trial_status_scheduled' => 'Scheduled',
        'trial_status_completed' => 'Completed',
        'trial_status_cancelled' => 'Cancelled',
        'trial_status_rejected' => 'Rejected',
        'time_preferences_morning' => 'Morning',
        'time_preferences_afternoon' => 'Afternoon',
        'time_preferences_evening' => 'Evening',
        'time_preferences_night' => 'Night',
        'trial_requests' => 'request',
        'trial_requests_plural' => 'requests',
    ],

    // Edit Profile
    'edit_profile' => [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email Address',
        'student_number' => 'Student Number',
        'phone' => 'Phone Number',
        'birth_date' => 'Date of Birth',
        'gender' => 'Gender',
        'gender_male' => 'Male',
        'gender_female' => 'Female',
        'gender_placeholder' => 'Select Gender',
        'nationality' => 'Nationality',
        'nationality_placeholder' => 'Select Nationality',
        'emergency_contact' => 'Emergency Contact',
        'grade_level' => 'Grade Level',
        'grade_level_placeholder' => 'Select Grade Level',
        'address' => 'Address',
    ],

    // Homework Submission
    'homework_submission' => [
        'submit_title' => 'Submit Homework',
        'view_title' => 'Homework Details',
        'type_academic' => 'Academic',
        'type_quran' => 'Quran',
        'type_interactive' => 'Interactive Course',
        'attachment' => 'Attachment',

        // Back Navigation
        'back_to_homework' => 'Back to Homework List',

        // Error Messages
        'error_loading_title' => 'Error Loading Homework',
        'error_loading_message' => 'Sorry, we could not load the requested homework information.',

        // Status Badges
        'status_late' => 'Late',

        // Homework Details
        'homework_description' => 'Homework Description:',
        'due_date_label' => 'Due Date',
        'submitted_label' => 'Submitted',
        'graded_label' => 'Graded',

        // Submission Details
        'student_answer' => 'Student Answer',
        'attached_files' => 'Attached Files',
        'teacher_feedback' => 'Teacher Feedback',
        'file_default_name' => 'Attached File',

        // Quality Scores
        'quality_assessment' => 'Quality Assessment',
        'content_quality' => 'Content Quality',
        'presentation_quality' => 'Presentation & Format',
        'effort_quality' => 'Effort',
    ],

    // Search Results
    'search_results' => [
        'academic_teachers' => 'Academic Teachers',
        'quran_teachers' => 'Quran Teachers',
    ],

    // Academic Teachers Listing Page
    'academic_teachers' => [
        'title' => 'Academic Teachers',
        'description' => 'Choose from our elite specialized teachers in academic subjects for private lessons',
        'my_teachers_count' => 'My Current Teachers:',
        'available_teachers' => 'available teacher',
        'available_teachers_plural' => 'available teachers',
        'showing_results' => 'Showing',
        'of_total' => 'of',

        // Pagination
        'page_label' => 'Page',
        'of_pages' => 'of',
        'previous' => 'Previous',
        'next' => 'Next',
        'teachers_of_total' => 'out of',
        'teachers_label' => 'teachers',

        // Empty State
        'no_teachers_title' => 'No Teachers Available',
        'no_results_description' => 'No teachers found matching your search criteria. Try adjusting filters.',
        'no_teachers_description' => 'No academic teachers available at the moment. New teachers will be added soon.',
        'reset_filters' => 'Reset Filters',
        'back_to_profile' => 'Back to Profile',
        'back_to_home' => 'Back to Home',
    ],

    // Quran Teachers Listing Page
    'quran_teachers' => [
        'title' => 'Quran Teachers',
        'description' => 'Choose from our elite qualified Quran teachers for private lessons',
        'my_teachers_count' => 'My Current Teachers:',
        'available_teachers' => 'available teacher',
        'available_teachers_plural' => 'available teachers',
        'showing_results' => 'Showing',
        'of_total' => 'of',

        // Pagination
        'page_label' => 'Page',
        'of_pages' => 'of',
        'previous' => 'Previous',
        'next' => 'Next',
        'teachers_of_total' => 'out of',
        'teachers_label' => 'teachers',

        // Empty State
        'no_teachers_title' => 'No Teachers Available',
        'no_results_description' => 'No teachers found matching your search criteria. Try adjusting filters.',
        'no_teachers_description' => 'No Quran teachers available at the moment. New teachers will be added soon.',
        'reset_filters' => 'Reset Filters',
        'back_to_profile' => 'Back to Profile',
        'back_to_home' => 'Back to Home',
    ],

    // Academic Teacher Detail Page
    'academic_teacher_detail' => [
        'breadcrumb_teachers' => 'Academic Teachers',
        'badge_text' => 'Academic Teacher',
        'subjects_and_grades' => 'Subjects & Grade Levels',
        'teaching_subjects' => 'Teaching Subjects',
        'grade_levels' => 'Grade Levels',
        'features_title' => 'Private Lesson Benefits',
        'features' => [
            'personalized_learning' => 'Personalized learning for each student',
            'custom_study_plan' => 'Custom study plan matching your level',
            'live_sessions' => 'Live online sessions',
            'homework_tracking' => 'Homework and continuous follow-up',
            'parent_reports' => 'Regular reports for parents',
            'flexible_schedule' => 'Flexible time selection',
        ],
        'choose_package' => 'Choose the Right Package for You',
        'package_description' => 'Educational plans designed to meet your needs and goals',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
    ],

    // Interactive Courses Listing Page
    'interactive_courses' => [
        'title' => 'Interactive Courses',
        'description' => 'Join live interactive courses in various academic subjects',
        'my_active_courses' => 'My Active Courses:',
        'available_courses' => 'available course',
        'available_courses_plural' => 'available courses',
        'showing_results' => 'Showing',
        'of_total' => 'of',

        // Empty State
        'no_courses_title' => 'No Courses Available',
        'no_results_description' => 'No courses found matching your search criteria',
        'show_all_courses' => 'Show All Courses',
        'back_to_profile' => 'Back to Profile',
        'back_to_home' => 'Back to Home',
    ],

    // Recorded Courses Listing Page
    'recorded_courses' => [
        'title' => 'Recorded Courses',
        'description' => 'Discover a diverse collection of high-quality recorded courses',
        'total_courses' => 'Total Courses',
        'courses_available' => 'courses available',
        'showing_results' => 'Showing',
        'of_total' => 'of',
        'no_courses_title' => 'No Courses Available',
        'no_courses_description' => 'No courses found matching your search criteria',
        'show_all_courses' => 'Show All Courses',
    ],

    // Assignments Page
    'assignments' => [
        'title' => 'My Assignments',
        'welcome' => 'Welcome to your assignments page',
        'under_development' => 'This page is under development...',
    ],

    // Toggle/Confirm Messages
    'confirm' => [
        'toggle_action_enable' => 'enable',
        'toggle_action_disable' => 'disable',
        'toggle_confirm_prefix' => 'Are you sure you want to',
        'toggle_confirm_suffix' => 'auto-renewal for this subscription?',
        'cancel_subscription_title' => 'Cancel Subscription',
        'cancel_subscription_message' => 'Are you sure you want to cancel this subscription? This action cannot be undone.',
    ],

    // Trial Request Detail Page (trial-request-detail.blade.php)
    'trial_request' => [
        'page_title' => 'Trial Session Details',
        'page_description' => 'Track your trial session request status',
        'breadcrumb' => 'Trial Request',
        'title' => 'Trial Session Request',
        'requested_at' => 'Requested',

        // Status Timeline
        'status_timeline' => 'Request Status',
        'step_pending' => 'Under Review',
        'step_approved' => 'Approved',
        'step_scheduled' => 'Scheduled',
        'step_completed' => 'Completed',
        'status_pending' => 'Pending',
        'status_approved' => 'Approved',
        'status_scheduled' => 'Scheduled',
        'status_completed' => 'Completed',
        'status_cancelled' => 'Cancelled',
        'status_rejected' => 'Rejected',

        // Teacher Information
        'teacher_info' => 'Teacher Information',
        'teacher_name' => 'Teacher Name',
        'teacher_not_assigned' => 'Teacher not assigned yet',
        'view_teacher_profile' => 'View Teacher Profile',

        // Session Details
        'session_details' => 'Session Details',
        'scheduled_date' => 'Scheduled Date',
        'scheduled_time' => 'Scheduled Time',
        'duration' => 'Duration',
        'minutes' => 'minutes',
        'join_session' => 'Join Session',
        'view_session_details' => 'View Session Details',
        'not_scheduled' => 'Not scheduled yet',
        'not_scheduled_title' => 'Session Not Yet Scheduled',
        'not_scheduled_description' => 'You will be notified when the teacher schedules your session',
        'session_not_scheduled' => 'Session has not been scheduled yet',

        // Your Request Details
        'your_request' => 'Your Request Details',
        'current_level' => 'Current Level',
        'learning_goals' => 'Learning Goals',
        'preferred_time' => 'Preferred Time',
        'your_notes' => 'Your Notes',
        'no_notes' => 'No notes',

        // Teacher Evaluation
        'teacher_evaluation' => 'Teacher Evaluation',
        'your_rating' => 'Your Rating',
        'teacher_feedback' => 'Teacher Feedback',
        'teacher_notes' => 'Teacher Notes',
        'no_feedback' => 'No feedback available yet',
        'feedback_available_after' => 'Teacher feedback will appear here after the session is completed',

        // Subscribe CTA
        'subscribe_cta_title' => 'Continue Your Learning Journey',
        'subscribe_cta_description' => 'Continue learning the Holy Quran with your teacher and subscribe now for regular sessions',

        // Actions
        'quick_actions' => 'Quick Actions',
        'back_to_subscriptions' => 'Back to Subscriptions',
        'subscribe_now' => 'Subscribe Now',
        'message_teacher' => 'Message Teacher',
        'view_details' => 'View Details',

        // Messages
        'request_pending' => 'Your request is being reviewed',
        'request_approved' => 'Your request has been approved',
        'session_scheduled' => 'Your session has been scheduled',
        'session_completed' => 'Your session has been completed',
        'ready_to_subscribe' => 'Ready to continue your learning journey?',
    ],

    // Payments Page
    'payments' => [
        'title' => 'Payments and Invoices',
        'parent_title' => 'Children Payments',
        'description' => 'View all your payments and invoices',
        'parent_description' => 'Track children payments and invoices',
        'total_payments' => 'Total Payments',
        'successful_payments' => 'Completed',
        'filters_title' => 'Filter Results',
        'status_label' => 'Status',
        'status_all' => 'All Statuses',
        'status_completed' => 'Completed',
        'status_pending' => 'Pending',
        'status_failed' => 'Failed',
        'status_refunded' => 'Refunded',
        'date_from' => 'From Date',
        'date_to' => 'To Date',
        'apply_filters' => 'Search',
        'reset_filters' => 'Reset',
        'fees_label' => 'Fees:',
        'transaction_id' => 'Transaction ID:',
        'tax_label' => 'Tax:',
        'discount_label' => 'Discount:',
        'net_amount' => 'Net Amount:',
        'date_not_available' => 'Not available',
        'download_receipt' => 'Download Receipt',
        'view_subscription' => 'View Subscription',
        'no_payments_title' => 'No Payments',
        'no_payments_parent_title' => 'No Children Payments',
        'no_payments_description' => 'You have not made any payments yet. When you subscribe to any service, your payments will appear here.',
        'no_payments_parent_description' => 'No payments have been recorded for children yet.',
    ],

    // Saved Payment Methods
    'saved_payment_methods' => [
        'title' => 'Saved Payment Methods',
        'description' => 'Manage saved cards for quick payment and automatic renewal',
        'add_new_card' => 'Add New Card',
        'default_badge' => 'Default',
        'expires_at' => 'Expires',
        'last_used' => 'Last used:',
        'set_default' => 'Set as Default',
        'delete' => 'Delete',
        'expired_warning' => 'This card has expired and cannot be used for payment',
        'no_cards_title' => 'No Saved Cards',
        'no_cards_description' => 'Save your card for easier future payments and automatic subscription renewal',
        'delete_modal_title' => 'Delete Payment Method',
        'delete_modal_message' => 'Are you sure you want to delete this card? You will not be able to use it for payment or automatic renewal after deletion.',
        'delete_confirm' => 'Yes, Delete Card',
        'delete_loading' => 'Deleting...',
        'cancel' => 'Cancel',
        'add_modal_title' => 'Add New Card',
        'loading_form' => 'Loading card form...',
        'error_title' => 'Error Occurred',
        'retry' => 'Retry',
        'security_notice' => 'Your data is fully protected and encrypted',
        'paymob_notice' => 'Card data is securely processed by Paymob',
        'load_form_error' => 'An error occurred while loading the card form. Please try again.',
        'tokenization_request_error' => 'Failed to create card save request',
        'tokenization_session_error' => 'Failed to get session key',
        'add_card_info_title' => 'To add a new card:',
        'add_card_info_message' => 'Make a new payment with the "Save card for future" option selected and your card will be saved automatically.',
        'understood' => 'OK, Got it',
        'tokenization_failed' => 'Failed to save card',
        'card_saved_success' => 'Card saved successfully',
        'card_already_saved' => 'This card is already saved',
        'redirecting_to_payment' => 'Redirecting to payment page...',
        'choose_method' => 'Choose Payment Method',
        'saved_cards' => 'Saved Cards',
        'new_card' => 'New Card',
        'new_card_description' => 'Add a new credit card',
        'save_for_future' => 'Save Card for Future',
        'save_for_future_description' => 'For easier payments and automatic renewal',
        'mobile_wallet' => 'Mobile Wallet',
        'mobile_wallet_description' => 'Vodafone Cash, Orange Cash, Etisalat Cash',
        'security_note' => 'All payments are encrypted and protected',
    ],

    // Common Elements
    'common' => [
        'academy_default' => 'Academy',
    ],
];
