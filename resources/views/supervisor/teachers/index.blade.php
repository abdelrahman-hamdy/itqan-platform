<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $hasActiveFilters = request('search')
        || request('type')
        || request('gender')
        || (request()->has('status') && request('status') !== '');

    $filterCount = (request('search') ? 1 : 0)
        + (request('type') ? 1 : 0)
        + (request('gender') ? 1 : 0)
        + (request()->has('status') && request('status') !== '' ? 1 : 0);

    $currentSort = request('sort', 'name_asc');
@endphp

<div>
    <x-ui.breadcrumb
        :items="[['label' => __('supervisor.teachers.page_title')]]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.teachers.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.teachers.page_subtitle') }}</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-team-line text-indigo-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-xl font-bold text-gray-900">{{ $totalTeachers }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.teachers.total_teachers') }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-book-read-line text-green-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-xl font-bold text-gray-900">{{ $quranCount }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.dashboard.quran_teachers') }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-graduation-cap-line text-violet-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-xl font-bold text-gray-900">{{ $academicCount }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.dashboard.academic_teachers') }}</p>
            </div>
        </div>
    </div>

    <!-- List Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <!-- List Header with Sort -->
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">
                {{ __('supervisor.teachers.list_title') }} ({{ $teachers->total() }})
            </h2>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-sort-desc"></i>
                    <span>
                        @switch($currentSort)
                            @case('name_desc') {{ __('supervisor.teachers.sort_name_desc') }} @break
                            @case('entities_desc') {{ __('supervisor.teachers.sort_entities_desc') }} @break
                            @case('entities_asc') {{ __('supervisor.teachers.sort_entities_asc') }} @break
                            @default {{ __('supervisor.teachers.sort_name_asc') }}
                        @endswitch
                    </span>
                    <i class="ri-arrow-down-s-line"></i>
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                    class="absolute start-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20">
                    @foreach(['name_asc', 'name_desc', 'entities_desc', 'entities_asc'] as $sortOption)
                        <a href="{{ request()->fullUrlWithQuery(['sort' => $sortOption, 'page' => 1]) }}"
                           class="block px-4 py-2 text-sm {{ $currentSort === $sortOption ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                            {{ __('supervisor.teachers.sort_' . $sortOption) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Collapsible Filters -->
        <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
            <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <span class="flex items-center gap-2">
                    <i class="ri-filter-3-line text-indigo-500"></i>
                    {{ __('supervisor.teachers.filter') }}
                    @if($hasActiveFilters)
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-indigo-500 rounded-full">{{ $filterCount }}</span>
                    @endif
                </span>
                <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>
            <div x-show="open" x-collapse>
                <form method="GET" action="{{ route('manage.teachers.index', ['subdomain' => $subdomain]) }}" class="px-4 md:px-6 pb-4">
                    @if(request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teachers.filter_search') }}</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}"
                                   placeholder="{{ __('supervisor.teachers.search_placeholder') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teachers.filter_type') }}</label>
                            <select name="type" id="type" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">{{ __('supervisor.teachers.all_types') }}</option>
                                <option value="quran" {{ request('type') === 'quran' ? 'selected' : '' }}>{{ __('supervisor.teachers.teacher_type_quran') }}</option>
                                <option value="academic" {{ request('type') === 'academic' ? 'selected' : '' }}>{{ __('supervisor.teachers.teacher_type_academic') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teachers.filter_gender') }}</label>
                            <select name="gender" id="gender" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">{{ __('supervisor.teachers.all_genders') }}</option>
                                <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>{{ __('supervisor.teachers.male') }}</option>
                                <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>{{ __('supervisor.teachers.female') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teachers.filter_status') }}</label>
                            <select name="status" id="status" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">{{ __('supervisor.teachers.all_statuses') }}</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('supervisor.teachers.active') }}</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('supervisor.teachers.inactive') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        <button type="submit"
                            class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <i class="ri-filter-line"></i>
                            {{ __('supervisor.teachers.filter') }}
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ route('manage.teachers.index', ['subdomain' => $subdomain]) }}"
                               class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                <i class="ri-close-line"></i>
                                {{ __('supervisor.teachers.clear_filters') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Teacher Items -->
        @if($teachers->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($teachers as $teacher)
                    @php
                        $isQuran = $teacher['type'] === 'quran';

                        $metadata = [
                            ['icon' => 'ri-hashtag', 'iconColor' => 'text-gray-400', 'text' => $teacher['code'] ?: '-'],
                            ['icon' => 'ri-book-open-line', 'iconColor' => $isQuran ? 'text-green-500' : 'text-violet-500', 'text' => __('supervisor.teachers.active_entities') . ': ' . $teacher['active_entities']],
                            ['icon' => 'ri-mail-line', 'iconColor' => 'text-gray-400', 'text' => $teacher['user']->email],
                        ];
                        if ($teacher['phone']) {
                            $metadata[] = ['icon' => 'ri-phone-line', 'iconColor' => 'text-gray-400', 'text' => $teacher['phone']];
                        }

                        $actions = [];

                        // View entities
                        $actions[] = [
                            'href' => route($teacher['entity_route'], ['subdomain' => $subdomain, 'teacher_id' => $teacher['user']->id]),
                            'icon' => $isQuran ? 'ri-book-read-line' : 'ri-graduation-cap-line',
                            'label' => $isQuran ? __('supervisor.teachers.view_circles') : __('supervisor.teachers.view_lessons'),
                            'shortLabel' => $isQuran ? __('supervisor.teachers.view_circles') : __('supervisor.teachers.view_lessons'),
                            'class' => $isQuran ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-violet-600 hover:bg-violet-700 text-white',
                        ];

                        // View sessions
                        $actions[] = [
                            'href' => route('manage.sessions.index', ['subdomain' => $subdomain, 'teacher_id' => $teacher['user']->id]),
                            'icon' => 'ri-calendar-event-line',
                            'label' => __('supervisor.teachers.view_sessions'),
                            'shortLabel' => __('supervisor.teachers.view_sessions'),
                            'class' => 'bg-blue-600 hover:bg-blue-700 text-white',
                        ];

                        // View reports
                        $actions[] = [
                            'href' => route('manage.session-reports.index', ['subdomain' => $subdomain, 'teacher_id' => $teacher['user']->id]),
                            'icon' => 'ri-file-chart-line',
                            'label' => __('supervisor.teachers.view_reports'),
                            'shortLabel' => __('supervisor.teachers.view_reports'),
                            'class' => 'bg-amber-600 hover:bg-amber-700 text-white',
                        ];

                        // Message
                        $actions[] = [
                            'href' => route('chat.start-with', ['subdomain' => $subdomain, 'user' => $teacher['user']->id]),
                            'icon' => 'ri-message-3-line',
                            'label' => __('supervisor.teachers.message_teacher'),
                            'shortLabel' => __('supervisor.teachers.message_teacher'),
                            'class' => 'bg-gray-100 hover:bg-gray-200 text-gray-700',
                        ];
                    @endphp

                    <x-teacher.entity-list-item
                        :title="$teacher['user']->name"
                        :avatar="$teacher['user']"
                        :status-badge="$teacher['type_label']"
                        :status-class="$isQuran ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700'"
                        :metadata="$metadata"
                        :actions="$actions"
                    />

                    {{-- Admin-only actions --}}
                    @if($isAdmin)
                        <div class="px-4 md:px-6 pb-3 -mt-2 flex flex-wrap items-center gap-2" x-data="{ confirmDelete: false, confirmReset: false }">
                            <span class="text-xs text-gray-400 me-1">{{ __('supervisor.teachers.admin_actions') }}:</span>

                            {{-- Toggle Status --}}
                            <form method="POST" action="{{ route('manage.teachers.toggle-status', ['subdomain' => $subdomain, 'teacher' => $teacher['user']->id]) }}">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg transition-colors
                                        {{ $teacher['is_active']
                                            ? 'bg-orange-50 text-orange-700 hover:bg-orange-100'
                                            : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                                    <i class="{{ $teacher['is_active'] ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}"></i>
                                    {{ $teacher['is_active'] ? __('supervisor.teachers.inactive') : __('supervisor.teachers.active') }}
                                </button>
                            </form>

                            {{-- Reset Password --}}
                            <button @click="confirmReset = true" type="button"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-yellow-50 text-yellow-700 hover:bg-yellow-100 transition-colors">
                                <i class="ri-lock-password-line"></i>
                                {{ __('supervisor.teachers.reset_password') }}
                            </button>

                            {{-- Reset Password Confirmation --}}
                            <template x-if="confirmReset">
                                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="confirmReset = false">
                                    <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm mx-4 w-full">
                                        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('supervisor.teachers.reset_password') }}</h3>
                                        <p class="text-sm text-gray-600 mb-4">{{ __('supervisor.teachers.confirm_reset_password') }}</p>
                                        <div class="flex items-center gap-3 justify-end">
                                            <button @click="confirmReset = false" type="button"
                                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                                {{ __('common.cancel') }}
                                            </button>
                                            <form method="POST" action="{{ route('manage.teachers.reset-password', ['subdomain' => $subdomain, 'teacher' => $teacher['user']->id]) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700">
                                                    {{ __('supervisor.teachers.reset_password') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Delete Teacher --}}
                            <button @click="confirmDelete = true" type="button"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-red-50 text-red-700 hover:bg-red-100 transition-colors">
                                <i class="ri-delete-bin-line"></i>
                                {{ __('supervisor.teachers.delete_teacher') }}
                            </button>

                            {{-- Delete Confirmation --}}
                            <template x-if="confirmDelete">
                                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="confirmDelete = false">
                                    <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm mx-4 w-full">
                                        <h3 class="text-lg font-bold text-red-600 mb-2">{{ __('supervisor.teachers.delete_teacher') }}</h3>
                                        <p class="text-sm text-gray-600 mb-4">{{ __('supervisor.teachers.confirm_delete') }}</p>
                                        <div class="flex items-center gap-3 justify-end">
                                            <button @click="confirmDelete = false" type="button"
                                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                                {{ __('common.cancel') }}
                                            </button>
                                            <form method="POST" action="{{ route('manage.teachers.destroy', ['subdomain' => $subdomain, 'teacher' => $teacher['user']->id]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                                    {{ __('supervisor.teachers.delete_teacher') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    @endif
                @endforeach
            </div>

            @if($teachers->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $teachers->withQueryString()->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-team-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                @if($hasActiveFilters)
                    <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('supervisor.teachers.no_results') }}</h3>
                    <p class="text-sm md:text-base text-gray-600">{{ __('supervisor.teachers.no_results_description') }}</p>
                    <a href="{{ route('manage.teachers.index', ['subdomain' => $subdomain]) }}"
                       class="min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('supervisor.teachers.view_all') }}
                    </a>
                @else
                    <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('supervisor.teachers.no_teachers') }}</h3>
                    <p class="text-gray-600 text-xs md:text-sm">{{ __('supervisor.teachers.no_teachers_description') }}</p>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- Success Flash --}}
@if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        class="fixed bottom-4 start-4 z-50 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-center gap-2">
        <i class="ri-checkbox-circle-line"></i>
        {{ session('success') }}
        <button @click="show = false" class="ms-2 hover:opacity-80"><i class="ri-close-line"></i></button>
    </div>
@endif

</x-layouts.supervisor>
