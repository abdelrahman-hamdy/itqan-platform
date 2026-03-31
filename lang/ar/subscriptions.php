<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscription Detail Messages (Arabic)
    |--------------------------------------------------------------------------
    */

    'course_types' => [
        'interactive' => 'دورة تفاعلية',
        'recorded' => 'دورة مسجلة',
        'training' => 'دورة تدريبية',
    ],

    'course' => [
        'one_time_purchase' => 'شراء لمرة واحدة',
        'access_expired' => 'انتهت صلاحية الوصول للدورة. يرجى تجديد الاشتراك للمتابعة.',
        'access_expires_in' => 'ستنتهي صلاحية الوصول للدورة بعد :days أيام.',
        'almost_done' => 'أنت على وشك إنهاء الدورة! واصل التقدم الرائع.',
        'progress_percent' => 'لقد أكملت :percent% من الدورة. استمر!',
        'started_course' => 'لقد بدأت الدورة. واصل الحضور لتحقيق أفضل النتائج.',
        'not_started' => 'لم تبدأ الدورة بعد. انتظر الجلسة القادمة للبدء.',
        'completed' => 'مبروك! لقد أكملت الدورة بنجاح.',
        'almost_done_watching' => 'أنت على وشك إنهاء الدورة! واصل التقدم الرائع.',
        'progress_watching' => 'لقد أكملت :percent% من الدورة. استمر!',
        'started_watching' => 'لقد بدأت الدورة. واصل المشاهدة لإكمال الدروس.',
        'start_now' => 'ابدأ الدورة الآن لتحقيق أهدافك التعليمية.',
    ],

    'type_quran' => 'اشتراك قرآن',
    'type_academic' => 'اشتراك أكاديمي',
    'type_course' => 'اشتراك دورة',

    // Payment confirmation
    'confirm_payment' => 'تأكيد الدفع',
    'confirm_subscription_payment' => 'تأكيد دفع الاشتراك',
    'confirm_payment_description' => 'سيتم تأكيد الدفع وتفعيل الاشتراك إذا كان معلقاً أو ملغياً.',
    'confirm_payment_grace_period' => 'الاشتراك في فترة سماح حتى :grace_end. تأكيد الدفع سيبدأ فترة اشتراك جديدة من تاريخ الانتهاء الأصلي (:ends_at).',
    'payment_reference_label' => 'مرجع الدفع (اختياري)',
    'payment_reference_placeholder' => 'رقم الإيصال أو مرجع التحويل',
    'payment_confirmed_title' => 'تم تأكيد الدفع',
    'payment_confirmed_and_activated' => 'تم تأكيد الدفع وتفعيل الاشتراك بنجاح.',
    'payment_confirmation_failed' => 'فشل تأكيد الدفع',
    'payment_confirmed_by' => 'تأكيد دفع بواسطة',
    'payment_reference' => 'المرجع',
    'admin_payment_reference' => 'مرجع الدفع الإداري',
    'manual_payment_created_by_admin' => 'دفعة يدوية تم إنشاؤها بواسطة الإدارة',
    'admin_confirmed_payment' => 'تم تأكيد الدفع بواسطة الإدارة',
    'admin_confirmed_with_reference' => 'تم تأكيد الدفع بواسطة الإدارة - المرجع: :reference',

    // Sessions exhausted
    'sessions_exhausted' => 'الجلسات مكتملة',
    'sessions_exhausted_message' => 'تم إكمال جميع الجلسات المتاحة، يمكنك التجديد للحصول على جلسات إضافية.',
    'grace_period_label' => 'فترة سماح',

    // Renewal
    'subscription_not_found' => 'الاشتراك غير موجود',
    'renewal_already_pending' => 'يوجد تجديد معلق بالفعل لهذا الاشتراك.',
    'cannot_renew' => 'لا يمكن تجديد هذا الاشتراك في حالته الحالية.',
    'cannot_resubscribe' => 'لا يمكن إعادة الاشتراك في حالته الحالية. يجب أن يكون الاشتراك ملغي أو منتهي.',
    'teacher_unavailable_select_new' => 'المعلم الأصلي لم يعد متاحاً. يرجى اختيار معلم جديد.',
    'renew_subscription' => 'تجديد الاشتراك',
    'resubscribe' => 'إعادة الاشتراك',
    'select_package' => 'اختر الباقة',
    'select_billing_cycle' => 'اختر دورة الفوترة',
    'renewal_success' => 'تم تجديد الاشتراك بنجاح.',
    'resubscribe_success' => 'تم إعادة الاشتراك بنجاح.',
    'activation_mode' => 'طريقة التفعيل',
    'activate_immediately' => 'تفعيل فوري',
    'create_as_pending' => 'إنشاء كمعلق (يتطلب دفع)',
    'sessions_carryover' => ':count جلسة متبقية سيتم ترحيلها',

    // Admin wizard
    'create_full_subscription' => 'إنشاء اشتراك كامل',
    'create_full_subscription_success' => 'تم إنشاء الاشتراك بنجاح مع جميع البيانات المرتبطة.',
    'package_not_found' => 'الباقة غير موجودة.',
    'wizard_step1_title' => 'نوع الاشتراك والطالب',
    'wizard_step2_title' => 'الباقة والتسعير',
    'wizard_step3_title' => 'معلومات الدفع',
    'wizard_step4_title' => 'التقدم المبدئي (اختياري)',
    'subscription_type_label' => 'نوع الاشتراك',
    'type_quran_individual' => 'قرآن - فردي',
    'type_quran_group' => 'قرآن - حلقة جماعية',
    'student_label' => 'الطالب',
    'search_student_placeholder' => 'ابحث بالاسم أو البريد الإلكتروني...',
    'teacher_label' => 'المعلم',
    'select_teacher' => 'اختر المعلم',
    'amount_label' => 'المبلغ',
    'discount_label' => 'الخصم',
    'sessions_per_month' => 'جلسة/شهر',
    'payment_method_label' => 'طريقة الدفع',
    'payment_method_manual' => 'يدوي',
    'payment_method_bank' => 'تحويل بنكي',
    'payment_method_cash' => 'نقدي',
    'payment_method_other' => 'أخرى',
    'payment_notes_label' => 'ملاحظات الدفع',
    'consumed_sessions_label' => 'الجلسات المستهلكة مسبقاً',
    'consumed_sessions_help' => 'عدد الجلسات التي تم استهلاكها خارج المنصة قبل إنشاء الاشتراك.',
    'memorization_level_label' => 'مستوى الحفظ',
    'level_beginner' => 'مبتدئ',
    'level_intermediate' => 'متوسط',
    'level_advanced' => 'متقدم',
    'specialization_label' => 'التخصص',
    'specialization_memorization' => 'حفظ',
    'specialization_recitation' => 'تلاوة',
    'specialization_tajweed' => 'تجويد',
    'specialization_complete' => 'شامل',
    'previous_step' => 'السابق',
    'next_step' => 'التالي',

    // Lifecycle error messages
    'errors' => [
        'cannot_cancel' => 'لا يمكن إلغاء الاشتراك في حالته الحالية.',
        'cannot_pause' => 'لا يمكن إيقاف الاشتراك مؤقتاً في حالته الحالية.',
        'cannot_resume' => 'لا يمكن استئناف الاشتراك في حالته الحالية.',
        'no_auto_renewal_support' => 'دورة الفوترة هذه لا تدعم التجديد التلقائي.',
        'certificate_already_issued' => 'تم إصدار الشهادة مسبقاً.',
        'certificate_not_eligible' => 'الاشتراك غير مؤهل للحصول على شهادة.',
    ],

    // Type labels
    'types' => [
        'academic_private' => 'دروس أكاديمية خاصة',
        'academic_group' => 'دروس أكاديمية جماعية',
    ],

    'generic_error' => 'حدث خطأ أثناء المعالجة. يرجى المحاولة مرة أخرى.',
];
