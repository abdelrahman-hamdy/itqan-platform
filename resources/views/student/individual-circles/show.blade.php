<x-layouts.student 
    :title="($individualCircle->subscription->package->name ?? 'حلقة فردية') . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل الحلقة الفردية للقرآن الكريم'">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('student.quran-circles', ['subdomain' => request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">حلقات القرآن</a></li>
            <li>/</li>
            <li class="text-gray-900">{{ $individualCircle->subscription->package->name ?? 'حلقة فردية' }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Circle Header -->
            <x-circle.circle-header :circle="$individualCircle" type="individual" view-type="student" context="quran" />

            <!-- Quran Sessions Section -->
            @php
                $allSessions = collect($upcomingSessions)->merge($pastSessions)->sortByDesc('scheduled_at');
            @endphp

            <x-sessions.sessions-list
                :sessions="$allSessions"
                title="جلسات الحلقة الفردية"
                view-type="student"
                :circle="$individualCircle"
                :show-tabs="false"
                empty-message="لا توجد جلسات مجدولة بعد" />

            <!-- Quizzes Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    الاختبارات
                </h3>
                <livewire:quizzes-widget :assignable="$individualCircle" />
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Circle Info Sidebar -->
            <x-circle.info-sidebar :circle="$individualCircle" view-type="student" context="individual" />
            
            <!-- Quick Actions -->
            <x-circle.quick-actions :circle="$individualCircle" type="individual" view-type="student" context="quran" />
            
            <!-- Subscription Details -->
            <x-circle.subscription-details :subscription="$individualCircle->subscription" view-type="student" />
        </div>
    </div>
</div>

<!-- Scripts -->
<x-slot name="scripts">
<script>
function openSessionDetail(sessionId) {
    @if(auth()->check())
        const sessionUrl = '{{ route("student.sessions.show", ["subdomain" => request()->route("subdomain") ?? auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);

        console.log('Student Session URL:', finalUrl);
        window.location.href = finalUrl;
    @else
        console.error('User not authenticated');
    @endif
}

// Add any additional student-specific functionality here
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips, progress bars, etc.
    console.log('Student individual circle page loaded');
});
</script>
</x-slot>

</x-layouts.student>