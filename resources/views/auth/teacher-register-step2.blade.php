<x-auth.layout title="{{ __('auth.register.teacher.step2.title') }}" subtitle="{{ __('auth.register.teacher.step2.subtitle') }}" maxWidth="lg" :academy="$academy">
    <!-- Teacher Type Badge -->
    <div class="mb-6 flex justify-center">
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium {{ $teacherType === 'quran_teacher' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
            <i class="ri-{{ $teacherType === 'quran_teacher' ? 'book-2' : 'graduation-cap' }}-line"></i>
            {{ $teacherType === 'quran_teacher' ? __('auth.register.teacher.step2.quran_teacher') : __('auth.register.teacher.step2.academic_teacher') }}
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
                <h3 class="text-lg font-semibold text-gray-900">{{ __('auth.register.teacher.step2.personal_info') }}</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-auth.input
                    label="{{ __('auth.register.student.first_name') }}"
                    name="first_name"
                    type="text"
                    icon="ri-user-line"
                    placeholder="{{ __('auth.register.student.first_name_placeholder') }}"
                    :value="old('first_name')"
                    :required="true"
                />

                <x-auth.input
                    label="{{ __('auth.register.student.last_name') }}"
                    name="last_name"
                    type="text"
                    icon="ri-user-line"
                    placeholder="{{ __('auth.register.student.last_name_placeholder') }}"
                    :value="old('last_name')"
                    :required="true"
                />
            </div>

            <x-auth.input
                label="{{ __('auth.register.student.email') }}"
                name="email"
                type="email"
                icon="ri-mail-line"
                placeholder="{{ __('common.placeholders.email_example') }}"
                :value="old('email')"
                :required="true"
                autocomplete="email"
            />

            <x-forms.phone-input
                name="phone"
                label="{{ __('auth.register.teacher.step2.phone') }}"
                :required="true"
                countryCodeField="phone_country_code"
                countryField="phone_country"
                initialCountry="sa"
                placeholder="{{ __('auth.register.teacher.step2.phone_placeholder') }}"
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
                <h3 class="text-lg font-semibold text-gray-900">{{ __('auth.register.teacher.step2.professional_info') }}</h3>
            </div>

            <!-- Education Level / Qualification -->
            <div class="mb-4">
                <label for="education_level" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('auth.register.teacher.step2.qualification') }}
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none text-gray-400">
                        <i class="ri-arrow-down-s-line text-lg"></i>
                    </div>
                    <select
                        id="education_level"
                        name="education_level"
                        required
                        class="appearance-none block w-full px-4 py-3 pe-10 border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 @error('education_level') border-red-500 ring-2 ring-red-200 @enderror"
                    >
                        <option value="">{{ __('auth.register.teacher.step2.qualification_placeholder') }}</option>
                        @foreach(\App\Enums\EducationalQualification::cases() as $qualification)
                            <option value="{{ $qualification->value }}" {{ old('education_level') == $qualification->value ? 'selected' : '' }}>
                                {{ $qualification->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('education_level')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-auth.input
                label="{{ __('auth.register.teacher.step2.university') }}"
                name="university"
                type="text"
                icon="ri-building-line"
                placeholder="{{ __('auth.register.teacher.step2.university_placeholder') }}"
                :value="old('university')"
                :required="true"
            />

            <x-auth.input
                label="{{ __('auth.register.teacher.step2.years_experience') }}"
                name="years_experience"
                type="number"
                icon="ri-time-line"
                placeholder="{{ __('auth.register.teacher.step2.years_experience_placeholder') }}"
                :value="old('years_experience')"
                :required="true"
            />

            @if($teacherType === 'quran_teacher')
                <!-- Quran Teacher Specific Fields -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('auth.register.teacher.step2.has_ijazah') }}
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
                            <span class="ms-3 text-sm text-gray-900">{{ __('auth.register.teacher.step2.has_ijazah_yes') }}</span>
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
                            <span class="ms-3 text-sm text-gray-900">{{ __('auth.register.teacher.step2.has_ijazah_no') }}</span>
                        </label>
                    </div>
                    @error('has_ijazah')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Ijazah Details (conditional) -->
                <div x-show="hasIjazah == '1'" x-cloak class="mb-4">
                    <label for="ijazah_details" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('auth.register.teacher.step2.ijazah_details') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute top-3 end-0 pe-3 flex items-start pointer-events-none text-gray-400">
                            <i class="ri-file-text-line text-lg"></i>
                        </div>
                        <textarea
                            id="ijazah_details"
                            name="ijazah_details"
                            rows="3"
                            :required="hasIjazah == '1'"
                            class="appearance-none block w-full px-4 py-3 pe-11 border border-gray-300 rounded-button text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-smooth @error('ijazah_details') border-red-500 ring-2 ring-red-200 @enderror"
                            placeholder="{{ __('auth.register.teacher.step2.ijazah_details_placeholder') }}"
                        >{{ old('ijazah_details') }}</textarea>
                    </div>
                    @error('ijazah_details')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
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
                        'sunday' => __('enums.week_days.sunday'),
                        'monday' => __('enums.week_days.monday'),
                        'tuesday' => __('enums.week_days.tuesday'),
                        'wednesday' => __('enums.week_days.wednesday'),
                        'thursday' => __('enums.week_days.thursday'),
                        'friday' => __('enums.week_days.friday'),
                        'saturday' => __('enums.week_days.saturday')
                    ];
                @endphp

                <!-- Subjects -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('auth.register.teacher.step2.subjects') }}
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
                                <span class="ms-2 text-sm text-gray-700">{{ $subject->name }}</span>
                            </label>
                        @empty
                            <p class="col-span-2 text-sm text-gray-500 text-center py-2">{{ __('auth.register.teacher.step2.no_subjects') }}</p>
                        @endforelse
                    </div>
                    @error('subjects')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Grade Levels -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('auth.register.teacher.step2.grade_levels') }}
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
                                <span class="ms-2 text-sm text-gray-700">{{ $gradeLevel->getDisplayName() }}</span>
                            </label>
                        @empty
                            <p class="col-span-2 text-sm text-gray-500 text-center py-2">{{ __('auth.register.teacher.step2.no_grade_levels') }}</p>
                        @endforelse
                    </div>
                    @error('grade_levels')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Available Days -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('auth.register.teacher.step2.available_days') }}
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
                                <span class="ms-2 text-sm text-gray-700">{{ $day }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('available_days')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
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
                <h3 class="text-lg font-semibold text-gray-900">{{ __('auth.register.teacher.step2.security') }}</h3>
            </div>

            <x-auth.input
                label="{{ __('auth.register.student.password') }}"
                name="password"
                type="password"
                icon="ri-lock-line"
                placeholder="{{ __('auth.register.student.password_placeholder') }}"
                :required="true"
                autocomplete="new-password"
                helperText="{{ __('auth.register.student.password_helper') }}"
            />

            <x-auth.input
                label="{{ __('auth.register.student.password_confirmation') }}"
                name="password_confirmation"
                type="password"
                icon="ri-lock-check-line"
                placeholder="{{ __('auth.register.student.password_confirmation_placeholder') }}"
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
            <span>{{ __('auth.register.teacher.step2.submit') }}</span>
        </button>

        <!-- Login Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                {{ __('auth.register.student.already_have_account') }}
                <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? request()->route('subdomain')]) }}"
                   class="font-medium text-primary hover:underline transition-smooth">
                    {{ __('auth.register.student.login_link') }}
                </a>
            </p>
        </div>
    </form>
</x-auth.layout>
