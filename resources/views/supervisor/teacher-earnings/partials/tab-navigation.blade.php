@props(['activeTab' => 'details', 'subdomain'])

<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex gap-6" aria-label="Tabs">
        <a href="{{ route('manage.teacher-earnings.index', ['subdomain' => $subdomain]) }}"
           class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors
               {{ $activeTab === 'details' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
            <i class="ri-file-list-3-line me-1.5"></i>
            {{ __('supervisor.teacher_earnings.tab_details') }}
        </a>
        <a href="{{ route('manage.teacher-earnings.teacher-summary', ['subdomain' => $subdomain]) }}"
           class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors
               {{ $activeTab === 'summary' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
            <i class="ri-group-line me-1.5"></i>
            {{ __('supervisor.teacher_earnings.tab_teacher_summary') }}
        </a>
    </nav>
</div>
