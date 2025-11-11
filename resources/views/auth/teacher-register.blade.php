<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $academy ? $academy->name . ' - ' : '' }}تسجيل معلم جديد</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;700;800;900&display=swap');
        body { font-family: 'Tajawal', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Academy Logo and Title -->
            <div class="text-center">
                @if($academy && $academy->logo_url)
                    <img class="mx-auto h-16 w-auto" src="{{ $academy->logo_url }}" alt="{{ $academy->name }}">
                @else
                    <div class="mx-auto h-16 w-16 bg-green-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-chalkboard-teacher text-white text-2xl"></i>
                    </div>
                @endif
                <h2 class="mt-6 text-3xl font-bold text-gray-900">
                    {{ $academy ? $academy->name : 'منصة إتقان' }}
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    تسجيل حساب معلم جديد
                </p>
            </div>

            <!-- Teacher Type Selection Form -->
            <form class="mt-8 space-y-6" method="POST" action="{{ route('teacher.register.step1', ['subdomain' => request()->route('subdomain')]) }}">
                @csrf
                
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6 text-center">
                        <i class="fas fa-question-circle ml-2"></i>
                        اختر نوع المعلم
                    </h3>
                    
                    <!-- Quran Teacher Option -->
                    <div class="mb-6">
                        <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition duration-150 ease-in-out">
                            <input type="radio" name="teacher_type" value="quran_teacher" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300" {{ old('teacher_type') == 'quran_teacher' ? 'checked' : '' }}>
                            <div class="mr-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-quran text-green-600 text-xl"></i>
                                    </div>
                                    <div class="mr-4">
                                        <h4 class="text-lg font-medium text-gray-900">معلم القرآن الكريم</h4>
                                        <p class="text-sm text-gray-600">تعليم القرآن الكريم وحفظه وتجويده</p>
                                    </div>
                                </div>
                                <div class="mt-3 text-sm text-gray-500">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>تعليم القرآن الكريم وحفظه</li>
                                        <li>التجويد وأحكام التلاوة</li>
                                        <li>إجازات القرآن الكريم</li>
                                        <li>حلقات القرآن الجماعية</li>
                                    </ul>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Academic Teacher Option -->
                    <div class="mb-6">
                        <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition duration-150 ease-in-out">
                            <input type="radio" name="teacher_type" value="academic_teacher" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" {{ old('teacher_type') == 'academic_teacher' ? 'checked' : '' }}>
                            <div class="mr-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-graduation-cap text-blue-600 text-xl"></i>
                                    </div>
                                    <div class="mr-4">
                                        <h4 class="text-lg font-medium text-gray-900">معلم المواد الأكاديمية</h4>
                                        <p class="text-sm text-gray-600">تعليم المواد الدراسية المختلفة</p>
                                    </div>
                                </div>
                                <div class="mt-3 text-sm text-gray-500">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>الرياضيات والعلوم</li>
                                        <li>اللغة العربية والإنجليزية</li>
                                        <li>المواد الاجتماعية</li>
                                        <li>الدروس الخصوصية</li>
                                    </ul>
                                </div>
                            </div>
                        </label>
                    </div>

                    @error('teacher_type')
                        <p class="mt-2 text-sm text-red-600 text-center">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                        <span class="absolute left-0 inset-y-0 flex items-center pr-3">
                            <i class="fas fa-arrow-right text-green-500 group-hover:text-green-400"></i>
                        </span>
                        التالي
                    </button>
                </div>

                <!-- Login Link -->
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        لديك حساب بالفعل؟
                        <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}" class="font-medium text-green-600 hover:text-green-500">
                            تسجيل الدخول
                        </a>
                    </p>
                </div>
            </form>

            <!-- Back to Home -->
            <div class="text-center">
                <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}" class="text-sm text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-right ml-1"></i>
                    العودة للصفحة الرئيسية
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="fixed top-4 left-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-lg z-50">
            <div class="flex items-center">
                <i class="fas fa-check-circle ml-2"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="fixed top-4 left-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-lg z-50">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle ml-2"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif
</body>
</html> 