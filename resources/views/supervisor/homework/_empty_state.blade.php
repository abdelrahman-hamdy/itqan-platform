@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $hasActiveFilters = request('teacher_id') || request('date_from') || request('date_to');
@endphp

<div class="px-4 md:px-6 py-8 md:py-12 text-center">
    <div class="w-14 h-14 md:w-16 md:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
        <i class="ri-book-2-line text-xl md:text-2xl text-gray-400"></i>
    </div>
    @if($hasActiveFilters)
        <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">{{ __('supervisor.homework.no_results') }}</h3>
        <p class="text-sm md:text-base text-gray-600">{{ __('supervisor.homework.no_results_description') }}</p>
        <a href="{{ route('manage.homework.index', ['subdomain' => $subdomain]) }}"
           class="cursor-pointer min-h-[44px] inline-flex items-center justify-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors mt-4">
            {{ __('supervisor.homework.view_all') }}
        </a>
    @else
        <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('supervisor.homework.no_homework') }}</h3>
        <p class="text-gray-600 text-xs md:text-sm">{{ __('supervisor.homework.no_homework_description') }}</p>
    @endif
</div>
