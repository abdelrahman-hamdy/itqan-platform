<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Session Type Prefixes
    |--------------------------------------------------------------------------
    |
    | These prefixes are used to generate unique session codes.
    | Format: {TYPE}-{YYMM}-{SEQ} (e.g., QI-2601-0042)
    |
    */
    'type_prefixes' => [
        'quran_individual' => 'QI',
        'quran_group' => 'QG',
        'quran_trial' => 'QT',
        'academic_private' => 'AP',
        'interactive_course' => 'IC',
    ],

    /*
    |--------------------------------------------------------------------------
    | Display Name Templates
    |--------------------------------------------------------------------------
    |
    | Templates for generating dynamic session titles based on audience.
    | Titles are computed on-the-fly and update automatically when data changes.
    |
    | Available placeholders:
    | - :session_code - The unique session code
    | - :session_number - Session number (for interactive courses)
    | - :student_name - Full student name
    | - :student_first - First name of student
    | - :teacher_name - Full teacher name
    | - :teacher_first - First name of teacher
    | - :circle_name - Circle name (for Quran sessions)
    | - :circle_short - Truncated circle name
    | - :subject_name - Subject name (for academic sessions)
    | - :subject_short - Truncated subject name
    | - :course_title - Course title (for interactive courses)
    | - :course_short - Truncated course title
    | - :students_count - Number of students (for group sessions)
    | - :n - Session number shorthand
    |
    */
    'templates' => [
        /*
        |----------------------------------------------------------------------
        | Calendar View (Compact, scannable for quick identification)
        |----------------------------------------------------------------------
        */
        'calendar' => [
            'quran_individual' => ':student_first - فردي',
            'quran_group' => ':circle_short (:students_count)',
            'quran_trial' => ':student_first - تجريبي',
            'academic_private' => ':student_first - :subject_short',
            'interactive_course' => ':course_short #:n',
        ],

        /*
        |----------------------------------------------------------------------
        | Teacher View (Student-focused, for teacher's perspective)
        |----------------------------------------------------------------------
        */
        'teacher' => [
            'quran_individual' => ':student_name - تحفيظ',
            'quran_group' => ':circle_name (:students_count طالب)',
            'quran_trial' => ':student_name - جلسة تجريبية',
            'academic_private' => ':student_name - :subject_name',
            'interactive_course' => ':course_title (ج:n)',
        ],

        /*
        |----------------------------------------------------------------------
        | Student/Parent View (Teacher-focused, shows who is teaching)
        |----------------------------------------------------------------------
        */
        'student' => [
            'quran_individual' => 'حلقة القرآن - أ/:teacher_first',
            'quran_group' => ':circle_name - أ/:teacher_first',
            'quran_trial' => 'جلسة تجريبية - أ/:teacher_first',
            'academic_private' => ':subject_name - أ/:teacher_first',
            'interactive_course' => ':course_title - أ/:teacher_first',
        ],

        /*
        |----------------------------------------------------------------------
        | Admin View (Full context with session code for management)
        |----------------------------------------------------------------------
        */
        'admin' => [
            'quran_individual' => ':session_code | :student_first - :teacher_first',
            'quran_group' => ':session_code | :circle_short (:students_count)',
            'quran_trial' => ':session_code | تجريبي: :student_first',
            'academic_private' => ':session_code | :student_first - :subject_short',
            'interactive_course' => ':session_code | :course_short #:n',
        ],

        /*
        |----------------------------------------------------------------------
        | Notification View (Action-focused for alerts and notifications)
        |----------------------------------------------------------------------
        */
        'notification' => [
            'quran_individual' => 'جلسة تحفيظ مع :student_name',
            'quran_group' => 'حلقة :circle_name',
            'quran_trial' => 'جلسة تجريبية مع :student_name',
            'academic_private' => 'درس :subject_name مع :student_name',
            'interactive_course' => 'جلسة :course_title',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Truncation Settings
    |--------------------------------------------------------------------------
    |
    | Maximum length for truncated names in compact views.
    |
    */
    'truncation' => [
        'circle_short' => 15,
        'subject_short' => 10,
        'course_short' => 15,
        'name_short' => 10,
    ],
];
