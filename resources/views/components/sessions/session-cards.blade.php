@props([
    'sessions' => collect(),
    'viewType' => 'student', // student, teacher
    'circle' => null,
    'title' => null,
    'subtitle' => null,
    'perPage' => 10
])

@php
    $title = $title ?? __('components.sessions.session_cards.title');
@endphp

@php
    use App\Enums\SessionStatus;

    // Helper function to get status value (handles both string and enum)
    $getStatusValue = function($session) {
        return is_object($session->status) ? $session->status->value : $session->status;
    };

    $totalSessions = $sessions->count();
    $comingSessions = $sessions->filter(function($session) use ($getStatusValue) {
        return in_array($getStatusValue($session), [SessionStatus::SCHEDULED->value, SessionStatus::READY->value, SessionStatus::ONGOING->value]);
    });
    $passedSessions = $sessions->filter(function($session) use ($getStatusValue) {
        return in_array($getStatusValue($session), [SessionStatus::COMPLETED->value, SessionStatus::CANCELLED->value]);
    });

    // Generate a unique ID for this component instance
    $componentId = 'sc-' . uniqid();
@endphp

<div class="bg-white rounded-none md:rounded-xl shadow-none md:shadow-sm border-0 md:border md:border-gray-200" id="{{ $componentId }}" data-per-page="{{ $perPage }}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 md:p-6 border-b border-gray-200">
        <h3 class="text-base md:text-xl font-bold text-gray-900">{{ $title }}</h3>
        <div class="flex items-center gap-2 text-xs md:text-sm text-gray-500">
            <span class="bg-blue-100 text-blue-700 px-2.5 md:px-3 py-1 rounded-full font-medium">
                {{ __('components.sessions.session_cards.total') }}: {{ $totalSessions }}
            </span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 overflow-x-auto">
        <nav class="flex gap-4 md:gap-8 px-4 md:px-6 min-w-max" id="sessionTabs">
            <button class="session-tab active min-h-[44px] py-3 md:py-4 px-1 border-b-2 border-blue-500 font-medium text-blue-600 text-xs md:text-sm whitespace-nowrap" data-tab="all">
                {{ __('components.sessions.tabs.all') }} ({{ $totalSessions }})
            </button>
            <button class="session-tab min-h-[44px] py-3 md:py-4 px-1 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700 text-xs md:text-sm whitespace-nowrap" data-tab="coming">
                {{ __('components.sessions.tabs.coming') }} ({{ $comingSessions->count() }})
            </button>
            <button class="session-tab min-h-[44px] py-3 md:py-4 px-1 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700 text-xs md:text-sm whitespace-nowrap" data-tab="passed">
                {{ __('components.sessions.tabs.passed') }} ({{ $passedSessions->count() }})
            </button>
        </nav>
    </div>

    <!-- Sessions Content -->
    <div class="p-4 md:p-6">
        <!-- All Sessions Tab -->
        <div id="all-sessions" class="session-tab-content block" data-tab-name="all">
            @if($sessions->count() > 0)
                <div class="space-y-3 md:space-y-4 session-items-container">
                    @foreach($sessions as $session)
                        <div class="session-paginated-item">
                            <x-sessions.session-item :session="$session" :circle="$circle" :view-type="$viewType" />
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state
                    icon="ri-calendar-line"
                    title="{{ __('components.sessions.empty_states.no_sessions') }}"
                    description="{{ __('components.sessions.empty_states.no_sessions_message') }}"
                    color="gray"
                    variant="compact"
                />
            @endif
        </div>

        <!-- Coming Sessions Tab -->
        <div id="coming-sessions" class="session-tab-content hidden" data-tab-name="coming">
            @if($comingSessions->count() > 0)
                <div class="space-y-3 md:space-y-4 session-items-container">
                    @foreach($comingSessions as $session)
                        <div class="session-paginated-item">
                            <x-sessions.session-item :session="$session" :circle="$circle" :view-type="$viewType" />
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state
                    icon="ri-calendar-check-line"
                    title="{{ __('components.sessions.empty_states.no_upcoming') }}"
                    description="{{ __('components.sessions.empty_states.no_upcoming_message') }}"
                    color="blue"
                    variant="compact"
                />
            @endif
        </div>

        <!-- Passed Sessions Tab -->
        <div id="passed-sessions" class="session-tab-content hidden" data-tab-name="passed">
            @if($passedSessions->count() > 0)
                <div class="space-y-3 md:space-y-4 session-items-container">
                    @foreach($passedSessions as $session)
                        <div class="session-paginated-item">
                            <x-sessions.session-item :session="$session" :circle="$circle" :view-type="$viewType" />
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state
                    icon="ri-history-line"
                    title="{{ __('components.sessions.empty_states.no_completed') }}"
                    description="{{ __('components.sessions.empty_states.no_completed_message') }}"
                    color="gray"
                    variant="compact"
                />
            @endif
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('{{ $componentId }}');
    if (!container) return;

    const perPage = parseInt(container.dataset.perPage) || 10;
    const tabPages = {};
    const labels = {
        previous: @json(__('components.sessions.pagination.previous')),
        next: @json(__('components.sessions.pagination.next')),
        showing: @json(__('components.sessions.pagination.showing')),
    };

    function initPagination(tabContent) {
        const tabName = tabContent.dataset.tabName;
        const items = tabContent.querySelectorAll('.session-paginated-item');
        const total = items.length;

        if (total <= perPage) {
            // Remove existing pagination if items decreased below perPage
            const existingPag = tabContent.querySelector('.session-pagination');
            if (existingPag) existingPag.remove();
            // Show all items
            items.forEach(item => item.style.display = '');
            return;
        }

        if (!tabPages[tabName]) {
            tabPages[tabName] = 1;
        }

        showPage(tabContent, tabName, tabPages[tabName]);
    }

    function showPage(tabContent, tabName, page) {
        const items = tabContent.querySelectorAll('.session-paginated-item');
        const total = items.length;
        const totalPages = Math.ceil(total / perPage);

        page = Math.max(1, Math.min(page, totalPages));
        tabPages[tabName] = page;

        const start = (page - 1) * perPage;
        const end = start + perPage;

        items.forEach((item, index) => {
            item.style.display = (index >= start && index < end) ? '' : 'none';
        });

        renderPaginationControls(tabContent, tabName, page, totalPages, total, start, end);
    }

    function renderPaginationControls(tabContent, tabName, currentPage, totalPages, total, start, end) {
        let paginationEl = tabContent.querySelector('.session-pagination');
        if (!paginationEl) {
            paginationEl = document.createElement('div');
            paginationEl.className = 'session-pagination mt-4 md:mt-6';
            tabContent.appendChild(paginationEl);
        }

        const actualEnd = Math.min(end, total);
        const showingText = labels.showing
            .replace(':from', start + 1)
            .replace(':to', actualEnd)
            .replace(':total', total);

        let pagesHtml = '';
        const maxVisible = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);
        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        if (startPage > 1) {
            pagesHtml += pageButton(tabName, 1, '1', false);
            if (startPage > 2) {
                pagesHtml += '<span class="px-1 text-gray-400">...</span>';
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            pagesHtml += pageButton(tabName, i, i.toString(), i === currentPage);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pagesHtml += '<span class="px-1 text-gray-400">...</span>';
            }
            pagesHtml += pageButton(tabName, totalPages, totalPages.toString(), false);
        }

        paginationEl.innerHTML = `
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-gray-100">
                <span class="text-xs md:text-sm text-gray-500 order-2 sm:order-1">${showingText}</span>
                <div class="flex items-center gap-1 order-1 sm:order-2">
                    <button type="button" class="session-page-btn px-3 py-1.5 text-xs md:text-sm rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white" data-tab="${tabName}" data-page="${currentPage - 1}" ${currentPage <= 1 ? 'disabled' : ''}>
                        ${labels.previous}
                    </button>
                    <div class="flex items-center gap-0.5 mx-1">
                        ${pagesHtml}
                    </div>
                    <button type="button" class="session-page-btn px-3 py-1.5 text-xs md:text-sm rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white" data-tab="${tabName}" data-page="${currentPage + 1}" ${currentPage >= totalPages ? 'disabled' : ''}>
                        ${labels.next}
                    </button>
                </div>
            </div>
        `;
    }

    function pageButton(tabName, page, label, isActive) {
        if (isActive) {
            return `<button type="button" class="session-page-btn min-w-[32px] px-2 py-1.5 text-xs md:text-sm rounded-md bg-blue-500 text-white font-medium" data-tab="${tabName}" data-page="${page}" disabled>${label}</button>`;
        }
        return `<button type="button" class="session-page-btn min-w-[32px] px-2 py-1.5 text-xs md:text-sm rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors" data-tab="${tabName}" data-page="${page}">${label}</button>`;
    }

    // Initialize pagination for all tabs
    container.querySelectorAll('.session-tab-content').forEach(initPagination);

    // Handle pagination button clicks
    container.addEventListener('click', function(e) {
        const pageBtn = e.target.closest('.session-page-btn');
        if (pageBtn && !pageBtn.disabled) {
            const tabName = pageBtn.dataset.tab;
            const page = parseInt(pageBtn.dataset.page);
            const tabContent = container.querySelector(`[data-tab-name="${tabName}"]`);
            if (tabContent) {
                showPage(tabContent, tabName, page);
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    });

    // Handle tab switching - reset to page 1
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('session-tab')) {
            e.preventDefault();

            const clickedTab = e.target;
            const targetTab = clickedTab.getAttribute('data-tab');

            if (!targetTab) return;

            // Update all tabs
            container.querySelectorAll('.session-tab').forEach(tab => {
                tab.classList.remove('active', 'border-blue-500', 'text-blue-600');
                tab.classList.add('border-transparent', 'text-gray-500');
            });

            // Activate clicked tab
            clickedTab.classList.add('active', 'border-blue-500', 'text-blue-600');
            clickedTab.classList.remove('border-transparent', 'text-gray-500');

            // Hide all tab contents
            container.querySelectorAll('.session-tab-content').forEach(content => {
                content.style.display = 'none';
                content.classList.add('hidden');
                content.classList.remove('block');
            });

            // Show target content
            const targetContent = container.querySelector(`#${targetTab}-sessions`);
            if (targetContent) {
                targetContent.style.display = 'block';
                targetContent.classList.remove('hidden');
                targetContent.classList.add('block');

                // Reset to page 1 when switching tabs
                tabPages[targetTab] = 1;
                initPagination(targetContent);
            }
        }
    });
});
</script>
