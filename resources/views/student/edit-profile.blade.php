<x-layouts.student title="{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - تعديل الملف الشخصي">
  <x-slot name="description">تعديل الملف الشخصي للطالب - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}</x-slot>

  <x-profile.form-wrapper
      title="تعديل الملف الشخصي"
      description="قم بتحديث معلوماتك الشخصية"
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
            label="الاسم الأول"
            name="first_name"
            :value="$studentProfile?->first_name ?? ''"
            required />

        <!-- Last Name -->
        <x-profile.text-input
            label="الاسم الأخير"
            name="last_name"
            :value="$studentProfile?->last_name ?? ''"
            required />

        <!-- Email (Non-editable) -->
        <x-profile.text-input
            label="البريد الإلكتروني"
            name="email"
            type="email"
            :value="auth()->user()->email"
            readonly />

        <!-- Student Code (Non-editable) -->
        <x-profile.text-input
            label="رقم الطالب"
            name="student_code"
            :value="$studentProfile?->student_code ?? ''"
            readonly />

        <!-- Phone -->
        <x-profile.text-input
            label="رقم الهاتف"
            name="phone"
            type="tel"
            :value="$studentProfile?->phone ?? ''" />

        <!-- Birth Date -->
        <x-profile.text-input
            label="تاريخ الميلاد"
            name="birth_date"
            type="date"
            :value="$studentProfile?->birth_date?->format('Y-m-d')" />

        <!-- Gender -->
        <x-profile.select-input
            label="الجنس"
            name="gender"
            :value="$studentProfile?->gender"
            :options="['male' => 'ذكر', 'female' => 'أنثى']"
            placeholder="اختر الجنس" />

        <!-- Nationality -->
        <x-profile.select-input
            label="الجنسية"
            name="nationality"
            :value="$studentProfile?->nationality"
            :options="$countries"
            placeholder="اختر الجنسية" />

        <!-- Emergency Contact -->
        <x-profile.text-input
            label="رقم الطوارئ"
            name="emergency_contact"
            type="tel"
            :value="$studentProfile?->emergency_contact ?? ''" />

        <!-- Grade Level -->
        <x-profile.select-input
            label="المرحلة الدراسية"
            name="grade_level_id"
            :value="$studentProfile?->grade_level_id"
            :options="$gradeLevels->pluck('name', 'id')->toArray()"
            placeholder="اختر المرحلة الدراسية" />
      </div>

      <!-- Address (Full Width) -->
      <div class="mt-6">
        <x-profile.textarea-input
            label="العنوان"
            name="address"
            :value="$studentProfile?->address ?? ''"
            :rows="3" />
      </div>

  </x-profile.form-wrapper>

</x-layouts.student>
