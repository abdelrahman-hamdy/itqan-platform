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
    <x-reports.session-reports.tab-navigation
        :activeTab="$currentTab"
        indexRoute="teacher.session-reports.index"
        :routeParams="['subdomain' => $subdomain]"
    />

    {{-- Active Tab Content --}}
    @if($currentTab === 'students')
        <x-reports.session-reports.student-overview-tab
            indexRoute="teacher.session-reports.index"
            :routeParams="['subdomain' => $subdomain]"
            :paginatedRows="$paginatedRows"
            :entityOptions="$entityOptions"
            :totalStudents="$totalStudents ?? 0"
            :totalEntities="$totalEntities ?? 0"
            :avgAttendance="$avgAttendance ?? 0"
            :showTeacherColumn="false"
        />
    @else
        <x-reports.session-reports.session-reports-tab
            indexRoute="teacher.session-reports.index"
            :routeParams="['subdomain' => $subdomain]"
            :paginatedReports="$paginatedReports"
            :entityOptions="$entityOptions"
            :totalReports="$totalReports ?? 0"
            :presentCount="$presentCount ?? 0"
            :absentCount="$absentCount ?? 0"
            :lateCount="$lateCount ?? 0"
            :showTeacherColumn="false"
        />
    @endif
</x-layouts.teacher>
