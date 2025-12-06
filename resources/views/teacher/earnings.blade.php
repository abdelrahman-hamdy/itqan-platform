<x-layouts.teacher title="{{ $academy->name }} - {{ __('earnings.my_earnings') }}">
  <x-slot name="description">{{ __('earnings.my_earnings') }} - {{ $academy->name }}</x-slot>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ activeTab: 'earnings' }">

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
              {{ __('earnings.my_earnings_and_payouts') }}
            </h1>
            <p class="text-gray-600">
              {{ __('earnings.track_your_earnings_description') }}
            </p>
          </div>

          <!-- Month Filter -->
          <div class="min-w-[200px]">
            <form method="GET" action="{{ route('teacher.earnings', ['subdomain' => $academy->subdomain]) }}" class="w-full">
              <select name="month" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
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

      <!-- Tab Navigation -->
      <div class="mb-8">
        <div class="border-b border-gray-200">
          <nav class="flex -mb-px space-x-8 space-x-reverse" aria-label="Tabs">
            <button
              @click="activeTab = 'earnings'"
              :class="activeTab === 'earnings' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
              <i class="ri-wallet-3-line ml-2"></i>
              {{ __('earnings.earnings') }}
            </button>
            <button
              @click="activeTab = 'payouts'"
              :class="activeTab === 'payouts' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
              <i class="ri-hand-coin-line ml-2"></i>
              {{ __('earnings.payouts') }}
            </button>
          </nav>
        </div>
      </div>

      <!-- Earnings Tab -->
      <div x-show="activeTab === 'earnings'" x-transition>

        <!-- Overall Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

          <!-- Selected Period Earnings -->
          <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <i class="ri-money-dollar-circle-line text-2xl"></i>
              </div>
              @if($stats['changePercent'] != 0 && !$isAllTime)
                <div class="flex items-center text-sm bg-white/20 px-2 py-1 rounded-lg">
                  @if($stats['changePercent'] > 0)
                    <i class="ri-arrow-up-line ml-1"></i>
                    <span class="font-medium">+{{ $stats['changePercent'] }}%</span>
                  @else
                    <i class="ri-arrow-down-line ml-1"></i>
                    <span class="font-medium">{{ $stats['changePercent'] }}%</span>
                  @endif
                </div>
              @endif
            </div>
            <div>
              <p class="text-sm font-medium opacity-90 mb-1">
                @if($isAllTime)
                  {{ __('earnings.all_time_earnings') }}
                @else
                  {{ __('earnings.selected_period_earnings') }}
                @endif
              </p>
              <p class="text-3xl font-bold">{{ number_format($stats['selectedMonth'], 2) }}</p>
              <p class="text-sm opacity-75">{{ $currency }}</p>
            </div>
          </div>

          <!-- Sessions Count -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="ri-calendar-check-line text-2xl text-blue-600"></i>
              </div>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500 mb-1">{{ __('earnings.sessions_count') }}</p>
              <p class="text-3xl font-bold text-gray-900">{{ $stats['sessionsCount'] }}</p>
              <p class="text-sm text-gray-600">{{ __('earnings.counted_session') }}</p>
            </div>
          </div>

          <!-- All Time Earnings -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="ri-wallet-3-line text-2xl text-purple-600"></i>
              </div>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500 mb-1">{{ __('earnings.total_earnings') }}</p>
              <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['allTimeEarnings'], 2) }}</p>
              <p class="text-sm text-gray-600">{{ $currency }}</p>
            </div>
          </div>

          <!-- Unpaid Earnings -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="ri-time-line text-2xl text-yellow-600"></i>
              </div>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500 mb-1">{{ __('earnings.pending_earnings') }}</p>
              <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['unpaidEarnings'], 2) }}</p>
              <p class="text-sm text-gray-600">{{ $currency }} - {{ __('earnings.awaiting_payment') }}</p>
            </div>
          </div>

        </div>

        <!-- Earnings by Source -->
        <div class="mb-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">
            <i class="ri-list-check text-blue-600 ml-2"></i>
            {{ __('earnings.earnings_by_source') }}
          </h2>

          @forelse($earningsBySource as $source)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-4 overflow-hidden" x-data="{ expanded: false }">

              <!-- Source Header -->
              <div @click="expanded = !expanded" class="p-6 cursor-pointer hover:bg-gray-50 transition-colors">
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <div class="flex items-center gap-3">
                      <!-- Source Icon -->
                      <div class="w-12 h-12 rounded-lg flex items-center justify-center
                        {{ $source['type'] === 'individual_circle' ? 'bg-blue-100' : '' }}
                        {{ $source['type'] === 'group_circle' ? 'bg-green-100' : '' }}
                        {{ $source['type'] === 'academic_lesson' ? 'bg-purple-100' : '' }}
                        {{ $source['type'] === 'interactive_course' ? 'bg-orange-100' : '' }}
                        {{ $source['type'] === 'other' ? 'bg-gray-100' : '' }}">
                        @if($source['type'] === 'individual_circle')
                          <i class="ri-user-line text-2xl text-blue-600"></i>
                        @elseif($source['type'] === 'group_circle')
                          <i class="ri-group-line text-2xl text-green-600"></i>
                        @elseif($source['type'] === 'academic_lesson')
                          <i class="ri-book-open-line text-2xl text-purple-600"></i>
                        @elseif($source['type'] === 'interactive_course')
                          <i class="ri-presentation-line text-2xl text-orange-600"></i>
                        @else
                          <i class="ri-file-list-line text-2xl text-gray-600"></i>
                        @endif
                      </div>

                      <!-- Source Info -->
                      <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $source['name'] }}</h3>
                        <p class="text-sm text-gray-500 mt-1">
                          {{ $source['sessions_count'] }} {{ __('earnings.session') }}
                          @if($source['type'] === 'individual_circle')
                            · {{ __('earnings.source_types.individual_circle') }}
                          @elseif($source['type'] === 'group_circle')
                            · {{ __('earnings.source_types.group_circle') }}
                          @elseif($source['type'] === 'academic_lesson')
                            · {{ __('earnings.source_types.academic_lesson') }}
                          @elseif($source['type'] === 'interactive_course')
                            · {{ __('earnings.source_types.interactive_course') }}
                          @endif
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Total Amount -->
                  <div class="text-left mr-4">
                    <p class="text-sm text-gray-500 mb-1">{{ __('earnings.total_earnings') }}</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($source['total'], 2) }}</p>
                    <p class="text-sm text-gray-600">{{ $currency }}</p>
                  </div>

                  <!-- Expand Icon -->
                  <div class="mr-4">
                    <i :class="expanded ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" class="text-2xl text-gray-400 transition-transform"></i>
                  </div>
                </div>
              </div>

              <!-- Earnings Details (Expandable) -->
              <div x-show="expanded" x-collapse>
                <div class="border-t border-gray-200 bg-gray-50 p-6">
                  <h4 class="text-sm font-semibold text-gray-700 mb-4">{{ __('earnings.session_details') }}</h4>

                  <div class="space-y-3">
                    @foreach($source['earnings'] as $earning)
                      <div class="flex items-center justify-between p-4 bg-white rounded-lg border border-gray-200">
                        <div class="flex-1">
                          <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900">
                              {{ $earning->calculation_method_label }}
                            </span>
                            @if($earning->payout_id)
                              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                <i class="ri-check-line ml-1"></i>
                                {{ __('earnings.paid_status') }}
                              </span>
                            @else
                              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="ri-time-line ml-1"></i>
                                {{ __('earnings.pending_status') }}
                              </span>
                            @endif
                          </div>
                          <p class="text-xs text-gray-500 mt-1">
                            <i class="ri-calendar-line ml-1"></i>
                            {{ $earning->session_completed_at ? $earning->session_completed_at->setTimezone($timezone)->format('d/m/Y - h:i A') : __('earnings.date_not_specified') }}
                          </p>
                        </div>
                        <div class="text-left">
                          <p class="text-lg font-bold text-green-600">{{ number_format($earning->amount, 2) }}</p>
                          <p class="text-xs text-gray-500">{{ $currency }}</p>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>

            </div>
          @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
              <i class="ri-inbox-line text-6xl text-gray-300 mb-4"></i>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('earnings.no_earnings_yet') }}</h3>
              <p class="text-gray-500">{{ __('earnings.earnings_will_appear_after_sessions') }}</p>
            </div>
          @endforelse
        </div>

      </div>

      <!-- Payouts Tab -->
      <div x-show="activeTab === 'payouts'" x-transition>

        <!-- Current Month Payout -->
        @if($currentMonthPayout)
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-8 mb-8 text-white">
          <div class="flex items-center justify-between flex-wrap gap-6">
            <div class="flex items-center gap-6">
              <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="ri-calendar-event-line text-3xl"></i>
              </div>
              <div>
                <p class="text-sm opacity-90 mb-1">{{ __('earnings.current_month_payout') }}</p>
                <h3 class="text-2xl font-bold">{{ $currentMonthPayout->payout_code }}</h3>
                <p class="text-sm opacity-75 mt-1">{{ $currentMonthPayout->month_name }}</p>
              </div>
            </div>
            <div class="text-left">
              <p class="text-sm opacity-90 mb-1">{{ __('earnings.amount_label') }}</p>
              <p class="text-3xl font-bold">{{ number_format($currentMonthPayout->total_amount, 2) }}</p>
              <p class="text-sm opacity-75">{{ $currency }}</p>
            </div>
            <div>
              <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold
                {{ $currentMonthPayout->status === 'paid' ? 'bg-white/30' : '' }}
                {{ $currentMonthPayout->status === 'approved' ? 'bg-blue-500/30' : '' }}
                {{ $currentMonthPayout->status === 'pending' ? 'bg-yellow-500/30' : '' }}
                {{ $currentMonthPayout->status === 'rejected' ? 'bg-red-500/30' : '' }}">
                {{ __('earnings.status.' . $currentMonthPayout->status) }}
              </span>
            </div>
          </div>
        </div>
        @endif

        <!-- Last Payout Highlight (if no current month payout) -->
        @if(!$currentMonthPayout && $stats['lastPayout'])
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-8 mb-8 text-white">
          <div class="flex items-center justify-between flex-wrap gap-6">
            <div class="flex items-center gap-6">
              <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="ri-hand-coin-line text-3xl"></i>
              </div>
              <div>
                <p class="text-sm opacity-90 mb-1">{{ __('earnings.last_payout') }}</p>
                <h3 class="text-2xl font-bold">{{ $stats['lastPayout']->payout_code }}</h3>
                <p class="text-sm opacity-75 mt-1">{{ $stats['lastPayout']->month_name }}</p>
              </div>
            </div>
            <div class="text-left">
              <p class="text-sm opacity-90 mb-1">{{ __('earnings.amount_label') }}</p>
              <p class="text-3xl font-bold">{{ number_format($stats['lastPayout']->total_amount, 2) }}</p>
              <p class="text-sm opacity-75">{{ $currency }}</p>
            </div>
            <div>
              <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-white/20">
                {{ __('earnings.status.' . $stats['lastPayout']->status) }}
              </span>
            </div>
          </div>
        </div>
        @endif

        <!-- Payout History -->
        <div class="mb-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">
            <i class="ri-history-line text-green-600 ml-2"></i>
            {{ __('earnings.payout_history') }}
          </h2>

          <div class="space-y-4">
            @forelse($payoutHistory as $payout)
              <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-6">
                  <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                      <div class="flex items-center gap-3 mb-2">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $payout->payout_code }}</h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                          {{ $payout->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                          {{ $payout->status === 'approved' ? 'bg-blue-100 text-blue-800' : '' }}
                          {{ $payout->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                          {{ $payout->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                          {{ __('earnings.status.' . $payout->status) }}
                        </span>
                      </div>
                      <p class="text-sm text-gray-500">
                        <i class="ri-calendar-line ml-1"></i>
                        {{ $payout->month_name }}
                      </p>
                    </div>
                    <div class="text-left">
                      <p class="text-sm text-gray-500 mb-1">{{ __('earnings.total_amount') }}</p>
                      <p class="text-2xl font-bold text-green-600">{{ number_format($payout->total_amount, 2) }}</p>
                      <p class="text-sm text-gray-600">{{ $currency }}</p>
                    </div>
                  </div>

                  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-gray-100">
                    <div>
                      <p class="text-xs text-gray-500 mb-1">{{ __('earnings.sessions_count') }}</p>
                      <p class="text-sm font-semibold text-gray-900">{{ $payout->sessions_count }}</p>
                    </div>

                    @if($payout->paid_at)
                    <div>
                      <p class="text-xs text-gray-500 mb-1">{{ __('earnings.payout_date') }}</p>
                      <p class="text-sm font-semibold text-gray-900">{{ $payout->paid_at->setTimezone($timezone)->format('d/m/Y') }}</p>
                    </div>
                    @endif

                    @if($payout->payment_method)
                    <div>
                      <p class="text-xs text-gray-500 mb-1">{{ __('earnings.payment_method') }}</p>
                      <p class="text-sm font-semibold text-gray-900">{{ __('earnings.payment_methods.' . $payout->payment_method) }}</p>
                    </div>
                    @endif

                    @if($payout->payment_reference)
                    <div>
                      <p class="text-xs text-gray-500 mb-1">{{ __('earnings.payment_reference') }}</p>
                      <p class="text-sm font-semibold text-gray-900">{{ $payout->payment_reference }}</p>
                    </div>
                    @endif
                  </div>

                  @if($payout->notes)
                  <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-500 mb-1">{{ __('earnings.notes_label') }}</p>
                    <p class="text-sm text-gray-700">{{ $payout->notes }}</p>
                  </div>
                  @endif
                </div>
              </div>
            @empty
              <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <i class="ri-inbox-line text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('earnings.no_payouts_yet') }}</h3>
                <p class="text-gray-500">{{ __('earnings.payouts_will_appear_when_issued') }}</p>
              </div>
            @endforelse
          </div>
        </div>

      </div>

  </div>

  @push('scripts')
  <script>
    // Alpine.js is already loaded, no additional scripts needed
  </script>
  @endpush

</x-layouts.teacher>
