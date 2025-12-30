<?php

/**
 * Arabic translations for Enum labels
 *
 * This file contains translations for all enum labels used in the application.
 * Enums should use __('enums.enum_name.case_value') for their labels.
 *
 * @see App\Enums
 */

return [
    // Session Status
    'session_status' => [
        'unscheduled' => 'غير مجدولة',
        'scheduled' => 'مجدولة',
        'ready' => 'جاهزة للبدء',
        'ongoing' => 'جارية الآن',
        'completed' => 'مكتملة',
        'cancelled' => 'ملغية',
        'absent' => 'غياب الطالب',
    ],

    // Attendance Status
    'attendance_status' => [
        'attended' => 'حاضر',
        'late' => 'متأخر',
        'left' => 'غادر مبكراً',
        'absent' => 'غائب',
    ],

    // Payment Status
    'payment_status' => [
        'pending' => 'قيد الانتظار',
        'processing' => 'جاري المعالجة',
        'completed' => 'مكتمل',
        'failed' => 'فشل',
        'cancelled' => 'ملغي',
        'refunded' => 'مسترد',
        'partially_refunded' => 'مسترد جزئياً',
    ],

    // Subscription Status
    'subscription_status' => [
        'pending' => 'في انتظار الدفع',
        'active' => 'نشط',
        'paused' => 'موقوف مؤقتاً',
        'expired' => 'منتهي',
        'cancelled' => 'ملغي',
        'completed' => 'مكتمل',
        'refunded' => 'مسترد',
    ],

    // Session Duration
    'session_duration' => [
        'thirty_minutes' => '30 دقيقة',
        'forty_five_minutes' => '45 دقيقة',
        'sixty_minutes' => 'ساعة واحدة',
    ],

    // Difficulty Level
    'difficulty_level' => [
        'beginner' => 'مبتدئ',
        'intermediate' => 'متوسط',
        'advanced' => 'متقدم',
    ],

    // Relationship Type (Parent to Child)
    'relationship_type' => [
        'father' => 'أب',
        'mother' => 'أم',
        'other' => 'أخرى',
    ],

    // Homework Submission Status
    'homework_submission_status' => [
        'not_started' => 'لم يتم البدء',
        'draft' => 'مسودة',
        'submitted' => 'تم التسليم',
        'late' => 'متأخر',
        'graded' => 'تم التقييم',
        'returned' => 'مُعاد للمراجعة',
        'resubmitted' => 'أُعيد تسليمه',
    ],

    // Homework Status
    'homework_status' => [
        'draft' => 'مسودة',
        'published' => 'منشور',
        'in_progress' => 'قيد التقدم',
        'archived' => 'مؤرشف',
    ],

    // Billing Cycle
    'billing_cycle' => [
        'monthly' => 'شهري',
        'quarterly' => 'ربع سنوي',
        'yearly' => 'سنوي',
        'lifetime' => 'مدى الحياة',
    ],

    // Certificate Type
    'certificate_type' => [
        'recorded_course' => 'دورة مسجلة',
        'interactive_course' => 'دورة تفاعلية',
        'quran_subscription' => 'حلقة قرآن',
        'academic_subscription' => 'حصص أكاديمية',
    ],

    // Certificate Template Style
    'certificate_template_style' => [
        'template_1' => 'القالب 1',
        'template_2' => 'القالب 2',
        'template_3' => 'القالب 3',
        'template_4' => 'القالب 4',
        'template_5' => 'القالب 5',
        'template_6' => 'القالب 6',
        'template_7' => 'القالب 7',
        'template_8' => 'القالب 8',
    ],

    // Interactive Course Status
    'interactive_course_status' => [
        'draft' => 'مسودة',
        'published' => 'منشور',
        'active' => 'نشط',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
    ],

    // Recording Status
    'recording_status' => [
        'recording' => 'جاري التسجيل',
        'processing' => 'جاري المعالجة',
        'completed' => 'مكتمل',
        'failed' => 'فشل',
        'deleted' => 'محذوف',
    ],

    // Week Days
    'week_days' => [
        'sunday' => 'الأحد',
        'monday' => 'الاثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة',
        'saturday' => 'السبت',
    ],

    // Educational Qualification
    'educational_qualification' => [
        'diploma' => 'دبلوم',
        'bachelor' => 'بكالوريوس',
        'master' => 'ماجستير',
        'phd' => 'دكتوراه',
        'other' => 'أخرى',
    ],

    // Teaching Language
    'teaching_language' => [
        'arabic' => 'العربية',
        'english' => 'الإنجليزية',
        'french' => 'الفرنسية',
        'german' => 'الألمانية',
    ],

    // Approval Status
    'approval_status' => [
        'pending' => 'قيد المراجعة',
        'approved' => 'موافق عليه',
        'rejected' => 'مرفوض',
    ],

    // Meeting Event Type
    'meeting_event_type' => [
        'joined' => 'انضم',
        'left' => 'غادر',
    ],

    // Meeting Status
    'meeting_status' => [
        'not_created' => 'لم يُنشأ بعد',
        'ready' => 'جاهز',
        'active' => 'نشط',
        'ended' => 'انتهى',
        'cancelled' => 'ملغي',
        'expired' => 'منتهي الصلاحية',
    ],

    // Review Status
    'review_status' => [
        'pending' => 'قيد المراجعة',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
    ],

    // Business Request Status
    'business_request_status' => [
        'pending' => 'قيد الانتظار',
        'reviewed' => 'تمت المراجعة',
        'approved' => 'موافق عليه',
        'rejected' => 'مرفوض',
        'completed' => 'مكتمل',
    ],

    // Enrollment Status
    'enrollment_status' => [
        'pending' => 'قيد الانتظار',
        'enrolled' => 'مسجل',
        'active' => 'نشط',
        'completed' => 'مكتمل',
        'dropped' => 'منسحب',
        'suspended' => 'موقوف',
    ],

    // Lesson Status
    'lesson_status' => [
        'pending' => 'قيد الانتظار',
        'active' => 'نشط',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
    ],

    // Payout Status
    'payout_status' => [
        'pending' => 'قيد الانتظار',
        'approved' => 'موافق عليها',
        'paid' => 'مدفوعة',
        'rejected' => 'مرفوضة',
    ],

    // Session Request Status
    'session_request_status' => [
        'pending' => 'قيد الانتظار',
        'agreed' => 'تم الموافقة',
        'paid' => 'مدفوع',
        'scheduled' => 'مجدول',
        'expired' => 'منتهي الصلاحية',
        'cancelled' => 'ملغي',
    ],

    // Trial Request Status
    'trial_request_status' => [
        'pending' => 'قيد الانتظار',
        'approved' => 'موافق عليها',
        'rejected' => 'مرفوضة',
        'scheduled' => 'مجدولة',
        'completed' => 'مكتملة',
        'cancelled' => 'ملغاة',
        'no_show' => 'لم يحضر',
    ],

    // Subscription Payment Status
    'subscription_payment_status' => [
        'pending' => 'في انتظار الدفع',
        'paid' => 'مدفوع',
        'failed' => 'فشل الدفع',
        'refunded' => 'مسترد',
    ],

    // Gradient Palette
    'gradient_palette' => [
        'ocean_breeze' => 'نسيم المحيط',
        'sunset_glow' => 'وهج الغروب',
        'forest_mist' => 'ضباب الغابة',
        'purple_dream' => 'حلم بنفسجي',
        'warm_flame' => 'لهب دافئ',
    ],

    // Country
    'country' => [
        'SA' => 'السعودية',
        'AE' => 'الإمارات العربية المتحدة',
        'EG' => 'مصر',
        'QA' => 'قطر',
        'KW' => 'الكويت',
        'BH' => 'البحرين',
        'OM' => 'عمان',
        'JO' => 'الأردن',
        'LB' => 'لبنان',
        'IQ' => 'العراق',
        'SY' => 'سوريا',
        'YE' => 'اليمن',
        'PS' => 'فلسطين',
        'MA' => 'المغرب',
        'DZ' => 'الجزائر',
        'TN' => 'تونس',
        'LY' => 'ليبيا',
        'SD' => 'السودان',
        'SO' => 'الصومال',
        'DJ' => 'جيبوتي',
        'KM' => 'جزر القمر',
        'MR' => 'موريتانيا',
    ],

    // Currency
    'currency' => [
        'SAR' => 'ريال سعودي (SAR)',
        'AED' => 'درهم إماراتي (AED)',
        'EGP' => 'جنيه مصري (EGP)',
        'QAR' => 'ريال قطري (QAR)',
        'KWD' => 'دينار كويتي (KWD)',
        'BHD' => 'دينار بحريني (BHD)',
        'OMR' => 'ريال عماني (OMR)',
        'JOD' => 'دينار أردني (JOD)',
        'LBP' => 'ليرة لبنانية (LBP)',
        'IQD' => 'دينار عراقي (IQD)',
        'SYP' => 'ليرة سورية (SYP)',
        'YER' => 'ريال يمني (YER)',
        'ILS' => 'شيكل إسرائيلي (ILS)',
        'MAD' => 'درهم مغربي (MAD)',
        'DZD' => 'دينار جزائري (DZD)',
        'TND' => 'دينار تونسي (TND)',
        'LYD' => 'دينار ليبي (LYD)',
        'SDG' => 'جنيه سوداني (SDG)',
        'SOS' => 'شلن صومالي (SOS)',
        'DJF' => 'فرنك جيبوتي (DJF)',
        'KMF' => 'فرنك قُمري (KMF)',
        'MRU' => 'أوقية موريتانية (MRU)',
    ],

    // Notification Type (common ones)
    'notification_type' => [
        'session_reminder' => 'تذكير بالجلسة',
        'session_cancelled' => 'إلغاء الجلسة',
        'session_rescheduled' => 'إعادة جدولة الجلسة',
        'payment_received' => 'استلام الدفع',
        'payment_failed' => 'فشل الدفع',
        'homework_assigned' => 'تكليف واجب',
        'homework_graded' => 'تقييم الواجب',
        'subscription_expiring' => 'انتهاء الاشتراك قريباً',
        'subscription_renewed' => 'تجديد الاشتراك',
    ],

    // Notification Category
    'notification_category' => [
        'session' => 'الجلسات',
        'attendance' => 'الحضور',
        'homework' => 'الواجبات',
        'payment' => 'المدفوعات',
        'meeting' => 'الاجتماعات',
        'progress' => 'التقدم',
        'system' => 'النظام',
    ],

    // Payment Flow Type
    'payment_flow_type' => [
        'redirect' => 'إعادة توجيه',
        'iframe' => 'نموذج مضمن',
        'api_only' => 'API مباشر',
    ],

    // Payment Result Status
    'payment_result_status' => [
        'pending' => 'قيد الانتظار',
        'processing' => 'جارٍ المعالجة',
        'success' => 'ناجح',
        'failed' => 'فشل',
        'cancelled' => 'ملغي',
        'refunded' => 'مسترد',
        'partially_refunded' => 'مسترد جزئياً',
        'expired' => 'منتهي الصلاحية',
    ],

    // Payout Status
    'payout_status' => [
        'pending' => 'قيد الانتظار',
        'approved' => 'موافق عليها',
        'paid' => 'مدفوعة',
        'rejected' => 'مرفوضة',
    ],

    // Recording Status
    'recording_status' => [
        'recording' => 'جاري التسجيل',
        'processing' => 'جاري المعالجة',
        'completed' => 'مكتمل',
        'failed' => 'فشل',
        'deleted' => 'محذوف',
    ],

    // Relationship Type
    'relationship_type' => [
        'father' => 'أب',
        'mother' => 'أم',
        'other' => 'أخرى',
    ],

    // Session Request Status
    'session_request_status' => [
        'pending' => 'قيد الانتظار',
        'agreed' => 'تم الموافقة',
        'paid' => 'مدفوع',
        'scheduled' => 'مجدول',
        'expired' => 'منتهي الصلاحية',
        'cancelled' => 'ملغي',
    ],

    // Subscription Payment Status
    'subscription_payment_status' => [
        'pending' => 'في انتظار الدفع',
        'paid' => 'مدفوع',
        'failed' => 'فشل الدفع',
        'refunded' => 'مسترد',
    ],

    // Trial Request Status
    'trial_request_status' => [
        'pending' => 'قيد الانتظار',
        'approved' => 'موافق عليها',
        'rejected' => 'مرفوضة',
        'scheduled' => 'مجدولة',
        'completed' => 'مكتملة',
        'cancelled' => 'ملغاة',
        'no_show' => 'لم يحضر',
    ],

    // Tailwind Color
    'tailwind_color' => [
        'red' => 'أحمر',
        'orange' => 'برتقالي',
        'amber' => 'كهرماني',
        'yellow' => 'أصفر',
        'lime' => 'ليموني',
        'green' => 'أخضر',
        'emerald' => 'زمردي',
        'teal' => 'أزرق مخضر',
        'cyan' => 'سماوي',
        'sky' => 'سماوي فاتح',
        'blue' => 'أزرق',
        'indigo' => 'نيلي',
        'violet' => 'بنفسجي',
        'purple' => 'أرجواني',
        'fuchsia' => 'فوشي',
        'pink' => 'وردي',
        'rose' => 'وردي غامق',
    ],

    // Timezone
    'timezone' => [
        'Asia/Riyadh' => 'الرياض (GMT+3)',
        'Asia/Dubai' => 'دبي (GMT+4)',
        'Africa/Cairo' => 'القاهرة (GMT+2)',
        'Asia/Qatar' => 'قطر (GMT+3)',
        'Asia/Kuwait' => 'الكويت (GMT+3)',
        'Asia/Bahrain' => 'البحرين (GMT+3)',
        'Asia/Muscat' => 'مسقط (GMT+4)',
        'Asia/Amman' => 'عمّان (GMT+2)',
        'Asia/Beirut' => 'بيروت (GMT+2)',
        'Asia/Baghdad' => 'بغداد (GMT+3)',
        'Asia/Damascus' => 'دمشق (GMT+2)',
        'Asia/Aden' => 'عدن (GMT+3)',
        'Asia/Gaza' => 'غزة (GMT+2)',
        'Africa/Casablanca' => 'الدار البيضاء (GMT+1)',
        'Africa/Algiers' => 'الجزائر (GMT+1)',
        'Africa/Tunis' => 'تونس (GMT+1)',
        'Africa/Tripoli' => 'طرابلس (GMT+2)',
        'Africa/Khartoum' => 'الخرطوم (GMT+2)',
        'Africa/Mogadishu' => 'مقديشو (GMT+3)',
        'Africa/Djibouti' => 'جيبوتي (GMT+3)',
        'Indian/Comoro' => 'القُمر (GMT+3)',
        'Africa/Nouakchott' => 'نواكشوط (GMT+0)',
    ],

    // Circle Status
    'circle_status' => [
        'pending' => 'في انتظار البداية',
        'active' => 'نشط',
        'completed' => 'مكتمل',
        'suspended' => 'معلق',
        'cancelled' => 'ملغي',
    ],

    // User Type
    'user_type' => [
        'student' => 'طالب',
        'parent' => 'ولي أمر',
        'quran_teacher' => 'معلم قرآن',
        'academic_teacher' => 'معلم أكاديمي',
        'supervisor' => 'مشرف',
        'admin' => 'مدير الأكاديمية',
        'super_admin' => 'مدير النظام',
    ],

    // Payment Method
    'payment_method' => [
        'credit_card' => 'بطاقة ائتمان',
        'debit_card' => 'بطاقة خصم',
        'bank_transfer' => 'تحويل بنكي',
        'wallet' => 'محفظة إلكترونية',
        'cash' => 'نقداً',
        'mada' => 'مدى',
        'visa' => 'فيزا',
        'mastercard' => 'ماستركارد',
        'apple_pay' => 'Apple Pay',
        'stc_pay' => 'STC Pay',
        'urpay' => 'UrPay',
    ],

    // Quran Specialization
    'quran_specialization' => [
        'memorization' => 'الحفظ',
        'recitation' => 'التلاوة',
        'interpretation' => 'التفسير',
        'arabic_language' => 'اللغة العربية',
        'complete' => 'متكامل',
    ],

    // Memorization Level
    'memorization_level' => [
        'beginner' => 'مبتدئ',
        'elementary' => 'ابتدائي',
        'intermediate' => 'متوسط',
        'advanced' => 'متقدم',
        'expert' => 'خبير',
    ],

    // Age Group
    'age_group' => [
        'children' => 'أطفال',
        'youth' => 'شباب',
        'adults' => 'كبار',
        'all_ages' => 'كل الفئات',
    ],

    // Gender Type (for groups/circles)
    'gender_type' => [
        'male' => 'رجال',
        'female' => 'نساء',
        'mixed' => 'مختلط',
    ],

    // Gender (for individuals)
    'gender' => [
        'male' => 'ذكر',
        'female' => 'أنثى',
        'teacher_male' => 'معلم',
        'teacher_female' => 'معلمة',
    ],

    // Schedule Status
    'schedule_status' => [
        'active' => 'نشط',
        'paused' => 'موقوف مؤقتاً',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
    ],

    // Quran Learning Level
    'quran_learning_level' => [
        'beginner' => 'مبتدئ (لا أعرف القراءة)',
        'elementary' => 'أساسي (أقرأ ببطء)',
        'intermediate' => 'متوسط (أقرأ بطلاقة)',
        'advanced' => 'متقدم (أحفظ أجزاء من القرآن)',
        'expert' => 'متمكن (أحفظ أكثر من 10 أجزاء)',
        'hafiz' => 'حافظ (أحفظ القرآن كاملاً)',
    ],

    // Learning Goal
    'learning_goal' => [
        'reading' => 'تعلم القراءة الصحيحة',
        'tajweed' => 'تعلم أحكام التجويد',
        'memorization' => 'حفظ القرآن الكريم',
        'improvement' => 'تحسين الأداء والإتقان',
    ],

    // Time Slot
    'time_slot' => [
        'morning' => 'صباحاً (6:00 - 12:00)',
        'afternoon' => 'بعد الظهر (12:00 - 18:00)',
        'evening' => 'مساءً (18:00 - 22:00)',
    ],
];
