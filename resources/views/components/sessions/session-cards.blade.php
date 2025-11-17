@props([
    'sessions' => collect(),
    'viewType' => 'student', // student, teacher
    'circle' => null,
    'title' => 'إدارة جلسات الحلقة',
    'subtitle' => null
])

@php
    // Helper function to get status value (handles both string and enum)
    $getStatusValue = function($session) {
        return is_object($session->status) ? $session->status->value : $session->status;
    };
    
    $totalSessions = $sessions->count();
    $comingSessions = $sessions->filter(function($session) use ($getStatusValue) {
        return in_array($getStatusValue($session), ['scheduled', 'ready', 'ongoing']);
    });
    $passedSessions = $sessions->filter(function($session) use ($getStatusValue) {
        return in_array($getStatusValue($session), ['completed', 'cancelled']);
    });
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <!-- Header -->
    <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-900">{{ $title }}</h3>
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-medium">
                المجموع: {{ $totalSessions }}
            </span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
        <nav class="flex gap-8 px-6" id="sessionTabs">
            <button class="session-tab active py-4 px-1 border-b-2 border-blue-500 font-medium text-blue-600 text-sm" data-tab="all">
                الكل ({{ $totalSessions }})
            </button>
            <button class="session-tab py-4 px-1 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700 text-sm" data-tab="coming">
                القادمة ({{ $comingSessions->count() }})
            </button>
            <button class="session-tab py-4 px-1 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700 text-sm" data-tab="passed">
                المنتهية ({{ $passedSessions->count() }})
            </button>
            <button class="session-tab py-4 px-1 border-b-2 border-transparent font-medium text-purple-500 hover:text-purple-700 text-sm" data-tab="testing">
                اختبار الحالات (8)
            </button>
        </nav>
    </div>
    
    <!-- Sessions Content -->
    <div class="p-6">
        <!-- All Sessions Tab -->
        <div id="all-sessions" class="session-tab-content block">
            @if($sessions->count() > 0)
                <div class="space-y-4">
                    @foreach($sessions as $session)
                        <x-sessions.session-item :session="$session" :circle="$circle" :view-type="$viewType" />
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-calendar-line text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">لا توجد جلسات مسجلة بعد</p>
                    <p class="text-sm text-gray-400 mt-1">ستظهر جلساتك هنا عند إنشائها</p>
                </div>
            @endif
        </div>

        <!-- Coming Sessions Tab -->
        <div id="coming-sessions" class="session-tab-content hidden">
            @if($comingSessions->count() > 0)
                <div class="space-y-4">
                    @foreach($comingSessions as $session)
                        <x-sessions.session-item :session="$session" :circle="$circle" :view-type="$viewType" />
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-calendar-line text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">لا توجد جلسات قادمة</p>
                    <p class="text-sm text-gray-400 mt-1">ستظهر الجلسات القادمة هنا</p>
                </div>
            @endif
        </div>

        <!-- Passed Sessions Tab -->
        <div id="passed-sessions" class="session-tab-content hidden">
            @if($passedSessions->count() > 0)
                <div class="space-y-4">
                    @foreach($passedSessions as $session)
                        <x-sessions.session-item :session="$session" :circle="$circle" :view-type="$viewType" />
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-calendar-line text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 font-medium">لا توجد جلسات منتهية</p>
                    <p class="text-sm text-gray-400 mt-1">ستظهر الجلسات المكتملة والملغاة هنا</p>
                </div>
            @endif
        </div>

        <!-- Testing Tab - Shows all possible session statuses -->
        <div id="testing-sessions" class="session-tab-content hidden">
            <div class="mb-4 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                <h4 class="text-sm font-bold text-purple-800 mb-1">
                    <i class="ri-test-tube-line"></i> قسم اختبار جميع حالات الجلسات
                </h4>
                <p class="text-xs text-purple-600">يعرض هذا القسم مثالاً لكل حالة ممكنة من حالات الجلسات</p>
            </div>

            <div class="space-y-4">
                @php
                    // Create mock sessions for testing all statuses
                    $testingSessions = collect([
                        (object)[
                            'id' => 9991,
                            'title' => 'جلسة مكتملة - للتجربة',
                            'status' => 'completed',
                            'scheduled_at' => now()->subHours(2),
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9992,
                            'title' => 'جلسة جارية - للتجربة',
                            'status' => 'ongoing',
                            'scheduled_at' => now()->subMinutes(15),
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9993,
                            'title' => 'جلسة جاهزة - للتجربة',
                            'status' => 'ready',
                            'scheduled_at' => now()->addMinutes(5),
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9994,
                            'title' => 'جلسة مجدولة (في التحضير) - للتجربة',
                            'status' => 'scheduled',
                            'scheduled_at' => now()->addMinutes(12), // Within 15 minutes
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9995,
                            'title' => 'جلسة مجدولة - للتجربة',
                            'status' => 'scheduled',
                            'scheduled_at' => now()->addHours(3),
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9996,
                            'title' => 'جلسة ملغاة - للتجربة',
                            'status' => 'cancelled',
                            'scheduled_at' => now()->addDays(1),
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9997,
                            'title' => 'جلسة غير مجدولة - للتجربة',
                            'status' => 'unscheduled',
                            'scheduled_at' => null,
                            'duration_minutes' => 60,
                        ],
                        (object)[
                            'id' => 9998,
                            'title' => 'جلسة غائب - للتجربة',
                            'status' => 'absent',
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

// Session detail function
function openSessionDetail(sessionId) {
    console.log('Opening session details for ID:', sessionId);
}
</script> 