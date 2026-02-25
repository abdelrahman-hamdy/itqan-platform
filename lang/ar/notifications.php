<?php

return [
    'categories' => [
        'session' => 'الجلسات',
        'attendance' => 'الحضور',
        'homework' => 'الواجبات',
        'payment' => 'المدفوعات',
        'meeting' => 'الاجتماعات',
        'progress' => 'التقدم',
        'system' => 'النظام',
        'review' => 'التقييمات',
        'trial' => 'الجلسات التجريبية',
        'alert' => 'تنبيهات هامة',
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
        'homework_submitted_teacher' => [
            'title' => 'تم تسليم واجب جديد',
            'message' => 'قام الطالب :student_name بتسليم واجب الجلسة :session_title',
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
        'grace_period_expiring' => [
            'title' => 'فترة السماح ستنتهي قريباً',
            'message' => 'فترة السماح لاشتراكك في :subscription_name ستنتهي خلال :days_left يوم. يرجى تجديد الاشتراك قبل :grace_end_date لتجنب تعليق الخدمة.',
        ],
        'subscription_expired' => [
            'title' => 'انتهى الاشتراك',
            'message' => 'انتهى اشتراكك في :subscription_name',
        ],
        'subscription_activated' => [
            'title' => 'تم تفعيل الاشتراك',
            'message' => 'تم تفعيل اشتراكك في :subscription_name بنجاح',
        ],
        'subscription_renewed' => [
            'title' => 'تم تجديد الاشتراك',
            'message' => 'تم تجديد اشتراكك في :subscription_name بنجاح',
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
        'quiz_completed_teacher' => [
            'title' => 'تم إكمال اختبار',
            'message' => 'أكمل الطالب :student_name اختبار :quiz_title بدرجة :score من :passing_score',
        ],
        'quiz_passed' => [
            'title' => 'نجحت في الاختبار!',
            'message' => 'مبروك! لقد نجحت في اختبار :quiz_title بدرجة :score من :passing_score',
        ],
        'quiz_failed' => [
            'title' => 'لم تنجح في الاختبار',
            'message' => 'لم تحصل على الدرجة المطلوبة في اختبار :quiz_title. درجتك: :score من :passing_score',
        ],
        'quiz_deadline_24h' => [
            'title' => 'تذكير: موعد الاختبار غداً',
            'message' => 'ينتهي موعد اختبار ":quiz_title" خلال 24 ساعة. أكمل الاختبار قبل فوات الأوان!',
        ],
        'quiz_deadline_1h' => [
            'title' => 'تنبيه عاجل: موعد الاختبار خلال ساعة!',
            'message' => 'ينتهي موعد اختبار ":quiz_title" خلال ساعة واحدة فقط! أكمل الاختبار الآن.',
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

        // Trial Session Notifications
        'trial_request_received' => [
            'title' => 'طلب جلسة تجريبية جديد',
            'message' => 'لديك طلب جلسة تجريبية جديد من :student_name',
        ],
        'trial_request_approved' => [
            'title' => 'تمت الموافقة على طلبك',
            'message' => 'تمت الموافقة على طلب الجلسة التجريبية مع المعلم :teacher_name',
        ],
        'trial_session_scheduled' => [
            'title' => 'تم جدولة الجلسة التجريبية',
            'message' => 'تم جدولة جلستك التجريبية مع :teacher_name في :scheduled_time',
        ],
        'trial_session_completed' => [
            'title' => 'اكتملت الجلسة التجريبية',
            'message' => 'اكتملت جلستك التجريبية مع :teacher_name - يمكنك الآن الاشتراك',
        ],
        'trial_session_reminder' => [
            'title' => 'تذكير بالجلسة التجريبية',
            'message' => 'جلستك التجريبية مع :teacher_name ستبدأ بعد ساعة',
        ],

        // Trial Session Notifications (role-specific)
        'trial_session_completed_student' => [
            'title' => 'اكتملت الجلسة التجريبية',
            'message' => 'اكتملت جلستك التجريبية مع :teacher_name - يمكنك الآن الاشتراك',
        ],
        'trial_session_completed_teacher' => [
            'title' => 'اكتملت الجلسة التجريبية',
            'message' => 'اكتملت الجلسة التجريبية مع الطالب :student_name',
        ],
        'trial_session_reminder_student' => [
            'title' => 'تذكير بالجلسة التجريبية',
            'message' => 'جلستك التجريبية مع :teacher_name ستبدأ بعد ساعة',
        ],
        'trial_session_reminder_teacher' => [
            'title' => 'تذكير بالجلسة التجريبية',
            'message' => 'لديك جلسة تجريبية مع الطالب :student_name ستبدأ بعد ساعة',
        ],
        'trial_session_reminder_parent' => [
            'title' => 'تذكير بجلسة تجريبية',
            'message' => 'الجلسة التجريبية لـ :student_name مع :teacher_name ستبدأ بعد ساعة',
        ],
        'trial_session_cancelled' => [
            'title' => 'تم إلغاء الجلسة التجريبية',
            'message' => 'تم إلغاء الجلسة التجريبية مع :teacher_name',
        ],

        // Session Notifications (role-specific for parents)
        'session_reminder_parent' => [
            'title' => 'تذكير بجلسة',
            'message' => 'جلسة :student_name (:session_title) ستبدأ بعد :minutes دقيقة',
        ],
        'session_started_parent' => [
            'title' => 'بدأت الجلسة',
            'message' => 'بدأت جلسة :student_name (:session_title)',
        ],
        'session_completed_parent' => [
            'title' => 'اكتملت الجلسة',
            'message' => 'اكتملت جلسة :student_name (:session_title)',
        ],

        // Admin-Specific Notifications
        'new_student_enrolled' => [
            'title' => 'طالب جديد مسجّل',
            'message' => 'تم تسجيل طالب جديد: :student_name',
        ],
        'new_trial_request_admin' => [
            'title' => 'طلب جلسة تجريبية جديد',
            'message' => 'طلب جلسة تجريبية جديد من :student_name مع المعلم :teacher_name',
        ],
        'new_payment_received' => [
            'title' => 'دفعة جديدة مستلمة',
            'message' => 'تم استلام دفعة بمبلغ :amount :currency من :student_name',
        ],
        'teacher_session_cancelled' => [
            'title' => 'تم إلغاء جلسة من قبل المعلم',
            'message' => 'تم إلغاء جلسة :session_type رقم #:session_id - السبب: :cancellation_reason',
        ],
        'subscription_renewal_failed_batch' => [
            'title' => 'فشل تجديد اشتراكات',
            'message' => 'فشل تجديد :count اشتراك - يرجى مراجعة الاشتراكات المتأثرة',
        ],
        'new_student_subscription_teacher' => [
            'title' => 'اشتراك طالب جديد',
            'message' => 'اشترك الطالب :student_name في حلقة فردية (:total_sessions جلسة) - يرجى جدولة الحصص',
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

    // CQ-001: Default strings for notification data (replaces hardcoded Arabic)
    'quran_session_default' => 'جلسة قرآنية',
    'academic_session_default' => 'جلسة أكاديمية',
    'teacher_default' => 'المعلم',
    'quran_homework' => 'واجب قرآني',
    'new_homework' => 'واجب جديد',
];
