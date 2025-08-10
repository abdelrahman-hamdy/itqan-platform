<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطأ في الجلسة - منصة إتقان</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-lg p-8 max-w-md mx-4 text-center">
            <!-- Icon -->
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.96-.833-2.73 0L5.084 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <!-- Content -->
            <h1 class="text-2xl font-bold text-gray-900 mb-4">خطأ في الجلسة</h1>
            <p class="text-gray-600 mb-6">{{ $message }}</p>
            
            <!-- Session Info -->
            @if($session)
            <div class="bg-gray-50 rounded-lg p-4 mb-6 text-right">
                <h3 class="font-semibold text-gray-900 mb-2">تفاصيل الجلسة</h3>
                @if($session->title)
                <p class="text-sm text-gray-600 mb-1">
                    <span class="font-medium">العنوان:</span> {{ $session->title }}
                </p>
                @endif
                @if($session->scheduled_at)
                <p class="text-sm text-gray-600 mb-1">
                    <span class="font-medium">الموعد:</span> {{ $session->scheduled_at->format('d/m/Y - h:i A') }}
                </p>
                @endif
                @if($session->duration_minutes)
                <p class="text-sm text-gray-600">
                    <span class="font-medium">المدة:</span> {{ $session->duration_minutes }} دقيقة
                </p>
                @endif
            </div>
            @endif
            
            <!-- Actions -->
            <div class="space-y-3">
                <button onclick="window.location.reload()" 
                        class="w-full bg-red-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-red-700 transition-colors">
                    إعادة المحاولة
                </button>
                
                <button onclick="window.close()" 
                        class="w-full bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                    إغلاق
                </button>
            </div>
            
            <!-- Support notice -->
            <div class="mt-6 p-3 bg-blue-50 rounded-lg">
                <p class="text-xs text-blue-700">
                    <strong>تحتاج مساعدة؟</strong><br>
                    تواصل مع الدعم الفني أو المعلم للحصول على المساعدة
                </p>
            </div>
        </div>
    </div>
</body>
</html>
