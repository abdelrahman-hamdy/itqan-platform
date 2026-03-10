@props([
    'activeTab' => 'students',
    'indexRoute' => 'teacher.session-reports.index',
    'routeParams' => [],
])

<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex gap-6" aria-label="Tabs">
        <a href="{{ route($indexRoute, array_merge($routeParams, ['tab' => 'students'])) }}"
           class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors
               {{ $activeTab === 'students' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
            <i class="ri-group-line me-1.5"></i>
            {{ __('reports.tab_student_overview') }}
        </a>
        <a href="{{ route($indexRoute, array_merge($routeParams, ['tab' => 'sessions'])) }}"
           class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors
               {{ $activeTab === 'sessions' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
            <i class="ri-file-chart-line me-1.5"></i>
            {{ __('reports.tab_session_reports') }}
        </a>
    </nav>
</div>
