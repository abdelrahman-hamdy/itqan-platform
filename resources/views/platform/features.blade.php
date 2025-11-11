<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="مميزات منصة إتقان - اكتشف جميع القدرات والتقنيات المتقدمة">
    
    <title>المميزات - منصة إتقان</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    
    <!-- Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    
    <style>
        .feature-icon {
            width: 64px;
            height: 64px;
        }
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="{{ route('platform.home') }}" class="text-2xl font-bold text-indigo-600">إتقان</a>
                    </div>
                    <div class="hidden md:block ml-10">
                        <div class="flex items-baseline space-x-4">
                            <a href="{{ route('platform.home') }}" class="text-gray-500 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">الرئيسية</a>
                            <a href="{{ route('platform.features') }}" class="text-gray-900 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">المميزات</a>
                            <a href="{{ route('platform.academies') }}" class="text-gray-500 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">الأكاديميات</a>
                            <a href="{{ route('platform.about') }}" class="text-gray-500 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">من نحن</a>
                            <a href="{{ route('platform.contact') }}" class="text-gray-500 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">اتصل بنا</a>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="http://itqan-academy.{{ config('app.domain') }}/login" class="text-gray-500 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">تسجيل الدخول</a>
                    <a href="http://itqan-academy.{{ config('app.domain') }}/register" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">انضم إلينا</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="bg-indigo-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                    مميزات منصة إتقان
                </h1>
                <p class="text-xl md:text-2xl text-indigo-100 mb-8 max-w-3xl mx-auto">
                    اكتشف جميع القدرات والتقنيات المتقدمة التي تجعل منصة إتقان الخيار الأمثل للتعليم الإسلامي
                </p>
            </div>
        </div>
    </div>

    <!-- Main Features Section -->
    <div class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    المميزات الأساسية
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    مجموعة شاملة من الأدوات والتقنيات لتمكين المؤسسات التعليمية الإسلامية
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Feature 1: Course Management -->
                <div class="feature-card bg-gray-50 p-8 rounded-xl">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900 mb-4">إدارة الدورات التعليمية</h3>
                            <p class="text-gray-600 mb-6">
                                نظام متكامل لإدارة الدورات التعليمية مع إمكانية رفع المحتوى التفاعلي والمراقبة المستمرة للتقدم
                            </p>
                            <ul class="space-y-2 text-gray-600">
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    رفع المحتوى التعليمي (فيديو، صوت، نصوص)
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    تتبع تقدم الطلاب
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    اختبارات وتقييمات
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Feature 2: Quran Circles -->
                <div class="feature-card bg-gray-50 p-8 rounded-xl">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900 mb-4">حلقات القرآن الكريم</h3>
                            <p class="text-gray-600 mb-6">
                                إدارة متقدمة لحلقات القرآن الكريم مع نظام حضور متكامل ومتابعة التقدم في الحفظ والتلاوة
                            </p>
                            <ul class="space-y-2 text-gray-600">
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    نظام حضور متكامل
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    متابعة التقدم في الحفظ
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    تقييم التلاوة
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Feature 3: Video Conferencing -->
                <div class="feature-card bg-gray-50 p-8 rounded-xl">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900 mb-4">مؤتمرات الفيديو</h3>
                            <p class="text-gray-600 mb-6">
                                نظام مؤتمرات فيديو متقدم يدعم الدروس المباشرة والتفاعل المباشر بين المعلمين والطلاب
                            </p>
                            <ul class="space-y-2 text-gray-600">
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    دروس مباشرة عالية الجودة
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    مشاركة الشاشة والملفات
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    تسجيل الدروس
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Feature 4: Analytics -->
                <div class="feature-card bg-gray-50 p-8 rounded-xl">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900 mb-4">تقارير وإحصائيات</h3>
                            <p class="text-gray-600 mb-6">
                                نظام تقارير متقدم يوفر رؤية شاملة عن أداء الطلاب وتقدمهم في جميع المجالات التعليمية
                            </p>
                            <ul class="space-y-2 text-gray-600">
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    تقارير مفصلة عن الأداء
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    رسوم بيانية تفاعلية
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    تصدير التقارير
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Features Section -->
    <div class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    المميزات المتقدمة
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    تقنيات متطورة تضمن تجربة تعليمية استثنائية
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Advanced Feature 1 -->
                <div class="feature-card bg-white p-6 rounded-xl shadow-sm">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">أمان وحماية متقدمة</h3>
                    <p class="text-gray-600 text-sm">
                        تشفير متقدم للبيانات، مصادقة متعددة العوامل، وحماية من الهجمات السيبرانية
                    </p>
                </div>

                <!-- Advanced Feature 2 -->
                <div class="feature-card bg-white p-6 rounded-xl shadow-sm">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">نظام مدفوعات آمن</h3>
                    <p class="text-gray-600 text-sm">
                        دعم متعدد لطرق الدفع، اشتراكات شهرية وسنوية، وفواتير تلقائية
                    </p>
                </div>

                <!-- Advanced Feature 3 -->
                <div class="feature-card bg-white p-6 rounded-xl shadow-sm">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">تجربة مستخدم محسنة</h3>
                    <p class="text-gray-600 text-sm">
                        واجهة مستخدم بديهية، تصميم متجاوب، ودعم للغة العربية والإنجليزية
                    </p>
                </div>

                <!-- Advanced Feature 4 -->
                <div class="feature-card bg-white p-6 rounded-xl shadow-sm">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">أداء عالي</h3>
                    <p class="text-gray-600 text-sm">
                        سرعة استجابة عالية، تحميل سريع للمحتوى، وتجربة سلسة على جميع الأجهزة
                    </p>
                </div>

                <!-- Advanced Feature 5 -->
                <div class="feature-card bg-white p-6 rounded-xl shadow-sm">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">تطبيق جوال</h3>
                    <p class="text-gray-600 text-sm">
                        تطبيق جوال متوافق مع iOS وAndroid للوصول السهل للمحتوى التعليمي
                    </p>
                </div>

                <!-- Advanced Feature 6 -->
                <div class="feature-card bg-white p-6 rounded-xl shadow-sm">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">دعم فني 24/7</h3>
                    <p class="text-gray-600 text-sm">
                        فريق دعم فني متخصص متاح على مدار الساعة لمساعدتك في حل أي مشكلة
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-24 bg-indigo-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                جرب منصة إتقان اليوم
            </h2>
            <p class="text-xl text-indigo-100 mb-8 max-w-2xl mx-auto">
                اكتشف كيف يمكن لمنصة إتقان أن تحول تجربتك التعليمية
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('platform.academies') }}" class="bg-white text-indigo-600 hover:bg-gray-100 px-8 py-3 rounded-lg text-lg font-semibold transition duration-150 ease-in-out">
                    اكتشف الأكاديميات
                </a>
                <a href="http://itqan-academy.{{ config('app.domain') }}/register" class="border-2 border-white text-white hover:bg-white hover:text-indigo-600 px-8 py-3 rounded-lg text-lg font-semibold transition duration-150 ease-in-out">
                    ابدأ الآن
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-2xl font-bold text-indigo-400 mb-4">إتقان</h3>
                    <p class="text-gray-400">
                        منصة تقنية متكاملة لتمكين التعليم الإسلامي من خلال أحدث التقنيات والحلول الرقمية
                    </p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">المنصة</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('platform.features') }}" class="hover:text-white">المميزات</a></li>
                        <li><a href="{{ route('platform.academies') }}" class="hover:text-white">الأكاديميات</a></li>
                        <li><a href="{{ route('platform.about') }}" class="hover:text-white">من نحن</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">الدعم</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('platform.contact') }}" class="hover:text-white">اتصل بنا</a></li>
                        <li><a href="#" class="hover:text-white">الدعم الفني</a></li>
                        <li><a href="#" class="hover:text-white">الأسئلة الشائعة</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">تواصل معنا</h4>
                    <div class="space-y-2 text-gray-400">
                        <p>البريد الإلكتروني: info@itqan.com</p>
                        <p>الهاتف: +966 50 123 4567</p>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} منصة إتقان. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>
</body>
</html>
