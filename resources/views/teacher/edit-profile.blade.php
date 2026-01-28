@php
  $isQuranTeacher = auth()->user()->isQuranTeacher();
  $isAcademicTeacher = auth()->user()->isAcademicTeacher();
@endphp

<x-layouts.teacher title="{{ auth()->user()->academy->name ?? __('teacher.edit_profile.academy_name') }} - {{ __('teacher.edit_profile.edit_teacher_profile') }}">
  <x-slot name="description">{{ __('teacher.edit_profile.edit_teacher_profile') }} - {{ auth()->user()->academy->name ?? __('teacher.edit_profile.academy_name') }}</x-slot>

  <x-profile.form-wrapper
      title="{{ __('teacher.edit_profile.title') }}"
      description="{{ __('teacher.edit_profile.description') }}"
      :action="route('teacher.profile.update', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
      method="PUT"
      enctype="multipart/form-data"
      :backRoute="route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])">

      <!-- Profile Picture Upload -->
      <x-profile.picture-upload
          :currentAvatar="$teacherProfile->avatar ?? null"
          :userName="($teacherProfile->first_name ?? '') . ' ' . ($teacherProfile->last_name ?? '')" />

      <!-- Personal Information Section -->
      <div class="mb-6 md:mb-8">
        <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 pb-2 border-b border-gray-200">{{ __('teacher.edit_profile.personal_info') }}</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
          <!-- First Name -->
          <x-profile.text-input
              label="{{ __('teacher.edit_profile.first_name') }}"
              name="first_name"
              :value="$teacherProfile->first_name ?? auth()->user()->first_name"
              required />

          <!-- Last Name -->
          <x-profile.text-input
              label="{{ __('teacher.edit_profile.last_name') }}"
              name="last_name"
              :value="$teacherProfile->last_name ?? auth()->user()->last_name"
              required />

          <!-- Email (readonly) -->
          <x-profile.text-input
              label="{{ __('teacher.edit_profile.email') }}"
              name="email"
              type="email"
              :value="$teacherProfile->email ?? auth()->user()->email"
              readonly />

          <!-- Phone -->
          <div>
            <x-forms.phone-input
                name="phone"
                :label="__('teacher.edit_profile.phone')"
                :required="false"
                countryCodeField="phone_country_code"
                countryField="phone_country"
                initialCountry="sa"
                :value="$teacherProfile->phone ?? auth()->user()->phone"
                :error="$errors->first('phone')"
            />
          </div>
        </div>
      </div>

      <!-- Qualifications Section -->
      <div class="mb-6 md:mb-8">
        <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 pb-2 border-b border-gray-200">{{ __('teacher.edit_profile.qualifications') }}</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
          <!-- Educational Qualification (Quran Teacher) -->
          @if($isQuranTeacher)
            <x-profile.select-input
                label="{{ __('teacher.edit_profile.educational_qualification') }}"
                name="educational_qualification"
                :value="$teacherProfile->educational_qualification ?? 'bachelor'"
                :options="\App\Enums\EducationalQualification::options()"
                placeholder="{{ __('teacher.edit_profile.choose_qualification') }}" />
          @endif

          <!-- Education Level (Academic Teacher) -->
          @if($isAcademicTeacher)
            <x-profile.select-input
                label="{{ __('teacher.edit_profile.educational_qualification') }}"
                name="education_level"
                :value="$teacherProfile->education_level?->value ?? 'bachelor'"
                :options="\App\Enums\EducationalQualification::options()"
                placeholder="{{ __('teacher.edit_profile.choose_qualification') }}" />

            <!-- University -->
            <x-profile.text-input
                label="{{ __('teacher.edit_profile.university') }}"
                name="university"
                :value="$teacherProfile->university ?? ''" />
          @endif

          <!-- Teaching Experience Years -->
          <x-profile.text-input
              label="{{ __('teacher.edit_profile.teaching_experience_years') }}"
              name="teaching_experience_years"
              type="number"
              :value="$teacherProfile->teaching_experience_years ?? 0" />
        </div>

        <!-- Certifications -->
        <div class="mt-4 md:mt-6">
          <x-profile.tags-input
              label="{{ __('teacher.edit_profile.certifications') }}"
              name="certifications"
              :value="$teacherProfile->certifications ?? []"
              placeholder="{{ __('teacher.edit_profile.certifications_placeholder') }}" />
        </div>

        <!-- Languages -->
        <div class="mt-4 md:mt-6">
          @php
            $languageOptions = \App\Enums\TeachingLanguage::toArray();
            $selectedLanguages = old('languages', $teacherProfile->languages ?? ['arabic']);
          @endphp

          <x-profile.checkbox-group
              label="{{ __('teacher.edit_profile.languages') }}"
              name="languages"
              :options="$languageOptions"
              :selected="$selectedLanguages"
              :columns="2" />
        </div>
      </div>

      <!-- Availability Section -->
      <div class="mb-6 md:mb-8">
        <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 pb-2 border-b border-gray-200">{{ __('teacher.edit_profile.teaching_hours') }}</h3>

        <!-- Available Days -->
        <div class="mb-4 md:mb-6">
          @php
            $daysOptions = \App\Enums\WeekDays::options();
            $selectedDays = old('available_days', is_array($teacherProfile->available_days) ? $teacherProfile->available_days : []);
          @endphp

          <x-profile.checkbox-group
              label="{{ __('teacher.edit_profile.available_days') }}"
              name="available_days"
              :options="$daysOptions"
              :selected="$selectedDays"
              :columns="2" />
        </div>

        <!-- Available Hours -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
          <x-profile.text-input
              label="{{ __('teacher.edit_profile.from_hour') }}"
              name="available_time_start"
              type="time"
              :value="$teacherProfile->available_time_start ? \Carbon\Carbon::parse($teacherProfile->available_time_start)->format('H:i') : '08:00'" />

          <x-profile.text-input
              label="{{ __('teacher.edit_profile.to_hour') }}"
              name="available_time_end"
              type="time"
              :value="$teacherProfile->available_time_end ? \Carbon\Carbon::parse($teacherProfile->available_time_end)->format('H:i') : '18:00'" />
        </div>
      </div>

      <!-- Bio Section -->
      <div class="mb-6 md:mb-8">
        <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 pb-2 border-b border-gray-200">{{ __('teacher.edit_profile.bio') }}</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
          <!-- Bio Arabic -->
          <x-profile.textarea-input
              label="{{ __('teacher.edit_profile.bio_arabic') }}"
              name="bio_arabic"
              :value="$teacherProfile->bio_arabic ?? ''"
              :rows="4"
              placeholder="{{ __('teacher.edit_profile.bio_arabic_placeholder') }}" />

          <!-- Bio English -->
          <x-profile.textarea-input
              label="{{ __('teacher.edit_profile.bio_english') }}"
              name="bio_english"
              :value="$teacherProfile->bio_english ?? ''"
              :rows="4"
              placeholder="{{ __('teacher.edit_profile.bio_english_placeholder') }}" />
        </div>
      </div>

      <!-- Preview Video Section -->
      <div class="mb-6 md:mb-8" x-data="{ removeVideo: false }">
        <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 pb-2 border-b border-gray-200">فيديو تعريفي</h3>
        <p class="text-sm text-gray-500 mb-4">فيديو تعريفي قصير يظهر في صفحة الملف الشخصي للطلاب (اختياري) - الحد الأقصى 50 ميجابايت</p>

        @if($teacherProfile->preview_video)
          <div class="mb-4" x-show="!removeVideo">
            <label class="block text-sm font-medium text-gray-700 mb-2">الفيديو الحالي</label>
            <video controls class="w-full max-w-lg rounded-lg border border-gray-200" preload="metadata">
              <source src="{{ asset('storage/' . $teacherProfile->preview_video) }}">
            </video>
            <button type="button"
                    @click="removeVideo = true"
                    class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
              <i class="ri-delete-bin-line"></i>
              حذف الفيديو
            </button>
          </div>
          <div x-show="removeVideo" class="mb-4 bg-red-50 text-red-700 px-4 py-3 rounded-lg flex items-center justify-between">
            <span class="text-sm">سيتم حذف الفيديو عند حفظ التعديلات</span>
            <button type="button" @click="removeVideo = false" class="text-sm font-medium text-red-800 hover:underline">تراجع</button>
          </div>
          <input type="hidden" name="remove_preview_video" :value="removeVideo ? '1' : '0'">
        @endif

        <div>
          <label for="preview_video" class="block text-sm font-medium text-gray-700 mb-2">{{ $teacherProfile->preview_video ? 'استبدال الفيديو' : 'رفع فيديو تعريفي' }}</label>
          <input type="file"
                 id="preview_video"
                 name="preview_video"
                 accept="video/mp4,video/webm,video/quicktime"
                 class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-secondary">
          <p class="text-xs text-gray-400 mt-1">الصيغ المدعومة: MP4, WebM, MOV</p>
        </div>

        @error('preview_video')
          <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
      </div>

  </x-profile.form-wrapper>

</x-layouts.teacher>
