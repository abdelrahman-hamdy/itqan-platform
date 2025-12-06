<x-auth.layout title="تسجيل طالب جديد" subtitle="انضم إلينا وابدأ رحلتك التعليمية" maxWidth="lg" :academy="$academy">
    <form method="POST"
          action="{{ route('student.register.post', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
          x-data="{ loading: false }"
          @submit="loading = true">
        @csrf

        <!-- Personal Information Section -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                    <i class="ri-user-line text-primary text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">المعلومات الشخصية</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-auth.input
                    label="الاسم الأول"
                    name="first_name"
                    type="text"
                    icon="ri-user-line"
                    placeholder="أدخل الاسم الأول"
                    :value="old('first_name')"
                    :required="true"
                />

                <x-auth.input
                    label="اسم العائلة"
                    name="last_name"
                    type="text"
                    icon="ri-user-line"
                    placeholder="أدخل اسم العائلة"
                    :value="old('last_name')"
                    :required="true"
                />
            </div>

            <x-auth.input
                label="البريد الإلكتروني"
                name="email"
                type="email"
                icon="ri-mail-line"
                placeholder="example@domain.com"
                :value="old('email')"
                :required="true"
                autocomplete="email"
            />

            <x-forms.phone-input
                name="phone"
                label="رقم الهاتف"
                :required="true"
                countryCodeField="phone_country_code"
                initialCountry="sa"
                placeholder="أدخل رقم الهاتف"
                :value="old('phone')"
                :error="$errors->first('phone')"
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Birth Date -->
                <div class="mb-4" x-data="{ focused: false }">
                    <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-2">
                        تاريخ الميلاد
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
                             :class="{ 'text-primary': focused, 'text-gray-400': !focused }">
                            <i class="ri-calendar-line text-lg transition-smooth"></i>
                        </div>
                        <input
                            type="date"
                            id="birth_date"
                            name="birth_date"
                            required
                            @focus="focused = true"
                            @blur="focused = false"
                            class="input-field appearance-none block w-full px-4 py-3 pr-11 border border-gray-300 rounded-button text-gray-900 focus:outline-none transition-smooth @error('birth_date') border-red-500 ring-2 ring-red-200 @enderror"
                            style="height: 48px;"
                            value="{{ old('birth_date') }}"
                        >
                    </div>
                    @error('birth_date')
                        <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
                            <i class="ri-error-warning-line ml-1"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Gender -->
                <div class="mb-4" x-data="{ focused: false }">
                    <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                        الجنس
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
                             :class="{ 'text-primary': focused, 'text-gray-400': !focused }">
                            <i class="ri-genderless-line text-lg transition-smooth"></i>
                        </div>
                        <select
                            id="gender"
                            name="gender"
                            required
                            @focus="focused = true"
                            @blur="focused = false"
                            class="appearance-none block w-full px-4 py-3 pr-11 border border-gray-300 rounded-button text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-smooth @error('gender') border-red-500 ring-2 ring-red-200 @enderror"
                        >
                            <option value="">اختر الجنس</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>ذكر</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>أنثى</option>
                        </select>
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-arrow-down-s-line text-gray-400"></i>
                        </div>
                    </div>
                    @error('gender')
                        <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
                            <i class="ri-error-warning-line ml-1"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <!-- Nationality -->
            <div class="mb-4" x-data="{ focused: false }">
                <label for="nationality" class="block text-sm font-medium text-gray-700 mb-2">
                    الجنسية
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
                         :class="{ 'text-primary': focused, 'text-gray-400': !focused }">
                        <i class="ri-flag-line text-lg transition-smooth"></i>
                    </div>
                    <select
                        id="nationality"
                        name="nationality"
                        required
                        @focus="focused = true"
                        @blur="focused = false"
                        class="appearance-none block w-full px-4 py-3 pr-11 border border-gray-300 rounded-button text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-smooth @error('nationality') border-red-500 ring-2 ring-red-200 @enderror"
                    >
                        <option value="">اختر الجنسية</option>
                        @foreach($countries as $code => $name)
                            <option value="{{ $code }}" {{ old('nationality') == $code ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-arrow-down-s-line text-gray-400"></i>
                    </div>
                </div>
                @error('nationality')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
                        <i class="ri-error-warning-line ml-1"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>

        <!-- Academic Information Section -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                    <i class="ri-graduation-cap-line text-primary text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">المعلومات الدراسية</h3>
            </div>

            <!-- Grade Level -->
            <div class="mb-4" x-data="{ focused: false }">
                <label for="grade_level" class="block text-sm font-medium text-gray-700 mb-2">
                    المستوى الدراسي
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
                         :class="{ 'text-primary': focused, 'text-gray-400': !focused }">
                        <i class="ri-book-line text-lg transition-smooth"></i>
                    </div>
                    <select
                        id="grade_level"
                        name="grade_level"
                        required
                        @focus="focused = true"
                        @blur="focused = false"
                        class="appearance-none block w-full px-4 py-3 pr-11 border border-gray-300 rounded-button text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-smooth @error('grade_level') border-red-500 ring-2 ring-red-200 @enderror"
                    >
                        <option value="">اختر المستوى الدراسي</option>
                        @foreach($gradeLevels as $gradeLevel)
                            <option value="{{ $gradeLevel->id }}" {{ old('grade_level') == $gradeLevel->id ? 'selected' : '' }}>
                                {{ $gradeLevel->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-arrow-down-s-line text-gray-400"></i>
                    </div>
                </div>
                @error('grade_level')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
                        <i class="ri-error-warning-line ml-1"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <x-forms.phone-input
                name="parent_phone"
                label="رقم هاتف ولي الأمر"
                :required="false"
                countryCodeField="parent_phone_country_code"
                initialCountry="sa"
                placeholder="أدخل رقم هاتف ولي الأمر (اختياري)"
                :value="old('parent_phone')"
                :error="$errors->first('parent_phone')"
                helperText="سيتم استخدامه للتواصل في حالات الطوارئ"
            />
        </div>

        <!-- Account Security Section -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                    <i class="ri-shield-check-line text-primary text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">أمان الحساب</h3>
            </div>

            <x-auth.input
                label="كلمة المرور"
                name="password"
                type="password"
                icon="ri-lock-line"
                placeholder="أدخل كلمة المرور (8 أحرف على الأقل)"
                :required="true"
                autocomplete="new-password"
                helperText="يجب أن تحتوي على 8 أحرف على الأقل"
            />

            <x-auth.input
                label="تأكيد كلمة المرور"
                name="password_confirmation"
                type="password"
                icon="ri-lock-check-line"
                placeholder="أعد إدخال كلمة المرور"
                :required="true"
                autocomplete="new-password"
            />
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            :disabled="loading"
            :class="{ 'btn-loading opacity-75': loading }"
            class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-gradient-to-r from-primary to-secondary text-white font-medium rounded-button hover:shadow-lg hover:-translate-y-0.5 transition-smooth disabled:cursor-not-allowed"
        >
            <i class="ri-user-add-line text-lg"></i>
            <span>إنشاء الحساب</span>
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
