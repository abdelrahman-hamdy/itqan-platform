@props([
    'sessions' => collect(),
    'title' => 'إدارة الجلسات',
    'viewType' => 'student', // 'student', 'teacher'
    'showTabs' => true,
    'emptyMessage' => 'لا توجد جلسات مسجلة بعد',
    'circle' => null
])

@php
    use App\Enums\SessionStatus;
    
    // Categorize sessions by status
    $allSessions = $sessions->sortByDesc('scheduled_at');
    
    // Coming sessions: SCHEDULED, READY, ONGOING
    $comingSessions = $sessions->filter(function($session) {
        return in_array($session->status, [
            SessionStatus::SCHEDULED,
            SessionStatus::READY, 
            SessionStatus::ONGOING
        ]);
    })->sortBy('scheduled_at');
    
    // Passed sessions: COMPLETED, CANCELLED, ABSENT
    $passedSessions = $sessions->filter(function($session) {
        return in_array($session->status, [
            SessionStatus::COMPLETED,
            SessionStatus::CANCELLED,
            SessionStatus::ABSENT
        ]);
    })->sortByDesc('scheduled_at');
    
    // Get status display data for each session
    function getEnhancedStatusData($session) {
        $statusData = $session->getStatusDisplayData();
        return $statusData;
    }
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <!-- Header -->
    <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-900">{{ $title }}</h3>
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-medium">
                المجموع: {{ $allSessions->count() }}
            </span>
        </div>
    </div>

    <!-- Tabs (if enabled) -->
    @if($showTabs)
    <div class="border-b border-gray-200">
        <nav class="flex gap-8 px-6" id="sessionTabs">
            <button class="session-tab active py-4 px-1 border-b-2 border-blue-500 font-medium text-blue-600 text-sm"
                    data-tab="all">
                الكل ({{ $allSessions->count() }})
            </button>
            <button class="session-tab py-4 px-1 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700 text-sm"
                    data-tab="coming">
                القادمة ({{ $comingSessions->count() }})
            </button>
            <button class="session-tab py-4 px-1 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700 text-sm"
                    data-tab="passed">
                المنتهية ({{ $passedSessions->count() }})
            </button>
        </nav>
    </div>
    @endif

    <!-- Sessions Content -->
    <div class="p-6">
        <!-- All Sessions Tab -->
        <div id="all-sessions" class="session-tab-content {{ $showTabs ? 'block' : 'block' }}">
            @if($allSessions->count() > 0)
                <div class="space-y-4">
                    @foreach($allSessions as $session)
                        @include('components.sessions.session-item', ['session' => $session, 'viewType' => $viewType])
                    @endforeach
                </div>
            @else
                @include('components.sessions.empty-state', ['message' => $emptyMessage])
            @endif
        </div>

        <!-- Coming Sessions Tab -->
        @if($showTabs)
        <div id="coming-sessions" class="session-tab-content hidden">
            @if($comingSessions->count() > 0)
                <div class="space-y-4">
                    @foreach($comingSessions as $session)
                        @include('components.sessions.session-item', ['session' => $session, 'viewType' => $viewType])
                    @endforeach
                </div>
            @else
                @include('components.sessions.empty-state', ['message' => 'لا توجد جلسات قادمة'])
            @endif
        </div>

        <!-- Passed Sessions Tab -->
        <div id="passed-sessions" class="session-tab-content hidden">
            @if($passedSessions->count() > 0)
                <div class="space-y-4">
                    @foreach($passedSessions as $session)
                        @include('components.sessions.session-item', ['session' => $session, 'viewType' => $viewType])
                    @endforeach
                </div>
            @else
                @include('components.sessions.empty-state', ['message' => 'لا توجد جلسات منتهية'])
            @endif
        </div>
        @endif
    </div>
</div>

<script>
// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.session-tab');
    const tabContents = document.querySelectorAll('.session-tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs
            tabs.forEach(t => {
                t.classList.remove('active', 'border-blue-500', 'text-blue-600');
                t.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Add active class to clicked tab
            this.classList.add('active', 'border-blue-500', 'text-blue-600');
            this.classList.remove('border-transparent', 'text-gray-500');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            
            // Show target tab content
            const targetContent = document.getElementById(targetTab + '-sessions');
            if (targetContent) {
                targetContent.classList.remove('hidden');
                targetContent.classList.add('block');
            }
        });
    });
});

// Session detail navigation
function openSessionDetail(sessionId) {
    const userType = '{{ $viewType }}';
    
    @if(auth()->check())
        if (userType === 'teacher') {
            const sessionUrl = '{{ route("teacher.sessions.show", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
            const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
            window.location.href = finalUrl;
        } else {
            const sessionUrl = '{{ route("student.sessions.show", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
            const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
            window.location.href = finalUrl;
        }
    @else
        console.error('User not authenticated');
    @endif
}
</script>

<style>
.session-tab.active {
    border-bottom: 2px solid #3B82F6 !important;
    color: #3B82F6 !important;
}

.session-tab:hover {
    color: #374151 !important;
}
</style>
