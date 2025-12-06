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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" data-sticky-container>
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Subscription Header (using circle header pattern) -->
            <x-circle.individual-header :circle="$subscription" view-type="teacher" context="academic" />

            @php
                $allSessions = collect($upcomingSessions)->merge($pastSessions)->sortByDesc('scheduled_at');
            @endphp

            <!-- Tabs Component -->
            <x-tabs id="academic-lesson-tabs" default-tab="sessions" variant="default" color="primary">
                <x-slot name="tabs">
                    <x-tabs.tab
                        id="sessions"
                        label="الجلسات"
                        icon="ri-calendar-line"
                        :badge="$allSessions->count()"
                    />
                    <x-tabs.tab
                        id="certificate"
                        label="الشهادة"
                        icon="ri-award-line"
                    />
                </x-slot>

                <x-slot name="panels">
                    <x-tabs.panel id="sessions">
                        <x-sessions.sessions-list
                            :sessions="$allSessions"
                            view-type="teacher"
                            :circle="$subscription"
                            :show-tabs="false"
                            empty-message="لا توجد جلسات مجدولة بعد" />
                    </x-tabs.panel>

                    <x-tabs.panel id="certificate">
                        <!-- Certificate Section -->
                        @if($subscription->certificate_issued && $subscription->certificate)
                            @php
                                $certificate = $subscription->certificate;
                                $previewImageUrl = $certificate->template_style?->previewImageUrl() ?? asset('certificates/templates/template_images/template_1.png');
                            @endphp

                            <div class="max-w-2xl mx-auto">
                                <!-- Certificate Card -->
                                <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                                    <!-- Template Preview -->
                                    <div class="aspect-[297/210] relative overflow-hidden bg-gray-100">
                                        <img src="{{ $previewImageUrl }}"
                                             alt="معاينة الشهادة"
                                             class="w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/20 to-black/10 backdrop-blur-sm"></div>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="inline-flex items-center px-6 py-3 bg-white/95 backdrop-blur-sm rounded-xl text-lg font-bold text-gray-800 shadow-lg">
                                                <i class="ri-award-fill ml-2 text-2xl text-amber-600"></i>
                                                شهادة إتمام البرنامج
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Certificate Details -->
                                    <div class="p-6">
                                        <!-- Certificate Number -->
                                        <div class="bg-amber-50 rounded-lg p-4 mb-4 border border-amber-200">
                                            <p class="text-xs text-amber-600 mb-1">رقم الشهادة</p>
                                            <p class="text-lg font-mono font-bold text-amber-900">
                                                {{ $certificate->certificate_number }}
                                            </p>
                                        </div>

                                        <!-- Meta Information -->
                                        <div class="space-y-3 mb-6">
                                            <!-- Student -->
                                            <div class="flex items-center text-sm">
                                                <i class="ri-user-line ml-2 text-gray-400 text-lg"></i>
                                                <span class="text-gray-600">الطالب:</span>
                                                <span class="font-medium text-gray-900 mr-2">{{ $subscription->student->name }}</span>
                                            </div>

                                            <!-- Issue Date -->
                                            <div class="flex items-center text-sm">
                                                <i class="ri-calendar-line ml-2 text-gray-400 text-lg"></i>
                                                <span class="text-gray-600">تاريخ الإصدار:</span>
                                                <span class="font-medium text-gray-900 mr-2">{{ $certificate->issued_at->locale('ar')->translatedFormat('d F Y') }}</span>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex gap-3">
                                            <a href="{{ route('student.certificate.view', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $certificate->id]) }}"
                                               target="_blank"
                                               class="flex-1 inline-flex items-center justify-center px-5 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-lg transition-colors shadow-sm hover:shadow-md">
                                                <i class="ri-eye-line ml-2 text-lg"></i>
                                                عرض الشهادة
                                            </a>
                                            <a href="{{ route('student.certificate.download', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $certificate->id]) }}"
                                               class="flex-1 inline-flex items-center justify-center px-5 py-3 bg-green-500 hover:bg-green-600 text-white font-medium rounded-lg transition-colors shadow-sm hover:shadow-md">
                                                <i class="ri-download-line ml-2 text-lg"></i>
                                                تحميل PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Empty State -->
                            <div class="text-center py-16">
                                <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="ri-award-line text-3xl text-amber-500"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2">لم يتم إصدار شهادة بعد</h3>
                                <p class="text-gray-600 text-sm mb-6">يمكنك إصدار شهادة للطالب عند إتمام البرنامج</p>
                                <p class="text-sm text-gray-500">استخدم القسم الجانبي لإصدار الشهادة</p>
                            </div>
                        @endif
                    </x-tabs.panel>
                </x-slot>
            </x-tabs>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1" data-sticky-sidebar>
            <div class="space-y-6">
                <!-- Academic Lesson Information -->
                <x-academic.lesson-info-sidebar :subscription="$subscription" viewType="teacher" />

                <!-- Quick Actions -->
                <x-circle.quick-actions
                    :circle="$subscription"
                    type="individual"
                    view-type="teacher"
                    context="academic"
                />

                <!-- Subscription Details -->
                <x-circle.subscription-details :subscription="$subscription" viewType="teacher" />

                <!-- Issue Certificate Widget -->
                <x-certificate.teacher-issue-widget type="academic" :entity="$subscription" />
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
