<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Calendar Event Messages (Arabic)
    |--------------------------------------------------------------------------
    |
    | Translations for calendar event handling (drag, resize, validation).
    |
    */

    'event' => [
        'cannot_move_type' => 'لا يمكن تحريك هذا النوع من الجلسات',
        'cannot_move_completed' => 'لا يمكن تحريك جلسة مكتملة أو ملغاة',
        'cannot_reschedule_status' => 'لا يمكن إعادة جدولة جلسة بهذه الحالة',
        'cannot_schedule_past' => 'لا يمكن جدولة جلسة في وقت ماضي',
        'updated_successfully' => 'تم تحديث موعد الجلسة بنجاح',
        'update_error' => 'حدث خطأ أثناء تحديث الموعد',

        'cannot_resize_type' => 'لا يمكن تغيير مدة هذا النوع من الجلسات',
        'cannot_resize_completed' => 'لا يمكن تغيير مدة جلسة مكتملة أو ملغاة',
        'duration_updated' => 'تم تحديث مدة الجلسة إلى :duration دقيقة',
        'duration_update_error' => 'حدث خطأ أثناء تحديث المدة',
        'min_duration' => 'الحد الأدنى لمدة الجلسة :min دقيقة',
        'max_duration' => 'الحد الأقصى لمدة الجلسة :max دقيقة',
    ],

    'subscription' => [
        'inactive' => 'الاشتراك غير نشط. لا يمكن تحريك الجلسة.',
        'before_start' => 'لا يمكن جدولة الجلسة قبل تاريخ بدء الاشتراك (:date)',
        'after_end' => 'لا يمكن جدولة الجلسة بعد تاريخ انتهاء الاشتراك (:date)',
        'circle_inactive' => 'الحلقة غير نشطة. لا يمكن تحريك الجلسة.',
    ],

    'course' => [
        'unpublished' => 'الدورة غير منشورة. لا يمكن تحريك الجلسة.',
        'before_start' => 'لا يمكن جدولة الجلسة قبل تاريخ بدء الدورة (:date)',
        'after_end' => 'لا يمكن جدولة الجلسة بعد تاريخ انتهاء الدورة (:date)',
    ],

    // EventFormattingService
    'formatting' => [
        'educational_course' => 'دورة تعليمية',
        'session' => 'جلسة',
        'group_circle' => 'حلقة جماعية',
        'group_circle_prefix' => 'حلقة جماعية - :description',
        'session_with_student' => 'جلسة مع :name',
        'session_with_teacher' => 'جلسة مع الأستاذ :name',
        'individual_with_student' => 'جلسة فردية مع الطالب :name',
        'individual_with_teacher' => 'جلسة فردية مع الأستاذ :name',
        'group_circle_description' => 'حلقة جماعية - :circle',
        'unknown_student' => 'طالب غير محدد',
        'unknown_teacher' => 'معلم غير محدد',
        'surah_number' => 'سورة رقم :number',
    ],

    // Calendar Strategy (AcademicSessionStrategy, QuranSessionStrategy)
    'strategy' => [
        // Academic strategy labels
        'individual_lessons' => 'الدروس الفردية',
        'interactive_courses' => 'الدورات التفاعلية',
        'academic_subject' => 'مادة أكاديمية',
        'private_lesson' => 'درس خاص - :subject',
        'unspecified' => 'غير محدد',
        'manage_academic_sessions' => 'إدارة الجلسات الأكاديمية',
        'select_academic_item' => 'اختر درس أو دورة لجدولة جلساتها على التقويم',
        'academic_session_types' => 'أنواع الجلسات الأكاديمية',
        'session_title' => ':title - جلسة :number',

        // Quran strategy labels
        'group_circles' => 'الحلقات الجماعية',
        'individual_circles' => 'الحلقات الفردية',
        'trial_sessions' => 'الجلسات التجريبية',
        'circle_session_title' => 'جلسة :circle - :day :time',
        'auto_scheduled_description' => 'جلسة حلقة القرآن المجدولة تلقائياً',
        'manage_quran_sessions' => 'إدارة الحلقات والجلسات',
        'select_quran_item' => 'اختر حلقة أو جلسة تجريبية لجدولة جلساتها على التقويم',
        'quran_session_types' => 'أنواع الحلقات والجلسات',

        // Shared error messages
        'item_info_incomplete' => 'معلومات العنصر غير مكتملة',
        'no_student_enrolled' => 'لا يمكن جدولة جلسات لدرس بدون طالب مسجل',
        'no_unscheduled_sessions' => 'لا توجد جلسات غير مجدولة لهذا الدرس',
        'all_times_conflict' => 'جميع الأوقات المختارة تتعارض مع جلسات أخرى. يرجى اختيار أوقات مختلفة.',
        'no_remaining_course_sessions' => 'لا توجد جلسات متبقية لجدولتها في هذه الدورة',
        'no_valid_subscription' => 'لا يمكن جدولة جلسات لحلقة بدون اشتراك صالح',
        'subscription_inactive' => 'الاشتراك غير نشط. يجب تفعيل الاشتراك لجدولة الجلسات',
        'no_remaining_circle_sessions' => 'لا توجد جلسات متبقية للجدولة في هذه الحلقة',
    ],
];
