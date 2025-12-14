<x-auth.layout title="إعادة تعيين كلمة المرور" subtitle="أدخل كلمة المرور الجديدة" :academy="$academy">
    <!-- Reset Password Form -->
    <form method="POST"
          action="{{ route('password.update', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
          x-data="{ loading: false, showPassword: false, showConfirmPassword: false }"
          @submit="loading = true">
        @csrf

        <!-- Hidden Fields -->
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="space-y-5">
            <!-- Email Display (Read-only) -->
            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-mail-line text-gray-500 text-lg ml-3"></i>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">البريد الإلكتروني</p>
                        <p class="text-sm font-medium text-gray-800">{{ $email }}</p>
                    </div>
                </div>
            </div>

            <!-- New Password Input -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    كلمة المرور الجديدة <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <i class="ri-lock-line text-gray-400"></i>
                    </div>
                    <input :type="showPassword ? 'text' : 'password'"
                           name="password"
                           required
                           minlength="8"
                           autocomplete="new-password"
                           placeholder="أدخل كلمة المرور الجديدة"
                           class="input-field w-full pr-10 pl-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition-smooth @error('password') border-red-500 @enderror">
                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 hover:text-gray-600">
                        <i :class="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">يجب أن تكون كلمة المرور 8 أحرف على الأقل</p>
            </div>

            <!-- Confirm Password Input -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    تأكيد كلمة المرور <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <i class="ri-lock-line text-gray-400"></i>
                    </div>
                    <input :type="showConfirmPassword ? 'text' : 'password'"
                           name="password_confirmation"
                           required
                           minlength="8"
                           autocomplete="new-password"
                           placeholder="أعد إدخال كلمة المرور"
                           class="input-field w-full pr-10 pl-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary transition-smooth">
                    <button type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 hover:text-gray-600">
                        <i :class="showConfirmPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    :class="{ 'btn-loading': loading }"
                    :disabled="loading"
                    class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary to-secondary text-white font-medium rounded-button hover:shadow-lg hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-smooth disabled:opacity-70 disabled:cursor-not-allowed">
                <i class="ri-lock-password-line text-lg"></i>
                <span>تعيين كلمة المرور الجديدة</span>
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

    <!-- Back to Login -->
    <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
       class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-button hover:bg-gray-200 transition-smooth">
        <i class="ri-arrow-right-line text-lg"></i>
        <span>العودة لتسجيل الدخول</span>
    </a>

    <x-slot name="footer">
        <p class="text-sm text-gray-600">
            جميع الحقوق محفوظة © {{ date('Y') }} {{ $academy ? $academy->name : 'منصة إتقان' }}
        </p>
    </x-slot>
</x-auth.layout>
