<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الجلسة غير جاهزة - منصة إتقان</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-lg p-8 max-w-md mx-4 text-center">
            <!-- Icon -->
            <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <!-- Content -->
            <h1 class="text-2xl font-bold text-gray-900 mb-4">الجلسة غير جاهزة</h1>
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
                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                    تحديث الصفحة
                </button>
                
                <button onclick="window.close()" 
                        class="w-full bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                    إغلاق
                </button>
            </div>
            
            <!-- Auto refresh notice -->
            <p class="text-xs text-gray-500 mt-4">
                ستحاول الصفحة التحديث تلقائياً كل 30 ثانية
            </p>
        </div>
    </div>

    <script>
        // Auto refresh every 30 seconds
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
