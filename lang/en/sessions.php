<?php

return [
    'status_display' => [
        'ready_message' => 'Session is ready - you can join now',
        'ready_button' => 'Join Session',
        'ongoing_message' => 'Session is ongoing - join to participate',
        'ongoing_button' => 'Join Ongoing Session',
        'completed_message' => 'Session completed successfully',
        'completed_button' => 'Session Ended',
        'cancelled_message' => 'Session cancelled',
        'cancelled_button' => 'Session Cancelled',
        'unscheduled_message' => 'Session not scheduled yet',
        'unscheduled_button' => 'Awaiting Scheduling',
        'default_message' => 'Session status: :status',
        'default_unknown' => 'Unknown',
        'default_button' => 'Not Available',

        'preparing_can_join' => 'Meeting is being prepared - you can join now',
        'scheduled_no_time' => 'Session is booked but time not set yet',
        'waiting_preparation' => 'Waiting for meeting preparation',
        'will_prepare_in' => 'Meeting will be prepared in :time',
        'preparing_now' => 'Preparing meeting...',

        'absent_teacher_can_join' => 'Session is active - you can start or join the meeting',
        'absent_student_can_join' => 'Your absence was recorded but you can join now',
        'absent_student_button' => 'Join Session (Absent)',
        'absent_teacher_expired' => 'Session period has ended',
        'absent_student_recorded' => 'Student absence recorded',
        'absent_student_button_text' => 'Student Absent',
    ],

    'naming' => [
        'session_n_student' => 'Session :n - :student',
        'session_n_circle' => 'Session :n - :circle',
        'trial_session' => 'Trial Session - :student',
        'default_student' => 'Student',
        'default_circle' => 'Circle',
        'default_teacher' => 'Teacher',
        'quran_individual_description' => 'Individual Quran memorization session with :student',
        'group_circle_description' => 'Circle session :circle',
        'trial_description' => 'Trial session to evaluate student level',
    ],

    'navigation' => [
        'quran_circles' => 'Group Quran Circles',
        'quran_teachers' => 'Quran Teachers',
        'interactive_courses' => 'Interactive Courses',
        'academic_teachers' => 'Academic Teachers',
        'recorded_courses' => 'Recorded Courses',
        'session_schedule' => 'Session Schedule',
        'trial_sessions' => 'Trial Sessions',
        'session_reports' => 'Session Reports',
        'homework' => 'Homework',
        'upcoming_sessions' => 'Upcoming Sessions',
        'subscriptions' => 'Subscriptions',
        'reports' => 'Reports',
    ],

    // Default session title fallbacks (used in API responses when no title is set)
    'default_title_quran' => 'Quran Session',
    'default_title_academic' => 'Academic Session',
    'default_title_interactive' => 'Interactive Session',
    'default_title_generic' => 'Session',

    'roles' => [
        'guest' => 'Guest',
        'student' => 'Student',
        'parent' => 'Parent',
        'teacher' => 'Teacher',
        'quran_teacher' => 'Quran Teacher',
        'academic_teacher' => 'Academic Teacher',
    ],
];
