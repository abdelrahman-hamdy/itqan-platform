@props([
    'sessions' => collect(),
    'title' => '',
    'viewType' => 'student', // student, teacher
    'showTabs' => false,
    'circle' => null,
    'emptyMessage' => 'لا توجد جلسات متاحة'
])

@php
    $now = now();

    // Helper function to get status value (handles both string and enum)
    $getStatusValue = function($session) {
        return is_object($session->status) ? $session->status->value : $session->status;
    };

    $ongoingSessions = $sessions->filter(fn($session) => $getStatusValue($session) === 'ongoing')
        ->sortBy('scheduled_at');
    $upcomingSessions = $sessions->filter(fn($session) =>
        $session->scheduled_at > $now &&
        in_array($getStatusValue($session), ['scheduled', 'ready'])
    )->sortBy('scheduled_at'); // Sort ASC - closest first
    $unscheduledSessions = $sessions->filter(fn($session) =>
        $getStatusValue($session) === 'unscheduled'
    );
    $pastSessions = $sessions->filter(fn($session) =>
        $session->scheduled_at <= $now &&
        in_array($getStatusValue($session), ['completed', 'absent'])
    )->sortByDesc('scheduled_at'); // Sort DESC - most recent first
@endphp

<div class="sessions-list-container">
    @if($title)
        <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $title }}</h3>
    @endif

    @if($showTabs && ($ongoingSessions->count() > 0 || $upcomingSessions->count() > 0 || $unscheduledSessions->count() > 0 || $pastSessions->count() > 0))
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8 space-x-reverse" aria-label="جلسات">
                @if($ongoingSessions->count() > 0)
                    <button type="button" 
                            class="session-tab border-b-2 py-2 px-1 text-sm font-medium whitespace-nowrap border-orange-500 text-orange-600" 
                            data-tab="ongoing">
                        الجلسات الجارية
                        <span class="ml-2 bg-orange-100 text-orange-600 py-0.5 px-2 rounded-full text-xs">{{ $ongoingSessions->count() }}</span>
                    </button>
                @endif
                
                @if($upcomingSessions->count() > 0)
                    <button type="button" 
                            class="session-tab border-b-2 py-2 px-1 text-sm font-medium whitespace-nowrap {{ $ongoingSessions->count() === 0 ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}" 
                            data-tab="upcoming">
                        الجلسات القادمة
                        <span class="ml-2 bg-blue-100 text-blue-600 py-0.5 px-2 rounded-full text-xs">{{ $upcomingSessions->count() }}</span>
                    </button>
                @endif
                
                @if($unscheduledSessions->count() > 0)
                    <button type="button" 
                            class="session-tab border-b-2 py-2 px-1 text-sm font-medium whitespace-nowrap {{ ($ongoingSessions->count() === 0 && $upcomingSessions->count() === 0) ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}" 
                            data-tab="unscheduled">
                        جلسات غير مجدولة
                        <span class="ml-2 bg-amber-100 text-amber-600 py-0.5 px-2 rounded-full text-xs">{{ $unscheduledSessions->count() }}</span>
                    </button>
                @endif
                
                @if($pastSessions->count() > 0)
                    <button type="button" 
                            class="session-tab border-b-2 py-2 px-1 text-sm font-medium whitespace-nowrap {{ $ongoingSessions->count() === 0 && $upcomingSessions->count() === 0 && $unscheduledSessions->count() === 0 ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}" 
                            data-tab="past">
                        الجلسات السابقة
                        <span class="ml-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs">{{ $pastSessions->count() }}</span>
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
                // Sort sessions properly: upcoming (closest first), then past (most recent first)
                $sortedSessions = $sessions->sortBy(function($session) use ($now) {
                    $scheduledAt = $session->scheduled_at;
                    if (!$scheduledAt) {
                        return PHP_INT_MAX; // Put unscheduled at the end
                    }
                    // For upcoming sessions, use positive timestamp (ASC)
                    if ($scheduledAt > $now) {
                        return $scheduledAt->timestamp;
                    }
                    // For past sessions, use negative timestamp to reverse order within past sessions
                    // Add a large offset to ensure past sessions come after upcoming ones
                    return PHP_INT_MAX - $scheduledAt->timestamp;
                });
            @endphp
            <x-sessions.session-cards :sessions="$sortedSessions" :view-type="$viewType" :circle="$circle" />
        @else
            <!-- Empty State Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-8 text-center">
                    <!-- Icon with gradient background -->
                    <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm">
                        <i class="ri-calendar-line text-3xl text-gray-500"></i>
                    </div>

                    <!-- Main message -->
                    <h4 class="text-lg font-bold text-gray-900 mb-2">{{ $emptyMessage }}</h4>

                    <!-- Context-aware subtitle -->
                    @if($viewType === 'student' && $circle)
                        <p class="text-gray-600 text-sm mb-6">سيتم إضافة الجلسات قريباً من قبل المعلم</p>
                    @elseif($viewType === 'teacher' && $circle)
                        <p class="text-gray-600 text-sm mb-6">يمكنك إضافة جلسات جديدة من لوحة إدارة الحلقة</p>
                    @else
                        <p class="text-gray-600 text-sm mb-6">ستظهر جلساتك هنا عند إنشائها</p>
                    @endif

                    <!-- Decorative element -->
                    <div class="flex items-center justify-center gap-2 text-xs text-gray-400">
                        <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                        <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                        <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                    </div>
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
