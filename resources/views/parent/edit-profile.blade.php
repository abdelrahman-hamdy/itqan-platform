@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout title="تعديل الملف الشخصي">

  <x-profile.form-wrapper
      title="تعديل الملف الشخصي"
      description="قم بتحديث معلوماتك الشخصية"
      :action="route('parent.profile.update', ['subdomain' => $subdomain])"
      method="PUT"
      enctype="multipart/form-data"
      :backRoute="route('parent.profile', ['subdomain' => $subdomain])">

      <!-- Profile Picture Upload -->
      <x-profile.picture-upload
          :currentAvatar="$parent?->avatar"
          :userName="($parent?->first_name ?? '') . ' ' . ($parent?->last_name ?? '')" />

      <!-- Personal Information -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- First Name -->
        <x-profile.text-input
            label="الاسم الأول"
            name="first_name"
            :value="$parent?->first_name ?? ''"
            required />

        <!-- Last Name -->
        <x-profile.text-input
            label="اسم العائلة"
            name="last_name"
            :value="$parent?->last_name ?? ''"
            required />

        <!-- Email (Non-editable) -->
        <x-profile.text-input
            label="البريد الإلكتروني"
            name="email"
            type="email"
            :value="auth()->user()->email"
            readonly />

        <!-- Parent Code (Non-editable) -->
        <x-profile.text-input
            label="رقم ولي الأمر"
            name="parent_code"
            :value="$parent?->parent_code ?? ''"
            readonly />

        <!-- Phone -->
        <x-profile.text-input
            label="رقم الهاتف"
            name="phone"
            type="tel"
            :value="$parent?->phone ?? ''" />

        <!-- Secondary Phone -->
        <x-profile.text-input
            label="رقم هاتف بديل"
            name="secondary_phone"
            type="tel"
            :value="$parent?->secondary_phone ?? ''" />

        <!-- Occupation -->
        <x-profile.text-input
            label="المهنة"
            name="occupation"
            :value="$parent?->occupation ?? ''" />

        <!-- Preferred Contact Method -->
        <x-profile.select-input
            label="طريقة التواصل المفضلة"
            name="preferred_contact_method"
            :value="$parent?->preferred_contact_method"
            :options="[
                'phone' => 'هاتف',
                'email' => 'بريد إلكتروني',
                'sms' => 'رسالة نصية',
                'whatsapp' => 'واتساب'
            ]"
            placeholder="اختر طريقة التواصل" />
      </div>

      <!-- Address (Full Width) -->
      <div class="mt-6">
        <x-profile.textarea-input
            label="العنوان"
            name="address"
            :value="$parent?->address ?? ''"
            :rows="3" />
      </div>

      <!-- Children Information (Read-only) -->
      @php
          $children = $parent?->students()->with('user')->get() ?? collect();
      @endphp
      @if($children->isNotEmpty())
          <div class="mt-8 pt-6 border-t border-gray-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                  <i class="ri-team-line text-xl text-purple-600 ml-2"></i>
                  الأبناء المسجلون
              </h3>
              <div class="bg-gray-50 rounded-lg p-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                      @foreach($children as $child)
                          <div class="flex items-center gap-3 bg-white rounded-lg p-3 border border-gray-200">
                              @if($child->user?->avatar)
                                  <img src="{{ Storage::url($child->user->avatar) }}"
                                       alt="{{ $child->user->name }}"
                                       class="w-10 h-10 rounded-full object-cover">
                              @else
                                  <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                                      <span class="text-white font-bold">
                                          {{ mb_substr($child->user->name ?? $child->first_name ?? 'ط', 0, 1) }}
                                      </span>
                                  </div>
                              @endif
                              <div>
                                  <p class="font-medium text-gray-900">{{ $child->user->name ?? $child->first_name }}</p>
                                  <p class="text-sm text-gray-500">{{ $child->student_code ?? 'طالب' }}</p>
                              </div>
                          </div>
                      @endforeach
                  </div>
                  <p class="text-sm text-gray-500 mt-3">
                      <i class="ri-information-line ml-1"></i>
                      لإضافة أو تعديل بيانات الأبناء، يرجى التواصل مع إدارة الأكاديمية.
                  </p>
              </div>
          </div>
      @endif

  </x-profile.form-wrapper>

</x-layouts.parent-layout>
