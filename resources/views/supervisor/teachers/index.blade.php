<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
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
                <p class="text-lg md:text-xl font-bold text-gray-900">{{ $teachers->count() }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.teachers.total_teachers') }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-book-read-line text-green-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-xl font-bold text-gray-900">{{ $teachers->where('type', 'quran')->count() }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.dashboard.quran_teachers') }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 md:p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-graduation-cap-line text-violet-600"></i>
            </div>
            <div>
                <p class="text-lg md:text-xl font-bold text-gray-900">{{ $teachers->where('type', 'academic')->count() }}</p>
                <p class="text-xs text-gray-600">{{ __('supervisor.dashboard.academic_teachers') }}</p>
            </div>
        </div>
    </div>

    <!-- Teachers List -->
    @if($teachers->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
            @foreach($teachers as $teacher)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                    <!-- Header with gradient -->
                    <div class="p-4 md:p-5 {{ $teacher['type'] === 'quran' ? 'bg-gradient-to-br from-green-50 to-emerald-50 border-b border-green-100' : 'bg-gradient-to-br from-violet-50 to-purple-50 border-b border-violet-100' }}">
                        <div class="flex items-center gap-3">
                            <x-avatar :user="$teacher['user']" size="md" :user-type="$teacher['type'] === 'quran' ? 'quran_teacher' : 'academic_teacher'" />
                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm md:text-base font-bold text-gray-900 truncate">{{ $teacher['user']->name }}</h3>
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full mt-1
                                    {{ $teacher['type'] === 'quran' ? 'bg-green-100 text-green-700' : 'bg-violet-100 text-violet-700' }}">
                                    <i class="{{ $teacher['type'] === 'quran' ? 'ri-book-read-line' : 'ri-graduation-cap-line' }}"></i>
                                    {{ $teacher['type_label'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="p-4 md:p-5">
                        <div class="space-y-2 mb-4">
                            @if($teacher['code'])
                                <div class="flex items-center gap-2 text-xs md:text-sm text-gray-600">
                                    <i class="ri-hashtag text-gray-400"></i>
                                    <span>{{ __('supervisor.teachers.teacher_code') }}: <span class="font-mono font-medium text-gray-900">{{ $teacher['code'] }}</span></span>
                                </div>
                            @endif
                            <div class="flex items-center gap-2 text-xs md:text-sm text-gray-600">
                                <i class="ri-book-open-line text-gray-400"></i>
                                <span>{{ __('supervisor.teachers.active_entities') }}: <span class="font-bold text-gray-900">{{ $teacher['active_entities'] }}</span></span>
                            </div>
                            <div class="flex items-center gap-2 text-xs md:text-sm text-gray-600">
                                <i class="ri-mail-line text-gray-400"></i>
                                <span class="truncate">{{ $teacher['user']->email }}</span>
                            </div>
                        </div>

                        <!-- Action -->
                        <a href="{{ route($teacher['entity_route'], ['subdomain' => $subdomain, 'teacher_id' => $teacher['user']->id]) }}"
                           class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium rounded-lg transition-colors
                               {{ $teacher['type'] === 'quran'
                                   ? 'bg-green-600 hover:bg-green-700 text-white'
                                   : 'bg-violet-600 hover:bg-violet-700 text-white' }}">
                            <i class="ri-eye-line"></i>
                            {{ $teacher['type'] === 'quran' ? __('supervisor.teachers.view_circles') : __('supervisor.teachers.view_lessons') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-12 md:py-16">
            <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                <i class="ri-team-line text-2xl md:text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('supervisor.teachers.no_teachers') }}</h3>
            <p class="text-gray-600 text-xs md:text-sm">{{ __('supervisor.teachers.no_teachers_description') }}</p>
        </div>
    @endif
</div>

</x-layouts.supervisor>
