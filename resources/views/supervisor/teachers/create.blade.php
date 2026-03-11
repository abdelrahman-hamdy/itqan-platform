<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $days = [
        'sunday' => __('enums.week_days.sunday'),
        'monday' => __('enums.week_days.monday'),
        'tuesday' => __('enums.week_days.tuesday'),
        'wednesday' => __('enums.week_days.wednesday'),
        'thursday' => __('enums.week_days.thursday'),
        'friday' => __('enums.week_days.friday'),
        'saturday' => __('enums.week_days.saturday'),
    ];
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.teachers.page_title'), 'url' => route('manage.teachers.index', ['subdomain' => $subdomain])],
            ['label' => __('supervisor.teachers.add_teacher')],
        ]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.teachers.create_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.teachers.create_subtitle') }}</p>
    </div>

    {{-- Global Error --}}
    @if($errors->has('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-sm text-red-700">{{ $errors->first('error') }}</p>
        </div>
    @endif

    <form method="POST"
          action="{{ route('manage.teachers.store', ['subdomain' => $subdomain]) }}"
          x-data="{
              teacherType: '{{ old('teacher_type', 'quran_teacher') }}',
              certifications: @js(old('certifications', [])),
              newCert: '',
              showPass: false,
              showConfirm: false,
              loading: false,
              addCertification() {
                  const cert = this.newCert.trim();
                  if (cert && !this.certifications.includes(cert)) {
                      this.certifications.push(cert);
                  }
                  this.newCert = '';
              },
              removeCertification(index) {
                  this.certifications.splice(index, 1);
              }
          }"
          @submit="loading = true">
        @csrf

        <div class="max-w-3xl space-y-6">

            <!-- Teacher Type Selection -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-user-settings-line text-indigo-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('supervisor.teachers.teacher_type_select') }}</h3>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="teacher_type" value="quran_teacher" x-model="teacherType" class="sr-only peer">
                        <div class="flex items-center gap-3 p-4 border-2 rounded-xl transition-all peer-checked:border-green-500 peer-checked:bg-green-50 border-gray-200 hover:border-gray-300">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="ri-book-read-line text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ __('supervisor.teachers.teacher_type_quran') }}</p>
                            </div>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="teacher_type" value="academic_teacher" x-model="teacherType" class="sr-only peer">
                        <div class="flex items-center gap-3 p-4 border-2 rounded-xl transition-all peer-checked:border-violet-500 peer-checked:bg-violet-50 border-gray-200 hover:border-gray-300">
                            <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="ri-graduation-cap-line text-violet-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ __('supervisor.teachers.teacher_type_academic') }}</p>
                            </div>
                        </div>
                    </label>
                </div>
                @error('teacher_type')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Personal Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-user-line text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('supervisor.teachers.personal_info') }}</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- First Name --}}
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.student.first_name') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('first_name') border-red-500 @enderror"
                               placeholder="{{ __('auth.register.student.first_name_placeholder') }}">
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Last Name --}}
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.student.last_name') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('last_name') border-red-500 @enderror"
                               placeholder="{{ __('auth.register.student.last_name_placeholder') }}">
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.student.email') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-500 @enderror"
                               placeholder="{{ __('common.placeholders.email_example') }}">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.teacher.step2.phone') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('phone') border-red-500 @enderror"
                               placeholder="{{ __('auth.register.teacher.step2.phone_placeholder') }}">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Gender --}}
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.teacher.step2.gender') }} <span class="text-red-600">*</span>
                        </label>
                        <select name="gender" id="gender" required
                                class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('gender') border-red-500 @enderror">
                            <option value="">{{ __('auth.register.teacher.step2.gender_placeholder') }}</option>
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>{{ __('auth.register.teacher.step2.gender_male') }}</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ __('auth.register.teacher.step2.gender_female') }}</option>
                        </select>
                        @error('gender')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Professional Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-briefcase-line text-amber-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('supervisor.teachers.professional_info') }}</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Education Level --}}
                    <div>
                        <label for="education_level" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.teacher.step2.qualification') }} <span class="text-red-600">*</span>
                        </label>
                        <select name="education_level" id="education_level" required
                                class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('education_level') border-red-500 @enderror">
                            <option value="">{{ __('auth.register.teacher.step2.qualification_placeholder') }}</option>
                            @foreach(\App\Enums\EducationalQualification::cases() as $qualification)
                                <option value="{{ $qualification->value }}" {{ old('education_level') === $qualification->value ? 'selected' : '' }}>
                                    {{ $qualification->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('education_level')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- University --}}
                    <div>
                        <label for="university" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.teacher.step2.university') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="text" name="university" id="university" value="{{ old('university') }}" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('university') border-red-500 @enderror"
                               placeholder="{{ __('auth.register.teacher.step2.university_placeholder') }}">
                        @error('university')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Years Experience --}}
                    <div>
                        <label for="years_experience" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.teacher.step2.years_experience') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="number" name="years_experience" id="years_experience" value="{{ old('years_experience') }}" required
                               min="0" max="50"
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('years_experience') border-red-500 @enderror"
                               placeholder="{{ __('auth.register.teacher.step2.years_experience_placeholder') }}">
                        @error('years_experience')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Quran-specific: Certifications -->
            <div x-show="teacherType === 'quran_teacher'" x-cloak
                 class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-award-line text-green-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('auth.register.teacher.step2.certifications') }}</h3>
                </div>

                <p class="text-sm text-gray-500 mb-3">{{ __('auth.register.teacher.step2.certifications_helper') }}</p>

                <div class="flex flex-wrap gap-2 p-3 border border-gray-300 rounded-lg min-h-[48px] focus-within:ring-2 focus-within:ring-green-500/20 focus-within:border-green-500 transition-all @error('certifications') border-red-500 @enderror @error('certifications.*') border-red-500 @enderror">
                    <template x-for="(cert, index) in certifications" :key="index">
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                            <span x-text="cert"></span>
                            <button type="button" @click="removeCertification(index)" class="cursor-pointer hover:text-green-900 focus:outline-none">
                                <i class="ri-close-line"></i>
                            </button>
                        </span>
                    </template>
                    <input
                        type="text"
                        x-model="newCert"
                        @keydown.enter.prevent="addCertification()"
                        @keydown.comma.prevent="addCertification()"
                        class="flex-1 min-w-[200px] border-0 focus:ring-0 p-0 text-sm placeholder-gray-400 bg-transparent"
                        placeholder="{{ __('auth.register.teacher.step2.certifications_placeholder') }}"
                    >
                </div>

                <template x-for="(cert, index) in certifications" :key="'hidden-'+index">
                    <input type="hidden" name="certifications[]" :value="cert">
                </template>

                @error('certifications')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Academic-specific: Subjects, Grade Levels, Days -->
            <div x-show="teacherType === 'academic_teacher'" x-cloak
                 class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-book-open-line text-violet-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('supervisor.teachers.academic_details') }}</h3>
                </div>

                <div class="space-y-5">
                    {{-- Subjects --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('auth.register.teacher.step2.subjects') }} <span class="text-red-600">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            @forelse($subjects as $subject)
                                <label class="flex items-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                    <input type="checkbox" name="subjects[]" value="{{ $subject->id }}"
                                           class="h-4 w-4 text-violet-600 focus:ring-violet-500 border-gray-300 rounded"
                                           {{ in_array($subject->id, old('subjects', [])) ? 'checked' : '' }}>
                                    <span class="ms-2 text-sm text-gray-700">{{ $subject->name }}</span>
                                </label>
                            @empty
                                <p class="col-span-2 text-sm text-gray-500 text-center py-2">{{ __('auth.register.teacher.step2.no_subjects') }}</p>
                            @endforelse
                        </div>
                        @error('subjects')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Grade Levels --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('auth.register.teacher.step2.grade_levels') }} <span class="text-red-600">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            @forelse($gradeLevels as $gradeLevel)
                                <label class="flex items-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                    <input type="checkbox" name="grade_levels[]" value="{{ $gradeLevel->id }}"
                                           class="h-4 w-4 text-violet-600 focus:ring-violet-500 border-gray-300 rounded"
                                           {{ in_array($gradeLevel->id, old('grade_levels', [])) ? 'checked' : '' }}>
                                    <span class="ms-2 text-sm text-gray-700">{{ $gradeLevel->getDisplayName() }}</span>
                                </label>
                            @empty
                                <p class="col-span-2 text-sm text-gray-500 text-center py-2">{{ __('auth.register.teacher.step2.no_grade_levels') }}</p>
                            @endforelse
                        </div>
                        @error('grade_levels')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Available Days --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('auth.register.teacher.step2.available_days') }} <span class="text-red-600">*</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach($days as $key => $day)
                                <label class="flex items-center p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                    <input type="checkbox" name="available_days[]" value="{{ $key }}"
                                           class="h-4 w-4 text-violet-600 focus:ring-violet-500 border-gray-300 rounded"
                                           {{ in_array($key, old('available_days', [])) ? 'checked' : '' }}>
                                    <span class="ms-2 text-sm text-gray-700">{{ $day }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('available_days')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Password -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-shield-check-line text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('supervisor.teachers.security_info') }}</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.student.password') }} <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" name="password" id="password" required
                                   minlength="6"
                                   class="min-h-[44px] w-full px-3 py-2 pe-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('password') border-red-500 @enderror"
                                   placeholder="{{ __('supervisor.teachers.new_password_placeholder') }}">
                            <button type="button" @click="showPass = !showPass"
                                class="cursor-pointer absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                                <i :class="showPass ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('supervisor.teachers.confirm_password') }} <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" required
                                   minlength="6"
                                   class="min-h-[44px] w-full px-3 py-2 pe-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="{{ __('supervisor.teachers.confirm_password_placeholder') }}">
                            <button type="button" @click="showConfirm = !showConfirm"
                                class="cursor-pointer absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                                <i :class="showConfirm ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center gap-3">
                <button type="submit" :disabled="loading"
                    class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="ri-add-line" x-show="!loading"></i>
                    <i class="ri-loader-4-line animate-spin" x-show="loading" x-cloak></i>
                    {{ __('supervisor.teachers.add_teacher') }}
                </button>
                <a href="{{ route('manage.teachers.index', ['subdomain' => $subdomain]) }}"
                   class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                    {{ __('common.cancel') }}
                </a>
            </div>
        </div>
    </form>
</div>

</x-layouts.supervisor>
