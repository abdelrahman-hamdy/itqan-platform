<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Session Messages (Arabic)
    |--------------------------------------------------------------------------
    |
    | Translations for session status display and session naming.
    |
    */

    // Session status display (SessionStatusService)
    'status_display' => [
        'ready_message' => 'الجلسة جاهزة - يمكنك الانضمام الآن',
        'ready_button' => 'انضم للجلسة',
        'ongoing_message' => 'الجلسة جارية الآن - انضم للمشاركة',
        'ongoing_button' => 'انضمام للجلسة الجارية',
        'completed_message' => 'تم إنهاء الجلسة بنجاح',
        'completed_button' => 'الجلسة منتهية',
        'cancelled_message' => 'تم إلغاء الجلسة',
        'cancelled_button' => 'الجلسة ملغية',
        'unscheduled_message' => 'الجلسة غير مجدولة بعد',
        'unscheduled_button' => 'في انتظار الجدولة',
        'default_message' => 'حالة الجلسة: :status',
        'default_unknown' => 'غير معروفة',
        'default_button' => 'غير متاح',

        // Scheduled sub-states
        'preparing_can_join' => 'جاري تحضير الاجتماع - يمكنك الانضمام الآن',
        'scheduled_no_time' => 'الجلسة محجوزة ولكن لم يتم تحديد موعد',
        'waiting_preparation' => 'في انتظار تحضير الاجتماع',
        'will_prepare_in' => 'سيتم تحضير الاجتماع خلال :time',
        'preparing_now' => 'جاري تحضير الاجتماع...',

        // Absent sub-states
        'absent_teacher_can_join' => 'الجلسة نشطة - يمكنك بدء أو الانضمام للاجتماع',
        'absent_student_can_join' => 'تم تسجيل غيابك ولكن يمكنك الانضمام الآن',
        'absent_student_button' => 'انضم للجلسة (غائب)',
        'absent_teacher_expired' => 'انتهت فترة الجلسة',
        'absent_student_recorded' => 'تم تسجيل غياب الطالب',
        'absent_student_button_text' => 'غياب الطالب',

        // Forgiven sub-states
        'forgiven_message' => 'تم إعفاء الطالب من هذه الجلسة',
        'forgiven_button' => 'معفو عنها',
    ],

    // Session naming (SessionNamingService)
    'naming' => [
        'session_n_student' => 'جلسة :n - :student',
        'session_n_circle' => 'جلسة :n - :circle',
        'individual_circle_session' => 'جلسة حلقة فردية :n - :student',
        'group_circle_session' => 'جلسة حلقة جماعية :n - :circle',
        'academic_session' => 'جلسة أكاديمية :n - :subject',
        'trial_session' => 'جلسة تجريبية - :student',
        'default_student' => 'طالب',
        'default_circle' => 'الحلقة',
        'default_teacher' => 'المعلم',
        'default_subject' => 'مادة',
        'quran_individual_description' => 'جلسة تحفيظ قرآن فردية مع :student',
        'group_circle_description' => 'جلسة حلقة :circle',
        'trial_description' => 'جلسة تجريبية لتقييم مستوى الطالب',
    ],

    // Navigation labels (NavigationService)
    'navigation' => [
        'quran_circles' => 'حلقات القرآن الجماعية',
        'quran_teachers' => 'معلمو القرآن',
        'interactive_courses' => 'الكورسات التفاعلية',
        'academic_teachers' => 'المعلمون الأكاديميون',
        'recorded_courses' => 'الكورسات المسجلة',
        'session_schedule' => 'جدول الجلسات',
        'trial_sessions' => 'الجلسات التجريبية',
        'session_reports' => 'تقارير الجلسات',
        'homework' => 'الواجبات',
        'upcoming_sessions' => 'الجلسات القادمة',
        'subscriptions' => 'الاشتراكات',
        'reports' => 'التقارير',
    ],

    // Default session title fallbacks (used in API responses when no title is set)
    'default_title_quran' => 'جلسة قرآنية',
    'default_title_academic' => 'جلسة أكاديمية',
    'default_title_interactive' => 'جلسة تفاعلية',
    'default_title_generic' => 'جلسة',

    // Role labels
    'roles' => [
        'guest' => 'ضيف',
        'student' => 'طالب',
        'parent' => 'ولي أمر',
        'teacher' => 'معلم',
        'quran_teacher' => 'معلم قرآن',
        'academic_teacher' => 'معلم أكاديمي',
    ],

    // Forgiveness actions (admin pardoning absence)
    'actions' => [
        'view_circle' => 'عرض الحلقة',
        'view_individual_circle' => 'عرض الحلقة الفردية',
        'view_lesson' => 'عرض الدرس',
        'view_course' => 'عرض الدورة',
        'view_subscription' => 'عرض الاشتراك',
        'forgive' => 'إعفاء الطالب',
        'forgive_heading' => 'إعفاء الطالب من الغياب',
        'forgive_description' => 'سيتم إرجاع الجلسة لرصيد الاشتراك وحذف مستحقات المعلم عن هذه الجلسة.',
        'forgive_confirm' => 'نعم، إعفاء',
        'forgive_success' => 'تم إعفاء الطالب بنجاح',
        'forgive_error' => 'فشل في إعفاء الطالب',
    ],

    'fields' => [
        'forgiven_reason' => 'سبب الإعفاء',
        'forgiven_at' => 'تاريخ الإعفاء',
        'forgiven_by' => 'تم الإعفاء بواسطة',
    ],

    'validation' => [
        'quarter_hour_only' => 'يجب أن يكون موعد الجلسة على أرباع الساعة فقط (00، 15، 30، 45)',
        'cannot_schedule_past' => 'لا يمكن جدولة جلسة في وقت ماضي',
        'conflict_quran' => 'يوجد تعارض مع جلسة قرآن (:time - :end). يجب ترك :break دقائق على الأقل بين الجلسات.',
        'conflict_academic' => 'يوجد تعارض مع جلسة أكاديمية (:time - :end). يجب ترك :break دقائق على الأقل بين الجلسات.',
        'conflict_course' => 'يوجد تعارض مع جلسة دورة (:title) في (:time - :end). يجب ترك :break دقائق على الأقل بين الجلسات.',
        'interactive_course_default' => 'دورة تفاعلية',
    ],
];
