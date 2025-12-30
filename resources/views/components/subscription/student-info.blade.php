@props(['user' => null])

@php
  $student = $user ?? auth()->user();
@endphp

<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
  <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
    <i class="ri-user-line text-primary"></i>
    {{ __('public.booking.quran.form.student_info') }}
  </h4>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
    <div>
      <span class="text-gray-600">{{ __('public.booking.quran.form.name') }}</span>
      <span class="font-medium">{{ $student->studentProfile?->full_name ?? $student->name }}</span>
    </div>
    <div>
      <span class="text-gray-600">{{ __('public.booking.quran.form.email') }}</span>
      <span class="font-medium">{{ $student->email }}</span>
    </div>
    @if($student->studentProfile?->phone)
    <div>
      <span class="text-gray-600">{{ __('public.booking.quran.form.phone') }}</span>
      <span class="font-medium" dir="ltr">{{ $student->studentProfile->phone }}</span>
    </div>
    @endif
    @if($student->studentProfile?->birth_date)
    <div>
      <span class="text-gray-600">{{ __('public.booking.quran.form.age') }}</span>
      <span class="font-medium">{{ (int) $student->studentProfile->birth_date->diffInYears(now()) }} {{ __('public.booking.quran.form.years') }}</span>
    </div>
    @endif
  </div>
</div>
