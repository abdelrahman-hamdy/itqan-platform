<x-layouts.student title="{{ auth()->user()->academy->name ?? __('student.common.academy_default') }} - {{ __('student.profile.edit_profile_title') }}">
  <x-slot name="description">{{ __('student.profile.page_title') }} - {{ auth()->user()->academy->name ?? __('student.common.academy_default') }}</x-slot>

  <x-profile.form-wrapper
      title="{{ __('student.profile.edit_profile_title') }}"
      description="{{ __('student.profile.edit_profile_description') }}"
      :action="route('student.profile.update', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
      method="PUT"
      enctype="multipart/form-data"
      :backRoute="route('student.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])">

      <!-- Profile Picture Upload -->
      <x-profile.picture-upload
          :currentAvatar="$studentProfile?->avatar"
          :userName="($studentProfile?->first_name ?? '') . ' ' . ($studentProfile?->last_name ?? '')" />

      <!-- Personal Information -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- First Name -->
        <x-profile.text-input
            label="{{ __('student.edit_profile.first_name') }}"
            name="first_name"
            :value="$studentProfile?->first_name ?? ''"
            required />

        <!-- Last Name -->
        <x-profile.text-input
            label="{{ __('student.edit_profile.last_name') }}"
            name="last_name"
            :value="$studentProfile?->last_name ?? ''"
            required />

        <!-- Email (Non-editable) -->
        <x-profile.text-input
            label="{{ __('student.edit_profile.email') }}"
            name="email"
            type="email"
            :value="auth()->user()->email"
            readonly />

        <!-- Student Code (Non-editable) -->
        <x-profile.text-input
            label="{{ __('student.edit_profile.student_number') }}"
            name="student_code"
            :value="$studentProfile?->student_code ?? ''"
            readonly />

        <!-- Phone -->
        <div>
          <x-forms.phone-input
              name="phone"
              :label="__('student.edit_profile.phone')"
              :required="false"
              countryCodeField="phone_country_code"
              countryField="phone_country"
              initialCountry="sa"
              :value="$studentProfile?->phone ?? ''"
              :error="$errors->first('phone')"
          />
        </div>

        <!-- Birth Date -->
        <x-profile.text-input
            label="{{ __('student.edit_profile.birth_date') }}"
            name="birth_date"
            type="date"
            :value="$studentProfile?->birth_date?->format('Y-m-d')" />

        <!-- Gender -->
        <x-profile.select-input
            label="{{ __('student.edit_profile.gender') }}"
            name="gender"
            :value="$studentProfile?->gender"
            :options="['male' => __('student.edit_profile.gender_male'), 'female' => __('student.edit_profile.gender_female')]"
            placeholder="{{ __('student.edit_profile.gender_placeholder') }}" />

        <!-- Nationality -->
        <x-profile.select-input
            label="{{ __('student.edit_profile.nationality') }}"
            name="nationality"
            :value="$studentProfile?->nationality"
            :options="$countries"
            placeholder="{{ __('student.edit_profile.nationality_placeholder') }}" />

        <!-- Emergency Contact -->
        <x-profile.text-input
            label="{{ __('student.edit_profile.emergency_contact') }}"
            name="emergency_contact"
            type="tel"
            :value="$studentProfile?->emergency_contact ?? ''" />

        <!-- Grade Level -->
        <x-profile.select-input
            label="{{ __('student.edit_profile.grade_level') }}"
            name="grade_level_id"
            :value="$studentProfile?->grade_level_id"
            :options="$gradeLevels->pluck('name', 'id')->toArray()"
            placeholder="{{ __('student.edit_profile.grade_level_placeholder') }}" />
      </div>

      <!-- Address (Full Width) -->
      <div class="mt-6">
        <x-profile.textarea-input
            label="{{ __('student.edit_profile.address') }}"
            name="address"
            :value="$studentProfile?->address ?? ''"
            :rows="3" />
      </div>

  </x-profile.form-wrapper>

</x-layouts.student>
