<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payments Arabic Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for payment-related pages and messages.
    |
    */

    'title' => 'المدفوعات والفواتير',
    'my_payments' => 'مدفوعاتي',
    'payment_history' => 'سجل المدفوعات',
    'invoices' => 'الفواتير',
    'payment_details' => 'تفاصيل الدفع',
    'invoice_details' => 'تفاصيل الفاتورة',

    // Status
    'status' => 'الحالة',
    'pending' => 'قيد الانتظار',
    'processing' => 'جارٍ المعالجة',
    'completed' => 'مكتمل',
    'failed' => 'فشل',
    'cancelled' => 'ملغي',
    'refunded' => 'مسترد',
    'partially_refunded' => 'مسترد جزئياً',

    // Payment info
    'payment_code' => 'رقم الدفع',
    'payment_date' => 'تاريخ الدفع',
    'payment_method' => 'طريقة الدفع',
    'payment_gateway' => 'بوابة الدفع',
    'amount' => 'المبلغ',
    'fees' => 'الرسوم',
    'tax' => 'الضريبة',
    'discount' => 'الخصم',
    'net_amount' => 'المبلغ الصافي',
    'total' => 'المجموع',
    'currency' => 'العملة',
    'description' => 'الوصف',
    'receipt' => 'الإيصال',
    'receipt_number' => 'رقم الإيصال',

    // Subscription info
    'subscription' => 'الاشتراك',
    'subscription_type' => 'نوع الاشتراك',
    'quran_subscription' => 'اشتراك قرآن',
    'academic_subscription' => 'اشتراك أكاديمي',
    'course_subscription' => 'اشتراك كورس',

    // Payment methods
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

    // Actions
    'view_details' => 'عرض التفاصيل',
    'download_receipt' => 'تحميل الإيصال',
    'download_invoice' => 'تحميل الفاتورة',
    'print_receipt' => 'طباعة الإيصال',
    'request_refund' => 'طلب استرداد',
    'view_subscription' => 'عرض الاشتراك',

    // Filters
    'all_payments' => 'كل المدفوعات',
    'successful_payments' => 'المدفوعات الناجحة',
    'pending_payments' => 'المدفوعات المعلقة',
    'failed_payments' => 'المدفوعات الفاشلة',
    'filter_by_status' => 'تصفية حسب الحالة',
    'filter_by_date' => 'تصفية حسب التاريخ',
    'filter_by_method' => 'تصفية حسب الطريقة',
    'search_payments' => 'بحث في المدفوعات',

    // Summary
    'total_payments' => 'إجمالي المدفوعات',
    'total_spent' => 'إجمالي الإنفاق',
    'this_month' => 'هذا الشهر',
    'last_month' => 'الشهر الماضي',
    'this_year' => 'هذا العام',
    'last_payment' => 'آخر دفعة',

    // Messages
    'no_payments' => 'لا توجد مدفوعات',
    'no_payments_message' => 'لم تقم بأي عملية دفع بعد. عندما تشترك في أي خدمة، ستظهر مدفوعاتك هنا.',
    'payment_successful' => 'تمت عملية الدفع بنجاح',
    'payment_failed' => 'فشلت عملية الدفع',
    'payment_pending' => 'الدفع قيد المراجعة',
    'refund_requested' => 'تم طلب الاسترداد',
    'refund_processed' => 'تم معالجة الاسترداد',

    // Time periods
    'today' => 'اليوم',
    'yesterday' => 'أمس',
    'last_7_days' => 'آخر 7 أيام',
    'last_30_days' => 'آخر 30 يوم',
    'custom_range' => 'نطاق مخصص',

    // Empty states
    'no_results' => 'لا توجد نتائج',
    'no_results_message' => 'لم نعثر على أي مدفوعات تطابق المعايير المحددة.',

    // Quran Subscription Payment Page
    'quran_payment' => [
        'page_title' => 'دفع اشتراك القرآن الكريم',
        'header_subtitle' => 'دفع اشتراك القرآن الكريم',
        'secure_payment' => 'دفع آمن',
        'complete_payment' => 'إكمال عملية الدفع',
        'choose_method' => 'اختر طريقة الدفع المناسبة لإكمال اشتراكك',
        'payment_method_label' => 'طريقة الدفع *',
        'credit_card_title' => 'بطاقة ائتمانية',
        'credit_card_desc' => 'Visa, MasterCard',
        'mada_title' => 'مدى',
        'mada_desc' => 'بطاقات الدفع السعودية',
        'stc_pay_title' => 'STC Pay',
        'stc_pay_desc' => 'الدفع عبر الجوال',
        'bank_transfer_title' => 'تحويل بنكي',
        'bank_transfer_desc' => 'تحويل مباشر',
        'card_number' => 'رقم البطاقة *',
        'cardholder_name' => 'اسم حامل البطاقة *',
        'cardholder_placeholder' => 'كما هو مكتوب على البطاقة',
        'expiry_month' => 'الشهر *',
        'expiry_year' => 'السنة *',
        'month_placeholder' => 'الشهر',
        'year_placeholder' => 'السنة',
        'cvv' => 'CVV *',
        'security_title' => 'أمان المعاملات',
        'security_message' => 'جميع المعاملات مشفرة ومحمية بأعلى معايير الأمان. لا نحتفظ ببيانات بطاقتك الائتمانية.',
        'pay_button' => 'دفع :amount :currency',
        'subscription_details' => 'تفاصيل الاشتراك',
        'quran_teacher' => 'معلم القرآن الكريم',
        'package_label' => 'الباقة:',
        'subscription_type_label' => 'نوع الاشتراك:',
        'private_sessions' => 'جلسات خاصة',
        'group_sessions' => 'جلسات جماعية',
        'sessions_count' => 'عدد الجلسات:',
        'sessions_unit' => 'جلسة',
        'subscription_duration' => 'مدة الاشتراك:',
        'billing_monthly' => 'شهر واحد',
        'billing_quarterly' => 'ثلاثة أشهر',
        'billing_yearly' => 'سنة واحدة',
        'payment_summary' => 'ملخص الدفع',
        'subscription_price' => 'سعر الاشتراك:',
        'discount_label' => 'الخصم:',
        'price_after_discount' => 'السعر بعد الخصم:',
        'vat_label' => 'ضريبة القيمة المضافة (15%):',
        'total_amount' => 'المجموع الكلي:',
        'need_help' => 'هل تحتاج مساعدة؟',
        'help_message' => 'إذا واجهت أي مشكلة في عملية الدفع، تواصل معنا',
        'processing_payment' => 'جارٍ معالجة الدفع...',
        'processing_message' => 'يرجى عدم إغلاق هذه الصفحة أو الضغط على زر الرجوع',
        'payment_error' => 'حدث خطأ أثناء عملية الدفع',
        'connection_error' => 'حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى',
        'payment_success' => 'تمت عملية الدفع بنجاح!',
        'currency_notice_title' => 'ملاحظة عن العملة',
        'currency_notice_message' => 'سيتم تحويل المبلغ من :currency إلى الجنيه المصري (EGP) حسب سعر الصرف الحالي عند إتمام الدفع.',
    ],

    // Subscription Flow Messages
    'subscription' => [
        // Success messages
        'created_successfully' => 'تم إنشاء الاشتراك بنجاح',
        'enrolled_successfully' => 'تم تسجيلك بنجاح في الكورس! يمكنك الآن متابعة الجلسات والمحتوى التعليمي.',
        'enrollment_pending' => 'تم إنشاء طلب التسجيل. يرجى إكمال عملية الدفع.',

        // Error messages
        'login_required' => 'يجب تسجيل الدخول كطالب للاشتراك',
        'login_to_continue' => 'يرجى تسجيل الدخول للمتابعة',
        'student_only' => 'يجب أن تكون طالباً للاشتراك في الباقات الأكاديمية',
        'complete_profile_first' => 'يجب إكمال الملف الشخصي للطالب أولاً قبل التسجيل في الكورس',
        'billing_cycle_unavailable' => 'دورة الفوترة المختارة غير متاحة لهذه الباقة',
        'already_subscribed' => 'لديك اشتراك نشط مع هذا المعلم في هذه المادة بالفعل',
        'already_subscribed_or_pending' => 'لديك اشتراك نشط أو معلق مع هذا المعلم',
        'already_enrolled' => 'أنت مسجل بالفعل في هذا الكورس',
        'enrollment_closed' => 'عذراً، التسجيل في هذا الكورس مغلق حالياً',
        'payment_init_failed' => 'فشل في بدء عملية الدفع',
        'subscription_creation_error' => 'حدث خطأ أثناء إنشاء الاشتراك',
        'enrollment_error' => 'حدث خطأ أثناء التسجيل',
        'request_error' => 'حدث خطأ أثناء إرسال الطلب. يرجى المحاولة مرة أخرى',
        'unknown_error' => 'خطأ غير معروف',
    ],

    // Trial Session Messages
    'trial' => [
        'login_required' => 'يجب تسجيل الدخول كطالب لحجز جلسة تجريبية',
        'already_requested' => 'لديك طلب جلسة تجريبية مسبق مع هذا المعلم',
        'request_success' => 'تم إرسال طلب الجلسة التجريبية بنجاح! سيتواصل معك المعلم خلال 24 ساعة',
    ],

    // Notifications
    'notifications' => [
        'payment' => 'دفعة',
        'generic_subscription' => 'اشتراك',
        'quran_individual_subscription' => 'اشتراك فردي في القرآن',
        'quran_group_subscription' => 'اشتراك جماعي في القرآن',
    ],

    // Subscription Types
    'subscription_types' => [
        'individual' => 'فردي',
        'group' => 'جماعي',
        'academic' => 'أكاديمي',
    ],

    // Academic
    'academic' => [
        'subject' => 'الموضوع',
    ],
];
