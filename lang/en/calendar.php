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
];
