<!-- Quick Stats Component -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 lg:gap-6 mb-4 md:mb-6 lg:mb-8">
  <!-- Next Upcoming Session -->
  <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between gap-2 md:gap-3">
      <div class="flex-1 min-w-0">
        <p class="text-[10px] md:text-xs lg:text-sm font-medium text-gray-500 truncate">{{ __('components.stats.quick_stats.next_session') }}</p>
        <p class="text-sm md:text-base lg:text-lg font-bold text-gray-900 mt-0.5 md:mt-1 truncate">{{ $nextSessionText ?? __('components.stats.quick_stats.no_sessions') }}</p>
        @if(isset($nextSessionDate))
          <p class="text-[10px] md:text-xs text-blue-600 mt-0.5 md:mt-1 truncate">
            <i class="ri-calendar-line ms-0.5 md:ms-1"></i>
            {{ $nextSessionDate->locale(app()->getLocale())->isoFormat('dddd، D MMMM - HH:mm') }}
          </p>
        @else
          <p class="text-[10px] md:text-xs text-gray-400 mt-0.5 md:mt-1 hidden sm:block">
            <i class="ri-calendar-line ms-0.5 md:ms-1"></i>
            {{ __('components.stats.quick_stats.no_upcoming_sessions') }}
          </p>
        @endif
      </div>
      <div class="w-8 h-8 md:w-10 md:h-10 lg:w-12 lg:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
        <i class="{{ $nextSessionIcon ?? 'ri-calendar-event-line' }} text-base md:text-lg lg:text-xl text-blue-600"></i>
      </div>
    </div>
  </div>

  <!-- Pending Homework (Not Yet Graded) -->
  <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between gap-2 md:gap-3">
      <div class="flex-1 min-w-0">
        <p class="text-[10px] md:text-xs lg:text-sm font-medium text-gray-500 truncate">{{ __('components.stats.quick_stats.homework_awaiting_grading') }}</p>
        <p class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900">{{ $pendingHomework ?? 0 }}</p>
        @if(($pendingHomework ?? 0) > 0)
          <p class="text-[10px] md:text-xs text-orange-600 mt-0.5 md:mt-1 truncate hidden sm:block">
            <i class="ri-alert-line ms-0.5 md:ms-1"></i>
            {{ __('components.stats.quick_stats.from_previous_sessions') }}
          </p>
        @else
          <p class="text-[10px] md:text-xs text-green-600 mt-0.5 md:mt-1 truncate hidden sm:block">
            <i class="ri-check-line ms-0.5 md:ms-1"></i>
            {{ __('components.stats.quick_stats.all_graded') }}
          </p>
        @endif
      </div>
      <div class="w-8 h-8 md:w-10 md:h-10 lg:w-12 lg:h-12 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
        <i class="ri-file-text-line text-base md:text-lg lg:text-xl text-orange-600"></i>
      </div>
    </div>
  </div>

  <!-- Pending Quizzes -->
  <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between gap-2 md:gap-3">
      <div class="flex-1 min-w-0">
        <p class="text-[10px] md:text-xs lg:text-sm font-medium text-gray-500 truncate">{{ __('components.stats.quick_stats.pending_quizzes') }}</p>
        <p class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900">{{ $pendingQuizzes ?? 0 }}</p>
        @if(($pendingQuizzes ?? 0) > 0)
          <p class="text-[10px] md:text-xs text-orange-600 mt-0.5 md:mt-1 hidden sm:block">
            <i class="ri-alert-line ms-0.5 md:ms-1"></i>
            {{ __('components.stats.quick_stats.must_be_completed') }}
          </p>
        @else
          <p class="text-[10px] md:text-xs text-green-600 mt-0.5 md:mt-1 hidden sm:block">
            <i class="ri-check-line ms-0.5 md:ms-1"></i>
            {{ __('components.stats.quick_stats.all_completed') }}
          </p>
        @endif
      </div>
      <div class="w-8 h-8 md:w-10 md:h-10 lg:w-12 lg:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
        <i class="ri-file-list-3-line text-base md:text-lg lg:text-xl text-purple-600"></i>
      </div>
    </div>
  </div>

  <!-- Today's Learning Hours -->
  <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 lg:p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between gap-2 md:gap-3">
      <div class="flex-1 min-w-0">
        <p class="text-[10px] md:text-xs lg:text-sm font-medium text-gray-500 truncate">{{ __('components.stats.quick_stats.learning_hours') }}</p>
        <p class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900">
          @if(($todayLearningHours ?? 0) > 0)
            {{ $todayLearningHours }} <span class="text-xs md:text-sm">{{ $todayLearningHours == 1 ? __('components.stats.quick_stats.hour') : __('components.stats.quick_stats.hours') }}</span>
          @else
            {{ $todayLearningMinutes ?? 0 }} <span class="text-xs md:text-sm">{{ ($todayLearningMinutes ?? 0) == 1 ? __('components.stats.quick_stats.minute') : __('components.stats.quick_stats.minutes') }}</span>
          @endif
        </p>
        @if(($todayLearningMinutes ?? 0) > 0)
          <p class="text-[10px] md:text-xs text-green-600 mt-0.5 md:mt-1 hidden sm:block">
            <i class="ri-time-line ms-0.5 md:ms-1"></i>
            {{ __('components.stats.quick_stats.keep_going') }}
          </p>
        @else
          <p class="text-[10px] md:text-xs text-gray-400 mt-0.5 md:mt-1 hidden sm:block">
            <i class="ri-calendar-line ms-0.5 md:ms-1"></i>
            {{ __('components.stats.quick_stats.no_sessions_today') }}
          </p>
        @endif
      </div>
      <div class="w-8 h-8 md:w-10 md:h-10 lg:w-12 lg:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
        <i class="ri-time-fill text-base md:text-lg lg:text-xl text-green-600"></i>
      </div>
    </div>
  </div>
</div>
