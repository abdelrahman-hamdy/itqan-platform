<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.sidebar.dashboard'), 'route' => route('supervisor.dashboard', ['subdomain' => $subdomain])],
            ['label' => __('supervisor.profile.page_title')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.profile.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.profile.page_subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Profile Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                        @if($user->avatar)
                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full object-cover">
                        @else
                            <i class="ri-user-line text-2xl text-indigo-600"></i>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg md:text-xl font-bold text-gray-900">{{ $user->name }}</h2>
                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                        @if($profile?->supervisor_code)
                            <span class="inline-flex items-center gap-1 mt-2 text-xs px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-700">
                                <i class="ri-hashtag"></i>
                                {{ $profile->supervisor_code }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Personal Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h3 class="text-base font-bold text-gray-900 mb-4">{{ __('supervisor.profile.personal_info') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">{{ __('supervisor.profile.full_name') }}</p>
                        <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">{{ __('supervisor.profile.email') }}</p>
                        <p class="text-sm font-medium text-gray-900">{{ $user->email }}</p>
                    </div>
                    @if($profile?->phone)
                        <div>
                            <p class="text-xs text-gray-500 mb-1">{{ __('supervisor.profile.phone') }}</p>
                            <p class="text-sm font-medium text-gray-900">{{ $profile->phone }}</p>
                        </div>
                    @endif
                    @if($profile?->gender)
                        <div>
                            <p class="text-xs text-gray-500 mb-1">{{ __('supervisor.profile.gender') }}</p>
                            <p class="text-sm font-medium text-gray-900">{{ $profile->gender }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            @if($profile?->notes)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-3">{{ __('supervisor.profile.notes') }}</h3>
                    <p class="text-sm text-gray-600">{{ $profile->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Sidebar Stats -->
        <div class="lg:col-span-1 space-y-4 md:space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
                <h3 class="text-sm font-bold text-gray-900 mb-4">{{ __('supervisor.profile.responsibilities') }}</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i class="ri-book-read-line text-green-600"></i>
                            <span class="text-sm text-gray-700">{{ __('supervisor.profile.quran_teachers') }}</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">{{ $quranTeacherCount }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-violet-50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i class="ri-graduation-cap-line text-violet-600"></i>
                            <span class="text-sm text-gray-700">{{ __('supervisor.profile.academic_teachers') }}</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">{{ $academicTeacherCount }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <i class="ri-slideshow-3-line text-blue-600"></i>
                            <span class="text-sm text-gray-700">{{ __('supervisor.profile.interactive_courses') }}</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">{{ $interactiveCourseCount }}</span>
                    </div>
                </div>
            </div>

            @if($profile?->performance_rating)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
                    <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.profile.performance_rating') }}</h3>
                    <div class="flex items-center gap-2">
                        <div class="text-2xl font-bold text-amber-500">{{ number_format($profile->performance_rating, 1) }}</div>
                        <div class="flex items-center">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="ri-star-{{ $i <= round($profile->performance_rating) ? 'fill' : 'line' }} text-amber-400"></i>
                            @endfor
                        </div>
                    </div>
                </div>
            @endif

            <a href="{{ route('supervisor.dashboard', ['subdomain' => $subdomain]) }}"
               class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                <i class="ri-arrow-right-line"></i>
                {{ __('supervisor.profile.back_to_dashboard') }}
            </a>
        </div>
    </div>
</div>

</x-layouts.supervisor>
