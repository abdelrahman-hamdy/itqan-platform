<x-auth.layout title="تسجيل معلم جديد" subtitle="أكمل بياناتك لإنهاء التسجيل" maxWidth="lg" :academy="$academy">
    <!-- Teacher Type Badge -->
    <div class="mb-6 flex justify-center">
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium {{ $teacherType === 'quran_teacher' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
            <i class="ri-{{ $teacherType === 'quran_teacher' ? 'book-2' : 'graduation-cap' }}-line"></i>
            {{ $teacherType === 'quran_teacher' ? 'معلم القرآن الكريم' : 'معلم المواد الأكاديمية' }}
        </span>
    </div>

    <form method="POST"
          action="{{ route('teacher.register.step2.post', ['subdomain' => request()->route('subdomain')]) }}"
          x-data="{
              loading: false,
              hasIjazah: {{ old('has_ijazah', '0') }}
          }"
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
        </div>

        <!-- Professional Information Section -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                    <i class="ri-briefcase-line text-primary text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">المعلومات المهنية</h3>
            </div>

            <!-- Education Level / Qualification -->
            <div class="mb-4" x-data="{ focused: false }">
                <label for="education_level" class="block text-sm font-medium text-gray-700 mb-2">
                    المؤهل التعليمي
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
                         :class="{ 'text-primary': focused, 'text-gray-400': !focused }">
                        <i class="ri-medal-line text-lg transition-smooth"></i>
                    </div>
                    <select
                        id="education_level"
                        name="education_level"
                        required
                        @focus="focused = true"
                        @blur="focused = false"
                        class="appearance-none block w-full px-4 py-3 pr-11 border border-gray-300 rounded-button text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-smooth @error('education_level') border-red-500 ring-2 ring-red-200 @enderror"
                    >
                        <option value="">اختر المؤهل التعليمي</option>
                        <option value="diploma" {{ old('education_level') == 'diploma' ? 'selected' : '' }}>دبلوم</option>
                        <option value="bachelor" {{ old('education_level') == 'bachelor' ? 'selected' : '' }}>بكالوريوس</option>
                        <option value="master" {{ old('education_level') == 'master' ? 'selected' : '' }}>ماجستير</option>
                        <option value="phd" {{ old('education_level') == 'phd' ? 'selected' : '' }}>دكتوراه</option>
                        <option value="other" {{ old('education_level') == 'other' ? 'selected' : '' }}>أخرى</option>
                    </select>
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-arrow-down-s-line text-gray-400"></i>
                    </div>
                </div>
                @error('education_level')
                    <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
                        <i class="ri-error-warning-line ml-1"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <x-auth.input
                label="الجامعة"
                name="university"
                type="text"
                icon="ri-building-line"
                placeholder="اسم الجامعة"
                :value="old('university')"
                :required="true"
            />

            <x-auth.input
                label="سنوات الخبرة"
                name="years_experience"
                type="number"
                icon="ri-time-line"
                placeholder="عدد سنوات الخبرة"
                :value="old('years_experience')"
                :required="true"
            />

            @if($teacherType === 'quran_teacher')
                <!-- Quran Teacher Specific Fields -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        هل لديك إجازة في القرآن الكريم؟
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center p-3 border border-gray-200 rounded-button cursor-pointer hover:bg-gray-50 transition-smooth">
                            <input
                                type="radio"
                                name="has_ijazah"
                                value="1"
                                x-model="hasIjazah"
                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
                                {{ old('has_ijazah') == '1' ? 'checked' : '' }}
                            >
                            <span class="mr-3 text-sm text-gray-900">نعم، لدي إجازة في القرآن الكريم</span>
                        </label>
                        <label class="flex items-center p-3 border border-gray-200 rounded-button cursor-pointer hover:bg-gray-50 transition-smooth">
                            <input
                                type="radio"
                                name="has_ijazah"
                                value="0"
                                x-model="hasIjazah"
                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
                                {{ old('has_ijazah') == '0' ? 'checked' : '' }}
                            >
                            <span class="mr-3 text-sm text-gray-900">لا، ليس لدي إجازة</span>
                        </label>
                    </div>
                    @error('has_ijazah')
                        <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
                            <i class="ri-error-warning-line ml-1"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Ijazah Details (conditional) -->
                <div x-show="hasIjazah == '1'" x-cloak class="mb-4">
                    <label for="ijazah_details" class="block text-sm font-medium text-gray-700 mb-2">
                        تفاصيل الإجازة
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute top-3 right-0 pr-3 flex items-start pointer-events-none text-gray-400">
                            <i class="ri-file-text-line text-lg"></i>
                        </div>
                        <textarea
                            id="ijazah_details"
                            name="ijazah_details"
                            rows="3"
                            :required="hasIjazah == '1'"
                            class="appearance-none block w-full px-4 py-3 pr-11 border border-gray-300 rounded-button text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-smooth @error('ijazah_details') border-red-500 ring-2 ring-red-200 @enderror"
                            placeholder="أدخل تفاصيل إجازتك في القرآن الكريم (الشيخ، السند، التاريخ...)"
                        >{{ old('ijazah_details') }}</textarea>
                    </div>
                    @error('ijazah_details')
                        <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
                            <i class="ri-error-warning-line ml-1"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            @else
                <!-- Academic Teacher Specific Fields -->
                @php
                    $subjects = \App\Models\AcademicSubject::where('academy_id', $academy->id)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get();
                    $gradeLevels = \App\Models\AcademicGradeLevel::where('academy_id', $academy->id)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get();
                    $days = [
                        'sunday' => 'الأحد',
                        'monday' => 'الاثنين',
                        'tuesday' => 'الثلاثاء',
                        'wednesday' => 'الأربعاء',
                        'thursday' => 'الخميس',
                        'friday' => 'الجمعة',
                        'saturday' => 'السبت'
                    ];
                @endphp

                <!-- Subjects -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        المواد التي يمكنك تدريسها
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        @forelse($subjects as $subject)
                            <label class="flex items-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-smooth">
                                <input
                                    type="checkbox"
                                    name="subjects[]"
                                    value="{{ $subject->id }}"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    {{ in_array($subject->id, old('subjects', [])) ? 'checked' : '' }}
                                >
                                <span class="mr-2 text-sm text-gray-700">{{ $subject->name }}</span>
                            </label>
                        @empty
                            <p class="col-span-2 text-sm text-gray-500 text-center py-2">لا توجد مواد دراسية متاحة حالياً</p>
                        @endforelse
                    </div>
                    @error('subjects')
                        <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
                            <i class="ri-error-warning-line ml-1"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Grade Levels -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        الصفوف الدراسية
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        @forelse($gradeLevels as $gradeLevel)
                            <label class="flex items-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-smooth">
                                <input
                                    type="checkbox"
                                    name="grade_levels[]"
                                    value="{{ $gradeLevel->id }}"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    {{ in_array($gradeLevel->id, old('grade_levels', [])) ? 'checked' : '' }}
                                >
                                <span class="mr-2 text-sm text-gray-700">{{ $gradeLevel->name }}</span>
                            </label>
                        @empty
                            <p class="col-span-2 text-sm text-gray-500 text-center py-2">لا توجد صفوف دراسية متاحة حالياً</p>
                        @endforelse
                    </div>
                    @error('grade_levels')
                        <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
                            <i class="ri-error-warning-line ml-1"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Available Days -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        الأيام المتاحة
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($days as $key => $day)
                            <label class="flex items-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-smooth">
                                <input
                                    type="checkbox"
                                    name="available_days[]"
                                    value="{{ $key }}"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    {{ in_array($key, old('available_days', [])) ? 'checked' : '' }}
                                >
                                <span class="mr-2 text-sm text-gray-700">{{ $day }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('available_days')
                        <p class="mt-1.5 text-sm text-red-600 flex items-center animate-shake">
                            <i class="ri-error-warning-line ml-1"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            @endif
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
            <i class="ri-send-plane-line text-lg"></i>
            <span>إرسال طلب التسجيل</span>
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
