@props(['user' => null, 'compact' => false])

@php
  $student = $user ?? auth()->user();
  $studentName = $student->studentProfile?->full_name ?? $student->name;
@endphp

@if($compact)
{{-- Compact collapsible mode --}}
<div x-data="{ expanded: false }">
  <button type="button" @click="expanded = !expanded" class="w-full flex items-center justify-between group">
    <div class="flex items-center gap-3">
      <x-avatar
        :user="$student"
        size="sm"
        userType="student" />
      <div class="text-start">
        <div class="font-semibold text-gray-900 text-sm">{{ $studentName }}</div>
        <div class="text-xs text-gray-500">{{ $student->email }}</div>
      </div>
    </div>
    <i class="ri-arrow-down-s-line text-gray-400 transition-transform duration-200"
       :class="expanded && 'rotate-180'"></i>
  </button>

  <div x-show="expanded" x-collapse x-cloak class="mt-3 ps-[3.75rem] space-y-1.5">
    @if($student->studentProfile?->phone)
      <div class="flex items-center gap-2 text-xs text-gray-600">
        <i class="ri-phone-line text-indigo-500"></i>
        <span dir="ltr">{{ $student->studentProfile->phone }}</span>
      </div>
    @endif
    @if($student->studentProfile?->birth_date)
      <div class="flex items-center gap-2 text-xs text-gray-600">
        <i class="ri-calendar-line text-indigo-500"></i>
        <span>{{ (int) $student->studentProfile->birth_date->diffInYears(nowInAcademyTimezone()) }} {{ __('public.booking.quran.form.years') }}</span>
      </div>
    @endif
  </div>
</div>

@else
{{-- Full card mode --}}
<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
  <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
    <i class="ri-user-line text-primary"></i>
    {{ __('public.booking.quran.form.student_info') }}
  </h4>
  <div class="flex items-center gap-4 mb-3">
    <x-avatar
      :user="$student"
      size="md"
      userType="student" />
    <div>
      <div class="font-medium text-gray-900">{{ $studentName }}</div>
      <div class="text-sm text-gray-500">{{ $student->email }}</div>
    </div>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
    @if($student->studentProfile?->phone)
    <div>
      <span class="text-gray-600">{{ __('public.booking.quran.form.phone') }}</span>
      <span class="font-medium" dir="ltr">{{ $student->studentProfile->phone }}</span>
    </div>
    @endif
    @if($student->studentProfile?->birth_date)
    <div>
      <span class="text-gray-600">{{ __('public.booking.quran.form.age') }}</span>
      <span class="font-medium">{{ (int) $student->studentProfile->birth_date->diffInYears(nowInAcademyTimezone()) }} {{ __('public.booking.quran.form.years') }}</span>
    </div>
    @endif
  </div>
</div>
@endif
