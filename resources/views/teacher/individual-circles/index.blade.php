<x-layouts.teacher
    :title="__('teacher.individual_circles_list.page_title') . ' - ' . config('app.name', __('common.app_name'))"
    :description="__('teacher.individual_circles_list.page_description')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $filterRoute = route('teacher.individual-circles.index', ['subdomain' => $subdomain]);

    $hasActiveFilters = request('status')
        || request('search')
        || request('date_from')
        || request('date_to');

    $filterCount = (request('status') ? 1 : 0)
        + (request('search') ? 1 : 0)
        + (request('date_from') ? 1 : 0)
        + (request('date_to') ? 1 : 0);
@endphp

<div>
    <x-ui.breadcrumb :items="[['label' => __('teacher.individual_circles_list.breadcrumb')]]" view-type="teacher" />

    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('teacher.individual_circles_list.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('teacher.individual_circles_list.page_description') }}</p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-user-star-line text-lg md:text-xl text-yellow-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.individual_circles_list.total_circles') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-play-circle-line text-lg md:text-xl text-green-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['active'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.individual_circles_list.active_circles') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-pause-circle-line text-lg md:text-xl text-orange-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['paused'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.individual_circles_list.paused_circles') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-check-circle-line text-lg md:text-xl text-yellow-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['completed'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('teacher.individual_circles_list.completed_circles') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- List Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        {{-- List Header --}}
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">{{ __('teacher.individual_circles_list.list_title') }} ({{ $circles->total() }})</h2>
        </div>

        {{-- Collapsible Filters --}}
        <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <span class="flex items-center gap-2">
                    <i class="ri-filter-3-line text-yellow-500"></i>
                    {{ __('teacher.quizzes.filter') }}
                    @if($hasActiveFilters)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-yellow-500 rounded-full">{{ $filterCount }}</span>
                    @endif
                </span>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <form method="GET" action="{{ $filterRoute }}" class="px-4 md:px-6 pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.filter') }}</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="{{ __('teacher.individual_circles_list.search_placeholder') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.common.filter_status') }}</label>
                            <select name="status" id="status" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="">{{ __('teacher.individual_circles_list.filter_all') }}</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('teacher.individual_circles_list.filter_active') }}</option>
                                <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>{{ __('teacher.individual_circles_list.filter_paused') }}</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('teacher.individual_circles_list.filter_completed') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.date_from') }}</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.date_to') }}</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <button type="submit" class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <i class="ri-filter-line"></i>
                            {{ __('teacher.quizzes.filter') }}
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ $filterRoute }}"
                               class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                <i class="ri-close-line"></i>
                                {{ __('teacher.quizzes.clear_filters') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Items --}}
        @if($circles->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($circles as $circle)
                    @php
                        $statusConfig = $circle->display_status;

                        $metadata = [];
                        if ($circle->subscription && $circle->subscription->package) {
                            $metadata[] = ['icon' => 'ri-bookmark-line', 'text' => $circle->subscription->package->name];
                        }
                        $metadata[] = ['icon' => 'ri-calendar-line', 'text' => $circle->created_at->format('Y/m/d')];
                        if ($circle->sessions_count) {
                            $metadata[] = ['icon' => 'ri-play-list-line', 'text' => __('teacher.individual_circles_list.sessions_count', ['count' => $circle->sessions_count])];
                        }

                        $actions = [
                            [
                                'href' => route('individual-circles.show', ['subdomain' => $subdomain, 'circle' => $circle->id]),
                                'icon' => 'ri-eye-line',
                                'label' => __('teacher.individual_circles_list.view_details'),
                                'shortLabel' => __('teacher.individual_circles_list.view_short'),
                                'class' => 'bg-yellow-600 hover:bg-yellow-700 text-white',
                            ],
                        ];

                        if ($circle->is_active && !$circle->completed_at) {
                            $actions[] = [
                                'href' => route('teacher.individual-circles.report', ['subdomain' => $subdomain, 'circle' => $circle->id]),
                                'icon' => 'ri-bar-chart-line',
                                'label' => __('teacher.individual_circles_list.report'),
                                'shortLabel' => __('teacher.individual_circles_list.report'),
                                'class' => 'bg-green-600 hover:bg-green-700 text-white',
                            ];

                            if ($circle->subscription && $circle->subscription->student && auth()->user()->hasSupervisor()) {
                                $studentUser = ($circle->student instanceof \App\Models\User) ? $circle->student : ($circle->student->user ?? null);
                                if ($studentUser) {
                                    $actions[] = [
                                        'href' => route('chat.start-supervised', [
                                            'subdomain' => $subdomain,
                                            'teacher' => auth()->id(),
                                            'student' => $studentUser->id,
                                            'entityType' => 'quran_individual',
                                            'entityId' => $circle->id,
                                        ]),
                                        'icon' => 'ri-shield-user-line',
                                        'label' => __('teacher.individual_circles_list.message_student'),
                                        'shortLabel' => __('teacher.individual_circles_list.message_short'),
                                        'class' => 'bg-yellow-500 hover:bg-yellow-600 text-white shadow-sm',
                                        'title' => __('chat.supervised_chat_tooltip'),
                                    ];
                                }
                            }
                        }
                    @endphp

                    <x-teacher.entity-list-item
                        :title="$circle->student->name ?? __('teacher.individual_circles_list.unknown_student')"
                        :status-badge="$statusConfig['text']"
                        :status-class="$statusConfig['class']"
                        :metadata="$metadata"
                        :actions="$actions"
                        :avatar="$circle->student"
                    />
                @endforeach
            </div>

            @if($circles->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $circles->links() }}
                </div>
            @endif
        @else
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-user-star-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('teacher.individual_circles_list.empty_title') }}</h3>
                <p class="text-sm md:text-base text-gray-600">{{ __('teacher.individual_circles_list.empty_description') }}</p>
                @if($hasActiveFilters)
                    <a href="{{ $filterRoute }}"
                       class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('teacher.individual_circles_list.view_all_circles') }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

</x-layouts.teacher>
