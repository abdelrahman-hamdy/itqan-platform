<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $filterRoute = route('manage.trial-sessions.index', ['subdomain' => $subdomain]);

    $hasActiveFilters = request('status')
        || request('search')
        || request('date_from')
        || request('date_to')
        || request('teacher_id');

    $filterCount = (request('status') ? 1 : 0)
        + (request('search') ? 1 : 0)
        + (request('date_from') ? 1 : 0)
        + (request('date_to') ? 1 : 0)
        + (request('teacher_id') ? 1 : 0);
@endphp

<div>
    <x-ui.breadcrumb :items="[['label' => __('supervisor.trial_sessions.page_title')]]" view-type="supervisor" />

    <div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.trial_sessions.page_title') }}</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.trial_sessions.page_subtitle') }}</p>
        </div>
        <livewire:supervisor.create-trial-request-modal />
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-user-add-line text-lg md:text-xl text-amber-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('supervisor.trial_sessions.total_requests') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-time-line text-lg md:text-xl text-yellow-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['pending'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('supervisor.trial_sessions.pending_requests') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-calendar-check-line text-lg md:text-xl text-green-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['scheduled'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('supervisor.trial_sessions.scheduled_requests') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-check-double-line text-lg md:text-xl text-blue-600"></i>
                </div>
                <div>
                    <p class="text-lg md:text-2xl font-bold text-gray-900">{{ $stats['completed'] }}</p>
                    <p class="text-xs md:text-sm text-gray-600">{{ __('supervisor.trial_sessions.completed_requests') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- List Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        {{-- List Header --}}
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">{{ __('supervisor.trial_sessions.page_title') }} ({{ $trialRequests->total() }})</h2>
        </div>

        {{-- Collapsible Filters --}}
        <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <span class="flex items-center gap-2">
                    <i class="ri-filter-3-line text-amber-500"></i>
                    {{ __('teacher.quizzes.filter') }}
                    @if($hasActiveFilters)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-amber-500 rounded-full">{{ $filterCount }}</span>
                    @endif
                </span>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <form method="GET" action="{{ $filterRoute }}" class="px-4 md:px-6 pb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                        <div>
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.common.filter_by_teacher') }}</label>
                            <select name="teacher_id" id="teacher_id" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                <option value="">{{ __('supervisor.common.all_teachers') }}</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher['id'] }}" {{ request('teacher_id') == $teacher['id'] ? 'selected' : '' }}>
                                        {{ $teacher['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.common.filter_search') }}</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="{{ __('supervisor.trial_sessions.search_placeholder') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.common.filter_status') }}</label>
                            <select name="status" id="status" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                <option value="">{{ __('teacher.trial_sessions_list.filter_all') }}</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('teacher.trial_sessions_list.filter_pending') }}</option>
                                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>{{ __('teacher.trial_sessions_list.filter_scheduled') }}</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('teacher.trial_sessions_list.filter_completed') }}</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('teacher.trial_sessions_list.filter_cancelled') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.date_from') }}</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mt-3">
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">{{ __('teacher.quizzes.date_to') }}</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <button type="submit" class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors text-sm font-medium">
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
        @if($trialRequests->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($trialRequests as $request)
                    @php
                        $status = $request->status;
                        $statusValue = $status instanceof \App\Enums\TrialRequestStatus ? $status->value : $status;

                        $statusConfig = match($statusValue) {
                            'pending' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => __('teacher.trial_sessions_list.status_pending')],
                            'scheduled' => ['class' => 'bg-green-100 text-green-800', 'text' => __('teacher.trial_sessions_list.status_scheduled')],
                            'completed' => ['class' => 'bg-emerald-100 text-emerald-800', 'text' => __('teacher.trial_sessions_list.status_completed')],
                            'cancelled' => ['class' => 'bg-gray-100 text-gray-800', 'text' => __('teacher.trial_sessions_list.status_cancelled')],
                            default => ['class' => 'bg-gray-100 text-gray-800', 'text' => $statusValue]
                        };

                        $teacherName = $request->teacher?->user?->name ?? '';

                        $metadata = [];
                        $metadata[] = ['icon' => 'ri-user-line', 'text' => __('supervisor.common.teacher_badge', ['name' => $teacherName])];
                        if ($request->current_level) {
                            $levelText = \App\Models\QuranTrialRequest::LEVELS[$request->current_level] ?? $request->current_level;
                            $metadata[] = ['icon' => 'ri-bar-chart-line', 'text' => $levelText];
                        }
                        if ($request->trialSession && $request->trialSession->scheduled_at) {
                            $metadata[] = ['icon' => 'ri-calendar-line', 'text' => $request->trialSession->scheduled_at->format('Y/m/d H:i')];
                        }
                        $metadata[] = ['icon' => 'ri-calendar-2-line', 'text' => $request->created_at->format('Y/m/d')];

                        $actions = [
                            [
                                'href' => route('manage.trial-sessions.show', ['subdomain' => $subdomain, 'trialRequest' => $request->id]),
                                'icon' => 'ri-eye-line',
                                'label' => __('supervisor.common.view_details'),
                                'shortLabel' => __('supervisor.common.view'),
                                'class' => 'bg-amber-600 hover:bg-amber-700 text-white',
                            ],
                        ];

                        $studentName = $request->student?->name ?? $request->student_name ?? '';
                    @endphp

                    <x-teacher.entity-list-item
                        :title="$studentName"
                        :status-badge="$statusConfig['text']"
                        :status-class="$statusConfig['class']"
                        :metadata="$metadata"
                        :actions="$actions"
                        :avatar="$request->student"
                        icon="ri-user-add-line"
                        icon-bg-class="bg-gradient-to-br from-amber-500 to-orange-600"
                    />
                @endforeach
            </div>

            @if($trialRequests->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $trialRequests->links() }}
                </div>
            @endif
        @else
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-user-add-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('supervisor.common.no_data') }}</h3>
                <p class="text-sm md:text-base text-gray-600">{{ __('supervisor.trial_sessions.page_subtitle') }}</p>
                @if($hasActiveFilters)
                    <a href="{{ $filterRoute }}"
                       class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('supervisor.common.back_to_list') }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

</x-layouts.supervisor>
