<?php

return [
    'types' => [
        'academic_session' => 'Academic Session',
        'quran_session' => 'Quran Session',
        'lecture' => 'Lecture',
        'interactive_course' => 'Interactive Course',
        'homework_prefix' => 'Homework: :title',
        'unspecified' => 'Unspecified',
    ],

    'quran' => [
        'memorization' => 'Memorization',
        'review' => 'Review',
        'comprehensive_review' => 'Comprehensive Review',
        'default_type' => 'Quran Homework',
        'title' => 'Quran Homework: :types',
        'evaluation_instruction' => 'This homework will be evaluated during the next session',
        'next_session_homework' => 'Quran homework for the next session',
        'new_memorization' => 'New Memorization: :range :pages',
        'review_section' => 'Review: :range :pages',
        'comprehensive_section' => 'Comprehensive Review: :surahs',
        'notes' => 'Notes: :notes',
        'pages_count' => '(:count pages)',
    ],

    'status' => [
        'graded' => 'Graded',
        'pending' => 'Pending',
    ],

    'invalid_file_type' => 'File type not allowed. Accepted types: PDF, images, Word, Excel.',
];
