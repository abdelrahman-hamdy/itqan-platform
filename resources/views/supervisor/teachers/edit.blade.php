<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $isQuran = $teacher->user_type === 'quran_teacher';
    $isAcademic = $teacher->user_type === 'academic_teacher';
    $profile = $isQuran ? $teacher->quranTeacherProfile : $teacher->academicTeacherProfile;

    $days = [
        'sunday' => __('enums.week_days.sunday'),
        'monday' => __('enums.week_days.monday'),
        'tuesday' => __('enums.week_days.tuesday'),
        'wednesday' => __('enums.week_days.wednesday'),
        'thursday' => __('enums.week_days.thursday'),
        'friday' => __('enums.week_days.friday'),
        'saturday' => __('enums.week_days.saturday'),
    ];

    $currentSubjects = $isAcademic ? ($teacher->academicTeacherProfile?->subject_ids ?? []) : [];
    $currentGradeLevels = $isAcademic ? ($teacher->academicTeacherProfile?->grade_level_ids ?? []) : [];
    $currentAvailableDays = $isAcademic ? ($teacher->academicTeacherProfile?->available_days ?? []) : [];
    $currentCertifications = $isQuran ? ($teacher->quranTeacherProfile?->certifications ?? []) : [];
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.teachers.page_title'), 'url' => route('manage.teachers.index', ['subdomain' => $subdomain])],
            ['label' => $teacher->name, 'url' => route('manage.teachers.show', ['subdomain' => $subdomain, 'teacher' => $teacher->id])],
            ['label' => __('supervisor.teachers.edit_teacher')],
        ]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.teachers.edit_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.teachers.edit_subtitle') }}</p>
    </div>

    {{-- Global Error --}}
    @if($errors->has('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-sm text-red-700">{{ $errors->first('error') }}</p>
        </div>
    @endif

    <form method="POST"
          action="{{ route('manage.teachers.update', ['subdomain' => $subdomain, 'teacher' => $teacher->id]) }}"
          enctype="multipart/form-data"
          x-data="{
              gender: '{{ old('gender', $profile?->gender ?? '') }}',
              certifications: @js(old('certifications', $currentCertifications)),
              newCert: '',
              loading: false,
              previewUrl: '',
              fileName: '',
              hasImage: false,
              currentAvatarUrl: '{{ $teacher->avatar ? asset('storage/' . $teacher->avatar) : '' }}',
              assetBase: '{{ asset('app-design-assets') }}',
              teacherType: '{{ $isQuran ? 'quran_teacher' : 'academic_teacher' }}',
              get defaultAvatarUrl() {
                  if (this.currentAvatarUrl && !this.previewUrl) return this.currentAvatarUrl;
                  const g = this.gender === 'female' ? 'female' : 'male';
                  const t = this.teacherType === 'quran_teacher' ? 'quran-teacher' : 'academic-teacher';
                  return this.assetBase + '/' + g + '-' + t + '-avatar.png';
              },
              get avatarBgClass() {
                  return this.teacherType === 'quran_teacher' ? 'bg-yellow-100' : 'bg-violet-100';
              },
              handleFileSelect(event) {
                  const file = event.target.files[0];
                  if (!file) return;
                  if (!file.type.startsWith('image/')) return;
                  if (file.size > 2 * 1024 * 1024) {
                      window.toast?.warning('{{ __('common.profile.image_size_warning') }}');
                      return;
                  }
                  this.fileName = file.name;
                  this.hasImage = true;
                  const reader = new FileReader();
                  reader.onload = (e) => { this.previewUrl = e.target.result; };
                  reader.readAsDataURL(file);
              },
              removeImage() {
                  this.previewUrl = '';
                  this.fileName = '';
                  this.hasImage = false;
                  document.getElementById('avatar').value = '';
              },
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
        @method('PUT')

        <div class="max-w-3xl mx-auto space-y-6">

            <!-- Teacher Type Badge (read-only) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 {{ $isQuran ? 'bg-green-100' : 'bg-violet-100' }} rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="{{ $isQuran ? 'ri-book-read-line text-green-600' : 'ri-graduation-cap-line text-violet-600' }}"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('supervisor.teachers.teacher_type_select') }}</p>
                        <p class="font-semibold text-gray-900">{{ $isQuran ? __('supervisor.teachers.teacher_type_quran') : __('supervisor.teachers.teacher_type_academic') }}</p>
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-user-line text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('supervisor.teachers.personal_info') }}</h3>
                </div>

                <!-- Avatar Upload -->
                <div class="mb-6 pb-6 border-b border-gray-200 flex justify-center">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative inline-block">
                            <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-white shadow-lg ring-2 ring-primary/20 relative"
                                 :class="avatarBgClass">
                                <img :src="previewUrl" alt="" class="w-full h-full object-cover relative z-10" x-show="previewUrl" x-cloak>
                                <div x-show="!previewUrl" class="absolute inset-0">
                                    <img :src="defaultAvatarUrl" alt=""
                                         class="absolute object-cover"
                                         style="width: 120%; height: 120%; top: 0; left: 50%; transform: translateX(-50%);">
                                </div>
                            </div>
                            <div class="absolute bottom-1 w-9 h-9 bg-primary text-white rounded-full flex items-center justify-center shadow-lg z-20" style="inset-inline-end: 0.25rem;">
                                <i class="ri-camera-line text-lg"></i>
                            </div>
                        </div>

                        <div class="mt-4">
                            <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden" @change="handleFileSelect">
                            <label for="avatar"
                                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white font-medium rounded-lg cursor-pointer hover:bg-primary-600 transition-all duration-200 text-sm">
                                <i class="ri-upload-2-line"></i>
                                <span x-text="hasImage ? '{{ __('common.profile.change_image') }}' : '{{ __('common.profile.add_image') }}'"></span>
                            </label>
                        </div>

                        <div x-show="fileName" class="mt-2 text-sm text-gray-600">
                            <i class="ri-file-image-line me-1"></i>
                            <span x-text="fileName"></span>
                        </div>

                        <div x-show="previewUrl" class="mt-2" x-cloak>
                            <button type="button" @click="removeImage" class="cursor-pointer text-sm text-red-600 hover:text-red-700 font-medium">
                                <i class="ri-delete-bin-line me-1"></i>
                                {{ __('common.profile.remove_image') }}
                            </button>
                        </div>

                        @error('avatar')
                            <div class="mt-2 text-sm text-red-600 bg-red-50 px-4 py-2 rounded-lg">
                                <i class="ri-error-warning-line me-1"></i>
                                {{ $message }}
                            </div>
                        @enderror

                        <p class="mt-2 text-xs text-gray-500">{{ __('common.profile.image_hint') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- First Name --}}
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.student.first_name') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $teacher->first_name) }}" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('first_name') border-red-500 @enderror">
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Last Name --}}
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.student.last_name') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $teacher->last_name) }}" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('last_name') border-red-500 @enderror">
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.student.email') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email', $teacher->email) }}" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <x-forms.phone-input
                            name="phone"
                            label="{{ __('auth.register.teacher.step2.phone') }}"
                            :required="true"
                            countryCodeField="phone_country_code"
                            countryField="phone_country"
                            initialCountry="sa"
                            :value="old('phone', $teacher->phone)"
                            :error="$errors->first('phone')"
                        />
                    </div>

                    {{-- Gender --}}
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('supervisor.teachers.gender_label') }} <span class="text-red-600">*</span>
                        </label>
                        <select name="gender" id="gender" required x-model="gender"
                                class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('gender') border-red-500 @enderror">
                            <option value="">{{ __('supervisor.teachers.gender_placeholder') }}</option>
                            <option value="male" {{ old('gender', $profile?->gender) === 'male' ? 'selected' : '' }}>{{ __('supervisor.teachers.gender_male_teacher') }}</option>
                            <option value="female" {{ old('gender', $profile?->gender) === 'female' ? 'selected' : '' }}>{{ __('supervisor.teachers.gender_female_teacher') }}</option>
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
                                <option value="{{ $qualification->value }}"
                                    {{ old('education_level', $isQuran ? ($profile?->educational_qualification ?? '') : ($profile?->education_level ?? '')) === $qualification->value ? 'selected' : '' }}>
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
                        <input type="text" name="university" id="university" value="{{ old('university', $isQuran ? ($profile?->university ?? $teacher->university) : $profile?->university) }}" required
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('university') border-red-500 @enderror">
                        @error('university')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Years Experience --}}
                    <div>
                        <label for="years_experience" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('auth.register.teacher.step2.years_experience') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="number" name="years_experience" id="years_experience"
                               value="{{ old('years_experience', $profile?->teaching_experience_years) }}" required
                               min="0" max="50"
                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('years_experience') border-red-500 @enderror">
                        @error('years_experience')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Quran-specific: Certifications --}}
            @if($isQuran)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="ri-award-line text-green-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('auth.register.teacher.step2.certifications') }}</h3>
                </div>

                <p class="text-sm text-gray-500 mb-3">{{ __('auth.register.teacher.step2.certifications_helper') }}</p>

                <div class="flex flex-wrap gap-2 p-3 border border-gray-300 rounded-lg min-h-[48px] focus-within:ring-2 focus-within:ring-green-500/20 focus-within:border-green-500 transition-all">
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
            </div>
            @endif

            {{-- Academic-specific: Subjects, Grade Levels, Days --}}
            @if($isAcademic)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
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
                                           {{ in_array($subject->id, old('subjects', $currentSubjects)) ? 'checked' : '' }}>
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
                                           {{ in_array($gradeLevel->id, old('grade_levels', $currentGradeLevels)) ? 'checked' : '' }}>
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
                                           {{ in_array($key, old('available_days', $currentAvailableDays)) ? 'checked' : '' }}>
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
            @endif

            <!-- Submit -->
            <div class="flex items-center gap-3">
                <button type="submit" :disabled="loading"
                    class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="ri-save-line" x-show="!loading"></i>
                    <i class="ri-loader-4-line animate-spin" x-show="loading" x-cloak></i>
                    {{ __('common.save') }}
                </button>
                <a href="{{ route('manage.teachers.show', ['subdomain' => $subdomain, 'teacher' => $teacher->id]) }}"
                   class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                    {{ __('common.cancel') }}
                </a>
            </div>
        </div>
    </form>
</div>

</x-layouts.supervisor>
