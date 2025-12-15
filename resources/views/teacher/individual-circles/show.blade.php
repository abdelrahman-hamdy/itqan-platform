<x-layouts.teacher
    :title="'الحلقة الفردية - ' . $circle->student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'إدارة الحلقة الفردية للطالب: ' . $circle->student->name">

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => 'الحلقات الفردية', 'route' => route('teacher.individual-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])],
            ['label' => $circle->student->name ?? 'طالب', 'truncate' => true],
        ]"
        view-type="teacher"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 lg:gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Circle Header -->
            <x-circle.individual-header :circle="$circle" view-type="teacher" />

            <!-- Tabs Component -->
            <x-tabs id="individual-circle-tabs" default-tab="sessions" variant="default" color="primary">
                <x-slot name="tabs">
                    <x-tabs.tab
                        id="sessions"
                        label="الجلسات"
                        icon="ri-calendar-line"
                        :badge="$circle->sessions->count()"
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
                            :sessions="$circle->sessions"
                            view-type="teacher"
                            :circle="$circle"
                            :show-tabs="false"
                            empty-message="لا توجد جلسات مجدولة بعد" />
                    </x-tabs.panel>

                    <x-tabs.panel id="certificate">
                        <!-- Certificate Section -->
                        @if(isset($circle->subscription))
                            @if($circle->subscription->certificate_issued && $circle->subscription->certificate)
                                @php
                                    $certificate = $circle->subscription->certificate;
                                    $previewImageUrl = $certificate->template_style?->previewImageUrl() ?? asset('certificates/templates/template_images/template_1.png');
                                @endphp

                                <div class="max-w-2xl mx-auto">
                                    <!-- Certificate Card -->
                                    <div class="bg-white rounded-lg md:rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                                        <!-- Template Preview -->
                                        <div class="aspect-[297/210] relative overflow-hidden bg-gray-100">
                                            <img src="{{ $previewImageUrl }}"
                                                 alt="معاينة الشهادة"
                                                 class="w-full h-full object-cover">
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/20 to-black/10 backdrop-blur-sm"></div>
                                            <div class="absolute inset-0 flex items-center justify-center p-4">
                                                <span class="inline-flex items-center px-3 sm:px-6 py-2 sm:py-3 bg-white/95 backdrop-blur-sm rounded-lg md:rounded-xl text-sm sm:text-lg font-bold text-gray-800 shadow-lg">
                                                    <i class="ri-award-fill ml-1.5 sm:ml-2 text-lg sm:text-2xl text-amber-600"></i>
                                                    شهادة إتمام البرنامج
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Certificate Details -->
                                        <div class="p-4 md:p-6">
                                            <!-- Certificate Number -->
                                            <div class="bg-amber-50 rounded-lg p-3 md:p-4 mb-3 md:mb-4 border border-amber-200">
                                                <p class="text-xs text-amber-600 mb-0.5 md:mb-1">رقم الشهادة</p>
                                                <p class="text-sm md:text-lg font-mono font-bold text-amber-900 break-all">
                                                    {{ $certificate->certificate_number }}
                                                </p>
                                            </div>

                                            <!-- Meta Information -->
                                            <div class="space-y-2 md:space-y-3 mb-4 md:mb-6">
                                                <!-- Student -->
                                                <div class="flex items-center text-xs md:text-sm">
                                                    <i class="ri-user-line ml-1.5 md:ml-2 text-gray-400 text-base md:text-lg"></i>
                                                    <span class="text-gray-600">الطالب:</span>
                                                    <span class="font-medium text-gray-900 mr-1 md:mr-2 truncate">{{ $circle->student->name }}</span>
                                                </div>

                                                <!-- Issue Date -->
                                                <div class="flex items-center text-xs md:text-sm">
                                                    <i class="ri-calendar-line ml-1.5 md:ml-2 text-gray-400 text-base md:text-lg"></i>
                                                    <span class="text-gray-600">تاريخ الإصدار:</span>
                                                    <span class="font-medium text-gray-900 mr-1 md:mr-2">{{ $certificate->issued_at->locale('ar')->translatedFormat('d F Y') }}</span>
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="flex flex-col sm:flex-row gap-2 md:gap-3">
                                                <a href="{{ route('student.certificate.view', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $certificate->id]) }}"
                                                   target="_blank"
                                                   class="min-h-[44px] flex-1 inline-flex items-center justify-center px-4 md:px-5 py-2.5 md:py-3 bg-blue-500 hover:bg-blue-600 text-white text-sm md:text-base font-medium rounded-lg transition-colors shadow-sm hover:shadow-md">
                                                    <i class="ri-eye-line ml-1.5 md:ml-2 text-base md:text-lg"></i>
                                                    عرض الشهادة
                                                </a>
                                                <a href="{{ route('student.certificate.download', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $certificate->id]) }}"
                                                   class="min-h-[44px] flex-1 inline-flex items-center justify-center px-4 md:px-5 py-2.5 md:py-3 bg-green-500 hover:bg-green-600 text-white text-sm md:text-base font-medium rounded-lg transition-colors shadow-sm hover:shadow-md">
                                                    <i class="ri-download-line ml-1.5 md:ml-2 text-base md:text-lg"></i>
                                                    تحميل PDF
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <!-- Empty State -->
                                <div class="text-center py-10 md:py-16">
                                    <div class="w-16 h-16 md:w-20 md:h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                                        <i class="ri-award-line text-2xl md:text-3xl text-amber-500"></i>
                                    </div>
                                    <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">لم يتم إصدار شهادة بعد</h3>
                                    <p class="text-gray-600 text-xs md:text-sm mb-4 md:mb-6">يمكنك إصدار شهادة للطالب عند إتمام البرنامج</p>
                                    <p class="text-xs md:text-sm text-gray-500">استخدم القسم الجانبي لإصدار الشهادة</p>
                                </div>
                            @endif
                        @endif
                    </x-tabs.panel>
                </x-slot>
            </x-tabs>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-4 md:space-y-6">
            <!-- Circle Info Sidebar -->
            <x-circle.info-sidebar :circle="$circle" view-type="teacher" context="individual" />

            <!-- Quick Actions -->
            <x-circle.quick-actions
                :circle="$circle"
                type="individual"
                view-type="teacher"
                context="quran"
            />

            <!-- Subscription Details -->
            <x-circle.subscription-details :subscription="$circle->subscription" view-type="teacher" />

            <!-- Issue Certificate Widget -->
            @if(isset($circle->subscription))
                <x-certificate.teacher-issue-widget type="quran_individual" :entity="$circle" />
            @endif
        </div>
    </div>
</div>

<!-- Certificate Modal -->
@livewire('issue-certificate-modal')

<script>
// Session detail function
function openSessionDetail(sessionId) {
    @if(auth()->check())
        // Use Laravel route helper to generate correct URL for teacher sessions
        const sessionUrl = '{{ route("teacher.sessions.show", ["subdomain" => request()->route("subdomain") ?? auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
        
        console.log('Teacher Session URL:', finalUrl);
        window.location.href = finalUrl;
    @else
        console.error('User not authenticated');
    @endif
}
</script>

</x-layouts.teacher>
