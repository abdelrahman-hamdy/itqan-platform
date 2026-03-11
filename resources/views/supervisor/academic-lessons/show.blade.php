<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $allSessions = collect($upcomingSessions)->merge($pastSessions)->sortByDesc('scheduled_at');
@endphp

<div>
    @if($teacher)
        <x-supervisor.teacher-info-banner :teacher="$teacher" type="academic" />
    @endif

    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.academic_lessons.breadcrumb'), 'route' => route('manage.academic-lessons.index', ['subdomain' => $subdomain])],
            ['label' => $subscription->student->name ?? '', 'truncate' => true],
        ]"
        view-type="supervisor"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-8" data-sticky-container>
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <x-circle.circle-header :circle="$subscription" type="individual" view-type="supervisor" context="academic" />

            <x-tabs id="academic-lesson-tabs" default-tab="sessions" variant="default" color="primary">
                <x-slot name="tabs">
                    <x-tabs.tab id="sessions" :label="__('teacher.circles.tabs.sessions')" icon="ri-calendar-line" :badge="$allSessions->count()" />
                    <x-tabs.tab id="quizzes" :label="__('teacher.circles.tabs.quizzes')" icon="ri-file-list-3-line" />
                    <x-tabs.tab id="certificate" :label="__('teacher.circles.tabs.certificates')" icon="ri-award-line" />
                </x-slot>

                <x-slot name="panels">
                    <x-tabs.panel id="sessions">
                        <x-sessions.sessions-list :sessions="$allSessions" view-type="supervisor" :circle="$subscription" :show-tabs="false" />
                    </x-tabs.panel>

                    <x-tabs.panel id="quizzes">
                        @if($subscription->lesson)
                            <livewire:teacher-quizzes-widget :assignable="$subscription->lesson" />
                        @else
                            <div class="bg-gray-50 rounded-xl py-12 text-center">
                                <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="ri-file-list-3-line text-3xl text-blue-400"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('supervisor.common.no_data') }}</h3>
                            </div>
                        @endif
                    </x-tabs.panel>

                    <x-tabs.panel id="certificate">
                        @if($subscription->certificate_issued && $subscription->certificate)
                            @php $certificate = $subscription->certificate; @endphp
                            <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden md:flex md:items-center">
                                <div class="bg-gradient-to-r from-amber-50 to-yellow-50 px-3 md:px-4 py-2.5 md:py-3 border-b md:border-b-0 md:border-e border-amber-100 md:min-w-[200px] md:self-stretch md:flex md:items-center">
                                    <div class="flex items-center gap-2 md:gap-3">
                                        <x-avatar :user="$subscription->student" size="sm" user-type="student" />
                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-gray-900 text-sm truncate">{{ $subscription->student->name }}</p>
                                            <p class="text-xs text-gray-600 truncate">{{ $certificate->certificate_number }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-3 md:p-4 flex-1 md:flex md:items-center md:justify-between md:gap-4">
                                    <div class="flex items-center gap-1.5 text-xs md:text-sm text-gray-600 mb-2 md:mb-0">
                                        <i class="ri-calendar-line text-amber-500"></i>
                                        <span>{{ $certificate->issued_at->locale(app()->getLocale())->translatedFormat('d F Y') }}</span>
                                    </div>
                                    <div class="flex gap-2 md:shrink-0">
                                        <a href="{{ route('student.certificate.view', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}" target="_blank" class="min-h-[40px] flex-1 md:flex-initial inline-flex items-center justify-center gap-1 px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                            <i class="ri-eye-line"></i> {{ __('supervisor.certificates.view_certificate') }}
                                        </a>
                                        <a href="{{ route('student.certificate.download', ['subdomain' => $subdomain, 'certificate' => $certificate->id]) }}" class="min-h-[40px] flex-1 md:flex-initial inline-flex items-center justify-center gap-1 px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded-lg transition-colors">
                                            <i class="ri-download-line"></i> {{ __('supervisor.certificates.download_certificate') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-10 md:py-16">
                                <div class="w-16 h-16 md:w-20 md:h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                                    <i class="ri-award-line text-2xl md:text-3xl text-amber-500"></i>
                                </div>
                                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-1 md:mb-2">{{ __('teacher.circles.individual.circle_not_issued') }}</h3>
                                <p class="text-gray-600 text-xs md:text-sm">{{ __('teacher.circles.individual.circle_not_issued_desc') }}</p>
                            </div>
                        @endif
                    </x-tabs.panel>
                </x-slot>
            </x-tabs>
        </div>

        <div class="lg:col-span-1" data-sticky-sidebar>
            <div class="space-y-4 md:space-y-6">
                <x-academic.lesson-info-sidebar :subscription="$subscription" viewType="supervisor" />
                <x-circle.subscription-details :subscription="$subscription" viewType="supervisor" />

                @if(isset($isAdmin) && $isAdmin)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">{{ __('supervisor.common.edit_details') }}</h3>
                    <form method="POST" action="{{ route('manage.academic-lessons.update', ['subdomain' => $subdomain, 'subscription' => $subscription->id]) }}">
                        @csrf
                        @method('PUT')
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('supervisor.common.status') }}</label>
                                <select name="status" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    @php $subStatus = is_object($subscription->status) ? $subscription->status->value : $subscription->status; @endphp
                                    <option value="active" {{ $subStatus === 'active' ? 'selected' : '' }}>{{ __('supervisor.common.active') }}</option>
                                    <option value="paused" {{ $subStatus === 'paused' ? 'selected' : '' }}>{{ __('supervisor.academic_lessons.paused') }}</option>
                                    <option value="completed" {{ $subStatus === 'completed' ? 'selected' : '' }}>{{ __('supervisor.academic_lessons.completed') }}</option>
                                    <option value="cancelled" {{ $subStatus === 'cancelled' ? 'selected' : '' }}>{{ __('supervisor.academic_lessons.cancelled') }}</option>
                                </select>
                            </div>
                            <button type="submit"
                                    class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                {{ __('supervisor.common.save') }}
                            </button>
                        </div>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

</x-layouts.supervisor>
