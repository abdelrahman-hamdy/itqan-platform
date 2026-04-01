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
    'no_teachers_available' => 'لا يوجد معلمون متاحون',
    'search_teacher_placeholder' => 'ابحث عن معلم...',
    'amount_label' => 'المبلغ',
    'discount_label' => 'الخصم',
    'package_price_label' => 'سعر الباقة',
    'final_price_label' => 'السعر النهائي',
    'paid_externally_label' => 'هل تم الدفع خارج المنصة؟',
    'yes_paid' => 'نعم، تم الدفع',
    'not_paid_yet' => 'لا، لم يتم الدفع بعد',
    'sessions_per_month' => 'جلسة/شهر',
    'payment_method_label' => 'طريقة الدفع',
    'payment_method_manual' => 'يدوي',
    'payment_method_bank' => 'تحويل بنكي',
    'payment_method_cash' => 'نقدي',
    'payment_method_mada' => 'مدى',
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
    'select_circle' => 'اختر الحلقة',
    'spots_available' => 'مقعد متاح',
    'payment_source_label' => 'مصدر الدفع',
    'paid_outside' => 'دفع خارج المنصة',
    'paid_outside_desc' => 'الطالب دفع نقداً أو تحويل بنكي',
    'paid_inside' => 'دفع عبر المنصة',
    'paid_inside_desc' => 'الاشتراك معلق حتى يتم الدفع أونلاين',
    'pending_payment_notice' => 'سيتم إنشاء الاشتراك كمعلق. الطالب سيحتاج للدفع عبر بوابة الدفع لتفعيله.',
    'learning_goals_label' => 'أهداف التعلم (اختياري)',
    'learning_goals_placeholder' => 'مثال: حفظ جزء عم، تحسين التلاوة...',

    // Lifecycle error messages
    'errors' => [
        'cannot_cancel' => 'لا يمكن إلغاء الاشتراك في حالته الحالية.',
        'cannot_pause' => 'لا يمكن إيقاف الاشتراك مؤقتاً في حالته الحالية.',
        'cannot_resume' => 'لا يمكن استئناف الاشتراك في حالته الحالية.',
        'no_auto_renewal_support' => 'دورة الفوترة هذه لا تدعم التجديد التلقائي.',
        'certificate_already_issued' => 'تم إصدار الشهادة مسبقاً.',
        'certificate_not_eligible' => 'الاشتراك غير مؤهل للحصول على شهادة.',
        'invalid_package' => 'الباقة المحددة غير متاحة.',
        'cannot_cancel_paid' => 'لا يمكن إلغاء اشتراك مدفوع. سينتهي الاشتراك تلقائياً في تاريخ الانتهاء.',
        'cancel_reason_student' => 'إلغاء من قبل الطالب',
        'cannot_delete_package_with_subscriptions' => 'لا يمكن حذف الباقة لوجود اشتراكات مرتبطة بها. يمكنك تعطيلها بدلاً من ذلك.',
        'delete_subscription' => 'حذف الاشتراك',
        'delete_subscription_heading' => 'حذف الاشتراك نهائياً',
        'delete_subscription_warning' => 'سيتم حذف الاشتراك وجميع البيانات المرتبطة به (الجلسات، الحلقة، الدروس، المدفوعات، التقارير) بشكل نهائي. لا يمكن التراجع عن هذا الإجراء.',
        'delete_subscription_confirm' => 'نعم، حذف نهائياً',
        'delete_subscription_success' => 'تم حذف الاشتراك وجميع البيانات المرتبطة بنجاح.',
    ],

    // Type labels
    'types' => [
        'academic_private' => 'دروس أكاديمية خاصة',
        'academic_group' => 'دروس أكاديمية جماعية',
    ],

    'generic_error' => 'حدث خطأ أثناء المعالجة. يرجى المحاولة مرة أخرى.',

    // Pause action
    'pause_label' => 'إيقاف مؤقت',
    'pause_modal_heading' => 'إيقاف الاشتراك مؤقتاً',
    'pause_modal_description' => 'سيتم إيقاف الاشتراك مؤقتاً ويمكن استئنافه لاحقاً.',
    'pause_success' => 'تم إيقاف الاشتراك مؤقتاً',

    // Resume action
    'resume_label' => 'استئناف الاشتراك',
    'resume_modal_heading' => 'استئناف الاشتراك',
    'resume_modal_description' => 'سيتم استئناف الاشتراك وإعادة تفعيله',
    'resume_success' => 'تم استئناف الاشتراك',

    // Reactivate action
    'reactivate_label' => 'إعادة تفعيل الاشتراك',
    'reactivate_modal_heading' => 'إعادة تفعيل اشتراك ملغي',
    'reactivate_modal_description' => 'سيتم إعادة تفعيل الاشتراك الملغي وتأكيد الدفع. سيتم تحديث تواريخ البدء والانتهاء.',
    'reactivate_confirm_button' => 'نعم، إعادة التفعيل',
    'reactivate_success' => 'تم إعادة تفعيل الاشتراك',
    'reactivate_success_body' => 'تم إعادة تفعيل الاشتراك الملغي بنجاح.',

    // Extend grace period action
    'extend_grace_label' => 'تمديد فترة السماح',
    'extend_grace_modal_heading' => 'تمديد فترة السماح',
    'extend_grace_modal_description' => 'منح الطالب فترة سماح إضافية. تاريخ انتهاء الاشتراك الأصلي (:ends_at) لن يتغير.',
    'not_specified' => 'غير محدد',
    'grace_days_label' => 'عدد أيام فترة السماح',
    'day_suffix' => 'يوم',
    'grace_calculated_from' => 'سيتم حساب فترة السماح من ',
    'grace_current_ends' => 'نهاية فترة السماح الحالية: ',
    'subscription_ends_at_prefix' => 'تاريخ انتهاء الاشتراك: ',
    'additional_days' => 'عدد الأيام الإضافية',
    'extend_grace_success' => 'تم تمديد فترة السماح',
    'extend_grace_success_body' => 'تم منح فترة سماح :days يوم حتى :date',

    // Cancel action
    'cancel_label' => 'إلغاء الاشتراك',
    'cancel_modal_heading' => 'إلغاء الاشتراك',
    'cancel_modal_description' => 'سيتم إلغاء الاشتراك وإلغاء جميع الجلسات المجدولة القادمة.',
    'cancel_confirm_button' => 'نعم، إلغاء الاشتراك',
    'cancel_success' => 'تم إلغاء الاشتراك',
    'cancel_success_body' => 'تم إلغاء الاشتراك و :count جلسة مجدولة.',

    // Create circle action
    'create_circle_label' => 'إنشاء حلقة',
    'create_circle_modal_heading' => 'إنشاء حلقة فردية',
    'create_circle_modal_description' => 'سيتم إنشاء حلقة فردية وربطها بهذا الاشتراك.',
    'specialization_interpretation' => 'تفسير',
    'circle_name_label' => 'اسم الحلقة (اختياري)',
    'circle_name_placeholder' => 'يتم إنشاؤه تلقائياً إذا تُرك فارغاً',
    'circle_description_label' => 'وصف الحلقة (اختياري)',
    'learning_objectives_label' => 'أهداف التعلم (اختياري)',
    'learning_objectives_placeholder' => 'أضف هدفاً تعليمياً',
    'default_session_duration_label' => 'مدة الجلسة الافتراضية',
    'auto_activated_title' => 'تم تفعيل الاشتراك تلقائياً',
    'auto_activated_body' => 'تم تفعيل الاشتراك لأن الدفع مؤكد والحلقة تم إنشاؤها.',
    'create_circle_success' => 'تم إنشاء الحلقة',
    'create_circle_success_body' => 'تم إنشاء الحلقة الفردية: :code',

    // Cancel pending action
    'cancel_pending_label' => 'إلغاء الطلب المعلق',
    'cancel_pending_modal_heading' => 'إلغاء طلب الاشتراك المعلق',
    'cancel_pending_modal_description' => 'هل أنت متأكد من إلغاء طلب الاشتراك هذا؟ هذا الإجراء لا يمكن التراجع عنه.',
    'cancel_pending_confirm_button' => 'نعم، إلغاء الطلب',
    'cancel_pending_success' => 'تم إلغاء الطلب',
    'cancel_pending_success_body' => 'تم إلغاء طلب الاشتراك بنجاح.',

    // Bulk cancel pending action
    'bulk_cancel_pending_label' => 'إلغاء الطلبات المعلقة المحددة',
    'bulk_cancel_pending_modal_heading' => 'إلغاء طلبات الاشتراك المعلقة',
    'bulk_cancel_pending_modal_description' => 'سيتم إلغاء جميع طلبات الاشتراك المعلقة المحددة. هذا الإجراء لا يمكن التراجع عنه.',
    'bulk_cancel_pending_confirm_button' => 'نعم، إلغاء الطلبات',
    'bulk_cancel_pending_success' => 'تم إلغاء الطلبات',
    'bulk_cancel_pending_success_body' => 'تم إلغاء :count طلب اشتراك بنجاح.',

    // Filters
    'request_status_label' => 'حالة الطلب',
    'filter_all_pending' => 'جميع الطلبات المعلقة',
    'filter_expired_pending' => 'طلبات منتهية الصلاحية',
    'filter_valid_pending' => 'طلبات صالحة',
    'filter_expired_hours' => 'طلبات منتهية (> :hours ساعة)',
];
