<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $profile = $isQuranTeacher ? $teacher->quranTeacherProfile : $teacher->academicTeacherProfile;
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

        {{-- Teacher Info Grid (merged from sidebar) --}}
        @if(($profile?->educational_qualification ?? $profile?->education_level) || $profile?->teaching_experience_years || $teacher->created_at)
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @if($profile?->educational_qualification ?? $profile?->education_level)
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <i class="ri-graduation-cap-line text-gray-400"></i>
                            <span>{{ __('supervisor.teachers.qualification') }}: {{ $profile->educational_qualification ?? $profile->education_level }}</span>
                        </div>
                    @endif
                    @if($profile?->teaching_experience_years)
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <i class="ri-briefcase-line text-gray-400"></i>
                            <span>{{ __('supervisor.teachers.experience') }}: {{ $profile->teaching_experience_years }} {{ __('supervisor.teachers.years_experience') }}</span>
                        </div>
                    @endif
                    @if($teacher->created_at)
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <i class="ri-calendar-line text-gray-400"></i>
                            <span>{{ __('supervisor.teachers.joined_at') }}: {{ $teacher->created_at->translatedFormat('Y/m/d') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Password Reset Modal --}}
    @if($isAdmin)
        <x-responsive.modal id="reset-password-show-{{ $teacher->id }}" :title="__('supervisor.teachers.reset_password')" size="sm">
            <form method="POST" action="{{ route('manage.teachers.reset-password', ['subdomain' => $subdomain, 'teacher' => $teacher->id]) }}"
                  x-data="{ showPass: false, showConfirm: false }">
                @csrf
                <div class="space-y-4">
                    <p class="text-sm text-gray-600">{{ __('supervisor.teachers.reset_password_description', ['name' => $teacher->name]) }}</p>
                    <div>
                        <label for="new_password_show" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teachers.new_password') }}</label>
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" name="new_password" id="new_password_show"
                                   class="min-h-[44px] w-full px-3 py-2 pe-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                   placeholder="{{ __('supervisor.teachers.new_password_placeholder') }}"
                                   required minlength="6">
                            <button type="button" @click="showPass = !showPass"
                                class="cursor-pointer absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                                <i :class="showPass ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="new_password_confirmation_show" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.teachers.confirm_password') }}</label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'" name="new_password_confirmation" id="new_password_confirmation_show"
                                   class="min-h-[44px] w-full px-3 py-2 pe-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                   placeholder="{{ __('supervisor.teachers.confirm_password_placeholder') }}"
                                   required minlength="6">
                            <button type="button" @click="showConfirm = !showConfirm"
                                class="cursor-pointer absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600">
                                <i :class="showConfirm ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <x-slot:footer>
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" @click="open = false"
                            class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit"
                            class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700">
                            {{ __('supervisor.teachers.reset_password') }}
                        </button>
                    </div>
                </x-slot:footer>
            </form>
        </x-responsive.modal>
    @endif

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
                            <a href="{{ route('manage.academic-lessons.show', ['subdomain' => $subdomain, 'subscription' => $lesson->id]) }}"
                               class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="ri-graduation-cap-line text-violet-600 text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $lesson->subject?->name ?? '' }} - {{ $lesson->student?->name ?? '' }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ __('supervisor.teachers.academic_lesson') }}</p>
                                </div>
                                <i class="ri-arrow-left-s-line text-gray-400 rtl:rotate-180"></i>
                            </a>
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
                            <a href="{{ route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => $session['type'], 'sessionId' => $session['id']]) }}"
                               class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors cursor-pointer">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 {{ $session['type'] === 'quran' ? 'bg-yellow-100' : 'bg-violet-100' }}">
                                    <i class="{{ $session['type'] === 'quran' ? 'ri-book-read-line text-yellow-600' : 'ri-graduation-cap-line text-violet-600' }}"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $session['title'] }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $session['student_name'] }}
                                        &middot;
                                        {{ $session['date']?->translatedFormat('Y/m/d') }}
                                    </p>
                                </div>
                                @php $statusEnum = $session['status'] instanceof \App\Enums\SessionStatus ? $session['status'] : \App\Enums\SessionStatus::tryFrom(is_object($session['status']) ? $session['status']->value : $session['status']); @endphp
                                <span class="text-xs px-2 py-1 rounded-full {{ match($statusEnum?->color()) {
                                    'success' => 'bg-green-100 text-green-700',
                                    'info' => 'bg-blue-100 text-blue-700',
                                    'danger' => 'bg-red-100 text-red-700',
                                    'primary' => 'bg-cyan-100 text-cyan-700',
                                    'warning' => 'bg-amber-100 text-amber-700',
                                    'gray' => 'bg-gray-100 text-gray-700',
                                    default => 'bg-gray-100 text-gray-700',
                                } }}">{{ $statusEnum?->label() ?? $session['status'] }}</span>
                                <i class="ri-arrow-left-s-line text-gray-400"></i>
                            </a>
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
            <!-- Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('supervisor.teachers.actions') }}</h3>
                <div class="space-y-2">
                    {{-- View Entities --}}
                    @if($isQuranTeacher)
                        <a href="{{ route('manage.group-circles.index', ['subdomain' => $subdomain, 'teacher_id' => $teacher->id]) }}"
                           class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors">
                            <i class="ri-team-line"></i>
                            {{ __('supervisor.teachers.view_circles') }}
                        </a>
                        <a href="{{ route('manage.individual-circles.index', ['subdomain' => $subdomain, 'teacher_id' => $teacher->id]) }}"
                           class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-yellow-600 hover:bg-yellow-700 text-white transition-colors">
                            <i class="ri-user-line"></i>
                            {{ __('supervisor.teachers.view_individual_circles') }}
                        </a>
                        <a href="{{ route('manage.trial-sessions.index', ['subdomain' => $subdomain, 'teacher_id' => $teacher->id]) }}"
                           class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-orange-500 hover:bg-orange-600 text-white transition-colors">
                            <i class="ri-gift-line"></i>
                            {{ __('supervisor.teachers.view_trial_sessions') }}
                        </a>
                    @else
                        <a href="{{ route('manage.academic-lessons.index', ['subdomain' => $subdomain, 'teacher_id' => $teacher->id]) }}"
                           class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-violet-600 hover:bg-violet-700 text-white transition-colors">
                            <i class="ri-graduation-cap-line"></i>
                            {{ __('supervisor.teachers.view_lessons') }}
                        </a>
                        <a href="{{ route('manage.interactive-courses.index', ['subdomain' => $subdomain, 'teacher_id' => $teacher->id]) }}"
                           class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-purple-600 hover:bg-purple-700 text-white transition-colors">
                            <i class="ri-live-line"></i>
                            {{ __('supervisor.teachers.view_interactive_courses') }}
                        </a>
                    @endif

                    {{-- Sessions & Reports --}}
                    <a href="{{ route('manage.sessions.index', ['subdomain' => $subdomain, 'teacher_id' => $teacher->id]) }}"
                       class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                        <i class="ri-calendar-event-line"></i>
                        {{ __('supervisor.teachers.view_sessions') }}
                    </a>
                    <a href="{{ route('manage.session-reports.index', ['subdomain' => $subdomain, 'teacher_id' => $teacher->id]) }}"
                       class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-fuchsia-600 hover:bg-fuchsia-700 text-white transition-colors">
                        <i class="ri-file-chart-line"></i>
                        {{ __('supervisor.teachers.view_reports') }}
                    </a>
                    <a href="{{ route('manage.teacher-earnings.index', ['subdomain' => $subdomain, 'teacher_id' => $teacher->id]) }}"
                       class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                        <i class="ri-money-dollar-circle-line"></i>
                        {{ __('supervisor.teachers.view_earnings') }}
                    </a>
                    <a href="{{ route('chat.start-with', ['subdomain' => $subdomain, 'user' => $teacher->id]) }}"
                       class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors">
                        <i class="ri-message-3-line"></i>
                        {{ __('supervisor.teachers.message_teacher') }}
                    </a>

                    {{-- Admin-only actions --}}
                    @if($isAdmin)
                        <div class="border-t border-gray-100 pt-2 mt-2"></div>

                        {{-- Edit Teacher --}}
                        <a href="{{ route('manage.teachers.edit', ['subdomain' => $subdomain, 'teacher' => $teacher->id]) }}"
                           class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                            <i class="ri-edit-line"></i>
                            {{ __('common.edit') }}
                        </a>

                        {{-- Toggle Status --}}
                        <form id="toggle-form-{{ $teacher->id }}" method="POST"
                              action="{{ route('manage.teachers.toggle-status', ['subdomain' => $subdomain, 'teacher' => $teacher->id]) }}">
                            @csrf
                        </form>
                        <button type="button"
                            onclick="window.confirmAction({
                                title: @js($teacher->active_status ? __('supervisor.teachers.deactivate') : __('supervisor.teachers.activate')),
                                message: @js($teacher->active_status ? __('supervisor.teachers.confirm_deactivate') : __('supervisor.teachers.confirm_activate')),
                                confirmText: @js($teacher->active_status ? __('supervisor.teachers.deactivate') : __('supervisor.teachers.activate')),
                                isDangerous: {{ $teacher->active_status ? 'true' : 'false' }},
                                icon: '{{ $teacher->active_status ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}',
                                onConfirm: () => document.getElementById('toggle-form-{{ $teacher->id }}').submit()
                            })"
                            class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors
                                {{ $teacher->active_status
                                    ? 'bg-orange-50 text-orange-700 hover:bg-orange-100'
                                    : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                            <i class="{{ $teacher->active_status ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}"></i>
                            {{ $teacher->active_status ? __('supervisor.teachers.deactivate') : __('supervisor.teachers.activate') }}
                        </button>

                        {{-- Reset Password --}}
                        <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-modal-reset-password-show-{{ $teacher->id }}'))"
                            class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-yellow-50 text-yellow-700 hover:bg-yellow-100 transition-colors">
                            <i class="ri-lock-password-line"></i>
                            {{ __('supervisor.teachers.reset_password') }}
                        </button>

                        {{-- Delete --}}
                        <form id="delete-form-{{ $teacher->id }}" method="POST"
                              action="{{ route('manage.teachers.destroy', ['subdomain' => $subdomain, 'teacher' => $teacher->id]) }}">
                            @csrf
                            @method('DELETE')
                        </form>
                        <button type="button"
                            onclick="window.confirmAction({
                                title: @js(__('supervisor.teachers.delete_teacher')),
                                message: @js(__('supervisor.teachers.confirm_delete')),
                                confirmText: @js(__('supervisor.teachers.delete_teacher')),
                                isDangerous: true,
                                icon: 'ri-delete-bin-line',
                                onConfirm: () => document.getElementById('delete-form-{{ $teacher->id }}').submit()
                            })"
                            class="cursor-pointer w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-red-50 text-red-700 hover:bg-red-100 transition-colors">
                            <i class="ri-delete-bin-line"></i>
                            {{ __('supervisor.teachers.delete_teacher') }}
                        </button>
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
