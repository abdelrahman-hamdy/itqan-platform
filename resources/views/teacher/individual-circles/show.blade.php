<x-layouts.teacher
    :title="__('teacher.circles.individual.page_title') . ' - ' . $circle->student->name . ' - ' . config('app.name', __('common.app_name'))"
    :description="__('teacher.circles.individual.page_description') . ' ' . $circle->student->name">

<div>
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('teacher.circles.individual.breadcrumb'), 'route' => route('teacher.individual-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])],
            ['label' => $circle->student->name ?? __('teacher.circles.individual.student_label'), 'truncate' => true],
        ]"
        view-type="teacher"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 lg:gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Circle Header -->
            <x-circle.circle-header :circle="$circle" type="individual" view-type="teacher" />

            <!-- Tabs Component -->
            <x-tabs id="individual-circle-tabs" default-tab="sessions" variant="default" color="primary">
                <x-slot name="tabs">
                    <x-tabs.tab
                        id="sessions"
                        :label="__('teacher.circles.tabs.sessions')"
                        icon="ri-calendar-line"
                        :badge="$circle->sessions->count()"
                    />
                    <x-tabs.tab
                        id="quizzes"
                        :label="__('teacher.circles.tabs.quizzes')"
                        icon="ri-file-list-3-line"
                    />
                    <x-tabs.tab
                        id="certificate"
                        :label="__('teacher.circles.individual.certificate_tab')"
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
                            :empty-message="__('teacher.circles.individual.no_sessions_yet')" />
                    </x-tabs.panel>

                    <x-tabs.panel id="quizzes">
                        <livewire:teacher-quizzes-widget :assignable="$circle" />
                    </x-tabs.panel>

                    <x-tabs.panel id="certificate">
                        <!-- Certificate Section -->
                        @if(isset($circle->subscription))
                            @if($circle->subscription->certificate_issued && $circle->subscription->certificate)
                                @php
                                    $certificate = $circle->subscription->certificate;
                                    $previewImageUrl = $certificate->template_style?->previewImageUrl() ?? asset('certificates/templates/template_images/template_1.png');
                                @endphp

                                <div class="space-y-3 md:space-y-4">
                                    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow md:flex md:items-center">
                                        <!-- Student Info -->
                                        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 px-3 md:px-4 py-2.5 md:py-3 border-b md:border-b-0 md:border-e border-amber-100 md:min-w-[200px] md:self-stretch md:flex md:items-center">
                                            <div class="flex items-center gap-2 md:gap-3">
                                                <x-avatar :user="$circle->student" size="sm" user-type="student" />
                                                <div class="min-w-0 flex-1">
                                                    <p class="font-bold text-gray-900 text-sm truncate">{{ $circle->student->name }}</p>
                                                    <p class="text-xs text-gray-600 truncate">{{ $certificate->certificate_number }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Certificate Details -->
                                        <div class="p-3 md:p-4 flex-1 md:flex md:items-center md:justify-between md:gap-4">
                                            <!-- Issue Date -->
                                            <div class="flex items-center gap-1.5 md:gap-2 text-xs md:text-sm text-gray-600 mb-2 md:mb-0">
                                                <i class="ri-calendar-line text-amber-500"></i>
                                                <span>{{ $certificate->issued_at->locale(app()->getLocale())->translatedFormat('d F Y') }}</span>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="flex gap-2 md:shrink-0">
                                                <a href="{{ route('student.certificate.view', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $certificate->id]) }}"
                                                   target="_blank"
                                                   class="min-h-[40px] md:min-h-[44px] flex-1 md:flex-initial inline-flex items-center justify-center gap-1 px-3 md:px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                    <i class="ri-eye-line"></i>
                                                    {{ __('teacher.circles.individual.view_certificate') }}
                                                </a>
                                                <a href="{{ route('student.certificate.download', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $certificate->id]) }}"
                                                   class="min-h-[40px] md:min-h-[44px] flex-1 md:flex-initial inline-flex items-center justify-center gap-1 px-3 md:px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                    <i class="ri-download-line"></i>
                                                    {{ __('teacher.circles.individual.download_pdf') }}
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
                                    <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('teacher.circles.individual.circle_not_issued') }}</h3>
                                    <p class="text-gray-600 text-xs md:text-sm mb-4 md:mb-6">{{ __('teacher.circles.individual.circle_not_issued_desc') }}</p>
                                    <p class="text-xs md:text-sm text-gray-500">{{ __('teacher.circles_list.group.show.issue_from_sidebar') }}</p>
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
        
        window.location.href = finalUrl;
    @else
    @endif
}
</script>

</x-layouts.teacher>
