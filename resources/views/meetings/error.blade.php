<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - {{ app()->getLocale() === 'ar' ? 'منصة إتقان' : 'Itqan Platform' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vite Assets (Compiled CSS & JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- RemixIcon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <!-- Error Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <!-- Title -->
            <h1 class="text-xl font-bold text-gray-900 mb-4">{{ $title }}</h1>
            
            <!-- Message -->
            <p class="text-gray-600 mb-6">{{ $message }}</p>
            
            <!-- Session Info (if available) -->
            @if($session)
                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-right">
                    <h3 class="font-semibold text-gray-800 mb-2">معلومات الجلسة:</h3>
                    <p class="text-sm text-gray-600">{{ $session->title ?? 'جلسة قرآنية' }}</p>
                    @if($session->scheduled_at)
                        <p class="text-sm text-gray-500 mt-1">
                            الموعد: {{ formatDateTimeArabic($session->scheduled_at) }}
                        </p>
                    @endif
                </div>
            @endif
            
            <!-- Action Buttons -->
            <div class="flex flex-col space-y-3">
                <button onclick="window.history.back()" 
                        class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                    العودة للخلف
                </button>
                
                <button onclick="window.location.reload()" 
                        class="w-full bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                    إعادة المحاولة
                </button>
            </div>
        </div>
        
        <!-- Support Link -->
        <div class="text-center mt-6">
            <p class="text-sm text-gray-500">
                هل تحتاج مساعدة؟ 
                <a href="#" class="text-blue-600 hover:text-blue-800">تواصل مع الدعم الفني</a>
            </p>
        </div>
    </div>
</body>
</html>