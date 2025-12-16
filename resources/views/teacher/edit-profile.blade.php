@php
  $isQuranTeacher = auth()->user()->isQuranTeacher();
  $isAcademicTeacher = auth()->user()->isAcademicTeacher();
@endphp

<x-layouts.teacher title="{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - تعديل ملف المعلم">
  <x-slot name="description">تعديل ملف المعلم - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}</x-slot>

  <x-profile.form-wrapper
      title="تعديل الملف الشخصي"
      description="تحديث بياناتك الشخصية ومعلومات التدريس"
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
        <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 pb-2 border-b border-gray-200">المعلومات الشخصية</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
          <!-- First Name -->
          <x-profile.text-input
              label="الاسم الأول"
              name="first_name"
              :value="$teacherProfile->first_name ?? auth()->user()->first_name"
              required />

          <!-- Last Name -->
          <x-profile.text-input
              label="الاسم الأخير"
              name="last_name"
              :value="$teacherProfile->last_name ?? auth()->user()->last_name"
              required />

          <!-- Email (readonly) -->
          <x-profile.text-input
              label="البريد الإلكتروني"
              name="email"
              type="email"
              :value="$teacherProfile->email ?? auth()->user()->email"
              readonly />

          <!-- Phone -->
          <x-profile.text-input
              label="رقم الهاتف"
              name="phone"
              type="tel"
              :value="$teacherProfile->phone ?? auth()->user()->phone" />
        </div>
      </div>

      <!-- Qualifications Section -->
      <div class="mb-6 md:mb-8">
        <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 pb-2 border-b border-gray-200">المؤهلات والخبرة</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
          <!-- Educational Qualification (Quran Teacher) -->
          @if($isQuranTeacher)
            <x-profile.select-input
                label="المؤهل التعليمي"
                name="educational_qualification"
                :value="$teacherProfile->educational_qualification ?? 'bachelor'"
                :options="[
                    'bachelor' => 'بكالوريوس',
                    'master' => 'ماجستير',
                    'phd' => 'دكتوراه',
                    'diploma' => 'دبلوم',
                    'other' => 'أخرى'
                ]"
                placeholder="اختر المؤهل التعليمي" />
          @endif

          <!-- Education Level (Academic Teacher) -->
          @if($isAcademicTeacher)
            <x-profile.select-input
                label="المؤهل التعليمي"
                name="education_level"
                :value="$teacherProfile->education_level ?? 'bachelor'"
                :options="[
                    'diploma' => 'دبلوم',
                    'bachelor' => 'بكالوريوس',
                    'master' => 'ماجستير',
                    'phd' => 'دكتوراه',
                    'other' => 'أخرى'
                ]"
                placeholder="اختر المؤهل التعليمي" />

            <!-- University -->
            <x-profile.text-input
                label="الجامعة"
                name="university"
                :value="$teacherProfile->university ?? ''" />
          @endif

          <!-- Teaching Experience Years -->
          <x-profile.text-input
              label="سنوات الخبرة في التدريس"
              name="teaching_experience_years"
              type="number"
              :value="$teacherProfile->teaching_experience_years ?? 0" />
        </div>

        <!-- Certifications -->
        <div class="mt-4 md:mt-6">
          <x-profile.tags-input
              label="الشهادات والإجازات"
              name="certifications"
              :value="$teacherProfile->certifications ?? []"
              placeholder="أضف شهادة أو إجازة واضغط Enter" />
        </div>

        <!-- Languages -->
        <div class="mt-4 md:mt-6">
          @php
            $languageOptions = [
              'arabic' => 'العربية',
              'english' => 'الإنجليزية',
              'french' => 'الفرنسية',
              'german' => 'الألمانية',
              'turkish' => 'التركية',
              'spanish' => 'الإسبانية',
              'urdu' => 'الأردية',
              'persian' => 'الفارسية',
            ];
            $selectedLanguages = old('languages', $teacherProfile->languages ?? ['arabic']);
          @endphp

          <x-profile.checkbox-group
              label="اللغات التي تجيدها"
              name="languages"
              :options="$languageOptions"
              :selected="$selectedLanguages"
              :columns="2" />
        </div>
      </div>

      <!-- Availability Section -->
      <div class="mb-6 md:mb-8">
        <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 pb-2 border-b border-gray-200">أوقات التدريس</h3>

        <!-- Available Days -->
        <div class="mb-4 md:mb-6">
          @php
            $daysOptions = [
              'sunday' => 'الأحد',
              'monday' => 'الاثنين',
              'tuesday' => 'الثلاثاء',
              'wednesday' => 'الأربعاء',
              'thursday' => 'الخميس',
              'friday' => 'الجمعة',
              'saturday' => 'السبت'
            ];
            $selectedDays = old('available_days', is_array($teacherProfile->available_days) ? $teacherProfile->available_days : []);
          @endphp

          <x-profile.checkbox-group
              label="الأيام المتاحة"
              name="available_days"
              :options="$daysOptions"
              :selected="$selectedDays"
              :columns="2" />
        </div>

        <!-- Available Hours -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
          <x-profile.text-input
              label="من الساعة"
              name="available_time_start"
              type="time"
              :value="$teacherProfile->available_time_start ? \Carbon\Carbon::parse($teacherProfile->available_time_start)->format('H:i') : '08:00'" />

          <x-profile.text-input
              label="إلى الساعة"
              name="available_time_end"
              type="time"
              :value="$teacherProfile->available_time_end ? \Carbon\Carbon::parse($teacherProfile->available_time_end)->format('H:i') : '18:00'" />
        </div>
      </div>

      <!-- Bio Section -->
      <div class="mb-6 md:mb-8">
        <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 pb-2 border-b border-gray-200">السيرة الذاتية</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
          <!-- Bio Arabic -->
          <x-profile.textarea-input
              label="السيرة الذاتية (عربي)"
              name="bio_arabic"
              :value="$teacherProfile->bio_arabic ?? ''"
              :rows="4"
              placeholder="اكتب نبذة مختصرة عنك وخبرتك في التدريس..." />

          <!-- Bio English -->
          <x-profile.textarea-input
              label="السيرة الذاتية (إنجليزي)"
              name="bio_english"
              :value="$teacherProfile->bio_english ?? ''"
              :rows="4"
              placeholder="Write a brief bio about yourself and your teaching experience..." />
        </div>
      </div>

  </x-profile.form-wrapper>

</x-layouts.teacher>
