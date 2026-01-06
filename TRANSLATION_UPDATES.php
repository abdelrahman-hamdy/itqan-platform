<?php

/**
 * Additional Translation Keys for Component Migration
 *
 * These keys should be added to lang/ar/components.php and lang/en/components.php
 * They replace hardcoded Arabic strings in component blade files.
 */

return [
    'arabic_additions' => [
        // Academic attendance overview additions
        'academic.attendance_overview.export_info_message' => 'سيتم تنفيذ تصدير البيانات قريباً',

        // Academic subscription header additions
        'academic.subscription_header.visible_to_admin_teachers' => 'مرئية للإدارة والمعلمين فقط',

        // Academic homework management additions
        'academic.homework_management.create_homework_title' => 'إنشاء واجب جديد',
        'academic.homework_management.create_homework_message' => 'سيتم تنفيذ إنشاء الواجبات قريباً',
        'academic.homework_management.edit_homework_title' => 'تعديل الواجب',
        'academic.homework_management.edit_homework_message' => 'سيتم تنفيذ تعديل الواجبات قريباً',
        'academic.homework_management.grade_homework_title' => 'تقييم الواجب',
        'academic.homework_management.grade_homework_message' => 'سيتم تنفيذ تقييم الواجبات قريباً',

        // Cards - Individual subscription
        'cards.individual_subscription.individual_sessions' => 'جلسات فردية (1 على 1)',
        'cards.individual_subscription.sessions_used' => 'جلسة',
        'cards.individual_subscription.last_session_ago' => 'آخر جلسة',
        'cards.individual_subscription.sessions_remaining' => 'جلسة متبقية',
        'cards.individual_subscription.subscription_cycle' => 'اشتراك',
        'cards.individual_subscription.monthly' => 'شهري',
        'cards.individual_subscription.quarterly' => 'ربع سنوي',
        'cards.individual_subscription.yearly' => 'سنوي',
        'cards.individual_subscription.preparing_circle' => 'جاري إعداد الحلقة الفردية...',

        // Cards - Group circle subscription
        'cards.group_circle.group_circle' => 'حلقة قرآنية',
        'cards.group_circle.students_enrolled' => 'طالب',
        'cards.group_circle.sessions_used' => 'جلسة مستخدمة',
        'cards.group_circle.remaining' => 'متبقية',
        'cards.group_circle.renewal_date' => 'التجديد:',
        'cards.group_circle.days_labels.sunday' => 'الأحد',
        'cards.group_circle.days_labels.monday' => 'الاثنين',
        'cards.group_circle.days_labels.tuesday' => 'الثلاثاء',
        'cards.group_circle.days_labels.wednesday' => 'الأربعاء',
        'cards.group_circle.days_labels.thursday' => 'الخميس',
        'cards.group_circle.days_labels.friday' => 'الجمعة',
        'cards.group_circle.days_labels.saturday' => 'السبت',

        // Cards - Learning section
        'cards.learning_section.section_title' => 'عنوان القسم',
        'cards.learning_section.section_description' => 'وصف القسم',
        'cards.learning_section.additional_options' => 'خيارات إضافية',
        'cards.learning_section.progress' => 'التقدم',
        'cards.learning_section.no_items' => 'لا توجد عناصر',
        'cards.learning_section.no_items_description' => 'لم يتم العثور على أي عناصر في هذا القسم',
        'cards.learning_section.view_all' => 'عرض الكل',
        'cards.learning_section.view_details' => 'عرض التفاصيل',
        'cards.learning_section.status_labels.active' => 'نشط',
        'cards.learning_section.status_labels.pending' => 'قيد الانتظار',
        'cards.learning_section.status_labels.cancelled' => 'ملغي',
        'cards.learning_section.status_labels.expired' => 'منتهي',
        'cards.learning_section.status_labels.paused' => 'متوقف',
        'cards.learning_section.status_labels.completed' => 'مكتمل',
        'cards.learning_section.status_labels.draft' => 'مسودة',
        'cards.learning_section.status_labels.published' => 'منشور',
        'cards.learning_section.status_labels.inactive' => 'غير نشط',
    ],

    'english_additions' => [
        // Academic attendance overview additions
        'academic.attendance_overview.export_info_message' => 'Data export will be implemented soon',

        // Academic subscription header additions
        'academic.subscription_header.visible_to_admin_teachers' => 'Visible to admin and teachers only',

        // Academic homework management additions
        'academic.homework_management.create_homework_title' => 'Create New Homework',
        'academic.homework_management.create_homework_message' => 'Homework creation will be implemented soon',
        'academic.homework_management.edit_homework_title' => 'Edit Homework',
        'academic.homework_management.edit_homework_message' => 'Homework editing will be implemented soon',
        'academic.homework_management.grade_homework_title' => 'Grade Homework',
        'academic.homework_management.grade_homework_message' => 'Homework grading will be implemented soon',

        // Cards - Individual subscription
        'cards.individual_subscription.individual_sessions' => 'Individual Sessions (1-on-1)',
        'cards.individual_subscription.sessions_used' => 'session',
        'cards.individual_subscription.last_session_ago' => 'Last session',
        'cards.individual_subscription.sessions_remaining' => 'sessions remaining',
        'cards.individual_subscription.subscription_cycle' => 'subscription',
        'cards.individual_subscription.monthly' => 'Monthly',
        'cards.individual_subscription.quarterly' => 'Quarterly',
        'cards.individual_subscription.yearly' => 'Yearly',
        'cards.individual_subscription.preparing_circle' => 'Preparing individual circle...',

        // Cards - Group circle subscription
        'cards.group_circle.group_circle' => 'Quran Circle',
        'cards.group_circle.students_enrolled' => 'student',
        'cards.group_circle.sessions_used' => 'session used',
        'cards.group_circle.remaining' => 'remaining',
        'cards.group_circle.renewal_date' => 'Renewal:',
        'cards.group_circle.days_labels.sunday' => 'Sunday',
        'cards.group_circle.days_labels.monday' => 'Monday',
        'cards.group_circle.days_labels.tuesday' => 'Tuesday',
        'cards.group_circle.days_labels.wednesday' => 'Wednesday',
        'cards.group_circle.days_labels.thursday' => 'Thursday',
        'cards.group_circle.days_labels.friday' => 'Friday',
        'cards.group_circle.days_labels.saturday' => 'Saturday',

        // Cards - Learning section
        'cards.learning_section.section_title' => 'Section Title',
        'cards.learning_section.section_description' => 'Section Description',
        'cards.learning_section.additional_options' => 'Additional Options',
        'cards.learning_section.progress' => 'Progress',
        'cards.learning_section.no_items' => 'No Items',
        'cards.learning_section.no_items_description' => 'No items found in this section',
        'cards.learning_section.view_all' => 'View All',
        'cards.learning_section.view_details' => 'View Details',
        'cards.learning_section.status_labels.active' => 'Active',
        'cards.learning_section.status_labels.pending' => 'Pending',
        'cards.learning_section.status_labels.cancelled' => 'Cancelled',
        'cards.learning_section.status_labels.expired' => 'Expired',
        'cards.learning_section.status_labels.paused' => 'Paused',
        'cards.learning_section.status_labels.completed' => 'Completed',
        'cards.learning_section.status_labels.draft' => 'Draft',
        'cards.learning_section.status_labels.published' => 'Published',
        'cards.learning_section.status_labels.inactive' => 'Inactive',
    ],
];
