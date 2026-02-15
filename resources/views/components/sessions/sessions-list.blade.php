@props([
    'sessions' => collect(),
    'title' => '',
    'viewType' => 'student', // student, teacher
    'showTabs' => false,
    'circle' => null,
    'emptyMessage' => null
])

@php
    $emptyMessage = $emptyMessage ?? __('components.sessions.sessions_list.no_available');
@endphp

@php
    use App\Enums\SessionStatus;

    $now = now();

    // Helper function to get status value (handles both string and enum)
    $getStatusValue = function($session) {
        return is_object($session->status) ? $session->status->value : $session->status;
    };

    $ongoingSessions = $sessions->filter(fn($session) => $getStatusValue($session) === SessionStatus::ONGOING->value)
        ->sortBy('scheduled_at');
    $upcomingSessions = $sessions->filter(fn($session) =>
        $session->scheduled_at > $now &&
        in_array($getStatusValue($session), [SessionStatus::SCHEDULED->value, SessionStatus::READY->value])
    )->sortBy('scheduled_at'); // Sort ASC - closest first
    $unscheduledSessions = $sessions->filter(fn($session) =>
        $getStatusValue($session) === SessionStatus::UNSCHEDULED->value
    );
    $pastSessions = $sessions->filter(fn($session) =>
        $session->scheduled_at <= $now &&
        in_array($getStatusValue($session), [SessionStatus::COMPLETED->value, SessionStatus::ABSENT->value])
    )->sortByDesc('scheduled_at'); // Sort DESC - most recent first
@endphp

<div class="sessions-list-container">
    @if($title)
        <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $title }}</h3>
    @endif

    @if($showTabs && ($ongoingSessions->count() > 0 || $upcomingSessions->count() > 0 || $unscheduledSessions->count() > 0 || $pastSessions->count() > 0))
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex gap-8" aria-label="{{ __('components.sessions.header.session') }}">
                @if($ongoingSessions->count() > 0)
                    <button type="button"
                            class="session-tab border-b-2 py-2 px-1 text-sm font-medium whitespace-nowrap border-orange-500 text-orange-600"
                            data-tab="ongoing">
                        {{ __('components.sessions.tabs.ongoing') }}
                        <span class="ms-2 bg-orange-100 text-orange-600 py-0.5 px-2 rounded-full text-xs">{{ $ongoingSessions->count() }}</span>
                    </button>
                @endif

                @if($upcomingSessions->count() > 0)
                    <button type="button"
                            class="session-tab border-b-2 py-2 px-1 text-sm font-medium whitespace-nowrap {{ $ongoingSessions->count() === 0 ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            data-tab="upcoming">
                        {{ __('components.sessions.tabs.upcoming') }}
                        <span class="ms-2 bg-blue-100 text-blue-600 py-0.5 px-2 rounded-full text-xs">{{ $upcomingSessions->count() }}</span>
                    </button>
                @endif

                @if($unscheduledSessions->count() > 0)
                    <button type="button"
                            class="session-tab border-b-2 py-2 px-1 text-sm font-medium whitespace-nowrap {{ ($ongoingSessions->count() === 0 && $upcomingSessions->count() === 0) ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            data-tab="unscheduled">
                        {{ __('components.sessions.tabs.unscheduled') }}
                        <span class="ms-2 bg-amber-100 text-amber-600 py-0.5 px-2 rounded-full text-xs">{{ $unscheduledSessions->count() }}</span>
                    </button>
                @endif

                @if($pastSessions->count() > 0)
                    <button type="button"
                            class="session-tab border-b-2 py-2 px-1 text-sm font-medium whitespace-nowrap {{ $ongoingSessions->count() === 0 && $upcomingSessions->count() === 0 && $unscheduledSessions->count() === 0 ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            data-tab="past">
                        {{ __('components.sessions.tabs.past') }}
                        <span class="ms-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs">{{ $pastSessions->count() }}</span>
                    </button>
                @endif
            </nav>
        </div>

        <!-- Tab Contents -->
        @if($ongoingSessions->count() > 0)
            <div class="session-tab-content" data-tab-content="ongoing" style="{{ $ongoingSessions->count() > 0 ? '' : 'display: none;' }}">
                <x-sessions.session-cards :sessions="$ongoingSessions" :view-type="$viewType" :circle="$circle" />
            </div>
        @endif

        @if($upcomingSessions->count() > 0)
            <div class="session-tab-content" data-tab-content="upcoming" style="{{ $ongoingSessions->count() === 0 ? '' : 'display: none;' }}">
                <x-sessions.session-cards :sessions="$upcomingSessions" :view-type="$viewType" :circle="$circle" />
            </div>
        @endif

        @if($unscheduledSessions->count() > 0)
            <div class="session-tab-content" data-tab-content="unscheduled" style="{{ ($ongoingSessions->count() === 0 && $upcomingSessions->count() === 0) ? '' : 'display: none;' }}">
                <x-sessions.session-cards :sessions="$unscheduledSessions" :view-type="$viewType" :circle="$circle" />
            </div>
        @endif

        @if($pastSessions->count() > 0)
            <div class="session-tab-content" data-tab-content="past" style="{{ $ongoingSessions->count() === 0 && $upcomingSessions->count() === 0 && $unscheduledSessions->count() === 0 ? '' : 'display: none;' }}">
                <x-sessions.session-cards :sessions="$pastSessions" :view-type="$viewType" :circle="$circle" />
            </div>
        @endif
    @else
        <!-- Simple list without tabs -->
        @if($sessions->count() > 0)
            @php
                // Sort sessions: ongoing first, then upcoming (closest first), then past (most recent first)
                $sortedSessions = $sessions->sortBy(function($session) use ($now, $getStatusValue) {
                    // Ongoing sessions always come first
                    if ($getStatusValue($session) === SessionStatus::ONGOING->value) {
                        return [0, 0];
                    }
                    if ($getStatusValue($session) === SessionStatus::READY->value) {
                        return [1, $session->scheduled_at ? $session->scheduled_at->timestamp : 0];
                    }
                    $scheduledAt = $session->scheduled_at;
                    if (!$scheduledAt) {
                        return [4, PHP_INT_MAX]; // Unscheduled at the end
                    }
                    if ($scheduledAt > $now) {
                        return [2, $scheduledAt->timestamp]; // Upcoming ASC
                    }
                    return [3, PHP_INT_MAX - $scheduledAt->timestamp]; // Past DESC
                });
            @endphp
            <x-sessions.session-cards :sessions="$sortedSessions" :view-type="$viewType" :circle="$circle" />
        @else
            @php
                if ($viewType === 'student' && $circle) {
                    $emptyDesc = __('components.sessions.sessions_list.student_circle_message');
                } elseif ($viewType === 'teacher' && $circle) {
                    $emptyDesc = __('components.sessions.sessions_list.teacher_circle_message');
                } else {
                    $emptyDesc = __('components.sessions.sessions_list.sessions_appear_here');
                }
            @endphp
            <div class="bg-gray-50 rounded-xl py-12 text-center">
                <div class="max-w-md mx-auto px-4">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-calendar-line text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $emptyMessage }}</h3>
                    <p class="text-sm text-gray-600">{{ $emptyDesc }}</p>
                </div>
            </div>
        @endif
    @endif
