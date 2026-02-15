<x-layouts.teacher title="{{ $academy->name }} - {{ __('earnings.my_earnings') }}">
  <x-slot name="description">{{ __('earnings.my_earnings') }} - {{ $academy->name }}</x-slot>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">
              {{ __('earnings.my_earnings') }}
            </h1>
            <p class="text-sm md:text-base text-gray-600">
              {{ __('earnings.track_your_earnings_description') }}
            </p>
          </div>

          <!-- Month Filter -->
          <div class="w-full sm:w-auto sm:min-w-[200px]">
            <form method="GET" action="{{ route('teacher.earnings', ['subdomain' => $academy->subdomain]) }}" class="w-full">
              <select name="month" onchange="this.form.submit()" class="w-full min-h-[44px] px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white text-sm md:text-base">
                <option value="all" {{ $isAllTime ? 'selected' : '' }}>{{ __('earnings.all_time') }}</option>
                @foreach($availableMonths as $monthOption)
                  <option value="{{ $monthOption['value'] }}" {{ $selectedMonth === $monthOption['value'] ? 'selected' : '' }}>
                    {{ $monthOption['label'] }}
                  </option>
                @endforeach
              </select>
            </form>
          </div>
        </div>
      </div>

      <!-- Header for Earnings Section -->
      <div class="mb-6 md:mb-8">
        <div class="border-b border-gray-200">
          <div class="flex items-center gap-3 pb-3">
            <i class="ri-wallet-3-line text-xl md:text-2xl text-green-600"></i>
            <h2 class="text-lg md:text-xl font-semibold text-gray-900">{{ __('earnings.earnings') }}</h2>
          </div>
        </div>
      </div>

      <!-- Overall Statistics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8">

          <!-- Selected Period Earnings -->
          <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-4 md:p-6 text-white">
            <div class="flex items-center justify-between mb-3 md:mb-4">
              <div class="w-10 h-10 md:w-12 md:h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <i class="ri-money-dollar-circle-line text-xl md:text-2xl"></i>
              </div>
              @if($stats['changePercent'] != 0 && !$isAllTime)
                <div class="flex items-center text-sm bg-white/20 px-2 py-1 rounded-lg">
                  @if($stats['changePercent'] > 0)
                    <i class="ri-arrow-up-line ms-1"></i>
                    <span class="font-medium">+{{ $stats['changePercent'] }}%</span>
                  @else
                    <i class="ri-arrow-down-line ms-1"></i>
                    <span class="font-medium">{{ $stats['changePercent'] }}%</span>
                  @endif
                </div>
              @endif
            </div>
            <div>
              <p class="text-xs md:text-sm font-medium opacity-90 mb-1">
                @if($isAllTime)
                  {{ __('earnings.all_time_earnings') }}
                @else
                  {{ __('earnings.selected_period_earnings') }}
                @endif
              </p>
              <p class="text-2xl md:text-3xl font-bold">{{ number_format($stats['selectedMonth'], 2) }}</p>
              <p class="text-xs md:text-sm opacity-75">{{ $currency }}</p>
            </div>
          </div>

          <!-- Sessions Count -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3 md:mb-4">
              <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="ri-calendar-check-line text-xl md:text-2xl text-blue-600"></i>
              </div>
            </div>
            <div>
              <p class="text-xs md:text-sm font-medium text-gray-500 mb-1">{{ __('earnings.sessions_count') }}</p>
              <p class="text-2xl md:text-3xl font-bold text-gray-900">{{ $stats['sessionsCount'] }}</p>
              <p class="text-xs md:text-sm text-gray-600">{{ __('earnings.counted_session') }}</p>
            </div>
          </div>

          <!-- All Time Earnings -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3 md:mb-4">
              <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="ri-wallet-3-line text-xl md:text-2xl text-purple-600"></i>
              </div>
            </div>
            <div>
              <p class="text-xs md:text-sm font-medium text-gray-500 mb-1">{{ __('earnings.total_earnings') }}</p>
              <p class="text-2xl md:text-3xl font-bold text-gray-900">{{ number_format($stats['allTimeEarnings'], 2) }}</p>
              <p class="text-xs md:text-sm text-gray-600">{{ $currency }}</p>
            </div>
          </div>

          <!-- Unpaid Earnings -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3 md:mb-4">
              <div class="w-10 h-10 md:w-12 md:h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="ri-time-line text-xl md:text-2xl text-yellow-600"></i>
              </div>
            </div>
            <div>
              <p class="text-xs md:text-sm font-medium text-gray-500 mb-1">{{ __('earnings.unpaid_earnings') }}</p>
              <p class="text-2xl md:text-3xl font-bold text-gray-900">{{ number_format($stats['unpaidEarnings'], 2) }}</p>
              <p class="text-xs md:text-sm text-gray-600">{{ $currency }} - {{ __('earnings.pending_finalization') }}</p>
            </div>
          </div>

        </div>

        <!-- Earnings by Source -->
        <div class="mb-6 md:mb-8">
          <h2 class="text-lg md:text-xl lg:text-2xl font-bold text-gray-900 mb-4 md:mb-6">
            <i class="ri-list-check text-blue-600 ms-2"></i>
            {{ __('earnings.earnings_by_source') }}
          </h2>

          @forelse($earningsBySource as $source)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-3 md:mb-4 overflow-hidden" x-data="{ expanded: false }">

              <!-- Source Header -->
              <div @click="expanded = !expanded" class="p-4 md:p-6 cursor-pointer hover:bg-gray-50 transition-colors min-h-[72px]">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                  <div class="flex items-center gap-3 flex-1">
                    <!-- Source Icon -->
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg flex items-center justify-center flex-shrink-0
                      {{ $source['type'] === 'individual_circle' ? 'bg-blue-100' : '' }}
                      {{ $source['type'] === 'group_circle' ? 'bg-green-100' : '' }}
                      {{ $source['type'] === 'academic_lesson' ? 'bg-purple-100' : '' }}
                      {{ $source['type'] === 'interactive_course' ? 'bg-orange-100' : '' }}
                      {{ $source['type'] === 'other' ? 'bg-gray-100' : '' }}">
                      @if($source['type'] === 'individual_circle')
                        <i class="ri-user-line text-xl md:text-2xl text-blue-600"></i>
                      @elseif($source['type'] === 'group_circle')
                        <i class="ri-group-line text-xl md:text-2xl text-green-600"></i>
                      @elseif($source['type'] === 'academic_lesson')
                        <i class="ri-book-open-line text-xl md:text-2xl text-purple-600"></i>
                      @elseif($source['type'] === 'interactive_course')
                        <i class="ri-presentation-line text-xl md:text-2xl text-orange-600"></i>
                      @else
                        <i class="ri-file-list-line text-xl md:text-2xl text-gray-600"></i>
                      @endif
                    </div>

                    <!-- Source Info -->
                    <div class="flex-1 min-w-0">
                      <h3 class="text-base md:text-lg font-semibold text-gray-900 truncate">{{ $source['name'] }}</h3>
                      <p class="text-xs md:text-sm text-gray-500 mt-0.5 md:mt-1">
                        {{ $source['sessions_count'] }} {{ __('earnings.session') }}
                        <span class="hidden sm:inline">
                          @if($source['type'] === 'individual_circle')
                            · {{ __('earnings.source_types.individual_circle') }}
                          @elseif($source['type'] === 'group_circle')
                            · {{ __('earnings.source_types.group_circle') }}
                          @elseif($source['type'] === 'academic_lesson')
                            · {{ __('earnings.source_types.academic_lesson') }}
                          @elseif($source['type'] === 'interactive_course')
                            · {{ __('earnings.source_types.interactive_course') }}
                          @endif
                        </span>
                      </p>
                    </div>
                  </div>

                  <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-4">
                    <!-- Total Amount -->
                    <div class="text-end">
                      <p class="text-xs md:text-sm text-gray-500 mb-0.5 md:mb-1">{{ __('earnings.total_earnings') }}</p>
                      <p class="text-lg md:text-2xl font-bold text-green-600">{{ number_format($source['total'], 2) }}</p>
                      <p class="text-xs md:text-sm text-gray-600">{{ $currency }}</p>
                    </div>

                    <!-- Expand Icon -->
                    <div class="flex-shrink-0">
                      <i :class="expanded ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" class="text-xl md:text-2xl text-gray-400 transition-transform"></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Earnings Details (Expandable) -->
              <div x-show="expanded" x-collapse>
                <div class="border-t border-gray-200 bg-gray-50 p-4 md:p-6">
                  <h4 class="text-xs md:text-sm font-semibold text-gray-700 mb-3 md:mb-4">{{ __('earnings.session_details') }}</h4>

                  <div class="space-y-2 md:space-y-3">
                    @foreach($source['earnings'] as $earning)
                      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-4 p-3 md:p-4 bg-white rounded-lg border border-gray-200">
                        <div class="flex-1 min-w-0">
                          <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs md:text-sm font-medium text-gray-900">
                              {{ $earning->calculation_method_label }}
                            </span>
                            @if($earning->is_finalized)
                              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                <i class="ri-check-line ms-1"></i>
                                {{ __('earnings.finalized_status') }}
                              </span>
                            @elseif($earning->is_disputed)
                              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                <i class="ri-error-warning-line ms-1"></i>
                                {{ __('earnings.disputed_status') }}
                              </span>
                            @else
                              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="ri-time-line ms-1"></i>
                                {{ __('earnings.pending_status') }}
                              </span>
                            @endif
                          </div>
                          <p class="text-xs text-gray-500 mt-1">
                            <i class="ri-calendar-line ms-1"></i>
                            {{ $earning->session_completed_at ? $earning->session_completed_at->setTimezone($timezone)->format('d/m/Y - h:i A') : __('earnings.date_not_specified') }}
                          </p>
                        </div>
                        <div class="text-end flex-shrink-0">
                          <p class="text-base md:text-lg font-bold text-green-600">{{ number_format($earning->amount, 2) }}</p>
                          <p class="text-xs text-gray-500">{{ $currency }}</p>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>

            </div>
          @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
              <i class="ri-inbox-line text-4xl md:text-6xl text-gray-300 mb-3 md:mb-4"></i>
              <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-1 md:mb-2">{{ __('earnings.no_earnings_yet') }}</h3>
              <p class="text-sm md:text-base text-gray-500">{{ __('earnings.earnings_will_appear_after_sessions') }}</p>
            </div>
          @endforelse
        </div>

  </div>

  @push('scripts')
  <script>
    // Alpine.js is already loaded, no additional scripts needed
  </script>
  @endpush

</x-layouts.teacher>
