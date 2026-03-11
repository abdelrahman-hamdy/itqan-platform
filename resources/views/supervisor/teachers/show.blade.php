<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.teachers.breadcrumb'), 'route' => route('manage.teachers.index', ['subdomain' => $subdomain])],
            ['label' => $teacher->name, 'truncate' => true],
        ]"
        view-type="supervisor"
    />

    <!-- Profile Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center gap-4">
            <x-avatar :user="$teacher" size="lg" :user-type="$isQuranTeacher ? 'quran_teacher' : 'academic_teacher'" />
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-bold text-gray-900">{{ $teacher->name }}</h2>
                <p class="text-gray-500 text-sm">{{ $teacher->email }}</p>
                @if($teacher->phone)
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="ri-phone-line text-gray-400"></i>
                        {{ $teacher->phone }}
                    </p>
                @endif
                <div class="flex flex-wrap gap-2 mt-2">
                    <span class="text-xs px-2.5 py-1 rounded-full {{ $isQuranTeacher ? 'bg-yellow-100 text-yellow-800' : 'bg-violet-100 text-violet-800' }}">
                        {{ $isQuranTeacher ? __('supervisor.teachers.teacher_type_quran') : __('supervisor.teachers.teacher_type_academic') }}
                    </span>
                    @php
                        $profile = $isQuranTeacher ? $teacher->quranTeacherProfile : $teacher->academicTeacherProfile;
                        $teacherCode = $profile?->teacher_code ?? '';
                    @endphp
                    @if($teacherCode)
                        <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ $teacherCode }}</span>
                    @endif
                    @if($profile?->rating)
                        <span class="text-xs px-2.5 py-1 rounded-full bg-amber-100 text-amber-800">
                            <i class="ri-star-fill text-amber-500"></i> {{ number_format($profile->rating, 1) }}
                        </span>
                    @endif
                    <span class="text-xs px-2.5 py-1 rounded-full {{ $teacher->active_status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $teacher->active_status ? __('supervisor.teachers.active') : __('supervisor.teachers.inactive') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-calendar-line text-blue-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $sessionsThisMonth }}</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.teachers.sessions_this_month') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-check-double-line text-green-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $completionRate }}%</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.teachers.completion_rate') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-user-3-line text-purple-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalStudents }}</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.teachers.total_students') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="ri-close-circle-line text-red-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $cancelledThisMonth }}</p>
                    <p class="text-xs text-gray-500">{{ __('supervisor.teachers.cancelled_this_month') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Assigned Entities -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('supervisor.teachers.assigned_entities') }}</h3>

                @if($assignedCircles->isNotEmpty() || $assignedIndividuals->isNotEmpty() || $assignedLessons->isNotEmpty() || $assignedCourses->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($assignedCircles as $circle)
                            <a href="{{ route('manage.group-circles.show', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}"
                               class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="ri-team-line text-yellow-600 text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $circle->name }}</p>
                                    <p class="text-xs text-gray-500">{{ __('supervisor.teachers.group_circle') }} &middot; {{ $circle->students_count }} {{ __('supervisor.teachers.students') }}</p>
                                </div>
                                <i class="ri-arrow-left-s-line text-gray-400 rtl:rotate-180"></i>
                            </a>
                        @endforeach

                        @foreach($assignedIndividuals as $ind)
                            <a href="{{ route('manage.individual-circles.show', ['subdomain' => $subdomain, 'circle' => $ind->id]) }}"
                               class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="ri-user-line text-yellow-600 text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $ind->student?->name ?? '' }}</p>
                                    <p class="text-xs text-gray-500">{{ __('supervisor.teachers.individual_circle') }}</p>
                                </div>
                                <i class="ri-arrow-left-s-line text-gray-400 rtl:rotate-180"></i>
                            </a>
                        @endforeach

                        @foreach($assignedLessons as $lesson)
                            <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200">
                                <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="ri-graduation-cap-line text-violet-600 text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $lesson->subject?->name ?? '' }} - {{ $lesson->student?->name ?? '' }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ __('supervisor.teachers.academic_lesson') }}</p>
                                </div>
                            </div>
                        @endforeach

                        @foreach($assignedCourses as $course)
                            <a href="{{ route('manage.interactive-courses.show', ['subdomain' => $subdomain, 'course' => $course->id]) }}"
                               class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="ri-live-line text-violet-600 text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $course->title }}</p>
                                    <p class="text-xs text-gray-500">{{ __('supervisor.teachers.interactive_course') }} &middot; {{ $course->enrollments->count() }} {{ __('supervisor.teachers.students') }}</p>
                                </div>
                                <i class="ri-arrow-left-s-line text-gray-400 rtl:rotate-180"></i>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="ri-folder-line text-2xl text-gray-400"></i>
                        </div>
                        <p class="text-sm text-gray-500">{{ __('supervisor.teachers.no_assigned_entities') }}</p>
                    </div>
                @endif
            </div>

            <!-- Recent Sessions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('supervisor.teachers.recent_sessions') }}</h3>

                @if($recentSessions->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($recentSessions as $session)
                            <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 {{ $session['type'] === 'quran' ? 'bg-yellow-100' : 'bg-violet-100' }}">
                                    <i class="{{ $session['type'] === 'quran' ? 'ri-book-read-line text-yellow-600' : 'ri-graduation-cap-line text-violet-600' }}"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $session['title'] }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $session['student_name'] }}
                                        &middot;
                                        {{ $session['date']?->format('Y/m/d') }}
                                    </p>
                                </div>
                                @php $sessionStatus = is_object($session['status']) ? $session['status']->value : $session['status']; @endphp
                                <span class="text-xs px-2 py-1 rounded-full {{ match($sessionStatus) {
                                    'completed' => 'bg-green-100 text-green-700',
                                    'scheduled' => 'bg-blue-100 text-blue-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                    'ongoing', 'live' => 'bg-orange-100 text-orange-700',
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
                        <p class="text-sm text-gray-500">{{ __('supervisor.teachers.no_recent_sessions') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Teacher Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.teachers.teacher_info') }}</h3>
                <div class="space-y-2 text-sm text-gray-600">
                    @if($profile?->educational_qualification ?? $profile?->education_level)
                        <div class="flex items-center gap-2">
                            <i class="ri-graduation-cap-line text-gray-400"></i>
                            {{ $profile->educational_qualification ?? $profile->education_level }}
                        </div>
                    @endif
                    @if($profile?->teaching_experience_years)
                        <div class="flex items-center gap-2">
                            <i class="ri-briefcase-line text-gray-400"></i>
                            {{ $profile->teaching_experience_years }} {{ __('supervisor.teachers.years_experience') }}
                        </div>
                    @endif
                    @if($teacher->created_at)
                        <div class="flex items-center gap-2">
                            <i class="ri-calendar-line text-gray-400"></i>
                            {{ __('supervisor.teachers.joined_at') }}: {{ $teacher->created_at->format('Y/m/d') }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Month Summary -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.teachers.month_summary') }}</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">{{ __('supervisor.teachers.total_sessions') }}</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $sessionsThisMonth }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">{{ __('supervisor.teachers.completed') }}</span>
                        <span class="text-sm font-semibold text-green-600">{{ $completedThisMonth }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">{{ __('supervisor.teachers.cancelled') }}</span>
                        <span class="text-sm font-semibold text-red-600">{{ $cancelledThisMonth }}</span>
                    </div>
                    <div class="pt-2 border-t border-gray-100 flex justify-between items-center">
                        <span class="text-sm text-gray-600">{{ __('supervisor.teachers.completion_rate') }}</span>
                        <span class="text-sm font-bold {{ $completionRate >= 80 ? 'text-green-600' : ($completionRate >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                            {{ $completionRate }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.supervisor>
