<?php

return [
    'categories' => [
        'session' => 'الجلسات',
        'attendance' => 'الحضور',
        'homework' => 'الواجبات',
        'payment' => 'المدفوعات',
        'meeting' => 'الاجتماعات',
        'progress' => 'التقدم',
        'chat' => 'المحادثات',
        'system' => 'النظام',
    ],

    'types' => [
        // Session Notifications
        'session_scheduled' => [
            'title' => 'تم جدولة جلسة جديدة',
            'message' => 'تم جدولة جلسة :session_title مع :teacher_name في :start_time',
        ],
        'session_reminder' => [
            'title' => 'تذكير بالجلسة',
            'message' => 'جلستك :session_title ستبدأ بعد :minutes دقيقة في :start_time',
        ],
        'session_started' => [
            'title' => 'بدأت الجلسة',
            'message' => 'جلسة :session_title قد بدأت الآن',
        ],
        'session_completed' => [
            'title' => 'اكتملت الجلسة',
            'message' => 'تم إكمال جلسة :session_title بنجاح',
        ],
        'session_cancelled' => [
            'title' => 'تم إلغاء الجلسة',
            'message' => 'تم إلغاء جلسة :session_title',
        ],
        'session_rescheduled' => [
            'title' => 'تم إعادة جدولة الجلسة',
            'message' => 'تم إعادة جدولة جلسة :session_title إلى :new_time',
        ],

        // Attendance Notifications
        'attendance_marked_present' => [
            'title' => 'تم تسجيل الحضور',
            'message' => 'تم تسجيل حضورك في جلسة :session_title بتاريخ :date',
        ],
        'attendance_marked_absent' => [
            'title' => 'تم تسجيل الغياب',
            'message' => 'تم تسجيل غيابك عن جلسة :session_title بتاريخ :date',
        ],
        'attendance_marked_late' => [
            'title' => 'تم تسجيل التأخر',
            'message' => 'تم تسجيل تأخرك عن جلسة :session_title بتاريخ :date',
        ],
        'attendance_report_ready' => [
            'title' => 'تقرير الحضور جاهز',
            'message' => 'تقرير الحضور الخاص بك للفترة :period جاهز الآن',
        ],

        // Homework Notifications
        'homework_assigned' => [
            'title' => 'واجب جديد',
            'message' => 'لديك واجب جديد من :teacher_name للجلسة :session_title - موعد التسليم :due_date',
        ],
        'homework_submitted' => [
            'title' => 'تم استلام الواجب',
            'message' => 'تم استلام واجبك للجلسة :session_title بنجاح',
        ],
        'homework_graded' => [
            'title' => 'تم تقييم الواجب',
            'message' => 'تم تقييم واجبك للجلسة :session_title - الدرجة: :grade',
        ],
        'homework_deadline_reminder' => [
            'title' => 'تذكير بموعد الواجب',
            'message' => 'موعد تسليم الواجب للجلسة :session_title بعد :hours ساعة',
        ],

        // Payment Notifications
        'payment_success' => [
            'title' => 'تمت عملية الدفع بنجاح',
            'message' => 'تم دفع :amount :currency بنجاح - :description',
        ],
        'payment_failed' => [
            'title' => 'فشلت عملية الدفع',
            'message' => 'فشلت عملية الدفع لمبلغ :amount :currency - يرجى المحاولة مرة أخرى',
        ],
        'subscription_expiring' => [
            'title' => 'اشتراكك سينتهي قريباً',
            'message' => 'اشتراكك في :subscription_name سينتهي بتاريخ :expiry_date',
        ],
        'subscription_expired' => [
            'title' => 'انتهى الاشتراك',
            'message' => 'انتهى اشتراكك في :subscription_name',
        ],
        'subscription_activated' => [
            'title' => 'تم تفعيل الاشتراك',
            'message' => 'تم تفعيل اشتراكك بنجاح',
        ],
        'subscription_renewed' => [
            'title' => 'تم تجديد الاشتراك',
            'message' => 'تم تجديد اشتراكك بنجاح',
        ],
        'invoice_generated' => [
            'title' => 'فاتورة جديدة',
            'message' => 'تم إصدار فاتورة جديدة بمبلغ :amount :currency',
        ],

        // Teacher Payout Notifications
        'payout_approved' => [
            'title' => 'تمت الموافقة على المستحقات',
            'message' => 'تمت الموافقة على مستحقاتك لشهر :month بمبلغ :amount :currency',
        ],
        'payout_rejected' => [
            'title' => 'تم رفض المستحقات',
            'message' => 'تم رفض مستحقاتك لشهر :month - السبب: :reason',
        ],
        'payout_paid' => [
            'title' => 'تم صرف المستحقات',
            'message' => 'تم صرف مستحقاتك لشهر :month بمبلغ :amount :currency - رقم العملية: :reference',
        ],

        // Meeting Notifications
        'meeting_room_ready' => [
            'title' => 'غرفة الاجتماع جاهزة',
            'message' => 'غرفة الاجتماع للجلسة :session_title جاهزة الآن',
        ],
        'meeting_participant_joined' => [
            'title' => 'انضم مشارك',
            'message' => 'انضم :participant_name إلى الاجتماع',
        ],
        'meeting_participant_left' => [
            'title' => 'غادر مشارك',
            'message' => 'غادر :participant_name الاجتماع',
        ],
        'meeting_recording_available' => [
            'title' => 'التسجيل متاح',
            'message' => 'تسجيل الجلسة :session_title متاح الآن للمشاهدة',
        ],
        'meeting_technical_issue' => [
            'title' => 'مشكلة تقنية',
            'message' => 'حدثت مشكلة تقنية في الاجتماع - :issue_description',
        ],

        // Academic Progress Notifications
        'progress_report_available' => [
            'title' => 'تقرير التقدم متاح',
            'message' => 'تقرير تقدمك لـ :period متاح الآن للمراجعة',
        ],
        'achievement_unlocked' => [
            'title' => 'إنجاز جديد!',
            'message' => 'مبروك! لقد حققت إنجاز: :achievement_name',
        ],
        'certificate_earned' => [
            'title' => 'شهادة جديدة',
            'message' => 'مبروك! لقد حصلت على شهادة من :teacher_name',
        ],
        'course_completed' => [
            'title' => 'اكتمل المقرر',
            'message' => 'مبروك! لقد أكملت مقرر :course_name بنجاح',
        ],

        // Quiz Notifications
        'quiz_assigned' => [
            'title' => 'اختبار جديد',
            'message' => 'تم تعيين اختبار جديد: :quiz_title',
        ],
        'quiz_completed' => [
            'title' => 'تم إكمال الاختبار',
            'message' => 'لقد أكملت اختبار :quiz_title',
        ],
        'quiz_passed' => [
            'title' => 'نجحت في الاختبار!',
            'message' => 'مبروك! لقد نجحت في اختبار :quiz_title بدرجة :score من :passing_score',
        ],
        'quiz_failed' => [
            'title' => 'لم تنجح في الاختبار',
            'message' => 'لم تحصل على الدرجة المطلوبة في اختبار :quiz_title. درجتك: :score من :passing_score',
        ],

        // Review Notifications
        'review_received' => [
            'title' => 'تقييم جديد',
            'message' => 'لقد حصلت على تقييم جديد من :student_name - :rating نجوم',
        ],
        'review_approved' => [
            'title' => 'تم قبول تقييمك',
            'message' => 'تم قبول تقييمك ونشره بنجاح',
        ],

        // Chat Notifications
        'chat_message_received' => [
            'title' => 'رسالة جديدة',
            'message' => 'لديك رسالة جديدة من :sender_name',
        ],
        'chat_mentioned' => [
            'title' => 'تمت الإشارة إليك',
            'message' => 'أشار إليك :sender_name في :chat_name',
        ],
        'chat_group_added' => [
            'title' => 'تمت إضافتك لمجموعة',
            'message' => 'تمت إضافتك إلى مجموعة :group_name',
        ],

        // System Notifications
        'account_verified' => [
            'title' => 'تم تفعيل الحساب',
            'message' => 'تم تفعيل حسابك بنجاح',
        ],
        'password_changed' => [
            'title' => 'تم تغيير كلمة المرور',
            'message' => 'تم تغيير كلمة المرور الخاصة بك بنجاح',
        ],
        'profile_updated' => [
            'title' => 'تم تحديث الملف الشخصي',
            'message' => 'تم تحديث معلومات ملفك الشخصي بنجاح',
        ],
        'system_maintenance' => [
            'title' => 'صيانة النظام',
            'message' => 'سيخضع النظام للصيانة في :maintenance_time',
        ],
    ],

    'actions' => [
        'view' => 'عرض',
        'mark_as_read' => 'تعليم كمقروء',
        'mark_all_as_read' => 'تعليم الكل كمقروء',
        'delete' => 'حذف',
        'settings' => 'إعدادات الإشعارات',
    ],

    'empty' => [
        'title' => 'لا توجد إشعارات',
        'message' => 'ليس لديك أي إشعارات في الوقت الحالي',
        'filtered_message' => 'لم يتم العثور على إشعارات تطابق المرشحات المحددة.',
    ],

    'page' => [
        'title' => 'الإشعارات',
        'page_title_suffix' => 'الإشعارات - ',
        'description' => 'تتبع جميع إشعاراتك وتحديثاتك',
        'breadcrumb' => [
            'home' => 'الرئيسية',
            'notifications' => 'الإشعارات',
        ],
        'filters' => [
            'category' => 'التصنيف',
            'all' => 'الكل',
            'unread_only' => 'غير المقروءة فقط',
        ],
        'view_all' => 'عرض كل الإشعارات',
        'loading' => 'جاري التحميل...',
    ],

    'preferences' => [
        'title' => 'تفضيلات الإشعارات',
        'email' => 'البريد الإلكتروني',
        'push' => 'الإشعارات الفورية',
        'sms' => 'الرسائل النصية',
    ],
];