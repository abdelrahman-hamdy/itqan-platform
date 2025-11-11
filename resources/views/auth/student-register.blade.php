<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $academy ? $academy->name . ' - ' : '' }}تسجيل طالب جديد</title>
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
                    <div class="mx-auto h-16 w-16 bg-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-graduate text-white text-2xl"></i>
                    </div>
                @endif
                <h2 class="mt-6 text-3xl font-bold text-gray-900">
                    {{ $academy ? $academy->name : 'منصة إتقان' }}
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    تسجيل حساب طالب جديد
                </p>
            </div>

            <!-- Registration Form -->
            <form class="mt-8 space-y-6" method="POST" action="{{ route('student.register.post', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}">
                @csrf
                
                <!-- Personal Information Section -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-user ml-2"></i>
                        المعلومات الشخصية
                    </h3>
                    
                    <!-- First Name -->
                    <div class="mb-4">
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                            الاسم الأول *
                        </label>
                        <input id="first_name" name="first_name" type="text" required 
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('first_name') border-red-500 @enderror"
                               placeholder="أدخل الاسم الأول"
                               value="{{ old('first_name') }}">
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Last Name -->
                    <div class="mb-4">
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                            اسم العائلة *
                        </label>
                        <input id="last_name" name="last_name" type="text" required 
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('last_name') border-red-500 @enderror"
                               placeholder="أدخل اسم العائلة"
                               value="{{ old('last_name') }}">
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            البريد الإلكتروني *
                        </label>
                        <input id="email" name="email" type="email" required 
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') border-red-500 @enderror"
                               placeholder="أدخل البريد الإلكتروني"
                               value="{{ old('email') }}">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="mb-4">
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            رقم الهاتف *
                        </label>
                        <input id="phone" name="phone" type="tel" required 
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('phone') border-red-500 @enderror"
                               placeholder="أدخل رقم الهاتف"
                               value="{{ old('phone') }}">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Birth Date -->
                    <div class="mb-4">
                        <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-2">
                            تاريخ الميلاد *
                        </label>
                        <input id="birth_date" name="birth_date" type="date" required 
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('birth_date') border-red-500 @enderror"
                               value="{{ old('birth_date') }}">
                        @error('birth_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nationality -->
                    <div class="mb-4">
                        <label for="nationality" class="block text-sm font-medium text-gray-700 mb-2">
                            الجنسية *
                        </label>
                        <select id="nationality" name="nationality" required 
                                class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('nationality') border-red-500 @enderror">
                            <option value="">اختر الجنسية</option>
                            @foreach($countries as $code => $name)
                                <option value="{{ $code }}" {{ old('nationality') == $code ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('nationality')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Gender -->
                    <div class="mb-4">
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                            الجنس *
                        </label>
                        <select id="gender" name="gender" required 
                                class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('gender') border-red-500 @enderror">
                            <option value="">اختر الجنس</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>ذكر</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>أنثى</option>
                        </select>
                        @error('gender')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Academic Information Section -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-graduation-cap ml-2"></i>
                        المعلومات الدراسية
                    </h3>
                    
                    <!-- Grade Level -->
                    <div class="mb-4">
                        <label for="grade_level" class="block text-sm font-medium text-gray-700 mb-2">
                            المستوى الدراسي *
                        </label>
                        <select id="grade_level" name="grade_level" required 
                                class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('grade_level') border-red-500 @enderror">
                            <option value="">اختر المستوى الدراسي</option>
                            @foreach($gradeLevels as $gradeLevel)
                                <option value="{{ $gradeLevel->id }}" {{ old('grade_level') == $gradeLevel->id ? 'selected' : '' }}>
                                    {{ $gradeLevel->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('grade_level')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Parent Phone -->
                    <div class="mb-4">
                        <label for="parent_phone" class="block text-sm font-medium text-gray-700 mb-2">
                            رقم هاتف ولي الأمر
                        </label>
                        <input id="parent_phone" name="parent_phone" type="tel" 
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('parent_phone') border-red-500 @enderror"
                               placeholder="أدخل رقم هاتف ولي الأمر (اختياري)"
                               value="{{ old('parent_phone') }}">
                        @error('parent_phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Account Security Section -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-shield-alt ml-2"></i>
                        أمان الحساب
                    </h3>
                    
                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            كلمة المرور *
                        </label>
                        <input id="password" name="password" type="password" required 
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password') border-red-500 @enderror"
                               placeholder="أدخل كلمة المرور (8 أحرف على الأقل)">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Confirmation -->
                    <div class="mb-4">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                            تأكيد كلمة المرور *
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required 
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="أعد إدخال كلمة المرور">
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        <span class="absolute left-0 inset-y-0 flex items-center pr-3">
                            <i class="fas fa-user-plus text-blue-500 group-hover:text-blue-400"></i>
                        </span>
                        إنشاء الحساب
                    </button>
                </div>

                <!-- Login Link -->
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        لديك حساب بالفعل؟
                        <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}" class="font-medium text-blue-600 hover:text-blue-500">
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