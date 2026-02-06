<?php

return [
    'event' => [
        'cannot_move_type' => 'Cannot move this session type',
        'cannot_move_completed' => 'Cannot move a completed or cancelled session',
        'cannot_reschedule_status' => 'Cannot reschedule a session with this status',
        'cannot_schedule_past' => 'Cannot schedule a session in the past',
        'updated_successfully' => 'Session time updated successfully',
        'update_error' => 'An error occurred while updating the time',

        'cannot_resize_type' => 'Cannot resize this session type',
        'cannot_resize_completed' => 'Cannot resize a completed or cancelled session',
        'duration_updated' => 'Session duration updated to :duration minutes',
        'duration_update_error' => 'An error occurred while updating the duration',
        'min_duration' => 'Minimum session duration is :min minutes',
        'max_duration' => 'Maximum session duration is :max minutes',
    ],

    'subscription' => [
        'inactive' => 'Subscription is not active. Cannot move session.',
        'before_start' => 'Cannot schedule session before subscription start date (:date)',
        'after_end' => 'Cannot schedule session after subscription end date (:date)',
        'circle_inactive' => 'Circle is not active. Cannot move session.',
    ],

    'course' => [
        'unpublished' => 'Course is not published. Cannot move session.',
        'before_start' => 'Cannot schedule session before course start date (:date)',
        'after_end' => 'Cannot schedule session after course end date (:date)',
    ],

    'formatting' => [
        'educational_course' => 'Educational Course',
        'session' => 'Session',
        'group_circle' => 'Group Circle',
        'group_circle_prefix' => 'Group Circle - :description',
        'session_with_student' => 'Session with :name',
        'session_with_teacher' => 'Session with :name',
        'individual_with_student' => 'Individual session with :name',
        'individual_with_teacher' => 'Individual session with :name',
        'group_circle_description' => 'Group circle - :circle',
        'unknown_student' => 'Unknown Student',
        'unknown_teacher' => 'Unknown Teacher',
        'surah_number' => 'Surah #:number',
    ],

    'strategy' => [
        'individual_lessons' => 'Individual Lessons',
        'interactive_courses' => 'Interactive Courses',
        'academic_subject' => 'Academic Subject',
        'private_lesson' => 'Private Lesson - :subject',
        'unspecified' => 'Unspecified',
        'manage_academic_sessions' => 'Manage Academic Sessions',
        'select_academic_item' => 'Select a lesson or course to schedule its sessions on the calendar',
        'academic_session_types' => 'Academic Session Types',
        'session_title' => ':title - Session :number',

        'group_circles' => 'Group Circles',
        'individual_circles' => 'Individual Circles',
        'trial_sessions' => 'Trial Sessions',
        'circle_session_title' => 'Session :circle - :day :time',
        'auto_scheduled_description' => 'Auto-scheduled Quran circle session',
        'manage_quran_sessions' => 'Manage Circles & Sessions',
        'select_quran_item' => 'Select a circle or trial session to schedule its sessions on the calendar',
        'quran_session_types' => 'Circle & Session Types',

        'item_info_incomplete' => 'Item information is incomplete',
        'no_student_enrolled' => 'Cannot schedule sessions for a lesson without an enrolled student',
        'no_unscheduled_sessions' => 'No unscheduled sessions for this lesson',
        'all_times_conflict' => 'All selected times conflict with other sessions. Please choose different times.',
        'no_remaining_course_sessions' => 'No remaining sessions to schedule for this course',
        'no_valid_subscription' => 'Cannot schedule sessions for a circle without a valid subscription',
        'subscription_inactive' => 'Subscription is not active. Subscription must be activated to schedule sessions',
        'no_remaining_circle_sessions' => 'No remaining sessions to schedule for this circle',
    ],
];
