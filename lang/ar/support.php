<?php

return [
    // Page titles
    'page_title' => 'مركز الدعم',
    'my_tickets' => 'تقارير المشاكل',
    'new_ticket' => 'إرسال مشكلة جديدة',
    'ticket_detail' => 'تفاصيل المشكلة',

    // Form
    'reason_label' => 'سبب المشكلة',
    'reason_placeholder' => 'اختر سبب المشكلة',
    'description_label' => 'وصف المشكلة',
    'description_placeholder' => 'اشرح المشكلة التي تواجهها بالتفصيل...',
    'image_label' => 'صورة توضيحية (اختياري)',
    'image_hint' => 'يمكنك إرفاق صورة توضح المشكلة (JPG, PNG, WEBP - حد أقصى 5 ميجا)',
    'submit' => 'إرسال',
    'reply_placeholder' => 'اكتب ردك هنا...',
    'send_reply' => 'إرسال الرد',
    'sending' => 'جار الإرسال...',

    // List
    'no_tickets' => 'لا توجد مشاكل مرسلة',
    'no_tickets_description' => 'لم ترسل أي مشكلة حتى الآن. إذا واجهت أي مشكلة، يمكنك إرسالها من هنا.',
    'replies_count' => ':count رد',
    'created_at' => 'تم الإرسال :date',
    'closed_at' => 'تم الإغلاق :date',
    'closed_by' => 'أغلقت بواسطة :name',

    // Detail
    'ticket_info' => 'معلومات المشكلة',
    'conversation' => 'المحادثة',
    'no_replies' => 'لا توجد ردود بعد',
    'no_replies_description' => 'سيتم الرد على مشكلتك في أقرب وقت ممكن.',
    'ticket_closed_message' => 'تم إغلاق هذه المشكلة.',
    'admin_badge' => 'إدارة',

    // Success messages
    'ticket_created' => 'تم إرسال المشكلة بنجاح. سيتم الرد عليك في أقرب وقت.',
    'reply_sent' => 'تم إرسال الرد بنجاح.',
    'ticket_closed' => 'تم إغلاق المشكلة بنجاح.',
    'settings_updated' => 'تم تحديث الإعدادات بنجاح.',

    // Contact form (profile page)
    'contact_form_title' => 'هل تواجه مشكلة؟',
    'contact_form_default_message' => 'نحن في إتقان نسعى دائماً لتقديم أفضل تجربة تعليمية لأهل القرآن. إذا واجهت أي مشكلة تقنية أو لديك أي استفسار، لا تتردد في التواصل مع الإدارة وسنقوم بالرد عليك في أسرع وقت ممكن.',
    'contact_form_button' => 'أرسل مشكلتك',

    // Supervisor
    'supervisor' => [
        'page_title' => 'تقارير المشاكل',
        'page_description' => 'عرض وإدارة المشاكل المرسلة من المعلمين والطلاب',
        'all_tickets' => 'الكل',
        'open_tickets' => 'المفتوحة',
        'closed_tickets' => 'المغلقة',
        'filter_status' => 'الحالة',
        'filter_reason' => 'السبب',
        'search_placeholder' => 'بحث في المشاكل...',
        'reporter' => 'المُبلّغ',
        'role' => 'الدور',
        'reason' => 'السبب',
        'status' => 'الحالة',
        'date' => 'التاريخ',
        'replies' => 'الردود',
        'close_ticket' => 'إغلاق المشكلة',
        'close_confirm' => 'هل أنت متأكد من إغلاق هذه المشكلة؟',
        'replied_by' => 'رد بواسطة',
        'no_tickets' => 'لا توجد مشاكل',
        'no_tickets_description' => 'لم يتم إرسال أي مشكلة حتى الآن.',
        'back_to_all' => 'العودة إلى كل المشاكل',
        'related_actions' => 'إجراءات ذات صلة',
        'view_student_profile' => 'عرض ملف الطالب',
        'view_teacher_profile' => 'عرض ملف المعلم',
        'view_student_subscriptions' => 'عرض اشتراكات الطالب',
        'view_teacher_subscriptions' => 'عرض اشتراكات المعلم',
        'view_student_payments' => 'عرض مدفوعات الطالب',
        'view_teacher_earnings' => 'عرض أرباح المعلم',
        'view_teacher_sessions' => 'عرض جلسات المعلم',
        'view_teacher_calendar' => 'عرض تقويم المعلم',
        'view_user_tickets' => 'كل البلاغات من :name',
        'filtered_by_user' => 'تم الفلترة حسب: :name',
        'clear_filter' => 'إزالة الفلتر',

        // Settings
        'settings_title' => 'إعدادات نموذج التواصل',
        'settings_description' => 'التحكم في ظهور نموذج إرسال المشاكل في الصفحة الرئيسية للطلاب والمعلمين',
        'form_enabled' => 'إظهار نموذج التواصل في الصفحة الرئيسية',
        'message_ar_label' => 'الرسالة بالعربية',
        'message_en_label' => 'الرسالة بالإنجليزية',
        'save_settings' => 'حفظ الإعدادات',
    ],

    // Validation
    'validation' => [
        'reason_required' => 'يرجى اختيار سبب المشكلة.',
        'description_required' => 'يرجى كتابة وصف للمشكلة.',
        'description_min' => 'وصف المشكلة يجب أن يكون 10 أحرف على الأقل.',
        'description_max' => 'وصف المشكلة يجب ألا يتجاوز 2000 حرف.',
        'image_invalid' => 'الملف المرفق يجب أن يكون صورة.',
        'image_max' => 'حجم الصورة يجب ألا يتجاوز 5 ميجا.',
        'reply_required' => 'يرجى كتابة الرد.',
        'reply_min' => 'الرد يجب أن يكون حرفين على الأقل.',
        'reply_max' => 'الرد يجب ألا يتجاوز 2000 حرف.',
    ],

    // Notifications
    'notifications' => [
        'new_ticket_title' => 'مشكلة جديدة',
        'new_ticket_message' => 'أرسل :name مشكلة جديدة: :reason',
        'reply_title' => 'رد جديد على مشكلتك',
        'reply_message' => 'رد :name على مشكلتك',
    ],
];
