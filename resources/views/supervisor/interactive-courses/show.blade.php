<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    @if($teacher)
        <x-supervisor.teacher-info-banner :teacher="$teacher" type="academic" />
    @endif

    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.interactive_courses.breadcrumb'), 'route' => route('supervisor.interactive-courses.index', ['subdomain' => $subdomain])],
            ['label' => $course->title, 'truncate' => true],
        ]"
        view-type="supervisor"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 lg:gap-8">
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Course Header -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h2 class="text-lg md:text-xl font-bold text-gray-900 mb-2">{{ $course->title }}</h2>
                @if($course->description)
                    <p class="text-sm text-gray-600 mb-4">{{ $course->description }}</p>
                @endif
                <div class="flex flex-wrap gap-2">
                    @php
                        $statusValue = is_object($course->status) ? $course->status->value : $course->status;
                        $statusClass = match($statusValue) {
                            'active' => 'bg-blue-100 text-blue-800',
                            'published' => 'bg-green-100 text-green-800',
                            'completed' => 'bg-purple-100 text-purple-800',
                            default => 'bg-gray-100 text-gray-800',
                        };
                    @endphp
                    <span class="text-xs px-2.5 py-1 rounded-full {{ $statusClass }}">{{ $statusValue }}</span>
                    @if($course->subject)
                        <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ $course->subject->name }}</span>
                    @endif
                    @if($course->gradeLevel)
                        <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ $course->gradeLevel->getDisplayName() }}</span>
                    @endif
                </div>
            </div>

            <x-tabs id="course-tabs" default-tab="sessions" variant="default" color="primary">
                <x-slot name="tabs">
                    <x-tabs.tab id="sessions" :label="__('teacher.circles.tabs.sessions')" icon="ri-calendar-line" :badge="$course->sessions->count()" />
                    <x-tabs.tab id="students" :label="__('teacher.circles.tabs.students')" icon="ri-user-3-line" :badge="$course->enrollments->count()" />
                </x-slot>

                <x-slot name="panels">
                    <x-tabs.panel id="sessions">
                        @if($course->sessions->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($course->sessions as $session)
                                    <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4 flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="ri-calendar-line text-blue-600"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900">{{ $session->title ?? __('teacher.circles.tabs.sessions') }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $session->scheduled_date?->format('Y/m/d') }}
                                                @if($session->scheduled_time) · {{ $session->scheduled_time }} @endif
                                            </p>
                                        </div>
                                        @php
                                            $sessionStatus = is_object($session->status) ? $session->status->value : $session->status;
                                        @endphp
                                        <span class="text-xs px-2 py-1 rounded-full {{ match($sessionStatus) {
                                            'completed' => 'bg-green-100 text-green-700',
                                            'scheduled' => 'bg-blue-100 text-blue-700',
                                            'cancelled' => 'bg-red-100 text-red-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        } }}">{{ $sessionStatus }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="ri-calendar-line text-2xl text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500">{{ __('supervisor.common.no_data') }}</p>
                            </div>
                        @endif
                    </x-tabs.panel>

                    <x-tabs.panel id="students">
                        @if($course->enrollments->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($course->enrollments as $enrollment)
                                    <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4 flex items-center gap-3">
                                        <x-avatar :user="$enrollment->student" size="sm" user-type="student" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $enrollment->student?->name ?? '' }}</p>
                                            <p class="text-xs text-gray-500">{{ $enrollment->created_at?->format('Y/m/d') }}</p>
                                        </div>
                                        @php $enrollStatus = is_object($enrollment->status) ? $enrollment->status->value : $enrollment->status; @endphp
                                        <span class="text-xs px-2 py-1 rounded-full {{ $enrollStatus === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $enrollStatus }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="ri-user-3-line text-2xl text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500">{{ __('supervisor.common.no_data') }}</p>
                            </div>
                        @endif
                    </x-tabs.panel>
                </x-slot>
            </x-tabs>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-4 md:space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
                <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.common.teacher_info') }}</h3>
                <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex items-center gap-2"><i class="ri-user-line text-gray-400"></i> {{ $course->assignedTeacher?->user?->name ?? '' }}</div>
                    @if($course->start_date)
                        <div class="flex items-center gap-2"><i class="ri-calendar-line text-gray-400"></i> {{ $course->start_date->format('Y/m/d') }}</div>
                    @endif
                    @if($course->end_date)
                        <div class="flex items-center gap-2"><i class="ri-calendar-check-line text-gray-400"></i> {{ $course->end_date->format('Y/m/d') }}</div>
                    @endif
                    @if($course->max_students)
                        <div class="flex items-center gap-2"><i class="ri-group-line text-gray-400"></i> {{ $course->enrollments->count() }} / {{ $course->max_students }}</div>
                    @endif
                    @if($course->total_sessions)
                        <div class="flex items-center gap-2"><i class="ri-play-list-line text-gray-400"></i> {{ $course->sessions->count() }} / {{ $course->total_sessions }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.supervisor>
