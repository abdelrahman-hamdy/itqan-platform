@props([
    'sessions' => collect(),
    'viewType' => 'student', // student, teacher
    'circle' => null,
    'title' => null,
    'subtitle' => null
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
@endphp

<div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200">
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
            <button class="session-tab min-h-[44px] py-3 md:py-4 px-1 border-b-2 border-transparent font-medium text-purple-500 hover:text-purple-700 text-xs md:text-sm whitespace-nowrap" data-tab="testing">
                {{ __('components.sessions.tabs.testing') }} (8)
            </button>
        </nav>
    </div>
    
    <!-- Sessions Content -->
    <div class="p-4 md:p-6">
        <!-- All Sessions Tab -->
        <div id="all-sessions" class="session-tab-content block">
            @if($sessions->count() > 0)
                <div class="space-y-3 md:space-y-4">
                    @foreach($sessions as $session)
                        <x-sessions.session-item :session="$session" :circle="$circle" :view-type="$viewType" />
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 md:py-12">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 md:mb-6 shadow-sm">
                        <i class="ri-calendar-line text-2xl md:text-3xl text-gray-500"></i>
                    </div>
                    <p class="text-base md:text-lg font-bold text-gray-900 mb-1.5 md:mb-2">{{ __('components.sessions.empty_states.no_sessions') }}</p>
                    <p class="text-xs md:text-sm text-gray-600 mb-4 md:mb-6">{{ __('components.sessions.empty_states.no_sessions_message') }}</p>
                    <div class="flex items-center justify-center gap-2">
                        <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                        <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                        <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Coming Sessions Tab -->
        <div id="coming-sessions" class="session-tab-content hidden">
            @if($comingSessions->count() > 0)
                <div class="space-y-3 md:space-y-4">
                    @foreach($comingSessions as $session)
                        <x-sessions.session-item :session="$session" :circle="$circle" :view-type="$viewType" />
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 md:py-12">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center mx-auto mb-4 md:mb-6 shadow-sm">
                        <i class="ri-calendar-check-line text-2xl md:text-3xl text-blue-600"></i>
                    </div>
                    <p class="text-base md:text-lg font-bold text-gray-900 mb-1.5 md:mb-2">{{ __('components.sessions.empty_states.no_upcoming') }}</p>
                    <p class="text-xs md:text-sm text-gray-600 mb-4 md:mb-6">{{ __('components.sessions.empty_states.no_upcoming_message') }}</p>
                    <div class="flex items-center justify-center gap-2">
                        <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                        <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                        <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Passed Sessions Tab -->
        <div id="passed-sessions" class="session-tab-content hidden">
            @if($passedSessions->count() > 0)
                <div class="space-y-3 md:space-y-4">
                    @foreach($passedSessions as $session)
                        <x-sessions.session-item :session="$session" :circle="$circle" :view-type="$viewType" />
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 md:py-12">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 md:mb-6 shadow-sm">
                        <i class="ri-history-line text-2xl md:text-3xl text-gray-500"></i>
                    </div>
                    <p class="text-base md:text-lg font-bold text-gray-900 mb-1.5 md:mb-2">{{ __('components.sessions.empty_states.no_completed') }}</p>
                    <p class="text-xs md:text-sm text-gray-600 mb-4 md:mb-6">{{ __('components.sessions.empty_states.no_completed_message') }}</p>
                    <div class="flex items-center justify-center gap-2">
                        <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                        <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                        <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Testing Tab - Shows all possible session statuses -->
        <div id="testing-sessions" class="session-tab-content hidden">
            <div class="mb-3 md:mb-4 p-3 md:p-4 bg-purple-50 border border-purple-200 rounded-lg">
                <h4 class="text-xs md:text-sm font-bold text-purple-800 mb-0.5 md:mb-1 flex items-center gap-1">
                    <i class="ri-test-tube-line"></i> {{ __('components.sessions.empty_states.test_section_title') }}
                </h4>
                <p class="text-[10px] md:text-xs text-purple-600">{{ __('components.sessions.empty_states.test_section_desc') }}</p>
            </div>

            <div class="space-y-3 md:space-y-4">
                @php
                    // Create mock sessions for testing all statuses
                    $testingSessions = collect([
                        (object)[
                            'id' => 9991,
                            'title' => __('components.sessions.session_cards.test_completed'),
                            'status' => \App\Enums\SessionStatus::COMPLETED->value,
                            'scheduled_at' => now()->subHours(2),
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9992,
                            'title' => __('components.sessions.session_cards.test_ongoing'),
                            'status' => \App\Enums\SessionStatus::ONGOING->value,
                            'scheduled_at' => now()->subMinutes(15),
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9993,
                            'title' => __('components.sessions.session_cards.test_ready'),
                            'status' => \App\Enums\SessionStatus::READY->value,
                            'scheduled_at' => now()->addMinutes(5),
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9994,
                            'title' => __('components.sessions.session_cards.test_scheduled_preparing'),
                            'status' => \App\Enums\SessionStatus::SCHEDULED->value,
                            'scheduled_at' => now()->addMinutes(12), // Within 15 minutes
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9995,
                            'title' => __('components.sessions.session_cards.test_scheduled'),
                            'status' => \App\Enums\SessionStatus::SCHEDULED->value,
                            'scheduled_at' => now()->addHours(3),
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9996,
                            'title' => __('components.sessions.session_cards.test_cancelled'),
                            'status' => \App\Enums\SessionStatus::CANCELLED->value,
                            'scheduled_at' => now()->addDays(1),
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9997,
                            'title' => __('components.sessions.session_cards.test_unscheduled'),
                            'status' => \App\Enums\SessionStatus::UNSCHEDULED->value,
                            'scheduled_at' => null,
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9998,
                            'title' => __('components.sessions.session_cards.test_absent'),
                            'status' => \App\Enums\SessionStatus::ABSENT->value,
                            'scheduled_at' => now()->subHours(5),
                            'duration_minutes' => 60,
                        ],
                    ]);
                @endphp

                @foreach($testingSessions as $testSession)
                    <x-sessions.session-item :session="$testSession" :circle="$circle" :view-type="$viewType" />
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple and robust event delegation for tabs
    document.addEventListener('click', function(e) {
        // Check if clicked element is a session tab
        if (e.target.classList.contains('session-tab')) {
            e.preventDefault();
            
            const clickedTab = e.target;
            const targetTab = clickedTab.getAttribute('data-tab');
            
            if (!targetTab) return;
            
            // Find the container
            const container = clickedTab.closest('.bg-white');
            if (!container) return;
            
            // Update all tabs in this container
            container.querySelectorAll('.session-tab').forEach(tab => {
                tab.classList.remove('active', 'border-blue-500', 'text-blue-600');
                tab.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Activate clicked tab
            clickedTab.classList.add('active', 'border-blue-500', 'text-blue-600');
            clickedTab.classList.remove('border-transparent', 'text-gray-500');
            
            // Update all tab contents in this container
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
            }
        }
    });
});
</script> 