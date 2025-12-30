<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Parent Pages Translation Lines (Arabic)
    |--------------------------------------------------------------------------
    |
    | Translation keys for parent-facing pages and interfaces
    |
    */

    // Children Management Page
    'children' => [
        'title' => 'إدارة الأبناء',
        'description' => 'عرض وإضافة الأبناء المرتبطين بحسابك',
        'add_new_title' => 'إضافة ابن جديد',
        'add_new_description' => 'أدخل كود الطالب للتحقق وإضافته إلى حسابك. سيتم التحقق تلقائياً من تطابق رقم هاتف ولي الأمر.',
        'student_code_label' => 'كود الطالب',
        'student_code_placeholder' => 'مثال: ST-01-123456789',
        'student_code_info' => 'يمكنك الحصول على كود الطالب من إدارة الأكاديمية',
        'add_student_button' => 'إضافة الطالب',
        'linked_children_title' => 'الأبناء المرتبطون بحسابك',
        'view_dashboard' => 'عرض',
        'view_dashboard_title' => 'عرض لوحة التحكم',
        'unlink' => 'إلغاء الربط',
        'unlink_title' => 'إلغاء الربط',
        'unlink_confirm_title' => 'إلغاء ربط الطالب',
        'unlink_confirm_message' => 'هل أنت متأكد من إلغاء ربط :name من حسابك؟ يمكنك إعادة ربطه لاحقاً باستخدام كود الطالب.',
        'unlink_button' => 'إلغاء الربط',
        'cancel_button' => 'رجوع',
        'no_children_title' => 'لا يوجد أبناء مرتبطون بحسابك',
        'no_children_description' => 'ابدأ بإضافة أول طالب باستخدام النموذج أعلاه',
        'need_code_tip' => 'تحتاج إلى كود الطالب من إدارة الأكاديمية',
        'loading' => 'جاري التحميل...',
        'error_selecting_child' => 'حدث خطأ أثناء تحديد الطالب. يرجى المحاولة مرة أخرى.',
        'errors_title' => 'يرجى تصحيح الأخطاء التالية:',
    ],

    // Profile Pages
    'profile' => [
        'title' => 'الملف الشخصي',
        'edit_title' => 'تعديل الملف الشخصي',
        'edit_description' => 'قم بتحديث معلوماتك الشخصية',
        'first_name' => 'الاسم الأول',
        'last_name' => 'اسم العائلة',
        'email' => 'البريد الإلكتروني',
        'parent_code' => 'رقم ولي الأمر',
        'phone' => 'رقم الهاتف',
        'secondary_phone' => 'رقم هاتف بديل',
        'occupation' => 'المهنة',
        'preferred_contact_method' => 'طريقة التواصل المفضلة',
        'address' => 'العنوان',
        'contact_methods' => [
            'phone' => 'هاتف',
            'email' => 'بريد إلكتروني',
            'sms' => 'رسالة نصية',
            'whatsapp' => 'واتساب',
        ],
        'contact_method_placeholder' => 'اختر طريقة التواصل',
        'children_registered_title' => 'الأبناء المسجلون',
        'children_info_notice' => 'لإضافة أو تعديل بيانات الأبناء، يرجى التواصل مع إدارة الأكاديمية.',
        'student_label' => 'طالب',
        'welcome' => 'مرحباً، :name! 👋',
        'welcome_simple' => 'مرحباً، :name',
        'tracking_child' => 'تتابع الآن بيانات :child',
        'tracking_all' => 'متابعة شاملة لتقدم جميع أبنائك في رحلة التعلم',
        'registered_children_title' => 'أبنائك المسجلون',
        'child_count_singular' => 'ابن',
        'child_count_plural' => 'أبناء',
    ],

    // Dashboard
    'dashboard' => [
        'upcoming_sessions_title' => 'الجلسات القادمة',
        'view_full_calendar' => 'عرض التقويم الكامل',
        'no_upcoming_sessions' => 'لا توجد جلسات قادمة',
        'no_sessions_description' => 'عندما يتم جدولة جلسات للأبناء ستظهر هنا',
        'session_status' => [
            'ready' => 'جاهزة للبدء',
            'live' => 'جارية الآن',
            'scheduled' => 'مجدولة',
            'pending' => 'قيد الانتظار',
            'ongoing' => 'جارية',
        ],
    ],

    // Reports
    'reports' => [
        'attendance_title' => 'تقرير الحضور',
        'progress_title' => 'تقارير الأبناء',
        'attendance_description' => 'متابعة سجل حضور أبنائك في جميع الجلسات',
        'progress_description' => 'متابعة تقدم أبنائك وحضورهم في جميع البرامج',
        'view_progress_report' => 'تقرير التقدم',

        // Attendance Stats
        'total_sessions' => 'إجمالي الجلسات',
        'sessions_recorded' => 'جلسة مسجلة',
        'present' => 'حضور',
        'sessions_attended' => 'جلسة حضرها الأبناء',
        'absent' => 'غياب',
        'absent_sessions' => 'جلسة غائبة',
        'attendance_rate' => 'نسبة الحضور',
        'attendance_rate_value' => 'معدل الحضور',
        'attendance_percentage' => 'نسبة الحضور',
        'late' => 'تأخر',

        // Program Breakdown
        'quran_attendance_title' => 'حضور جلسات القرآن',
        'academic_attendance_title' => 'حضور الجلسات الأكاديمية',

        // Per Child
        'per_child_title' => 'حضور كل طالب',
        'child_subscriptions_title' => 'تفاصيل اشتراكات الأبناء',
        'child_subscriptions_description' => 'عرض التقدم والأداء لكل اشتراك على حدة',
        'no_subscriptions_title' => 'لا توجد اشتراكات',
        'no_subscriptions_description' => 'لم يتم تسجيل أي اشتراكات لأبنائك حتى الآن',
        'no_child_subscriptions' => 'لا توجد اشتراكات لهذا الطالب',

        // Programs
        'quran_program' => 'برنامج القرآن الكريم',
        'academic_program' => 'البرنامج الأكاديمي',
        'interactive_courses' => 'الدورات التفاعلية',
        'subscription_count' => 'اشتراك',
        'course_count' => 'دورة',

        // Subscription Details
        'teacher_name' => 'المعلم',
        'started_on' => 'بدأ :date',
        'enrolled_on' => 'التحق :date',
        'sessions_count' => 'الجلسات',
        'lessons_count' => 'الحصص',
        'performance' => 'الأداء',
        'progress' => 'التقدم',
        'detailed_report' => 'التقرير التفصيلي',
        'no_report' => 'لا يوجد تقرير',
        'attended_sessions' => 'الجلسات الحضور',

        // Progress Report
        'progress' => [
            'page_header' => 'تقارير الأبناء',
            'page_description' => 'متابعة تقدم أبنائك وحضورهم في جميع البرامج',
        ],

        // Attendance Report
        'attendance' => [
            'page_header' => 'تقرير الحضور',
            'page_description' => 'متابعة سجل حضور أبنائك في جميع الجلسات',
        ],
    ],

    // Quizzes
    'quizzes' => [
        'title' => 'اختبارات الأبناء',
        'description' => 'عرض جميع الاختبارات المتاحة وسجل محاولات أبنائك',
        'quiz_count' => 'اختبار',
        'attempt_count' => 'محاولة',
        'available_tab' => 'المتاحة',
        'available_tab_count' => 'المتاحة (:count)',
        'history_tab' => 'السجل',
        'history_tab_count' => 'السجل (:count)',
        'questions_count' => 'سؤال',
        'duration_minutes' => 'دقيقة',
        'due_date' => 'الاستحقاق: :date',
        'status' => [
            'pending' => 'انتظار',
            'in_progress' => 'جاري',
            'completed' => 'مكتمل',
        ],
        'attempts_used' => 'المحاولات: :used/:max',
        'no_quizzes_title' => 'لا توجد اختبارات متاحة',
        'no_quizzes_description' => 'ستظهر الاختبارات هنا عند تخصيصها لأبنائك',
        'no_history_title' => 'لا توجد محاولات سابقة',
        'no_history_description' => 'ستظهر سجلات محاولات أبنائك للاختبارات هنا',

        // Table Headers
        'quiz_name' => 'الاختبار',
        'student_name' => 'الطالب',
        'score' => 'النتيجة',
        'date' => 'التاريخ',
        'status_label' => 'الحالة',
        'not_specified' => 'غير محدد',

        // Stats
        'completed_count' => 'مكتمل',
        'average_score' => 'متوسط',
        'passed_count' => 'ناجح',
    ],

    // Certificates
    'certificates' => [
        'title' => 'عرض الشهادة',
        'back_to_certificates' => 'العودة إلى الشهادات',
        'certificate_types' => [
            'quran' => 'شهادة قرآن',
            'academic' => 'شهادة أكاديمية',
            'course' => 'شهادة دورة',
            'quran_full' => 'شهادة قرآن كريم',
            'academic_full' => 'شهادة أكاديمية',
            'course_full' => 'شهادة دورة تعليمية',
        ],
        'awarded_to' => 'هذه الشهادة تمنح إلى',
        'issue_date' => 'تاريخ الإصدار',
        'issued_by' => 'صادرة من',
        'verification_code' => 'رمز التحقق',
        'academy_label' => 'الأكاديمية',
        'download_pdf' => 'تحميل الشهادة بصيغة PDF',
        'print' => 'طباعة',

        // Details Section
        'certificate_info_title' => 'معلومات الشهادة',
        'student_label' => 'الطالب',
        'issue_date_label' => 'تاريخ الإصدار',
        'issued_by_label' => 'صادرة من',
        'certificate_type_label' => 'نوع الشهادة',
        'verification_code_label' => 'رمز التحقق',

        // Actions
        'quick_actions' => 'إجراءات سريعة',
        'download_pdf_action' => 'تحميل PDF',
        'print_action' => 'طباعة',

        // Related Links
        'related_links' => 'روابط ذات صلة',
        'all_certificates' => 'جميع الشهادات',
        'homepage' => 'الصفحة الرئيسية',
    ],

    // Subscriptions
    'subscriptions' => [
        'title' => 'تفاصيل الاشتراك',
        'back_to_subscriptions' => 'العودة إلى الاشتراكات',

        // Types
        'quran_subscription' => 'اشتراك قرآن',
        'academic_subscription' => 'اشتراك أكاديمي',
        'course_subscription' => 'اشتراك دورة',
        'course_subscription_short' => 'دورة',
        'individual' => 'اشتراك فردي',
        'group' => 'حلقة جماعية',
        'group_circle' => 'حلقة جماعية',
        'educational_course' => 'دورة تعليمية',
        'level' => 'مستوى',

        // Status
        'status' => [
            'active' => 'نشط',
            'expired' => 'منتهي',
            'pending' => 'قيد الانتظار',
        ],

        // Details
        'subscription_details' => 'تفاصيل الاشتراك',
        'student' => 'الطالب',
        'teacher' => 'المعلم',
        'start_date' => 'تاريخ البدء',
        'end_date' => 'تاريخ الانتهاء',

        // Stats & Progress
        'stats_and_progress' => 'الإحصائيات والتقدم',
        'total_sessions' => 'إجمالي الجلسات',
        'remaining_sessions' => 'الجلسات المتبقية',
        'completed_sessions' => 'الجلسات المكتملة',
        'completion_percentage' => 'نسبة الإنجاز',
        'completed_lessons' => 'الحصص المكتملة',
        'total_hours' => 'إجمالي الساعات',
        'progress_percentage' => 'نسبة الإنجاز',

        // Recent Sessions
        'recent_sessions' => 'الجلسات الأخيرة',
        'session_status' => [
            'completed' => 'مكتملة',
            'scheduled' => 'مجدولة',
            'cancelled' => 'ملغاة',
        ],

        // Quick Actions
        'quick_actions' => 'إجراءات سريعة',
        'upcoming_sessions' => 'الجلسات القادمة',
        'session_history' => 'سجل الجلسات',
        'payment_history' => 'سجل المدفوعات',

        // Subscription Status
        'subscription_status' => 'حالة الاشتراك',
    ],

    // Payments
    'payments' => [
        'title' => 'تفاصيل الدفع',
        'back_to_payments' => 'العودة إلى المدفوعات',
        'invoice_number' => 'فاتورة رقم #:number',
        'subscription_payment' => 'دفع اشتراك',

        // Payment Details
        'payment_details' => 'تفاصيل الدفع',
        'total_amount' => 'المبلغ الإجمالي',
        'student' => 'الطالب',
        'payment_method' => 'طريقة الدفع',
        'payment_methods' => [
            'card' => 'بطاقة ائتمانية',
            'bank' => 'تحويل بنكي',
            'cash' => 'نقداً',
        ],
        'payment_date' => 'تاريخ الدفع',
        'transaction_id' => 'رقم المعاملة',
        'reference_number' => 'الرقم المرجعي',

        // Related Subscription
        'related_subscription' => 'الاشتراك المرتبط',
        'quran_subscription' => 'اشتراك قرآن',
        'academic_subscription' => 'اشتراك أكاديمي',
        'course_subscription' => 'دورة',
        'individual' => 'فردي',
        'group_circle' => 'حلقة جماعية',
        'educational_course' => 'دورة تعليمية',

        // Notes & Status
        'notes_title' => 'ملاحظات',
        'failure_reason' => 'سبب فشل الدفع',
        'refund_reason' => 'سبب الاسترداد',
        'refund_date' => 'تاريخ الاسترداد: :date',

        // Actions
        'quick_actions' => 'إجراءات سريعة',
        'download_receipt' => 'تحميل الإيصال',

        // Timeline
        'timeline_title' => 'السجل الزمني',
        'payment_created' => 'تم إنشاء الدفع',
        'payment_completed' => 'تم الدفع بنجاح',
        'payment_failed' => 'فشل الدفع',
        'payment_refunded' => 'تم الاسترداد',

        // Related Links
        'related_links' => 'روابط ذات صلة',
        'all_payments' => 'جميع المدفوعات',
        'subscriptions' => 'الاشتراكات',
    ],

    // Sessions
    'sessions' => [
        'title' => 'تفاصيل الجلسة',
        'back' => 'العودة',

        // Session Types
        'quran_session' => 'جلسة قرآن',
        'quran_session_type' => 'جلسة قرآن - :type',
        'academic_lesson' => 'حصة دراسية',
        'academic_lesson_subject' => 'حصة دراسية - :subject',
        'individual' => 'فردي',
        'group' => 'حلقة جماعية',
        'group_circle' => 'حلقة جماعية',
        'subject' => 'مادة',
        'level' => 'مستوى',

        // Status
        'status' => [
            'scheduled' => 'مجدولة',
            'ongoing' => 'جارية',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
        ],

        // Session Information
        'session_info' => 'معلومات الجلسة',
        'date_time' => 'التاريخ والوقت',
        'duration' => 'المدة',
        'duration_minutes' => 'دقيقة',
        'actual_duration' => 'المدة الفعلية: :minutes دقيقة',
        'teacher' => 'المعلم',
        'student' => 'الطالب',

        // Quran Details
        'quran_details_title' => 'تفاصيل الحفظ والمراجعة',
        'new_memorization' => 'الحفظ الجديد',
        'review' => 'المراجعة',
        'page_from_to' => 'من صفحة :from إلى :to',
        'tajweed_score' => 'تقييم التجويد',
        'memorization_quality' => 'جودة الحفظ',

        // Academic Details
        'lesson_content_title' => 'محتوى الحصة',
        'lesson_topic' => 'موضوع الدرس',
        'learning_outcomes' => 'نواتج التعلم',
        'homework_title' => 'الواجب المنزلي',
        'download_attachment' => 'تحميل الملف المرفق',

        // Teacher Notes
        'teacher_notes_title' => 'ملاحظات المعلم',
        'cancellation_reason' => 'سبب الإلغاء',

        // Attendance
        'attendance_status_title' => 'حالة الحضور',
        'attendance_status' => [
            'attended' => 'حاضر',
            'absent' => 'غائب',
            'left_early' => 'غادر مبكراً',
            'late' => 'متأخر',
        ],
        'entry_time' => 'وقت الدخول',
        'exit_time' => 'وقت الخروج',
        'attendance_duration' => 'مدة الحضور',

        // Quick Stats
        'quick_stats_title' => 'إحصائيات سريعة',
        'total_sessions_count' => 'الجلسات الكلية',
        'completed_sessions_count' => 'الجلسات المكتملة',
        'attendance_rate' => 'نسبة الحضور',

        // Related Links
        'related_links' => 'روابط ذات صلة',
        'upcoming_sessions' => 'الجلسات القادمة',
        'session_history' => 'سجل الجلسات',

        // Show Page Specific
        'show' => [
            'page_title' => 'تفاصيل الجلسة',
        ],
    ],

    // Quick Stats (Parent Dashboard)
    'quick_stats' => [
        'selected_child' => 'الابن المحدد',
        'student' => 'طالب',
        'children_count' => 'عدد الأبناء',
        'viewing_all_children' => 'عرض بيانات جميع الأبناء',
        'active_subscriptions' => 'الاشتراكات النشطة',
        'active_subscriptions_label' => 'اشتراك فعال',
        'active_subscriptions_label_plural' => 'اشتراكات فعالة',
        'no_active_subscriptions' => 'لا توجد اشتراكات نشطة',
        'upcoming_sessions' => 'الجلسات القادمة',
        'scheduled_session' => 'جلسة مجدولة',
        'scheduled_sessions' => 'جلسات مجدولة',
        'no_upcoming_sessions' => 'لا توجد جلسات قادمة',
        'attendance_rate' => 'معدل الحضور',
        'rate_excellent' => 'ممتاز!',
        'rate_good' => 'جيد',
        'rate_needs_improvement' => 'يحتاج تحسين',
        'certificates_label' => 'شهادة',
        'payments_label' => 'دفعة مكتملة',
        'quran_subscription' => 'اشتراك قرآن',
        'academic_subscription' => 'اشتراك أكاديمي',
    ],

    // Sidebar Navigation
    'sidebar' => [
        'role' => 'ولي أمر',
        'navigation_label' => 'قائمة التنقل',
        'profile_section' => 'الملف الشخصي',
        'home' => 'الصفحة الرئيسية',
        'edit_profile' => 'تعديل الملف الشخصي',
        'manage_children' => 'إدارة الأبناء',
        'learning_progress' => 'التقدم الدراسي',
        'calendar_sessions' => 'التقويم والجلسات',
        'homework' => 'الواجبات',
        'quizzes' => 'الاختبارات',
        'reports' => 'التقارير',
        'certificates' => 'الشهادات',
        'subscriptions_payments' => 'الاشتراكات والمدفوعات',
        'subscriptions' => 'الاشتراكات',
        'payment_history' => 'سجل المدفوعات',
    ],

    // Common Labels
    'common' => [
        'required' => '*',
        'no_data' => '-',
        'loading' => 'جاري التحميل...',
        'success' => 'تم بنجاح',
        'error' => 'حدث خطأ',
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'view' => 'عرض',
        'download' => 'تحميل',
        'print' => 'طباعة',
        'search' => 'بحث',
        'filter' => 'تصفية',
        'reset' => 'إعادة تعيين',
        'apply' => 'تطبيق',
        'close' => 'إغلاق',
        'back' => 'رجوع',
        'next' => 'التالي',
        'previous' => 'السابق',
        'confirm' => 'تأكيد',
        'yes' => 'نعم',
        'no' => 'لا',
        'level' => 'مستوى',
        'subject' => 'مادة',
        'course' => 'دورة',
    ],
];
