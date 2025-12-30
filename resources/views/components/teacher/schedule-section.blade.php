@props([
    'teacher',
    'color' => 'yellow' // yellow or violet
])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
  <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
    <i class="ri-calendar-line text-{{ $color }}-600 text-xl"></i>
    {{ __('components.teacher.schedule_section.title') }}
  </h2>

  @php
    $availableDays = is_array($teacher->available_days) ? $teacher->available_days : (is_string($teacher->available_days) ? json_decode($teacher->available_days, true) : []);
    $availableDays = $availableDays ?: [];
  @endphp

  @if(!empty($availableDays))
    <div class="mb-6">
      <div class="text-sm text-gray-500 mb-3 flex items-center gap-2">
        <i class="ri-calendar-check-line text-{{ $color }}-600"></i>
        {{ __('components.teacher.schedule_section.available_days') }}
      </div>
      <div class="flex flex-wrap gap-2">
        @foreach($availableDays as $day)
          <span class="px-4 py-2 bg-{{ $color }}-600 text-white rounded-xl text-sm font-medium">
            {{ __('components.teacher.schedule_section.days.' . $day) }}
          </span>
        @endforeach
      </div>
    </div>
  @endif

  @if($teacher->available_time_start && $teacher->available_time_end)
    <div class="mb-6">
      <div class="text-sm text-gray-500 mb-3 flex items-center gap-2">
        <i class="ri-time-line text-{{ $color }}-600"></i>
        {{ __('components.teacher.schedule_section.available_times') }}
      </div>
      <div class="flex items-center gap-4">
        @php
          $startHour = $teacher->available_time_start->format('H');
          $startMinute = $teacher->available_time_start->format('i');
          $startTime12 = $startHour > 12 ? ($startHour - 12) : ($startHour == 0 ? 12 : $startHour);
          $startPeriod = $startHour >= 12 ? __('components.teacher.schedule_section.periods.pm') : __('components.teacher.schedule_section.periods.am');

          $endHour = $teacher->available_time_end->format('H');
          $endMinute = $teacher->available_time_end->format('i');
          $endTime12 = $endHour > 12 ? ($endHour - 12) : ($endHour == 0 ? 12 : $endHour);
          $endPeriod = $endHour >= 12 ? __('components.teacher.schedule_section.periods.pm') : __('components.teacher.schedule_section.periods.am');
        @endphp
        <span class="text-gray-900 font-medium">{{ $startTime12 }}:{{ $startMinute }} {{ $startPeriod }}</span>
        <span class="text-gray-400">—</span>
        <span class="text-gray-900 font-medium">{{ $endTime12 }}:{{ $endMinute }} {{ $endPeriod }}</span>
      </div>
    </div>
  @endif

  <div class="bg-{{ $color }}-50 rounded-xl p-4 text-sm text-{{ $color }}-900 flex items-start gap-3">
    <i class="ri-information-line text-{{ $color }}-600 text-lg flex-shrink-0 mt-0.5"></i>
    <span>{{ __('components.teacher.schedule_section.flexibility_note') }}</span>
  </div>
</div>
