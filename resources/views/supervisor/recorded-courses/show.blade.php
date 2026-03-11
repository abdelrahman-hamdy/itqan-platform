<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.recorded_courses.page_title'), 'route' => route('manage.recorded-courses.index', ['subdomain' => $subdomain])],
            ['label' => $course->title, 'truncate' => true],
        ]"
        view-type="supervisor"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 lg:gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <!-- Course Header -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <div class="flex items-start justify-between mb-4">
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">{{ $course->title }}</h2>
                    <form method="POST" action="{{ route('manage.recorded-courses.toggle-publish', ['subdomain' => $subdomain, 'course' => $course->id]) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg {{ $course->is_published ? 'bg-yellow-100 hover:bg-yellow-200 text-yellow-800' : 'bg-green-100 hover:bg-green-200 text-green-800' }} transition-colors">
                            <i class="{{ $course->is_published ? 'ri-eye-off-line' : 'ri-eye-line' }}"></i>
                            {{ $course->is_published ? __('supervisor.recorded_courses.unpublish') : __('supervisor.recorded_courses.publish') }}
                        </button>
                    </form>
                </div>
                @if($course->description)
                    <p class="text-sm text-gray-600 mb-4">{{ $course->description }}</p>
                @endif
                <div class="flex flex-wrap gap-2">
                    @if($course->is_published)
                        <span class="text-xs px-2.5 py-1 rounded-full bg-green-100 text-green-800">{{ __('supervisor.recorded_courses.published') }}</span>
                    @else
                        <span class="text-xs px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-800">{{ __('supervisor.recorded_courses.draft') }}</span>
                    @endif
                    @if($course->difficulty_level)
                        <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ $course->difficulty_level }}</span>
                    @endif
                    @if($course->avg_rating > 0)
                        <span class="text-xs px-2.5 py-1 rounded-full bg-amber-100 text-amber-800">
                            <i class="ri-star-fill"></i> {{ number_format($course->avg_rating, 1) }}
                        </span>
                    @endif
                </div>
            </div>

            <x-tabs id="course-tabs" default-tab="sections" variant="default" color="primary">
                <x-slot name="tabs">
                    <x-tabs.tab id="sections" :label="__('supervisor.recorded_courses.sections_lessons')" icon="ri-book-open-line" :badge="$course->sections->count()" />
                    <x-tabs.tab id="students" :label="__('supervisor.recorded_courses.enrolled_students')" icon="ri-user-3-line" :badge="$course->enrollments->count()" />
                </x-slot>

                <x-slot name="panels">
                    <!-- Sections & Lessons Tab -->
                    <x-tabs.panel id="sections">
                        @if($course->sections->isNotEmpty())
                            <div class="space-y-4">
                                @foreach($course->sections as $section)
                                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                        <div class="p-3 md:p-4 bg-gray-50 flex items-center gap-3">
                                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <i class="ri-folder-line text-blue-600 text-sm"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900">{{ $section->title }}</p>
                                                @if($section->description)
                                                    <p class="text-xs text-gray-500 truncate">{{ $section->description }}</p>
                                                @endif
                                            </div>
                                            <span class="text-xs text-gray-500">{{ $section->lessons->count() }} {{ __('supervisor.recorded_courses.lessons') }}</span>
                                        </div>
                                        @if($section->lessons->isNotEmpty())
                                            <div class="divide-y divide-gray-100">
                                                @foreach($section->lessons as $lesson)
                                                    <div class="p-3 md:p-4 flex items-center gap-3 pr-6">
                                                        <div class="w-7 h-7 bg-gray-100 rounded flex items-center justify-center flex-shrink-0">
                                                            <i class="ri-play-circle-line text-gray-500 text-sm"></i>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm text-gray-700">{{ $lesson->title }}</p>
                                                        </div>
                                                        <div class="flex items-center gap-2 flex-shrink-0">
                                                            @if($lesson->is_free_preview)
                                                                <span class="text-xs px-2 py-0.5 rounded bg-green-50 text-green-700">{{ __('supervisor.recorded_courses.free_preview') }}</span>
                                                            @endif
                                                            @if($lesson->is_published)
                                                                <span class="w-2 h-2 rounded-full bg-green-400"></span>
                                                            @else
                                                                <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="p-4 text-center text-sm text-gray-400">{{ __('supervisor.recorded_courses.no_lessons') }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="ri-book-open-line text-2xl text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500">{{ __('supervisor.common.no_data') }}</p>
                            </div>
                        @endif
                    </x-tabs.panel>

                    <!-- Enrolled Students Tab -->
                    <x-tabs.panel id="students">
                        @if($course->enrollments->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.recorded_courses.student_name') }}</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase hidden md:table-cell">{{ __('supervisor.recorded_courses.enrollment_date') }}</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.recorded_courses.progress') }}</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase hidden md:table-cell">{{ __('supervisor.recorded_courses.enrollment_status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($course->enrollments as $enrollment)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-3">
                                                        <x-avatar :user="$enrollment->student" size="sm" user-type="student" />
                                                        <span class="text-sm font-medium text-gray-900">{{ $enrollment->student?->name ?? '-' }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center hidden md:table-cell">
                                                    <span class="text-sm text-gray-600">{{ $enrollment->enrolled_at?->format('Y/m/d') ?? $enrollment->created_at?->format('Y/m/d') }}</span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <div class="w-20 bg-gray-200 rounded-full h-2">
                                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($enrollment->progress_percentage ?? 0, 100) }}%"></div>
                                                        </div>
                                                        <span class="text-xs text-gray-600">{{ number_format($enrollment->progress_percentage ?? 0, 0) }}%</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center hidden md:table-cell">
                                                    @php
                                                        $enrollStatus = is_object($enrollment->status) ? $enrollment->status->value : $enrollment->status;
                                                    @endphp
                                                    <span class="text-xs px-2 py-1 rounded-full {{ match($enrollStatus) {
                                                        'enrolled', 'active' => 'bg-green-100 text-green-700',
                                                        'completed' => 'bg-blue-100 text-blue-700',
                                                        'cancelled' => 'bg-red-100 text-red-700',
                                                        default => 'bg-gray-100 text-gray-700',
                                                    } }}">{{ $enrollStatus }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
                <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.recorded_courses.course_info') }}</h3>
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <i class="ri-user-line text-gray-400"></i>
                        <span>{{ __('supervisor.recorded_courses.instructor') }}: {{ '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ri-money-dollar-circle-line text-gray-400"></i>
                        <span>{{ __('supervisor.recorded_courses.price') }}: {{ $course->formatted_price }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ri-folder-line text-gray-400"></i>
                        <span>{{ __('supervisor.recorded_courses.sections_count') }}: {{ $course->sections->count() }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ri-play-circle-line text-gray-400"></i>
                        <span>{{ __('supervisor.recorded_courses.lessons_count') }}: {{ $course->sections->sum(fn($s) => $s->lessons->count()) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="ri-user-star-line text-gray-400"></i>
                        <span>{{ __('supervisor.recorded_courses.enrollments_count') }}: {{ $course->enrollments->count() }}</span>
                    </div>
                    @if($course->total_duration_minutes)
                        <div class="flex items-center gap-2">
                            <i class="ri-time-line text-gray-400"></i>
                            <span>{{ __('supervisor.recorded_courses.duration') }}: {{ $course->duration_formatted }}</span>
                        </div>
                    @endif
                    @if($course->course_code)
                        <div class="flex items-center gap-2">
                            <i class="ri-barcode-line text-gray-400"></i>
                            <span>{{ __('supervisor.recorded_courses.course_code') }}: {{ $course->course_code }}</span>
                        </div>
                    @endif
                </div>
            </div>

            @if($course->learning_outcomes && count($course->learning_outcomes) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-5">
                    <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.recorded_courses.learning_outcomes') }}</h3>
                    <ul class="space-y-2">
                        @foreach($course->learning_outcomes as $outcome)
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <i class="ri-check-line text-green-500 mt-0.5 flex-shrink-0"></i>
                                <span>{{ $outcome }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>

</x-layouts.supervisor>