</div>

<!-- Tab functionality script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Use the same unified approach as session-cards.blade.php
    function initializeEnhancedSessionTabs(container = document) {
        const tabs = container.querySelectorAll('.session-tab');
        const tabContents = container.querySelectorAll('.session-tab-content');

        // Only proceed if we have both tabs and content
        if (!tabs.length || !tabContents.length) return;

        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetTab = this.getAttribute('data-tab');
                
                // Find the container to scope the changes
                const tabContainer = this.closest('.sessions-list-container') || container;
                const scopedTabs = tabContainer.querySelectorAll('.session-tab');
                const scopedContents = tabContainer.querySelectorAll('.session-tab-content');
                
                // Remove active classes from all tabs in this container
                scopedTabs.forEach(t => {
                    t.classList.remove('border-primary-500', 'text-primary-600', 'border-orange-500', 'text-orange-600', 'border-amber-500', 'text-amber-600');
                    t.classList.add('border-transparent', 'text-gray-500');
                });
                
                // Add active class to clicked tab
                this.classList.remove('border-transparent', 'text-gray-500');
                if (targetTab === 'ongoing') {
                    this.classList.add('border-orange-500', 'text-orange-600');
                } else if (targetTab === 'upcoming') {
                    this.classList.add('border-primary-500', 'text-primary-600');
                } else if (targetTab === 'unscheduled') {
                    this.classList.add('border-amber-500', 'text-amber-600');
                } else { // past
                    this.classList.add('border-primary-500', 'text-primary-600');
                }
                
                // Hide all tab contents in this container
                scopedContents.forEach(content => {
                    content.style.display = 'none';
                    content.classList.add('hidden');
                    content.classList.remove('block');
                });
                
                // Show target tab content
                const targetContent = tabContainer.querySelector(`[data-tab-content="${targetTab}"]`);
                if (targetContent) {
                    targetContent.style.display = 'block';
                    targetContent.classList.remove('hidden');
                    targetContent.classList.add('block');
                }
                
                // Trigger custom event for other components to listen
                tabContainer.dispatchEvent(new CustomEvent('enhancedSessionTabChanged', {
                    detail: { targetTab, targetContent }
                }));
            });
        });
    }

    // Initialize tabs immediately
    initializeEnhancedSessionTabs();
    
    // Re-initialize when new content is loaded (for dynamic content like Livewire updates)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && (node.querySelector('.session-tab') || node.classList?.contains('session-tab'))) {
                        initializeEnhancedSessionTabs(node);
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Listen for Livewire updates and reinitialize tabs
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('message.processed', (message, component) => {
            // Small delay to ensure DOM is updated
            setTimeout(() => {
                initializeEnhancedSessionTabs();
            }, 100);
        });
    }
});
</script>
