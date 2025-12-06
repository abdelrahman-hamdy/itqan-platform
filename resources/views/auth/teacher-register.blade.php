<x-auth.layout title="تسجيل معلم جديد" subtitle="انضم إلى فريقنا التعليمي المتميز" maxWidth="md" :academy="$academy">
    <form method="POST"
          action="{{ route('teacher.register.step1', ['subdomain' => request()->route('subdomain')]) }}"
          x-data="{ loading: false }"
          @submit="loading = true">
        @csrf

        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">
                اختر نوع التدريس
            </h3>

            <!-- Quran Teacher Option -->
            <label class="block mb-4 cursor-pointer group">
                <input type="radio" name="teacher_type" value="quran_teacher" class="peer sr-only" {{ old('teacher_type') == 'quran_teacher' ? 'checked' : '' }}>
                <div class="p-5 border-2 border-gray-200 rounded-button transition-smooth peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-300 hover:shadow-md">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-smooth">
                            <i class="ri-book-2-line text-green-600 text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-lg font-semibold text-gray-900 mb-1 flex items-center gap-2">
                                معلم القرآن الكريم
                                <div class="hidden peer-checked:block">
                                    <i class="ri-checkbox-circle-fill text-green-600"></i>
                                </div>
                            </h4>
                            <p class="text-sm text-gray-600 mb-3">تعليم القرآن الكريم وحفظه وتجويده</p>
                            <ul class="text-xs text-gray-500 space-y-1">
                                <li class="flex items-center gap-1.5">
                                    <i class="ri-checkbox-line text-green-600"></i>
                                    <span>تعليم القرآن الكريم وحفظه</span>
                                </li>
                                <li class="flex items-center gap-1.5">
                                    <i class="ri-checkbox-line text-green-600"></i>
                                    <span>التجويد وأحكام التلاوة</span>
                                </li>
                                <li class="flex items-center gap-1.5">
                                    <i class="ri-checkbox-line text-green-600"></i>
                                    <span>إجازات القرآن الكريم</span>
                                </li>
                                <li class="flex items-center gap-1.5">
                                    <i class="ri-checkbox-line text-green-600"></i>
                                    <span>حلقات القرآن الجماعية</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </label>

            <!-- Academic Teacher Option -->
            <label class="block cursor-pointer group">
                <input type="radio" name="teacher_type" value="academic_teacher" class="peer sr-only" {{ old('teacher_type') == 'academic_teacher' ? 'checked' : '' }}>
                <div class="p-5 border-2 border-gray-200 rounded-button transition-smooth peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-blue-300 hover:shadow-md">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-smooth">
                            <i class="ri-graduation-cap-line text-blue-600 text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-lg font-semibold text-gray-900 mb-1 flex items-center gap-2">
                                معلم المواد الأكاديمية
                                <div class="hidden peer-checked:block">
                                    <i class="ri-checkbox-circle-fill text-blue-600"></i>
                                </div>
                            </h4>
                            <p class="text-sm text-gray-600 mb-3">تعليم المواد الدراسية المختلفة</p>
                            <ul class="text-xs text-gray-500 space-y-1">
                                <li class="flex items-center gap-1.5">
                                    <i class="ri-checkbox-line text-blue-600"></i>
                                    <span>الرياضيات والعلوم</span>
                                </li>
                                <li class="flex items-center gap-1.5">
                                    <i class="ri-checkbox-line text-blue-600"></i>
                                    <span>اللغة العربية والإنجليزية</span>
                                </li>
                                <li class="flex items-center gap-1.5">
                                    <i class="ri-checkbox-line text-blue-600"></i>
                                    <span>المواد الاجتماعية</span>
                                </li>
                                <li class="flex items-center gap-1.5">
                                    <i class="ri-checkbox-line text-blue-600"></i>
                                    <span>الدروس الخصوصية</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </label>

            @error('teacher_type')
                <p class="mt-3 text-sm text-red-600 text-center flex items-center justify-center animate-shake">
                    <i class="ri-error-warning-line ml-1"></i>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            :disabled="loading"
            :class="{ 'btn-loading opacity-75': loading }"
            class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-gradient-to-r from-primary to-secondary text-white font-medium rounded-button hover:shadow-lg hover:-translate-y-0.5 transition-smooth disabled:cursor-not-allowed"
        >
            <span>التالي</span>
            <i class="ri-arrow-left-line text-lg"></i>
        </button>

        <!-- Login Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                لديك حساب بالفعل؟
                <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
                   class="font-medium text-primary hover:underline transition-smooth">
                    تسجيل الدخول
                </a>
            </p>
        </div>
    </form>
</x-auth.layout>
