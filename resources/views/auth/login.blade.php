<x-auth.layout title="تسجيل الدخول" subtitle="مرحباً بعودتك! سجل دخولك للمتابعة" :academy="$academy">
    <!-- Login Form -->
    <form method="POST"
          action="{{ route('login.post', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
          x-data="{ loading: false }"
          @submit="loading = true">
        @csrf

        <div class="space-y-5">
            <!-- Email Input -->
            <x-auth.input
                label="البريد الإلكتروني"
                name="email"
                type="email"
                icon="ri-mail-line"
                placeholder="example@domain.com"
                :required="true"
                autocomplete="email"
            />

            <!-- Password Input -->
            <x-auth.input
                label="كلمة المرور"
                name="password"
                type="password"
                icon="ri-lock-line"
                placeholder="أدخل كلمة المرور"
                :required="true"
                autocomplete="current-password"
            />

            <!-- Remember Me and Forgot Password -->
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center cursor-pointer group">
                    <input type="checkbox"
                           name="remember"
                           class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-2 focus:ring-primary transition-smooth">
                    <span class="mr-2 text-gray-700 group-hover:text-primary transition-smooth">تذكرني</span>
                </label>

                <a href="{{ route('password.request', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
                   class="font-medium text-primary hover:underline transition-smooth">
                    نسيت كلمة المرور؟
                </a>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    :class="{ 'btn-loading': loading }"
                    :disabled="loading"
                    class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary to-secondary text-white font-medium rounded-button hover:shadow-lg hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-smooth disabled:opacity-70 disabled:cursor-not-allowed">
                <i class="ri-login-box-line text-lg"></i>
                <span>تسجيل الدخول</span>
            </button>
        </div>
    </form>

    <!-- Divider -->
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-white text-gray-500">أو</span>
        </div>
    </div>

    <!-- Registration Links -->
    <div class="grid grid-cols-1 gap-3">
        <!-- Student Registration -->
        <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
           class="flex items-center justify-center gap-2 px-6 py-3 bg-blue-50 text-blue-700 font-medium rounded-button hover:bg-blue-100 hover:shadow-md transition-smooth group">
            <i class="ri-user-add-line text-lg group-hover:scale-110 transition-smooth"></i>
            <span>تسجيل طالب جديد</span>
        </a>

        <!-- Teacher Registration -->
        <a href="{{ route('teacher.register', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
           class="flex items-center justify-center gap-2 px-6 py-3 bg-green-50 text-green-700 font-medium rounded-button hover:bg-green-100 hover:shadow-md transition-smooth group">
            <i class="ri-user-star-line text-lg group-hover:scale-110 transition-smooth"></i>
            <span>تسجيل معلم جديد</span>
        </a>

        <!-- Parent Registration -->
        <a href="{{ route('parent.register', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
           class="flex items-center justify-center gap-2 px-6 py-3 bg-purple-50 text-purple-700 font-medium rounded-button hover:bg-purple-100 hover:shadow-md transition-smooth group">
            <i class="ri-parent-line text-lg group-hover:scale-110 transition-smooth"></i>
            <span>تسجيل ولي أمر جديد</span>
        </a>
    </div>

    <x-slot name="footer">
        <p class="text-sm text-gray-600">
            جميع الحقوق محفوظة © {{ date('Y') }} {{ $academy ? $academy->name : 'منصة إتقان' }}
        </p>
    </x-slot>
</x-auth.layout>
