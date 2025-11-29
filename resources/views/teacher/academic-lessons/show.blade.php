<x-layouts.teacher 
    :title="'الدرس الخاص - ' . $subscription->student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'إدارة الدرس الخاص للطالب: ' . $subscription->student->name">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">{{ auth()->user()->name }}</a></li>
            <li>/</li>
            <li><a href="{{ route('teacher.academic.lessons.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">الدروس الخاصة</a></li>
            <li>/</li>
            <li class="text-gray-900">{{ $subscription->student->name ?? 'طالب' }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Subscription Header (using circle header pattern) -->
            <x-circle.individual-header :circle="$subscription" view-type="teacher" context="academic" />

            <!-- Sessions Section with Tabs -->
            @php
                $allSessions = collect($upcomingSessions)->merge($pastSessions)->sortByDesc('scheduled_at');
            @endphp
            
            <x-sessions.sessions-list
                :sessions="$allSessions"
                title="إدارة جلسات الدرس الخاص"
                view-type="teacher"
                :circle="$subscription"
                :show-tabs="false"
                empty-message="لا توجد جلسات مجدولة بعد" />
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Academic Lesson Information -->
            <x-academic.lesson-info-sidebar :subscription="$subscription" viewType="teacher" />

            <!-- Actions and Progress -->
            <div class="space-y-6">
                <x-circle.individual-quick-actions :circle="$subscription" viewType="teacher" type="academic" />
                <x-circle.individual-progress-overview :circle="$subscription" type="academic" />
            </div>

            <!-- Certificate Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-award-line text-amber-500"></i>
                    الشهادات
                </h3>

                @if($subscription->certificate_issued && $subscription->certificate)
                    <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl p-4 border-2 border-amber-200 mb-4">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                                <i class="ri-award-fill text-xl text-amber-600"></i>
                            </div>
                            <div>
                                <p class="font-bold text-amber-800">تم إصدار الشهادة</p>
                                <p class="text-xs text-amber-600">{{ $subscription->certificate->certificate_number }}</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <a href="{{ route('student.certificate.view', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $subscription->certificate->id]) }}"
                               target="_blank"
                               class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="ri-eye-line ml-2"></i>
                                عرض الشهادة
                            </a>
                            <a href="{{ route('student.certificate.download', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $subscription->certificate->id]) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="ri-download-line ml-2"></i>
                                تحميل PDF
                            </a>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-600 mb-4">يمكنك إصدار شهادة للطالب عند إتمام البرنامج أو تحقيق إنجاز معين</p>
                    <button type="button"
                            onclick="Livewire.dispatch('openModal', { subscriptionType: 'academic', subscriptionId: {{ $subscription->id }}, circleId: null })"
                            class="w-full inline-flex items-center justify-center px-5 py-3 bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl">
                        <i class="ri-award-line ml-2 text-lg"></i>
                        إصدار شهادة
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Certificate Modal -->
@livewire('issue-certificate-modal')

<script>
// Session detail function
function openSessionDetail(sessionId) {
    @if(auth()->check())
        // Use consolidated academic session route
        const sessionUrl = '{{ route("teacher.academic-sessions.show", ["subdomain" => request()->route("subdomain") ?? auth()->user()->academy->subdomain ?? "itqan-academy", "session" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);

        console.log('Academic Teacher Session URL:', finalUrl);
        window.location.href = finalUrl;
    @else
        console.error('User not authenticated');
    @endif
}
</script>

</x-layouts.teacher>
