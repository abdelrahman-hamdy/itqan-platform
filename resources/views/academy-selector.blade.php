<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>اختر الأكاديمية - منصة إتقان</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cairo:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-arabic bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                🎓 منصة إتقان
            </h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                اختر الأكاديمية التي تريد زيارتها
            </p>
        </div>

        <!-- Academies Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto mb-12">
            @foreach($academies as $academy)
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:transform hover:scale-105 transition-all duration-300 hover:shadow-xl">
                    <!-- Academy Logo -->
                    @if($academy->logo_url)
                        <div class="h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center p-4">
                            <img src="{{ $academy->logo_url }}" alt="{{ $academy->name }}" class="max-h-full max-w-full object-contain rounded-lg">
                        </div>
                    @else
                        <div class="h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                            <div class="text-white text-4xl font-bold">
                                {{ substr($academy->name, 0, 2) }}
                            </div>
                        </div>
                    @endif
                    
                    <!-- Academy Info -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $academy->name }}</h3>
                        <p class="text-gray-600 mb-4 line-clamp-2">{{ $academy->description }}</p>
                        
                        <!-- Academy Domain -->
                        <div class="bg-gray-50 rounded-lg p-3 mb-4">
                            <p class="text-sm text-gray-500 mb-1">رابط الأكاديمية:</p>
                            <code class="text-sm font-mono text-blue-600">{{ $academy->subdomain }}.itqan-platform.test</code>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="space-y-2">
                            <!-- Test Route Link -->
                            <a href="/test-academy-{{ $academy->subdomain }}" class="w-full bg-green-600 text-white text-center py-3 rounded-lg font-medium hover:bg-green-700 transition-colors inline-block">
                                👀 معاينة التصميم
                            </a>
                            
                            <!-- Copy Domain Link -->
                            <button
                                x-data
                                @click="navigator.clipboard.writeText('http://{{ $academy->subdomain }}.itqan-platform.test').then(() => window.toast?.success('تم نسخ الرابط'))"
                                class="w-full bg-blue-600 text-white text-center py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                📋 نسخ الرابط
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Instructions -->
        <div class="bg-white rounded-2xl shadow-lg p-8 max-w-4xl mx-auto">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">📋 طرق الوصول للأكاديميات</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Method 1: Test Routes -->
                <div class="border border-gray-200 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-green-600 mb-3">✅ الطريقة الأولى (موصى بها)</h4>
                    <p class="text-gray-700 mb-3">استخدم روابط "معاينة التصميم" أعلاه للوصول السريع</p>
                    <div class="bg-green-50 rounded p-3">
                        <code class="text-sm">http://127.0.0.1:8000/test-academy</code>
                    </div>
                </div>
                
                <!-- Method 2: Subdomain Setup -->
                <div class="border border-gray-200 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-blue-600 mb-3">🔧 الطريقة الثانية (متقدمة)</h4>
                    <p class="text-gray-700 mb-3">إعداد النطاقات الفرعية محلياً</p>
                    <div class="bg-blue-50 rounded p-3 text-sm space-y-1">
                        <p><strong>1.</strong> أضف للـ hosts file:</p>
                        <code class="block">127.0.0.1 itqan-academy.itqan-platform.test</code>
                        <p><strong>2.</strong> استخدم Valet أو مشابه</p>
                    </div>
                </div>
            </div>
            
            <!-- Current Status -->
            <div class="mt-8 bg-green-100 border border-green-300 rounded-lg p-4">
                <h4 class="font-semibold text-green-800 mb-2">✅ حالة التصميم الحالية</h4>
                <ul class="text-green-700 space-y-1">
                    <li>• TailwindCSS يعمل بشكل صحيح ✅</li>
                    <li>• تصميم RTL للغة العربية ✅</li>
                    <li>• الرسوم المتحركة والتأثيرات ✅</li>
                    <li>• الخطوط العربية محملة ✅</li>
                    <li>• الألوان والتدرجات ✅</li>
                </ul>
            </div>
        </div>
    </div>

</body>
</html>