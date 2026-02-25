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

        // ── Developer Technical Documentation (super_admin only) ──────────────
        'developer' => [
            'label' => 'Developer Technical Documentation',
            'icon'  => 'ri-code-s-slash-line',
            'color' => 'slate',
            'articles' => [

                // ── Architecture ──────────────────────────────────────────────
                'platform-architecture' => [
                    'title'       => 'Platform Architecture Overview',
                    'description' => 'Tech stack, multi-tenancy design, application layers, and the 4 Filament panels',
                    'icon'        => 'ri-layout-masonry-line',
                    'section'     => 'architecture',
                    'keywords'    => ['architecture', 'stack', 'laravel', 'filament', 'livewire', 'multi-tenancy', 'panels', 'layers'],
                ],
                'database-schema' => [
                    'title'       => 'Database Schema & ERD',
                    'description' => '~90 tables across 8 domains, naming conventions, key relationships, multi-tenancy scoping',
                    'icon'        => 'ri-database-2-line',
                    'section'     => 'architecture',
                    'keywords'    => ['database', 'schema', 'tables', 'erd', 'migrations', 'relations', 'mysql'],
                ],

                // ── Models ────────────────────────────────────────────────────
                'model-architecture' => [
                    'title'       => 'Model Architecture & Patterns',
                    'description' => 'Polymorphic inheritance, constructor merge pattern, getCasts() override, 22 traits, 20 policies',
                    'icon'        => 'ri-stack-line',
                    'section'     => 'models',
                    'keywords'    => ['models', 'traits', 'policies', 'patterns', 'eloquent', 'inheritance', 'casts'],
                ],
                'session-system' => [
                    'title'       => 'Session System Deep Dive',
                    'description' => 'BaseSession → 3 types, status lifecycle, attendance auto-tracking, CountsTowardsSubscription trait',
                    'icon'        => 'ri-vidicon-line',
                    'section'     => 'models',
                    'keywords'    => ['sessions', 'quran', 'academic', 'interactive', 'attendance', 'status', 'livekit', 'subscription'],
                ],
                'subscription-system' => [
                    'title'       => 'Subscription System',
                    'description' => 'BaseSubscription → 3 types, billing cycles, auto-renewal, payment integration',
                    'icon'        => 'ri-calendar-check-line',
                    'section'     => 'models',
                    'keywords'    => ['subscriptions', 'renewal', 'billing', 'payment', 'quran', 'academic', 'course', 'lifecycle'],
                ],

                // ── Services ──────────────────────────────────────────────────
                'service-layer' => [
                    'title'       => 'Service Layer Architecture',
                    'description' => '100+ services organized by domain — when to use which service and how to add new ones',
                    'icon'        => 'ri-settings-5-line',
                    'section'     => 'services',
                    'keywords'    => ['services', 'business logic', 'architecture', 'patterns', 'domain'],
                ],
                'real-time-system' => [
                    'title'       => 'Real-time & Video Architecture',
                    'description' => 'Laravel Reverb WebSocket, LiveKit video conferencing, WireChat supervised messaging',
                    'icon'        => 'ri-broadcast-line',
                    'section'     => 'services',
                    'keywords'    => ['reverb', 'websocket', 'livekit', 'wirechat', 'broadcasting', 'real-time', 'video'],
                ],
                'payment-system' => [
                    'title'       => 'Payment Architecture',
                    'description' => '5 gateways (Paymob, EasyKash, Tapay, Moyasar, STC Pay), payment state machine, webhook handling, auto-renewal',
                    'icon'        => 'ri-secure-payment-line',
                    'section'     => 'services',
                    'keywords'    => ['payment', 'paymob', 'easykash', 'webhook', 'renewal', 'gateway', 'state machine'],
                ],
                'api-architecture' => [
                    'title'       => 'API Architecture',
                    'description' => 'REST API at /api/v1/, Sanctum authentication, mobile endpoint conventions, response format',
                    'icon'        => 'ri-api-line',
                    'section'     => 'services',
                    'keywords'    => ['api', 'rest', 'sanctum', 'mobile', 'endpoints', 'authentication', 'response'],
                ],

                // ── Admin Panels ──────────────────────────────────────────────
                'filament-panels' => [
                    'title'       => 'Filament Admin Panels',
                    'description' => '4 panels (Academy/Teacher/AcademicTeacher/Supervisor), resource conventions, widgets, common pitfalls',
                    'icon'        => 'ri-layout-masonry-fill',
                    'section'     => 'admin',
                    'keywords'    => ['filament', 'panels', 'resources', 'widgets', 'pages', 'admin', 'pitfalls'],
                ],

                // ── Conventions ───────────────────────────────────────────────
                'code-conventions' => [
                    'title'       => 'Code Conventions & Patterns',
                    'description' => 'Service layer, multi-tenancy scoping, timezone handling, RTL Arabic, observer pattern, localization',
                    'icon'        => 'ri-code-s-slash-line',
                    'section'     => 'conventions',
                    'keywords'    => ['conventions', 'patterns', 'timezone', 'rtl', 'multi-tenancy', 'observer', 'localization'],
                ],

                // ── Infrastructure ────────────────────────────────────────────
                'deployment-guide' => [
                    'title'       => 'Deployment & Infrastructure',
                    'description' => 'Production server, Supervisor workers, Horizon queues, Redis DB separation, full deployment checklist',
                    'icon'        => 'ri-server-line',
                    'section'     => 'infrastructure',
                    'keywords'    => ['deployment', 'server', 'supervisor', 'horizon', 'redis', 'production', 'checklist'],
                ],
                'development-setup' => [
                    'title'       => 'Local Development Setup',
                    'description' => 'Environment prerequisites, composer dev, database seeding, multi-tenancy local config, E2E testing',
                    'icon'        => 'ri-computer-line',
                    'section'     => 'infrastructure',
                    'keywords'    => ['development', 'local', 'setup', 'vite', 'playwright', 'seeding', 'env'],
                ],

            ],
        ],

    ],

    'common' => [
        'articles' => [],  // Populated in a future phase
    ],

];
