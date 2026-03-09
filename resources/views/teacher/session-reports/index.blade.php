<x-layouts.teacher :title="__('teacher.reports.page_title') . ' - ' . config('app.name')">
@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $currentTab = $activeTab ?? 'students';
@endphp

    {{-- Breadcrumbs --}}
    <x-ui.breadcrumb :items="[['label' => __('teacher.reports.breadcrumb')]]" view-type="teacher" />

    {{-- Page Header --}}
    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('teacher.reports.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('teacher.reports.page_description') }}</p>
    </div>

    {{-- Tab Navigation --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6" aria-label="Tabs">
            <a href="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain, 'tab' => 'students']) }}"
               class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors
                   {{ $currentTab === 'students' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                <i class="ri-group-line me-1.5"></i>
                {{ __('teacher.reports.tab_student_overview') }}
            </a>
            <a href="{{ route('teacher.session-reports.index', ['subdomain' => $subdomain, 'tab' => 'sessions']) }}"
               class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors
                   {{ $currentTab === 'sessions' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                <i class="ri-file-chart-line me-1.5"></i>
                {{ __('teacher.reports.tab_session_reports') }}
            </a>
        </nav>
    </div>

    {{-- Active Tab Content --}}
    @if($currentTab === 'students')
        @include('teacher.session-reports.partials.student-overview-tab')
    @else
        @include('teacher.session-reports.partials.session-reports-tab')
    @endif
</x-layouts.teacher>
