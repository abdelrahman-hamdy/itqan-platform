<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $academy ? $academy->name . ' - ' : '' }}تسجيل معلم جديد - الخطوة 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap');
        body { font-family: 'Cairo', sans-serif; }
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
                    تسجيل حساب معلم جديد - الخطوة 2
                </p>
                <div class="mt-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                        {{ $teacherType === 'quran_teacher' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                        <i class="fas {{ $teacherType === 'quran_teacher' ? 'fa-quran' : 'fa-graduation-cap' }} ml-1"></i>
                        {{ $teacherType === 'quran_teacher' ? 'معلم القرآن الكريم' : 'معلم المواد الأكاديمية' }}
                    </span>
                </div>
            </div>

            <!-- Registration Form -->
            <form class="mt-8 space-y-6" method="POST" action="{{ route('teacher.register.step2.post', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}">
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
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('first_name') border-red-500 @enderror"
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
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('last_name') border-red-500 @enderror"
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
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('email') border-red-500 @enderror"
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
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('phone') border-red-500 @enderror"
                               placeholder="أدخل رقم الهاتف"
                               value="{{ old('phone') }}">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Professional Information Section -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-briefcase ml-2"></i>
                        المعلومات المهنية
                    </h3>
                    
                    <!-- Qualification Degree -->
                    <div class="mb-4">
                        <label for="qualification_degree" class="block text-sm font-medium text-gray-700 mb-2">
                            الدرجة العلمية *
                        </label>
                        <input id="qualification_degree" name="qualification_degree" type="text" required 
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('qualification_degree') border-red-500 @enderror"
                               placeholder="مثال: بكالوريوس، ماجستير، دكتوراه"
                               value="{{ old('qualification_degree') }}">
                        @error('qualification_degree')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- University -->
                    <div class="mb-4">
                        <label for="university" class="block text-sm font-medium text-gray-700 mb-2">
                            الجامعة *
                        </label>
                        <input id="university" name="university" type="text" required 
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('university') border-red-500 @enderror"
                               placeholder="اسم الجامعة"
                               value="{{ old('university') }}">
                        @error('university')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Years Experience -->
                    <div class="mb-4">
                        <label for="years_experience" class="block text-sm font-medium text-gray-700 mb-2">
                            سنوات الخبرة *
                        </label>
                        <input id="years_experience" name="years_experience" type="number" min="0" max="50" required 
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('years_experience') border-red-500 @enderror"
                               placeholder="عدد سنوات الخبرة"
                               value="{{ old('years_experience') }}">
                        @error('years_experience')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($teacherType === 'quran_teacher')
                        <!-- Quran Teacher Specific Fields -->
                        <div class="mb-4">
                            <label for="has_ijazah" class="block text-sm font-medium text-gray-700 mb-2">
                                هل لديك إجازة في القرآن الكريم؟ *
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="has_ijazah" value="1" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300" {{ old('has_ijazah') == '1' ? 'checked' : '' }}>
                                    <span class="mr-2 text-sm text-gray-700">نعم</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="has_ijazah" value="0" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300" {{ old('has_ijazah') == '0' ? 'checked' : '' }}>
                                    <span class="mr-2 text-sm text-gray-700">لا</span>
                                </label>
                            </div>
                            @error('has_ijazah')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4" id="ijazah_details_div" style="display: none;">
                            <label for="ijazah_details" class="block text-sm font-medium text-gray-700 mb-2">
                                تفاصيل الإجازة *
                            </label>
                            <textarea id="ijazah_details" name="ijazah_details" rows="3"
                                      class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('ijazah_details') border-red-500 @enderror"
                                      placeholder="أدخل تفاصيل إجازتك في القرآن الكريم">{{ old('ijazah_details') }}</textarea>
                            @error('ijazah_details')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <!-- Academic Teacher Specific Fields -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                المواد التي يمكنك تدريسها *
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="subjects[]" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('1', old('subjects', [])) ? 'checked' : '' }}>
                                    <span class="mr-2 text-sm text-gray-700">الرياضيات</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="subjects[]" value="2" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('2', old('subjects', [])) ? 'checked' : '' }}>
                                    <span class="mr-2 text-sm text-gray-700">العلوم</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="subjects[]" value="3" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('3', old('subjects', [])) ? 'checked' : '' }}>
                                    <span class="mr-2 text-sm text-gray-700">اللغة العربية</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="subjects[]" value="4" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('4', old('subjects', [])) ? 'checked' : '' }}>
                                    <span class="mr-2 text-sm text-gray-700">اللغة الإنجليزية</span>
                                </label>
                            </div>
                            @error('subjects')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                المستويات الدراسية *
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="grade_levels[]" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('1', old('grade_levels', [])) ? 'checked' : '' }}>
                                    <span class="mr-2 text-sm text-gray-700">المرحلة الابتدائية</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="grade_levels[]" value="2" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('2', old('grade_levels', [])) ? 'checked' : '' }}>
                                    <span class="mr-2 text-sm text-gray-700">المرحلة المتوسطة</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="grade_levels[]" value="3" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" {{ in_array('3', old('grade_levels', [])) ? 'checked' : '' }}>
                                    <span class="mr-2 text-sm text-gray-700">المرحلة الثانوية</span>
                                </label>
                            </div>
                            @error('grade_levels')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
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
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('password') border-red-500 @enderror"
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
                               class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                               placeholder="أعد إدخال كلمة المرور">
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                        <span class="absolute left-0 inset-y-0 flex items-center pr-3">
                            <i class="fas fa-user-plus text-green-500 group-hover:text-green-400"></i>
                        </span>
                        إرسال طلب التسجيل
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

    @if($teacherType === 'quran_teacher')
        <script>
            // Show/hide ijazah details based on selection
            document.addEventListener('DOMContentLoaded', function() {
                const hasIjazahRadios = document.querySelectorAll('input[name="has_ijazah"]');
                const ijazahDetailsDiv = document.getElementById('ijazah_details_div');
                const ijazahDetailsInput = document.getElementById('ijazah_details');

                function toggleIjazahDetails() {
                    const selectedValue = document.querySelector('input[name="has_ijazah"]:checked');
                    if (selectedValue && selectedValue.value === '1') {
                        ijazahDetailsDiv.style.display = 'block';
                        ijazahDetailsInput.required = true;
                    } else {
                        ijazahDetailsDiv.style.display = 'none';
                        ijazahDetailsInput.required = false;
                    }
                }

                hasIjazahRadios.forEach(radio => {
                    radio.addEventListener('change', toggleIjazahDetails);
                });

                // Initial state
                toggleIjazahDetails();
            });
        </script>
    @endif
</body>
</html> 