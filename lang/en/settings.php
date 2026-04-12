<?php

return [
    'attendance_rules' => 'Attendance Rules',
    'attendance_rules_description' => 'Attendance status is determined by the attended percentage of the scheduled session duration',

    // Student attendance thresholds
    'student_attendance' => 'Student Attendance',
    'student_full_attendance' => 'Student Full Attendance Threshold',
    'student_full_help' => 'If the student attends this percentage of the session or more, they are marked as fully attended',
    'student_partial_attendance' => 'Student Partial Attendance Threshold',
    'student_partial_help' => 'If the student attends this percentage or more (but less than full), they are marked as partially attended; below this they are marked absent',

    // Kept for backward compatibility with any views still referencing it (deprecated)
    'full_attendance_threshold' => 'Full Attendance Threshold',
    'full_attendance_help' => 'Minimum percentage required to count as fully attended',

    // Teacher attendance thresholds
    'teacher_attendance' => 'Teacher Attendance',
    'teacher_full_attendance' => 'Teacher Full Attendance Threshold',
    'teacher_full_attendance_help' => 'If the teacher attends this percentage of the session or more, they are marked as fully attended and earnings are credited',
    'teacher_partial_attendance' => 'Teacher Partial Attendance Threshold',
    'teacher_partial_attendance_help' => 'If the teacher attends this percentage or more they are marked as partially attended (student subscription still counts, but teacher does not earn); below this they are marked absent',

    // Cross-field validation
    'attendance_partial_lte_full' => 'Partial attendance percentage must be less than or equal to full attendance percentage',

    // Counting management
    'counting_management' => 'Session Counting Management',
    'counts_for_teacher' => 'Counts for Teacher',
    'counts_for_subscription' => 'Counts for Subscription',
    'teacher_attendance_status' => 'Teacher Attendance',
    'student_attendance_status' => 'Student Attendance',
    'override_by' => 'Overridden by',
    'auto_calculated' => 'Auto-calculated',
    'manually_set' => 'Manually set',
    'minutes' => 'min',
];
