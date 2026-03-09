<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <!-- Teacher Info Banner -->
    @if($teacher)
        <x-supervisor.teacher-info-banner :teacher="$teacher" type="quran" />
    @endif

    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.group_circles.breadcrumb'), 'route' => route('manage.group-circles.index', ['subdomain' => $subdomain])],
            ['label' => $circle->name, 'truncate' => true],
        ]"
        view-type="supervisor"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 lg:gap-8" data-sticky-container>
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Circle Header -->
            <x-circle.circle-header :circle="$circle" type="group" view-type="supervisor" />

            @php
                $allSessions = $circle->sessions()->orderBy('scheduled_at', 'desc')->get();
                $totalStudents = $circle->students()->count();
                $studentsWithCertificates = $circle->students()->wherePivot('certificate_issued', true)->count();
            @endphp

            <x-tabs id="circle-tabs" default-tab="sessions" variant="default" color="primary">
                <x-slot name="tabs">
                    <x-tabs.tab id="sessions" :label="__('teacher.circles.tabs.sessions')" icon="ri-calendar-line" :badge="$allSessions->count()" />
                    <x-tabs.tab id="students" :label="__('teacher.circles.tabs.students')" icon="ri-user-3-line" :badge="$totalStudents" />
                    <x-tabs.tab id="quizzes" :label="__('teacher.circles.tabs.quizzes')" icon="ri-file-list-3-line" />
                    <x-tabs.tab id="certificates" :label="__('teacher.circles.tabs.certificates')" icon="ri-award-line" :badge="$studentsWithCertificates" />
                </x-slot>

                <x-slot name="panels">
                    <x-tabs.panel id="sessions">
                        <x-sessions.sessions-list :sessions="$allSessions" view-type="supervisor" :circle="$circle" :show-tabs="false" />
                    </x-tabs.panel>

                    <x-tabs.panel id="students">
                        <x-circle.group-students-list :circle="$circle" view-type="supervisor" />
                    </x-tabs.panel>

                    <x-tabs.panel id="quizzes">
                        <livewire:teacher-quizzes-widget :assignable="$circle" />
                    </x-tabs.panel>

                    <x-tabs.panel id="certificates">
                        @php
                            $certificates = \App\Models\Certificate::whereIn('student_id', $circle->students->pluck('id'))
                                ->where('certificate_type', 'quran_subscription')
                                ->latest('issued_at')
                                ->get();
                        @endphp

                        @if($certificates->count() > 0)
                            <div class="bg-green-50 rounded-lg p-3 md:p-4 mb-4 md:mb-6 border border-green-200">
                                <p class="text-xs md:text-sm text-green-800 font-medium flex items-center gap-1">
                                    <i class="ri-checkbox-circle-fill flex-shrink-0"></i>
                                    <span>{{ __('teacher.circles_list.group.show.certificates_issued_count', ['count' => $certificates->count()]) }}</span>
                                </p>
                            </div>

                            <div class="space-y-3 md:space-y-4">
                                @foreach($certificates as $certificate)
                                    <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow md:flex md:items-center">
                                        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 px-3 md:px-4 py-2.5 md:py-3 border-b md:border-b-0 md:border-e border-amber-100 md:min-w-[200px] md:self-stretch md:flex md:items-center">
                                            <div class="flex items-center gap-2 md:gap-3">
                                                <x-avatar :user="$certificate->student" size="sm" user-type="student" />
                                                <div class="min-w-0 flex-1">
                                                    <p class="font-bold text-gray-900 text-sm truncate">{{ $certificate->student->name }}</p>
                                                    <p class="text-xs text-gray-600 truncate">{{ $certificate->certificate_number }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-3 md:p-4 flex-1 md:flex md:items-center md:justify-between md:gap-4">
                                            <div class="flex items-center gap-1.5 md:gap-2 text-xs md:text-sm text-gray-600 mb-2 md:mb-0">
                                                <i class="ri-calendar-line text-amber-500"></i>
                                                <span>{{ $certificate->issued_at->locale(app()->getLocale())->translatedFormat('d F Y') }}</span>
                                            </div>
                                            <div class="flex gap-2 md:shrink-0">
                                                <a href="{{ route('student.certificate.view', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
                                                   target="_blank"
                                                   class="min-h-[40px] md:min-h-[44px] flex-1 md:flex-initial inline-flex items-center justify-center gap-1 px-3 md:px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                    <i class="ri-eye-line"></i>
                                                    {{ __('supervisor.certificates.view_certificate') }}
                                                </a>
                                                <a href="{{ route('student.certificate.download', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}"
                                                   class="min-h-[40px] md:min-h-[44px] flex-1 md:flex-initial inline-flex items-center justify-center gap-1 px-3 md:px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                    <i class="ri-download-line"></i>
                                                    {{ __('supervisor.certificates.download_certificate') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 md:py-12">
                                <div class="w-16 h-16 md:w-20 md:h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                                    <i class="ri-award-line text-2xl md:text-3xl text-amber-500"></i>
                                </div>
                                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('teacher.circles_list.group.show.no_certificates') }}</h3>
                                <p class="text-gray-600 text-xs md:text-sm">{{ __('teacher.circles_list.group.show.no_certificates_issued') }}</p>
                            </div>
                        @endif
                    </x-tabs.panel>
                </x-slot>
            </x-tabs>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1" data-sticky-sidebar>
            <div class="space-y-4 md:space-y-6">
                <x-circle.info-sidebar :circle="$circle" view-type="supervisor" />
            </div>
        </div>
    </div>
</div>

</x-layouts.supervisor>
