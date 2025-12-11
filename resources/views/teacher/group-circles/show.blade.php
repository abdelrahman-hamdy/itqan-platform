<x-layouts.teacher
    :title="'الحلقة الجماعية - ' . $circle->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'إدارة الحلقة الجماعية: ' . $circle->name">

<!-- Flash Messages -->
@if(session('success'))
    <div class="mb-4 md:mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg md:rounded-xl p-3 md:p-4 flex items-start gap-2 md:gap-3">
        <i class="ri-checkbox-circle-fill text-green-500 text-lg md:text-xl mt-0.5 flex-shrink-0"></i>
        <span class="text-sm md:text-base">{{ session('success') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="mb-4 md:mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg md:rounded-xl p-3 md:p-4 flex items-start gap-2 md:gap-3">
        <i class="ri-error-warning-fill text-red-500 text-lg md:text-xl mt-0.5 flex-shrink-0"></i>
        <span class="text-sm md:text-base">{{ session('error') }}</span>
    </div>
@endif

@if(session('warning'))
    <div class="mb-4 md:mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg md:rounded-xl p-3 md:p-4 flex items-start gap-2 md:gap-3">
        <i class="ri-alert-fill text-yellow-500 text-lg md:text-xl mt-0.5 flex-shrink-0"></i>
        <span class="text-sm md:text-base">{{ session('warning') }}</span>
    </div>
@endif

<div>
    <!-- Breadcrumb -->
    <nav class="mb-4 md:mb-8 overflow-x-auto">
        <ol class="flex items-center gap-2 text-xs md:text-sm text-gray-600 whitespace-nowrap">
            <li><a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="min-h-[44px] inline-flex items-center hover:text-primary">{{ auth()->user()->name }}</a></li>
            <li>/</li>
            <li class="text-gray-900 font-medium truncate max-w-[150px] md:max-w-none">{{ $circle->name }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 lg:gap-8" data-sticky-container>
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Circle Header -->
            <x-circle.group-header :circle="$circle" view-type="teacher" />

            <!-- Tabs Component -->
            @php
                // Get all sessions for the circle
                $allSessions = $circle->sessions()->orderBy('scheduled_at', 'desc')->get();
                $totalStudents = $circle->students()->count();
                $studentsWithCertificates = $circle->students()->wherePivot('certificate_issued', true)->count();
            @endphp

            <x-tabs id="circle-tabs" default-tab="sessions" variant="default" color="primary">
                <x-slot name="tabs">
                    <x-tabs.tab
                        id="sessions"
                        label="الجلسات"
                        icon="ri-calendar-line"
                        :badge="$allSessions->count()"
                    />
                    <x-tabs.tab
                        id="students"
                        label="الطلاب"
                        icon="ri-user-3-line"
                        :badge="$totalStudents"
                    />
                    <x-tabs.tab
                        id="certificates"
                        label="الشهادات"
                        icon="ri-award-line"
                        :badge="$studentsWithCertificates"
                    />
                </x-slot>

                <x-slot name="panels">
                    <x-tabs.panel id="sessions">
                        <x-sessions.sessions-list
                            :sessions="$allSessions"
                            view-type="teacher"
                            :circle="$circle"
                            :show-tabs="false" />
                    </x-tabs.panel>

                    <x-tabs.panel id="students">
                        <x-circle.group-students-list :circle="$circle" view-type="teacher" />
                    </x-tabs.panel>

                    <x-tabs.panel id="certificates">
                        <!-- Certificates List Section -->
                        @php
                            // Get all certificates for students in this circle
                            $certificates = \App\Models\Certificate::whereIn('student_id', $circle->students->pluck('id'))
                                ->where('certificate_type', 'quran_subscription')
                                ->latest('issued_at')
                                ->get();
                        @endphp

                        @if($certificates->count() > 0)
                            <div class="bg-green-50 rounded-lg p-3 md:p-4 mb-4 md:mb-6 border border-green-200">
                                <p class="text-xs md:text-sm text-green-800 font-medium flex items-center gap-1">
                                    <i class="ri-checkbox-circle-fill flex-shrink-0"></i>
                                    <span>تم إصدار {{ $certificates->count() }} شهادة للطلاب</span>
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-6">
                                @foreach($certificates as $certificate)
                                    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                                        <!-- Student Info Header -->
                                        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 px-3 md:px-4 py-2.5 md:py-3 border-b border-amber-100">
                                            <div class="flex items-center gap-2 md:gap-3">
                                                <x-avatar :user="$certificate->student" size="sm" user-type="student" />
                                                <div class="min-w-0 flex-1">
                                                    <p class="font-bold text-gray-900 text-sm truncate">{{ $certificate->student->name }}</p>
                                                    <p class="text-xs text-gray-600 truncate">{{ $certificate->certificate_number }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Certificate Details -->
                                        <div class="p-3 md:p-4 space-y-2 md:space-y-3">
                                            <!-- Issue Date -->
                                            <div class="flex items-center text-xs md:text-sm text-gray-600">
                                                <i class="ri-calendar-line ml-1.5 md:ml-2 text-amber-500"></i>
                                                <span>{{ $certificate->issued_at->locale('ar')->translatedFormat('d F Y') }}</span>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="flex gap-2 pt-1 md:pt-2">
                                                <a href="{{ route('student.certificate.view', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $certificate->id]) }}"
                                                   target="_blank"
                                                   class="min-h-[40px] md:min-h-[44px] flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                    <i class="ri-eye-line ml-1"></i>
                                                    عرض
                                                </a>
                                                <a href="{{ route('student.certificate.download', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $certificate->id]) }}"
                                                   class="min-h-[40px] md:min-h-[44px] flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                    <i class="ri-download-line ml-1"></i>
                                                    تحميل
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <!-- Empty State -->
                            <div class="text-center py-8 md:py-12">
                                <div class="w-16 h-16 md:w-20 md:h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                                    <i class="ri-award-line text-2xl md:text-3xl text-amber-500"></i>
                                </div>
                                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">لا توجد شهادات</h3>
                                <p class="text-gray-600 text-xs md:text-sm mb-4 md:mb-6">لم يتم إصدار أي شهادات للطلاب بعد</p>
                                <p class="text-xs md:text-sm text-gray-500">يمكنك إصدار الشهادات من خلال القسم الجانبي</p>
                            </div>
                        @endif
                    </x-tabs.panel>
                </x-slot>
            </x-tabs>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1" data-sticky-sidebar>
            <div class="space-y-4 md:space-y-6">
                <!-- Circle Info Sidebar -->
                <x-circle.info-sidebar :circle="$circle" view-type="teacher" />

                <!-- Quick Actions -->
                <x-circle.quick-actions :circle="$circle" view-type="teacher" />

                <!-- Issue Certificate Widget -->
                <x-certificate.teacher-issue-widget type="quran_group" :entity="$circle" />
            </div>
        </div>
    </div>
</div>

<!-- Certificate Modal -->
@livewire('issue-certificate-modal')

<script>
// Student management functions
function contactStudent(studentId) {
    const subdomain = '{{ auth()->user()->academy->subdomain ?? "itqan-academy" }}';
    window.location.href = `/${subdomain}/teacher/students/${studentId}/contact`;
}

// Session detail function
function openSessionDetail(sessionId) {
    @if(auth()->check())
        const sessionUrl = '{{ route("teacher.sessions.show", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
        window.location.href = finalUrl;
    @else
        console.error('User not authenticated');
    @endif
}
</script>

</x-layouts.teacher>