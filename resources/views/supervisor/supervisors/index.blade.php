<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';

    $hasActiveFilters = request('search')
        || request('gender')
        || (request()->has('status') && request('status') !== '')
        || (request()->has('has_responsibilities') && request('has_responsibilities') !== '');

    $filterCount = (request('search') ? 1 : 0)
        + (request('gender') ? 1 : 0)
        + (request()->has('status') && request('status') !== '' ? 1 : 0)
        + (request()->has('has_responsibilities') && request('has_responsibilities') !== '' ? 1 : 0);

    $currentSort = request('sort', 'name_asc');
@endphp

<div>
    <x-ui.breadcrumb
        :items="[['label' => __('supervisor.supervisors.page_title')]]"
        view-type="supervisor"
    />

    <!-- Page Header -->
    <div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.supervisors.page_title') }}</h1>
            <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.supervisors.page_subtitle') }}</p>
        </div>
        <a href="{{ route('manage.supervisors.create', ['subdomain' => $subdomain]) }}"
           class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap cursor-pointer">
            <i class="ri-add-line"></i>
            {{ __('supervisor.supervisors.add_supervisor') }}
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4 mb-6">
        {{-- Total Supervisors --}}
        <div class="col-span-2 sm:col-span-1 bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-user-settings-line text-blue-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xl font-bold text-gray-900">{{ $totalSupervisors }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.supervisors.total_supervisors') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-500 pt-2">
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

        {{-- Active --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-checkbox-circle-line text-green-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xl font-bold text-gray-900">{{ $activeCount }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.supervisors.active_supervisors') }}</p>
                </div>
            </div>
        </div>

        {{-- Male --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-sky-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-men-line text-sky-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xl font-bold text-gray-900">{{ $maleCount }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.supervisors.male_supervisors') }}</p>
                </div>
            </div>
        </div>

        {{-- Female --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="ri-women-line text-pink-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xl font-bold text-gray-900">{{ $femaleCount }}</p>
                    <p class="text-xs text-gray-600">{{ __('supervisor.supervisors.female_supervisors') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- List Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <!-- List Header with Sort -->
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base md:text-lg font-semibold text-gray-900">
                {{ __('supervisor.supervisors.list_title') }} ({{ $supervisors->total() }})
            </h2>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button"
                    class="cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-sort-desc"></i>
                    <span>
                        @switch($currentSort)
                            @case('name_desc') {{ __('supervisor.supervisors.sort_name_desc') }} @break
                            @case('newest') {{ __('supervisor.supervisors.sort_newest') }} @break
                            @case('oldest') {{ __('supervisor.supervisors.sort_oldest') }} @break
                            @case('responsibilities_count') {{ __('supervisor.supervisors.sort_responsibilities_count') }} @break
                            @default {{ __('supervisor.supervisors.sort_name_asc') }}
                        @endswitch
                    </span>
                    <i class="ri-arrow-down-s-line"></i>
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                    class="absolute start-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20">
                    @foreach(['name_asc', 'name_desc', 'newest', 'oldest', 'responsibilities_count'] as $sortOption)
                        <a href="{{ request()->fullUrlWithQuery(['sort' => $sortOption, 'page' => 1]) }}"
                           class="block px-4 py-2 text-sm cursor-pointer {{ $currentSort === $sortOption ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                            {{ __('supervisor.supervisors.sort_' . $sortOption) }}
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
                <form method="GET" action="{{ route('manage.supervisors.index', ['subdomain' => $subdomain]) }}" class="px-4 md:px-6 pb-4">
                    @if(request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teachers.filter_search') }}</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}"
                                   placeholder="{{ __('supervisor.supervisors.search_placeholder') }}"
                                   class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.supervisors.filter_gender') }}</label>
                            <select name="gender" id="gender" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">{{ __('supervisor.supervisors.all_genders') }}</option>
                                <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>{{ __('supervisor.teachers.male') }}</option>
                                <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>{{ __('supervisor.teachers.female') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.supervisors.filter_status') }}</label>
                            <select name="status" id="status" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">{{ __('supervisor.supervisors.all_statuses') }}</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('supervisor.teachers.active') }}</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('supervisor.teachers.inactive') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="has_responsibilities" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.supervisors.filter_has_responsibilities') }}</label>
                            <select name="has_responsibilities" id="has_responsibilities" class="min-h-[44px] w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">{{ __('supervisor.teachers.all_statuses') }}</option>
                                <option value="yes" {{ request('has_responsibilities') === 'yes' ? 'selected' : '' }}>{{ __('supervisor.supervisors.has_responsibilities') }}</option>
                                <option value="no" {{ request('has_responsibilities') === 'no' ? 'selected' : '' }}>{{ __('supervisor.supervisors.no_responsibilities') }}</option>
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
                            <a href="{{ route('manage.supervisors.index', ['subdomain' => $subdomain]) }}"
                               class="cursor-pointer min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                                <i class="ri-close-line"></i>
                                {{ __('supervisor.teachers.clear_filters') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Supervisor Items -->
        @if($supervisors->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($supervisors as $sup)
                    @php
                        $supId = $sup['user']->id;
                    @endphp

                    <div class="px-4 md:px-6 py-4 md:py-5 hover:bg-gray-50/50 transition-colors">
                        <!-- Top row: Avatar + Info -->
                        <div class="flex items-start gap-3 md:gap-4 mb-3">
                            <x-avatar :user="$sup['user']" size="md" user-type="supervisor" />
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <h3 class="text-base md:text-lg font-bold text-gray-900 truncate">{{ $sup['user']->name }}</h3>
                                    @if($sup['can_manage_teachers'])
                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                                            <i class="ri-shield-star-line"></i>
                                            {{ __('supervisor.supervisors.can_manage_teachers') }}
                                        </span>
                                    @endif
                                    @if($sup['can_manage_students'])
                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                            <i class="ri-group-2-line"></i>
                                            {{ __('supervisor.supervisors.can_manage_students') }}
                                        </span>
                                    @endif
                                    @if(!$sup['is_active'])
                                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                                            {{ __('supervisor.teachers.inactive') }}
                                        </span>
                                    @endif
                                </div>
                                <!-- Metadata row -->
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs md:text-sm text-gray-600">
                                    @if($sup['supervisor_code'])
                                        <span class="flex items-center gap-1">
                                            <i class="ri-hashtag text-gray-400"></i>
                                            <span class="font-mono">{{ $sup['supervisor_code'] }}</span>
                                        </span>
                                    @endif
                                    <span class="flex items-center gap-1">
                                        <i class="ri-book-read-line text-green-500"></i>
                                        {{ __('supervisor.supervisors.quran_teachers_assigned') }}: <strong>{{ $sup['quran_teachers_count'] }}</strong>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="ri-graduation-cap-line text-violet-500"></i>
                                        {{ __('supervisor.supervisors.academic_teachers_assigned') }}: <strong>{{ $sup['academic_teachers_count'] }}</strong>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="ri-mail-line text-gray-400"></i>
                                        {{ $sup['user']->email }}
                                    </span>
                                    @if($sup['phone'])
                                        <span class="flex items-center gap-1">
                                            <i class="ri-phone-line text-gray-400"></i>
                                            {{ $sup['phone'] }}
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
                                    {{-- Edit --}}
                                    <a href="{{ route('manage.supervisors.edit', ['subdomain' => $subdomain, 'supervisor' => $supId]) }}"
                                       class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                                        <i class="ri-edit-line"></i>
                                        {{ __('supervisor.supervisors.edit_supervisor') }}
                                    </a>

                                    {{-- Message --}}
                                    <a href="{{ route('chat.start-with', ['subdomain' => $subdomain, 'user' => $supId]) }}"
                                       class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors">
                                        <i class="ri-message-3-line"></i>
                                        {{ __('supervisor.supervisors.message_supervisor') }}
                                    </a>

                                    <span class="hidden md:inline text-gray-300 mx-0.5">|</span>

                                    {{-- Toggle Status --}}
                                    <form id="toggle-form-{{ $supId }}" method="POST"
                                          action="{{ route('manage.supervisors.toggle-status', ['subdomain' => $subdomain, 'supervisor' => $supId]) }}">
                                        @csrf
                                    </form>
                                    <button type="button"
                                        onclick="window.confirmAction({
                                            title: @js($sup['is_active'] ? __('supervisor.teachers.deactivate') : __('supervisor.teachers.activate')),
                                            message: @js($sup['is_active'] ? __('supervisor.supervisors.confirm_deactivate') : __('supervisor.supervisors.confirm_activate')),
                                            confirmText: @js($sup['is_active'] ? __('supervisor.teachers.deactivate') : __('supervisor.teachers.activate')),
                                            isDangerous: {{ $sup['is_active'] ? 'true' : 'false' }},
                                            icon: '{{ $sup['is_active'] ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}',
                                            onConfirm: () => document.getElementById('toggle-form-{{ $supId }}').submit()
                                        })"
                                        class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg transition-colors
                                            {{ $sup['is_active']
                                                ? 'bg-orange-50 text-orange-700 hover:bg-orange-100'
                                                : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                                        <i class="{{ $sup['is_active'] ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}"></i>
                                        {{ $sup['is_active'] ? __('supervisor.teachers.deactivate') : __('supervisor.teachers.activate') }}
                                    </button>

                                    {{-- Reset Password --}}
                                    <button type="button"
                                        onclick="window.dispatchEvent(new CustomEvent('open-modal-reset-password-{{ $supId }}'))"
                                        class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-yellow-50 text-yellow-700 hover:bg-yellow-100 transition-colors">
                                        <i class="ri-lock-password-line"></i>
                                        {{ __('supervisor.teachers.reset_password') }}
                                    </button>

                                    {{-- Delete --}}
                                    <form id="delete-form-{{ $supId }}" method="POST"
                                          action="{{ route('manage.supervisors.destroy', ['subdomain' => $subdomain, 'supervisor' => $supId]) }}">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <button type="button"
                                        onclick="window.confirmAction({
                                            title: @js(__('supervisor.supervisors.delete_supervisor')),
                                            message: @js(__('supervisor.supervisors.confirm_delete')),
                                            confirmText: @js(__('supervisor.supervisors.delete_supervisor')),
                                            isDangerous: true,
                                            icon: 'ri-delete-bin-line',
                                            onConfirm: () => document.getElementById('delete-form-{{ $supId }}').submit()
                                        })"
                                        class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg bg-red-50 text-red-700 hover:bg-red-100 transition-colors">
                                        <i class="ri-delete-bin-line"></i>
                                        {{ __('supervisor.supervisors.delete_supervisor') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Password Reset Modal --}}
                    <x-responsive.modal id="reset-password-{{ $supId }}" :title="__('supervisor.teachers.reset_password')" size="sm">
                        <form method="POST" action="{{ route('manage.supervisors.reset-password', ['subdomain' => $subdomain, 'supervisor' => $supId]) }}"
                              x-data="{ showPass: false, showConfirm: false }">
                            @csrf
                            <div class="space-y-4">
                                <p class="text-sm text-gray-600">{{ __('supervisor.supervisors.reset_password_description', ['name' => $sup['user']->name]) }}</p>
                                <div>
                                    <label for="new_password_{{ $supId }}" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teachers.new_password') }}</label>
                                    <div class="relative">
                                        <input :type="showPass ? 'text' : 'password'" name="new_password" id="new_password_{{ $supId }}"
                                               class="min-h-[44px] w-full px-3 py-2 pe-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                               placeholder="{{ __('supervisor.teachers.new_password_placeholder') }}"
                                               required minlength="6">
                                        <button type="button" @click="showPass = !showPass"
                                            class="cursor-pointer absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                                            <i :class="showPass ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label for="new_password_confirmation_{{ $supId }}" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teachers.confirm_password') }}</label>
                                    <div class="relative">
                                        <input :type="showConfirm ? 'text' : 'password'" name="new_password_confirmation" id="new_password_confirmation_{{ $supId }}"
                                               class="min-h-[44px] w-full px-3 py-2 pe-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                               placeholder="{{ __('supervisor.teachers.confirm_password_placeholder') }}"
                                               required minlength="6">
                                        <button type="button" @click="showConfirm = !showConfirm"
                                            class="cursor-pointer absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                                            <i :class="showConfirm ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                                        </button>
                                    </div>
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
                @endforeach
            </div>

            @if($supervisors->hasPages())
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $supervisors->withQueryString()->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="px-4 md:px-6 py-8 md:py-12 text-center">
                <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-user-settings-line text-xl md:text-2xl text-gray-400"></i>
                </div>
                @if($hasActiveFilters)
                    <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('supervisor.supervisors.no_results') }}</h3>
                    <p class="text-sm md:text-base text-gray-600">{{ __('supervisor.supervisors.no_results_description') }}</p>
                    <a href="{{ route('manage.supervisors.index', ['subdomain' => $subdomain]) }}"
                       class="cursor-pointer min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
                        {{ __('supervisor.teachers.view_all') }}
                    </a>
                @else
                    <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('supervisor.supervisors.no_supervisors') }}</h3>
                    <p class="text-gray-600 text-xs md:text-sm">{{ __('supervisor.supervisors.no_supervisors_description') }}</p>
                @endif
            </div>
        @endif
    </div>
</div>
</x-layouts.supervisor>
