<?php

/**
 * Arabic translations for Filament Admin Panel
 *
 * This file contains translations for Filament resources, forms, tables, and actions.
 * Use these keys with __('filament.key') pattern.
 */

return [
    // Form Labels
    'gender' => 'الجنس',
    'phone' => 'رقم الهاتف',
    'email' => 'البريد الإلكتروني',
    'name' => 'الاسم',
    'avatar' => 'الصورة',
    'is_active' => 'نشط',
    'is_active_helper' => 'هل هذا السجل نشط؟',
    'status' => 'الحالة',
    'created_at' => 'تاريخ الإنشاء',
    'updated_at' => 'تاريخ التحديث',
    'last_login_at' => 'آخر تسجيل دخول',
    'academy' => 'الأكاديمية',
    'description' => 'الوصف',
    'notes' => 'ملاحظات',

    // Table Labels
    'table' => [
        'empty' => 'لا توجد بيانات',
        'search' => 'بحث',
        'filters' => 'تصفية',
        'actions' => 'الإجراءات',
    ],

    // Tab Labels
    'tabs' => [
        'all' => 'الكل',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'pending' => 'قيد الانتظار',
        'pending_approval' => 'بانتظار الموافقة',
        'pending_requests' => 'طلبات بانتظار المراجعة',
        'approved' => 'معتمد',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
        'expired' => 'منتهي',
        'paused' => 'متوقف',
        'failed' => 'فاشل',
        'refunded' => 'مسترد',
        'individual' => 'فردي',
        'group' => 'جماعي',
        'published' => 'منشور',
        'draft' => 'مسودة',
        'free' => 'مجاني',
        'paid' => 'مدفوع',
        'quran' => 'معلمي القرآن',
        'academic' => 'المعلمين الأكاديميين',
        'unpaid' => 'غير مصروف',
        'disputed' => 'معترض عليه',
        'rejected' => 'مرفوض',
    ],

    // Actions
    'actions' => [
        'view' => 'عرض',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'create' => 'إنشاء',
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
        'confirm' => 'تأكيد',
        'approve' => 'اعتماد',
        'approve_teacher' => 'اعتماد المعلم',
        'approve_confirm_heading' => 'تأكيد الاعتماد',
        'approve_confirm_description' => 'هل أنت متأكد من اعتماد هذا المعلم؟',
        'reject' => 'رفض',
        'reject_confirm_heading' => 'تأكيد الرفض',
        'reject_confirm_description' => 'هل أنت متأكد من رفض هذا الطلب؟',
        'activate' => 'تفعيل',
        'deactivate' => 'إلغاء التفعيل',
        'activate_confirm_heading' => 'تأكيد التفعيل',
        'activate_confirm_description' => 'هل أنت متأكد من تفعيل هذا السجل؟',
        'deactivate_confirm_heading' => 'تأكيد إلغاء التفعيل',
        'deactivate_confirm_description' => 'هل أنت متأكد من إلغاء تفعيل هذا السجل؟',
        'finalize' => 'تأكيد',
        'dispute' => 'اعتراض',
        'pay' => 'دفع',
        'mark_paid' => 'تحديد كمدفوع',
        'restore' => 'استعادة',
        'force_delete' => 'حذف نهائي',
        'restore_selected' => 'استعادة المحدد',
        'force_delete_selected' => 'حذف نهائي للمحدد',
    ],

    // Filters
    'filters' => [
        'from_date' => 'من تاريخ',
        'to_date' => 'إلى تاريخ',
        'active_status' => 'حالة التفعيل',
        'upcoming' => 'قادم',
        'ongoing' => 'جاري',
        'date_range' => 'النطاق الزمني',
        'status' => 'الحالة',
        'type' => 'النوع',
        'trashed' => 'السجلات المحذوفة',
        'today_sessions' => 'جلسات اليوم',
        'this_week_sessions' => 'جلسات هذا الأسبوع',
    ],

    // Navigation Groups
    'nav_groups' => [
        'dashboard' => 'لوحة التحكم',
        'user_management' => 'إدارة المستخدمين',
        'quran_management' => 'إدارة القرآن',
        'academic_management' => 'إدارة التعليم الأكاديمي',
        'recorded_courses' => 'إدارة الدورات المسجلة',
        'teacher_settings' => 'إعدادات المعلمين',
        'settings' => 'الإعدادات',
        'reports' => 'التقارير',
        'payments' => 'المدفوعات',
        'system_management' => 'إدارة النظام',
        'academy_management' => 'إدارة الأكاديميات',
        'quran_memorization' => 'إدارة تحفيظ القرآن',
        'interactive_courses' => 'إدارة الدورات التفاعلية',
        'certificates' => 'إدارة الشهادات',
        'developer_tools' => 'أدوات المطور',
        'exams' => 'الاختبارات',
        'reviews' => 'التقييمات والمراجعات',
        'reports_attendance' => 'التقارير والحضور',
    ],

    // Common Labels
    'all' => 'الكل',
    'active' => 'نشط',
    'inactive' => 'غير نشط',
    'yes' => 'نعم',
    'no' => 'لا',
    'none' => 'لا يوجد',
    'unknown' => 'غير معروف',
    'not_set' => 'غير محدد',

    // Resource-specific
    'children_count' => 'عدد الأطفال',
    'has_active_subscription' => 'لديه اشتراك نشط',
    'subscription_code' => 'رمز الاشتراك',
    'session_count' => 'عدد الجلسات',
    'remaining_sessions' => 'الجلسات المتبقية',
    'completed_sessions' => 'الجلسات المكتملة',
    'total_amount' => 'المبلغ الإجمالي',
    'monthly_amount' => 'المبلغ الشهري',

    // Teacher-specific
    'teacher' => 'المعلم',
    'teacher_type' => 'نوع المعلم',
    'quran_teacher' => 'معلم قرآن',
    'academic_teacher' => 'معلم أكاديمي',
    'approval_status' => 'حالة الاعتماد',
    'linked_to_account' => 'مربوط بحساب',

    // Student-specific
    'student' => 'الطالب',
    'grade_level' => 'المرحلة الدراسية',
    'parent' => 'ولي الأمر',

    // Subscription-specific
    'subscription' => [
        'subscription' => 'الاشتراك',
        'payment_current' => 'محدث',
        'payment_overdue' => 'متأخر',
    ],
    'package' => 'الباقة',
    'start_date' => 'تاريخ البداية',
    'end_date' => 'تاريخ الانتهاء',
    'billing_cycle' => 'دورة الفوترة',

    // Payment-specific
    'payment' => 'الدفع',
    'payment_method' => 'طريقة الدفع',
    'payment_status' => 'حالة الدفع',
    'amount' => 'المبلغ',
    'paid_at' => 'تاريخ الدفع',

    // Payout-specific
    'payout' => 'الصرف',
    'payout_code' => 'رمز الصرف',
    'payout_status' => 'حالة الصرف',

    // Earning-specific
    'earning' => 'الربح',
    'earning_month' => 'شهر الربح',
    'calculation_method' => 'طريقة الحساب',
    'is_finalized' => 'مؤكد',
    'is_disputed' => 'معترض عليه',
    'dispute_notes' => 'ملاحظات الاعتراض',

    // Circle-specific
    'circle' => [
        'circle' => 'الحلقة',
        'circle_type' => 'نوع الحلقة',
        'memorization_level' => 'مستوى الحفظ',
        'enrollment_status' => 'حالة التسجيل',
        'enrollment_open' => 'مفتوح',
        'enrollment_closed' => 'مغلق',
        'enrollment_full' => 'ممتلئ',
        'age_group' => 'الفئة العمرية',
        'children' => 'أطفال',
        'youth' => 'شباب',
        'adults' => 'كبار',
        'all_ages' => 'كل الفئات',
        'male' => 'رجال',
        'female' => 'نساء',
        'mixed' => 'مختلط',
        'available_spots' => 'يوجد أماكن متاحة',
    ],
    'gender_type' => 'النوع',
    'max_students' => 'الحد الأقصى للطلاب',
    'current_students' => 'عدد الطلاب الحالي',
    'available_spots' => 'الأماكن المتاحة',

    // Course-specific
    'course' => [
        'course' => 'الدورة',
        'course_title' => 'عنوان الدورة',
        'subject' => 'المادة الدراسية',
        'is_free' => 'نوع السعر',
    ],
    'difficulty_level' => 'مستوى الصعوبة',
    'is_published' => 'منشور',
    'price' => 'السعر',
    'enrollment_count' => 'عدد المسجلين',

    // Session-specific
    'session' => 'الجلسة',
    'session_status' => 'حالة الجلسة',
    'scheduled_at' => 'موعد الجلسة',
    'duration' => 'المدة',
    'attendance_status' => 'حالة الحضور',

    // Dashboard Widget Labels
    'academic_teachers_active' => 'المعلمين الأكاديميين النشطين',
    'academic_subscriptions_active' => 'الاشتراكات الأكاديمية النشطة',
    'pending_subscriptions' => 'الاشتراكات المعلقة',
    'today_sessions' => 'جلسات اليوم',
    'active_courses' => 'الدورات النشطة',
    'published_courses' => 'دورات منشورة',
    'from_total' => 'من إجمالي :count',
    'from_total_this_month' => 'من إجمالي :count جلسة هذا الشهر',
    'this_month' => 'هذا الشهر',
    'needs_review' => 'يحتاج مراجعة',
    'no_pending_requests' => 'لا توجد طلبات معلقة',

    // Messages
    'messages' => [
        'created' => 'تم الإنشاء بنجاح',
        'updated' => 'تم التحديث بنجاح',
        'deleted' => 'تم الحذف بنجاح',
        'approved' => 'تم الاعتماد بنجاح',
        'rejected' => 'تم الرفض',
        'activated' => 'تم التفعيل',
        'deactivated' => 'تم إلغاء التفعيل',
        'confirm_delete' => 'هل أنت متأكد من حذف هذا السجل؟',
        'no_records' => 'لا توجد سجلات',
    ],
];
