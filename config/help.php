<?php

/*
|--------------------------------------------------------------------------
| Help Center Configuration
|--------------------------------------------------------------------------
|
| Defines the navigation structure for the in-app help center (مركز المساعدة).
| Each role has a flat list of articles. An optional 'section' key groups
| articles visually on the landing page without affecting the route structure.
|
| Article routes: GET /help/{role}/{slug}  →  help.{role}.{slug} view
| Common routes:  GET /help/common/{slug}  →  help.common.{slug} view
|
*/

return [

    'roles' => [

        'admin' => [
            'label' => 'دليل المدير والمشرف العام',
            'icon'  => 'ri-shield-user-line',
            'color' => 'amber',
            'articles' => [
                // ── Platform Overview ──────────────────────────────────────────
                'platform-overview' => [
                    'title'       => 'نظرة عامة على منصة إتقان',
                    'description' => 'تعرف على بنية المنصة، أنواع المستخدمين، لوحات التحكم، وروابط تسجيل الدخول',
                    'icon'        => 'ri-layout-masonry-line',
                    'section'     => 'overview',
                    'keywords'    => ['نظرة عامة', 'بنية', 'منصة', 'مستخدمين', 'لوحة تحكم', 'تسجيل دخول', 'أدوار', 'أكاديمية', 'مدير', 'معلم', 'طالب', 'ولي أمر', 'مشرف', 'روابط'],
                ],

                // ── Quran Management ──────────────────────────────────────────
                'quran-programs' => [
                    'title'       => 'إدارة القرآن الكريم - نظرة عامة',
                    'description' => 'تعرف على قسم إدارة القرآن في لوحة التحكم وما يمكنك تنفيذه',
                    'icon'        => 'ri-book-open-line',
                    'section'     => 'quran',
                    'keywords'    => ['قرآن', 'إدارة', 'نظرة عامة', 'لوحة تحكم', 'قسم', 'باقات', 'معلمون', 'حلقات', 'اشتراكات'],
                ],
                'quran-packages' => [
                    'title'       => 'إدارة باقات القرآن',
                    'description' => 'كيفية إنشاء وتعديل وتفعيل باقات حلقات القرآن الكريم والتسعير',
                    'icon'        => 'ri-price-tag-3-line',
                    'section'     => 'quran',
                    'keywords'    => ['باقات', 'قرآن', 'أسعار', 'جلسات', 'اشتراك', 'تسعير', 'شهري', 'ربع سنوي', 'سنوي', 'مميزات'],
                ],
                'quran-teachers' => [
                    'title'       => 'إدارة معلمي القرآن',
                    'description' => 'إضافة معلمين جدد وإدارة حساباتهم وتفعيل أو تعطيل الوصول',
                    'icon'        => 'ri-user-star-line',
                    'section'     => 'quran',
                    'keywords'    => ['معلم', 'قرآن', 'إضافة', 'تفعيل', 'تعطيل', 'كود المعلم', 'حساب', 'نوع الجنس', 'صورة'],
                ],
                'quran-circles' => [
                    'title'       => 'إدارة الحلقات الفردية',
                    'description' => 'إنشاء الحلقات وتتبع تقدم الطلاب في الحفظ والمراجعة',
                    'icon'        => 'ri-group-line',
                    'section'     => 'quran',
                    'keywords'    => ['حلقة', 'فردية', 'حفظ', 'مراجعة', 'تلاوة', 'تجويد', 'تخصص', 'مستوى', 'تقدم', 'صفحات', 'سور'],
                ],
                'quran-subscriptions' => [
                    'title'       => 'إدارة اشتراكات القرآن',
                    'description' => 'إنشاء الاشتراكات وإدارة عدد الجلسات والتجديد التلقائي',
                    'icon'        => 'ri-calendar-check-line',
                    'section'     => 'quran',
                    'keywords'    => ['اشتراك', 'قرآن', 'جلسات', 'متبقي', 'تجديد', 'تلقائي', 'دفع', 'حالة', 'فردي', 'جماعي'],
                ],
                'quran-sessions' => [
                    'title'       => 'إدارة جلسات القرآن',
                    'description' => 'عرض الجلسات ومتابعة حالتها وتسيير الواجبات القرآنية',
                    'icon'        => 'ri-vidicon-line',
                    'section'     => 'quran',
                    'keywords'    => ['جلسة', 'قرآن', 'حالة', 'مجدولة', 'مباشر', 'مكتملة', 'واجب', 'حفظ', 'مراجعة', 'سور', 'كود الجلسة'],
                ],

                // ── Academic Management (future phase) ────────────────────────
                // 'academic-programs' => [...],
                // 'academic-packages' => [...],
                // ...

                // ── Users / Finance / Settings (future phase) ─────────────────
                // 'user-management' => [...],
                // 'payments-finance' => [...],
                // ...
            ],
        ],

        'student' => [
            'label'    => 'دليل الطالب',
            'icon'     => 'ri-graduation-cap-line',
            'color'    => 'blue',
            'articles' => [],  // Populated in a future phase
        ],

        'parent' => [
            'label'    => 'دليل ولي الأمر',
            'icon'     => 'ri-parent-line',
            'color'    => 'green',
            'articles' => [],
        ],

        'quran_teacher' => [
            'label'    => 'دليل معلم القرآن',
            'icon'     => 'ri-book-2-line',
            'color'    => 'purple',
            'articles' => [],
        ],

        'academic_teacher' => [
            'label'    => 'دليل المعلم الأكاديمي',
            'icon'     => 'ri-pencil-ruler-2-line',
            'color'    => 'indigo',
            'articles' => [],
        ],

        'supervisor' => [
            'label'    => 'دليل المشرف',
            'icon'     => 'ri-eye-line',
            'color'    => 'orange',
            'articles' => [],
        ],

    ],

    'common' => [
        'articles' => [],  // Populated in a future phase
    ],

];
