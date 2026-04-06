<x-layouts.teacher title="{{ $academy->name }} - {{ __('earnings.my_earnings') }}">
  <x-slot name="description">{{ __('earnings.my_earnings') }} - {{ $academy->name }}</x-slot>

  @php
      $subdomain = request()->route('subdomain') ?? $academy->subdomain ?? 'itqan-academy';

      $hasActiveFilters = $currentMonth || $currentSource || ($startDate ?? null) || ($endDate ?? null);

      $filterCount = ($currentMonth ? 1 : 0)
          + ($currentSource ? 1 : 0)
          + (($startDate ?? null) || ($endDate ?? null) ? 1 : 0);
  @endphp

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      <!-- Header -->
      <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('earnings.my_earnings') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('earnings.track_your_earnings_description') }}</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
        {{-- Total Earnings This Month --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-money-dollar-circle-line text-blue-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-lg md:text-xl font-bold text-gray-900">{{ number_format($stats['totalEarningsThisMonth'], 2) }} {{ $currencySymbol }}</p>
                    <p class="text-xs text-gray-600 truncate">{{ __('earnings.this_month') }}</p>
                </div>
            </div>
        </div>

        {{-- Finalized --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-checkbox-circle-line text-green-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-lg md:text-xl font-bold text-gray-900">{{ number_format($stats['finalizedAmount'], 2) }} {{ $currencySymbol }}</p>
                    <p class="text-xs text-gray-600 truncate">{{ __('earnings.finalized_earnings') }}</p>
                </div>
            </div>
        </div>

        {{-- Unpaid --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-time-line text-yellow-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-lg md:text-xl font-bold text-gray-900">{{ number_format($stats['unpaidAmount'], 2) }} {{ $currencySymbol }}</p>
                    <p class="text-xs text-gray-600 truncate">{{ __('earnings.unpaid_earnings') }}</p>
                </div>
            </div>
        </div>

        {{-- Sessions Count --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-calendar-check-line text-indigo-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-lg md:text-xl font-bold text-gray-900">{{ $stats['sessionsCount'] }}</p>
                    <p class="text-xs text-gray-600 truncate">{{ __('earnings.sessions_count') }}</p>
                </div>
            </div>
        </div>
      </div>

      <!-- List Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <!-- Header -->
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">
                {{ __('earnings.list_title') }} ({{ $earnings->total() }})
            </h2>
        </div>

        <!-- Collapsible Filters -->
        <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
            <button type="button" @click="open = !open"
                class="cursor-pointer w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <span class="flex items-center gap-2">
                    <i class="ri-filter-3-line text-green-500"></i>
                    {{ __('earnings.filter') }}
                    @if($hasActiveFilters)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-green-500 rounded-full">{{ $filterCount }}</span>
                    @endif
                </span>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <form method="GET" action="{{ route('teacher.earnings', ['subdomain' => $subdomain]) }}" class="px-4 md:px-6 pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                        {{-- Month --}}
                        <div>
                            <label for="month" class="block text-sm font-medium text-gray-700 mb-1">{{ __('earnings.filter_month') }}</label>
                            <select name="month" id="month" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <option value="">{{ __('earnings.all_months') }}</option>
                                @foreach($availableMonths as $m)
                                    <option value="{{ $m['value'] }}" {{ $currentMonth === $m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Source --}}
                        <div>
                            <label for="source" class="block text-sm font-medium text-gray-700 mb-1">{{ __('earnings.filter_source') }}</label>
                            <select name="source" id="source" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <option value="">{{ __('earnings.all_sources') }}</option>
                                @foreach($sources as $src)
                                    <option value="{{ $src['value'] }}" {{ $currentSource === $src['value'] ? 'selected' : '' }}>{{ $src['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Start Date --}}
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('earnings.filter_start_date') }}</label>
                            <input type="date" name="start_date" id="start_date" value="{{ $startDate ?? '' }}"
                                class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>

                        {{-- End Date --}}
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('earnings.filter_end_date') }}</label>
                            <input type="date" name="end_date" id="end_date" value="{{ $endDate ?? '' }}"
                                class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">{{ __('earnings.date_range_hint') }}</p>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <button type="submit"
                            class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <i class="ri-filter-line"></i>
                            {{ __('earnings.filter') }}
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ route('teacher.earnings', ['subdomain' => $subdomain]) }}"
                               class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                <i class="ri-close-line"></i>
                                {{ __('earnings.clear_filters') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Earnings Items -->
        @if($earnings->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($earnings as $earning)
                    @php
                        $session = $earning->session;
                        $sessionType = match ($earning->session_type) {
                            \App\Models\QuranSession::class => 'quran',
                            \App\Models\AcademicSession::class => 'academic',
                            \App\Models\InteractiveCourseSession::class => 'interactive',
                            default => 'other',
                        };

                        if ($sessionType === 'quran' && $session) {
                            $isIndividualQuran = $session->session_type === 'individual';
                            $sourceLabel = $isIndividualQuran
                                ? __('earnings.source_types.individual_circle')
                                : __('earnings.source_types.group_circle');
                        } else {
                            $sourceLabel = match ($sessionType) {
                                'academic' => __('earnings.source_types.academic_lesson'),
                                'interactive' => __('earnings.source_types.interactive_course'),
                                default => __('earnings.source_other'),
                            };
                        }

                        $sourceBadgeClass = match ($sessionType) {
                            'quran' => 'bg-green-100 text-green-700',
                            'academic' => 'bg-violet-100 text-violet-700',
                            'interactive' => 'bg-blue-100 text-blue-700',
                            default => 'bg-gray-100 text-gray-700',
                        };

                        $sourceName = null;
                        if ($session) {
                            if ($sessionType === 'quran') {
                                $sourceName = ($session->session_type === 'individual')
                                    ? $session->individualCircle?->name
                                    : $session->circle?->name;
                            } elseif ($sessionType === 'academic') {
                                $sourceName = $session->academicIndividualLesson?->subject?->name
                                    ? $session->academicIndividualLesson->subject->name.' - '.$session->student?->name
                                    : $session->student?->name;
                            } elseif ($sessionType === 'interactive') {
                                $sourceName = $session->course?->title;
                            }
                        }

                        $statusLabel = $earning->is_disputed
                            ? __('earnings.disputed_status')
                            : ($earning->is_finalized
                                ? __('earnings.finalized_status')
                                : __('earnings.pending_status'));

                        $statusClass = $earning->is_disputed
                            ? 'bg-red-100 text-red-700'
                            : ($earning->is_finalized
                                ? 'bg-green-100 text-green-700'
                                : 'bg-amber-100 text-amber-700');
                    @endphp

                    <div class="px-4 md:px-6 py-4 md:py-5 hover:bg-gray-50/50 transition-colors">
                        <div class="flex items-start gap-3 md:gap-4">
                            {{-- Source Icon --}}
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                                {{ $sessionType === 'quran' ? 'bg-green-100' : '' }}
                                {{ $sessionType === 'academic' ? 'bg-violet-100' : '' }}
                                {{ $sessionType === 'interactive' ? 'bg-blue-100' : '' }}
                                {{ $sessionType === 'other' ? 'bg-gray-100' : '' }}">
                                @if($sessionType === 'quran')
                                    <i class="ri-book-open-line text-green-600"></i>
                                @elseif($sessionType === 'academic')
                                    <i class="ri-graduation-cap-line text-violet-600"></i>
                                @elseif($sessionType === 'interactive')
                                    <i class="ri-presentation-line text-blue-600"></i>
                                @else
                                    <i class="ri-file-list-line text-gray-600"></i>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full {{ $sourceBadgeClass }}">
                                        {{ $sourceLabel }}
                                    </span>
                                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs md:text-sm text-gray-600">
                                    <span class="flex items-center gap-1 font-bold text-gray-900">
                                        <i class="ri-money-dollar-circle-line text-green-500"></i>
                                        {{ number_format($earning->amount, 2) }} {{ $currencySymbol }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="ri-calculator-line text-gray-400"></i>
                                        {{ $earning->calculation_method_label }}
                                    </span>
                                    @if($earning->session_completed_at)
                                        <span class="flex items-center gap-1">
                                            <i class="ri-calendar-line text-gray-400"></i>
                                            {{ $earning->session_completed_at->setTimezone($timezone)->format('d/m/Y - h:i A') }}
                                        </span>
                                    @elseif($earning->earning_month)
                                        <span class="flex items-center gap-1">
                                            <i class="ri-calendar-line text-gray-400"></i>
                                            {{ $earning->earning_month->locale('ar')->translatedFormat('F Y') }}
                                        </span>
                                    @endif
                                    @if($sourceName)
                                        <span class="flex items-center gap-1">
                                            <i class="ri-book-open-line text-gray-400"></i>
                                            {{ $sourceName }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($earnings->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $earnings->withQueryString()->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-money-dollar-circle-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                @if($hasActiveFilters)
                    <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('earnings.no_results') }}</h3>
                    <p class="text-sm md:text-base text-gray-600">{{ __('earnings.no_results_description') }}</p>
                    <a href="{{ route('teacher.earnings', ['subdomain' => $subdomain]) }}"
                       class="cursor-pointer min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('earnings.view_all') }}
                    </a>
                @else
                    <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('earnings.no_earnings_yet') }}</h3>
                    <p class="text-gray-600 text-xs md:text-sm">{{ __('earnings.earnings_will_appear_after_sessions') }}</p>
                @endif
            </div>
        @endif
      </div>

  </div>

</x-layouts.teacher>
