@props(['user' => null])

@php
  $student = $user ?? auth()->user();
@endphp

<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
  <h4 class="font-semibold text-gray-900 mb-3">
    <i class="ri-user-line text-primary ml-2"></i>
    معلومات الطالب
  </h4>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
    <div>
      <span class="text-gray-600">الاسم:</span>
      <span class="font-medium">{{ $student->studentProfile?->full_name ?? $student->name }}</span>
    </div>
    <div>
      <span class="text-gray-600">البريد الإلكتروني:</span>
      <span class="font-medium">{{ $student->email }}</span>
    </div>
    @if($student->studentProfile?->phone)
    <div>
      <span class="text-gray-600">رقم الهاتف:</span>
      <span class="font-medium">{{ $student->studentProfile->phone }}</span>
    </div>
    @endif
    @if($student->studentProfile?->birth_date)
    <div>
      <span class="text-gray-600">العمر:</span>
      <span class="font-medium">{{ $student->studentProfile->birth_date->diffInYears(now()) }} سنة</span>
    </div>
    @endif
  </div>
</div>
