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
];
