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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4 mb-6">
        {{-- Total Teachers --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-team-line text-blue-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900">{{ $totalTeachers }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.teachers.total_teachers') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-500 border-t border-gray-100 pt-2.5">
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    {{ __('supervisor.teachers.active') }}: <strong class="text-gray-700">{{ $activeCount }}</strong>
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-red-400"></span>
                    {{ __('supervisor.teachers.inactive') }}: <strong class="text-gray-700">{{ $inactiveCount }}</strong>
                </span>
            </div>
        </div>

        {{-- Quran Teachers --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-book-read-line text-amber-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900">{{ $quranCount }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.dashboard.quran_teachers') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-500 border-t border-gray-100 pt-2.5">
                <span class="flex items-center gap-1">
                    <i class="ri-men-line text-blue-500"></i>
                    {{ __('supervisor.teachers.male') }}: <strong class="text-gray-700">{{ $quranMale }}</strong>
                </span>
                <span class="flex items-center gap-1">
                    <i class="ri-women-line text-pink-500"></i>
                    {{ __('supervisor.teachers.female') }}: <strong class="text-gray-700">{{ $quranFemale }}</strong>
                </span>
            </div>
        </div>

        {{-- Academic Teachers --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-graduation-cap-line text-violet-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900">{{ $academicCount }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.dashboard.academic_teachers') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-500 border-t border-gray-100 pt-2.5">
                <span class="flex items-center gap-1">
                    <i class="ri-men-line text-blue-500"></i>
                    {{ __('supervisor.teachers.male') }}: <strong class="text-gray-700">{{ $academicMale }}</strong>
                </span>
                <span class="flex items-center gap-1">
                    <i class="ri-women-line text-pink-500"></i>
                    {{ __('supervisor.teachers.female') }}: <strong class="text-gray-700">{{ $academicFemale }}</strong>
                </span>
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
                    class="cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-sort-desc"></i>
                    <span>
                        @switch($currentSort)
                            @case('name_desc') {{ __('supervisor.teachers.sort_name_desc') }} @break
                            @case('entities_desc') {{ __('supervisor.teachers.sort_entities_desc') }} @break
                            @case('entities_asc') {{ __('supervisor.teachers.sort_entities_asc') }} @break
                            @case('rating_desc') {{ __('supervisor.teachers.sort_rating_desc') }} @break
                            @case('rating_asc') {{ __('supervisor.teachers.sort_rating_asc') }} @break
                            @case('newest') {{ __('supervisor.teachers.sort_newest') }} @break
                            @case('oldest') {{ __('supervisor.teachers.sort_oldest') }} @break
                            @default {{ __('supervisor.teachers.sort_name_asc') }}
                        @endswitch
                    </span>
                    <i class="ri-arrow-down-s-line"></i>
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                    class="absolute start-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20">
                    @foreach(['name_asc', 'name_desc', 'entities_desc', 'entities_asc', 'rating_desc', 'rating_asc', 'newest', 'oldest'] as $sortOption)
                        <a href="{{ request()->fullUrlWithQuery(['sort' => $sortOption, 'page' => 1]) }}"
                           class="block px-4 py-2 text-sm cursor-pointer {{ $currentSort === $sortOption ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                            {{ __('supervisor.teachers.sort_' . $sortOption) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Collapsible Filters -->
        <div x-data="{ open: {{ $hasActiveFilters ? 'true' : 'false' }} }" class="border-b border-gray-200">
            <button type="button" @click="open = !open"
                class="cursor-pointer w-full flex items-center justify-between px-4 md:px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
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
                            class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <i class="ri-filter-line"></i>
                            {{ __('supervisor.teachers.filter') }}
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ route('manage.teachers.index', ['subdomain' => $subdomain]) }}"
                               class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
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
                        $teacherId = $teacher['user']->id;
                    @endphp

                    <div class="px-4 md:px-6 py-4 md:py-5 hover:bg-gray-50/50 transition-colors">
                        <!-- Top row: Avatar + Info + Type Badge -->
                        <div class="flex items-start gap-3 md:gap-4 mb-3">
                            <x-avatar :user="$teacher['user']" size="md" :user-type="$isQuran ? 'quran_teacher' : 'academic_teacher'" />
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <h3 class="text-base md:text-lg font-bold text-gray-900 truncate">{{ $teacher['user']->name }}</h3>
                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                                        {{ $isQuran ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700' }}">
                                        <i class="{{ $isQuran ? 'ri-book-read-line' : 'ri-graduation-cap-line' }}"></i>
                                        {{ $teacher['type_label'] }}
                                    </span>
                                    @if(!$teacher['is_active'])
                                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                                            {{ __('supervisor.teachers.inactive') }}
                                        </span>
                                    @endif
                                </div>
                                <!-- Metadata row -->
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs md:text-sm text-gray-600">
                                    @if($teacher['code'])
                                        <span class="flex items-center gap-1">
                                            <i class="ri-hashtag text-gray-400"></i>
                                            <span class="font-mono">{{ $teacher['code'] }}</span>
                                        </span>
                                    @endif
                                    <span class="flex items-center gap-1">
                                        <i class="ri-book-open-line {{ $isQuran ? 'text-green-500' : 'text-violet-500' }}"></i>
                                        {{ __('supervisor.teachers.active_entities') }}: <strong>{{ $teacher['active_entities'] }}</strong>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="ri-star-fill text-amber-400"></i>
                                        {{ number_format($teacher['rating'], 1) }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="ri-mail-line text-gray-400"></i>
                                        {{ $teacher['user']->email }}
                                    </span>
                                    @if($teacher['phone'])
                                        <span class="flex items-center gap-1">
                                            <i class="ri-phone-line text-gray-400"></i>
                                            {{ $teacher['phone'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Collapsible Action Buttons -->
                        <div x-data="{ expanded: false }" class="ms-0 md:ms-14">
                            <button @click="expanded = !expanded" type="button"
                                class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 text-xs md:text-sm font-medium rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors mb-2">
                                <i class="ri-apps-line"></i>
                                {{ __('supervisor.teachers.actions') }}
                                <i class="ri-arrow-down-s-line transition-transform" :class="{ 'rotate-180': expanded }"></i>
                            </button>
                            <div x-show="expanded" x-collapse>
                                <div class="flex flex-wrap items-center gap-2">
                                    {{-- Primary: View Entities --}}
                                    @if($isQuran)
                                        <a href="{{ route('manage.group-circles.index', ['subdomain' => $subdomain, 'teacher_id' => $teacherId]) }}"
                                           class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors">
                                            <i class="ri-team-line"></i>
                                            {{ __('supervisor.teachers.view_circles') }}
                                        </a>
                                        <a href="{{ route('manage.individual-circles.index', ['subdomain' => $subdomain, 'teacher_id' => $teacherId]) }}"
                                           class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                                            <i class="ri-user-line"></i>
                                            {{ __('supervisor.teachers.view_individual_circles') }}
                                        </a>
                                        <a href="{{ route('manage.trial-sessions.index', ['subdomain' => $subdomain, 'teacher_id' => $teacherId]) }}"
                                           class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-teal-600 hover:bg-teal-700 text-white transition-colors">
                                            <i class="ri-gift-line"></i>
                                            {{ __('supervisor.teachers.view_trial_sessions') }}
                                        </a>
                                    @else
                                        <a href="{{ route('manage.academic-lessons.index', ['subdomain' => $subdomain, 'teacher_id' => $teacherId]) }}"
                                           class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-violet-600 hover:bg-violet-700 text-white transition-colors">
                                            <i class="ri-graduation-cap-line"></i>
                                            {{ __('supervisor.teachers.view_lessons') }}
                                        </a>
                                        <a href="{{ route('manage.interactive-courses.index', ['subdomain' => $subdomain, 'teacher_id' => $teacherId]) }}"
                                           class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-purple-600 hover:bg-purple-700 text-white transition-colors">
                                            <i class="ri-live-line"></i>
                                            {{ __('supervisor.teachers.view_interactive_courses') }}
                                        </a>
                                    @endif

                                    {{-- Sessions & Reports --}}
                                    <a href="{{ route('manage.sessions.index', ['subdomain' => $subdomain, 'teacher_id' => $teacherId]) }}"
                                       class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                                        <i class="ri-calendar-event-line"></i>
                                        {{ __('supervisor.teachers.view_sessions') }}
                                    </a>
                                    <a href="{{ route('manage.session-reports.index', ['subdomain' => $subdomain, 'teacher_id' => $teacherId]) }}"
                                       class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-amber-600 hover:bg-amber-700 text-white transition-colors">
                                        <i class="ri-file-chart-line"></i>
                                        {{ __('supervisor.teachers.view_reports') }}
                                    </a>

                                    {{-- Message --}}
                                    <a href="{{ route('chat.start-with', ['subdomain' => $subdomain, 'user' => $teacherId]) }}"
                                       class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors">
                                        <i class="ri-message-3-line"></i>
                                        {{ __('supervisor.teachers.message_teacher') }}
                                    </a>

                                    {{-- Admin-only actions --}}
                                    @if($isAdmin)
                                        <span class="hidden md:inline text-gray-300 mx-0.5">|</span>

                                        {{-- Toggle Status --}}
                                        <form id="toggle-form-{{ $teacherId }}" method="POST"
                                              action="{{ route('manage.teachers.toggle-status', ['subdomain' => $subdomain, 'teacher' => $teacherId]) }}">
                                            @csrf
                                        </form>
                                        <button type="button"
                                            onclick="window.confirmAction({
                                                title: @js($teacher['is_active'] ? __('supervisor.teachers.deactivate') : __('supervisor.teachers.activate')),
                                                message: @js($teacher['is_active'] ? __('supervisor.teachers.confirm_deactivate') : __('supervisor.teachers.confirm_activate')),
                                                confirmText: @js($teacher['is_active'] ? __('supervisor.teachers.deactivate') : __('supervisor.teachers.activate')),
                                                isDangerous: {{ $teacher['is_active'] ? 'true' : 'false' }},
                                                icon: '{{ $teacher['is_active'] ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}',
                                                onConfirm: () => document.getElementById('toggle-form-{{ $teacherId }}').submit()
                                            })"
                                            class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg transition-colors
                                                {{ $teacher['is_active']
                                                    ? 'bg-orange-50 text-orange-700 hover:bg-orange-100'
                                                    : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                                            <i class="{{ $teacher['is_active'] ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}"></i>
                                            {{ $teacher['is_active'] ? __('supervisor.teachers.deactivate') : __('supervisor.teachers.activate') }}
                                        </button>

                                        {{-- Reset Password --}}
                                        <button type="button"
                                            onclick="window.dispatchEvent(new CustomEvent('open-modal-reset-password-{{ $teacherId }}'))"
                                            class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-yellow-50 text-yellow-700 hover:bg-yellow-100 transition-colors">
                                            <i class="ri-lock-password-line"></i>
                                            {{ __('supervisor.teachers.reset_password') }}
                                        </button>

                                        {{-- Delete --}}
                                        <form id="delete-form-{{ $teacherId }}" method="POST"
                                              action="{{ route('manage.teachers.destroy', ['subdomain' => $subdomain, 'teacher' => $teacherId]) }}">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <button type="button"
                                            onclick="window.confirmAction({
                                                title: @js(__('supervisor.teachers.delete_teacher')),
                                                message: @js(__('supervisor.teachers.confirm_delete')),
                                                confirmText: @js(__('supervisor.teachers.delete_teacher')),
                                                isDangerous: true,
                                                icon: 'ri-delete-bin-line',
                                                onConfirm: () => document.getElementById('delete-form-{{ $teacherId }}').submit()
                                            })"
                                            class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-red-50 text-red-700 hover:bg-red-100 transition-colors">
                                            <i class="ri-delete-bin-line"></i>
                                            {{ __('supervisor.teachers.delete_teacher') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Password Reset Modal --}}
                    @if($isAdmin)
                        <x-responsive.modal id="reset-password-{{ $teacherId }}" :title="__('supervisor.teachers.reset_password')" size="sm">
                            <form method="POST" action="{{ route('manage.teachers.reset-password', ['subdomain' => $subdomain, 'teacher' => $teacherId]) }}">
                                @csrf
                                <div class="space-y-4">
                                    <p class="text-sm text-gray-600">{{ __('supervisor.teachers.reset_password_description', ['name' => $teacher['user']->name]) }}</p>
                                    <div>
                                        <label for="new_password_{{ $teacherId }}" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teachers.new_password') }}</label>
                                        <input type="text" name="new_password" id="new_password_{{ $teacherId }}"
                                               class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                               placeholder="{{ __('supervisor.teachers.new_password_placeholder') }}"
                                               required minlength="6">
                                    </div>
                                </div>
                                <x-slot:footer>
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" @click="open = false"
                                            class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                            {{ __('common.cancel') }}
                                        </button>
                                        <button type="submit"
                                            class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700">
                                            {{ __('supervisor.teachers.reset_password') }}
                                        </button>
                                    </div>
                                </x-slot:footer>
                            </form>
                        </x-responsive.modal>
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
                       class="cursor-pointer min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
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

{{-- Flash Messages --}}
@if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        class="fixed bottom-4 start-4 z-50 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-center gap-2">
        <i class="ri-checkbox-circle-line"></i>
        {{ session('success') }}
        <button @click="show = false" class="cursor-pointer ms-2 hover:opacity-80"><i class="ri-close-line"></i></button>
    </div>
@endif
@if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        class="fixed bottom-4 start-4 z-50 bg-red-600 text-white px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-center gap-2">
        <i class="ri-error-warning-line"></i>
        {{ session('error') }}
        <button @click="show = false" class="cursor-pointer ms-2 hover:opacity-80"><i class="ri-close-line"></i></button>
    </div>
@endif

</x-layouts.supervisor>
