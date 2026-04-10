<!-- Teacher Stats Component -->
@php
$studentsSubtitle = isset($stats['activeCircles'])
    ? __('teacher.quick_stats.individual_students_label')
    : (isset($stats['activeCourses']) ? __('teacher.quick_stats.private_students_label') : null);
$monthHours = floor(($stats['thisMonthDuration'] ?? 0) / 60);
$monthEarnings = number_format($stats['monthlyEarnings'] ?? 0, 0);
$teacherRating = number_format($stats['teacherRating'] ?? 0, 1);
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 lg:gap-6 mb-4 md:mb-6 lg:mb-8">
  <!-- Total Students -->
  <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between gap-2 md:gap-3">
      <div class="flex-1 min-w-0">
        <p class="text-[10px] md:text-xs lg:text-sm font-medium text-gray-500 truncate">{{ __('teacher.quick_stats.total_students') }}</p>
        <p class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900">{{ $stats['totalStudents'] ?? 0 }}</p>
        @if($studentsSubtitle)
          <p class="text-[10px] md:text-xs text-blue-600 mt-0.5 md:mt-1 truncate hidden sm:block">
            <i class="ri-group-line ms-0.5 md:ms-1"></i>
            {{ $studentsSubtitle }}
          </p>
        @endif
      </div>
      <div class="w-8 h-8 md:w-10 md:h-10 lg:w-12 lg:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
        <i class="ri-group-line text-base md:text-lg lg:text-xl text-blue-600"></i>
      </div>
    </div>
  </div>

  <!-- Month Duration (Hours) -->
  <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between gap-2 md:gap-3">
      <div class="flex-1 min-w-0">
        <p class="text-[10px] md:text-xs lg:text-sm font-medium text-gray-500 truncate">{{ __('teacher.quick_stats.month_duration') }}</p>
        <p class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900">
          {{ $monthHours }} <span class="text-xs md:text-sm">{{ __('teacher.quick_stats.hours') }}</span>
        </p>
        <p class="text-[10px] md:text-xs text-purple-600 mt-0.5 md:mt-1 truncate hidden sm:block">
          <i class="ri-time-line ms-0.5 md:ms-1"></i>
          {{ __('teacher.quick_stats.hours') }}
        </p>
      </div>
      <div class="w-8 h-8 md:w-10 md:h-10 lg:w-12 lg:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
        <i class="ri-time-line text-base md:text-lg lg:text-xl text-purple-600"></i>
      </div>
    </div>
  </div>

  <!-- Month Earnings -->
  <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between gap-2 md:gap-3">
      <div class="flex-1 min-w-0">
        <p class="text-[10px] md:text-xs lg:text-sm font-medium text-gray-500 truncate">{{ __('teacher.quick_stats.month_earnings') }}</p>
        <p class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900">
          {{ $monthEarnings }} <span class="text-xs md:text-sm">{{ $currencySymbol ?? __('teacher.quick_stats.currency') }}</span>
        </p>
        <p class="text-[10px] md:text-xs text-green-600 mt-0.5 md:mt-1 truncate hidden sm:block">
          <i class="ri-money-dollar-circle-line ms-0.5 md:ms-1"></i>
          {{ $currencySymbol ?? __('teacher.quick_stats.currency') }}
        </p>
      </div>
      <div class="w-8 h-8 md:w-10 md:h-10 lg:w-12 lg:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
        <i class="ri-money-dollar-circle-line text-base md:text-lg lg:text-xl text-green-600"></i>
      </div>
    </div>
  </div>

  <!-- Teacher Rating -->
  <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between gap-2 md:gap-3">
      <div class="flex-1 min-w-0">
        <p class="text-[10px] md:text-xs lg:text-sm font-medium text-gray-500 truncate">{{ __('teacher.quick_stats.teacher_rating') }}</p>
        <p class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900">{{ $teacherRating }}</p>
        <p class="text-[10px] md:text-xs text-yellow-600 mt-0.5 md:mt-1 truncate hidden sm:block">
          <i class="ri-star-line ms-0.5 md:ms-1"></i>
          {{ __('teacher.quick_stats.out_of_stars') }}
        </p>
      </div>
      <div class="w-8 h-8 md:w-10 md:h-10 lg:w-12 lg:h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
        <i class="ri-star-line text-base md:text-lg lg:text-xl text-yellow-600"></i>
      </div>
    </div>
  </div>
</div>
