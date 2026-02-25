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
        'articles' => [
            'login' => [
                'title'       => 'تسجيل الدخول وإنشاء الحساب',
                'description' => 'كيفية تسجيل الدخول واستعادة كلمة المرور',
                'icon'        => 'ri-login-box-line',
                'keywords'    => ['تسجيل', 'دخول', 'حساب', 'كلمة مرور', 'استعادة'],
            ],
            'meetings' => [
                'title'       => 'الاجتماعات المرئية',
                'description' => 'كيفية الانضمام إلى الجلسات المرئية وإدارتها',
                'icon'        => 'ri-video-chat-line',
                'keywords'    => ['اجتماع', 'فيديو', 'مرئي', 'كاميرا', 'مايكروفون', 'انضمام'],
            ],
            'chat' => [
                'title'       => 'المحادثات والرسائل',
                'description' => 'كيفية استخدام نظام المحادثات والرسائل',
                'icon'        => 'ri-chat-3-line',
                'keywords'    => ['محادثة', 'رسالة', 'دردشة', 'تواصل'],
            ],
            'notifications' => [
                'title'       => 'الإشعارات',
                'description' => 'إدارة الإشعارات وتخصيص التنبيهات',
                'icon'        => 'ri-notification-3-line',
                'keywords'    => ['إشعار', 'تنبيه', 'إعدادات'],
            ],
        ],
    ],

];
