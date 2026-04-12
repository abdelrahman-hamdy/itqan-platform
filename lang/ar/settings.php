<?php

return [
    'attendance_rules' => 'قواعد الحضور والغياب',
    'attendance_rules_description' => 'يتم تحديد حالة الحضور بناءً على نسبة المدة التي حضرها الطالب أو المعلم من مدة الجلسة المجدولة',

    // Student attendance thresholds
    'student_attendance' => 'حضور الطلاب',
    'student_full_attendance' => 'نسبة الحضور الكامل للطالب',
    'student_full_help' => 'إذا حضر الطالب هذه النسبة من مدة الجلسة أو أكثر، يُعتبر حاضراً بالكامل',
    'student_partial_attendance' => 'نسبة الحضور الجزئي للطالب',
    'student_partial_help' => 'إذا حضر الطالب هذه النسبة أو أكثر (وأقل من نسبة الحضور الكامل) يُعتبر حاضراً جزئياً، وإذا حضر أقل منها يُعتبر غائباً',

    // Kept for backward compatibility with any views still referencing it (deprecated)
    'full_attendance_threshold' => 'نسبة الحضور الكامل',
    'full_attendance_help' => 'النسبة المئوية المطلوبة لاعتبار الطالب حاضراً بالكامل',

    // Teacher attendance thresholds
    'teacher_attendance' => 'حضور المعلمين',
    'teacher_full_attendance' => 'نسبة الحضور الكامل للمعلم',
    'teacher_full_attendance_help' => 'إذا حضر المعلم هذه النسبة أو أكثر من مدة الجلسة، يُعتبر حاضراً ويتم احتساب أرباحه',
    'teacher_partial_attendance' => 'نسبة الحضور الجزئي للمعلم',
    'teacher_partial_attendance_help' => 'إذا حضر المعلم هذه النسبة أو أكثر يُعتبر حاضراً جزئياً (يُحتسب الاشتراك للطالب لكن لا تُحتسب أرباح للمعلم)، وإذا حضر أقل منها يُعتبر غائباً',

    // Cross-field validation
    'attendance_partial_lte_full' => 'يجب أن تكون نسبة الحضور الجزئي أقل من أو تساوي نسبة الحضور الكامل',

    // Counting management
    'counting_management' => 'إدارة احتساب الجلسة',
    'counts_for_teacher' => 'محتسبة للمعلم',
    'counts_for_subscription' => 'محتسبة للاشتراك',
    'teacher_attendance_status' => 'حضور المعلم',
    'student_attendance_status' => 'حضور الطالب',
    'override_by' => 'تم التعديل بواسطة',
    'auto_calculated' => 'محسوب تلقائياً',
    'manually_set' => 'تم تعيينه يدوياً',
];
